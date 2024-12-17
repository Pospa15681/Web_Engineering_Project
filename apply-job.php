<?php
session_start();
require_once 'config/database.php';

// Redirect if not logged in or not a candidate
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'candidate') {
    header("Location: login.php");
    exit();
}

$job_id = isset($_GET['id']) ? $_GET['id'] : 0;

// First check if user has already applied
try {
    $check_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM job_applications 
        WHERE job_id = ? AND candidate_id = (
            SELECT id FROM candidate_profiles WHERE user_id = ?
        )
    ");
    $check_stmt->execute([$job_id, $_SESSION['user_id']]);
    if ($check_stmt->fetchColumn() > 0) {
        $_SESSION['error'] = "You have already applied for this position.";
        header("Location: view-job.php?id=" . $job_id);
        exit();
    }

    // Get job details
    $stmt = $pdo->prepare("
        SELECT j.*, e.company_name 
        FROM jobs j
        JOIN employer_profiles e ON j.employer_id = e.id
        WHERE j.id = ?
    ");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();

    if (!$job) {
        header("Location: search-jobs.php");
        exit();
    }
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cover_letter = filter_input(INPUT_POST, 'cover_letter', FILTER_SANITIZE_STRING);
    
    try {
        // Get candidate profile ID
        $profile_stmt = $pdo->prepare("SELECT id FROM candidate_profiles WHERE user_id = ?");
        $profile_stmt->execute([$_SESSION['user_id']]);
        $candidate = $profile_stmt->fetch();

        if ($candidate) {
            // Handle resume upload
            $resume_path = null;
            if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                $file_info = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($file_info, $_FILES['resume']['tmp_name']);
                finfo_close($file_info);

                if (!in_array($mime_type, $allowed_types)) {
                    $error = "Invalid file type. Please upload PDF or Word documents only.";
                } else {
                    $upload_dir = 'uploads/resumes/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $filename = uniqid() . '_' . basename($_FILES['resume']['name']);
                    $resume_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path)) {
                        // File uploaded successfully
                    } else {
                        $error = "Error uploading resume.";
                    }
                }
            }

            if (!isset($error)) {
                // Insert application
                $stmt = $pdo->prepare("
                    INSERT INTO job_applications (job_id, candidate_id, cover_letter, resume_path, status, application_date) 
                    VALUES (?, ?, ?, ?, 'pending', NOW())
                ");
                
                if ($stmt->execute([$job_id, $candidate['id'], $cover_letter, $resume_path])) {
                    $_SESSION['message'] = "Application submitted successfully!";
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Failed to submit application.";
                }
            }
        } else {
            $error = "Candidate profile not found.";
        }
    } catch(PDOException $e) {
        $error = "Error submitting application: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for <?php echo htmlspecialchars($job['title']); ?> - Job Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .application-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 30px auto;
        }
        .job-header {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .job-header h2 {
            margin-bottom: 10px;
            color: #333;
        }
        .company-name {
            color: #666;
            font-size: 1.1em;
        }
        .form-header {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .resume-info {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        textarea.form-control {
            min-height: 200px;
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
        <div class="application-form">
            <div class="job-header">
                <h2><?php echo htmlspecialchars($job['title']); ?></h2>
                <p class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></p>
            </div>

            <?php if(isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-header">
                    <h3>Submit Your Application</h3>
                    <p>Please fill out the following information carefully.</p>
                </div>

                <div class="form-group">
                    <label>Cover Letter:</label>
                    <textarea name="cover_letter" class="form-control" required
                              placeholder="Introduce yourself and explain why you're a great fit for this position..."><?php 
                        echo isset($_POST['cover_letter']) ? htmlspecialchars($_POST['cover_letter']) : ''; 
                    ?></textarea>
                </div>

                <div class="form-group">
                    <label>Resume/CV:</label>
                    <input type="file" name="resume" class="form-control" required accept=".pdf,.doc,.docx">
                    <p class="resume-info">Accepted formats: PDF, DOC, DOCX. Maximum size: 5MB</p>
                </div>

                <div class="form-actions" style="margin-top: 20px;">
                    <a href="view-job.php?id=<?php echo $job_id; ?>" class="btn">Cancel</a>
                    <button type="submit" class="btn btn-primary">Submit Application</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 