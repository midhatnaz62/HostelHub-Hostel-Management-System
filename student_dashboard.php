<?php
require_once 'auth.php';
require_once 'db.php';

// Only Students can access this dashboard
if (strtolower($_SESSION['role']) !== 'student') {
    header("Location: index.php");
    exit;
}

// Get logged-in student's information
$stmt = $conn->prepare("
    SELECT s.*
    FROM Users u
    INNER JOIN Students s ON u.Student_ID = s.Student_ID
    WHERE u.User_ID = ?
");

$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student profile not found!");
}

$studentID = $student['Student_ID'];

// Student Statistics
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM Attendance
    WHERE Student_ID = ? AND Status = 'Present'
");
$stmt->execute([$studentID]);
$presentDays = $stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT COUNT(*) FROM Payments
    WHERE Student_ID = ? AND Status = 'Paid'
");
$stmt->execute([$studentID]);
$paidPayments = $stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT COUNT(*) FROM Complaints
    WHERE Student_ID = ?
");
$stmt->execute([$studentID]);
$totalComplaints = $stmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Dashboard | HostelHub</title>

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
            <h1 class="page-title">Student Dashboard</h1>
            <p class="page-subtitle">Welcome to your HostelHub Portal</p>
        </div>

        <div class="admin-profile">

            <div>
                <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                <p class="page-subtitle">Student</p>
            </div>

            <div class="admin-avatar">S</div>

        </div>

    </header>


    <div class="panel mb-4">

        <h4>My Profile</h4>

        <p><strong>Name:</strong>
            <?= htmlspecialchars($student['Name']) ?>
        </p>

        <p><strong>Student ID:</strong>
            <?= htmlspecialchars($student['Student_ID']) ?>
        </p>

        <p><strong>Department:</strong>
            <?= htmlspecialchars($student['Department']) ?>
        </p>

        <p><strong>Semester:</strong>
            <?= htmlspecialchars($student['Semester']) ?>
        </p>

        <p><strong>Email:</strong>
            <?= htmlspecialchars($student['Email']) ?>
        </p>

    </div>


    <section class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clipboard-check"></i>
            </div>

            <div>
                <p>Present Days</p>
                <h2><?= $presentDays ?></h2>
            </div>
        </div>


        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>

            <div>
                <p>Paid Payments</p>
                <h2><?= $paidPayments ?></h2>
            </div>
        </div>


        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-comment-dots"></i>
            </div>

            <div>
                <p>My Complaints</p>
                <h2><?= $totalComplaints ?></h2>
            </div>
        </div>

    </section>


    <div class="panel mt-4">

        <h4>Welcome, <?= htmlspecialchars($student['Name']) ?>!</h4>

        <p class="text-muted mb-0">
            You can use your Student Portal to view your hostel information
            and stay updated with your academic and hostel records.
        </p>

    </div>

</main>

</div>

</body>
</html>