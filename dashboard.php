<?php
session_start();
require_once "../config/db.php";

// Student Login Guard
if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit();
}

$student_id = intval($_SESSION["student_id"]);

// Fetch Student Profile
$student_name = "Student";
$roll_no = "";
$username = "";

$stmt = $conn->prepare("SELECT name, roll_no, username FROM students WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $student_name = $row["name"];
        $roll_no = $row["roll_no"];
        $username = $row["username"];
    }
    $stmt->close();
}

// Fetch Counts
$total_complaints = 0; $pending_count = 0; $progress_count = 0; $resolved_count = 0;
$c_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status IN ('Assigned', 'In Progress') THEN 1 ELSE 0 END) as progress,
        SUM(CASE WHEN status IN ('Resolved', 'Completed') THEN 1 ELSE 0 END) as resolved
    FROM complaints 
    WHERE student_id = ?
");

if ($c_stmt) {
    $c_stmt->bind_param("i", $student_id);
    $c_stmt->execute();
    $c_res = $c_stmt->get_result();
    if ($c_row = $c_res->fetch_assoc()) {
        $total_complaints = (int)$c_row["total"];
        $pending_count = (int)$c_row["pending"];
        $progress_count = (int)$c_row["progress"];
        $resolved_count = (int)$c_row["resolved"];
    }
    $c_stmt->close();
}

// Fetch Complaints
$recent_complaints = [];
$r_stmt = $conn->prepare("SELECT id, title, category, location, priority, status, created_at FROM complaints WHERE student_id = ? ORDER BY created_at DESC LIMIT 5");
if ($r_stmt) {
    $r_stmt->bind_param("i", $student_id);
    $r_stmt->execute();
    $r_res = $r_stmt->get_result();
    while ($r_row = $r_res->fetch_assoc()) {
        $recent_complaints[] = $r_row;
    }
    $r_stmt->close();
}

// Unread Notifications Count
$unread_notifications = 0;
$n_stmt = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE student_id = ? AND (is_read = 0 OR is_read IS NULL)");
if ($n_stmt) {
    $n_stmt->bind_param("i", $student_id);
    $n_stmt->execute();
    $n_res = $n_stmt->get_result();
    if ($n_row = $n_res->fetch_assoc()) {
        $unread_notifications = (int)$n_row["unread"];
    }
    $n_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusVoice - Student Dashboard</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; color: #334155; min-height: 100vh; display: flex; }

        /* DARK SIDEBAR MATCHING IMAGE */
        .sidebar { width: 220px; background-color: #17202e; border-right: 1px solid #0f172a; display: flex; flex-direction: column; position: fixed; height: 100vh; top: 0; left: 0; z-index: 100; }
        .brand { padding: 16px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .brand-icon { font-size: 20px; color: #38bdf8; }
        .brand-text h2 { font-size: 15px; font-weight: 700; color: #ffffff; line-height: 1.1; }
        .brand-text span { font-size: 10px; color: #94a3b8; display: block; }
        .nav-menu { padding: 14px 10px; flex: 1; display: flex; flex-direction: column; gap: 4px; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #94a3b8; text-decoration: none; font-size: 13px; font-weight: 500; border-radius: 6px; transition: all 0.2s ease; }
        .nav-item:hover { background-color: rgba(255, 255, 255, 0.05); color: #ffffff; }
        .nav-item.active { background-color: #115e59; color: #34d399; font-weight: 600; }
        .nav-item i { width: 16px; font-size: 14px; text-align: center; }

        /* MAIN WRAPPER */
        .main-wrapper { margin-left: 220px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
        .topbar { height: 50px; background-color: #ffffff; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 90; }
        .search-box { position: relative; width: 260px; }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 12px; }
        .search-box input { width: 100%; background-color: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 6px; padding: 5px 12px 5px 32px; color: #1e293b; font-size: 12px; outline: none; }
        
        .topbar-right { display: flex; align-items: center; gap: 16px; font-size: 12px; color: #64748b; font-weight: 500; }
        .user-profile-badge { display: flex; align-items: center; gap: 8px; }

        .content { padding: 20px 24px; }
        .welcome-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 20px; margin-bottom: 20px; }
        .welcome-card h1 { font-size: 18px; font-weight: 700; color: #1e293b; }
        .welcome-card p { font-size: 12px; color: #64748b; margin-top: 2px; }
        .account-details-pills { display: flex; gap: 8px; margin-top: 10px; }
        .pill { background: #1e293b; color: #ffffff; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }

        /* SOLID STAT CARDS MATCHING EXACT IMAGE */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
        .stat-card { border-radius: 10px; padding: 20px 16px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: #ffffff; transition: transform 0.15s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .stat-card:hover { transform: translateY(-2px); }

        .stat-icon { width: 36px; height: 36px; border-radius: 8px; background: rgba(255, 255, 255, 0.2); display: flex; align-items: center; justify-content: center; font-size: 16px; margin-bottom: 10px; color: #ffffff; }

        /* Exact Background Colors */
        .card-total { background-color: #035388; }
        .card-pending { background-color: #d97706; }
        .card-progress { background-color: #0d828a; }
        .card-resolved { background-color: #0d8a56; }

        .stat-card .lbl { font-size: 12px; font-weight: 500; opacity: 0.9; }
        .stat-card .num { font-size: 20px; font-weight: 700; margin-top: 2px; }

        /* SOLID QUICK ACTION CARDS MATCHING EXACT IMAGE */
        .section-head { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 10px; }
        .actions-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        
        .action-card { border-radius: 10px; padding: 18px 14px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; text-decoration: none; font-size: 12px; font-weight: 600; color: #ffffff; position: relative; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .action-card:hover { transform: translateY(-2px); opacity: 0.95; }

        .act-icon-box { width: 34px; height: 34px; border-radius: 8px; background: rgba(255, 255, 255, 0.2); display: flex; align-items: center; justify-content: center; font-size: 15px; color: #ffffff; }

        .act-new { background-color: #b91c1c; }
        .act-my { background-color: #d97706; }
        .act-notif { background-color: #1d4ed8; }
        .act-feed { background-color: #6b21a8; }

        .badge-notif { position: absolute; top: 10px; right: 42%; transform: translateX(50%); background-color: #ef4444; color: white; border-radius: 10px; font-size: 10px; padding: 1px 6px; font-weight: 700; border: 1px solid #ffffff; }

        /* RECENT COMPLAINT CARD */
        .recent-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 24px; }
        .rc-id { font-size: 11px; font-weight: 700; color: #0284c7; }
        .rc-title { font-size: 13px; font-weight: 600; color: #0f172a; margin-top: 2px; margin-bottom: 10px; }
        
        .rc-pills { display: flex; gap: 12px; background-color: #f8fafc; padding: 8px 12px; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 11px; color: #64748b; }
        .p-medium { background: #fef3c7; color: #b45309; padding: 2px 8px; border-radius: 4px; font-weight: 600; }

        /* TRACK COMPLAINTS TABLE */
        .table-container { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; text-align: left; }
        th { background-color: #e2e8f0; color: #334155; font-weight: 700; padding: 10px 16px; font-size: 11px; border-bottom: 1px solid #cbd5e1; }
        td { padding: 10px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; font-weight: 500; }
        tr:last-child td { border-bottom: none; }

        .status-badge-med { background-color: #fef3c7; color: #b45309; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-badge-res { background-color: #22c55e; color: #ffffff; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; display: inline-block; }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="brand">
            <i class="fa-solid fa-graduation-cap brand-icon"></i>
            <div class="brand-text">
                <h2>CampusVoice</h2>
                <span>Swayam Website System</span>
            </div>
        </div>

        <div class="nav-menu">
            <a href="dashboard.php" class="nav-item active"><i class="fa-solid fa-border-all"></i><span>Dashboard</span></a>
            <a href="my_complaints.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i><span>My Complaints</span></a>
            <a href="notifications.php" class="nav-item"><i class="fa-solid fa-bell"></i><span>Notifications</span></a>
            <a href="feedback.php" class="nav-item"><i class="fa-solid fa-star"></i><span>Feedback</span></a>
            <a href="settings.php" class="nav-item"><i class="fa-solid fa-gear"></i><span>Settings</span></a>
        </div>
    </div>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">
        <div class="topbar">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Search dashboard...">
            </div>

            <div class="topbar-right">
                <span>14:15 | 2026-09-04</span>
                <div class="user-profile-badge">
                    <i class="fa-solid fa-user-check" style="color: #64748b;"></i>
                    <span>Verify It's you</span>
                </div>
            </div>
        </div>

        <div class="content">
            <!-- WELCOME HEADER -->
            <div class="welcome-card">
                <h1>Welcome back, <?php echo htmlspecialchars($student_name); ?>! 👋</h1>
                <p>Submit complaints, track their status, and receive updates from college staff.</p>

                <div class="account-details-pills">
                    <div class="pill">ROLL NO: <?php echo htmlspecialchars($roll_no); ?></div>
                    <div class="pill">USERNAME: <?php echo htmlspecialchars($username); ?></div>
                </div>
            </div>

            <!-- 4 SOLID COLOR STAT CARDS -->
            <div class="stats-grid">
                <div class="stat-card card-total">
                    <div class="stat-icon"><i class="fa-solid fa-file-lines"></i></div>
                    <div class="lbl">Total Complaints</div>
                    <div class="num"><?php echo $total_complaints; ?></div>
                </div>

                <div class="stat-card card-pending">
                    <div class="stat-icon"><i class="fa-solid fa-hourglass"></i></div>
                    <div class="lbl">Pending</div>
                    <div class="num"><?php echo $pending_count; ?></div>
                </div>

                <div class="stat-card card-progress">
                    <div class="stat-icon"><i class="fa-solid fa-rotate"></i></div>
                    <div class="lbl">In Progress</div>
                    <div class="num"><?php echo $progress_count; ?></div>
                </div>

                <div class="stat-card card-resolved">
                    <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="lbl">Resolved</div>
                    <div class="num"><?php echo $resolved_count; ?></div>
                </div>
            </div>

            <!-- QUICK ACTIONS (SOLID CARDS) -->
            <div class="section-head">Quick Actions</div>
            <div class="actions-grid">
                <a href="new_complaint.php" class="action-card act-new">
                    <div class="act-icon-box"><i class="fa-solid fa-pen-to-square"></i></div>
                    <span>New Complaint</span>
                </a>
                <a href="my_complaints.php" class="action-card act-my">
                    <div class="act-icon-box"><i class="fa-solid fa-folder-open"></i></div>
                    <span>My Complaints</span>
                </a>
                <a href="notifications.php" class="action-card act-notif">
                    <?php if($unread_notifications > 0): ?>
                        <span class="badge-notif"><?php echo $unread_notifications; ?></span>
                    <?php endif; ?>
                    <div class="act-icon-box"><i class="fa-solid fa-bell"></i></div>
                    <span>Notifications (<?php echo $unread_notifications; ?>)</span>
                </a>
                <a href="feedback.php" class="action-card act-feed">
                    <div class="act-icon-box"><i class="fa-solid fa-star"></i></div>
                    <span>Feedback & Rating</span>
                </a>
            </div>

            <!-- RECENT COMPLAINTS -->
            <div class="section-head">Recent Complaints</div>
            <?php if (!empty($recent_complaints)): $latest = $recent_complaints[0]; ?>
                <div class="recent-card">
                    <div class="rc-id">CMP-<?php echo str_pad($latest["id"], 3, "0", STR_PAD_LEFT); ?></div>
                    <div class="rc-title"><?php echo htmlspecialchars($latest["title"]); ?></div>
                    <div class="rc-pills">
                        <div>CATEGORY: <strong><?php echo htmlspecialchars($latest["category"]); ?></strong></div>
                        <div>LOCATION: <strong><?php echo htmlspecialchars($latest["location"]); ?></strong></div>
                        <div>PRIORITY: <span class="p-medium"><?php echo htmlspecialchars($latest["priority"]); ?></span></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TRACK COMPLAINTS TABLE -->
            <div class="section-head">Track Complaints</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>CMP-ID</th>
                            <th>CATEGORY</th>
                            <th>LOCATION</th>
                            <th>PRIORITY</th>
                            <th>STATUS</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_complaints)): ?>
                            <tr><td colspan="6" style="text-align:center;">No complaints found</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_complaints as $c): ?>
                                <tr>
                                    <td>CMP-<?php echo str_pad($c["id"], 3, "0", STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($c["category"]); ?></td>
                                    <td><?php echo htmlspecialchars($c["location"]); ?></td>
                                    <td><?php echo htmlspecialchars($c["priority"]); ?></td>
                                    <td><span class="status-badge-med"><?php echo htmlspecialchars($c["priority"]); ?></span></td>
                                    <td><span class="status-badge-res"><?php echo htmlspecialchars($c["status"]); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</body>
</html>