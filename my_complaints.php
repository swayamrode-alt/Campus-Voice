<?php

session_start();

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit();
}

require_once "../config/db.php";

$student_id = (int) $_SESSION["student_id"];


/* =========================================================
   HELPER FUNCTION
   ========================================================= */

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


/* =========================================================
   VERIFY STUDENT ACCOUNT
   ========================================================= */

$student_sql = "
    SELECT id, name, roll_no, status
    FROM students
    WHERE id = ?
    LIMIT 1
";

$student_stmt = $conn->prepare($student_sql);

if (!$student_stmt) {
    die("Something went wrong. Please try again later.");
}

$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();

$student_result = $student_stmt->get_result();
$student = $student_result->fetch_assoc();

$student_stmt->close();

if (!$student) {
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}


/* =========================================================
   CHECK STUDENT APPROVAL STATUS
   ========================================================= */

if (
    !isset($student["status"]) ||
    $student["status"] !== "Approved"
) {
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}


/* =========================================================
   CSRF TOKEN
   ========================================================= */

if (empty($_SESSION["student_csrf_token"])) {
    $_SESSION["student_csrf_token"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["student_csrf_token"];


/* =========================================================
   MESSAGE
   ========================================================= */

$message = "";
$message_type = "";


/* =========================================================
   REQUEST REOPEN
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals(
            $csrf_token,
            $_POST["csrf_token"]
        )
    ) {

        $message = "Invalid request.";
        $message_type = "error";

    } else {

        $action = $_POST["action"] ?? "";


        /* =================================================
           REQUEST REOPEN ACTION
           ================================================= */

        if ($action === "request_reopen") {

            $complaint_id = filter_input(
                INPUT_POST,
                "complaint_id",
                FILTER_VALIDATE_INT
            );

            $reason = trim(
                $_POST["reason"] ?? ""
            );


            /* ---------------------------------------------
               BASIC VALIDATION
               --------------------------------------------- */

            if (!$complaint_id) {

                $message = "Invalid complaint.";
                $message_type = "error";

            } elseif ($reason === "") {

                $message = "Please provide a reason for reopening.";
                $message_type = "error";

            } elseif (strlen($reason) > 1000) {

                $message = "Reason must not exceed 1000 characters.";
                $message_type = "error";

            } else {


                /* =========================================
                   CHECK COMPLAINT
                   ========================================= */

                $complaint_sql = "
                    SELECT
                        c.id,
                        c.title,
                        c.status,
                        c.student_id,
                        a.staff_id
                    FROM complaints c

                    LEFT JOIN assignments a
                        ON a.complaint_id = c.id

                    WHERE
                        c.id = ?
                        AND c.student_id = ?

                    ORDER BY a.id DESC
                    LIMIT 1
                ";

                $complaint_stmt =
                    $conn->prepare($complaint_sql);

                if (!$complaint_stmt) {

                    $message = "Something went wrong.";
                    $message_type = "error";

                } else {

                    $complaint_stmt->bind_param(
                        "ii",
                        $complaint_id,
                        $student_id
                    );

                    $complaint_stmt->execute();

                    $complaint_result =
                        $complaint_stmt->get_result();

                    $complaint =
                        $complaint_result->fetch_assoc();

                    $complaint_stmt->close();


                    if (!$complaint) {

                        $message = "Complaint not found.";
                        $message_type = "error";

                    } elseif (
                        !in_array(
                            $complaint["status"],
                            ["Resolved", "Completed"],
                            true
                        )
                    ) {

                        $message =
                            "Only resolved complaints can be reopened.";

                        $message_type = "error";

                    } elseif (
                        empty($complaint["staff_id"])
                    ) {

                        $message =
                            "No staff is associated with this complaint.";

                        $message_type = "error";

                    } else {


                        /* =================================
                           COUNT PREVIOUS REQUESTS
                           ================================= */

                        $count_sql = "
                            SELECT COUNT(*) AS total
                            FROM reopen_requests
                            WHERE
                                complaint_id = ?
                                AND student_id = ?
                        ";

                        $count_stmt =
                            $conn->prepare($count_sql);

                        if (!$count_stmt) {

                            $message = "Something went wrong.";
                            $message_type = "error";

                        } else {

                            $count_stmt->bind_param(
                                "ii",
                                $complaint_id,
                                $student_id
                            );

                            $count_stmt->execute();

                            $count_result =
                                $count_stmt->get_result();

                            $count_row =
                                $count_result->fetch_assoc();

                            $count_stmt->close();

                            $request_count =
                                (int)($count_row["total"] ?? 0);


                            /* =================================
                               MAXIMUM 2 REQUESTS
                               ================================= */

                            if ($request_count >= 2) {

                                $message =
                                    "Maximum 2 reopen requests are allowed for one complaint.";

                                $message_type = "error";

                            } else {


                                /* =============================
                                   CHECK PENDING REQUEST
                                   ============================= */

                                $pending_sql = "
                                    SELECT id
                                    FROM reopen_requests
                                    WHERE
                                        complaint_id = ?
                                        AND student_id = ?
                                        AND status = 'Pending'
                                    LIMIT 1
                                ";

                                $pending_stmt =
                                    $conn->prepare($pending_sql);

                                if (!$pending_stmt) {

                                    $message =
                                        "Something went wrong.";

                                    $message_type = "error";

                                } else {

                                    $pending_stmt->bind_param(
                                        "ii",
                                        $complaint_id,
                                        $student_id
                                    );

                                    $pending_stmt->execute();

                                    $pending_result =
                                        $pending_stmt->get_result();

                                    $pending_request =
                                        $pending_result->fetch_assoc();

                                    $pending_stmt->close();


                                    if ($pending_request) {

                                        $message =
                                            "A reopen request is already pending for this complaint.";

                                        $message_type = "error";

                                    } else {


                                        /* =========================
                                           OPTIONAL PHOTO UPLOAD
                                           ========================= */

                                        $photo_filename = null;


                                        if (
                                            isset($_FILES["reopen_photo"]) &&
                                            $_FILES["reopen_photo"]["error"] !== UPLOAD_ERR_NO_FILE
                                        ) {

                                            $file = $_FILES["reopen_photo"];


                                            if (
                                                $file["error"] !== UPLOAD_ERR_OK
                                            ) {

                                                $message =
                                                    "There was a problem uploading the evidence photo.";

                                                $message_type = "error";

                                            } elseif (
                                                $file["size"] > 5 * 1024 * 1024
                                            ) {

                                                $message =
                                                    "Evidence photo must be 5 MB or smaller.";

                                                $message_type = "error";

                                            } else {

                                                $finfo =
                                                    new finfo(FILEINFO_MIME_TYPE);

                                                $mime =
                                                    $finfo->file(
                                                        $file["tmp_name"]
                                                    );


                                                $allowed_mimes = [
                                                    "image/jpeg" => "jpg",
                                                    "image/png"  => "png",
                                                    "image/webp" => "webp"
                                                ];


                                                if (
                                                    !isset(
                                                        $allowed_mimes[$mime]
                                                    )
                                                ) {

                                                    $message =
                                                        "Only JPG, PNG or WEBP images are allowed.";

                                                    $message_type = "error";

                                                } else {

                                                    $upload_dir =
                                                        __DIR__ . "/../uploads/";


                                                    if (
                                                        !is_dir($upload_dir)
                                                    ) {

                                                        mkdir(
                                                            $upload_dir,
                                                            0755,
                                                            true
                                                        );
                                                    }


                                                    $photo_filename =
                                                        "reopen_" .
                                                        bin2hex(
                                                            random_bytes(16)
                                                        ) .
                                                        "." .
                                                        $allowed_mimes[$mime];


                                                    $photo_path =
                                                        $upload_dir .
                                                        $photo_filename;


                                                    if (
                                                        !move_uploaded_file(
                                                            $file["tmp_name"],
                                                            $photo_path
                                                        )
                                                    ) {

                                                        $photo_filename = null;

                                                        $message =
                                                            "Could not save the evidence photo.";

                                                        $message_type = "error";
                                                    }
                                                }
                                            }
                                        }


                                        /* =========================
                                           INSERT REQUEST
                                           ========================= */

                                        if ($message === "") {

                                            $insert_sql = "
                                                INSERT INTO reopen_requests
                                                (
                                                    complaint_id,
                                                    student_id,
                                                    staff_id,
                                                    reason,
                                                    photo,
                                                    status
                                                )
                                                VALUES
                                                (?, ?, ?, ?, ?, 'Pending')
                                            ";

                                            $insert_stmt =
                                                $conn->prepare(
                                                    $insert_sql
                                                );


                                            if (!$insert_stmt) {

                                                if (
                                                    $photo_filename &&
                                                    file_exists(
                                                        __DIR__ .
                                                        "/../uploads/" .
                                                        $photo_filename
                                                    )
                                                ) {
                                                    unlink(
                                                        __DIR__ .
                                                        "/../uploads/" .
                                                        $photo_filename
                                                    );
                                                }

                                                $message =
                                                    "Could not submit reopen request.";

                                                $message_type = "error";

                                            } else {

                                                $staff_id =
                                                    (int)$complaint["staff_id"];

                                                $insert_stmt->bind_param(
                                                    "iiiss",
                                                    $complaint_id,
                                                    $student_id,
                                                    $staff_id,
                                                    $reason,
                                                    $photo_filename
                                                );


                                                if (
                                                    $insert_stmt->execute()
                                                ) {

                                                    /*
                                                     * Notify all admins
                                                     */

                                                    $admin_sql =
                                                        "SELECT id FROM admins";

                                                    $admin_result =
                                                        $conn->query(
                                                            $admin_sql
                                                        );


                                                    if ($admin_result) {

                                                        $notification_sql = "
                                                            INSERT INTO notifications
                                                            (
                                                                admin_id,
                                                                message,
                                                                is_read,
                                                                created_at
                                                            )
                                                            VALUES
                                                            (?, ?, 0, NOW())
                                                        ";

                                                        $notification_stmt =
                                                            $conn->prepare(
                                                                $notification_sql
                                                            );


                                                        if (
                                                            $notification_stmt
                                                        ) {

                                                            $notification_message =
                                                                "Student has requested reopening of Complaint #" .
                                                                $complaint_id .
                                                                " (" .
                                                                $complaint["title"] .
                                                                ").";


                                                            while (
                                                                $admin =
                                                                $admin_result->fetch_assoc()
                                                            ) {

                                                                $admin_id =
                                                                    (int)$admin["id"];

                                                                $notification_stmt->bind_param(
                                                                    "is",
                                                                    $admin_id,
                                                                    $notification_message
                                                                );

                                                                $notification_stmt->execute();
                                                            }


                                                            $notification_stmt->close();
                                                        }
                                                    }


                                                    $message =
                                                        "Reopen request submitted successfully. Admin will review it.";

                                                    $message_type =
                                                        "success";

                                                } else {

                                                    if (
                                                        $photo_filename &&
                                                        file_exists(
                                                            __DIR__ .
                                                            "/../uploads/" .
                                                            $photo_filename
                                                        )
                                                    ) {
                                                        unlink(
                                                            __DIR__ .
                                                            "/../uploads/" .
                                                            $photo_filename
                                                        );
                                                    }


                                                    $message =
                                                        "Could not submit reopen request.";

                                                    $message_type =
                                                        "error";
                                                }


                                                $insert_stmt->close();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}


/* =========================================================
   GET FILTER VALUES
   ========================================================= */

$search = isset($_GET["search"])
    ? trim($_GET["search"])
    : "";

$status_filter = isset($_GET["status"])
    ? trim($_GET["status"])
    : "";

$category_filter = isset($_GET["category"])
    ? trim($_GET["category"])
    : "";

$priority_filter = isset($_GET["priority"])
    ? trim($_GET["priority"])
    : "";


/* =========================================================
   ALLOWED FILTER VALUES
   ========================================================= */

$allowed_statuses = [
    "Pending",
    "Accepted",
    "In Progress",
    "Rejected",
    "Resolved",
    "Completed"
];

$allowed_priorities = [
    "Low",
    "Medium",
    "High"
];


if (
    $status_filter !== "" &&
    !in_array(
        $status_filter,
        $allowed_statuses,
        true
    )
) {
    $status_filter = "";
}


if (
    $priority_filter !== "" &&
    !in_array(
        $priority_filter,
        $allowed_priorities,
        true
    )
) {
    $priority_filter = "";
}


/* =========================================================
   FETCH CATEGORIES USED BY THIS STUDENT
   ========================================================= */

$categories = [];

$category_sql = "
    SELECT DISTINCT category
    FROM complaints
    WHERE
        student_id = ?
        AND category IS NOT NULL
        AND category != ''
    ORDER BY category ASC
";

$category_stmt =
    $conn->prepare($category_sql);

if ($category_stmt) {

    $category_stmt->bind_param(
        "i",
        $student_id
    );

    $category_stmt->execute();

    $category_result =
        $category_stmt->get_result();


    while (
        $row =
        $category_result->fetch_assoc()
    ) {

        $categories[] =
            $row["category"];
    }


    $category_stmt->close();
}


/* =========================================================
   BUILD MAIN QUERY
   ========================================================= */

$sql = "
    SELECT
        c.id,
        c.title,
        c.description,
        c.category,
        c.location,
        c.photo,
        c.after_photo,
        c.status,
        c.priority,
        c.created_at,
        c.updated_at,

        s.id AS staff_id,
        s.name AS staff_name

    FROM complaints c

    LEFT JOIN assignments a
        ON a.complaint_id = c.id

    LEFT JOIN staff s
        ON a.staff_id = s.id

    WHERE c.student_id = ?
";

$params = [$student_id];
$types = "i";


/* =========================================================
   SEARCH
   ========================================================= */

if ($search !== "") {

    $sql .= "
        AND (
            CAST(c.id AS CHAR) LIKE ?
            OR c.title LIKE ?
            OR c.description LIKE ?
            OR c.location LIKE ?
            OR c.category LIKE ?
        )
    ";

    $search_value =
        "%" . $search . "%";


    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "sssss";
}


/* =========================================================
   STATUS FILTER
   ========================================================= */

if ($status_filter !== "") {

    $sql .= "
        AND c.status = ?
    ";

    $params[] =
        $status_filter;

    $types .= "s";
}


/* =========================================================
   CATEGORY FILTER
   ========================================================= */

if ($category_filter !== "") {

    $sql .= "
        AND c.category = ?
    ";

    $params[] =
        $category_filter;

    $types .= "s";
}


/* =========================================================
   PRIORITY FILTER
   ========================================================= */

if ($priority_filter !== "") {

    $sql .= "
        AND c.priority = ?
    ";

    $params[] =
        $priority_filter;

    $types .= "s";
}


/* =========================================================
   ORDER
   ========================================================= */

$sql .= "
    ORDER BY
        c.created_at DESC,
        a.id DESC
";


/* =========================================================
   PREPARE MAIN QUERY
   ========================================================= */

$stmt =
    $conn->prepare($sql);

if (!$stmt) {
    die("Something went wrong. Please try again later.");
}


/* =========================================================
   BIND PARAMETERS
   ========================================================= */

$stmt->bind_param(
    $types,
    ...$params
);

$stmt->execute();

$result =
    $stmt->get_result();


/* =========================================================
   STATUS CSS CLASS
   ========================================================= */

function statusClass($status)
{
    $status =
        strtolower(trim($status));


    if ($status === "pending") {
        return "pending";
    }


    if ($status === "accepted") {
        return "accepted";
    }


    if ($status === "in progress") {
        return "in-progress";
    }


    if ($status === "rejected") {
        return "rejected";
    }


    if ($status === "resolved") {
        return "resolved";
    }


    if ($status === "completed") {
        return "completed";
    }


    return "default";
}


/* =========================================================
   PHOTO PATH HELPER
   ========================================================= */

function photoPath($filename)
{
    if (!$filename) {
        return "";
    }

    $filename =
        basename($filename);

    return "../uploads/" .
        $filename;
}


/* =========================================================
   GET REOPEN REQUEST STATUS FOR EACH COMPLAINT
   ========================================================= */

$reopen_requests = [];

$reopen_sql = "
    SELECT
        id,
        complaint_id,
        reason,
        photo,
        status,
        admin_remark,
        created_at,
        reviewed_at
    FROM reopen_requests
    WHERE student_id = ?
    ORDER BY created_at DESC
";

$reopen_stmt =
    $conn->prepare($reopen_sql);

if ($reopen_stmt) {

    $reopen_stmt->bind_param(
        "i",
        $student_id
    );

    $reopen_stmt->execute();

    $reopen_result =
        $reopen_stmt->get_result();


    while (
        $reopen_row =
        $reopen_result->fetch_assoc()
    ) {

        $reopen_requests[
            (int)$reopen_row["complaint_id"]
        ][] =
            $reopen_row;
    }


    $reopen_stmt->close();
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

    <title>My Complaints - CampusVoice</title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #222;
        }


        .header {
            background: #1f2937;
            color: white;
            padding: 18px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }


        .header h2 {
            margin: 0;
        }


        .header a {
            color: white;
            text-decoration: none;
            background: #dc2626;
            padding: 9px 15px;
            border-radius: 6px;
        }


        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 18px;
        }


        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow:
                0 2px 8px rgba(0,0,0,0.08);
        }


        .filters form {
            display: grid;
            grid-template-columns:
                2fr 1fr 1fr 1fr auto auto;
            gap: 10px;
            align-items: center;
        }


        input,
        select,
        textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }


        textarea {
            resize: vertical;
            min-height: 100px;
        }


        button,
        .clear-btn {
            border: none;
            padding: 11px 16px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }


        button {
            background: #2563eb;
            color: white;
        }


        .clear-btn {
            background: #6b7280;
            color: white;
        }


        .complaint {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow:
                0 2px 8px rgba(0,0,0,0.08);
        }


        .complaint-header {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }


        .complaint-header h3 {
            margin: 0;
            color: #111827;
        }


        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }


        .pending {
            background: #fef3c7;
            color: #92400e;
        }


        .accepted {
            background: #dbeafe;
            color: #1e40af;
        }


        .in-progress {
            background: #e0e7ff;
            color: #3730a3;
        }


        .rejected {
            background: #fee2e2;
            color: #991b1b;
        }


        .resolved,
        .completed {
            background: #dcfce7;
            color: #166534;
        }


        .default {
            background: #e5e7eb;
            color: #374151;
        }


        .details {
            line-height: 1.6;
            margin-top: 10px;
        }


        .details strong {
            color: #374151;
        }


        .photo-section {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 18px;
        }


        .photo-box {
            width: 220px;
        }


        .photo-box img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }


        .actions {
            margin-top: 18px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }


        .rate-btn {
            background: #16a34a;
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
            text-decoration: none;
        }


        .reopen-btn {
            background: #f59e0b;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }


        .reopen-form {
            display: none;
            margin-top: 18px;
            padding: 20px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 8px;
        }


        .reopen-form h4 {
            margin-top: 0;
            color: #9a3412;
        }


        .reopen-form label {
            display: block;
            font-weight: bold;
            margin: 12px 0 6px;
        }


        .submit-reopen {
            margin-top: 12px;
            background: #ea580c;
        }


        .cancel-reopen {
            margin-top: 12px;
            background: #6b7280;
        }


        .request-status {
            margin-top: 18px;
            padding: 15px;
            background: #f8fafc;
            border-left: 4px solid #64748b;
            border-radius: 6px;
        }


        .request-status h4 {
            margin: 0 0 10px;
        }


        .request-row {
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }


        .request-row:last-child {
            border-bottom: none;
        }


        .request-pending {
            color: #b45309;
            font-weight: bold;
        }


        .request-approved {
            color: #15803d;
            font-weight: bold;
        }


        .request-rejected {
            color: #b91c1c;
            font-weight: bold;
        }


        .message {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }


        .message.success {
            background: #dcfce7;
            color: #166534;
        }


        .message.error {
            background: #fee2e2;
            color: #991b1b;
        }


        .evidence-photo {
            margin-top: 10px;
            max-width: 180px;
            border-radius: 7px;
            border: 1px solid #ddd;
        }


        .no-complaints {
            background: white;
            padding: 35px;
            text-align: center;
            border-radius: 10px;
            box-shadow:
                0 2px 8px rgba(0,0,0,0.08);
        }


        @media (max-width: 900px) {

            .filters form {
                grid-template-columns: 1fr 1fr;
            }

        }


        @media (max-width: 600px) {

            .filters form {
                grid-template-columns: 1fr;
            }


            .container {
                padding: 0 12px;
            }


            .photo-box {
                width: 100%;
            }


            .header {
                padding: 15px;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     HEADER
     ===================================================== -->

<div class="header">

    <h2>
        📋 My Complaints
    </h2>


    <a href="dashboard.php">
        Dashboard
    </a>

</div>


<div class="container">


    <!-- =================================================
         MESSAGE
         ================================================= -->

    <?php if ($message !== ""): ?>

        <div
            class="message <?= e($message_type) ?>"
        >
            <?= e($message) ?>
        </div>

    <?php endif; ?>


    <!-- =================================================
         FILTERS
         ================================================= -->

    <div class="filters">

        <form method="GET">

            <input
                type="text"
                name="search"
                placeholder="Search complaint..."
                value="<?= e($search) ?>"
            >


            <select name="status">

                <option value="">
                    All Status
                </option>


                <?php foreach (
                    $allowed_statuses
                    as $status
                ): ?>

                    <option
                        value="<?= e($status) ?>"
                        <?= $status_filter === $status
                            ? "selected"
                            : "" ?>
                    >
                        <?= e($status) ?>
                    </option>

                <?php endforeach; ?>

            </select>


            <select name="category">

                <option value="">
                    All Categories
                </option>


                <?php foreach (
                    $categories
                    as $category
                ): ?>

                    <option
                        value="<?= e($category) ?>"
                        <?= $category_filter === $category
                            ? "selected"
                            : "" ?>
                    >
                        <?= e($category) ?>
                    </option>

                <?php endforeach; ?>

            </select>


            <select name="priority">

                <option value="">
                    All Priority
                </option>


                <?php foreach (
                    $allowed_priorities
                    as $priority
                ): ?>

                    <option
                        value="<?= e($priority) ?>"
                        <?= $priority_filter === $priority
                            ? "selected"
                            : "" ?>
                    >
                        <?= e($priority) ?>
                    </option>

                <?php endforeach; ?>

            </select>


            <button type="submit">
                🔍 Search
            </button>


            <a
                href="my_complaint.php"
                class="clear-btn"
            >
                Clear
            </a>

        </form>

    </div>


    <!-- =================================================
         COMPLAINT LIST
         ================================================= -->

    <?php if ($result->num_rows === 0): ?>

        <div class="no-complaints">

            <h3>
                No complaints found.
            </h3>

            <p>
                Try changing your search or filter.
            </p>

        </div>

    <?php else: ?>


        <?php while (
            $complaint =
            $result->fetch_assoc()
        ): ?>

            <div class="complaint">


                <!-- =====================================
                     HEADER
                     ===================================== -->

                <div class="complaint-header">

                    <h3>

                        #<?= (int)$complaint["id"] ?>

                        -

                        <?= e($complaint["title"]) ?>

                    </h3>


                    <span
                        class="badge
                        <?= e(
                            statusClass(
                                $complaint["status"]
                            )
                        ) ?>"
                    >

                        <?= e(
                            $complaint["status"]
                        ) ?>

                    </span>

                </div>


                <!-- =====================================
                     DETAILS
                     ===================================== -->

                <div class="details">

                    <p>

                        <strong>
                            Category:
                        </strong>

                        <?= e(
                            $complaint["category"]
                        ) ?>

                    </p>


                    <p>

                        <strong>
                            Location:
                        </strong>

                        <?= e(
                            $complaint["location"]
                        ) ?>

                    </p>


                    <p>

                        <strong>
                            Priority:
                        </strong>

                        <?= e(
                            $complaint["priority"]
                        ) ?>

                    </p>


                    <p>

                        <strong>
                            Description:
                        </strong>

                        <br>

                        <?= nl2br(
                            e(
                                $complaint["description"]
                            )
                        ) ?>

                    </p>


                    <p>

                        <strong>
                            Submitted:
                        </strong>

                        <?= e(
                            $complaint["created_at"]
                        ) ?>

                    </p>


                    <?php if (
                        !empty(
                            $complaint["updated_at"]
                        )
                    ): ?>

                        <p>

                            <strong>
                                Last Updated:
                            </strong>

                            <?= e(
                                $complaint["updated_at"]
                            ) ?>

                        </p>

                    <?php endif; ?>


                    <?php if (
                        !empty(
                            $complaint["staff_name"]
                        )
                    ): ?>

                        <p>

                            <strong>
                                Assigned Staff:
                            </strong>

                            <?= e(
                                $complaint["staff_name"]
                            ) ?>

                        </p>

                    <?php endif; ?>

                </div>


                <!-- =====================================
                     PHOTOS
                     ===================================== -->

                <?php

                $before_photo =
                    photoPath(
                        $complaint["photo"]
                    );

                $after_photo =
                    photoPath(
                        $complaint["after_photo"]
                    );

                ?>


                <?php if (
                    $before_photo ||
                    $after_photo
                ): ?>

                    <div class="photo-section">


                        <?php if (
                            $before_photo
                        ): ?>

                            <div class="photo-box">

                                <strong>
                                    Before Photo
                                </strong>

                                <br><br>

                                <img
                                    src="<?= e(
                                        $before_photo
                                    ) ?>"
                                    alt="Complaint Photo"
                                >

                            </div>

                        <?php endif; ?>


                        <?php if (
                            $after_photo
                        ): ?>

                            <div class="photo-box">

                                <strong>
                                    After Photo
                                </strong>

                                <br><br>

                                <img
                                    src="<?= e(
                                        $after_photo
                                    ) ?>"
                                    alt="Completed Work Photo"
                                >

                            </div>

                        <?php endif; ?>


                    </div>

                <?php endif; ?>


                <!-- =====================================
                     ACTIONS
                     ===================================== -->

                <div class="actions">


                    <?php

                    $current_status =
                        $complaint["status"];

                    ?>


                    <!-- RATE STAFF -->

                    <?php if (
                        in_array(
                            $current_status,
                            [
                                "Resolved",
                                "Completed"
                            ],
                            true
                        ) &&
                        !empty(
                            $complaint["staff_id"]
                        )
                    ): ?>

                        <a
                            href="rate_staff.php?complaint_id=<?= (int)$complaint["id"] ?>"
                            class="rate-btn"
                        >
                            ⭐ Rate Staff
                        </a>

                    <?php endif; ?>


                    <!-- REQUEST REOPEN -->

                    <?php

                    $complaint_id =
                        (int)$complaint["id"];

                    $complaint_requests =
                        $reopen_requests[
                            $complaint_id
                        ] ?? [];

                    $has_pending_request = false;

                    $request_count =
                        count(
                            $complaint_requests
                        );


                    foreach (
                        $complaint_requests
                        as $req
                    ) {

                        if (
                            $req["status"] ===
                            "Pending"
                        ) {

                            $has_pending_request =
                                true;

                            break;
                        }
                    }

                    ?>


                    <?php if (
                        in_array(
                            $current_status,
                            [
                                "Resolved",
                                "Completed"
                            ],
                            true
                        ) &&
                        !empty(
                            $complaint["staff_id"]
                        ) &&
                        $request_count < 2 &&
                        !$has_pending_request
                    ): ?>

                        <button
                            type="button"
                            class="reopen-btn"
                            onclick="toggleReopenForm(
                                <?= $complaint_id ?>
                            )"
                        >
                            🔄 Request Reopen
                        </button>

                    <?php endif; ?>


                </div>


                <!-- =====================================
                     REOPEN FORM
                     ===================================== -->

                <?php if (
                    in_array(
                        $current_status,
                        [
                            "Resolved",
                            "Completed"
                        ],
                        true
                    ) &&
                    !empty(
                        $complaint["staff_id"]
                    ) &&
                    $request_count < 2 &&
                    !$has_pending_request
                ): ?>

                    <div
                        class="reopen-form"
                        id="reopen-form-<?= $complaint_id ?>"
                    >

                        <h4>
                            🔄 Request Complaint Reopen
                        </h4>


                        <p>
                            If the work was not completed properly,
                            the wrong work was done, or the uploaded
                            completion photo is incorrect, explain
                            the issue below.
                        </p>


                        <form
                            method="POST"
                            enctype="multipart/form-data"
                        >

                            <input
                                type="hidden"
                                name="action"
                                value="request_reopen"
                            >


                            <input
                                type="hidden"
                                name="complaint_id"
                                value="<?= $complaint_id ?>"
                            >


                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(
                                    $csrf_token
                                ) ?>"
                            >


                            <label>
                                Reason *
                            </label>


                            <textarea
                                name="reason"
                                maxlength="1000"
                                required
                                placeholder="Explain why the complaint should be reopened..."
                            ></textarea>


                            <label>
                                Evidence Photo
                                (Optional)
                            </label>


                            <input
                                type="file"
                                name="reopen_photo"
                                accept="image/jpeg,image/png,image/webp"
                            >


                            <small>
                                JPG, PNG or WEBP.
                                Maximum 5 MB.
                            </small>


                            <br>


                            <button
                                type="submit"
                                class="submit-reopen"
                            >
                                Submit Reopen Request
                            </button>


                            <button
                                type="button"
                                class="cancel-reopen"
                                onclick="toggleReopenForm(
                                    <?= $complaint_id ?>
                                )"
                            >
                                Cancel
                            </button>

                        </form>

                    </div>

                <?php endif; ?>


                <!-- =====================================
                     REOPEN REQUEST STATUS
                     ===================================== -->

                <?php if (
                    !empty(
                        $complaint_requests
                    )
                ): ?>

                    <div class="request-status">

                        <h4>
                            🔄 Reopen Request Status
                        </h4>


                        <?php foreach (
                            $complaint_requests
                            as $req
                        ): ?>

                            <?php

                            $req_status =
                                strtolower(
                                    trim(
                                        $req["status"]
                                    )
                                );

                            ?>


                            <div class="request-row">

                                <strong>
                                    Request #<?= (int)$req["id"] ?>
                                </strong>


                                <br>


                                <span
                                    class="<?=
                                        $req_status === "pending"
                                            ? "request-pending"
                                            : (
                                                $req_status === "approved"
                                                    ? "request-approved"
                                                    : (
                                                        $req_status === "rejected"
                                                            ? "request-rejected"
                                                            : ""
                                                    )
                                            )
                                    ?>"
                                >

                                    Status:
                                    <?= e(
                                        $req["status"]
                                    ) ?>

                                </span>


                                <br>


                                <strong>
                                    Reason:
                                </strong>

                                <?= nl2br(
                                    e(
                                        $req["reason"]
                                    )
                                ) ?>


                                <br>


                                <small>

                                    Submitted:
                                    <?= e(
                                        $req["created_at"]
                                    ) ?>

                                </small>


                                <?php if (
                                    !empty(
                                        $req["admin_remark"]
                                    )
                                ): ?>

                                    <br><br>

                                    <strong>
                                        Admin Remark:
                                    </strong>

                                    <?= nl2br(
                                        e(
                                            $req["admin_remark"]
                                        )
                                    ) ?>

                                <?php endif; ?>


                                <?php if (
                                    !empty(
                                        $req["photo"]
                                    )
                                ): ?>

                                    <br><br>

                                    <strong>
                                        Your Evidence:
                                    </strong>

                                    <br>

                                    <img
                                        src="<?= e(
                                            photoPath(
                                                $req["photo"]
                                            )
                                        ) ?>"
                                        class="evidence-photo"
                                        alt="Reopen Evidence"
                                    >

                                <?php endif; ?>

                            </div>

                        <?php endforeach; ?>


                    </div>

                <?php endif; ?>


            </div>

        <?php endwhile; ?>


    <?php endif; ?>


</div>


<!-- =====================================================
     JAVASCRIPT
     ===================================================== -->

<script>

function toggleReopenForm(complaintId)
{
    const form =
        document.getElementById(
            "reopen-form-" + complaintId
        );

    if (!form) {
        return;
    }

    if (
        form.style.display === "block"
    ) {

        form.style.display = "none";

    } else {

        form.style.display = "block";

        form.scrollIntoView({
            behavior: "smooth",
            block: "center"
        });
    }
}

</script>


</body>

</html>

<?php

$stmt->close();

?>