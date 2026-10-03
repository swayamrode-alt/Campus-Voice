<?php

session_start();

require_once "../config/db.php";


/* =========================================================
   STUDENT LOGIN CHECK
========================================================= */

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit();
}

$student_id = (int) $_SESSION["student_id"];


/* =========================================================
   VERIFY STUDENT ACCOUNT
========================================================= */

$student_sql = "
    SELECT
        id,
        status
    FROM students
    WHERE id = ?
    LIMIT 1
";

$student_stmt = $conn->prepare($student_sql);

if (!$student_stmt) {
    die("Unable to verify student account.");
}

$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();

$student_result = $student_stmt->get_result();

if ($student_result->num_rows !== 1) {

    $student_stmt->close();

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}

$student = $student_result->fetch_assoc();

$student_stmt->close();


/* =========================================================
   APPROVAL CHECK
========================================================= */

if (($student["status"] ?? "") !== "Approved") {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}


/* =========================================================
   CSRF TOKEN
========================================================= */

if (
    !isset($_SESSION["student_csrf_token"]) ||
    !is_string($_SESSION["student_csrf_token"]) ||
    strlen($_SESSION["student_csrf_token"]) < 32
) {
    $_SESSION["student_csrf_token"] =
        bin2hex(random_bytes(32));
}

$csrf_token =
    $_SESSION["student_csrf_token"];


$error = "";
$success = "";

$floor = "";
$location = "";
$description = "";


/* =========================================================
   COLLEGE STRUCTURE
========================================================= */

$locations = [

    "Ground Floor" => [
        "Canteen",
        "Girls Washroom",
        "Boys Washroom",
        "Room 7",
        "Room 6",
        "Room 5",
        "Room 4",
        "Room 3",
        "Room 2",
        "Auditorium",
        "Gymkhana",
        "Ground Floor Corridor"
    ],

    "1st Floor" => [
        "Room 13",
        "Room 12",
        "Room 11",
        "Room 10",
        "Room 9",
        "Room 8",
        "Department of Statistics",
        "Physics Lab 1",
        "Physics PhD Lab",
        "Boys Washroom",
        "Physics Lab 3",
        "Physics Lab 2",
        "1st Floor Corridor"
    ],

    "2nd Floor" => [
        "Library",
        "Chemistry Lab 1",
        "Chemistry Lab 2",
        "Chemistry Lab 3",
        "Chemistry PhD Centre",
        "2nd Floor Corridor"
    ],

    "3rd Floor" => [
        "Room 14",
        "Biology Lab 1",
        "Biology Lab 2",
        "Biology Lab 3",
        "Biology Lab 4",
        "Botanical Science Laboratory",
        "Room 22",
        "Room 21",
        "Room 20",
        "Room 19",
        "Room 18",
        "Room 17",
        "Room 16",
        "Room 15",
        "3rd Floor Corridor"
    ],

    "4th Floor" => [
        "Microbiology Laboratory",
        "Biotechnology Laboratory",
        "Computer Lab 1",
        "Computer Lab 2 (PG CS)",
        "Computer Lab 3 (PG IT)",
        "Computer Lab 4 (IT)",
        "Computer Lab 5 (Data Science)",
        "Computer Lab 6",
        "Boys Washroom",
        "Room 31",
        "Room 30",
        "Room 29",
        "Room 28",
        "Room 27",
        "Room 26",
        "Room 25",
        "Room 24",
        "4th Floor Corridor"
    ],

    "Quadrangle" => [
        "Main Quadrangle"
    ]
];


/* =========================================================
   AUTOMATIC CATEGORY
========================================================= */

function getCategory($location)
{
    $loc = strtolower($location);

    if (
        strpos($loc, "corridor") !== false ||
        strpos($loc, "quadrangle") !== false
    ) {
        return "Cleanliness";
    }

    if (strpos($loc, "canteen") !== false) {
        return "Canteen";
    }

    if (strpos($loc, "library") !== false) {
        return "Library";
    }

    if (strpos($loc, "washroom") !== false) {
        return "Cleanliness";
    }

    if (strpos($loc, "computer lab") !== false) {
        return "IT / Computer";
    }

    if (
        strpos($loc, "physics") !== false ||
        strpos($loc, "chemistry") !== false ||
        strpos($loc, "biology") !== false ||
        strpos($loc, "botanical") !== false ||
        strpos($loc, "microbiology") !== false ||
        strpos($loc, "biotechnology") !== false
    ) {
        return "Laboratory";
    }

    if (strpos($loc, "statistics") !== false) {
        return "Department";
    }

    if (strpos($loc, "gymkhana") !== false) {
        return "Gymkhana";
    }

    return "Classroom / Infrastructure";
}


/* =========================================================
   RELATED STAFF
   DISPLAY / NOTIFICATION PURPOSE ONLY
========================================================= */

function getStaffUsername($category, $floor, $location)
{
    if ($category === "IT / Computer") {
        return "rahul";
    }

    if ($category === "Library") {
        return "priya";
    }

    if ($category === "Laboratory") {
        return "mahesh";
    }

    if ($category === "Cleanliness") {

        if ($floor === "Ground Floor") {
            return "ramesh";
        }

        if ($floor === "1st Floor") {
            return "sunil";
        }

        if ($floor === "2nd Floor") {
            return "vijay";
        }

        if ($floor === "3rd Floor") {
            return "akash";
        }

        if ($floor === "4th Floor") {
            return "manoj";
        }

        if ($floor === "Quadrangle") {
            return "ramesh";
        }
    }

    return null;
}


/* =========================================================
   GET STAFF RECORD
========================================================= */

function getStaffRecord($conn, $username)
{
    if ($username === null) {
        return null;
    }

    $sql = "
        SELECT
            id,
            name,
            username,
            department
        FROM staff
        WHERE username = ?
        AND approval_status = 'Approved'
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    $staff = null;

    if ($result->num_rows === 1) {
        $staff = $result->fetch_assoc();
    }

    $stmt->close();

    return $staff;
}


/* =========================================================
   FORM SUBMISSION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* =====================================================
       CSRF CHECK
    ===================================================== */

    $submitted_csrf =
        $_POST["csrf_token"] ?? "";

    if (
        !is_string($submitted_csrf) ||
        !hash_equals(
            $csrf_token,
            $submitted_csrf
        )
    ) {

        $error =
            "Invalid form request. Please refresh the page and try again.";

    } else {

        $floor =
            trim($_POST["floor"] ?? "");

        $location =
            trim($_POST["location"] ?? "");

        $description =
            trim($_POST["description"] ?? "");


        /* =================================================
           VALIDATE FLOOR
        ================================================= */

        if (!isset($locations[$floor])) {

            $error =
                "Please select a valid floor.";

        }


        /* =================================================
           VALIDATE LOCATION
        ================================================= */

        elseif (
            !in_array(
                $location,
                $locations[$floor],
                true
            )
        ) {

            $error =
                "Please select a valid location.";

        }


        /* =================================================
           VALIDATE DESCRIPTION
        ================================================= */

        elseif ($description === "") {

            $error =
                "Please enter complaint description.";

        }

        elseif (strlen($description) > 5000) {

            $error =
                "Complaint description is too long.";

        }


        /* =================================================
           PROCESS COMPLAINT
        ================================================= */

        else {

            $category =
                getCategory($location);

            $title =
                $category .
                " complaint at " .
                $location;

            $priority =
                "Medium";

            $full_location =
                $floor .
                " - " .
                $location;


            /* =================================================
               PHOTO UPLOAD
            ================================================= */

            $photo_name = null;

            if (
                isset($_FILES["photo"]) &&
                $_FILES["photo"]["error"] !== UPLOAD_ERR_NO_FILE
            ) {

                if (
                    $_FILES["photo"]["error"] !== UPLOAD_ERR_OK
                ) {

                    $error =
                        "Photo upload failed.";

                }

                elseif (
                    !isset($_FILES["photo"]["tmp_name"]) ||
                    !is_uploaded_file(
                        $_FILES["photo"]["tmp_name"]
                    )
                ) {

                    $error =
                        "Invalid photo upload.";

                }

                elseif (
                    $_FILES["photo"]["size"] > 5 * 1024 * 1024
                ) {

                    $error =
                        "Photo size must be 5 MB or less.";

                }

                else {

                    $finfo =
                        new finfo(FILEINFO_MIME_TYPE);

                    $mime =
                        $finfo->file(
                            $_FILES["photo"]["tmp_name"]
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

                        $error =
                            "Only JPG, JPEG, PNG and WEBP images are allowed.";

                    }

                    else {

                        $upload_dir =
                            "../uploads/";


                        if (!is_dir($upload_dir)) {

                            if (
                                !mkdir(
                                    $upload_dir,
                                    0755,
                                    true
                                )
                            ) {

                                $error =
                                    "Unable to create upload directory.";

                            }

                        }


                        if ($error === "") {

                            $extension =
                                $allowed_mimes[$mime];


                            $photo_name =
                                "complaint_" .
                                bin2hex(
                                    random_bytes(16)
                                ) .
                                "." .
                                $extension;


                            $photo_path =
                                $upload_dir .
                                $photo_name;


                            if (
                                !move_uploaded_file(
                                    $_FILES["photo"]["tmp_name"],
                                    $photo_path
                                )
                            ) {

                                $error =
                                    "Unable to save uploaded photo.";

                                $photo_name = null;

                            }

                            else {

                                @chmod(
                                    $photo_path,
                                    0644
                                );

                            }

                        }

                    }

                }

            }


            /* =================================================
               INSERT COMPLAINT
            ================================================= */

            if ($error === "") {

                $sql = "
                    INSERT INTO complaints
                    (
                        student_id,
                        title,
                        description,
                        category,
                        location,
                        photo,
                        status,
                        priority
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'Pending',
                        ?
                    )
                ";

                $stmt =
                    $conn->prepare($sql);


                if (!$stmt) {

                    $error =
                        "Unable to submit complaint.";

                }

                else {

                    $stmt->bind_param(
                        "issssss",
                        $student_id,
                        $title,
                        $description,
                        $category,
                        $full_location,
                        $photo_name,
                        $priority
                    );


                    if ($stmt->execute()) {

                        $complaint_id =
                            $stmt->insert_id;


                        /* =================================================
                           FIND RELATED STAFF
                        ================================================= */

                        $staff_username =
                            getStaffUsername(
                                $category,
                                $floor,
                                $location
                            );


                        $related_staff =
                            getStaffRecord(
                                $conn,
                                $staff_username
                            );


                        /* =================================================
                           ADMIN NOTIFICATION
                        ================================================= */

                        $notification_message =
                            "New complaint CMP-" .
                            str_pad(
                                $complaint_id,
                                3,
                                "0",
                                STR_PAD_LEFT
                            ) .
                            " | " .
                            $full_location .
                            " | " .
                            $category;


                        $notification_sql = "
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


                        $notification_stmt =
                            $conn->prepare(
                                $notification_sql
                            );


                        if ($notification_stmt) {

                            $notification_stmt->bind_param(
                                "s",
                                $notification_message
                            );

                            $notification_stmt->execute();

                            $notification_stmt->close();

                        }


                        /* =================================================
                           STAFF NOTIFICATION
                        ================================================= */

                        if ($related_staff !== null) {

                            $staff_database_id =
                                (int) $related_staff["id"];


                            $staff_notification_message =
                                "New complaint CMP-" .
                                str_pad(
                                    $complaint_id,
                                    3,
                                    "0",
                                    STR_PAD_LEFT
                                ) .
                                " is available in your department. Any related staff member can accept it.";


                            $staff_notification_sql = "
                                INSERT INTO notifications
                                (
                                    staff_id,
                                    message
                                )
                                VALUES
                                (
                                    ?,
                                    ?
                                )
                            ";


                            $staff_notification_stmt =
                                $conn->prepare(
                                    $staff_notification_sql
                                );


                            if ($staff_notification_stmt) {

                                $staff_notification_stmt->bind_param(
                                    "is",
                                    $staff_database_id,
                                    $staff_notification_message
                                );

                                $staff_notification_stmt->execute();

                                $staff_notification_stmt->close();

                            }

                        }


                        /* =================================================
                           SUCCESS
                        ================================================= */

                        $success =
                            "Complaint submitted successfully. Complaint ID: CMP-" .
                            str_pad(
                                $complaint_id,
                                3,
                                "0",
                                STR_PAD_LEFT
                            );


                        if ($related_staff !== null) {

                            $success .=
                                " | Related staff have been notified. Any related staff member can accept the complaint.";

                        }

                        else {

                            $success .=
                                " | Admin can assign suitable staff if required.";

                        }


                        $floor = "";
                        $location = "";
                        $description = "";


                        /* New CSRF token after successful submission */

                        $_SESSION["student_csrf_token"] =
                            bin2hex(
                                random_bytes(32)
                            );

                        $csrf_token =
                            $_SESSION["student_csrf_token"];

                    }

                    else {

                        $error =
                            "Unable to submit complaint.";

                    }


                    $stmt->close();

                }

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

<title>New Complaint</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f7fb;
    color: #1f2937;
}

.page {
    min-height: 100vh;
    padding: 25px 15px 50px;
}

.container {
    max-width: 1000px;
    margin: auto;
}

.back-link {
    display: inline-block;
    margin-bottom: 18px;
    color: #123c69;
    font-weight: bold;
    text-decoration: none;
}

.header {
    background: #123c69;
    color: white;
    padding: 28px;
    border-radius: 14px 14px 0 0;
}

.header h1 {
    margin: 0 0 8px;
}

.header p {
    margin: 0;
    opacity: .9;
}

.form-card {
    background: white;
    padding: 30px;
    border-radius: 0 0 14px 14px;
    box-shadow: 0 5px 20px rgba(0,0,0,.08);
}

.error {
    background: #fee2e2;
    color: #991b1b;
    padding: 13px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.success {
    background: #dcfce7;
    color: #166534;
    padding: 13px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.section-title {
    color: #123c69;
    margin: 10px 0 15px;
}

.floor-grid {
    display: grid;
    grid-template-columns: repeat(3,1fr);
    gap: 14px;
}

.floor-btn {
    min-height: 85px;
    border: 2px solid #123c69;
    background: white;
    color: #123c69;
    border-radius: 10px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
}

.floor-btn:hover,
.floor-btn.active {
    background: #123c69;
    color: white;
}

#locationSection {
    display: none;
    margin-top: 30px;
}

#locationSection.show {
    display: block;
}

.location-grid {
    display: grid;
    grid-template-columns: repeat(3,1fr);
    gap: 12px;
}

.location-btn {
    min-height: 65px;
    padding: 10px;
    border: 2px solid #d4dce7;
    background: white;
    border-radius: 9px;
    cursor: pointer;
    font-weight: bold;
}

.location-btn:hover,
.location-btn.active {
    background: #123c69;
    color: white;
    border-color: #123c69;
}

.auto-details {
    display: none;
    margin-top: 20px;
}

.auto-details.show {
    display: block;
}

.auto-box {
    padding: 14px;
    margin-bottom: 10px;
    border-radius: 8px;
    background: #eef5ff;
    color: #123c69;
    font-weight: bold;
}

.staff-box {
    background: #ecfdf5;
    color: #166534;
}

.form-group {
    margin-top: 25px;
}

.form-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
}

textarea {
    width: 100%;
    min-height: 140px;
    padding: 13px;
    border: 1px solid #ccd5e0;
    border-radius: 8px;
    resize: vertical;
    font-size: 15px;
}

.photo {
    margin-top: 20px;
}

.photo label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
}

.submit-btn {
    width: 100%;
    padding: 15px;
    margin-top: 25px;
    border: none;
    border-radius: 8px;
    background: #123c69;
    color: white;
    font-size: 17px;
    font-weight: bold;
    cursor: pointer;
}

@media(max-width:700px) {

    .floor-grid,
    .location-grid {
        grid-template-columns: repeat(2,1fr);
    }

}

@media(max-width:450px) {

    .floor-grid,
    .location-grid {
        grid-template-columns: 1fr;
    }

    .form-card {
        padding: 20px;
    }

}

</style>

</head>

<body>

<div class="page">

<div class="container">

<a
    href="dashboard.php"
    class="back-link"
>
← Back to Dashboard
</a>

<div class="header">

<h1>
📢 Submit New Complaint
</h1>

<p>
Select floor and exact location.
</p>

</div>

<div class="form-card">

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


<form
    method="POST"
    enctype="multipart/form-data"
    id="complaintForm"
>


<!-- CSRF TOKEN -->

<input
    type="hidden"
    name="csrf_token"
    value="<?php echo htmlspecialchars($csrf_token); ?>"
>


<input
    type="hidden"
    name="floor"
    id="floorInput"
>

<input
    type="hidden"
    name="location"
    id="locationInput"
>


<h2 class="section-title">
1. Select Floor
</h2>


<div class="floor-grid">

<?php foreach (
    $locations
    as $floor_name => $places
): ?>

<button
    type="button"
    class="floor-btn"
    data-floor="<?php echo htmlspecialchars($floor_name); ?>"
>

<?php echo htmlspecialchars($floor_name); ?>

</button>

<?php endforeach; ?>

</div>


<div id="locationSection">

<h2
    class="section-title"
    id="locationHeading"
>
2. Select Location
</h2>


<div
    class="location-grid"
    id="locationGrid"
>
</div>

</div>


<div
    class="auto-details"
    id="autoDetails"
>

<div
    class="auto-box"
    id="categoryBox"
>
</div>

<div
    class="auto-box staff-box"
    id="staffBox"
>
</div>

</div>


<div class="form-group">

<label for="description">
3. Complaint Description
</label>

<textarea
    id="description"
    name="description"
    placeholder="Describe your problem..."
    maxlength="5000"
    required
></textarea>

</div>


<div class="photo">

<label for="photo">
📷 Upload Photo / Evidence
</label>

<input
    type="file"
    id="photo"
    name="photo"
    accept="image/jpeg,image/png,image/webp"
>

<p style="font-size:13px;color:#6b7280;">
Maximum file size: 5 MB. JPG, PNG and WEBP only.
</p>

</div>


<button
    type="submit"
    class="submit-btn"
>
Submit Complaint
</button>

</form>

</div>

</div>

</div>


<script>

const locations =
<?php echo json_encode($locations); ?>;


const floorButtons =
document.querySelectorAll(".floor-btn");

const floorInput =
document.getElementById("floorInput");

const locationInput =
document.getElementById("locationInput");

const locationSection =
document.getElementById("locationSection");

const locationHeading =
document.getElementById("locationHeading");

const locationGrid =
document.getElementById("locationGrid");

const autoDetails =
document.getElementById("autoDetails");

const categoryBox =
document.getElementById("categoryBox");

const staffBox =
document.getElementById("staffBox");


/* =========================================================
   CATEGORY
========================================================= */

function getCategory(location) {

    const loc =
        location.toLowerCase();

    if (
        loc.includes("corridor") ||
        loc.includes("quadrangle")
    ) {
        return "Cleanliness";
    }

    if (loc.includes("canteen")) {
        return "Canteen";
    }

    if (loc.includes("library")) {
        return "Library";
    }

    if (loc.includes("washroom")) {
        return "Cleanliness";
    }

    if (loc.includes("computer lab")) {
        return "IT / Computer";
    }

    if (
        loc.includes("physics") ||
        loc.includes("chemistry") ||
        loc.includes("biology") ||
        loc.includes("botanical") ||
        loc.includes("microbiology") ||
        loc.includes("biotechnology")
    ) {
        return "Laboratory";
    }

    if (loc.includes("statistics")) {
        return "Department";
    }

    if (loc.includes("gymkhana")) {
        return "Gymkhana";
    }

    return "Classroom / Infrastructure";
}


/* =========================================================
   STAFF DISPLAY
========================================================= */

function getStaff(category, floor) {

    if (category === "IT / Computer") {
        return "Rahul IT";
    }

    if (category === "Library") {
        return "Priya Library";
    }

    if (category === "Laboratory") {
        return "Mahesh Lab";
    }

    if (category === "Cleanliness") {

        if (floor === "Ground Floor")
            return "Ramesh Ground";

        if (floor === "1st Floor")
            return "Sunil 1st Floor";

        if (floor === "2nd Floor")
            return "Vijay 2nd Floor";

        if (floor === "3rd Floor")
            return "Akash 3rd Floor";

        if (floor === "4th Floor")
            return "Manoj 4th Floor";

        if (floor === "Quadrangle")
            return "Ramesh Ground";
    }

    return "Admin will assign suitable staff if required";
}


/* =========================================================
   FLOOR CLICK
========================================================= */

floorButtons.forEach(function(button) {

    button.addEventListener(
        "click",
        function() {

            const floor =
                this.dataset.floor;

            floorInput.value =
                floor;

            locationInput.value =
                "";

            floorButtons.forEach(
                function(btn) {

                    btn.classList.remove(
                        "active"
                    );

                }
            );

            this.classList.add(
                "active"
            );

            locationSection.classList.add(
                "show"
            );

            autoDetails.classList.remove(
                "show"
            );

            locationHeading.innerText =
                "2. Select Location - " +
                floor;

            locationGrid.innerHTML =
                "";

            locations[floor].forEach(
                function(place) {

                    const btn =
                        document.createElement(
                            "button"
                        );

                    btn.type =
                        "button";

                    btn.className =
                        "location-btn";

                    btn.innerText =
                        place;

                    btn.addEventListener(
                        "click",
                        function() {

                            document
                            .querySelectorAll(
                                ".location-btn"
                            )
                            .forEach(
                                function(item) {

                                    item.classList.remove(
                                        "active"
                                    );

                                }
                            );

                            this.classList.add(
                                "active"
                            );

                            locationInput.value =
                                place;

                            const category =
                                getCategory(
                                    place
                                );

                            const staff =
                                getStaff(
                                    category,
                                    floor
                                );

                            categoryBox.innerText =
                                "📂 Related Category: " +
                                category;

                            staffBox.innerText =
                                "👨‍🔧 Related Staff: " +
                                staff;

                            autoDetails.classList.add(
                                "show"
                            );

                        }
                    );

                    locationGrid.appendChild(
                        btn
                    );

                }
            );

        }
    );

});


/* =========================================================
   CLIENT-SIDE VALIDATION
========================================================= */

document
.getElementById("complaintForm")
.addEventListener(
    "submit",
    function(event) {

        if (
            floorInput.value === "" ||
            locationInput.value === ""
        ) {

            event.preventDefault();

            alert(
                "Please select Floor and Location."
            );

            return;
        }


        const photo =
            document.getElementById("photo");


        if (
            photo.files.length > 0 &&
            photo.files[0].size > 5 * 1024 * 1024
        ) {

            event.preventDefault();

            alert(
                "Photo size must be 5 MB or less."
            );

        }

    }
);

</script>

</body>

</html>