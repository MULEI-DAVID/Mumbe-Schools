<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mumbe_schools');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Security Settings
define('PEPPER', 'mumbe-educational-pepper-!@#$%^&*()');

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

// Handle form submission
$errors = [];
$success = false;
$schools = [];
$departments = [];

// Fetch schools and departments
try {
    $stmt = $conn->query("SELECT * FROM departments");
    $departments = $stmt->fetchAll();
    
    // Group departments by school (for demo purposes)
    $schools = [
        'Mumbe Kindergarten' => array_filter($departments, function($d) { return in_array($d['name'], ['Early Childhood']); }),
        'Mumbe Primary School' => array_filter($departments, function($d) { return in_array($d['name'], ['Languages', 'Mathematics', 'Humanities']); }),
        'Mumbe Junior Secondary' => array_filter($departments, function($d) { return in_array($d['name'], ['Mathematics', 'Sciences', 'Technology']); }),
        'Mumbe Girls High School' => $departments,
        'Mumbe Boys High School' => $departments,
    ];
} catch (PDOException $e) {
    $errors[] = "Failed to load school data. Please try again later.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form validation
    $required = ['auth_code', 'school', 'department', 'name', 'email', 'phone', 'password', 'confirm_password'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "All fields are required";
            break;
        }
    }
    
    // Validate email
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Validate phone
    if (!preg_match('/^[0-9]{10,15}$/', $_POST['phone'])) {
        $errors[] = "Phone number must be 10-15 digits";
    }
    
    // Validate password
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $errors[] = "Passwords do not match";
    } elseif (strlen($_POST['password']) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    // Validate authentication code
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT * FROM faculty_registration_codes 
                                    WHERE code = :code 
                                    AND used_at IS NULL 
                                    AND expires_at > NOW()");
            $stmt->bindValue(':code', $_POST['auth_code']);
            $stmt->execute();
            $code = $stmt->fetch();
            
            if (!$code) {
                $errors[] = "Invalid or expired authentication code";
            }
        } catch (PDOException $e) {
            $errors[] = "System error. Please try again later.";
        }
    }
    
    // Check if email exists
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT id FROM faculty WHERE email = :email");
            $stmt->bindValue(':email', $_POST['email']);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                $errors[] = "Email address already registered";
            }
        } catch (PDOException $e) {
            $errors[] = "System error. Please try again later.";
        }
    }
    
    // Register faculty if no errors
    if (empty($errors)) {
        // Hash password
        $password_hash = password_hash($_POST['password'] . PEPPER, PASSWORD_DEFAULT);
        
        try {
            // Start transaction
            $conn->beginTransaction();
            
            // Insert faculty
            $stmt = $conn->prepare("INSERT INTO faculty 
                (name, email, phone, password_hash, department_id, registration_code, qualifications, status, join_date)
                VALUES (:name, :email, :phone, :password_hash, :department_id, :registration_code, :qualifications, 'pending', CURDATE())");
            
            $stmt->bindValue(':name', $_POST['name']);
            $stmt->bindValue(':email', $_POST['email']);
            $stmt->bindValue(':phone', $_POST['phone']);
            $stmt->bindValue(':password_hash', $password_hash);
            $stmt->bindValue(':department_id', $_POST['department']);
            $stmt->bindValue(':registration_code', $_POST['auth_code']);
            $stmt->bindValue(':qualifications', $_POST['qualifications'] ?? '');
            
            if ($stmt->execute()) {
                // Mark code as used
                $stmt = $conn->prepare("UPDATE faculty_registration_codes 
                                       SET used_at = NOW(), used_by = :faculty_id 
                                       WHERE id = :code_id");
                $faculty_id = $conn->lastInsertId();
                $stmt->bindValue(':faculty_id', $faculty_id);
                $stmt->bindValue(':code_id', $code['id']);
                $stmt->execute();
                
                $conn->commit();
                $success = true;
            } else {
                $conn->rollBack();
                $errors[] = "Registration failed. Please try again.";
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = "System error. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Registration | Mumbe Group of Schools</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #1a4480;
            --secondary-yellow: #daa520;
            --accent-gold: #daa520;
            --dark: #333;
            --light: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark);
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header & Navigation */
        .navbar {
            background: linear-gradient(to right, var(--primary-blue), #0d2c52);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
        
        .registration-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            position: relative;
            z-index: 10;
        }
        
        .registration-header {
            background: linear-gradient(to right, var(--primary-blue), #0d2c52);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .registration-header::before {
            content: "";
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .registration-header::after {
            content: "";
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .registration-header h2 {
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .registration-body {
            padding: 30px;
            position: relative;
            z-index: 1;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--primary-blue);
            margin-bottom: 5px;
        }
        
        .form-control, .form-select {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.25rem rgba(26, 68, 128, 0.25);
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
            background: linear-gradient(to right, var(--primary-blue), #0d2c52);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
            border-radius: 8px;
            width: 100%;
            font-size: 18px;
            box-shadow: 0 4px 10px rgba(26, 68, 128, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        
        .form-section {
            background: #f9fafb;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 4px solid var(--secondary-yellow);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .form-section-title {
            color: var(--primary-blue);
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .form-section-title i {
            margin-right: 10px;
            color: var(--secondary-yellow);
        }
        
        .school-logo {
            height: 40px;
            margin-right: 10px;
        }
        
        .success-message {
            background: linear-gradient(to right, #28a745, #218838);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.2);
        }
        
        .success-message i {
            font-size: 48px;
            margin-bottom: 20px;
            display: block;
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
            background: linear-gradient(to right, #0a1a30, var(--primary-blue));
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
            background: linear-gradient(to right, var(--secondary-yellow), var(--accent-gold));
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--secondary-yellow);
        }
        
        .login-link {
            text-align: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .login-link a {
            color: var(--primary-blue);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .login-link a:hover {
            color: #0d2c52;
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .registration-container {
                margin: 20px;
            }
            
            .registration-header, .registration-body {
                padding: 20px;
            }
        }
        
        /* Background decoration */
        .bg-decoration {
            position: fixed;
            z-index: 0;
        }
        
        .bg-circle-1 {
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(26, 68, 128, 0.1) 0%, rgba(218, 165, 32, 0.1) 100%);
            top: -100px;
            right: -100px;
        }
        
        .bg-circle-2 {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(218, 165, 32, 0.1) 0%, rgba(26, 68, 128, 0.1) 100%);
            bottom: 50px;
            left: -50px;
        }
        
        .bg-circle-3 {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(26, 68, 128, 0.08);
            bottom: 150px;
            right: 100px;
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
                <div style="width: 40px; height: 40px; background: var(--secondary-yellow); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
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
                        <a class="nav-link active" href="#">Faculty Portal</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Registration Form -->
    <div class="container">
        <div class="registration-container">
            <div class="registration-header">
                <h2><i class="fas fa-chalkboard-teacher me-2"></i>Faculty Registration</h2>
                <p>Join our team of dedicated educators at Mumbe Group of Schools</p>
            </div>
            
            <div class="registration-body">
                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <strong>Registration Error:</strong>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <h3>Registration Successful!</h3>
                        <p>Your faculty registration has been submitted for review.</p>
                        <p>You will receive an email confirmation once your account is activated.</p>
                        <a href="faculty_login.php" class="btn btn-light mt-3">Proceed to Login</a>
                    </div>
                <?php else: ?>
                    <form method="POST" id="facultyRegistrationForm">
                        <!-- Personal Information -->
                        <div class="form-section">
                            <h4 class="form-section-title">
                                <i class="fas fa-user"></i> Personal Information
                            </h4>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           placeholder="Enter your full name" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="Enter your email" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           placeholder="e.g., 0712345678" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="qualifications" class="form-label">Qualifications</label>
                                    <input type="text" class="form-control" id="qualifications" name="qualifications" 
                                           placeholder="Degrees, certifications, etc.">
                                </div>
                            </div>
                        </div>

                         <!-- School & Department Selection -->
                        <div class="form-section">
                            <h4 class="form-section-title">
                                <i class="fas fa-school"></i> School & Department
                            </h4>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="school" class="form-label">School</label>
                                    <select class="form-select" id="school" name="school" required>
                                        <option value="" disabled selected>Select a school</option>
                                        <?php foreach ($schools as $school => $depts): ?>
                                            <option value="<?= htmlspecialchars($school) ?>"><?= htmlspecialchars($school) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="department" class="form-label">Department</label>
                                    <select class="form-select" id="department" name="department" required disabled>
                                        <option value="" disabled selected>Select department</option>
                                        <!-- Departments will be populated by JavaScript -->
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Authentication Section -->
                        <div class="form-section">
                            <h4 class="form-section-title">
                                <i class="fas fa-key"></i> Authentication
                            </h4>
                            <div class="mb-3">
                                <label for="auth_code" class="form-label">Authentication Code</label>
                                <input type="text" class="form-control" id="auth_code" name="auth_code" 
                                       placeholder="Enter code provided by administration" required>
                                <small class="form-text text-muted">This code is provided by the school administration for faculty registration.</small>
                            </div>
                        </div>

                        <!-- Password Section -->
                        <div class="form-section">
                            <h4 class="form-section-title">
                                <i class="fas fa-lock"></i> Account Security
                            </h4>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="password-container">
                                        <input type="password" class="form-control" id="password" name="password" 
                                               placeholder="Create a strong password" required>
                                        <span class="password-toggle" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                    <div class="password-strength mt-2">
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar" id="passwordStrengthBar" role="progressbar" style="width: 0%;"></div>
                                        </div>
                                        <small id="passwordHelp" class="form-text"></small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <div class="password-container">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                               placeholder="Confirm your password" required>
                                        <span class="password-toggle" id="toggleConfirmPassword">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                    <small id="confirmPasswordHelp" class="form-text text-danger d-none">Passwords do not match</small>
                                </div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" class="text-primary">Terms of Service</a> and <a href="#" class="text-primary">Privacy Policy</a>
                                </label>
                            </div>
                            
                            <!-- Login link added here -->
                            <div class="login-link">
                                <p>Already registered? <a href="faculty_login.php">Login to your account</a></p>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i> Complete Registration
                        </button>
                    </form>
                <?php endif; ?>
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
                        <li><a href="#"><i class="fas fa-chevron-right me-2"></i> Faculty Portal</a></li>
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
            // School and department data from PHP
            const departmentsBySchool = <?= json_encode($schools) ?>;
            
            // School selection handler
            const schoolSelect = document.getElementById('school');
            const departmentSelect = document.getElementById('department');
            
            schoolSelect.addEventListener('change', function() {
                const selectedSchool = this.value;
                
                // Clear previous departments
                departmentSelect.innerHTML = '<option value="" disabled selected>Select department</option>';
                departmentSelect.disabled = !selectedSchool;
                
                if (selectedSchool && departmentsBySchool[selectedSchool]) {
                    departmentsBySchool[selectedSchool].forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept.id;
                        option.textContent = dept.name;
                        departmentSelect.appendChild(option);
                    });
                }
            });
            
            // Password visibility toggle
            function setupPasswordToggle(inputId, toggleId) {
                const passwordInput = document.getElementById(inputId);
                const toggleButton = document.getElementById(toggleId);
                
                toggleButton.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                });
            }
            
            setupPasswordToggle('password', 'togglePassword');
            setupPasswordToggle('confirm_password', 'toggleConfirmPassword');
            
            // Password strength meter
            const passwordInput = document.getElementById('password');
            const strengthBar = document.getElementById('passwordStrengthBar');
            const passwordHelp = document.getElementById('passwordHelp');
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let message = '';
                
                // Check password length
                if (password.length >= 8) strength += 25;
                
                // Check for uppercase letters
                if (/[A-Z]/.test(password)) strength += 25;
                
                // Check for numbers
                if (/[0-9]/.test(password)) strength += 25;
                
                // Check for special characters
                if (/[^A-Za-z0-9]/.test(password)) strength += 25;
                
                // Update progress bar
                strengthBar.style.width = strength + '%';
                
                // Set color and message
                if (strength < 50) {
                    strengthBar.className = 'progress-bar bg-danger';
                    message = 'Weak password';
                } else if (strength < 75) {
                    strengthBar.className = 'progress-bar bg-warning';
                    message = 'Medium strength password';
                } else {
                    strengthBar.className = 'progress-bar bg-success';
                    message = 'Strong password';
                }
                
                passwordHelp.textContent = message;
            });
            
            // Password confirmation validation
            const confirmPasswordInput = document.getElementById('confirm_password');
            const confirmPasswordHelp = document.getElementById('confirmPasswordHelp');
            
            function validatePasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (password && confirmPassword && password !== confirmPassword) {
                    confirmPasswordHelp.classList.remove('d-none');
                    return false;
                } else {
                    confirmPasswordHelp.classList.add('d-none');
                    return true;
                }
            }
            
            passwordInput.addEventListener('input', validatePasswordMatch);
            confirmPasswordInput.addEventListener('input', validatePasswordMatch);
            
            // Form validation
            const form = document.getElementById('facultyRegistrationForm');
            
            form.addEventListener('submit', function(e) {
                if (!validatePasswordMatch()) {
                    e.preventDefault();
                    confirmPasswordHelp.classList.remove('d-none');
                }
            });
        });
    </script>
</body>
</html>