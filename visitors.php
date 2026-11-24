<?php
require_once 'auth.php';

// Only Admin and Staff can access Visitors Management
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
$editVisitor = null;


// ADD VISITOR
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_visitor'])) {

    try {
        $stmt = $conn->prepare(
            "INSERT INTO Visitors
            (Student_ID, Visitor_Name, Relation, Phone, Visit_Date, Entry_Time, Exit_Time)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $_POST['student_id'],
            $_POST['visitor_name'],
            $_POST['relation'],
            $_POST['phone'],
            $_POST['visit_date'],
            $_POST['entry_time'],
            !empty($_POST['exit_time']) ? $_POST['exit_time'] : null
        ]);

        $message = "Visitor added successfully!";

    } catch (PDOException $e) {
        $message = "Unable to add visitor. Please check the details.";
    }
}


// UPDATE VISITOR
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_visitor'])) {

    try {
        $stmt = $conn->prepare(
            "UPDATE Visitors SET
            Student_ID = ?,
            Visitor_Name = ?,
            Relation = ?,
            Phone = ?,
            Visit_Date = ?,
            Entry_Time = ?,
            Exit_Time = ?
            WHERE Visitor_ID = ?"
        );

        $stmt->execute([
            $_POST['student_id'],
            $_POST['visitor_name'],
            $_POST['relation'],
            $_POST['phone'],
            $_POST['visit_date'],
            $_POST['entry_time'],
            !empty($_POST['exit_time']) ? $_POST['exit_time'] : null,
            $_POST['visitor_id']
        ]);

        header("Location: visitors.php?updated=1");
        exit;

    } catch (PDOException $e) {
        $message = "Unable to update visitor.";
    }
}


// DELETE VISITOR
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {

    try {
        $stmt = $conn->prepare(
            "DELETE FROM Visitors WHERE Visitor_ID = ?"
        );

        $stmt->execute([(int) $_POST['delete_id']]);

        $message = "Visitor deleted successfully!";

    } catch (PDOException $e) {
        $message = "Unable to delete visitor.";
    }
}


// UPDATE MESSAGE
if (isset($_GET['updated'])) {
    $message = "Visitor updated successfully!";
}


// FETCH STUDENTS
$stmt = $conn->query(
    "SELECT Student_ID, Name FROM Students ORDER BY Name"
);

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);


// FETCH VISITOR FOR EDITING
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {

    $stmt = $conn->prepare(
        "SELECT * FROM Visitors WHERE Visitor_ID = ?"
    );

    $stmt->execute([(int) $_GET['edit']]);

    $editVisitor = $stmt->fetch(PDO::FETCH_ASSOC);
}


// FETCH VISITOR RECORDS
$stmt = $conn->query(
    "SELECT Visitors.*, Students.Name AS Student_Name
     FROM Visitors
     INNER JOIN Students
     ON Visitors.Student_ID = Students.Student_ID
     ORDER BY Visitor_ID DESC"
);

$visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);


// VISITOR STATISTICS
$stmt = $conn->query(
    "SELECT
        COUNT(*) AS Total_Visitors,
        COUNT(DISTINCT Visit_Date) AS Visit_Days,
        SUM(CASE WHEN Exit_Time IS NULL THEN 1 ELSE 0 END) AS Currently_Inside
     FROM Visitors"
);

$stats = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Visitors Management | HostelHub</title>

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

            <h1 class="page-title">Visitors Management</h1>

            <p class="page-subtitle">
                Manage hostel visitor records and entry details.
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

            <h2>Visitors</h2>

            <p class="page-subtitle">
                Manage visitor information, entry and exit records.
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


    <!-- VISITOR STATISTICS -->

    <div class="stats-grid">


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>

            <div>

                <p class="stat-label">Total Visitors</p>

                <h3 class="stat-value">
                    <?= (int) $stats['Total_Visitors'] ?>
                </h3>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fas fa-calendar-days"></i>
            </div>

            <div>

                <p class="stat-label">Visit Days</p>

                <h3 class="stat-value">
                    <?= (int) $stats['Visit_Days'] ?>
                </h3>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fas fa-door-open"></i>
            </div>

            <div>

                <p class="stat-label">Currently Inside</p>

                <h3 class="stat-value">
                    <?= (int) $stats['Currently_Inside'] ?>
                </h3>

            </div>

        </div>


    </div>


    <!-- ADD / EDIT VISITOR FORM -->

    <section class="student-form">

        <h3>

            <?php if ($editVisitor): ?>

                <i class="fas fa-edit"></i>
                Edit Visitor

            <?php else: ?>

                <i class="fas fa-user-plus"></i>
                Add New Visitor

            <?php endif; ?>

        </h3>


        <form method="POST">


            <?php if ($editVisitor): ?>

                <input type="hidden"
                    name="visitor_id"
                    value="<?= (int) $editVisitor['Visitor_ID'] ?>">

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
                                <?= $editVisitor && $editVisitor['Student_ID'] == $student['Student_ID'] ? 'selected' : '' ?>>

                                <?= htmlspecialchars($student['Name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- VISITOR NAME -->

                <div class="col-md-6">

                    <label>Visitor Name</label>

                    <input type="text"
                        name="visitor_name"
                        class="form-control"
                        placeholder="Enter visitor name"
                        value="<?= $editVisitor ? htmlspecialchars($editVisitor['Visitor_Name']) : '' ?>"
                        required>

                </div>


                <!-- RELATION -->

                <div class="col-md-6">

                    <label>Relation with Student</label>

                    <input type="text"
                        name="relation"
                        class="form-control"
                        placeholder="e.g. Father, Mother, Friend"
                        value="<?= $editVisitor ? htmlspecialchars($editVisitor['Relation']) : '' ?>"
                        required>

                </div>


                <!-- PHONE -->

                <div class="col-md-6">

                    <label>Contact Number</label>

                    <input type="tel"
                        name="phone"
                        class="form-control"
                        placeholder="Enter phone number"
                        value="<?= $editVisitor ? htmlspecialchars($editVisitor['Phone']) : '' ?>"
                        required>

                </div>


                <!-- VISIT DATE -->

                <div class="col-md-4">

                    <label>Visit Date</label>

                    <input type="date"
                        name="visit_date"
                        class="form-control"
                        value="<?= $editVisitor ? htmlspecialchars($editVisitor['Visit_Date']) : date('Y-m-d') ?>"
                        required>

                </div>


                <!-- ENTRY TIME -->

                <div class="col-md-4">

                    <label>Entry Time</label>

                    <input type="time"
                        name="entry_time"
                        class="form-control"
                        value="<?= $editVisitor ? htmlspecialchars(substr($editVisitor['Entry_Time'], 0, 5)) : '' ?>"
                        required>

                </div>


                <!-- EXIT TIME -->

                <div class="col-md-4">

                    <label>Exit Time</label>

                    <input type="time"
                        name="exit_time"
                        class="form-control"
                        value="<?= $editVisitor && !empty($editVisitor['Exit_Time']) ? htmlspecialchars(substr($editVisitor['Exit_Time'], 0, 5)) : '' ?>">

                </div>


            </div>


            <!-- FORM BUTTONS -->

            <?php if ($editVisitor): ?>

                <button type="submit"
                    name="update_visitor"
                    class="add-btn">

                    <i class="fas fa-save"></i>
                    Update Visitor

                </button>


                <a href="visitors.php" class="btn btn-secondary">
                    Cancel
                </a>


            <?php else: ?>

                <button type="submit"
                    name="add_visitor"
                    class="add-btn">

                    <i class="fas fa-plus"></i>
                    Add Visitor

                </button>

            <?php endif; ?>


        </form>

    </section>


    <!-- VISITOR RECORDS -->

    <section class="student-table">


        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

            <h3 class="mb-0">

                <i class="fas fa-users"></i>
                Visitor Records

            </h3>


            <input type="text"
                id="visitorSearch"
                class="form-control"
                style="max-width: 320px;"
                placeholder="Search by visitor, student, relation or phone...">

        </div>


        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>

                        <th>Visitor ID</th>
                        <th>Visitor Name</th>
                        <th>Student Name</th>
                        <th>Relation</th>
                        <th>Phone</th>
                        <th>Visit Date</th>
                        <th>Entry Time</th>
                        <th>Exit Time</th>
                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (count($visitors) > 0): ?>

                        <?php foreach ($visitors as $visitor): ?>

                            <tr>


                                <td>
                                    #<?= (int) $visitor['Visitor_ID'] ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($visitor['Visitor_Name']) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($visitor['Student_Name']) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($visitor['Relation']) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($visitor['Phone']) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($visitor['Visit_Date']) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($visitor['Entry_Time']) ?>
                                </td>


                                <td>

                                    <?php if (empty($visitor['Exit_Time'])): ?>

                                        <span class="badge bg-warning text-dark">
                                            Still Inside
                                        </span>

                                    <?php else: ?>

                                        <?= htmlspecialchars($visitor['Exit_Time']) ?>

                                    <?php endif; ?>

                                </td>


                                <!-- ACTIONS -->

                                <td>


                                    <a href="visitors.php?edit=<?= (int) $visitor['Visitor_ID'] ?>"
                                        class="btn btn-primary btn-sm mb-1">

                                        <i class="fas fa-edit"></i>
                                        Edit

                                    </a>


                                    <form method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Are you sure you want to delete this visitor record?');">


                                        <input type="hidden"
                                            name="delete_id"
                                            value="<?= (int) $visitor['Visitor_ID'] ?>">


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

                            <td colspan="9" class="text-center">
                                No visitor records found.
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

const searchInput = document.getElementById('visitorSearch');

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