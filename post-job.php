<?php
session_start();
require_once 'config/database.php';

// Redirect if not logged in or not an employer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $requirements = filter_input(INPUT_POST, 'requirements', FILTER_SANITIZE_STRING);
    $salary_range = filter_input(INPUT_POST, 'salary_range', FILTER_SANITIZE_STRING);
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);

    try {
        // Get employer profile ID
        $stmt = $pdo->prepare("SELECT id FROM employer_profiles WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $employer = $stmt->fetch();

        if ($employer) {
            $stmt = $pdo->prepare("INSERT INTO jobs (employer_id, title, description, requirements, salary_range, location) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$employer['id'], $title, $description, $requirements, $salary_range, $location])) {
                $_SESSION['message'] = "Job posted successfully!";
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Failed to post job. Please try again.";
            }
        } else {
            $_SESSION['message'] = "Please complete your company profile first.";
            header("Location: complete-profile.php");
            exit();
        }
    } catch(PDOException $e) {
        $error = "Error posting job: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post New Job - Job Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .post-job-form {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        textarea.form-control {
            min-height: 150px;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <h1>Job Portal</h1>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h2>Post New Job</h2>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="post-job-form">
            <div class="form-group">
                <label>Job Title:</label>
                <input type="text" name="title" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Job Description:</label>
                <textarea name="description" class="form-control" required></textarea>
            </div>

            <div class="form-group">
                <label>Requirements:</label>
                <textarea name="requirements" class="form-control" required></textarea>
            </div>

            <div class="form-group">
                <label>Salary Range:</label>
                <input type="text" name="salary_range" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Location:</label>
                <input type="text" name="location" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">Post Job</button>
        </form>
    </div>
</body>
</html> 