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

            @media print {

                #adminmenumain,
                #wpadminbar,
                #adminmenuback,
                .ayument-hamd-no-print,
                .notice {
                    display: none !important;
                }

                #wpcontent {
                    margin-left: 0 !important;
                }

                .ayument-hamd-wrap {
                    max-width: none;
                }

                .ayument-hamd-card,
                .ayument-hamd-result {
                    box-shadow: none;
                    break-inside: avoid;
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


        <?php if ($submitted): ?>

            <?php
            $severity = ayument_hamd_severity($total);
            ?>

            <div class="ayument-hamd-hero">

                <h1>HAM-D17 Assessment Result</h1>

                <p>
                    Hamilton Depression Rating Scale – 17 Item Assessment
                </p>

                <p>
                    Assessment timeframe: <strong>Past week</strong>
                </p>

            </div>


            <div class="ayument-hamd-result">

                <div>HAM-D17 Total Score</div>

                <div class="ayument-hamd-score">
                    <?php echo esc_html($total); ?> / 52
                </div>

                <div class="ayument-hamd-severity">
                    <?php echo esc_html($severity['label']); ?>
                </div>

                <p>
                    <?php echo esc_html($severity['description']); ?>
                </p>

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


            <div class="ayument-hamd-card">

                <h2>Item-wise Assessment</h2>

                <table class="ayument-hamd-table">

                    <thead>

                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Score</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($items as $number => $item): ?>

                        <tr>

                            <td>
                                <?php echo esc_html($number); ?>
                            </td>

                            <td>
                                <?php echo esc_html($item['title']); ?>
                            </td>

                            <td>
                                <strong>
                                    <?php echo esc_html($scores[$number]); ?>
                                </strong>
                                /
                                <?php echo esc_html($item['max']); ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <div class="ayument-hamd-disclaimer">

                <strong>Important:</strong>

                HAM-D17 is a clinician-rated measure of depressive symptom
                severity. This result is an assessment-support output and
                should not be used alone to establish a psychiatric diagnosis
                or determine treatment.

            </div>


            <div class="ayument-hamd-no-print ayument-hamd-print">

                <button
                    type="button"
                    class="button button-primary"
                    onclick="window.print();"
                >
                    Print / Save Result
                </button>

                <a
                    href="<?php echo esc_url(admin_url('admin.php?page=ayument-hamd')); ?>"
                    class="button"
                >
                    New Assessment
                </a>

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