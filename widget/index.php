<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : index.php
Module  : Widget Module Information
Status  : Development
Author  : Umesh + ChatGPT
Created : 07 July 2026
==================================================
*/
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <!-- ==========================================
         01. META TAGS
    ========================================== -->

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>XD Chat Widget Module</title>


    <!-- ==========================================
         02. PAGE STYLES
    ========================================== -->

    <style>

        *{
            box-sizing:border-box;
        }

        body{
            min-height:100vh;
            margin:0;
            font-family:Inter, Arial, sans-serif;
            color:#f8fafc;
            background:
                radial-gradient(circle at 16% 16%, rgba(37,99,235,.34), transparent 28%),
                radial-gradient(circle at 84% 18%, rgba(124,58,237,.28), transparent 32%),
                linear-gradient(135deg,#07111f 0%,#0f172a 48%,#111827 100%);
        }

        .xd-widget-page{
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:40px 20px;
        }

        .xd-widget-shell{
            width:100%;
            max-width:1120px;
        }

        .xd-widget-hero{
            display:grid;
            grid-template-columns:1.05fr .95fr;
            gap:28px;
            align-items:stretch;
        }

        .xd-widget-panel,
        .xd-widget-card{
            border:1px solid rgba(255,255,255,.14);
            border-radius:28px;
            background:rgba(15,23,42,.74);
            box-shadow:0 30px 90px rgba(0,0,0,.42);
            backdrop-filter:blur(22px);
        }

        .xd-widget-panel{
            padding:52px;
        }

        .xd-widget-badge{
            display:inline-flex;
            align-items:center;
            gap:10px;
            margin-bottom:24px;
            padding:9px 15px;
            border-radius:999px;
            background:rgba(37,99,235,.18);
            color:#bfdbfe;
            font-size:13px;
            font-weight:800;
        }

        .xd-widget-dot{
            width:10px;
            height:10px;
            border-radius:50%;
            background:#22c55e;
            box-shadow:0 0 0 5px rgba(34,197,94,.14);
        }

        h1{
            max-width:680px;
            margin:0 0 18px;
            color:#ffffff;
            font-size:56px;
            line-height:1.05;
            letter-spacing:0;
        }

        .xd-widget-lead{
            max-width:680px;
            margin:0 0 30px;
            color:#cbd5e1;
            font-size:18px;
            line-height:1.8;
        }

        .xd-widget-actions{
            display:flex;
            flex-wrap:wrap;
            gap:14px;
        }

        .xd-widget-btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:48px;
            padding:0 20px;
            border-radius:14px;
            text-decoration:none;
            font-weight:900;
            transition:.25s;
        }

        .xd-widget-btn.primary{
            background:linear-gradient(135deg,#2563eb,#7c3aed);
            color:#ffffff;
            box-shadow:0 18px 36px rgba(37,99,235,.28);
        }

        .xd-widget-btn.secondary{
            border:1px solid rgba(255,255,255,.18);
            background:rgba(255,255,255,.08);
            color:#dbeafe;
        }

        .xd-widget-btn:hover{
            transform:translateY(-2px);
        }

        .xd-widget-card{
            padding:34px;
        }

        .xd-widget-card h2{
            margin:0 0 16px;
            color:#ffffff;
            font-size:24px;
        }

        .xd-widget-card p{
            margin:0;
            color:#cbd5e1;
            line-height:1.75;
        }

        .xd-widget-flow{
            display:grid;
            grid-template-columns:repeat(5,1fr);
            gap:12px;
            margin-top:28px;
        }

        .xd-flow-step{
            min-height:92px;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:16px;
            border:1px solid rgba(255,255,255,.12);
            border-radius:18px;
            background:rgba(255,255,255,.08);
            color:#e0f2fe;
            text-align:center;
            font-size:14px;
            font-weight:800;
        }

        .xd-widget-grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:28px;
            margin-top:28px;
        }

        .xd-widget-code{
            overflow:auto;
            margin:18px 0 0;
            padding:20px;
            border:1px solid rgba(96,165,250,.25);
            border-radius:18px;
            background:#020617;
            color:#bfdbfe;
            font-size:14px;
            line-height:1.7;
        }

        .xd-widget-note{
            display:grid;
            gap:14px;
        }

        .xd-note-item{
            padding:16px;
            border-radius:16px;
            background:rgba(255,255,255,.08);
            color:#dbeafe;
            line-height:1.6;
        }

        @media(max-width:960px){

            .xd-widget-hero,
            .xd-widget-grid{
                grid-template-columns:1fr;
            }

            h1{
                font-size:44px;
            }

            .xd-widget-flow{
                grid-template-columns:1fr;
            }

        }

        @media(max-width:560px){

            .xd-widget-page{
                padding:18px;
            }

            .xd-widget-panel,
            .xd-widget-card{
                padding:26px;
                border-radius:22px;
            }

            h1{
                font-size:34px;
            }

            .xd-widget-lead{
                font-size:16px;
            }

            .xd-widget-actions{
                flex-direction:column;
            }

            .xd-widget-btn{
                width:100%;
            }

        }

    </style>

</head>

<body>

    <!-- ==========================================
         03. WIDGET MODULE PAGE
    ========================================== -->

    <main class="xd-widget-page">

        <div class="xd-widget-shell">

            <!-- ==========================================
                 04. HERO SECTION
            ========================================== -->

            <section class="xd-widget-hero">

                <div class="xd-widget-panel">

                    <div class="xd-widget-badge">
                        <span class="xd-widget-dot"></span>
                        Widget Engine
                    </div>

                    <h1>XD Chat Widget Module</h1>

                    <p class="xd-widget-lead">
                        This module powers embeddable live chat widgets for websites connected through the XD Chat dashboard.
                    </p>

                    <div class="xd-widget-actions">

                        <a href="../dashboard/index.php"
                           class="xd-widget-btn primary">
                            Go to Dashboard
                        </a>

                        <a href="../test-widget.html"
                           class="xd-widget-btn secondary">
                            Test Widget
                        </a>

                    </div>

                </div>

                <div class="xd-widget-card">

                    <h2>Not a visitor page</h2>

                    <p>
                        Visitors do not open this URL directly. Website owners create a widget from the dashboard, copy the embed code, and paste it into their website. Visitors then see the chat button on that website.
                    </p>

                </div>

            </section>


            <!-- ==========================================
                 05. SAMPLE FLOW
            ========================================== -->

            <section class="xd-widget-card" style="margin-top:28px;">

                <h2>Widget installation flow</h2>

                <div class="xd-widget-flow">

                    <div class="xd-flow-step">Dashboard</div>

                    <div class="xd-flow-step">Website Add</div>

                    <div class="xd-flow-step">Widget Key</div>

                    <div class="xd-flow-step">Embed Code</div>

                    <div class="xd-flow-step">Visitor Chat</div>

                </div>

            </section>


            <!-- ==========================================
                 06. EMBED CODE AND NOTES
            ========================================== -->

            <section class="xd-widget-grid">

                <div class="xd-widget-card">

                    <h2>Sample embed code</h2>

                    <p>
                        Replace <strong>YOUR_WIDGET_KEY</strong> with the widget key generated from your dashboard.
                    </p>

                    <pre class="xd-widget-code"><code>&lt;script src="http://localhost/XD-Chat/widget/widget.js"
        data-widget-key="YOUR_WIDGET_KEY"&gt;&lt;/script&gt;</code></pre>

                </div>

                <div class="xd-widget-card">

                    <h2>What this module handles</h2>

                    <div class="xd-widget-note">

                        <div class="xd-note-item">
                            Loads widget settings such as theme, position, color, and welcome message.
                        </div>

                        <div class="xd-note-item">
                            Sends visitor messages to the XD Chat dashboard.
                        </div>

                        <div class="xd-note-item">
                            Keeps the website chat experience separate for every account and website.
                        </div>

                    </div>

                </div>

            </section>

        </div>

    </main>

</body>

</html>
