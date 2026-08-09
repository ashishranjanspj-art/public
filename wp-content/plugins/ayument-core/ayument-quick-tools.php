<?php
/**
 * AyuMent Quick Tools
 *
 * A simple utility hub for the AyuMent platform.
 */

if (!defined('ABSPATH')) {
    exit;
}

/* =========================================================
 * QUICK TOOLS MENU
 * ========================================================= */

function ayument_quick_tools_menu() {

    add_submenu_page(
        'ayument-dashboard',
        'Quick Tools',
        'Quick Tools',
        'manage_options',
        'ayument-quick-tools',
        'ayument_quick_tools_page'
    );
}

add_action('admin_menu', 'ayument_quick_tools_menu');


/* =========================================================
 * QUICK TOOLS PAGE
 * ========================================================= */

function ayument_quick_tools_page() {
    ?>

   <div class="wrap ayument-quick-tools">

    <div style="
        background: linear-gradient(135deg, #315c45, #4f8065);
        color: #ffffff;
        padding: 28px 32px;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    ">
        <h1 style="margin: 0 0 10px 0; color: #ffffff;">
            AyuMent Quick Tools
        </h1>

        <p style="margin: 0; font-size: 16px; line-height: 1.6;">
            Your workspace for Ayurveda research, clinical documentation,
            laboratory interpretation and AI-assisted tools.
        </p>

        <p style="margin: 10px 0 0 0; opacity: 0.9;">
            Explore the tools below to simplify your Ayurveda workflow.
        </p>
    </div>

    <style>

            .ayument-quick-tools {
                max-width: 1100px;
                margin: 25px auto;
            }

            .ayument-tools-header {
                background: linear-gradient(
                    135deg,
                    #315c45,
                    #4f8064
                );
                color: #ffffff;
                padding: 30px;
                border-radius: 16px;
                margin-bottom: 25px;
                box-shadow: 0 5px 18px rgba(0,0,0,.12);
            }

            .ayument-tools-header h1 {
                color: #ffffff;
                margin: 0 0 8px;
                font-size: 30px;
            }

            .ayument-tools-header p {
                margin: 0;
                font-size: 16px;
                opacity: .95;
            }

            .ayument-tools-grid {
                display: grid;
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
                gap: 20px;
            }

            .ayument-tool-card {
                background: #ffffff;
                border: 1px solid #dfe8e2;
                border-radius: 14px;
                padding: 25px;
                min-height: 175px;
                box-sizing: border-box;
                box-shadow: 0 4px 14px rgba(0,0,0,.06);
                transition: .2s ease;
            }

            .ayument-tool-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 22px rgba(0,0,0,.10);
                border-color: #315c45;
            }

            .ayument-tool-icon {
                font-size: 32px;
                margin-bottom: 12px;
            }

            .ayument-tool-card h2 {
                margin: 0 0 8px;
                color: #26372d;
                font-size: 20px;
            }

            .ayument-tool-card p {
                margin: 0 0 18px;
                color: #66736b;
                line-height: 1.6;
                font-size: 14px;
            }

            .ayument-tool-status {
                display: inline-block;
                padding: 5px 11px;
                border-radius: 20px;
                background: #eef7f1;
                color: #315c45;
                font-size: 12px;
                font-weight: 600;
            }

            .ayument-tools-footer {
                margin-top: 25px;
                background: #ffffff;
                border: 1px solid #dfe8e2;
                border-radius: 14px;
                padding: 22px;
            }

            .ayument-tools-footer p {
                margin: 0;
                color: #66736b;
            }

            .ayument-tools-back {
                margin-top: 20px;
            }

            @media(max-width:700px) {

                .ayument-tools-grid {
                    grid-template-columns: 1fr;
                }

            }

        </style>


        <!-- HEADER -->

        <div class="ayument-tools-header">

            <h1>
                🧰 AyuMent Quick Tools
            </h1>

            <p>
                A central place for useful tools and utilities
                across the AyuMent platform.
            </p>

        </div>


        <!-- TOOLS -->

        <div class="ayument-tools-grid">


            <!-- LAB REPORT -->

            <div class="ayument-tool-card">

                <div class="ayument-tool-icon">
                    🧪
                </div>

                <h2>
                    Lab Report Reader
                </h2>

                <p>
                    Upload and analyse laboratory reports
                    using future OCR and intelligent interpretation
                    features.
                </p>

                <span class="ayument-tool-status">
                    Coming Soon
                </span>

            </div>


            <!-- AYURVEDA REFERENCE -->

            <div class="ayument-tool-card">

                <div class="ayument-tool-icon">
                    📚
                </div>

                <h2>
                    Ayurveda Reference
                </h2>

                <p>
                    Access classical Ayurvedic references,
                    formulations, concepts and educational material.
                </p>

                <span class="ayument-tool-status">
                    Coming Soon
                </span>

            </div>


            <!-- FORMULATION SEARCH -->

            <div class="ayument-tool-card">

                <div class="ayument-tool-icon">
                    💊
                </div>

                <h2>
                    Formulation Search
                </h2>

                <p>
                    Search Ayurvedic medicines and formulations
                    with ingredients, indications and references.
                </p>

                <span class="ayument-tool-status">
                    Coming Soon
                </span>

            </div>


            <!-- CASE NOTES -->

            <div class="ayument-tool-card">

                <div class="ayument-tool-icon">
                    📝
                </div>

                <h2>
                    Case Notes
                </h2>

                <p>
                    Quickly create structured clinical case notes
                    and organize patient information.
                </p>

                <span class="ayument-tool-status">
                    Coming Soon
                </span>

            </div>


            <!-- RESEARCH -->

            <div class="ayument-tool-card">

                <div class="ayument-tool-icon">
                    🔬
                </div>

                <h2>
                    Research Tools
                </h2>

                <p>
                    Tools for literature review, research planning,
                    references and academic work.
                </p>

                <span class="ayument-tool-status">
                    Coming Soon
                </span>

            </div>


            <!-- AI TOOLS -->

            <div class="ayument-tool-card">

                <div class="ayument-tool-icon">
                    🤖
                </div>

                <h2>
                    AI Tools
                </h2>

                <p>
                    A future collection of AI-powered tools
                    for Ayurvedic healthcare and education.
                </p>

                <span class="ayument-tool-status">
                    API Pending
                </span>

            </div>


        </div>


        <!-- FOOTER -->

        <div class="ayument-tools-footer">

            <p>
                🌿 <strong>AyuMent</strong> — building a bridge
                between Ayurveda, healthcare and modern technology.
            </p>

        </div>


        <!-- BACK -->

        <div class="ayument-tools-back">

            <a
                href="<?php echo esc_url(
                    admin_url(
                        'admin.php?page=ayument-dashboard'
                    )
                ); ?>"
                class="button button-primary"
            >
                ← Back to AyuMent Dashboard
            </a>

        </div>

    </div>

    <?php
}