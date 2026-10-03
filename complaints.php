<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Available Complaints</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .complaints-page {
            min-height: 100vh;
            background: #f4f7fb;
            padding: 25px;
        }

        .complaints-container {
            max-width: 1100px;
            margin: auto;
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

        .complaints-box {
            background: white;
            padding: 25px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #123c69;
            text-decoration: none;
            font-weight: bold;
        }

        .filter-row {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .filter-row select {
            padding: 10px;
            border: 1px solid #d1d9e6;
            border-radius: 6px;
        }

        .complaint-card {
            border: 1px solid #e1e7ef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 18px;
        }

        .complaint-top {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }

        .complaint-title {
            font-size: 18px;
            font-weight: bold;
            color: #123c69;
        }

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            background: #fff3cd;
            color: #856404;
        }

        .complaint-info {
            margin-top: 15px;
            line-height: 1.7;
            color: #555;
        }

        .action-row {
            margin-top: 18px;
            display: flex;
            gap: 10px;
        }

        .action-row a,
        .action-row button {
            padding: 10px 15px;
            border-radius: 6px;
            border: none;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
        }

        .accept-btn {
            background: #123c69;
            color: white;
        }

        .details-btn {
            background: #e8f0ff;
            color: #123c69;
        }

        @media (max-width: 600px) {
            .complaints-page {
                padding: 15px;
            }

            .complaint-top {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<div class="complaints-page">

    <div class="complaints-container">

        <a href="dashboard.php" class="back-link">
            ← Back to Dashboard
        </a>

        <div class="page-header">
            <h1>Available Complaints</h1>
            <p>Complaints available for your assigned work area.</p>
        </div>

        <div class="complaints-box">

            <div class="filter-row">

                <select>
                    <option>All Categories</option>
                    <option>IT / Computer</option>
                    <option>Library</option>
                    <option>Laboratory</option>
                    <option>Cleaning</option>
                    <option>Electrical</option>
                    <option>Maintenance</option>
                </select>

                <select>
                    <option>All Priorities</option>
                    <option>Urgent</option>
                    <option>High</option>
                    <option>Medium</option>
                    <option>Low</option>
                </select>

            </div>


            <!-- Complaint 1 -->

            <div class="complaint-card">

                <div class="complaint-top">

                    <div class="complaint-title">
                        Computer not working in IT Lab
                    </div>

                    <span class="status">
                        Waiting for Staff
                    </span>

                </div>

                <div class="complaint-info">

                    <strong>Complaint ID:</strong> CMP-001<br>

                    <strong>Category:</strong> IT / Computer<br>

                    <strong>Location:</strong> IT Lab - 4th Floor<br>

                    <strong>Priority:</strong> High<br>

                    <strong>Reported:</strong> Today

                </div>

                <div class="action-row">

                    <button class="accept-btn">
                        Accept Complaint
                    </button>

                    <a href="complaint_details.php" class="details-btn">
                        View Details
                    </a>

                </div>

            </div>


            <!-- Complaint 2 -->

            <div class="complaint-card">

                <div class="complaint-top">

                    <div class="complaint-title">
                        Cleaning required near laboratory
                    </div>

                    <span class="status">
                        Waiting for Staff
                    </span>

                </div>

                <div class="complaint-info">

                    <strong>Complaint ID:</strong> CMP-005<br>

                    <strong>Category:</strong> Cleaning<br>

                    <strong>Location:</strong> Laboratory Section<br>

                    <strong>Priority:</strong> Medium<br>

                    <strong>Reported:</strong> Today

                </div>

                <div class="action-row">

                    <button class="accept-btn">
                        Accept Complaint
                    </button>

                    <a href="complaint_details.php" class="details-btn">
                        View Details
                    </a>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>