<?php
/*
Plugin Name: AyuMent Core
Plugin URI: https://ayument.com
Description: Core functionality for the AyuMent AI-powered Ayurveda platform.
Version: 1.1.0
Author: Mr. Manish Kumar & Mr. Ashish Ranjan
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| AYUMENT ADMIN MENU
|--------------------------------------------------------------------------
*/

function ayument_core_menu() {

    add_menu_page(
        'AyuMent Dashboard',
        'AyuMent',
        'manage_options',
        'ayument-dashboard',
        'ayument_dashboard_page',
        'dashicons-heart',
        25
    );

    add_submenu_page(
        'ayument-dashboard',
        'AI Consultation',
        'AI Consultation',
        'manage_options',
        'ayument-ai',
        'ayument_ai_page'
    );

    add_submenu_page(
        'ayument-dashboard',
        'Consult Doctor',
        'Consult Doctor',
        'manage_options',
        'ayument-doctor',
        'ayument_doctor_page'
    );

    add_submenu_page(
        'ayument-dashboard',
        'Patients',
        'Patients',
        'manage_options',
        'ayument-patients',
        'ayument_patients_page'
    );

    add_submenu_page(
        'ayument-dashboard',
        'Appointments',
        'Appointments',
        'manage_options',
        'ayument-appointments',
        'ayument_appointments_page'
    );

    add_submenu_page(
        'ayument-dashboard',
        'Prescriptions',
        'Prescriptions',
        'manage_options',
        'ayument-prescriptions',
        'ayument_prescriptions_page'
    );

    add_submenu_page(
        'ayument-dashboard',
        'Medicine Store',
        'Medicine Store',
        'manage_options',
        'ayument-store',
        'ayument_store_page'
    );

    add_submenu_page(
        'ayument-dashboard',
        'Research Hub',
        'Research Hub',
        'manage_options',
        'ayument-research',
        'ayument_research_page'
    );

    add_submenu_page(
        'ayument-dashboard',
        'Analytics',
        'Analytics',
        'manage_options',
        'ayument-analytics',
        'ayument_analytics_page'
    );

    add_submenu_page(
        'ayument-dashboard',
        'Settings',
        'Settings',
        'manage_options',
        'ayument-settings',
        'ayument_settings_page'
    );
}

add_action('admin_menu', 'ayument_core_menu');


/*
|--------------------------------------------------------------------------
| DASHBOARD
|--------------------------------------------------------------------------
*/

function ayument_dashboard_page() {

    $modules = array(

        array(
            'icon' => '🤖',
            'title' => 'AI Consultation',
            'description' => 'Get AI-assisted Ayurvedic guidance and preliminary health information.',
            'url' => admin_url('admin.php?page=ayument-ai')
        ),

        array(
            'icon' => '👨‍⚕️',
            'title' => 'Consult Doctor',
            'description' => 'Book a consultation with a verified Ayurvedic doctor.',
            'url' => admin_url('admin.php?page=ayument-doctor')
        ),

        array(
            'icon' => '👤',
            'title' => 'Patients',
            'description' => 'Manage patient profiles and consultation records.',
            'url' => admin_url('admin.php?page=ayument-patients')
        ),

        array(
            'icon' => '📅',
            'title' => 'Appointments',
            'description' => 'Schedule and manage consultations and appointments.',
            'url' => admin_url('admin.php?page=ayument-appointments')
        ),

        array(
            'icon' => '💊',
            'title' => 'Prescriptions',
            'description' => 'Manage prescriptions created during consultations.',
            'url' => admin_url('admin.php?page=ayument-prescriptions')
        ),

        array(
            'icon' => '💊',
            'title' => 'Medicine Store',
            'description' => 'Browse and order Ayurvedic medicines and products.',
            'url' => admin_url('admin.php?page=ayument-store')
        ),

        array(
            'icon' => '📚',
            'title' => 'Research Hub',
            'description' => 'Explore Ayurvedic research, literature and educational resources.',
            'url' => admin_url('admin.php?page=ayument-research')
        ),

        array(
            'icon' => '📊',
            'title' => 'Analytics',
            'description' => 'View platform usage and business analytics.',
            'url' => admin_url('admin.php?page=ayument-analytics')
        ),

        array(
            'icon' => '⚙️',
            'title' => 'Settings',
            'description' => 'Configure AyuMent platform settings.',
            'url' => admin_url('admin.php?page=ayument-settings')
        )

    );
    ?>

    <div class="wrap ayument-dashboard">

        <style>

            .ayument-dashboard {
                max-width: 1100px;
                margin-top: 30px;
            }

            .ayument-header {
                background: linear-gradient(135deg, #315c45, #4f8064);
                color: #ffffff;
                padding: 30px;
                border-radius: 14px;
                margin-bottom: 25px;
                box-shadow: 0 5px 18px rgba(0,0,0,0.12);
            }

            .ayument-header h1 {
                color: #ffffff;
                font-size: 32px;
                margin: 0 0 10px;
            }

            .ayument-header p {
                font-size: 16px;
                margin: 0;
            }

            .ayument-badge {
                display: inline-block;
                margin-top: 15px;
                padding: 7px 14px;
                background: rgba(255,255,255,0.18);
                border-radius: 20px;
                font-size: 13px;
                font-weight: 600;
            }

            .ayument-section h2 {
                font-size: 22px;
                margin-bottom: 18px;
            }

            .ayument-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 20px;
            }

            .ayument-card-link {
                text-decoration: none;
                color: inherit;
                display: block;
            }

            .ayument-card {
                background: #ffffff;
                border: 1px solid #e3e8e5;
                border-radius: 14px;
                padding: 24px;
                min-height: 150px;
                box-sizing: border-box;
                transition: all 0.2s ease;
                box-shadow: 0 3px 12px rgba(0,0,0,0.06);
            }

            .ayument-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 22px rgba(0,0,0,0.12);
                border-color: #315c45;
            }

            .ayument-icon {
                font-size: 28px;
                margin-bottom: 10px;
            }

            .ayument-card h2 {
                font-size: 20px;
                margin: 0 0 8px;
                color: #26372d;
            }

            .ayument-card p {
                color: #647067;
                font-size: 14px;
                line-height: 1.6;
                margin: 0 0 18px;
            }

            .ayument-button {
                display: inline-block;
                padding: 8px 16px;
                background: #315c45;
                color: #ffffff;
                border-radius: 6px;
                font-weight: 600;
                font-size: 13px;
            }

            @media (max-width: 700px) {
                .ayument-grid {
                    grid-template-columns: 1fr;
                }
            }

        </style>


        <div class="ayument-header">

            <h1>🌿 AyuMent Dashboard</h1>

            <p>
                Welcome to the future of AI-assisted Ayurvedic healthcare.
            </p>

            <span class="ayument-badge">
                Development Version 1.1.0
            </span>

        </div>


        <div class="ayument-section">

            <h2>Platform Modules</h2>

            <div class="ayument-grid">

                <?php foreach ($modules as $module) : ?>

                    <a
                        class="ayument-card-link"
                        href="<?php echo $module['url']; ?>"
                    >

                        <div class="ayument-card">

                            <div class="ayument-icon">
                                <?php echo $module['icon']; ?>
                            </div>

                            <h2>
                                <?php echo $module['title']; ?>
                            </h2>

                            <p>
                                <?php echo $module['description']; ?>
                            </p>

                            <span class="ayument-button">
                                Open →
                            </span>

                        </div>

                    </a>

                <?php endforeach; ?>

            </div>

        </div>

    </div>

    <?php
}


/*
|--------------------------------------------------------------------------
| AI CONSULTATION
|--------------------------------------------------------------------------
*/

function ayument_ai_page() {

    $submitted = false;

    if (
        isset($_POST['ayument_ai_submit']) &&
        isset($_POST['ayument_ai_nonce']) &&
        wp_verify_nonce($_POST['ayument_ai_nonce'], 'ayument_ai_consultation')
    ) {
        $submitted = true;

        $patient_name = sanitize_text_field($_POST['patient_name'] ?? '');
        $age = sanitize_text_field($_POST['age'] ?? '');
        $sex = sanitize_text_field($_POST['sex'] ?? '');
        $complaint = sanitize_textarea_field($_POST['complaint'] ?? '');
        $duration = sanitize_text_field($_POST['duration'] ?? '');
        $symptoms = sanitize_textarea_field($_POST['symptoms'] ?? '');
        $appetite = sanitize_text_field($_POST['appetite'] ?? '');
        $digestion = sanitize_text_field($_POST['digestion'] ?? '');
        $sleep = sanitize_text_field($_POST['sleep'] ?? '');
        $bowel = sanitize_text_field($_POST['bowel'] ?? '');
        $history = sanitize_textarea_field($_POST['history'] ?? '');
        $medicines = sanitize_textarea_field($_POST['medicines'] ?? '');
        $allergies = sanitize_textarea_field($_POST['allergies'] ?? '');
        $prakriti = sanitize_text_field($_POST['prakriti'] ?? '');
    }

    ?>

    <div class="wrap ayument-ai-page">

        <style>

            .ayument-ai-page {
                max-width: 1100px;
                margin: 25px auto;
            }

            .ayument-ai-header {
                background: linear-gradient(135deg, #315c45, #4f8064);
                color: #ffffff;
                padding: 30px;
                border-radius: 16px;
                margin-bottom: 22px;
                box-shadow: 0 5px 18px rgba(0,0,0,0.12);
            }

            .ayument-ai-header h1 {
                color: #ffffff;
                margin: 0 0 8px;
                font-size: 30px;
            }

            .ayument-ai-header p {
                margin: 0;
                font-size: 16px;
            }

            .ayument-ai-card {
                background: #ffffff;
                border: 1px solid #dfe8e2;
                border-radius: 16px;
                padding: 28px;
                margin-bottom: 22px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            }

            .ayument-ai-card h2 {
                color: #315c45;
                margin-top: 0;
                margin-bottom: 20px;
                font-size: 22px;
            }

            .ayument-ai-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 20px;
            }

            .ayument-ai-field {
                display: flex;
                flex-direction: column;
            }

            .ayument-ai-field.full {
                grid-column: 1 / -1;
            }

            .ayument-ai-field label {
                font-weight: 600;
                color: #26372d;
                margin-bottom: 7px;
            }

            .ayument-ai-field input,
            .ayument-ai-field select,
            .ayument-ai-field textarea {
                width: 100%;
                box-sizing: border-box;
                border: 1px solid #cfdad3;
                border-radius: 8px;
                padding: 11px 13px;
                font-size: 14px;
                background: #ffffff;
            }

            .ayument-ai-field textarea {
                min-height: 110px;
                resize: vertical;
            }

            .ayument-ai-field input:focus,
            .ayument-ai-field select:focus,
            .ayument-ai-field textarea:focus {
                border-color: #315c45;
                box-shadow: 0 0 0 2px rgba(49,92,69,0.12);
                outline: none;
            }

            .ayument-ai-submit {
                background: #315c45 !important;
                border-color: #315c45 !important;
                color: #ffffff !important;
                padding: 12px 26px !important;
                height: auto !important;
                border-radius: 8px !important;
                font-size: 16px !important;
                font-weight: 600 !important;
                cursor: pointer;
            }

            .ayument-ai-submit:hover {
                background: #244936 !important;
                border-color: #244936 !important;
            }

            .ayument-ai-notice {
                background: #eef7f1;
                border-left: 5px solid #315c45;
                padding: 18px;
                border-radius: 8px;
                margin-bottom: 22px;
            }

            .ayument-ai-notice strong {
                color: #315c45;
            }

            .ayument-ai-result {
                background: #f5faf7;
                border: 1px solid #cfe2d5;
                border-radius: 12px;
                padding: 24px;
            }

            .ayument-ai-result h3 {
                color: #315c45;
                margin-top: 0;
            }

            .ayument-ai-warning {
                background: #fff8e8;
                border-left: 5px solid #d99a18;
                padding: 16px;
                border-radius: 8px;
                margin-top: 18px;
            }

            .ayument-back {
                margin-top: 20px;
            }

            @media (max-width: 700px) {
                .ayument-ai-grid {
                    grid-template-columns: 1fr;
                }

                .ayument-ai-field.full {
                    grid-column: auto;
                }
            }

        </style>


        <div class="ayument-ai-header">

            <h1>🤖 AyuMent AI Consultation</h1>

            <p>
                AI-assisted Ayurvedic assessment and preliminary health guidance.
            </p>

        </div>


        <div class="ayument-ai-notice">

            <strong>Important:</strong>

            This consultation is intended for preliminary guidance and educational
            support. It does not replace examination or diagnosis by a qualified
            healthcare professional.

        </div>


        <form method="post">

            <?php wp_nonce_field('ayument_ai_consultation', 'ayument_ai_nonce'); ?>


            <div class="ayument-ai-card">

                <h2>👤 Patient Information</h2>

                <div class="ayument-ai-grid">

                    <div class="ayument-ai-field">

                        <label for="patient_name">
                            Patient Name
                        </label>

                        <input
                            type="text"
                            id="patient_name"
                            name="patient_name"
                            placeholder="Enter patient name"
                            value="<?php echo esc_attr($patient_name ?? ''); ?>"
                        >

                    </div>


                    <div class="ayument-ai-field">

                        <label for="age">
                            Age
                        </label>

                        <input
                            type="number"
                            id="age"
                            name="age"
                            min="0"
                            max="120"
                            placeholder="Age"
                            value="<?php echo esc_attr($age ?? ''); ?>"
                        >

                    </div>


                    <div class="ayument-ai-field">

                        <label for="sex">
                            Sex
                        </label>

                        <select id="sex" name="sex">

                            <option value="">Select</option>

                            <option value="Male"
                                <?php selected($sex ?? '', 'Male'); ?>>
                                Male
                            </option>

                            <option value="Female"
                                <?php selected($sex ?? '', 'Female'); ?>>
                                Female
                            </option>

                            <option value="Other"
                                <?php selected($sex ?? '', 'Other'); ?>>
                                Other
                            </option>

                        </select>

                    </div>


                    <div class="ayument-ai-field">

                        <label for="prakriti">
                            Prakriti
                        </label>

                        <select id="prakriti" name="prakriti">

                            <option value="">Not assessed</option>

                            <option value="Vata"
                                <?php selected($prakriti ?? '', 'Vata'); ?>>
                                Vata
                            </option>

                            <option value="Pitta"
                                <?php selected($prakriti ?? '', 'Pitta'); ?>>
                                Pitta
                            </option>

                            <option value="Kapha"
                                <?php selected($prakriti ?? '', 'Kapha'); ?>>
                                Kapha
                            </option>

                            <option value="Vata-Pitta"
                                <?php selected($prakriti ?? '', 'Vata-Pitta'); ?>>
                                Vata-Pitta
                            </option>

                            <option value="Pitta-Kapha"
                                <?php selected($prakriti ?? '', 'Pitta-Kapha'); ?>>
                                Pitta-Kapha
                            </option>

                            <option value="Vata-Kapha"
                                <?php selected($prakriti ?? '', 'Vata-Kapha'); ?>>
                                Vata-Kapha
                            </option>

                            <option value="Tridosha"
                                <?php selected($prakriti ?? '', 'Tridosha'); ?>>
                                Tridosha
                            </option>

                        </select>

                    </div>

                </div>

            </div>


            <div class="ayument-ai-card">

                <h2>🩺 Chief Complaint</h2>

                <div class="ayument-ai-grid">

                    <div class="ayument-ai-field full">

                        <label for="complaint">
                            Main Complaint
                        </label>

                        <textarea
                            id="complaint"
                            name="complaint"
                            placeholder="What is the main health concern?"
                        ><?php echo esc_textarea($complaint ?? ''); ?></textarea>

                    </div>


                    <div class="ayument-ai-field">

                        <label for="duration">
                            Duration
                        </label>

                        <input
                            type="text"
                            id="duration"
                            name="duration"
                            placeholder="e.g. 3 days, 2 months"
                            value="<?php echo esc_attr($duration ?? ''); ?>"
                        >

                    </div>


                    <div class="ayument-ai-field full">

                        <label for="symptoms">
                            Associated Symptoms
                        </label>

                        <textarea
                            id="symptoms"
                            name="symptoms"
                            placeholder="Describe other symptoms..."
                        ><?php echo esc_textarea($symptoms ?? ''); ?></textarea>

                    </div>

                </div>

            </div>


            <div class="ayument-ai-card">

                <h2>🌿 Ayurvedic Assessment</h2>

                <div class="ayument-ai-grid">

                    <div class="ayument-ai-field">

                        <label for="appetite">
                            Appetite
                        </label>

                        <select id="appetite" name="appetite">

                            <option value="">Select</option>
                            <option value="Normal">Normal</option>
                            <option value="Low">Low</option>
                            <option value="Increased">Increased</option>
                            <option value="Irregular">Irregular</option>

                        </select>

                    </div>


                    <div class="ayument-ai-field">

                        <label for="digestion">
                            Digestion
                        </label>

                        <select id="digestion" name="digestion">

                            <option value="">Select</option>
                            <option value="Normal">Normal</option>
                            <option value="Weak">Weak</option>
                            <option value="Irregular">Irregular</option>
                            <option value="Strong">Strong</option>

                        </select>

                    </div>


                    <div class="ayument-ai-field">

                        <label for="sleep">
                            Sleep
                        </label>

                        <select id="sleep" name="sleep">

                            <option value="">Select</option>
                            <option value="Good">Good</option>
                            <option value="Poor">Poor</option>
                            <option value="Interrupted">Interrupted</option>
                            <option value="Excessive">Excessive</option>

                        </select>

                    </div>


                    <div class="ayument-ai-field">

                        <label for="bowel">
                            Bowel Habits
                        </label>

                        <select id="bowel" name="bowel">

                            <option value="">Select</option>
                            <option value="Normal">Normal</option>
                            <option value="Constipation">Constipation</option>
                            <option value="Loose">Loose stools</option>
                            <option value="Irregular">Irregular</option>

                        </select>

                    </div>

                </div>

            </div>


            <div class="ayument-ai-card">

                <h2>📋 Medical Information</h2>

                <div class="ayument-ai-grid">

                    <div class="ayument-ai-field full">

                        <label for="history">
                            Medical History
                        </label>

                        <textarea
                            id="history"
                            name="history"
                            placeholder="Previous illnesses, surgeries, diabetes, hypertension, etc."
                        ><?php echo esc_textarea($history ?? ''); ?></textarea>

                    </div>


                    <div class="ayument-ai-field full">

                        <label for="medicines">
                            Current Medicines
                        </label>

                        <textarea
                            id="medicines"
                            name="medicines"
                            placeholder="List current medicines, supplements or Ayurvedic preparations."
                        ><?php echo esc_textarea($medicines ?? ''); ?></textarea>

                    </div>


                    <div class="ayument-ai-field full">

                        <label for="allergies">
                            Allergies
                        </label>

                        <textarea
                            id="allergies"
                            name="allergies"
                            placeholder="Known drug, food or other allergies."
                        ><?php echo esc_textarea($allergies ?? ''); ?></textarea>

                    </div>

                </div>

            </div>


            <div class="ayument-ai-card">

                <button
                    type="submit"
                    name="ayument_ai_submit"
                    value="1"
                    class="button button-primary ayument-ai-submit"
                >
                    🤖 Generate AI Assessment
                </button>

            </div>

        </form>


        <?php if ($submitted) : ?>
            <?php

/* =========================================================
 * AYUMENT AI ASSESSMENT ENGINE
 * ========================================================= */

$patient_name = isset($patient_name) ? $patient_name : '';
$complaint    = isset($complaint) ? $complaint : '';
$duration     = isset($duration) ? $duration : '';


$ayument_ai_result = '';

if (defined('AYUMENT_OPENAI_API_KEY') && AYUMENT_OPENAI_API_KEY) {

    $ayument_prompt = "
You are the AyuMent AI assistant for an Ayurveda-focused health information platform.

Analyze the following consultation information:

Patient name: " . ($patient_name ?? 'Not provided') . "
Chief complaint: " . ($complaint ?? 'Not provided') . "
Duration: " . ($duration ?? 'Not provided') . "

Provide a structured preliminary assessment.

Use this structure:

1. Clinical Summary
2. Ayurvedic Considerations
3. Possible Dosha Involvement
4. Agni / Ama Considerations
5. Preliminary Assessment
6. General Supportive Measures
7. Red Flags / When to Seek Professional Care

Important:
- Do not claim to make a definitive diagnosis.
- Do not prescribe prescription medicines.
- Clearly distinguish preliminary Ayurvedic reasoning from medical diagnosis.
- Mention professional consultation when appropriate.
- Keep the response practical and understandable.
";

    $ayument_response = wp_remote_post(
        'https://api.openai.com/v1/chat/completions',
        array(
            'timeout' => 60,
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . AYUMENT_OPENAI_API_KEY,
            ),
            'body' => wp_json_encode(
                array(
                    'model' => 'gpt-4o-mini',
                    'messages' => array(
                        array(
                            'role' => 'system',
                            'content' => 'You are a careful Ayurveda-focused health information assistant.'
                        ),
                        array(
                            'role' => 'user',
                            'content' => $ayument_prompt
                        )
                    ),
                    'temperature' => 0.3,
                )
            ),
        )
    );

    if (!is_wp_error($ayument_response)) {

    $ayument_body = json_decode(
        wp_remote_retrieve_body($ayument_response),
        true
    );

    if (
        isset($ayument_body['choices'][0]['message']['content'])
    ) {
        $ayument_ai_result =
            $ayument_body['choices'][0]['message']['content'];
    }
}

if (empty($ayument_ai_result)) {

    if (is_wp_error($ayument_response)) {

        $ayument_ai_result = 'AyuMent AI Error: ' . $ayument_response->get_error_message();

    } elseif (isset($ayument_body['error']['message'])) {

        $ayument_ai_result = 'AyuMent OpenAI Error: ' . $ayument_body['error']['message'];

    } else {

        $ayument_ai_result = 'AyuMent AI did not return a usable response. Please check the OpenAI API connection.';
    }
}

}
?>

            <div class="ayument-ai-card">

                <div class="ayument-ai-result">

                    <h3>🧠 AyuMent Preliminary Assessment</h3>

                    <p>
                        <strong>Patient:</strong>
                        <?php echo esc_html($patient_name ?: 'Not provided'); ?>
                    </p>

                    <p>
                        <strong>Chief Complaint:</strong>
                        <?php echo esc_html($complaint ?: 'Not provided'); ?>
                    </p>

                    <p>
                        <strong>Duration:</strong>
                        <?php echo esc_html($duration ?: 'Not provided'); ?>
                    </p>

                    <hr>

                    <h3>Assessment Status</h3>

                    <p>
                        Your consultation information has been successfully
                        collected by the AyuMent system.
                    </p>

                    <?php if ( ! empty( $ayument_ai_result ) ) : ?>

    <div class="ayument-ai-response">
        <h3>🤖 AyuMent AI Assessment</h3>

        <div class="ayument-ai-response-content">
            <?php echo wpautop( esc_html( $ayument_ai_result ) ); ?>
        </div>
    </div>

<?php else : ?>

    <p>
        The AI assessment could not be generated at this time.
        Please try again.
    </p>


                    <div class="ayument-ai-warning">

                        <strong>⚠ Safety Notice</strong>

                        <p>
                            This preliminary system must not be used as a
                            substitute for professional medical diagnosis,
                            emergency care, or prescribed treatment.
                        </p>

                    </div>

                </div>

            </div>

        <?php endif; ?>


        <div class="ayument-back">

            <a
                href="<?php echo esc_url(admin_url('admin.php?page=ayument-dashboard')); ?>"
                class="button"
            >
                ← Back to AyuMent Dashboard
            </a>

        </div>

    </div>

<?php endif; ?>
    <?php
}


/*
|--------------------------------------------------------------------------
| CONSULT DOCTOR
|--------------------------------------------------------------------------
*/

function ayument_doctor_page() {
    ayument_module_page(
        '👨‍⚕️ Consult Doctor',
        'Connect users with verified Ayurvedic doctors.',
        'Doctor consultation and booking functionality will be developed here.'
    );
}


/*
|--------------------------------------------------------------------------
| PATIENTS
|--------------------------------------------------------------------------
*/

function ayument_patients_page() {
    ayument_module_page(
        '👤 Patients',
        'Manage patient profiles and consultation records.',
        'Patient management functionality will be developed here.'
    );
}


/*
|--------------------------------------------------------------------------
| APPOINTMENTS
|--------------------------------------------------------------------------
*/

function ayument_appointments_page() {
    ayument_module_page(
        '📅 Appointments',
        'Schedule and manage consultations and appointments.',
        'Appointment scheduling functionality will be developed here.'
    );
}


/*
|--------------------------------------------------------------------------
| PRESCRIPTIONS
|--------------------------------------------------------------------------
*/

function ayument_prescriptions_page() {
    ayument_module_page(
        '💊 Prescriptions',
        'Manage prescriptions created during consultations.',
        'Prescription management functionality will be developed here.'
    );
}


/*
|--------------------------------------------------------------------------
| MEDICINE STORE
|--------------------------------------------------------------------------
*/

function ayument_store_page() {
    ayument_module_page(
        '💊 Medicine Store',
        'Browse and order Ayurvedic medicines and products.',
        'The Ayurvedic medicine store will be developed here.'
    );
}


/*
|--------------------------------------------------------------------------
| RESEARCH HUB
|--------------------------------------------------------------------------
*/

function ayument_research_page() {
    ayument_module_page(
        '📚 Research Hub',
        'Explore Ayurvedic research, literature and educational resources.',
        'Research and educational resources will be developed here.'
    );
}


/*
|--------------------------------------------------------------------------
| ANALYTICS
|--------------------------------------------------------------------------
*/

function ayument_analytics_page() {
    ayument_module_page(
        '📊 Analytics',
        'View platform usage and business analytics.',
        'Analytics and platform insights will be developed here.'
    );
}


/*
|--------------------------------------------------------------------------
| SETTINGS
|--------------------------------------------------------------------------
*/

function ayument_settings_page() {
    ayument_module_page(
        '⚙️ Settings',
        'Configure AyuMent platform settings.',
        'AyuMent configuration and settings will be developed here.'
    );
}


/*
|--------------------------------------------------------------------------
| COMMON MODULE PAGE
|--------------------------------------------------------------------------
*/

/**
 * Render a standard AyuMent module page.
 *
 * @param string $title
 * @param string $description
 * @param string $message
 */
function ayument_module_page($title, $description, $message) {
    ?>

    <div class="wrap">

        <div
            style="
                max-width:900px;
                background:#ffffff;
                padding:35px;
                margin-top:30px;
                border-radius:14px;
                border:1px solid #e3e8e5;
                box-shadow:0 4px 15px rgba(0,0,0,0.08);
            "
        >

            <h1 style="font-size:30px;">
                <?php echo $title; ?>
            </h1>

            <p style="font-size:17px;color:#555;">
                <?php echo $description; ?>
            </p>

            <hr>

            <div
                style="
                    margin-top:25px;
                    padding:22px;
                    background:#f1f7f3;
                    border-left:5px solid #315c45;
                    border-radius:8px;
                "
            >

                <h2 style="margin-top:0;">
                    AyuMent Development Module
                </h2>

                <p style="font-size:16px;">
                    <?php echo $message; ?>
                </p>

                <p>
                    <strong>Status:</strong>
                    Development
                </p>

            </div>

            <p style="margin-top:25px;">
                <a
                    href="<?php echo admin_url('admin.php?page=ayument-dashboard'); ?>"
                    class="button button-primary"
                >
                    ← Back to AyuMent Dashboards
                </a>
            </p> 

        </div>

    </div>
    <?php
}  
require_once plugin_dir_path(__FILE__) . 'ayument-quick-tools.php';