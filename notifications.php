<?php

session_start();

require_once "../config/db.php";


/* =========================================================
   1. STUDENT LOGIN CHECK
========================================================= */

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit();
}

$student_id = (int) $_SESSION["student_id"];


/* =========================================================
   2. VERIFY STUDENT ACCOUNT
========================================================= */

$student_sql = "
    SELECT id, status
    FROM students
    WHERE id = ?
    LIMIT 1
";

$student_stmt = $conn->prepare($student_sql);

if (!$student_stmt) {
    die("Something went wrong.");
}

$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();

$student_result = $student_stmt->get_result();

if ($student_result->num_rows === 0) {

    $student_stmt->close();

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}

$student = $student_result->fetch_assoc();

$student_stmt->close();


/* =========================================================
   3. CHECK STUDENT APPROVAL
========================================================= */

if (
    !isset($student["status"]) ||
    strtolower(trim($student["status"])) !== "approved"
) {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}


/* =========================================================
   4. FETCH ONLY THIS STUDENT'S NOTIFICATIONS
========================================================= */

$sql = "
    SELECT
        id,
        message,
        created_at
    FROM notifications
    WHERE student_id = ?
    ORDER BY created_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Something went wrong.");
}

$stmt->bind_param("i", $student_id);
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

    <title>Notifications - Student</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #1f2937;
        }

        .container {
            width: 90%;
            max-width: 1000px;
            margin: 40px auto;
        }

        .header {
            background: #0f172a;
            color: white;
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .header h1 {
            font-size: 26px;
            margin-bottom: 6px;
        }

        .header p {
            color: #cbd5e1;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 18px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }

        .back-btn:hover {
            background: #1d4ed8;
        }

        .notification {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border-left: 5px solid #2563eb;
        }

        .notification h3 {
            margin-bottom: 8px;
            font-size: 18px;
        }

        .notification p {
            color: #475569;
            line-height: 1.5;
            margin-bottom: 10px;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .date {
            font-size: 13px;
            color: #64748b;
        }

        .empty {
            background: white;
            padding: 40px;
            text-align: center;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.06);
        }

        .empty h2 {
            margin-bottom: 8px;
        }

        .empty p {
            color: #64748b;
        }

        @media (max-width: 600px) {

            .container {
                width: 94%;
                margin: 25px auto;
            }

            .header {
                padding: 18px;
            }

            .header h1 {
                font-size: 22px;
            }

            .notification {
                padding: 16px;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <a
        href="dashboard.php"
        class="back-btn"
    >
        ← Back to Dashboard
    </a>


    <div class="header">

        <h1>🔔 Notifications</h1>

        <p>
            Updates related to your complaints
        </p>

    </div>


    <?php if ($result->num_rows > 0): ?>

        <?php while ($notification = $result->fetch_assoc()): ?>

            <div class="notification">

                <h3>
                    🔔 Notification
                </h3>

                <p>
                    <?php
                    echo htmlspecialchars(
                        $notification["message"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>
                </p>

                <div class="date">

                    <?php

                    $timestamp = strtotime(
                        $notification["created_at"]
                    );

                    if ($timestamp !== false) {

                        echo htmlspecialchars(
                            date(
                                "d M Y, h:i A",
                                $timestamp
                            ),
                            ENT_QUOTES,
                            "UTF-8"
                        );

                    } else {

                        echo "Date unavailable";

                    }

                    ?>

                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty">

            <h2>
                📭 No Notifications
            </h2>

            <p>
                You don't have any notifications yet.
            </p>

        </div>

    <?php endif; ?>


</div>

</body>

</html>

<?php

$stmt->close();
$conn->close();

?>