<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}

require_once "../config/db.php";


/* =========================================================
   OVERALL STAFF RATINGS
========================================================= */

$summary_sql = "
    SELECT
        s.id AS staff_id,
        s.name AS staff_name,
        s.staff_id AS staff_code,
        s.department,

        COUNT(r.id) AS total_ratings,

        ROUND(AVG(r.rating), 1) AS average_rating

    FROM staff s

    LEFT JOIN ratings r
        ON r.staff_id = s.id

    GROUP BY
        s.id,
        s.name,
        s.staff_id,
        s.department

    ORDER BY
        average_rating DESC,
        s.name ASC
";

$summary_result = $conn->query($summary_sql);


/* =========================================================
   INDIVIDUAL RATINGS
========================================================= */

$sql = "
    SELECT
        r.id,
        r.complaint_id,
        r.rating,
        r.feedback,
        r.created_at,

        s.name AS staff_name,
        s.staff_id AS staff_code,

        st.name AS student_name

    FROM ratings r

    INNER JOIN staff s
        ON r.staff_id = s.id

    INNER JOIN students st
        ON r.student_id = st.id

    ORDER BY r.created_at DESC
";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Staff Ratings</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
        }

        .page {
            min-height: 100vh;
            padding: 25px;
        }

        .container {
            max-width: 1150px;
            margin: auto;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #123c69;
            text-decoration: none;
            font-weight: bold;
        }

        /* =========================
           HEADER
        ========================= */

        .header {
            background: #123c69;
            color: white;
            padding: 25px;
            border-radius: 12px 12px 0 0;
        }

        .header h1 {
            margin: 0 0 8px;
        }

        .header p {
            margin: 0;
            opacity: 0.9;
        }

        /* =========================
           CONTENT
        ========================= */

        .content {
            background: white;
            padding: 25px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
        }

        /* =========================
           SECTION TITLE
        ========================= */

        .section-title {
            color: #123c69;
            margin: 0 0 18px;
        }

        /* =========================
           STAFF SUMMARY
        ========================= */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            margin-bottom: 35px;
        }

        .summary-card {
            border: 1px solid #e1e7ef;
            border-radius: 10px;
            padding: 20px;
            background: #ffffff;
        }

        .summary-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .staff-name {
            color: #123c69;
            font-size: 19px;
            font-weight: bold;
        }

        .staff-id {
            color: #777;
            font-size: 13px;
            margin-top: 5px;
        }

        .department {
            color: #555;
            font-size: 13px;
            margin-top: 4px;
        }

        .average {
            font-size: 28px;
            font-weight: bold;
            color: #f59e0b;
            white-space: nowrap;
        }

        .average small {
            font-size: 14px;
            color: #777;
            font-weight: normal;
        }

        .summary-bottom {
            margin-top: 18px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        .rating-stars {
            color: #f59e0b;
            font-size: 22px;
            margin-bottom: 5px;
        }

        .total-ratings {
            color: #666;
            font-size: 13px;
        }

        .no-rating {
            color: #999;
            font-size: 14px;
        }

        /* =========================
           INDIVIDUAL RATINGS
        ========================= */

        .individual-section {
            margin-top: 10px;
        }

        .rating-card {
            border: 1px solid #e1e7ef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 18px;
        }

        .top {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            align-items: center;
        }

        .stars {
            color: #f59e0b;
            font-size: 21px;
            white-space: nowrap;
        }

        .info {
            margin-top: 15px;
            background: #f8fafc;
            padding: 13px;
            border-radius: 7px;
        }

        .info strong {
            color: #666;
            font-size: 12px;
            display: block;
            margin-bottom: 5px;
        }

        .info span {
            color: #123c69;
            font-weight: bold;
        }

        .feedback {
            margin-top: 15px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 7px;
            color: #444;
            line-height: 1.5;
        }

        .date {
            color: #777;
            font-size: 12px;
            margin-top: 10px;
        }

        /* =========================
           EMPTY
        ========================= */

        .empty {
            text-align: center;
            padding: 50px 20px;
            color: #777;
        }

        .empty-icon {
            font-size: 45px;
        }

        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 750px) {

            .summary-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 650px) {

            .page {
                padding: 15px;
            }

            .content {
                padding: 18px;
            }

            .top {
                flex-direction: column;
                align-items: flex-start;
            }

            .summary-top {
                flex-direction: column;
                align-items: flex-start;
            }

            .average {
                font-size: 24px;
            }

        }

    </style>

</head>


<body>

<div class="page">

    <div class="container">


        <!-- BACK -->

        <a
            href="dashboard.php"
            class="back"
        >
            ← Back to Dashboard
        </a>


        <!-- HEADER -->

        <div class="header">

            <h1>
                ⭐ Staff Ratings
            </h1>

            <p>
                View overall staff performance and student feedback.
            </p>

        </div>


        <div class="content">


            <!-- =================================================
                 OVERALL STAFF RATINGS
            ================================================== -->

            <h2 class="section-title">
                ⭐ Overall Staff Ratings
            </h2>


            <?php if (
                $summary_result &&
                $summary_result->num_rows > 0
            ): ?>


                <div class="summary-grid">


                    <?php while (
                        $staff = $summary_result->fetch_assoc()
                    ): ?>


                        <div class="summary-card">


                            <div class="summary-top">


                                <div>

                                    <div class="staff-name">

                                        👨‍🔧
                                        <?php echo htmlspecialchars(
                                            $staff["staff_name"]
                                        ); ?>

                                    </div>


                                    <div class="staff-id">

                                        Staff ID:
                                        <?php echo htmlspecialchars(
                                            $staff["staff_code"]
                                        ); ?>

                                    </div>


                                    <div class="department">

                                        Department:
                                        <?php echo htmlspecialchars(
                                            $staff["department"]
                                        ); ?>

                                    </div>

                                </div>


                                <?php if (
                                    $staff["total_ratings"] > 0
                                ): ?>

                                    <div class="average">

                                        <?php echo number_format(
                                            (float) $staff["average_rating"],
                                            1
                                        ); ?>

                                        <small>/ 5</small>

                                    </div>

                                <?php else: ?>

                                    <div class="average">

                                        —

                                    </div>

                                <?php endif; ?>


                            </div>


                            <div class="summary-bottom">


                                <?php if (
                                    $staff["total_ratings"] > 0
                                ): ?>


                                    <div class="rating-stars">

                                        <?php

                                        $average =
                                            (float) $staff["average_rating"];

                                        /*
                                         * Show rounded average stars
                                         */

                                        $rounded =
                                            round($average);

                                        for (
                                            $i = 1;
                                            $i <= 5;
                                            $i++
                                        ) {

                                            echo ($i <= $rounded)
                                                ? "★"
                                                : "☆";

                                        }

                                        ?>

                                    </div>


                                    <div class="total-ratings">

                                        Based on

                                        <strong>
                                            <?php echo $staff["total_ratings"]; ?>
                                        </strong>

                                        rating<?php
                                        echo (
                                            $staff["total_ratings"] == 1
                                            ? ""
                                            : "s"
                                        );
                                        ?>

                                    </div>


                                <?php else: ?>


                                    <div class="no-rating">

                                        No ratings received yet.

                                    </div>


                                <?php endif; ?>


                            </div>


                        </div>


                    <?php endwhile; ?>


                </div>


            <?php else: ?>


                <div class="empty">

                    <div class="empty-icon">
                        ⭐
                    </div>

                    <h2>
                        No Staff Found
                    </h2>

                </div>


            <?php endif; ?>



            <!-- =================================================
                 INDIVIDUAL RATINGS
            ================================================== -->

            <div class="individual-section">


                <h2 class="section-title">

                    📋 Individual Ratings & Feedback

                </h2>


                <?php if (
                    $result &&
                    $result->num_rows > 0
                ): ?>


                    <?php while (
                        $row = $result->fetch_assoc()
                    ): ?>


                        <div class="rating-card">


                            <div class="top">


                                <div>

                                    <div class="staff-name">

                                        <?php echo htmlspecialchars(
                                            $row["staff_name"]
                                        ); ?>

                                    </div>


                                    <div class="staff-id">

                                        Staff ID:
                                        <?php echo htmlspecialchars(
                                            $row["staff_code"]
                                        ); ?>

                                    </div>

                                </div>


                                <div class="stars">

                                    <?php

                                    $rating =
                                        (int) $row["rating"];

                                    for (
                                        $i = 1;
                                        $i <= 5;
                                        $i++
                                    ) {

                                        echo ($i <= $rating)
                                            ? "★"
                                            : "☆";

                                    }

                                    ?>

                                </div>


                            </div>


                            <!-- STUDENT -->

                            <div class="info">

                                <strong>
                                    Student
                                </strong>

                                <span>

                                    <?php echo htmlspecialchars(
                                        $row["student_name"]
                                    ); ?>

                                </span>

                            </div>


                            <!-- COMPLAINT -->

                            <div class="info">

                                <strong>
                                    Complaint ID
                                </strong>

                                <span>

                                    CMP-<?php echo str_pad(
                                        $row["complaint_id"],
                                        3,
                                        "0",
                                        STR_PAD_LEFT
                                    ); ?>

                                </span>

                            </div>


                            <!-- RATING -->

                            <div class="info">

                                <strong>
                                    Rating
                                </strong>

                                <span>

                                    <?php echo $rating; ?>/5

                                </span>

                            </div>


                            <!-- FEEDBACK -->

                            <?php if (
                                !empty($row["feedback"])
                            ): ?>


                                <div class="feedback">

                                    <strong>
                                        💬 Feedback
                                    </strong>

                                    <br><br>

                                    <?php echo nl2br(
                                        htmlspecialchars(
                                            $row["feedback"]
                                        )
                                    ); ?>

                                </div>


                            <?php else: ?>


                                <div class="feedback">

                                    <strong>
                                        💬 Feedback
                                    </strong>

                                    <br><br>

                                    No feedback provided.

                                </div>


                            <?php endif; ?>


                            <!-- DATE -->

                            <div class="date">

                                Rated on:

                                <?php echo date(
                                    "d M Y, h:i A",
                                    strtotime(
                                        $row["created_at"]
                                    )
                                ); ?>

                            </div>


                        </div>


                    <?php endwhile; ?>


                <?php else: ?>


                    <div class="empty">

                        <div class="empty-icon">
                            💬
                        </div>

                        <h2>
                            No Individual Ratings Yet
                        </h2>

                        <p>
                            Student ratings will appear here
                            after completed complaints are rated.
                        </p>

                    </div>


                <?php endif; ?>


            </div>


        </div>

    </div>

</div>


</body>

</html>


<?php

if (isset($conn)) {
    $conn->close();
}

?>