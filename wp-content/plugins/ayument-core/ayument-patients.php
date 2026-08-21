<?php
/**
 * AyuMent - Patients Module
 *
 * Patient registration, search, profiles and basic patient records.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ---------------------------------------------------------
 * DATABASE
 * ---------------------------------------------------------
 */

function ayument_patients_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'ayument_patients';
}


function ayument_patients_install_table() {

    global $wpdb;

    $table_name      = ayument_patients_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        patient_id VARCHAR(30) NOT NULL,
        name VARCHAR(150) NOT NULL,
        dob DATE NULL,
        gender VARCHAR(30) NULL,
        phone VARCHAR(30) NULL,
        email VARCHAR(150) NULL,
        address TEXT NULL,
        occupation VARCHAR(150) NULL,
        prakriti VARCHAR(100) NULL,
        allergies TEXT NULL,
        medical_history LONGTEXT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY patient_id (patient_id),
        KEY name (name),
        KEY phone (phone)
    ) {$charset_collate};";

    dbDelta( $sql );
}


/**
 * Make sure the table exists.
 *
 * We do this lazily so development works even if
 * the plugin was already activated before this table
 * was introduced.
 */
function ayument_patients_ensure_table() {

    global $wpdb;

    $table_name = ayument_patients_table_name();

    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        )
    );

    if ( $exists !== $table_name ) {
        ayument_patients_install_table();
    }
}


/**
 * Generate patient ID.
 */
function ayument_generate_patient_id() {

    global $wpdb;

    $table_name = ayument_patients_table_name();

    do {

        $number = wp_rand( 10000, 99999 );

        $patient_id = 'AYU-' . $number;

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE patient_id = %s LIMIT 1",
                $patient_id
            )
        );

    } while ( $exists );

    return $patient_id;
}


/**
 * ---------------------------------------------------------
 * MAIN PAGE
 * ---------------------------------------------------------
 */

function ayument_render_patients_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.' ) );
    }

    ayument_patients_ensure_table();

    global $wpdb;

    $table_name = ayument_patients_table_name();

    /*
     * -----------------------------------------------------
     * DELETE PATIENT
     * -----------------------------------------------------
     */

    if (
        isset( $_GET['ayument_delete_patient'] ) &&
        isset( $_GET['_wpnonce'] )
    ) {

        $patient_db_id = absint( $_GET['ayument_delete_patient'] );

        if (
            wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash( $_GET['_wpnonce'] )
                ),
                'ayument_delete_patient_' . $patient_db_id
            )
        ) {

            $wpdb->delete(
                $table_name,
                array(
                    'id' => $patient_db_id,
                ),
                array( '%d' )
            );

            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>Patient deleted successfully.</p>';
            echo '</div>';
        }
    }


    /*
     * -----------------------------------------------------
     * SAVE PATIENT
     * -----------------------------------------------------
     */

    if (
        isset( $_POST['ayument_save_patient'] ) &&
        isset( $_POST['ayument_patient_nonce'] )
    ) {

        if (
            wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash( $_POST['ayument_patient_nonce'] )
                ),
                'ayument_save_patient'
            )
        ) {

            $patient_db_id = isset( $_POST['patient_db_id'] )
                ? absint( $_POST['patient_db_id'] )
                : 0;

            $name = isset( $_POST['patient_name'] )
                ? sanitize_text_field(
                    wp_unslash( $_POST['patient_name'] )
                )
                : '';

            if ( empty( $name ) ) {

                echo '<div class="notice notice-error">';
                echo '<p>Please enter the patient name.</p>';
                echo '</div>';

            } else {

                $data = array(
                    'name' => $name,

                    'dob' => ! empty( $_POST['patient_dob'] )
                        ? sanitize_text_field(
                            wp_unslash( $_POST['patient_dob'] )
                        )
                        : null,

                    'gender' => isset( $_POST['patient_gender'] )
                        ? sanitize_text_field(
                            wp_unslash( $_POST['patient_gender'] )
                        )
                        : '',

                    'phone' => isset( $_POST['patient_phone'] )
                        ? sanitize_text_field(
                            wp_unslash( $_POST['patient_phone'] )
                        )
                        : '',

                    'email' => isset( $_POST['patient_email'] )
                        ? sanitize_email(
                            wp_unslash( $_POST['patient_email'] )
                        )
                        : '',

                    'address' => isset( $_POST['patient_address'] )
                        ? sanitize_textarea_field(
                            wp_unslash( $_POST['patient_address'] )
                        )
                        : '',

                    'occupation' => isset( $_POST['patient_occupation'] )
                        ? sanitize_text_field(
                            wp_unslash( $_POST['patient_occupation'] )
                        )
                        : '',

                    'prakriti' => isset( $_POST['patient_prakriti'] )
                        ? sanitize_text_field(
                            wp_unslash( $_POST['patient_prakriti'] )
                        )
                        : '',

                    'allergies' => isset( $_POST['patient_allergies'] )
                        ? sanitize_textarea_field(
                            wp_unslash( $_POST['patient_allergies'] )
                        )
                        : '',

                    'medical_history' => isset( $_POST['patient_history'] )
                        ? sanitize_textarea_field(
                            wp_unslash( $_POST['patient_history'] )
                        )
                        : '',

                    'updated_at' => current_time( 'mysql' ),
                );


                /*
                 * EDIT EXISTING PATIENT
                 */
                if ( $patient_db_id > 0 ) {

                    $wpdb->update(
                        $table_name,
                        $data,
                        array(
                            'id' => $patient_db_id,
                        )
                    );

                    echo '<div class="notice notice-success is-dismissible">';
                    echo '<p>Patient information updated successfully.</p>';
                    echo '</div>';

                }

                /*
                 * NEW PATIENT
                 */
                else {

                    $data['patient_id'] = ayument_generate_patient_id();
                    $data['created_at']  = current_time( 'mysql' );

                    $wpdb->insert(
                        $table_name,
                        $data
                    );

                    echo '<div class="ayument-patient-success" role="status" aria-live="polite">';
                    echo '<div class="ayument-success-icon" aria-hidden="true">✓</div>';
                    echo '<div class="ayument-success-content">';
                    echo '<strong class="ayument-success-title">Patient registered successfully</strong>';
                    echo '<span class="ayument-success-id">Patient ID: <strong>';
                    echo esc_html( $data['patient_id'] );
                    echo '</strong></span>';
                    echo '</div>';
                    echo '<button type="button" class="ayument-success-close" aria-label="Dismiss notification" onclick="this.parentElement.remove();">×</button>';
                    echo '</div>';
                }
            }
        }
    }


    /*
     * -----------------------------------------------------
     * DETERMINE CURRENT VIEW
     * -----------------------------------------------------
     */

    $view_patient_id = isset( $_GET['patient'] )
        ? absint( $_GET['patient'] )
        : 0;


    /*
     * -----------------------------------------------------
     * EDIT PATIENT
     * -----------------------------------------------------
     */

    $edit_patient = null;

    if (
        isset( $_GET['edit_patient'] ) &&
        absint( $_GET['edit_patient'] ) > 0
    ) {

        $edit_patient = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d LIMIT 1",
                absint( $_GET['edit_patient'] )
            )
        );
    }


    /*
     * -----------------------------------------------------
     * PATIENT PROFILE
     * -----------------------------------------------------
     */

    if ( $view_patient_id > 0 ) {

        $patient = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d LIMIT 1",
                $view_patient_id
            )
        );

        if ( ! $patient ) {

            echo '<div class="wrap">';
            echo '<div class="ayument-patient-error">';
            echo '<h2>Patient not found</h2>';
            echo '<a class="ayument-patient-button" href="' .
                esc_url(
                    admin_url( 'admin.php?page=ayument-patients' )
                ) .
                '">← Back to Patients</a>';
            echo '</div>';
            echo '</div>';

            return;
        }

        ayument_patients_profile_page( $patient );

        return;
    }


    /*
     * -----------------------------------------------------
     * EDIT / ADD FORM
     * -----------------------------------------------------
     */

    if ( $edit_patient || isset( $_GET['add_patient'] ) ) {

        ayument_patients_form( $edit_patient );

        return;
    }


    /*
     * -----------------------------------------------------
     * PATIENT LIST
     * -----------------------------------------------------
     */

    $search = isset( $_GET['patient_search'] )
        ? sanitize_text_field(
            wp_unslash( $_GET['patient_search'] )
        )
        : '';

    if ( ! empty( $search ) ) {

        $like = '%' . $wpdb->esc_like( $search ) . '%';

        $patients = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name}
                 WHERE name LIKE %s
                 OR patient_id LIKE %s
                 OR phone LIKE %s
                 ORDER BY id DESC",
                $like,
                $like,
                $like
            )
        );

    } else {

        $patients = $wpdb->get_results(
            "SELECT * FROM {$table_name} ORDER BY id DESC"
        );
    }


    /*
     * -----------------------------------------------------
     * DASHBOARD
     * -----------------------------------------------------
     */

    $total_patients = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$table_name}"
    );

    ?>

    <div class="wrap ayument-patients-wrap">

        <style>

.ayument-patient-success {
    position: relative;
    display: flex;
    align-items: center;
    gap: 14px;
    width: 100%;
    box-sizing: border-box;
    margin: 18px 0 20px;
    padding: 16px 48px 16px 16px;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    border-left: 5px solid #10b981;
    border-radius: 12px;
    color: #065f46;
    box-shadow: 0 6px 18px rgba(16,185,129,.10);
}

.ayument-success-icon {
    flex: 0 0 38px;
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #10b981;
    color: #fff;
    font-size: 22px;
    font-weight: 700;
    line-height: 1;
}

.ayument-success-content {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.ayument-success-title {
    display: block;
    color: #047857;
    font-size: 15px;
    font-weight: 700;
    line-height: 1.35;
}

.ayument-success-id {
    display: block;
    color: #065f46;
    font-size: 13px;
    line-height: 1.4;
}

.ayument-success-id strong {
    color: #047857;
    font-weight: 700;
}

.ayument-success-close {
    position: absolute;
    top: 50%;
    right: 12px;
    transform: translateY(-50%);
    width: 32px;
    height: 32px;
    padding: 0;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: #047857;
    font-size: 24px;
    line-height: 30px;
    text-align: center;
    cursor: pointer;
    transition: background .18s ease, color .18s ease;
}

.ayument-success-close:hover,
.ayument-success-close:focus {
    background: #d1fae5;
    color: #064e3b;
    outline: none;
}

@media (max-width: 600px) {
    .ayument-patient-success {
        padding: 14px 44px 14px 13px;
    }

    .ayument-success-icon {
        flex-basis: 34px;
        width: 34px;
        height: 34px;
        font-size: 19px;
    }

    .ayument-success-title {
        font-size: 14px;
    }
}


            .ayument-patients-wrap {
                max-width: 1400px;
                margin: 25px auto;
            }

            .ayument-patients-header {
                background: linear-gradient(
                    135deg,
                    #172554,
                    #2563eb
                );
                border-radius: 20px;
                padding: 35px 40px;
                color: #fff;
                margin-bottom: 25px;
                box-shadow: 0 15px 35px rgba(30,64,175,.18);
            }

            .ayument-patients-header h1 {
                color: #fff;
                font-size: 32px;
                margin: 0 0 8px;
                font-weight: 700;
            }

            .ayument-patients-header p {
                margin: 0;
                color: rgba(255,255,255,.85);
                font-size: 16px;
            }

            .ayument-patients-toolbar {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 18px;
                padding: 18px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 15px;
                margin-bottom: 22px;
                box-shadow: 0 8px 25px rgba(15,23,42,.05);
            }

            .ayument-patient-search {
                display: flex;
                gap: 8px;
                flex: 1;
                max-width: 600px;
            }

            .ayument-patient-search input {
                flex: 1;
                min-height: 42px;
                border: 1px solid #dbe3ef;
                border-radius: 10px;
                padding: 0 14px;
                font-size: 14px;
            }

            .ayument-patient-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 42px;
                padding: 0 18px;
                border-radius: 10px;
                border: 0;
                background: #2563eb;
                color: #fff !important;
                text-decoration: none !important;
                cursor: pointer;
                font-weight: 600;
                transition: .2s ease;
            }

            .ayument-patient-button:hover {
                background: #1d4ed8;
                transform: translateY(-1px);
            }

            .ayument-patient-button.secondary {
                background: #eef4ff;
                color: #1d4ed8 !important;
            }

            .ayument-patient-button.danger {
                background: #fff1f2;
                color: #dc2626 !important;
            }

            .ayument-patient-stat {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 20px 24px;
                margin-bottom: 22px;
                display: inline-flex;
                align-items: center;
                gap: 15px;
                box-shadow: 0 8px 25px rgba(15,23,42,.04);
            }

            .ayument-patient-stat-icon {
                width: 48px;
                height: 48px;
                border-radius: 14px;
                background: #eff6ff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
            }

            .ayument-patient-stat strong {
                display: block;
                font-size: 25px;
                color: #172554;
            }

            .ayument-patient-stat span {
                color: #64748b;
                font-size: 13px;
            }

            .ayument-patient-card {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 18px;
                overflow: hidden;
                box-shadow: 0 8px 25px rgba(15,23,42,.05);
            }

            .ayument-patient-table {
                width: 100%;
                border-collapse: collapse;
            }

            .ayument-patient-table th {
                background: #f8fafc;
                color: #475569;
                text-align: left;
                font-size: 12px;
                text-transform: uppercase;
                letter-spacing: .04em;
                padding: 15px;
                border-bottom: 1px solid #e2e8f0;
            }

            .ayument-patient-table td {
                padding: 16px 15px;
                border-bottom: 1px solid #eef2f7;
                vertical-align: middle;
                color: #334155;
            }

            .ayument-patient-table tr:last-child td {
                border-bottom: 0;
            }

            .ayument-patient-name {
                font-weight: 700;
                color: #172554;
                font-size: 15px;
            }

            .ayument-patient-id {
                display: inline-block;
                margin-top: 3px;
                color: #64748b;
                font-size: 12px;
            }

            .ayument-patient-actions {
                display: flex;
                gap: 7px;
                flex-wrap: wrap;
            }

            .ayument-empty {
                padding: 65px 20px;
                text-align: center;
                color: #64748b;
            }

            .ayument-empty-icon {
                font-size: 45px;
                margin-bottom: 10px;
            }

            @media (max-width: 800px) {

                .ayument-patients-toolbar {
                    flex-direction: column;
                    align-items: stretch;
                }

                .ayument-patient-search {
                    max-width: none;
                }

                .ayument-patient-table {
                    min-width: 850px;
                }

                .ayument-patient-card {
                    overflow-x: auto;
                }
            }

        </style>


        <div class="ayument-patients-header">

            <h1>👤 Patients</h1>

            <p>
                Manage patient profiles, clinical information
                and consultation records.
            </p>

        </div>


        <div class="ayument-patient-stat">

            <div class="ayument-patient-stat-icon">
                👥
            </div>

            <div>
                <strong>
                    <?php echo esc_html( $total_patients ); ?>
                </strong>

                <span>
                    Registered Patients
                </span>
            </div>

        </div>


        <div class="ayument-patients-toolbar">

            <form
                method="get"
                class="ayument-patient-search"
            >

                <input
                    type="hidden"
                    name="page"
                    value="ayument-patients"
                >

                <input
                    type="search"
                    name="patient_search"
                    value="<?php echo esc_attr( $search ); ?>"
                    placeholder="Search by patient name, ID or mobile..."
                >

                <button
                    type="submit"
                    class="ayument-patient-button"
                >
                    Search
                </button>

            </form>


            <a
                href="<?php echo esc_url(
                    admin_url(
                        'admin.php?page=ayument-patients&add_patient=1'
                    )
                ); ?>"
                class="ayument-patient-button"
            >
                ＋ Add New Patient
            </a>

        </div>


        <div class="ayument-patient-card">

            <?php if ( empty( $patients ) ) : ?>

                <div class="ayument-empty">

                    <div class="ayument-empty-icon">
                        👤
                    </div>

                    <h2>
                        No patients found
                    </h2>

                    <p>
                        Start by registering your first patient.
                    </p>

                    <a
                        href="<?php echo esc_url(
                            admin_url(
                                'admin.php?page=ayument-patients&add_patient=1'
                            )
                        ); ?>"
                        class="ayument-patient-button"
                    >
                        ＋ Add New Patient
                    </a>

                </div>

            <?php else : ?>

                <table class="ayument-patient-table">

                    <thead>

                        <tr>

                            <th>Patient</th>

                            <th>Gender</th>

                            <th>Phone</th>

                            <th>Prakriti</th>

                            <th>Registered</th>

                            <th>Actions</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ( $patients as $patient ) : ?>

                        <tr>

                            <td>

                                <div class="ayument-patient-name">
                                    <?php echo esc_html( $patient->name ); ?>
                                </div>

                                <div class="ayument-patient-id">
                                    <?php echo esc_html( $patient->patient_id ); ?>
                                </div>

                            </td>


                            <td>
                                <?php
                                echo esc_html(
                                    $patient->gender ?: '—'
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo esc_html(
                                    $patient->phone ?: '—'
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo esc_html(
                                    $patient->prakriti ?: 'Not assessed'
                                );
                                ?>
                            </td>


                            <td>

                                <?php
                                echo esc_html(
                                    mysql2date(
                                        'd M Y',
                                        $patient->created_at
                                    )
                                );
                                ?>

                            </td>


                            <td>

                                <div class="ayument-patient-actions">

                                    <a
                                        class="ayument-patient-button"
                                        href="<?php echo esc_url(
                                            admin_url(
                                                'admin.php?page=ayument-patients&patient=' .
                                                absint( $patient->id )
                                            )
                                        ); ?>"
                                    >
                                        View
                                    </a>


                                    <a
                                        class="ayument-patient-button secondary"
                                        href="<?php echo esc_url(
                                            admin_url(
                                                'admin.php?page=ayument-patients&edit_patient=' .
                                                absint( $patient->id )
                                            )
                                        ); ?>"
                                    >
                                        Edit
                                    </a>


                                    <a
                                        class="ayument-patient-button danger"
                                        href="<?php echo esc_url(
                                            wp_nonce_url(
                                                admin_url(
                                                    'admin.php?page=ayument-patients&ayument_delete_patient=' .
                                                    absint( $patient->id )
                                                ),
                                                'ayument_delete_patient_' .
                                                absint( $patient->id )
                                            )
                                        ); ?>"
                                        onclick="return confirm('Delete this patient record? This action cannot be undone.');"
                                    >
                                        Delete
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>

        </div>

    </div>

    <?php
}


/**
 * ---------------------------------------------------------
 * ADD / EDIT PATIENT FORM
 * ---------------------------------------------------------
 */

function ayument_patients_form( $patient = null ) {

    $editing = ! empty( $patient );

    ?>

    <div class="wrap ayument-patients-wrap">

        <style>

            .ayument-form-header {
                background: linear-gradient(
                    135deg,
                    #172554,
                    #2563eb
                );
                border-radius: 20px;
                padding: 30px 35px;
                color: #fff;
                margin-bottom: 25px;
            }

            .ayument-form-header h1 {
                color: #fff;
                margin: 0 0 7px;
                font-size: 30px;
            }

            .ayument-form-header p {
                margin: 0;
                color: rgba(255,255,255,.85);
            }

            .ayument-form-card {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 18px;
                padding: 30px;
                box-shadow: 0 8px 25px rgba(15,23,42,.05);
            }

            .ayument-form-section {
                margin-bottom: 30px;
            }

            .ayument-form-section h2 {
                font-size: 19px;
                color: #172554;
                border-bottom: 1px solid #e2e8f0;
                padding-bottom: 12px;
                margin-bottom: 20px;
            }

            .ayument-form-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0,1fr));
                gap: 20px;
            }

            .ayument-field.full {
                grid-column: 1 / -1;
            }

            .ayument-field label {
                display: block;
                font-weight: 600;
                color: #334155;
                margin-bottom: 7px;
            }

            .ayument-field input,
            .ayument-field select,
            .ayument-field textarea {
                width: 100%;
                box-sizing: border-box;
                border: 1px solid #dbe3ef;
                border-radius: 10px;
                padding: 11px 13px;
                font-size: 14px;
                background: #fff;
            }

            .ayument-field textarea {
                min-height: 110px;
                resize: vertical;
            }

            .ayument-field input:focus,
            .ayument-field select:focus,
            .ayument-field textarea:focus {
                border-color: #2563eb;
                box-shadow: 0 0 0 3px rgba(37,99,235,.10);
                outline: none;
            }

            .ayument-form-actions {
                display: flex;
                gap: 10px;
                border-top: 1px solid #e2e8f0;
                padding-top: 22px;
            }

            @media(max-width:700px) {
                .ayument-form-grid {
                    grid-template-columns: 1fr;
                }

                .ayument-field.full {
                    grid-column: auto;
                }
            }

        </style>


        <div class="ayument-form-header">

            <h1>
                <?php
                echo $editing
                    ? 'Edit Patient'
                    : 'Add New Patient';
                ?>
            </h1>

            <p>
                <?php
                echo $editing
                    ? 'Update the patient information below.'
                    : 'Create a new patient record in AyuMent.';
                ?>
            </p>

        </div>


        <form
            method="post"
            class="ayument-form-card"
        >

            <?php wp_nonce_field(
                'ayument_save_patient',
                'ayument_patient_nonce'
            ); ?>


            <?php if ( $editing ) : ?>

                <input
                    type="hidden"
                    name="patient_db_id"
                    value="<?php echo esc_attr( $patient->id ); ?>"
                >

            <?php endif; ?>


            <div class="ayument-form-section">

                <h2>
                    👤 Basic Information
                </h2>


                <div class="ayument-form-grid">

                    <div class="ayument-field">

                        <label>
                            Full Name *
                        </label>

                        <input
                            type="text"
                            name="patient_name"
                            required
                            value="<?php
                            echo $editing
                                ? esc_attr( $patient->name )
                                : '';
                            ?>"
                            placeholder="Enter patient's full name"
                        >

                    </div>


                    <div class="ayument-field">

                        <label>
                            Date of Birth
                        </label>

                        <input
                            type="date"
                            name="patient_dob"
                            value="<?php
                            echo $editing
                                ? esc_attr( $patient->dob )
                                : '';
                            ?>"
                        >

                    </div>


                    <div class="ayument-field">

                        <label>
                            Gender
                        </label>

                        <select name="patient_gender">

                            <option value="">
                                Select gender
                            </option>

                            <?php
                            $genders = array(
                                'Male',
                                'Female',
                                'Other',
                            );

                            foreach ( $genders as $gender ) :
                            ?>

                                <option
                                    value="<?php echo esc_attr( $gender ); ?>"
                                    <?php
                                    selected(
                                        $editing
                                            ? $patient->gender
                                            : '',
                                        $gender
                                    );
                                    ?>
                                >
                                    <?php echo esc_html( $gender ); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="ayument-field">

                        <label>
                            Mobile Number
                        </label>

                        <input
                            type="tel"
                            name="patient_phone"
                            value="<?php
                            echo $editing
                                ? esc_attr( $patient->phone )
                                : '';
                            ?>"
                            placeholder="10-digit mobile number"
                        >

                    </div>


                    <div class="ayument-field">

                        <label>
                            Email
                        </label>

                        <input
                            type="email"
                            name="patient_email"
                            value="<?php
                            echo $editing
                                ? esc_attr( $patient->email )
                                : '';
                            ?>"
                            placeholder="patient@example.com"
                        >

                    </div>


                    <div class="ayument-field">

                        <label>
                            Occupation
                        </label>

                        <input
                            type="text"
                            name="patient_occupation"
                            value="<?php
                            echo $editing
                                ? esc_attr( $patient->occupation )
                                : '';
                            ?>"
                            placeholder="Occupation"
                        >

                    </div>


                    <div class="ayument-field full">

                        <label>
                            Address
                        </label>

                        <textarea
                            name="patient_address"
                            placeholder="Residential address"
                        ><?php
                        echo $editing
                            ? esc_textarea( $patient->address )
                            : '';
                        ?></textarea>

                    </div>

                </div>

            </div>


            <div class="ayument-form-section">

                <h2>
                    🌿 Ayurvedic Information
                </h2>


                <div class="ayument-form-grid">

                    <div class="ayument-field">

                        <label>
                            Prakriti
                        </label>

                        <select name="patient_prakriti">

                            <option value="">
                                Not assessed
                            </option>

                            <?php
                            $prakritis = array(
                                'Vata',
                                'Pitta',
                                'Kapha',
                                'Vata-Pitta',
                                'Pitta-Kapha',
                                'Vata-Kapha',
                                'Tridosha / Sama',
                            );

                            foreach ( $prakritis as $prakriti ) :
                            ?>

                                <option
                                    value="<?php echo esc_attr( $prakriti ); ?>"
                                    <?php
                                    selected(
                                        $editing
                                            ? $patient->prakriti
                                            : '',
                                        $prakriti
                                    );
                                    ?>
                                >
                                    <?php echo esc_html( $prakriti ); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="ayument-field">

                        <label>
                            Known Allergies
                        </label>

                        <textarea
                            name="patient_allergies"
                            placeholder="Medicines, foods or other known allergies"
                        ><?php
                        echo $editing
                            ? esc_textarea( $patient->allergies )
                            : '';
                        ?></textarea>

                    </div>


                    <div class="ayument-field full">

                        <label>
                            Medical History
                        </label>

                        <textarea
                            name="patient_history"
                            placeholder="Previous illnesses, surgeries, chronic conditions, ongoing medicines etc."
                        ><?php
                        echo $editing
                            ? esc_textarea( $patient->medical_history )
                            : '';
                        ?></textarea>

                    </div>

                </div>

            </div>


            <div class="ayument-form-actions">

                <button
                    type="submit"
                    name="ayument_save_patient"
                    value="1"
                    class="ayument-patient-button"
                >
                    <?php
                    echo $editing
                        ? '✓ Update Patient'
                        : '✓ Register Patient';
                    ?>
                </button>


                <a
                    href="<?php echo esc_url(
                        admin_url(
                            'admin.php?page=ayument-patients'
                        )
                    ); ?>"
                    class="ayument-patient-button secondary"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

    <?php
}


/**
 * ---------------------------------------------------------
 * PATIENT PROFILE
 * ---------------------------------------------------------
 */

function ayument_patients_profile_page( $patient ) {

    ?>

    <div class="wrap ayument-patients-wrap">

        <style>

            .ayument-profile-header {
                background: linear-gradient(
                    135deg,
                    #172554,
                    #2563eb
                );
                border-radius: 20px;
                padding: 32px 38px;
                color: #fff;
                margin-bottom: 25px;
            }

            .ayument-profile-header h1 {
                color: #fff;
                margin: 0 0 6px;
                font-size: 30px;
            }

            .ayument-profile-header p {
                margin: 0;
                color: rgba(255,255,255,.82);
            }

            .ayument-profile-actions {
                margin-top: 22px;
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }

            .ayument-profile-grid {
                display: grid;
                grid-template-columns: repeat(2,minmax(0,1fr));
                gap: 20px;
            }

            .ayument-profile-card {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 18px;
                padding: 25px;
                box-shadow: 0 8px 25px rgba(15,23,42,.05);
            }

            .ayument-profile-card.full {
                grid-column: 1 / -1;
            }

            .ayument-profile-card h2 {
                margin: 0 0 20px;
                color: #172554;
                font-size: 19px;
            }

            .ayument-profile-row {
                display: flex;
                justify-content: space-between;
                gap: 20px;
                padding: 12px 0;
                border-bottom: 1px solid #eef2f7;
            }

            .ayument-profile-row:last-child {
                border-bottom: 0;
            }

            .ayument-profile-label {
                color: #64748b;
                font-size: 13px;
            }

            .ayument-profile-value {
                color: #172554;
                font-weight: 600;
                text-align: right;
                white-space: pre-line;
            }

            .ayument-profile-text {
                color: #475569;
                line-height: 1.7;
                white-space: pre-line;
            }

            @media(max-width:700px) {
                .ayument-profile-grid {
                    grid-template-columns: 1fr;
                }

                .ayument-profile-card.full {
                    grid-column: auto;
                }
            }

        </style>


        <div class="ayument-profile-header">

            <h1>
                <?php echo esc_html( $patient->name ); ?>
            </h1>

            <p>
                Patient ID:
                <strong>
                    <?php echo esc_html( $patient->patient_id ); ?>
                </strong>
            </p>


            <div class="ayument-profile-actions">

                <a
                    class="ayument-patient-button"
                    href="<?php echo esc_url(
                        admin_url(
                            'admin.php?page=ayument-consultations&patient_id=' .
                            absint( $patient->id )
                        )
                    ); ?>"
                >
                    🩺 New Consultation
                </a>

                <a
                    class="ayument-patient-button"
                    href="<?php echo esc_url(
                        admin_url(
                            'admin.php?page=ayument-patients&edit_patient=' .
                            absint( $patient->id )
                        )
                    ); ?>"
                >
                    ✎ Edit Patient
                </a>


                <a
                    class="ayument-patient-button secondary"
                    href="<?php echo esc_url(
                        admin_url(
                            'admin.php?page=ayument-patients'
                        )
                    ); ?>"
                >
                    ← All Patients
                </a>

            </div>

        </div>


        <div class="ayument-profile-grid">


            <div class="ayument-profile-card">

                <h2>
                    👤 Personal Information
                </h2>


                <div class="ayument-profile-row">

                    <span class="ayument-profile-label">
                        Date of Birth
                    </span>

                    <span class="ayument-profile-value">
                        <?php
                        echo $patient->dob
                            ? esc_html(
                                mysql2date(
                                    'd M Y',
                                    $patient->dob
                                )
                            )
                            : '—';
                        ?>
                    </span>

                </div>


                <div class="ayument-profile-row">

                    <span class="ayument-profile-label">
                        Gender
                    </span>

                    <span class="ayument-profile-value">
                        <?php echo esc_html(
                            $patient->gender ?: '—'
                        ); ?>
                    </span>

                </div>


                <div class="ayument-profile-row">

                    <span class="ayument-profile-label">
                        Mobile
                    </span>

                    <span class="ayument-profile-value">
                        <?php echo esc_html(
                            $patient->phone ?: '—'
                        ); ?>
                    </span>

                </div>


                <div class="ayument-profile-row">

                    <span class="ayument-profile-label">
                        Email
                    </span>

                    <span class="ayument-profile-value">
                        <?php echo esc_html(
                            $patient->email ?: '—'
                        ); ?>
                    </span>

                </div>


                <div class="ayument-profile-row">

                    <span class="ayument-profile-label">
                        Occupation
                    </span>

                    <span class="ayument-profile-value">
                        <?php echo esc_html(
                            $patient->occupation ?: '—'
                        ); ?>
                    </span>

                </div>

            </div>


            <div class="ayument-profile-card">

                <h2>
                    🌿 Ayurvedic Information
                </h2>


                <div class="ayument-profile-row">

                    <span class="ayument-profile-label">
                        Prakriti
                    </span>

                    <span class="ayument-profile-value">
                        <?php echo esc_html(
                            $patient->prakriti ?: 'Not assessed'
                        ); ?>
                    </span>

                </div>


                <div class="ayument-profile-row">

                    <span class="ayument-profile-label">
                        Registered
                    </span>

                    <span class="ayument-profile-value">
                        <?php echo esc_html(
                            mysql2date(
                                'd M Y',
                                $patient->created_at
                            )
                        ); ?>
                    </span>

                </div>


                <div class="ayument-profile-row">

                    <span class="ayument-profile-label">
                        Last Updated
                    </span>

                    <span class="ayument-profile-value">
                        <?php echo esc_html(
                            mysql2date(
                                'd M Y',
                                $patient->updated_at
                            )
                        ); ?>
                    </span>

                </div>

            </div>


            <div class="ayument-profile-card">

                <h2>
                    📍 Address
                </h2>

                <div class="ayument-profile-text">

                    <?php
                    echo $patient->address
                        ? esc_html( $patient->address )
                        : 'No address recorded.';
                    ?>

                </div>

            </div>


            <div class="ayument-profile-card">

                <h2>
                    ⚠️ Allergies
                </h2>

                <div class="ayument-profile-text">

                    <?php
                    echo $patient->allergies
                        ? esc_html( $patient->allergies )
                        : 'No known allergies recorded.';
                    ?>

                </div>

            </div>


            <div class="ayument-profile-card full">

                <h2>
                    🩺 Medical History
                </h2>

                <div class="ayument-profile-text">

                    <?php
                    echo $patient->medical_history
                        ? esc_html( $patient->medical_history )
                        : 'No medical history recorded.';
                    ?>

                </div>

            </div>


            <?php
            /*
             * -------------------------------------------------
             * CONSULTATION HISTORY
             * -------------------------------------------------
             */
            global $wpdb;

            if ( function_exists( 'ayument_consultations_ensure_table' ) ) {
                ayument_consultations_ensure_table();

                $consultations_table = ayument_consultations_table_name();

                $patient_consultations = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT id, visit_date, chief_complaint, assessment, dosha_involvement
                         FROM {$consultations_table}
                         WHERE patient_db_id = %d
                         ORDER BY visit_date DESC, id DESC
                         LIMIT 10",
                        absint( $patient->id )
                    )
                );
            } else {
                $patient_consultations = array();
            }
            ?>

            <div class="ayument-profile-card full">

                <h2>
                    🩺 Consultation History
                </h2>

                <?php if ( ! empty( $patient_consultations ) ) : ?>

                    <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;min-width:700px;">
                            <thead>
                                <tr>
                                    <th style="text-align:left;padding:12px;background:#f8fafc;border-bottom:1px solid #e2e8f0;">Visit Date</th>
                                    <th style="text-align:left;padding:12px;background:#f8fafc;border-bottom:1px solid #e2e8f0;">Chief Complaint</th>
                                    <th style="text-align:left;padding:12px;background:#f8fafc;border-bottom:1px solid #e2e8f0;">Assessment</th>
                                    <th style="text-align:left;padding:12px;background:#f8fafc;border-bottom:1px solid #e2e8f0;">Dosha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $patient_consultations as $consultation ) : ?>
                                    <tr>
                                        <td style="padding:13px;border-bottom:1px solid #eef2f7;">
                                            <?php echo esc_html( mysql2date( 'd M Y', $consultation->visit_date ) ); ?>
                                        </td>
                                        <td style="padding:13px;border-bottom:1px solid #eef2f7;">
                                            <?php echo esc_html( $consultation->chief_complaint ?: '—' ); ?>
                                        </td>
                                        <td style="padding:13px;border-bottom:1px solid #eef2f7;">
                                            <?php echo esc_html( $consultation->assessment ?: '—' ); ?>
                                        </td>
                                        <td style="padding:13px;border-bottom:1px solid #eef2f7;">
                                            <?php echo esc_html( $consultation->dosha_involvement ?: '—' ); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php else : ?>

                    <p class="ayument-profile-text">
                        No consultations recorded yet.
                    </p>

                <?php endif; ?>

                <p style="margin-top:18px;">
                    <a
                        class="ayument-patient-button"
                        href="<?php echo esc_url(
                            admin_url(
                                'admin.php?page=ayument-consultations&patient_id=' .
                                absint( $patient->id )
                            )
                        ); ?>"
                    >
                        <?php echo empty( $patient_consultations ) ? '＋ Start Consultation' : '🩺 Open Consultations'; ?>
                    </a>
                </p>

            </div>


            <div class="ayument-profile-card full">

                <h2>
                    🔗 AyuMent Clinical Modules
                </h2>

                <p class="ayument-profile-text">

                    This patient profile will become the central
                    clinical record for AyuMent.

                    Future modules such as HAM-D, HAM-A,
                    Prakriti Assessment, consultations,
                    prescriptions and appointments can be
                    connected to this patient.

                </p>

            </div>


        </div>

    </div>

    <?php
}


/**
 * ---------------------------------------------------------
 * DOCTOR PORTAL — MY PATIENTS
 * ---------------------------------------------------------
 *
 * Shows only patients connected to the currently logged-in
 * doctor through the AyuMent appointments table.
 *
 * The existing admin Patients module above is intentionally
 * left unchanged.
 */

if ( ! function_exists( 'ayument_doctor_patients_get_doctor_id' ) ) {
    function ayument_doctor_patients_get_doctor_id() {

        $user = wp_get_current_user();

        if ( ! $user || ! $user->ID ) {
            return 0;
        }

        $allowed_roles = array(
            'ayument_doctor',
            'ayument_doctor_pending',
            'administrator',
        );

        foreach ( (array) $user->roles as $role ) {
            if ( in_array( $role, $allowed_roles, true ) ) {
                return (int) $user->ID;
            }
        }

        return 0;
    }
}


if ( ! function_exists( 'ayument_doctor_patients_shortcode' ) ) {
    function ayument_doctor_patients_shortcode() {

        if ( ! is_user_logged_in() ) {
            return '<div class="ayument-doctor-patients-message">Please log in to access your patients.</div>';
        }

        $doctor_id = ayument_doctor_patients_get_doctor_id();

        if ( ! $doctor_id ) {
            return '<div class="ayument-doctor-patients-message">Doctor access is required to view patients.</div>';
        }

        global $wpdb;

        $patients_table     = $wpdb->prefix . 'ayument_patients';
        $appointments_table = $wpdb->prefix . 'ayument_appointments';

        /*
         * Make sure the patient table exists.
         */
        ayument_patients_ensure_table();

        /*
         * Make sure appointments table exists when the
         * appointments module is available.
         */
        if ( function_exists( 'ayument_appointments_create_table' ) ) {
            ayument_appointments_create_table();
        }

        /*
         * Verify both tables exist before querying.
         */
        $patients_exists = $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $patients_table
            )
        );

        $appointments_exists = $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $appointments_table
            )
        );

        if ( $patients_exists !== $patients_table || $appointments_exists !== $appointments_table ) {
            return '<div class="ayument-doctor-patients-message">Patient records are not available yet.</div>';
        }

        /*
         * -----------------------------------------------------
         * VIEW A SINGLE PATIENT
         * -----------------------------------------------------
         */
        $view_patient_id = isset( $_GET['patient_id'] )
            ? absint( $_GET['patient_id'] )
            : 0;

        if ( $view_patient_id > 0 ) {

            /*
             * Security: the patient must have at least one
             * appointment belonging to this doctor.
             */
            $patient = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT p.*,
                            MAX(a.appointment_date) AS last_appointment_date,
                            COUNT(a.id) AS appointment_count
                     FROM {$patients_table} p
                     INNER JOIN {$appointments_table} a
                        ON a.patient_id = p.id
                     WHERE p.id = %d
                       AND a.doctor_id = %d
                     GROUP BY p.id
                     LIMIT 1",
                    $view_patient_id,
                    $doctor_id
                )
            );

            if ( ! $patient ) {
                return '
                    <div class="ayument-doctor-patients-message">
                        <strong>Patient not found.</strong>
                        <p>This patient is not assigned to your doctor account.</p>
                        <a class="ayument-doctor-patients-btn"
                           href="' . esc_url(
                               add_query_arg(
                                   array( 'doctor_view' => 'patients' ),
                                   get_permalink()
                               )
                           ) . '">
                            ← Back to My Patients
                        </a>
                    </div>';
            }

            /*
             * Doctor-specific appointment history.
             */
            $patient_appointments = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT appointment_date,
                            appointment_time,
                            appointment_type,
                            status,
                            reason
                     FROM {$appointments_table}
                     WHERE patient_id = %d
                       AND doctor_id = %d
                     ORDER BY appointment_date DESC, appointment_time DESC
                     LIMIT 20",
                    $view_patient_id,
                    $doctor_id
                )
            );

            ob_start();
            ?>
            <style>
                .ayument-doctor-patient-wrap {
                    max-width: 1200px;
                    margin: 0 auto;
                    font-family: inherit;
                    color: #172033;
                }

                .ayument-doctor-patient-wrap * {
                    box-sizing: border-box;
                }

                .ayument-doctor-patient-hero {
                    background: linear-gradient(135deg,#12306b,#2563eb);
                    color: #fff;
                    padding: 32px;
                    border-radius: 20px;
                    margin-bottom: 22px;
                }

                .ayument-doctor-patient-hero h2 {
                    margin: 0 0 7px;
                    color: #fff;
                    font-size: 30px;
                }

                .ayument-doctor-patient-hero p {
                    margin: 0;
                    color: #eef5ff;
                }

                .ayument-doctor-patient-actions {
                    margin-top: 20px;
                    display: flex;
                    gap: 10px;
                    flex-wrap: wrap;
                }

                .ayument-doctor-patients-btn {
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
                    font-weight: 600;
                    cursor: pointer;
                }

                .ayument-doctor-patients-btn:hover {
                    background: #1d4ed8;
                }

                .ayument-doctor-patients-btn.light {
                    background: rgba(255,255,255,.14);
                    border: 1px solid rgba(255,255,255,.25);
                }

                .ayument-doctor-patients-card {
                    background: #fff;
                    border: 1px solid #e3eaf4;
                    border-radius: 18px;
                    padding: 24px;
                    margin-bottom: 22px;
                    box-shadow: 0 8px 24px rgba(15,23,42,.06);
                }

                .ayument-doctor-patients-card h3 {
                    margin: 0 0 18px;
                    color: #12306b;
                    font-size: 20px;
                }

                .ayument-doctor-patient-grid {
                    display: grid;
                    grid-template-columns: repeat(2,minmax(0,1fr));
                    gap: 18px 30px;
                }

                .ayument-doctor-patient-field {
                    padding: 13px 0;
                    border-bottom: 1px solid #edf1f6;
                }

                .ayument-doctor-patient-label {
                    display: block;
                    color: #64748b;
                    font-size: 13px;
                    margin-bottom: 5px;
                }

                .ayument-doctor-patient-value {
                    color: #172033;
                    font-weight: 600;
                    white-space: pre-line;
                }

                .ayument-doctor-patient-table {
                    width: 100%;
                    border-collapse: collapse;
                    min-width: 700px;
                }

                .ayument-doctor-patient-table th {
                    text-align: left;
                    padding: 12px;
                    background: #f8fafc;
                    color: #475569;
                    font-size: 12px;
                    text-transform: uppercase;
                    letter-spacing: .03em;
                }

                .ayument-doctor-patient-table td {
                    padding: 13px 12px;
                    border-top: 1px solid #edf1f6;
                    vertical-align: top;
                    color: #334155;
                }

                .ayument-doctor-patient-status {
                    display: inline-block;
                    padding: 5px 9px;
                    border-radius: 999px;
                    background: #dbeafe;
                    color: #1d4ed8;
                    font-size: 12px;
                    font-weight: 700;
                }

                .ayument-doctor-patient-status.completed {
                    background: #dcfce7;
                    color: #15803d;
                }

                .ayument-doctor-patient-status.cancelled {
                    background: #fee2e2;
                    color: #b91c1c;
                }

                @media(max-width:700px) {
                    .ayument-doctor-patient-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>

            <div class="ayument-doctor-patient-wrap">

                <div class="ayument-doctor-patient-hero">
                    <h2><?php echo esc_html( $patient->name ); ?></h2>
                    <p>
                        Patient ID:
                        <strong><?php echo esc_html( $patient->patient_id ); ?></strong>
                    </p>

                    <div class="ayument-doctor-patient-actions">
                        <a
                            class="ayument-doctor-patients-btn light"
                            href="<?php echo esc_url(
                                add_query_arg(
                                    array( 'doctor_view' => 'patients' ),
                                    get_permalink()
                                )
                            ); ?>"
                        >
                            ← All Patients
                        </a>
                    </div>
                </div>

                <div class="ayument-doctor-patients-card">
                    <h3>👤 Patient Information</h3>

                    <div class="ayument-doctor-patient-grid">

                        <div class="ayument-doctor-patient-field">
                            <span class="ayument-doctor-patient-label">Date of Birth</span>
                            <span class="ayument-doctor-patient-value">
                                <?php
                                echo $patient->dob
                                    ? esc_html( mysql2date( 'd M Y', $patient->dob ) )
                                    : '—';
                                ?>
                            </span>
                        </div>

                        <div class="ayument-doctor-patient-field">
                            <span class="ayument-doctor-patient-label">Gender</span>
                            <span class="ayument-doctor-patient-value">
                                <?php echo esc_html( $patient->gender ?: '—' ); ?>
                            </span>
                        </div>

                        <div class="ayument-doctor-patient-field">
                            <span class="ayument-doctor-patient-label">Mobile</span>
                            <span class="ayument-doctor-patient-value">
                                <?php echo esc_html( $patient->phone ?: '—' ); ?>
                            </span>
                        </div>

                        <div class="ayument-doctor-patient-field">
                            <span class="ayument-doctor-patient-label">Email</span>
                            <span class="ayument-doctor-patient-value">
                                <?php echo esc_html( $patient->email ?: '—' ); ?>
                            </span>
                        </div>

                        <div class="ayument-doctor-patient-field">
                            <span class="ayument-doctor-patient-label">Occupation</span>
                            <span class="ayument-doctor-patient-value">
                                <?php echo esc_html( $patient->occupation ?: '—' ); ?>
                            </span>
                        </div>

                        <div class="ayument-doctor-patient-field">
                            <span class="ayument-doctor-patient-label">Prakriti</span>
                            <span class="ayument-doctor-patient-value">
                                <?php echo esc_html( $patient->prakriti ?: 'Not assessed' ); ?>
                            </span>
                        </div>

                        <div class="ayument-doctor-patient-field">
                            <span class="ayument-doctor-patient-label">Last Appointment</span>
                            <span class="ayument-doctor-patient-value">
                                <?php
                                echo ! empty( $patient->last_appointment_date )
                                    ? esc_html( mysql2date( 'd M Y', $patient->last_appointment_date ) )
                                    : '—';
                                ?>
                            </span>
                        </div>

                        <div class="ayument-doctor-patient-field">
                            <span class="ayument-doctor-patient-label">Your Appointments</span>
                            <span class="ayument-doctor-patient-value">
                                <?php echo esc_html( absint( $patient->appointment_count ) ); ?>
                            </span>
                        </div>

                    </div>
                </div>

                <div class="ayument-doctor-patients-card">
                    <h3>📍 Address</h3>
                    <div style="color:#475569;line-height:1.7;white-space:pre-line;">
                        <?php echo $patient->address ? esc_html( $patient->address ) : 'No address recorded.'; ?>
                    </div>
                </div>

                <div class="ayument-doctor-patients-card">
                    <h3>⚠️ Allergies</h3>
                    <div style="color:#475569;line-height:1.7;white-space:pre-line;">
                        <?php echo $patient->allergies ? esc_html( $patient->allergies ) : 'No known allergies recorded.'; ?>
                    </div>
                </div>

                <div class="ayument-doctor-patients-card">
                    <h3>🩺 Medical History</h3>
                    <div style="color:#475569;line-height:1.7;white-space:pre-line;">
                        <?php echo $patient->medical_history ? esc_html( $patient->medical_history ) : 'No medical history recorded.'; ?>
                    </div>
                </div>

                <div class="ayument-doctor-patients-card">
                    <h3>📅 Appointment History</h3>

                    <?php if ( empty( $patient_appointments ) ) : ?>

                        <div style="padding:18px;background:#f8fafc;border-radius:12px;color:#64748b;">
                            No appointments recorded for this patient.
                        </div>

                    <?php else : ?>

                        <div style="overflow-x:auto;">
                            <table class="ayument-doctor-patient-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $patient_appointments as $appointment ) : ?>
                                        <tr>
                                            <td>
                                                <?php echo esc_html( mysql2date( 'd M Y', $appointment->appointment_date ) ); ?>
                                            </td>
                                            <td>
                                                <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $appointment->appointment_time ) ) ); ?>
                                            </td>
                                            <td>
                                                <?php echo esc_html( $appointment->appointment_type ?: '—' ); ?>
                                            </td>
                                            <td>
                                                <span class="ayument-doctor-patient-status <?php
                                                    echo 'Completed' === $appointment->status
                                                        ? 'completed'
                                                        : ( 'Cancelled' === $appointment->status ? 'cancelled' : '' );
                                                ?>">
                                                    <?php echo esc_html( $appointment->status ); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo esc_html( $appointment->reason ?: '—' ); ?>
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

            return ob_get_clean();
        }

        /*
         * -----------------------------------------------------
         * MY PATIENTS LIST
         * -----------------------------------------------------
         */

        $search = isset( $_GET['patient_search'] )
            ? sanitize_text_field( wp_unslash( $_GET['patient_search'] ) )
            : '';

        $where_search = '';
        $params = array( $doctor_id );

        if ( $search !== '' ) {

            $like = '%' . $wpdb->esc_like( $search ) . '%';

            $where_search = "
                AND (
                    p.name LIKE %s
                    OR p.patient_id LIKE %s
                    OR p.phone LIKE %s
                )
            ";

            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "
            SELECT
                p.*,
                MAX(a.appointment_date) AS last_appointment_date,
                COUNT(a.id) AS appointment_count
            FROM {$patients_table} p
            INNER JOIN {$appointments_table} a
                ON a.patient_id = p.id
            WHERE a.doctor_id = %d
            {$where_search}
            GROUP BY p.id
            ORDER BY last_appointment_date DESC, p.name ASC
        ";

        $patients = $wpdb->get_results(
            $wpdb->prepare( $sql, $params )
        );

        $total_patients = count( $patients );

        ob_start();
        ?>
        <style>
            .ayument-doctor-patients {
                max-width: 1200px;
                margin: 0 auto;
                font-family: inherit;
                color: #172033;
            }

            .ayument-doctor-patients * {
                box-sizing: border-box;
            }

            .ayument-doctor-patients-hero {
                background: linear-gradient(135deg,#12306b,#2563eb);
                color:#fff;
                padding:32px;
                border-radius:20px;
                margin-bottom:24px;
            }

            .ayument-doctor-patients-hero h2 {
                margin:0 0 8px;
                color:#fff;
                font-size:30px;
            }

            .ayument-doctor-patients-hero p {
                margin:0;
                color:#eef5ff;
            }

            .ayument-doctor-patients-card {
                background:#fff;
                border:1px solid #e3eaf4;
                border-radius:18px;
                padding:24px;
                margin-bottom:24px;
                box-shadow:0 8px 24px rgba(15,23,42,.06);
            }

            .ayument-doctor-patients-toolbar {
                display:flex;
                justify-content:space-between;
                align-items:center;
                gap:15px;
                flex-wrap:wrap;
            }

            .ayument-doctor-patients-search {
                display:flex;
                gap:8px;
                flex:1;
                max-width:650px;
            }

            .ayument-doctor-patients-search input {
                flex:1;
                min-height:44px;
                border:1px solid #dbe3ef;
                border-radius:10px;
                padding:0 14px;
                font-size:14px;
            }

            .ayument-doctor-patients-btn {
                display:inline-flex;
                align-items:center;
                justify-content:center;
                min-height:42px;
                padding:0 17px;
                border-radius:10px;
                border:0;
                background:#2563eb;
                color:#fff !important;
                text-decoration:none !important;
                font-weight:600;
                cursor:pointer;
            }

            .ayument-doctor-patients-btn:hover {
                background:#1d4ed8;
            }

            .ayument-doctor-patients-btn.secondary {
                background:#eef4ff;
                color:#1d4ed8 !important;
            }

            .ayument-doctor-patients-stat {
                display:inline-flex;
                align-items:center;
                gap:14px;
                padding:16px 20px;
                border-radius:14px;
                background:#f8fafc;
                border:1px solid #e3eaf4;
                margin-bottom:20px;
            }

            .ayument-doctor-patients-stat strong {
                font-size:24px;
                color:#12306b;
            }

            .ayument-doctor-patients-stat span {
                color:#64748b;
                font-size:13px;
            }

            .ayument-doctor-patients-table-wrap {
                overflow-x:auto;
            }

            .ayument-doctor-patients-table {
                width:100%;
                border-collapse:collapse;
                min-width:820px;
            }

            .ayument-doctor-patients-table th {
                text-align:left;
                padding:13px 12px;
                background:#f8fafc;
                color:#475569;
                font-size:12px;
                text-transform:uppercase;
                letter-spacing:.03em;
                border-bottom:1px solid #e2e8f0;
            }

            .ayument-doctor-patients-table td {
                padding:16px 12px;
                border-bottom:1px solid #edf1f6;
                vertical-align:middle;
                color:#334155;
            }

            .ayument-doctor-patients-table tr:last-child td {
                border-bottom:0;
            }

            .ayument-doctor-patient-name {
                font-weight:700;
                color:#12306b;
                font-size:15px;
            }

            .ayument-doctor-patient-id {
                margin-top:3px;
                color:#64748b;
                font-size:12px;
            }

            .ayument-doctor-patient-muted {
                color:#64748b;
                font-size:13px;
            }

            .ayument-doctor-patients-empty {
                padding:45px 20px;
                text-align:center;
                color:#64748b;
                background:#f8fafc;
                border-radius:12px;
            }

            @media(max-width:700px) {
                .ayument-doctor-patients-search {
                    max-width:none;
                    width:100%;
                }

                .ayument-doctor-patients-toolbar {
                    align-items:stretch;
                }
            }
        </style>

        <div class="ayument-doctor-patients">

            <div class="ayument-doctor-patients-hero">
                <h2>My Patients</h2>
                <p>Patients connected to your AyuMent appointments and clinical care.</p>
            </div>

            <div class="ayument-doctor-patients-card">

                <div class="ayument-doctor-patients-toolbar">

                    <form
                        method="get"
                        class="ayument-doctor-patients-search"
                    >
                        <?php
                        /*
                         * Preserve the current portal view so the
                         * existing doctor dashboard keeps control.
                         */
                        ?>
                        <input
                            type="hidden"
                            name="doctor_view"
                            value="patients"
                        >

                        <input
                            type="search"
                            name="patient_search"
                            value="<?php echo esc_attr( $search ); ?>"
                            placeholder="Search by patient name, ID or mobile..."
                        >

                        <button
                            type="submit"
                            class="ayument-doctor-patients-btn"
                        >
                            Search
                        </button>
                    </form>

                    <div class="ayument-doctor-patients-stat">
                        <strong><?php echo esc_html( $total_patients ); ?></strong>
                        <span>My Patients</span>
                    </div>

                </div>

            </div>

            <div class="ayument-doctor-patients-card">

                <?php if ( empty( $patients ) ) : ?>

                    <div class="ayument-doctor-patients-empty">
                        <div style="font-size:42px;margin-bottom:10px;">👤</div>
                        <h3 style="margin:0 0 8px;color:#12306b;">No patients yet</h3>
                        <p style="margin:0;">
                            Patients will appear here after an appointment is assigned to you.
                        </p>
                    </div>

                <?php else : ?>

                    <div class="ayument-doctor-patients-table-wrap">
                        <table class="ayument-doctor-patients-table">

                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Gender</th>
                                    <th>Mobile</th>
                                    <th>Prakriti</th>
                                    <th>Last Appointment</th>
                                    <th>Visits</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ( $patients as $patient ) : ?>

                                    <tr>

                                        <td>
                                            <div class="ayument-doctor-patient-name">
                                                <?php echo esc_html( $patient->name ); ?>
                                            </div>

                                            <div class="ayument-doctor-patient-id">
                                                <?php echo esc_html( $patient->patient_id ); ?>
                                            </div>
                                        </td>

                                        <td>
                                            <?php echo esc_html( $patient->gender ?: '—' ); ?>
                                        </td>

                                        <td>
                                            <?php echo esc_html( $patient->phone ?: '—' ); ?>
                                        </td>

                                        <td>
                                            <?php echo esc_html( $patient->prakriti ?: 'Not assessed' ); ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo ! empty( $patient->last_appointment_date )
                                                ? esc_html( mysql2date( 'd M Y', $patient->last_appointment_date ) )
                                                : '—';
                                            ?>
                                        </td>

                                        <td>
                                            <?php echo esc_html( absint( $patient->appointment_count ) ); ?>
                                        </td>

                                        <td>
                                            <a
                                                class="ayument-doctor-patients-btn"
                                                href="<?php echo esc_url(
                                                    add_query_arg(
                                                        array(
                                                            'doctor_view' => 'patients',
                                                            'patient_id'  => absint( $patient->id ),
                                                        ),
                                                        get_permalink()
                                                    )
                                                ); ?>"
                                            >
                                                View Patient
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
        <?php

        return ob_get_clean();
    }
}


if ( ! shortcode_exists( 'ayument_doctor_patients' ) ) {
    add_shortcode(
        'ayument_doctor_patients',
        'ayument_doctor_patients_shortcode'
    );
}


/**
 * Automatically attach My Patients to the existing Doctor Portal.
 *
 * The existing portal already uses:
 *     ?doctor_view=patients
 *
 * Therefore no change to the Doctor Portal page itself is required.
 */
if ( ! function_exists( 'ayument_patients_attach_to_doctor_portal' ) ) {
    function ayument_patients_attach_to_doctor_portal( $content ) {

        if ( is_admin() || ! is_user_logged_in() ) {
            return $content;
        }

        if (
            empty( $_GET['doctor_view'] )
            || 'patients' !== sanitize_key( wp_unslash( $_GET['doctor_view'] ) )
        ) {
            return $content;
        }

        if ( ! ayument_doctor_patients_get_doctor_id() ) {
            return $content;
        }

        if ( has_shortcode( $content, 'ayument_doctor_patients' ) ) {
            return $content;
        }

        return $content . do_shortcode( '[ayument_doctor_patients]' );
    }

    add_filter(
        'the_content',
        'ayument_patients_attach_to_doctor_portal',
        30
    );
}
