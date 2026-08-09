<?php
/**
 * AyuMent - HAM-A Assessment
 *
 * Hamilton Anxiety Rating Scale (HAM-A), 14-item clinician-rated assessment.
 * Questions, item descriptions and scoring are based on the supplied HAM-A source.
 */

if (!defined('ABSPATH')) {
    exit;
}

/* =========================================================
 * ADMIN MENU
 * ========================================================= */
add_action('admin_menu', 'ayument_hama_add_menu');

function ayument_hama_add_menu() {
    add_submenu_page(
        'ayument',
        'HAM-A Assessment',
        'HAM-A Assessment',
        'manage_options',
        'ayument-hama',
        'ayument_hama_page'
    );
}

/* =========================================================
 * HAM-A 14 ITEMS
 * Source: supplied Hamilton Anxiety Rating Scale PDF.
 * Each item is scored 0–4.
 * ========================================================= */
function ayument_hama_items() {
    return array(
        1 => array(
            'title' => 'Anxious mood',
            'help'  => 'In simple words: How much has the person been feeling worried, fearful, tense about what may happen, or unusually irritable?',
            'description' => 'Worries, anticipation of the worst, fearful anticipation, irritability.',
            'options' => array(
                0 => 'Not present',
                1 => 'Mild',
                2 => 'Moderate',
                3 => 'Severe',
                4 => 'Very severe'
            ),
            'domain' => 'Psychic anxiety'
        ),
        2 => array(
            'title' => 'Tension',
            'help'  => 'In simple words: How much is the person feeling tense, restless, easily startled, unable to relax, or physically keyed-up?',
            'description' => 'Feelings of tension, fatigability, startle response, moved to tears easily, trembling, feelings of restlessness, inability to relax.',
            'options' => array(
                0 => 'Not present',
                1 => 'Mild',
                2 => 'Moderate',
                3 => 'Severe',
                4 => 'Very severe'
            ),
            'domain' => 'Psychic anxiety'
        ),
        3 => array(
            'title' => 'Fears',
            'help'  => 'In simple words: How much does the person experience fears such as fear of darkness, strangers, being alone, animals, traffic, or crowds?',
            'description' => 'Of dark, of strangers, of being left alone, of animals, of traffic, of crowds.',
            'options' => array(
                0 => 'Not present',
                1 => 'Mild',
                2 => 'Moderate',
                3 => 'Severe',
                4 => 'Very severe'
            ),
            'domain' => 'Psychic anxiety'
        ),
        4 => array(
            'title' => 'Insomnia',
            'help'  => 'In simple words: How much has anxiety affected sleep, including difficulty falling asleep, broken sleep, poor-quality sleep, dreams, nightmares, or night terrors?',
            'description' => 'Difficulty in falling asleep, broken sleep, unsatisfying sleep and fatigue on waking, dreams, nightmares, night terrors.',
            'options' => array(
                0 => 'Not present',
                1 => 'Mild',
                2 => 'Moderate',
                3 => 'Severe',
                4 => 'Very severe'
            ),
            'domain' => 'Psychic anxiety'
        ),
        5 => array(
            'title' => 'Intellectual',
            'help'  => 'In simple words: How much difficulty is there with concentration or memory?',
            'description' => 'Difficulty in concentration, poor memory.',
            'options' => array(
                0 => 'Not present',
                1 => 'Mild',
                2 => 'Moderate',
                3 => 'Severe',
                4 => 'Very severe'
            ),
            'domain' => 'Psychic anxiety'
        ),
        6 => array(
            'title' => 'Depressed mood',
            'help'  => 'In simple words: How much loss of interest, reduced pleasure, low mood, early waking, or daily variation in mood is present?',
            'description' => 'Loss of interest, lack of pleasure in hobbies, depression, early waking, diurnal swing.',
            'options' => array(
                0 => 'Not present',
                1 => 'Mild',
                2 => 'Moderate',
                3 => 'Severe',
                4 => 'Very severe'
            ),
            'domain' => 'Psychic anxiety'
        ),
        7 => array(
            'title' => 'Somatic (muscular)',
            'help'  => 'In simple words: How much are physical muscle-related symptoms present, such as aches, twitching, stiffness, tremors or increased muscle tension?',
            'description' => 'Pains and aches, twitching, stiffness, myoclonic jerks, grinding of teeth, unsteady voice, increased muscular tone.',
            'options' => array(
                0 => 'Not present',
                1 => 'Mild',
                2 => 'Moderate',
                3 => 'Severe',
                4 => 'Very severe'
            ),
            'domain' => 'Somatic anxiety'
        ),
        8 => array(
            'title' => 'Somatic (sensory)',
            'help'  => 'In simple words: How much are sensory physical symptoms present, such as ringing in the ears, blurred vision, hot/cold flushes, weakness or prickling?',
            'description' => 'Tinnitus, blurring of vision, hot and cold flushes, feelings of weakness, pricking sensation.',
            'options' => array(
                0 => 'Not present',
                1 => 'Mild',
                2 => 'Moderate',
                3 => 'Severe',
                4 => 'Very severe'
            ),
            'domain' => 'Somatic anxiety'
        ),
        9 => array(
            'title' => 'Cardiovascular symptoms',
            'help'  => 'In simple words: How much are heart or circulation-related symptoms present, such as a racing or pounding heart, chest pain, faint feelings, or missed beats?',
            'description' => 'Tachycardia, palpitations, pain in chest, throbbing of vessels, fainting feelings, missing beat.',
            'options' => array(
                0 => 'Not present',
                1 => 'Mild',
                2 => 'Moderate',
                3 => 'Severe',
                4 => 'Very severe'
            ),
            'domain' => 'Somatic anxiety'
        ),
        10 => array(
            'title' => 'Respiratory symptoms',
            'help'  => 'In simple words: How much are breathing-related symptoms present, such as chest tightness, choking feelings, sighing or shortness of breath?',
            'description' => 'Pressure or constriction in chest, choking feelings, sighing, dyspnea.',
            'options' => array(
                0 => 'Not present',
                1 => 'Mild',
                2 => 'Moderate',
                3 => 'Severe',
                4 => 'Very severe'
            ),
            'domain' => 'Somatic anxiety'
        ),
        11 => array(
            'title' => 'Gastrointestinal symptoms',
            'help'  => 'In simple words: How much are digestive symptoms present, such as swallowing difficulty, abdominal discomfort, nausea, vomiting, bowel changes or constipation?',
            'description' => 'Difficulty in swallowing, wind, abdominal pain, burning sensations, abdominal fullness, nausea, vomiting, borborygmi, looseness of bowels, loss of weight, constipation.',
            'options' => array(
                0 => 'Not present',
                1 => 'Mild',
                2 => 'Moderate',
                3 => 'Severe',
                4 => 'Very severe'
            ),
            'domain' => 'Somatic anxiety'
        ),
        12 => array(
            'title' => 'Genitourinary symptoms',
            'help'  => 'In simple words: How much are urinary or sexual-function symptoms present, such as urinary frequency/urgency or changes in sexual functioning?',
            'description' => 'Frequency of micturition, urgency of micturition, amenorrhea, menorrhagia, development of frigidity, premature ejaculation, loss of libido, impotence.',
            'options' => array(
                0 => 'Not present',
                1 => 'Mild',
                2 => 'Moderate',
                3 => 'Severe',
                4 => 'Very severe'
            ),
            'domain' => 'Somatic anxiety'
        ),
        13 => array(
            'title' => 'Autonomic symptoms',
            'help'  => 'In simple words: How much are autonomic symptoms present, such as dry mouth, flushing, pallor, sweating, dizziness, tension headache, or hair standing up?',
            'description' => 'Dry mouth, flushing, pallor, tendency to sweat, giddiness, tension headache, raising of hair.',
            'options' => array(
                0 => 'Not present',
                1 => 'Mild',
                2 => 'Moderate',
                3 => 'Severe',
                4 => 'Very severe'
            ),
            'domain' => 'Somatic anxiety'
        ),
        14 => array(
            'title' => 'Behavior at interview',
            'help'  => 'In simple words: During the interview, how much is visible anxiety shown through fidgeting, pacing, tremor, a strained face, sighing, rapid breathing, pallor or repeated swallowing?',
            'description' => 'Fidgeting, restlessness or pacing, tremor of hands, furrowed brow, strained face, sighing or rapid respiration, facial pallor, swallowing, etc.',
            'options' => array(
                0 => 'Not present',
                1 => 'Mild',
                2 => 'Moderate',
                3 => 'Severe',
                4 => 'Very severe'
            ),
            'domain' => 'Somatic anxiety'
        ),
    );
}

/* =========================================================
 * SEVERITY
 *
 * The supplied source explicitly states:
 * <17 mild
 * 18–24 mild to moderate
 * 25–30 moderate to severe
 *
 * The source excerpt does not specify the remaining scores.
 * For a complete 0–56 gauge, scores 31–56 are displayed as
 * "Severe (above 30)" as a clearly identified practical extension.
 * ========================================================= */
function ayument_hama_severity(int $total): array {
    if ($total <= 16) {
        return array(
            'label' => 'Mild',
            'class' => 'mild',
            'description' => 'Score is below 17 and falls in the mild range stated in the supplied HAM-A source.'
        );
    }

    if ($total <= 24) {
        return array(
            'label' => 'Mild to Moderate',
            'class' => 'mild-moderate',
            'description' => 'Score falls within the 18–24 mild-to-moderate range stated in the supplied HAM-A source.'
        );
    }

    if ($total <= 30) {
        return array(
            'label' => 'Moderate to Severe',
            'class' => 'moderate-severe',
            'description' => 'Score falls within the 25–30 moderate-to-severe range stated in the supplied HAM-A source.'
        );
    }

    return array(
        'label' => 'Severe',
        'class' => 'severe',
        'description' => 'Score is above 30. The supplied source excerpt does not provide a separate label for scores above 30; AyuMent displays these as Severe for dashboard continuity.'
    );
}

function ayument_hama_gauge_percent(int $total): int {
    $total = max(0, min(56, $total));
    return (int) round(($total / 56) * 100);
}

function ayument_hama_item_level(int $score): string {
    if ($score <= 0) return 'None';
    if ($score === 1) return 'Mild';
    if ($score === 2) return 'Moderate';
    if ($score === 3) return 'Severe';
    return 'Very severe';
}

/* =========================================================
 * MAIN PAGE
 * ========================================================= */
function ayument_hama_page() {

    $items = ayument_hama_items();

    $submitted = false;
    $scores = array();
    $total = 0;
    $error = '';

    if (
        isset($_POST['ayument_hama_submit']) &&
        check_admin_referer('ayument_hama_assessment', 'ayument_hama_nonce')
    ) {

        $valid = true;

        foreach ($items as $number => $item) {

            $field = 'hama_' . $number;

            if (!isset($_POST[$field])) {
                $valid = false;
                break;
            }

            $score = intval($_POST[$field]);

            if ($score < 0 || $score > 4) {
                $valid = false;
                break;
            }

            $scores[$number] = $score;
            $total += $score;
        }

        if (!$valid) {
            $error = 'Please complete all 14 HAM-A items before submitting the assessment.';
            $scores = array();
            $total = 0;
        } else {
            $submitted = true;
        }
    }

    ?>
    <div class="wrap ayument-hama-wrap">

        <style>
            .ayument-hama-wrap { max-width: 1180px; }

            .ayument-hama-hero {
                background: linear-gradient(135deg,#172554,#2563eb);
                color:#fff;
                padding:30px;
                border-radius:18px;
                margin:20px 0;
                box-shadow:0 12px 32px rgba(15,23,42,.10);
            }
            .ayument-hama-hero h1 { color:#fff; margin:0 0 8px; font-size:30px; }
            .ayument-hama-hero p { margin:5px 0; font-size:15px; }

            .ayument-hama-card {
                background:#fff;
                border:1px solid #e2e8f0;
                border-radius:16px;
                padding:22px;
                margin:16px 0;
                box-shadow:0 4px 16px rgba(15,23,42,.04);
            }
            .ayument-hama-card h2 { margin-top:0; color:#172554; }

            .ayument-hama-question {
                font-size:17px;
                font-weight:800;
                color:#172554;
                margin-bottom:14px;
            }
            .ayument-hama-question small {
                display:block;
                margin-top:6px;
                color:#64748b;
                font-weight:400;
            }

            /* ? help */
            .ayument-hama-help {
                position:relative;
                display:inline-flex;
                vertical-align:middle;
                margin-left:7px;
            }
            .ayument-hama-help-button {
                width:22px;
                height:22px;
                padding:0;
                border:1px solid #2563eb;
                border-radius:50%;
                background:#eff6ff;
                color:#2563eb;
                font-size:13px;
                font-weight:800;
                line-height:20px;
                text-align:center;
                cursor:help;
            }
            .ayument-hama-help-button:hover,
            .ayument-hama-help-button:focus {
                background:#2563eb;
                color:#fff;
                outline:none;
            }
            .ayument-hama-help-tooltip {
                display:none;
                position:absolute;
                z-index:1000;
                left:29px;
                top:-8px;
                width:340px;
                max-width:70vw;
                padding:12px 14px;
                border-radius:10px;
                background:#172554;
                color:#fff;
                font-size:13px;
                font-weight:400;
                line-height:1.5;
                box-shadow:0 10px 25px rgba(0,0,0,.2);
                text-align:left;
            }
            .ayument-hama-help-tooltip:before {
                content:"";
                position:absolute;
                left:-7px;
                top:12px;
                border-width:7px 7px 7px 0;
                border-style:solid;
                border-color:transparent #172554 transparent transparent;
            }
            .ayument-hama-help:hover .ayument-hama-help-tooltip,
            .ayument-hama-help:focus-within .ayument-hama-help-tooltip,
            .ayument-hama-help.is-open .ayument-hama-help-tooltip { display:block; }

            .ayument-hama-option {
                display:block;
                padding:13px 15px;
                margin:8px 0;
                border:1px solid #dbe3ef;
                border-radius:11px;
                cursor:pointer;
                transition:.15s ease;
                background:#fafcff;
            }
            .ayument-hama-option:hover {
                border-color:#2563eb;
                background:#eff6ff;
                transform:translateY(-1px);
            }
            .ayument-hama-option input { margin-right:10px; }

            .ayument-hama-submit {
                background:#2563eb !important;
                color:#fff !important;
                border:none !important;
                padding:13px 28px !important;
                border-radius:10px !important;
                font-size:16px !important;
                cursor:pointer;
            }

            /* =================================================
             * RESULT DASHBOARD
             * ================================================= */
            .ayument-hama-result-main {
                background:#fff;
                border:1px solid #e2e8f0;
                border-radius:22px;
                padding:28px;
                box-shadow:0 12px 35px rgba(15,23,42,.07);
                margin-top:18px;
            }
            .ayument-hama-result-topbar {
                display:flex;
                align-items:flex-start;
                justify-content:space-between;
                gap:18px;
                margin-bottom:20px;
            }
            .ayument-hama-result-heading {
                color:#12306b;
                margin:0;
                font-size:30px;
                line-height:1.15;
                font-weight:850;
            }
            .ayument-hama-result-subheading {
                color:#64748b;
                margin:7px 0 0;
                font-size:15px;
            }
            .ayument-hama-result-actions {
                display:flex;
                gap:8px;
                flex-shrink:0;
            }
            .ayument-hama-result-actions .button {
                min-height:42px;
                padding:8px 15px !important;
                border-radius:10px !important;
            }

            .ayument-hama-result-grid {
                display:grid;
                grid-template-columns:220px minmax(430px,1fr) 270px;
                gap:22px;
                align-items:stretch;
            }

            .ayument-hama-score-card,
            .ayument-hama-meaning-card {
                border:1px solid #dbe5f2;
                border-radius:17px;
                background:linear-gradient(145deg,#fbfdff,#f5f8fd);
                padding:22px;
                min-height:300px;
            }
            .ayument-hama-score-card {
                display:flex;
                flex-direction:column;
                justify-content:center;
                text-align:center;
            }
            .ayument-hama-score-label {
                color:#12306b;
                font-size:17px;
                font-weight:800;
                margin-bottom:12px;
            }
            .ayument-hama-score-number {
                color:#12306b;
                font-size:64px;
                line-height:.95;
                font-weight:900;
                letter-spacing:-2px;
            }
            .ayument-hama-score-outof {
                color:#64748b;
                font-size:15px;
                margin-top:5px;
            }
            .ayument-hama-severity {
                display:inline-block;
                margin:16px auto 10px;
                padding:8px 18px;
                border-radius:999px;
                background:#fff1f0;
                color:#dc2626;
                font-size:17px;
                font-weight:850;
            }
            .ayument-hama-score-copy {
                color:#475569;
                font-size:13px;
                line-height:1.6;
                margin:4px 0 0;
            }

            .ayument-hama-meaning-card h3 {
                margin:0 0 18px;
                color:#12306b;
                font-size:18px;
            }
            .ayument-hama-meaning-row {
                display:flex;
                gap:11px;
                margin:0 0 17px;
                color:#475569;
                line-height:1.5;
                font-size:13px;
            }
            .ayument-hama-meaning-icon {
                width:30px;
                height:30px;
                flex:0 0 30px;
                display:grid;
                place-items:center;
                border-radius:9px;
                background:#edf4ff;
                color:#2563eb;
                font-weight:900;
            }

            /* =================================================
             * MODERN BIKE / MOTORCYCLE SPEEDOMETER
             * ================================================= */
            .ayument-hama-gauge-wrap {
                min-width:0;
                display:flex;
                flex-direction:column;
                justify-content:center;
                align-items:center;
            }
            .ayument-hama-gauge {
                position:relative;
                width:min(100%,560px);
                height:350px;
                overflow:visible;
                padding-bottom:28px;
                box-sizing:border-box;
            }
            .ayument-hama-gauge-arc {
                position:absolute;
                width:470px;
                height:235px;
                left:50%;
                top:22px;
                transform:translateX(-50%);
                border-radius:470px 470px 0 0;
                background:
                    radial-gradient(circle at 50% 100%,#111827 0 48%,transparent 49%),
                    conic-gradient(
                        from 270deg at 50% 100%,
                        #22c55e 0deg 25deg,
                        #84cc16 25deg 47deg,
                        #facc15 47deg 67deg,
                        #f59e0b 67deg 78deg,
                        #ef4444 78deg 180deg,
                        transparent 180deg
                    );
                box-shadow:
                    0 0 0 7px #cbd5e1,
                    0 0 0 10px #64748b,
                    0 13px 30px rgba(15,23,42,.18);
            }
            .ayument-hama-gauge-arc:before {
                content:"";
                position:absolute;
                inset:9px;
                border-radius:inherit;
                background:
                    repeating-conic-gradient(
                        from 270deg at 50% 100%,
                        rgba(255,255,255,.45) 0deg 1deg,
                        transparent 1deg 6deg
                    );
                -webkit-mask:linear-gradient(to bottom,#000 0 50%,transparent 50%);
                mask:linear-gradient(to bottom,#000 0 50%,transparent 50%);
                opacity:.75;
            }
            .ayument-hama-gauge-arc:after {
                content:"";
                position:absolute;
                width:382px;
                height:191px;
                left:44px;
                top:44px;
                border-radius:382px 382px 0 0;
                background:radial-gradient(circle at 50% 100%,#17212e 0%,#0b111a 58%,#060a10 100%);
                box-shadow:inset 0 8px 18px rgba(255,255,255,.05),inset 0 -15px 28px rgba(0,0,0,.45);
            }

            .ayument-hama-gauge-needle {
                position:absolute;
                left:50%;
                bottom:25px;
                width:7px;
                height:157px;
                background:linear-gradient(to right,#f8fafc 0 20%,#ef3340 21% 80%,#7f1d1d 81%);
                border-radius:10px;
                transform-origin:50% 100%;
                transform:translateX(-50%) rotate(-90deg);
                transition:transform 1s cubic-bezier(.18,.8,.2,1);
                z-index:5;
                filter:drop-shadow(0 2px 3px rgba(0,0,0,.45));
            }
            .ayument-hama-gauge-needle:before {
                content:"";
                position:absolute;
                left:50%;
                top:-10px;
                width:12px;
                height:22px;
                transform:translateX(-50%);
                background:#f43f4f;
                clip-path:polygon(50% 0,100% 100%,50% 82%,0 100%);
            }
            .ayument-hama-gauge-needle:after {
                content:"";
                position:absolute;
                width:34px;
                height:34px;
                border-radius:50%;
                left:50%;
                bottom:-17px;
                transform:translateX(-50%);
                background:radial-gradient(circle at 35% 30%,#64748b,#17212e 45%,#020617 70%);
                border:3px solid #94a3b8;
                box-shadow:0 2px 9px rgba(0,0,0,.5);
            }

            .ayument-hama-gauge-score {
                position:absolute;
                left:50%;
                top:163px;
                transform:translateX(-50%);
                z-index:6;
                text-align:center;
                color:#fff;
                min-width:130px;
            }
            .ayument-hama-gauge-score strong {
                display:block;
                color:#fff;
                font-size:48px;
                line-height:.95;
                font-weight:900;
                text-shadow:0 2px 8px rgba(0,0,0,.45);
            }
            .ayument-hama-gauge-score span {
                display:block;
                color:#cbd5e1;
                font-size:12px;
                margin-top:7px;
            }

            .ayument-hama-gauge-scale {
                position:absolute;
                left:50%;
                top:78px;
                transform:translateX(-50%);
                width:430px;
                max-width:88%;
                display:flex;
                justify-content:space-between;
                color:#e2e8f0;
                font-size:11px;
                font-weight:800;
                z-index:6;
            }
            .ayument-hama-gauge-labels {
                position:absolute;
                left:50%;
                top:276px;
                transform:translateX(-50%);
                width:470px;
                max-width:94%;
                display:flex;
                justify-content:space-between;
                align-items:flex-start;
                color:#334155;
                font-size:10px;
                line-height:1.2;
                font-weight:850;
                z-index:8;
                gap:6px;
                padding-top:8px;
                border-top:1px solid #cbd5e1;
            }
            .ayument-hama-gauge-labels span {
                white-space:nowrap;
                text-align:center;
                background:#fff;
                padding:0 3px;
            }
            .ayument-hama-gauge-marker {
                text-align:center;
                color:#12306b;
                font-weight:800;
                margin-top:4px;
                font-size:14px;
            }

            /* Summary cards */
            .ayument-hama-summary-grid {
                display:grid;
                grid-template-columns:repeat(4,minmax(0,1fr));
                gap:12px;
                margin-top:18px;
            }
            .ayument-hama-summary-item {
                position:relative;
                padding:17px 17px 17px 58px;
                border:1px solid #e2e8f0;
                border-radius:14px;
                background:#fff;
                min-height:58px;
            }
            .ayument-hama-summary-item:before {
                content:"✓";
                position:absolute;
                left:16px;
                top:17px;
                width:30px;
                height:30px;
                display:grid;
                place-items:center;
                border-radius:9px;
                background:#eff6ff;
                color:#2563eb;
                font-weight:900;
            }
            .ayument-hama-summary-item strong {
                display:block;
                color:#12306b;
                margin-bottom:4px;
                font-size:13px;
            }
            .ayument-hama-summary-item span {
                color:#475569;
                font-size:16px;
                font-weight:800;
            }

            /* Domain profile */
            .ayument-hama-section {
                background:#fff;
                border:1px solid #e2e8f0;
                border-radius:17px;
                padding:20px;
                margin-top:18px;
            }
            .ayument-hama-section h2 {
                color:#12306b;
                font-size:18px;
                margin:0 0 15px;
            }
            .ayument-hama-domain-grid {
                display:grid;
                grid-template-columns:repeat(2,minmax(0,1fr));
                gap:12px;
            }
            .ayument-hama-domain {
                border:1px solid #e2e8f0;
                border-radius:14px;
                padding:16px;
                background:#fbfdff;
            }
            .ayument-hama-domain-head {
                display:flex;
                justify-content:space-between;
                gap:12px;
                font-weight:800;
                color:#12306b;
            }
            .ayument-hama-progress {
                height:8px;
                border-radius:99px;
                background:#e5e7eb;
                overflow:hidden;
                margin:11px 0 7px;
            }
            .ayument-hama-progress span {
                display:block;
                height:100%;
                border-radius:99px;
                background:linear-gradient(90deg,#22c55e,#facc15,#ef4444);
            }
            .ayument-hama-domain-meta {
                display:flex;
                justify-content:space-between;
                color:#64748b;
                font-size:12px;
            }

            .ayument-hama-result-lower {
                display:grid;
                grid-template-columns:minmax(0,1fr) minmax(0,1fr);
                gap:18px;
                margin-top:18px;
            }
            .ayument-hama-lower-card {
                background:#fff;
                border:1px solid #e2e8f0;
                border-radius:16px;
                padding:20px;
            }
            .ayument-hama-lower-card h2 {
                color:#12306b;
                font-size:17px;
                margin:0 0 14px;
            }
            .ayument-hama-pills {
                display:flex;
                flex-wrap:wrap;
                gap:8px;
            }
            .ayument-hama-pill {
                display:inline-block;
                padding:7px 11px;
                border-radius:999px;
                background:#fff0ef;
                color:#b42318;
                font-size:12px;
                font-weight:750;
            }

            .ayument-hama-table {
                width:100%;
                border-collapse:collapse;
                margin-top:10px;
            }
            .ayument-hama-table th,
            .ayument-hama-table td {
                padding:10px;
                border-bottom:1px solid #e5e7eb;
                text-align:left;
            }
            .ayument-hama-table th { background:#f8fafc; }

            .ayument-hama-disclaimer {
                margin-top:20px;
                padding:18px;
                border-left:4px solid #2563eb;
                background:#eff6ff;
                color:#1e3a8a;
                line-height:1.55;
            }

            .ayument-hama-source-note {
                margin-top:12px;
                padding:14px 16px;
                border:1px solid #dbe5f2;
                border-radius:12px;
                background:#f8fafc;
                color:#475569;
                font-size:12px;
                line-height:1.5;
            }

            @media(max-width:1050px) {
                .ayument-hama-result-grid {
                    grid-template-columns:190px minmax(380px,1fr);
                }
                .ayument-hama-meaning-card {
                    grid-column:1/-1;
                    min-height:auto;
                }
                .ayument-hama-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
            }

            @media(max-width:700px) {
                .ayument-hama-result-main { padding:16px; }
                .ayument-hama-result-topbar { display:block; }
                .ayument-hama-result-heading { font-size:23px; }
                .ayument-hama-result-actions { margin-top:12px; }

                .ayument-hama-result-grid {
                    display:flex;
                    flex-direction:column;
                }
                .ayument-hama-score-card { min-height:auto; }
                .ayument-hama-gauge {
                    height:270px;
                    transform:scale(.94);
                    transform-origin:top center;
                    margin-bottom:-10px;
                }
                .ayument-hama-gauge-arc {
                    width:350px;
                    height:175px;
                    top:25px;
                }
                .ayument-hama-gauge-arc:after {
                    width:286px;
                    height:143px;
                    left:32px;
                    top:32px;
                }
                .ayument-hama-gauge-needle {
                    bottom:27px;
                    height:115px;
                }
                .ayument-hama-gauge-score { top:132px; }
                .ayument-hama-gauge-scale { top:62px; width:315px; }
                .ayument-hama-gauge-labels {
                    top:214px;
                    width:350px;
                    font-size:8px;
                }
                .ayument-hama-summary-grid,
                .ayument-hama-result-lower,
                .ayument-hama-domain-grid {
                    grid-template-columns:1fr;
                }
                .ayument-hama-help-tooltip {
                    position:fixed;
                    left:50%;
                    top:50%;
                    transform:translate(-50%,-50%);
                    width:min(88vw,360px);
                    max-width:88vw;
                    padding:16px;
                    font-size:14px;
                    box-shadow:0 12px 35px rgba(0,0,0,.28);
                }
                .ayument-hama-help-tooltip:before { display:none; }
            }

            @media print {
                #adminmenumain,
                #wpadminbar,
                #adminmenuback,
                .ayument-hama-no-print,
                .notice {
                    display:none !important;
                }
                #wpcontent { margin-left:0 !important; }
                .ayument-hama-wrap { max-width:none; }
                .ayument-hama-card,
                .ayument-hama-result-main,
                .ayument-hama-section,
                .ayument-hama-lower-card {
                    box-shadow:none;
                    break-inside:avoid;
                }
            }
        </style>

        <script>
        (function(){
            function closeAllHelp(except){
                document.querySelectorAll('.ayument-hama-help.is-open').forEach(function(wrap){
                    if(wrap !== except){
                        wrap.classList.remove('is-open');
                        var btn=wrap.querySelector('.ayument-hama-help-button');
                        if(btn) btn.setAttribute('aria-expanded','false');
                    }
                });
            }

            document.querySelectorAll('.ayument-hama-help-button').forEach(function(button){
                button.addEventListener('click',function(event){
                    event.preventDefault();
                    event.stopPropagation();

                    var wrap=button.closest('.ayument-hama-help');
                    var open=wrap.classList.contains('is-open');

                    closeAllHelp(wrap);
                    wrap.classList.toggle('is-open',!open);
                    button.setAttribute('aria-expanded',open?'false':'true');
                });
            });

            document.addEventListener('click',function(event){
                if(!event.target.closest('.ayument-hama-help')){
                    closeAllHelp(null);
                }
            });
        })();
        </script>

        <script>
        (function(){
            var needle=document.querySelector('.ayument-hama-gauge-needle');
            if(needle){
                var finalTransform=needle.style.transform;
                needle.style.transform='translateX(-50%) rotate(-90deg)';
                window.requestAnimationFrame(function(){
                    window.requestAnimationFrame(function(){
                        needle.style.transform=finalTransform;
                    });
                });
            }
        })();
        </script>

        <?php if ($submitted): ?>

            <?php
            $severity = ayument_hama_severity($total);
            $gauge_percent = ayument_hama_gauge_percent($total);
            $needle_angle = -90 + (180 * ($gauge_percent / 100));

            $recorded_items = array();
            foreach($items as $number=>$item){
                if(isset($scores[$number]) && $scores[$number] > 0){
                    $recorded_items[]=$item['title'];
                }
            }

            $psychic_items = array(1,2,3,4,5,6);
            $somatic_items = array(7,8,9,10,11,12,13,14);

            $psychic_score = 0;
            $somatic_score = 0;

            foreach($psychic_items as $n) $psychic_score += intval($scores[$n]);
            foreach($somatic_items as $n) $somatic_score += intval($scores[$n]);

            $psychic_max = count($psychic_items) * 4;
            $somatic_max = count($somatic_items) * 4;

            $highest_score = -1;
            $highest_item = '';
            foreach($items as $n=>$item){
                if($scores[$n] > $highest_score){
                    $highest_score=$scores[$n];
                    $highest_item=$item['title'];
                }
            }
            ?>

            <div class="ayument-hama-result-main">

                <div class="ayument-hama-result-topbar">
                    <div>
                        <h1 class="ayument-hama-result-heading">HAM-A Assessment Result</h1>
                        <p class="ayument-hama-result-subheading">
                            Hamilton Anxiety Rating Scale · 14 items · Clinician-rated
                        </p>
                    </div>

                    <div class="ayument-hama-result-actions ayument-hama-no-print">
                        <button type="button" class="button" onclick="window.print();">
                            🖨 Print / Save
                        </button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=ayument-hama')); ?>"
                           class="button button-primary">
                            ↻ Retake Test
                        </a>
                    </div>
                </div>

                <div class="ayument-hama-result-grid">

                    <div class="ayument-hama-score-card">
                        <div class="ayument-hama-score-label">Your Score</div>
                        <div class="ayument-hama-score-number"><?php echo esc_html($total); ?></div>
                        <div class="ayument-hama-score-outof">out of 56</div>

                        <span class="ayument-hama-severity">
                            <?php echo esc_html($severity['label']); ?>
                        </span>

                        <p class="ayument-hama-score-copy">
                            <?php echo esc_html($severity['description']); ?>
                        </p>
                    </div>

                    <div class="ayument-hama-gauge-wrap">

                        <div class="ayument-hama-gauge"
                             role="img"
                             aria-label="<?php echo esc_attr('HAM-A score '.$total.' out of 56, '.$severity['label']); ?>">

                            <div class="ayument-hama-gauge-arc"></div>

                            <div class="ayument-hama-gauge-scale" aria-hidden="true">
                                <span>0</span>
                                <span>17</span>
                                <span>24</span>
                                <span>30</span>
                                <span>56</span>
                            </div>

                            <div class="ayument-hama-gauge-needle"
                                 style="transform:translateX(-50%) rotate(<?php echo esc_attr($needle_angle); ?>deg);"></div>

                            <div class="ayument-hama-gauge-score">
                                <strong><?php echo esc_html($total); ?></strong>
                                <span>out of 56</span>
                            </div>

                            <div class="ayument-hama-gauge-labels">
                                <span>MILD</span>
                                <span>MILD–MODERATE</span>
                                <span>MODERATE–SEVERE</span>
                                <span>SEVERE</span>
                            </div>

                        </div>

                        <div class="ayument-hama-gauge-marker">
                            HAM-A anxiety severity
                        </div>
                    </div>

                    <div class="ayument-hama-meaning-card">
                        <h3>What does this mean? ⓘ</h3>

                        <div class="ayument-hama-meaning-row">
                            <span class="ayument-hama-meaning-icon">✦</span>
                            <span>
                                This score reflects the anxiety symptoms rated during this assessment.
                            </span>
                        </div>

                        <div class="ayument-hama-meaning-row">
                            <span class="ayument-hama-meaning-icon">✓</span>
                            <span>
                                The current score is displayed against the HAM-A severity ranges used in this AyuMent implementation.
                            </span>
                        </div>

                        <div class="ayument-hama-meaning-row">
                            <span class="ayument-hama-meaning-icon">♙</span>
                            <span>
                                HAM-A is a clinician-rated assessment and is not, by itself, a standalone psychiatric diagnosis.
                            </span>
                        </div>
                    </div>

                </div>

                <div class="ayument-hama-summary-grid">

                    <div class="ayument-hama-summary-item">
                        <strong>Total Score</strong>
                        <span><?php echo esc_html($total); ?> / 56</span>
                    </div>

                    <div class="ayument-hama-summary-item">
                        <strong>Maximum Score</strong>
                        <span>56</span>
                    </div>

                    <div class="ayument-hama-summary-item">
                        <strong>Percentage</strong>
                        <span><?php echo esc_html(number_format(($total/56)*100,1)); ?>%</span>
                    </div>

                    <div class="ayument-hama-summary-item">
                        <strong>Highest Item</strong>
                        <span><?php echo esc_html($highest_score); ?> / 4</span>
                    </div>

                </div>

            </div>

            <div class="ayument-hama-section">
                <h2>Symptom Profile</h2>

                <div class="ayument-hama-domain-grid">

                    <div class="ayument-hama-domain">
                        <div class="ayument-hama-domain-head">
                            <span>Psychic Anxiety</span>
                            <span><?php echo esc_html($psychic_score); ?>/<?php echo esc_html($psychic_max); ?></span>
                        </div>
                        <div class="ayument-hama-progress">
                            <span style="width:<?php echo esc_attr(($psychic_score/$psychic_max)*100); ?>%;"></span>
                        </div>
                        <div class="ayument-hama-domain-meta">
                            <span><?php echo esc_html(number_format(($psychic_score/$psychic_max)*100,0)); ?>% of domain maximum</span>
                            <span>Items 1–6</span>
                        </div>
                    </div>

                    <div class="ayument-hama-domain">
                        <div class="ayument-hama-domain-head">
                            <span>Somatic Anxiety</span>
                            <span><?php echo esc_html($somatic_score); ?>/<?php echo esc_html($somatic_max); ?></span>
                        </div>
                        <div class="ayument-hama-progress">
                            <span style="width:<?php echo esc_attr(($somatic_score/$somatic_max)*100); ?>%;"></span>
                        </div>
                        <div class="ayument-hama-domain-meta">
                            <span><?php echo esc_html(number_format(($somatic_score/$somatic_max)*100,0)); ?>% of domain maximum</span>
                            <span>Items 7–14</span>
                        </div>
                    </div>

                </div>
            </div>

            <div class="ayument-hama-result-lower">

                <div class="ayument-hama-lower-card">
                    <h2>Areas with Symptoms Recorded</h2>

                    <?php if(!empty($recorded_items)): ?>
                        <div class="ayument-hama-pills">
                            <?php foreach($recorded_items as $name): ?>
                                <span class="ayument-hama-pill"><?php echo esc_html($name); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <p style="margin:14px 0 0;color:#64748b;font-size:12px;">
                            These are the HAM-A items where the recorded score was above zero.
                        </p>
                    <?php else: ?>
                        <p>No HAM-A item received a score above zero.</p>
                    <?php endif; ?>
                </div>

                <div class="ayument-hama-lower-card">
                    <h2>Assessment Summary</h2>
                    <p style="margin:0;color:#475569;line-height:1.6;">
                        <strong><?php echo esc_html($total); ?>/56</strong> total points were recorded
                        across all 14 items.
                    </p>
                    <p style="margin:10px 0 0;color:#475569;line-height:1.6;">
                        Highest recorded item:
                        <strong><?php echo esc_html($highest_item); ?> (<?php echo esc_html($highest_score); ?>/4)</strong>.
                    </p>
                </div>

            </div>

            <div class="ayument-hama-section">
                <h2>14-item Assessment Profile</h2>

                <table class="ayument-hama-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Domain</th>
                            <th>Level</th>
                            <th>Score</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($items as $number=>$item): ?>
                        <tr>
                            <td><?php echo esc_html($number); ?></td>
                            <td><?php echo esc_html($item['title']); ?></td>
                            <td><?php echo esc_html($item['domain']); ?></td>
                            <td><?php echo esc_html(ayument_hama_item_level($scores[$number])); ?></td>
                            <td><strong><?php echo esc_html($scores[$number]); ?></strong> / 4</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="ayument-hama-section">
                <h2>Clinical Context</h2>
                <p style="color:#475569;line-height:1.65;margin:0;">
                    The HAM-A is a clinician-rated measure intended to assess the severity
                    of anxiety symptoms. Interpretation should be made by a qualified
                    clinician using the interview, history, examination and other relevant
                    information.
                </p>

                <div class="ayument-hama-source-note">
                    <strong>Source note:</strong>
                    The supplied HAM-A document describes 14 items, each scored from
                    0 (not present) to 4 (very severe), for a total range of 0–56.
                    It states the ranges &lt;17, 18–24 and 25–30; the source excerpt
                    does not separately label scores above 30.
                </div>
            </div>

            <div class="ayument-hama-disclaimer">
                <strong>Important:</strong>
                HAM-A is a clinician-rated anxiety symptom severity instrument.
                This AyuMent implementation is intended for educational and
                clinical-support purposes and should not by itself be used to
                establish a psychiatric diagnosis or determine treatment.
            </div>

        <?php else: ?>

            <div class="ayument-hama-hero">
                <h1>🧠 HAM-A Assessment</h1>
                <p>Hamilton Anxiety Rating Scale – 14 Item Version</p>
                <p>Clinician-rated assessment of anxiety symptom severity</p>
            </div>

            <?php if($error): ?>
                <div class="notice notice-error is-dismissible">
                    <p><strong><?php echo esc_html($error); ?></strong></p>
                </div>
            <?php endif; ?>

            <div class="ayument-hama-card">
                <h2>Before You Begin</h2>

                <p>
                    The supplied HAM-A form instructs the rater to select one of
                    five responses for each of the fourteen questions:
                    <strong>0 = Not present, 1 = Mild, 2 = Moderate,
                    3 = Severe, 4 = Very severe.</strong>
                </p>

                <p>
                    The HAM-A is a <strong>clinician-rated</strong> instrument.
                    Use the clinical interview and relevant information when
                    selecting the response that best describes the patient.
                </p>

                <p><strong>14 items · Maximum score 56 · Administration time 10–15 minutes</strong></p>
            </div>

            <form method="post" id="ayument-hama-form">

                <?php
                wp_nonce_field(
                    'ayument_hama_assessment',
                    'ayument_hama_nonce'
                );
                ?>

                <?php foreach($items as $number=>$item): ?>

                    <div class="ayument-hama-card">

                        <div class="ayument-hama-question">

                            <?php echo esc_html($number); ?>.
                            <?php echo esc_html($item['title']); ?>

                            <span class="ayument-hama-help">
                                <button
                                    type="button"
                                    class="ayument-hama-help-button"
                                    aria-label="Explain this question in simple words"
                                    aria-expanded="false"
                                    title="Explain this question in simple words"
                                >?</button>

                                <span class="ayument-hama-help-tooltip" role="tooltip">
                                    <?php echo esc_html($item['help']); ?>
                                </span>
                            </span>

                            <small>
                                <?php echo esc_html($item['description']); ?>
                            </small>

                        </div>

                        <?php foreach($item['options'] as $score=>$text): ?>

                            <label class="ayument-hama-option">
                                <input
                                    type="radio"
                                    name="hama_<?php echo esc_attr($number); ?>"
                                    value="<?php echo esc_attr($score); ?>"
                                    required
                                >
                                <strong><?php echo esc_html($score); ?></strong>
                                —
                                <?php echo esc_html($text); ?>
                            </label>

                        <?php endforeach; ?>

                    </div>

                <?php endforeach; ?>

                <div class="ayument-hama-card">
                    <button
                        type="submit"
                        name="ayument_hama_submit"
                        class="button ayument-hama-submit"
                    >
                        Calculate HAM-A Score
                    </button>
                </div>

            </form>

            <div class="ayument-hama-disclaimer">
                <strong>Important:</strong>
                HAM-A is a clinician-rated anxiety severity instrument.
                This AyuMent implementation is intended for educational and
                clinical-support purposes and does not itself diagnose anxiety
                or replace professional clinical assessment.
            </div>

        <?php endif; ?>

    </div>
    <?php
}
