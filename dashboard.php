<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user-specific data
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    if ($role === 'employer') {
        // Get employer profile and stats
        $profile_stmt = $pdo->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
        $profile_stmt->execute([$user_id]);
        $profile = $profile_stmt->fetch();
        
        if ($profile) {
            // Get jobs and applications stats
            $stats = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT j.id) as total_jobs,
                    COUNT(ja.id) as total_applications,
                    SUM(CASE WHEN ja.status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
                    SUM(CASE WHEN j.posted_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as recent_jobs
                FROM jobs j
                LEFT JOIN job_applications ja ON j.id = ja.job_id
                WHERE j.employer_id = ?
            ");
            $stats->execute([$profile['id']]);
            $stats_data = $stats->fetch();

            // Get recent jobs
            $jobs_stmt = $pdo->prepare("
                SELECT j.*, COUNT(ja.id) as applications_count 
                FROM jobs j 
                LEFT JOIN job_applications ja ON j.id = ja.job_id 
                WHERE j.employer_id = ?
                GROUP BY j.id 
                ORDER BY j.posted_date DESC
                LIMIT 5
            ");
            $jobs_stmt->execute([$profile['id']]);
            $recent_jobs = $jobs_stmt->fetchAll();
        }
    } else {
        // Get candidate stats and data
        $candidate_stmt = $pdo->prepare("
            SELECT cp.*, 
                COUNT(DISTINCT ja.job_id) as total_applications,
                SUM(CASE WHEN ja.status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
                SUM(CASE WHEN ja.status = 'accepted' THEN 1 ELSE 0 END) as accepted_applications
            FROM candidate_profiles cp
            LEFT JOIN job_applications ja ON cp.id = ja.candidate_id
            WHERE cp.user_id = ?
            GROUP BY cp.id
        ");
        $candidate_stmt->execute([$user_id]);
        $candidate_data = $candidate_stmt->fetch();

        // Get recent applications
        $applications_stmt = $pdo->prepare("
            SELECT ja.*, j.title, j.location, e.company_name 
            FROM job_applications ja 
            JOIN jobs j ON ja.job_id = j.id 
            JOIN employer_profiles e ON j.employer_id = e.id 
            WHERE ja.candidate_id = ?
            ORDER BY ja.application_date DESC 
            LIMIT 5
        ");
        $applications_stmt->execute([$candidate_data['id']]);
        $recent_applications = $applications_stmt->fetchAll();
    }
} catch(PDOException $e) {
    $error = "Error fetching data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Job Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .welcome-banner {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2em;
            margin-bottom: 10px;
            color: #007bff;
        }
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
        }
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        .content-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        .section-title {
            font-size: 1.2em;
            color: #333;
            margin: 0;
        }
        .action-button {
            padding: 8px 15px;
            border-radius: 5px;
            background: #007bff;
            color: white;
            text-decoration: none;
            font-size: 0.9em;
            transition: background 0.3s ease;
        }
        .action-button:hover {
            background: #0056b3;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 500;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-accepted { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            color: #333;
            font-weight: 600;
        }
        tr:hover {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <h1>Job Portal</h1>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="dashboard-container">
        <div class="welcome-banner">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['display_name']); ?>!</h2>
            <p><?php echo $role === 'employer' ? 'Manage your job listings and applications' : 'Find and apply for your dream job'; ?></p>
        </div>

        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo htmlspecialchars($_SESSION['message']); 
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if($role === 'employer'): ?>
            <!-- Employer Dashboard -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-value"><?php echo $stats_data['total_jobs']; ?></div>
                    <div class="stat-label">Active Jobs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-value"><?php echo $stats_data['total_applications']; ?></div>
                    <div class="stat-label">Total Applications</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-value"><?php echo $stats_data['pending_applications']; ?></div>
                    <div class="stat-label">Pending Reviews</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🆕</div>
                    <div class="stat-value"><?php echo $stats_data['recent_jobs']; ?></div>
                    <div class="stat-label">New Jobs This Week</div>
                </div>
            </div>

            <div class="content-section">
                <div class="section-header">
                    <h3 class="section-title">Recent Job Listings</h3>
                    <a href="post-job.php" class="action-button">Post New Job</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Location</th>
                                <th>Posted Date</th>
                                <th>Applications</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_jobs as $job): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($job['title']); ?></td>
                                    <td><?php echo htmlspecialchars($job['location']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($job['posted_date'])); ?></td>
                                    <td>
                                        <span class="status-badge">
                                            <?php echo $job['applications_count']; ?> applications
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view-applications.php?job_id=<?php echo $job['id']; ?>" 
                                           class="btn btn-primary">View Applications</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <!-- Candidate Dashboard -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-value"><?php echo $candidate_data['total_applications']; ?></div>
                    <div class="stat-label">Total Applications</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-value"><?php echo $candidate_data['pending_applications']; ?></div>
                    <div class="stat-label">Pending Applications</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $candidate_data['accepted_applications']; ?></div>
                    <div class="stat-label">Accepted Applications</div>
                </div>
            </div>

            <div class="content-section">
                <div class="section-header">
                    <h3 class="section-title">Recent Applications</h3>
                    <a href="search-jobs.php" class="action-button">Find Jobs</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Company</th>
                                <th>Applied Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_applications as $application): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($application['title']); ?></td>
                                    <td><?php echo htmlspecialchars($application['company_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($application['application_date'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $application['status']; ?>">
                                            <?php echo ucfirst($application['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 