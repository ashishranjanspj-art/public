<?php
/**
 * AyuMent Appointments Module
 * Version 2.0
 *
 * Keeps the existing admin appointment manager and adds a doctor-facing
 * appointments workspace through the shortcode:
 * [ayument_doctor_appointments]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================
 * DATABASE
 * ========================================================= */

function ayument_appointments_create_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'ayument_appointments';
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        patient_id bigint(20) unsigned NOT NULL DEFAULT 0,
        patient_name varchar(255) NOT NULL DEFAULT '',
        doctor_id bigint(20) unsigned NOT NULL DEFAULT 0,
        doctor_name varchar(255) NOT NULL DEFAULT '',
        appointment_date date NOT NULL,
        appointment_time time NOT NULL,
        appointment_type varchar(100) NOT NULL DEFAULT 'General Consultation',
        status varchar(50) NOT NULL DEFAULT 'Scheduled',
        reason text NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY patient_id (patient_id),
        KEY doctor_id (doctor_id),
        KEY appointment_date (appointment_date),
        KEY status (status)
    ) {$charset_collate};";

    dbDelta( $sql );

    // Safe upgrades for an already-existing table.
    $doctor_id_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SHOW COLUMNS FROM {$table_name} LIKE %s",
            'doctor_id'
        )
    );

    if ( ! $doctor_id_exists ) {
        $wpdb->query(
            "ALTER TABLE {$table_name}
             ADD doctor_id bigint(20) unsigned NOT NULL DEFAULT 0 AFTER patient_name"
        );
    }

    $doctor_name_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SHOW COLUMNS FROM {$table_name} LIKE %s",
            'doctor_name'
        )
    );

    if ( ! $doctor_name_exists ) {
        $wpdb->query(
            "ALTER TABLE {$table_name}
             ADD doctor_name varchar(255) NOT NULL DEFAULT '' AFTER doctor_id"
        );
    }
}

add_action( 'admin_init', 'ayument_appointments_create_table' );
add_action( 'init', 'ayument_appointments_create_table', 20 );


/* =========================================================
 * PATIENT HELPERS
 * ========================================================= */

function ayument_appointments_get_patients() {
    global $wpdb;

    $possible_tables = array(
        $wpdb->prefix . 'ayument_patients',
        $wpdb->prefix . 'ayument_patient',
    );

    foreach ( $possible_tables as $table ) {

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table
            )
        );

        if ( $exists !== $table ) {
            continue;
        }

        $columns = $wpdb->get_results( "SHOW COLUMNS FROM {$table}" );
        $column_names = array();

        foreach ( $columns as $column ) {
            $column_names[] = $column->Field;
        }

        $id_column = in_array( 'id', $column_names, true )
            ? 'id'
            : ( in_array( 'patient_id', $column_names, true ) ? 'patient_id' : '' );

        if ( ! $id_column ) {
            continue;
        }

        $name_column = '';

        foreach ( array( 'name', 'patient_name', 'full_name' ) as $candidate ) {
            if ( in_array( $candidate, $column_names, true ) ) {
                $name_column = $candidate;
                break;
            }
        }

        if ( ! $name_column ) {
            continue;
        }

        $results = $wpdb->get_results(
            "SELECT * FROM {$table} ORDER BY {$name_column} ASC"
        );

        return array(
            'table'       => $table,
            'id_column'   => $id_column,
            'name_column' => $name_column,
            'patients'    => $results,
        );
    }

    return array(
        'table'       => '',
        'id_column'   => '',
        'name_column' => '',
        'patients'    => array(),
    );
}

function ayument_appointments_get_patient_name( $patient_id ) {
    if ( ! $patient_id ) {
        return '';
    }

    $data = ayument_appointments_get_patients();

    if (
        empty( $data['table'] ) ||
        empty( $data['id_column'] ) ||
        empty( $data['name_column'] )
    ) {
        return '';
    }

    global $wpdb;

    $name = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT {$data['name_column']}
             FROM {$data['table']}
             WHERE {$data['id_column']} = %d
             LIMIT 1",
            $patient_id
        )
    );

    return $name ? $name : '';
}


/* =========================================================
 * APPOINTMENT HELPERS
 * ========================================================= */

function ayument_appointments_get( $appointment_id ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'ayument_appointments';

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d LIMIT 1",
            $appointment_id
        )
    );
}

function ayument_appointments_delete( $appointment_id ) {
    global $wpdb;

    $wpdb->delete(
        $wpdb->prefix . 'ayument_appointments',
        array( 'id' => $appointment_id ),
        array( '%d' )
    );
}

function ayument_appointments_doctor_id() {
    $user = wp_get_current_user();

    if ( ! $user || ! $user->ID ) {
        return 0;
    }

    $allowed = array( 'ayument_doctor', 'ayument_doctor_pending', 'administrator' );

    foreach ( (array) $user->roles as $role ) {
        if ( in_array( $role, $allowed, true ) ) {
            return (int) $user->ID;
        }
    }

    return 0;
}

function ayument_appointments_doctor_name( $user_id = 0 ) {
    $user_id = $user_id ? absint( $user_id ) : get_current_user_id();
    $user = $user_id ? get_user_by( 'id', $user_id ) : false;

    if ( ! $user ) {
        return '';
    }

    $name = trim( $user->first_name . ' ' . $user->last_name );

    return $name ? $name : $user->display_name;
}


/* =========================================================
 * ADMIN ACTIONS
 * ========================================================= */

function ayument_appointments_handle_actions() {

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( ! isset( $_GET['page'] ) || 'ayument-appointments' !== $_GET['page'] ) {
        return;
    }

    if (
        isset( $_GET['action'] ) &&
        'delete' === $_GET['action'] &&
        isset( $_GET['appointment_id'] )
    ) {
        $appointment_id = absint( $_GET['appointment_id'] );

        if (
            isset( $_GET['_wpnonce'] ) &&
            wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ),
                'ayument_delete_appointment_' . $appointment_id
            )
        ) {
            ayument_appointments_delete( $appointment_id );

            wp_safe_redirect(
                admin_url( 'admin.php?page=ayument-appointments&deleted=1' )
            );
            exit;
        }
    }

    if ( isset( $_POST['ayument_save_appointment'] ) ) {

        if (
            ! isset( $_POST['ayument_appointment_nonce'] ) ||
            ! wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash( $_POST['ayument_appointment_nonce'] )
                ),
                'ayument_save_appointment'
            )
        ) {
            return;
        }

        global $wpdb;

        $table_name = $wpdb->prefix . 'ayument_appointments';

        $appointment_id = isset( $_POST['appointment_id'] )
            ? absint( $_POST['appointment_id'] )
            : 0;

        $patient_id = isset( $_POST['patient_id'] )
            ? absint( $_POST['patient_id'] )
            : 0;

        $patient_name = isset( $_POST['patient_name'] )
            ? sanitize_text_field( wp_unslash( $_POST['patient_name'] ) )
            : '';

        $doctor_id = isset( $_POST['doctor_id'] )
            ? absint( $_POST['doctor_id'] )
            : 0;

        $doctor_name = isset( $_POST['doctor_name'] )
            ? sanitize_text_field( wp_unslash( $_POST['doctor_name'] ) )
            : '';

        $appointment_date = isset( $_POST['appointment_date'] )
            ? sanitize_text_field( wp_unslash( $_POST['appointment_date'] ) )
            : '';

        $appointment_time = isset( $_POST['appointment_time'] )
            ? sanitize_text_field( wp_unslash( $_POST['appointment_time'] ) )
            : '';

        $appointment_type = isset( $_POST['appointment_type'] )
            ? sanitize_text_field( wp_unslash( $_POST['appointment_type'] ) )
            : 'General Consultation';

        $status = isset( $_POST['status'] )
            ? sanitize_text_field( wp_unslash( $_POST['status'] ) )
            : 'Scheduled';

        $reason = isset( $_POST['reason'] )
            ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) )
            : '';

        if ( $patient_id ) {
            $stored_patient_name = ayument_appointments_get_patient_name( $patient_id );

            if ( $stored_patient_name ) {
                $patient_name = $stored_patient_name;
            }
        }

        if ( empty( $appointment_date ) ) {
            $appointment_date = current_time( 'Y-m-d' );
        }

        if ( empty( $appointment_time ) ) {
            $appointment_time = '09:00';
        }

        if ( ! $doctor_id ) {
            $doctor_id = get_current_user_id();
        }

        if ( ! $doctor_name ) {
            $doctor_name = ayument_appointments_doctor_name( $doctor_id );
        }

        $data = array(
            'patient_id'       => $patient_id,
            'patient_name'     => $patient_name,
            'doctor_id'        => $doctor_id,
            'doctor_name'      => $doctor_name,
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'appointment_type' => $appointment_type,
            'status'           => $status,
            'reason'           => $reason,
            'updated_at'       => current_time( 'mysql' ),
        );

        $formats = array(
            '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s',
        );

        if ( $appointment_id ) {

            $wpdb->update(
                $table_name,
                $data,
                array( 'id' => $appointment_id ),
                $formats,
                array( '%d' )
            );

            $redirect_url = add_query_arg(
                array(
                    'page'           => 'ayument-appointments',
                    'updated'        => 1,
                    'appointment_id' => $appointment_id,
                ),
                admin_url( 'admin.php' )
            );

        } else {

            $data['created_at'] = current_time( 'mysql' );

            $wpdb->insert(
                $table_name,
                $data,
                array(
                    '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                )
            );

            $redirect_url = add_query_arg(
                array(
                    'page'           => 'ayument-appointments',
                    'saved'          => 1,
                    'appointment_id' => $wpdb->insert_id,
                ),
                admin_url( 'admin.php' )
            );
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }
}

add_action( 'admin_init', 'ayument_appointments_handle_actions' );


/* =========================================================
 * ADMIN MENU
 * ========================================================= */

function ayument_appointments_menu() {
    add_submenu_page(
        'ayument-dashboard',
        'Appointments',
        'Appointments',
        'manage_options',
        'ayument-appointments',
        'ayument_appointments_page'
    );
}

if ( ! function_exists( 'ayument_register_appointments_menu' ) ) {
    add_action( 'admin_menu', 'ayument_appointments_menu', 30 );
}


/* =========================================================
 * ADMIN PAGE
 * ========================================================= */

function ayument_appointments_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to access this page.' );
    }

    global $wpdb;

    ayument_appointments_create_table();

    $table_name = $wpdb->prefix . 'ayument_appointments';

    $patient_id = isset( $_GET['patient_id'] ) ? absint( $_GET['patient_id'] ) : 0;
    $edit_id    = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;

    $edit_appointment = $edit_id ? ayument_appointments_get( $edit_id ) : null;

    if ( $edit_appointment && ! empty( $edit_appointment->patient_id ) ) {
        $patient_id = absint( $edit_appointment->patient_id );
    }

    $patient_data = ayument_appointments_get_patients();
    $patients = $patient_data['patients'];

    $selected_patient_name = $patient_id
        ? ayument_appointments_get_patient_name( $patient_id )
        : '';

    if ( $edit_appointment && ! empty( $edit_appointment->patient_name ) ) {
        $selected_patient_name = $edit_appointment->patient_name;
    }

    $form_date   = $edit_appointment ? $edit_appointment->appointment_date : current_time( 'Y-m-d' );
    $form_time   = $edit_appointment ? substr( $edit_appointment->appointment_time, 0, 5 ) : '09:00';
    $form_type   = $edit_appointment ? $edit_appointment->appointment_type : 'General Consultation';
    $form_status = $edit_appointment ? $edit_appointment->status : 'Scheduled';
    $form_reason = $edit_appointment ? $edit_appointment->reason : '';

    $appointments = $wpdb->get_results(
        "SELECT * FROM {$table_name}
         ORDER BY appointment_date DESC, appointment_time DESC"
    );

    ?>
    <div class="wrap">
        <h1>AyuMent Appointments</h1>

        <?php if ( isset( $_GET['saved'] ) || isset( $_GET['updated'] ) || isset( $_GET['deleted'] ) ) : ?>
            <div class="notice notice-success is-dismissible">
                <p>Appointment saved successfully.</p>
            </div>
        <?php endif; ?>

        <div style="background:#fff;padding:25px;margin:20px 0;border:1px solid #ddd;border-radius:12px;">
            <h2><?php echo $edit_appointment ? 'Edit Appointment' : 'New Appointment'; ?></h2>

            <form method="post">
                <?php wp_nonce_field( 'ayument_save_appointment', 'ayument_appointment_nonce' ); ?>

                <input type="hidden" name="appointment_id" value="<?php echo esc_attr( $edit_appointment ? $edit_appointment->id : 0 ); ?>">

                <table class="form-table">
                    <tr>
                        <th><label for="ayument_patient_id">Patient</label></th>
                        <td>
                            <select name="patient_id" id="ayument_patient_id">
                                <option value="">Select Patient</option>
                                <?php foreach ( $patients as $patient ) : ?>
                                    <?php
                                    $pid = isset( $patient->{$patient_data['id_column']} )
                                        ? $patient->{$patient_data['id_column']}
                                        : 0;
                                    $pname = isset( $patient->{$patient_data['name_column']} )
                                        ? $patient->{$patient_data['name_column']}
                                        : '';
                                    ?>
                                    <option
                                        value="<?php echo esc_attr( $pid ); ?>"
                                        data-name="<?php echo esc_attr( $pname ); ?>"
                                        <?php selected( $patient_id, $pid ); ?>
                                    >
                                        <?php echo esc_html( $pname ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="ayument_patient_name">Patient Name</label></th>
                        <td>
                            <input
                                type="text"
                                class="regular-text"
                                name="patient_name"
                                id="ayument_patient_name"
                                value="<?php echo esc_attr( $selected_patient_name ); ?>"
                            >
                        </td>
                    </tr>

                    <tr>
                        <th><label for="ayument_doctor_id">Doctor</label></th>
                        <td>
                            <input type="number" name="doctor_id" id="ayument_doctor_id"
                                   value="<?php echo esc_attr( $edit_appointment ? $edit_appointment->doctor_id : get_current_user_id() ); ?>">
                            <p class="description">User ID of the doctor.</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="ayument_appointment_date">Date</label></th>
                        <td><input type="date" name="appointment_date" id="ayument_appointment_date" value="<?php echo esc_attr( $form_date ); ?>" required></td>
                    </tr>

                    <tr>
                        <th><label for="ayument_appointment_time">Time</label></th>
                        <td><input type="time" name="appointment_time" id="ayument_appointment_time" value="<?php echo esc_attr( $form_time ); ?>" required></td>
                    </tr>

                    <tr>
                        <th><label for="ayument_appointment_type">Type</label></th>
                        <td>
                            <select name="appointment_type">
                                <?php
                                foreach (
                                    array(
                                        'General Consultation',
                                        'Follow-up',
                                        'Prakriti Assessment',
                                        'HAM-D Assessment',
                                        'HAM-A Assessment',
                                        'AI Consultation',
                                        'Other',
                                    ) as $type
                                ) :
                                ?>
                                    <option value="<?php echo esc_attr( $type ); ?>" <?php selected( $form_type, $type ); ?>>
                                        <?php echo esc_html( $type ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="ayument_status">Status</label></th>
                        <td>
                            <select name="status" id="ayument_status">
                                <?php foreach ( array( 'Scheduled', 'Completed', 'Cancelled', 'No-show' ) as $status ) : ?>
                                    <option value="<?php echo esc_attr( $status ); ?>" <?php selected( $form_status, $status ); ?>>
                                        <?php echo esc_html( $status ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="ayument_reason">Reason / Chief Complaint</label></th>
                        <td>
                            <textarea name="reason" id="ayument_reason" rows="5" class="large-text"><?php echo esc_textarea( $form_reason ); ?></textarea>
                        </td>
                    </tr>
                </table>

                <p>
                    <button type="submit" name="ayument_save_appointment" class="button button-primary">
                        <?php echo $edit_appointment ? 'Update Appointment' : 'Save Appointment'; ?>
                    </button>
                </p>
            </form>
        </div>

        <div style="background:#fff;padding:25px;border:1px solid #ddd;border-radius:12px;">
            <h2>Appointment History</h2>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $appointments ) ) : ?>
                    <tr><td colspan="8">No appointments recorded yet.</td></tr>
                <?php else : ?>
                    <?php foreach ( $appointments as $appointment ) : ?>
                        <tr>
                            <td>#<?php echo esc_html( $appointment->id ); ?></td>
                            <td><?php echo esc_html( $appointment->appointment_date ); ?></td>
                            <td><?php echo esc_html( $appointment->appointment_time ); ?></td>
                            <td><?php echo esc_html( $appointment->patient_name ); ?></td>
                            <td><?php echo esc_html( $appointment->doctor_name ); ?></td>
                            <td><?php echo esc_html( $appointment->appointment_type ); ?></td>
                            <td><?php echo esc_html( $appointment->status ); ?></td>
                            <td>
                                <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ayument-appointments&edit=' . absint( $appointment->id ) ) ); ?>">Edit</a>
                                <a
                                    class="button"
                                    href="<?php echo esc_url(
                                        wp_nonce_url(
                                            admin_url(
                                                'admin.php?page=ayument-appointments&action=delete&appointment_id=' . absint( $appointment->id )
                                            ),
                                            'ayument_delete_appointment_' . absint( $appointment->id )
                                        )
                                    ); ?>"
                                    onclick="return confirm('Delete this appointment?');"
                                >Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const select = document.getElementById('ayument_patient_id');
        const name = document.getElementById('ayument_patient_name');

        if (!select || !name) return;

        select.addEventListener('change', function () {
            const option = this.options[this.selectedIndex];
            name.value = option ? (option.dataset.name || '') : '';
        });
    });
    </script>
    <?php
}


/* =========================================================
 * DOCTOR-FACING APPOINTMENTS
 * ========================================================= */

function ayument_doctor_appointments_shortcode() {

    if ( ! is_user_logged_in() ) {
        return '<div style="padding:25px;border:1px solid #ddd;border-radius:14px;background:#fff;">Please log in to access appointments.</div>';
    }

    $doctor_id = ayument_appointments_doctor_id();

    if ( ! $doctor_id ) {
        return '<div style="padding:25px;border:1px solid #ddd;border-radius:14px;background:#fff;">Doctor access is required for appointments.</div>';
    }

    ayument_appointments_create_table();

    global $wpdb;

    $table_name = $wpdb->prefix . 'ayument_appointments';

    /*
     * Doctor actions.
     */
    if (
        isset( $_POST['ayument_doctor_appointment_action'] ) &&
        isset( $_POST['appointment_id'] ) &&
        isset( $_POST['ayument_doctor_appointment_nonce'] )
    ) {
        if (
            wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash( $_POST['ayument_doctor_appointment_nonce'] )
                ),
                'ayument_doctor_appointment_action'
            )
        ) {
            $appointment_id = absint( $_POST['appointment_id'] );
            $action = sanitize_key(
                wp_unslash( $_POST['ayument_doctor_appointment_action'] )
            );

            $appointment = ayument_appointments_get( $appointment_id );

            if (
                $appointment &&
                absint( $appointment->doctor_id ) === $doctor_id &&
                in_array( $action, array( 'complete', 'cancel' ), true )
            ) {
                $new_status = 'complete' === $action ? 'Completed' : 'Cancelled';

                $wpdb->update(
                    $table_name,
                    array(
                        'status'     => $new_status,
                        'updated_at' => current_time( 'mysql' ),
                    ),
                    array( 'id' => $appointment_id ),
                    array( '%s', '%s' ),
                    array( '%d' )
                );
            }
        }
    }

    /*
     * Only show this doctor's appointments.
     */
    $appointments = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT *
             FROM {$table_name}
             WHERE doctor_id = %d
             ORDER BY appointment_date ASC, appointment_time ASC",
            $doctor_id
        )
    );

    $today = current_time( 'Y-m-d' );
    $upcoming = array();
    $history = array();

    foreach ( $appointments as $appointment ) {

        /*
         * Upcoming = only appointments that are still scheduled
         * and whose date is today or in the future.
         *
         * Completed / Cancelled appointments always belong
         * in History, even when their date is today or future.
         */
        if (
            $appointment->appointment_date >= $today &&
            'Scheduled' === $appointment->status
        ) {
            $upcoming[] = $appointment;
        } else {
            $history[] = $appointment;
        }
    }

    ob_start();
    ?>
    <style>
        .ayument-doctor-apps {
            max-width: 1200px;
            margin: 0 auto;
            font-family: inherit;
        }

        .ayument-doctor-apps * {
            box-sizing: border-box;
        }

        .ayument-doctor-apps-hero {
            background: linear-gradient(135deg,#12306b,#2563eb);
            color:#fff;
            padding:32px;
            border-radius:20px;
            margin-bottom:24px;
        }

        .ayument-doctor-apps-hero h2 {
            color:#fff;
            margin:0 0 8px;
            font-size:30px;
        }

        .ayument-doctor-apps-hero p {
            margin:0;
            color:#eef5ff;
        }

        .ayument-doctor-apps-card {
            background:#fff;
            border:1px solid #e3eaf4;
            border-radius:18px;
            padding:24px;
            margin-bottom:24px;
            box-shadow:0 8px 24px rgba(15,23,42,.06);
        }

        .ayument-doctor-apps-card h3 {
            margin:0 0 18px;
            color:#12306b;
        }

        .ayument-doctor-app-row {
            display:grid;
            grid-template-columns:1.2fr 1fr 1fr 1fr 1.2fr;
            gap:14px;
            align-items:center;
            padding:16px 0;
            border-top:1px solid #edf1f6;
        }

        .ayument-doctor-app-row:first-of-type {
            border-top:0;
        }

        .ayument-doctor-app-muted {
            color:#64748b;
            font-size:13px;
        }

        .ayument-doctor-app-status {
            display:inline-block;
            padding:6px 10px;
            border-radius:999px;
            background:#dbeafe;
            color:#1d4ed8;
            font-size:12px;
            font-weight:700;
        }

        .ayument-doctor-app-status.completed {
            background:#dcfce7;
            color:#15803d;
        }

        .ayument-doctor-app-status.cancelled {
            background:#fee2e2;
            color:#b91c1c;
        }

        .ayument-doctor-app-actions {
            display:flex;
            gap:8px;
            flex-wrap:wrap;
        }

        .ayument-doctor-app-actions button {
            border:0;
            border-radius:9px;
            padding:9px 12px;
            cursor:pointer;
            font-weight:600;
        }

        .ayument-doctor-app-complete {
            background:#2563eb;
            color:#fff;
        }

        .ayument-doctor-app-cancel {
            background:#fee2e2;
            color:#b91c1c;
        }

        .ayument-doctor-app-empty {
            padding:20px;
            background:#f8fafc;
            border-radius:12px;
            color:#64748b;
        }

        @media(max-width:800px) {
            .ayument-doctor-app-row {
                grid-template-columns:1fr;
                padding:18px 0;
            }
        }
    </style>

    <div class="ayument-doctor-apps">

        <div class="ayument-doctor-apps-hero">
            <h2>Appointments</h2>
            <p>Manage your upcoming patient appointments and consultation schedule.</p>
        </div>

        <div class="ayument-doctor-apps-card">
            <h3>Upcoming Appointments</h3>

            <?php if ( empty( $upcoming ) ) : ?>

                <div class="ayument-doctor-app-empty">
                    No upcoming appointments.
                </div>

            <?php else : ?>

                <?php foreach ( $upcoming as $appointment ) : ?>

                    <div class="ayument-doctor-app-row">

                        <div>
                            <strong><?php echo esc_html( $appointment->patient_name ); ?></strong>
                            <div class="ayument-doctor-app-muted">
                                Patient
                            </div>
                        </div>

                        <div>
                            <strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $appointment->appointment_date ) ) ); ?></strong>
                            <div class="ayument-doctor-app-muted">
                                <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $appointment->appointment_time ) ) ); ?>
                            </div>
                        </div>

                        <div>
                            <strong><?php echo esc_html( $appointment->appointment_type ); ?></strong>
                            <div class="ayument-doctor-app-muted">
                                Appointment type
                            </div>
                        </div>

                        <div>
                            <span class="ayument-doctor-app-status">
                                <?php echo esc_html( $appointment->status ); ?>
                            </span>
                        </div>

                        <div class="ayument-doctor-app-actions">
                            <form method="post">
                                <?php wp_nonce_field( 'ayument_doctor_appointment_action', 'ayument_doctor_appointment_nonce' ); ?>
                                <input type="hidden" name="appointment_id" value="<?php echo esc_attr( $appointment->id ); ?>">

                                <?php if ( 'Scheduled' === $appointment->status ) : ?>

                                    <button
                                        type="submit"
                                        name="ayument_doctor_appointment_action"
                                        value="complete"
                                        class="ayument-doctor-app-complete"
                                    >Mark Completed</button>

                                    <button
                                        type="submit"
                                        name="ayument_doctor_appointment_action"
                                        value="cancel"
                                        class="ayument-doctor-app-cancel"
                                        onclick="return confirm('Cancel this appointment?');"
                                    >Cancel</button>

                                <?php endif; ?>
                            </form>
                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>
        </div>

        <div class="ayument-doctor-apps-card">
            <h3>Appointment History</h3>

            <?php if ( empty( $history ) ) : ?>

                <div class="ayument-doctor-app-empty">
                    No appointment history yet.
                </div>

            <?php else : ?>

                <?php foreach ( $history as $appointment ) : ?>

                    <div class="ayument-doctor-app-row">

                        <div>
                            <strong><?php echo esc_html( $appointment->patient_name ); ?></strong>
                        </div>

                        <div>
                            <?php echo esc_html( $appointment->appointment_date ); ?>
                            <div class="ayument-doctor-app-muted">
                                <?php echo esc_html( $appointment->appointment_time ); ?>
                            </div>
                        </div>

                        <div>
                            <?php echo esc_html( $appointment->appointment_type ); ?>
                        </div>

                        <div>
                            <span class="ayument-doctor-app-status <?php echo 'Completed' === $appointment->status ? 'completed' : ( 'Cancelled' === $appointment->status ? 'cancelled' : '' ); ?>">
                                <?php echo esc_html( $appointment->status ); ?>
                            </span>
                        </div>

                        <div class="ayument-doctor-app-muted">
                            <?php echo esc_html( $appointment->reason ); ?>
                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>
        </div>

    </div>
    <?php

    return ob_get_clean();
}

add_shortcode(
    'ayument_doctor_appointments',
    'ayument_doctor_appointments_shortcode'
);


/* =========================================================
 * AUTOMATIC DOCTOR PORTAL INTEGRATION
 *
 * The existing doctor portal uses ?doctor_view=appointments.
 * This hook makes the appointments workspace appear there
 * without requiring another shortcode to be manually added.
 * ========================================================= */

function ayument_appointments_attach_to_doctor_portal( $content ) {

    if ( is_admin() || ! is_user_logged_in() ) {
        return $content;
    }

    if (
        empty( $_GET['doctor_view'] ) ||
        'appointments' !== sanitize_key( wp_unslash( $_GET['doctor_view'] ) )
    ) {
        return $content;
    }

    $doctor_id = ayument_appointments_doctor_id();

    if ( ! $doctor_id ) {
        return $content;
    }

    if ( has_shortcode( $content, 'ayument_doctor_appointments' ) ) {
        return $content;
    }

    return $content . do_shortcode( '[ayument_doctor_appointments]' );
}

add_filter(
    'the_content',
    'ayument_appointments_attach_to_doctor_portal',
    30
);
