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
   GET REOPEN REQUESTS
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

        s.name AS student_name,
        s.roll_no,

        st.name AS staff_name,

        c.title AS complaint_title,
        c.category,
        c.location,
        c.status AS complaint_status

    FROM reopen_requests rr

    INNER JOIN students s
        ON rr.student_id = s.id

    INNER JOIN staff st
        ON rr.staff_id = st.id

    INNER JOIN complaints c
        ON rr.complaint_id = c.id

    ORDER BY
        CASE
            WHEN rr.status = 'Pending' THEN 0
            ELSE 1
        END,
        rr.created_at DESC
";

$stmt = $conn->prepare($sql);

$result = false;

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
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

    <title>Reopen Requests</title>

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
            max-width: 1250px;
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

        .topbar p {
            margin: 0;
            opacity: 0.9;
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

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 13px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #f8fafc;
            color: #123c69;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .pending {
            background: #fff3cd;
            color: #856404;
        }

        .approved {
            background: #e8f7ee;
            color: #137333;
        }

        .rejected {
            background: #fdecec;
            color: #b42318;
        }

        .review-btn {
            display: inline-block;
            background: #123c69;
            color: white;
            padding: 8px 13px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 13px;
        }

        .review-btn:hover {
            opacity: 0.9;
        }

        .empty {
            text-align: center;
            padding: 35px;
            color: #666;
        }

        @media (max-width: 700px) {

            .page {
                padding: 15px;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            table {
                font-size: 13px;
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
                    Reopen Requests
                </h1>

                <p>
                    Review student requests to reopen resolved complaints.
                </p>

            </div>

            <a
                href="dashboard.php"
                class="back-btn"
            >
                ← Dashboard
            </a>

        </div>


        <!-- REQUESTS -->

        <div class="section">

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Student
                            </th>

                            <th>
                                Complaint
                            </th>

                            <th>
                                Category
                            </th>

                            <th>
                                Staff
                            </th>

                            <th>
                                Complaint Status
                            </th>

                            <th>
                                Request Status
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php

                    if (
                        $result &&
                        $result->num_rows > 0
                    ):

                        while (
                            $row =
                            $result->fetch_assoc()
                        ):

                            $request_status =
                                strtolower(
                                    trim(
                                        (string)
                                        $row["status"]
                                    )
                                );

                            if (
                                $request_status ===
                                "pending"
                            ) {

                                $status_class =
                                    "pending";

                            } elseif (
                                $request_status ===
                                "approved"
                            ) {

                                $status_class =
                                    "approved";

                            } else {

                                $status_class =
                                    "rejected";

                            }

                    ?>

                        <tr>

                            <td>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        (string)
                                        $row["student_name"]
                                    );
                                    ?>
                                </strong>

                                <br>

                                <small>
                                    Roll No:
                                    <?php
                                    echo htmlspecialchars(
                                        (string)
                                        $row["roll_no"]
                                    );
                                    ?>
                                </small>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    (string)
                                    $row["complaint_title"]
                                );
                                ?>

                                <br>

                                <small>
                                    Complaint ID:
                                    <?php
                                    echo (int)
                                    $row["complaint_id"];
                                    ?>
                                </small>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    (string)
                                    $row["category"]
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    (string)
                                    $row["staff_name"]
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    (string)
                                    $row["complaint_status"]
                                );
                                ?>

                            </td>


                            <td>

                                <span
                                    class="status
                                    <?php
                                    echo htmlspecialchars(
                                        $status_class
                                    );
                                    ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        (string)
                                        $row["status"]
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    (string)
                                    $row["created_at"]
                                );
                                ?>

                            </td>


                            <td>

                                <a
                                    href="review_reopen.php?id=<?php echo (int) $row["id"]; ?>"
                                    class="review-btn"
                                >
                                    Review
                                </a>

                            </td>

                        </tr>

                    <?php

                        endwhile;

                    else:

                    ?>

                        <tr>

                            <td
                                colspan="8"
                                class="empty"
                            >

                                No reopen requests found.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


    </div>

</div>

</body>

</html>

<?php

if ($stmt) {
    $stmt->close();
}

?>