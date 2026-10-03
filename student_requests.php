<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}

require_once "../config/db.php";

$message = "";
$error = "";


/* =========================
   ACCEPT / REJECT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $student_id = (int)($_POST["student_id"] ?? 0);
    $action = $_POST["action"] ?? "";

    if ($student_id <= 0) {

        $error = "Invalid student request.";

    } elseif (!in_array($action, ["approve", "reject"], true)) {

        $error = "Invalid action.";

    } else {

        $new_status = ($action === "approve")
            ? "Approved"
            : "Rejected";

        $sql = "
            UPDATE students
            SET status = ?
            WHERE id = ?
            AND status = 'Pending'
        ";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param(
                "si",
                $new_status,
                $student_id
            );

            if ($stmt->execute()) {

                if ($stmt->affected_rows === 1) {

                    if ($action === "approve") {
                        $message =
                            "Student account approved successfully.";
                    } else {
                        $message =
                            "Student registration rejected.";
                    }

                } else {

                    $error =
                        "This request was already processed.";
                }

            } else {

                $error =
                    "Unable to update student request.";
            }

            $stmt->close();

        } else {

            $error =
                "Database error. Please try again.";
        }
    }
}


/* =========================
   GET PENDING STUDENTS
========================= */

$sql = "
    SELECT
        id,
        name,
        roll_no,
        mobile,
        username,
        created_at
    FROM students
    WHERE status = 'Pending'
    ORDER BY created_at ASC
";

$result = $conn->query($sql);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Student Requests | Admin</title>

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
            max-width: 1200px;
            margin: auto;
        }

        .topbar {
            background: #123c69;
            color: white;
            padding: 22px 25px;
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

        .back {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .section {
            background: white;
            margin-top: 22px;
            padding: 25px;
            border-radius: 10px;

            box-shadow:
                0 4px 15px rgba(0,0,0,0.06);
        }

        .message,
        .error {
            padding: 12px 15px;
            border-radius: 7px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .message {
            background: #e3f7e8;
            color: #176b2c;
        }

        .error {
            background: #ffe5e5;
            color: #b00020;
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
            white-space: nowrap;
        }

        th {
            background: #f8fafc;
            color: #123c69;
        }

        .approve,
        .reject {
            border: none;
            padding: 9px 13px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .approve {
            background: #137333;
            color: white;
        }

        .reject {
            background: #b42318;
            color: white;
        }

        .empty {
            text-align: center;
            padding: 30px;
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

            th,
            td {
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
                    Student Registration Requests
                </h1>

                <p>
                    Review and approve new student accounts
                </p>

            </div>

            <a
                href="dashboard.php"
                class="back"
            >
                ← Dashboard
            </a>

        </div>


        <!-- REQUESTS -->

        <div class="section">

            <?php if ($message !== ""): ?>

                <div class="message">
                    <?php
                    echo htmlspecialchars($message);
                    ?>
                </div>

            <?php endif; ?>


            <?php if ($error !== ""): ?>

                <div class="error">
                    <?php
                    echo htmlspecialchars($error);
                    ?>
                </div>

            <?php endif; ?>


            <h2 style="color:#123c69;">
                Pending Requests
            </h2>


            <?php if ($result && $result->num_rows > 0): ?>

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>Name</th>
                                <th>Roll No</th>
                                <th>Mobile</th>
                                <th>Username</th>
                                <th>Requested On</th>
                                <th>Action</th>

                            </tr>

                        </thead>

                        <tbody>


                        <?php while (
                            $student =
                            $result->fetch_assoc()
                        ): ?>

                            <tr>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student["name"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student["roll_no"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student["mobile"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student["username"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student["created_at"]
                                    );
                                    ?>
                                </td>

                                <td>

                                    <!-- ACCEPT -->

                                    <form
                                        method="POST"
                                        style="display:inline;"
                                    >

                                        <input
                                            type="hidden"
                                            name="student_id"
                                            value="<?php
                                                echo (int)
                                                    $student["id"];
                                            ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="action"
                                            value="approve"
                                            class="approve"
                                            onclick="return confirm('Approve this student account?');"
                                        >
                                            ✅ Accept
                                        </button>

                                    </form>


                                    <!-- REJECT -->

                                    <form
                                        method="POST"
                                        style="display:inline;"
                                    >

                                        <input
                                            type="hidden"
                                            name="student_id"
                                            value="<?php
                                                echo (int)
                                                    $student["id"];
                                            ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="action"
                                            value="reject"
                                            class="reject"
                                            onclick="return confirm('Reject this student registration?');"
                                        >
                                            ❌ Reject
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty">
                    🎉 No pending student registration requests.
                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

</body>

</html>