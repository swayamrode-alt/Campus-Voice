<?php

session_start();

require_once "../config/db.php";


/* =========================================================
   ADMIN AUTHENTICATION
========================================================= */

if (!isset($_SESSION["admin_id"]) || !is_numeric($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}

$admin_id = (int) $_SESSION["admin_id"];


/* Verify admin still exists */

$admin_check_sql = "
    SELECT id, name
    FROM admins
    WHERE id = ?
    LIMIT 1
";

$admin_check_stmt = $conn->prepare($admin_check_sql);

if (!$admin_check_stmt) {
    header("Location: login.php");
    exit();
}

$admin_check_stmt->bind_param("i", $admin_id);
$admin_check_stmt->execute();

$admin_check_result = $admin_check_stmt->get_result();

if ($admin_check_result->num_rows === 0) {
    $admin_check_stmt->close();

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}

$admin_data = $admin_check_result->fetch_assoc();

$admin_check_stmt->close();


/* =========================================================
   CSRF TOKEN
========================================================= */

if (
    !isset($_SESSION["admin_csrf_token"]) ||
    !is_string($_SESSION["admin_csrf_token"]) ||
    $_SESSION["admin_csrf_token"] === ""
) {
    $_SESSION["admin_csrf_token"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["admin_csrf_token"];


/* =========================================================
   VARIABLES
========================================================= */

$error = "";
$success = "";

$complaint_id = 0;


/* =========================================================
   GET COMPLAINT ID
========================================================= */

if (
    isset($_GET["id"]) &&
    is_numeric($_GET["id"])
) {
    $complaint_id = (int) $_GET["id"];

} elseif (
    isset($_GET["complaint_id"]) &&
    is_numeric($_GET["complaint_id"])
) {
    $complaint_id = (int) $_GET["complaint_id"];
}

if ($complaint_id <= 0) {
    header("Location: complaints.php");
    exit();
}


/* =========================================================
   FETCH COMPLAINT
========================================================= */

$sql = "
    SELECT
        complaints.*,
        students.name AS student_name,
        students.roll_no
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

if ($result->num_rows === 0) {
    $stmt->close();

    header("Location: complaints.php");
    exit();
}

$complaint = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   HANDLE ASSIGNMENT
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    /* =====================================================
       CSRF CHECK
    ===================================================== */

    $submitted_csrf = $_POST["csrf_token"] ?? "";

    if (
        !is_string($submitted_csrf) ||
        !hash_equals($csrf_token, $submitted_csrf)
    ) {

        $error = "Invalid security request. Please try again.";

    } else {


        /* =================================================
           GET FORM DATA
        ================================================= */

        $selected_staff_id = isset($_POST["staff_id"])
            ? (int) $_POST["staff_id"]
            : 0;

        $work_details = trim(
            (string) ($_POST["work_details"] ?? "")
        );


        /* =================================================
           VALIDATION
        ================================================= */

        if ($selected_staff_id <= 0) {

            $error = "Please select a staff member.";

        } elseif ($work_details === "") {

            $error = "Please enter work details.";

        } elseif (mb_strlen($work_details) > 2000) {

            $error = "Work details are too long.";

        } else {


            /* =================================================
               CHECK STAFF
            ================================================= */

            $staff_check_sql = "
                SELECT
                    id,
                    name,
                    staff_id,
                    department,
                    status,
                    approval_status
                FROM staff
                WHERE id = ?
                LIMIT 1
            ";

            $staff_check_stmt =
                $conn->prepare($staff_check_sql);

            if (!$staff_check_stmt) {

                $error = "Unable to verify staff member.";

            } else {

                $staff_check_stmt->bind_param(
                    "i",
                    $selected_staff_id
                );

                $staff_check_stmt->execute();

                $staff_check_result =
                    $staff_check_stmt->get_result();


                if ($staff_check_result->num_rows === 0) {

                    $error = "Selected staff member not found.";

                } else {

                    $selected_staff =
                        $staff_check_result->fetch_assoc();


                    /* =================================================
                       CHECK STAFF APPROVAL
                    ================================================= */

                    if (
                        !isset($selected_staff["approval_status"]) ||
                        strtolower(
                            trim(
                                $selected_staff["approval_status"]
                            )
                        ) !== "approved"
                    ) {

                        $error =
                            "Selected staff member is not approved.";

                    }


                    /* =================================================
                       CHECK STAFF AVAILABILITY
                    ================================================= */

                    elseif (
                        !isset($selected_staff["status"]) ||
                        strtolower(
                            trim(
                                $selected_staff["status"]
                            )
                        ) !== "present"
                    ) {

                        $error =
                            "Selected staff member is not currently present.";

                    }


                    /* =================================================
                       CHECK COMPLAINT STATUS
                    ================================================= */

                    elseif (
                        isset($complaint["status"]) &&
                        in_array(
                            strtolower(
                                trim(
                                    $complaint["status"]
                                )
                            ),
                            ["resolved", "completed"],
                            true
                        )
                    ) {

                        $error =
                            "This complaint has already been completed.";

                    } else {


                        /* =================================================
                           CHECK EXISTING ASSIGNMENT
                        ================================================= */

                        $check_sql = "
                            SELECT
                                id,
                                staff_id,
                                status
                            FROM assignments
                            WHERE complaint_id = ?
                            LIMIT 1
                        ";

                        $check_stmt =
                            $conn->prepare($check_sql);

                        if (!$check_stmt) {

                            $error =
                                "Unable to check existing assignment.";

                        } else {

                            $check_stmt->bind_param(
                                "i",
                                $complaint_id
                            );

                            $check_stmt->execute();

                            $check_result =
                                $check_stmt->get_result();


                            /* =================================================
                               START TRANSACTION
                            ================================================= */

                            $transaction_started = false;


                            if (
                                $check_result->num_rows > 0
                            ) {

                                $existing_assignment =
                                    $check_result->fetch_assoc();

                                $assignment_id =
                                    (int) $existing_assignment["id"];


                                $conn->begin_transaction();

                                $transaction_started = true;


                                /* =================================================
                                   UPDATE EXISTING ASSIGNMENT
                                ================================================= */

                                $update_assignment_sql = "
                                    UPDATE assignments
                                    SET
                                        staff_id = ?,
                                        assigned_by = ?,
                                        work_details = ?,
                                        status = 'Assigned',
                                        assigned_at = NOW(),
                                        completed_at = NULL
                                    WHERE id = ?
                                ";

                                $update_assignment_stmt =
                                    $conn->prepare(
                                        $update_assignment_sql
                                    );


                                if (!$update_assignment_stmt) {

                                    $conn->rollback();

                                    $error =
                                        "Unable to update assignment.";

                                } else {

                                    $update_assignment_stmt->bind_param(
                                        "iisi",
                                        $selected_staff_id,
                                        $admin_id,
                                        $work_details,
                                        $assignment_id
                                    );


                                    if (
                                        !$update_assignment_stmt->execute()
                                    ) {

                                        $conn->rollback();

                                        $error =
                                            "Unable to update assignment.";

                                    } else {


                                        /* =====================================
                                           UPDATE COMPLAINT
                                        ===================================== */

                                        $update_complaint_sql = "
                                            UPDATE complaints
                                            SET
                                                status = 'Assigned',
                                                updated_at = NOW()
                                            WHERE id = ?
                                        ";

                                        $update_complaint_stmt =
                                            $conn->prepare(
                                                $update_complaint_sql
                                            );


                                        if (!$update_complaint_stmt) {

                                            $conn->rollback();

                                            $error =
                                                "Unable to update complaint.";

                                        } else {

                                            $update_complaint_stmt->bind_param(
                                                "i",
                                                $complaint_id
                                            );


                                            if (
                                                !$update_complaint_stmt->execute()
                                            ) {

                                                $conn->rollback();

                                                $error =
                                                    "Unable to update complaint.";

                                            } else {

                                                $conn->commit();

                                                $success =
                                                    "Complaint successfully assigned to " .
                                                    $selected_staff["name"] .
                                                    ".";

                                            }

                                            $update_complaint_stmt->close();
                                        }
                                    }

                                    $update_assignment_stmt->close();
                                }


                                /* =================================================
                                   NOTIFICATIONS
                                ================================================= */

                                if ($success !== "") {


                                    /* Staff notification */

                                    $notification_message =
                                        "Complaint CMP-" .
                                        str_pad(
                                            $complaint_id,
                                            3,
                                            "0",
                                            STR_PAD_LEFT
                                        ) .
                                        " has been assigned to you.";

                                    $notification_sql = "
                                        INSERT INTO notifications
                                        (
                                            staff_id,
                                            message
                                        )
                                        VALUES (?, ?)
                                    ";

                                    $notification_stmt =
                                        $conn->prepare(
                                            $notification_sql
                                        );

                                    if ($notification_stmt) {

                                        $notification_stmt->bind_param(
                                            "is",
                                            $selected_staff_id,
                                            $notification_message
                                        );

                                        $notification_stmt->execute();

                                        $notification_stmt->close();
                                    }


                                    /* Student notification */

                                    $student_notification_message =
                                        "Your complaint CMP-" .
                                        str_pad(
                                            $complaint_id,
                                            3,
                                            "0",
                                            STR_PAD_LEFT
                                        ) .
                                        " has been assigned to a staff member.";

                                    $student_notification_sql = "
                                        INSERT INTO notifications
                                        (
                                            student_id,
                                            message
                                        )
                                        VALUES (?, ?)
                                    ";

                                    $student_notification_stmt =
                                        $conn->prepare(
                                            $student_notification_sql
                                        );

                                    if ($student_notification_stmt) {

                                        $student_notification_stmt->bind_param(
                                            "is",
                                            $complaint["student_id"],
                                            $student_notification_message
                                        );

                                        $student_notification_stmt->execute();

                                        $student_notification_stmt->close();
                                    }
                                }

                            } else {


                                /* =================================================
                                   NEW ASSIGNMENT
                                ================================================= */

                                $conn->begin_transaction();

                                $transaction_started = true;


                                $insert_sql = "
                                    INSERT INTO assignments
                                    (
                                        complaint_id,
                                        staff_id,
                                        assigned_by,
                                        work_details,
                                        status,
                                        assigned_at
                                    )
                                    VALUES
                                    (?, ?, ?, ?, 'Assigned', NOW())
                                ";

                                $insert_stmt =
                                    $conn->prepare($insert_sql);


                                if (!$insert_stmt) {

                                    $conn->rollback();

                                    $error =
                                        "Unable to create assignment.";

                                } else {

                                    $insert_stmt->bind_param(
                                        "iiis",
                                        $complaint_id,
                                        $selected_staff_id,
                                        $admin_id,
                                        $work_details
                                    );


                                    if (
                                        !$insert_stmt->execute()
                                    ) {

                                        $conn->rollback();

                                        $error =
                                            "Unable to assign complaint.";

                                    } else {


                                        /* =====================================
                                           UPDATE COMPLAINT STATUS
                                        ===================================== */

                                        $update_complaint_sql = "
                                            UPDATE complaints
                                            SET
                                                status = 'Assigned',
                                                updated_at = NOW()
                                            WHERE id = ?
                                        ";

                                        $update_complaint_stmt =
                                            $conn->prepare(
                                                $update_complaint_sql
                                            );


                                        if (!$update_complaint_stmt) {

                                            $conn->rollback();

                                            $error =
                                                "Unable to update complaint.";

                                        } else {

                                            $update_complaint_stmt->bind_param(
                                                "i",
                                                $complaint_id
                                            );


                                            if (
                                                !$update_complaint_stmt->execute()
                                            ) {

                                                $conn->rollback();

                                                $error =
                                                    "Unable to update complaint.";

                                            } else {

                                                $conn->commit();

                                                $success =
                                                    "Complaint successfully assigned to " .
                                                    $selected_staff["name"] .
                                                    ".";

                                            }

                                            $update_complaint_stmt->close();
                                        }
                                    }

                                    $insert_stmt->close();
                                }


                                /* =================================================
                                   NOTIFICATIONS
                                ================================================= */

                                if ($success !== "") {


                                    /* Staff notification */

                                    $notification_message =
                                        "New complaint assigned to you. Complaint ID: CMP-" .
                                        str_pad(
                                            $complaint_id,
                                            3,
                                            "0",
                                            STR_PAD_LEFT
                                        );

                                    $notification_sql = "
                                        INSERT INTO notifications
                                        (
                                            staff_id,
                                            message
                                        )
                                        VALUES (?, ?)
                                    ";

                                    $notification_stmt =
                                        $conn->prepare(
                                            $notification_sql
                                        );

                                    if ($notification_stmt) {

                                        $notification_stmt->bind_param(
                                            "is",
                                            $selected_staff_id,
                                            $notification_message
                                        );

                                        $notification_stmt->execute();

                                        $notification_stmt->close();
                                    }


                                    /* Student notification */

                                    $student_notification_message =
                                        "Your complaint CMP-" .
                                        str_pad(
                                            $complaint_id,
                                            3,
                                            "0",
                                            STR_PAD_LEFT
                                        ) .
                                        " has been assigned to a staff member.";

                                    $student_notification_sql = "
                                        INSERT INTO notifications
                                        (
                                            student_id,
                                            message
                                        )
                                        VALUES (?, ?)
                                    ";

                                    $student_notification_stmt =
                                        $conn->prepare(
                                            $student_notification_sql
                                        );

                                    if ($student_notification_stmt) {

                                        $student_notification_stmt->bind_param(
                                            "is",
                                            $complaint["student_id"],
                                            $student_notification_message
                                        );

                                        $student_notification_stmt->execute();

                                        $student_notification_stmt->close();
                                    }
                                }
                            }

                            $check_stmt->close();
                        }
                    }
                }

                $staff_check_stmt->close();
            }
        }
    }
}


/* =========================================================
   FETCH PRESENT + APPROVED STAFF
========================================================= */

$staff_sql = "
    SELECT
        id,
        name,
        staff_id,
        department,
        status,
        approval_status
    FROM staff
    WHERE
        status = 'Present'
        AND approval_status = 'Approved'
    ORDER BY name ASC
";

$staff_result = $conn->query($staff_sql);

if (!$staff_result) {
    $staff_result = false;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Assign Work</title>

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
    max-width: 850px;
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

.complaint-box {
    background: #f8fafc;
    padding: 20px;
    border-radius: 9px;
    margin-bottom: 25px;
}

.complaint-box h2 {
    margin-top: 0;
    color: #123c69;
}

.complaint-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
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

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

select,
textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccd5e0;
    border-radius: 7px;
    font-size: 14px;
    font-family: Arial, sans-serif;
}

select:focus,
textarea:focus {
    outline: none;
    border-color: #123c69;
}

textarea {
    min-height: 130px;
    resize: vertical;
}

.btn {
    width: 100%;
    padding: 13px;
    border: none;
    border-radius: 7px;
    background: #123c69;
    color: white;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
}

.error {
    background: #fee2e2;
    color: #991b1b;
    padding: 12px;
    border-radius: 7px;
    margin-bottom: 20px;
}

.success {
    background: #d1fae5;
    color: #065f46;
    padding: 15px;
    border-radius: 7px;
    margin-bottom: 20px;
}

.note {
    margin-top: 18px;
    padding: 13px;
    background: #fff7e6;
    color: #765b1f;
    border-radius: 7px;
    font-size: 13px;
}

@media (max-width: 650px) {

    .page {
        padding: 15px;
    }

    .content {
        padding: 20px;
    }

    .complaint-info {
        grid-template-columns: 1fr;
    }

}

</style>

</head>

<body>

<div class="page">

<div class="container">

<a
    href="complaint_details.php?id=<?php echo $complaint_id; ?>"
    class="back"
>
    ← Back to Complaint
</a>


<div class="header">

<h1>Assign Work</h1>

<p>
Assign this complaint to a staff member.
</p>

</div>


<div class="content">


<?php if ($error !== ""): ?>

<div class="error">
    <?php echo htmlspecialchars($error); ?>
</div>

<?php endif; ?>


<?php if ($success !== ""): ?>

<div class="success">

    <?php echo htmlspecialchars($success); ?>

    <br><br>

    <a
        href="complaint_details.php?id=<?php echo $complaint_id; ?>"
    >
        View Complaint
    </a>

</div>

<?php endif; ?>


<!-- COMPLAINT -->

<div class="complaint-box">

<h2>
<?php echo htmlspecialchars($complaint["title"]); ?>
</h2>

<div class="complaint-info">

<div class="info">

<strong>Complaint ID</strong>

<span>
CMP-<?php echo str_pad(
    $complaint_id,
    3,
    "0",
    STR_PAD_LEFT
); ?>
</span>

</div>


<div class="info">

<strong>Student</strong>

<span>
<?php echo htmlspecialchars(
    $complaint["student_name"]
); ?>
</span>

</div>


<div class="info">

<strong>Roll Number</strong>

<span>
<?php echo htmlspecialchars(
    $complaint["roll_no"]
); ?>
</span>

</div>


<div class="info">

<strong>Category</strong>

<span>
<?php echo htmlspecialchars(
    $complaint["category"]
); ?>
</span>

</div>


<div class="info">

<strong>Location</strong>

<span>
<?php echo htmlspecialchars(
    $complaint["location"]
); ?>
</span>

</div>


<div class="info">

<strong>Priority</strong>

<span>
<?php echo htmlspecialchars(
    $complaint["priority"]
); ?>
</span>

</div>

</div>

</div>


<?php if ($success === ""): ?>

<form method="POST">

<input
    type="hidden"
    name="csrf_token"
    value="<?php echo htmlspecialchars(
        $csrf_token,
        ENT_QUOTES,
        "UTF-8"
    ); ?>"
>


<div class="form-group">

<label for="staff_id">
Select Staff
</label>

<select
    name="staff_id"
    id="staff_id"
    required
>

<option value="">
-- Select Staff Member --
</option>


<?php if (
    $staff_result !== false &&
    $staff_result->num_rows > 0
): ?>

<?php while (
    $staff = $staff_result->fetch_assoc()
): ?>

<option
    value="<?php echo (int) $staff["id"]; ?>"
>

<?php echo htmlspecialchars(
    $staff["name"]
); ?>

-
<?php echo htmlspecialchars(
    $staff["staff_id"]
); ?>

-
<?php echo htmlspecialchars(
    $staff["department"]
); ?>

(
<?php echo htmlspecialchars(
    $staff["status"]
); ?>
)

</option>

<?php endwhile; ?>

<?php else: ?>

<option value="" disabled>
No Present Approved Staff Available
</option>

<?php endif; ?>

</select>

</div>


<div class="form-group">

<label for="work_details">
Work Details
</label>

<textarea
    name="work_details"
    id="work_details"
    placeholder="Enter the work that staff member needs to perform..."
    maxlength="2000"
    required
></textarea>

</div>


<button
    type="submit"
    class="btn"
>
🔧 Assign Complaint
</button>

</form>


<div class="note">

Only staff members with
<strong>Present</strong>
status and
<strong>Approved</strong>
approval status
are shown.

<br><br>

If this complaint was already assigned,
selecting another staff member will
<strong>reassign</strong> the complaint.

</div>

<?php endif; ?>


</div>

</div>

</div>

</body>

</html>