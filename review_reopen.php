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
   VERIFY ADMIN
========================= */

$admin_sql = "
    SELECT id, name, username
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

$admin_result =
    $admin_stmt->get_result();

if (
    $admin_result->num_rows !== 1
) {
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
   REQUEST ID
========================= */

$request_id =
    filter_input(
        INPUT_GET,
        "id",
        FILTER_VALIDATE_INT
    );

if (!$request_id || $request_id < 1) {
    header("Location: reopen_requests.php");
    exit();
}


/* =========================
   FETCH REQUEST
========================= */

$sql = "
    SELECT

        rr.id,
        rr.complaint_id,
        rr.student_id,
        rr.staff_id,
        rr.reason,
        rr.photo,
        rr.status,
        rr.admin_remark,
        rr.created_at,
        rr.reviewed_at,

        s.name AS student_name,
        s.roll_no,
        s.mobile,

        st.name AS staff_name,
        st.staff_id AS staff_code,
        st.department,

        c.title AS complaint_title,
        c.description,
        c.category,
        c.location,
        c.photo AS complaint_photo,
        c.after_photo,
        c.status AS complaint_status

    FROM reopen_requests rr

    INNER JOIN students s
        ON rr.student_id = s.id

    INNER JOIN staff st
        ON rr.staff_id = st.id

    INNER JOIN complaints c
        ON rr.complaint_id = c.id

    WHERE rr.id = ?

    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Unable to load reopen request.");
}

$stmt->bind_param("i", $request_id);
$stmt->execute();

$result =
    $stmt->get_result();

if ($result->num_rows !== 1) {

    $stmt->close();

    header("Location: reopen_requests.php");
    exit();
}

$request =
    $result->fetch_assoc();

$stmt->close();


/* =========================
   APPROVE / REJECT
========================= */

$error = "";
$success = "";

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    $csrf_token =
        $_POST["csrf_token"] ?? "";

    if (
        !is_string($csrf_token) ||
        !hash_equals(
            $_SESSION["admin_csrf_token"],
            $csrf_token
        )
    ) {

        $error =
            "Invalid request. Please try again.";

    } else {

        $action =
            $_POST["action"] ?? "";

        $remark =
            trim(
                (string)
                ($_POST["admin_remark"] ?? "")
            );

        if (
            strlen($remark) > 1000
        ) {

            $error =
                "Admin remark cannot exceed 1000 characters.";

        } elseif (
            $action !== "approve" &&
            $action !== "reject"
        ) {

            $error =
                "Invalid action.";

        } else {

            /*
             * Re-fetch request inside transaction
             * so it cannot be processed twice.
             */

            $conn->begin_transaction();

            try {

                $lock_sql = "
                    SELECT
                        id,
                        complaint_id,
                        student_id,
                        staff_id,
                        status
                    FROM reopen_requests
                    WHERE id = ?
                    LIMIT 1
                    FOR UPDATE
                ";

                $lock_stmt =
                    $conn->prepare(
                        $lock_sql
                    );

                if (!$lock_stmt) {
                    throw new Exception(
                        "Unable to process request."
                    );
                }

                $lock_stmt->bind_param(
                    "i",
                    $request_id
                );

                $lock_stmt->execute();

                $lock_result =
                    $lock_stmt->get_result();

                if (
                    $lock_result->num_rows !== 1
                ) {

                    $lock_stmt->close();

                    throw new Exception(
                        "Reopen request not found."
                    );
                }

                $locked =
                    $lock_result->fetch_assoc();

                $lock_stmt->close();


                if (
                    $locked["status"] !==
                    "Pending"
                ) {

                    throw new Exception(
                        "This reopen request has already been reviewed."
                    );
                }


                /* =========================
                   APPROVE
                ========================= */

                if (
                    $action === "approve"
                ) {

                    /*
                     * Reopen complaint.
                     *
                     * The complaint is returned to the
                     * Assigned state because the same previous
                     * staff member will receive it again.
                     */

                    $complaint_sql = "
                        UPDATE complaints
                        SET
                            status = 'Assigned',
                            updated_at = NOW()
                        WHERE id = ?
                        AND status IN ('Resolved', 'Completed')
                    ";

                    $complaint_stmt =
                        $conn->prepare(
                            $complaint_sql
                        );

                    if (!$complaint_stmt) {
                        throw new Exception(
                            "Unable to reopen complaint."
                        );
                    }

                    $complaint_stmt->bind_param(
                        "i",
                        $locked["complaint_id"]
                    );

                    $complaint_stmt->execute();

                    if (
                        $complaint_stmt->affected_rows !== 1
                    ) {

                        $complaint_stmt->close();

                        throw new Exception(
                            "Complaint cannot be reopened because its status has changed."
                        );
                    }

                    $complaint_stmt->close();


                    /*
                     * Restore previous staff assignment.
                     *
                     * The old assignment is currently Completed.
                     * Change it back to Accepted so the same staff
                     * member can start the work again.
                     */

                    $assignment_sql = "
                        UPDATE assignments
                        SET
                            status = 'Accepted',
                            assigned_at = NOW(),
                            completed_at = NULL
                        WHERE complaint_id = ?
                        AND staff_id = ?
                        AND status = 'Completed'
                        LIMIT 1
                    ";

                    $assignment_stmt =
                        $conn->prepare(
                            $assignment_sql
                        );

                    if (!$assignment_stmt) {
                        throw new Exception(
                            "Unable to restore staff assignment."
                        );
                    }

                    $assignment_stmt->bind_param(
                        "ii",
                        $locked["complaint_id"],
                        $locked["staff_id"]
                    );

                    $assignment_stmt->execute();

                    if (
                        $assignment_stmt->affected_rows !== 1
                    ) {

                        $assignment_stmt->close();

                        throw new Exception(
                            "Previous staff assignment could not be restored."
                        );
                    }

                    $assignment_stmt->close();


                    /*
                     * Mark request approved.
                     */

                    $update_sql = "
                        UPDATE reopen_requests
                        SET
                            status = 'Approved',
                            admin_remark = ?,
                            reviewed_at = NOW()
                        WHERE id = ?
                    ";

                    $update_stmt =
                        $conn->prepare(
                            $update_sql
                        );

                    if (!$update_stmt) {
                        throw new Exception(
                            "Unable to update request."
                        );
                    }

                    $update_stmt->bind_param(
                        "si",
                        $remark,
                        $request_id
                    );

                    $update_stmt->execute();

                    $update_stmt->close();


                    /*
                     * Notify student.
                     */

                    $student_message =
                        "Your reopen request for Complaint #" .
                        $locked["complaint_id"] .
                        " has been approved by admin. The complaint has been reopened and assigned to the previous staff member.";

                    $notify_student_sql = "
                        INSERT INTO notifications
                        (
                            student_id,
                            message,
                            is_read,
                            created_at
                        )
                        VALUES
                        (?, ?, 0, NOW())
                    ";

                    $notify_student_stmt =
                        $conn->prepare(
                            $notify_student_sql
                        );

                    if ($notify_student_stmt) {

                        $notify_student_stmt->bind_param(
                            "is",
                            $locked["student_id"],
                            $student_message
                        );

                        $notify_student_stmt->execute();

                        $notify_student_stmt->close();
                    }


                    /*
                     * Notify staff.
                     */

                    $staff_message =
                        "Complaint #" .
                        $locked["complaint_id"] .
                        " has been reopened by admin. The complaint has been assigned back to you. Please review and work on it again.";

                    $notify_staff_sql = "
                        INSERT INTO notifications
                        (
                            staff_id,
                            message,
                            is_read,
                            created_at
                        )
                        VALUES
                        (?, ?, 0, NOW())
                    ";

                    $notify_staff_stmt =
                        $conn->prepare(
                            $notify_staff_sql
                        );

                    if ($notify_staff_stmt) {

                        $notify_staff_stmt->bind_param(
                            "is",
                            $locked["staff_id"],
                            $staff_message
                        );

                        $notify_staff_stmt->execute();

                        $notify_staff_stmt->close();
                    }


                    /*
                     * Admin notification / log.
                     */

                    $admin_message =
                        "Reopen request #" .
                        $request_id .
                        " approved for Complaint #" .
                        $locked["complaint_id"] .
                        ". Complaint has been assigned back to the previous staff member.";

                    $notify_admin_sql = "
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

                    $notify_admin_stmt =
                        $conn->prepare(
                            $notify_admin_sql
                        );

                    if ($notify_admin_stmt) {

                        $notify_admin_stmt->bind_param(
                            "is",
                            $admin_id,
                            $admin_message
                        );

                        $notify_admin_stmt->execute();

                        $notify_admin_stmt->close();
                    }


                    $conn->commit();

                    header(
                        "Location: reopen_requests.php?success=approved"
                    );

                    exit();

                }


                /* =========================
                   REJECT
                ========================= */

                if (
                    $action === "reject"
                ) {

                    $update_sql = "
                        UPDATE reopen_requests
                        SET
                            status = 'Rejected',
                            admin_remark = ?,
                            reviewed_at = NOW()
                        WHERE id = ?
                    ";

                    $update_stmt =
                        $conn->prepare(
                            $update_sql
                        );

                    if (!$update_stmt) {
                        throw new Exception(
                            "Unable to update request."
                        );
                    }

                    $update_stmt->bind_param(
                        "si",
                        $remark,
                        $request_id
                    );

                    $update_stmt->execute();

                    $update_stmt->close();


                    /*
                     * Notify student.
                     */

                    $student_message =
                        "Your reopen request for Complaint #" .
                        $locked["complaint_id"] .
                        " was rejected by admin.";

                    if ($remark !== "") {

                        $student_message .=
                            " Admin remark: " .
                            $remark;
                    }

                    $notify_student_sql = "
                        INSERT INTO notifications
                        (
                            student_id,
                            message,
                            is_read,
                            created_at
                        )
                        VALUES
                        (?, ?, 0, NOW())
                    ";

                    $notify_student_stmt =
                        $conn->prepare(
                            $notify_student_sql
                        );

                    if ($notify_student_stmt) {

                        $notify_student_stmt->bind_param(
                            "is",
                            $locked["student_id"],
                            $student_message
                        );

                        $notify_student_stmt->execute();

                        $notify_student_stmt->close();
                    }


                    /*
                     * Admin notification.
                     */

                    $admin_message =
                        "Reopen request #" .
                        $request_id .
                        " rejected for Complaint #" .
                        $locked["complaint_id"] .
                        ".";

                    $notify_admin_sql = "
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

                    $notify_admin_stmt =
                        $conn->prepare(
                            $notify_admin_sql
                        );

                    if ($notify_admin_stmt) {

                        $notify_admin_stmt->bind_param(
                            "is",
                            $admin_id,
                            $admin_message
                        );

                        $notify_admin_stmt->execute();

                        $notify_admin_stmt->close();
                    }


                    $conn->commit();

                    header(
                        "Location: reopen_requests.php?success=rejected"
                    );

                    exit();
                }

            } catch (Throwable $e) {

                $conn->rollback();

                $error =
                    $e->getMessage();
            }
        }
    }
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

    <title>Review Reopen Request</title>

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
            max-width: 1000px;
            margin: auto;
        }

        .topbar {
            background: #123c69;
            color: white;
            padding: 22px;
            border-radius: 12px;

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .topbar h1 {
            margin: 0 0 6px;
        }

        .back-btn {
            background: white;
            color: #123c69;
            padding: 10px 15px;
            border-radius: 7px;
            text-decoration: none;
            font-weight: bold;
        }

        .section {
            background: white;
            margin-top: 20px;
            padding: 22px;
            border-radius: 10px;

            box-shadow:
                0 4px 15px rgba(0,0,0,0.06);
        }

        .section h2 {
            margin-top: 0;
            color: #123c69;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .info-box {
            background: #f8fafc;
            border: 1px solid #e1e7ef;
            padding: 15px;
            border-radius: 8px;
        }

        .label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .value {
            font-weight: bold;
            color: #123c69;
        }

        .reason {
            background: #fff8e1;
            border: 1px solid #f0d98c;
            padding: 15px;
            border-radius: 8px;
            white-space: pre-wrap;
        }

        .remark-box {
            width: 100%;
            min-height: 110px;
            resize: vertical;

            padding: 12px;

            border: 1px solid #d1d5db;
            border-radius: 7px;

            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        .photos {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .photo-box {
            background: #f8fafc;
            border: 1px solid #e1e7ef;
            padding: 12px;
            border-radius: 8px;
        }

        .photo-box h4 {
            margin-top: 0;
            color: #123c69;
        }

        .photo-box img {
            width: 100%;
            max-height: 260px;
            object-fit: cover;
            border-radius: 6px;
        }

        .no-photo {
            color: #777;
            font-size: 13px;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            border: none;
            padding: 12px 20px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
        }

        .approve {
            background: #137333;
            color: white;
        }

        .reject {
            background: #b42318;
            color: white;
        }

        .message {
            padding: 12px 15px;
            border-radius: 7px;
            margin-top: 20px;
        }

        .error {
            background: #fdecec;
            color: #b42318;
        }

        .already {
            background: #f1f5f9;
            color: #475569;
            padding: 12px 15px;
            border-radius: 7px;
            font-weight: bold;
        }

        @media (max-width: 700px) {

            .page {
                padding: 15px;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
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


        <!-- TOP BAR -->

        <div class="topbar">

            <div>

                <h1>
                    Review Reopen Request
                </h1>

                <div>
                    Complaint #<?php
                    echo (int)
                    $request["complaint_id"];
                    ?>
                </div>

            </div>

            <a
                href="reopen_requests.php"
                class="back-btn"
            >
                ← Back
            </a>

        </div>


        <?php if ($error !== ""): ?>

            <div class="message error">

                <?php
                echo htmlspecialchars(
                    $error
                );
                ?>

            </div>

        <?php endif; ?>


        <!-- STUDENT -->

        <div class="section">

            <h2>
                Student Details
            </h2>

            <div class="info-grid">

                <div class="info-box">

                    <div class="label">
                        Name
                    </div>

                    <div class="value">
                        <?php
                        echo htmlspecialchars(
                            (string)
                            $request["student_name"]
                        );
                        ?>
                    </div>

                </div>


                <div class="info-box">

                    <div class="label">
                        Roll No
                    </div>

                    <div class="value">
                        <?php
                        echo htmlspecialchars(
                            (string)
                            $request["roll_no"]
                        );
                        ?>
                    </div>

                </div>

            </div>

        </div>


        <!-- COMPLAINT -->

        <div class="section">

            <h2>
                Complaint Details
            </h2>

            <div class="info-grid">

                <div class="info-box">

                    <div class="label">
                        Title
                    </div>

                    <div class="value">
                        <?php
                        echo htmlspecialchars(
                            (string)
                            $request["complaint_title"]
                        );
                        ?>
                    </div>

                </div>


                <div class="info-box">

                    <div class="label">
                        Category
                    </div>

                    <div class="value">
                        <?php
                        echo htmlspecialchars(
                            (string)
                            $request["category"]
                        );
                        ?>
                    </div>

                </div>


                <div class="info-box">

                    <div class="label">
                        Location
                    </div>

                    <div class="value">
                        <?php
                        echo htmlspecialchars(
                            (string)
                            $request["location"]
                        );
                        ?>
                    </div>

                </div>


                <div class="info-box">

                    <div class="label">
                        Current Status
                    </div>

                    <div class="value">
                        <?php
                        echo htmlspecialchars(
                            (string)
                            $request["complaint_status"]
                        );
                        ?>
                    </div>

                </div>

            </div>


            <br>

            <div class="label">
                Description
            </div>

            <div class="reason">
                <?php
                echo htmlspecialchars(
                    (string)
                    $request["description"]
                );
                ?>
            </div>

        </div>


        <!-- STAFF -->

        <div class="section">

            <h2>
                Assigned Staff
            </h2>

            <div class="info-grid">

                <div class="info-box">

                    <div class="label">
                        Staff Name
                    </div>

                    <div class="value">
                        <?php
                        echo htmlspecialchars(
                            (string)
                            $request["staff_name"]
                        );
                        ?>
                    </div>

                </div>


                <div class="info-box">

                    <div class="label">
                        Staff ID
                    </div>

                    <div class="value">
                        <?php
                        echo htmlspecialchars(
                            (string)
                            $request["staff_code"]
                        );
                        ?>
                    </div>

                </div>


                <div class="info-box">

                    <div class="label">
                        Department
                    </div>

                    <div class="value">
                        <?php
                        echo htmlspecialchars(
                            (string)
                            $request["department"]
                        );
                        ?>
                    </div>

                </div>

            </div>

        </div>


        <!-- REOPEN REASON -->

        <div class="section">

            <h2>
                Student's Reopen Reason
            </h2>

            <div class="reason">

                <?php
                echo htmlspecialchars(
                    (string)
                    $request["reason"]
                );
                ?>

            </div>

        </div>


        <!-- PHOTOS -->

        <div class="section">

            <h2>
                Evidence & Complaint Photos
            </h2>

            <div class="photos">


                <div class="photo-box">

                    <h4>
                        Original Photo
                    </h4>

                    <?php if (
                        !empty(
                            $request["complaint_photo"]
                        )
                    ): ?>

                        <img
                            src="../uploads/<?php
                            echo rawurlencode(
                                basename(
                                    $request["complaint_photo"]
                                )
                            );
                            ?>"
                            alt="Original complaint photo"
                        >

                    <?php else: ?>

                        <div class="no-photo">
                            No original photo.
                        </div>

                    <?php endif; ?>

                </div>


                <div class="photo-box">

                    <h4>
                        Staff After Photo
                    </h4>

                    <?php if (
                        !empty(
                            $request["after_photo"]
                        )
                    ): ?>

                        <img
                            src="../uploads/<?php
                            echo rawurlencode(
                                basename(
                                    $request["after_photo"]
                                )
                            );
                            ?>"
                            alt="Staff after photo"
                        >

                    <?php else: ?>

                        <div class="no-photo">
                            No staff after photo.
                        </div>

                    <?php endif; ?>

                </div>


                <div class="photo-box">

                    <h4>
                        Student Evidence
                    </h4>

                    <?php if (
                        !empty(
                            $request["photo"]
                        )
                    ): ?>

                        <img
                            src="../uploads/<?php
                            echo rawurlencode(
                                basename(
                                    $request["photo"]
                                )
                            );
                            ?>"
                            alt="Student evidence"
                        >

                    <?php else: ?>

                        <div class="no-photo">
                            No evidence photo.
                        </div>

                    <?php endif; ?>

                </div>


            </div>

        </div>


        <!-- ADMIN ACTION -->

        <div class="section">

            <h2>
                Admin Decision
            </h2>


            <?php if (
                $request["status"] === "Pending"
            ): ?>


                <form
                    method="POST"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php
                        echo htmlspecialchars(
                            $_SESSION[
                                "admin_csrf_token"
                            ]
                        );
                        ?>"
                    >


                    <label>
                        Admin Remark
                    </label>

                    <br><br>

                    <textarea
                        name="admin_remark"
                        class="remark-box"
                        maxlength="1000"
                        placeholder="Optional remark..."
                    ></textarea>


                    <br><br>


                    <div class="actions">

                        <button
                            type="submit"
                            name="action"
                            value="approve"
                            class="btn approve"
                        >
                            ✅ Approve Reopen
                        </button>


                        <button
                            type="submit"
                            name="action"
                            value="reject"
                            class="btn reject"
                        >
                            ❌ Reject Request
                        </button>

                    </div>

                </form>


            <?php else: ?>


                <div class="already">

                    This request has already been reviewed.

                    Status:
                    <?php
                    echo htmlspecialchars(
                        (string)
                        $request["status"]
                    );
                    ?>


                    <?php if (
                        !empty(
                            $request["admin_remark"]
                        )
                    ): ?>

                        <br><br>

                        Admin Remark:
                        <?php
                        echo htmlspecialchars(
                            (string)
                            $request["admin_remark"]
                        );
                        ?>

                    <?php endif; ?>

                </div>

            <?php endif; ?>


        </div>


    </div>

</div>

</body>

</html>