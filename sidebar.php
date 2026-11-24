<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$userRole = strtolower(trim($_SESSION['role'] ?? ''));
?>

<div class="dashboard">

    <aside class="sidebar">

        <div class="brand">
            HOSTEL<span>HUB</span>
        </div>

        <p class="sidebar-title">Main Menu</p>

        <?php if ($userRole === 'admin'): ?>

            <a href="index.php" class="nav-link <?= $currentPage == 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>

            <a href="students.php" class="nav-link <?= $currentPage == 'students.php' ? 'active' : '' ?>">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>

            <a href="hostels.php" class="nav-link <?= $currentPage == 'hostels.php' ? 'active' : '' ?>">
                <i class="fas fa-building"></i>
                <span>Hostels</span>
            </a>

            <a href="rooms.php" class="nav-link <?= $currentPage == 'rooms.php' ? 'active' : '' ?>">
                <i class="fas fa-door-open"></i>
                <span>Rooms</span>
            </a>

            <a href="room_allocations.php" class="nav-link <?= $currentPage == 'room_allocations.php' ? 'active' : '' ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Room Allocation</span>
            </a>

            <p class="sidebar-title">Management</p>

            <a href="payments.php" class="nav-link <?= $currentPage == 'payments.php' ? 'active' : '' ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>Payments</span>
            </a>

            <a href="complaints.php" class="nav-link <?= $currentPage == 'complaints.php' ? 'active' : '' ?>">
                <i class="fas fa-comment-dots"></i>
                <span>Complaints</span>
            </a>

            <a href="visitors.php" class="nav-link <?= $currentPage == 'visitors.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Visitors</span>
            </a>

            <a href="employees.php" class="nav-link <?= $currentPage == 'employees.php' ? 'active' : '' ?>">
                <i class="fas fa-user-tie"></i>
                <span>Employees</span>
            </a>

            <a href="attendance.php" class="nav-link <?= $currentPage == 'attendance.php' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-check"></i>
                <span>Attendance</span>
            </a>

        <?php elseif ($userRole === 'staff'): ?>

            <a href="staff_dashboard.php" class="nav-link <?= $currentPage == 'staff_dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>

            <p class="sidebar-title">Operations</p>

            <a href="students.php" class="nav-link <?= $currentPage == 'students.php' ? 'active' : '' ?>">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>

            <a href="rooms.php" class="nav-link <?= $currentPage == 'rooms.php' ? 'active' : '' ?>">
                <i class="fas fa-door-open"></i>
                <span>Rooms</span>
            </a>

            <a href="room_allocations.php" class="nav-link <?= $currentPage == 'room_allocations.php' ? 'active' : '' ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Room Allocation</span>
            </a>

            <a href="complaints.php" class="nav-link <?= $currentPage == 'complaints.php' ? 'active' : '' ?>">
                <i class="fas fa-comment-dots"></i>
                <span>Complaints</span>
            </a>

            <a href="visitors.php" class="nav-link <?= $currentPage == 'visitors.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Visitors</span>
            </a>

            <a href="attendance.php" class="nav-link <?= $currentPage == 'attendance.php' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-check"></i>
                <span>Attendance</span>
            </a>

        <?php elseif ($userRole === 'student'): ?>

            <a href="student_dashboard.php" class="nav-link <?= $currentPage == 'student_dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i>
                <span>My Dashboard</span>
            </a>

        <?php endif; ?>

        <p class="sidebar-title">Account</p>

        <a href="logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>

    </aside>