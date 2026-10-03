<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}

require_once "../config/db.php";

/* =========================
   FILTERS
========================= */

$period = $_GET["period"] ?? "all";
$category = $_GET["category"] ?? "all";

$where = [];
$params = [];
$types = "";

/* DATE FILTER */

if ($period == "this_month") {

    $where[] = "MONTH(c.created_at) = MONTH(CURDATE())
                AND YEAR(c.created_at) = YEAR(CURDATE())";

} elseif ($period == "last_month") {

    $where[] = "MONTH(c.created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                AND YEAR(c.created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";

} elseif ($period == "last_3_months") {

    $where[] = "c.created_at >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";

} elseif ($period == "this_year") {

    $where[] = "YEAR(c.created_at) = YEAR(CURDATE())";
}

/* CATEGORY FILTER */

if ($category != "all") {

    $where[] = "c.category = ?";
    $params[] = $category;
    $types .= "s";
}

/* WHERE */

$whereSQL = "";

if (!empty($where)) {
    $whereSQL = " WHERE " . implode(" AND ", $where);
}


/* =========================
   SUMMARY
========================= */

$total = 0;
$resolved = 0;
$pending = 0;
$in_progress = 0;
$assigned = 0;


/* TOTAL */

$sql = "SELECT COUNT(*) AS total
        FROM complaints c
        $whereSQL";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$total = $row["total"];


/* STATUS COUNTS */

$statusSQL = "SELECT c.status, COUNT(*) AS total
              FROM complaints c
              $whereSQL
              GROUP BY c.status";

$stmt = $conn->prepare($statusSQL);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$statusResult = $stmt->get_result();

while ($row = $statusResult->fetch_assoc()) {

    $status = strtolower(trim($row["status"]));

    if ($status == "resolved" || $status == "completed") {
        $resolved += $row["total"];
    }

    elseif ($status == "pending") {
        $pending += $row["total"];
    }

    elseif ($status == "in progress") {
        $in_progress += $row["total"];
    }

    elseif ($status == "assigned") {
        $assigned += $row["total"];
    }
}


/* =========================
   CATEGORY REPORT
========================= */

$categoryData = [];

$catSQL = "SELECT c.category, COUNT(*) AS total
           FROM complaints c
           $whereSQL
           GROUP BY c.category
           ORDER BY total DESC";

$stmt = $conn->prepare($catSQL);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$catResult = $stmt->get_result();

while ($row = $catResult->fetch_assoc()) {
    $categoryData[] = $row;
}


/* Maximum category count */

$maxCategory = 1;

foreach ($categoryData as $item) {

    if ($item["total"] > $maxCategory) {
        $maxCategory = $item["total"];
    }
}


/* =========================
   STAFF PERFORMANCE
========================= */

$staffData = [];

$staffSQL = "
SELECT
    s.id,
    s.name,
    s.staff_id,

    COUNT(a.id) AS assigned,

    SUM(
        CASE
            WHEN a.status = 'Completed'
            OR a.status = 'Resolved'
            THEN 1
            ELSE 0
        END
    ) AS resolved,

    COALESCE(AVG(r.rating), 0) AS avg_rating

FROM staff s

LEFT JOIN assignments a
    ON s.id = a.staff_id

LEFT JOIN ratings r
    ON s.id = r.staff_id

GROUP BY s.id, s.name, s.staff_id

ORDER BY resolved DESC, avg_rating DESC
";

$staffResult = $conn->query($staffSQL);

if ($staffResult) {

    while ($row = $staffResult->fetch_assoc()) {

        $row["assigned"] = $row["assigned"] ?? 0;
        $row["resolved"] = $row["resolved"] ?? 0;

        $staffData[] = $row;
    }
}


/* =========================
   EXPORT CSV
========================= */

if (isset($_GET["export"]) && $_GET["export"] == "1") {

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=complaint_report.csv");

    $output = fopen("php://output", "w");

    fputcsv($output, [
        "Staff",
        "Staff ID",
        "Assigned",
        "Resolved",
        "Average Rating"
    ]);

    foreach ($staffData as $staff) {

        fputcsv($output, [
            $staff["name"],
            $staff["staff_id"],
            $staff["assigned"],
            $staff["resolved"],
            number_format((float)$staff["avg_rating"], 2)
        ]);
    }

    fclose($output);
    exit();
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Reports</title>

<link rel="stylesheet" href="../assets/css/style.css">

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
    max-width: 1100px;
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
    padding: 25px;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.07);
}

.filter-box {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 25px;
}

.filter-box select {
    padding: 10px;
    border: 1px solid #ccd5e0;
    border-radius: 7px;
}

.filter-btn {
    padding: 10px 18px;
    border: none;
    border-radius: 7px;
    background: #123c69;
    color: white;
    font-weight: bold;
    cursor: pointer;
}

.stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 30px;
}

.stat-card {
    background: #f8fafc;
    border: 1px solid #e1e7ef;
    border-radius: 9px;
    padding: 20px;
}

.stat-card .icon {
    font-size: 28px;
}

.stat-card h3 {
    color: #123c69;
    margin: 10px 0 5px;
}

.number {
    font-size: 27px;
    font-weight: bold;
    color: #123c69;
}

.section {
    margin-top: 30px;
}

.section h2 {
    color: #123c69;
    margin-bottom: 15px;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
}

.report-table th,
.report-table td {
    padding: 13px;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
}

.report-table th {
    color: #123c69;
}

.bar-row {
    margin-bottom: 18px;
}

.bar-title {
    display: flex;
    justify-content: space-between;
    margin-bottom: 6px;
    color: #444;
    font-size: 14px;
}

.bar {
    width: 100%;
    height: 10px;
    background: #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
}

.bar-fill {
    height: 100%;
    background: #123c69;
    border-radius: 10px;
}

.export-btn {
    display: inline-block;
    margin-top: 25px;
    padding: 12px 18px;
    background: #123c69;
    color: white;
    text-decoration: none;
    border-radius: 7px;
    font-weight: bold;
}

.note {
    margin-top: 20px;
    padding: 14px;
    background: #eef6ff;
    color: #315477;
    border-radius: 7px;
    font-size: 13px;
}

@media (max-width: 850px) {

    .stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {

    .page {
        padding: 15px;
    }

    .stats {
        grid-template-columns: 1fr;
    }

    .report-table {
        font-size: 13px;
    }

}

</style>

</head>

<body>

<div class="page">

<div class="container">

<a href="dashboard.php" class="back">
← Back to Dashboard
</a>

<div class="header">

<h1>Reports & Analytics</h1>

<p>
View complaint and staff performance reports.
</p>

</div>

<div class="content">


<!-- FILTER -->

<form method="GET" class="filter-box">

<select name="period">

<option value="all"
<?php if($period=="all") echo "selected"; ?>>
All Time
</option>

<option value="this_month"
<?php if($period=="this_month") echo "selected"; ?>>
This Month
</option>

<option value="last_month"
<?php if($period=="last_month") echo "selected"; ?>>
Last Month
</option>

<option value="last_3_months"
<?php if($period=="last_3_months") echo "selected"; ?>>
Last 3 Months
</option>

<option value="this_year"
<?php if($period=="this_year") echo "selected"; ?>>
This Year
</option>

</select>


<select name="category">

<option value="all">
All Categories
</option>

<?php

$categories = [
    "IT / Computer",
    "Library",
    "Laboratory",
    "Cleaning",
    "Electrical"
];

foreach ($categories as $cat) {

?>

<option value="<?php echo htmlspecialchars($cat); ?>"
<?php if($category==$cat) echo "selected"; ?>>

<?php echo htmlspecialchars($cat); ?>

</option>

<?php } ?>

</select>


<button type="submit" class="filter-btn">
Generate Report
</button>

</form>


<!-- SUMMARY -->

<div class="stats">


<div class="stat-card">

<div class="icon">📋</div>

<h3>Total Complaints</h3>

<div class="number">
<?php echo $total; ?>
</div>

</div>


<div class="stat-card">

<div class="icon">✅</div>

<h3>Resolved</h3>

<div class="number">
<?php echo $resolved; ?>
</div>

</div>


<div class="stat-card">

<div class="icon">⏳</div>

<h3>Pending</h3>

<div class="number">
<?php echo $pending; ?>
</div>

</div>


<div class="stat-card">

<div class="icon">⏱️</div>

<h3>In Progress</h3>

<div class="number">
<?php echo $in_progress; ?>
</div>

</div>


</div>


<!-- CATEGORY REPORT -->

<div class="section">

<h2>Complaints by Category</h2>


<?php if(empty($categoryData)): ?>

<p>No complaint data available.</p>

<?php else: ?>


<?php foreach($categoryData as $item): ?>

<div class="bar-row">

<div class="bar-title">

<span>
<?php echo htmlspecialchars($item["category"]); ?>
</span>

<strong>
<?php echo $item["total"]; ?>
</strong>

</div>


<div class="bar">

<div class="bar-fill"
style="width: <?php echo ($item["total"] / $maxCategory) * 100; ?>%;">
</div>

</div>

</div>

<?php endforeach; ?>


<?php endif; ?>

</div>


<!-- STAFF PERFORMANCE -->

<div class="section">

<h2>Staff Performance</h2>


<table class="report-table">

<tr>

<th>Staff</th>

<th>Assigned</th>

<th>Resolved</th>

<th>Rating</th>

</tr>


<?php if(empty($staffData)): ?>

<tr>

<td colspan="4">
No staff data available.
</td>

</tr>

<?php else: ?>


<?php foreach($staffData as $staff): ?>

<tr>

<td>

<?php echo htmlspecialchars($staff["name"]); ?>

<br>

<small>
ID: <?php echo htmlspecialchars($staff["staff_id"]); ?>
</small>

</td>


<td>

<?php echo $staff["assigned"]; ?>

</td>


<td>

<?php echo $staff["resolved"]; ?>

</td>


<td>

<?php

$rating = (float)$staff["avg_rating"];

if($rating > 0) {

    echo "⭐ " . number_format($rating, 1);

} else {

    echo "No rating";

}

?>

</td>

</tr>

<?php endforeach; ?>


<?php endif; ?>

</table>

</div>


<!-- EXPORT -->

<a href="?period=<?php echo urlencode($period); ?>&category=<?php echo urlencode($category); ?>&export=1"
class="export-btn">

📥 Export Report

</a>


<div class="note">

ℹ️ Report data is generated automatically from complaints,
assignments and staff ratings stored in the database.

</div>


</div>

</div>

</div>

</body>

</html>