<?php
/**
 * AyuMent Doctor Portal
 *
 * Frontend dashboard for registered doctors.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * ---------------------------------------------------------
 * DOCTOR DASHBOARD SHORTCODE
 * ---------------------------------------------------------
 */

function ayument_doctor_dashboard_shortcode() {

    if ( ! is_user_logged_in() ) {
        return '
        <div style="
            max-width:700px;
            margin:40px auto;
            padding:40px;
            background:#fff;
            border-radius:18px;
            text-align:center;
            box-shadow:0 8px 30px rgba(0,0,0,.08);
        ">
            <h2>AyuMent Doctor Portal</h2>
            <p>Please log in to access your Doctor Portal.</p>
            <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '"
               style="
                   display:inline-block;
                   margin-top:15px;
                   padding:12px 24px;
                   background:#2563eb;
                   color:#fff;
                   text-decoration:none;
                   border-radius:8px;
               ">
               Doctor Login
            </a>
        </div>';
    }


    $user = wp_get_current_user();

    $allowed_roles = array(
        'ayument_doctor',
        'ayument_doctor_pending',
        'administrator',
    );

    $is_allowed = false;

    foreach ( $allowed_roles as $role ) {
        if ( in_array( $role, (array) $user->roles, true ) ) {
            $is_allowed = true;
            break;
        }
    }

    if ( ! $is_allowed ) {
        return '
        <div style="
            max-width:700px;
            margin:40px auto;
            padding:40px;
            background:#fff;
            border-radius:18px;
            text-align:center;
        ">
            <h2>Doctor Portal</h2>
            <p>Your account does not have access to the Doctor Portal.</p>
        </div>';
    }


    global $wpdb;

    $table_name = $wpdb->prefix . 'ayument_doctor_applications';

    $application = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table_name}
             WHERE user_id = %d
             ORDER BY id DESC
             LIMIT 1",
            $user->ID
        )
    );


    /*
     * If application does not exist.
     */

    if ( ! $application ) {

        return '
        <div style="
            max-width:850px;
            margin:40px auto;
            padding:40px;
            background:#fff;
            border-radius:18px;
            box-shadow:0 8px 30px rgba(0,0,0,.08);
        ">
            <h2>AyuMent Doctor Portal</h2>

            <div style="
                margin-top:20px;
                padding:20px;
                background:#fff7ed;
                border-left:5px solid #f59e0b;
                border-radius:8px;
            ">
                <strong>No doctor application found.</strong>
                <p>
                    Please complete your doctor registration first.
                </p>
            </div>
        </div>';
    }


    /*
     * Status.
     */

    $status = strtolower( trim( $application->status ) );

    $status_label = ucfirst( $status );

    $status_background = '#fef3c7';
    $status_color      = '#92400e';

    if ( 'approved' === $status ) {
        $status_background = '#dcfce7';
        $status_color      = '#166534';
    }

    if ( 'rejected' === $status ) {
        $status_background = '#fee2e2';
        $status_color      = '#991b1b';
    }


    /*
     * Documents.
     *
     * New registrations store multiple verification documents as JSON
     * in verification_documents. Keep support for the legacy
     * registration_document and profile_photo columns as well.
     */

    $documents = array();

    if ( ! empty( $application->verification_documents ) ) {

        $decoded_documents = json_decode(
            $application->verification_documents,
            true
        );

        if ( is_array( $decoded_documents ) ) {

            foreach ( $decoded_documents as $document ) {

                if ( ! is_array( $document ) ) {
                    continue;
                }

                $document_url = ! empty( $document['url'] )
                    ? esc_url_raw( $document['url'] )
                    : '';

                if ( empty( $document_url ) ) {
                    continue;
                }

                $document_name = ! empty( $document['label'] )
                    ? sanitize_text_field( $document['label'] )
                    : (
                        ! empty( $document['name'] )
                            ? sanitize_text_field( $document['name'] )
                            : 'Verification Document'
                    );

                $documents[] = array(
                    'name' => $document_name,
                    'url'  => $document_url,
                );
            }
        }
    }

    /*
     * Legacy fallback.
     * Only add these when the same URL is not already present.
     */

    if ( ! empty( $application->registration_document ) ) {

        $legacy_registration_url = esc_url_raw(
            $application->registration_document
        );

        $already_exists = false;

        foreach ( $documents as $existing_document ) {

            if (
                ! empty( $existing_document['url'] )
                && $existing_document['url'] === $legacy_registration_url
            ) {
                $already_exists = true;
                break;
            }
        }

        if ( ! $already_exists ) {
            $documents[] = array(
                'name' => 'Registration Document',
                'url'  => $legacy_registration_url,
            );
        }
    }

    if ( ! empty( $application->profile_photo ) ) {

        $profile_photo_url = esc_url_raw(
            $application->profile_photo
        );

        $already_exists = false;

        foreach ( $documents as $existing_document ) {

            if (
                ! empty( $existing_document['url'] )
                && $existing_document['url'] === $profile_photo_url
            ) {
                $already_exists = true;
                break;
            }
        }

        if ( ! $already_exists ) {
            $documents[] = array(
                'name' => 'Profile Photo',
                'url'  => $profile_photo_url,
            );
        }
    }


    ob_start();
    ?>

    <div style="
        max-width:1100px;
        margin:40px auto;
        font-family:Arial,sans-serif;
    ">

        <!-- HEADER -->

        <div style="
            background:linear-gradient(135deg,#ffffff,#f8fafc);
            padding:35px;
            border-radius:20px;
            border:1px solid #e5e7eb;
            box-shadow:0 8px 30px rgba(0,0,0,.06);
        ">

            <div style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                gap:20px;
                flex-wrap:wrap;
            ">

                <div>

                    <div style="
                        font-size:14px;
                        color:#64748b;
                        margin-bottom:8px;
                    ">
                        AyuMent Doctor Portal
                    </div>

                    <h1 style="
                        margin:0;
                        font-size:34px;
                        color:#172033;
                    ">
                        Welcome, Dr. <?php echo esc_html( $application->full_name ); ?>
                    </h1>

                    <p style="
                        margin:10px 0 0;
                        color:#64748b;
                    ">
                        Manage your professional profile and AyuMent activities.
                    </p>

                </div>


                <div style="
                    background:<?php echo esc_attr( $status_background ); ?>;
                    color:<?php echo esc_attr( $status_color ); ?>;
                    padding:12px 20px;
                    border-radius:30px;
                    font-weight:700;
                ">
                    <?php echo esc_html( $status_label ); ?>
                </div>

            </div>

        </div>


        <!-- APPLICATION STATUS -->

        <div style="
            margin-top:25px;
            background:#fff;
            padding:30px;
            border-radius:18px;
            border:1px solid #e5e7eb;
            box-shadow:0 6px 25px rgba(0,0,0,.05);
        ">

            <h2 style="margin-top:0;">
                Application Status
            </h2>

            <div style="
                display:grid;
                grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
                gap:15px;
                margin-top:20px;
            ">

                <div style="
                    padding:20px;
                    background:#f8fafc;
                    border-radius:12px;
                ">
                    <strong>Application</strong>
                    <div style="
                        margin-top:8px;
                        font-size:20px;
                        font-weight:700;
                    ">
                        <?php echo esc_html( $status_label ); ?>
                    </div>
                </div>


                <div style="
                    padding:20px;
                    background:#f8fafc;
                    border-radius:12px;
                ">
                    <strong>Qualification</strong>
                    <div style="
                        margin-top:8px;
                    ">
                        <?php echo esc_html( $application->qualification ); ?>
                    </div>
                </div>


                <div style="
                    padding:20px;
                    background:#f8fafc;
                    border-radius:12px;
                ">
                    <strong>Registration No.</strong>
                    <div style="
                        margin-top:8px;
                    ">
                        <?php
                        echo esc_html(
                            $application->registration_number
                                ? $application->registration_number
                                : 'Not provided'
                        );
                        ?>
                    </div>
                </div>

            </div>


            <?php if ( ! empty( $application->admin_notes ) ) : ?>

                <div style="
                    margin-top:20px;
                    padding:20px;
                    background:#eff6ff;
                    border-left:5px solid #2563eb;
                    border-radius:8px;
                ">

                    <strong>Admin Message</strong>

                    <p style="margin-bottom:0;">
                        <?php echo esc_html( $application->admin_notes ); ?>
                    </p>

                </div>

            <?php endif; ?>

        </div>


        <!-- PROFILE -->

        <div style="
            margin-top:25px;
            background:#fff;
            padding:30px;
            border-radius:18px;
            border:1px solid #e5e7eb;
            box-shadow:0 6px 25px rgba(0,0,0,.05);
        ">

            <h2 style="margin-top:0;">
                Professional Profile
            </h2>

            <div style="
                display:grid;
                grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
                gap:20px;
                margin-top:20px;
            ">

                <div>
                    <strong>Name</strong>
                    <p><?php echo esc_html( $application->full_name ); ?></p>
                </div>

                <div>
                    <strong>Email</strong>
                    <p><?php echo esc_html( $application->email ); ?></p>
                </div>

                <div>
                    <strong>Phone</strong>
                    <p><?php echo esc_html( $application->phone ); ?></p>
                </div>

                <div>
                    <strong>Specialization</strong>
                    <p>
                        <?php
                        echo esc_html(
                            $application->specialization
                                ? $application->specialization
                                : 'Not provided'
                        );
                        ?>
                    </p>
                </div>

                <div>
                    <strong>Experience</strong>
                    <p>
                        <?php
                        echo esc_html(
                            $application->experience
                                ? $application->experience
                                : 'Not provided'
                        );
                        ?>
                    </p>
                </div>

                <div>
                    <strong>Clinic</strong>
                    <p>
                        <?php
                        echo esc_html(
                            $application->clinic_name
                                ? $application->clinic_name
                                : 'Not provided'
                        );
                        ?>
                    </p>
                </div>

            </div>

        </div>


        <!-- DOCUMENTS -->

        <div style="
            margin-top:25px;
            background:#fff;
            padding:30px;
            border-radius:18px;
            border:1px solid #e5e7eb;
            box-shadow:0 6px 25px rgba(0,0,0,.05);
        ">

            <h2 style="margin-top:0;">
                My Documents
            </h2>

            <?php if ( ! empty( $documents ) ) : ?>

                <div style="
                    display:grid;
                    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
                    gap:15px;
                    margin-top:20px;
                ">

                    <?php foreach ( $documents as $document ) : ?>

                        <div style="
                            padding:20px;
                            background:#f8fafc;
                            border-radius:12px;
                            border:1px solid #e2e8f0;
                        ">

                            <strong>
                                <?php echo esc_html( $document['name'] ); ?>
                            </strong>

                            <br><br>

                            <a
                                href="<?php echo esc_url( $document['url'] ); ?>"
                                target="_blank"
                                rel="noopener"
                                style="
                                    display:inline-block;
                                    padding:9px 16px;
                                    background:#2563eb;
                                    color:#fff;
                                    text-decoration:none;
                                    border-radius:7px;
                                "
                            >
                                View Document
                            </a>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else : ?>

                <div style="
                    padding:20px;
                    background:#f8fafc;
                    border-radius:10px;
                    color:#64748b;
                ">
                    No documents uploaded.
                </div>

            <?php endif; ?>

        </div>


        <!-- QUICK ACTIONS -->

        <div style="
            margin-top:25px;
            background:#fff;
            padding:30px;
            border-radius:18px;
            border:1px solid #e5e7eb;
            box-shadow:0 6px 25px rgba(0,0,0,.05);
        ">

            <h2 style="margin-top:0;">
                Quick Actions
            </h2>

            <div style="
                display:grid;
                grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
                gap:15px;
                margin-top:20px;
            ">

                <?php
                $actions = array(
                    'My Profile',
                    'Appointments',
                    'My Patients',
                    'Prescriptions',
                    'Consultations',
                );

                foreach ( $actions as $action ) :
                ?>

                    <div style="
                        padding:22px;
                        background:#f8fafc;
                        border-radius:12px;
                        text-align:center;
                        border:1px solid #e2e8f0;
                    ">

                        <strong>
                            <?php echo esc_html( $action ); ?>
                        </strong>

                        <div style="
                            margin-top:8px;
                            color:#94a3b8;
                            font-size:13px;
                        ">
                            Coming next
                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>


        <!-- LOGOUT -->

        <div style="
            margin-top:25px;
            text-align:right;
        ">

            <a
                href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"
                style="
                    color:#dc2626;
                    text-decoration:none;
                    font-weight:600;
                "
            >
                Log out
            </a>

        </div>

    </div>

    <?php

    return ob_get_clean();
}


add_shortcode(
    'ayument_doctor_dashboard',
    'ayument_doctor_dashboard_shortcode'
);