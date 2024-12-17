<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize input
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required";
    } else {
        try {
            // First check if username or email already exists
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $check_stmt->execute([$username, $email]);
            
            if ($check_stmt->rowCount() > 0) {
                $error = "Username or email already exists";
            } else {
                // Insert new user
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                if ($stmt->execute([$username, $email, $hashed_password, $role])) {
                    $user_id = $pdo->lastInsertId();
                    
                    // Create corresponding profile based on role
                    if ($role === 'employer') {
                        $profile_stmt = $pdo->prepare("INSERT INTO employer_profiles (user_id, company_name) VALUES (?, ?)");
                        $profile_stmt->execute([$user_id, $username]); // Using username as temporary company name
                    } else {
                        $profile_stmt = $pdo->prepare("INSERT INTO candidate_profiles (user_id, full_name) VALUES (?, ?)");
                        $profile_stmt->execute([$user_id, $username]); // Using username as temporary full name
                    }
                    
                    $_SESSION['message'] = "Registration successful! Please login.";
                    header("Location: login.php");
                    exit();
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Job Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <h1>Job Portal</h1>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h2>Register</h2>
        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" class="form-control" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" class="form-control" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Role:</label>
                <select name="role" class="form-control" required>
                    <option value="employer" <?php echo (isset($_POST['role']) && $_POST['role'] === 'employer') ? 'selected' : ''; ?>>Employer</option>
                    <option value="candidate" <?php echo (isset($_POST['role']) && $_POST['role'] === 'candidate') ? 'selected' : ''; ?>>Job Seeker</option>
                </select>
            </div>
            
            <button type="submit" class="btn">Register</button>
        </form>
        <p style="margin-top: 15px;">Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html> 