<?php

session_start();

if (!isset($_SESSION["staff_id"])) {
    header("Location: login.php");
    exit();
}

require_once "../config/db.php";

$staff_id = $_SESSION["staff_id"];


// Check assignment ID
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid assignment ID.");
}

$assignment_id = (int) $_GET["id"];


// Allowed status
$allowed_statuses = [
    "Accepted",
    "In Progress",
    "Completed",
    "Rejected"
];

if (
    !isset($_GET["status"]) ||
    !in_array($_GET["status"], $allowed_statuses)
) {
    die("Invalid work status.");
}

$new_status = $_GET["status"];


// Get assignment + student
$sql = "
    SELECT
        assignments.complaint_id,
        complaints.student_id,
        assignments.status AS current_status
    FROM assignments

    INNER JOIN complaints
        ON assignments.complaint_id = complaints.id

    WHERE assignments.id = ?
    AND assignments.staff_id = ?

    LIMIT 1
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $assignment_id,
    $staff_id
);

$stmt->execute();

$result = $stmt->get_result();


// Work not found
if ($result->num_rows === 0) {
    die("Work not found or you are not authorized.");
}

$assignment = $result->fetch_assoc();

$stmt->close();


$complaint_id = $assignment["complaint_id"];
$student_id = $assignment["student_id"];
$current_status = $assignment["current_status"];


// Prevent invalid backwards movement

$valid_transition = false;

if (
    $current_status === "Assigned" &&
    in_array($new_status, ["Accepted", "Rejected"])
) {
    $valid_transition = true;
}

if (
    $current_status === "Accepted" &&
    $new_status === "In Progress"
) {
    $valid_transition = true;
}

if (
    $current_status === "In Progress" &&
    $new_status === "Completed"
) {
    $valid_transition = true;
}


if (!$valid_transition) {
    die(
        "Invalid status change: " .
        htmlspecialchars($current_status) .
        " → " .
        htmlspecialchars($new_status)
    );
}


// Update assignment

if ($new_status === "Completed") {

    $update_sql = "
        UPDATE assignments
        SET
            status = ?,
            completed_at = CURRENT_TIMESTAMP
        WHERE id = ?
        AND staff_id = ?
    ";

} else {

    $update_sql = "
        UPDATE assignments
        SET status = ?
        WHERE id = ?
        AND staff_id = ?
    ";
}

$update_stmt = $conn->prepare($update_sql);

$update_stmt->bind_param(
    "sii",
    $new_status,
    $assignment_id,
    $staff_id
);

$update_stmt->execute();

$update_stmt->close();


// Determine complaint status

if ($new_status === "Accepted") {

    $complaint_status = "Accepted";

} elseif ($new_status === "In Progress") {

    $complaint_status = "In Progress";

} elseif ($new_status === "Completed") {

    $complaint_status = "Resolved";

} elseif ($new_status === "Rejected") {

    $complaint_status = "Rejected";

}


// Update complaint

$complaint_sql = "
    UPDATE complaints
    SET status = ?
    WHERE id = ?
";

$complaint_stmt = $conn->prepare($complaint_sql);

$complaint_stmt->bind_param(
    "si",
    $complaint_status,
    $complaint_id
);

$complaint_stmt->execute();

$complaint_stmt->close();


// Staff name

$staff_name = $_SESSION["staff_name"] ?? "Staff";


// Complaint number

$complaint_number =
    "CMP-" .
    str_pad(
        $complaint_id,
        3,
        "0",
        STR_PAD_LEFT
    );


// Notification message for student

$student_message =
    "Your complaint " .
    $complaint_number .
    " has been updated to " .
    $new_status .
    " by staff.";

if ($new_status === "Completed") {

    $student_message =
        "Your complaint " .
        $complaint_number .
        " has been resolved. You can now provide staff feedback.";

}


// Insert student notification

$student_notification_sql = "
    INSERT INTO notifications
    (
        student_id,
        message,
        is_read
    )
    VALUES
    (
        ?,
        ?,
        0
    )
";

$student_notification_stmt =
    $conn->prepare(
        $student_notification_sql
    );

$student_notification_stmt->bind_param(
    "is",
    $student_id,
    $student_message
);

$student_notification_stmt->execute();

$student_notification_stmt->close();


// Notification for admin

$admin_message =
    "Staff " .
    $staff_name .
    " updated complaint " .
    $complaint_number .
    " to " .
    $new_status .
    ".";


// Send notification to all admins

$admin_notification_sql = "
    INSERT INTO notifications
    (
        admin_id,
        message,
        is_read
    )
    SELECT
        id,
        ?,
        0
    FROM admins
";

$admin_notification_stmt =
    $conn->prepare(
        $admin_notification_sql
    );

$admin_notification_stmt->bind_param(
    "s",
    $admin_message
);

$admin_notification_stmt->execute();

$admin_notification_stmt->close();


// Go back

header(
    "Location: work_details.php"
);

exit();

?>