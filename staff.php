<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}

require_once "../config/db.php";


/* =========================
   STAFF SUMMARY
========================= */

$total_sql = "
    SELECT COUNT(*) AS total
    FROM staff
";

$total_result = $conn->query($total_sql);
$total_staff = $total_result->fetch_assoc()["total"];


$present_sql = "
    SELECT COUNT(*) AS total
    FROM staff
    WHERE status = 'Present'
";

$present_result = $conn->query($present_sql);
$present_staff = $present_result->fetch_assoc()["total"];


/*
 * Staff currently working:
 * staff having at least one active assignment
 */

$working_sql = "
    SELECT COUNT(DISTINCT staff_id) AS total
    FROM assignments
    WHERE status = 'In Progress'
";

$working_result = $conn->query($working_sql);
$working_staff = $working_result->fetch_assoc()["total"];


/* =========================
   STAFF LIST
========================= */

$staff_sql = "
    SELECT
        staff.id,
        staff.name,
        staff.staff_id,
        staff.department,
        staff.status,

        COUNT(
            CASE
                WHEN assignments.status IN ('Assigned', 'Accepted', 'In Progress')
                THEN assignments.id
            END
        ) AS active_works

    FROM staff

    LEFT JOIN assignments
        ON staff.id = assignments.staff_id

    GROUP BY
        staff.id,
        staff.name,
        staff.staff_id,
        staff.department,
        staff.status

    ORDER BY staff.name ASC
";

$staff_result = $conn->query($staff_sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Staff</title>

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
            max-width: 1200px;
            margin: auto;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #123c69;
            text-decoration: none;
            font-weight: bold;
        }

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

        .content {
            background: white;
            padding: 25px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
        }

        /* SUMMARY */

        .summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .summary-card {
            padding: 18px;
            border-radius: 9px;
            background: #f8fafc;
            border: 1px solid #e1e7ef;
        }

        .summary-card strong {
            display: block;
            color: #666;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .summary-card span {
            font-size: 25px;
            font-weight: bold;
            color: #123c69;
        }

        /* STAFF CARD */

        .staff-card {
            border: 1px solid #e1e7ef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 18px;
        }

        .staff-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .staff-name {
            font-size: 18px;
            font-weight: bold;
            color: #123c69;
        }

        .staff-id {
            color: #777;
            font-size: 13px;
            margin-top: 5px;
        }

        /* STATUS */

        .status {
            padding: 7px 13px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .present {
            background: #d1fae5;
            color: #065f46;
        }

        .absent {
            background: #fee2e2;
            color: #991b1b;
        }

        /* STAFF INFORMATION */

        .staff-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 18px;
        }

        .info {
            background: #f8fafc;
            padding: 13px;
            border-radius: 7px;
        }

        .info strong {
            display: block;
            color: #666;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .info span {
            color: #123c69;
            font-weight: bold;
        }

        /* ACTION */

        .actions {
            margin-top: 18px;
        }

        .details-btn {
            display: inline-block;
            padding: 10px 16px;
            background: #e8f0ff;
            color: #123c69;
            border-radius: 7px;
            text-decoration: none;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 50px 20px;
            color: #777;
        }

        .empty-icon {
            font-size: 45px;
            margin-bottom: 10px;
        }

        @media (max-width: 800px) {

            .summary {
                grid-template-columns: 1fr;
            }

            .staff-info {
                grid-template-columns: 1fr;
            }

            .staff-top {
                align-items: flex-start;
                flex-direction: column;
            }

        }

        @media (max-width: 600px) {

            .page {
                padding: 15px;
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

            <h1>Manage Staff</h1>

            <p>
                View staff availability and current workload.
            </p>

        </div>


        <div class="content">


            <!-- SUMMARY -->

            <div class="summary">


                <div class="summary-card">

                    <strong>
                        Total Staff
                    </strong>

                    <span>
                        <?php echo $total_staff; ?>
                    </span>

                </div>


                <div class="summary-card">

                    <strong>
                        Present
                    </strong>

                    <span>
                        <?php echo $present_staff; ?>
                    </span>

                </div>


                <div class="summary-card">

                    <strong>
                        Currently Working
                    </strong>

                    <span>
                        <?php echo $working_staff; ?>
                    </span>

                </div>


            </div>


            <!-- STAFF LIST -->

            <?php if ($staff_result->num_rows > 0): ?>


                <?php while ($staff = $staff_result->fetch_assoc()): ?>


                    <div class="staff-card">


                        <!-- STAFF TOP -->

                        <div class="staff-top">


                            <div>

                                <div class="staff-name">

                                    <?php echo htmlspecialchars(
                                        $staff["name"]
                                    ); ?>

                                </div>


                                <div class="staff-id">

                                    Staff ID:

                                    <?php echo htmlspecialchars(
                                        $staff["staff_id"]
                                    ); ?>

                                </div>

                            </div>


                            <?php

                            if ($staff["status"] === "Present") {

                                $status_class = "present";
                                $status_text = "🟢 Present";

                            } else {

                                $status_class = "absent";
                                $status_text = "🔴 Absent";

                            }

                            ?>


                            <span class="status <?php echo $status_class; ?>">

                                <?php echo $status_text; ?>

                            </span>


                        </div>


                        <!-- STAFF INFO -->

                        <div class="staff-info">


                            <div class="info">

                                <strong>
                                    Department
                                </strong>

                                <span>

                                    <?php echo htmlspecialchars(
                                        $staff["department"]
                                    ); ?>

                                </span>

                            </div>


                            <div class="info">

                                <strong>
                                    Current Active Works
                                </strong>

                                <span>

                                    <?php echo $staff["active_works"]; ?>

                                </span>

                            </div>


                            <div class="info">

                                <strong>
                                    Availability
                                </strong>

                                <span>

                                    <?php

                                    if ($staff["status"] === "Present") {

                                        if ($staff["active_works"] > 0) {

                                            echo "Working";

                                        } else {

                                            echo "Available";

                                        }

                                    } else {

                                        echo "Unavailable";

                                    }

                                    ?>

                                </span>

                            </div>


                        </div>


                        <!-- ACTION -->

                        <div class="actions">

                            <a
                                href="staff_details.php?id=<?php echo $staff["id"]; ?>"
                                class="details-btn"
                            >
                                View Staff Details
                            </a>

                        </div>


                    </div>


                <?php endwhile; ?>


            <?php else: ?>


                <div class="empty">

                    <div class="empty-icon">
                        👨‍💼
                    </div>

                    <h2>
                        No Staff Found
                    </h2>

                    <p>
                        Staff members will appear here
                        once they are added to the system.
                    </p>

                </div>


            <?php endif; ?>


        </div>

    </div>

</div>

</body>

</html>