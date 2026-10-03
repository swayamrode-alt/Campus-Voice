<?php

session_start();

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit();
}

require_once "../config/db.php";

$student_id = (int) $_SESSION["student_id"];

$error = "";
$success = "";


/* =========================================================
   SUBMIT FEEDBACK
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $complaint_id = isset($_POST["complaint_id"])
        ? (int) $_POST["complaint_id"]
        : 0;

    $rating = isset($_POST["rating"])
        ? (int) $_POST["rating"]
        : 0;

    $feedback = trim(
        $_POST["feedback"] ?? ""
    );


    /* -----------------------------------------------------
       BASIC VALIDATION
    ----------------------------------------------------- */

    if (
        $complaint_id <= 0 ||
        $rating < 1 ||
        $rating > 5 ||
        $feedback === ""
    ) {

        $error =
            "Please provide a rating and feedback.";

    }

    elseif (strlen($feedback) > 1000) {

        $error =
            "Feedback must be less than 1000 characters.";

    }

    else {

        /* =================================================
           CHECK COMPLAINT
        ================================================= */

        $check_sql = "
            SELECT
                c.id,
                c.title,
                c.status,
                a.staff_id,
                s.name AS staff_name

            FROM complaints c

            INNER JOIN assignments a
                ON a.id = (
                    SELECT MAX(a2.id)
                    FROM assignments a2
                    WHERE a2.complaint_id = c.id
                )

            INNER JOIN staff s
                ON s.id = a.staff_id

            WHERE c.id = ?
            AND c.student_id = ?
            AND c.status IN
            (
                'Resolved',
                'Completed'
            )

            LIMIT 1
        ";


        $check_stmt =
            $conn->prepare($check_sql);


        if (!$check_stmt) {

            $error =
                "Unable to verify complaint. Please try again.";

        }

        else {

            $check_stmt->bind_param(
                "ii",
                $complaint_id,
                $student_id
            );


            if (!$check_stmt->execute()) {

                $error =
                    "Unable to verify complaint.";

            }

            else {

                $check_result =
                    $check_stmt->get_result();


                if ($check_result->num_rows !== 1) {

                    $error =
                        "Invalid complaint or complaint is not resolved.";

                }

                else {

                    $complaint =
                        $check_result->fetch_assoc();

                    $staff_id =
                        (int) $complaint["staff_id"];


                    /* =====================================
                       CHECK EXISTING RATING
                    ===================================== */

                    $existing_sql = "
                        SELECT id
                        FROM ratings
                        WHERE complaint_id = ?
                        AND student_id = ?
                        LIMIT 1
                    ";


                    $existing_stmt =
                        $conn->prepare(
                            $existing_sql
                        );


                    if (!$existing_stmt) {

                        $error =
                            "Unable to check existing feedback.";

                    }

                    else {

                        $existing_stmt->bind_param(
                            "ii",
                            $complaint_id,
                            $student_id
                        );


                        if (
                            !$existing_stmt->execute()
                        ) {

                            $error =
                                "Unable to check existing feedback.";

                        }

                        else {

                            $existing_result =
                                $existing_stmt->get_result();


                            if (
                                $existing_result->num_rows > 0
                            ) {

                                $error =
                                    "You have already submitted feedback for this complaint.";

                            }

                            else {

                                /* =================================
                                   INSERT INTO RATINGS
                                ================================= */

                                $insert_sql = "
                                    INSERT INTO ratings
                                    (
                                        complaint_id,
                                        student_id,
                                        staff_id,
                                        rating,
                                        feedback
                                    )
                                    VALUES
                                    (
                                        ?,
                                        ?,
                                        ?,
                                        ?,
                                        ?
                                    )
                                ";


                                $insert_stmt =
                                    $conn->prepare(
                                        $insert_sql
                                    );


                                if (!$insert_stmt) {

                                    $error =
                                        "Unable to submit feedback. Please try again.";

                                }

                                else {

                                    $insert_stmt->bind_param(
                                        "iiiis",
                                        $complaint_id,
                                        $student_id,
                                        $staff_id,
                                        $rating,
                                        $feedback
                                    );


                                    if (
                                        $insert_stmt->execute()
                                    ) {

                                        $success =
                                            "Feedback submitted successfully! ⭐";

                                    }

                                    else {

                                        $error =
                                            "Unable to submit feedback. Please try again.";

                                    }


                                    $insert_stmt->close();
                                }
                            }
                        }


                        $existing_stmt->close();
                    }
                }
            }


            $check_stmt->close();
        }
    }
}


/* =========================================================
   GET RESOLVED / COMPLETED COMPLAINTS
========================================================= */

$sql = "
    SELECT
        c.id,
        c.title,
        c.status,
        a.staff_id,
        s.name AS staff_name

    FROM complaints c

    INNER JOIN assignments a
        ON a.id = (
            SELECT MAX(a2.id)
            FROM assignments a2
            WHERE a2.complaint_id = c.id
        )

    INNER JOIN staff s
        ON s.id = a.staff_id

    WHERE c.student_id = ?

    AND c.status IN
    (
        'Resolved',
        'Completed'
    )

    ORDER BY c.created_at DESC
";


$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    die(
        "Unable to load feedback page."
    );

}


$stmt->bind_param(
    "i",
    $student_id
);


if (!$stmt->execute()) {

    $stmt->close();

    die(
        "Unable to load complaints."
    );

}


$result =
    $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Staff Feedback</title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        .feedback-page {

            min-height: 100vh;

            background: #f4f7fb;

            padding: 30px 20px;

        }


        .feedback-container {

            max-width: 700px;

            margin: auto;

        }


        .back-link {

            display: inline-block;

            margin-bottom: 20px;

            color: #123c69;

            text-decoration: none;

            font-weight: bold;

        }


        .feedback-header {

            background: #123c69;

            color: white;

            padding: 25px;

            border-radius: 12px 12px 0 0;

        }


        .feedback-header h1 {

            margin: 0 0 8px;

        }


        .feedback-header p {

            margin: 0;

            opacity: 0.9;

        }


        .feedback-form {

            background: white;

            padding: 30px;

            border-radius: 0 0 12px 12px;

            box-shadow:
                0 4px 15px rgba(0,0,0,0.08);

        }


        .message {

            padding: 12px;

            border-radius: 7px;

            margin-bottom: 20px;

            font-weight: bold;

        }


        .success {

            background: #d1fae5;

            color: #065f46;

        }


        .error {

            background: #fee2e2;

            color: #991b1b;

        }


        .info-box {

            background: #f8fafc;

            padding: 15px;

            border-radius: 8px;

            margin-bottom: 25px;

        }


        .info-box p {

            margin: 7px 0;

        }


        .form-group {

            margin-bottom: 22px;

        }


        .form-group label {

            display: block;

            font-weight: bold;

            margin-bottom: 8px;

        }


        .form-group textarea {

            width: 100%;

            padding: 12px;

            border: 1px solid #d1d9e6;

            border-radius: 7px;

            box-sizing: border-box;

            font-size: 15px;

            min-height: 120px;

            resize: vertical;

        }


        .rating-options {

            display: flex;

            gap: 10px;

            flex-wrap: wrap;

        }


        .rating-options input {

            display: none;

        }


        .rating-options label {

            cursor: pointer;

            font-size: 32px;

            color: #cbd5e1;

            transition: 0.2s;

        }


        .rating-options label:hover,

        .rating-options input:checked + label {

            color: #f59e0b;

        }


        .submit-btn {

            width: 100%;

            padding: 14px;

            background: #123c69;

            color: white;

            border: none;

            border-radius: 7px;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;

        }


        .submit-btn:hover {

            opacity: 0.9;

        }


        .empty {

            text-align: center;

            padding: 40px 20px;

            color: #666;

        }


    </style>

</head>


<body>


<div class="feedback-page">


    <div class="feedback-container">


        <a
            href="dashboard.php"
            class="back-link"
        >
            ← Back to Dashboard
        </a>


        <div class="feedback-header">

            <h1>
                Staff Feedback ⭐
            </h1>

            <p>
                Rate the staff member who handled your complaint.
            </p>

        </div>


        <div class="feedback-form">


            <?php if ($success !== ""): ?>

                <div class="message success">

                    <?php
                    echo htmlspecialchars(
                        $success
                    );
                    ?>

                </div>

            <?php endif; ?>


            <?php if ($error !== ""): ?>

                <div class="message error">

                    <?php
                    echo htmlspecialchars(
                        $error
                    );
                    ?>

                </div>

            <?php endif; ?>


            <?php if ($result->num_rows > 0): ?>


                <?php while (
                    $complaint =
                    $result->fetch_assoc()
                ): ?>


                    <?php

                    /* =====================================
                       CHECK IF ALREADY RATED
                    ===================================== */

                    $feedback_check_sql = "
                        SELECT id
                        FROM ratings
                        WHERE complaint_id = ?
                        AND student_id = ?
                        LIMIT 1
                    ";


                    $feedback_check_stmt =
                        $conn->prepare(
                            $feedback_check_sql
                        );


                    $already_rated = false;


                    if ($feedback_check_stmt) {

                        $feedback_check_stmt->bind_param(
                            "ii",
                            $complaint["id"],
                            $student_id
                        );


                        if (
                            $feedback_check_stmt->execute()
                        ) {

                            $feedback_check_result =
                                $feedback_check_stmt->get_result();


                            if (
                                $feedback_check_result->num_rows > 0
                            ) {

                                $already_rated = true;

                            }

                        }

                    }

                    ?>


                    <div class="info-box">


                        <p>

                            <strong>
                                Complaint ID:
                            </strong>

                            CMP-<?php

                            echo str_pad(
                                $complaint["id"],
                                3,
                                "0",
                                STR_PAD_LEFT
                            );

                            ?>

                        </p>


                        <p>

                            <strong>
                                Complaint:
                            </strong>

                            <?php

                            echo htmlspecialchars(
                                $complaint["title"]
                            );

                            ?>

                        </p>


                        <p>

                            <strong>
                                Staff:
                            </strong>

                            <?php

                            echo htmlspecialchars(
                                $complaint["staff_name"]
                            );

                            ?>

                        </p>


                        <p>

                            <strong>
                                Status:
                            </strong>

                            <?php

                            echo htmlspecialchars(
                                $complaint["status"]
                            );

                            ?>

                        </p>


                    </div>


                    <?php if ($already_rated): ?>


                        <div class="message success">

                            Feedback already submitted
                            for this complaint. ⭐

                        </div>


                    <?php else: ?>


                        <form
                            method="POST"
                            class="feedback-form"
                            style="
                                box-shadow:none;
                                padding:0;
                            "
                        >


                            <input
                                type="hidden"
                                name="complaint_id"
                                value="<?php
                                    echo (int)
                                    $complaint["id"];
                                ?>"
                            >


                            <div class="form-group">


                                <label>

                                    Your Rating

                                </label>


                                <div class="rating-options">


                                    <input
                                        type="radio"
                                        id="star1_<?php
                                            echo (int)
                                            $complaint["id"];
                                        ?>"
                                        name="rating"
                                        value="1"
                                        required
                                    >

                                    <label
                                        for="star1_<?php
                                            echo (int)
                                            $complaint["id"];
                                        ?>"
                                    >
                                        ★
                                    </label>


                                    <input
                                        type="radio"
                                        id="star2_<?php
                                            echo (int)
                                            $complaint["id"];
                                        ?>"
                                        name="rating"
                                        value="2"
                                    >

                                    <label
                                        for="star2_<?php
                                            echo (int)
                                            $complaint["id"];
                                        ?>"
                                    >
                                        ★
                                    </label>


                                    <input
                                        type="radio"
                                        id="star3_<?php
                                            echo (int)
                                            $complaint["id"];
                                        ?>"
                                        name="rating"
                                        value="3"
                                    >

                                    <label
                                        for="star3_<?php
                                            echo (int)
                                            $complaint["id"];
                                        ?>"
                                    >
                                        ★
                                    </label>


                                    <input
                                        type="radio"
                                        id="star4_<?php
                                            echo (int)
                                            $complaint["id"];
                                        ?>"
                                        name="rating"
                                        value="4"
                                    >

                                    <label
                                        for="star4_<?php
                                            echo (int)
                                            $complaint["id"];
                                        ?>"
                                    >
                                        ★
                                    </label>


                                    <input
                                        type="radio"
                                        id="star5_<?php
                                            echo (int)
                                            $complaint["id"];
                                        ?>"
                                        name="rating"
                                        value="5"
                                    >

                                    <label
                                        for="star5_<?php
                                            echo (int)
                                            $complaint["id"];
                                        ?>"
                                    >
                                        ★
                                    </label>


                                </div>

                            </div>


                            <div class="form-group">


                                <label>

                                    Feedback / Comment

                                </label>


                                <textarea
                                    name="feedback"
                                    placeholder="Tell us about your experience..."
                                    maxlength="1000"
                                    required
                                ></textarea>


                            </div>


                            <button
                                type="submit"
                                class="submit-btn"
                            >
                                Submit Feedback
                            </button>


                            <div
                                class="info-box"
                                style="margin-top:18px;"
                            >

                                🔒 Your rating and feedback
                                will be visible to the Admin
                                for staff performance evaluation.

                            </div>


                        </form>


                    <?php endif; ?>


                    <?php

                    if ($feedback_check_stmt) {

                        $feedback_check_stmt->close();

                    }

                    ?>


                <?php endwhile; ?>


            <?php else: ?>


                <div class="empty">


                    <h2>
                        No Feedback Available
                    </h2>


                    <p>

                        Feedback can be given after
                        a complaint is resolved.

                    </p>


                </div>


            <?php endif; ?>


        </div>


    </div>


</div>


</body>

</html>

<?php

$stmt->close();

?>