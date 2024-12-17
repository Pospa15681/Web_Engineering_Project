<?php
session_start();
require_once 'config/database.php';

// Redirect if not logged in or not an employer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: login.php");
    exit();
}

$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

try {
    // First verify this job belongs to the employer
    $job_stmt = $pdo->prepare("
        SELECT j.*, e.company_name 
        FROM jobs j
        JOIN employer_profiles e ON j.employer_id = e.id
        WHERE j.id = ? AND e.user_id = ?
    ");
    $job_stmt->execute([$job_id, $_SESSION['user_id']]);
    $job = $job_stmt->fetch();

    if (!$job) {
        header("Location: dashboard.php");
        exit();
    }

    // Get all applications for this job
    $applications_stmt = $pdo->prepare("
        SELECT 
            ja.*,
            cp.full_name,
            cp.email,
            cp.phone,
            cp.skills,
            cp.experience
        FROM job_applications ja
        JOIN candidate_profiles cp ON ja.candidate_id = cp.id
        WHERE ja.job_id = ?
        ORDER BY ja.application_date DESC
    ");
    $applications_stmt->execute([$job_id]);
    $applications = $applications_stmt->fetchAll();

    // Handle status updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
        $app_id = $_POST['application_id'];
        $new_status = $_POST['status'];
        
        $update_stmt = $pdo->prepare("
            UPDATE job_applications 
            SET status = ?, updated_at = NOW() 
            WHERE id = ? AND job_id = ?
        ");
        $update_stmt->execute([$new_status, $app_id, $job_id]);
        
        header("Location: view-applications.php?job_id=" . $job_id);
        exit();
    }
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Applications - <?php echo htmlspecialchars($job['title']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .job-header {
            background: linear-gradient(135deg, #2193b0, #6dd5ed);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .job-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .job-header p {
            margin: 10px 0 0;
            opacity: 0.9;
        }
        .applications-grid {
            display: grid;
            gap: 20px;
        }
        .application-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        .application-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }
        .application-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .candidate-info h3 {
            margin: 0;
            color: #333;
            font-size: 1.2em;
        }
        .application-body {
            padding: 20px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }
        .info-value {
            color: #333;
            font-weight: 500;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-accepted { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-reviewed { background: #cce5ff; color: #004085; }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-accept {
            background: #28a745;
            color: white;
        }
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        .btn-review {
            background: #17a2b8;
            color: white;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 12px;
            margin-top: 20px;
        }
        .empty-state h3 {
            color: #333;
            margin-bottom: 10px;
        }
        .empty-state p {
            color: #666;
            margin-bottom: 20px;
        }
        .cover-letter {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        .cover-letter-text {
            color: #666;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <h1>Job Portal</h1>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="post-job.php">Post Job</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="job-header">
            <h1><?php echo htmlspecialchars($job['title']); ?></h1>
            <p><?php echo htmlspecialchars($job['company_name']); ?></p>
        </div>

        <?php if(empty($applications)): ?>
            <div class="empty-state">
                <h3>No Applications Yet</h3>
                <p>There are currently no applications for this position.</p>
                <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            </div>
        <?php else: ?>
            <div class="applications-grid">
                <?php foreach($applications as $application): ?>
                    <div class="application-card">
                        <div class="application-header">
                            <div class="candidate-info">
                                <h3><?php echo htmlspecialchars($application['full_name']); ?></h3>
                                <p><?php echo htmlspecialchars($application['email']); ?></p>
                            </div>
                            <span class="status-badge status-<?php echo $application['status']; ?>">
                                <?php echo ucfirst($application['status']); ?>
                            </span>
                        </div>
                        
                        <div class="application-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Phone</span>
                                    <span class="info-value"><?php echo htmlspecialchars($application['phone']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Experience</span>
                                    <span class="info-value"><?php echo htmlspecialchars($application['experience']); ?> years</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Applied On</span>
                                    <span class="info-value">
                                        <?php echo date('M d, Y', strtotime($application['application_date'])); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="skills">
                                <span class="info-label">Skills</span>
                                <p><?php echo htmlspecialchars($application['skills']); ?></p>
                            </div>

                            <?php if($application['cover_letter']): ?>
                                <div class="cover-letter">
                                    <span class="info-label">Cover Letter</span>
                                    <p class="cover-letter-text">
                                        <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <?php if($application['resume_path']): ?>
                                <div class="resume-section">
                                    <a href="<?php echo htmlspecialchars($application['resume_path']); ?>" 
                                       target="_blank" class="btn btn-primary">
                                        View Resume
                                    </a>
                                </div>
                            <?php endif; ?>

                            <div class="action-buttons">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="application_id" 
                                           value="<?php echo $application['id']; ?>">
                                    <input type="hidden" name="update_status" value="1">
                                    
                                    <?php if($application['status'] !== 'accepted'): ?>
                                        <button type="submit" name="status" value="accepted" 
                                                class="btn btn-accept">Accept</button>
                                    <?php endif; ?>
                                    
                                    <?php if($application['status'] !== 'rejected'): ?>
                                        <button type="submit" name="status" value="rejected" 
                                                class="btn btn-reject">Reject</button>
                                    <?php endif; ?>
                                    
                                    <?php if($application['status'] !== 'reviewed'): ?>
                                        <button type="submit" name="status" value="reviewed" 
                                                class="btn btn-review">Mark as Reviewed</button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 