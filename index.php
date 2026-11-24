<?php
require_once 'auth.php';

// Only Admin can access this dashboard
$userRole = strtolower(trim($_SESSION['role'] ?? ''));

if ($userRole !== 'admin') {

    if ($userRole === 'staff') {
        header("Location: staff_dashboard.php");
        exit;
    }

    if ($userRole === 'student') {
        header("Location: student_dashboard.php");
        exit;
    }

    header("Location: login.php");
    exit;
}

require_once 'db.php';

// Dashboard Statistics
$totalStudents = $conn->query("SELECT COUNT(*) FROM Students")->fetchColumn();

$totalHostels = $conn->query("SELECT COUNT(*) FROM Hostels")->fetchColumn();

$totalRooms = $conn->query("SELECT COUNT(*) FROM Rooms")->fetchColumn();

$availableBeds = $conn->query(
    "SELECT COALESCE(SUM(Capacity - Occupied_Beds), 0) FROM Rooms"
)->fetchColumn();

$totalPayments = $conn->query(
    "SELECT COALESCE(SUM(Amount), 0) FROM Payments WHERE Status = 'Paid'"
)->fetchColumn();

$pendingPayments = $conn->query(
    "SELECT COUNT(*) FROM Payments WHERE Status = 'Pending'"
)->fetchColumn();

$totalComplaints = $conn->query(
    "SELECT COUNT(*) FROM Complaints WHERE Status != 'Resolved'"
)->fetchColumn();

// Recent Complaints
$complaints = $conn->query(
    "SELECT Complaints.*, Students.Name
     FROM Complaints
     JOIN Students ON Complaints.Student_ID = Students.Student_ID
     ORDER BY Complaint_ID DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

// Recent Payments
$payments = $conn->query(
    "SELECT Payments.*, Students.Name
     FROM Payments
     JOIN Students ON Payments.Student_ID = Students.Student_ID
     ORDER BY Payment_ID DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Hostel Management System | Dashboard</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet">
</head>

<body>

<?php include 'sidebar.php'; ?>

<!-- Main Content -->
<main class="main-content">

    <!-- Topbar -->
    <header class="topbar">

        <div>
            <h1 class="page-title">Dashboard Overview</h1>
            <p class="page-subtitle">
                Welcome back! Here's what's happening in your hostel.
            </p>
        </div>

        <div class="admin-profile">

            <div>
                <strong>Administrator</strong>
                <p class="page-subtitle">System Admin</p>
            </div>

            <div class="admin-avatar">A</div>

        </div>

    </header>

    <!-- Statistics Cards -->
    <section class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-graduate"></i>
            </div>

            <p class="stat-label">Total Students</p>
            <h2 class="stat-value">
                <?= number_format($totalStudents) ?>
            </h2>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-building"></i>
            </div>

            <p class="stat-label">Total Hostels</p>
            <h2 class="stat-value">
                <?= number_format($totalHostels) ?>
            </h2>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-door-open"></i>
            </div>

            <p class="stat-label">Total Rooms</p>
            <h2 class="stat-value">
                <?= number_format($totalRooms) ?>
            </h2>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-bed"></i>
            </div>

            <p class="stat-label">Available Beds</p>
            <h2 class="stat-value">
                <?= number_format($availableBeds) ?>
            </h2>
        </div>

    </section>

    <!-- Financial Statistics -->
    <section class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-wallet"></i>
            </div>

            <p class="stat-label">Total Fee Collected</p>
            <h2 class="stat-value">
                Rs. <?= number_format($totalPayments) ?>
            </h2>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>

            <p class="stat-label">Pending Payments</p>
            <h2 class="stat-value">
                <?= number_format($pendingPayments) ?>
            </h2>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>

            <p class="stat-label">Open Complaints</p>
            <h2 class="stat-value">
                <?= number_format($totalComplaints) ?>
            </h2>
        </div>

    </section>

    <!-- Recent Complaints -->
    <section class="panel">

        <div class="panel-header">
            <h3 class="panel-title">Recent Complaints</h3>

            <span class="badge bg-warning text-dark">
                <?= count($complaints) ?> Records
            </span>
        </div>

        <div class="table-container">

            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Complaint Type</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($complaints as $complaint): ?>

                    <tr>
                        <td>
                            <?= htmlspecialchars($complaint['Name']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($complaint['Complaint_Type']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($complaint['Description']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($complaint['Complaint_Date']) ?>
                        </td>

                        <td>
                            <?php
                            $status = $complaint['Status'];

                            $badge = $status === 'Resolved'
                                ? 'bg-success'
                                : ($status === 'Pending'
                                    ? 'bg-warning text-dark'
                                    : 'bg-primary');
                            ?>

                            <span class="badge <?= $badge ?>">
                                <?= htmlspecialchars($status) ?>
                            </span>
                        </td>
                    </tr>

                <?php endforeach; ?>

                </tbody>
            </table>

        </div>

    </section>

    <!-- Recent Payments -->
    <section class="panel">

        <div class="panel-header">
            <h3 class="panel-title">Recent Payments</h3>

            <span class="badge bg-success">
                Payment Records
            </span>
        </div>

        <div class="table-container">

            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Amount</th>
                        <th>Payment Date</th>
                        <th>Method</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($payments as $payment): ?>

                    <tr>
                        <td>
                            <?= htmlspecialchars($payment['Name']) ?>
                        </td>

                        <td>
                            Rs. <?= number_format($payment['Amount']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($payment['Payment_Date']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($payment['Payment_Method']) ?>
                        </td>

                        <td>
                            <?php
                            $paymentBadge = $payment['Status'] === 'Paid'
                                ? 'bg-danger'
                                : 'bg-warning text-dark';
                            ?>

                            <span class="badge <?= $paymentBadge ?>">
                                <?= htmlspecialchars($payment['Status']) ?>
                            </span>
                        </td>
                    </tr>

                <?php endforeach; ?>

                </tbody>
            </table>

        </div>

    </section>

    <p class="page-subtitle text-center mt-4">
        © 2026 HostelHub Management System. All Rights Reserved.
    </p>

</main>

</div>

</body>
</html>