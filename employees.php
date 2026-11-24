<?php
require_once 'auth.php';

// Only Admin can access Employees Management
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

$message = "";


// ADD EMPLOYEE
if (isset($_POST['add_employee'])) {

    try {

        $stmt = $conn->prepare("
            INSERT INTO Employees
            (Name, Job_Role, Phone, Email, Hire_Date, Salary, Status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_POST['name'],
            $_POST['job_role'],
            $_POST['phone'],
            $_POST['email'],
            $_POST['hire_date'],
            $_POST['salary'],
            $_POST['status']
        ]);

        header("Location: employees.php?added=1");
        exit;

    } catch (PDOException $e) {

        $message = "Error: Email may already exist.";

    }
}


// UPDATE EMPLOYEE
if (isset($_POST['update_employee'])) {

    try {

        $stmt = $conn->prepare("
            UPDATE Employees SET
            Name = ?,
            Job_Role = ?,
            Phone = ?,
            Email = ?,
            Hire_Date = ?,
            Salary = ?,
            Status = ?
            WHERE Employee_ID = ?
        ");

        $stmt->execute([
            $_POST['name'],
            $_POST['job_role'],
            $_POST['phone'],
            $_POST['email'],
            $_POST['hire_date'],
            $_POST['salary'],
            $_POST['status'],
            $_POST['employee_id']
        ]);

        header("Location: employees.php?updated=1");
        exit;

    } catch (PDOException $e) {

        $message = "Error: Email may already exist.";

    }
}


// DELETE EMPLOYEE
if (isset($_POST['delete_employee'])) {

    try {

        $stmt = $conn->prepare("
            DELETE FROM Employees WHERE Employee_ID = ?
        ");

        $stmt->execute([(int) $_POST['employee_id']]);

        header("Location: employees.php?deleted=1");
        exit;

    } catch (PDOException $e) {

        $message = "Unable to delete this employee.";

    }
}


// FETCH EMPLOYEE FOR EDITING
$editEmployee = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {

    $stmt = $conn->prepare("
        SELECT * FROM Employees WHERE Employee_ID = ?
    ");

    $stmt->execute([(int) $_GET['edit']]);

    $editEmployee = $stmt->fetch(PDO::FETCH_ASSOC);

}


// FETCH EMPLOYEES
$stmt = $conn->query("
    SELECT * FROM Employees
    ORDER BY Employee_ID DESC
");

$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);


// EMPLOYEE STATISTICS
$stats = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN Status = 'Active' THEN 1 ELSE 0 END) AS active,
        SUM(CASE WHEN Status != 'Active' THEN 1 ELSE 0 END) AS inactive,
        COALESCE(SUM(Salary), 0) AS total_salary
    FROM Employees
")->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Employees Management | HostelHub</title>

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

            <h1 class="page-title">Employees Management</h1>

            <p class="page-subtitle">
                Manage hostel employees and their information.
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

            <h2>Employees</h2>

            <p class="page-subtitle">
                Manage employee details, roles and salary records.
            </p>

        </div>

    </div>


    <!-- SUCCESS MESSAGES -->

    <?php if (isset($_GET['added'])): ?>

        <div class="alert alert-success alert-dismissible fade show">

            Employee added successfully!

            <button type="button" class="btn-close"
                data-bs-dismiss="alert"></button>

        </div>

    <?php endif; ?>


    <?php if (isset($_GET['updated'])): ?>

        <div class="alert alert-success alert-dismissible fade show">

            Employee updated successfully!

            <button type="button" class="btn-close"
                data-bs-dismiss="alert"></button>

        </div>

    <?php endif; ?>


    <?php if (isset($_GET['deleted'])): ?>

        <div class="alert alert-success alert-dismissible fade show">

            Employee deleted successfully!

            <button type="button" class="btn-close"
                data-bs-dismiss="alert"></button>

        </div>

    <?php endif; ?>


    <!-- ERROR MESSAGE -->

    <?php if ($message): ?>

        <div class="alert alert-danger alert-dismissible fade show">

            <?= htmlspecialchars($message) ?>

            <button type="button" class="btn-close"
                data-bs-dismiss="alert"></button>

        </div>

    <?php endif; ?>


    <!-- EMPLOYEE STATISTICS -->

    <div class="stats-grid">


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>

            <div>

                <p class="stat-label">Total Employees</p>

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

                <p class="stat-label">Active Employees</p>

                <h3 class="stat-value">
                    <?= (int) $stats['active'] ?>
                </h3>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fas fa-user-slash"></i>
            </div>

            <div>

                <p class="stat-label">Inactive Employees</p>

                <h3 class="stat-value">
                    <?= (int) $stats['inactive'] ?>
                </h3>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>

            <div>

                <p class="stat-label">Total Monthly Salary</p>

                <h3 class="stat-value">
                    Rs. <?= number_format((float) $stats['total_salary']) ?>
                </h3>

            </div>

        </div>


    </div>


    <!-- ADD / EDIT EMPLOYEE FORM -->

    <section class="student-form">

        <h3>

            <?php if ($editEmployee): ?>

                <i class="fas fa-edit"></i>
                Edit Employee

            <?php else: ?>

                <i class="fas fa-user-plus"></i>
                Add New Employee

            <?php endif; ?>

        </h3>


        <form method="POST">


            <?php if ($editEmployee): ?>

                <input type="hidden"
                    name="employee_id"
                    value="<?= (int) $editEmployee['Employee_ID'] ?>">

            <?php endif; ?>


            <div class="row">


                <!-- EMPLOYEE NAME -->

                <div class="col-md-6">

                    <label>Employee Name</label>

                    <input type="text"
                        name="name"
                        class="form-control"
                        placeholder="Enter employee name"
                        value="<?= htmlspecialchars($editEmployee['Name'] ?? '') ?>"
                        required>

                </div>


                <!-- JOB ROLE -->

                <div class="col-md-6">

                    <label>Job Role</label>

                    <input type="text"
                        name="job_role"
                        class="form-control"
                        placeholder="e.g. Warden, Security Guard"
                        value="<?= htmlspecialchars($editEmployee['Job_Role'] ?? '') ?>"
                        required>

                </div>


                <!-- PHONE -->

                <div class="col-md-6">

                    <label>Phone Number</label>

                    <input type="text"
                        name="phone"
                        class="form-control"
                        placeholder="Enter phone number"
                        value="<?= htmlspecialchars($editEmployee['Phone'] ?? '') ?>"
                        required>

                </div>


                <!-- EMAIL -->

                <div class="col-md-6">

                    <label>Email Address</label>

                    <input type="email"
                        name="email"
                        class="form-control"
                        placeholder="Enter email address"
                        value="<?= htmlspecialchars($editEmployee['Email'] ?? '') ?>"
                        required>

                </div>


                <!-- HIRE DATE -->

                <div class="col-md-6">

                    <label>Hire Date</label>

                    <input type="date"
                        name="hire_date"
                        class="form-control"
                        value="<?= htmlspecialchars($editEmployee['Hire_Date'] ?? '') ?>"
                        required>

                </div>


                <!-- SALARY -->

                <div class="col-md-6">

                    <label>Monthly Salary (Rs.)</label>

                    <input type="number"
                        name="salary"
                        class="form-control"
                        min="0"
                        step="0.01"
                        placeholder="Enter monthly salary"
                        value="<?= htmlspecialchars($editEmployee['Salary'] ?? '') ?>"
                        required>

                </div>


                <!-- STATUS -->

                <div class="col-md-6">

                    <label>Employee Status</label>

                    <select name="status" class="form-select" required>

                        <option value="Active"
                            <?= ($editEmployee['Status'] ?? 'Active') == 'Active' ? 'selected' : '' ?>>

                            Active

                        </option>

                        <option value="Inactive"
                            <?= ($editEmployee['Status'] ?? '') == 'Inactive' ? 'selected' : '' ?>>

                            Inactive

                        </option>

                    </select>

                </div>


            </div>


            <!-- FORM BUTTONS -->

            <?php if ($editEmployee): ?>

                <button type="submit"
                    name="update_employee"
                    class="add-btn">

                    <i class="fas fa-save"></i>
                    Update Employee

                </button>


                <a href="employees.php" class="btn btn-secondary">
                    Cancel
                </a>


            <?php else: ?>

                <button type="submit"
                    name="add_employee"
                    class="add-btn">

                    <i class="fas fa-plus"></i>
                    Add Employee

                </button>

            <?php endif; ?>


        </form>

    </section>


    <!-- EMPLOYEE RECORDS -->

    <section class="student-table">


        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

            <h3 class="mb-0">

                <i class="fas fa-users"></i>
                Employee Records

            </h3>


            <input type="text"
                id="searchInput"
                class="form-control"
                style="max-width: 320px;"
                placeholder="Search by name, role, phone or email...">

        </div>


        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>

                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Job Role</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Hire Date</th>
                        <th>Salary</th>
                        <th>Status</th>
                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody id="employeeTable">

                    <?php if (count($employees) > 0): ?>

                        <?php foreach ($employees as $employee): ?>

                            <tr>


                                <td>
                                    #<?= (int) $employee['Employee_ID'] ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($employee['Name']) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($employee['Job_Role']) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($employee['Phone']) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($employee['Email']) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($employee['Hire_Date']) ?>
                                </td>


                                <td>
                                    Rs. <?= number_format((float) $employee['Salary']) ?>
                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php if ($employee['Status'] == 'Active'): ?>

                                        <span class="badge bg-success">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACTIONS -->

                                <td>


                                    <a href="employees.php?edit=<?= (int) $employee['Employee_ID'] ?>"
                                        class="btn btn-primary btn-sm mb-1">

                                        <i class="fas fa-edit"></i>
                                        Edit

                                    </a>


                                    <form method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Are you sure you want to delete this employee?');">


                                        <input type="hidden"
                                            name="employee_id"
                                            value="<?= (int) $employee['Employee_ID'] ?>">


                                        <button type="submit"
                                            name="delete_employee"
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
                                No employees found.
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

    let rows = document.querySelectorAll("#employeeTable tr");

    rows.forEach(function (row) {

        let rowText = row.innerText.toLowerCase();

        row.style.display = rowText.includes(searchValue) ? "" : "none";

    });

});

</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
