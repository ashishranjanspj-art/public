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
        KEY visit_date (visit_date)
    ) {$charset_collate};";

    dbDelta( $sql );
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
