<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$role = isset($_GET['role']) ? $_GET['role'] : '';
if (!in_array($role, ['employer', 'candidate'])) {
    header("Location: select-role.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("SELECT users.*, 
            COALESCE(ep.company_name, cp.full_name) as display_name 
            FROM users 
            LEFT JOIN employer_profiles ep ON users.id = ep.user_id 
            LEFT JOIN candidate_profiles cp ON users.id = cp.user_id 
            WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['display_name'] = $user['display_name'];
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password";
        }
    } catch(PDOException $e) {
        $error = "Login failed. Please try again.";
    }
}

$role_title = $role === 'employer' ? 'Employer' : 'Job Seeker';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $role_title; ?> Login - Job Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .role-indicator {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .role-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <h1>Job Portal</h1>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="select-role.php">Change Role</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="login-container">
            <div class="role-indicator">
                <div class="role-icon"><?php echo $role === 'employer' ? '🏢' : '👔'; ?></div>
                <h2><?php echo $role_title; ?> Login</h2>
            </div>
            
            <?php if(isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo htmlspecialchars($_SESSION['message']); 
                    unset($_SESSION['message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if(isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" class="form-control" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            </form>
            <p style="margin-top: 15px; text-align: center;">
                Don't have an account? 
                <a href="register.php?role=<?php echo $role; ?>">Register as <?php echo $role_title; ?></a>
            </p>
        </div>
    </div>
</body>
</html> 