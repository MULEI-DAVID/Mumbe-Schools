<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mumbe_schools');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Create database connection
try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8mb4");
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $school = trim($_POST['school'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Children data
    $children = [];
    if (isset($_POST['child_name']) && is_array($_POST['child_name'])) {
        foreach ($_POST['child_name'] as $index => $childName) {
            $admission = trim($_POST['child_admission'][$index] ?? '');
            $grade = trim($_POST['child_grade'][$index] ?? '');
            
            if (!empty($childName) && !empty($admission)) {
                $children[] = [
                    'name' => $childName,
                    'admission' => $admission,
                    'grade' => $grade
                ];
            }
        }
    }
    
    // Validate name
    if (empty($name)) {
        $errors['name'] = "Name is required";
    } elseif (strlen($name) < 3) {
        $errors['name'] = "Name must be at least 3 characters";
    }
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM parents WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $errors['email'] = "Email already registered";
        }
    }
    
    // Validate phone
    if (empty($phone)) {
        $errors['phone'] = "Phone number is required";
    } elseif (!preg_match('/^[0-9+ ]{10,15}$/', $phone)) {
        $errors['phone'] = "Invalid phone number";
    }
    
    // Validate school
    if (empty($school)) {
        $errors['school'] = "School selection is required";
    }
    
    // Validate children
    if (count($children) < 1) {
        $errors['children'] = "At least one child must be added";
    }
    
    // Validate password
    if (empty($password)) {
        $errors['password'] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters";
    } elseif (!preg_match('/[A-Z]/', $password) || 
               !preg_match('/[a-z]/', $password) || 
               !preg_match('/[0-9]/', $password)) {
        $errors['password'] = "Password must contain uppercase, lowercase, and numbers";
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match";
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert parent
            $stmt = $conn->prepare("INSERT INTO parents (name, email, phone, password_hash) 
                                   VALUES (:name, :email, :phone, :password_hash)");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':password_hash', $password_hash);
            $stmt->execute();
            $parent_id = $conn->lastInsertId();
            
            // Link children
            foreach ($children as $child) {
                // Check if student exists
                $stmt = $conn->prepare("SELECT id FROM students WHERE admission_number = :admission_number");
                $stmt->bindParam(':admission_number', $child['admission']);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $student = $stmt->fetch(PDO::FETCH_ASSOC);
                    $student_id = $student['id'];
                    
                    // Create parent-student relationship
                    $stmt = $conn->prepare("INSERT INTO parent_students (parent_id, student_id, relationship, is_primary) 
                                           VALUES (:parent_id, :student_id, 'parent', 1)");
                    $stmt->bindParam(':parent_id', $parent_id);
                    $stmt->bindParam(':student_id', $student_id);
                    $stmt->execute();
                } else {
                    // Student doesn't exist - create new?
                    // For now, just skip
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            $success = true;
            
        } catch(PDOException $e) {
            $conn->rollBack();
            $errors['database'] = "Registration failed: " . $e->getMessage();
        }
    }
}

// Get list of schools for dropdown
$schools = [
    'Mumbe Primary School',
    'Mumbe High School',
    'Mumbe Academy',
    'Mumbe International School'
];

// Grade levels
$gradeLevels = [
    'Pre-K', 'Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 
    'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8',
    'Form 1', 'Form 2', 'Form 3', 'Form 4'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Registration | Mumbe Group of Schools</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #1a4480;
            --secondary-yellow: #daa520;
            --accent-gold: #daa520;
            --light: #f8f9fa;
            --dark: #333;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            color: var(--dark);
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
        
        .school-logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .school-logo i {
            font-size: 48px;
            color: var(--primary-blue);
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
        
        .form-control {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        
        .form-control:focus {
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
            font-size: 18px;
            box-shadow: 0 4px 10px rgba(26, 68, 128, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-outline-primary {
            border-color: var(--primary-blue);
            color: var(--primary-blue);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-blue);
            color: white;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 5px;
        }
        
        .success-message {
            background: linear-gradient(to right, #28a745, #218838);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
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
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        
        .step-indicator::before {
            content: "";
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #dee2e6;
            z-index: 1;
        }
        
        .step {
            text-align: center;
            z-index: 2;
            position: relative;
            flex: 1;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #dee2e6;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .step.active .step-number {
            background: var(--primary-blue);
            color: white;
        }
        
        .step.completed .step-number {
            background: var(--secondary-yellow);
            color: white;
        }
        
        .step-text {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .step.active .step-text {
            color: var(--primary-blue);
            font-weight: 600;
        }
        
        .child-entry {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
        }
        
        .remove-child {
            color: #dc3545;
            cursor: pointer;
            font-size: 1.2rem;
        }
        
        .child-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .child-number {
            background-color: var(--primary-blue);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .section-title {
            color: var(--primary-blue);
            border-bottom: 2px solid var(--secondary-yellow);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .form-section {
            margin-bottom: 30px;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
    <!-- Background decorations -->
    <div class="bg-decoration bg-circle-1"></div>
    <div class="bg-decoration bg-circle-2"></div>
    <div class="bg-decoration bg-circle-3"></div>
    
    <div class="registration-container">
        <div class="registration-header">
            <div class="school-logo">
                <i class="fas fa-school"></i>
            </div>
            <h2>Parent Registration</h2>
            <p>Mumbe Group of Schools</p>
        </div>
        
        <div class="registration-body">
            <?php if ($success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle fa-2x mb-3"></i>
                    <h3>Registration Successful!</h3>
                    <p>Your parent account has been created successfully.</p>
                    <p>You can now log in to the parent portal using your credentials.</p>
                    <a href="parent_login.php" class="btn btn-light mt-3">Go to Login</a>
                </div>
            <?php else: ?>
                <?php if (!empty($errors['database'])): ?>
                    <div class="alert alert-danger">
                        <?= $errors['database'] ?>
                    </div>
                <?php endif; ?>
                
                <div class="step-indicator">
                    <div class="step active">
                        <div class="step-number">1</div>
                        <div class="step-text">Parent Info</div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-text">Children Info</div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-text">Account Setup</div>
                    </div>
                </div>
                
                <form method="POST" id="registrationForm">
                    <div class="form-section">
                        <h4 class="section-title">Parent Information</h4>
                        
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                       id="name" name="name" value="<?= htmlspecialchars($name) ?>" required>
                                <?php if (isset($errors['name'])): ?>
                                    <div class="error-message"><?= $errors['name'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                       id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="error-message"><?= $errors['email'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" 
                                       id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>" required>
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="error-message"><?= $errors['phone'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="school" class="form-label">School</label>
                                <select class="form-select <?= isset($errors['school']) ? 'is-invalid' : '' ?>" 
                                        id="school" name="school" required>
                                    <option value="">Select School</option>
                                    <?php foreach ($schools as $schoolOption): ?>
                                        <option value="<?= htmlspecialchars($schoolOption) ?>" 
                                            <?= ($school === $schoolOption) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($schoolOption) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['school'])): ?>
                                    <div class="error-message"><?= $errors['school'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="section-title">Children Information</h4>
                            <button type="button" class="btn btn-outline-primary" id="addChildBtn">
                                <i class="fas fa-plus me-1"></i> Add Child
                            </button>
                        </div>
                        
                        <?php if (isset($errors['children'])): ?>
                            <div class="alert alert-danger"><?= $errors['children'] ?></div>
                        <?php endif; ?>
                        
                        <div id="childrenContainer">
                            <?php if (count($children) > 0): ?>
                                <?php foreach ($children as $index => $child): ?>
                                    <div class="child-entry" id="childEntry<?= $index ?>">
                                        <div class="child-header">
                                            <div class="d-flex align-items-center">
                                                <div class="child-number"><?= $index + 1 ?></div>
                                                <h5 class="ms-2 mb-0">Child Information</h5>
                                            </div>
                                            <div class="remove-child" onclick="removeChild(<?= $index ?>)">
                                                <i class="fas fa-times"></i>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Child Name</label>
                                                <input type="text" class="form-control" 
                                                       name="child_name[]" 
                                                       value="<?= htmlspecialchars($child['name']) ?>" 
                                                       placeholder="Child's full name">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Admission Number</label>
                                                <input type="text" class="form-control" 
                                                       name="child_admission[]" 
                                                       value="<?= htmlspecialchars($child['admission']) ?>" 
                                                       placeholder="e.g. S12345" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Grade Level</label>
                                                <select class="form-select" name="child_grade[]">
                                                    <option value="">Select Grade</option>
                                                    <?php foreach ($gradeLevels as $grade): ?>
                                                        <option value="<?= htmlspecialchars($grade) ?>" 
                                                            <?= ($child['grade'] === $grade) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($grade) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="child-entry" id="childEntry0">
                                    <div class="child-header">
                                        <div class="d-flex align-items-center">
                                            <div class="child-number">1</div>
                                            <h5 class="ms-2 mb-0">Child Information</h5>
                                        </div>
                                        <div class="remove-child" onclick="removeChild(0)">
                                            <i class="fas fa-times"></i>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Child Name</label>
                                            <input type="text" class="form-control" 
                                                   name="child_name[]" 
                                                   placeholder="Child's full name">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Admission Number</label>
                                            <input type="text" class="form-control" 
                                                   name="child_admission[]" 
                                                   placeholder="e.g. S12345" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Grade Level</label>
                                            <select class="form-select" name="child_grade[]">
                                                <option value="">Select Grade</option>
                                                <?php foreach ($gradeLevels as $grade): ?>
                                                    <option value="<?= htmlspecialchars($grade) ?>">
                                                        <?= htmlspecialchars($grade) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4 class="section-title">Account Setup</h4>
                        
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="password-container">
                                    <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                                           id="password" name="password" required>
                                    <span class="password-toggle" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="error-message"><?= $errors['password'] ?></div>
                                <?php endif; ?>
                                <small class="text-muted">Must be at least 8 characters with uppercase, lowercase, and numbers</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="password-container">
                                    <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" 
                                           id="confirm_password" name="confirm_password" required>
                                    <span class="password-toggle" id="toggleConfirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <div class="error-message"><?= $errors['confirm_password'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-check mb-4">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" class="text-primary">Terms and Conditions</a> and 
                                <a href="#" class="text-primary">Privacy Policy</a> of Mumbe Group of Schools
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-user-plus me-2"></i> Register Account
                        </button>
                        
                        <div class="text-center mt-4">
                            <p>Already have an account? <a href="parent_login.php">Login here</a></p>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password visibility toggle
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const togglePassword = document.getElementById('togglePassword');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
            
            toggleConfirmPassword.addEventListener('click', function() {
                const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPasswordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
            
            // Child management
            let childCount = <?= count($children) > 0 ? count($children) : 1 ?>;
            
            // Add child button
            document.getElementById('addChildBtn').addEventListener('click', function() {
                childCount++;
                
                const childEntry = document.createElement('div');
                childEntry.className = 'child-entry';
                childEntry.id = `childEntry${childCount}`;
                childEntry.innerHTML = `
                    <div class="child-header">
                        <div class="d-flex align-items-center">
                            <div class="child-number">${childCount}</div>
                            <h5 class="ms-2 mb-0">Child Information</h5>
                        </div>
                        <div class="remove-child" onclick="removeChild(${childCount})">
                            <i class="fas fa-times"></i>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Child Name</label>
                            <input type="text" class="form-control" 
                                   name="child_name[]" 
                                   placeholder="Child's full name">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Admission Number</label>
                            <input type="text" class="form-control" 
                                   name="child_admission[]" 
                                   placeholder="e.g. S12345" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Grade Level</label>
                            <select class="form-select" name="child_grade[]">
                                <option value="">Select Grade</option>
                                <?php foreach ($gradeLevels as $grade): ?>
                                    <option value="<?= htmlspecialchars($grade) ?>">
                                        <?= htmlspecialchars($grade) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                `;
                
                document.getElementById('childrenContainer').appendChild(childEntry);
            });
            
            // Form validation
            const form = document.getElementById('registrationForm');
            form.addEventListener('submit', function(e) {
                // Validate terms agreement
                const terms = document.getElementById('terms');
                if (!terms.checked) {
                    e.preventDefault();
                    alert('You must agree to the Terms and Conditions');
                    return false;
                }
                
                // Validate at least one child added
                const children = document.querySelectorAll('input[name="child_admission[]"]');
                let hasChild = false;
                children.forEach(child => {
                    if (child.value.trim() !== '') {
                        hasChild = true;
                    }
                });
                
                if (!hasChild) {
                    e.preventDefault();
                    alert('Please add at least one child');
                    return false;
                }
                
                return true;
            });
        });
        
        // Remove child function
        function removeChild(index) {
            const childEntry = document.getElementById(`childEntry${index}`);
            if (childEntry && document.querySelectorAll('.child-entry').length > 1) {
                childEntry.remove();
                
                // Update child numbers
                const children = document.querySelectorAll('.child-entry');
                children.forEach((child, i) => {
                    const numberElement = child.querySelector('.child-number');
                    if (numberElement) {
                        numberElement.textContent = i + 1;
                    }
                });
            }
        }
    </script>
</body>
</html>