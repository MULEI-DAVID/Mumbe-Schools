<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mumbe_schools');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', 'Mumbe Group of Schools');
define('APP_URL', 'https://www.mumbeschools.com/');
define('APP_ENV', 'production');

// Security Settings
define('PEPPER', 'mumbe-educational-pepper-!@#$%^&*()');
define('TOKEN_EXPIRY', 3600); // 1 hour
define('LOCKOUT_THRESHOLD', 5); // 5 failed attempts
define('LOCKOUT_TIME', 1800); // 30 minutes lockout

// Portal Types
define('PORTAL_ADMIN', 'admin');
define('PORTAL_FACULTY', 'faculty');
define('PORTAL_PARENT', 'parent');
define('PORTAL_STUDENT', 'student');

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
    error_log("Database connection failed: " . $e->getMessage());
    die("System temporarily unavailable. Please try again later.");
}

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400, // 1 day
        'path' => '/',
        'domain' => parse_url(APP_URL, PHP_URL_HOST),
        'secure' => (APP_ENV === 'production'), // HTTPS in production
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    session_start();
}

// Authentication check for portals
$current_script = basename($_SERVER['PHP_SELF']);
$login_pages = ['login.php', 'register.php', 'forgot_password.php'];

if (!isset($_SESSION['user_id']) && !in_array($current_script, $login_pages)) {
    // Redirect to appropriate login page based on portal
    $portal = $_SESSION['portal_type'] ?? PORTAL_ADMIN;
    header("Location: " . APP_URL . $portal . "/login.php");
    exit();
}

// Fetch user data if logged in
$current_user = [];
if (isset($_SESSION['user_id'])) {
    try {
        $table = match($_SESSION['portal_type']) {
            PORTAL_ADMIN => 'admins',
            PORTAL_FACULTY => 'faculty',
            PORTAL_PARENT => 'parents',
            PORTAL_STUDENT => 'students',
            default => 'admins'
        };
        
        $stmt = $conn->prepare("SELECT id, name, email, role, created_at FROM $table WHERE id = :id");
        $stmt->bindValue(':id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            $current_user = $stmt->fetch();
            $current_user['portal_type'] = $_SESSION['portal_type'];
            
            // Generate avatar initials
            $names = explode(' ', $current_user['name']);
            $current_user['avatar'] = strtoupper(substr($names[0], 0, 1) . 
                (isset($names[1]) ? substr($names[1], 0, 1) : ''));
        } else {
            // Invalid session, destroy it
            session_destroy();
            $portal = $_SESSION['portal_type'] ?? PORTAL_ADMIN;
            header("Location: " . APP_URL . $portal . "/login.php");
            exit();
        }
    } catch (PDOException $e) {
        error_log("User fetch error: " . $e->getMessage());
        die("System error occurred. Please try again later.");
    }
}

// Initialize dashboard statistics
$stats = [
    'students' => 0,
    'faculty' => 0,
    'classes' => 0,
    'active_courses' => 0,
    'pending_assignments' => 0,
    'upcoming_events' => 0,
    'attendance_rate' => 0
];

// Fetch statistics only if user is logged in
if (isset($_SESSION['user_id'])) {
    $queries = [
        'students' => "SELECT COUNT(*) AS total FROM students WHERE status = 'active'",
        'faculty' => "SELECT COUNT(*) AS total FROM faculty WHERE status = 'active'",
        'classes' => "SELECT COUNT(*) AS total FROM classes",
        'active_courses' => "SELECT COUNT(*) AS total FROM courses WHERE status = 'active'",
        'pending_assignments' => "SELECT COUNT(*) AS total FROM assignments WHERE due_date >= CURDATE() AND status = 'pending'",
        'upcoming_events' => "SELECT COUNT(*) AS total FROM events WHERE event_date >= CURDATE()",
        'attendance_rate' => "SELECT ROUND(AVG(present)*100 AS rate FROM attendance WHERE date = CURDATE()"
    ];

    foreach ($queries as $key => $sql) {
        try {
            $stmt = $conn->query($sql);
            $row = $stmt->fetch();
            $stats[$key] = $row['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Statistics error ($key): " . $e->getMessage());
        }
    }
}

// Handle form submissions (only if user is logged in)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    // Add Student
    if (isset($_POST['add_student'])) {
        $name = trim($_POST['name']);
        $parent_id = (int)$_POST['parent_id'];
        $grade_level = trim($_POST['grade_level']);
        
        $errors = [];
        if (empty($name)) $errors[] = "Student name is required";
        if ($parent_id <= 0) $errors[] = "Parent selection is required";
        if (empty($grade_level)) $errors[] = "Grade level is required";
        
        if (empty($errors)) {
            try {
                $sql = "INSERT INTO students (name, parent_id, grade_level, status, join_date) 
                        VALUES (:name, :parent_id, :grade_level, 'active', NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':name', $name, PDO::PARAM_STR);
                $stmt->bindValue(':parent_id', $parent_id, PDO::PARAM_INT);
                $stmt->bindValue(':grade_level', $grade_level, PDO::PARAM_STR);
                $stmt->execute();
                
                $_SESSION['success'] = "Student added successfully!";
            } catch (PDOException $e) {
                error_log("Student add error: " . $e->getMessage());
                $_SESSION['error'] = "Error adding student. Please try again.";
            }
        } else {
            $_SESSION['error'] = implode("<br>", $errors);
        }
    }
    
    // Add Faculty
    if (isset($_POST['add_faculty'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $department = (int)$_POST['department'];
        $qualifications = trim($_POST['qualifications']);
        
        $errors = [];
        if (empty($name)) $errors[] = "Name is required";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
        if (empty($phone)) $errors[] = "Phone number is required";
        if ($department <= 0) $errors[] = "Department selection is required";
        
        if (empty($errors)) {
            try {
                $sql = "INSERT INTO faculty (name, email, phone, department_id, qualifications, status) 
                        VALUES (:name, :email, :phone, :department, :qualifications, 'active')";
                
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':name', $name, PDO::PARAM_STR);
                $stmt->bindValue(':email', $email, PDO::PARAM_STR);
                $stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
                $stmt->bindValue(':department', $department, PDO::PARAM_INT);
                $stmt->bindValue(':qualifications', $qualifications, PDO::PARAM_STR);
                $stmt->execute();
                
                $_SESSION['success'] = "Faculty member added successfully!";
            } catch (PDOException $e) {
                error_log("Faculty add error: " . $e->getMessage());
                $_SESSION['error'] = "Error adding faculty member. Please try again.";
            }
        } else {
            $_SESSION['error'] = implode("<br>", $errors);
        }
    }
    
    // Add Course
    if (isset($_POST['add_course'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $teacher_id = (int)$_POST['teacher_id'];
        $grade_level = trim($_POST['grade_level']);
        
        $errors = [];
        if (empty($title)) $errors[] = "Course title is required";
        if (empty($description)) $errors[] = "Description is required";
        if ($teacher_id <= 0) $errors[] = "Teacher selection is required";
        if (empty($grade_level)) $errors[] = "Grade level is required";
        
        if (empty($errors)) {
            try {
                $sql = "INSERT INTO courses (title, description, teacher_id, grade_level, status) 
                        VALUES (:title, :description, :teacher_id, :grade_level, 'active')";
                
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':title', $title, PDO::PARAM_STR);
                $stmt->bindValue(':description', $description, PDO::PARAM_STR);
                $stmt->bindValue(':teacher_id', $teacher_id, PDO::PARAM_INT);
                $stmt->bindValue(':grade_level', $grade_level, PDO::PARAM_STR);
                $stmt->execute();
                
                $_SESSION['success'] = "Course added successfully!";
            } catch (PDOException $e) {
                error_log("Course add error: " . $e->getMessage());
                $_SESSION['error'] = "Error adding course. Please try again.";
            }
        } else {
            $_SESSION['error'] = implode("<br>", $errors);
        }
    }
    
    // Update School Settings
    if (isset($_POST['update_settings'])) {
        $school_name = trim($_POST['school_name']);
        $school_email = trim($_POST['school_email']);
        $academic_year = trim($_POST['academic_year']);
        $term = trim($_POST['term']);
        
        $errors = [];
        if (empty($school_name)) $errors[] = "School name is required";
        if (!filter_var($school_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid school email";
        if (empty($academic_year)) $errors[] = "Academic year is required";
        if (empty($term)) $errors[] = "Term is required";
        
        if (empty($errors)) {
            try {
                $sql = "UPDATE school_settings 
                        SET school_name = :school_name, 
                            school_email = :school_email,
                            academic_year = :academic_year,
                            current_term = :term 
                        WHERE id = 1";
                
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':school_name', $school_name, PDO::PARAM_STR);
                $stmt->bindValue(':school_email', $school_email, PDO::PARAM_STR);
                $stmt->bindValue(':academic_year', $academic_year, PDO::PARAM_STR);
                $stmt->bindValue(':term', $term, PDO::PARAM_STR);
                $stmt->execute();
                
                $_SESSION['success'] = "School settings updated successfully!";
            } catch (PDOException $e) {
                error_log("Settings update error: " . $e->getMessage());
                $_SESSION['error'] = "Error updating settings. Please try again.";
            }
        } else {
            $_SESSION['error'] = implode("<br>", $errors);
        }
    }
    
    // Refresh page
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Fetch data for display (only if user is logged in)
$display_data = [];
if (isset($_SESSION['user_id'])) {
    $data_sets = [
        'students' => "SELECT s.id, s.name, p.name AS parent, s.grade_level, s.status 
                      FROM students s
                      JOIN parents p ON s.parent_id = p.id
                      ORDER BY s.join_date DESC LIMIT 10",
        
        'faculty' => "SELECT f.id, f.name, d.name AS department, f.email, f.status, f.join_date 
                     FROM faculty f
                     JOIN departments d ON f.department_id = d.id
                     ORDER BY f.join_date DESC LIMIT 10",
        
        'courses' => "SELECT c.id, c.title, f.name AS teacher, c.grade_level, c.status
                     FROM courses c
                     JOIN faculty f ON c.teacher_id = f.id
                     ORDER BY c.id DESC LIMIT 10",
        
        'assignments' => "SELECT a.id, c.title AS course, a.title, a.due_date, COUNT(sa.id) AS submissions
                         FROM assignments a
                         JOIN courses c ON a.course_id = c.id
                         LEFT JOIN student_assignments sa ON a.id = sa.assignment_id
                         GROUP BY a.id
                         ORDER BY a.due_date ASC LIMIT 10",
        
        'attendance' => "SELECT a.date, c.title AS course, 
                        COUNT(CASE WHEN a.present = 1 THEN 1 END) AS present_count,
                        COUNT(*) AS total_students
                        FROM attendance a
                        JOIN courses c ON a.course_id = c.id
                        WHERE a.date = CURDATE()
                        GROUP BY a.course_id",
        
        'events' => "SELECT id, title, event_date, location, audience 
                    FROM events 
                    WHERE event_date >= CURDATE()
                    ORDER BY event_date ASC LIMIT 10",
        
        'announcements' => "SELECT id, title, created_at, audience 
                           FROM announcements 
                           ORDER BY created_at DESC LIMIT 10"
    ];

    foreach ($data_sets as $key => $sql) {
        try {
            $stmt = $conn->query($sql);
            $display_data[$key] = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Data fetch error ($key): " . $e->getMessage());
            $display_data[$key] = [];
        }
    }
}

// Get school settings
$settings = [
    'school_name' => 'Mumbe Group of Schools',
    'school_email' => 'info@mumbeschools.com',
    'academic_year' => '2024/2025',
    'current_term' => 'Term 1',
    'timezone' => 'Africa/Nairobi'
];

try {
    $stmt = $conn->query("SELECT * FROM school_settings WHERE id = 1");
    if ($row = $stmt->fetch()) {
        $settings = array_merge($settings, $row);
    }
} catch (PDOException $e) {
    error_log("Settings fetch error: " . $e->getMessage());
}

// Check if first login
$firstLogin = isset($_SESSION['first_login']) ? $_SESSION['first_login'] : false;
unset($_SESSION['first_login']);

// Portal-specific initialization
if (isset($_SESSION['portal_type'])) {
    switch ($_SESSION['portal_type']) {
        case PORTAL_STUDENT:
            // Load student-specific data
            break;
            
        case PORTAL_PARENT:
            // Load parent-specific data
            break;
            
        case PORTAL_FACULTY:
            // Load faculty-specific data
            break;
    }
}