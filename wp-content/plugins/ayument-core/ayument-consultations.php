<?php
/**
 * AyuMent - Consultations Module
 *
 * Patient-linked clinical consultation records.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ---------------------------------------------------------
 * DATABASE
 * ---------------------------------------------------------
 */

function ayument_consultations_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'ayument_consultations';
}

function ayument_consultations_install_table() {
    global $wpdb;

    $table_name      = ayument_consultations_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        patient_db_id BIGINT(20) UNSIGNED NOT NULL,
        patient_id VARCHAR(30) NOT NULL,
        doctor_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        appointment_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        visit_date DATE NOT NULL,
        chief_complaint TEXT NULL,
        duration VARCHAR(100) NULL,
        onset VARCHAR(100) NULL,
        progression VARCHAR(100) NULL,
        associated_symptoms LONGTEXT NULL,
        history LONGTEXT NULL,
        previous_treatment LONGTEXT NULL,
        general_examination LONGTEXT NULL,
        systemic_examination LONGTEXT NULL,
        examination LONGTEXT NULL,
        vitals LONGTEXT NULL,
        prakriti VARCHAR(100) NULL,
        vikriti VARCHAR(255) NULL,
        dosha_involvement VARCHAR(255) NULL,
        dushya VARCHAR(255) NULL,
        agni VARCHAR(100) NULL,
        ama VARCHAR(100) NULL,
        koshtha VARCHAR(100) NULL,
        bala VARCHAR(100) NULL,
        appetite VARCHAR(50) NULL,
        digestion VARCHAR(50) NULL,
        bowel VARCHAR(50) NULL,
        mala VARCHAR(100) NULL,
        mutra VARCHAR(100) NULL,
        sleep VARCHAR(50) NULL,
        nidra VARCHAR(100) NULL,
        assessment LONGTEXT NULL,
        clinical_diagnosis VARCHAR(255) NULL,
        ayurvedic_diagnosis VARCHAR(255) NULL,
        differential_diagnosis LONGTEXT NULL,
        treatment_plan LONGTEXT NULL,
        medicines LONGTEXT NULL,
        medicine_dose LONGTEXT NULL,
        anupana VARCHAR(255) NULL,
        medicine_frequency VARCHAR(100) NULL,
        medicine_duration VARCHAR(100) NULL,
        pathya LONGTEXT NULL,
        procedures LONGTEXT NULL,
        advice LONGTEXT NULL,
        follow_up LONGTEXT NULL,
        follow_up_date DATE NULL,
        response_to_treatment LONGTEXT NULL,
        next_plan LONGTEXT NULL,
        notes LONGTEXT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY patient_db_id (patient_db_id),
        KEY patient_id (patient_id),
        KEY doctor_id (doctor_id),
        KEY appointment_id (appointment_id),
        KEY visit_date (visit_date)
    ) {$charset_collate};";

    dbDelta( $sql );
}

function ayument_consultations_upgrade_columns() {
    global $wpdb;
    $table_name = ayument_consultations_table_name();
    $columns = array(
        'doctor_id' => "ALTER TABLE {$table_name} ADD doctor_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER patient_id",
        'appointment_id' => "ALTER TABLE {$table_name} ADD appointment_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER doctor_id",
    );
    foreach ( $columns as $column => $sql ) {
        $exists = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table_name} LIKE %s", $column ) );
        if ( ! $exists ) { $wpdb->query( $sql ); }
    }
}

function ayument_consultations_ensure_table() {
    /*
     * Always run dbDelta().
     *
     * The consultations table has evolved as new clinical fields were
     * added. dbDelta() safely adds any missing columns to an existing
     * table. Previously this function only called dbDelta() when the
     * table did not exist, which could leave an older table schema in
     * place and make inserts fail silently.
     */
    ayument_consultations_install_table();
    ayument_consultations_upgrade_columns();
}

/**
 * ---------------------------------------------------------
 * ADMIN PAGE
 * ---------------------------------------------------------
 */


/**
 * Register the Clinical Consultations admin page.
 *
 * WordPress must know this page exists before a direct
 * admin.php?page=ayument-consultations URL can be opened.
 */
function ayument_register_consultations_admin_page() {
    add_submenu_page(
        'ayument-dashboard',
        'Consultations',
        'Consultations',
        'manage_options',
        'ayument-consultations',
        'ayument_consultation_page'
    );
}
add_action( 'admin_menu', 'ayument_register_consultations_admin_page', 20 );

function ayument_consultation_page() {
    ayument_render_consultation_page();
}

function ayument_render_consultation_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.' ) );
    }

    ayument_patients_ensure_table();
    ayument_consultations_ensure_table();

    global $wpdb;

    $patients_table      = ayument_patients_table_name();
    $consultations_table = ayument_consultations_table_name();

    $patient_db_id = isset( $_GET['patient_id'] )
        ? absint( $_GET['patient_id'] )
        : 0;

    if ( ! $patient_db_id && isset( $_POST['patient_db_id'] ) ) {
        $patient_db_id = absint( $_POST['patient_db_id'] );
    }

    if ( ! $patient_db_id ) {
        echo '<div class="wrap ayument-consultation-wrap">';
        echo '<div class="ayument-consultation-header">';
        echo '<h1>🩺 Clinical Consultations</h1>';
        echo '<p>Select a patient from the Patients module to start a consultation.</p>';
        echo '</div>';
        echo '<a class="ayument-consultation-button" href="' .
            esc_url( admin_url( 'admin.php?page=ayument-patients' ) ) .
            '">← Go to Patients</a>';
        echo '</div>';
        return;
    }

    $patient = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$patients_table} WHERE id = %d LIMIT 1",
            $patient_db_id
        )
    );

    if ( ! $patient ) {
        wp_die( esc_html__( 'Patient not found.' ) );
    }

    $message = '';
    $message_type = 'success';

    /*
     * -----------------------------------------------------
     * DELETE CONSULTATION
     * -----------------------------------------------------
     */

    if (
        isset( $_GET['delete_consultation'] ) &&
        isset( $_GET['_wpnonce'] )
    ) {
        $consultation_id = absint( $_GET['delete_consultation'] );

        if (
            wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ),
                'ayument_delete_consultation_' . $consultation_id
            )
        ) {
            $wpdb->delete(
                $consultations_table,
                array(
                    'id'            => $consultation_id,
                    'patient_db_id' => $patient_db_id,
                ),
                array( '%d', '%d' )
            );

            $message = 'Consultation deleted successfully.';
        }
    }

    /*
     * -----------------------------------------------------
     * SAVE CONSULTATION
     * -----------------------------------------------------
     */

    if (
        isset( $_POST['ayument_save_consultation'] ) &&
        isset( $_POST['ayument_consultation_nonce'] )
    ) {
        if (
            wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['ayument_consultation_nonce'] ) ),
                'ayument_save_consultation'
            )
        ) {
            $consultation_id = isset( $_POST['consultation_id'] )
                ? absint( $_POST['consultation_id'] )
                : 0;

            $data = array(
                'patient_db_id'       => $patient_db_id,
                'patient_id'          => $patient->patient_id,
                'doctor_id'           => 0,
                'appointment_id'      => 0,
                'visit_date'          => ! empty( $_POST['visit_date'] )
                    ? sanitize_text_field( wp_unslash( $_POST['visit_date'] ) )
                    : current_time( 'Y-m-d' ),
                'chief_complaint'      => sanitize_textarea_field( wp_unslash( $_POST['chief_complaint'] ?? '' ) ),
                'duration'             => sanitize_text_field( wp_unslash( $_POST['duration'] ?? '' ) ),
                'onset'                => sanitize_text_field( wp_unslash( $_POST['onset'] ?? '' ) ),
                'progression'          => sanitize_text_field( wp_unslash( $_POST['progression'] ?? '' ) ),
                'associated_symptoms'  => sanitize_textarea_field( wp_unslash( $_POST['associated_symptoms'] ?? '' ) ),
                'history'              => sanitize_textarea_field( wp_unslash( $_POST['history'] ?? '' ) ),
                'previous_treatment'   => sanitize_textarea_field( wp_unslash( $_POST['previous_treatment'] ?? '' ) ),
                'general_examination'  => sanitize_textarea_field( wp_unslash( $_POST['general_examination'] ?? '' ) ),
                'systemic_examination' => sanitize_textarea_field( wp_unslash( $_POST['systemic_examination'] ?? '' ) ),
                'examination'          => sanitize_textarea_field( wp_unslash( $_POST['examination'] ?? '' ) ),
                'vitals'               => sanitize_textarea_field( wp_unslash( $_POST['vitals'] ?? '' ) ),
                'prakriti'             => sanitize_text_field( wp_unslash( $_POST['prakriti'] ?? '' ) ),
                'vikriti'              => sanitize_text_field( wp_unslash( $_POST['vikriti'] ?? '' ) ),
                'dosha_involvement'    => sanitize_text_field( wp_unslash( $_POST['dosha_involvement'] ?? '' ) ),
                'dushya'               => sanitize_text_field( wp_unslash( $_POST['dushya'] ?? '' ) ),
                'agni'                 => sanitize_text_field( wp_unslash( $_POST['agni'] ?? '' ) ),
                'ama'                  => sanitize_text_field( wp_unslash( $_POST['ama'] ?? '' ) ),
                'koshtha'              => sanitize_text_field( wp_unslash( $_POST['koshtha'] ?? '' ) ),
                'bala'                 => sanitize_text_field( wp_unslash( $_POST['bala'] ?? '' ) ),
                'appetite'             => sanitize_text_field( wp_unslash( $_POST['appetite'] ?? '' ) ),
                'digestion'            => sanitize_text_field( wp_unslash( $_POST['digestion'] ?? '' ) ),
                'bowel'                => sanitize_text_field( wp_unslash( $_POST['bowel'] ?? '' ) ),
                'mala'                 => sanitize_text_field( wp_unslash( $_POST['mala'] ?? '' ) ),
                'mutra'                => sanitize_text_field( wp_unslash( $_POST['mutra'] ?? '' ) ),
                'sleep'                => sanitize_text_field( wp_unslash( $_POST['sleep'] ?? '' ) ),
                'nidra'                => sanitize_text_field( wp_unslash( $_POST['nidra'] ?? '' ) ),
                'assessment'           => sanitize_textarea_field( wp_unslash( $_POST['assessment'] ?? '' ) ),
                'clinical_diagnosis'   => sanitize_text_field( wp_unslash( $_POST['clinical_diagnosis'] ?? '' ) ),
                'ayurvedic_diagnosis'  => sanitize_text_field( wp_unslash( $_POST['ayurvedic_diagnosis'] ?? '' ) ),
                'differential_diagnosis' => sanitize_textarea_field( wp_unslash( $_POST['differential_diagnosis'] ?? '' ) ),
                'treatment_plan'       => sanitize_textarea_field( wp_unslash( $_POST['treatment_plan'] ?? '' ) ),
                'medicines'            => sanitize_textarea_field( wp_unslash( $_POST['medicines'] ?? '' ) ),
                'medicine_dose'        => sanitize_textarea_field( wp_unslash( $_POST['medicine_dose'] ?? '' ) ),
                'anupana'              => sanitize_text_field( wp_unslash( $_POST['anupana'] ?? '' ) ),
                'medicine_frequency'   => sanitize_text_field( wp_unslash( $_POST['medicine_frequency'] ?? '' ) ),
                'medicine_duration'    => sanitize_text_field( wp_unslash( $_POST['medicine_duration'] ?? '' ) ),
                'pathya'               => sanitize_textarea_field( wp_unslash( $_POST['pathya'] ?? '' ) ),
                'procedures'           => sanitize_textarea_field( wp_unslash( $_POST['procedures'] ?? '' ) ),
                'advice'               => sanitize_textarea_field( wp_unslash( $_POST['advice'] ?? '' ) ),
                'follow_up'            => sanitize_textarea_field( wp_unslash( $_POST['follow_up'] ?? '' ) ),
                'follow_up_date'       => ! empty( $_POST['follow_up_date'] ) ? sanitize_text_field( wp_unslash( $_POST['follow_up_date'] ) ) : null,
                'response_to_treatment'=> sanitize_textarea_field( wp_unslash( $_POST['response_to_treatment'] ?? '' ) ),
                'next_plan'            => sanitize_textarea_field( wp_unslash( $_POST['next_plan'] ?? '' ) ),
                'notes'                => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
                'updated_at'          => current_time( 'mysql' ),
            );

            if ( $consultation_id > 0 ) {
                $updated = $wpdb->update(
                    $consultations_table,
                    $data,
                    array(
                        'id'            => $consultation_id,
                        'patient_db_id' => $patient_db_id,
                    )
                );

                if ( false === $updated ) {
                    $message = 'Consultation could not be updated. Database error: ' . $wpdb->last_error;
                    $message_type = 'error';
                } else {
                    $message = 'Consultation updated successfully.';
                }
            } else {
                $data['created_at'] = current_time( 'mysql' );

                $inserted = $wpdb->insert(
                    $consultations_table,
                    $data
                );

                if ( false === $inserted ) {
                    $message = 'Consultation could not be saved. Database error: ' . $wpdb->last_error;
                    $message_type = 'error';
                } else {
                    $message = 'Consultation saved successfully.';
                }
            }
        } else {
            $message = 'Security verification failed. Please try again.';
            $message_type = 'error';
        }
    }

    /*
     * -----------------------------------------------------
     * EDIT CONSULTATION
     * -----------------------------------------------------
     */

    $edit_id = isset( $_GET['edit_consultation'] )
        ? absint( $_GET['edit_consultation'] )
        : 0;

    $edit_consultation = null;

    if ( $edit_id > 0 ) {
        $edit_consultation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$consultations_table}
                 WHERE id = %d AND patient_db_id = %d
                 LIMIT 1",
                $edit_id,
                $patient_db_id
            )
        );
    }

    /*
     * -----------------------------------------------------
     * CONSULTATION HISTORY
     * -----------------------------------------------------
     */

    $consultations = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$consultations_table}
             WHERE patient_db_id = %d
             ORDER BY visit_date DESC, id DESC",
            $patient_db_id
        )
    );

    $today = current_time( 'Y-m-d' );

    ?>

    <div class="wrap ayument-consultation-wrap">

        <style>
            .ayument-consultation-wrap {
                max-width: 1400px;
                margin: 25px auto;
            }

            .ayument-consultation-header {
                background: linear-gradient(135deg,#172554,#2563eb);
                border-radius: 20px;
                padding: 32px 38px;
                color: #fff;
                margin-bottom: 22px;
                box-shadow: 0 15px 35px rgba(30,64,175,.18);
            }

            .ayument-consultation-header h1 {
                color: #fff;
                font-size: 30px;
                margin: 0 0 8px;
            }

            .ayument-consultation-header p {
                margin: 0;
                color: rgba(255,255,255,.85);
                font-size: 15px;
            }

            .ayument-consultation-patient {
                margin-top: 18px;
                display: flex;
                gap: 12px;
                flex-wrap: wrap;
                align-items: center;
            }

            .ayument-consultation-patient strong {
                font-size: 18px;
            }

            .ayument-consultation-id {
                background: rgba(255,255,255,.14);
                padding: 6px 11px;
                border-radius: 20px;
                font-size: 13px;
            }

            .ayument-consultation-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 42px;
                padding: 0 17px;
                border-radius: 10px;
                border: 0;
                background: #2563eb;
                color: #fff !important;
                text-decoration: none !important;
                cursor: pointer;
                font-weight: 600;
                transition: .2s ease;
            }

            .ayument-consultation-button:hover {
                background: #1d4ed8;
                transform: translateY(-1px);
            }

            .ayument-consultation-button.secondary {
                background: #eef4ff;
                color: #1d4ed8 !important;
            }

            .ayument-consultation-button.danger {
                background: #fff1f2;
                color: #dc2626 !important;
            }

            .ayument-consultation-notice {
                padding: 14px 17px;
                border-radius: 10px;
                margin-bottom: 20px;
                background: #ecfdf5;
                border: 1px solid #a7f3d0;
                border-left: 5px solid #10b981;
                color: #065f46;
            }

            .ayument-consultation-notice.error {
                background: #fff1f2;
                border-color: #fecdd3;
                border-left-color: #dc2626;
                color: #991b1b;
            }

            .ayument-consultation-card {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 18px;
                padding: 28px;
                margin-bottom: 22px;
                box-shadow: 0 8px 25px rgba(15,23,42,.05);
            }

            .ayument-consultation-card h2 {
                margin: 0 0 20px;
                color: #172554;
                font-size: 20px;
            }

            .ayument-consultation-section {
                margin-bottom: 28px;
            }

            .ayument-consultation-section:last-child {
                margin-bottom: 0;
            }

            .ayument-consultation-section h3 {
                margin: 0 0 15px;
                padding-bottom: 10px;
                border-bottom: 1px solid #e2e8f0;
                color: #1e3a8a;
                font-size: 16px;
            }

            .ayument-consultation-grid {
                display: grid;
                grid-template-columns: repeat(2,minmax(0,1fr));
                gap: 18px;
            }

            .ayument-consultation-field.full {
                grid-column: 1 / -1;
            }

            .ayument-consultation-field label {
                display: block;
                margin-bottom: 7px;
                color: #334155;
                font-weight: 600;
            }

            .ayument-consultation-field input,
            .ayument-consultation-field select,
            .ayument-consultation-field textarea {
                width: 100%;
                box-sizing: border-box;
                border: 1px solid #dbe3ef;
                border-radius: 10px;
                padding: 11px 13px;
                font-size: 14px;
                background: #fff;
            }

            .ayument-consultation-field textarea {
                min-height: 105px;
                resize: vertical;
            }

            .ayument-consultation-field input:focus,
            .ayument-consultation-field select:focus,
            .ayument-consultation-field textarea:focus {
                border-color: #2563eb;
                box-shadow: 0 0 0 3px rgba(37,99,235,.10);
                outline: none;
            }

            .ayument-consultation-actions {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                border-top: 1px solid #e2e8f0;
                padding-top: 20px;
                margin-top: 25px;
            }

            .ayument-consultation-history {
                overflow-x: auto;
            }

            .ayument-consultation-table {
                width: 100%;
                border-collapse: collapse;
                min-width: 850px;
            }

            .ayument-consultation-table th {
                background: #f8fafc;
                color: #475569;
                text-align: left;
                font-size: 12px;
                text-transform: uppercase;
                letter-spacing: .04em;
                padding: 14px;
                border-bottom: 1px solid #e2e8f0;
            }

            .ayument-consultation-table td {
                padding: 15px 14px;
                border-bottom: 1px solid #eef2f7;
                vertical-align: top;
                color: #334155;
            }

            .ayument-consultation-table tr:last-child td {
                border-bottom: 0;
            }

            .ayument-consultation-actions-cell {
                display: flex;
                gap: 7px;
                flex-wrap: wrap;
            }

            .ayument-empty-consultations {
                text-align: center;
                padding: 45px 20px;
                color: #64748b;
            }

            @media(max-width:700px) {
                .ayument-consultation-grid {
                    grid-template-columns: 1fr;
                }

                .ayument-consultation-field.full {
                    grid-column: auto;
                }

                .ayument-consultation-card {
                    padding: 20px;
                }
            }
        </style>

        <div class="ayument-consultation-header">
            <h1>🩺 Clinical Consultation</h1>
            <p>Create and maintain patient-linked clinical consultation records.</p>

            <div class="ayument-consultation-patient">
                <strong><?php echo esc_html( $patient->name ); ?></strong>
                <span class="ayument-consultation-id">
                    Patient ID: <?php echo esc_html( $patient->patient_id ); ?>
                </span>
            </div>
        </div>

        <?php if ( ! empty( $message ) ) : ?>
            <div class="ayument-consultation-notice <?php echo 'error' === $message_type ? 'error' : ''; ?>">
                <?php echo esc_html( $message ); ?>
            </div>
        <?php endif; ?>

        <div class="ayument-consultation-card">

            <h2>
                <?php echo $edit_consultation ? '✎ Edit Consultation' : '＋ New Consultation'; ?>
            </h2>

            <form method="post">
                <?php wp_nonce_field( 'ayument_save_consultation', 'ayument_consultation_nonce' ); ?>

                <input type="hidden" name="patient_db_id" value="<?php echo esc_attr( $patient->id ); ?>">

                <?php if ( $edit_consultation ) : ?>
                    <input type="hidden" name="consultation_id" value="<?php echo esc_attr( $edit_consultation->id ); ?>">
                <?php endif; ?>

                <div class="ayument-consultation-section">
                    <h3>📅 Visit Information</h3>

                    <div class="ayument-consultation-grid">
                        <div class="ayument-consultation-field">
                            <label for="visit_date">Visit Date</label>
                            <input
                                type="date"
                                id="visit_date"
                                name="visit_date"
                                value="<?php echo esc_attr( $edit_consultation->visit_date ?? $today ); ?>"
                                required
                            >
                        </div>

                        <div class="ayument-consultation-field">
                            <label>Patient</label>
                            <input type="text" value="<?php echo esc_attr( $patient->name . ' — ' . $patient->patient_id ); ?>" readonly>
                        </div>
                    </div>
                </div>

                <div class="ayument-consultation-section">
                    <h3>🩺 Chief Complaint & History</h3>

                    <div class="ayument-consultation-grid">
                        <div class="ayument-consultation-field full">
                            <label for="chief_complaint">Chief Complaint</label>
                            <textarea id="chief_complaint" name="chief_complaint" placeholder="Main complaint / presenting concern"><?php echo esc_textarea( $edit_consultation->chief_complaint ?? '' ); ?></textarea>
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="duration">Duration</label>
                            <input type="text" id="duration" name="duration" value="<?php echo esc_attr( $edit_consultation->duration ?? '' ); ?>" placeholder="e.g. 3 days, 2 months">
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="onset">Onset</label>
                            <select id="onset" name="onset">
                                <option value="">Select</option>
                                <?php foreach ( array( 'Sudden', 'Gradual', 'Insidious', 'Recurrent', 'Not known' ) as $option ) : ?>
                                    <option value="<?php echo esc_attr( $option ); ?>" <?php selected( $edit_consultation->onset ?? '', $option ); ?>><?php echo esc_html( $option ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="progression">Progression</label>
                            <select id="progression" name="progression">
                                <option value="">Select</option>
                                <?php foreach ( array( 'Improving', 'Stable', 'Worsening', 'Fluctuating', 'Recurrent', 'Not known' ) as $option ) : ?>
                                    <option value="<?php echo esc_attr( $option ); ?>" <?php selected( $edit_consultation->progression ?? '', $option ); ?>><?php echo esc_html( $option ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="ayument-consultation-field full">
                            <label for="associated_symptoms">Associated Symptoms</label>
                            <textarea id="associated_symptoms" name="associated_symptoms" placeholder="Other relevant symptoms"><?php echo esc_textarea( $edit_consultation->associated_symptoms ?? '' ); ?></textarea>
                        </div>

                        <div class="ayument-consultation-field full">
                            <label for="history">History of Present Illness / Relevant History</label>
                            <textarea id="history" name="history" placeholder="Clinical history, relevant past history and other important details"><?php echo esc_textarea( $edit_consultation->history ?? '' ); ?></textarea>
                        </div>

                        <div class="ayument-consultation-field full">
                            <label for="previous_treatment">Previous Treatment</label>
                            <textarea id="previous_treatment" name="previous_treatment" placeholder="Previous medicines, procedures, response and relevant treatment history"><?php echo esc_textarea( $edit_consultation->previous_treatment ?? '' ); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="ayument-consultation-section">
                    <h3>🔎 Clinical Examination</h3>

                    <div class="ayument-consultation-grid">
                        <div class="ayument-consultation-field full">
                            <label for="vitals">Vitals / Measurements</label>
                            <textarea id="vitals" name="vitals" placeholder="BP, pulse, temperature, respiratory rate, SpO₂, weight, height, BMI, etc."><?php echo esc_textarea( $edit_consultation->vitals ?? '' ); ?></textarea>
                        </div>

                        <div class="ayument-consultation-field full">
                            <label for="general_examination">General Examination</label>
                            <textarea id="general_examination" name="general_examination" placeholder="General appearance, nourishment, hydration, pallor, icterus, cyanosis, clubbing, lymph nodes, edema, etc."><?php echo esc_textarea( $edit_consultation->general_examination ?? '' ); ?></textarea>
                        </div>

                        <div class="ayument-consultation-field full">
                            <label for="systemic_examination">Systemic Examination</label>
                            <textarea id="systemic_examination" name="systemic_examination" placeholder="CVS, respiratory, abdomen, CNS and other relevant systemic findings"><?php echo esc_textarea( $edit_consultation->systemic_examination ?? '' ); ?></textarea>
                        </div>

                        <div class="ayument-consultation-field full">
                            <label for="examination">Other Clinical Examination / Special Findings</label>
                            <textarea id="examination" name="examination" placeholder="Focused examination, local examination or other clinically relevant findings"><?php echo esc_textarea( $edit_consultation->examination ?? '' ); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="ayument-consultation-section">
                    <h3>🌿 Ayurvedic Assessment</h3>

                    <div class="ayument-consultation-grid">
                        <div class="ayument-consultation-field">
                            <label for="prakriti">Prakriti</label>
                            <input type="text" id="prakriti" name="prakriti" value="<?php echo esc_attr( $edit_consultation->prakriti ?? $patient->prakriti ?? '' ); ?>" placeholder="e.g. Vata-Pitta">
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="vikriti">Vikriti / Current Dosha State</label>
                            <input type="text" id="vikriti" name="vikriti" value="<?php echo esc_attr( $edit_consultation->vikriti ?? '' ); ?>" placeholder="Current assessment">
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="dosha_involvement">Dosha Involvement</label>
                            <input type="text" id="dosha_involvement" name="dosha_involvement" value="<?php echo esc_attr( $edit_consultation->dosha_involvement ?? '' ); ?>" placeholder="e.g. Vata-Pitta">
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="dushya">Dushya</label>
                            <input type="text" id="dushya" name="dushya" value="<?php echo esc_attr( $edit_consultation->dushya ?? '' ); ?>" placeholder="e.g. Rasa, Rakta, Meda">
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="agni">Agni</label>
                            <select id="agni" name="agni">
                                <option value="">Select</option>
                                <?php foreach ( array( 'Sama', 'Vishama', 'Tikshna', 'Manda', 'Not assessed' ) as $option ) : ?>
                                    <option value="<?php echo esc_attr( $option ); ?>" <?php selected( $edit_consultation->agni ?? '', $option ); ?>><?php echo esc_html( $option ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="ama">Ama</label>
                            <select id="ama" name="ama">
                                <option value="">Select</option>
                                <?php foreach ( array( 'Present', 'Absent', 'Suspected', 'Not assessed' ) as $option ) : ?>
                                    <option value="<?php echo esc_attr( $option ); ?>" <?php selected( $edit_consultation->ama ?? '', $option ); ?>><?php echo esc_html( $option ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="koshtha">Koshtha</label>
                            <select id="koshtha" name="koshtha">
                                <option value="">Select</option>
                                <?php foreach ( array( 'Mridu', 'Madhyama', 'Krura', 'Not assessed' ) as $option ) : ?>
                                    <option value="<?php echo esc_attr( $option ); ?>" <?php selected( $edit_consultation->koshtha ?? '', $option ); ?>><?php echo esc_html( $option ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="bala">Bala</label>
                            <select id="bala" name="bala">
                                <option value="">Select</option>
                                <?php foreach ( array( 'Pravara', 'Madhyama', 'Avara', 'Not assessed' ) as $option ) : ?>
                                    <option value="<?php echo esc_attr( $option ); ?>" <?php selected( $edit_consultation->bala ?? '', $option ); ?>><?php echo esc_html( $option ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="appetite">Appetite</label>
                            <select id="appetite" name="appetite">
                                <option value="">Select</option>
                                <?php foreach ( array( 'Normal', 'Low', 'Increased', 'Irregular' ) as $option ) : ?>
                                    <option value="<?php echo esc_attr( $option ); ?>" <?php selected( $edit_consultation->appetite ?? '', $option ); ?>><?php echo esc_html( $option ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="digestion">Digestion</label>
                            <select id="digestion" name="digestion">
                                <option value="">Select</option>
                                <?php foreach ( array( 'Normal', 'Weak', 'Irregular', 'Strong' ) as $option ) : ?>
                                    <option value="<?php echo esc_attr( $option ); ?>" <?php selected( $edit_consultation->digestion ?? '', $option ); ?>><?php echo esc_html( $option ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="bowel">Bowel Habits</label>
                            <select id="bowel" name="bowel">
                                <option value="">Select</option>
                                <?php foreach ( array( 'Normal', 'Constipation', 'Loose stools', 'Irregular' ) as $option ) : ?>
                                    <option value="<?php echo esc_attr( $option ); ?>" <?php selected( $edit_consultation->bowel ?? '', $option ); ?>><?php echo esc_html( $option ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="mala">Mala</label>
                            <input type="text" id="mala" name="mala" value="<?php echo esc_attr( $edit_consultation->mala ?? '' ); ?>" placeholder="Stool / mala assessment">
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="mutra">Mutra</label>
                            <input type="text" id="mutra" name="mutra" value="<?php echo esc_attr( $edit_consultation->mutra ?? '' ); ?>" placeholder="Urine / mutra assessment">
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="sleep">Sleep</label>
                            <select id="sleep" name="sleep">
                                <option value="">Select</option>
                                <?php foreach ( array( 'Good', 'Poor', 'Interrupted', 'Excessive' ) as $option ) : ?>
                                    <option value="<?php echo esc_attr( $option ); ?>" <?php selected( $edit_consultation->sleep ?? '', $option ); ?>><?php echo esc_html( $option ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="nidra">Nidra</label>
                            <input type="text" id="nidra" name="nidra" value="<?php echo esc_attr( $edit_consultation->nidra ?? '' ); ?>" placeholder="Duration / quality / pattern">
                        </div>
                    </div>
                </div>

                <div class="ayument-consultation-section">
                    <h3>📋 Assessment & Diagnosis</h3>

                    <div class="ayument-consultation-grid">
                        <div class="ayument-consultation-field full">
                            <label for="clinical_diagnosis">Clinical Diagnosis</label>
                            <input type="text" id="clinical_diagnosis" name="clinical_diagnosis" value="<?php echo esc_attr( $edit_consultation->clinical_diagnosis ?? '' ); ?>" placeholder="Clinical / biomedical diagnosis">
                        </div>

                        <div class="ayument-consultation-field full">
                            <label for="ayurvedic_diagnosis">Ayurvedic Diagnosis</label>
                            <input type="text" id="ayurvedic_diagnosis" name="ayurvedic_diagnosis" value="<?php echo esc_attr( $edit_consultation->ayurvedic_diagnosis ?? '' ); ?>" placeholder="Ayurvedic diagnosis / roga correlation">
                        </div>

                        <div class="ayument-consultation-field full">
                            <label for="differential_diagnosis">Differential Diagnosis</label>
                            <textarea id="differential_diagnosis" name="differential_diagnosis" placeholder="Important differential diagnoses considered"><?php echo esc_textarea( $edit_consultation->differential_diagnosis ?? '' ); ?></textarea>
                        </div>

                        <div class="ayument-consultation-field full">
                            <label for="assessment">Assessment / Clinical Impression</label>
                            <textarea id="assessment" name="assessment" placeholder="Clinical reasoning, samprapti assessment, impression and important findings"><?php echo esc_textarea( $edit_consultation->assessment ?? '' ); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="ayument-consultation-section">
                    <h3>💊 Treatment & Management Plan</h3>

                    <div class="ayument-consultation-grid">
                        <div class="ayument-consultation-field full">
                            <label for="medicines">Medicines / Formulations</label>
                            <textarea id="medicines" name="medicines" placeholder="One medicine/formulation per line where possible"><?php echo esc_textarea( $edit_consultation->medicines ?? '' ); ?></textarea>
                        </div>

                        <div class="ayument-consultation-field full">
                            <label for="medicine_dose">Dose</label>
                            <textarea id="medicine_dose" name="medicine_dose" placeholder="Dose corresponding to the medicines listed above"><?php echo esc_textarea( $edit_consultation->medicine_dose ?? '' ); ?></textarea>
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="anupana">Anupana</label>
                            <input type="text" id="anupana" name="anupana" value="<?php echo esc_attr( $edit_consultation->anupana ?? '' ); ?>" placeholder="e.g. Madhu, Jala, Ushna Jala">
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="medicine_frequency">Frequency / Timing</label>
                            <input type="text" id="medicine_frequency" name="medicine_frequency" value="<?php echo esc_attr( $edit_consultation->medicine_frequency ?? '' ); ?>" placeholder="e.g. OD, BD, TDS / before food">
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="medicine_duration">Medicine Duration</label>
                            <input type="text" id="medicine_duration" name="medicine_duration" value="<?php echo esc_attr( $edit_consultation->medicine_duration ?? '' ); ?>" placeholder="e.g. 7 days">
                        </div>

                        <div class="ayument-consultation-field full">
                            <label for="treatment_plan">Overall Treatment / Management Plan</label>
                            <textarea id="treatment_plan" name="treatment_plan" placeholder="Overall therapeutic plan and rationale"><?php echo esc_textarea( $edit_consultation->treatment_plan ?? '' ); ?></textarea>
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="pathya">Pathya / Apathya</label>
                            <textarea id="pathya" name="pathya" placeholder="Diet, lifestyle and restrictions"><?php echo esc_textarea( $edit_consultation->pathya ?? '' ); ?></textarea>
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="procedures">Procedures / Therapies</label>
                            <textarea id="procedures" name="procedures" placeholder="Panchakarma, kriya, local procedures or other therapies"><?php echo esc_textarea( $edit_consultation->procedures ?? '' ); ?></textarea>
                        </div>

                        <div class="ayument-consultation-field full">
                            <label for="advice">Patient Advice</label>
                            <textarea id="advice" name="advice" placeholder="General instructions, precautions and patient counselling"><?php echo esc_textarea( $edit_consultation->advice ?? '' ); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="ayument-consultation-section">
                    <h3>📅 Follow-up & Continuity</h3>

                    <div class="ayument-consultation-grid">
                        <div class="ayument-consultation-field">
                            <label for="follow_up_date">Follow-up Date</label>
                            <input type="date" id="follow_up_date" name="follow_up_date" value="<?php echo esc_attr( $edit_consultation->follow_up_date ?? '' ); ?>">
                        </div>

                        <div class="ayument-consultation-field">
                            <label for="follow_up">Follow-up Instructions</label>
                            <input type="text" id="follow_up" name="follow_up" value="<?php echo esc_attr( $edit_consultation->follow_up ?? '' ); ?>" placeholder="e.g. Review after 7 days">
                        </div>

                        <div class="ayument-consultation-field full">
                            <label for="response_to_treatment">Response to Treatment</label>
                            <textarea id="response_to_treatment" name="response_to_treatment" placeholder="For follow-up visits: improvement, worsening, adverse effects, adherence, etc."><?php echo esc_textarea( $edit_consultation->response_to_treatment ?? '' ); ?></textarea>
                        </div>

                        <div class="ayument-consultation-field full">
                            <label for="next_plan">Next Plan</label>
                            <textarea id="next_plan" name="next_plan" placeholder="What should happen at or before the next review?"><?php echo esc_textarea( $edit_consultation->next_plan ?? '' ); ?></textarea>
                        </div>

                        <div class="ayument-consultation-field full">
                            <label for="notes">Additional Notes</label>
                            <textarea id="notes" name="notes" placeholder="Any additional clinical notes"><?php echo esc_textarea( $edit_consultation->notes ?? '' ); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="ayument-consultation-actions">
                    <button type="submit" name="ayument_save_consultation" value="1" class="ayument-consultation-button">
                        <?php echo $edit_consultation ? '✓ Update Consultation' : '✓ Save Consultation'; ?>
                    </button>

                    <?php if ( $edit_consultation ) : ?>
                        <a
                            class="ayument-consultation-button secondary"
                            href="<?php echo esc_url( admin_url( 'admin.php?page=ayument-consultations&patient_id=' . absint( $patient->id ) ) ); ?>"
                        >
                            Cancel Edit
                        </a>
                    <?php endif; ?>

                    <a
                        class="ayument-consultation-button secondary"
                        href="<?php echo esc_url( admin_url( 'admin.php?page=ayument-patients&patient=' . absint( $patient->id ) ) ); ?>"
                    >
                        ← Patient Profile
                    </a>
                </div>
            </form>
        </div>

        <div class="ayument-consultation-card">

            <h2>📚 Consultation History</h2>

            <?php if ( empty( $consultations ) ) : ?>

                <div class="ayument-empty-consultations">
                    <div style="font-size:42px;">🩺</div>
                    <h3>No consultations recorded yet</h3>
                    <p>Save the first consultation above to build this patient's clinical history.</p>
                </div>

            <?php else : ?>

                <div class="ayument-consultation-history">
                    <table class="ayument-consultation-table">
                        <thead>
                            <tr>
                                <th>Visit Date</th>
                                <th>Chief Complaint</th>
                                <th>Assessment</th>
                                <th>Dosha</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ( $consultations as $consultation ) : ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?php echo esc_html( mysql2date( 'd M Y', $consultation->visit_date ) ); ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php echo esc_html( $consultation->chief_complaint ?: '—' ); ?>
                                    </td>

                                    <td>
                                        <?php echo esc_html( $consultation->assessment ?: '—' ); ?>
                                    </td>

                                    <td>
                                        <?php echo esc_html( $consultation->dosha_involvement ?: '—' ); ?>
                                    </td>

                                    <td>
                                        <div class="ayument-consultation-actions-cell">

                                            <a
                                                class="ayument-consultation-button secondary"
                                                href="<?php echo esc_url(
                                                    admin_url(
                                                        'admin.php?page=ayument-consultations&patient_id=' .
                                                        absint( $patient->id ) .
                                                        '&edit_consultation=' .
                                                        absint( $consultation->id )
                                                    )
                                                ); ?>"
                                            >
                                                Edit
                                            </a>

                                            <a
                                                class="ayument-consultation-button danger"
                                                href="<?php echo esc_url(
                                                    wp_nonce_url(
                                                        admin_url(
                                                            'admin.php?page=ayument-consultations&patient_id=' .
                                                            absint( $patient->id ) .
                                                            '&delete_consultation=' .
                                                            absint( $consultation->id )
                                                        ),
                                                        'ayument_delete_consultation_' . absint( $consultation->id )
                                                    )
                                                ); ?>"
                                                onclick="return confirm('Delete this consultation record? This action cannot be undone.');"
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

    <?php
}

/* =========================================================
 * DOCTOR PORTAL CONSULTATIONS
 * ========================================================= */
function ayument_doctor_consultation_user_id() {
    if ( ! is_user_logged_in() ) return 0;
    $id=get_current_user_id(); $u=get_userdata($id);
    if(!$u) return 0;
    return (user_can($id,'manage_options') || in_array('ayument_doctor',(array)$u->roles,true)) ? $id : 0;
}
function ayument_doctor_consultation_patient($patient_id,$doctor_id){
    global $wpdb; $pt=ayument_patients_table_name(); $at=$wpdb->prefix.'ayument_appointments';
    return $wpdb->get_row($wpdb->prepare("SELECT p.*,MAX(a.appointment_date) AS last_appointment_date,COUNT(DISTINCT a.id) AS appointment_count FROM {$pt} p INNER JOIN {$at} a ON a.patient_id=p.id WHERE p.id=%d AND a.doctor_id=%d AND a.status<>'Cancelled' GROUP BY p.id LIMIT 1",$patient_id,$doctor_id));
}
function ayument_doctor_latest_appointment($patient_id,$doctor_id){
    global $wpdb; $at=$wpdb->prefix.'ayument_appointments';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$at} WHERE patient_id=%d AND doctor_id=%d AND status<>'Cancelled' ORDER BY appointment_date DESC,appointment_time DESC,id DESC LIMIT 1",$patient_id,$doctor_id));
}
function ayument_doctor_consultation_render(){
    if(!is_user_logged_in()) return '<div style="padding:25px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;">Please log in to access consultations.</div>';
    $doctor_id=ayument_doctor_consultation_user_id();
    if(!$doctor_id) return '<div style="padding:25px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;"><strong>Doctor access is required.</strong><p>Your account must be a verified AyuMent doctor to create consultations.</p></div>';
    ayument_patients_ensure_table(); ayument_consultations_ensure_table(); global $wpdb;
    $ct=ayument_consultations_table_name(); $patient_id=isset($_GET['patient_id'])?absint($_GET['patient_id']):0; $edit_id=isset($_GET['edit_consultation'])?absint($_GET['edit_consultation']):0; $message=''; $error=false;
    if(isset($_GET['delete_consultation'],$_GET['_wpnonce'])){
        $did=absint($_GET['delete_consultation']); $nonce=sanitize_text_field(wp_unslash($_GET['_wpnonce']));
        if(wp_verify_nonce($nonce,'ayument_doctor_delete_consultation_'.$did)){$ok=$wpdb->delete($ct,array('id'=>$did,'doctor_id'=>$doctor_id),array('%d','%d'));$message=$ok?'Consultation deleted successfully.':'Consultation could not be deleted.';$error=!$ok;}else{$message='Security verification failed.';$error=true;}
    }
    $patient=$patient_id?ayument_doctor_consultation_patient($patient_id,$doctor_id):null;
    if($patient_id && !$patient) return '<div style="max-width:1100px;margin:0 auto;padding:30px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;"><strong>Patient not found.</strong><p>This patient is not assigned to your doctor account.</p><a href="'.esc_url(add_query_arg(array('doctor_view'=>'patients'),get_permalink())).'">← Back to My Patients</a></div>';
    if($patient_id && isset($_POST['ayument_doctor_save_consultation'])){
        $nonce=isset($_POST['ayument_consultation_nonce'])?sanitize_text_field(wp_unslash($_POST['ayument_consultation_nonce'])):'';
        if(!wp_verify_nonce($nonce,'ayument_save_consultation')){$message='Security verification failed. Please try again.';$error=true;}else{
            $appointment=ayument_doctor_latest_appointment($patient_id,$doctor_id);
            if(!$appointment){$message='No active appointment was found for this patient and doctor.';$error=true;}else{
                $cid=isset($_POST['consultation_id'])?absint($_POST['consultation_id']):0;
                $data=array(
                    'patient_db_id'=>$patient_id,'patient_id'=>$patient->patient_id,'doctor_id'=>$doctor_id,'appointment_id'=>absint($appointment->id),
                    'visit_date'=>!empty($_POST['visit_date'])?sanitize_text_field(wp_unslash($_POST['visit_date'])):current_time('Y-m-d'),
                    'chief_complaint'=>sanitize_textarea_field(wp_unslash($_POST['chief_complaint']??'')),'duration'=>sanitize_text_field(wp_unslash($_POST['duration']??'')),'onset'=>sanitize_text_field(wp_unslash($_POST['onset']??'')),'progression'=>sanitize_text_field(wp_unslash($_POST['progression']??'')),
                    'associated_symptoms'=>sanitize_textarea_field(wp_unslash($_POST['associated_symptoms']??'')),'history'=>sanitize_textarea_field(wp_unslash($_POST['history']??'')),'previous_treatment'=>sanitize_textarea_field(wp_unslash($_POST['previous_treatment']??'')),'general_examination'=>sanitize_textarea_field(wp_unslash($_POST['general_examination']??'')),'systemic_examination'=>sanitize_textarea_field(wp_unslash($_POST['systemic_examination']??'')),'examination'=>sanitize_textarea_field(wp_unslash($_POST['examination']??'')),'vitals'=>sanitize_textarea_field(wp_unslash($_POST['vitals']??'')),
                    'prakriti'=>sanitize_text_field(wp_unslash($_POST['prakriti']??'')),'vikriti'=>sanitize_text_field(wp_unslash($_POST['vikriti']??'')),'dosha_involvement'=>sanitize_text_field(wp_unslash($_POST['dosha_involvement']??'')),'dushya'=>sanitize_text_field(wp_unslash($_POST['dushya']??'')),'agni'=>sanitize_text_field(wp_unslash($_POST['agni']??'')),'ama'=>sanitize_text_field(wp_unslash($_POST['ama']??'')),'koshtha'=>sanitize_text_field(wp_unslash($_POST['koshtha']??'')),'bala'=>sanitize_text_field(wp_unslash($_POST['bala']??'')),'appetite'=>sanitize_text_field(wp_unslash($_POST['appetite']??'')),'digestion'=>sanitize_text_field(wp_unslash($_POST['digestion']??'')),'bowel'=>sanitize_text_field(wp_unslash($_POST['bowel']??'')),'mala'=>sanitize_text_field(wp_unslash($_POST['mala']??'')),'mutra'=>sanitize_text_field(wp_unslash($_POST['mutra']??'')),'sleep'=>sanitize_text_field(wp_unslash($_POST['sleep']??'')),'nidra'=>sanitize_text_field(wp_unslash($_POST['nidra']??'')),
                    'assessment'=>sanitize_textarea_field(wp_unslash($_POST['assessment']??'')),'clinical_diagnosis'=>sanitize_text_field(wp_unslash($_POST['clinical_diagnosis']??'')),'ayurvedic_diagnosis'=>sanitize_text_field(wp_unslash($_POST['ayurvedic_diagnosis']??'')),'differential_diagnosis'=>sanitize_textarea_field(wp_unslash($_POST['differential_diagnosis']??'')),'treatment_plan'=>sanitize_textarea_field(wp_unslash($_POST['treatment_plan']??'')),'medicines'=>sanitize_textarea_field(wp_unslash($_POST['medicines']??'')),'medicine_dose'=>sanitize_textarea_field(wp_unslash($_POST['medicine_dose']??'')),'anupana'=>sanitize_text_field(wp_unslash($_POST['anupana']??'')),'medicine_frequency'=>sanitize_text_field(wp_unslash($_POST['medicine_frequency']??'')),'medicine_duration'=>sanitize_text_field(wp_unslash($_POST['medicine_duration']??'')),'pathya'=>sanitize_textarea_field(wp_unslash($_POST['pathya']??'')),'procedures'=>sanitize_textarea_field(wp_unslash($_POST['procedures']??'')),'advice'=>sanitize_textarea_field(wp_unslash($_POST['advice']??'')),'follow_up'=>sanitize_textarea_field(wp_unslash($_POST['follow_up']??'')),'follow_up_date'=>!empty($_POST['follow_up_date'])?sanitize_text_field(wp_unslash($_POST['follow_up_date'])):null,'response_to_treatment'=>sanitize_textarea_field(wp_unslash($_POST['response_to_treatment']??'')),'next_plan'=>sanitize_textarea_field(wp_unslash($_POST['next_plan']??'')),'notes'=>sanitize_textarea_field(wp_unslash($_POST['notes']??'')),'updated_at'=>current_time('mysql')
                );
                if($cid){$existing=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$ct} WHERE id=%d AND patient_db_id=%d AND doctor_id=%d LIMIT 1",$cid,$patient_id,$doctor_id));if($existing){$ok=$wpdb->update($ct,$data,array('id'=>$cid));$message=false===$ok?'Consultation could not be updated. Database error: '.$wpdb->last_error:'Consultation updated successfully.';$error=false===$ok;}else{$message='Consultation not found or access denied.';$error=true;}}else{$data['created_at']=current_time('mysql');$ok=$wpdb->insert($ct,$data);$message=false===$ok?'Consultation could not be saved. Database error: '.$wpdb->last_error:'Consultation saved successfully.';$error=false===$ok;if(!$error)$edit_id=0;}
            }
        }
    }
    $style_html=base64_decode('ICAgICAgICA8c3R5bGU+CiAgICAgICAgICAgIC5heXVtZW50LWNvbnN1bHRhdGlvbi13cmFwIHsKICAgICAgICAgICAgICAgIG1heC13aWR0aDogMTQwMHB4OwogICAgICAgICAgICAgICAgbWFyZ2luOiAyNXB4IGF1dG87CiAgICAgICAgICAgIH0KCiAgICAgICAgICAgIC5heXVtZW50LWNvbnN1bHRhdGlvbi1oZWFkZXIgewogICAgICAgICAgICAgICAgYmFja2dyb3VuZDogbGluZWFyLWdyYWRpZW50KDEzNWRlZywjMTcyNTU0LCMyNTYzZWIpOwogICAgICAgICAgICAgICAgYm9yZGVyLXJhZGl1czogMjBweDsKICAgICAgICAgICAgICAgIHBhZGRpbmc6IDMycHggMzhweDsKICAgICAgICAgICAgICAgIGNvbG9yOiAjZmZmOwogICAgICAgICAgICAgICAgbWFyZ2luLWJvdHRvbTogMjJweDsKICAgICAgICAgICAgICAgIGJveC1zaGFkb3c6IDAgMTVweCAzNXB4IHJnYmEoMzAsNjQsMTc1LC4xOCk7CiAgICAgICAgICAgIH0KCiAgICAgICAgICAgIC5heXVtZW50LWNvbnN1bHRhdGlvbi1oZWFkZXIgaDEgewogICAgICAgICAgICAgICAgY29sb3I6ICNmZmY7CiAgICAgICAgICAgICAgICBmb250LXNpemU6IDMwcHg7CiAgICAgICAgICAgICAgICBtYXJnaW46IDAgMCA4cHg7CiAgICAgICAgICAgIH0KCiAgICAgICAgICAgIC5heXVtZW50LWNvbnN1bHRhdGlvbi1oZWFkZXIgcCB7CiAgICAgICAgICAgICAgICBtYXJnaW46IDA7CiAgICAgICAgICAgICAgICBjb2xvcjogcmdiYSgyNTUsMjU1LDI1NSwuODUpOwogICAgICAgICAgICAgICAgZm9udC1zaXplOiAxNXB4OwogICAgICAgICAgICB9CgogICAgICAgICAgICAuYXl1bWVudC1jb25zdWx0YXRpb24tcGF0aWVudCB7CiAgICAgICAgICAgICAgICBtYXJnaW4tdG9wOiAxOHB4OwogICAgICAgICAgICAgICAgZGlzcGxheTogZmxleDsKICAgICAgICAgICAgICAgIGdhcDogMTJweDsKICAgICAgICAgICAgICAgIGZsZXgtd3JhcDogd3JhcDsKICAgICAgICAgICAgICAgIGFsaWduLWl0ZW1zOiBjZW50ZXI7CiAgICAgICAgICAgIH0KCiAgICAgICAgICAgIC5heXVtZW50LWNvbnN1bHRhdGlvbi1wYXRpZW50IHN0cm9uZyB7CiAgICAgICAgICAgICAgICBmb250LXNpemU6IDE4cHg7CiAgICAgICAgICAgIH0KCiAgICAgICAgICAgIC5heXVtZW50LWNvbnN1bHRhdGlvbi1pZCB7CiAgICAgICAgICAgICAgICBiYWNrZ3JvdW5kOiByZ2JhKDI1NSwyNTUsMjU1LC4xNCk7CiAgICAgICAgICAgICAgICBwYWRkaW5nOiA2cHggMTFweDsKICAgICAgICAgICAgICAgIGJvcmRlci1yYWRpdXM6IDIwcHg7CiAgICAgICAgICAgICAgICBmb250LXNpemU6IDEzcHg7CiAgICAgICAgICAgIH0KCiAgICAgICAgICAgIC5heXVtZW50LWNvbnN1bHRhdGlvbi1idXR0b24gewogICAgICAgICAgICAgICAgZGlzcGxheTogaW5saW5lLWZsZXg7CiAgICAgICAgICAgICAgICBhbGlnbi1pdGVtczogY2VudGVyOwogICAgICAgICAgICAgICAganVzdGlmeS1jb250ZW50OiBjZW50ZXI7CiAgICAgICAgICAgICAgICBtaW4taGVpZ2h0OiA0MnB4OwogICAgICAgICAgICAgICAgcGFkZGluZzogMCAxN3B4OwogICAgICAgICAgICAgICAgYm9yZGVyLXJhZGl1czogMTBweDsKICAgICAgICAgICAgICAgIGJvcmRlcjogMDsKICAgICAgICAgICAgICAgIGJhY2tncm91bmQ6ICMyNTYzZWI7CiAgICAgICAgICAgICAgICBjb2xvcjogI2ZmZiAhaW1wb3J0YW50OwogICAgICAgICAgICAgICAgdGV4dC1kZWNvcmF0aW9uOiBub25lICFpbXBvcnRhbnQ7CiAgICAgICAgICAgICAgICBjdXJzb3I6IHBvaW50ZXI7CiAgICAgICAgICAgICAgICBmb250LXdlaWdodDogNjAwOwogICAgICAgICAgICAgICAgdHJhbnNpdGlvbjogLjJzIGVhc2U7CiAgICAgICAgICAgIH0KCiAgICAgICAgICAgIC5heXVtZW50LWNvbnN1bHRhdGlvbi1idXR0b246aG92ZXIgewogICAgICAgICAgICAgICAgYmFja2dyb3VuZDogIzFkNGVkODsKICAgICAgICAgICAgICAgIHRyYW5zZm9ybTogdHJhbnNsYXRlWSgtMXB4KTsKICAgICAgICAgICAgfQoKICAgICAgICAgICAgLmF5dW1lbnQtY29uc3VsdGF0aW9uLWJ1dHRvbi5zZWNvbmRhcnkgewogICAgICAgICAgICAgICAgYmFja2dyb3VuZDogI2VlZjRmZjsKICAgICAgICAgICAgICAgIGNvbG9yOiAjMWQ0ZWQ4ICFpbXBvcnRhbnQ7CiAgICAgICAgICAgIH0KCiAgICAgICAgICAgIC5heXVtZW50LWNvbnN1bHRhdGlvbi1idXR0b24uZGFuZ2VyIHsKICAgICAgICAgICAgICAgIGJhY2tncm91bmQ6ICNmZmYxZjI7CiAgICAgICAgICAgICAgICBjb2xvcjogI2RjMjYyNiAhaW1wb3J0YW50OwogICAgICAgICAgICB9CgogICAgICAgICAgICAuYXl1bWVudC1jb25zdWx0YXRpb24tbm90aWNlIHsKICAgICAgICAgICAgICAgIHBhZGRpbmc6IDE0cHggMTdweDsKICAgICAgICAgICAgICAgIGJvcmRlci1yYWRpdXM6IDEwcHg7CiAgICAgICAgICAgICAgICBtYXJnaW4tYm90dG9tOiAyMHB4OwogICAgICAgICAgICAgICAgYmFja2dyb3VuZDogI2VjZmRmNTsKICAgICAgICAgICAgICAgIGJvcmRlcjogMXB4IHNvbGlkICNhN2YzZDA7CiAgICAgICAgICAgICAgICBib3JkZXItbGVmdDogNXB4IHNvbGlkICMxMGI5ODE7CiAgICAgICAgICAgICAgICBjb2xvcjogIzA2NWY0NjsKICAgICAgICAgICAgfQoKICAgICAgICAgICAgLmF5dW1lbnQtY29uc3VsdGF0aW9uLW5vdGljZS5lcnJvciB7CiAgICAgICAgICAgICAgICBiYWNrZ3JvdW5kOiAjZmZmMWYyOwogICAgICAgICAgICAgICAgYm9yZGVyLWNvbG9yOiAjZmVjZGQzOwogICAgICAgICAgICAgICAgYm9yZGVyLWxlZnQtY29sb3I6ICNkYzI2MjY7CiAgICAgICAgICAgICAgICBjb2xvcjogIzk5MWIxYjsKICAgICAgICAgICAgfQoKICAgICAgICAgICAgLmF5dW1lbnQtY29uc3VsdGF0aW9uLWNhcmQgewogICAgICAgICAgICAgICAgYmFja2dyb3VuZDogI2ZmZjsKICAgICAgICAgICAgICAgIGJvcmRlcjogMXB4IHNvbGlkICNlMmU4ZjA7CiAgICAgICAgICAgICAgICBib3JkZXItcmFkaXVzOiAxOHB4OwogICAgICAgICAgICAgICAgcGFkZGluZzogMjhweDsKICAgICAgICAgICAgICAgIG1hcmdpbi1ib3R0b206IDIycHg7CiAgICAgICAgICAgICAgICBib3gtc2hhZG93OiAwIDhweCAyNXB4IHJnYmEoMTUsMjMsNDIsLjA1KTsKICAgICAgICAgICAgfQoKICAgICAgICAgICAgLmF5dW1lbnQtY29uc3VsdGF0aW9uLWNhcmQgaDIgewogICAgICAgICAgICAgICAgbWFyZ2luOiAwIDAgMjBweDsKICAgICAgICAgICAgICAgIGNvbG9yOiAjMTcyNTU0OwogICAgICAgICAgICAgICAgZm9udC1zaXplOiAyMHB4OwogICAgICAgICAgICB9CgogICAgICAgICAgICAuYXl1bWVudC1jb25zdWx0YXRpb24tc2VjdGlvbiB7CiAgICAgICAgICAgICAgICBtYXJnaW4tYm90dG9tOiAyOHB4OwogICAgICAgICAgICB9CgogICAgICAgICAgICAuYXl1bWVudC1jb25zdWx0YXRpb24tc2VjdGlvbjpsYXN0LWNoaWxkIHsKICAgICAgICAgICAgICAgIG1hcmdpbi1ib3R0b206IDA7CiAgICAgICAgICAgIH0KCiAgICAgICAgICAgIC5heXVtZW50LWNvbnN1bHRhdGlvbi1zZWN0aW9uIGgzIHsKICAgICAgICAgICAgICAgIG1hcmdpbjogMCAwIDE1cHg7CiAgICAgICAgICAgICAgICBwYWRkaW5nLWJvdHRvbTogMTBweDsKICAgICAgICAgICAgICAgIGJvcmRlci1ib3R0b206IDFweCBzb2xpZCAjZTJlOGYwOwogICAgICAgICAgICAgICAgY29sb3I6ICMxZTNhOGE7CiAgICAgICAgICAgICAgICBmb250LXNpemU6IDE2cHg7CiAgICAgICAgICAgIH0KCiAgICAgICAgICAgIC5heXVtZW50LWNvbnN1bHRhdGlvbi1ncmlkIHsKICAgICAgICAgICAgICAgIGRpc3BsYXk6IGdyaWQ7CiAgICAgICAgICAgICAgICBncmlkLXRlbXBsYXRlLWNvbHVtbnM6IHJlcGVhdCgyLG1pbm1heCgwLDFmcikpOwogICAgICAgICAgICAgICAgZ2FwOiAxOHB4OwogICAgICAgICAgICB9CgogICAgICAgICAgICAuYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQuZnVsbCB7CiAgICAgICAgICAgICAgICBncmlkLWNvbHVtbjogMSAvIC0xOwogICAgICAgICAgICB9CgogICAgICAgICAgICAuYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQgbGFiZWwgewogICAgICAgICAgICAgICAgZGlzcGxheTogYmxvY2s7CiAgICAgICAgICAgICAgICBtYXJnaW4tYm90dG9tOiA3cHg7CiAgICAgICAgICAgICAgICBjb2xvcjogIzMzNDE1NTsKICAgICAgICAgICAgICAgIGZvbnQtd2VpZ2h0OiA2MDA7CiAgICAgICAgICAgIH0KCiAgICAgICAgICAgIC5heXVtZW50LWNvbnN1bHRhdGlvbi1maWVsZCBpbnB1dCwKICAgICAgICAgICAgLmF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIHNlbGVjdCwKICAgICAgICAgICAgLmF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIHRleHRhcmVhIHsKICAgICAgICAgICAgICAgIHdpZHRoOiAxMDAlOwogICAgICAgICAgICAgICAgYm94LXNpemluZzogYm9yZGVyLWJveDsKICAgICAgICAgICAgICAgIGJvcmRlcjogMXB4IHNvbGlkICNkYmUzZWY7CiAgICAgICAgICAgICAgICBib3JkZXItcmFkaXVzOiAxMHB4OwogICAgICAgICAgICAgICAgcGFkZGluZzogMTFweCAxM3B4OwogICAgICAgICAgICAgICAgZm9udC1zaXplOiAxNHB4OwogICAgICAgICAgICAgICAgYmFja2dyb3VuZDogI2ZmZjsKICAgICAgICAgICAgfQoKICAgICAgICAgICAgLmF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIHRleHRhcmVhIHsKICAgICAgICAgICAgICAgIG1pbi1oZWlnaHQ6IDEwNXB4OwogICAgICAgICAgICAgICAgcmVzaXplOiB2ZXJ0aWNhbDsKICAgICAgICAgICAgfQoKICAgICAgICAgICAgLmF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIGlucHV0OmZvY3VzLAogICAgICAgICAgICAuYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQgc2VsZWN0OmZvY3VzLAogICAgICAgICAgICAuYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQgdGV4dGFyZWE6Zm9jdXMgewogICAgICAgICAgICAgICAgYm9yZGVyLWNvbG9yOiAjMjU2M2ViOwogICAgICAgICAgICAgICAgYm94LXNoYWRvdzogMCAwIDAgM3B4IHJnYmEoMzcsOTksMjM1LC4xMCk7CiAgICAgICAgICAgICAgICBvdXRsaW5lOiBub25lOwogICAgICAgICAgICB9CgogICAgICAgICAgICAuYXl1bWVudC1jb25zdWx0YXRpb24tYWN0aW9ucyB7CiAgICAgICAgICAgICAgICBkaXNwbGF5OiBmbGV4OwogICAgICAgICAgICAgICAgZ2FwOiAxMHB4OwogICAgICAgICAgICAgICAgZmxleC13cmFwOiB3cmFwOwogICAgICAgICAgICAgICAgYm9yZGVyLXRvcDogMXB4IHNvbGlkICNlMmU4ZjA7CiAgICAgICAgICAgICAgICBwYWRkaW5nLXRvcDogMjBweDsKICAgICAgICAgICAgICAgIG1hcmdpbi10b3A6IDI1cHg7CiAgICAgICAgICAgIH0KCiAgICAgICAgICAgIC5heXVtZW50LWNvbnN1bHRhdGlvbi1oaXN0b3J5IHsKICAgICAgICAgICAgICAgIG92ZXJmbG93LXg6IGF1dG87CiAgICAgICAgICAgIH0KCiAgICAgICAgICAgIC5heXVtZW50LWNvbnN1bHRhdGlvbi10YWJsZSB7CiAgICAgICAgICAgICAgICB3aWR0aDogMTAwJTsKICAgICAgICAgICAgICAgIGJvcmRlci1jb2xsYXBzZTogY29sbGFwc2U7CiAgICAgICAgICAgICAgICBtaW4td2lkdGg6IDg1MHB4OwogICAgICAgICAgICB9CgogICAgICAgICAgICAuYXl1bWVudC1jb25zdWx0YXRpb24tdGFibGUgdGggewogICAgICAgICAgICAgICAgYmFja2dyb3VuZDogI2Y4ZmFmYzsKICAgICAgICAgICAgICAgIGNvbG9yOiAjNDc1NTY5OwogICAgICAgICAgICAgICAgdGV4dC1hbGlnbjogbGVmdDsKICAgICAgICAgICAgICAgIGZvbnQtc2l6ZTogMTJweDsKICAgICAgICAgICAgICAgIHRleHQtdHJhbnNmb3JtOiB1cHBlcmNhc2U7CiAgICAgICAgICAgICAgICBsZXR0ZXItc3BhY2luZzogLjA0ZW07CiAgICAgICAgICAgICAgICBwYWRkaW5nOiAxNHB4OwogICAgICAgICAgICAgICAgYm9yZGVyLWJvdHRvbTogMXB4IHNvbGlkICNlMmU4ZjA7CiAgICAgICAgICAgIH0KCiAgICAgICAgICAgIC5heXVtZW50LWNvbnN1bHRhdGlvbi10YWJsZSB0ZCB7CiAgICAgICAgICAgICAgICBwYWRkaW5nOiAxNXB4IDE0cHg7CiAgICAgICAgICAgICAgICBib3JkZXItYm90dG9tOiAxcHggc29saWQgI2VlZjJmNzsKICAgICAgICAgICAgICAgIHZlcnRpY2FsLWFsaWduOiB0b3A7CiAgICAgICAgICAgICAgICBjb2xvcjogIzMzNDE1NTsKICAgICAgICAgICAgfQoKICAgICAgICAgICAgLmF5dW1lbnQtY29uc3VsdGF0aW9uLXRhYmxlIHRyOmxhc3QtY2hpbGQgdGQgewogICAgICAgICAgICAgICAgYm9yZGVyLWJvdHRvbTogMDsKICAgICAgICAgICAgfQoKICAgICAgICAgICAgLmF5dW1lbnQtY29uc3VsdGF0aW9uLWFjdGlvbnMtY2VsbCB7CiAgICAgICAgICAgICAgICBkaXNwbGF5OiBmbGV4OwogICAgICAgICAgICAgICAgZ2FwOiA3cHg7CiAgICAgICAgICAgICAgICBmbGV4LXdyYXA6IHdyYXA7CiAgICAgICAgICAgIH0KCiAgICAgICAgICAgIC5heXVtZW50LWVtcHR5LWNvbnN1bHRhdGlvbnMgewogICAgICAgICAgICAgICAgdGV4dC1hbGlnbjogY2VudGVyOwogICAgICAgICAgICAgICAgcGFkZGluZzogNDVweCAyMHB4OwogICAgICAgICAgICAgICAgY29sb3I6ICM2NDc0OGI7CiAgICAgICAgICAgIH0KCiAgICAgICAgICAgIEBtZWRpYShtYXgtd2lkdGg6NzAwcHgpIHsKICAgICAgICAgICAgICAgIC5heXVtZW50LWNvbnN1bHRhdGlvbi1ncmlkIHsKICAgICAgICAgICAgICAgICAgICBncmlkLXRlbXBsYXRlLWNvbHVtbnM6IDFmcjsKICAgICAgICAgICAgICAgIH0KCiAgICAgICAgICAgICAgICAuYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQuZnVsbCB7CiAgICAgICAgICAgICAgICAgICAgZ3JpZC1jb2x1bW46IGF1dG87CiAgICAgICAgICAgICAgICB9CgogICAgICAgICAgICAgICAgLmF5dW1lbnQtY29uc3VsdGF0aW9uLWNhcmQgewogICAgICAgICAgICAgICAgICAgIHBhZGRpbmc6IDIwcHg7CiAgICAgICAgICAgICAgICB9CiAgICAgICAgICAgIH0KICAgICAgICA8L3N0eWxlPg=='); $form_html=base64_decode('ICAgICAgICAgICAgPGZvcm0gbWV0aG9kPSJwb3N0Ij4KICAgICAgICAgICAgICAgIDxpbnB1dCB0eXBlPSJoaWRkZW4iIG5hbWU9ImF5dW1lbnRfZG9jdG9yX3NhdmVfY29uc3VsdGF0aW9uIiB2YWx1ZT0iMSI+CiAgICAgICAgICAgICAgICA8P3BocCB3cF9ub25jZV9maWVsZCggJ2F5dW1lbnRfc2F2ZV9jb25zdWx0YXRpb24nLCAnYXl1bWVudF9jb25zdWx0YXRpb25fbm9uY2UnICk7ID8+CgogICAgICAgICAgICAgICAgPGlucHV0IHR5cGU9ImhpZGRlbiIgbmFtZT0icGF0aWVudF9kYl9pZCIgdmFsdWU9Ijw/cGhwIGVjaG8gZXNjX2F0dHIoICRwYXRpZW50LT5pZCApOyA/PiI+CgogICAgICAgICAgICAgICAgPD9waHAgaWYgKCAkZWRpdF9jb25zdWx0YXRpb24gKSA6ID8+CiAgICAgICAgICAgICAgICAgICAgPGlucHV0IHR5cGU9ImhpZGRlbiIgbmFtZT0iY29uc3VsdGF0aW9uX2lkIiB2YWx1ZT0iPD9waHAgZWNobyBlc2NfYXR0ciggJGVkaXRfY29uc3VsdGF0aW9uLT5pZCApOyA/PiI+CiAgICAgICAgICAgICAgICA8P3BocCBlbmRpZjsgPz4KCiAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1zZWN0aW9uIj4KICAgICAgICAgICAgICAgICAgICA8aDM+8J+ThSBWaXNpdCBJbmZvcm1hdGlvbjwvaDM+CgogICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWdyaWQiPgogICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1maWVsZCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8bGFiZWwgZm9yPSJ2aXNpdF9kYXRlIj5WaXNpdCBEYXRlPC9sYWJlbD4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxpbnB1dAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHR5cGU9ImRhdGUiCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWQ9InZpc2l0X2RhdGUiCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgbmFtZT0idmlzaXRfZGF0ZSIKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YWx1ZT0iPD9waHAgZWNobyBlc2NfYXR0ciggJGVkaXRfY29uc3VsdGF0aW9uLT52aXNpdF9kYXRlID8/ICR0b2RheSApOyA/PiIKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXF1aXJlZAogICAgICAgICAgICAgICAgICAgICAgICAgICAgPgogICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxsYWJlbD5QYXRpZW50PC9sYWJlbD4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxpbnB1dCB0eXBlPSJ0ZXh0IiB2YWx1ZT0iPD9waHAgZWNobyBlc2NfYXR0ciggJHBhdGllbnQtPm5hbWUgLiAnIOKAlCAnIC4gJHBhdGllbnQtPnBhdGllbnRfaWQgKTsgPz4iIHJlYWRvbmx5PgogICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KICAgICAgICAgICAgICAgIDwvZGl2PgoKICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLXNlY3Rpb24iPgogICAgICAgICAgICAgICAgICAgIDxoMz7wn6m6IENoaWVmIENvbXBsYWludCAmIEhpc3Rvcnk8L2gzPgoKICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1ncmlkIj4KICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQgZnVsbCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8bGFiZWwgZm9yPSJjaGllZl9jb21wbGFpbnQiPkNoaWVmIENvbXBsYWludDwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGV4dGFyZWEgaWQ9ImNoaWVmX2NvbXBsYWludCIgbmFtZT0iY2hpZWZfY29tcGxhaW50IiBwbGFjZWhvbGRlcj0iTWFpbiBjb21wbGFpbnQgLyBwcmVzZW50aW5nIGNvbmNlcm4iPjw/cGhwIGVjaG8gZXNjX3RleHRhcmVhKCAkZWRpdF9jb25zdWx0YXRpb24tPmNoaWVmX2NvbXBsYWludCA/PyAnJyApOyA/PjwvdGV4dGFyZWE+CiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PgoKICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPGxhYmVsIGZvcj0iZHVyYXRpb24iPkR1cmF0aW9uPC9sYWJlbD4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxpbnB1dCB0eXBlPSJ0ZXh0IiBpZD0iZHVyYXRpb24iIG5hbWU9ImR1cmF0aW9uIiB2YWx1ZT0iPD9waHAgZWNobyBlc2NfYXR0ciggJGVkaXRfY29uc3VsdGF0aW9uLT5kdXJhdGlvbiA/PyAnJyApOyA/PiIgcGxhY2Vob2xkZXI9ImUuZy4gMyBkYXlzLCAyIG1vbnRocyI+CiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PgoKICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPGxhYmVsIGZvcj0ib25zZXQiPk9uc2V0PC9sYWJlbD4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxzZWxlY3QgaWQ9Im9uc2V0IiBuYW1lPSJvbnNldCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPG9wdGlvbiB2YWx1ZT0iIj5TZWxlY3Q8L29wdGlvbj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8P3BocCBmb3JlYWNoICggYXJyYXkoICdTdWRkZW4nLCAnR3JhZHVhbCcsICdJbnNpZGlvdXMnLCAnUmVjdXJyZW50JywgJ05vdCBrbm93bicgKSBhcyAkb3B0aW9uICkgOiA/PgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8b3B0aW9uIHZhbHVlPSI8P3BocCBlY2hvIGVzY19hdHRyKCAkb3B0aW9uICk7ID8+IiA8P3BocCBzZWxlY3RlZCggJGVkaXRfY29uc3VsdGF0aW9uLT5vbnNldCA/PyAnJywgJG9wdGlvbiApOyA/Pj48P3BocCBlY2hvIGVzY19odG1sKCAkb3B0aW9uICk7ID8+PC9vcHRpb24+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPD9waHAgZW5kZm9yZWFjaDsgPz4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvc2VsZWN0PgogICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxsYWJlbCBmb3I9InByb2dyZXNzaW9uIj5Qcm9ncmVzc2lvbjwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8c2VsZWN0IGlkPSJwcm9ncmVzc2lvbiIgbmFtZT0icHJvZ3Jlc3Npb24iPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxvcHRpb24gdmFsdWU9IiI+U2VsZWN0PC9vcHRpb24+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPD9waHAgZm9yZWFjaCAoIGFycmF5KCAnSW1wcm92aW5nJywgJ1N0YWJsZScsICdXb3JzZW5pbmcnLCAnRmx1Y3R1YXRpbmcnLCAnUmVjdXJyZW50JywgJ05vdCBrbm93bicgKSBhcyAkb3B0aW9uICkgOiA/PgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8b3B0aW9uIHZhbHVlPSI8P3BocCBlY2hvIGVzY19hdHRyKCAkb3B0aW9uICk7ID8+IiA8P3BocCBzZWxlY3RlZCggJGVkaXRfY29uc3VsdGF0aW9uLT5wcm9ncmVzc2lvbiA/PyAnJywgJG9wdGlvbiApOyA/Pj48P3BocCBlY2hvIGVzY19odG1sKCAkb3B0aW9uICk7ID8+PC9vcHRpb24+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPD9waHAgZW5kZm9yZWFjaDsgPz4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvc2VsZWN0PgogICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIGZ1bGwiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPGxhYmVsIGZvcj0iYXNzb2NpYXRlZF9zeW1wdG9tcyI+QXNzb2NpYXRlZCBTeW1wdG9tczwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGV4dGFyZWEgaWQ9ImFzc29jaWF0ZWRfc3ltcHRvbXMiIG5hbWU9ImFzc29jaWF0ZWRfc3ltcHRvbXMiIHBsYWNlaG9sZGVyPSJPdGhlciByZWxldmFudCBzeW1wdG9tcyI+PD9waHAgZWNobyBlc2NfdGV4dGFyZWEoICRlZGl0X2NvbnN1bHRhdGlvbi0+YXNzb2NpYXRlZF9zeW1wdG9tcyA/PyAnJyApOyA/PjwvdGV4dGFyZWE+CiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PgoKICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQgZnVsbCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8bGFiZWwgZm9yPSJoaXN0b3J5Ij5IaXN0b3J5IG9mIFByZXNlbnQgSWxsbmVzcyAvIFJlbGV2YW50IEhpc3Rvcnk8L2xhYmVsPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRleHRhcmVhIGlkPSJoaXN0b3J5IiBuYW1lPSJoaXN0b3J5IiBwbGFjZWhvbGRlcj0iQ2xpbmljYWwgaGlzdG9yeSwgcmVsZXZhbnQgcGFzdCBoaXN0b3J5IGFuZCBvdGhlciBpbXBvcnRhbnQgZGV0YWlscyI+PD9waHAgZWNobyBlc2NfdGV4dGFyZWEoICRlZGl0X2NvbnN1bHRhdGlvbi0+aGlzdG9yeSA/PyAnJyApOyA/PjwvdGV4dGFyZWE+CiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PgoKICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQgZnVsbCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8bGFiZWwgZm9yPSJwcmV2aW91c190cmVhdG1lbnQiPlByZXZpb3VzIFRyZWF0bWVudDwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGV4dGFyZWEgaWQ9InByZXZpb3VzX3RyZWF0bWVudCIgbmFtZT0icHJldmlvdXNfdHJlYXRtZW50IiBwbGFjZWhvbGRlcj0iUHJldmlvdXMgbWVkaWNpbmVzLCBwcm9jZWR1cmVzLCByZXNwb25zZSBhbmQgcmVsZXZhbnQgdHJlYXRtZW50IGhpc3RvcnkiPjw/cGhwIGVjaG8gZXNjX3RleHRhcmVhKCAkZWRpdF9jb25zdWx0YXRpb24tPnByZXZpb3VzX3RyZWF0bWVudCA/PyAnJyApOyA/PjwvdGV4dGFyZWE+CiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICAgICAgPC9kaXY+CgogICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iYXl1bWVudC1jb25zdWx0YXRpb24tc2VjdGlvbiI+CiAgICAgICAgICAgICAgICAgICAgPGgzPvCflI4gQ2xpbmljYWwgRXhhbWluYXRpb248L2gzPgoKICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1ncmlkIj4KICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQgZnVsbCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8bGFiZWwgZm9yPSJ2aXRhbHMiPlZpdGFscyAvIE1lYXN1cmVtZW50czwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGV4dGFyZWEgaWQ9InZpdGFscyIgbmFtZT0idml0YWxzIiBwbGFjZWhvbGRlcj0iQlAsIHB1bHNlLCB0ZW1wZXJhdHVyZSwgcmVzcGlyYXRvcnkgcmF0ZSwgU3BP4oKCLCB3ZWlnaHQsIGhlaWdodCwgQk1JLCBldGMuIj48P3BocCBlY2hvIGVzY190ZXh0YXJlYSggJGVkaXRfY29uc3VsdGF0aW9uLT52aXRhbHMgPz8gJycgKTsgPz48L3RleHRhcmVhPgogICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIGZ1bGwiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPGxhYmVsIGZvcj0iZ2VuZXJhbF9leGFtaW5hdGlvbiI+R2VuZXJhbCBFeGFtaW5hdGlvbjwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGV4dGFyZWEgaWQ9ImdlbmVyYWxfZXhhbWluYXRpb24iIG5hbWU9ImdlbmVyYWxfZXhhbWluYXRpb24iIHBsYWNlaG9sZGVyPSJHZW5lcmFsIGFwcGVhcmFuY2UsIG5vdXJpc2htZW50LCBoeWRyYXRpb24sIHBhbGxvciwgaWN0ZXJ1cywgY3lhbm9zaXMsIGNsdWJiaW5nLCBseW1waCBub2RlcywgZWRlbWEsIGV0Yy4iPjw/cGhwIGVjaG8gZXNjX3RleHRhcmVhKCAkZWRpdF9jb25zdWx0YXRpb24tPmdlbmVyYWxfZXhhbWluYXRpb24gPz8gJycgKTsgPz48L3RleHRhcmVhPgogICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIGZ1bGwiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPGxhYmVsIGZvcj0ic3lzdGVtaWNfZXhhbWluYXRpb24iPlN5c3RlbWljIEV4YW1pbmF0aW9uPC9sYWJlbD4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0ZXh0YXJlYSBpZD0ic3lzdGVtaWNfZXhhbWluYXRpb24iIG5hbWU9InN5c3RlbWljX2V4YW1pbmF0aW9uIiBwbGFjZWhvbGRlcj0iQ1ZTLCByZXNwaXJhdG9yeSwgYWJkb21lbiwgQ05TIGFuZCBvdGhlciByZWxldmFudCBzeXN0ZW1pYyBmaW5kaW5ncyI+PD9waHAgZWNobyBlc2NfdGV4dGFyZWEoICRlZGl0X2NvbnN1bHRhdGlvbi0+c3lzdGVtaWNfZXhhbWluYXRpb24gPz8gJycgKTsgPz48L3RleHRhcmVhPgogICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIGZ1bGwiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPGxhYmVsIGZvcj0iZXhhbWluYXRpb24iPk90aGVyIENsaW5pY2FsIEV4YW1pbmF0aW9uIC8gU3BlY2lhbCBGaW5kaW5nczwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGV4dGFyZWEgaWQ9ImV4YW1pbmF0aW9uIiBuYW1lPSJleGFtaW5hdGlvbiIgcGxhY2Vob2xkZXI9IkZvY3VzZWQgZXhhbWluYXRpb24sIGxvY2FsIGV4YW1pbmF0aW9uIG9yIG90aGVyIGNsaW5pY2FsbHkgcmVsZXZhbnQgZmluZGluZ3MiPjw/cGhwIGVjaG8gZXNjX3RleHRhcmVhKCAkZWRpdF9jb25zdWx0YXRpb24tPmV4YW1pbmF0aW9uID8/ICcnICk7ID8+PC90ZXh0YXJlYT4KICAgICAgICAgICAgICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1zZWN0aW9uIj4KICAgICAgICAgICAgICAgICAgICA8aDM+8J+MvyBBeXVydmVkaWMgQXNzZXNzbWVudDwvaDM+CgogICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWdyaWQiPgogICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1maWVsZCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8bGFiZWwgZm9yPSJwcmFrcml0aSI+UHJha3JpdGk8L2xhYmVsPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPGlucHV0IHR5cGU9InRleHQiIGlkPSJwcmFrcml0aSIgbmFtZT0icHJha3JpdGkiIHZhbHVlPSI8P3BocCBlY2hvIGVzY19hdHRyKCAkZWRpdF9jb25zdWx0YXRpb24tPnByYWtyaXRpID8/ICRwYXRpZW50LT5wcmFrcml0aSA/PyAnJyApOyA/PiIgcGxhY2Vob2xkZXI9ImUuZy4gVmF0YS1QaXR0YSI+CiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PgoKICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPGxhYmVsIGZvcj0idmlrcml0aSI+Vmlrcml0aSAvIEN1cnJlbnQgRG9zaGEgU3RhdGU8L2xhYmVsPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPGlucHV0IHR5cGU9InRleHQiIGlkPSJ2aWtyaXRpIiBuYW1lPSJ2aWtyaXRpIiB2YWx1ZT0iPD9waHAgZWNobyBlc2NfYXR0ciggJGVkaXRfY29uc3VsdGF0aW9uLT52aWtyaXRpID8/ICcnICk7ID8+IiBwbGFjZWhvbGRlcj0iQ3VycmVudCBhc3Nlc3NtZW50Ij4KICAgICAgICAgICAgICAgICAgICAgICAgPC9kaXY+CgogICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1maWVsZCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8bGFiZWwgZm9yPSJkb3NoYV9pbnZvbHZlbWVudCI+RG9zaGEgSW52b2x2ZW1lbnQ8L2xhYmVsPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPGlucHV0IHR5cGU9InRleHQiIGlkPSJkb3NoYV9pbnZvbHZlbWVudCIgbmFtZT0iZG9zaGFfaW52b2x2ZW1lbnQiIHZhbHVlPSI8P3BocCBlY2hvIGVzY19hdHRyKCAkZWRpdF9jb25zdWx0YXRpb24tPmRvc2hhX2ludm9sdmVtZW50ID8/ICcnICk7ID8+IiBwbGFjZWhvbGRlcj0iZS5nLiBWYXRhLVBpdHRhIj4KICAgICAgICAgICAgICAgICAgICAgICAgPC9kaXY+CgogICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1maWVsZCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8bGFiZWwgZm9yPSJkdXNoeWEiPkR1c2h5YTwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8aW5wdXQgdHlwZT0idGV4dCIgaWQ9ImR1c2h5YSIgbmFtZT0iZHVzaHlhIiB2YWx1ZT0iPD9waHAgZWNobyBlc2NfYXR0ciggJGVkaXRfY29uc3VsdGF0aW9uLT5kdXNoeWEgPz8gJycgKTsgPz4iIHBsYWNlaG9sZGVyPSJlLmcuIFJhc2EsIFJha3RhLCBNZWRhIj4KICAgICAgICAgICAgICAgICAgICAgICAgPC9kaXY+CgogICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1maWVsZCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8bGFiZWwgZm9yPSJhZ25pIj5BZ25pPC9sYWJlbD4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxzZWxlY3QgaWQ9ImFnbmkiIG5hbWU9ImFnbmkiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxvcHRpb24gdmFsdWU9IiI+U2VsZWN0PC9vcHRpb24+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPD9waHAgZm9yZWFjaCAoIGFycmF5KCAnU2FtYScsICdWaXNoYW1hJywgJ1Rpa3NobmEnLCAnTWFuZGEnLCAnTm90IGFzc2Vzc2VkJyApIGFzICRvcHRpb24gKSA6ID8+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxvcHRpb24gdmFsdWU9Ijw/cGhwIGVjaG8gZXNjX2F0dHIoICRvcHRpb24gKTsgPz4iIDw/cGhwIHNlbGVjdGVkKCAkZWRpdF9jb25zdWx0YXRpb24tPmFnbmkgPz8gJycsICRvcHRpb24gKTsgPz4+PD9waHAgZWNobyBlc2NfaHRtbCggJG9wdGlvbiApOyA/Pjwvb3B0aW9uPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDw/cGhwIGVuZGZvcmVhY2g7ID8+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8L3NlbGVjdD4KICAgICAgICAgICAgICAgICAgICAgICAgPC9kaXY+CgogICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1maWVsZCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8bGFiZWwgZm9yPSJhbWEiPkFtYTwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8c2VsZWN0IGlkPSJhbWEiIG5hbWU9ImFtYSI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPG9wdGlvbiB2YWx1ZT0iIj5TZWxlY3Q8L29wdGlvbj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8P3BocCBmb3JlYWNoICggYXJyYXkoICdQcmVzZW50JywgJ0Fic2VudCcsICdTdXNwZWN0ZWQnLCAnTm90IGFzc2Vzc2VkJyApIGFzICRvcHRpb24gKSA6ID8+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxvcHRpb24gdmFsdWU9Ijw/cGhwIGVjaG8gZXNjX2F0dHIoICRvcHRpb24gKTsgPz4iIDw/cGhwIHNlbGVjdGVkKCAkZWRpdF9jb25zdWx0YXRpb24tPmFtYSA/PyAnJywgJG9wdGlvbiApOyA/Pj48P3BocCBlY2hvIGVzY19odG1sKCAkb3B0aW9uICk7ID8+PC9vcHRpb24+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPD9waHAgZW5kZm9yZWFjaDsgPz4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvc2VsZWN0PgogICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxsYWJlbCBmb3I9Imtvc2h0aGEiPktvc2h0aGE8L2xhYmVsPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPHNlbGVjdCBpZD0ia29zaHRoYSIgbmFtZT0ia29zaHRoYSI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPG9wdGlvbiB2YWx1ZT0iIj5TZWxlY3Q8L29wdGlvbj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8P3BocCBmb3JlYWNoICggYXJyYXkoICdNcmlkdScsICdNYWRoeWFtYScsICdLcnVyYScsICdOb3QgYXNzZXNzZWQnICkgYXMgJG9wdGlvbiApIDogPz4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPG9wdGlvbiB2YWx1ZT0iPD9waHAgZWNobyBlc2NfYXR0ciggJG9wdGlvbiApOyA/PiIgPD9waHAgc2VsZWN0ZWQoICRlZGl0X2NvbnN1bHRhdGlvbi0+a29zaHRoYSA/PyAnJywgJG9wdGlvbiApOyA/Pj48P3BocCBlY2hvIGVzY19odG1sKCAkb3B0aW9uICk7ID8+PC9vcHRpb24+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPD9waHAgZW5kZm9yZWFjaDsgPz4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvc2VsZWN0PgogICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxsYWJlbCBmb3I9ImJhbGEiPkJhbGE8L2xhYmVsPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPHNlbGVjdCBpZD0iYmFsYSIgbmFtZT0iYmFsYSI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPG9wdGlvbiB2YWx1ZT0iIj5TZWxlY3Q8L29wdGlvbj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8P3BocCBmb3JlYWNoICggYXJyYXkoICdQcmF2YXJhJywgJ01hZGh5YW1hJywgJ0F2YXJhJywgJ05vdCBhc3Nlc3NlZCcgKSBhcyAkb3B0aW9uICkgOiA/PgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8b3B0aW9uIHZhbHVlPSI8P3BocCBlY2hvIGVzY19hdHRyKCAkb3B0aW9uICk7ID8+IiA8P3BocCBzZWxlY3RlZCggJGVkaXRfY29uc3VsdGF0aW9uLT5iYWxhID8/ICcnLCAkb3B0aW9uICk7ID8+Pjw/cGhwIGVjaG8gZXNjX2h0bWwoICRvcHRpb24gKTsgPz48L29wdGlvbj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8P3BocCBlbmRmb3JlYWNoOyA/PgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPC9zZWxlY3Q+CiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PgoKICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPGxhYmVsIGZvcj0iYXBwZXRpdGUiPkFwcGV0aXRlPC9sYWJlbD4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxzZWxlY3QgaWQ9ImFwcGV0aXRlIiBuYW1lPSJhcHBldGl0ZSI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPG9wdGlvbiB2YWx1ZT0iIj5TZWxlY3Q8L29wdGlvbj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8P3BocCBmb3JlYWNoICggYXJyYXkoICdOb3JtYWwnLCAnTG93JywgJ0luY3JlYXNlZCcsICdJcnJlZ3VsYXInICkgYXMgJG9wdGlvbiApIDogPz4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPG9wdGlvbiB2YWx1ZT0iPD9waHAgZWNobyBlc2NfYXR0ciggJG9wdGlvbiApOyA/PiIgPD9waHAgc2VsZWN0ZWQoICRlZGl0X2NvbnN1bHRhdGlvbi0+YXBwZXRpdGUgPz8gJycsICRvcHRpb24gKTsgPz4+PD9waHAgZWNobyBlc2NfaHRtbCggJG9wdGlvbiApOyA/Pjwvb3B0aW9uPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDw/cGhwIGVuZGZvcmVhY2g7ID8+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8L3NlbGVjdD4KICAgICAgICAgICAgICAgICAgICAgICAgPC9kaXY+CgogICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1maWVsZCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8bGFiZWwgZm9yPSJkaWdlc3Rpb24iPkRpZ2VzdGlvbjwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8c2VsZWN0IGlkPSJkaWdlc3Rpb24iIG5hbWU9ImRpZ2VzdGlvbiI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPG9wdGlvbiB2YWx1ZT0iIj5TZWxlY3Q8L29wdGlvbj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8P3BocCBmb3JlYWNoICggYXJyYXkoICdOb3JtYWwnLCAnV2VhaycsICdJcnJlZ3VsYXInLCAnU3Ryb25nJyApIGFzICRvcHRpb24gKSA6ID8+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxvcHRpb24gdmFsdWU9Ijw/cGhwIGVjaG8gZXNjX2F0dHIoICRvcHRpb24gKTsgPz4iIDw/cGhwIHNlbGVjdGVkKCAkZWRpdF9jb25zdWx0YXRpb24tPmRpZ2VzdGlvbiA/PyAnJywgJG9wdGlvbiApOyA/Pj48P3BocCBlY2hvIGVzY19odG1sKCAkb3B0aW9uICk7ID8+PC9vcHRpb24+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPD9waHAgZW5kZm9yZWFjaDsgPz4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvc2VsZWN0PgogICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxsYWJlbCBmb3I9ImJvd2VsIj5Cb3dlbCBIYWJpdHM8L2xhYmVsPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPHNlbGVjdCBpZD0iYm93ZWwiIG5hbWU9ImJvd2VsIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8b3B0aW9uIHZhbHVlPSIiPlNlbGVjdDwvb3B0aW9uPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDw/cGhwIGZvcmVhY2ggKCBhcnJheSggJ05vcm1hbCcsICdDb25zdGlwYXRpb24nLCAnTG9vc2Ugc3Rvb2xzJywgJ0lycmVndWxhcicgKSBhcyAkb3B0aW9uICkgOiA/PgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8b3B0aW9uIHZhbHVlPSI8P3BocCBlY2hvIGVzY19hdHRyKCAkb3B0aW9uICk7ID8+IiA8P3BocCBzZWxlY3RlZCggJGVkaXRfY29uc3VsdGF0aW9uLT5ib3dlbCA/PyAnJywgJG9wdGlvbiApOyA/Pj48P3BocCBlY2hvIGVzY19odG1sKCAkb3B0aW9uICk7ID8+PC9vcHRpb24+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPD9waHAgZW5kZm9yZWFjaDsgPz4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvc2VsZWN0PgogICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxsYWJlbCBmb3I9Im1hbGEiPk1hbGE8L2xhYmVsPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPGlucHV0IHR5cGU9InRleHQiIGlkPSJtYWxhIiBuYW1lPSJtYWxhIiB2YWx1ZT0iPD9waHAgZWNobyBlc2NfYXR0ciggJGVkaXRfY29uc3VsdGF0aW9uLT5tYWxhID8/ICcnICk7ID8+IiBwbGFjZWhvbGRlcj0iU3Rvb2wgLyBtYWxhIGFzc2Vzc21lbnQiPgogICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxsYWJlbCBmb3I9Im11dHJhIj5NdXRyYTwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8aW5wdXQgdHlwZT0idGV4dCIgaWQ9Im11dHJhIiBuYW1lPSJtdXRyYSIgdmFsdWU9Ijw/cGhwIGVjaG8gZXNjX2F0dHIoICRlZGl0X2NvbnN1bHRhdGlvbi0+bXV0cmEgPz8gJycgKTsgPz4iIHBsYWNlaG9sZGVyPSJVcmluZSAvIG11dHJhIGFzc2Vzc21lbnQiPgogICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxsYWJlbCBmb3I9InNsZWVwIj5TbGVlcDwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8c2VsZWN0IGlkPSJzbGVlcCIgbmFtZT0ic2xlZXAiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxvcHRpb24gdmFsdWU9IiI+U2VsZWN0PC9vcHRpb24+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPD9waHAgZm9yZWFjaCAoIGFycmF5KCAnR29vZCcsICdQb29yJywgJ0ludGVycnVwdGVkJywgJ0V4Y2Vzc2l2ZScgKSBhcyAkb3B0aW9uICkgOiA/PgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8b3B0aW9uIHZhbHVlPSI8P3BocCBlY2hvIGVzY19hdHRyKCAkb3B0aW9uICk7ID8+IiA8P3BocCBzZWxlY3RlZCggJGVkaXRfY29uc3VsdGF0aW9uLT5zbGVlcCA/PyAnJywgJG9wdGlvbiApOyA/Pj48P3BocCBlY2hvIGVzY19odG1sKCAkb3B0aW9uICk7ID8+PC9vcHRpb24+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPD9waHAgZW5kZm9yZWFjaDsgPz4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvc2VsZWN0PgogICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxsYWJlbCBmb3I9Im5pZHJhIj5OaWRyYTwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8aW5wdXQgdHlwZT0idGV4dCIgaWQ9Im5pZHJhIiBuYW1lPSJuaWRyYSIgdmFsdWU9Ijw/cGhwIGVjaG8gZXNjX2F0dHIoICRlZGl0X2NvbnN1bHRhdGlvbi0+bmlkcmEgPz8gJycgKTsgPz4iIHBsYWNlaG9sZGVyPSJEdXJhdGlvbiAvIHF1YWxpdHkgLyBwYXR0ZXJuIj4KICAgICAgICAgICAgICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1zZWN0aW9uIj4KICAgICAgICAgICAgICAgICAgICA8aDM+8J+TiyBBc3Nlc3NtZW50ICYgRGlhZ25vc2lzPC9oMz4KCiAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iYXl1bWVudC1jb25zdWx0YXRpb24tZ3JpZCI+CiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIGZ1bGwiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPGxhYmVsIGZvcj0iY2xpbmljYWxfZGlhZ25vc2lzIj5DbGluaWNhbCBEaWFnbm9zaXM8L2xhYmVsPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPGlucHV0IHR5cGU9InRleHQiIGlkPSJjbGluaWNhbF9kaWFnbm9zaXMiIG5hbWU9ImNsaW5pY2FsX2RpYWdub3NpcyIgdmFsdWU9Ijw/cGhwIGVjaG8gZXNjX2F0dHIoICRlZGl0X2NvbnN1bHRhdGlvbi0+Y2xpbmljYWxfZGlhZ25vc2lzID8/ICcnICk7ID8+IiBwbGFjZWhvbGRlcj0iQ2xpbmljYWwgLyBiaW9tZWRpY2FsIGRpYWdub3NpcyI+CiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PgoKICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQgZnVsbCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8bGFiZWwgZm9yPSJheXVydmVkaWNfZGlhZ25vc2lzIj5BeXVydmVkaWMgRGlhZ25vc2lzPC9sYWJlbD4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxpbnB1dCB0eXBlPSJ0ZXh0IiBpZD0iYXl1cnZlZGljX2RpYWdub3NpcyIgbmFtZT0iYXl1cnZlZGljX2RpYWdub3NpcyIgdmFsdWU9Ijw/cGhwIGVjaG8gZXNjX2F0dHIoICRlZGl0X2NvbnN1bHRhdGlvbi0+YXl1cnZlZGljX2RpYWdub3NpcyA/PyAnJyApOyA/PiIgcGxhY2Vob2xkZXI9IkF5dXJ2ZWRpYyBkaWFnbm9zaXMgLyByb2dhIGNvcnJlbGF0aW9uIj4KICAgICAgICAgICAgICAgICAgICAgICAgPC9kaXY+CgogICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1maWVsZCBmdWxsIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxsYWJlbCBmb3I9ImRpZmZlcmVudGlhbF9kaWFnbm9zaXMiPkRpZmZlcmVudGlhbCBEaWFnbm9zaXM8L2xhYmVsPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRleHRhcmVhIGlkPSJkaWZmZXJlbnRpYWxfZGlhZ25vc2lzIiBuYW1lPSJkaWZmZXJlbnRpYWxfZGlhZ25vc2lzIiBwbGFjZWhvbGRlcj0iSW1wb3J0YW50IGRpZmZlcmVudGlhbCBkaWFnbm9zZXMgY29uc2lkZXJlZCI+PD9waHAgZWNobyBlc2NfdGV4dGFyZWEoICRlZGl0X2NvbnN1bHRhdGlvbi0+ZGlmZmVyZW50aWFsX2RpYWdub3NpcyA/PyAnJyApOyA/PjwvdGV4dGFyZWE+CiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PgoKICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQgZnVsbCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8bGFiZWwgZm9yPSJhc3Nlc3NtZW50Ij5Bc3Nlc3NtZW50IC8gQ2xpbmljYWwgSW1wcmVzc2lvbjwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGV4dGFyZWEgaWQ9ImFzc2Vzc21lbnQiIG5hbWU9ImFzc2Vzc21lbnQiIHBsYWNlaG9sZGVyPSJDbGluaWNhbCByZWFzb25pbmcsIHNhbXByYXB0aSBhc3Nlc3NtZW50LCBpbXByZXNzaW9uIGFuZCBpbXBvcnRhbnQgZmluZGluZ3MiPjw/cGhwIGVjaG8gZXNjX3RleHRhcmVhKCAkZWRpdF9jb25zdWx0YXRpb24tPmFzc2Vzc21lbnQgPz8gJycgKTsgPz48L3RleHRhcmVhPgogICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KICAgICAgICAgICAgICAgIDwvZGl2PgoKICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLXNlY3Rpb24iPgogICAgICAgICAgICAgICAgICAgIDxoMz7wn5KKIFRyZWF0bWVudCAmIE1hbmFnZW1lbnQgUGxhbjwvaDM+CgogICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWdyaWQiPgogICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1maWVsZCBmdWxsIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxsYWJlbCBmb3I9Im1lZGljaW5lcyI+TWVkaWNpbmVzIC8gRm9ybXVsYXRpb25zPC9sYWJlbD4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0ZXh0YXJlYSBpZD0ibWVkaWNpbmVzIiBuYW1lPSJtZWRpY2luZXMiIHBsYWNlaG9sZGVyPSJPbmUgbWVkaWNpbmUvZm9ybXVsYXRpb24gcGVyIGxpbmUgd2hlcmUgcG9zc2libGUiPjw/cGhwIGVjaG8gZXNjX3RleHRhcmVhKCAkZWRpdF9jb25zdWx0YXRpb24tPm1lZGljaW5lcyA/PyAnJyApOyA/PjwvdGV4dGFyZWE+CiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PgoKICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQgZnVsbCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8bGFiZWwgZm9yPSJtZWRpY2luZV9kb3NlIj5Eb3NlPC9sYWJlbD4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0ZXh0YXJlYSBpZD0ibWVkaWNpbmVfZG9zZSIgbmFtZT0ibWVkaWNpbmVfZG9zZSIgcGxhY2Vob2xkZXI9IkRvc2UgY29ycmVzcG9uZGluZyB0byB0aGUgbWVkaWNpbmVzIGxpc3RlZCBhYm92ZSI+PD9waHAgZWNobyBlc2NfdGV4dGFyZWEoICRlZGl0X2NvbnN1bHRhdGlvbi0+bWVkaWNpbmVfZG9zZSA/PyAnJyApOyA/PjwvdGV4dGFyZWE+CiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PgoKICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPGxhYmVsIGZvcj0iYW51cGFuYSI+QW51cGFuYTwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8aW5wdXQgdHlwZT0idGV4dCIgaWQ9ImFudXBhbmEiIG5hbWU9ImFudXBhbmEiIHZhbHVlPSI8P3BocCBlY2hvIGVzY19hdHRyKCAkZWRpdF9jb25zdWx0YXRpb24tPmFudXBhbmEgPz8gJycgKTsgPz4iIHBsYWNlaG9sZGVyPSJlLmcuIE1hZGh1LCBKYWxhLCBVc2huYSBKYWxhIj4KICAgICAgICAgICAgICAgICAgICAgICAgPC9kaXY+CgogICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1maWVsZCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8bGFiZWwgZm9yPSJtZWRpY2luZV9mcmVxdWVuY3kiPkZyZXF1ZW5jeSAvIFRpbWluZzwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8aW5wdXQgdHlwZT0idGV4dCIgaWQ9Im1lZGljaW5lX2ZyZXF1ZW5jeSIgbmFtZT0ibWVkaWNpbmVfZnJlcXVlbmN5IiB2YWx1ZT0iPD9waHAgZWNobyBlc2NfYXR0ciggJGVkaXRfY29uc3VsdGF0aW9uLT5tZWRpY2luZV9mcmVxdWVuY3kgPz8gJycgKTsgPz4iIHBsYWNlaG9sZGVyPSJlLmcuIE9ELCBCRCwgVERTIC8gYmVmb3JlIGZvb2QiPgogICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxsYWJlbCBmb3I9Im1lZGljaW5lX2R1cmF0aW9uIj5NZWRpY2luZSBEdXJhdGlvbjwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8aW5wdXQgdHlwZT0idGV4dCIgaWQ9Im1lZGljaW5lX2R1cmF0aW9uIiBuYW1lPSJtZWRpY2luZV9kdXJhdGlvbiIgdmFsdWU9Ijw/cGhwIGVjaG8gZXNjX2F0dHIoICRlZGl0X2NvbnN1bHRhdGlvbi0+bWVkaWNpbmVfZHVyYXRpb24gPz8gJycgKTsgPz4iIHBsYWNlaG9sZGVyPSJlLmcuIDcgZGF5cyI+CiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PgoKICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQgZnVsbCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8bGFiZWwgZm9yPSJ0cmVhdG1lbnRfcGxhbiI+T3ZlcmFsbCBUcmVhdG1lbnQgLyBNYW5hZ2VtZW50IFBsYW48L2xhYmVsPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRleHRhcmVhIGlkPSJ0cmVhdG1lbnRfcGxhbiIgbmFtZT0idHJlYXRtZW50X3BsYW4iIHBsYWNlaG9sZGVyPSJPdmVyYWxsIHRoZXJhcGV1dGljIHBsYW4gYW5kIHJhdGlvbmFsZSI+PD9waHAgZWNobyBlc2NfdGV4dGFyZWEoICRlZGl0X2NvbnN1bHRhdGlvbi0+dHJlYXRtZW50X3BsYW4gPz8gJycgKTsgPz48L3RleHRhcmVhPgogICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxsYWJlbCBmb3I9InBhdGh5YSI+UGF0aHlhIC8gQXBhdGh5YTwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGV4dGFyZWEgaWQ9InBhdGh5YSIgbmFtZT0icGF0aHlhIiBwbGFjZWhvbGRlcj0iRGlldCwgbGlmZXN0eWxlIGFuZCByZXN0cmljdGlvbnMiPjw/cGhwIGVjaG8gZXNjX3RleHRhcmVhKCAkZWRpdF9jb25zdWx0YXRpb24tPnBhdGh5YSA/PyAnJyApOyA/PjwvdGV4dGFyZWE+CiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PgoKICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPGxhYmVsIGZvcj0icHJvY2VkdXJlcyI+UHJvY2VkdXJlcyAvIFRoZXJhcGllczwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGV4dGFyZWEgaWQ9InByb2NlZHVyZXMiIG5hbWU9InByb2NlZHVyZXMiIHBsYWNlaG9sZGVyPSJQYW5jaGFrYXJtYSwga3JpeWEsIGxvY2FsIHByb2NlZHVyZXMgb3Igb3RoZXIgdGhlcmFwaWVzIj48P3BocCBlY2hvIGVzY190ZXh0YXJlYSggJGVkaXRfY29uc3VsdGF0aW9uLT5wcm9jZWR1cmVzID8/ICcnICk7ID8+PC90ZXh0YXJlYT4KICAgICAgICAgICAgICAgICAgICAgICAgPC9kaXY+CgogICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1maWVsZCBmdWxsIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxsYWJlbCBmb3I9ImFkdmljZSI+UGF0aWVudCBBZHZpY2U8L2xhYmVsPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRleHRhcmVhIGlkPSJhZHZpY2UiIG5hbWU9ImFkdmljZSIgcGxhY2Vob2xkZXI9IkdlbmVyYWwgaW5zdHJ1Y3Rpb25zLCBwcmVjYXV0aW9ucyBhbmQgcGF0aWVudCBjb3Vuc2VsbGluZyI+PD9waHAgZWNobyBlc2NfdGV4dGFyZWEoICRlZGl0X2NvbnN1bHRhdGlvbi0+YWR2aWNlID8/ICcnICk7ID8+PC90ZXh0YXJlYT4KICAgICAgICAgICAgICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1zZWN0aW9uIj4KICAgICAgICAgICAgICAgICAgICA8aDM+8J+ThSBGb2xsb3ctdXAgJiBDb250aW51aXR5PC9oMz4KCiAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iYXl1bWVudC1jb25zdWx0YXRpb24tZ3JpZCI+CiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxsYWJlbCBmb3I9ImZvbGxvd191cF9kYXRlIj5Gb2xsb3ctdXAgRGF0ZTwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8aW5wdXQgdHlwZT0iZGF0ZSIgaWQ9ImZvbGxvd191cF9kYXRlIiBuYW1lPSJmb2xsb3dfdXBfZGF0ZSIgdmFsdWU9Ijw/cGhwIGVjaG8gZXNjX2F0dHIoICRlZGl0X2NvbnN1bHRhdGlvbi0+Zm9sbG93X3VwX2RhdGUgPz8gJycgKTsgPz4iPgogICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxsYWJlbCBmb3I9ImZvbGxvd191cCI+Rm9sbG93LXVwIEluc3RydWN0aW9uczwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8aW5wdXQgdHlwZT0idGV4dCIgaWQ9ImZvbGxvd191cCIgbmFtZT0iZm9sbG93X3VwIiB2YWx1ZT0iPD9waHAgZWNobyBlc2NfYXR0ciggJGVkaXRfY29uc3VsdGF0aW9uLT5mb2xsb3dfdXAgPz8gJycgKTsgPz4iIHBsYWNlaG9sZGVyPSJlLmcuIFJldmlldyBhZnRlciA3IGRheXMiPgogICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KCiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWZpZWxkIGZ1bGwiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPGxhYmVsIGZvcj0icmVzcG9uc2VfdG9fdHJlYXRtZW50Ij5SZXNwb25zZSB0byBUcmVhdG1lbnQ8L2xhYmVsPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRleHRhcmVhIGlkPSJyZXNwb25zZV90b190cmVhdG1lbnQiIG5hbWU9InJlc3BvbnNlX3RvX3RyZWF0bWVudCIgcGxhY2Vob2xkZXI9IkZvciBmb2xsb3ctdXAgdmlzaXRzOiBpbXByb3ZlbWVudCwgd29yc2VuaW5nLCBhZHZlcnNlIGVmZmVjdHMsIGFkaGVyZW5jZSwgZXRjLiI+PD9waHAgZWNobyBlc2NfdGV4dGFyZWEoICRlZGl0X2NvbnN1bHRhdGlvbi0+cmVzcG9uc2VfdG9fdHJlYXRtZW50ID8/ICcnICk7ID8+PC90ZXh0YXJlYT4KICAgICAgICAgICAgICAgICAgICAgICAgPC9kaXY+CgogICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1maWVsZCBmdWxsIj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxsYWJlbCBmb3I9Im5leHRfcGxhbiI+TmV4dCBQbGFuPC9sYWJlbD4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0ZXh0YXJlYSBpZD0ibmV4dF9wbGFuIiBuYW1lPSJuZXh0X3BsYW4iIHBsYWNlaG9sZGVyPSJXaGF0IHNob3VsZCBoYXBwZW4gYXQgb3IgYmVmb3JlIHRoZSBuZXh0IHJldmlldz8iPjw/cGhwIGVjaG8gZXNjX3RleHRhcmVhKCAkZWRpdF9jb25zdWx0YXRpb24tPm5leHRfcGxhbiA/PyAnJyApOyA/PjwvdGV4dGFyZWE+CiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PgoKICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iYXl1bWVudC1jb25zdWx0YXRpb24tZmllbGQgZnVsbCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8bGFiZWwgZm9yPSJub3RlcyI+QWRkaXRpb25hbCBOb3RlczwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGV4dGFyZWEgaWQ9Im5vdGVzIiBuYW1lPSJub3RlcyIgcGxhY2Vob2xkZXI9IkFueSBhZGRpdGlvbmFsIGNsaW5pY2FsIG5vdGVzIj48P3BocCBlY2hvIGVzY190ZXh0YXJlYSggJGVkaXRfY29uc3VsdGF0aW9uLT5ub3RlcyA/PyAnJyApOyA/PjwvdGV4dGFyZWE+CiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICAgICAgPC9kaXY+CgogICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iYXl1bWVudC1jb25zdWx0YXRpb24tYWN0aW9ucyI+CiAgICAgICAgICAgICAgICAgICAgPGJ1dHRvbiB0eXBlPSJzdWJtaXQiIG5hbWU9ImF5dW1lbnRfZG9jdG9yX3NhdmVfY29uc3VsdGF0aW9uIiB2YWx1ZT0iMSIgY2xhc3M9ImF5dW1lbnQtY29uc3VsdGF0aW9uLWJ1dHRvbiI+CiAgICAgICAgICAgICAgICAgICAgICAgIDw/cGhwIGVjaG8gJGVkaXRfY29uc3VsdGF0aW9uID8gJ+KckyBVcGRhdGUgQ29uc3VsdGF0aW9uJyA6ICfinJMgU2F2ZSBDb25zdWx0YXRpb24nOyA/PgogICAgICAgICAgICAgICAgICAgIDwvYnV0dG9uPgoKICAgICAgICAgICAgICAgICAgICA8P3BocCBpZiAoICRlZGl0X2NvbnN1bHRhdGlvbiApIDogPz4KICAgICAgICAgICAgICAgICAgICAgICAgPGEKICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1idXR0b24gc2Vjb25kYXJ5IgogICAgICAgICAgICAgICAgICAgICAgICAgICAgaHJlZj0iPD9waHAgZWNobyBlc2NfdXJsKCBhZGRfcXVlcnlfYXJnKCBhcnJheSggJ2RvY3Rvcl92aWV3JyA9PiAnY29uc3VsdGF0aW9ucycsICdwYXRpZW50X2lkJyA9PiBhYnNpbnQoICRwYXRpZW50LT5pZCApICksIGdldF9wZXJtYWxpbmsoKSApICk7ID8+IgogICAgICAgICAgICAgICAgICAgICAgICA+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICBDYW5jZWwgRWRpdAogICAgICAgICAgICAgICAgICAgICAgICA8L2E+CiAgICAgICAgICAgICAgICAgICAgPD9waHAgZW5kaWY7ID8+CgogICAgICAgICAgICAgICAgICAgIDxhCiAgICAgICAgICAgICAgICAgICAgICAgIGNsYXNzPSJheXVtZW50LWNvbnN1bHRhdGlvbi1idXR0b24gc2Vjb25kYXJ5IgogICAgICAgICAgICAgICAgICAgICAgICBocmVmPSI8P3BocCBlY2hvIGVzY191cmwoIGFkZF9xdWVyeV9hcmcoIGFycmF5KCAnZG9jdG9yX3ZpZXcnID0+ICdwYXRpZW50cycsICdwYXRpZW50X2lkJyA9PiBhYnNpbnQoICRwYXRpZW50LT5pZCApICksIGdldF9wZXJtYWxpbmsoKSApICk7ID8+IgogICAgICAgICAgICAgICAgICAgID4KICAgICAgICAgICAgICAgICAgICAgICAg4oaQIFBhdGllbnQgUHJvZmlsZQogICAgICAgICAgICAgICAgICAgIDwvYT4KICAgICAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICA8L2Zvcm0+');
    if($patient){
        $edit_consultation=$edit_id?$wpdb->get_row($wpdb->prepare("SELECT * FROM {$ct} WHERE id=%d AND patient_db_id=%d AND doctor_id=%d LIMIT 1",$edit_id,$patient_id,$doctor_id)):null;
        $history=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$ct} WHERE patient_db_id=%d AND doctor_id=%d ORDER BY visit_date DESC,id DESC",$patient_id,$doctor_id));
        ob_start(); ?>
        <div class="ayument-consultation-wrap ayument-doctor-consultation-page">
            <?php echo $style_html; ?>
            <div class="ayument-consultation-header"><h1>🩺 Clinical Consultation</h1><p>Doctor workspace · create and maintain a patient-linked clinical record.</p><div class="ayument-consultation-patient"><strong><?php echo esc_html($patient->name); ?></strong><span class="ayument-consultation-id">Patient ID: <?php echo esc_html($patient->patient_id); ?></span></div><div style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap;"><a class="ayument-consultation-button secondary" href="<?php echo esc_url(add_query_arg(array('doctor_view'=>'patients','patient_id'=>absint($patient->id)),get_permalink())); ?>">← Patient Profile</a><a class="ayument-consultation-button secondary" href="<?php echo esc_url(add_query_arg(array('doctor_view'=>'consultations'),get_permalink())); ?>">All Consultations</a></div></div>
            <?php if(!empty($message)): ?><div class="ayument-consultation-notice <?php echo $error?'error':''; ?>"><?php echo esc_html($message); ?></div><?php endif; ?>
            <div class="ayument-consultation-card"><h2><?php echo $edit_consultation?'✎ Edit Consultation':'＋ New Consultation'; ?></h2><div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;margin-bottom:18px;"><strong>Latest appointment:</strong> <?php $latest=ayument_doctor_latest_appointment($patient_id,$doctor_id); echo $latest?esc_html(mysql2date('d M Y',$latest->appointment_date).' · '.date_i18n('g:i a',strtotime($latest->appointment_time)).' · '.$latest->appointment_type):'No appointment found.'; ?></div><?php eval('?>'.$form_html); ?></div>
            <div class="ayument-consultation-card"><h2>📚 My Consultation History</h2><?php if($history): ?><div style="overflow-x:auto;"><table class="widefat striped" style="min-width:900px;"><thead><tr><th>Date</th><th>Chief Complaint</th><th>Diagnosis</th><th>Dosha</th><th>Follow-up</th><th>Actions</th></tr></thead><tbody><?php foreach($history as $r): ?><tr><td><?php echo esc_html(mysql2date('d M Y',$r->visit_date)); ?></td><td><?php echo esc_html($r->chief_complaint?:'—'); ?></td><td><?php echo esc_html($r->ayurvedic_diagnosis?:($r->clinical_diagnosis?:'—')); ?></td><td><?php echo esc_html($r->dosha_involvement?:'—'); ?></td><td><?php echo esc_html($r->follow_up_date?mysql2date('d M Y',$r->follow_up_date):($r->follow_up?:'—')); ?></td><td><a class="button" href="<?php echo esc_url(add_query_arg(array('doctor_view'=>'consultations','patient_id'=>absint($patient->id),'edit_consultation'=>absint($r->id)),get_permalink())); ?>">Edit</a> <a class="button" href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('doctor_view'=>'consultations','patient_id'=>absint($patient->id),'delete_consultation'=>absint($r->id)),get_permalink()),'ayument_doctor_delete_consultation_'.absint($r->id))); ?>" onclick="return confirm('Delete this consultation record?');">Delete</a></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div style="padding:20px;background:#f8fafc;border-radius:12px;color:#64748b;">No consultations recorded for this patient by you yet.</div><?php endif; ?></div>
        </div>
        <?php return ob_get_clean();
    }
    $pt=ayument_patients_table_name();$at=$wpdb->prefix.'ayument_appointments';
    $rows=$wpdb->get_results($wpdb->prepare("SELECT p.id,p.name,p.patient_id,MAX(a.appointment_date) AS last_appointment,COUNT(DISTINCT a.id) AS visits,COUNT(DISTINCT c.id) AS consultations FROM {$pt} p INNER JOIN {$at} a ON a.patient_id=p.id LEFT JOIN {$ct} c ON c.patient_db_id=p.id AND c.doctor_id=%d WHERE a.doctor_id=%d AND a.status<>'Cancelled' GROUP BY p.id ORDER BY last_appointment DESC,p.name ASC",$doctor_id,$doctor_id));
    ob_start(); ?>
    <div class="ayument-consultation-wrap ayument-doctor-consultation-page"><?php echo $style_html; ?><div class="ayument-consultation-header"><h1>🩺 Consultations</h1><p>Create, review and manage clinical consultation records for your patients.</p></div><div class="ayument-consultation-card"><h2>My Patients / Consultations</h2><?php if(!$rows): ?><div style="padding:25px;background:#f8fafc;border-radius:12px;color:#64748b;">No patients with active appointments are assigned to you yet.</div><?php else: ?><div style="overflow-x:auto;"><table class="widefat striped" style="min-width:850px;"><thead><tr><th>Patient</th><th>Patient ID</th><th>Last Appointment</th><th>Visits</th><th>Consultations</th><th>Action</th></tr></thead><tbody><?php foreach($rows as $row): ?><tr><td><strong><?php echo esc_html($row->name); ?></strong></td><td><?php echo esc_html($row->patient_id); ?></td><td><?php echo esc_html($row->last_appointment?mysql2date('d M Y',$row->last_appointment):'—'); ?></td><td><?php echo esc_html(absint($row->visits)); ?></td><td><?php echo esc_html(absint($row->consultations)); ?></td><td><a class="ayument-consultation-button" href="<?php echo esc_url(add_query_arg(array('doctor_view'=>'consultations','patient_id'=>absint($row->id)),get_permalink())); ?>"><?php echo $row->consultations?'Open Consultation':'＋ Start Consultation'; ?></a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
    <?php return ob_get_clean();
}
if(!shortcode_exists('ayument_doctor_consultations')) add_shortcode('ayument_doctor_consultations','ayument_doctor_consultation_render');
function ayument_consultations_attach_to_doctor_portal($content){if(is_admin()||!is_user_logged_in())return $content;if(empty($_GET['doctor_view'])||'consultations'!==sanitize_key(wp_unslash($_GET['doctor_view'])))return $content;if(!ayument_doctor_consultation_user_id())return $content;if(has_shortcode($content,'ayument_doctor_consultations'))return $content;return $content.do_shortcode('[ayument_doctor_consultations]');}
add_filter('the_content','ayument_consultations_attach_to_doctor_portal',31);

