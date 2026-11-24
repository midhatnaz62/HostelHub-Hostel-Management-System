<?php
require_once 'auth.php';

// Only Admin and Staff can access Complaints Management
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
$editComplaint = null;


// ADD COMPLAINT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_complaint'])) {

    try {
        $stmt = $conn->prepare(
            "INSERT INTO Complaints
            (Student_ID, Complaint_Type, Description, Complaint_Date, Status, Resolution_Date)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $_POST['student_id'],
            $_POST['complaint_type'],
            $_POST['description'],
            $_POST['complaint_date'],
            $_POST['status'],
            !empty($_POST['resolution_date']) ? $_POST['resolution_date'] : null
        ]);

        $message = "Complaint added successfully!";

    } catch (PDOException $e) {
        $message = "Unable to add complaint. Please check the details.";
    }
}


// UPDATE COMPLAINT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_complaint'])) {

    try {
        $stmt = $conn->prepare(
            "UPDATE Complaints SET
            Student_ID = ?,
            Complaint_Type = ?,
            Description = ?,
            Complaint_Date = ?,
            Status = ?,
            Resolution_Date = ?
            WHERE Complaint_ID = ?"
        );

        $stmt->execute([
            $_POST['student_id'],
            $_POST['complaint_type'],
            $_POST['description'],
            $_POST['complaint_date'],
            $_POST['status'],
            !empty($_POST['resolution_date']) ? $_POST['resolution_date'] : null,
            $_POST['complaint_id']
        ]);

        header("Location: complaints.php?updated=1");
        exit;

    } catch (PDOException $e) {
        $message = "Unable to update complaint.";
    }
}


// DELETE COMPLAINT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {

    try {
        $stmt = $conn->prepare(
            "DELETE FROM Complaints WHERE Complaint_ID = ?"
        );

        $stmt->execute([(int) $_POST['delete_id']]);

        $message = "Complaint deleted successfully!";

    } catch (PDOException $e) {
        $message = "Unable to delete complaint.";
    }
}


// UPDATE MESSAGE
if (isset($_GET['updated'])) {
    $message = "Complaint updated successfully!";
}


// FETCH STUDENTS
$stmt = $conn->query(
    "SELECT Student_ID, Name FROM Students ORDER BY Name"
);

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);


// FETCH COMPLAINT FOR EDITING
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {

    $stmt = $conn->prepare(
        "SELECT * FROM Complaints WHERE Complaint_ID = ?"
    );

    $stmt->execute([(int) $_GET['edit']]);

    $editComplaint = $stmt->fetch(PDO::FETCH_ASSOC);
}


// FETCH COMPLAINT RECORDS
$stmt = $conn->query(
    "SELECT Complaints.*, Students.Name
     FROM Complaints
     INNER JOIN Students
     ON Complaints.Student_ID = Students.Student_ID
     ORDER BY Complaint_ID DESC"
);

$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);


// COMPLAINT STATISTICS
$stmt = $conn->query(
    "SELECT
        COUNT(*) AS Total_Complaints,
        SUM(CASE WHEN Status = 'Pending' THEN 1 ELSE 0 END) AS Pending_Complaints,
        SUM(CASE WHEN Status = 'In Progress' THEN 1 ELSE 0 END) AS In_Progress,
        SUM(CASE WHEN Status = 'Resolved' THEN 1 ELSE 0 END) AS Resolved_Complaints
     FROM Complaints"
);

$stats = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Complaints Management | HostelHub</title>

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

            <h1 class="page-title">Complaints Management</h1>

            <p class="page-subtitle">
                Manage and track student complaints.
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

            <h2>All Complaints</h2>

            <p class="page-subtitle">
                Manage student complaints and monitor their status.
            </p>

        </div>

    </div>


    <!-- MESSAGE -->

    <?php if ($message): ?>

        <div class="alert alert-info alert-dismissible fade show">

            <?= htmlspecialchars($message) ?>

            <button type="button"
                class="btn-close"
                data-bs-dismiss="alert"></button>

        </div>

    <?php endif; ?>


    <!-- COMPLAINT STATISTICS -->

    <div class="stats-grid">


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fas fa-comments"></i>
            </div>

            <div>

                <p class="stat-label">Total Complaints</p>

                <h3 class="stat-value">
                    <?= (int) $stats['Total_Complaints'] ?>
                </h3>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>

            <div>

                <p class="stat-label">Pending</p>

                <h3 class="stat-value">
                    <?= (int) $stats['Pending_Complaints'] ?>
                </h3>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fas fa-spinner"></i>
            </div>

            <div>

                <p class="stat-label">In Progress</p>

                <h3 class="stat-value">
                    <?= (int) $stats['In_Progress'] ?>
                </h3>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>

            <div>

                <p class="stat-label">Resolved</p>

                <h3 class="stat-value">
                    <?= (int) $stats['Resolved_Complaints'] ?>
                </h3>

            </div>

        </div>


    </div>


    <!-- ADD / EDIT COMPLAINT FORM -->

    <section class="student-form">

        <h3>

            <?php if ($editComplaint): ?>

                <i class="fas fa-edit"></i>
                Edit Complaint

            <?php else: ?>

                <i class="fas fa-plus-circle"></i>
                Add New Complaint

            <?php endif; ?>

        </h3>


        <form method="POST">


            <?php if ($editComplaint): ?>

                <input type="hidden"
                    name="complaint_id"
                    value="<?= (int) $editComplaint['Complaint_ID'] ?>">

            <?php endif; ?>


            <div class="row">


                <!-- STUDENT -->

                <div class="col-md-6">

                    <label>Select Student</label>

                    <select name="student_id"
                        class="form-select"
                        required>

                        <option value="">Choose Student</option>

                        <?php foreach ($students as $student): ?>

                            <option value="<?= (int) $student['Student_ID'] ?>"
                                <?= $editComplaint && $editComplaint['Student_ID'] == $student['Student_ID'] ? 'selected' : '' ?>>

                                <?= htmlspecialchars($student['Name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- COMPLAINT TYPE -->

                <div class="col-md-6">

                    <label>Complaint Type</label>

                    <?php
                    $types = ['Maintenance', 'Cleanliness', 'Food', 'Security', 'Other'];
                    ?>

                    <select name="complaint_type"
                        class="form-select"
                        required>

                        <?php foreach ($types as $type): ?>

                            <option value="<?= htmlspecialchars($type) ?>"
                                <?= $editComplaint && $editComplaint['Complaint_Type'] == $type ? 'selected' : '' ?>>

                                <?= htmlspecialchars($type) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- DESCRIPTION -->

                <div class="col-md-12">

                    <label>Complaint Description</label>

                    <textarea name="description"
                        class="form-control"
                        rows="3"
                        placeholder="Enter complaint details..."
                        required><?= $editComplaint ? htmlspecialchars($editComplaint['Description']) : '' ?></textarea>

                </div>


                <!-- COMPLAINT DATE -->

                <div class="col-md-6">

                    <label>Complaint Date</label>

                    <input type="date"
                        name="complaint_date"
                        class="form-control"
                        value="<?= $editComplaint ? htmlspecialchars($editComplaint['Complaint_Date']) : date('Y-m-d') ?>"
                        required>

                </div>


                <!-- STATUS -->

                <div class="col-md-6">

                    <label>Complaint Status</label>

                    <?php
                    $statuses = ['Pending', 'In Progress', 'Resolved'];
                    ?>

                    <select name="status"
                        class="form-select"
                        required>

                        <?php foreach ($statuses as $status): ?>

                            <option value="<?= htmlspecialchars($status) ?>"
                                <?= $editComplaint && $editComplaint['Status'] == $status ? 'selected' : '' ?>>

                                <?= htmlspecialchars($status) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- RESOLUTION DATE -->

                <div class="col-md-6">

                    <label>Resolution Date</label>

                    <input type="date"
                        name="resolution_date"
                        class="form-control"
                        value="<?= $editComplaint ? htmlspecialchars($editComplaint['Resolution_Date'] ?? '') : '' ?>">

                </div>


            </div>


            <!-- FORM BUTTONS -->

            <?php if ($editComplaint): ?>

                <button type="submit"
                    name="update_complaint"
                    class="add-btn">

                    <i class="fas fa-save"></i>
                    Update Complaint

                </button>


                <a href="complaints.php" class="btn btn-secondary">
                    Cancel
                </a>


            <?php else: ?>

                <button type="submit"
                    name="add_complaint"
                    class="add-btn">

                    <i class="fas fa-plus"></i>
                    Add Complaint

                </button>

            <?php endif; ?>


        </form>

    </section>


    <!-- COMPLAINT RECORDS -->

    <section class="student-table">


        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

            <h3 class="mb-0">

                <i class="fas fa-list"></i>
                Complaint Records

            </h3>


            <input type="text"
                id="complaintSearch"
                class="form-control"
                style="max-width: 320px;"
                placeholder="Search by student, complaint type or status...">

        </div>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>Complaint ID</th>
                        <th>Student Name</th>
                        <th>Complaint Type</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Resolution Date</th>
                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (count($complaints) > 0): ?>

                        <?php foreach ($complaints as $complaint): ?>

                            <tr>


                                <td>

                                    #<?= (int) $complaint['Complaint_ID'] ?>

                                </td>


                                <td>

                                    <strong>
                                        <?= htmlspecialchars($complaint['Name']) ?>
                                    </strong>

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


                                <!-- STATUS BADGE -->

                                <td>

                                    <?php if ($complaint['Status'] == 'Resolved'): ?>

                                        <span class="badge bg-success">
                                            Resolved
                                        </span>

                                    <?php elseif ($complaint['Status'] == 'In Progress'): ?>

                                        <span class="badge bg-primary">
                                            In Progress
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-warning text-dark">
                                            Pending
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars($complaint['Resolution_Date'] ?? 'N/A') ?>

                                </td>


                                <!-- ACTIONS -->

                                <td>


                                    <a href="complaints.php?edit=<?= (int) $complaint['Complaint_ID'] ?>"
                                        class="btn btn-primary btn-sm mb-1">

                                        <i class="fas fa-edit"></i>
                                        Edit

                                    </a>


                                    <form method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Are you sure you want to delete this complaint?');">


                                        <input type="hidden"
                                            name="delete_id"
                                            value="<?= (int) $complaint['Complaint_ID'] ?>">


                                        <button type="submit"
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

                            <td colspan="8" class="text-center">
                                No complaint records found.
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

const searchInput = document.getElementById('complaintSearch');

searchInput.addEventListener('keyup', function () {

    const searchValue = this.value.toLowerCase();

    const rows = document.querySelectorAll('.student-table tbody tr');

    rows.forEach(function (row) {

        const rowText = row.innerText.toLowerCase();

        row.style.display = rowText.includes(searchValue) ? '' : 'none';

    });

});

</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>