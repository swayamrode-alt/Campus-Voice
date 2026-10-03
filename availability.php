<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Staff Availability</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .availability-page {
            min-height: 100vh;
            background: #f4f7fb;
            padding: 30px 20px;
        }

        .availability-container {
            max-width: 750px;
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

        .availability-box {
            background: white;
            padding: 30px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .current-status {
            background: #f8fafc;
            padding: 18px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .current-status strong {
            color: #123c69;
        }

        .status-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .status-option {
            border: 2px solid #e1e7ef;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: 0.2s;
        }

        .status-option:hover {
            border-color: #123c69;
        }

        .status-option input {
            display: none;
        }

        .status-option:has(input:checked) {
            border-color: #123c69;
            background: #eef6ff;
        }

        .status-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .status-name {
            font-weight: bold;
            color: #123c69;
        }

        .status-description {
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }

        .save-btn {
            width: 100%;
            margin-top: 25px;
            padding: 14px;
            border: none;
            border-radius: 7px;
            background: #123c69;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .info-note {
            margin-top: 20px;
            padding: 13px;
            background: #eef6ff;
            color: #315477;
            border-radius: 7px;
            font-size: 13px;
        }

        @media (max-width: 650px) {
            .status-options {
                grid-template-columns: 1fr;
            }

            .availability-page {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<div class="availability-page">

    <div class="availability-container">

        <a href="dashboard.php" class="back-link">
            ← Back to Dashboard
        </a>

        <div class="page-header">
            <h1>My Availability</h1>
            <p>Update your current work availability.</p>
        </div>

        <div class="availability-box">

            <div class="current-status">
                <strong>Current Status:</strong>
                🟢 Available
            </div>

            <form>

                <div class="status-options">

                    <label class="status-option">

                        <input
                            type="radio"
                            name="availability"
                            value="available"
                            checked
                        >

                        <div class="status-icon">🟢</div>

                        <div class="status-name">
                            Available
                        </div>

                        <div class="status-description">
                            Ready to accept new work
                        </div>

                    </label>


                    <label class="status-option">

                        <input
                            type="radio"
                            name="availability"
                            value="busy"
                        >

                        <div class="status-icon">🟡</div>

                        <div class="status-name">
                            Busy / Working
                        </div>

                        <div class="status-description">
                            Currently handling assigned work
                        </div>

                    </label>


                    <label class="status-option">

                        <input
                            type="radio"
                            name="availability"
                            value="absent"
                        >

                        <div class="status-icon">🔴</div>

                        <div class="status-name">
                            Absent
                        </div>

                        <div class="status-description">
                            Not available for work
                        </div>

                    </label>

                </div>

                <button type="submit" class="save-btn">
                    Save Availability
                </button>

            </form>

            <div class="info-note">
                ℹ️ Your availability status will be visible to the
                Admin. The Admin can use this information when assigning
                complaints or additional work.
            </div>

        </div>

    </div>

</div>

</body>
</html>