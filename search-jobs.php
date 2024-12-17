<?php
session_start();
require_once 'config/database.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get search parameters
$search = isset($_GET['keywords']) ? $_GET['keywords'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';

try {
    $query = "SELECT j.*, e.company_name 
              FROM jobs j 
              JOIN employer_profiles e ON j.employer_id = e.id 
              WHERE 1=1";
    $params = [];

    if ($search) {
        $query .= " AND (j.title LIKE ? OR j.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($location) {
        $query .= " AND j.location LIKE ?";
        $params[] = "%$location%";
    }

    $query .= " ORDER BY j.posted_date DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error fetching jobs: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Jobs - Job Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .search-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .search-form {
            display: flex;
            gap: 15px;
        }
        .search-form .form-group {
            flex: 1;
        }
        .job-card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .job-title {
            color: #333;
            margin-bottom: 10px;
        }
        .company-name {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        .job-details {
            display: flex;
            gap: 20px;
            color: #777;
            font-size: 0.9em;
            margin: 10px 0;
        }
        .job-actions {
            margin-top: 15px;
        }
        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
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
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="search-box">
            <h2>Search Jobs</h2>
            <form method="GET" class="search-form">
                <div class="form-group">
                    <input type="text" name="keywords" class="form-control" 
                           placeholder="Job title or keywords" 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="form-group">
                    <input type="text" name="location" class="form-control" 
                           placeholder="Location" 
                           value="<?php echo htmlspecialchars($location); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Search Jobs</button>
            </form>
        </div>

        <div class="available-jobs">
            <h3>Available Jobs <?php echo ($search || $location) ? '- Search Results' : ''; ?></h3>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php elseif(empty($jobs)): ?>
                <div class="alert alert-info">No jobs found matching your criteria.</div>
            <?php else: ?>
                <?php foreach($jobs as $job): ?>
                    <div class="job-card">
                        <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                        <p class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></p>
                        <div class="job-details">
                            <span>📍 <?php echo htmlspecialchars($job['location']); ?></span>
                            <span>💰 <?php echo htmlspecialchars($job['salary_range']); ?></span>
                            <span>📅 Posted <?php echo date('M d, Y', strtotime($job['posted_date'])); ?></span>
                        </div>
                        <div class="job-actions">
                            <a href="view-job.php?id=<?php echo $job['id']; ?>" class="btn">View Details</a>
                            <a href="apply-job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary">Apply Now</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 