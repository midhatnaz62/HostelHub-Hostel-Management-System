<?php
require_once 'auth.php';

// Only Admin and Staff can access Attendance Management
$userRole = strtolower(trim($_SESSION['role'] ?? ''));

if (!in_array($userRole, ['admin', 'staff'], true)) {

    if ($userRole === 'student') {
        header("Location: student_dashboard.php");
        exit;
    }

    header("Location: login.php");
    exit;
}

require_once 'db.php';

$message = "";


// ADD ATTENDANCE
if (isset($_POST['add_attendance'])) {

    try {

        $stmt = $conn->prepare("
            INSERT INTO Attendance (Student_ID, Attendance_Date, Status)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $_POST['student_id'],
            $_POST['attendance_date'],
            $_POST['status']
        ]);

        header("Location: attendance.php?added=1");
        exit;

    } catch (PDOException $e) {

        $message = "Unable to add attendance. Please check the details.";

    }
}


// UPDATE ATTENDANCE
if (isset($_POST['update_attendance'])) {

    try {

        $stmt = $conn->prepare("
            UPDATE Attendance
            SET Student_ID = ?, Attendance_Date = ?, Status = ?
            WHERE Attendance_ID = ?
        ");

        $stmt->execute([
            $_POST['student_id'],
            $_POST['attendance_date'],
            $_POST['status'],
            $_POST['attendance_id']
        ]);

        header("Location: attendance.php?updated=1");
        exit;

    } catch (PDOException $e) {

        $message = "Unable to update attendance.";

    }
}


// DELETE ATTENDANCE
if (isset($_POST['delete_attendance'])) {

    try {

        $stmt = $conn->prepare("
            DELETE FROM Attendance
            WHERE Attendance_ID = ?
        ");

        $stmt->execute([(int) $_POST['attendance_id']]);

        header("Location: attendance.php?deleted=1");
        exit;

    } catch (PDOException $e) {

        $message = "Unable to delete attendance.";

    }
}


// FETCH STUDENTS
$students = $conn->query("
    SELECT Student_ID, Name
    FROM Students
    ORDER BY Name
")->fetchAll(PDO::FETCH_ASSOC);


// FETCH ATTENDANCE FOR EDITING
$editAttendance = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {

    $stmt = $conn->prepare("
        SELECT *
        FROM Attendance
        WHERE Attendance_ID = ?
    ");

    $stmt->execute([(int) $_GET['edit']]);

    $editAttendance = $stmt->fetch(PDO::FETCH_ASSOC);

}


// FETCH ATTENDANCE RECORDS
$stmt = $conn->query("
    SELECT Attendance.*, Students.Name AS Student_Name
    FROM Attendance
    INNER JOIN Students
    ON Attendance.Student_ID = Students.Student_ID
    ORDER BY Attendance_Date DESC, Attendance_ID DESC
");

$attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ATTENDANCE STATISTICS
$stats = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN Status = 'Present' THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN Status = 'Absent' THEN 1 ELSE 0 END) AS absent,
        SUM(CASE WHEN Status = 'Late' THEN 1 ELSE 0 END) AS late
    FROM Attendance
")->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Attendance Management | HostelHub</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>

<body>

<?php include 'sidebar.php'; ?>

<main class="main-content">


    <!-- TOPBAR -->

    <header class="topbar">

        <div>

            <h1 class="page-title">Attendance Management</h1>

            <p class="page-subtitle">
                Manage and monitor student attendance.
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


    <!-- PAGE HEADING -->

    <div class="page-heading">

        <div>

            <h2>Attendance Records</h2>

            <p class="page-subtitle">
                Track and manage student attendance information.
            </p>

        </div>

    </div>


    <!-- SUCCESS MESSAGES -->

    <?php if (isset($_GET['added'])): ?>

        <div class="alert alert-success alert-dismissible fade show">

            Attendance added successfully!

            <button type="button"
                class="btn-close"
                data-bs-dismiss="alert"></button>

        </div>

    <?php endif; ?>


    <?php if (isset($_GET['updated'])): ?>

        <div class="alert alert-success alert-dismissible fade show">

            Attendance updated successfully!

            <button type="button"
                class="btn-close"
                data-bs-dismiss="alert"></button>

        </div>

    <?php endif; ?>


    <?php if (isset($_GET['deleted'])): ?>

        <div class="alert alert-success alert-dismissible fade show">

            Attendance deleted successfully!

            <button type="button"
                class="btn-close"
                data-bs-dismiss="alert"></button>

        </div>

    <?php endif; ?>


    <!-- ERROR MESSAGE -->

    <?php if ($message): ?>

        <div class="alert alert-danger alert-dismissible fade show">

            <?= htmlspecialchars($message) ?>

            <button type="button"
                class="btn-close"
                data-bs-dismiss="alert"></button>

        </div>

    <?php endif; ?>


    <!-- ATTENDANCE STATISTICS -->

    <div class="stats-grid">


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fas fa-clipboard-list"></i>
            </div>

            <div>

                <p class="stat-label">Total Records</p>

                <h3 class="stat-value">
                    <?= (int) $stats['total'] ?>
                </h3>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fas fa-user-check"></i>
            </div>

            <div>

                <p class="stat-label">Present</p>

                <h3 class="stat-value">
                    <?= (int) $stats['present'] ?>
                </h3>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fas fa-user-times"></i>
            </div>

            <div>

                <p class="stat-label">Absent</p>

                <h3 class="stat-value">
                    <?= (int) $stats['absent'] ?>
                </h3>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>

            <div>

                <p class="stat-label">Late</p>

                <h3 class="stat-value">
                    <?= (int) $stats['late'] ?>
                </h3>

            </div>

        </div>


    </div>


    <!-- ADD / EDIT ATTENDANCE FORM -->

    <section class="student-form">

        <h3>

            <?php if ($editAttendance): ?>

                <i class="fas fa-edit"></i>
                Edit Attendance

            <?php else: ?>

                <i class="fas fa-clipboard-check"></i>
                Mark Student Attendance

            <?php endif; ?>

        </h3>


        <form method="POST">


            <?php if ($editAttendance): ?>

                <input type="hidden"
                    name="attendance_id"
                    value="<?= (int) $editAttendance['Attendance_ID'] ?>">

            <?php endif; ?>


            <div class="row">


                <!-- STUDENT -->

                <div class="col-md-4">

                    <label>Select Student</label>

                    <select name="student_id"
                        class="form-select"
                        required>

                        <option value="">Choose Student</option>

                        <?php foreach ($students as $student): ?>

                            <option value="<?= (int) $student['Student_ID'] ?>"
                                <?= ($editAttendance && $editAttendance['Student_ID'] == $student['Student_ID']) ? 'selected' : '' ?>>

                                <?= htmlspecialchars($student['Name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- DATE -->

                <div class="col-md-4">

                    <label>Attendance Date</label>

                    <input type="date"
                        name="attendance_date"
                        class="form-control"
                        value="<?= htmlspecialchars($editAttendance['Attendance_Date'] ?? date('Y-m-d')) ?>"
                        required>

                </div>


                <!-- STATUS -->

                <div class="col-md-4">

                    <label>Attendance Status</label>

                    <select name="status"
                        class="form-select"
                        required>

                        <option value="Present"
                            <?= ($editAttendance['Status'] ?? '') == 'Present' ? 'selected' : '' ?>>

                            Present

                        </option>

                        <option value="Absent"
                            <?= ($editAttendance['Status'] ?? '') == 'Absent' ? 'selected' : '' ?>>

                            Absent

                        </option>

                        <option value="Late"
                            <?= ($editAttendance['Status'] ?? '') == 'Late' ? 'selected' : '' ?>>

                            Late

                        </option>

                    </select>

                </div>


            </div>


            <!-- FORM BUTTONS -->

            <?php if ($editAttendance): ?>

                <button type="submit"
                    name="update_attendance"
                    class="add-btn">

                    <i class="fas fa-save"></i>
                    Update Attendance

                </button>


                <a href="attendance.php" class="btn btn-secondary">
                    Cancel
                </a>


            <?php else: ?>

                <button type="submit"
                    name="add_attendance"
                    class="add-btn">

                    <i class="fas fa-plus"></i>
                    Mark Attendance

                </button>

            <?php endif; ?>


        </form>

    </section>


    <!-- ATTENDANCE RECORDS -->

    <section class="student-table">


        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

            <h3 class="mb-0">

                <i class="fas fa-clipboard-list"></i>
                Attendance Records

            </h3>


            <input type="text"
                id="searchInput"
                class="form-control"
                style="max-width: 320px;"
                placeholder="Search by student, date or status...">

        </div>


        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>

                        <th>Attendance ID</th>
                        <th>Student Name</th>
                        <th>Attendance Date</th>
                        <th>Status</th>
                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody id="attendanceTable">

                    <?php if (count($attendanceRecords) > 0): ?>

                        <?php foreach ($attendanceRecords as $record): ?>

                            <tr>


                                <td>
                                    #<?= (int) $record['Attendance_ID'] ?>
                                </td>


                                <td>

                                    <strong>
                                        <?= htmlspecialchars($record['Student_Name']) ?>
                                    </strong>

                                </td>


                                <td>
                                    <?= htmlspecialchars($record['Attendance_Date']) ?>
                                </td>


                                <!-- STATUS BADGE -->

                                <td>

                                    <?php if ($record['Status'] == 'Present'): ?>

                                        <span class="badge bg-success">
                                            Present
                                        </span>

                                    <?php elseif ($record['Status'] == 'Absent'): ?>

                                        <span class="badge bg-danger">
                                            Absent
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-warning text-dark">
                                            Late
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACTIONS -->

                                <td>


                                    <a href="attendance.php?edit=<?= (int) $record['Attendance_ID'] ?>"
                                        class="btn btn-primary btn-sm mb-1">

                                        <i class="fas fa-edit"></i>
                                        Edit

                                    </a>


                                    <form method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Are you sure you want to delete this attendance record?');">


                                        <input type="hidden"
                                            name="attendance_id"
                                            value="<?= (int) $record['Attendance_ID'] ?>">


                                        <button type="submit"
                                            name="delete_attendance"
                                            class="btn btn-danger btn-sm">

                                            <i class="fas fa-trash"></i>
                                            Delete

                                        </button>

                                    </form>


                                </td>

                            </tr>

                        <?php endforeach; ?>


                    <?php else: ?>

                        <tr>

                            <td colspan="5" class="text-center">
                                No attendance records found.
                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


    <!-- FOOTER -->

    <p class="page-subtitle text-center mt-4">

        © 2026 HostelHub Management System. All Rights Reserved.

    </p>


</main>

</div>


<!-- LIVE SEARCH -->

<script>

document.getElementById("searchInput").addEventListener("keyup", function () {

    let searchValue = this.value.toLowerCase();

    let rows = document.querySelectorAll("#attendanceTable tr");

    rows.forEach(function (row) {

        let rowText = row.innerText.toLowerCase();

        row.style.display = rowText.includes(searchValue) ? "" : "none";

    });

});

</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>