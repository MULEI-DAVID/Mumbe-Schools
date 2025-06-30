<?php
// Start session and check authentication
session_start();

// Simulate faculty authentication
if (!isset($_SESSION['faculty_logged_in'])) {
    $_SESSION['faculty_logged_in'] = true;
    $_SESSION['faculty_id'] = 1001;
    $_SESSION['faculty_name'] = 'Dr. Sarah Johnson';
    $_SESSION['faculty_department'] = 'Science Department';
    $_SESSION['faculty_school'] = 'Mumbe High School';
    $_SESSION['faculty_photo'] = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iIzFhNDQ4MCI+PHBhdGggZD0iTTEyIDJDNi40OCAyIDIgNi40OCAyIDEyczQuNDggMTAgMTAgMTAgMTAtNC40OCAxMC0xMFMxNy41MiAyIDEyIDJ6bTAgM2MxLjY2IDAgMyAxLjM0IDMgM3MtMS4zNCAzLTMgMy0zLTEuMzQtMy0zIDEuMzQtMyAzLTN6bTAgMTQuMmMtMi41IDAtNC43MS0xLjI4LTYtMy4yMi4wMy0xLjk5IDQtMy4wOCA2LTMuMDggMS45OSAwIDUuOTcgMS4wOSA2IDMuMDgtMS4yOSAxLjk0LTMuNSAzLjIyLTYgMy4yeiIvPjwvc3ZnPg==';
}

// Database connection simulation
$classes = [
    ['id' => 1, 'name' => 'Form 3 Physics', 'students' => 42],
    ['id' => 2, 'name' => 'Form 4 Physics', 'students' => 35],
    ['id' => 3, 'name' => 'Form 2 Science', 'students' => 40],
    ['id' => 4, 'name' => 'Form 1 Science', 'students' => 45]
];

$students = [
    ['id' => 1, 'admission_no' => 'S00123', 'name' => 'John Kamau', 'class_id' => 1],
    ['id' => 2, 'admission_no' => 'S00124', 'name' => 'Mary Wanjiku', 'class_id' => 1],
    ['id' => 3, 'admission_no' => 'S00125', 'name' => 'Peter Otieno', 'class_id' => 1],
    ['id' => 4, 'admission_no' => 'S00126', 'name' => 'Jane Akinyi', 'class_id' => 2],
    ['id' => 5, 'admission_no' => 'S00127', 'name' => 'David Mwangi', 'class_id' => 2],
    ['id' => 6, 'admission_no' => 'S00128', 'name' => 'Grace Wambui', 'class_id' => 3],
    ['id' => 7, 'admission_no' => 'S00129', 'name' => 'James Mutua', 'class_id' => 4]
];

$assignments = [
    ['id' => 1, 'title' => 'Physics Project - Forces', 'due_date' => 'Sep 20, 2023', 'class_id' => 1],
    ['id' => 2, 'title' => 'Chemistry Lab Report', 'due_date' => 'Sep 15, 2023', 'class_id' => 2],
    ['id' => 3, 'title' => 'Science Quiz 2', 'due_date' => 'Sep 10, 2023', 'class_id' => 3]
];

$notifications = [
    ['id' => 1, 'type' => 'assignment', 'text' => 'New assignment submitted by John Kamau', 'time' => '2 hours ago'],
    ['id' => 2, 'type' => 'message', 'text' => 'New message from James Mutua', 'time' => '5 hours ago'],
    ['id' => 3, 'type' => 'event', 'text' => 'Staff meeting reminder: Tomorrow at 10 AM', 'time' => '1 day ago']
];

// Determine current section
$sections = ['dashboard', 'classes', 'students', 'attendance', 'assignments', 'grades', 'timetable', 'messages', 'resources', 'profile'];
$current_section = isset($_GET['section']) && in_array($_GET['section'], $sections) ? $_GET['section'] : 'dashboard';

// Handle grade submission
$grade_message = '';
if (isset($_POST['add_grade'])) {
    $grade_message = "Grade for student ID {$_POST['student_id']} on assignment ID {$_POST['assignment_id']} saved successfully!";
}

// Handle attendance submission
if (isset($_POST['mark_attendance'])) {
    $attendance_message = "Attendance for {$_POST['class_id']} on {$_POST['attendance_date']} marked successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard | Mumbe Group of Schools</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #1a4480;
            --secondary-yellow: #daa520;
            --accent-gold: #daa520;
            --light-gray: #f5f7fa;
            --dark: #333;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --sidebar-width: 280px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f0f2f5;
            color: var(--dark);
            overflow-x: hidden;
        }
        
        /* Dashboard Layout */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(to bottom, var(--primary-blue), #0d2c52);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid var(--secondary-yellow);
            margin-bottom: 15px;
        }
        
        .sidebar-header h4 {
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            opacity: 0.8;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .menu-item:hover, .menu-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 4px solid var(--secondary-yellow);
        }
        
        .menu-item i {
            width: 30px;
            font-size: 18px;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: all 0.3s;
        }
        
        /* Top Navigation */
        .top-nav {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .page-title {
            font-weight: 700;
            color: var(--primary-blue);
            margin: 0;
        }
        
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .notification-badge {
            position: relative;
            cursor: pointer;
        }
        
        .notification-badge .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #dc3545;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        
        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--secondary-yellow);
        }
        
        /* Content Area */
        .content-area {
            padding: 30px;
        }
        
        .dashboard-section {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        .dashboard-section.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Dashboard Cards */
        .dashboard-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            overflow: hidden;
            transition: transform 0.3s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background: linear-gradient(to right, var(--primary-blue), #0d2c52);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: rgba(26, 68, 128, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 24px;
            color: var(--primary-blue);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        /* Table Styles */
        .dashboard-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .dashboard-table th {
            background-color: rgba(26, 68, 128, 0.05);
            color: var(--primary-blue);
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .dashboard-table td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .dashboard-table tr:hover td {
            background-color: rgba(26, 68, 128, 0.03);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block !important;
            }
        }
        
        .menu-toggle {
            display: none;
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 5px;
            width: 40px;
            height: 40px;
            font-size: 20px;
            cursor: pointer;
        }
        
        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 350px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 20px;
            z-index: 1000;
            display: none;
        }
        
        .notification-item {
            display: flex;
            gap: 15px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(26, 68, 128, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: var(--primary-blue);
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-time {
            font-size: 0.8rem;
            color: #777;
        }
        
        .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 250px;
            z-index: 1000;
            display: none;
        }
        
        .profile-dropdown a {
            display: block;
            padding: 12px 20px;
            text-decoration: none;
            color: var(--dark);
            transition: all 0.3s;
        }
        
        .profile-dropdown a:hover {
            background: rgba(26, 68, 128, 0.05);
            color: var(--primary-blue);
        }
        
        .dropdown-divider {
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            margin: 5px 0;
        }
        
        .attendance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .class-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .class-header {
            background: linear-gradient(to right, var(--primary-blue), #0d2c52);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .class-body {
            padding: 20px;
        }
        
        .class-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
        
        .class-stat {
            text-align: center;
        }
        
        .class-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-blue);
        }
        
        .class-stat-label {
            font-size: 0.8rem;
            color: #777;
        }
        
        .btn-mumbe {
            background: linear-gradient(to right, var(--primary-blue), #0d2c52);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-mumbe:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .event-calendar {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .event-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            border-left: 4px solid var(--secondary-yellow);
            transition: all 0.3s;
        }
        
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .event-date {
            background: var(--primary-blue);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .event-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--primary-blue);
        }
        
        .alert-success {
            background: linear-gradient(to right, #28a745, #218838);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .grade-form {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .grade-form label {
            font-weight: 600;
            color: var(--primary-blue);
        }
        
        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.25rem rgba(26, 68, 128, 0.25);
        }
        
        .attendance-form {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .timetable-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .timetable-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            border-left: 4px solid var(--secondary-yellow);
        }
        
        .timetable-day {
            font-weight: 600;
            color: var(--primary-blue);
            margin-bottom: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding-bottom: 10px;
        }
        
        .timetable-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px dashed rgba(0,0,0,0.05);
        }
        
        .message-item {
            padding: 15px;
            border-radius: 8px;
            background: white;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }
        
        .message-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .resource-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }
        
        .resource-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .resource-icon {
            width: 50px;
            height: 50px;
            background: rgba(26, 68, 128, 0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--primary-blue);
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .profile-form {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="<?= $_SESSION['faculty_photo'] ?>" alt="Faculty Photo">
                <h4><?= $_SESSION['faculty_name'] ?></h4>
                <p><?= $_SESSION['faculty_department'] ?></p>
                <p><?= $_SESSION['faculty_school'] ?></p>
            </div>
            
            <div class="sidebar-menu">
                <a href="?section=dashboard" class="menu-item <?= $current_section === 'dashboard' ? 'active' : '' ?>" data-target="dashboard">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="?section=classes" class="menu-item <?= $current_section === 'classes' ? 'active' : '' ?>" data-target="classes">
                    <i class="fas fa-chalkboard"></i>
                    <span>My Classes</span>
                </a>
                
                <a href="?section=students" class="menu-item <?= $current_section === 'students' ? 'active' : '' ?>" data-target="students">
                    <i class="fas fa-users"></i>
                    <span>Students</span>
                </a>
                
                <a href="?section=attendance" class="menu-item <?= $current_section === 'attendance' ? 'active' : '' ?>" data-target="attendance">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Attendance</span>
                </a>
                
                <a href="?section=assignments" class="menu-item <?= $current_section === 'assignments' ? 'active' : '' ?>" data-target="assignments">
                    <i class="fas fa-book"></i>
                    <span>Assignments</span>
                </a>
                
                <a href="?section=grades" class="menu-item <?= $current_section === 'grades' ? 'active' : '' ?>" data-target="grades">
                    <i class="fas fa-chart-bar"></i>
                    <span>Grades</span>
                </a>
                
                <a href="?section=timetable" class="menu-item <?= $current_section === 'timetable' ? 'active' : '' ?>" data-target="timetable">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Timetable</span>
                </a>
                
                <a href="?section=messages" class="menu-item <?= $current_section === 'messages' ? 'active' : '' ?>" data-target="messages">
                    <i class="fas fa-comments"></i>
                    <span>Messages</span>
                </a>
                
                <a href="?section=resources" class="menu-item <?= $current_section === 'resources' ? 'active' : '' ?>" data-target="resources">
                    <i class="fas fa-folder"></i>
                    <span>Resources</span>
                </a>
                
                <a href="?section=profile" class="menu-item <?= $current_section === 'profile' ? 'active' : '' ?>" data-target="profile">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                
                <a href="faculty_logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navigation -->
            <div class="top-nav">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <h2 class="page-title">
                    <?php 
                    $titles = [
                        'dashboard' => 'Dashboard',
                        'classes' => 'My Classes',
                        'students' => 'Students',
                        'attendance' => 'Attendance',
                        'assignments' => 'Assignments',
                        'grades' => 'Grades',
                        'timetable' => 'Timetable',
                        'messages' => 'Messages',
                        'resources' => 'Resources',
                        'profile' => 'Profile'
                    ];
                    echo $titles[$current_section];
                    ?>
                </h2>
                
                <div class="nav-actions">
                    <div class="notification-badge" id="notificationBtn">
                        <i class="fas fa-bell fa-lg"></i>
                        <span class="badge"><?= count($notifications) ?></span>
                        
                        <div class="notification-dropdown" id="notificationDropdown">
                            <h5 class="mb-3">Notifications</h5>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item">
                                    <div class="notification-icon">
                                        <i class="fas fa-<?= 
                                            $notification['type'] === 'assignment' ? 'book' : 
                                            ($notification['type'] === 'message' ? 'envelope' : 'calendar')
                                        ?>"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p class="mb-1"><?= $notification['text'] ?></p>
                                        <small class="notification-time"><?= $notification['time'] ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-2">
                                <a href="#" class="text-primary">View All Notifications</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="user-profile" id="profileBtn">
                        <img src="<?= $_SESSION['faculty_photo'] ?>" alt="User Photo">
                        <span><?= explode(' ', $_SESSION['faculty_name'])[0] ?></span>
                        
                        <div class="profile-dropdown" id="profileDropdown">
                            <div class="p-3 text-center">
                                <img src="<?= $_SESSION['faculty_photo'] ?>" alt="User Photo" class="mb-2" width="80" height="80" style="border-radius: 50%; border: 2px solid var(--secondary-yellow);">
                                <h5 class="mb-0"><?= $_SESSION['faculty_name'] ?></h5>
                                <p class="text-muted mb-0"><?= $_SESSION['faculty_department'] ?></p>
                            </div>
                            
                            <div class="dropdown-divider"></div>
                            
                            <a href="?section=profile">
                                <i class="fas fa-user me-2"></i> My Profile
                            </a>
                            
                            <a href="#">
                                <i class="fas fa-cog me-2"></i> Settings
                            </a>
                            
                            <div class="dropdown-divider"></div>
                            
                            <a href="faculty_logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="content-area">
                <!-- Dashboard Section -->
                <div class="dashboard-section <?= $current_section === 'dashboard' ? 'active' : '' ?>" id="dashboard">
                    <?php if (!empty($grade_message)): ?>
                        <div class="alert-success">
                            <i class="fas fa-check-circle me-2"></i> <?= $grade_message ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="stats-grid mb-4">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-value">142</div>
                            <div class="stat-label">Total Students</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-value">12</div>
                            <div class="stat-label">Active Assignments</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <div class="stat-value">93%</div>
                            <div class="stat-label">Attendance Rate</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="stat-value">3</div>
                            <div class="stat-label">Upcoming Events</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="dashboard-card">
                                <div class="card-header">
                                    <i class="fas fa-chalkboard"></i>
                                    <span>My Classes</span>
                                </div>
                                <div class="card-body">
                                    <div class="attendance-grid">
                                        <?php foreach ($classes as $class): ?>
                                            <div class="class-card">
                                                <div class="class-header"><?= $class['name'] ?></div>
                                                <div class="class-body">
                                                    <p class="mb-3"><?= $class['students'] ?> students</p>
                                                    <div class="class-stats">
                                                        <div class="class-stat">
                                                            <div class="class-stat-value">85%</div>
                                                            <div class="class-stat-label">Attendance</div>
                                                        </div>
                                                        <div class="class-stat">
                                                            <div class="class-stat-value">2</div>
                                                            <div class="class-stat-label">Assignments</div>
                                                        </div>
                                                    </div>
                                                    <a href="?section=students&class_id=<?= $class['id'] ?>" class="btn-mumbe w-100 mt-3">View Class</a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="dashboard-card">
                                <div class="card-header">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Upcoming Events</span>
                                </div>
                                <div class="card-body">
                                    <div class="event-calendar">
                                        <div class="event-card">
                                            <div class="event-date">Mon, 15 Sep</div>
                                            <div class="event-title">Staff Meeting</div>
                                            <p class="mb-0">All teaching staff required for curriculum planning</p>
                                        </div>
                                        
                                        <div class="event-card">
                                            <div class="event-date">Wed, 17 Sep</div>
                                            <div class="event-title">Science Fair</div>
                                            <p class="mb-0">Annual science competition for all students</p>
                                        </div>
                                        
                                        <div class="event-card">
                                            <div class="event-date">Fri, 19 Sep</div>
                                            <div class="event-title">Parents Meeting</div>
                                            <p class="mb-0">Form 3 parents meeting at the main hall</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-header">
                            <i class="fas fa-tasks"></i>
                            <span>Recent Assignments</span>
                        </div>
                        <div class="card-body">
                            <table class="dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Assignment</th>
                                        <th>Class</th>
                                        <th>Due Date</th>
                                        <th>Submissions</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Physics Project - Forces</td>
                                        <td>Form 3 Physics</td>
                                        <td>Sep 20, 2023</td>
                                        <td>32/42</td>
                                        <td><span class="badge bg-success">Active</span></td>
                                    </tr>
                                    <tr>
                                        <td>Chemistry Lab Report</td>
                                        <td>Form 4 Physics</td>
                                        <td>Sep 15, 2023</td>
                                        <td>28/35</td>
                                        <td><span class="badge bg-warning">Grading</span></td>
                                    </tr>
                                    <tr>
                                        <td>Science Quiz 2</td>
                                        <td>Form 2 Science</td>
                                        <td>Sep 10, 2023</td>
                                        <td>40/40</td>
                                        <td><span class="badge bg-secondary">Completed</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Classes Section -->
                <div class="dashboard-section <?= $current_section === 'classes' ? 'active' : '' ?>" id="classes">
                    <h3 class="mb-4">My Classes</h3>
                    <div class="attendance-grid">
                        <?php foreach ($classes as $class): ?>
                            <div class="class-card">
                                <div class="class-header"><?= $class['name'] ?></div>
                                <div class="class-body">
                                    <p class="mb-3"><?= $class['students'] ?> students</p>
                                    <div class="class-stats">
                                        <div class="class-stat">
                                            <div class="class-stat-value">85%</div>
                                            <div class="class-stat-label">Attendance</div>
                                        </div>
                                        <div class="class-stat">
                                            <div class="class-stat-value">2</div>
                                            <div class="class-stat-label">Assignments</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 d-flex gap-2">
                                        <a href="?section=students&class_id=<?= $class['id'] ?>" class="btn-mumbe flex-grow-1">View Students</a>
                                        <a href="?section=attendance&class_id=<?= $class['id'] ?>" class="btn btn-outline-primary">Attendance</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Students Section -->
                <div class="dashboard-section <?= $current_section === 'students' ? 'active' : '' ?>" id="students">
                    <h3 class="mb-4">Student Management</h3>
                    
                    <?php if (isset($_GET['class_id'])): ?>
                        <?php 
                        $class_id = $_GET['class_id'];
                        $class_name = $classes[$class_id-1]['name'];
                        ?>
                        <div class="dashboard-card">
                            <div class="card-header">
                                <i class="fas fa-users"></i>
                                <span>Class: <?= $class_name ?></span>
                            </div>
                            <div class="card-body">
                                <table class="dashboard-table">
                                    <thead>
                                        <tr>
                                            <th>Admission No</th>
                                            <th>Name</th>
                                            <th>Performance</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <?php if ($student['class_id'] == $class_id): ?>
                                                <tr>
                                                    <td><?= $student['admission_no'] ?></td>
                                                    <td><?= $student['name'] ?></td>
                                                    <td>
                                                        <div class="progress" style="height: 8px;">
                                                            <div class="progress-bar bg-success" style="width: 85%"></div>
                                                        </div>
                                                        <small>85% Average</small>
                                                    </td>
                                                    <td>
                                                        <a href="?section=grades&student_id=<?= $student['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-chart-bar me-1"></i> Grades
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Please select a class to view students
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Attendance Section -->
                <div class="dashboard-section <?= $current_section === 'attendance' ? 'active' : '' ?>" id="attendance">
                    <h3 class="mb-4">Attendance Management</h3>
                    
                    <?php if (isset($attendance_message)): ?>
                        <div class="alert-success">
                            <i class="fas fa-check-circle me-2"></i> <?= $attendance_message ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="attendance-form">
                        <form method="POST">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="class_id" class="form-label">Select Class</label>
                                    <select class="form-select" id="class_id" name="class_id" required>
                                        <option value="">Select Class</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?= $class['id'] ?>"><?= $class['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="attendance_date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="attendance_date" name="attendance_date" required>
                                </div>
                            </div>
                            
                            <div class="dashboard-card">
                                <div class="card-header">
                                    <i class="fas fa-user-check"></i>
                                    <span>Mark Attendance</span>
                                </div>
                                <div class="card-body">
                                    <table class="dashboard-table">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Status</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php for ($i=1; $i<=5; $i++): ?>
                                                <tr>
                                                    <td>Student <?= $i ?></td>
                                                    <td>
                                                        <select class="form-select" name="attendance_status[]">
                                                            <option value="present">Present</option>
                                                            <option value="absent">Absent</option>
                                                            <option value="late">Late</option>
                                                            <option value="excused">Excused</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="attendance_notes[]" placeholder="Notes">
                                                    </td>
                                                </tr>
                                            <?php endfor; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <button type="submit" name="mark_attendance" class="btn-mumbe mt-3">
                                <i class="fas fa-save me-2"></i> Save Attendance
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Assignments Section -->
                <div class="dashboard-section <?= $current_section === 'assignments' ? 'active' : '' ?>" id="assignments">
                    <h3 class="mb-4">Assignment Management</h3>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="dashboard-card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-tasks"></i>
                                    <span>My Assignments</span>
                                </div>
                                <div class="card-body">
                                    <table class="dashboard-table">
                                        <thead>
                                            <tr>
                                                <th>Assignment</th>
                                                <th>Class</th>
                                                <th>Due Date</th>
                                                <th>Submissions</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($assignments as $assignment): ?>
                                                <tr>
                                                    <td><?= $assignment['title'] ?></td>
                                                    <td><?= $classes[$assignment['class_id']-1]['name'] ?></td>
                                                    <td><?= $assignment['due_date'] ?></td>
                                                    <td>32/42</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye me-1"></i> View
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="dashboard-card">
                                <div class="card-header">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Create New Assignment</span>
                                </div>
                                <div class="card-body">
                                    <form>
                                        <div class="mb-3">
                                            <label for="assignment_title" class="form-label">Title</label>
                                            <input type="text" class="form-control" id="assignment_title" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="assignment_class" class="form-label">Class</label>
                                            <select class="form-select" id="assignment_class" required>
                                                <option value="">Select Class</option>
                                                <?php foreach ($classes as $class): ?>
                                                    <option value="<?= $class['id'] ?>"><?= $class['name'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="due_date" class="form-label">Due Date</label>
                                            <input type="date" class="form-control" id="due_date" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="assignment_desc" class="form-label">Description</label>
                                            <textarea class="form-control" id="assignment_desc" rows="3"></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="assignment_file" class="form-label">Attach File</label>
                                            <input type="file" class="form-control" id="assignment_file">
                                        </div>
                                        
                                        <button type="submit" class="btn-mumbe w-100">
                                            <i class="fas fa-plus me-2"></i> Create Assignment
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Grades Section -->
                <div class="dashboard-section <?= $current_section === 'grades' ? 'active' : '' ?>" id="grades">
                    <h3 class="mb-4">Grade Management</h3>
                    
                    <?php if (!empty($grade_message)): ?>
                        <div class="alert-success">
                            <i class="fas fa-check-circle me-2"></i> <?= $grade_message ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['student_id'])): ?>
                        <div class="dashboard-card mb-4">
                            <div class="card-header">
                                <i class="fas fa-user-graduate"></i>
                                <span>Student: John Kamau (S00123)</span>
                            </div>
                            <div class="card-body">
                                <h5 class="mb-3">Current Grades</h5>
                                <table class="dashboard-table">
                                    <thead>
                                        <tr>
                                            <th>Assignment</th>
                                            <th>Subject</th>
                                            <th>Due Date</th>
                                            <th>Grade</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Physics Project - Forces</td>
                                            <td>Physics</td>
                                            <td>Sep 20, 2023</td>
                                            <td>85%</td>
                                            <td><span class="badge bg-success">Graded</span></td>
                                        </tr>
                                        <tr>
                                            <td>Chemistry Lab Report</td>
                                            <td>Chemistry</td>
                                            <td>Sep 15, 2023</td>
                                            <td>78%</td>
                                            <td><span class="badge bg-success">Graded</span></td>
                                        </tr>
                                        <tr>
                                            <td>Science Quiz 2</td>
                                            <td>Science</td>
                                            <td>Sep 10, 2023</td>
                                            <td>92%</td>
                                            <td><span class="badge bg-success">Graded</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="dashboard-card">
                        <div class="card-header">
                            <i class="fas fa-plus-circle"></i>
                            <span>Add/Update Grade</span>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="grade-form">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="student_id" class="form-label">Student</label>
                                        <select class="form-select" id="student_id" name="student_id" required>
                                            <option value="">Select Student</option>
                                            <?php foreach ($students as $student): ?>
                                                <option value="<?= $student['id'] ?>"><?= $student['name'] ?> (<?= $student['admission_no'] ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="assignment_id" class="form-label">Assignment</label>
                                        <select class="form-select" id="assignment_id" name="assignment_id" required>
                                            <option value="">Select Assignment</option>
                                            <?php foreach ($assignments as $assignment): ?>
                                                <option value="<?= $assignment['id'] ?>"><?= $assignment['title'] ?> (Due: <?= $assignment['due_date'] ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="grade" class="form-label">Grade (0-100)</label>
                                        <input type="number" class="form-control" id="grade" name="grade" min="0" max="100" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="comments" class="form-label">Comments</label>
                                        <input type="text" class="form-control" id="comments" name="comments">
                                    </div>
                                </div>
                                
                                <button type="submit" name="add_grade" class="btn-mumbe">
                                    <i class="fas fa-save me-2"></i> Save Grade
                                </button>
                            </form>
                            
                            <div class="alert alert-info mt-4">
                                <i class="fas fa-info-circle me-2"></i>
                                Grades entered here will be immediately visible to students and parents through their portals.
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Timetable Section -->
                <div class="dashboard-section <?= $current_section === 'timetable' ? 'active' : '' ?>" id="timetable">
                    <h3 class="mb-4">Teaching Timetable</h3>
                    
                    <div class="timetable-grid">
                        <div class="timetable-card">
                            <div class="timetable-day">Monday</div>
                            <div class="timetable-item">
                                <span>8:00 AM - 9:00 AM</span>
                                <span>Form 3 Physics</span>
                            </div>
                            <div class="timetable-item">
                                <span>10:00 AM - 11:00 AM</span>
                                <span>Form 4 Physics</span>
                            </div>
                            <div class="timetable-item">
                                <span>1:00 PM - 2:00 PM</span>
                                <span>Form 2 Science</span>
                            </div>
                        </div>
                        
                        <div class="timetable-card">
                            <div class="timetable-day">Tuesday</div>
                            <div class="timetable-item">
                                <span>9:00 AM - 10:00 AM</span>
                                <span>Form 1 Science</span>
                            </div>
                            <div class="timetable-item">
                                <span>11:00 AM - 12:00 PM</span>
                                <span>Form 3 Physics Lab</span>
                            </div>
                        </div>
                        
                        <div class="timetable-card">
                            <div class="timetable-day">Wednesday</div>
                            <div class="timetable-item">
                                <span>8:00 AM - 9:00 AM</span>
                                <span>Form 4 Physics</span>
                            </div>
                            <div class="timetable-item">
                                <span>10:00 AM - 11:00 AM</span>
                                <span>Form 2 Science Lab</span>
                            </div>
                            <div class="timetable-item">
                                <span>2:00 PM - 3:00 PM</span>
                                <span>Form 1 Science</span>
                            </div>
                        </div>
                        
                        <div class="timetable-card">
                            <div class="timetable-day">Thursday</div>
                            <div class="timetable-item">
                                <span>8:00 AM - 10:00 AM</span>
                                <span>Form 3 Physics Lab</span>
                            </div>
                            <div class="timetable-item">
                                <span>1:00 PM - 2:00 PM</span>
                                <span>Form 4 Physics</span>
                            </div>
                        </div>
                        
                        <div class="timetable-card">
                            <div class="timetable-day">Friday</div>
                            <div class="timetable-item">
                                <span>9:00 AM - 10:00 AM</span>
                                <span>Form 2 Science</span>
                            </div>
                            <div class="timetable-item">
                                <span>11:00 AM - 12:00 PM</span>
                                <span>Form 1 Science Lab</span>
                            </div>
                            <div class="timetable-item">
                                <span>2:00 PM - 3:00 PM</span>
                                <span>Department Meeting</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Messages Section -->
                <div class="dashboard-section <?= $current_section === 'messages' ? 'active' : '' ?>" id="messages">
                    <h3 class="mb-4">Messages</h3>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="dashboard-card">
                                <div class="card-header">
                                    <i class="fas fa-user-friends"></i>
                                    <span>Contacts</span>
                                </div>
                                <div class="card-body">
                                    <div class="list-group">
                                        <a href="#" class="list-group-item list-group-item-action active">
                                            <div class="d-flex align-items-center">
                                                <img src="<?= $_SESSION['faculty_photo'] ?>" class="rounded-circle me-3" width="40" height="40">
                                                <div>
                                                    <h6 class="mb-0">Principal's Office</h6>
                                                    <small>Last message: 2 hours ago</small>
                                                </div>
                                            </div>
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action">
                                            <div class="d-flex align-items-center">
                                                <img src="<?= $_SESSION['faculty_photo'] ?>" class="rounded-circle me-3" width="40" height="40">
                                                <div>
                                                    <h6 class="mb-0">Science Department</h6>
                                                    <small>Last message: Yesterday</small>
                                                </div>
                                            </div>
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action">
                                            <div class="d-flex align-items-center">
                                                <img src="<?= $_SESSION['faculty_photo'] ?>" class="rounded-circle me-3" width="40" height="40">
                                                <div>
                                                    <h6 class="mb-0">John Kamau (Parent)</h6>
                                                    <small>Last message: Sep 12</small>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="dashboard-card">
                                <div class="card-header">
                                    <i class="fas fa-comments"></i>
                                    <span>Conversation with Principal's Office</span>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex flex-column" style="max-height: 400px; overflow-y: auto;">
                                        <div class="message-item">
                                            <div class="d-flex">
                                                <img src="<?= $_SESSION['faculty_photo'] ?>" class="rounded-circle me-3" width="40" height="40">
                                                <div>
                                                    <h6 class="mb-0">Principal's Office</h6>
                                                    <small class="text-muted">Today, 10:30 AM</small>
                                                    <p class="mb-0 mt-2">Dr. Johnson, please remember to submit your department budget proposal by tomorrow.</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="message-item bg-light">
                                            <div class="d-flex">
                                                <img src="<?= $_SESSION['faculty_photo'] ?>" class="rounded-circle me-3" width="40" height="40">
                                                <div>
                                                    <h6 class="mb-0">You</h6>
                                                    <small class="text-muted">Today, 10:45 AM</small>
                                                    <p class="mb-0 mt-2">Thank you for the reminder. I will submit it by end of day.</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="message-item">
                                            <div class="d-flex">
                                                <img src="<?= $_SESSION['faculty_photo'] ?>" class="rounded-circle me-3" width="40" height="40">
                                                <div>
                                                    <h6 class="mb-0">Principal's Office</h6>
                                                    <small class="text-muted">Today, 11:15 AM</small>
                                                    <p class="mb-0 mt-2">Also, don't forget about the staff meeting tomorrow at 10 AM in the conference room.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3 d-flex">
                                        <input type="text" class="form-control me-2" placeholder="Type your message...">
                                        <button class="btn-mumbe">
                                            <i class="fas fa-paper-plane"></i> Send
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Resources Section -->
                <div class="dashboard-section <?= $current_section === 'resources' ? 'active' : '' ?>" id="resources">
                    <h3 class="mb-4">Teaching Resources</h3>
                    
                    <div class="dashboard-card mb-4">
                        <div class="card-header">
                            <i class="fas fa-folder-plus"></i>
                            <span>Upload New Resource</span>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="resource_title" class="form-label">Title</label>
                                        <input type="text" class="form-control" id="resource_title" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="resource_class" class="form-label">Class</label>
                                        <select class="form-select" id="resource_class" required>
                                            <option value="">Select Class</option>
                                            <?php foreach ($classes as $class): ?>
                                                <option value="<?= $class['id'] ?>"><?= $class['name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="resource_desc" class="form-label">Description</label>
                                    <textarea class="form-control" id="resource_desc" rows="3"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="resource_file" class="form-label">Upload File</label>
                                    <input type="file" class="form-control" id="resource_file" required>
                                </div>
                                
                                <button type="submit" class="btn-mumbe">
                                    <i class="fas fa-upload me-2"></i> Upload Resource
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-header">
                            <i class="fas fa-folder-open"></i>
                            <span>My Resources</span>
                        </div>
                        <div class="card-body">
                            <div class="resource-item">
                                <div class="resource-icon">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1">Physics Lesson Plan - Forces</h5>
                                    <p class="mb-1 text-muted">Form 3 Physics | Uploaded: Sep 5, 2023</p>
                                    <div class="d-flex gap-2">
                                        <a href="#" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="#" class="btn btn-sm btn-outline-success">Download</a>
                                        <a href="#" class="btn btn-sm btn-outline-danger">Delete</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="resource-item">
                                <div class="resource-icon">
                                    <i class="fas fa-file-word"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1">Chemistry Lab Worksheet</h5>
                                    <p class="mb-1 text-muted">Form 4 Physics | Uploaded: Sep 1, 2023</p>
                                    <div class="d-flex gap-2">
                                        <a href="#" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="#" class="btn btn-sm btn-outline-success">Download</a>
                                        <a href="#" class="btn btn-sm btn-outline-danger">Delete</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="resource-item">
                                <div class="resource-icon">
                                    <i class="fas fa-file-powerpoint"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1">Science Presentation - Cells</h5>
                                    <p class="mb-1 text-muted">Form 2 Science | Uploaded: Aug 28, 2023</p>
                                    <div class="d-flex gap-2">
                                        <a href="#" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="#" class="btn btn-sm btn-outline-success">Download</a>
                                        <a href="#" class="btn btn-sm btn-outline-danger">Delete</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Section -->
                <div class="dashboard-section <?= $current_section === 'profile' ? 'active' : '' ?>" id="profile">
                    <h3 class="mb-4">My Profile</h3>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="dashboard-card">
                                <div class="card-header">
                                    <i class="fas fa-user"></i>
                                    <span>Profile Information</span>
                                </div>
                                <div class="card-body text-center">
                                    <img src="<?= $_SESSION['faculty_photo'] ?>" alt="User Photo" class="mb-3" width="120" height="120" style="border-radius: 50%; border: 3px solid var(--secondary-yellow);">
                                    <h4><?= $_SESSION['faculty_name'] ?></h4>
                                    <p class="text-muted"><?= $_SESSION['faculty_department'] ?></p>
                                    <p class="text-muted"><?= $_SESSION['faculty_school'] ?></p>
                                    
                                    <div class="mt-4">
                                        <h5>Contact Information</h5>
                                        <p class="mb-1"><i class="fas fa-envelope me-2"></i> sarah.johnson@mumbeschool.ac.ke</p>
                                        <p class="mb-1"><i class="fas fa-phone me-2"></i> +254 712 345 678</p>
                                        <p><i class="fas fa-map-marker-alt me-2"></i> Nairobi, Kenya</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="dashboard-card">
                                <div class="card-header">
                                    <i class="fas fa-edit"></i>
                                    <span>Edit Profile</span>
                                </div>
                                <div class="card-body">
                                    <form class="profile-form">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="full_name" class="form-label">Full Name</label>
                                                <input type="text" class="form-control" id="full_name" value="<?= $_SESSION['faculty_name'] ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="email" class="form-label">Email Address</label>
                                                <input type="email" class="form-control" id="email" value="sarah.johnson@mumbeschool.ac.ke">
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="phone" class="form-label">Phone Number</label>
                                                <input type="text" class="form-control" id="phone" value="+254 712 345 678">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="department" class="form-label">Department</label>
                                                <input type="text" class="form-control" id="department" value="<?= $_SESSION['faculty_department'] ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="school" class="form-label">School</label>
                                            <input type="text" class="form-control" id="school" value="<?= $_SESSION['faculty_school'] ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="bio" class="form-label">Bio</label>
                                            <textarea class="form-control" id="bio" rows="3">Experienced Physics teacher with 10+ years of teaching. Specialized in making complex concepts accessible to students.</textarea>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="profile_photo" class="form-label">Profile Photo</label>
                                            <input type="file" class="form-control" id="profile_photo">
                                        </div>
                                        
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="reset" class="btn btn-outline-secondary">Cancel</button>
                                            <button type="submit" class="btn-mumbe">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dashboard functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Menu toggle for mobile
            document.getElementById('menuToggle').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('active');
            });
            
            // Notification dropdown
            const notificationBtn = document.getElementById('notificationBtn');
            const notificationDropdown = document.getElementById('notificationDropdown');
            
            notificationBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.style.display = notificationDropdown.style.display === 'block' ? 'none' : 'block';
                profileDropdown.style.display = 'none';
            });
            
            // Profile dropdown
            const profileBtn = document.getElementById('profileBtn');
            const profileDropdown = document.getElementById('profileDropdown');
            
            profileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                profileDropdown.style.display = profileDropdown.style.display === 'block' ? 'none' : 'block';
                notificationDropdown.style.display = 'none';
            });
            
            // Close dropdowns when clicking elsewhere
            document.addEventListener('click', function(e) {
                if (!notificationBtn.contains(e.target)) {
                    notificationDropdown.style.display = 'none';
                }
                if (!profileBtn.contains(e.target)) {
                    profileDropdown.style.display = 'none';
                }
            });
            
            // Set active student in grade form if coming from student link
            <?php if (isset($_GET['student_id'])): ?>
                document.getElementById('student_id').value = <?= $_GET['student_id'] ?>;
            <?php endif; ?>
            
            // Set current date for attendance
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('attendance_date').value = today;
        });
    </script>
</body>
</html>