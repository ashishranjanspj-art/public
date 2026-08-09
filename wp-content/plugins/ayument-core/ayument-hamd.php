<?php
/**
 * AyuMent - HAM-D17 Assessment
 *
 * Hamilton Depression Rating Scale - 17 item clinician-assisted assessment.
 *
 * IMPORTANT:
 * This implementation is intended as an educational/clinical-support
 * feature. HAM-D17 is a clinician-rated instrument and should not be
 * presented as an autonomous diagnostic test.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'ayument_hamd_add_menu');

function ayument_hamd_add_menu() {
    add_submenu_page(
        'ayument',
        'HAM-D17 Assessment',
        'HAM-D17 Assessment',
        'manage_options',
        'ayument-hamd',
        'ayument_hamd_page'
    );
}

/* =========================================================
 * HAM-D 17 ITEMS
 * ========================================================= */

function ayument_hamd_items() {

    return array(

        1 => array(
            'title' => 'Depressed Mood',
            'help'  => 'In simple terms: Has the person been feeling sad, low, empty, or unhappy during the past week?',
            'max'   => 4,
            'options' => array(
                0 => 'Absent',
                1 => 'Sadness, etc. indicated only on questioning',
                2 => 'Sadness reported spontaneously',
                3 => 'Communicates sadness non-verbally',
                4 => 'Virtually only these feelings expressed'
            )
        ),

        2 => array(
            'title' => 'Feelings of Guilt',
            'help'  => 'In simple terms: Does the person feel unusually guilty, blame themselves, or feel they have done something wrong?',
            'max'   => 4,
            'options' => array(
                0 => 'Absent',
                1 => 'Self-reproach; feels they have let people down',
                2 => 'Ideas of guilt or rumination over past errors',
                3 => 'Feels present illness is a punishment; guilty delusions',
                4 => 'Guilt accompanied by delusional conviction'
            )
        ),

        3 => array(
            'title' => 'Suicidal Thoughts / Behaviour',
            'help'  => 'In simple terms: Has the person felt that life is not worth living, wished to be dead, thought about suicide, or attempted suicide?',
            'max'   => 4,
            'options' => array(
                0 => 'Absent',
                1 => 'Feels life is not worth living',
                2 => 'Wishes they were dead or has thoughts of possible death',
                3 => 'Suicidal thoughts or gestures',
                4 => 'Suicide attempt'
            )
        ),

        4 => array(
            'title' => 'Insomnia – Early',
            'help'  => 'In simple terms: Does the person have trouble falling asleep when they first go to bed?',
            'max'   => 2,
            'options' => array(
                0 => 'No difficulty falling asleep',
                1 => 'Occasional difficulty falling asleep',
                2 => 'Frequent difficulty falling asleep'
            )
        ),

        5 => array(
            'title' => 'Insomnia – Middle',
            'help'  => 'In simple terms: Does the person wake up or become restless during the night?',
            'max'   => 2,
            'options' => array(
                0 => 'No difficulty during the night',
                1 => 'Complains of restlessness or disturbance during the night',
                2 => 'Wakes during the night or gets out of bed'
            )
        ),

        6 => array(
            'title' => 'Insomnia – Late',
            'help'  => 'In simple terms: Does the person wake up too early and have trouble going back to sleep?',
            'max'   => 2,
            'options' => array(
                0 => 'No difficulty waking early',
                1 => 'Wakes early but can return to sleep',
                2 => 'Unable to return to sleep after waking early'
            )
        ),

        7 => array(
            'title' => 'Work and Activities',
            'help'  => 'In simple terms: Has sadness, low energy, or loss of interest made it harder for the person to work, study, enjoy hobbies, or do normal activities?',
            'max'   => 4,
            'options' => array(
                0 => 'No difficulty',
                1 => 'Thoughts and feelings of incapacity or fatigue related to activities',
                2 => 'Loss of interest in activities, hobbies or work',
                3 => 'Decrease in actual time spent in activities',
                4 => 'Stopped working/activities because of present illness'
            )
        ),

        8 => array(
            'title' => 'Psychomotor Retardation',
            'help'  => 'In simple terms: Does the person appear unusually slow when speaking, moving, or responding?',
            'max'   => 4,
            'options' => array(
                0 => 'Normal speech and activity',
                1 => 'Slight slowing during interview',
                2 => 'Obvious slowing during interview',
                3 => 'Interview difficult because of marked slowing',
                4 => 'Complete stupor'
            )
        ),

        9 => array(
            'title' => 'Psychomotor Agitation',
            'help'  => 'In simple terms: Does the person seem unusually restless, fidgety, or unable to sit still?',
            'max'   => 4,
            'options' => array(
                0 => 'None',
                1 => 'Restlessness during interview',
                2 => 'Clearly restless',
                3 => 'Frequent movement or inability to remain seated',
                4 => 'Marked agitation'
            )
        ),

        10 => array(
            'title' => 'Anxiety – Psychological',
            'help'  => 'In simple terms: Does the person feel unusually worried, tense, fearful, or easily irritated?',
            'max'   => 4,
            'options' => array(
                0 => 'No difficulty',
                1 => 'Subjective tension and irritability',
                2 => 'Worrying about minor matters',
                3 => 'Marked apprehension or fear',
                4 => 'Fearful or disabling anxiety'
            )
        ),

        11 => array(
            'title' => 'Anxiety – Somatic',
            'help'  => 'In simple terms: Is the anxiety causing physical symptoms such as stomach upset, sweating, trembling, or a racing heart?',
            'max'   => 4,
            'options' => array(
                0 => 'Absent',
                1 => 'Mild gastrointestinal or autonomic symptoms',
                2 => 'Moderate somatic symptoms',
                3 => 'Severe somatic symptoms',
                4 => 'Incapacitating somatic symptoms'
            )
        ),

        12 => array(
            'title' => 'Somatic Symptoms – Gastrointestinal',
            'help'  => 'In simple terms: Has the person lost appetite or had difficulty eating normally?',
            'max'   => 2,
            'options' => array(
                0 => 'None',
                1 => 'Loss of appetite but eating without encouragement',
                2 => 'Difficulty eating without encouragement or requests for food'
            )
        ),

        13 => array(
            'title' => 'General Somatic Symptoms',
            'help'  => 'In simple terms: Does the person have physical complaints such as tiredness, low energy, heaviness, or body aches?',
            'max'   => 2,
            'options' => array(
                0 => 'None',
                1 => 'Heaviness, aching, loss of energy or fatigue',
                2 => 'Any definite somatic symptom clearly present'
            )
        ),

        14 => array(
            'title' => 'Genital Symptoms',
            'help'  => 'In simple terms: Has there been a noticeable change in sexual interest or sexual functioning compared with usual?',
            'max'   => 2,
            'options' => array(
                0 => 'Absent',
                1 => 'Mild or doubtful disturbance',
                2 => 'Clearly present disturbance'
            )
        ),

        15 => array(
            'title' => 'Hypochondriasis',
            'help'  => 'In simple terms: Is the person very worried or convinced that they have a physical illness, even when the concern may be excessive?',
            'max'   => 4,
            'options' => array(
                0 => 'Absent',
                1 => 'Preoccupation with bodily symptoms',
                2 => 'Preoccupation with health or disease',
                3 => 'Strong conviction of physical disease',
                4 => 'Hypochondriacal delusions'
            )
        ),

        16 => array(
            'title' => 'Loss of Weight',
            'help'  => 'In simple terms: Has the person lost weight during the recent period without intentionally trying to lose it?',
            'max'   => 2,
            'options' => array(
                0 => 'No weight loss',
                1 => 'Slight or doubtful weight loss',
                2 => 'Definite weight loss'
            )
        ),

        17 => array(
            'title' => 'Insight',
            'help'  => 'In simple terms: Does the person understand or accept that they are currently unwell?',
            'max'   => 2,
            'options' => array(
                0 => 'Recognizes being ill',
                1 => 'Recognizes illness but attributes it to other causes',
                2 => 'Denies being ill'
            )
        )
    );
}


/* =========================================================
 * ADMIN MENU
 * ========================================================= */

function ayument_hamd_admin_menu() {

    add_submenu_page(
        'ayument',
        'HAM-D17 Assessment',
        'HAM-D17 Assessment',
        'manage_options',
        'ayument-hamd',
        'ayument_hamd_page'
    );
}

add_action('admin_menu', 'ayument_hamd_admin_menu');


/* =========================================================
 * RESULT / SEVERITY
 * ========================================================= */

function ayument_hamd_severity(int $total): array {

    if ($total <= 7) {
        return array(
            'label' => 'Normal / Minimal',
            'class' => 'minimal',
            'description' => 'Score is within the commonly used minimal/no-depression range.'
        );
    }

    if ($total <= 13) {
        return array(
            'label' => 'Mild',
            'class' => 'mild',
            'description' => 'Score falls within a commonly used mild symptom range.'
        );
    }

    if ($total <= 18) {
        return array(
            'label' => 'Moderate',
            'class' => 'moderate',
            'description' => 'Score falls within a commonly used moderate symptom range.'
        );
    }

    if ($total <= 22) {
        return array(
            'label' => 'Severe',
            'class' => 'severe',
            'description' => 'Score falls within a commonly used severe symptom range.'
        );
    }

    return array(
        'label' => 'Very Severe',
        'class' => 'very-severe',
        'description' => 'Score is in the very severe range.'
    );
}



/* =========================================================
 * SPEEDOMETER / RESULT HELPERS
 * ========================================================= */

function ayument_hamd_gauge_percent(int $total): int {
    $total = max(0, min(52, $total));
    return (int) round(($total / 52) * 100);
}

function ayument_hamd_score_level(int $score, int $max): string {
    if ($score <= 0) {
        return 'None';
    }

    if ($max >= 4) {
        if ($score >= 3) {
            return 'Higher';
        }
        if ($score >= 2) {
            return 'Moderate';
        }
        return 'Mild';
    }

    if ($score >= 2) {
        return 'Higher';
    }

    return 'Mild';
}


/* =========================================================
 * RESULT / SYMPTOM DOMAIN HELPERS
 * ========================================================= */

function ayument_hamd_domains(): array {
    return array(
        'Mood & Cognition' => array(1, 2, 3, 15, 17),
        'Sleep' => array(4, 5, 6),
        'Activity & Psychomotor' => array(7, 8, 9),
        'Anxiety' => array(10, 11),
        'Physical / Somatic' => array(12, 13, 14, 16),
    );
}


/* =========================================================
 * MAIN PAGE
 * ========================================================= */

function ayument_hamd_page() {

    $items = ayument_hamd_items();

    $submitted = false;
    $scores = array();
    $total = 0;
    $suicide_score = 0;
    $error = '';

    if (
        isset($_POST['ayument_hamd_submit']) &&
        check_admin_referer('ayument_hamd_assessment', 'ayument_hamd_nonce')
    ) {

        $valid = true;

        foreach ($items as $number => $item) {

            $field = 'hamd_' . $number;

            if (!isset($_POST[$field])) {
                $valid = false;
                break;
            }

            $score = intval($_POST[$field]);

            if ($score < 0 || $score > intval($item['max'])) {
                $valid = false;
                break;
            }

            $scores[$number] = $score;
            $total += $score;
        }

        if (!$valid) {
            $error = 'Please complete all 17 HAM-D items before submitting the assessment.';
            $scores = array();
            $total = 0;
        } else {
            $submitted = true;
            $suicide_score = isset($scores[3]) ? intval($scores[3]) : 0;
        }
    }

    ?>

    <div class="wrap ayument-hamd-wrap">

        <style>

            .ayument-hamd-wrap {
                max-width: 1100px;
            }

            .ayument-hamd-hero {
                background: linear-gradient(135deg, #172554, #2563eb);
                color: #fff;
                padding: 30px;
                border-radius: 16px;
                margin: 20px 0;
                box-shadow: 0 10px 30px rgba(0,0,0,.08);
            }

            .ayument-hamd-hero h1 {
                color: #fff;
                margin: 0 0 8px;
                font-size: 30px;
            }

            .ayument-hamd-hero p {
                margin: 5px 0;
                font-size: 15px;
            }

            .ayument-hamd-card {
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 14px;
                padding: 22px;
                margin: 16px 0;
                box-shadow: 0 4px 15px rgba(0,0,0,.04);
            }

            .ayument-hamd-card h2 {
                margin-top: 0;
                color: #172554;
            }

            .ayument-hamd-question {
                font-size: 17px;
                font-weight: 700;
                margin-bottom: 14px;
            }

            .ayument-hamd-question small {
                display: block;
                margin-top: 5px;
                color: #64748b;
                font-weight: 400;
            }

            .ayument-hamd-help {
                position: relative;
                display: inline-flex;
                vertical-align: middle;
                margin-left: 7px;
            }

            .ayument-hamd-help-button {
                width: 22px;
                height: 22px;
                padding: 0;
                border: 1px solid #2563eb;
                border-radius: 50%;
                background: #eff6ff;
                color: #2563eb;
                font-size: 13px;
                font-weight: 800;
                line-height: 20px;
                text-align: center;
                cursor: help;
                vertical-align: middle;
            }

            .ayument-hamd-help-button:hover,
            .ayument-hamd-help-button:focus {
                background: #2563eb;
                color: #fff;
                outline: none;
            }

            .ayument-hamd-help-tooltip {
                display: none;
                position: absolute;
                z-index: 1000;
                left: 28px;
                top: -7px;
                width: 330px;
                max-width: 70vw;
                padding: 12px 14px;
                border-radius: 9px;
                background: #172554;
                color: #fff;
                font-size: 13px;
                font-weight: 400;
                line-height: 1.5;
                box-shadow: 0 8px 22px rgba(0,0,0,.18);
                text-align: left;
            }

            .ayument-hamd-help-tooltip::before {
                content: "";
                position: absolute;
                left: -7px;
                top: 12px;
                border-width: 7px 7px 7px 0;
                border-style: solid;
                border-color: transparent #172554 transparent transparent;
            }

            /* Show the explanation when the user hovers over the ? button. */
            .ayument-hamd-help:hover .ayument-hamd-help-tooltip,
            .ayument-hamd-help:focus-within .ayument-hamd-help-tooltip,
            .ayument-hamd-help.is-open .ayument-hamd-help-tooltip {
                display: block;
            }

            @media (max-width: 700px) {
                .ayument-hamd-help-tooltip {
                    position: fixed;
                    left: 50%;
                    top: 50%;
                    transform: translate(-50%, -50%);
                    width: min(88vw, 360px);
                    max-width: 88vw;
                    padding: 16px;
                    font-size: 14px;
                    box-shadow: 0 12px 35px rgba(0,0,0,.28);
                }

                .ayument-hamd-help-tooltip::before {
                    display: none;
                }

                .ayument-hamd-help-backdrop {
                    display: none;
                    position: fixed;
                    inset: 0;
                    z-index: 999;
                    background: rgba(0,0,0,.28);
                }

                .ayument-hamd-help.is-open .ayument-hamd-help-backdrop {
                    display: block;
                }
            }

            .ayument-hamd-option {
                display: block;
                padding: 12px 14px;
                margin: 8px 0;
                border: 1px solid #dbe3ef;
                border-radius: 10px;
                cursor: pointer;
                transition: .15s ease;
                background: #fafcff;
            }

            .ayument-hamd-option:hover {
                border-color: #2563eb;
                background: #eff6ff;
            }

            .ayument-hamd-option input {
                margin-right: 10px;
            }

            .ayument-hamd-submit {
                background: #2563eb !important;
                color: #fff !important;
                border: none !important;
                padding: 13px 28px !important;
                border-radius: 9px !important;
                font-size: 16px !important;
                cursor: pointer;
            }

            .ayument-hamd-result {
                text-align: center;
                padding: 35px;
                border-radius: 16px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
            }

            .ayument-hamd-score {
                font-size: 56px;
                line-height: 1;
                font-weight: 800;
                color: #172554;
                margin: 15px 0;
            }

            .ayument-hamd-severity {
                display: inline-block;
                padding: 8px 18px;
                border-radius: 999px;
                font-weight: 700;
                background: #e0f2fe;
                color: #075985;
            }

            .ayument-hamd-alert {
                margin: 20px 0;
                padding: 18px;
                border-radius: 12px;
                background: #fff1f2;
                border: 2px solid #fb7185;
                color: #881337;
                text-align: left;
            }

            .ayument-hamd-disclaimer {
                margin-top: 25px;
                padding: 18px;
                border-left: 4px solid #2563eb;
                background: #eff6ff;
                color: #1e3a8a;
            }

            .ayument-hamd-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }

            .ayument-hamd-table th,
            .ayument-hamd-table td {
                padding: 10px;
                border-bottom: 1px solid #e5e7eb;
                text-align: left;
            }

            .ayument-hamd-table th {
                background: #f8fafc;
            }

            .ayument-hamd-print {
                margin-top: 20px;
            }


            /* =====================================================
             * MODERN BIKE-STYLE RESULT DASHBOARD
             * ===================================================== */

            .ayument-hamd-result-dashboard {
                margin: 18px 0;
            }

            .ayument-hamd-result-main {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 22px;
                padding: 28px;
                box-shadow: 0 12px 35px rgba(15,23,42,.07);
            }

            .ayument-hamd-result-topbar {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 18px;
                margin-bottom: 20px;
            }

            .ayument-hamd-result-heading {
                color: #12306b;
                margin: 0;
                font-size: 30px;
                line-height: 1.15;
                font-weight: 850;
            }

            .ayument-hamd-result-subheading {
                color: #64748b;
                margin: 7px 0 0;
                font-size: 15px;
            }

            .ayument-hamd-result-actions-top {
                display: flex;
                gap: 8px;
                flex-shrink: 0;
            }

            .ayument-hamd-result-actions-top .button {
                min-height: 38px;
                padding: 7px 14px !important;
                border-radius: 9px !important;
            }

            .ayument-hamd-result-grid {
                display: grid;
                grid-template-columns: 220px minmax(420px, 1fr) 270px;
                gap: 22px;
                align-items: stretch;
            }

            .ayument-hamd-score-card,
            .ayument-hamd-meaning-card {
                border: 1px solid #dbe5f2;
                border-radius: 17px;
                background: linear-gradient(145deg,#fbfdff,#f5f8fd);
                padding: 22px;
                min-height: 300px;
            }

            .ayument-hamd-score-card {
                display: flex;
                flex-direction: column;
                justify-content: center;
                text-align: center;
            }

            .ayument-hamd-score-card-label {
                color: #12306b;
                font-size: 17px;
                font-weight: 800;
                margin-bottom: 12px;
            }

            .ayument-hamd-score-number {
                color: #12306b;
                font-size: 64px;
                line-height: .95;
                font-weight: 900;
                letter-spacing: -2px;
            }

            .ayument-hamd-score-outof {
                color: #64748b;
                font-size: 15px;
                margin-top: 5px;
            }

            .ayument-hamd-severity-large {
                display: inline-block;
                margin: 16px auto 10px;
                padding: 8px 22px;
                border-radius: 999px;
                background: #fff1f0;
                color: #dc2626;
                font-size: 18px;
                font-weight: 850;
            }

            .ayument-hamd-score-copy {
                color: #475569;
                font-size: 13px;
                line-height: 1.6;
                margin: 4px 0 0;
            }

            .ayument-hamd-meaning-card h3 {
                margin: 0 0 18px;
                color: #12306b;
                font-size: 18px;
            }

            .ayument-hamd-meaning-row {
                display: flex;
                gap: 11px;
                margin: 0 0 17px;
                color: #475569;
                line-height: 1.5;
                font-size: 13px;
            }

            .ayument-hamd-meaning-icon {
                width: 30px;
                height: 30px;
                flex: 0 0 30px;
                display: grid;
                place-items: center;
                border-radius: 9px;
                background: #edf4ff;
                color: #2563eb;
                font-weight: 900;
            }

            /* Bike / motorcycle dashboard gauge */
            .ayument-hamd-gauge-wrap {
                min-width: 0;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
            }

            .ayument-hamd-gauge {
                position: relative;
                width: min(100%, 560px);
                height: 350px;
                overflow: visible;
                padding-bottom: 28px;
                box-sizing: border-box;
            }

            .ayument-hamd-gauge-arc {
                position: absolute;
                width: 470px;
                height: 235px;
                left: 50%;
                top: 22px;
                transform: translateX(-50%);
                border-radius: 470px 470px 0 0;
                background:
                    radial-gradient(circle at 50% 100%, #111827 0 48%, transparent 49%),
                    conic-gradient(
                        from 270deg at 50% 100%,
                        #22c55e 0deg 27deg,
                        #84cc16 27deg 49deg,
                        #facc15 49deg 68deg,
                        #f59e0b 68deg 78deg,
                        #ef4444 78deg 180deg,
                        transparent 180deg
                    );
                box-shadow:
                    0 0 0 7px #cbd5e1,
                    0 0 0 10px #64748b,
                    0 13px 30px rgba(15,23,42,.18);
            }

            .ayument-hamd-gauge-arc::before {
                content: "";
                position: absolute;
                inset: 9px;
                border-radius: inherit;
                background:
                    repeating-conic-gradient(
                        from 270deg at 50% 100%,
                        rgba(255,255,255,.42) 0deg 1deg,
                        transparent 1deg 6deg
                    );
                -webkit-mask: linear-gradient(to bottom, #000 0 50%, transparent 50%);
                mask: linear-gradient(to bottom, #000 0 50%, transparent 50%);
                opacity: .75;
            }

            .ayument-hamd-gauge-arc::after {
                content: "";
                position: absolute;
                width: 382px;
                height: 191px;
                left: 44px;
                top: 44px;
                border-radius: 382px 382px 0 0;
                background:
                    radial-gradient(circle at 50% 100%, #17212e 0%, #0b111a 58%, #060a10 100%);
                box-shadow:
                    inset 0 8px 18px rgba(255,255,255,.05),
                    inset 0 -15px 28px rgba(0,0,0,.45);
            }

            .ayument-hamd-gauge-needle {
                position: absolute;
                left: 50%;
                bottom: 25px;
                width: 7px;
                height: 157px;
                background: linear-gradient(to right,#f8fafc 0 20%,#ef3340 21% 80%,#7f1d1d 81%);
                border-radius: 10px;
                transform-origin: 50% 100%;
                transform: translateX(-50%) rotate(-90deg);
                transition: transform 1s cubic-bezier(.18,.8,.2,1);
                z-index: 5;
                filter: drop-shadow(0 2px 3px rgba(0,0,0,.45));
            }

            .ayument-hamd-gauge-needle::before {
                content: "";
                position: absolute;
                left: 50%;
                top: -10px;
                width: 12px;
                height: 22px;
                transform: translateX(-50%);
                background: #f43f4f;
                clip-path: polygon(50% 0,100% 100%,50% 82%,0 100%);
            }

            .ayument-hamd-gauge-needle::after {
                content: "";
                position: absolute;
                width: 34px;
                height: 34px;
                border-radius: 50%;
                left: 50%;
                bottom: -17px;
                transform: translateX(-50%);
                background: radial-gradient(circle at 35% 30%,#64748b,#17212e 45%,#020617 70%);
                border: 3px solid #94a3b8;
                box-shadow: 0 2px 9px rgba(0,0,0,.5);
            }

            .ayument-hamd-gauge-score {
                position: absolute;
                left: 50%;
                top: 163px;
                transform: translateX(-50%);
                z-index: 6;
                text-align: center;
                color: #fff;
                min-width: 130px;
            }

            .ayument-hamd-gauge-score strong {
                display: block;
                color: #fff;
                font-size: 48px;
                line-height: .95;
                font-weight: 900;
                letter-spacing: -1px;
                text-shadow: 0 2px 8px rgba(0,0,0,.45);
            }

            .ayument-hamd-gauge-score span {
                display: block;
                color: #cbd5e1;
                font-size: 12px;
                margin-top: 7px;
            }

            .ayument-hamd-gauge-scale {
                position: absolute;
                left: 50%;
                top: 78px;
                transform: translateX(-50%);
                width: 430px;
                max-width: 88%;
                display: flex;
                justify-content: space-between;
                color: #e2e8f0;
                font-size: 11px;
                font-weight: 800;
                z-index: 6;
            }

            .ayument-hamd-gauge-labels {
                position: absolute;
                left: 50%;
                top: 276px;
                transform: translateX(-50%);
                width: 470px;
                max-width: 94%;
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                color: #334155;
                font-size: 10px;
                line-height: 1.2;
                font-weight: 850;
                z-index: 8;
                gap: 6px;
                padding-top: 8px;
                border-top: 1px solid #cbd5e1;
            }

            .ayument-hamd-gauge-labels span {
                white-space: nowrap;
                text-align: center;
                background: #fff;
                padding: 0 3px;
            }

            .ayument-hamd-gauge-marker {
                text-align: center;
                color: #12306b;
                font-weight: 800;
                margin-top: 4px;
                padding-top: 0;
                font-size: 14px;
                letter-spacing: .1px;
            }

            @media (max-width: 1100px) {
                .ayument-hamd-gauge {
                    width: min(100%, 540px);
                }

                .ayument-hamd-gauge-labels {
                    width: 450px;
                }
            }

            @media (max-width: 700px) {
                .ayument-hamd-gauge {
                    width: 100%;
                    height: 325px;
                    transform: scale(.94);
                    transform-origin: top center;
                    margin-bottom: -10px;
                }

                .ayument-hamd-gauge-labels {
                    width: 430px;
                    font-size: 9px;
                }
            }

            /* =====================================================
             * RESULT INSIGHTS / CLINICAL PROFILE
             * ===================================================== */

            .ayument-hamd-insight-grid {
                display: grid;
                grid-template-columns: repeat(4,minmax(0,1fr));
                gap: 12px;
                margin-top: 18px;
            }

            .ayument-hamd-insight-card {
                border: 1px solid #e2e8f0;
                border-radius: 15px;
                background: #fff;
                padding: 17px;
                min-height: 112px;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }

            .ayument-hamd-insight-label {
                color: #64748b;
                font-size: 11px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: .5px;
            }

            .ayument-hamd-insight-card strong {
                color: #12306b;
                font-size: 28px;
                line-height: 1.1;
                margin: 8px 0 5px;
            }

            .ayument-hamd-insight-card strong small {
                font-size: 14px;
                font-weight: 700;
                color: #64748b;
            }

            .ayument-hamd-insight-note {
                color: #64748b;
                font-size: 11px;
                line-height: 1.4;
            }

            .ayument-hamd-domain-card,
            .ayument-hamd-items-card,
            .ayument-hamd-symptom-list-card,
            .ayument-hamd-interpretation-card {
                margin-top: 18px;
            }

            .ayument-hamd-section-heading {
                display: flex;
                align-items: flex-end;
                justify-content: space-between;
                gap: 15px;
                margin-bottom: 16px;
            }

            .ayument-hamd-section-heading h2 {
                margin: 3px 0 0;
                color: #12306b;
                font-size: 19px;
            }

            .ayument-hamd-section-kicker {
                color: #2563eb;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: 1.1px;
            }

            .ayument-hamd-section-note {
                color: #94a3b8;
                font-size: 11px;
            }

            .ayument-hamd-domain-grid {
                display: grid;
                grid-template-columns: repeat(5,minmax(0,1fr));
                gap: 12px;
            }

            .ayument-hamd-domain-item {
                border: 1px solid #e2e8f0;
                border-radius: 13px;
                padding: 14px;
                background: #fbfdff;
            }

            .ayument-hamd-domain-top,
            .ayument-hamd-domain-bottom {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 8px;
            }

            .ayument-hamd-domain-top strong {
                color: #1e3a8a;
                font-size: 12px;
            }

            .ayument-hamd-domain-top span {
                color: #475569;
                font-size: 11px;
                font-weight: 800;
                white-space: nowrap;
            }

            .ayument-hamd-domain-track,
            .ayument-hamd-item-bar {
                height: 7px;
                border-radius: 99px;
                background: #e8eef6;
                overflow: hidden;
            }

            .ayument-hamd-domain-track {
                margin: 12px 0 8px;
            }

            .ayument-hamd-domain-track span,
            .ayument-hamd-item-bar span {
                display: block;
                height: 100%;
                border-radius: inherit;
                background: linear-gradient(90deg,#2563eb,#ef4444);
            }

            .ayument-hamd-domain-bottom span,
            .ayument-hamd-domain-bottom b {
                font-size: 9px;
            }

            .ayument-hamd-domain-bottom span {
                color: #94a3b8;
            }

            .ayument-hamd-domain-bottom b {
                color: #475569;
            }

            .ayument-hamd-item-list {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .ayument-hamd-item-row {
                display: grid;
                grid-template-columns: 32px minmax(180px,1.3fr) minmax(140px,1fr) 55px;
                align-items: center;
                gap: 12px;
                padding: 11px 12px;
                border: 1px solid #edf1f6;
                border-radius: 11px;
                background: #fff;
            }

            .ayument-hamd-item-number {
                width: 29px;
                height: 29px;
                border-radius: 9px;
                display: grid;
                place-items: center;
                background: #eff6ff;
                color: #2563eb;
                font-weight: 900;
                font-size: 11px;
            }

            .ayument-hamd-item-name {
                min-width: 0;
            }

            .ayument-hamd-item-name strong {
                display: block;
                color: #334155;
                font-size: 12px;
            }

            .ayument-hamd-item-name span {
                display: block;
                margin-top: 2px;
                color: #94a3b8;
                font-size: 9px;
                font-weight: 700;
            }

            .ayument-hamd-item-score {
                color: #12306b;
                font-size: 15px;
                font-weight: 900;
                text-align: right;
            }

            .ayument-hamd-item-score small {
                color: #94a3b8;
                font-size: 10px;
                margin-left: 2px;
            }

            .ayument-hamd-interpretation-content {
                display: flex;
                gap: 14px;
                align-items: flex-start;
                padding: 16px;
                border-radius: 13px;
                background: #f8fbff;
                border: 1px solid #e1ecfb;
                color: #475569;
                font-size: 13px;
                line-height: 1.65;
            }

            .ayument-hamd-interpretation-content p {
                margin: 0 0 9px;
            }

            .ayument-hamd-interpretation-content p:last-child {
                margin-bottom: 0;
            }

            .ayument-hamd-interpretation-icon {
                flex: 0 0 32px;
                width: 32px;
                height: 32px;
                display: grid;
                place-items: center;
                border-radius: 50%;
                background: #2563eb;
                color: #fff;
                font-weight: 900;
            }

            .ayument-hamd-report-actions {
                display: flex;
                justify-content: flex-end;
                gap: 9px;
                margin-top: 18px;
            }

            .ayument-hamd-summary-grid {
                display: grid;
                grid-template-columns: repeat(4,minmax(0,1fr));
                gap: 12px;
                margin-top: 18px;
            }

            .ayument-hamd-summary-item {
                position: relative;
                padding: 17px 17px 17px 58px;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                background: #fff;
                min-height: 58px;
            }

            .ayument-hamd-summary-item::before {
                content: "✓";
                position: absolute;
                left: 16px;
                top: 17px;
                width: 30px;
                height: 30px;
                display: grid;
                place-items: center;
                border-radius: 9px;
                background: #eff6ff;
                color: #2563eb;
                font-weight: 900;
            }

            .ayument-hamd-summary-item strong {
                display: block;
                color: #12306b;
                margin-bottom: 4px;
                font-size: 13px;
            }

            .ayument-hamd-summary-item span {
                color: #475569;
                font-size: 16px;
                font-weight: 800;
            }

            .ayument-hamd-result-lower {
                display: grid;
                grid-template-columns: minmax(0,1fr) minmax(0,1fr);
                gap: 18px;
                margin-top: 18px;
            }

            .ayument-hamd-lower-card {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 20px;
            }

            .ayument-hamd-lower-card h2 {
                color: #12306b;
                font-size: 17px;
                margin: 0 0 14px;
            }

            .ayument-hamd-symptom-pills {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            .ayument-hamd-symptom-pill {
                display: inline-block;
                padding: 7px 11px;
                border-radius: 999px;
                background: #fff0ef;
                color: #b42318;
                font-size: 12px;
                font-weight: 750;
            }

            .ayument-hamd-meaning {
                display: none;
            }

            .ayument-hamd-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 20px;
            }

            .ayument-hamd-actions .button {
                min-height: 40px;
                padding: 8px 16px !important;
                border-radius: 9px !important;
            }

            @media (max-width: 1050px) {
                .ayument-hamd-insight-grid {
                    grid-template-columns: repeat(2,minmax(0,1fr));
                }

                .ayument-hamd-domain-grid {
                    grid-template-columns: repeat(3,minmax(0,1fr));
                }

                .ayument-hamd-result-grid {
                    grid-template-columns: 190px minmax(380px,1fr);
                }

                .ayument-hamd-meaning-card {
                    grid-column: 1 / -1;
                    min-height: auto;
                }

                .ayument-hamd-summary-grid {
                    grid-template-columns: repeat(2,minmax(0,1fr));
                }
            }

            @media (max-width: 700px) {
                .ayument-hamd-result-main {
                    padding: 16px;
                }

                .ayument-hamd-result-topbar {
                    display: block;
                }

                .ayument-hamd-result-heading {
                    font-size: 23px;
                }

                .ayument-hamd-result-actions-top {
                    margin-top: 12px;
                }

                .ayument-hamd-result-grid {
                    display: flex;
                    flex-direction: column;
                }

                .ayument-hamd-score-card {
                    min-height: auto;
                }

                .ayument-hamd-gauge {
                    height: 270px;
                }

                .ayument-hamd-gauge-arc {
                    width: 350px;
                    height: 175px;
                    top: 25px;
                }

                .ayument-hamd-gauge-arc::after {
                    width: 286px;
                    height: 143px;
                    left: 32px;
                    top: 32px;
                }

                .ayument-hamd-gauge-needle {
                    bottom: 27px;
                    height: 115px;
                }

                .ayument-hamd-gauge-score {
                    top: 132px;
                }

                .ayument-hamd-gauge-scale {
                    top: 62px;
                    width: 315px;
                }

                .ayument-hamd-gauge-labels {
                    top: 214px;
                    width: 350px;
                    font-size: 8px;
                }

                .ayument-hamd-summary-grid,
                .ayument-hamd-result-lower {
                    grid-template-columns: 1fr;
                }

                .ayument-hamd-meaning-card {
                    min-height: auto;
                }
            }

            @media (max-width: 700px) {
                .ayument-hamd-insight-grid,
                .ayument-hamd-domain-grid {
                    grid-template-columns: 1fr;
                }

                .ayument-hamd-section-heading {
                    display: block;
                }

                .ayument-hamd-section-note {
                    display: block;
                    margin-top: 5px;
                }

                .ayument-hamd-item-row {
                    grid-template-columns: 30px minmax(0,1fr) 52px;
                }

                .ayument-hamd-item-bar {
                    grid-column: 2 / 4;
                    grid-row: 2;
                }

                .ayument-hamd-item-score {
                    grid-column: 3;
                    grid-row: 1;
                }

                .ayument-hamd-report-actions {
                    justify-content: stretch;
                    flex-direction: column;
                }

                .ayument-hamd-report-actions .button {
                    width: 100%;
                    text-align: center;
                }
            }

            @media print {

                /* =========================================================
                 * CLEAN A4 PRINT / PDF REPORT
                 * These rules affect Print / Save as PDF only.
                 * The normal browser result screen remains unchanged.
                 * ========================================================= */

                @page {
                    size: A4 portrait;
                    margin: 11mm;
                }

                html,
                body {
                    background: #fff !important;
                }

                body {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }

                #adminmenumain,
                #wpadminbar,
                #adminmenuback,
                .ayument-hamd-no-print,
                .notice {
                    display: none !important;
                }

                #wpcontent,
                #wpbody-content {
                    margin-left: 0 !important;
                    padding-left: 0 !important;
                    padding-right: 0 !important;
                }

                .ayument-hamd-wrap {
                    width: 100% !important;
                    max-width: none !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }

                .ayument-hamd-result-dashboard {
                    margin: 0 !important;
                }

                .ayument-hamd-result-main {
                    border: 0 !important;
                    border-radius: 0 !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    box-shadow: none !important;
                    background: #fff !important;
                }

                /* ================= PAGE 1 ================= */

                .ayument-hamd-result-topbar {
                    margin-bottom: 10px !important;
                }

                .ayument-hamd-result-heading {
                    font-size: 23px !important;
                    color: #12306b !important;
                }

                .ayument-hamd-result-subheading {
                    font-size: 11px !important;
                    margin-top: 4px !important;
                }

                .ayument-hamd-result-grid {
                    grid-template-columns: 145px minmax(0, 1fr) 175px !important;
                    gap: 9px !important;
                    align-items: center !important;
                }

                .ayument-hamd-score-card,
                .ayument-hamd-meaning-card {
                    min-height: 235px !important;
                    padding: 14px !important;
                    border-radius: 12px !important;
                    box-shadow: none !important;
                }

                .ayument-hamd-score-card-label {
                    font-size: 13px !important;
                    margin-bottom: 8px !important;
                }

                .ayument-hamd-score-number {
                    font-size: 48px !important;
                }

                .ayument-hamd-score-outof {
                    font-size: 11px !important;
                }

                .ayument-hamd-severity-large {
                    margin: 11px auto 7px !important;
                    padding: 6px 12px !important;
                    font-size: 13px !important;
                }

                .ayument-hamd-score-copy {
                    font-size: 10px !important;
                    line-height: 1.4 !important;
                }

                .ayument-hamd-meaning-card h3 {
                    font-size: 13px !important;
                    margin-bottom: 11px !important;
                }

                .ayument-hamd-meaning-row {
                    gap: 7px !important;
                    margin-bottom: 10px !important;
                    font-size: 10px !important;
                    line-height: 1.35 !important;
                }

                .ayument-hamd-meaning-icon {
                    width: 22px !important;
                    height: 22px !important;
                    flex-basis: 22px !important;
                    border-radius: 6px !important;
                    font-size: 10px !important;
                }

                /* Compact bike-style gauge for A4. */
                .ayument-hamd-gauge-wrap {
                    min-width: 0 !important;
                }

                .ayument-hamd-gauge {
                    width: 100% !important;
                    max-width: 410px !important;
                    height: 265px !important;
                    padding-bottom: 18px !important;
                }

                .ayument-hamd-gauge-arc {
                    width: 350px !important;
                    height: 175px !important;
                    top: 15px !important;
                }

                .ayument-hamd-gauge-arc::after {
                    width: 284px !important;
                    height: 142px !important;
                    left: 33px !important;
                    top: 33px !important;
                }

                .ayument-hamd-gauge-needle {
                    height: 116px !important;
                    bottom: 18px !important;
                    width: 5px !important;
                }

                .ayument-hamd-gauge-needle::before {
                    width: 10px !important;
                    height: 18px !important;
                    top: -8px !important;
                }

                .ayument-hamd-gauge-needle::after {
                    width: 28px !important;
                    height: 28px !important;
                    bottom: -14px !important;
                }

                .ayument-hamd-gauge-score {
                    top: 120px !important;
                    min-width: 100px !important;
                }

                .ayument-hamd-gauge-score strong {
                    font-size: 36px !important;
                }

                .ayument-hamd-gauge-score span {
                    font-size: 9px !important;
                    margin-top: 4px !important;
                }

                .ayument-hamd-gauge-scale {
                    top: 55px !important;
                    width: 315px !important;
                    font-size: 8px !important;
                }

                .ayument-hamd-gauge-labels {
                    top: 206px !important;
                    width: 350px !important;
                    max-width: 95% !important;
                    font-size: 7px !important;
                    padding-top: 5px !important;
                }

                .ayument-hamd-gauge-marker {
                    margin-top: 0 !important;
                    font-size: 10px !important;
                }

                .ayument-hamd-summary-grid {
                    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
                    gap: 7px !important;
                    margin-top: 9px !important;
                }

                .ayument-hamd-summary-item {
                    min-height: 43px !important;
                    padding: 9px 9px 9px 38px !important;
                    border-radius: 9px !important;
                }

                .ayument-hamd-summary-item::before {
                    left: 9px !important;
                    top: 11px !important;
                    width: 21px !important;
                    height: 21px !important;
                    border-radius: 6px !important;
                    font-size: 10px !important;
                }

                .ayument-hamd-summary-item strong {
                    font-size: 9px !important;
                    margin-bottom: 2px !important;
                }

                .ayument-hamd-summary-item span {
                    font-size: 11px !important;
                }

                .ayument-hamd-insight-grid {
                    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
                    gap: 7px !important;
                    margin-top: 9px !important;
                }

                .ayument-hamd-insight-card {
                    min-height: 65px !important;
                    padding: 9px !important;
                    border-radius: 9px !important;
                }

                .ayument-hamd-insight-label {
                    font-size: 7px !important;
                    letter-spacing: .3px !important;
                }

                .ayument-hamd-insight-card strong {
                    font-size: 18px !important;
                    margin: 4px 0 2px !important;
                }

                .ayument-hamd-insight-card strong small {
                    font-size: 9px !important;
                }

                .ayument-hamd-insight-note {
                    font-size: 7px !important;
                }

                /* Start the detailed symptom profile on a fresh page. */
                .ayument-hamd-domain-card {
                    break-before: page !important;
                    page-break-before: always !important;
                }

                /* ================= PAGE 2 ================= */

                .ayument-hamd-lower-card {
                    box-shadow: none !important;
                    break-inside: auto !important;
                    page-break-inside: auto !important;
                    border-radius: 10px !important;
                    padding: 12px !important;
                    margin-top: 10px !important;
                }

                .ayument-hamd-section-heading {
                    margin-bottom: 9px !important;
                }

                .ayument-hamd-section-heading h2 {
                    font-size: 14px !important;
                }

                .ayument-hamd-section-kicker {
                    font-size: 7px !important;
                    letter-spacing: .8px !important;
                }

                .ayument-hamd-section-note {
                    font-size: 8px !important;
                }

                .ayument-hamd-domain-grid {
                    grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
                    gap: 7px !important;
                }

                .ayument-hamd-domain-item {
                    padding: 8px !important;
                    border-radius: 8px !important;
                    box-shadow: none !important;
                    break-inside: avoid !important;
                    page-break-inside: avoid !important;
                }

                .ayument-hamd-domain-top strong {
                    font-size: 8px !important;
                }

                .ayument-hamd-domain-top span {
                    font-size: 8px !important;
                }

                .ayument-hamd-domain-track {
                    height: 5px !important;
                    margin: 7px 0 5px !important;
                }

                .ayument-hamd-domain-bottom span,
                .ayument-hamd-domain-bottom b {
                    font-size: 6.5px !important;
                }

                .ayument-hamd-items-card {
                    margin-top: 10px !important;
                }

                .ayument-hamd-item-list {
                    gap: 4px !important;
                }

                .ayument-hamd-item-row {
                    grid-template-columns: 24px minmax(150px, 1.3fr) minmax(120px, 1fr) 42px !important;
                    gap: 7px !important;
                    padding: 5px 7px !important;
                    min-height: 25px !important;
                    border-radius: 7px !important;
                    box-shadow: none !important;
                    break-inside: avoid !important;
                    page-break-inside: avoid !important;
                }

                .ayument-hamd-item-number {
                    width: 21px !important;
                    height: 21px !important;
                    border-radius: 6px !important;
                    font-size: 8px !important;
                }

                .ayument-hamd-item-name strong {
                    font-size: 8px !important;
                }

                .ayument-hamd-item-name span {
                    font-size: 6.5px !important;
                    margin-top: 1px !important;
                }

                .ayument-hamd-item-bar {
                    height: 5px !important;
                }

                .ayument-hamd-item-score {
                    font-size: 10px !important;
                }

                .ayument-hamd-item-score small {
                    font-size: 7px !important;
                }

                /* If symptom pills exist, they start page 3.
                 * If not, the interpretation card starts page 3. */
                .ayument-hamd-items-card + .ayument-hamd-symptom-list-card,
                .ayument-hamd-items-card + .ayument-hamd-interpretation-card {
                    break-before: page !important;
                    page-break-before: always !important;
                }

                /* ================= PAGE 3 ================= */

                .ayument-hamd-symptom-list-card,
                .ayument-hamd-interpretation-card {
                    break-inside: avoid !important;
                    page-break-inside: avoid !important;
                }

                .ayument-hamd-symptom-pills {
                    gap: 5px !important;
                }

                .ayument-hamd-symptom-pill {
                    padding: 5px 8px !important;
                    font-size: 8px !important;
                }

                .ayument-hamd-interpretation-content {
                    gap: 9px !important;
                    padding: 10px !important;
                    border-radius: 9px !important;
                    font-size: 9px !important;
                    line-height: 1.45 !important;
                }

                .ayument-hamd-interpretation-content p {
                    margin-bottom: 6px !important;
                }

                .ayument-hamd-interpretation-icon {
                    flex-basis: 24px !important;
                    width: 24px !important;
                    height: 24px !important;
                    font-size: 10px !important;
                }

                .ayument-hamd-alert {
                    margin: 10px 0 !important;
                    padding: 11px !important;
                    border-width: 1px !important;
                    border-radius: 9px !important;
                    font-size: 9px !important;
                    line-height: 1.4 !important;
                    break-inside: avoid !important;
                    page-break-inside: avoid !important;
                }

                .ayument-hamd-alert p {
                    margin: 5px 0 !important;
                }

                .ayument-hamd-disclaimer {
                    margin-top: 10px !important;
                    padding: 10px !important;
                    border-left-width: 3px !important;
                    font-size: 8px !important;
                    line-height: 1.4 !important;
                    break-inside: avoid !important;
                    page-break-inside: avoid !important;
                }

                /* General print hygiene. */
                .ayument-hamd-card,
                .ayument-hamd-result,
                .ayument-hamd-result-main,
                .ayument-hamd-lower-card,
                .ayument-hamd-insight-card,
                .ayument-hamd-domain-item,
                .ayument-hamd-item-row {
                    box-shadow: none !important;
                }

                h1, h2, h3 {
                    page-break-after: avoid;
                }

                a {
                    color: inherit !important;
                    text-decoration: none !important;
                }
            }

        </style>

        <script>
        (function () {
            function closeAllHelp(except) {
                document.querySelectorAll('.ayument-hamd-help.is-open').forEach(function (wrap) {
                    if (wrap !== except) {
                        wrap.classList.remove('is-open');
                        var btn = wrap.querySelector('.ayument-hamd-help-button');
                        if (btn) {
                            btn.setAttribute('aria-expanded', 'false');
                        }
                    }
                });
            }

            document.querySelectorAll('.ayument-hamd-help-button').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    var wrap = button.closest('.ayument-hamd-help');
                    var isOpen = wrap.classList.contains('is-open');

                    closeAllHelp(wrap);

                    wrap.classList.toggle('is-open', !isOpen);
                    button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                });
            });

            document.addEventListener('click', function (event) {
                if (!event.target.closest('.ayument-hamd-help')) {
                    closeAllHelp(null);
                }
            });
        })();
        </script>

        <script>
        (function () {
            var needle = document.querySelector('.ayument-hamd-gauge-needle');

            if (needle) {
                var finalTransform = needle.style.transform;
                needle.style.transform = 'translateX(-50%) rotate(-90deg)';

                window.requestAnimationFrame(function () {
                    window.requestAnimationFrame(function () {
                        needle.style.transform = finalTransform;
                    });
                });
            }
        })();
        </script>



        <?php if ($submitted): ?>

            <?php
            $severity = ayument_hamd_severity($total);
            ?>

            <?php
            $gauge_percent = ayument_hamd_gauge_percent($total);

            /*
             * The gauge spans a 180° semicircle.
             * Convert 0–100% into -90° to +90° for the needle.
             */
            $needle_angle = -90 + (180 * ($gauge_percent / 100));

            $high_items = array();
            $active_item_count = 0;
            $highest_item_score = 0;
            $highest_item_title = '';

            foreach ($items as $number => $item) {
                if (isset($scores[$number]) && $scores[$number] > 0) {
                    $high_items[] = $item['title'];
                    $active_item_count++;
                }

                if (isset($scores[$number]) && $scores[$number] > $highest_item_score) {
                    $highest_item_score = intval($scores[$number]);
                    $highest_item_title = $item['title'];
                }
            }

            $domains = ayument_hamd_domains();
            $domain_results = array();

            foreach ($domains as $domain_name => $domain_items) {
                $domain_total = 0;
                $domain_max = 0;

                foreach ($domain_items as $domain_item_number) {
                    if (isset($items[$domain_item_number])) {
                        $domain_total += isset($scores[$domain_item_number]) ? intval($scores[$domain_item_number]) : 0;
                        $domain_max += intval($items[$domain_item_number]['max']);
                    }
                }

                $domain_results[$domain_name] = array(
                    'score' => $domain_total,
                    'max' => $domain_max,
                    'percent' => $domain_max > 0 ? round(($domain_total / $domain_max) * 100) : 0,
                );
            }

            $meaning_text = 'This score reflects the depressive symptoms rated during this assessment. It should be interpreted in the context of the clinical interview and the person’s overall clinical picture.';
            ?>

            <div class="ayument-hamd-result-dashboard">

                <div class="ayument-hamd-result-main">

                    <div class="ayument-hamd-result-topbar">
                        <div>
                            <h1 class="ayument-hamd-result-heading">HAM-D17 Assessment Result</h1>
                            <p class="ayument-hamd-result-subheading">
                                Hamilton Depression Rating Scale · 17 items · Past week
                            </p>
                        </div>

                        <div class="ayument-hamd-result-actions-top ayument-hamd-no-print">
                            <button type="button" class="button" onclick="window.print();">
                                🖨 Print / Save
                            </button>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=ayument-hamd')); ?>"
                               class="button button-primary">
                                ↻ Retake Test
                            </a>
                        </div>
                    </div>

                    <div class="ayument-hamd-result-grid">

                        <div class="ayument-hamd-score-card">
                            <div class="ayument-hamd-score-card-label">Your Score</div>
                            <div class="ayument-hamd-score-number"><?php echo esc_html($total); ?></div>
                            <div class="ayument-hamd-score-outof">out of 52</div>

                            <span class="ayument-hamd-severity-large">
                                <?php echo esc_html($severity['label']); ?>
                            </span>

                            <p class="ayument-hamd-score-copy">
                                <?php echo esc_html($severity['description']); ?>
                            </p>
                        </div>

                        <div class="ayument-hamd-gauge-wrap">

                            <div class="ayument-hamd-gauge"
                                 role="img"
                                 aria-label="<?php echo esc_attr('HAM-D17 score ' . $total . ' out of 52, ' . $severity['label']); ?>">

                                <div class="ayument-hamd-gauge-arc"></div>

                                <div class="ayument-hamd-gauge-scale" aria-hidden="true">
                                    <span>0</span>
                                    <span>7</span>
                                    <span>13</span>
                                    <span>18</span>
                                    <span>22</span>
                                    <span>52</span>
                                </div>

                                <div
                                    class="ayument-hamd-gauge-needle"
                                    style="transform: translateX(-50%) rotate(<?php echo esc_attr($needle_angle); ?>deg);"
                                ></div>

                                <div class="ayument-hamd-gauge-score">
                                    <strong><?php echo esc_html($total); ?></strong>
                                    <span>out of 52</span>
                                </div>

                                <div class="ayument-hamd-gauge-labels">
                                    <span>MINIMAL</span>
                                    <span>MILD</span>
                                    <span>MODERATE</span>
                                    <span>SEVERE</span>
                                    <span>VERY SEVERE</span>
                                </div>

                            </div>

                            <div class="ayument-hamd-gauge-marker">HAM-D symptom severity</div>

                        </div>

                        <div class="ayument-hamd-meaning-card">

                            <h3>What does this mean? ⓘ</h3>

                            <div class="ayument-hamd-meaning-row">
                                <span class="ayument-hamd-meaning-icon">✦</span>
                                <span>
                                    This score reflects the depressive symptoms rated during this assessment.
                                </span>
                            </div>

                            <div class="ayument-hamd-meaning-row">
                                <span class="ayument-hamd-meaning-icon">✓</span>
                                <span>
                                    The current score falls within the
                                    <strong><?php echo esc_html($severity['label']); ?></strong>
                                    range used by this AyuMent implementation.
                                </span>
                            </div>

                            <div class="ayument-hamd-meaning-row">
                                <span class="ayument-hamd-meaning-icon">♙</span>
                                <span>
                                    This is a clinician-assisted assessment and is not a standalone psychiatric diagnosis.
                                </span>
                            </div>

                        </div>

                    </div>

                    <div class="ayument-hamd-summary-grid">

                        <div class="ayument-hamd-summary-item">
                            <strong>Total Score</strong>
                            <span><?php echo esc_html($total); ?> / 52</span>
                        </div>

                        <div class="ayument-hamd-summary-item">
                            <strong>Maximum Score</strong>
                            <span>52</span>
                        </div>

                        <div class="ayument-hamd-summary-item">
                            <strong>Percentage</strong>
                            <span><?php echo esc_html(number_format(($total / 52) * 100, 1)); ?>%</span>
                        </div>

                        <div class="ayument-hamd-summary-item">
                            <strong>Severity Level</strong>
                            <span><?php echo esc_html($severity['label']); ?></span>
                        </div>

                    </div>

                    <div class="ayument-hamd-insight-grid">

                        <div class="ayument-hamd-insight-card">
                            <span class="ayument-hamd-insight-label">Items with symptoms</span>
                            <strong><?php echo esc_html($active_item_count); ?><small> / 17</small></strong>
                            <span class="ayument-hamd-insight-note">Items receiving a score above zero</span>
                        </div>

                        <div class="ayument-hamd-insight-card">
                            <span class="ayument-hamd-insight-label">Score percentage</span>
                            <strong><?php echo esc_html(number_format(($total / 52) * 100, 1)); ?><small>%</small></strong>
                            <span class="ayument-hamd-insight-note">Total score compared with the maximum</span>
                        </div>

                        <div class="ayument-hamd-insight-card">
                            <span class="ayument-hamd-insight-label">Highest item score</span>
                            <strong><?php echo esc_html($highest_item_score); ?><small> / 4</small></strong>
                            <span class="ayument-hamd-insight-note"><?php echo esc_html($highest_item_title ?: 'No positive item'); ?></span>
                        </div>

                        <div class="ayument-hamd-insight-card">
                            <span class="ayument-hamd-insight-label">Assessment period</span>
                            <strong>7<small> days</small></strong>
                            <span class="ayument-hamd-insight-note">Symptoms rated for the past week</span>
                        </div>

                    </div>


                    <div class="ayument-hamd-lower-card ayument-hamd-domain-card">

                        <div class="ayument-hamd-section-heading">
                            <div>
                                <span class="ayument-hamd-section-kicker">SYMPTOM PROFILE</span>
                                <h2>Where are symptoms showing up?</h2>
                            </div>
                            <span class="ayument-hamd-section-note">Grouped from the 17 rated items</span>
                        </div>

                        <div class="ayument-hamd-domain-grid">

                            <?php foreach ($domain_results as $domain_name => $domain): ?>

                                <?php
                                $domain_level = 'Low';
                                if ($domain['percent'] >= 70) {
                                    $domain_level = 'High';
                                } elseif ($domain['percent'] >= 40) {
                                    $domain_level = 'Moderate';
                                } elseif ($domain['percent'] > 0) {
                                    $domain_level = 'Mild';
                                }
                                ?>

                                <div class="ayument-hamd-domain-item">

                                    <div class="ayument-hamd-domain-top">
                                        <strong><?php echo esc_html($domain_name); ?></strong>
                                        <span><?php echo esc_html($domain['score']); ?>/<?php echo esc_html($domain['max']); ?></span>
                                    </div>

                                    <div class="ayument-hamd-domain-track">
                                        <span style="width:<?php echo esc_attr($domain['percent']); ?>%;"></span>
                                    </div>

                                    <div class="ayument-hamd-domain-bottom">
                                        <span><?php echo esc_html($domain['percent']); ?>% of domain maximum</span>
                                        <b><?php echo esc_html($domain_level); ?></b>
                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>


                    <div class="ayument-hamd-lower-card ayument-hamd-items-card">

                        <div class="ayument-hamd-section-heading">
                            <div>
                                <span class="ayument-hamd-section-kicker">ITEM BREAKDOWN</span>
                                <h2>17-item assessment profile</h2>
                            </div>
                            <span class="ayument-hamd-section-note">Recorded score / maximum</span>
                        </div>

                        <div class="ayument-hamd-item-list">

                            <?php foreach ($items as $number => $item): ?>

                                <?php
                                $item_score = isset($scores[$number]) ? intval($scores[$number]) : 0;
                                $item_percent = intval(round(($item_score / max(1, intval($item['max']))) * 100));
                                $item_level = ayument_hamd_score_level($item_score, intval($item['max']));
                                ?>

                                <div class="ayument-hamd-item-row">

                                    <div class="ayument-hamd-item-number"><?php echo esc_html($number); ?></div>

                                    <div class="ayument-hamd-item-name">
                                        <strong><?php echo esc_html($item['title']); ?></strong>
                                        <span><?php echo esc_html($item_level); ?></span>
                                    </div>

                                    <div class="ayument-hamd-item-bar">
                                        <span style="width:<?php echo esc_attr($item_percent); ?>%;"></span>
                                    </div>

                                    <div class="ayument-hamd-item-score">
                                        <?php echo esc_html($item_score); ?><small>/<?php echo esc_html($item['max']); ?></small>
                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>


                    <?php if (!empty($high_items)): ?>

                        <div class="ayument-hamd-lower-card ayument-hamd-symptom-list-card">

                            <div class="ayument-hamd-section-heading">
                                <div>
                                    <span class="ayument-hamd-section-kicker">RECORDED SYMPTOMS</span>
                                    <h2>Areas with symptoms recorded</h2>
                                </div>
                            </div>

                            <div class="ayument-hamd-symptom-pills">
                                <?php foreach ($high_items as $high_item): ?>
                                    <span class="ayument-hamd-symptom-pill"><?php echo esc_html($high_item); ?></span>
                                <?php endforeach; ?>
                            </div>

                        </div>

                    <?php endif; ?>


                    <div class="ayument-hamd-lower-card ayument-hamd-interpretation-card">

                        <div class="ayument-hamd-section-heading">
                            <div>
                                <span class="ayument-hamd-section-kicker">CLINICAL CONTEXT</span>
                                <h2>What this result means</h2>
                            </div>
                        </div>

                        <div class="ayument-hamd-interpretation-content">

                            <div class="ayument-hamd-interpretation-icon">i</div>

                            <div>
                                <p>
                                    The recorded HAM-D17 score is
                                    <strong><?php echo esc_html($total); ?>/52</strong>
                                    and falls within the
                                    <strong><?php echo esc_html($severity['label']); ?></strong>
                                    range used by this AyuMent implementation.
                                </p>

                                <p>
                                    The result describes symptom severity captured during the
                                    assessment; it does not by itself establish a psychiatric
                                    diagnosis or determine treatment.
                                </p>

                                <p>
                                    Interpretation should be made by a qualified clinician using
                                    the interview, history, examination and other relevant information.
                                </p>
                            </div>

                        </div>

                    </div>


                    <div class="ayument-hamd-report-actions ayument-hamd-no-print">

                        <button type="button" class="button button-primary" onclick="window.print();">
                            🖨 Print / Save Report
                        </button>

                        <a href="<?php echo esc_url(admin_url('admin.php?page=ayument-hamd')); ?>" class="button">
                            ↻ Retake Assessment
                        </a>

                    </div>


                </div>


            </div>


            <?php if ($suicide_score > 0): ?>

                <div class="ayument-hamd-alert">

                    <strong>⚠ Clinical Safety Alert</strong>

                    <p>
                        The suicidality item received a score greater than zero.
                        This requires direct clinical attention and appropriate
                        safety assessment by a qualified healthcare professional.
                    </p>

                    <p>
                        If there is current suicidal intent, a plan, or immediate
                        danger, do not rely on this assessment result—seek urgent
                        professional/emergency assistance.
                    </p>

                </div>

            <?php endif; ?>


            <div class="ayument-hamd-disclaimer">

                <strong>Important:</strong>

                HAM-D17 is a clinician-rated measure of depressive symptom
                severity. This result is an assessment-support output and
                should not be used alone to establish a psychiatric diagnosis
                or determine treatment.

            </div>


        <?php else: ?>


            <div class="ayument-hamd-hero">

                <h1>🧠 HAM-D17 Assessment</h1>

                <p>
                    Hamilton Depression Rating Scale – 17 Item Version
                </p>

                <p>
                    Clinician-assisted assessment of depressive symptom severity
                </p>

            </div>


            <?php if ($error): ?>

                <div class="notice notice-error is-dismissible">

                    <p>
                        <strong>
                            <?php echo esc_html($error); ?>
                        </strong>
                    </p>

                </div>

            <?php endif; ?>


            <div class="ayument-hamd-card">

                <h2>Before You Begin</h2>

                <p>
                    Rate the patient's symptoms based on the
                    <strong>past week</strong>, using the clinical interview
                    and available relevant information.
                </p>

                <p>
                    Select the response that best represents the observed
                    clinical picture for each item.
                </p>

                <p>
                    <strong>17 items · Maximum score 52</strong>
                </p>

            </div>


            <form
                method="post"
                id="ayument-hamd-form"
            >

                <?php
                wp_nonce_field(
                    'ayument_hamd_assessment',
                    'ayument_hamd_nonce'
                );
                ?>


                <?php foreach ($items as $number => $item): ?>

                    <div class="ayument-hamd-card">

                        <div class="ayument-hamd-question">

                            <?php echo esc_html($number); ?>.
                            <?php echo esc_html($item['title']); ?>

                            <span class="ayument-hamd-help">
                                <button
                                    type="button"
                                    class="ayument-hamd-help-button"
                                    aria-label="Explain this question in simple words"
                                    aria-expanded="false"
                                    title="Explain this question in simple words"
                                >?</button>

                                <span
                                    class="ayument-hamd-help-tooltip"
                                    role="tooltip"
                                >
                                    <?php echo esc_html($item['help']); ?>
                                </span>
                            </span>

                            <small>
                                Maximum score: <?php echo esc_html($item['max']); ?>
                            </small>

                        </div>


                        <?php foreach ($item['options'] as $score => $text): ?>

                            <label class="ayument-hamd-option">

                                <input
                                    type="radio"
                                    name="hamd_<?php echo esc_attr($number); ?>"
                                    value="<?php echo esc_attr($score); ?>"
                                    required
                                >

                                <strong>
                                    <?php echo esc_html($score); ?>
                                </strong>

                                —
                                <?php echo esc_html($text); ?>

                            </label>

                        <?php endforeach; ?>

                    </div>

                <?php endforeach; ?>


                <div class="ayument-hamd-card">

                    <button
                        type="submit"
                        name="ayument_hamd_submit"
                        class="button ayument-hamd-submit"
                    >
                        Calculate HAM-D17 Score
                    </button>

                </div>


            </form>


            <div class="ayument-hamd-disclaimer">

                <strong>Important:</strong>

                HAM-D17 is a clinician-rated depression severity instrument.
                This AyuMent implementation is intended for educational and
                clinical-support purposes and does not itself diagnose
                depression or replace professional psychiatric assessment.

            </div>


        <?php endif; ?>

    </div>

    <?php
}