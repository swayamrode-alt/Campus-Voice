<?php

session_start();

require_once "../config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {

        $error = "Please enter username and password.";

    } else {

        $sql = "
            SELECT
                id,
                name,
                roll_no,
                mobile,
                username,
                password,
                status
            FROM students
            WHERE username = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param("s", $username);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $student = $result->fetch_assoc();

                /* Check account status */

                if ($student["status"] === "Pending") {

                    $error = "Your registration request is still pending. Please wait for admin approval.";

                } elseif ($student["status"] === "Rejected") {

                    $error = "Your registration request has been rejected by the administrator.";

                } elseif ($student["status"] !== "Approved") {

                    $error = "Your account is not approved for login.";

                } else {

                    /*
                     * Check new hashed password
                     */

                    $password_correct = password_verify(
                        $password,
                        $student["password"]
                    );

                    /*
                     * If old account has plain-text password,
                     * allow it once and convert it to hash.
                     */

                    if (!$password_correct && $password === $student["password"]) {

                        $password_correct = true;

                        $new_hash = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                        $update_sql = "
                            UPDATE students
                            SET password = ?
                            WHERE id = ?
                        ";

                        $update_stmt = $conn->prepare($update_sql);

                        if ($update_stmt) {

                            $update_stmt->bind_param(
                                "si",
                                $new_hash,
                                $student["id"]
                            );

                            $update_stmt->execute();
                            $update_stmt->close();
                        }
                    }

                    /*
                     * Password wrong
                     */

                    if (!$password_correct) {

                        $error = "Invalid username or password.";

                    } else {

                        /*
                         * Login successful
                         */

                        session_regenerate_id(true);

                        $_SESSION["student_id"] = $student["id"];
                        $_SESSION["student_name"] = $student["name"];
                        $_SESSION["student_roll_no"] = $student["roll_no"];
                        $_SESSION["student_username"] = $student["username"];

                        header("Location: dashboard.php");
                        exit();
                    }
                }

            } else {

                $error = "Invalid username or password.";
            }

            $stmt->close();

        } else {

            $error = "Database error. Please try again.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Login | CampusVoice</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f7fb;

    min-height: 100vh;

    display: flex;
    justify-content: center;
    align-items: center;
}

.login-container {
    width: 100%;
    max-width: 420px;

    background: white;

    padding: 35px;

    border-radius: 14px;

    box-shadow: 0 8px 30px rgba(0,0,0,0.10);
}

.logo {
    text-align: center;
    margin-bottom: 25px;
}

.logo h1 {
    color: #174574;
    font-size: 30px;
    margin-bottom: 8px;
}

.logo p {
    color: #666;
    font-size: 14px;
}

.error {
    background: #ffe8e8;
    color: #b00020;

    border: 1px solid #ffb5b5;

    padding: 12px;

    border-radius: 7px;

    margin-bottom: 18px;

    font-size: 14px;
}

.form-group {
    margin-bottom: 18px;
}

label {
    display: block;

    margin-bottom: 7px;

    font-weight: bold;

    color: #333;
}

input {
    width: 100%;

    padding: 13px;

    border: 1px solid #ccc;

    border-radius: 7px;

    font-size: 15px;

    outline: none;
}

input:focus {
    border-color: #174574;
}

.login-btn {
    width: 100%;

    padding: 13px;

    border: none;

    border-radius: 7px;

    background: #174574;

    color: white;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;
}

.login-btn:hover {
    background: #0f3559;
}

.signup {
    text-align: center;

    margin-top: 20px;

    font-size: 14px;

    color: #555;
}

.signup a {
    color: #174574;

    font-weight: bold;

    text-decoration: none;
}

.back {
    text-align: center;

    margin-top: 15px;
}

.back a {
    text-decoration: none;

    color: #666;

    font-size: 14px;
}

</style>

</head>

<body>

<div class="login-container">

    <div class="logo">

        <h1>🎓 CampusVoice</h1>

        <p>Student Complaint Management System</p>

    </div>


    <?php if ($error !== ""): ?>

        <div class="error">

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <form method="POST" action="">

        <div class="form-group">

            <label for="username">
                Username
            </label>

            <input
                type="text"
                id="username"
                name="username"
                placeholder="Enter your username"
                required
            >

        </div>


        <div class="form-group">

            <label for="password">
                Password
            </label>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                required
            >

        </div>


        <button
            type="submit"
            class="login-btn"
        >
            Login as Student
        </button>

    </form>


    <div class="signup">

        Don't have an account?

        <a href="signup.php">
            Sign Up
        </a>

    </div>


    <div class="back">

        <a href="../index.php">
            ← Back to Home
        </a>

    </div>

</div>

</body>

</html>