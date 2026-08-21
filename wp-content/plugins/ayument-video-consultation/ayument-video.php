<?php
/*
Plugin Name: AyuMent Video Consultation
Description: Secure doctor-patient video consultation using browser WebRTC and WordPress REST signaling.
Version: 2.0.0
Author: AyuMent
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AYUMENT_VC_VERSION', '2.0.0' );
define( 'AYUMENT_VC_NS', 'ayument/v1' );

/* =========================================================
 * DATABASE
 * ========================================================= */

function ayument_vc_rooms_table() {
    global $wpdb;
    return $wpdb->prefix . 'ayument_video_rooms';
}

function ayument_vc_signals_table() {
    global $wpdb;
    return $wpdb->prefix . 'ayument_video_signals';
}

function ayument_vc_install_tables() {
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset = $wpdb->get_charset_collate();
    $rooms   = ayument_vc_rooms_table();
    $signals = ayument_vc_signals_table();

    dbDelta( "CREATE TABLE {$rooms} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        appointment_id BIGINT(20) UNSIGNED NOT NULL,
        doctor_id BIGINT(20) UNSIGNED NOT NULL,
        patient_id BIGINT(20) UNSIGNED NOT NULL,
        room_token VARCHAR(128) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL,
        ended_at DATETIME NULL,
        last_activity DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY room_token (room_token),
        KEY appointment_id (appointment_id),
        KEY doctor_id (doctor_id),
        KEY patient_id (patient_id),
        KEY status (status)
    ) {$charset};" );

    dbDelta( "CREATE TABLE {$signals} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        room_token VARCHAR(128) NOT NULL,
        sender_role VARCHAR(20) NOT NULL,
        message_type VARCHAR(30) NOT NULL,
        payload LONGTEXT NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY room_token (room_token),
        KEY created_at (created_at)
    ) {$charset};" );

    update_option( 'ayument_vc_version', AYUMENT_VC_VERSION );
}

register_activation_hook( __FILE__, 'ayument_vc_install_tables' );

/* =========================================================
 * VIDEO ROOM PAGE
 * ========================================================= */

function ayument_vc_room_page() {
    $page_id = (int) get_option( 'ayument_vc_room_page_id' );
    if ( $page_id && get_post_status( $page_id ) ) {
        return $page_id;
    }

    $existing = get_page_by_path( 'ayument-video-room' );
    if ( $existing ) {
        update_option( 'ayument_vc_room_page_id', $existing->ID );
        return $existing->ID;
    }

    $page_id = wp_insert_post( array(
        'post_title'   => 'AyuMent Video Consultation',
        'post_name'    => 'ayument-video-room',
        'post_content' => '[ayument_video_room]',
        'post_status'  => 'publish',
        'post_type'    => 'page',
    ) );

    if ( $page_id && ! is_wp_error( $page_id ) ) {
        update_option( 'ayument_vc_room_page_id', $page_id );
        return (int) $page_id;
    }

    return 0;
}

register_activation_hook( __FILE__, 'ayument_vc_room_page' );

function ayument_vc_room_url( $token ) {
    $page_id = ayument_vc_room_page();
    $url = $page_id ? get_permalink( $page_id ) : home_url( '/' );
    return add_query_arg( array(
        'ayument_room' => rawurlencode( $token ),
    ), $url );
}

/* =========================================================
 * APPOINTMENT / ACCESS HELPERS
 * ========================================================= */

function ayument_vc_appointment( $appointment_id ) {
    global $wpdb;

    $table = $wpdb->prefix . 'ayument_appointments';
    $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );

    if ( $exists !== $table ) {
        return null;
    }

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE id=%d LIMIT 1",
            $appointment_id
        )
    );
}

function ayument_vc_patient( $patient_id ) {
    global $wpdb;

    $table = $wpdb->prefix . 'ayument_patients';
    $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );

    if ( $exists !== $table ) {
        return null;
    }

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE id=%d LIMIT 1",
            $patient_id
        )
    );
}

function ayument_vc_is_doctor() {
    if ( ! is_user_logged_in() ) {
        return false;
    }

    $id = get_current_user_id();
    $u  = get_userdata( $id );

    if ( ! $u ) {
        return false;
    }

    return user_can( $id, 'manage_options' )
        || in_array( 'ayument_doctor', (array) $u->roles, true );
}

function ayument_vc_patient_matches_user( $patient_id ) {
    if ( ! is_user_logged_in() ) {
        return false;
    }

    $patient = ayument_vc_patient( $patient_id );
    if ( ! $patient ) {
        return false;
    }

    $uid   = get_current_user_id();
    $user  = wp_get_current_user();
    $email = strtolower( trim( (string) $user->user_email ) );

    /* Preferred explicit mapping if another AyuMent module stores it. */
    foreach ( array( 'ayument_patient_id', 'patient_db_id', 'ayument_patient_db_id' ) as $key ) {
        $mapped = absint( get_user_meta( $uid, $key, true ) );
        if ( $mapped && $mapped === (int) $patient_id ) {
            return true;
        }
    }

    /* Existing patient records contain email, so use it as a fallback. */
    if ( ! empty( $patient->email ) && $email !== '' ) {
        return strtolower( trim( $patient->email ) ) === $email;
    }

    return false;
}

function ayument_vc_authorize_appointment( $appointment, $role ) {
    if ( ! $appointment || ! is_user_logged_in() ) {
        return false;
    }

    $uid = get_current_user_id();

    if ( 'doctor' === $role ) {
        return ayument_vc_is_doctor()
            && (int) $appointment->doctor_id === $uid;
    }

    if ( 'patient' === $role ) {
        return ayument_vc_patient_matches_user( (int) $appointment->patient_id );
    }

    return false;
}

function ayument_vc_cleanup() {
    global $wpdb;

    $rooms   = ayument_vc_rooms_table();
    $signals = ayument_vc_signals_table();
    $cutoff  = gmdate( 'Y-m-d H:i:s', time() - 6 * HOUR_IN_SECONDS );

    $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$rooms}
             SET status='ended', ended_at=UTC_TIMESTAMP()
             WHERE status='active' AND last_activity < %s",
            $cutoff
        )
    );

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$signals} WHERE created_at < %s",
            gmdate( 'Y-m-d H:i:s', time() - 12 * HOUR_IN_SECONDS )
        )
    );

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$rooms} WHERE status='ended' AND ended_at < %s",
            gmdate( 'Y-m-d H:i:s', time() - 2 * DAY_IN_SECONDS )
        )
    );
}

/* =========================================================
 * REST API
 * ========================================================= */

add_action( 'rest_api_init', function () {

    register_rest_route( AYUMENT_VC_NS, '/room/create', array(
        'methods'             => 'POST',
        'callback'            => 'ayument_vc_api_create_room',
        'permission_callback' => function () { return is_user_logged_in(); },
    ) );

    register_rest_route( AYUMENT_VC_NS, '/room/info', array(
        'methods'             => 'GET',
        'callback'            => 'ayument_vc_api_room_info',
        'permission_callback' => function () { return is_user_logged_in(); },
    ) );

    register_rest_route( AYUMENT_VC_NS, '/room/end', array(
        'methods'             => 'POST',
        'callback'            => 'ayument_vc_api_end_room',
        'permission_callback' => function () { return is_user_logged_in(); },
    ) );

    register_rest_route( AYUMENT_VC_NS, '/signal/send', array(
        'methods'             => 'POST',
        'callback'            => 'ayument_vc_api_signal_send',
        'permission_callback' => function () { return is_user_logged_in(); },
    ) );

    register_rest_route( AYUMENT_VC_NS, '/signal/poll', array(
        'methods'             => 'GET',
        'callback'            => 'ayument_vc_api_signal_poll',
        'permission_callback' => function () { return is_user_logged_in(); },
    ) );
} );

function ayument_vc_api_create_room( WP_REST_Request $request ) {
    global $wpdb;

    ayument_vc_cleanup();

    $appointment_id = absint( $request->get_param( 'appointment_id' ) );
    $appointment    = ayument_vc_appointment( $appointment_id );

    if ( ! ayument_vc_authorize_appointment( $appointment, 'doctor' ) ) {
        return new WP_Error( 'forbidden', 'You are not authorized for this appointment.', array( 'status' => 403 ) );
    }

    if ( in_array( $appointment->status, array( 'Cancelled', 'No-show' ), true ) ) {
        return new WP_Error( 'invalid_appointment', 'This appointment is not available for video consultation.', array( 'status' => 400 ) );
    }

    $rooms = ayument_vc_rooms_table();

    $existing = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$rooms}
             WHERE appointment_id=%d AND doctor_id=%d AND status='active'
             ORDER BY id DESC LIMIT 1",
            $appointment_id,
            get_current_user_id()
        )
    );

    if ( $existing ) {
        $wpdb->update(
            $rooms,
            array( 'last_activity' => current_time( 'mysql', true ) ),
            array( 'id' => $existing->id ),
            array( '%s' ),
            array( '%d' )
        );

        return rest_ensure_response( array(
            'success'       => true,
            'room_token'    => $existing->room_token,
            'room_url'      => ayument_vc_room_url( $existing->room_token ),
            'appointment_id'=> $appointment_id,
            'status'        => 'active',
        ) );
    }

    $token = wp_generate_password( 64, false, false );
    $now   = current_time( 'mysql', true );

    $ok = $wpdb->insert(
        $rooms,
        array(
            'appointment_id' => $appointment_id,
            'doctor_id'      => (int) $appointment->doctor_id,
            'patient_id'     => (int) $appointment->patient_id,
            'room_token'     => $token,
            'status'         => 'active',
            'created_at'     => $now,
            'last_activity'  => $now,
        ),
        array( '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
    );

    if ( false === $ok ) {
        return new WP_Error( 'db_error', 'Could not create video room.', array( 'status' => 500 ) );
    }

    return rest_ensure_response( array(
        'success'        => true,
        'room_token'     => $token,
        'room_url'       => ayument_vc_room_url( $token ),
        'appointment_id' => $appointment_id,
        'status'         => 'active',
    ) );
}

function ayument_vc_get_room_by_token( $token ) {
    global $wpdb;

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM " . ayument_vc_rooms_table() . " WHERE room_token=%s LIMIT 1",
            $token
        )
    );
}

function ayument_vc_room_role( $room ) {
    if ( ! $room || ! is_user_logged_in() ) {
        return false;
    }

    $uid = get_current_user_id();

    if ( (int) $room->doctor_id === $uid && ayument_vc_is_doctor() ) {
        return 'doctor';
    }

    if ( ayument_vc_patient_matches_user( (int) $room->patient_id ) ) {
        return 'patient';
    }

    return false;
}

function ayument_vc_api_room_info( WP_REST_Request $request ) {
    global $wpdb;

    $token = sanitize_text_field( (string) $request->get_param( 'room_token' ) );
    if ( strlen( $token ) < 32 ) {
        return new WP_Error( 'invalid_room', 'Invalid room.', array( 'status' => 400 ) );
    }

    $room = ayument_vc_get_room_by_token( $token );
    $role = ayument_vc_room_role( $room );

    if ( ! $role ) {
        return new WP_Error( 'forbidden', 'You are not authorized to join this consultation.', array( 'status' => 403 ) );
    }

    if ( 'active' !== $room->status ) {
        return new WP_Error( 'room_ended', 'This video consultation has ended.', array( 'status' => 410 ) );
    }

    $wpdb->update(
        ayument_vc_rooms_table(),
        array( 'last_activity' => current_time( 'mysql', true ) ),
        array( 'id' => $room->id ),
        array( '%s' ),
        array( '%d' )
    );

    $appointment = ayument_vc_appointment( (int) $room->appointment_id );
    $patient     = ayument_vc_patient( (int) $room->patient_id );

    return rest_ensure_response( array(
        'success'         => true,
        'role'            => $role,
        'room_token'      => $room->room_token,
        'appointment_id'  => (int) $room->appointment_id,
        'status'          => $room->status,
        'patient_name'    => $patient ? $patient->name : '',
        'appointment_date'=> $appointment ? $appointment->appointment_date : '',
        'appointment_time'=> $appointment ? $appointment->appointment_time : '',
    ) );
}

function ayument_vc_api_end_room( WP_REST_Request $request ) {
    global $wpdb;

    $token = sanitize_text_field( (string) $request->get_param( 'room_token' ) );
    $room  = ayument_vc_get_room_by_token( $token );
    $role  = ayument_vc_room_role( $room );

    if ( ! $role ) {
        return new WP_Error( 'forbidden', 'You are not authorized.', array( 'status' => 403 ) );
    }

    if ( 'doctor' !== $role ) {
        return new WP_Error( 'forbidden', 'Only the doctor can end the consultation.', array( 'status' => 403 ) );
    }

    $wpdb->update(
        ayument_vc_rooms_table(),
        array(
            'status'        => 'ended',
            'ended_at'      => current_time( 'mysql', true ),
            'last_activity' => current_time( 'mysql', true ),
        ),
        array( 'id' => $room->id ),
        array( '%s', '%s', '%s' ),
        array( '%d' )
    );

    return rest_ensure_response( array( 'success' => true ) );
}

function ayument_vc_api_signal_send( WP_REST_Request $request ) {
    global $wpdb;

    $token   = sanitize_text_field( (string) $request->get_param( 'room_token' ) );
    $type    = sanitize_key( (string) $request->get_param( 'type' ) );
    $payload = $request->get_param( 'payload' );

    $room = ayument_vc_get_room_by_token( $token );
    $role = ayument_vc_room_role( $room );

    if ( ! $role || 'active' !== $room->status ) {
        return new WP_Error( 'forbidden', 'Video room access denied.', array( 'status' => 403 ) );
    }

    $allowed = array( 'offer', 'answer', 'ice', 'hangup', 'ready' );
    if ( ! in_array( $type, $allowed, true ) ) {
        return new WP_Error( 'invalid_type', 'Invalid signaling message.', array( 'status' => 400 ) );
    }

    $json = is_string( $payload ) ? $payload : wp_json_encode( $payload );

    if ( strlen( $json ) > 200000 ) {
        return new WP_Error( 'payload_too_large', 'Signaling payload is too large.', array( 'status' => 413 ) );
    }

    $wpdb->insert(
        ayument_vc_signals_table(),
        array(
            'room_token'  => $token,
            'sender_role' => $role,
            'message_type'=> $type,
            'payload'     => $json,
            'created_at'  => current_time( 'mysql', true ),
        ),
        array( '%s', '%s', '%s', '%s', '%s' )
    );

    $wpdb->update(
        ayument_vc_rooms_table(),
        array( 'last_activity' => current_time( 'mysql', true ) ),
        array( 'id' => $room->id ),
        array( '%s' ),
        array( '%d' )
    );

    return rest_ensure_response( array( 'success' => true ) );
}

function ayument_vc_api_signal_poll( WP_REST_Request $request ) {
    global $wpdb;

    $token = sanitize_text_field( (string) $request->get_param( 'room_token' ) );
    $since = absint( $request->get_param( 'since' ) );

    $room = ayument_vc_get_room_by_token( $token );
    $role = ayument_vc_room_role( $room );

    if ( ! $role ) {
        return new WP_Error( 'forbidden', 'Video room access denied.', array( 'status' => 403 ) );
    }

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id,sender_role,message_type,payload,created_at
             FROM " . ayument_vc_signals_table() . "
             WHERE room_token=%s AND id>%d
             ORDER BY id ASC
             LIMIT 100",
            $token,
            $since
        )
    );

    $messages = array();

    foreach ( $rows as $row ) {
        if ( $row->sender_role === $role ) {
            continue;
        }

        $decoded = json_decode( $row->payload, true );

        $messages[] = array(
            'id'          => (int) $row->id,
            'sender_role' => $row->sender_role,
            'type'        => $row->message_type,
            'payload'     => null !== $decoded ? $decoded : $row->payload,
            'created_at'  => $row->created_at,
        );
    }

    $wpdb->update(
        ayument_vc_rooms_table(),
        array( 'last_activity' => current_time( 'mysql', true ) ),
        array( 'id' => $room->id ),
        array( '%s' ),
        array( '%d' )
    );

    return rest_ensure_response( array(
        'success'  => true,
        'messages' => $messages,
        'room_status' => $room->status,
    ) );
}

/* =========================================================
 * FRONT-END VIDEO ROOM
 * ========================================================= */

function ayument_vc_room_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<div class="ayument-vc-message">Please log in to join your video consultation.</div>';
    }

    $token = isset( $_GET['ayument_room'] )
        ? sanitize_text_field( wp_unslash( $_GET['ayument_room'] ) )
        : '';

    if ( ! $token ) {
        return '<div class="ayument-vc-message">No video consultation room was selected.</div>';
    }

    $nonce = wp_create_nonce( 'wp_rest' );
    $api   = esc_url_raw( rest_url( AYUMENT_VC_NS ) );

    ob_start();
    ?>
    <div class="ayument-vc-app" data-token="<?php echo esc_attr( $token ); ?>" data-api="<?php echo esc_attr( $api ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">
        <div class="ayument-vc-topbar">
            <div>
                <div class="ayument-vc-brand">AyuMent</div>
                <div class="ayument-vc-title">Video Consultation</div>
            </div>
            <div class="ayument-vc-status" data-status>Connecting…</div>
        </div>

        <div class="ayument-vc-layout">
            <main class="ayument-vc-stage">
                <div class="ayument-vc-video-wrap remote">
                    <video data-remote autoplay playsinline></video>
                    <div class="ayument-vc-placeholder" data-remote-placeholder>
                        <div class="ayument-vc-avatar">👤</div>
                        <div>Waiting for the other participant…</div>
                    </div>
                    <div class="ayument-vc-label">Doctor / Patient</div>
                </div>

                <div class="ayument-vc-video-wrap local">
                    <video data-local autoplay muted playsinline></video>
                    <div class="ayument-vc-local-label">You</div>
                </div>

                <div class="ayument-vc-controls">
                    <button type="button" data-mic>🎙️ Mute</button>
                    <button type="button" data-camera>📹 Camera</button>
                    <button type="button" data-screen>🖥️ Share screen</button>
                    <button type="button" data-end class="danger">☎ End call</button>
                </div>
            </main>

            <aside class="ayument-vc-info">
                <h3>Consultation</h3>
                <div data-info>Loading appointment…</div>
                <hr>
                <p><strong>Privacy</strong></p>
                <p class="small">Your audio and video are sent peer-to-peer through WebRTC. WordPress is used only for room authorization and signaling.</p>
                <div class="ayument-vc-error" data-error></div>
            </aside>
        </div>
    </div>

    <style>
        .ayument-vc-app{max-width:1400px;margin:25px auto;background:#071225;color:#fff;border-radius:22px;overflow:hidden;box-shadow:0 20px 60px rgba(15,23,42,.25);font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
        .ayument-vc-topbar{display:flex;justify-content:space-between;align-items:center;padding:18px 22px;background:linear-gradient(135deg,#172554,#2563eb)}
        .ayument-vc-brand{font-weight:800;font-size:13px;opacity:.8;text-transform:uppercase;letter-spacing:.08em}
        .ayument-vc-title{font-size:22px;font-weight:700}
        .ayument-vc-status{background:rgba(255,255,255,.15);padding:8px 13px;border-radius:999px;font-size:13px}
        .ayument-vc-layout{display:grid;grid-template-columns:minmax(0,1fr) 300px;min-height:650px}
        .ayument-vc-stage{position:relative;background:#020617;min-height:650px;padding:18px}
        .ayument-vc-video-wrap{position:relative;background:#111827;border-radius:18px;overflow:hidden;border:1px solid rgba(255,255,255,.08)}
        .ayument-vc-video-wrap.remote{height:calc(100% - 82px);min-height:520px}
        .ayument-vc-video-wrap video{width:100%;height:100%;object-fit:cover;background:#020617}
        .ayument-vc-video-wrap.local{position:absolute;right:34px;top:34px;width:230px;height:160px;z-index:3;box-shadow:0 12px 35px rgba(0,0,0,.45)}
        .ayument-vc-label,.ayument-vc-local-label{position:absolute;left:12px;bottom:10px;background:rgba(0,0,0,.55);padding:5px 9px;border-radius:8px;font-size:12px}
        .ayument-vc-placeholder{position:absolute;inset:0;display:flex;flex-direction:column;justify-content:center;align-items:center;gap:12px;color:#94a3b8}
        .ayument-vc-avatar{font-size:55px}
        .ayument-vc-controls{height:64px;display:flex;justify-content:center;align-items:center;gap:9px;flex-wrap:wrap}
        .ayument-vc-controls button{border:0;border-radius:10px;padding:10px 14px;background:#1e293b;color:#fff;font-weight:700;cursor:pointer}
        .ayument-vc-controls button:hover{background:#334155}
        .ayument-vc-controls button.danger{background:#dc2626}
        .ayument-vc-info{background:#fff;color:#172554;padding:25px}
        .ayument-vc-info h3{margin-top:0;font-size:22px}
        .ayument-vc-info p{color:#475569;line-height:1.55}
        .ayument-vc-info .small{font-size:13px}
        .ayument-vc-error{color:#b91c1c;background:#fff1f2;padding:10px;border-radius:9px;margin-top:15px;display:none}
        .ayument-vc-message{max-width:900px;margin:40px auto;padding:25px;border:1px solid #e2e8f0;border-radius:16px;background:#fff}
        @media(max-width:850px){
            .ayument-vc-layout{grid-template-columns:1fr}
            .ayument-vc-info{display:none}
            .ayument-vc-stage{min-height:70vh}
            .ayument-vc-video-wrap.remote{min-height:60vh}
            .ayument-vc-video-wrap.local{width:145px;height:105px;right:28px;top:28px}
        }
    </style>

    <script>
    (function(){
        const app = document.querySelector('.ayument-vc-app');
        if (!app || app.dataset.started === '1') return;
        app.dataset.started = '1';

        const token = app.dataset.token;
        const api = app.dataset.api.replace(/\/$/,'');
        const nonce = app.dataset.nonce;
        const localVideo = app.querySelector('[data-local]');
        const remoteVideo = app.querySelector('[data-remote]');
        const remotePlaceholder = app.querySelector('[data-remote-placeholder]');
        const statusEl = app.querySelector('[data-status]');
        const infoEl = app.querySelector('[data-info]');
        const errorEl = app.querySelector('[data-error]');
        const micBtn = app.querySelector('[data-mic]');
        const camBtn = app.querySelector('[data-camera]');
        const screenBtn = app.querySelector('[data-screen]');
        const endBtn = app.querySelector('[data-end]');

        let role = null;
        let pc = null;
        let localStream = null;
        let screenStream = null;
        let pollTimer = null;
        let signalId = 0;
        let remoteDescriptionSet = false;
        let pendingIce = [];
        let callEnded = false;

        const rtcConfig = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ]
        };

        function setStatus(text){ statusEl.textContent = text; }
        function showError(text){
            errorEl.textContent = text;
            errorEl.style.display = 'block';
        }

        async function request(path, options = {}){
            const opts = Object.assign({}, options);
            opts.headers = Object.assign({
                'X-WP-Nonce': nonce,
                'Content-Type': 'application/json'
            }, options.headers || {});
            const res = await fetch(api + path, opts);
            let data = {};
            try { data = await res.json(); } catch(e){}
            if (!res.ok) throw new Error(data.message || 'Request failed.');
            return data;
        }

        async function sendSignal(type, payload){
            return request('/signal/send', {
                method:'POST',
                body:JSON.stringify({
                    room_token:token,
                    type:type,
                    payload:payload
                })
            });
        }

        function makePeer(){
            pc = new RTCPeerConnection(rtcConfig);

            pc.ontrack = function(event){
                if (event.streams && event.streams[0]) {
                    remoteVideo.srcObject = event.streams[0];
                    remotePlaceholder.style.display = 'none';
                    setStatus('Connected');
                }
            };

            pc.onicecandidate = function(event){
                if (event.candidate) sendSignal('ice', event.candidate.toJSON()).catch(()=>{});
            };

            pc.onconnectionstatechange = function(){
                if (!pc) return;
                const state = pc.connectionState;
                if (state === 'connected') setStatus('Connected');
                else if (state === 'connecting') setStatus('Connecting…');
                else if (state === 'disconnected') setStatus('Connection interrupted');
                else if (state === 'failed') setStatus('Connection failed');
                else if (state === 'closed') setStatus('Call ended');
            };

            if (localStream) {
                localStream.getTracks().forEach(track => pc.addTrack(track, localStream));
            }
        }

        async function flushIce(){
            if (!pc || !remoteDescriptionSet) return;
            while(pendingIce.length){
                const c = pendingIce.shift();
                try { await pc.addIceCandidate(c); } catch(e){}
            }
        }

        async function createOffer(){
            if (role !== 'doctor' || !pc) return;
            setStatus('Calling patient…');
            const offer = await pc.createOffer();
            await pc.setLocalDescription(offer);
            await sendSignal('offer', { type:offer.type, sdp:offer.sdp });
        }

        async function handleMessage(message){
            signalId = Math.max(signalId, Number(message.id || 0));

            if (message.type === 'offer' && role === 'patient') {
                if (!pc) makePeer();
                await pc.setRemoteDescription(new RTCSessionDescription(message.payload));
                remoteDescriptionSet = true;
                await flushIce();
                const answer = await pc.createAnswer();
                await pc.setLocalDescription(answer);
                await sendSignal('answer', { type:answer.type, sdp:answer.sdp });
                setStatus('Connecting to doctor…');
                return;
            }

            if (message.type === 'answer' && role === 'doctor') {
                if (!pc) return;
                await pc.setRemoteDescription(new RTCSessionDescription(message.payload));
                remoteDescriptionSet = true;
                await flushIce();
                setStatus('Connecting to patient…');
                return;
            }

            if (message.type === 'ice') {
                const candidate = new RTCIceCandidate(message.payload);
                if (pc && remoteDescriptionSet) {
                    try { await pc.addIceCandidate(candidate); } catch(e){}
                } else {
                    pendingIce.push(candidate);
                }
                return;
            }

            if (message.type === 'ready' && role === 'doctor') {
                if (pc && pc.signalingState === 'stable') {
                    await createOffer();
                }
                return;
            }

            if (message.type === 'hangup') {
                setStatus('The other participant ended the call.');
                closeLocal(false);
            }
        }

        async function poll(){
            if (callEnded) return;
            try {
                const data = await request('/signal/poll?room_token=' + encodeURIComponent(token) + '&since=' + signalId, {
                    method:'GET',
                    headers:{'Content-Type':'application/json'}
                });
                if (data.room_status && data.room_status !== 'active') {
                    setStatus('Consultation ended');
                    closeLocal(false);
                    return;
                }
                for (const message of (data.messages || [])) {
                    try { await handleMessage(message); } catch(e){ console.warn(e); }
                }
            } catch(e) {
                if (!callEnded) showError(e.message);
            }
            pollTimer = setTimeout(poll, 800);
        }

        async function start(){
            try {
                setStatus('Checking consultation…');
                const room = await request('/room/info?room_token=' + encodeURIComponent(token), {
                    method:'GET',
                    headers:{'Content-Type':'application/json'}
                });

                role = room.role;
                infoEl.innerHTML =
                    '<strong>' + (role === 'doctor' ? 'Doctor' : 'Patient') + '</strong><br>' +
                    'Appointment: ' + (room.appointment_date || '—') + '<br>' +
                    'Time: ' + (room.appointment_time || '—') + '<br>' +
                    'Patient: ' + (room.patient_name || '—');

                setStatus('Requesting camera and microphone…');

                localStream = await navigator.mediaDevices.getUserMedia({
                    video:true,
                    audio:true
                });

                localVideo.srcObject = localStream;
                makePeer();

                await sendSignal('ready', { at:Date.now() });

                if (role === 'doctor') {
                    await createOffer();
                } else {
                    setStatus('Waiting for doctor…');
                }

                poll();
            } catch(e){
                showError(e.message || 'Could not start video consultation.');
                setStatus('Unable to connect');
            }
        }

        function closeLocal(sendHangup){
            if (callEnded) return;
            callEnded = true;
            if (pollTimer) clearTimeout(pollTimer);
            if (sendHangup) sendSignal('hangup', { at:Date.now() }).catch(()=>{});
            if (pc) {
                try { pc.close(); } catch(e){}
                pc = null;
            }
            if (localStream) {
                localStream.getTracks().forEach(t => t.stop());
                localStream = null;
            }
            if (screenStream) {
                screenStream.getTracks().forEach(t => t.stop());
                screenStream = null;
            }
            setStatus('Call ended');
        }

        micBtn.addEventListener('click', function(){
            if (!localStream) return;
            const track = localStream.getAudioTracks()[0];
            if (!track) return;
            track.enabled = !track.enabled;
            micBtn.textContent = track.enabled ? '🎙️ Mute' : '🔇 Unmute';
        });

        camBtn.addEventListener('click', function(){
            if (!localStream) return;
            const track = localStream.getVideoTracks()[0];
            if (!track) return;
            track.enabled = !track.enabled;
            camBtn.textContent = track.enabled ? '📹 Camera' : '📷 Camera off';
        });

        screenBtn.addEventListener('click', async function(){
            if (!pc || !localStream) return;

            try {
                if (!screenStream) {
                    screenStream = await navigator.mediaDevices.getDisplayMedia({ video:true });
                    const screenTrack = screenStream.getVideoTracks()[0];
                    const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                    if (sender) await sender.replaceTrack(screenTrack);

                    screenTrack.onended = async function(){
                        if (!localStream) return;
                        const cameraTrack = localStream.getVideoTracks()[0];
                        const sender2 = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                        if (sender2 && cameraTrack) await sender2.replaceTrack(cameraTrack);
                        screenStream = null;
                        screenBtn.textContent = '🖥️ Share screen';
                    };

                    screenBtn.textContent = '🛑 Stop sharing';
                } else {
                    screenStream.getTracks().forEach(t => t.stop());
                    screenStream = null;
                    const cameraTrack = localStream.getVideoTracks()[0];
                    const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                    if (sender && cameraTrack) await sender.replaceTrack(cameraTrack);
                    screenBtn.textContent = '🖥️ Share screen';
                }
            } catch(e) {
                console.warn(e);
            }
        });

        endBtn.addEventListener('click', async function(){
            if (!confirm('End this video consultation?')) return;

            if (role === 'doctor') {
                try {
                    await request('/room/end', {
                        method:'POST',
                        body:JSON.stringify({room_token:token})
                    });
                } catch(e){}
            }

            closeLocal(true);
        });

        start();
    })();
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode( 'ayument_video_room', 'ayument_vc_room_shortcode' );

/* =========================================================
 * DOCTOR PORTAL: VIDEO BUTTONS
 * ========================================================= */

function ayument_vc_doctor_button( $appointment, $label = '🎥 Start Video Consultation' ) {
    if ( ! $appointment ) return '';

    $appointment_id = absint( $appointment->id );
    $url = wp_nonce_url(
        add_query_arg(
            array(
                'ayument_vc_start' => $appointment_id,
            ),
            get_permalink( ayument_vc_room_page() )
        ),
        'ayument_vc_start_' . $appointment_id
    );

    return '<a href="' . esc_url( $url ) . '" class="ayument-vc-start-button">' . esc_html( $label ) . '</a>';
}

function ayument_vc_doctor_portal_integration( $content ) {
    if ( is_admin() || ! is_user_logged_in() || ! ayument_vc_is_doctor() ) {
        return $content;
    }

    $view = isset( $_GET['doctor_view'] )
        ? sanitize_key( wp_unslash( $_GET['doctor_view'] ) )
        : '';

    /* Patient profile workspace: add a direct video-call control to the
     * patient's profile page (for example Manish). The latest non-cancelled
     * appointment is used so the button is tied to the correct patient.
     */
    if ( 'patients' === $view && ! empty( $_GET['patient_id'] ) ) {
        global $wpdb;
        $patient_id = absint( $_GET['patient_id'] );
        $doctor_id  = get_current_user_id();
        $table      = $wpdb->prefix . 'ayument_appointments';
        $rooms      = ayument_vc_rooms_table();

        $appointment = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT a.*, r.room_token, r.status AS room_status
                 FROM {$table} a
                 LEFT JOIN {$rooms} r ON r.appointment_id=a.id AND r.status='active'
                 WHERE a.patient_id=%d AND a.doctor_id=%d AND a.status<>'Cancelled'
                 ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.id DESC
                 LIMIT 1",
                $patient_id,
                $doctor_id
            )
        );

        if ( $appointment ) {
            $patient_name = ! empty( $appointment->patient_name )
                ? $appointment->patient_name
                : 'Patient';

            $box  = '<div class="ayument-vc-portal-box ayument-vc-profile-video-box">';
            $box .= '<div><strong>🎥 Video Consultation</strong>';
            $box .= '<span>Start a secure call for ' . esc_html( $patient_name ) . ' or share the patient join link.</span></div>';
            $box .= ayument_vc_doctor_button( $appointment, '🎥 Start Video Call' );

            if ( ! empty( $appointment->room_token ) ) {
                $patient_url = ayument_vc_room_url( $appointment->room_token );
                $box .= '<div class="ayument-vc-patient-link-row">';
                $box .= '<input type="text" readonly value="' . esc_attr( $patient_url ) . '" class="ayument-vc-patient-link" id="ayument-vc-patient-link">';
                $box .= '<button type="button" class="ayument-vc-copy-link" onclick="navigator.clipboard.writeText(document.getElementById(\'ayument-vc-patient-link\').value).then(function(){this.textContent=\'✓ Copied\';}.bind(this))">Copy Patient Link</button>';
                $box .= '</div>';
            }

            $box .= '</div>';
            return $box . $content;
        }

        return $content;
    }

    if ( 'appointments' !== $view && 'consultations' !== $view ) {
        return $content;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ayument_appointments';

    if ( 'consultations' === $view && ! empty( $_GET['patient_id'] ) ) {
        $patient_id = absint( $_GET['patient_id'] );
        $doctor_id  = get_current_user_id();

        $appointment = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE patient_id=%d AND doctor_id=%d AND status<>'Cancelled'
                 ORDER BY appointment_date DESC, appointment_time DESC, id DESC
                 LIMIT 1",
                $patient_id,
                $doctor_id
            )
        );

        if ( $appointment ) {
            $box = '<div class="ayument-vc-portal-box">
                <div>
                    <strong>🎥 Video Consultation</strong>
                    <span>Start a secure video call for this patient.</span>
                </div>
                ' . ayument_vc_doctor_button( $appointment ) . '
            </div>';

            /* Put the video control BEFORE the consultation form so it is
             * immediately visible at the top of the doctor's consultation
             * workspace. The actual room is still created only after the
             * doctor clicks the button.
             */
            return $box . $content;
        }

        return $content;
    }

    /* Appointment workspace: show a compact video area below the existing list. */
    $doctor_id = get_current_user_id();

    $upcoming = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE doctor_id=%d
               AND status NOT IN ('Cancelled','Completed','No-show')
             ORDER BY appointment_date ASC, appointment_time ASC
             LIMIT 20",
            $doctor_id
        )
    );

    if ( empty( $upcoming ) ) {
        return $content;
    }

    ob_start();
    ?>
    <div class="ayument-vc-portal-box ayument-vc-appointments-box">
        <div>
            <strong>🎥 Video Consultations</strong>
            <span>Start a secure browser video call for an upcoming appointment.</span>
        </div>
        <div class="ayument-vc-appointment-links">
            <?php foreach ( $upcoming as $appointment ) : ?>
                <div class="ayument-vc-appointment-link">
                    <span>
                        <strong><?php echo esc_html( $appointment->patient_name ); ?></strong>
                        · <?php echo esc_html( mysql2date( 'd M Y', $appointment->appointment_date ) ); ?>
                        · <?php echo esc_html( date_i18n( 'g:i a', strtotime( $appointment->appointment_time ) ) ); ?>
                    </span>
                    <?php echo ayument_vc_doctor_button( $appointment, '🎥 Start Call' ); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php

    return $content . ob_get_clean();
}

add_filter( 'the_content', 'ayument_vc_doctor_portal_integration', 45 );

/* =========================================================
 * START-ROOM REDIRECT
 * ========================================================= */

function ayument_vc_handle_start_request() {
    if ( empty( $_GET['ayument_vc_start'] ) || ! is_user_logged_in() ) {
        return;
    }

    $appointment_id = absint( $_GET['ayument_vc_start'] );
    $nonce = isset( $_GET['_wpnonce'] )
        ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) )
        : '';

    if ( ! wp_verify_nonce( $nonce, 'ayument_vc_start_' . $appointment_id ) ) {
        return;
    }

    if ( ! ayument_vc_is_doctor() ) {
        return;
    }

    $appointment = ayument_vc_appointment( $appointment_id );

    if ( ! ayument_vc_authorize_appointment( $appointment, 'doctor' ) ) {
        return;
    }

    $request = new WP_REST_Request( 'POST', '/' . AYUMENT_VC_NS . '/room/create' );
    $request->set_param( 'appointment_id', $appointment_id );

    $response = ayument_vc_api_create_room( $request );

    if ( is_wp_error( $response ) ) {
        wp_die( esc_html( $response->get_error_message() ) );
    }

    $data = $response->get_data();

    if ( ! empty( $data['room_url'] ) ) {
        wp_safe_redirect( $data['room_url'] );
        exit;
    }
}
add_action( 'template_redirect', 'ayument_vc_handle_start_request', 1 );

/* =========================================================
 * PATIENT PORTAL: SHOW ACTIVE VIDEO CALLS
 *
 * Shortcode:
 * [ayument_patient_video_consultations]
 *
 * It can also be automatically appended to patient portal
 * pages using ?patient_view=appointments or ?patient_view=dashboard.
 * ========================================================= */

function ayument_vc_patient_portal_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<div class="ayument-vc-message">Please log in to view your video consultations.</div>';
    }

    global $wpdb;

    $patient_table = $wpdb->prefix . 'ayument_patients';
    $appt_table    = $wpdb->prefix . 'ayument_appointments';
    $rooms_table   = ayument_vc_rooms_table();

    $user = wp_get_current_user();
    $email = strtolower( trim( (string) $user->user_email ) );

    $patient = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$patient_table}
             WHERE LOWER(email)=LOWER(%s)
             LIMIT 1",
            $email
        )
    );

    if ( ! $patient ) {
        $mapped = 0;
        foreach ( array( 'ayument_patient_id', 'patient_db_id', 'ayument_patient_db_id' ) as $key ) {
            $candidate = absint( get_user_meta( get_current_user_id(), $key, true ) );
            if ( $candidate ) {
                $mapped = $candidate;
                break;
            }
        }
        if ( $mapped ) {
            $patient = ayument_vc_patient( $mapped );
        }
    }

    if ( ! $patient ) {
        return '<div class="ayument-vc-message">Your AyuMent patient account is not linked to a patient record yet.</div>';
    }

    $appointments = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT a.*,r.room_token,r.status AS room_status
             FROM {$appt_table} a
             LEFT JOIN {$rooms_table} r
               ON r.appointment_id=a.id AND r.status='active'
             WHERE a.patient_id=%d
               AND a.status NOT IN ('Cancelled','No-show')
             ORDER BY a.appointment_date DESC,a.appointment_time DESC
             LIMIT 20",
            $patient->id
        )
    );

    ob_start();
    ?>
    <div class="ayument-vc-patient-card">
        <div class="ayument-vc-patient-hero">
            <div>
                <div class="eyebrow">AyuMent</div>
                <h2>🎥 Video Consultations</h2>
                <p>Join an active consultation with your doctor from your browser.</p>
            </div>
        </div>

        <?php if ( empty( $appointments ) ) : ?>
            <div class="ayument-vc-patient-empty">No appointments available for video consultation.</div>
        <?php else : ?>
            <div class="ayument-vc-patient-list">
                <?php foreach ( $appointments as $appointment ) : ?>
                    <div class="ayument-vc-patient-row">
                        <div>
                            <strong><?php echo esc_html( $appointment->patient_name ); ?></strong>
                            <div><?php echo esc_html( mysql2date( 'd M Y', $appointment->appointment_date ) ); ?> · <?php echo esc_html( date_i18n( 'g:i a', strtotime( $appointment->appointment_time ) ) ); ?></div>
                            <small><?php echo esc_html( $appointment->appointment_type ); ?></small>
                        </div>

                        <?php if ( ! empty( $appointment->room_token ) ) : ?>
                            <a class="ayument-vc-join" href="<?php echo esc_url( ayument_vc_room_url( $appointment->room_token ) ); ?>">Join Video Call</a>
                        <?php else : ?>
                            <span class="ayument-vc-wait">Waiting for doctor</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .ayument-vc-patient-card{max-width:1100px;margin:25px auto;background:#fff;border:1px solid #e2e8f0;border-radius:20px;overflow:hidden;box-shadow:0 8px 25px rgba(15,23,42,.06)}
        .ayument-vc-patient-hero{padding:28px 32px;background:linear-gradient(135deg,#172554,#2563eb);color:#fff}
        .ayument-vc-patient-hero h2{margin:5px 0 6px;color:#fff;font-size:28px}
        .ayument-vc-patient-hero p{margin:0;color:#dbeafe}
        .ayument-vc-patient-hero .eyebrow{font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;opacity:.8}
        .ayument-vc-patient-list{padding:15px 25px}
        .ayument-vc-patient-row{display:flex;justify-content:space-between;align-items:center;gap:15px;padding:18px 0;border-bottom:1px solid #eef2f7}
        .ayument-vc-patient-row:last-child{border-bottom:0}
        .ayument-vc-patient-row strong{font-size:17px;color:#172554}
        .ayument-vc-patient-row div div{color:#475569;margin-top:4px}
        .ayument-vc-patient-row small{color:#64748b}
        .ayument-vc-join{display:inline-flex;padding:10px 15px;border-radius:10px;background:#2563eb;color:#fff!important;text-decoration:none!important;font-weight:700}
        .ayument-vc-wait{padding:9px 12px;border-radius:999px;background:#fef3c7;color:#92400e;font-size:13px;font-weight:700}
        .ayument-vc-patient-empty{padding:30px;color:#64748b}
        @media(max-width:700px){.ayument-vc-patient-row{align-items:flex-start;flex-direction:column}.ayument-vc-join{width:100%;justify-content:center}}
    </style>
    <?php

    return ob_get_clean();
}

add_shortcode( 'ayument_patient_video_consultations', 'ayument_vc_patient_portal_shortcode' );

function ayument_vc_patient_portal_integration( $content ) {
    if ( is_admin() || ! is_user_logged_in() ) {
        return $content;
    }

    if ( has_shortcode( $content, 'ayument_patient_video_consultations' ) ) {
        return $content;
    }

    $view = isset( $_GET['patient_view'] )
        ? sanitize_key( wp_unslash( $_GET['patient_view'] ) )
        : '';

    if ( in_array( $view, array( 'appointments', 'dashboard', 'consultations' ), true ) ) {
        return $content . do_shortcode( '[ayument_patient_video_consultations]' );
    }

    return $content;
}
add_filter( 'the_content', 'ayument_vc_patient_portal_integration', 46 );

/* =========================================================
 * SHARED CSS FOR PORTAL BUTTONS
 * ========================================================= */

add_action( 'wp_head', function() {
    ?>
    <style>
        .ayument-vc-portal-box{margin:0 0 22px;padding:18px 22px;border:1px solid #bfdbfe;border-radius:16px;background:linear-gradient(135deg,#eff6ff,#ffffff);box-shadow:0 8px 25px rgba(15,23,42,.06)}
        .ayument-vc-portal-box>div:first-child{display:flex;flex-direction:column;gap:4px;margin-bottom:14px}
        .ayument-vc-portal-box strong{color:#172554}
        .ayument-vc-portal-box span{color:#64748b;font-size:13px}
        .ayument-vc-start-button{display:inline-flex;align-items:center;justify-content:center;padding:10px 15px;border-radius:10px;background:#2563eb;color:#fff!important;text-decoration:none!important;font-weight:700}
        .ayument-vc-start-button:hover{background:#1d4ed8}
        .ayument-vc-appointment-links{display:flex;flex-direction:column;gap:9px}
        .ayument-vc-patient-link-row{display:flex;gap:8px;align-items:center;margin-top:12px;max-width:100%}
        .ayument-vc-patient-link{flex:1;min-width:0;padding:9px 11px;border:1px solid #cbd5e1;border-radius:9px;background:#fff;color:#475569;font-size:12px}
        .ayument-vc-copy-link{border:0;padding:10px 13px;border-radius:9px;background:#0f766e;color:#fff;font-weight:700;cursor:pointer;white-space:nowrap}
        .ayument-vc-copy-link:hover{background:#115e59}
        @media(max-width:700px){.ayument-vc-patient-link-row{align-items:stretch;flex-direction:column}.ayument-vc-copy-link{width:100%}}
        .ayument-vc-appointment-link{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:11px 13px;background:#f8fafc;border-radius:11px}
        .ayument-vc-appointment-link>span{color:#334155!important}
        @media(max-width:700px){.ayument-vc-appointment-link{align-items:flex-start;flex-direction:column}}
    </style>
    <?php
} );
