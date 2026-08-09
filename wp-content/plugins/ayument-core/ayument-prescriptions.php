<?php
/**
 * AyuMent - Prescriptions Module
 *
 * Patient and consultation linked prescription management.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/* =========================================================
 * DATABASE
 * ========================================================= */

function ayument_prescriptions_table_name() {
    global $wpdb;

    return $wpdb->prefix . 'ayument_prescriptions';
}


function ayument_prescriptions_install_table() {

    global $wpdb;

    $table_name      = ayument_prescriptions_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        patient_db_id BIGINT(20) UNSIGNED NOT NULL,
        patient_id VARCHAR(30) NOT NULL,
        consultation_id BIGINT(20) UNSIGNED NULL,

        prescription_date DATE NOT NULL,

        diagnosis VARCHAR(255) NULL,
        clinical_notes LONGTEXT NULL,

        medicines LONGTEXT NULL,

        general_instructions LONGTEXT NULL,
        pathya LONGTEXT NULL,
        apathya LONGTEXT NULL,

        follow_up_date DATE NULL,
        follow_up_notes LONGTEXT NULL,

        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,

        PRIMARY KEY (id),
        KEY patient_db_id (patient_db_id),
        KEY patient_id (patient_id),
        KEY consultation_id (consultation_id),
        KEY prescription_date (prescription_date)

    ) {$charset_collate};";

    dbDelta( $sql );
}


function ayument_prescriptions_ensure_table() {

    global $wpdb;

    $table_name = ayument_prescriptions_table_name();

    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        )
    );

    if ( $exists !== $table_name ) {
        ayument_prescriptions_install_table();
    }
}


/* =========================================================
 * ADMIN PAGE
 * ========================================================= */

function ayument_prescriptions_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die(
            esc_html__(
                'You do not have permission to access this page.'
            )
        );
    }

    ayument_prescriptions_ensure_table();

    global $wpdb;

    $patients_table       = $wpdb->prefix . 'ayument_patients';
    $consultations_table  = $wpdb->prefix . 'ayument_consultations';
    $prescriptions_table  = ayument_prescriptions_table_name();

    $patient_db_id = isset( $_GET['patient_id'] )
        ? absint( $_GET['patient_id'] )
        : 0;

    $consultation_id = isset( $_GET['consultation_id'] )
        ? absint( $_GET['consultation_id'] )
        : 0;

    $message      = '';
    $message_type = 'success';


    /* =====================================================
     * DELETE PRESCRIPTION
     * ===================================================== */

    if (
        isset( $_GET['ayument_delete_prescription'] ) &&
        isset( $_GET['_wpnonce'] )
    ) {

        $delete_id = absint(
            $_GET['ayument_delete_prescription']
        );

        if (
            wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash( $_GET['_wpnonce'] )
                ),
                'ayument_delete_prescription_' . $delete_id
            )
        ) {

            $wpdb->delete(
                $prescriptions_table,
                array(
                    'id' => $delete_id,
                ),
                array( '%d' )
            );

            $message = 'Prescription deleted successfully.';
        }
    }


    /* =====================================================
     * SAVE PRESCRIPTION
     * ===================================================== */

    if (
        isset( $_POST['ayument_save_prescription'] )
    ) {

        if (
            ! isset(
                $_POST['ayument_prescription_nonce']
            ) ||
            ! wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash(
                        $_POST['ayument_prescription_nonce']
                    )
                ),
                'ayument_save_prescription'
            )
        ) {

            wp_die( 'Security check failed.' );
        }


        $patient_db_id = isset( $_POST['patient_db_id'] )
            ? absint( $_POST['patient_db_id'] )
            : 0;


        $consultation_id = isset( $_POST['consultation_id'] )
            ? absint( $_POST['consultation_id'] )
            : 0;


        $patient = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$patients_table}
                 WHERE id = %d
                 LIMIT 1",
                $patient_db_id
            )
        );


        if ( ! $patient ) {

            $message      = 'Patient not found.';
            $message_type = 'error';

        } else {

            $prescription_date = isset(
                $_POST['prescription_date']
            )
                ? sanitize_text_field(
                    wp_unslash(
                        $_POST['prescription_date']
                    )
                )
                : current_time( 'Y-m-d' );


            $diagnosis = isset(
                $_POST['diagnosis']
            )
                ? sanitize_text_field(
                    wp_unslash(
                        $_POST['diagnosis']
                    )
                )
                : '';


            $clinical_notes = isset(
                $_POST['clinical_notes']
            )
                ? sanitize_textarea_field(
                    wp_unslash(
                        $_POST['clinical_notes']
                    )
                )
                : '';


            $general_instructions = isset(
                $_POST['general_instructions']
            )
                ? sanitize_textarea_field(
                    wp_unslash(
                        $_POST['general_instructions']
                    )
                )
                : '';


            $pathya = isset(
                $_POST['pathya']
            )
                ? sanitize_textarea_field(
                    wp_unslash(
                        $_POST['pathya']
                    )
                )
                : '';


            $apathya = isset(
                $_POST['apathya']
            )
                ? sanitize_textarea_field(
                    wp_unslash(
                        $_POST['apathya']
                    )
                )
                : '';


            $follow_up_date = isset(
                $_POST['follow_up_date']
            )
                ? sanitize_text_field(
                    wp_unslash(
                        $_POST['follow_up_date']
                    )
                )
                : '';


            $follow_up_notes = isset(
                $_POST['follow_up_notes']
            )
                ? sanitize_textarea_field(
                    wp_unslash(
                        $_POST['follow_up_notes']
                    )
                )
                : '';


            /*
             * Medicines are stored as JSON.
             */

            $medicines = array();


            if (
                isset( $_POST['medicine_name'] ) &&
                is_array( $_POST['medicine_name'] )
            ) {

                $names = $_POST['medicine_name'];

                $dosages = isset(
                    $_POST['medicine_dose']
                ) && is_array(
                    $_POST['medicine_dose']
                )
                    ? $_POST['medicine_dose']
                    : array();


                $frequencies = isset(
                    $_POST['medicine_frequency']
                ) && is_array(
                    $_POST['medicine_frequency']
                )
                    ? $_POST['medicine_frequency']
                    : array();


                $timings = isset(
                    $_POST['medicine_timing']
                ) && is_array(
                    $_POST['medicine_timing']
                )
                    ? $_POST['medicine_timing']
                    : array();


                $anupanas = isset(
                    $_POST['medicine_anupana']
                ) && is_array(
                    $_POST['medicine_anupana']
                )
                    ? $_POST['medicine_anupana']
                    : array();


                $durations = isset(
                    $_POST['medicine_duration']
                ) && is_array(
                    $_POST['medicine_duration']
                )
                    ? $_POST['medicine_duration']
                    : array();


                $quantities = isset(
                    $_POST['medicine_quantity']
                ) && is_array(
                    $_POST['medicine_quantity']
                )
                    ? $_POST['medicine_quantity']
                    : array();


                $instructions = isset(
                    $_POST['medicine_instruction']
                ) && is_array(
                    $_POST['medicine_instruction']
                )
                    ? $_POST['medicine_instruction']
                    : array();


                foreach ( $names as $index => $name ) {

                    $name = sanitize_text_field(
                        wp_unslash( $name )
                    );


                    if ( '' === $name ) {
                        continue;
                    }


                    $medicines[] = array(

                        'name' => $name,

                        'dose' => isset(
                            $dosages[ $index ]
                        )
                            ? sanitize_text_field(
                                wp_unslash(
                                    $dosages[ $index ]
                                )
                            )
                            : '',

                        'frequency' => isset(
                            $frequencies[ $index ]
                        )
                            ? sanitize_text_field(
                                wp_unslash(
                                    $frequencies[ $index ]
                                )
                            )
                            : '',

                        'timing' => isset(
                            $timings[ $index ]
                        )
                            ? sanitize_text_field(
                                wp_unslash(
                                    $timings[ $index ]
                                )
                            )
                            : '',

                        'anupana' => isset(
                            $anupanas[ $index ]
                        )
                            ? sanitize_text_field(
                                wp_unslash(
                                    $anupanas[ $index ]
                                )
                            )
                            : '',

                        'duration' => isset(
                            $durations[ $index ]
                        )
                            ? sanitize_text_field(
                                wp_unslash(
                                    $durations[ $index ]
                                )
                            )
                            : '',

                        'quantity' => isset(
                            $quantities[ $index ]
                        )
                            ? sanitize_text_field(
                                wp_unslash(
                                    $quantities[ $index ]
                                )
                            )
                            : '',

                        'instruction' => isset(
                            $instructions[ $index ]
                        )
                            ? sanitize_text_field(
                                wp_unslash(
                                    $instructions[ $index ]
                                )
                            )
                            : '',
                    );
                }
            }


            $data = array(

                'patient_db_id' => $patient_db_id,

                'patient_id' => $patient->patient_id,

                'consultation_id' => $consultation_id
                    ? $consultation_id
                    : null,

                'prescription_date' => $prescription_date,

                'diagnosis' => $diagnosis,

                'clinical_notes' => $clinical_notes,

                'medicines' => wp_json_encode(
                    $medicines
                ),

                'general_instructions' =>
                    $general_instructions,

                'pathya' => $pathya,

                'apathya' => $apathya,

                'follow_up_date' =>
                    $follow_up_date
                        ? $follow_up_date
                        : null,

                'follow_up_notes' =>
                    $follow_up_notes,

                'updated_at' =>
                    current_time( 'mysql' ),
            );


            $format = array(

                '%d',
                '%s',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            );


            $edit_id = isset(
                $_POST['prescription_id']
            )
                ? absint(
                    $_POST['prescription_id']
                )
                : 0;


            if ( $edit_id ) {

                $wpdb->update(
                    $prescriptions_table,
                    $data,
                    array(
                        'id' => $edit_id,
                    ),
                    $format,
                    array( '%d' )
                );

                $message =
                    'Prescription updated successfully.';

            } else {

                $data['created_at'] =
                    current_time( 'mysql' );

                $insert_format = array(

                    '%d',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                );

                $wpdb->insert(
                    $prescriptions_table,
                    $data,
                    $insert_format
                );

                $message =
                    'Prescription saved successfully.';
            }
        }
    }


    /* =====================================================
     * EDIT PRESCRIPTION
     * ===================================================== */

    $edit_prescription = null;


    if (
        isset( $_GET['edit_prescription'] )
    ) {

        $edit_id = absint(
            $_GET['edit_prescription']
        );


        $edit_prescription = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$prescriptions_table}
                 WHERE id = %d
                 LIMIT 1",
                $edit_id
            )
        );


        if ( $edit_prescription ) {

            $patient_db_id =
                absint(
                    $edit_prescription->patient_db_id
                );

            $consultation_id =
                absint(
                    $edit_prescription->consultation_id
                );
        }
    }


    /* =====================================================
     * PATIENT
     * ===================================================== */

    $patient = null;


    if ( $patient_db_id ) {

        $patient = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$patients_table}
                 WHERE id = %d
                 LIMIT 1",
                $patient_db_id
            )
        );
    }


    /* =====================================================
     * CONSULTATION
     * ===================================================== */

    $consultation = null;


    if (
        $consultation_id &&
        $wpdb->get_var(
            $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $consultations_table
            )
        ) === $consultations_table
    ) {

        $consultation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$consultations_table}
                 WHERE id = %d
                 LIMIT 1",
                $consultation_id
            )
        );
    }


    /* =====================================================
     * LIST PRESCRIPTIONS
     * ===================================================== */

    $prescriptions = array();


    if ( $patient_db_id ) {

        $prescriptions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                 FROM {$prescriptions_table}
                 WHERE patient_db_id = %d
                 ORDER BY prescription_date DESC, id DESC",
                $patient_db_id
            )
        );

    } else {

        $prescriptions = $wpdb->get_results(
            "SELECT p.*, pt.name AS patient_name
             FROM {$prescriptions_table} p
             LEFT JOIN {$patients_table} pt
             ON p.patient_db_id = pt.id
             ORDER BY p.prescription_date DESC, p.id DESC
             LIMIT 50"
        );
    }


    /* =====================================================
     * DISPLAY
     * ===================================================== */

    ?>

    <div class="wrap ayument-prescriptions-wrap">

        <style>

            .ayument-prescriptions-wrap {
                max-width: 1400px;
                margin-top: 25px;
            }

            .ayument-prescription-header {
                background: linear-gradient(
                    135deg,
                    #172554,
                    #2563eb
                );
                color: #ffffff;
                padding: 32px 38px;
                border-radius: 20px;
                margin-bottom: 25px;
                box-shadow:
                    0 10px 30px
                    rgba(15, 23, 42, .12);
            }

            .ayument-prescription-header h1 {
                color: #ffffff;
                margin: 0 0 8px;
                font-size: 32px;
            }

            .ayument-prescription-header p {
                margin: 0;
                color: #dbeafe;
                font-size: 15px;
            }

            .ayument-prescription-patient {
                margin-top: 20px;
                display: flex;
                gap: 12px;
                align-items: center;
                flex-wrap: wrap;
            }

            .ayument-prescription-patient-badge {
                background: rgba(255,255,255,.15);
                padding: 8px 14px;
                border-radius: 20px;
                color: #ffffff;
                font-weight: 600;
            }

            .ayument-prescription-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 18px;
                padding: 28px;
                margin-bottom: 24px;
                box-shadow:
                    0 5px 20px
                    rgba(15,23,42,.06);
            }

            .ayument-prescription-card h2 {
                margin-top: 0;
                color: #172554;
                font-size: 23px;
            }

            .ayument-prescription-section {
                margin-top: 28px;
                padding-top: 22px;
                border-top: 1px solid #e2e8f0;
            }

            .ayument-prescription-section h3 {
                color: #1e3a8a;
                font-size: 18px;
                margin-bottom: 18px;
            }

            .ayument-prescription-grid {
                display: grid;
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
                gap: 20px;
            }

            .ayument-prescription-field {
                display: flex;
                flex-direction: column;
            }

            .ayument-prescription-field.full {
                grid-column: 1 / -1;
            }

            .ayument-prescription-field label {
                font-weight: 600;
                color: #334155;
                margin-bottom: 8px;
            }

            .ayument-prescription-field input,
            .ayument-prescription-field textarea,
            .ayument-prescription-field select {
                width: 100%;
                box-sizing: border-box;
                border: 1px solid #cbd5e1;
                border-radius: 10px;
                padding: 12px 14px;
                font-size: 14px;
                background: #ffffff;
            }

            .ayument-prescription-field textarea {
                min-height: 110px;
                resize: vertical;
            }

            .ayument-medicine {
                border: 1px solid #dbeafe;
                background: #f8fbff;
                border-radius: 14px;
                padding: 20px;
                margin-bottom: 16px;
            }

            .ayument-medicine-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 16px;
            }

            .ayument-medicine-header strong {
                color: #172554;
                font-size: 16px;
            }

            .ayument-remove-medicine {
                border: 0;
                background: #fee2e2;
                color: #991b1b;
                border-radius: 8px;
                padding: 7px 12px;
                cursor: pointer;
            }

            .ayument-add-medicine {
                border: 0;
                background: #dbeafe;
                color: #1d4ed8;
                border-radius: 9px;
                padding: 10px 16px;
                font-weight: 600;
                cursor: pointer;
            }

            .ayument-save-prescription {
                border: 0;
                background: #2563eb;
                color: #ffffff;
                padding: 13px 22px;
                border-radius: 10px;
                font-weight: 700;
                font-size: 15px;
                cursor: pointer;
            }

            .ayument-back-button {
                display: inline-block;
                text-decoration: none;
                background: #eff6ff;
                color: #1d4ed8;
                padding: 12px 18px;
                border-radius: 10px;
                font-weight: 600;
                margin-left: 8px;
            }

            .ayument-notice {
                background: #ecfdf5;
                border-left: 5px solid #10b981;
                color: #065f46;
                padding: 15px 18px;
                border-radius: 10px;
                margin-bottom: 20px;
            }

            .ayument-notice.error {
                background: #fef2f2;
                border-left-color: #ef4444;
                color: #991b1b;
            }

            .ayument-prescription-table {
                width: 100%;
                border-collapse: collapse;
            }

            .ayument-prescription-table th {
                background: #f8fafc;
                color: #475569;
                text-align: left;
                padding: 13px;
                border-bottom: 1px solid #e2e8f0;
                font-size: 13px;
            }

            .ayument-prescription-table td {
                padding: 14px 13px;
                border-bottom: 1px solid #eef2f7;
                color: #334155;
                vertical-align: top;
            }

            .ayument-action {
                display: inline-block;
                text-decoration: none;
                padding: 7px 11px;
                border-radius: 7px;
                margin-right: 5px;
                font-size: 12px;
                font-weight: 600;
            }

            .ayument-action.edit {
                background: #dbeafe;
                color: #1d4ed8;
            }

            .ayument-action.delete {
                background: #fee2e2;
                color: #b91c1c;
            }

            .ayument-empty {
                text-align: center;
                padding: 35px;
                color: #64748b;
            }

            @media(max-width: 800px) {

                .ayument-prescription-grid {
                    grid-template-columns: 1fr;
                }

                .ayument-prescription-field.full {
                    grid-column: auto;
                }
            }

        </style>


        <!-- HEADER -->

        <div class="ayument-prescription-header">

            <h1>💊 Prescriptions</h1>

            <p>
                Create and maintain patient-linked
                Ayurvedic prescriptions.
            </p>

            <?php if ( $patient ) : ?>

                <div class="ayument-prescription-patient">

                    <strong>
                        <?php
                        echo esc_html(
                            $patient->name
                        );
                        ?>
                    </strong>

                    <span class="ayument-prescription-patient-badge">
                        Patient ID:
                        <?php
                        echo esc_html(
                            $patient->patient_id
                        );
                        ?>
                    </span>

                </div>

            <?php endif; ?>

        </div>


        <?php if ( $message ) : ?>

            <div
                class="ayument-notice <?php
                echo 'error' === $message_type
                    ? 'error'
                    : '';
                ?>"
            >
                <?php
                echo esc_html(
                    $message
                );
                ?>
            </div>

        <?php endif; ?>


        <!-- FORM -->

        <div class="ayument-prescription-card">

            <h2>
                <?php
                echo $edit_prescription
                    ? '✎ Edit Prescription'
                    : '＋ New Prescription';
                ?>
            </h2>


            <?php if ( ! $patient ) : ?>

                <div class="ayument-notice error">
                    Please open this page from a patient
                    profile or select a patient below.
                </div>


                <div class="ayument-prescription-grid">

                    <div
                        class="ayument-prescription-field full"
                    >

                        <label>
                            Patient
                        </label>

                        <select
                            onchange="
                                if(this.value){
                                    window.location.href =
                                    '<?php echo esc_js(
                                        admin_url(
                                            'admin.php?page=ayument-prescriptions&patient_id='
                                        )
                                    ); ?>' + this.value;
                                }
                            "
                        >

                            <option value="">
                                Select Patient
                            </option>

                            <?php

                            $all_patients =
                                $wpdb->get_results(
                                    "SELECT id, name, patient_id
                                     FROM {$patients_table}
                                     ORDER BY name ASC"
                                );

                            foreach (
                                $all_patients
                                as $p
                            ) :

                            ?>

                                <option
                                    value="<?php
                                    echo esc_attr(
                                        $p->id
                                    );
                                    ?>"
                                >
                                    <?php
                                    echo esc_html(
                                        $p->name .
                                        ' — ' .
                                        $p->patient_id
                                    );
                                    ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>

            <?php else : ?>


                <form method="post">

                    <?php
                    wp_nonce_field(
                        'ayument_save_prescription',
                        'ayument_prescription_nonce'
                    );
                    ?>


                    <input
                        type="hidden"
                        name="patient_db_id"
                        value="<?php
                        echo esc_attr(
                            $patient->id
                        );
                        ?>"
                    >


                    <?php if ( $edit_prescription ) : ?>

                        <input
                            type="hidden"
                            name="prescription_id"
                            value="<?php
                            echo esc_attr(
                                $edit_prescription->id
                            );
                            ?>"
                        >

                    <?php endif; ?>


                    <input
                        type="hidden"
                        name="consultation_id"
                        value="<?php
                        echo esc_attr(
                            $consultation_id
                        );
                        ?>"
                    >


                    <!-- VISIT -->

                    <div class="ayument-prescription-section">

                        <h3>
                            📅 Prescription Information
                        </h3>

                        <div class="ayument-prescription-grid">

                            <div class="ayument-prescription-field">

                                <label>
                                    Prescription Date
                                </label>

                                <input
                                    type="date"
                                    name="prescription_date"
                                    value="<?php
                                    echo esc_attr(
                                        $edit_prescription
                                            ? $edit_prescription->prescription_date
                                            : current_time(
                                                'Y-m-d'
                                            )
                                    );
                                    ?>"
                                    required
                                >

                            </div>


                            <div class="ayument-prescription-field">

                                <label>
                                    Consultation
                                </label>

                                <?php if ( $consultation ) : ?>

                                    <input
                                        type="text"
                                        value="<?php
                                        echo esc_attr(
                                            'Consultation #' .
                                            $consultation->id .
                                            ' — ' .
                                            mysql2date(
                                                'd M Y',
                                                $consultation->visit_date
                                            )
                                        );
                                        ?>"
                                        readonly
                                    >

                                <?php else : ?>

                                    <input
                                        type="text"
                                        value="Standalone prescription"
                                        readonly
                                    >

                                <?php endif; ?>

                            </div>


                            <div
                                class="ayument-prescription-field full"
                            >

                                <label>
                                    Diagnosis
                                </label>

                                <input
                                    type="text"
                                    name="diagnosis"
                                    placeholder="Clinical / Ayurvedic diagnosis"
                                    value="<?php
                                    echo esc_attr(
                                        $edit_prescription
                                            ? $edit_prescription->diagnosis
                                            : (
                                                $consultation
                                                    ? (
                                                        $consultation->ayurvedic_diagnosis
                                                        ?: $consultation->clinical_diagnosis
                                                    )
                                                    : ''
                                            )
                                    );
                                    ?>"
                                >

                            </div>

                        </div>

                    </div>


                    <!-- MEDICINES -->

                    <div class="ayument-prescription-section">

                        <h3>
                            🌿 Medicines
                        </h3>


                        <div id="ayument-medicines">

                            <?php

                            $existing_medicines = array();

                            if ( $edit_prescription ) {

                                $decoded =
                                    json_decode(
                                        $edit_prescription->medicines,
                                        true
                                    );

                                if ( is_array( $decoded ) ) {
                                    $existing_medicines =
                                        $decoded;
                                }
                            }


                            if ( empty( $existing_medicines ) ) {

                                $existing_medicines[] =
                                    array();

                            }


                            foreach (
                                $existing_medicines
                                as $index => $medicine
                            ) :

                            ?>

                                <div
                                    class="ayument-medicine"
                                >

                                    <div
                                        class="ayument-medicine-header"
                                    >

                                        <strong>
                                            Medicine
                                            <?php
                                            echo esc_html(
                                                $index + 1
                                            );
                                            ?>
                                        </strong>

                                        <button
                                            type="button"
                                            class="ayument-remove-medicine"
                                            onclick="
                                                this.closest('.ayument-medicine').remove();
                                            "
                                        >
                                            Remove
                                        </button>

                                    </div>


                                    <div
                                        class="ayument-prescription-grid"
                                    >

                                        <div
                                            class="ayument-prescription-field full"
                                        >

                                            <label>
                                                Medicine Name
                                            </label>

                                            <input
                                                type="text"
                                                name="medicine_name[]"
                                                placeholder="e.g. Guduchi Churna"
                                                value="<?php
                                                echo esc_attr(
                                                    $medicine['name']
                                                        ?? ''
                                                );
                                                ?>"
                                            >

                                        </div>


                                        <div
                                            class="ayument-prescription-field"
                                        >

                                            <label>
                                                Dose
                                            </label>

                                            <input
                                                type="text"
                                                name="medicine_dose[]"
                                                placeholder="e.g. 3 g"
                                                value="<?php
                                                echo esc_attr(
                                                    $medicine['dose']
                                                        ?? ''
                                                );
                                                ?>"
                                            >

                                        </div>


                                        <div
                                            class="ayument-prescription-field"
                                        >

                                            <label>
                                                Frequency
                                            </label>

                                            <input
                                                type="text"
                                                name="medicine_frequency[]"
                                                placeholder="e.g. BD / TDS"
                                                value="<?php
                                                echo esc_attr(
                                                    $medicine['frequency']
                                                        ?? ''
                                                );
                                                ?>"
                                            >

                                        </div>


                                        <div
                                            class="ayument-prescription-field"
                                        >

                                            <label>
                                                Timing
                                            </label>

                                            <input
                                                type="text"
                                                name="medicine_timing[]"
                                                placeholder="e.g. Before food"
                                                value="<?php
                                                echo esc_attr(
                                                    $medicine['timing']
                                                        ?? ''
                                                );
                                                ?>"
                                            >

                                        </div>


                                        <div
                                            class="ayument-prescription-field"
                                        >

                                            <label>
                                                Anupana
                                            </label>

                                            <input
                                                type="text"
                                                name="medicine_anupana[]"
                                                placeholder="e.g. Warm water"
                                                value="<?php
                                                echo esc_attr(
                                                    $medicine['anupana']
                                                        ?? ''
                                                );
                                                ?>"
                                            >

                                        </div>


                                        <div
                                            class="ayument-prescription-field"
                                        >

                                            <label>
                                                Duration
                                            </label>

                                            <input
                                                type="text"
                                                name="medicine_duration[]"
                                                placeholder="e.g. 7 days"
                                                value="<?php
                                                echo esc_attr(
                                                    $medicine['duration']
                                                        ?? ''
                                                );
                                                ?>"
                                            >

                                        </div>


                                        <div
                                            class="ayument-prescription-field"
                                        >

                                            <label>
                                                Quantity
                                            </label>

                                            <input
                                                type="text"
                                                name="medicine_quantity[]"
                                                placeholder="e.g. 100 g"
                                                value="<?php
                                                echo esc_attr(
                                                    $medicine['quantity']
                                                        ?? ''
                                                );
                                                ?>"
                                            >

                                        </div>


                                        <div
                                            class="ayument-prescription-field full"
                                        >

                                            <label>
                                                Instructions
                                            </label>

                                            <input
                                                type="text"
                                                name="medicine_instruction[]"
                                                placeholder="Special instructions"
                                                value="<?php
                                                echo esc_attr(
                                                    $medicine['instruction']
                                                        ?? ''
                                                );
                                                ?>"
                                            >

                                        </div>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>


                        <button
                            type="button"
                            class="ayument-add-medicine"
                            onclick="ayumentAddMedicine();"
                        >
                            ＋ Add Medicine
                        </button>

                    </div>


                    <!-- NOTES -->

                    <div class="ayument-prescription-section">

                        <h3>
                            📝 Clinical Notes
                        </h3>

                        <div class="ayument-prescription-grid">

                            <div
                                class="ayument-prescription-field full"
                            >

                                <label>
                                    Clinical Notes
                                </label>

                                <textarea
                                    name="clinical_notes"
                                    placeholder="Relevant clinical observations"
                                ><?php
                                echo esc_textarea(
                                    $edit_prescription
                                        ? $edit_prescription->clinical_notes
                                        : ''
                                );
                                ?></textarea>

                            </div>


                            <div
                                class="ayument-prescription-field full"
                            >

                                <label>
                                    General Instructions
                                </label>

                                <textarea
                                    name="general_instructions"
                                    placeholder="General medication / lifestyle instructions"
                                ><?php
                                echo esc_textarea(
                                    $edit_prescription
                                        ? $edit_prescription->general_instructions
                                        : ''
                                );
                                ?></textarea>

                            </div>

                        </div>

                    </div>


                    <!-- PATHYA -->

                    <div class="ayument-prescription-section">

                        <h3>
                            🥗 Pathya & Apathya
                        </h3>

                        <div class="ayument-prescription-grid">

                            <div
                                class="ayument-prescription-field"
                            >

                                <label>
                                    Pathya
                                </label>

                                <textarea
                                    name="pathya"
                                    placeholder="Recommended diet and lifestyle"
                                ><?php
                                echo esc_textarea(
                                    $edit_prescription
                                        ? $edit_prescription->pathya
                                        : ''
                                );
                                ?></textarea>

                            </div>


                            <div
                                class="ayument-prescription-field"
                            >

                                <label>
                                    Apathya
                                </label>

                                <textarea
                                    name="apathya"
                                    placeholder="Avoid / restrict"
                                ><?php
                                echo esc_textarea(
                                    $edit_prescription
                                        ? $edit_prescription->apathya
                                        : ''
                                );
                                ?></textarea>

                            </div>

                        </div>

                    </div>


                    <!-- FOLLOW UP -->

                    <div class="ayument-prescription-section">

                        <h3>
                            📅 Follow-up
                        </h3>

                        <div class="ayument-prescription-grid">

                            <div
                                class="ayument-prescription-field"
                            >

                                <label>
                                    Follow-up Date
                                </label>

                                <input
                                    type="date"
                                    name="follow_up_date"
                                    value="<?php
                                    echo esc_attr(
                                        $edit_prescription
                                            ? $edit_prescription->follow_up_date
                                            : ''
                                    );
                                    ?>"
                                >

                            </div>


                            <div
                                class="ayument-prescription-field"
                            >

                                <label>
                                    Follow-up Notes
                                </label>

                                <textarea
                                    name="follow_up_notes"
                                    placeholder="What should be reviewed?"
                                ><?php
                                echo esc_textarea(
                                    $edit_prescription
                                        ? $edit_prescription->follow_up_notes
                                        : ''
                                );
                                ?></textarea>

                            </div>

                        </div>

                    </div>


                    <!-- BUTTONS -->

                    <div
                        style="
                            margin-top:28px;
                            padding-top:20px;
                            border-top:1px solid #e2e8f0;
                        "
                    >

                        <button
                            type="submit"
                            name="ayument_save_prescription"
                            value="1"
                            class="ayument-save-prescription"
                        >
                            ✓
                            <?php
                            echo $edit_prescription
                                ? 'Update Prescription'
                                : 'Save Prescription';
                            ?>
                        </button>


                        <a
                            href="<?php
                            echo esc_url(
                                admin_url(
                                    'admin.php?page=ayument-patients&patient=' .
                                    absint(
                                        $patient->id
                                    )
                                )
                            );
                            ?>"
                            class="ayument-back-button"
                        >
                            ← Patient Profile
                        </a>

                    </div>

                </form>

            <?php endif; ?>

        </div>


        <!-- HISTORY -->

        <div class="ayument-prescription-card">

            <h2>
                📋 Prescription History
            </h2>


            <?php if ( empty( $prescriptions ) ) : ?>

                <div class="ayument-empty">

                    No prescriptions recorded yet.

                </div>

            <?php else : ?>

                <div style="overflow-x:auto;">

                    <table
                        class="ayument-prescription-table"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Date
                                </th>

                                <?php if ( ! $patient ) : ?>

                                    <th>
                                        Patient
                                    </th>

                                <?php endif; ?>

                                <th>
                                    Diagnosis
                                </th>

                                <th>
                                    Medicines
                                </th>

                                <th>
                                    Follow-up
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php foreach (
                            $prescriptions
                            as $prescription
                        ) : ?>

                            <?php

                            $medicine_list =
                                json_decode(
                                    $prescription->medicines,
                                    true
                                );

                            ?>

                            <tr>

                                <td>
                                    <?php
                                    echo esc_html(
                                        mysql2date(
                                            'd M Y',
                                            $prescription->prescription_date
                                        )
                                    );
                                    ?>
                                </td>


                                <?php if ( ! $patient ) : ?>

                                    <td>

                                        <strong>
                                            <?php
                                            echo esc_html(
                                                $prescription->patient_name
                                                    ?? 'Unknown'
                                            );
                                            ?>
                                        </strong>

                                        <br>

                                        <small>
                                            <?php
                                            echo esc_html(
                                                $prescription->patient_id
                                            );
                                            ?>
                                        </small>

                                    </td>

                                <?php endif; ?>


                                <td>
                                    <?php
                                    echo esc_html(
                                        $prescription->diagnosis
                                            ?: '—'
                                    );
                                    ?>
                                </td>


                                <td>

                                    <?php if (
                                        is_array(
                                            $medicine_list
                                        )
                                    ) : ?>

                                        <?php foreach (
                                            $medicine_list
                                            as $medicine
                                        ) : ?>

                                            <div
                                                style="
                                                    margin-bottom:8px;
                                                "
                                            >

                                                <strong>
                                                    <?php
                                                    echo esc_html(
                                                        $medicine['name']
                                                            ?? ''
                                                    );
                                                    ?>
                                                </strong>

                                                <?php
                                                if (
                                                    ! empty(
                                                        $medicine['dose']
                                                    )
                                                ) {
                                                    echo ' — ' .
                                                        esc_html(
                                                            $medicine['dose']
                                                        );
                                                }
                                                ?>

                                                <?php
                                                if (
                                                    ! empty(
                                                        $medicine['frequency']
                                                    )
                                                ) {
                                                    echo ' ' .
                                                        esc_html(
                                                            $medicine['frequency']
                                                        );
                                                }
                                                ?>

                                            </div>

                                        <?php endforeach; ?>

                                    <?php else : ?>

                                        —

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php
                                    echo $prescription->follow_up_date
                                        ? esc_html(
                                            mysql2date(
                                                'd M Y',
                                                $prescription->follow_up_date
                                            )
                                        )
                                        : '—';
                                    ?>

                                </td>


                                <td>

                                    <a
                                        class="ayument-action edit"
                                        href="<?php
                                        echo esc_url(
                                            admin_url(
                                                'admin.php?page=ayument-prescriptions' .
                                                '&patient_id=' .
                                                absint(
                                                    $prescription->patient_db_id
                                                ) .
                                                '&edit_prescription=' .
                                                absint(
                                                    $prescription->id
                                                )
                                            )
                                        );
                                        ?>"
                                    >
                                        Edit
                                    </a>


                                    <a
                                        class="ayument-action delete"
                                        href="<?php
                                        echo esc_url(
                                            wp_nonce_url(
                                                admin_url(
                                                    'admin.php?page=ayument-prescriptions' .
                                                    '&patient_id=' .
                                                    absint(
                                                        $prescription->patient_db_id
                                                    ) .
                                                    '&ayument_delete_prescription=' .
                                                    absint(
                                                        $prescription->id
                                                    )
                                                ),
                                                'ayument_delete_prescription_' .
                                                absint(
                                                    $prescription->id
                                                )
                                            )
                                        );
                                        ?>"
                                        onclick="
                                            return confirm(
                                                'Delete this prescription?'
                                            );
                                        "
                                    >
                                        Delete
                                    </a>

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

        function ayumentAddMedicine() {

            const container =
                document.getElementById(
                    'ayument-medicines'
                );

            const count =
                container.children.length + 1;


            const medicine =
                document.createElement(
                    'div'
                );


            medicine.className =
                'ayument-medicine';


            medicine.innerHTML = `

                <div
                    class="ayument-medicine-header"
                >

                    <strong>
                        Medicine ${count}
                    </strong>

                    <button
                        type="button"
                        class="ayument-remove-medicine"
                        onclick="
                            this.closest(
                                '.ayument-medicine'
                            ).remove();
                        "
                    >
                        Remove
                    </button>

                </div>


                <div
                    class="ayument-prescription-grid"
                >

                    <div
                        class="ayument-prescription-field full"
                    >
                        <label>
                            Medicine Name
                        </label>

                        <input
                            type="text"
                            name="medicine_name[]"
                            placeholder="e.g. Guduchi Churna"
                        >
                    </div>


                    <div
                        class="ayument-prescription-field"
                    >
                        <label>
                            Dose
                        </label>

                        <input
                            type="text"
                            name="medicine_dose[]"
                            placeholder="e.g. 3 g"
                        >
                    </div>


                    <div
                        class="ayument-prescription-field"
                    >
                        <label>
                            Frequency
                        </label>

                        <input
                            type="text"
                            name="medicine_frequency[]"
                            placeholder="e.g. BD / TDS"
                        >
                    </div>


                    <div
                        class="ayument-prescription-field"
                    >
                        <label>
                            Timing
                        </label>

                        <input
                            type="text"
                            name="medicine_timing[]"
                            placeholder="e.g. Before food"
                        >
                    </div>


                    <div
                        class="ayument-prescription-field"
                    >
                        <label>
                            Anupana
                        </label>

                        <input
                            type="text"
                            name="medicine_anupana[]"
                            placeholder="e.g. Warm water"
                        >
                    </div>


                    <div
                        class="ayument-prescription-field"
                    >
                        <label>
                            Duration
                        </label>

                        <input
                            type="text"
                            name="medicine_duration[]"
                            placeholder="e.g. 7 days"
                        >
                    </div>


                    <div
                        class="ayument-prescription-field"
                    >
                        <label>
                            Quantity
                        </label>

                        <input
                            type="text"
                            name="medicine_quantity[]"
                            placeholder="e.g. 100 g"
                        >
                    </div>


                    <div
                        class="ayument-prescription-field full"
                    >
                        <label>
                            Instructions
                        </label>

                        <input
                            type="text"
                            name="medicine_instruction[]"
                            placeholder="Special instructions"
                        >
                    </div>

                </div>
            `;


            container.appendChild(
                medicine
            );
        }

    </script>

    <?php
}