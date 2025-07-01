<?php
session_start();

// Simulate database connection and data retrieval
$parentData = [
    'name' => 'Parent User',
    'email' => 'parent@example.com',
    'join_date' => date('Y-m-d', strtotime('-1 week')),
    'last_login' => date('Y-m-d H:i:s'),
    'children' => [
        [
            'name' => 'Student One',
            'grade' => 'Grade 5',
            'house' => 'Blue House',
            'avatar' => 'avatar1.png'
        ],
        [
            'name' => 'Student Two',
            'grade' => 'Grade 3',
            'house' => 'Green House',
            'avatar' => 'avatar2.png'
        ]
    ],
    'is_first_login' => true // This would come from database in real app
];

// Check if first login (simulated)
if (!isset($_SESSION['first_login_completed'])) {
    $is_first_login = true;
    $_SESSION['first_login_completed'] = true;
} else {
    $is_first_login = false;
}

// Handle commercial offer dismissal
if (isset($_POST['dismiss_offer'])) {
    $_SESSION['commercial_offer_dismissed'] = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard | Mumbe Group of Schools</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-teal: #008080;
            --secondary-teal: #006666;
            --accent-gold: #daa520;
            --light-teal: #e6f2f2;
            --dark-teal: #004d4d;
            --light-gray: #f8f9fa;
            --medium-gray: #e9ecef;
            --text-dark: #333;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--text-dark);
        }
        
        .sidebar {
            background: linear-gradient(to bottom, var(--primary-teal), var(--dark-teal));
            color: white;
            min-height: 100vh;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
            position: fixed;
            width: 250px;
            z-index: 100;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }
        
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 99;
        }
        
        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent-gold);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 12px 20px;
            border-radius: 5px;
            margin: 5px 15px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
        }
        
        .sidebar .nav-link i {
            width: 25px;
            text-align: center;
            margin-right: 10px;
        }
        
        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid var(--medium-gray);
            font-weight: 600;
            color: var(--primary-teal);
            padding: 15px 20px;
            border-radius: 12px 12px 0 0 !important;
        }
        
        .card-title {
            color: var(--dark-teal);
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .welcome-banner {
            background: linear-gradient(to right, var(--primary-teal), var(--dark-teal));
            color: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            text-align: center;
            padding: 20px;
            border-radius: 12px;
            color: white;
            margin-bottom: 20px;
        }
        
        .stat-card.blue {
            background: linear-gradient(to right, #3a7bd5, #00d2ff);
        }
        
        .stat-card.green {
            background: linear-gradient(to right, #00b09b, #96c93d);
        }
        
        .stat-card.orange {
            background: linear-gradient(to right, #ff8c00, #ffcc00);
        }
        
        .child-selector {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        
        .child-item {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .child-item:hover, .child-item.active {
            background: var(--light-teal);
            border-color: var(--primary-teal);
        }
        
        .child-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent-gold);
            background: linear-gradient(45deg, #3a7bd5, #00d2ff);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        
        .subject-card {
            padding: 15px;
            border-radius: 8px;
            background: white;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-teal);
        }
        
        .meeting-item {
            padding: 15px;
            border-radius: 8px;
            background: white;
            margin-bottom: 15px;
            border-left: 4px solid var(--accent-gold);
        }
        
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .badge-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-overdue {
            background: #f8d7da;
            color: #721c24;
        }
        
        .fee-item {
            padding: 15px;
            border-radius: 8px;
            background: white;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-teal);
        }
        
        .progress-container {
            height: 10px;
            background: var(--medium-gray);
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .progress-bar {
            height: 100%;
            border-radius: 5px;
        }
        
        .btn-action {
            background: var(--primary-teal);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-action:hover {
            background: var(--dark-teal);
            transform: translateY(-2px);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-logo {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-footer {
            padding: 20px;
            text-align: center;
            position: absolute;
            bottom: 0;
            width: 100%;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .commercial-offer {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            z-index: 1000;
            max-width: 600px;
            width: 90%;
            overflow: hidden;
        }
        
        .offer-header {
            background: linear-gradient(to right, #ff8c00, #ffcc00);
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .offer-body {
            padding: 30px;
        }
        
        .offer-feature {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .offer-feature i {
            font-size: 24px;
            color: #ff8c00;
            margin-right: 15px;
            min-width: 30px;
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 999;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar .nav-link span {
                display: none;
            }
            
            .sidebar .nav-link i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            
            .main-content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
            
            .sidebar .logo-text {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Commercial Offer Modal (only shown on first login) -->
    <?php if ($is_first_login && !isset($_SESSION['commercial_offer_dismissed'])): ?>
    <div class="overlay"></div>
    <div class="commercial-offer">
        <div class="offer-header">
            <h3><i class="fas fa-gift me-2"></i> Welcome to Mumbe Schools!</h3>
            <p>Special Offer for New Parents</p>
        </div>
        <div class="offer-body">
            <h4 class="text-center mb-4">Upgrade to Premium Parent Plan</h4>
            
            <div class="offer-feature">
                <i class="fas fa-check-circle"></i>
                <div>
                    <h5>Enhanced Performance Tracking</h5>
                    <p class="mb-0">Detailed analytics of your child's academic progress with predictive insights</p>
                </div>
            </div>
            
            <div class="offer-feature">
                <i class="fas fa-check-circle"></i>
                <div>
                    <h5>Priority Meeting Scheduling</h5>
                    <p class="mb-0">Book meetings with teachers 48 hours before standard members</p>
                </div>
            </div>
            
            <div class="offer-feature">
                <i class="fas fa-check-circle"></i>
                <div>
                    <h5>Exclusive Learning Resources</h5>
                    <p class="mb-0">Access premium educational content for home learning support</p>
                </div>
            </div>
            
            <div class="offer-feature">
                <i class="fas fa-check-circle"></i>
                <div>
                    <h5>Fee Payment Flexibility</h5>
                    <p class="mb-0">Split payments and extended due dates without penalties</p>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <div class="pricing mb-4">
                    <h2 class="text-primary">Ksh 1,500/term</h2>
                    <p class="text-muted">First term FREE for new parents!</p>
                </div>
                
                <form method="POST" class="d-inline">
                    <button type="submit" name="dismiss_offer" class="btn btn-lg btn-outline-primary me-2">
                        Maybe Later
                    </button>
                </form>
                <button class="btn btn-lg btn-primary">
                    <i class="fas fa-rocket me-1"></i> Upgrade Now
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <div class="d-flex align-items-center justify-content-center">
                <i class="fas fa-school fa-2x text-warning"></i>
                <h5 class="ms-2 mb-0 logo-text">Mumbe Schools</h5>
            </div>
        </div>
        
        <ul class="nav flex-column mt-4">
            <li class="nav-item">
                <a class="nav-link active" href="#">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-user-graduate"></i>
                    <span>Children</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-chart-line"></i>
                    <span>Performance</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Meetings</span>
                    <span class="notification-badge">3</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>School Fees</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-file-alt"></i>
                    <span>Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <span class="notification-badge">5</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <a href="#" class="btn btn-sm btn-outline-light">
                <i class="fas fa-sign-out-alt"></i>
                <span class="logo-text">Logout</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation Bar -->
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <button class="btn btn-sm btn-light d-lg-none" type="button">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="d-flex align-items-center ms-auto">
                    <div class="position-relative me-4">
                        <i class="fas fa-bell fa-lg text-muted"></i>
                        <span class="notification-badge">5</span>
                    </div>
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle text-decoration-none" data-bs-toggle="dropdown">
                            <div class="profile-img">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="d-none d-md-inline">Parent User</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3>Welcome to Mumbe Schools!</h3>
                    <p class="mb-0">Your parent dashboard for academic insights and school management</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <span class="badge bg-warning p-2">Term 2 in progress</span>
                </div>
            </div>
        </div>
        
        <!-- Stats Summary -->
        <div class="row">
            <div class="col-md-4">
                <div class="stat-card blue">
                    <h3><i class="fas fa-user-graduate me-2"></i> 2</h3>
                    <p>Children Enrolled</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card green">
                    <h3><i class="fas fa-calendar-check me-2"></i> 3</h3>
                    <p>Upcoming Meetings</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card orange">
                    <h3><i class="fas fa-money-bill-wave me-2"></i> Ksh 12,500</h3>
                    <p>Outstanding Fees</p>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Children Selector -->
                <div class="child-selector">
                    <h5 class="card-title">Select Child</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="child-item active">
                                <div class="d-flex align-items-center">
                                    <div class="child-avatar me-3">S1</div>
                                    <div>
                                        <h6 class="mb-1">Student One</h6>
                                        <p class="mb-0 text-muted">Grade 5 - Blue House</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="child-item">
                                <div class="d-flex align-items-center">
                                    <div class="child-avatar me-3">S2</div>
                                    <div>
                                        <h6 class="mb-1">Student Two</h6>
                                        <p class="mb-0 text-muted">Grade 3 - Green House</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Performance Summary -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Student Performance Summary</h5>
                        <button class="btn btn-sm btn-action">View Full Report</button>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Term 2 Grades</h6>
                                <canvas id="gradesChart" height="200"></canvas>
                            </div>
                            <div class="col-md-6">
                                <h6>Attendance</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Present: 92%</span>
                                    <span>Absent: 8%</span>
                                </div>
                                <div class="progress mb-4" style="height: 20px;">
                                    <div class="progress-bar bg-success" style="width: 92%">92%</div>
                                </div>
                                
                                <h6>Subject Performance</h6>
                                <div class="subject-card">
                                    <div class="d-flex justify-content-between">
                                        <strong>Mathematics</strong>
                                        <span>87% (A)</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar bg-success" style="width: 87%"></div>
                                    </div>
                                </div>
                                <div class="subject-card">
                                    <div class="d-flex justify-content-between">
                                        <strong>English</strong>
                                        <span>78% (B+)</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar bg-success" style="width: 78%"></div>
                                    </div>
                                </div>
                                <div class="subject-card">
                                    <div class="d-flex justify-content-between">
                                        <strong>Science</strong>
                                        <span>82% (A-)</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar bg-success" style="width: 82%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Meetings Section -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Upcoming Meetings</h5>
                        <button class="btn btn-sm btn-action">Schedule Meeting</button>
                    </div>
                    <div class="card-body">
                        <div class="meeting-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Parent-Teacher Conference</h6>
                                    <p class="mb-0"><i class="fas fa-calendar me-2"></i> May 15, 2023 | 10:00 AM - 11:00 AM</p>
                                    <p class="mb-0"><i class="fas fa-map-marker-alt me-2"></i> School Administration Building</p>
                                </div>
                                <span class="badge badge-status bg-info">Confirmed</span>
                            </div>
                        </div>
                        
                        <div class="meeting-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Science Fair Planning</h6>
                                    <p class="mb-0"><i class="fas fa-calendar me-2"></i> May 20, 2023 | 2:00 PM - 3:30 PM</p>
                                    <p class="mb-0"><i class="fas fa-map-marker-alt me-2"></i> Science Laboratory</p>
                                </div>
                                <span class="badge badge-status bg-warning">Pending</span>
                            </div>
                        </div>
                        
                        <div class="meeting-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Term 2 Progress Review</h6>
                                    <p class="mb-0"><i class="fas fa-calendar me-2"></i> June 5, 2023 | 9:00 AM - 10:00 AM</p>
                                    <p class="mb-0"><i class="fas fa-map-marker-alt me-2"></i> Principal's Office</p>
                                </div>
                                <span class="badge badge-status bg-info">Confirmed</span>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="#" class="text-primary"><i class="fas fa-history me-1"></i> View Past Meetings</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Fees Overview -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Fees Overview</h5>
                        <button class="btn btn-sm btn-action">Make Payment</button>
                    </div>
                    <div class="card-body">
                        <div class="fee-item">
                            <div class="d-flex justify-content-between">
                                <strong>Student One - Term 2</strong>
                                <span class="badge badge-paid">Paid</span>
                            </div>
                            <p class="mb-1">Ksh 25,000 / Ksh 25,000</p>
                            <div class="progress-container">
                                <div class="progress-bar bg-success" style="width: 100%"></div>
                            </div>
                            <small class="text-muted">Paid on: Apr 15, 2023</small>
                        </div>
                        
                        <div class="fee-item">
                            <div class="d-flex justify-content-between">
                                <strong>Student Two - Term 2</strong>
                                <span class="badge badge-pending">Partial</span>
                            </div>
                            <p class="mb-1">Ksh 12,500 / Ksh 25,000</p>
                            <div class="progress-container">
                                <div class="progress-bar bg-warning" style="width: 50%"></div>
                            </div>
                            <small class="text-muted">Due: May 30, 2023</small>
                        </div>
                        
                        <div class="fee-item">
                            <div class="d-flex justify-content-between">
                                <strong>Student One - Term 3</strong>
                                <span class="badge badge-overdue">Unpaid</span>
                            </div>
                            <p class="mb-1">Ksh 0 / Ksh 25,000</p>
                            <div class="progress-container">
                                <div class="progress-bar bg-danger" style="width: 0%"></div>
                            </div>
                            <small class="text-muted">Due: Aug 15, 2023</small>
                        </div>
                        
                        <div class="mt-4">
                            <h6>Payment Methods</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light p-2"><i class="fas fa-mobile-alt me-1"></i> M-Pesa</span>
                                <span class="badge bg-light p-2"><i class="fas fa-university me-1"></i> Bank Transfer</span>
                                <span class="badge bg-light p-2"><i class="fas fa-credit-card me-1"></i> Credit Card</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- School Announcements -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">School Announcements</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0 me-3">
                                <i class="fas fa-calendar-day fa-2x text-primary"></i>
                            </div>
                            <div>
                                <h6>Sports Day</h6>
                                <p class="mb-0">Annual sports day on June 10th. All parents are invited!</p>
                                <small class="text-muted">Posted: May 5, 2023</small>
                            </div>
                        </div>
                        
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0 me-3">
                                <i class="fas fa-book fa-2x text-success"></i>
                            </div>
                            <div>
                                <h6>Mid-Term Break</h6>
                                <p class="mb-0">School will close for mid-term break from June 15-18.</p>
                                <small class="text-muted">Posted: May 3, 2023</small>
                            </div>
                        </div>
                        
                        <div class="d-flex">
                            <div class="flex-shrink-0 me-3">
                                <i class="fas fa-flask fa-2x text-info"></i>
                            </div>
                            <div>
                                <h6>Science Fair</h6>
                                <p class="mb-0">Annual science fair scheduled for June 25th in the school hall.</p>
                                <small class="text-muted">Posted: Apr 28, 2023</small>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="#" class="text-primary"><i class="fas fa-bullhorn me-1"></i> View All Announcements</a>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4 mb-3">
                                <a href="#" class="text-decoration-none">
                                    <div class="p-3 bg-light rounded-circle d-inline-block">
                                        <i class="fas fa-envelope fa-lg text-primary"></i>
                                    </div>
                                    <p class="mt-2 mb-0">Message</p>
                                </a>
                            </div>
                            <div class="col-4 mb-3">
                                <a href="#" class="text-decoration-none">
                                    <div class="p-3 bg-light rounded-circle d-inline-block">
                                        <i class="fas fa-file-download fa-lg text-success"></i>
                                    </div>
                                    <p class="mt-2 mb-0">Reports</p>
                                </a>
                            </div>
                            <div class="col-4 mb-3">
                                <a href="#" class="text-decoration-none">
                                    <div class="p-3 bg-light rounded-circle d-inline-block">
                                        <i class="fas fa-calendar-plus fa-lg text-info"></i>
                                    </div>
                                    <p class="mt-2 mb-0">Schedule</p>
                                </a>
                            </div>
                            <div class="col-4">
                                <a href="#" class="text-decoration-none">
                                    <div class="p-3 bg-light rounded-circle d-inline-block">
                                        <i class="fas fa-money-check-alt fa-lg text-warning"></i>
                                    </div>
                                    <p class="mt-2 mb-0">Pay Fees</p>
                                </a>
                            </div>
                            <div class="col-4">
                                <a href="#" class="text-decoration-none">
                                    <div class="p-3 bg-light rounded-circle d-inline-block">
                                        <i class="fas fa-user-edit fa-lg text-teal"></i>
                                    </div>
                                    <p class="mt-2 mb-0">Profile</p>
                                </a>
                            </div>
                            <div class="col-4">
                                <a href="#" class="text-decoration-none">
                                    <div class="p-3 bg-light rounded-circle d-inline-block">
                                        <i class="fas fa-question-circle fa-lg text-secondary"></i>
                                    </div>
                                    <p class="mt-2 mb-0">Help</p>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="mt-5 py-4 border-top">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">Mumbe Group of Schools &copy; 2023. All Rights Reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-decoration-none me-3">Privacy Policy</a>
                    <a href="#" class="text-decoration-none me-3">Terms of Service</a>
                    <a href="#" class="text-decoration-none">Contact Us</a>
                </div>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Grades Chart
            const gradesCtx = document.getElementById('gradesChart').getContext('2d');
            const gradesChart = new Chart(gradesCtx, {
                type: 'bar',
                data: {
                    labels: ['Math', 'English', 'Science', 'History', 'Swahili', 'CRE'],
                    datasets: [{
                        label: 'Term 2 Grades (%)',
                        data: [87, 78, 82, 75, 80, 85],
                        backgroundColor: [
                            'rgba(0, 128, 128, 0.7)',
                            'rgba(0, 128, 128, 0.7)',
                            'rgba(0, 128, 128, 0.7)',
                            'rgba(0, 128, 128, 0.5)',
                            'rgba(0, 128, 128, 0.7)',
                            'rgba(0, 128, 128, 0.7)'
                        ],
                        borderColor: [
                            'rgba(0, 128, 128, 1)',
                            'rgba(0, 128, 128, 1)',
                            'rgba(0, 128, 128, 1)',
                            'rgba(0, 128, 128, 1)',
                            'rgba(0, 128, 128, 1)',
                            'rgba(0, 128, 128, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                stepSize: 20
                            }
                        }
                    }
                }
            });
            
            // Child Selection
            const childItems = document.querySelectorAll('.child-item');
            childItems.forEach(item => {
                item.addEventListener('click', function() {
                    childItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    
                    // In a real app, this would update the dashboard with the selected child's data
                });
            });
        });
    </script>
</body>
</html>