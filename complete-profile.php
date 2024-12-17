<?php
session_start();
require_once 'config/database.php';

// Redirect if not logged in or not an employer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: login.php");
    exit();
}

// Check if profile already exists
$stmt = $pdo->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = filter_input(INPUT_POST, 'company_name', FILTER_SANITIZE_STRING);
    $company_description = filter_input(INPUT_POST, 'company_description', FILTER_SANITIZE_STRING);
    $website = filter_input(INPUT_POST, 'website', FILTER_SANITIZE_URL);

    try {
        if ($profile) {
            // Update existing profile
            $stmt = $pdo->prepare("UPDATE employer_profiles 
                                 SET company_name = ?, company_description = ?, website = ? 
                                 WHERE user_id = ?");
            $stmt->execute([$company_name, $company_description, $website, $_SESSION['user_id']]);
        } else {
            // Create new profile
            $stmt = $pdo->prepare("INSERT INTO employer_profiles 
                                 (user_id, company_name, company_description, website) 
                                 VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $company_name, $company_description, $website]);
        }
        
        $_SESSION['display_name'] = $company_name;
        $_SESSION['message'] = "Profile updated successfully!";
        header("Location: dashboard.php");
        exit();
    } catch(PDOException $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Company Profile - Job Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .profile-form {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-header i {
            font-size: 3em;
            color: #007bff;
            margin-bottom: 15px;
        }
        textarea.form-control {
            min-height: 120px;
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
        <form method="POST" class="profile-form">
            <div class="form-header">
                <div class="role-icon">🏢</div>
                <h2>Complete Your Company Profile</h2>
                <p>Please provide your company details to start posting jobs</p>
            </div>

            <?php if(isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label>Company Name:</label>
                <input type="text" name="company_name" class="form-control" required
                       value="<?php echo isset($profile['company_name']) ? htmlspecialchars($profile['company_name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label>Company Description:</label>
                <textarea name="company_description" class="form-control" required><?php 
                    echo isset($profile['company_description']) ? htmlspecialchars($profile['company_description']) : ''; 
                ?></textarea>
            </div>

            <div class="form-group">
                <label>Company Website:</label>
                <input type="url" name="website" class="form-control"
                       value="<?php echo isset($profile['website']) ? htmlspecialchars($profile['website']) : ''; ?>"
                       placeholder="https://www.example.com">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <?php echo $profile ? 'Update Profile' : 'Complete Profile'; ?>
            </button>
        </form>
    </div>
</body>
</html> 