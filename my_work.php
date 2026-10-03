<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Work</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .work-page {
            min-height: 100vh;
            background: #f4f7fb;
            padding: 30px 20px;
        }

        .work-container {
            max-width: 1100px;
            margin: auto;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #123c69;
            text-decoration: none;
            font-weight: bold;
        }

        .page-header {
            background: #123c69;
            color: white;
            padding: 25px;
            border-radius: 12px 12px 0 0;
        }

        .page-header h1 {
            margin: 0 0 8px;
        }

        .page-header p {
            margin: 0;
            opacity: 0.9;
        }

        .work-box {
            background: white;
            padding: 25px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .work-card {
            border: 1px solid #e1e7ef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 18px;
        }

        .work-top {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            align-items: center;
        }

        .work-title {
            font-size: 18px;
            font-weight: bold;
            color: #123c69;
        }

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .accepted {
            background: #dbeafe;
            color: #1e40af;
        }

        .progress {
            background: #e0e7ff;
            color: #3730a3;
        }

        .resolved {
            background: #d1fae5;
            color: #065f46;
        }

        .work-details {
            margin-top: 15px;
            line-height: 1.7;
            color: #555;
        }

        .action-row {
            margin-top: 18px;
        }

        .details-btn {
            display: inline-block;
            padding: 10px 16px;
            background: #e8f0ff;
            color: #123c69;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }

        .empty-message {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }

        @media (max-width: 600px) {
            .work-page {
                padding: 15px;
            }

            .work-top {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

<div class="work-page">

    <div class="work-container">

        <a href="dashboard.php" class="back-link">
            ← Back to Dashboard
        </a>

        <div class="page-header">
            <h1>My Work</h1>
            <p>Complaints accepted or assigned to you.</p>
        </div>

        <div class="work-box">

            <!-- Active Work -->

            <div class="work-card">

                <div class="work-top">

                    <div class="work-title">
                        Computer not working in IT Lab
                    </div>

                    <span class="status accepted">
                        Accepted
                    </span>

                </div>

                <div class="work-details">

                    <strong>Complaint ID:</strong> CMP-001<br>

                    <strong>Category:</strong> IT / Computer<br>

                    <strong>Location:</strong> IT Lab - 4th Floor<br>

                    <strong>Priority:</strong> High<br>

                    <strong>Accepted:</strong> Today

                </div>

                <div class="action-row">

                    <a href="work_details.php" class="details-btn">
                        Open Work
                    </a>

                </div>

            </div>


            <!-- Work In Progress -->

            <div class="work-card">

                <div class="work-top">

                    <div class="work-title">
                        Library fan not working
                    </div>

                    <span class="status progress">
                        In Progress
                    </span>

                </div>

                <div class="work-details">

                    <strong>Complaint ID:</strong> CMP-002<br>

                    <strong>Category:</strong> Electrical<br>

                    <strong>Location:</strong> Library<br>

                    <strong>Priority:</strong> Medium

                </div>

                <div class="action-row">

                    <a href="work_details.php" class="details-btn">
                        Open Work
                    </a>

                </div>

            </div>


            <!-- Completed Work -->

            <div class="work-card">

                <div class="work-top">

                    <div class="work-title">
                        Classroom cleaning request
                    </div>

                    <span class="status resolved">
                        Resolved
                    </span>

                </div>

                <div class="work-details">

                    <strong>Complaint ID:</strong> CMP-003<br>

                    <strong>Category:</strong> Cleaning<br>

                    <strong>Location:</strong> Classroom<br>

                    <strong>Priority:</strong> Low

                </div>

                <div class="action-row">

                    <a href="work_details.php" class="details-btn">
                        View Work
                    </a>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>