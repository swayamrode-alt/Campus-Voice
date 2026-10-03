<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}

require_once "../config/db.php";


/* =========================
   CHECK STAFF ID
========================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid staff ID.");
}

$staff_id = (int) $_GET["id"];


/* =========================
   GET STAFF DETAILS
========================= */

$sql = "
    SELECT
        id,
        name,
        staff_id,
        department,
        status
    FROM staff
    WHERE id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Staff member not found.");
}

$staff = $result->fetch_assoc();

$stmt->close();


/* =========================
   WORK SUMMARY
========================= */

$total_works = 0;
$active_works = 0;
$completed_works = 0;


/* Total works */

$sql_total = "
    SELECT COUNT(*) AS total
    FROM assignments
    WHERE staff_id = ?
";

$stmt = $conn->prepare($sql_total);
$stmt->bind_param("i", $staff_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result) {
    $total_works = $result->fetch_assoc()["total"];
}

$stmt->close();


/* Active works */

$sql_active = "
    SELECT COUNT(*) AS total
    FROM assignments
    WHERE staff_id = ?
    AND status IN ('Assigned', 'Accepted', 'In Progress')
";

$stmt = $conn->prepare($sql_active);
$stmt->bind_param("i", $staff_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result) {
    $active_works = $result->fetch_assoc()["total"];
}

$stmt->close();


/* Completed works */

$sql_completed = "
    SELECT COUNT(*) AS total
    FROM assignments
    WHERE staff_id = ?
    AND status = 'Completed'
";

$stmt = $conn->prepare($sql_completed);
$stmt->bind_param("i", $staff_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result) {
    $completed_works = $result->fetch_assoc()["total"];
}

$stmt->close();


/* =========================
   GET ASSIGNED WORKS
========================= */

$works_sql = "
    SELECT
        assignments.id AS assignment_id,
        assignments.work_details,
        assignments.status AS work_status,
        assignments.assigned_at,
        assignments.completed_at,

        complaints.id AS complaint_id,
        complaints.title,
        complaints.category,
        complaints.location,
        complaints.priority

    FROM assignments

    INNER JOIN complaints
        ON assignments.complaint_id = complaints.id

    WHERE assignments.staff_id = ?

    ORDER BY assignments.assigned_at DESC
";

$works_stmt = $conn->prepare($works_sql);
$works_stmt->bind_param("i", $staff_id);
$works_stmt->execute();

$works_result = $works_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Staff Details</title>

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
    max-width: 1100px;
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
    padding: 30px;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.07);
}


/* STAFF PROFILE */

.profile {
    background: #f8fafc;
    padding: 22px;
    border-radius: 10px;
    margin-bottom: 25px;
}

.profile-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.profile-name {
    font-size: 24px;
    font-weight: bold;
    color: #123c69;
}

.profile-id {
    color: #777;
    margin-top: 5px;
}

.status {
    display: inline-block;
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


/* INFO */

.info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-top: 20px;
}

.info {
    background: white;
    padding: 14px;
    border-radius: 8px;
    border: 1px solid #e1e7ef;
}

.info strong {
    display: block;
    color: #666;
    font-size: 12px;
    margin-bottom: 6px;
}

.info span {
    color: #123c69;
    font-weight: bold;
}


/* SUMMARY */

.summary {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 30px;
}

.summary-card {
    background: white;
    border: 1px solid #e1e7ef;
    padding: 18px;
    border-radius: 9px;
}

.summary-card strong {
    display: block;
    color: #666;
    font-size: 13px;
    margin-bottom: 8px;
}

.summary-card span {
    font-size: 27px;
    font-weight: bold;
    color: #123c69;
}


/* SECTION */

.section {
    margin-top: 30px;
}

.section h2 {
    color: #123c69;
    margin-bottom: 15px;
}


/* WORK CARD */

.work-card {
    border: 1px solid #e1e7ef;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 15px;
}

.work-top {
    display: flex;
    justify-content: space-between;
    gap: 15px;
}

.work-title {
    font-size: 18px;
    font-weight: bold;
    color: #123c69;
}

.complaint-id {
    font-size: 12px;
    color: #777;
    margin-top: 5px;
}

.work-info {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-top: 18px;
}

.work-info-box {
    background: #f8fafc;
    padding: 12px;
    border-radius: 7px;
}

.work-info-box strong {
    display: block;
    color: #666;
    font-size: 12px;
    margin-bottom: 5px;
}

.work-info-box span {
    color: #123c69;
    font-weight: bold;
}

.work-details {
    margin-top: 15px;
    background: #f8fafc;
    padding: 14px;
    border-radius: 7px;
    color: #444;
    line-height: 1.5;
}


/* WORK STATUS */

.work-status {
    display: inline-block;
    padding: 6px 11px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.status-assigned {
    background: #dbeafe;
    color: #1e40af;
}

.status-accepted {
    background: #e0f2fe;
    color: #0369a1;
}

.status-progress {
    background: #e0e7ff;
    color: #3730a3;
}

.status-completed {
    background: #d1fae5;
    color: #065f46;
}

.status-rejected {
    background: #fee2e2;
    color: #991b1b;
}


/* EMPTY */

.empty {
    text-align: center;
    padding: 45px 20px;
    color: #777;
    background: #f8fafc;
    border-radius: 9px;
}


/* BOTTOM */

.bottom-actions {
    margin-top: 25px;
}

.back-btn {
    display: inline-block;
    padding: 11px 17px;
    background: #e8f0ff;
    color: #123c69;
    border-radius: 7px;
    text-decoration: none;
    font-weight: bold;
}


/* MOBILE */

@media (max-width: 800px) {

    .summary {
        grid-template-columns: 1fr;
    }

    .info-grid,
    .work-info {
        grid-template-columns: 1fr;
    }

    .profile-top,
    .work-top {
        flex-direction: column;
        align-items: flex-start;
    }

}

@media (max-width: 600px) {

    .page {
        padding: 15px;
    }

    .content {
        padding: 20px;
    }

}

</style>

</head>


<body>

<div class="page">

<div class="container">


<a href="staff.php" class="back">
    ← Back to Staff
</a>


<div class="header">

    <h1>Staff Details</h1>

    <p>
        View staff information and assigned work.
    </p>

</div>


<div class="content">


<!-- STAFF PROFILE -->

<div class="profile">


<div class="profile-top">


<div>

    <div class="profile-name">

        <?php echo htmlspecialchars(
            $staff["name"]
        ); ?>

    </div>

    <div class="profile-id">

        Staff ID:

        <?php echo htmlspecialchars(
            $staff["staff_id"]
        ); ?>

    </div>

</div>


<?php if ($staff["status"] === "Present"): ?>

    <span class="status present">
        🟢 Present
    </span>

<?php else: ?>

    <span class="status absent">
        🔴 Absent
    </span>

<?php endif; ?>


</div>


<div class="info-grid">


<div class="info">

    <strong>Department</strong>

    <span>
        <?php echo htmlspecialchars(
            $staff["department"]
        ); ?>
    </span>

</div>


<div class="info">

    <strong>Staff Status</strong>

    <span>
        <?php echo htmlspecialchars(
            $staff["status"]
        ); ?>
    </span>

</div>


<div class="info">

    <strong>Staff Database ID</strong>

    <span>
        <?php echo $staff["id"]; ?>
    </span>

</div>


</div>


</div>


<!-- WORK SUMMARY -->

<div class="summary">


<div class="summary-card">

    <strong>Total Assigned Works</strong>

    <span>
        <?php echo $total_works; ?>
    </span>

</div>


<div class="summary-card">

    <strong>Active Works</strong>

    <span>
        <?php echo $active_works; ?>
    </span>

</div>


<div class="summary-card">

    <strong>Completed Works</strong>

    <span>
        <?php echo $completed_works; ?>
    </span>

</div>


</div>


<!-- ASSIGNED WORK -->

<div class="section">

<h2>Assigned Work</h2>


<?php if ($works_result->num_rows > 0): ?>


<?php while ($work = $works_result->fetch_assoc()): ?>


<?php

$work_status = $work["work_status"];

if ($work_status === "Assigned") {
    $work_class = "status-assigned";
} elseif ($work_status === "Accepted") {
    $work_class = "status-accepted";
} elseif ($work_status === "In Progress") {
    $work_class = "status-progress";
} elseif ($work_status === "Completed") {
    $work_class = "status-completed";
} else {
    $work_class = "status-rejected";
}

?>


<div class="work-card">


<div class="work-top">


<div>

    <div class="work-title">

        <?php echo htmlspecialchars(
            $work["title"]
        ); ?>

    </div>

    <div class="complaint-id">

        Complaint ID:

        CMP-<?php echo str_pad(
            $work["complaint_id"],
            3,
            "0",
            STR_PAD_LEFT
        ); ?>

    </div>

</div>


<span class="work-status <?php echo $work_class; ?>">

    <?php echo htmlspecialchars(
        $work_status
    ); ?>

</span>


</div>


<div class="work-info">


<div class="work-info-box">

    <strong>Category</strong>

    <span>
        <?php echo htmlspecialchars(
            $work["category"]
        ); ?>
    </span>

</div>


<div class="work-info-box">

    <strong>Location</strong>

    <span>
        <?php echo htmlspecialchars(
            $work["location"]
        ); ?>
    </span>

</div>


<div class="work-info-box">

    <strong>Priority</strong>

    <span>
        <?php echo htmlspecialchars(
            $work["priority"]
        ); ?>
    </span>

</div>


</div>


<div class="work-details">

    <strong>Work Details:</strong>

    <br><br>

    <?php echo nl2br(
        htmlspecialchars(
            $work["work_details"]
        )
    ); ?>

</div>


<div class="work-info">


<div class="work-info-box">

    <strong>Assigned On</strong>

    <span>

        <?php echo date(
            "d M Y, h:i A",
            strtotime(
                $work["assigned_at"]
            )
        ); ?>

    </span>

</div>


<div class="work-info-box">

    <strong>Completed On</strong>

    <span>

        <?php

        if (!empty($work["completed_at"])) {

            echo date(
                "d M Y, h:i A",
                strtotime(
                    $work["completed_at"]
                )
            );

        } else {

            echo "Not completed";

        }

        ?>

    </span>

</div>


</div>


</div>


<?php endwhile; ?>


<?php else: ?>


<div class="empty">

    <h3>No Assigned Work</h3>

    <p>
        This staff member currently has no assigned complaints.
    </p>

</div>


<?php endif; ?>


</div>


<!-- BOTTOM -->

<div class="bottom-actions">

<a
    href="staff.php"
    class="back-btn"
>
    ← Back to Staff
</a>

</div>


</div>

</div>

</div>


</body>

</html>