<?php
session_start();
require_once 'config/database.php';

// Redirect if not logged in or not a candidate
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'candidate') {
    header("Location: login.php");
    exit();
}

try {
    // Get candidate ID and profile
    $stmt = $pdo->prepare("SELECT cp.* FROM candidate_profiles cp WHERE cp.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $candidate = $stmt->fetch();

    if ($candidate) {
        // Get application statistics
        $stats_stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM job_applications
            WHERE candidate_id = ?
        ");
        $stats_stmt->execute([$candidate['id']]);
        $stats = $stats_stmt->fetch();

        // Get recent applications
        $applications_stmt = $pdo->prepare("
            SELECT 
                ja.*,
                j.title as job_title,
                j.location,
                j.salary_range,
                e.company_name,
                e.website,
                e.company_description
            FROM job_applications ja 
            JOIN jobs j ON ja.job_id = j.id 
            JOIN employer_profiles e ON j.employer_id = e.id 
            WHERE ja.candidate_id = ?
            ORDER BY ja.application_date DESC
        ");
        $applications_stmt->execute([$candidate['id']]);
        $applications = $applications_stmt->fetchAll();
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
    <title>My Applications - Job Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .welcome-banner {
            background: linear-gradient(135deg, #2193b0, #6dd5ed);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .welcome-banner h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .welcome-banner p {
            margin: 10px 0 0;
            opacity: 0.9;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #2193b0;
            margin: 5px 0;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
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
            align-items: flex-start;
        }
        .company-info {
            flex: 1;
        }
        .company-name {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin: 0 0 5px 0;
        }
        .job-title {
            color: #666;
            font-size: 16px;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .status-pending { background: #fff8e1; color: #f57c00; }
        .status-accepted { background: #e8f5e9; color: #2e7d32; }
        .status-rejected { background: #ffebee; color: #c62828; }
        .application-body {
            padding: 20px;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .detail-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-value {
            font-size: 14px;
            color: #333;
        }
        .cover-letter {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        .cover-letter p {
            margin: 0;
            font-size: 14px;
            line-height: 1.6;
            color: #555;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .empty-state h3 {
            color: #333;
            margin-bottom: 10px;
        }
        .empty-state p {
            color: #666;
            margin-bottom: 20px;
        }
        .action-button {
            display: inline-block;
            padding: 10px 20px;
            background: #2193b0;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.3s ease;
        }
        .action-button:hover {
            background: #1c7d94;
        }
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
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

    <div class="dashboard-container">
        <div class="welcome-banner">
            <h1>My Applications</h1>
            <p>Track and manage your job applications</p>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">📝</div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Applications</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Under Review</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo $stats['accepted']; ?></div>
                <div class="stat-label">Accepted</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">❌</div>
                <div class="stat-value"><?php echo $stats['rejected']; ?></div>
                <div class="stat-label">Not Selected</div>
            </div>
        </div>

        <?php if(empty($applications)): ?>
            <div class="empty-state">
                <h3>No Applications Yet</h3>
                <p>Start your job search and apply to positions that match your skills and interests.</p>
                <a href="search-jobs.php" class="action-button">Browse Jobs</a>
            </div>
        <?php else: ?>
            <div class="applications-grid">
                <?php foreach($applications as $application): ?>
                    <div class="application-card">
                        <div class="application-header">
                            <div class="company-info">
                                <div class="company-name">
                                    <?php echo htmlspecialchars($application['company_name']); ?>
                                    <?php if($application['website']): ?>
                                        <a href="<?php echo htmlspecialchars($application['website']); ?>" 
                                           target="_blank" style="margin-left: 5px;">🔗</a>
                                    <?php endif; ?>
                                </div>
                                <div class="job-title"><?php echo htmlspecialchars($application['job_title']); ?></div>
                            </div>
                            <span class="status-badge status-<?php echo $application['status']; ?>">
                                <?php echo ucfirst($application['status']); ?>
                            </span>
                        </div>
                        <div class="application-body">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Location</span>
                                    <span class="detail-value">📍 <?php echo htmlspecialchars($application['location']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Salary Range</span>
                                    <span class="detail-value">💰 <?php echo htmlspecialchars($application['salary_range']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Applied On</span>
                                    <span class="detail-value">📅 <?php echo date('M d, Y', strtotime($application['application_date'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Resume</span>
                                    <span class="detail-value">
                                        <?php if($application['resume_path']): ?>
                                            <a href="<?php echo htmlspecialchars($application['resume_path']); ?>" 
                                               class="resume-link" target="_blank">View Resume 📄</a>
                                        <?php else: ?>
                                            No resume attached
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            <?php if($application['cover_letter']): ?>
                                <div class="cover-letter">
                                    <div class="detail-label">Cover Letter</div>
                                    <p><?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 