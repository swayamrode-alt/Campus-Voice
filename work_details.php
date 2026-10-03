<?php

session_start();

if (!isset($_SESSION["staff_id"])) {
    header("Location: login.php");
    exit();
}

require_once "../config/db.php";

$staff_id = $_SESSION["staff_id"];

$sql = "
    SELECT
        assignments.*,
        complaints.title,
        complaints.description,
        complaints.category,
        complaints.location,
        complaints.priority,
        complaints.status AS complaint_status,
        students.name AS student_name,
        students.roll_no
    FROM assignments

    INNER JOIN complaints
        ON assignments.complaint_id = complaints.id

    INNER JOIN students
        ON complaints.student_id = students.id

    WHERE assignments.staff_id = ?

    ORDER BY assignments.assigned_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Work</title>

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
            max-width: 1000px;
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
        }

        .content {
            background: white;
            padding: 25px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
        }

        .work-card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 22px;
            margin-bottom: 20px;
        }

        .work-title {
            color: #123c69;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            margin-bottom: 20px;
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

        .section {
            margin-top: 18px;
        }

        .section h3 {
            color: #123c69;
            margin-bottom: 8px;
        }

        .description {
            background: #f8fafc;
            padding: 15px;
            border-radius: 7px;
            line-height: 1.6;
            color: #444;
        }

        .work-details {
            background: #eef6ff;
            padding: 15px;
            border-radius: 7px;
            line-height: 1.6;
        }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .assigned {
            background: #dbeafe;
            color: #1e40af;
        }

        .accepted {
            background: #fef3c7;
            color: #92400e;
        }

        .in-progress {
            background: #e0e7ff;
            color: #3730a3;
        }

        .completed {
            background: #d1fae5;
            color: #065f46;
        }

        .rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .work-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-btn {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 7px;
            text-decoration: none;
            font-weight: bold;
            font-size: 13px;
        }

        .accept {
            background: #d1fae5;
            color: #065f46;
        }

        .reject {
            background: #fee2e2;
            color: #991b1b;
        }

        .progress-btn {
            background: #dbeafe;
            color: #1e40af;
        }

        .complete {
            background: #d1fae5;
            color: #065f46;
        }

        .completed-text {
            color: #065f46;
            font-weight: bold;
        }

        .rejected-text {
            color: #991b1b;
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

        @media (max-width: 650px) {

            .page {
                padding: 15px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<div class="page">

    <div class="container">

        <a href="dashboard.php" class="back">
            ← Back to Dashboard
        </a>


        <div class="header">

            <h1>My Assigned Work</h1>

            <p>
                Welcome,
                <?php echo htmlspecialchars($_SESSION["staff_name"]); ?>
            </p>

        </div>


        <div class="content">

            <?php if ($result->num_rows > 0): ?>

                <?php while ($work = $result->fetch_assoc()): ?>

                    <?php

                    $status_class = strtolower(
                        str_replace(
                            " ",
                            "-",
                            $work["status"]
                        )
                    );

                    ?>

                    <div class="work-card">

                        <div class="work-title">

                            CMP-<?php echo str_pad(
                                $work["complaint_id"],
                                3,
                                "0",
                                STR_PAD_LEFT
                            ); ?>

                            —
                            <?php echo htmlspecialchars(
                                $work["title"]
                            ); ?>

                        </div>


                        <div class="info-grid">

                            <div class="info">

                                <strong>Student</strong>

                                <span>
                                    <?php echo htmlspecialchars(
                                        $work["student_name"]
                                    ); ?>
                                </span>

                            </div>


                            <div class="info">

                                <strong>Roll Number</strong>

                                <span>
                                    <?php echo htmlspecialchars(
                                        $work["roll_no"]
                                    ); ?>
                                </span>

                            </div>


                            <div class="info">

                                <strong>Category</strong>

                                <span>
                                    <?php echo htmlspecialchars(
                                        $work["category"]
                                    ); ?>
                                </span>

                            </div>


                            <div class="info">

                                <strong>Location</strong>

                                <span>
                                    <?php echo htmlspecialchars(
                                        $work["location"]
                                    ); ?>
                                </span>

                            </div>


                            <div class="info">

                                <strong>Priority</strong>

                                <span>
                                    <?php echo htmlspecialchars(
                                        $work["priority"]
                                    ); ?>
                                </span>

                            </div>


                            <div class="info">

                                <strong>Work Status</strong>

                                <span>

                                    <span class="status <?php echo $status_class; ?>">

                                        <?php echo htmlspecialchars(
                                            $work["status"]
                                        ); ?>

                                    </span>

                                </span>

                            </div>

                        </div>


                        <div class="section">

                            <h3>Complaint Description</h3>

                            <div class="description">

                                <?php echo nl2br(
                                    htmlspecialchars(
                                        $work["description"]
                                    )
                                ); ?>

                            </div>

                        </div>


                        <div class="section">

                            <h3>Work Assigned by Admin</h3>

                            <div class="work-details">

                                <?php echo nl2br(
                                    htmlspecialchars(
                                        $work["work_details"]
                                    )
                                ); ?>

                            </div>

                        </div>


                        <div class="section">

                            <strong>Assigned On:</strong>

                            <?php echo date(
                                "d M Y, h:i A",
                                strtotime(
                                    $work["assigned_at"]
                                )
                            ); ?>

                        </div>


                        <!-- WORK ACTIONS -->

                        <div class="work-actions">

                            <?php if ($work["status"] === "Assigned"): ?>

                                <a
                                    href="update_work.php?id=<?php echo $work["id"]; ?>&status=Accepted"
                                    class="action-btn accept"
                                >
                                    ✅ Accept Work
                                </a>

                                <a
                                    href="update_work.php?id=<?php echo $work["id"]; ?>&status=Rejected"
                                    class="action-btn reject"
                                >
                                    ❌ Reject Work
                                </a>


                            <?php elseif ($work["status"] === "Accepted"): ?>

                                <a
                                    href="update_work.php?id=<?php echo $work["id"]; ?>&status=In%20Progress"
                                    class="action-btn progress-btn"
                                >
                                    🔄 Start Work
                                </a>


                            <?php elseif ($work["status"] === "In Progress"): ?>

                                <a
                                    href="update_work.php?id=<?php echo $work["id"]; ?>&status=Completed"
                                    class="action-btn complete"
                                >
                                    ✔️ Mark Completed
                                </a>


                            <?php elseif ($work["status"] === "Completed"): ?>

                                <span class="completed-text">
                                    ✔️ Work Completed
                                </span>


                            <?php elseif ($work["status"] === "Rejected"): ?>

                                <span class="rejected-text">
                                    ❌ Work Rejected
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endwhile; ?>


            <?php else: ?>

                <div class="empty">

                    <div class="empty-icon">
                        📋
                    </div>

                    <h2>No Assigned Work</h2>

                    <p>
                        You currently have no complaints assigned to you.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

</body>

</html>