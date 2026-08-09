<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * AyuMent Appointments Module
 * Version 1.0
 */

/**
 * Create appointments table.
 */
function ayument_appointments_create_table() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'ayument_appointments';

    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        patient_id bigint(20) unsigned NOT NULL DEFAULT 0,
        patient_name varchar(255) NOT NULL DEFAULT '',
        appointment_date date NOT NULL,
        appointment_time time NOT NULL,
        appointment_type varchar(100) NOT NULL DEFAULT 'General Consultation',
        status varchar(50) NOT NULL DEFAULT 'Scheduled',
        reason text NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY patient_id (patient_id),
        KEY appointment_date (appointment_date),
        KEY status (status)
    ) {$charset_collate};";

    dbDelta( $sql );
}


/**
 * Try to create the table when the module is loaded.
 */
add_action( 'admin_init', 'ayument_appointments_create_table' );


/**
 * Get patients.
 *
 * Uses the AyuMent patients table if available.
 */
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


/**
 * Get patient name from patient ID.
 */
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


/**
 * Get appointment.
 */
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


/**
 * Delete appointment.
 */
function ayument_appointments_delete( $appointment_id ) {

    global $wpdb;

    $table_name = $wpdb->prefix . 'ayument_appointments';

    $wpdb->delete(
        $table_name,
        array(
            'id' => $appointment_id,
        ),
        array(
            '%d',
        )
    );
}


/**
 * Handle appointment actions.
 */
function ayument_appointments_handle_actions() {

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( ! isset( $_GET['page'] ) || 'ayument-appointments' !== $_GET['page'] ) {
        return;
    }

    /*
     * Delete
     */
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
                admin_url(
                    'admin.php?page=ayument-appointments&deleted=1'
                )
            );

            exit;
        }
    }

    /*
     * Save appointment.
     */
    if (
        isset( $_POST['ayument_save_appointment'] )
    ) {

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
            ? sanitize_text_field(
                wp_unslash( $_POST['patient_name'] )
            )
            : '';

        $appointment_date = isset( $_POST['appointment_date'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['appointment_date'] )
            )
            : '';

        $appointment_time = isset( $_POST['appointment_time'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['appointment_time'] )
            )
            : '';

        $appointment_type = isset( $_POST['appointment_type'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['appointment_type'] )
            )
            : 'General Consultation';

        $status = isset( $_POST['status'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['status'] )
            )
            : 'Scheduled';

        $reason = isset( $_POST['reason'] )
            ? sanitize_textarea_field(
                wp_unslash( $_POST['reason'] )
            )
            : '';

        /*
         * If patient is selected, always prefer the stored patient name.
         */
        if ( $patient_id ) {

            $stored_patient_name =
                ayument_appointments_get_patient_name( $patient_id );

            if ( $stored_patient_name ) {
                $patient_name = $stored_patient_name;
            }
        }

        /*
         * Basic validation.
         */
        if ( empty( $appointment_date ) ) {
            $appointment_date = current_time( 'Y-m-d' );
        }

        if ( empty( $appointment_time ) ) {
            $appointment_time = '09:00';
        }

        $data = array(
            'patient_id'        => $patient_id,
            'patient_name'      => $patient_name,
            'appointment_date'  => $appointment_date,
            'appointment_time'  => $appointment_time,
            'appointment_type'  => $appointment_type,
            'status'            => $status,
            'reason'            => $reason,
            'updated_at'        => current_time( 'mysql' ),
        );

        $formats = array(
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
        );

        if ( $appointment_id ) {

            $wpdb->update(
                $table_name,
                $data,
                array(
                    'id' => $appointment_id,
                ),
                $formats,
                array(
                    '%d',
                )
            );

            $redirect_url = add_query_arg(
                array(
                    'page'            => 'ayument-appointments',
                    'updated'         => 1,
                    'appointment_id'  => $appointment_id,
                ),
                admin_url( 'admin.php' )
            );

        } else {

            $data['created_at'] = current_time( 'mysql' );

            $wpdb->insert(
                $table_name,
                $data,
                array(
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                )
            );

            $new_id = $wpdb->insert_id;

            $redirect_url = add_query_arg(
                array(
                    'page'           => 'ayument-appointments',
                    'saved'          => 1,
                    'appointment_id' => $new_id,
                ),
                admin_url( 'admin.php' )
            );
        }

        wp_safe_redirect( $redirect_url );

        exit;
    }
}

add_action(
    'admin_init',
    'ayument_appointments_handle_actions'
);


/**
 * Register menu.
 *
 * Only add this if the parent plugin has not already registered
 * the menu elsewhere.
 */
function ayument_appointments_menu() {

    /*
     * The main AyuMent plugin already loads this module.
     * The menu is intentionally registered here so the module
     * remains usable by itself.
     */
    add_submenu_page(
        'ayument-dashboard',
        'Appointments',
        'Appointments',
        'manage_options',
        'ayument-appointments',
        'ayument_appointments_page'
    );
}


/*
 * Prevent duplicate menu registration if the main plugin already
 * handles the menu.
 */
if ( ! function_exists( 'ayument_register_appointments_menu' ) ) {
    add_action(
        'admin_menu',
        'ayument_appointments_menu',
        30
    );
}


/**
 * Appointments page.
 */
function ayument_appointments_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to access this page.' );
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'ayument_appointments';

    /*
     * Make sure table exists.
     */
    ayument_appointments_create_table();

    /*
     * Determine patient from URL.
     */
    $patient_id = isset( $_GET['patient_id'] )
        ? absint( $_GET['patient_id'] )
        : 0;

    /*
     * Determine edit appointment.
     */
    $edit_id = isset( $_GET['edit'] )
        ? absint( $_GET['edit'] )
        : 0;

    $edit_appointment = null;

    if ( $edit_id ) {
        $edit_appointment = ayument_appointments_get( $edit_id );
    }

    /*
     * If editing, use that appointment's patient.
     */
    if (
        $edit_appointment &&
        ! empty( $edit_appointment->patient_id )
    ) {
        $patient_id = absint(
            $edit_appointment->patient_id
        );
    }

    /*
     * Patient list.
     */
    $patient_data = ayument_appointments_get_patients();

    $patients = $patient_data['patients'];

    /*
     * Patient name.
     */
    $selected_patient_name = '';

    if ( $patient_id ) {
        $selected_patient_name =
            ayument_appointments_get_patient_name(
                $patient_id
            );
    }

    if (
        $edit_appointment &&
        ! empty( $edit_appointment->patient_name )
    ) {
        $selected_patient_name =
            $edit_appointment->patient_name;
    }

    /*
     * Form defaults.
     */
    $form_date = current_time( 'Y-m-d' );
    $form_time = '09:00';
    $form_type = 'General Consultation';
    $form_status = 'Scheduled';
    $form_reason = '';

    if ( $edit_appointment ) {

        $form_date =
            $edit_appointment->appointment_date;

        $form_time =
            substr(
                $edit_appointment->appointment_time,
                0,
                5
            );

        $form_type =
            $edit_appointment->appointment_type;

        $form_status =
            $edit_appointment->status;

        $form_reason =
            $edit_appointment->reason;
    }

    /*
     * History.
     */
    if ( $patient_id ) {

        $appointments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                 FROM {$table_name}
                 WHERE patient_id = %d
                 ORDER BY appointment_date DESC,
                          appointment_time DESC",
                $patient_id
            )
        );

    } else {

        $appointments = $wpdb->get_results(
            "SELECT *
             FROM {$table_name}
             ORDER BY appointment_date DESC,
                      appointment_time DESC"
        );
    }

    ?>

    <div class="wrap">

        <style>

            .ayument-app-wrap {
                max-width: 1200px;
                margin: 30px auto;
                padding: 0 20px;
            }

            .ayument-app-hero {
                background: linear-gradient(
                    135deg,
                    #1e3a8a,
                    #2563eb
                );
                color: #ffffff;
                padding: 42px;
                border-radius: 24px;
                margin-bottom: 28px;
                box-shadow: 0 10px 30px rgba(
                    15,
                    23,
                    42,
                    0.12
                );
            }

            .ayument-app-hero h1 {
                color: #ffffff;
                font-size: 34px;
                margin: 0 0 10px;
            }

            .ayument-app-hero p {
                color: #ffffff;
                font-size: 16px;
                margin: 0;
                opacity: 0.95;
            }

            .ayument-app-card {
                background: #ffffff;
                border-radius: 22px;
                padding: 30px;
                margin-bottom: 28px;
                box-shadow: 0 8px 28px rgba(
                    15,
                    23,
                    42,
                    0.08
                );
            }

            .ayument-app-card h2 {
                color: #12306b;
                font-size: 24px;
                margin-top: 0;
            }

            .ayument-app-grid {
                display: grid;
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
                gap: 22px;
            }

            .ayument-app-field {
                display: flex;
                flex-direction: column;
            }

            .ayument-app-field.full {
                grid-column: 1 / -1;
            }

            .ayument-app-field label {
                font-weight: 600;
                margin-bottom: 8px;
                color: #183153;
            }

            .ayument-app-field input,
            .ayument-app-field select,
            .ayument-app-field textarea {
                width: 100%;
                border: 1px solid #d7e0ef;
                border-radius: 12px;
                padding: 13px 14px;
                font-size: 15px;
                box-sizing: border-box;
                background: #ffffff;
            }

            .ayument-app-field textarea {
                min-height: 110px;
                resize: vertical;
            }

            .ayument-app-field input:focus,
            .ayument-app-field select:focus,
            .ayument-app-field textarea:focus {
                border-color: #2563eb;
                box-shadow: 0 0 0 2px rgba(
                    37,
                    99,
                    235,
                    0.12
                );
                outline: none;
            }

            .ayument-app-actions {
                margin-top: 25px;
                display: flex;
                gap: 12px;
                flex-wrap: wrap;
            }

            .ayument-app-btn {
                display: inline-block;
                text-decoration: none;
                border: 0;
                border-radius: 10px;
                padding: 12px 20px;
                cursor: pointer;
                font-size: 15px;
                font-weight: 600;
            }

            .ayument-app-btn-primary {
                background: #2563eb;
                color: #ffffff;
            }

            .ayument-app-btn-secondary {
                background: #eaf2ff;
                color: #174ea6;
            }

            .ayument-app-btn-danger {
                background: #fee2e2;
                color: #b91c1c;
            }

            .ayument-app-table-wrap {
                overflow-x: auto;
            }

            .ayument-app-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
            }

            .ayument-app-table th {
                background: #f4f7fb;
                color: #183153;
                text-align: left;
                padding: 13px;
                border-bottom: 1px solid #e1e7f0;
            }

            .ayument-app-table td {
                padding: 13px;
                border-bottom: 1px solid #edf1f6;
                vertical-align: top;
            }

            .ayument-status {
                display: inline-block;
                padding: 5px 10px;
                border-radius: 999px;
                font-size: 12px;
                font-weight: 600;
            }

            .ayument-status-scheduled {
                background: #dbeafe;
                color: #1d4ed8;
            }

            .ayument-status-completed {
                background: #dcfce7;
                color: #15803d;
            }

            .ayument-status-cancelled {
                background: #fee2e2;
                color: #b91c1c;
            }

            .ayument-status-no-show {
                background: #fef3c7;
                color: #92400e;
            }

            .ayument-notice {
                padding: 14px 18px;
                border-radius: 10px;
                margin-bottom: 20px;
            }

            .ayument-notice-success {
                background: #ecfdf5;
                border-left: 5px solid #10b981;
                color: #047857;
            }

            .ayument-notice-info {
                background: #eff6ff;
                border-left: 5px solid #2563eb;
                color: #1d4ed8;
            }

            .ayument-notice-warning {
                background: #fff7ed;
                border-left: 5px solid #f97316;
                color: #c2410c;
            }

            .ayument-empty {
                padding: 25px 0;
                color: #64748b;
            }

            @media (max-width: 800px) {

                .ayument-app-grid {
                    grid-template-columns: 1fr;
                }

                .ayument-app-field.full {
                    grid-column: auto;
                }

                .ayument-app-hero {
                    padding: 28px;
                }

            }

        </style>

        <div class="ayument-app-wrap">

            <?php if ( isset( $_GET['saved'] ) ) : ?>

                <div class="ayument-notice ayument-notice-success">
                    <strong>
                        Appointment saved successfully.
                    </strong>
                </div>

            <?php endif; ?>

            <?php if ( isset( $_GET['updated'] ) ) : ?>

                <div class="ayument-notice ayument-notice-success">
                    <strong>
                        Appointment updated successfully.
                    </strong>
                </div>

            <?php endif; ?>

            <?php if ( isset( $_GET['deleted'] ) ) : ?>

                <div class="ayument-notice ayument-notice-success">
                    <strong>
                        Appointment deleted successfully.
                    </strong>
                </div>

            <?php endif; ?>


            <div class="ayument-app-hero">

                <h1>📅 Appointments</h1>

                <p>
                    Schedule and manage patient appointments
                    for AyuMent.
                </p>

            </div>


            <div class="ayument-app-card">

                <h2>
                    <?php
                    echo $edit_appointment
                        ? '✏️ Edit Appointment'
                        : '+ New Appointment';
                    ?>
                </h2>

                <?php if ( empty( $patients ) ) : ?>

                    <div class="ayument-notice ayument-notice-warning">

                        No patients were found in the AyuMent
                        patient database.

                        Please create a patient first.

                    </div>

                <?php endif; ?>


                <form method="post">

                    <?php
                    wp_nonce_field(
                        'ayument_save_appointment',
                        'ayument_appointment_nonce'
                    );
                    ?>

                    <input
                        type="hidden"
                        name="appointment_id"
                        value="<?php
                        echo esc_attr(
                            $edit_appointment
                                ? $edit_appointment->id
                                : 0
                        );
                        ?>"
                    >

                    <div class="ayument-app-grid">


                        <div class="ayument-app-field">

                            <label for="ayument_patient_id">
                                Patient
                            </label>

                            <select
                                name="patient_id"
                                id="ayument_patient_id"
                            >

                                <option value="">
                                    Select Patient
                                </option>

                                <?php foreach ( $patients as $patient ) : ?>

                                    <?php

                                    $pid =
                                        isset(
                                            $patient->{$patient_data['id_column']}
                                        )
                                        ? $patient->{$patient_data['id_column']}
                                        : 0;

                                    $pname =
                                        isset(
                                            $patient->{$patient_data['name_column']}
                                        )
                                        ? $patient->{$patient_data['name_column']}
                                        : '';

                                    ?>

                                    <option
                                        value="<?php echo esc_attr( $pid ); ?>"
                                        <?php
                                        selected(
                                            $patient_id,
                                            $pid
                                        );
                                        ?>
                                        data-name="<?php
                                        echo esc_attr( $pname );
                                        ?>"
                                    >
                                        <?php
                                        echo esc_html(
                                            $pname
                                        );
                                        ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="ayument-app-field">

                            <label for="ayument_patient_name">
                                Patient Name
                            </label>

                            <input
                                type="text"
                                name="patient_name"
                                id="ayument_patient_name"
                                value="<?php
                                echo esc_attr(
                                    $selected_patient_name
                                );
                                ?>"
                                placeholder="Patient name"
                                <?php
                                if ( $patient_id ) {
                                    echo 'readonly';
                                }
                                ?>
                            >

                        </div>


                        <div class="ayument-app-field">

                            <label for="ayument_appointment_date">
                                Appointment Date
                            </label>

                            <input
                                type="date"
                                name="appointment_date"
                                id="ayument_appointment_date"
                                value="<?php
                                echo esc_attr(
                                    $form_date
                                );
                                ?>"
                                required
                            >

                        </div>


                        <div class="ayument-app-field">

                            <label for="ayument_appointment_time">
                                Appointment Time
                            </label>

                            <input
                                type="time"
                                name="appointment_time"
                                id="ayument_appointment_time"
                                value="<?php
                                echo esc_attr(
                                    $form_time
                                );
                                ?>"
                                required
                            >

                        </div>


                        <div class="ayument-app-field">

                            <label for="ayument_appointment_type">
                                Appointment Type
                            </label>

                            <select
                                name="appointment_type"
                                id="ayument_appointment_type"
                            >

                                <?php

                                $types = array(
                                    'General Consultation',
                                    'Follow-up',
                                    'Prakriti Assessment',
                                    'HAM-D Assessment',
                                    'HAM-A Assessment',
                                    'AI Consultation',
                                    'Other',
                                );

                                foreach ( $types as $type ) :

                                ?>

                                    <option
                                        value="<?php
                                        echo esc_attr( $type );
                                        ?>"
                                        <?php
                                        selected(
                                            $form_type,
                                            $type
                                        );
                                        ?>
                                    >
                                        <?php
                                        echo esc_html( $type );
                                        ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="ayument-app-field">

                            <label for="ayument_status">
                                Status
                            </label>

                            <select
                                name="status"
                                id="ayument_status"
                            >

                                <?php

                                $statuses = array(
                                    'Scheduled',
                                    'Completed',
                                    'Cancelled',
                                    'No-show',
                                );

                                foreach (
                                    $statuses as $status
                                ) :

                                ?>

                                    <option
                                        value="<?php
                                        echo esc_attr( $status );
                                        ?>"
                                        <?php
                                        selected(
                                            $form_status,
                                            $status
                                        );
                                        ?>
                                    >
                                        <?php
                                        echo esc_html(
                                            $status
                                        );
                                        ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="ayument-app-field full">

                            <label for="ayument_reason">
                                Reason / Chief Complaint
                            </label>

                            <textarea
                                name="reason"
                                id="ayument_reason"
                                placeholder="Reason for appointment / presenting concern"
                            ><?php
                            echo esc_textarea(
                                $form_reason
                            );
                            ?></textarea>

                        </div>

                    </div>


                    <div class="ayument-app-actions">

                        <button
                            type="submit"
                            name="ayument_save_appointment"
                            class="ayument-app-btn ayument-app-btn-primary"
                        >
                            <?php
                            echo $edit_appointment
                                ? '✓ Update Appointment'
                                : '✓ Save Appointment';
                            ?>
                        </button>


                        <?php if ( $edit_appointment ) : ?>

                            <a
                                href="<?php
                                echo esc_url(
                                    admin_url(
                                        'admin.php?page=ayument-appointments'
                                    )
                                );
                                ?>"
                                class="ayument-app-btn ayument-app-btn-secondary"
                            >
                                Cancel Edit
                            </a>

                        <?php endif; ?>


                        <?php if ( $patient_id ) : ?>

                            <a
                                href="<?php
                                echo esc_url(
                                    admin_url(
                                        'admin.php?page=ayument-patients&patient_id=' .
                                        absint( $patient_id )
                                    )
                                );
                                ?>"
                                class="ayument-app-btn ayument-app-btn-secondary"
                            >
                                ← Patient Profile
                            </a>

                        <?php endif; ?>

                    </div>

                </form>

            </div>


            <div class="ayument-app-card">

                <h2>
                    📋 Appointment History
                </h2>


                <?php if ( empty( $appointments ) ) : ?>

                    <div class="ayument-empty">
                        No appointments recorded yet.
                    </div>

                <?php else : ?>

                    <div class="ayument-app-table-wrap">

                        <table class="ayument-app-table">

                            <thead>

                                <tr>

                                    <th>
                                        Date
                                    </th>

                                    <th>
                                        Time
                                    </th>

                                    <?php if ( ! $patient_id ) : ?>

                                        <th>
                                            Patient
                                        </th>

                                    <?php endif; ?>

                                    <th>
                                        Type
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Reason
                                    </th>

                                    <th>
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach (
                                    $appointments as $appointment
                                ) : ?>

                                    <?php

                                    $status_class =
                                        'ayument-status-scheduled';

                                    if (
                                        'Completed' ===
                                        $appointment->status
                                    ) {
                                        $status_class =
                                            'ayument-status-completed';
                                    }

                                    if (
                                        'Cancelled' ===
                                        $appointment->status
                                    ) {
                                        $status_class =
                                            'ayument-status-cancelled';
                                    }

                                    if (
                                        'No-show' ===
                                        $appointment->status
                                    ) {
                                        $status_class =
                                            'ayument-status-no-show';
                                    }

                                    ?>

                                    <tr>

                                        <td>
                                            <?php
                                            echo esc_html(
                                                date_i18n(
                                                    get_option(
                                                        'date_format'
                                                    ),
                                                    strtotime(
                                                        $appointment
                                                            ->appointment_date
                                                    )
                                                )
                                            );
                                            ?>
                                        </td>


                                        <td>
                                            <?php
                                            echo esc_html(
                                                date_i18n(
                                                    get_option(
                                                        'time_format'
                                                    ),
                                                    strtotime(
                                                        $appointment
                                                            ->appointment_time
                                                    )
                                                )
                                            );
                                            ?>
                                        </td>


                                        <?php if ( ! $patient_id ) : ?>

                                            <td>

                                                <strong>
                                                    <?php
                                                    echo esc_html(
                                                        $appointment
                                                            ->patient_name
                                                    );
                                                    ?>
                                                </strong>

                                            </td>

                                        <?php endif; ?>


                                        <td>
                                            <?php
                                            echo esc_html(
                                                $appointment
                                                    ->appointment_type
                                            );
                                            ?>
                                        </td>


                                        <td>

                                            <span
                                                class="ayument-status <?php
                                                echo esc_attr(
                                                    $status_class
                                                );
                                                ?>"
                                            >
                                                <?php
                                                echo esc_html(
                                                    $appointment->status
                                                );
                                                ?>
                                            </span>

                                        </td>


                                        <td>

                                            <?php

                                            $reason =
                                                $appointment->reason;

                                            if (
                                                strlen(
                                                    $reason
                                                ) > 80
                                            ) {
                                                $reason =
                                                    substr(
                                                        $reason,
                                                        0,
                                                        80
                                                    ) . '…';
                                            }

                                            echo esc_html(
                                                $reason
                                            );

                                            ?>

                                        </td>


                                        <td>

                                            <div
                                                style="
                                                display:flex;
                                                gap:6px;
                                                flex-wrap:wrap;
                                                "
                                            >

                                                <a
                                                    href="<?php
                                                    echo esc_url(
                                                        add_query_arg(
                                                            array(
                                                                'page' =>
                                                                    'ayument-appointments',
                                                                'edit' =>
                                                                    $appointment
                                                                        ->id,
                                                            ),
                                                            admin_url(
                                                                'admin.php'
                                                            )
                                                        )
                                                    );
                                                    ?>"
                                                    class="ayument-app-btn ayument-app-btn-secondary"
                                                >
                                                    Edit
                                                </a>


                                                <a
                                                    href="<?php
                                                    echo esc_url(
                                                        wp_nonce_url(
                                                            add_query_arg(
                                                                array(
                                                                    'page' =>
                                                                        'ayument-appointments',
                                                                    'action' =>
                                                                        'delete',
                                                                    'appointment_id' =>
                                                                        $appointment
                                                                            ->id,
                                                                ),
                                                                admin_url(
                                                                    'admin.php'
                                                                )
                                                            ),
                                                            'ayument_delete_appointment_' .
                                                            $appointment->id
                                                        )
                                                    );
                                                    ?>"
                                                    class="ayument-app-btn ayument-app-btn-danger"
                                                    onclick="return confirm('Are you sure you want to delete this appointment?');"
                                                >
                                                    Delete
                                                </a>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </div>


        <script>

            document.addEventListener(
                'DOMContentLoaded',
                function () {

                    const patientSelect =
                        document.getElementById(
                            'ayument_patient_id'
                        );

                    const patientName =
                        document.getElementById(
                            'ayument_patient_name'
                        );

                    if (
                        patientSelect &&
                        patientName
                    ) {

                        patientSelect.addEventListener(
                            'change',
                            function () {

                                const option =
                                    this.options[
                                        this.selectedIndex
                                    ];

                                const name =
                                    option
                                    ? option.dataset.name
                                    : '';

                                patientName.value =
                                    name;

                                if ( name ) {
                                    patientName.readOnly =
                                        true;
                                } else {
                                    patientName.readOnly =
                                        false;
                                }

                            }
                        );

                    }

                }
            );

        </script>

    </div>

    <?php
}