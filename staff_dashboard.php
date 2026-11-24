<?php
require_once 'auth.php';
require_once 'db.php';

// Only Staff can access this dashboard
if (strtolower($_SESSION['role']) !== 'staff') {
    header("Location: index.php");
    exit;
}

// Dashboard Statistics
$totalStudents = $conn->query(
    "SELECT COUNT(*) FROM Students"
)->fetchColumn();

$totalRooms = $conn->query(
    "SELECT COUNT(*) FROM Rooms"
)->fetchColumn();

$activeAllocations = $conn->query(
    "SELECT COUNT(*) FROM Room_Allocations WHERE Status = 'Active'"
)->fetchColumn();

$pendingComplaints = $conn->query(
    "SELECT COUNT(*) FROM Complaints WHERE Status = 'Pending'"
)->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Staff Dashboard | HostelHub</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<?php require_once 'sidebar.php'; ?>

<main class="main-content">

    <header class="topbar">

        <div>
            <h1 class="page-title">Staff Dashboard</h1>
            <p class="page-subtitle">Welcome to HostelHub Staff Portal</p>
        </div>

        <div class="admin-profile">

            <div>
                <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                <p class="page-subtitle">Staff Member</p>
            </div>

            <div class="admin-avatar">S</div>

        </div>

    </header>


    <section class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-graduate"></i>
            </div>

            <div>
                <p>Total Students</p>
                <h2><?= $totalStudents ?></h2>
            </div>
        </div>


        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-door-open"></i>
            </div>

            <div>
                <p>Total Rooms</p>
                <h2><?= $totalRooms ?></h2>
            </div>
        </div>


        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>

            <div>
                <p>Active Allocations</p>
                <h2><?= $activeAllocations ?></h2>
            </div>
        </div>


        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-comment-dots"></i>
            </div>

            <div>
                <p>Pending Complaints</p>
                <h2><?= $pendingComplaints ?></h2>
            </div>
        </div>

    </section>


    <div class="panel mt-4">

        <h4>Staff Responsibilities</h4>

        <p class="text-muted">
            Manage daily hostel operations through the available modules.
        </p>

        <ul>
            <li>Manage student records.</li>
            <li>Manage room information and allocations.</li>
            <li>Handle student complaints.</li>
            <li>Manage visitor records.</li>
            <li>Maintain student attendance.</li>
        </ul>

    </div>

</main>

</div>

</body>
</html>