<?php

session_start();

if (!isset($_SESSION["staff_id"])) {
    header("Location: login.php");
    exit();
}

require_once "../config/db.php";

$staff_id = (int) $_SESSION["staff_id"];

/*
|--------------------------------------------------------------------------
| Fetch logged-in staff details
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        name,
        staff_id,
        username,
        department,
        status,
        approval_status
    FROM staff
    WHERE id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error.");
}

$stmt->bind_param("i", $staff_id);
$stmt->execute();

$result = $stmt->get_result();
$staff = $result->fetch_assoc();

$stmt->close();

if (!$staff) {
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Approval check
|--------------------------------------------------------------------------
*/

if (strtolower(trim($staff["approval_status"])) !== "approved") {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Refresh session information
|--------------------------------------------------------------------------
*/

$_SESSION["staff_name"] = $staff["name"];
$_SESSION["staff_code"] = $staff["staff_id"];
$_SESSION["staff_department"] = $staff["department"];

/*
|--------------------------------------------------------------------------
| Safe output helper
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}


/*
|--------------------------------------------------------------------------
| Availability
|--------------------------------------------------------------------------
*/

$availability = strtolower(trim($staff["status"]));

if ($availability === "present") {

    $availability_text = "Available";
    $availability_icon = "🟢";

} elseif ($availability === "absent") {

    $availability_text = "Absent";
    $availability_icon = "🔴";

} else {

    $availability_text = ucfirst($availability);
    $availability_icon = "🟡";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Staff Profile</title>

    <link rel="stylesheet"
          href="../assets/css/style.css">

    <style>

        .profile-page {
            min-height: 100vh;
            background: #f4f7fb;
            padding: 30px 20px;
        }

        .profile-container {
            max-width: 800px;
            margin: auto;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #123c69;
            text-decoration: none;
            font-weight: bold;
        }

        .profile-header {
            background: #123c69;
            color: white;
            padding: 25px;
            border-radius: 12px 12px 0 0;
        }

        .profile-header h1 {
            margin: 0 0 8px;
        }

        .profile-header p {
            margin: 0;
            opacity: 0.9;
        }

        .profile-box {
            background: white;
            padding: 30px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .profile-top {
            text-align: center;
            padding-bottom: 25px;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 25px;
        }

        .profile-icon {
            width: 90px;
            height: 90px;
            margin: auto;
            border-radius: 50%;
            background: #e8f0ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 45px;
        }

        .profile-top h2 {
            color: #123c69;
            margin: 12px 0 5px;
        }

        .profile-top p {
            color: #666;
            margin: 0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .info-item {
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
        }

        .info-item strong {
            display: block;
            color: #666;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .info-item span {
            color: #123c69;
            font-weight: bold;
        }

        .available {
            color: #137333 !important;
        }

        .absent {
            color: #c5221f !important;
        }

        .pending {
            color: #b06000 !important;
        }

        .approved {
            color: #137333 !important;
        }

        .note {
            margin-top: 25px;
            padding: 14px;
            background: #eef6ff;
            color: #315477;
            border-radius: 7px;
            font-size: 13px;
        }

        .profile-actions {
            margin-top: 25px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .action-btn {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 7px;
            text-decoration: none;
            font-weight: bold;
        }

        .dashboard-btn {
            background: #123c69;
            color: white;
        }

        .notification-btn {
            background: #eef6ff;
            color: #123c69;
        }

        @media (max-width: 650px) {

            .info-grid {
                grid-template-columns: 1fr;
            }

            .profile-page {
                padding: 15px;
            }

            .profile-box {
                padding: 20px;
            }

        }

    </style>

</head>

<body>

<div class="profile-page">

    <div class="profile-container">

        <a href="dashboard.php" class="back-link">
            ← Back to Dashboard
        </a>

        <div class="profile-header">

            <h1>Staff Profile</h1>

            <p>
                View your staff account information.
            </p>

        </div>


        <div class="profile-box">


            <!-- Profile Top -->

            <div class="profile-top">

                <div class="profile-icon">
                    👨‍💼
                </div>

                <h2>
                    <?= e($staff["name"]) ?>
                </h2>

                <p>
                    Staff ID:
                    <?= e($staff["staff_id"]) ?>
                </p>

            </div>


            <!-- Staff Information -->

            <div class="info-grid">


                <div class="info-item">

                    <strong>
                        Full Name
                    </strong>

                    <span>
                        <?= e($staff["name"]) ?>
                    </span>

                </div>


                <div class="info-item">

                    <strong>
                        Staff ID
                    </strong>

                    <span>
                        <?= e($staff["staff_id"]) ?>
                    </span>

                </div>


                <div class="info-item">

                    <strong>
                        Username
                    </strong>

                    <span>
                        <?= e($staff["username"]) ?>
                    </span>

                </div>


                <div class="info-item">

                    <strong>
                        Department
                    </strong>

                    <span>
                        <?= e($staff["department"]) ?>
                    </span>

                </div>


                <div class="info-item">

                    <strong>
                        Availability
                    </strong>

                    <span
                        class="<?= $availability_text === 'Available'
                            ? 'available'
                            : ($availability_text === 'Absent'
                                ? 'absent'
                                : '') ?>"
                    >

                        <?= $availability_icon ?>
                        <?= e($availability_text) ?>

                    </span>

                </div>


                <div class="info-item">

                    <strong>
                        Account Approval
                    </strong>

                    <span class="approved">

                        🟢
                        <?= e(ucfirst($staff["approval_status"])) ?>

                    </span>

                </div>


            </div>


            <!-- Note -->

            <div class="note">

                ℹ️ Staff account information and availability
                are managed by the Admin. Detailed staff
                performance and student ratings are visible
                only to the Admin.

            </div>


            <!-- Actions -->

            <div class="profile-actions">

                <a href="dashboard.php"
                   class="action-btn dashboard-btn">

                    📊 Dashboard

                </a>

                <a href="notifications.php"
                   class="action-btn notification-btn">

                    🔔 Notifications

                </a>

            </div>


        </div>

    </div>

</div>

</body>

</html>