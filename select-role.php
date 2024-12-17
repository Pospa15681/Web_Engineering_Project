<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Role - Job Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .role-selection {
            text-align: center;
            margin: 40px 0;
        }

        .role-selection h2 {
            font-size: 2.5em;
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .role-selection p {
            color: #666;
            font-size: 1.1em;
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .roles-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            max-width: 900px;
            margin: 0 auto;
        }

        .role-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .role-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, #2193b0, #6dd5ed);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .role-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .role-card:hover::before {
            transform: scaleX(1);
        }

        .role-icon {
            font-size: 3.5em;
            margin-bottom: 20px;
            display: inline-block;
            background: linear-gradient(135deg, #2193b0, #6dd5ed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .role-title {
            font-size: 1.8em;
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .role-description {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 1em;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2193b0, #6dd5ed);
            color: white;
            box-shadow: 0 5px 15px rgba(33, 147, 176, 0.3);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #2193b0;
            border: 2px solid #2193b0;
        }

        .btn:hover {
            transform: translateY(-3px);
        }

        .btn-primary:hover {
            box-shadow: 0 8px 25px rgba(33, 147, 176, 0.4);
        }

        .btn-secondary:hover {
            background: #2193b0;
            color: white;
        }

        @media (max-width: 768px) {
            .roles-grid {
                grid-template-columns: 1fr;
                padding: 0 20px;
            }

            .role-selection h2 {
                font-size: 2em;
            }

            .button-group {
                flex-direction: column;
            }

            .role-card {
                padding: 30px 20px;
            }
        }

        .wave {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: url('assets/images/wave.svg') repeat-x;
            animation: wave 10s linear infinite;
            z-index: -1;
            opacity: 0.5;
        }

        @keyframes wave {
            0% { background-position-x: 0; }
            100% { background-position-x: 1000px; }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <h1>Job Portal</h1>
            <ul>
                <li><a href="index.php">Home</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="role-selection">
            <h2>Choose Your Path</h2>
            <p>Whether you're looking for your next career opportunity or seeking talented professionals, we've got you covered.</p>
            
            <div class="roles-grid">
                <div class="role-card">
                    <div class="role-icon">👔</div>
                    <h3 class="role-title">Job Seeker</h3>
                    <p class="role-description">
                        Find your dream job and apply to exciting opportunities that match your skills and aspirations.
                    </p>
                    <div class="button-group">
                        <a href="login.php?role=candidate" class="btn btn-primary">Login</a>
                        <a href="register.php?role=candidate" class="btn btn-secondary">Register</a>
                    </div>
                </div>

                <div class="role-card">
                    <div class="role-icon">🏢</div>
                    <h3 class="role-title">Employer</h3>
                    <p class="role-description">
                        Post job openings and find the perfect candidates for your organization.
                    </p>
                    <div class="button-group">
                        <a href="login.php?role=employer" class="btn btn-primary">Login</a>
                        <a href="register.php?role=employer" class="btn btn-secondary">Register</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="wave"></div>
</body>
</html> 