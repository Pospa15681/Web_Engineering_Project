<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$job_id = isset($_GET['id']) ? $_GET['id'] : 0;

try {
    $stmt = $pdo->prepare("
        SELECT j.*, e.company_name, e.company_description, e.website,
        (SELECT COUNT(*) FROM job_applications 
         WHERE job_id = j.id AND candidate_id = 
            (SELECT id FROM candidate_profiles WHERE user_id = ?)) as has_applied
        FROM jobs j
        JOIN employer_profiles e ON j.employer_id = e.id
        WHERE j.id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $job_id]);
    $job = $stmt->fetch();

    if (!$job) {
        header("Location: search-jobs.php");
        exit();
    }
} catch(PDOException $e) {
    $error = "Error fetching job details: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['title']); ?> - Job Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .job-details {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .company-section {
            margin: 20px 0;
            padding: 20px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        .meta-info {
            display: flex;
            gap: 20px;
            margin: 15px 0;
            color: #666;
        }
        .description-section {
            margin: 20px 0;
        }
        .description-section h3 {
            margin-bottom: 10px;
            color: #333;
        }
        .apply-section {
            margin-top: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <h1>Job Portal</h1>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="search-jobs.php">Search Jobs</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="job-details">
            <h2><?php echo htmlspecialchars($job['title']); ?></h2>
            
            <div class="company-section">
                <h3><?php echo htmlspecialchars($job['company_name']); ?></h3>
                <?php if($job['website']): ?>
                    <a href="<?php echo htmlspecialchars($job['website']); ?>" target="_blank" class="website-link">
                        Visit Company Website
                    </a>
                <?php endif; ?>
            </div>

            <div class="meta-info">
                <span>📍 <?php echo htmlspecialchars($job['location']); ?></span>
                <span>💰 <?php echo htmlspecialchars($job['salary_range']); ?></span>
                <span>📅 Posted <?php echo date('M d, Y', strtotime($job['posted_date'])); ?></span>
            </div>

            <div class="description-section">
                <h3>Job Description</h3>
                <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
            </div>

            <div class="description-section">
                <h3>Requirements</h3>
                <p><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></p>
            </div>

            <div class="apply-section">
                <?php if($job['has_applied']): ?>
                    <div class="alert alert-info">You have already applied for this position.</div>
                <?php else: ?>
                    <a href="apply-job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary btn-large">
                        Apply for this Position
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 