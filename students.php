<?php
require_once 'auth.php';

// Only Admin and Staff can access Students Management
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
$editStudent = null;


// DELETE STUDENT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {

    $deleteID = (int) $_POST['delete_id'];

    try {
        $stmt = $conn->prepare(
            "DELETE FROM Students WHERE Student_ID = ?"
        );

        $stmt->execute([$deleteID]);

        $message = "Student deleted successfully!";

    } catch (PDOException $e) {
        $message = "Unable to delete student. This student may have related records.";
    }
}


// UPDATE STUDENT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_student'])) {

    $studentID = (int) $_POST['student_id'];

    try {

        $sql = "UPDATE Students SET
                Name = ?,
                Father_Name = ?,
                Gender = ?,
                DOB = ?,
                CNIC = ?,
                Phone = ?,
                Email = ?,
                Address = ?,
                Department = ?,
                Semester = ?,
                Admission_Date = ?
                WHERE Student_ID = ?";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            trim($_POST['name']),
            trim($_POST['father_name']),
            $_POST['gender'],
            $_POST['dob'],
            trim($_POST['cnic']),
            trim($_POST['phone']),
            trim($_POST['email']),
            trim($_POST['address']),
            trim($_POST['department']),
            $_POST['semester'],
            $_POST['admission_date'],
            $studentID
        ]);

        $message = "Student updated successfully!";

    } catch (PDOException $e) {
        $message = "Unable to update student. CNIC or Email may already exist.";
    }
}


// ADD STUDENT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_student'])) {

    try {

        $sql = "INSERT INTO Students
        (Name, Father_Name, Gender, DOB, CNIC, Phone, Email,
        Address, Department, Semester, Admission_Date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            trim($_POST['name']),
            trim($_POST['father_name']),
            $_POST['gender'],
            $_POST['dob'],
            trim($_POST['cnic']),
            trim($_POST['phone']),
            trim($_POST['email']),
            trim($_POST['address']),
            trim($_POST['department']),
            $_POST['semester'],
            $_POST['admission_date']
        ]);

        $message = "Student added successfully!";

    } catch (PDOException $e) {
        $message = "Unable to add student. CNIC or Email may already exist.";
    }
}


// FETCH STUDENT FOR EDIT
if (isset($_GET['edit'])) {

    $editID = (int) $_GET['edit'];

    $stmt = $conn->prepare(
        "SELECT * FROM Students WHERE Student_ID = ?"
    );

    $stmt->execute([$editID]);

    $editStudent = $stmt->fetch(PDO::FETCH_ASSOC);
}


// FETCH ALL STUDENTS
$students = $conn->query(
    "SELECT * FROM Students ORDER BY Student_ID DESC"
)->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Students Management | HostelHub</title>

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
            <h1 class="page-title">Students Management</h1>

            <p class="page-subtitle">
                Manage and organize hostel student records.
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
            <h2>All Students</h2>

            <p class="page-subtitle">
                Total Students: <?= count($students) ?>
            </p>
        </div>

        <a href="#studentForm" class="add-btn">

            <i class="fas fa-plus"></i>
            Add Student

        </a>

    </div>


    <!-- MESSAGES -->

    <?php if ($message): ?>

        <div class="alert alert-info">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <!-- ADD / EDIT STUDENT FORM -->

    <section class="student-form" id="studentForm">

        <h3>

            <?php if ($editStudent): ?>

                <i class="fas fa-user-edit"></i>
                Edit Student

            <?php else: ?>

                <i class="fas fa-user-plus"></i>
                Add New Student

            <?php endif; ?>

        </h3>


        <form method="POST" action="students.php<?= $editStudent ? '?edit=' . (int)$editStudent['Student_ID'] . '#studentForm' : '#studentForm' ?>">

            <?php if ($editStudent): ?>

                <input type="hidden"
                    name="student_id"
                    value="<?= (int)$editStudent['Student_ID'] ?>">

            <?php endif; ?>


            <div class="row">


                <div class="col-md-6">

                    <label>Student Name</label>

                    <input type="text"
                        name="name"
                        class="form-control"
                        value="<?= htmlspecialchars($editStudent['Name'] ?? '') ?>"
                        required>

                </div>


                <div class="col-md-6">

                    <label>Father Name</label>

                    <input type="text"
                        name="father_name"
                        class="form-control"
                        value="<?= htmlspecialchars($editStudent['Father_Name'] ?? '') ?>"
                        required>

                </div>


                <div class="col-md-6">

                    <label>Gender</label>

                    <select name="gender"
                        class="form-select"
                        required>

                        <option value="">Select Gender</option>

                        <option value="Male"
                            <?= ($editStudent['Gender'] ?? '') == 'Male' ? 'selected' : '' ?>>
                            Male
                        </option>

                        <option value="Female"
                            <?= ($editStudent['Gender'] ?? '') == 'Female' ? 'selected' : '' ?>>
                            Female
                        </option>

                    </select>

                </div>


                <div class="col-md-6">

                    <label>Date of Birth</label>

                    <input type="date"
                        name="dob"
                        class="form-control"
                        value="<?= htmlspecialchars($editStudent['DOB'] ?? '') ?>"
                        required>

                </div>


                <div class="col-md-6">

                    <label>CNIC</label>

                    <input type="text"
                        name="cnic"
                        class="form-control"
                        value="<?= htmlspecialchars($editStudent['CNIC'] ?? '') ?>"
                        required>

                </div>


                <div class="col-md-6">

                    <label>Phone Number</label>

                    <input type="text"
                        name="phone"
                        class="form-control"
                        value="<?= htmlspecialchars($editStudent['Phone'] ?? '') ?>"
                        required>

                </div>


                <div class="col-md-6">

                    <label>Email Address</label>

                    <input type="email"
                        name="email"
                        class="form-control"
                        value="<?= htmlspecialchars($editStudent['Email'] ?? '') ?>"
                        required>

                </div>


                <div class="col-md-6">

                    <label>Department</label>

                    <input type="text"
                        name="department"
                        class="form-control"
                        value="<?= htmlspecialchars($editStudent['Department'] ?? '') ?>"
                        required>

                </div>


                <div class="col-md-6">

                    <label>Semester</label>

                    <input type="number"
                        name="semester"
                        class="form-control"
                        min="1"
                        max="8"
                        value="<?= htmlspecialchars($editStudent['Semester'] ?? '') ?>"
                        required>

                </div>


                <div class="col-md-6">

                    <label>Admission Date</label>

                    <input type="date"
                        name="admission_date"
                        class="form-control"
                        value="<?= htmlspecialchars($editStudent['Admission_Date'] ?? '') ?>"
                        required>

                </div>


                <div class="col-12">

                    <label>Address</label>

                    <textarea name="address"
                        class="form-control"
                        rows="2"
                        required><?= htmlspecialchars($editStudent['Address'] ?? '') ?></textarea>

                </div>

            </div>


            <?php if ($editStudent): ?>

                <button type="submit"
                    name="update_student"
                    class="add-btn">

                    <i class="fas fa-save"></i>
                    Update Student

                </button>

                <a href="students.php"
                    class="btn btn-secondary ms-2">

                    Cancel

                </a>

            <?php else: ?>

                <button type="submit"
                    name="add_student"
                    class="add-btn">

                    <i class="fas fa-save"></i>
                    Save Student

                </button>

            <?php endif; ?>

        </form>

    </section>


    <!-- STUDENT RECORDS -->

    <section class="student-table">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

            <h3 class="mb-0">Student Records</h3>

            <input type="text"
                id="studentSearch"
                class="form-control"
                style="max-width: 320px;"
                placeholder="Search students by name, ID or department...">

        </div>


        <div class="table-container">

            <table>

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Father Name</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>

                </thead>


                <tbody>

                    <?php if (count($students) > 0): ?>

                        <?php foreach ($students as $student): ?>

                            <tr>

                                <td>
                                    #<?= htmlspecialchars($student['Student_ID']) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($student['Name']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($student['Father_Name']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($student['Department']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($student['Semester']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($student['Phone']) ?>
                                </td>

                                <td>

                                    <?php
                                    $studentBadge = $student['Status'] === 'Active'
                                        ? 'bg-success'
                                        : 'bg-danger';
                                    ?>

                                    <span class="badge <?= $studentBadge ?>">
                                        <?= htmlspecialchars($student['Status']) ?>
                                    </span>

                                </td>

                                <td>

                                    <a href="students.php?edit=<?= (int)$student['Student_ID'] ?>#studentForm"
                                        class="btn btn-sm btn-primary">

                                        <i class="fas fa-edit"></i>
                                        Edit

                                    </a>


                                    <form method="POST"
                                        action="students.php"
                                        style="display:inline;">

                                        <input type="hidden"
                                            name="delete_id"
                                            value="<?= (int)$student['Student_ID'] ?>">

                                        <button type="submit"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Are you sure you want to delete this student?')">

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
                                No student records found.
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

const searchInput = document.getElementById('studentSearch');

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