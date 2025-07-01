<?php
session_start();
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mumbe_schools');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Security Settings
define('PEPPER', 'mumbe-educational-pepper-!@#$%^&*()');
define('LOCKOUT_THRESHOLD', 5);
define('LOCKOUT_TIME', 300); // 5 minutes

// Create database connection
try {
    $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
    $conn->exec("SET time_zone = '+3:00'"); // East Africa Time
    
} catch (PDOException $e) {
    die("System temporarily unavailable. Please try again later.");
}

// Handle login form submission
$errors = [];
$login_disabled = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Check if account is locked
    $lockout_key = 'login_attempts_' . $email;
    $attempts = $_SESSION[$lockout_key]['count'] ?? 0;
    $last_attempt = $_SESSION[$lockout_key]['time'] ?? 0;
    
    if ($attempts >= LOCKOUT_THRESHOLD && (time() - $last_attempt) < LOCKOUT_TIME) {
        $login_disabled = true;
        $remaining_time = LOCKOUT_TIME - (time() - $last_attempt);
        $errors[] = "Too many failed attempts. Please try again in " . ceil($remaining_time / 60) . " minutes.";
    } else {
        // Validate credentials
        if (empty($email) || empty($password)) {
            $errors[] = "Email and password are required";
        } else {
            try {
                $stmt = $conn->prepare("SELECT id, full_name, email, password_hash, status, last_login, children_ids FROM parents WHERE email = :email");
                $stmt->bindValue(':email', $email);
                $stmt->execute();
                $parent = $stmt->fetch();
                
                if ($parent) {
                    // Check account status
                    if ($parent['status'] === 'suspended') {
                        $errors[] = "Your account has been suspended. Please contact administration.";
                    } elseif ($parent['status'] === 'pending') {
                        $errors[] = "Your account is pending approval. Please check your email for verification.";
                    } else {
                        // Verify password
                        if (password_verify($password . PEPPER, $parent['password_hash'])) {
                            // Successful login
                            $_SESSION['parent_id'] = $parent['id'];
                            $_SESSION['parent_name'] = $parent['full_name'];
                            $_SESSION['parent_email'] = $parent['email'];
                            $_SESSION['children_ids'] = json_decode($parent['children_ids'], true);
                            $_SESSION['last_login'] = $parent['last_login'];
                            
                            // Update last login
                            $stmt = $conn->prepare("UPDATE parents SET last_login = NOW() WHERE id = :id");
                            $stmt->bindValue(':id', $parent['id']);
                            $stmt->execute();
                            
                            // Reset login attempts
                            unset($_SESSION[$lockout_key]);
                            
                            // Redirect to dashboard
                            header("Location: parent_dashboard.php");
                            exit();
                        } else {
                            $errors[] = "Invalid email or password";
                            $attempts++;
                            $_SESSION[$lockout_key] = [
                                'count' => $attempts,
                                'time' => time()
                            ];
                            
                            if ($attempts >= LOCKOUT_THRESHOLD) {
                                $login_disabled = true;
                                $errors[] = "Too many failed attempts. Account locked for " . (LOCKOUT_TIME / 60) . " minutes.";
                            }
                        }
                    }
                } else {
                    $errors[] = "Invalid email or password";
                    $attempts++;
                    $_SESSION[$lockout_key] = [
                        'count' => $attempts,
                        'time' => time()
                    ];
                    
                    if ($attempts >= LOCKOUT_THRESHOLD) {
                        $login_disabled = true;
                        $errors[] = "Too many failed attempts. Account locked for " . (LOCKOUT_TIME / 60) . " minutes.";
                    }
                }
            } catch (PDOException $e) {
                $errors[] = "System error. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Portal Login | Mumbe Group of Schools</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same styles as faculty login with minor color adjustments */
        :root {
            --primary-green: #1a4480;
            --secondary-teal: #daa520;
            --accent-orange: #ff8f00;
            --dark: #333;
            --light: #f8f9fa;
        }
        
        .navbar {
            background: linear-gradient(to right, var(--primary-green), #1b5e20);
        }
        
        .login-header {
            background: linear-gradient(to right, var(--primary-green), #1b5e20);
        }
        
        .form-label {
            color: var(--primary-green);
        }
        
        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.25rem rgba(46, 125, 50, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary-green), #1b5e20);
            box-shadow: 0 4px 10px rgba(46, 125, 50, 0.3);
        }
        
        .last-login {
            background: #e8f5e9;
            border-left: 3px solid var(--primary-green);
        }
        
        .school-logo {
            background: linear-gradient(to right, var(--primary-green), #1b5e20);
        }
        
        .school-logo i {
            color: white;
        }
        
        /* Keep other styles the same as faculty login */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark);
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            color: white !important;
            font-weight: 700;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            margin: 0 5px;
            padding: 10px 15px !important;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white !important;
            background-color: rgba(255, 215, 0, 0.2);
        }
        
        .login-container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            position: relative;
            z-index: 10;
        }
        
        .login-header {
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before, .login-header::after {
            content: "";
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .login-header::before {
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
        }
        
        .login-header::after {
            bottom: -30px;
            left: -30px;
            width: 100px;
            height: 100px;
        }
        
        .login-header h2 {
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .login-body {
            padding: 30px;
            position: relative;
            z-index: 1;
        }
        
        .form-control {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(46, 125, 50, 0.25);
        }
        
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
        }
        
        .btn-primary {
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
            border-radius: 8px;
            width: 100%;
            font-size: 18px;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        .footer {
            background: linear-gradient(to right, #0a1a30, #1b5e20);
            color: white;
            padding: 30px 0 15px;
            margin-top: auto;
            position: relative;
        }
        
        .footer::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--accent-orange), #ff8f00);
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--accent-orange);
        }
        
        .login-links {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .login-links a {
            color: var(--primary-green);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .login-links a:hover {
            color: #1b5e20;
            text-decoration: underline;
        }
        
        .bg-decoration {
            position: fixed;
            z-index: 0;
        }
        
        .bg-circle-1 {
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(46, 125, 50, 0.1) 0%, rgba(255, 143, 0, 0.1) 100%);
            top: -100px;
            right: -100px;
        }
        
        .bg-circle-2 {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255, 143, 0, 0.1) 0%, rgba(46, 125, 50, 0.1) 100%);
            bottom: 50px;
            left: -50px;
        }
        
        .bg-circle-3 {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(46, 125, 50, 0.08);
            bottom: 150px;
            right: 100px;
        }
        
        .school-logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .school-logo i {
            font-size: 48px;
        }
    </style>
</head>
<body>
    <!-- Background decorations -->
    <div class="bg-decoration bg-circle-1"></div>
    <div class="bg-decoration bg-circle-2"></div>
    <div class="bg-decoration bg-circle-3"></div>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <div style="width: 40px; height: 40px; background: var(--accent-orange); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                    <i class="fas fa-school text-white"></i>
                </div>
                <span>Mumbe Group of Schools</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Parent Portal</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Login Form -->
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <div class="school-logo">
                    <i class="fas fa-users"></i>
                </div>
                <h2>Parent Portal Login</h2>
                <p>Access your children's academic information</p>
            </div>
            
            <div class="login-body">
                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <strong>Login Error:</strong>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="parentLoginForm">
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Enter your registered email" required
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               <?= $login_disabled ? 'disabled' : '' ?>>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-container">
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Enter your password" required
                                   <?= $login_disabled ? 'disabled' : '' ?>>
                            <span class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary mb-4" <?= $login_disabled ? 'disabled' : '' ?>>
                        <i class="fas fa-sign-in-alt me-2"></i> Access Parent Portal
                    </button>
                    
                    <div class="login-links">
                        <a href="parent_register.php">
                            <i class="fas fa-user-plus me-1"></i> Register Account
                        </a>
                        <a href="parent_forgot_password.php">
                            <i class="fas fa-key me-1"></i> Forgot Password?
                        </a>
                    </div>
                    
                    <?php if (isset($_SESSION['parent_id']) && isset($_SESSION['last_login'])): ?>
                        <div class="last-login">
                            <p><strong>Last successful login:</strong> <?= date('F j, Y, g:i a', strtotime($_SESSION['last_login'])) ?></p>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <h5>Mumbe Group of Schools</h5>
                    <p>Providing quality education from kindergarten to high school since 2002.</p>
                    <p>Makindu, Makueni County, Kenya</p>
                </div>
                <div class="col-md-6 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled footer-links">
                        <li><a href="#"><i class="fas fa-chevron-right me-2"></i> Home</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right me-2"></i> About Us</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right me-2"></i> Parent Portal</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right me-2"></i> Contact</a></li>
                    </ul>
                </div>
            </div>
            <div class="text-center pt-3 border-top border-white border-opacity-10">
                <p class="mb-0">&copy; 2025 Mumbe Group of Schools. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap & JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password visibility toggle
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('togglePassword');
            
            toggleButton.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
            
            // Auto-focus on email field
            document.getElementById('email').focus();
            
            // Show last login info if available
            <?php if (isset($_SESSION['parent_id']) && isset($_SESSION['last_login'])): ?>
                const lastLogin = document.querySelector('.last-login');
                setTimeout(() => {
                    lastLogin.style.opacity = '1';
                    lastLogin.style.transform = 'translateY(0)';
                }, 500);
            <?php endif; ?>
        });
    </script>
</body>
</html>