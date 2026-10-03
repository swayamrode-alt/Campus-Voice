<?php

session_start();
require_once "../config/db.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $roll_no = trim($_POST["roll_no"] ?? "");
    $mobile = trim($_POST["mobile"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    /* Basic validation */
    if ($name === "" || $roll_no === "" || $mobile === "" || $username === "" || $password === "") {

        $error = "Please fill all details.";

    } elseif (!preg_match("/^[0-9]{10}$/", $mobile)) {

        $error = "Please enter a valid 10-digit mobile number.";

    } elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters.";

    } else {

        /* Check duplicate Roll No / Username / Mobile */
        $check_sql = "
            SELECT id
            FROM students
            WHERE roll_no = ?
               OR username = ?
               OR mobile = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($check_sql);

        if ($stmt) {

            $stmt->bind_param("sss", $roll_no, $username, $mobile);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows > 0) {

                $error = "Roll Number, Username or Mobile Number is already registered.";

            } else {

                /* Secure password */
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                /*
                 * New student starts as Pending.
                 * Admin must approve before login.
                 */
                $insert_sql = "
                    INSERT INTO students
                    (name, roll_no, mobile, username, password, status)
                    VALUES (?, ?, ?, ?, ?, 'Pending')
                ";

                $insert = $conn->prepare($insert_sql);

                if ($insert) {

                    $insert->bind_param(
                        "sssss",
                        $name,
                        $roll_no,
                        $mobile,
                        $username,
                        $hashed_password
                    );

                    if ($insert->execute()) {

                        $success = "Registration request submitted successfully. Please wait for admin approval.";

                    } else {

                        $error = "Registration failed. Please try again.";
                    }

                    $insert->close();

                } else {

                    $error = "Database error. Please try again.";
                }
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

    <title>Student Sign Up | CampusVoice</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            background: #f4f7fb;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 450px;
            background: white;
            padding: 35px;
            border-radius: 14px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .logo {
            text-align: center;
            color: #123c69;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        h2 {
            text-align: center;
            color: #222;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            color: #333;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 17px;
            border: 1px solid #ccc;
            border-radius: 7px;
            font-size: 14px;
        }

        input:focus {
            outline: none;
            border-color: #123c69;
        }

        button {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 7px;
            background: #123c69;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #0d3157;
        }

        .error {
            background: #ffe5e5;
            color: #b00020;
            padding: 11px;
            border-radius: 7px;
            margin-bottom: 18px;
            text-align: center;
        }

        .success {
            background: #e3f7e8;
            color: #176b2c;
            padding: 11px;
            border-radius: 7px;
            margin-bottom: 18px;
            text-align: center;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #123c69;
            text-decoration: none;
            font-weight: bold;
        }

        .info {
            text-align: center;
            color: #666;
            font-size: 13px;
            margin-top: 15px;
            line-height: 1.5;
        }

    </style>

</head>

<body>

<div class="container">

    <div class="logo">🎓 CampusVoice</div>

    <h2>Student Sign Up</h2>

    <?php if ($error !== ""): ?>

        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>

    <?php if ($success !== ""): ?>

        <div class="success">
            <?php echo htmlspecialchars($success); ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <label>Full Name</label>
        <input
            type="text"
            name="name"
            maxlength="100"
            required
        >

        <label>Roll Number</label>
        <input
            type="text"
            name="roll_no"
            maxlength="50"
            required
        >

        <label>Mobile Number</label>
        <input
            type="text"
            name="mobile"
            maxlength="10"
            pattern="[0-9]{10}"
            required
        >

        <label>Username</label>
        <input
            type="text"
            name="username"
            maxlength="50"
            required
        >

        <label>Password</label>
        <input
            type="password"
            name="password"
            minlength="8"
            required
        >

        <button type="submit">
            Create Student Account
        </button>

    </form>

    <div class="info">
        Your account will remain pending until the college administrator approves your request.
    </div>

    <div class="login-link">
        Already have an account?
        <a href="login.php">Login</a>
    </div>

</div>

</body>

</html>