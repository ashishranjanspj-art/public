<?php
/**
 * AyuMent Doctor Module
 *
 * Doctor registration, verification and approval workflow.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/* =========================================================
 * DOCTOR ROLES
 * ========================================================= */

function ayument_doctors_register_roles() {

    if ( ! get_role( 'ayument_doctor_pending' ) ) {
        add_role(
            'ayument_doctor_pending',
            'AyuMent Doctor - Pending',
            array(
                'read' => true,
            )
        );
    }

    if ( ! get_role( 'ayument_doctor' ) ) {
        add_role(
            'ayument_doctor',
            'AyuMent Doctor',
            array(
                'read' => true,
            )
        );
    }
}

add_action( 'init', 'ayument_doctors_register_roles' );


/* =========================================================
 * DATABASE TABLE
 * ========================================================= */

function ayument_doctors_create_table() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'ayument_doctor_applications';

    // Do not run dbDelta() on every admin request.
    // It was causing repeated upgrade.php warnings on the local development site.
    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        )
    );

    if ( $exists === $table_name ) {
        $column_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW COLUMNS FROM {$table_name} LIKE %s",
                'verification_documents'
            )
        );

        if ( ! $column_exists ) {
            $wpdb->query(
                "ALTER TABLE {$table_name} ADD verification_documents longtext NULL"
            );
        }

        return;
    }

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL DEFAULT 0,

        full_name varchar(150) NOT NULL,
        email varchar(190) NOT NULL,
        phone varchar(50) NOT NULL,

        qualification varchar(150) NOT NULL,
        specialization varchar(150) DEFAULT '',
        registration_number varchar(150) DEFAULT '',
        registration_council varchar(200) DEFAULT '',
        experience varchar(100) DEFAULT '',

        clinic_name varchar(200) DEFAULT '',
        languages varchar(255) DEFAULT '',
        professional_bio text,

        profile_photo varchar(500) DEFAULT '',
        registration_document varchar(500) DEFAULT '',
        verification_documents longtext,

        status varchar(30) NOT NULL DEFAULT 'pending',

        admin_notes text,

        created_at datetime NOT NULL,
        reviewed_at datetime DEFAULT NULL,

        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY status (status),
        KEY email (email)
    ) {$charset_collate}";

    $wpdb->query( $sql );
}

add_action( 'admin_init', 'ayument_doctors_create_table' );


/* =========================================================
 * ADMIN MENU
 * ========================================================= */

function ayument_doctors_admin_menu() {

    add_submenu_page(
        'ayument-dashboard',
        'Doctors',
        'Doctors',
        'manage_options',
        'ayument-doctors',
        'ayument_doctors_admin_page'
    );
}

add_action( 'admin_menu', 'ayument_doctors_admin_menu', 20 );


/* =========================================================
 * ADMIN ACTIONS
 * ========================================================= */

function ayument_doctors_handle_admin_action() {

    if ( ! is_admin() ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( empty( $_GET['page'] ) || 'ayument-doctors' !== $_GET['page'] ) {
        return;
    }

    if ( empty( $_GET['action'] ) || empty( $_GET['application_id'] ) ) {
        return;
    }

    $action         = sanitize_key( $_GET['action'] );
    $application_id = absint( $_GET['application_id'] );

    if ( ! in_array( $action, array( 'approve', 'reject' ), true ) ) {
        return;
    }

    check_admin_referer(
        'ayument_doctor_action_' . $application_id
    );

    global $wpdb;

    $table_name = $wpdb->prefix . 'ayument_doctor_applications';

    $application = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $application_id
        )
    );

    if ( ! $application ) {
        return;
    }

    if ( 'approve' === $action ) {

        if ( $application->user_id ) {

            $user = get_user_by( 'id', $application->user_id );

            if ( $user ) {
                $user->set_role( 'ayument_doctor' );
            }
        }

        $wpdb->update(
            $table_name,
            array(
                'status'      => 'approved',
                'reviewed_at' => current_time( 'mysql' ),
            ),
            array(
                'id' => $application_id,
            ),
            array(
                '%s',
                '%s',
            ),
            array(
                '%d',
            )
        );

    } elseif ( 'reject' === $action ) {

        if ( $application->user_id ) {

            $user = get_user_by( 'id', $application->user_id );

            if ( $user ) {
                $user->set_role( 'subscriber' );
            }
        }

        $wpdb->update(
            $table_name,
            array(
                'status'      => 'rejected',
                'reviewed_at' => current_time( 'mysql' ),
            ),
            array(
                'id' => $application_id,
            ),
            array(
                '%s',
                '%s',
            ),
            array(
                '%d',
            )
        );
    }

    wp_safe_redirect(
        admin_url( 'admin.php?page=ayument-doctors&updated=1' )
    );

    exit;
}

add_action( 'admin_init', 'ayument_doctors_handle_admin_action' );


/* =========================================================
 * ADMIN PAGE
 * ========================================================= */

function ayument_doctors_admin_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to access this page.' );
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'ayument_doctor_applications';

    $applications = $wpdb->get_results(
        "SELECT * FROM {$table_name} ORDER BY created_at DESC"
    );

    ?>

    <div class="wrap">

        <div
            style="
                max-width:1200px;
                margin-top:25px;
            "
        >

            <div
                style="
                    background:#ffffff;
                    padding:30px;
                    border-radius:16px;
                    border:1px solid #e5e7eb;
                    box-shadow:0 4px 18px rgba(0,0,0,0.06);
                    margin-bottom:25px;
                "
            >

                <h1 style="font-size:32px;margin-bottom:8px;">
                    🩺 AyuMent Doctors
                </h1>

                <p style="font-size:16px;color:#555;margin-top:0;">
                    Review and approve doctors before they become available
                    in the AyuMent consultation platform.
                </p>

            </div>


            <?php if ( isset( $_GET['updated'] ) ) : ?>

                <div
                    style="
                        background:#ecfdf5;
                        border-left:4px solid #10b981;
                        padding:14px 18px;
                        margin-bottom:20px;
                    "
                >
                    Doctor application updated successfully.
                </div>

            <?php endif; ?>


            <div
                style="
                    background:#ffffff;
                    padding:28px;
                    border-radius:16px;
                    border:1px solid #e5e7eb;
                    box-shadow:0 4px 18px rgba(0,0,0,0.05);
                "
            >

                <h2 style="margin-top:0;">
                    Doctor Applications
                </h2>


                <?php if ( empty( $applications ) ) : ?>

                    <p style="color:#666;font-size:15px;">
                        No doctor applications yet.
                    </p>

                <?php else : ?>

                    <div style="overflow-x:auto;">

                        <table
                            class="widefat striped"
                            style="min-width:1000px;"
                        >

                            <thead>

                                <tr>

                                    <th>ID</th>
                                    <th>Doctor</th>
                                    <th>Contact</th>
                                    <th>Qualification</th>
                                    <th>Registration</th>
                                    <th>Documents</th>
                                    <th>Status</th>
                                    <th>Applied</th>
                                    <th>Actions</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ( $applications as $application ) : ?>

                                    <tr>

                                        <td>
                                            #<?php echo esc_html( $application->id ); ?>
                                        </td>

                                        <td>

                                            <strong>
                                                <?php
                                                echo esc_html(
                                                    $application->full_name
                                                );
                                                ?>
                                            </strong>

                                            <br>

                                            <small>
                                                <?php
                                                echo esc_html(
                                                    $application->specialization
                                                );
                                                ?>
                                            </small>

                                        </td>

                                        <td>

                                            <?php
                                            echo esc_html(
                                                $application->email
                                            );
                                            ?>

                                            <br>

                                            <?php
                                            echo esc_html(
                                                $application->phone
                                            );
                                            ?>

                                        </td>

                                        <td>
                                            <?php
                                            echo esc_html(
                                                $application->qualification
                                            );
                                            ?>
                                        </td>

                                        <td>

                                            <?php
                                            echo esc_html(
                                                $application->registration_number
                                            );
                                            ?>

                                            <br>

                                            <small>
                                                <?php
                                                echo esc_html(
                                                    $application->registration_council
                                                );
                                                ?>
                                            </small>

                                        </td>

                                        <td>

                                            <?php
                                            $verification_documents = array();

                                            /* New multi-document storage. */
                                            if ( ! empty( $application->verification_documents ) ) {
                                                $decoded_documents = json_decode(
                                                    $application->verification_documents,
                                                    true
                                                );

                                                if ( is_array( $decoded_documents ) ) {
                                                    $verification_documents = $decoded_documents;
                                                }
                                            }

                                            /*
                                             * Backward compatibility: older applications may have
                                             * stored a single registration document in the legacy
                                             * registration_document column.
                                             */
                                            if ( empty( $verification_documents ) && ! empty( $application->registration_document ) ) {
                                                $verification_documents[] = array(
                                                    'label' => 'Registration Document',
                                                    'url'   => $application->registration_document,
                                                );
                                            }
                                            ?>

                                            <?php if ( ! empty( $verification_documents ) ) : ?>

                                                <?php foreach ( $verification_documents as $document ) : ?>

                                                    <?php
                                                    $document_url = ! empty( $document['url'] )
                                                        ? $document['url']
                                                        : '';
                                                    $document_label = ! empty( $document['label'] )
                                                        ? $document['label']
                                                        : 'Document';
                                                    ?>

                                                    <?php if ( $document_url ) : ?>
                                                        <a
                                                            href="<?php echo esc_url( $document_url ); ?>"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="button button-small"
                                                            style="margin-bottom:4px;"
                                                        >
                                                            <?php echo esc_html( $document_label ); ?>
                                                        </a>
                                                        <br>
                                                    <?php endif; ?>

                                                <?php endforeach; ?>

                                            <?php else : ?>

                                                <span style="color:#999;">No documents</span>

                                            <?php endif; ?>

                                        </td>

                                        <td>

                                            <?php if ( 'approved' === $application->status ) : ?>

                                                <span
                                                    style="
                                                        display:inline-block;
                                                        padding:5px 10px;
                                                        background:#dcfce7;
                                                        color:#166534;
                                                        border-radius:20px;
                                                        font-weight:600;
                                                    "
                                                >
                                                    Approved
                                                </span>

                                            <?php elseif ( 'rejected' === $application->status ) : ?>

                                                <span
                                                    style="
                                                        display:inline-block;
                                                        padding:5px 10px;
                                                        background:#fee2e2;
                                                        color:#991b1b;
                                                        border-radius:20px;
                                                        font-weight:600;
                                                    "
                                                >
                                                    Rejected
                                                </span>

                                            <?php else : ?>

                                                <span
                                                    style="
                                                        display:inline-block;
                                                        padding:5px 10px;
                                                        background:#fef3c7;
                                                        color:#92400e;
                                                        border-radius:20px;
                                                        font-weight:600;
                                                    "
                                                >
                                                    Pending
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td>

                                            <?php
                                            echo esc_html(
                                                date_i18n(
                                                    'd M Y',
                                                    strtotime(
                                                        $application->created_at
                                                    )
                                                )
                                            );
                                            ?>

                                        </td>

                                        <td>

                                            <?php if ( 'pending' === $application->status ) : ?>

                                                <?php
                                                $approve_url = wp_nonce_url(
                                                    admin_url(
                                                        'admin.php?page=ayument-doctors&action=approve&application_id=' .
                                                        absint( $application->id )
                                                    ),
                                                    'ayument_doctor_action_' .
                                                    absint( $application->id )
                                                );

                                                $reject_url = wp_nonce_url(
                                                    admin_url(
                                                        'admin.php?page=ayument-doctors&action=reject&application_id=' .
                                                        absint( $application->id )
                                                    ),
                                                    'ayument_doctor_action_' .
                                                    absint( $application->id )
                                                );
                                                ?>

                                                <a
                                                    href="<?php echo esc_url( $approve_url ); ?>"
                                                    class="button button-primary"
                                                    onclick="return confirm('Approve this doctor?');"
                                                >
                                                    Approve
                                                </a>

                                                <a
                                                    href="<?php echo esc_url( $reject_url ); ?>"
                                                    class="button"
                                                    onclick="return confirm('Reject this doctor application?');"
                                                >
                                                    Reject
                                                </a>

                                            <?php else : ?>

                                                <span style="color:#777;">
                                                    Reviewed
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

    <?php
}


/* =========================================================
 * DOCTOR APPLICATION STATUS SHORTCODE
 * ========================================================= */

function ayument_doctor_application_status_shortcode() {

    if ( ! is_user_logged_in() ) {

        return '
        <div style="
            max-width:700px;
            margin:40px auto;
            padding:30px;
            background:#ffffff;
            border-radius:16px;
            border:1px solid #e5e7eb;
            text-align:center;
        ">
            <h2>Doctor Application Status</h2>
            <p>Please log in to view your application status.</p>
        </div>';
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'ayument_doctor_applications';
    $user_id    = get_current_user_id();

    $application = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY id DESC LIMIT 1",
            $user_id
        )
    );

    if ( ! $application ) {

        return '
        <div style="
            max-width:800px;
            margin:40px auto;
            padding:35px;
            background:#ffffff;
            border-radius:18px;
            border:1px solid #e5e7eb;
            box-shadow:0 5px 25px rgba(0,0,0,0.06);
            text-align:center;
        ">
            <h2 style="color:#173f73;">Doctor Application Status</h2>
            <p style="color:#64748b;">
                No doctor application was found for your account.
            </p>
        </div>';
    }

    $status = strtolower( trim( $application->status ) );

    $status_label = 'Pending Review';
    $status_bg    = '#fef3c7';
    $status_color = '#92400e';

    if ( 'approved' === $status ) {
        $status_label = 'Approved';
        $status_bg    = '#dcfce7';
        $status_color = '#166534';
    } elseif ( 'rejected' === $status ) {
        $status_label = 'Rejected';
        $status_bg    = '#fee2e2';
        $status_color = '#991b1b';
    }

    $documents = array();

    if ( ! empty( $application->verification_documents ) ) {

        $decoded_documents = json_decode(
            $application->verification_documents,
            true
        );

        if ( is_array( $decoded_documents ) ) {
            $documents = $decoded_documents;
        }
    }

    /* Also show the legacy single registration document if present. */
    if ( empty( $documents ) && ! empty( $application->registration_document ) ) {
        $documents[] = array(
            'label' => 'Registration Document',
            'url'   => $application->registration_document,
        );
    }

    ob_start();
    ?>

    <div style="
        max-width:900px;
        margin:40px auto;
        background:#ffffff;
        padding:35px;
        border-radius:18px;
        border:1px solid #e5e7eb;
        box-shadow:0 5px 25px rgba(0,0,0,0.06);
    ">

        <div style="margin-bottom:25px;">
            <h2 style="
                font-size:30px;
                margin-bottom:8px;
                color:#173f73;
            ">
                Doctor Application Status
            </h2>

            <p style="color:#64748b;margin-bottom:0;">
                Review the current status of your AyuMent doctor application.
            </p>
        </div>

        <div style="
            padding:22px;
            background:<?php echo esc_attr( $status_bg ); ?>;
            border-radius:12px;
            margin-bottom:28px;
        ">

            <div style="
                font-size:13px;
                color:#64748b;
                margin-bottom:6px;
                text-transform:uppercase;
                letter-spacing:.5px;
            ">
                Current Status
            </div>

            <strong style="
                font-size:24px;
                color:<?php echo esc_attr( $status_color ); ?>;
            ">
                <?php echo esc_html( $status_label ); ?>
            </strong>

            <?php if ( 'pending' === $status ) : ?>

                <p style="margin:10px 0 0;color:#78350f;">
                    Your application is currently under verification.
                    You will be able to use the doctor consultation portal
                    after approval.
                </p>

            <?php elseif ( 'approved' === $status ) : ?>

                <p style="margin:10px 0 0;color:#166534;">
                    Your professional application has been approved.
                    Your doctor account is now verified.
                </p>

            <?php elseif ( 'rejected' === $status ) : ?>

                <p style="margin:10px 0 0;color:#991b1b;">
                    Your application was not approved at this time.
                    Please review any administrator note shown below.
                </p>

            <?php endif; ?>

        </div>

        <h3 style="
            color:#173f73;
            border-bottom:1px solid #e5e7eb;
            padding-bottom:10px;
        ">
            Application Details
        </h3>

        <div style="
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:18px;
            margin-top:20px;
        ">

            <div>
                <strong>Application ID</strong>
                <div style="color:#64748b;margin-top:4px;">
                    #<?php echo esc_html( $application->id ); ?>
                </div>
            </div>

            <div>
                <strong>Full Name</strong>
                <div style="color:#64748b;margin-top:4px;">
                    <?php echo esc_html( $application->full_name ); ?>
                </div>
            </div>

            <div>
                <strong>Email</strong>
                <div style="color:#64748b;margin-top:4px;">
                    <?php echo esc_html( $application->email ); ?>
                </div>
            </div>

            <div>
                <strong>Phone</strong>
                <div style="color:#64748b;margin-top:4px;">
                    <?php echo esc_html( $application->phone ); ?>
                </div>
            </div>

            <div>
                <strong>Qualification</strong>
                <div style="color:#64748b;margin-top:4px;">
                    <?php echo esc_html( $application->qualification ); ?>
                </div>
            </div>

            <div>
                <strong>Registration Number</strong>
                <div style="color:#64748b;margin-top:4px;">
                    <?php echo esc_html( $application->registration_number ); ?>
                </div>
            </div>

            <div>
                <strong>Submitted On</strong>
                <div style="color:#64748b;margin-top:4px;">
                    <?php
                    echo esc_html(
                        date_i18n(
                            'd M Y, h:i A',
                            strtotime( $application->created_at )
                        )
                    );
                    ?>
                </div>
            </div>

            <div>
                <strong>Reviewed On</strong>
                <div style="color:#64748b;margin-top:4px;">
                    <?php
                    if ( ! empty( $application->reviewed_at ) ) {
                        echo esc_html(
                            date_i18n(
                                'd M Y, h:i A',
                                strtotime( $application->reviewed_at )
                            )
                        );
                    } else {
                        echo 'Not reviewed yet';
                    }
                    ?>
                </div>
            </div>

        </div>

        <?php if ( ! empty( $documents ) ) : ?>

            <h3 style="
                color:#173f73;
                border-bottom:1px solid #e5e7eb;
                padding-bottom:10px;
                margin-top:35px;
            ">
                Submitted Documents
            </h3>

            <div style="
                display:grid;
                grid-template-columns:1fr 1fr;
                gap:12px;
                margin-top:18px;
            ">

                <?php foreach ( $documents as $document ) : ?>

                    <?php
                    $document_url   = ! empty( $document['url'] )
                        ? $document['url']
                        : '';
                    $document_label = ! empty( $document['label'] )
                        ? $document['label']
                        : 'Document';
                    ?>

                    <?php if ( $document_url ) : ?>

                        <a
                            href="<?php echo esc_url( $document_url ); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            style="
                                display:block;
                                padding:14px;
                                border:1px solid #dbe3ea;
                                border-radius:10px;
                                text-decoration:none;
                                color:#173f73;
                                background:#f8fafc;
                            "
                        >
                            📄 <?php echo esc_html( $document_label ); ?>

                            <span style="
                                display:block;
                                color:#64748b;
                                font-size:12px;
                                margin-top:4px;
                            ">
                                View submitted document
                            </span>
                        </a>

                    <?php endif; ?>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <?php if ( ! empty( $application->admin_notes ) ) : ?>

            <div style="
                margin-top:30px;
                padding:20px;
                background:#f8fafc;
                border:1px solid #e2e8f0;
                border-radius:12px;
            ">

                <strong style="color:#173f73;">
                    Administrator Note
                </strong>

                <p style="
                    margin:10px 0 0;
                    color:#475569;
                    white-space:pre-wrap;
                ">
                    <?php echo esc_html( $application->admin_notes ); ?>
                </p>

            </div>

        <?php endif; ?>

        <?php if ( 'pending' === $status ) : ?>

            <div style="
                margin-top:30px;
                padding:18px;
                background:#f8fafc;
                border-radius:10px;
                color:#64748b;
            ">
                Your application is still being reviewed. You can return
                to this page anytime to re-check the latest status.
            </div>

        <?php endif; ?>

    </div>

    <style>
        @media (max-width:700px) {
            div[style*="grid-template-columns:1fr 1fr"] {
                grid-template-columns:1fr !important;
            }
        }
    </style>

    <?php

    return ob_get_clean();
}

add_shortcode(
    'ayument_doctor_application_status',
    'ayument_doctor_application_status_shortcode'
);


/* =========================================================
 * DOCTOR SIGNUP SHORTCODE
 * ========================================================= */

function ayument_doctors_signup_shortcode() {

    if ( is_user_logged_in() ) {

        return '
        <div style="
            max-width:700px;
            margin:40px auto;
            padding:30px;
            background:#ffffff;
            border-radius:16px;
            border:1px solid #e5e7eb;
            text-align:center;
        ">
            <h2>Doctor Registration</h2>
            <p>You are already logged in.</p>
            <p style="margin-top:18px;">
                <a
                    href="' . esc_url( site_url( '/doctor-application-status/' ) ) . '"
                    style="
                        display:inline-block;
                        background:#2563eb;
                        color:#ffffff;
                        text-decoration:none;
                        padding:12px 20px;
                        border-radius:8px;
                        font-weight:600;
                    "
                >
                    View Application Status
                </a>
            </p>
        </div>';
    }


    $message = '';


    /* -----------------------------------------------------
     * FORM SUBMISSION
     * ----------------------------------------------------- */

    if (
        isset( $_POST['ayument_doctor_signup_submit'] )
        && isset( $_POST['ayument_doctor_signup_nonce'] )
    ) {

        if (
            ! wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash(
                        $_POST['ayument_doctor_signup_nonce']
                    )
                ),
                'ayument_doctor_signup'
            )
        ) {

            $message = '
                <div style="
                    padding:14px;
                    background:#fee2e2;
                    color:#991b1b;
                    border-radius:8px;
                    margin-bottom:20px;
                ">
                    Security verification failed. Please try again.
                </div>
            ';

        } else {

            $full_name = isset( $_POST['full_name'] )
                ? sanitize_text_field(
                    wp_unslash( $_POST['full_name'] )
                )
                : '';

            $email = isset( $_POST['email'] )
                ? sanitize_email(
                    wp_unslash( $_POST['email'] )
                )
                : '';

            $phone = isset( $_POST['phone'] )
                ? sanitize_text_field(
                    wp_unslash( $_POST['phone'] )
                )
                : '';

            $password = isset( $_POST['password'] )
                ? (string) $_POST['password']
                : '';

            $qualification = isset( $_POST['qualification'] )
                ? sanitize_text_field(
                    wp_unslash( $_POST['qualification'] )
                )
                : '';

            $specialization = isset( $_POST['specialization'] )
                ? sanitize_text_field(
                    wp_unslash( $_POST['specialization'] )
                )
                : '';

            $registration_number = isset( $_POST['registration_number'] )
                ? sanitize_text_field(
                    wp_unslash( $_POST['registration_number'] )
                )
                : '';

            $registration_council = isset( $_POST['registration_council'] )
                ? sanitize_text_field(
                    wp_unslash( $_POST['registration_council'] )
                )
                : '';

            $experience = isset( $_POST['experience'] )
                ? sanitize_text_field(
                    wp_unslash( $_POST['experience'] )
                )
                : '';

            $clinic_name = isset( $_POST['clinic_name'] )
                ? sanitize_text_field(
                    wp_unslash( $_POST['clinic_name'] )
                )
                : '';

            $languages = isset( $_POST['languages'] )
                ? sanitize_text_field(
                    wp_unslash( $_POST['languages'] )
                )
                : '';

            $professional_bio = isset( $_POST['professional_bio'] )
                ? sanitize_textarea_field(
                    wp_unslash( $_POST['professional_bio'] )
                )
                : '';


            /* -------------------------------------------------
             * VALIDATION
             * ------------------------------------------------- */

            $errors = array();
            if ( empty( $full_name ) ) {
                $errors[] = 'Please enter your full name.';
            }

            if ( empty( $email ) || ! is_email( $email ) ) {
                $errors[] = 'Please enter a valid email address.';
            }

            if ( empty( $phone ) ) {
                $errors[] = 'Please enter your phone number.';
            }

            if ( strlen( $password ) < 8 ) {
                $errors[] = 'Password must contain at least 8 characters.';
            }

            if ( empty( $qualification ) ) {
                $errors[] = 'Please enter your qualification.';
            }

            if ( empty( $registration_number ) ) {
                $errors[] = 'Please enter your registration number.';
            }


            if ( email_exists( $email ) ) {
                $errors[] = 'An account with this email already exists.';
            }


            $uploaded_documents = array();

            $document_fields = array(
                'registration_certificate'   => 'Registration Certificate',
                'qualification_certificate' => 'Qualification Certificate',
                'identity_proof'             => 'Identity Proof',
            );

            require_once ABSPATH . 'wp-admin/includes/file.php';

            foreach ( $document_fields as $field_name => $document_label ) {

                $has_file = isset( $_FILES[ $field_name ] )
                    && ! empty( $_FILES[ $field_name ]['name'] );

                $is_required = in_array(
                    $field_name,
                    array(
                        'registration_certificate',
                        'qualification_certificate',
                    ),
                    true
                );

                if ( ! $has_file ) {
                    if ( $is_required ) {
                        $errors[] = $document_label . ' is required.';
                    }
                    continue;
                }

                $file = $_FILES[ $field_name ];

                if ( ! empty( $file['error'] ) ) {
                    $errors[] = $document_label . ' could not be uploaded.';
                    continue;
                }

                if ( $file['size'] > 5 * 1024 * 1024 ) {
                    $errors[] = $document_label . ' must be 5 MB or smaller.';
                    continue;
                }

                $check = wp_check_filetype_and_ext(
                    $file['tmp_name'],
                    $file['name']
                );

                $extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
                $allowed_extensions = array( 'pdf', 'jpg', 'jpeg', 'png' );

                if (
                    ! in_array( $extension, $allowed_extensions, true ) ||
                    ( ! empty( $check['ext'] ) && ! in_array( strtolower( $check['ext'] ), $allowed_extensions, true ) )
                ) {
                    $errors[] =
                        $document_label .
                        ' must be a PDF, JPG, JPEG or PNG file.';
                    continue;
                }

                $upload = wp_handle_upload(
                    $file,
                    array(
                        'test_form' => false,
                        'mimes'     => array(
                            'pdf'  => 'application/pdf',
                            'jpg'  => 'image/jpeg',
                            'jpeg' => 'image/jpeg',
                            'png'  => 'image/png',
                        ),
                    )
                );

                if ( isset( $upload['error'] ) ) {
                    $errors[] = $document_label . ': ' . $upload['error'];
                    continue;
                }

                $uploaded_documents[ $field_name ] = array(
                    'label' => $document_label,
                    'url'   => esc_url_raw( $upload['url'] ),
                    'file'  => sanitize_text_field( $upload['file'] ),
                );
            }


            if ( ! empty( $errors ) ) {

                $message = '
                    <div style="
                        padding:16px;
                        background:#fee2e2;
                        color:#991b1b;
                        border-radius:8px;
                        margin-bottom:20px;
                    ">
                        <strong>Please correct the following:</strong>
                        <ul style="margin-bottom:0;">
                ';

                foreach ( $errors as $error ) {

                    $message .= '<li>' .
                        esc_html( $error ) .
                        '</li>';
                }

                $message .= '
                        </ul>
                    </div>
                ';

            } else {

                /* ---------------------------------------------
                 * CREATE WORDPRESS USER
                 * --------------------------------------------- */

                $username_base = sanitize_user(
                    strtolower(
                        preg_replace(
                            '/\s+/',
                            '',
                            $full_name
                        )
                    ),
                    true
                );

                if ( empty( $username_base ) ) {
                    $username_base = 'doctor';
                }

                $username = $username_base;
                $counter  = 1;

                while ( username_exists( $username ) ) {

                    $username =
                        $username_base .
                        $counter;

                    $counter++;
                }


                $user_id = wp_create_user(
                    $username,
                    $password,
                    $email
                );


                if ( is_wp_error( $user_id ) ) {

                    $message = '
                        <div style="
                            padding:14px;
                            background:#fee2e2;
                            color:#991b1b;
                            border-radius:8px;
                            margin-bottom:20px;
                        ">
                            Unable to create the account.
                            Please try again.
                        </div>
                    ';

                } else {

                    $user = new WP_User( $user_id );

                    /*
                     * IMPORTANT:
                     * Doctor is NOT approved yet.
                     */
                    $user->set_role(
                        'ayument_doctor_pending'
                    );


                    update_user_meta(
                        $user_id,
                        'ayument_doctor_name',
                        $full_name
                    );

                    /*
                     * Keep uploaded verification documents available
                     * to the doctor account as well as the application row.
                     * This keeps the Doctor Portal / My Documents area
                     * compatible with the registration workflow.
                     */
                    if ( ! empty( $uploaded_documents ) ) {
                        $encoded_documents = wp_json_encode( $uploaded_documents );

                        update_user_meta(
                            $user_id,
                            'ayument_verification_documents',
                            $encoded_documents
                        );

                        /*
                         * Compatibility key for portal components that
                         * read verification_documents from user meta.
                         */
                        update_user_meta(
                            $user_id,
                            'verification_documents',
                            $encoded_documents
                        );

                        /*
                         * Preserve the registration certificate in the
                         * legacy user-meta field when available.
                         */
                        if (
                            isset( $uploaded_documents['registration_certificate']['url'] )
                            && ! empty( $uploaded_documents['registration_certificate']['url'] )
                        ) {
                            update_user_meta(
                                $user_id,
                                'registration_document',
                                esc_url_raw(
                                    $uploaded_documents['registration_certificate']['url']
                                )
                            );
                        }
                    }


                    /* -----------------------------------------
                     * SAVE APPLICATION
                     * ----------------------------------------- */

                    global $wpdb;

                    $table_name =
                        $wpdb->prefix .
                        'ayument_doctor_applications';


                  
                    $wpdb->insert(
                        $table_name,
                        array(
                            'user_id'                => $user_id,
                            'full_name'              => $full_name,
                            'email'                  => $email,
                            'phone'                  => $phone,
                            'qualification'          => $qualification,
                            'specialization'         => $specialization,
                            'registration_number'    => $registration_number,
                            'registration_council'  => $registration_council,
                            'experience'             => $experience,
                            'clinic_name'            => $clinic_name,
                            'languages'              => $languages,
                            'professional_bio'       => $professional_bio,
                            'verification_documents' => wp_json_encode( $uploaded_documents ),
                            'status'                 => 'pending',
                            'created_at'             => current_time( 'mysql' ),
                        ),
                        array(
                            '%d', '%s', '%s', '%s', '%s',
                            '%s', '%s', '%s', '%s', '%s',
                            '%s', '%s', '%s', '%s', '%s',
                        )
                    );


                    $message = '
                        <div style="
                            padding:25px;
                            background:#ecfdf5;
                            border-left:5px solid #10b981;
                            border-radius:10px;
                            margin-bottom:25px;
                        ">

                            <h3 style="
                                margin-top:0;
                                color:#065f46;
                            ">
                                Registration Submitted
                            </h3>

                            <p>
                                Thank you for registering with AyuMent.
                            </p>

                            <p>
                                Your application has been submitted
                                for verification.
                            </p>

                            <p>
                                Your account will remain pending until
                                the AyuMent administration team approves
                                your professional credentials.
                            </p>

                        </div>
                    ';


                    /*
                     * Do not show the form after successful
                     * registration.
                     */
                    return $message;
                }
            }
        }
    }


    /* =====================================================
     * FORM
     * ===================================================== */

    ob_start();

    ?>

    <div
        style="
            max-width:900px;
            margin:40px auto;
            background:#ffffff;
            padding:35px;
            border-radius:18px;
            border:1px solid #e5e7eb;
            box-shadow:0 5px 25px rgba(0,0,0,0.06);
        "
    >

        <div style="margin-bottom:30px;">

            <h2
                style="
                    font-size:30px;
                    margin-bottom:8px;
                    color:#173f73;
                "
            >
                Doctor Registration
            </h2>

            <p style="color:#666;font-size:16px;">
                Join the AyuMent consultation platform as a
                verified Ayurvedic healthcare professional.
            </p>

        </div>


        <?php echo $message; ?>


        <form
            method="post"
            enctype="multipart/form-data"
        >

            <?php wp_nonce_field(
                'ayument_doctor_signup',
                'ayument_doctor_signup_nonce'
            ); ?>


            <h3
                style="
                    color:#173f73;
                    border-bottom:1px solid #e5e7eb;
                    padding-bottom:10px;
                "
            >
                Personal Information
            </h3>


            <div
                style="
                    display:grid;
                    grid-template-columns:1fr 1fr;
                    gap:20px;
                "
            >

                <div>

                    <label>
                        <strong>Full Name *</strong>
                    </label>

                    <input
                        type="text"
                        name="full_name"
                        required
                        style="
                            width:100%;
                            margin-top:7px;
                            padding:12px;
                            border:1px solid #d1d5db;
                            border-radius:8px;
                        "
                    >

                </div>


                <div>

                    <label>
                        <strong>Email *</strong>
                    </label>

                    <input
                        type="email"
                        name="email"
                        required
                        style="
                            width:100%;
                            margin-top:7px;
                            padding:12px;
                            border:1px solid #d1d5db;
                            border-radius:8px;
                        "
                    >

                </div>


                <div>

                    <label>
                        <strong>Phone *</strong>
                    </label>

                    <input
                        type="text"
                        name="phone"
                        required
                        style="
                            width:100%;
                            margin-top:7px;
                            padding:12px;
                            border:1px solid #d1d5db;
                            border-radius:8px;
                        "
                    >

                </div>


                <div>

                    <label>
                        <strong>Password *</strong>
                    </label>

                    <input
                        type="password"
                        name="password"
                        minlength="8"
                        required
                        style="
                            width:100%;
                            margin-top:7px;
                            padding:12px;
                            border:1px solid #d1d5db;
                            border-radius:8px;
                        "
                    >

                    <small>
                        Minimum 8 characters.
                    </small>

                </div>

            </div>


            <h3
                style="
                    color:#173f73;
                    border-bottom:1px solid #e5e7eb;
                    padding-bottom:10px;
                    margin-top:35px;
                "
            >
                Professional Information
            </h3>


            <div
                style="
                    display:grid;
                    grid-template-columns:1fr 1fr;
                    gap:20px;
                "
            >

                <div>

                    <label>
                        <strong>Qualification *</strong>
                    </label>

                    <input
                        type="text"
                        name="qualification"
                        placeholder="e.g. BAMS, MD (Ayurveda)"
                        required
                        style="
                            width:100%;
                            margin-top:7px;
                            padding:12px;
                            border:1px solid #d1d5db;
                            border-radius:8px;
                        "
                    >

                </div>


                <div>

                    <label>
                        <strong>Specialization</strong>
                    </label>

                    <input
                        type="text"
                        name="specialization"
                        placeholder="e.g. Psychiatry / Manas Roga"
                        style="
                            width:100%;
                            margin-top:7px;
                            padding:12px;
                            border:1px solid #d1d5db;
                            border-radius:8px;
                        "
                    >

                </div>


                <div>

                    <label>
                        <strong>Registration Number *</strong>
                    </label>

                    <input
                        type="text"
                        name="registration_number"
                        required
                        style="
                            width:100%;
                            margin-top:7px;
                            padding:12px;
                            border:1px solid #d1d5db;
                            border-radius:8px;
                        "
                    >

                </div>


                <div>

                    <label>
                        <strong>Registration Council</strong>
                    </label>

                    <input
                        type="text"
                        name="registration_council"
                        placeholder="e.g. State Medical Council"
                        style="
                            width:100%;
                            margin-top:7px;
                            padding:12px;
                            border:1px solid #d1d5db;
                            border-radius:8px;
                        "
                    >

                </div>


                <div>

                    <label>
                        <strong>Experience</strong>
                    </label>

                    <input
                        type="text"
                        name="experience"
                        placeholder="e.g. 5 years"
                        style="
                            width:100%;
                            margin-top:7px;
                            padding:12px;
                            border:1px solid #d1d5db;
                            border-radius:8px;
                        "
                    >

                </div>


                <div>

                    <label>
                        <strong>Clinic / Practice Name</strong>
                    </label>

                    <input
                        type="text"
                        name="clinic_name"
                        style="
                            width:100%;
                            margin-top:7px;
                            padding:12px;
                            border:1px solid #d1d5db;
                            border-radius:8px;
                        "
                    >

                </div>

            </div>


            <div style="margin-top:20px;">

                <label>
                    <strong>Languages</strong>
                </label>

                <input
                    type="text"
                    name="languages"
                    placeholder="Hindi, English, Sanskrit..."
                    style="
                        width:100%;
                        margin-top:7px;
                        padding:12px;
                        border:1px solid #d1d5db;
                        border-radius:8px;
                    "
                >

            </div>


            <div style="margin-top:20px;">

                <label>
                    <strong>Professional Bio</strong>
                </label>

                <textarea
                    name="professional_bio"
                    rows="5"
                    placeholder="Tell patients about your professional experience..."
                    style="
                        width:100%;
                        margin-top:7px;
                        padding:12px;
                        border:1px solid #d1d5db;
                        border-radius:8px;
                    "
                ></textarea>

            </div>


            <div
                style="
                    margin-top:35px;
                    padding:24px;
                    background:#f8fafc;
                    border:1px solid #e2e8f0;
                    border-radius:12px;
                "
            >

                <h3 style="color:#173f73;margin-top:0;">
                    Verification Documents
                </h3>

                <p style="color:#64748b;">
                    Upload clear copies. Accepted formats: PDF, JPG, JPEG and PNG.
                    Maximum size: 5 MB per document.
                </p>

                <div
                    style="
                        display:grid;
                        grid-template-columns:1fr 1fr;
                        gap:20px;
                    "
                >

                    <div>
                        <label><strong>Registration Certificate *</strong></label>
                        <input
                            type="file"
                            name="registration_certificate"
                            accept=".pdf,.jpg,.jpeg,.png"
                            required
                            style="
                                width:100%;
                                margin-top:8px;
                                padding:10px;
                                background:#ffffff;
                                border:1px solid #d1d5db;
                                border-radius:8px;
                            "
                        >
                    </div>

                    <div>
                        <label><strong>Qualification Certificate / Degree *</strong></label>
                        <input
                            type="file"
                            name="qualification_certificate"
                            accept=".pdf,.jpg,.jpeg,.png"
                            required
                            style="
                                width:100%;
                                margin-top:8px;
                                padding:10px;
                                background:#ffffff;
                                border:1px solid #d1d5db;
                                border-radius:8px;
                            "
                        >
                    </div>

                    <div>
                        <label><strong>Identity Proof</strong></label>
                        <input
                            type="file"
                            name="identity_proof"
                            accept=".pdf,.jpg,.jpeg,.png"
                            style="
                                width:100%;
                                margin-top:8px;
                                padding:10px;
                                background:#ffffff;
                                border:1px solid #d1d5db;
                                border-radius:8px;
                            "
                        >
                    </div>

                </div>

            </div>


            <div
                style="
                    margin-top:30px;
                    padding:18px;
                    background:#f8fafc;
                    border-radius:10px;
                    border:1px solid #e2e8f0;
                "
            >

                <strong>
                    Verification
                </strong>

                <p
                    style="
                        margin-bottom:0;
                        color:#64748b;
                    "
                >
                    Your professional credentials will be reviewed
                    by the AyuMent administration team before your
                    account becomes available for patient consultation.
                </p>

            </div>


            <button
                type="submit"
                name="ayument_doctor_signup_submit"
                value="1"
                style="
                    margin-top:30px;
                    background:#2563eb;
                    color:#ffffff;
                    border:0;
                    padding:14px 28px;
                    border-radius:8px;
                    font-size:16px;
                    font-weight:600;
                    cursor:pointer;
                "
            >
                Submit Doctor Registration
            </button>


        </form>

    </div>


    <style>

        @media (max-width:700px) {

            form > div[style*="grid-template-columns"] {
                grid-template-columns:1fr !important;
            }

        }

    </style>

    <?php

    return ob_get_clean();
}

add_shortcode(
    'ayument_doctor_signup',
    'ayument_doctors_signup_shortcode'
);