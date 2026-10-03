<?php

session_start();

require_once "../config/db.php";


/* =========================
   ADMIN SESSION SECURITY
========================= */

if (
    !isset($_SESSION["admin_id"]) ||
    !is_numeric($_SESSION["admin_id"])
) {
    header("Location: login.php");
    exit();
}

$admin_id = (int) $_SESSION["admin_id"];


/* =========================
   VERIFY ADMIN EXISTS
========================= */

$admin_sql = "
    SELECT id
    FROM admins
    WHERE id = ?
    LIMIT 1
";

$admin_stmt = $conn->prepare($admin_sql);

if (!$admin_stmt) {
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}

$admin_stmt->bind_param("i", $admin_id);
$admin_stmt->execute();

$admin_result = $admin_stmt->get_result();

if ($admin_result->num_rows !== 1) {

    $admin_stmt->close();

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}

$admin_stmt->close();


/* =========================
   CSRF TOKEN
========================= */

if (
    !isset($_SESSION["admin_csrf_token"]) ||
    !is_string($_SESSION["admin_csrf_token"]) ||
    strlen($_SESSION["admin_csrf_token"]) < 32
) {
    $_SESSION["admin_csrf_token"] =
        bin2hex(random_bytes(32));
}


/* =========================
   CHECK COMPLAINT ID
========================= */

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"]) ||
    (int)$_GET["id"] <= 0
) {
    header("Location: complaints.php");
    exit();
}

$complaint_id = (int) $_GET["id"];


/* =========================
   GET COMPLAINT
========================= */

$sql = "
    SELECT
        complaints.*,
        students.name AS student_name,
        students.roll_no,
        students.mobile AS student_mobile
    FROM complaints

    INNER JOIN students
        ON complaints.student_id = students.id

    WHERE complaints.id = ?

    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    header("Location: complaints.php");
    exit();
}

$stmt->bind_param("i", $complaint_id);
$stmt->execute();

$result = $stmt->get_result();

if (!$result || $result->num_rows !== 1) {

    $stmt->close();

    header("Location: complaints.php");
    exit();
}

$complaint = $result->fetch_assoc();

$stmt->close();


/* =========================
   GET ASSIGNMENT
========================= */

$assignment = null;

$sql2 = "
    SELECT
        assignments.*,
        staff.name AS staff_name,
        staff.staff_id AS staff_code,
        staff.department AS staff_department
    FROM assignments

    LEFT JOIN staff
        ON assignments.staff_id = staff.id

    WHERE assignments.complaint_id = ?

    ORDER BY assignments.id DESC

    LIMIT 1
";

$stmt2 = $conn->prepare($sql2);

if ($stmt2) {

    $stmt2->bind_param(
        "i",
        $complaint_id
    );

    $stmt2->execute();

    $assignment_result =
        $stmt2->get_result();

    if (
        $assignment_result &&
        $assignment_result->num_rows > 0
    ) {
        $assignment =
            $assignment_result->fetch_assoc();
    }

    $stmt2->close();
}


/* =========================
   PHOTO PATH FUNCTION
========================= */

function photoPath($photo)
{
    if (
        !is_string($photo) ||
        trim($photo) === ""
    ) {
        return "";
    }

    $photo = str_replace(
        "\\",
        "/",
        trim($photo)
    );

    /*
     * Remove dangerous path traversal
     */
    $photo = str_replace(
        ["../", "..\\"],
        "",
        $photo
    );

    /*
     * If DB contains uploads/file.jpg
     */
    if (
        strpos(
            $photo,
            "uploads/"
        ) === 0
    ) {
        return "../" . $photo;
    }

    /*
     * If DB contains only filename
     */
    return "../uploads/" . basename($photo);
}


/* =========================
   STATUS
========================= */

$status =
    $complaint["status"] ?? "Pending";

$status_class =
    strtolower(
        str_replace(
            " ",
            "-",
            (string)$status
        )
    );

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Complaint Details</title>

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
    max-width: 900px;
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
    opacity: .9;
}

.content {
    background: white;
    padding: 30px;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,.07);
}

.title {
    color: #123c69;
    font-size: 22px;
    font-weight: bold;
    margin-bottom: 20px;
}

.section {
    margin-top: 30px;
}

.section h2 {
    color: #123c69;
    margin-bottom: 15px;
    font-size: 19px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2,1fr);
    gap: 15px;
}

.info {
    background: #f8fafc;
    padding: 15px;
    border-radius: 8px;
}

.info strong {
    display: block;
    color: #666;
    font-size: 13px;
    margin-bottom: 6px;
}

.info span {
    color: #123c69;
    font-weight: bold;
}

.description {
    background: #f8fafc;
    padding: 18px;
    border-radius: 8px;
    color: #444;
    line-height: 1.6;
}

.status {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.pending {
    background: #fff3cd;
    color: #856404;
}

.assigned {
    background: #dbeafe;
    color: #1e40af;
}

.in-progress {
    background: #e0e7ff;
    color: #3730a3;
}

.resolved {
    background: #d1fae5;
    color: #065f46;
}

.rejected {
    background: #fee2e2;
    color: #991b1b;
}

.assignment-box {
    background: #eef6ff;
    padding: 18px;
    border-radius: 8px;
}


/* PHOTOS */

.photos {
    display: grid;
    grid-template-columns: repeat(2,1fr);
    gap: 18px;
}

.photo-box {
    background: #f8fafc;
    padding: 16px;
    border-radius: 9px;
}

.photo-box h3 {
    margin: 0 0 12px;
    color: #123c69;
}

.photo-box img {
    width: 100%;
    max-height: 320px;
    object-fit: contain;
    background: white;
    border-radius: 8px;
    border: 1px solid #ddd;
    cursor: pointer;
}

.after-box {
    background: #f0fdf4;
}

.after-box h3 {
    color: #166534;
}

.no-photo {
    color: #777;
    padding: 20px 5px;
}


/* ACTIONS */

.actions {
    margin-top: 30px;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn {
    display: inline-block;
    padding: 12px 18px;
    border-radius: 7px;
    text-decoration: none;
    font-weight: bold;
}

.assign {
    background: #123c69;
    color: white;
}

.back-btn {
    background: #e8f0ff;
    color: #123c69;
}


@media (max-width:650px) {

    .page {
        padding: 15px;
    }

    .content {
        padding: 20px;
    }

    .info-grid,
    .photos {
        grid-template-columns: 1fr;
    }

}

</style>

</head>


<body>

<div class="page">

<div class="container">


<a
    href="complaints.php"
    class="back"
>
← Back to Complaints
</a>


<div class="header">

<h1>
Complaint Details
</h1>

<p>
Complete information about this complaint.
</p>

</div>


<div class="content">


<!-- TITLE -->

<div class="title">

CMP-<?php echo str_pad(
    (int)$complaint["id"],
    3,
    "0",
    STR_PAD_LEFT
); ?>

—

<?php echo htmlspecialchars(
    (string)$complaint["title"]
); ?>

</div>


<!-- COMPLAINT INFORMATION -->

<div class="section">

<h2>
Complaint Information
</h2>

<div class="info-grid">


<div class="info">

<strong>
Complaint ID
</strong>

<span>

CMP-<?php echo str_pad(
    (int)$complaint["id"],
    3,
    "0",
    STR_PAD_LEFT
); ?>

</span>

</div>


<div class="info">

<strong>
Category
</strong>

<span>

<?php echo htmlspecialchars(
    (string)$complaint["category"]
); ?>

</span>

</div>


<div class="info">

<strong>
Location
</strong>

<span>

<?php echo htmlspecialchars(
    (string)$complaint["location"]
); ?>

</span>

</div>


<div class="info">

<strong>
Priority
</strong>

<span>

<?php echo htmlspecialchars(
    (string)$complaint["priority"]
); ?>

</span>

</div>


<div class="info">

<strong>
Status
</strong>

<span class="status <?php echo htmlspecialchars($status_class); ?>">

<?php echo htmlspecialchars(
    (string)$status
); ?>

</span>

</div>


<div class="info">

<strong>
Submitted On
</strong>

<span>

<?php

if (!empty($complaint["created_at"])) {

    $timestamp =
        strtotime(
            $complaint["created_at"]
        );

    if ($timestamp !== false) {

        echo htmlspecialchars(
            date(
                "d M Y, h:i A",
                $timestamp
            )
        );

    } else {

        echo "-";

    }

} else {

    echo "-";

}

?>

</span>

</div>


</div>

</div>


<!-- DESCRIPTION -->

<div class="section">

<h2>
Description
</h2>

<div class="description">

<?php echo nl2br(
    htmlspecialchars(
        (string)$complaint["description"]
    )
); ?>

</div>

</div>


<!-- STUDENT INFORMATION -->

<div class="section">

<h2>
Student Information
</h2>

<div class="info-grid">


<div class="info">

<strong>
Name
</strong>

<span>

<?php echo htmlspecialchars(
    (string)$complaint["student_name"]
); ?>

</span>

</div>


<div class="info">

<strong>
Roll Number
</strong>

<span>

<?php echo htmlspecialchars(
    (string)$complaint["roll_no"]
); ?>

</span>

</div>


<div class="info">

<strong>
Mobile Number
</strong>

<span>

<?php echo htmlspecialchars(
    (string)$complaint["student_mobile"]
); ?>

</span>

</div>


</div>

</div>


<!-- BEFORE / AFTER PHOTOS -->

<div class="section">

<h2>
📷 Complaint Photos
</h2>


<div class="photos">


<!-- BEFORE -->

<div class="photo-box">

<h3>
📷 Before Repair
</h3>


<?php

$before_photo =
    photoPath(
        $complaint["photo"] ?? ""
    );

?>


<?php if ($before_photo !== ""): ?>

<img
    src="<?php echo htmlspecialchars(
        $before_photo
    ); ?>"
    alt="Before Repair Photo"
    onclick="window.open(this.src,'_blank')"
>

<?php else: ?>

<div class="no-photo">

Student did not upload a photo.

</div>

<?php endif; ?>

</div>


<!-- AFTER -->

<div class="photo-box after-box">

<h3>
✅ After Repair
</h3>


<?php

$after_photo =
    photoPath(
        $complaint["after_photo"] ?? ""
    );

?>


<?php if ($after_photo !== ""): ?>

<img
    src="<?php echo htmlspecialchars(
        $after_photo
    ); ?>"
    alt="After Repair Photo"
    onclick="window.open(this.src,'_blank')"
>

<?php else: ?>

<div class="no-photo">

Staff has not uploaded the repaired photo yet.

</div>

<?php endif; ?>


</div>


</div>

</div>


<!-- ASSIGNMENT INFORMATION -->

<div class="section">

<h2>
Staff Assignment
</h2>


<?php if ($assignment): ?>


<div class="assignment-box">


<div class="info-grid">


<div class="info">

<strong>
Assigned Staff
</strong>

<span>

<?php echo htmlspecialchars(
    (string)$assignment["staff_name"]
); ?>

</span>

</div>


<div class="info">

<strong>
Staff ID
</strong>

<span>

<?php echo htmlspecialchars(
    (string)$assignment["staff_code"]
); ?>

</span>

</div>


<div class="info">

<strong>
Department
</strong>

<span>

<?php echo htmlspecialchars(
    (string)$assignment["staff_department"]
); ?>

</span>

</div>


<div class="info">

<strong>
Work Status
</strong>

<span>

<?php echo htmlspecialchars(
    (string)$assignment["status"]
); ?>

</span>

</div>


<div class="info">

<strong>
Assigned On
</strong>

<span>

<?php

if (!empty($assignment["assigned_at"])) {

    $timestamp =
        strtotime(
            $assignment["assigned_at"]
        );

    if ($timestamp !== false) {

        echo htmlspecialchars(
            date(
                "d M Y, h:i A",
                $timestamp
            )
        );

    } else {

        echo "-";

    }

} else {

    echo "-";

}

?>

</span>

</div>


<div class="info">

<strong>
Completed On
</strong>

<span>

<?php

if (!empty($assignment["completed_at"])) {

    $timestamp =
        strtotime(
            $assignment["completed_at"]
        );

    if ($timestamp !== false) {

        echo htmlspecialchars(
            date(
                "d M Y, h:i A",
                $timestamp
            )
        );

    } else {

        echo "-";

    }

} else {

    echo "Not Completed";

}

?>

</span>

</div>


</div>


<?php if (
    !empty($assignment["work_details"])
): ?>


<div
    class="description"
    style="margin-top:15px;"
>

<strong>
🔧 Work Details
</strong>

<br><br>

<?php echo nl2br(
    htmlspecialchars(
        (string)$assignment["work_details"]
    )
); ?>

</div>


<?php endif; ?>


</div>


<?php else: ?>


<div class="assignment-box">

No staff member has been assigned yet.

</div>


<?php endif; ?>


</div>


<!-- ACTIONS -->

<div class="actions">


<?php if (!$assignment): ?>

<a
    href="assign_work.php?id=<?php echo $complaint_id; ?>"
    class="btn assign"
>
🔧 Assign Work
</a>

<?php endif; ?>


<a
    href="complaints.php"
    class="btn back-btn"
>
← Back to Complaints
</a>


</div>


</div>

</div>

</div>

</body>

</html>