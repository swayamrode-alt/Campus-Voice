<?php

session_start();

require_once "../config/db.php";

/* =========================================================
   1. STUDENT LOGIN CHECK
========================================================= */

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit();
}

$student_id = (int) $_SESSION["student_id"];


/* =========================================================
   2. VERIFY STUDENT ACCOUNT
========================================================= */

$student_sql = "
    SELECT id, status
    FROM students
    WHERE id = ?
    LIMIT 1
";

$student_stmt = $conn->prepare($student_sql);

if (!$student_stmt) {
    die("Something went wrong.");
}

$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();

$student_result = $student_stmt->get_result();

if ($student_result->num_rows === 0) {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}

$student = $student_result->fetch_assoc();

$student_stmt->close();


/* =========================================================
   3. STUDENT APPROVAL CHECK
========================================================= */

if (
    !isset($student["status"]) ||
    strtolower(trim($student["status"])) !== "approved"
) {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}


/* =========================================================
   4. CSRF TOKEN
========================================================= */

if (!isset($_SESSION["student_csrf_token"])) {

    $_SESSION["student_csrf_token"] =
        bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["student_csrf_token"];


/* =========================================================
   5. VALIDATE COMPLAINT ID
========================================================= */

if (
    !isset($_GET["complaint_id"]) ||
    !ctype_digit($_GET["complaint_id"])
) {
    die("Invalid complaint ID.");
}

$complaint_id = (int) $_GET["complaint_id"];

if ($complaint_id <= 0) {
    die("Invalid complaint ID.");
}


/* =========================================================
   6. GET COMPLAINT
   IMPORTANT:
   Complaint must belong to logged-in student.
========================================================= */

$sql = "
    SELECT
        c.id,
        c.title,
        c.status,
        s.id AS staff_id,
        s.name AS staff_name,
        s.staff_id AS staff_code

    FROM complaints c

    LEFT JOIN assignments a
        ON a.complaint_id = c.id

    LEFT JOIN staff s
        ON a.staff_id = s.id

    WHERE c.id = ?
    AND c.student_id = ?

    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Something went wrong.");
}

$stmt->bind_param(
    "ii",
    $complaint_id,
    $student_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $stmt->close();

    die("Complaint not found.");
}

$complaint = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   7. CHECK STAFF ASSIGNMENT
========================================================= */

if (
    empty($complaint["staff_id"]) ||
    empty($complaint["staff_name"])
) {
    die("No staff member is assigned to this complaint.");
}


/* =========================================================
   8. CHECK COMPLAINT STATUS
========================================================= */

if (
    $complaint["status"] !== "Completed" &&
    $complaint["status"] !== "Resolved"
) {
    die("This complaint is not completed yet.");
}


/* =========================================================
   9. CHECK IF ALREADY RATED
========================================================= */

$check_sql = "
    SELECT id
    FROM ratings
    WHERE complaint_id = ?
    AND student_id = ?
    LIMIT 1
";

$check_stmt = $conn->prepare($check_sql);

if (!$check_stmt) {
    die("Something went wrong.");
}

$check_stmt->bind_param(
    "ii",
    $complaint_id,
    $student_id
);

$check_stmt->execute();

$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {

    $check_stmt->close();

    die("You have already rated this complaint.");
}

$check_stmt->close();


/* =========================================================
   10. HANDLE RATING SUBMISSION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    /* -----------------------------------------------------
       CSRF CHECK
    ----------------------------------------------------- */

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals(
            $_SESSION["student_csrf_token"],
            $_POST["csrf_token"]
        )
    ) {

        $error = "Invalid request. Please try again.";

    } else {


        /* -------------------------------------------------
           RATING
        ------------------------------------------------- */

        $rating = isset($_POST["rating"])
            ? (int) $_POST["rating"]
            : 0;


        /* -------------------------------------------------
           FEEDBACK
        ------------------------------------------------- */

        $feedback = trim(
            $_POST["feedback"] ?? ""
        );


        /* -------------------------------------------------
           VALIDATE RATING
        ------------------------------------------------- */

        if ($rating < 1 || $rating > 5) {

            $error =
                "Please select a rating between 1 and 5.";

        }

        /* -------------------------------------------------
           VALIDATE FEEDBACK LENGTH
        ------------------------------------------------- */

        elseif (mb_strlen($feedback) > 1000) {

            $error =
                "Feedback cannot exceed 1000 characters.";

        }

        else {


            /* ---------------------------------------------
               RE-CHECK COMPLAINT OWNERSHIP
               This is important because POST requests
               should not rely only on the earlier GET check.
            --------------------------------------------- */

            $verify_sql = "
                SELECT
                    c.id,
                    c.status,
                    s.id AS staff_id

                FROM complaints c

                INNER JOIN assignments a
                    ON a.complaint_id = c.id

                INNER JOIN staff s
                    ON a.staff_id = s.id

                WHERE c.id = ?
                AND c.student_id = ?
                AND (
                    c.status = 'Completed'
                    OR c.status = 'Resolved'
                )

                LIMIT 1
            ";

            $verify_stmt = $conn->prepare($verify_sql);

            if (!$verify_stmt) {

                $error =
                    "Unable to submit rating.";

            } else {

                $verify_stmt->bind_param(
                    "ii",
                    $complaint_id,
                    $student_id
                );

                $verify_stmt->execute();

                $verify_result =
                    $verify_stmt->get_result();

                if ($verify_result->num_rows === 0) {

                    $error =
                        "You are not authorized to rate this complaint.";

                } else {

                    $verified =
                        $verify_result->fetch_assoc();

                    $verified_staff_id =
                        (int) $verified["staff_id"];


                    /* -------------------------------------
                       CHECK AGAIN FOR DUPLICATE RATING
                    ------------------------------------- */

                    $duplicate_sql = "
                        SELECT id
                        FROM ratings
                        WHERE complaint_id = ?
                        AND student_id = ?
                        LIMIT 1
                    ";

                    $duplicate_stmt =
                        $conn->prepare($duplicate_sql);

                    if (!$duplicate_stmt) {

                        $error =
                            "Unable to submit rating.";

                    } else {

                        $duplicate_stmt->bind_param(
                            "ii",
                            $complaint_id,
                            $student_id
                        );

                        $duplicate_stmt->execute();

                        $duplicate_result =
                            $duplicate_stmt->get_result();


                        if (
                            $duplicate_result->num_rows > 0
                        ) {

                            $error =
                                "You have already rated this complaint.";

                        } else {


                            /* -----------------------------
                               INSERT RATING
                            ----------------------------- */

                            $insert_sql = "
                                INSERT INTO ratings
                                (
                                    complaint_id,
                                    student_id,
                                    staff_id,
                                    rating,
                                    feedback
                                )
                                VALUES (?, ?, ?, ?, ?)
                            ";

                            $insert_stmt =
                                $conn->prepare($insert_sql);

                            if (!$insert_stmt) {

                                $error =
                                    "Unable to submit rating.";

                            } else {

                                $insert_stmt->bind_param(
                                    "iiiis",
                                    $complaint_id,
                                    $student_id,
                                    $verified_staff_id,
                                    $rating,
                                    $feedback
                                );


                                if (
                                    $insert_stmt->execute()
                                ) {

                                    $success = true;

                                } else {

                                    /*
                                     * Do not expose database
                                     * error details to student.
                                     */

                                    $error =
                                        "Unable to submit rating. Please try again.";
                                }

                                $insert_stmt->close();
                            }
                        }

                        $duplicate_stmt->close();
                    }
                }

                $verify_stmt->close();
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

    <title>Rate Staff</title>

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
            padding: 30px 20px;
        }

        .container {
            max-width: 600px;
            margin: auto;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #123c69;
            text-decoration: none;
            font-weight: bold;
        }

        .box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        h1 {
            color: #123c69;
            margin-top: 0;
        }

        .complaint {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .complaint strong {
            color: #123c69;
        }

        .staff {
            background: #eef6ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .staff-name {
            font-size: 18px;
            font-weight: bold;
            color: #123c69;
        }

        .staff-id {
            color: #666;
            margin-top: 5px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .stars {
            display: flex;
            gap: 8px;
            margin-bottom: 25px;
        }

        .stars input {
            display: none;
        }

        .stars label {
            font-size: 38px;
            cursor: pointer;
            color: #ccc;
            margin: 0;
        }

        .stars input:checked + label {
            color: #f5b301;
        }

        textarea {
            width: 100%;
            min-height: 120px;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            resize: vertical;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        button {
            width: 100%;
            margin-top: 20px;
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
            background: #0d3155;
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
            border-radius: 8px;
            font-weight: bold;
            margin-bottom: 20px;
        }

    </style>

</head>

<body>

<div class="page">

    <div class="container">

        <a href="my_complaints.php" class="back">
            ← Back to My Complaints
        </a>

        <div class="box">

            <h1>⭐ Rate Staff</h1>

            <?php if (isset($success)): ?>

                <div class="success">
                    ✅ Rating submitted successfully!
                    <br><br>
                    Thank you for your feedback.
                </div>

                <a
                    href="my_complaints.php"
                    class="back"
                >
                    ← Return to My Complaints
                </a>

            <?php else: ?>

                <div class="complaint">

                    <strong>Complaint:</strong>

                    <br><br>

                    CMP-<?php echo str_pad(
                        $complaint["id"],
                        3,
                        "0",
                        STR_PAD_LEFT
                    ); ?>

                    —
                    <?php echo htmlspecialchars(
                        $complaint["title"],
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>

                </div>


                <div class="staff">

                    <div class="staff-name">

                        👨‍🔧

                        <?php echo htmlspecialchars(
                            $complaint["staff_name"],
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>

                    </div>

                    <div class="staff-id">

                        Staff ID:

                        <?php echo htmlspecialchars(
                            $complaint["staff_code"],
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>

                    </div>

                </div>


                <?php if (isset($error)): ?>

                    <div class="error">

                        <?php echo htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>

                    </div>

                <?php endif; ?>


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

                    <label>
                        How would you rate the staff?
                    </label>


                    <div class="stars">

                        <input
                            type="radio"
                            name="rating"
                            id="star1"
                            value="1"
                        >

                        <label for="star1">★</label>


                        <input
                            type="radio"
                            name="rating"
                            id="star2"
                            value="2"
                        >

                        <label for="star2">★</label>


                        <input
                            type="radio"
                            name="rating"
                            id="star3"
                            value="3"
                        >

                        <label for="star3">★</label>


                        <input
                            type="radio"
                            name="rating"
                            id="star4"
                            value="4"
                        >

                        <label for="star4">★</label>


                        <input
                            type="radio"
                            name="rating"
                            id="star5"
                            value="5"
                        >

                        <label for="star5">★</label>

                    </div>


                    <label>
                        Feedback (Optional)
                    </label>

                    <textarea
                        name="feedback"
                        maxlength="1000"
                        placeholder="Write your feedback about the staff..."
                    ></textarea>


                    <button type="submit">
                        Submit Rating
                    </button>

                </form>

            <?php endif; ?>

        </div>

    </div>

</div>

</body>

</html>