<?php
session_start();
require_once 'config/database.php';

// Initialize default values
$stats = [
    'active_jobs' => 0,
    'employers' => 0,
    'candidates' => 0
];

// Fetch latest job count and employer count
try {
    // Get active jobs count
    $jobs_stmt = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'active'");
    $stats['active_jobs'] = $jobs_stmt->fetchColumn();

    // Get employers count
    $employers_stmt = $pdo->query("SELECT COUNT(*) FROM employer_profiles");
    $stats['employers'] = $employers_stmt->fetchColumn();

    // Get candidates count
    $candidates_stmt = $pdo->query("SELECT COUNT(*) FROM candidate_profiles");
    $stats['candidates'] = $candidates_stmt->fetchColumn();

    // Fetch featured/latest jobs with error handling
    $featured_jobs = $pdo->query("
        SELECT j.*, e.company_name, e.company_logo
        FROM jobs j
        JOIN employer_profiles e ON j.employer_id = e.id
        WHERE j.status = 'active'
        ORDER BY j.posted_date DESC
        LIMIT 6
    ")->fetchAll();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
    $featured_jobs = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Portal - Your Career Starts Here</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .hero-section {
            background: linear-gradient(135deg, rgba(32,32,32,0.9), rgba(0,0,0,0.8)), 
                        url('assets/images/hero-bg.jpg') center/cover;
            min-height: 600px;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
            text-align: center;
            position: relative;
            z-index: 2;
        }
        .hero-title {
            font-size: 3.5em;
            margin-bottom: 20px;
            font-weight: 700;
            color: #ffffff;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            animation: fadeInUp 1s ease;
        }
        .hero-subtitle {
            font-size: 1.3em;
            margin-bottom: 30px;
            color: rgba(255,255,255,0.9);
            line-height: 1.6;
            animation: fadeInUp 1s ease 0.2s;
        }
        .stats-section {
            margin-top: -50px;
            padding: 0 20px;
            position: relative;
            z-index: 3;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        .stat-value {
            font-size: 2.8em;
            font-weight: 700;
            background: linear-gradient(135deg, #2193b0, #6dd5ed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        .stat-label {
            color: #555;
            font-size: 1.1em;
            font-weight: 500;
        }
        .features-section {
            padding: 100px 0 80px;
            background: white;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .feature-card {
            background: white;
            padding: 40px 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }
        .feature-icon {
            font-size: 3em;
            margin-bottom: 20px;
            display: inline-block;
            background: linear-gradient(135deg, #2193b0, #6dd5ed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .feature-title {
            font-size: 1.4em;
            margin-bottom: 15px;
            color: #333;
            font-weight: 600;
        }
        .feature-description {
            color: #666;
            line-height: 1.7;
            font-size: 1.1em;
        }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            border-radius: 30px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 1.1em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #2193b0, #6dd5ed);
            color: white;
            box-shadow: 0 5px 15px rgba(33, 147, 176, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(33, 147, 176, 0.4);
        }
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 2px solid white;
            margin-left: 20px;
        }
        .btn-secondary:hover {
            background: white;
            color: #2193b0;
            transform: translateY(-3px);
        }
        .cta-buttons {
            margin-top: 40px;
            animation: fadeInUp 1s ease 0.4s;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5em;
            }
            .stats-grid, .features-grid {
                grid-template-columns: 1fr;
            }
            .cta-buttons {
                display: flex;
                flex-direction: column;
                gap: 20px;
            }
            .btn-secondary {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <h1>Job Portal</h1>
            <ul>
                <li><a href="index.php">Home</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="select-role.php">Login</a></li>
                    <li><a href="select-role.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Find Your Dream Job Today</h1>
            <p class="hero-subtitle">
                Connect with top employers and opportunities. Whether you're looking 
                for your next career move or seeking talent, we've got you covered.
            </p>
            <div class="cta-buttons">
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <a href="select-role.php" class="btn btn-primary">Get Started</a>
                    <a href="search-jobs.php" class="btn btn-secondary">Browse Jobs</a>
                <?php else: ?>
                    <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    <a href="search-jobs.php" class="btn btn-secondary">Browse Jobs</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="stats-section">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['active_jobs']); ?>+</div>
                <div class="stat-label">Active Jobs</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['employers']); ?>+</div>
                <div class="stat-label">Companies</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['candidates']); ?>+</div>
                <div class="stat-label">Job Seekers</div>
            </div>
        </div>
    </section>

    <section class="features-section">
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">👔</div>
                <h3 class="feature-title">For Job Seekers</h3>
                <p class="feature-description">
                    Create your professional profile, upload your resume, and apply to 
                    thousands of job opportunities.
                </p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🏢</div>
                <h3 class="feature-title">For Employers</h3>
                <p class="feature-description">
                    Post job openings, manage applications, and find the perfect 
                    candidates for your organization.
                </p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3 class="feature-title">Easy to Use</h3>
                <p class="feature-description">
                    User-friendly interface designed to make job searching and 
                    recruitment process seamless.
                </p>
            </div>
        </div>
    </section>

    <section class="latest-jobs">
        <h2 class="section-title">Latest Job Opportunities</h2>
        <div class="jobs-grid">
            <?php foreach($featured_jobs as $job): ?>
                <div class="job-card">
                    <div class="job-header">
                        <?php if($job['company_logo']): ?>
                            <img src="<?php echo htmlspecialchars($job['company_logo']); ?>" 
                                 alt="<?php echo htmlspecialchars($job['company_name']); ?>" 
                                 class="company-logo">
                        <?php endif; ?>
                        <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                        <p class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></p>
                    </div>
                    <div class="job-details">
                        <div class="job-meta">
                            <span>📍 <?php echo htmlspecialchars($job['location']); ?></span>
                            <span>💰 <?php echo htmlspecialchars($job['salary_range']); ?></span>
                        </div>
                        <a href="view-job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="cta-section">
        <div class="cta-content">
            <h2>Ready to Take the Next Step?</h2>
            <p>Join thousands of professionals who have found their perfect career match through our platform.</p>
            <div class="cta-buttons">
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <a href="select-role.php" class="btn btn-secondary">Get Started Today</a>
                <?php else: ?>
                    <a href="dashboard.php" class="btn btn-secondary">Go to Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer style="background: #333; color: white; padding: 40px 0; text-align: center;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Job Portal. All rights reserved.</p>
        </div>
    </footer>
</body>
</html> 