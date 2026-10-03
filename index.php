<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CampusVoice - College Complaint Management System</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>

        /* =========================
           HOME PAGE
        ========================= */

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #222;
        }

        /* HEADER */

        .home-header {
            width: 100%;
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            box-sizing: border-box;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .home-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .home-logo {
            font-size: 45px;
        }

        .home-brand h2 {
            margin: 0;
            color: #123c69;
            font-size: 24px;
        }

        .home-brand p {
            margin: 3px 0 0;
            color: #555;
            font-size: 14px;
        }

        .home-nav {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .home-nav a {
            color: #222;
            text-decoration: none;
            font-weight: 500;
        }

        .home-nav a:hover {
            color: #123c69;
        }

        .sign-button {
            border: 1px solid #123c69;
            padding: 10px 18px;
            border-radius: 7px;
            color: #123c69 !important;
        }

        /* MAIN */

        .home-main {
            text-align: center;
            padding: 40px 20px 30px;
        }

        .home-welcome {
            font-size: 24px;
            font-weight: bold;
            color: #123c69;
            margin-bottom: 10px;
        }

        .home-title {
            font-size: 38px;
            color: #123c69;
            margin: 0;
        }

        .home-subtitle {
            font-size: 19px;
            color: #555;
            margin-top: 15px;
        }

        /* PORTALS */

        .portal-container {
            max-width: 1000px;
            margin: 35px auto;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        .portal-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 18px rgba(0,0,0,0.12);
        }

        .portal-heading {
            color: white;
            font-size: 20px;
            font-weight: bold;
            padding: 15px;
        }

        .student-portal .portal-heading {
            background: #1976d2;
        }

        .staff-portal .portal-heading {
            background: #43a047;
        }

        .portal-body {
            padding: 25px;
        }

        .portal-icon {
            font-size: 60px;
            margin-bottom: 10px;
        }

        .portal-body h2 {
            margin: 10px 0;
            color: #222;
        }

        .portal-body p {
            color: #555;
            line-height: 1.5;
            min-height: 48px;
        }

        /* IMPORTANT:
           Real buttons instead of old folder links
        */

        .portal-login {
            display: inline-block;
            width: 80%;
            border: none;
            border-radius: 7px;
            padding: 13px;
            margin-top: 15px;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .student-login {
            background: #1976d2;
        }

        .student-login:hover {
            background: #125ca8;
        }

        .staff-login {
            background: #43a047;
        }

        .staff-login:hover {
            background: #2e7d32;
        }

        /* QUICK STATUS */

        .quick-status {
            max-width: 600px;
            margin: 30px auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .quick-status h2 {
            margin-top: 0;
            color: #123c69;
        }

        .quick-status input {
            width: 65%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        .quick-status button {
            padding: 12px 18px;
            margin-left: 5px;
            border: none;
            border-radius: 6px;
            background: #123c69;
            color: white;
            cursor: pointer;
        }

        /* FOOTER */

        .home-footer {
            position: relative;
            min-height: 70px;
            background: white;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 15px 30px;
            box-sizing: border-box;
        }

        .home-footer p {
            margin: 0;
            color: #666;
        }

        .footer-left {
            position: absolute;
            left: 30px;
            display: flex;
            gap: 20px;
        }

        .footer-left a {
            color: #444;
            text-decoration: none;
        }

        /* SMALL ADMIN BUTTON */

        .hidden-admin {
            position: absolute;
            right: 20px;
            bottom: 8px;

            background: #b71c1c;
            color: white !important;

            padding: 7px 12px;
            border-radius: 5px;

            font-size: 11px;
            font-weight: bold;

            text-decoration: none;
        }

        .hidden-admin:hover {
            background: #8e0000;
        }

        /* MOBILE */

        @media (max-width: 800px) {

            .home-header {
                flex-direction: column;
                gap: 15px;
            }

            .home-nav {
                flex-wrap: wrap;
                justify-content: center;
            }

            .portal-container {
                grid-template-columns: 1fr;
            }

            .home-title {
                font-size: 28px;
            }

            .footer-left {
                position: static;
                margin-bottom: 10px;
            }

            .home-footer {
                flex-direction: column;
                gap: 10px;
            }

            .hidden-admin {
                position: static;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     HEADER
========================= -->

<header class="home-header">

    <div class="home-brand">

        <div class="home-logo">
            🎓
        </div>

        <div>

            <h2>
                CAMPUSVOICE
            </h2>

            <p>
                College Complaint Management System
            </p>

        </div>

    </div>


    <nav class="home-nav">

        <a href="#">
            Home
        </a>

        <a href="#">
            About
        </a>

        <a href="#">
            Contact
        </a>

        <a href="#">
            Help Desk
        </a>


        <!-- STUDENT SIGN IN -->

        <a
            href="http://localhost/swayam/student/login.php"
            class="sign-button"
        >
            Sign In
        </a>

    </nav>

</header>



<!-- =========================
     MAIN
========================= -->

<main class="home-main">


    <div class="home-welcome">
        WELCOME TO CAMPUSVOICE
    </div>


    <h1 class="home-title">
        Efficiently Reporting &amp; Resolving
        College Concerns
    </h1>


    <p class="home-subtitle">
        Select your Portal to get started.
    </p>



    <!-- =========================
         PORTALS
    ========================= -->

    <div class="portal-container">


        <!-- STUDENT -->

        <div class="portal-card student-portal">

            <div class="portal-heading">
                STUDENT PORTAL
            </div>


            <div class="portal-body">

                <div class="portal-icon">
                    🎓
                </div>


                <h2>
                    Lodge &amp; Track Complaints
                </h2>


                <p>
                    Submit academic, facility, or grievance
                    issues and track status.
                </p>


                <button
                    type="button"
                    class="portal-login student-login"
                    onclick="window.location.href='http://localhost/swayam/student/login.php';"
                >
                    LOGIN AS STUDENT
                </button>

            </div>

        </div>



        <!-- STAFF -->

        <div class="portal-card staff-portal">

            <div class="portal-heading">
                STAFF / FACULTY PORTAL
            </div>


            <div class="portal-body">

                <div class="portal-icon">
                    👨‍💼
                </div>


                <h2>
                    Manage Grievances
                </h2>


                <p>
                    Review assigned student concerns,
                    provide updates, and facilitate resolutions.
                </p>


                <button
                    type="button"
                    class="portal-login staff-login"
                    onclick="window.location.href='http://localhost/swayam/staff/login.php';"
                >
                    LOGIN AS STAFF
                </button>

            </div>

        </div>


    </div>



    <!-- =========================
         QUICK STATUS
    ========================= -->

    <div class="quick-status">

        <h2>
            Quick Complaint Status
        </h2>

        <p>
            Enter your Complaint ID to check the current status.
        </p>


        <input
            type="text"
            placeholder="Enter Complaint ID to Track"
        >


        <button type="button">
            Track
        </button>

    </div>


</main>



<!-- =========================
     FOOTER
========================= -->

<footer class="home-footer">


    <div class="footer-left">

        <a href="#">
            Privacy Policy
        </a>

        <a href="#">
            Terms
        </a>

    </div>


    <p>
        © 2026 CampusVoice
    </p>


    <!-- ADMIN -->

    <a
        href="http://localhost/swayam/admin/login.php"
        class="hidden-admin"
    >
        Admin Login
    </a>


</footer>


</body>

</html>