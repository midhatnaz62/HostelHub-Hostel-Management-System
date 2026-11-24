<?php
require_once 'auth.php';

// Only Admin can access Hostels Management
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
$editHostel = null;


// DELETE HOSTEL
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {

    $deleteID = (int) $_POST['delete_id'];

    try {
        $stmt = $conn->prepare(
            "DELETE FROM Hostels WHERE Hostel_ID = ?"
        );

        $stmt->execute([$deleteID]);

        $message = "Hostel deleted successfully!";

    } catch (PDOException $e) {
        $message = "Unable to delete hostel. It may have related room records.";
    }
}


// UPDATE HOSTEL
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_hostel'])) {

    $hostelID = (int) $_POST['hostel_id'];

    try {

        $stmt = $conn->prepare(
            "UPDATE Hostels SET
            Hostel_Name = ?,
            Location = ?,
            Total_Rooms = ?,
            Warden_Name = ?,
            Contact_Number = ?
            WHERE Hostel_ID = ?"
        );

        $stmt->execute([
            trim($_POST['hostel_name']),
            trim($_POST['location']),
            (int) $_POST['total_rooms'],
            trim($_POST['warden_name']),
            trim($_POST['contact_number']),
            $hostelID
        ]);

        $message = "Hostel updated successfully!";

    } catch (PDOException $e) {
        $message = "Unable to update hostel. Please try again.";
    }
}


// ADD NEW HOSTEL
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_hostel'])) {

    try {

        $stmt = $conn->prepare(
            "INSERT INTO Hostels
            (Hostel_Name, Location, Total_Rooms, Warden_Name, Contact_Number)
            VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            trim($_POST['hostel_name']),
            trim($_POST['location']),
            (int) $_POST['total_rooms'],
            trim($_POST['warden_name']),
            trim($_POST['contact_number'])
        ]);

        $message = "Hostel added successfully!";

    } catch (PDOException $e) {
        $message = "Unable to add hostel. Please try again.";
    }
}


// FETCH HOSTEL FOR EDIT
if (isset($_GET['edit'])) {

    $editID = (int) $_GET['edit'];

    $stmt = $conn->prepare(
        "SELECT * FROM Hostels WHERE Hostel_ID = ?"
    );

    $stmt->execute([$editID]);

    $editHostel = $stmt->fetch(PDO::FETCH_ASSOC);
}


// FETCH ALL HOSTELS
$hostels = $conn->query(
    "SELECT * FROM Hostels ORDER BY Hostel_ID DESC"
)->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Hostel Management | HostelHub</title>

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
            <h1 class="page-title">Hostel Management</h1>

            <p class="page-subtitle">
                Manage and organize hostel records.
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
            <h2>All Hostels</h2>

            <p class="page-subtitle">
                Total Hostels: <?= count($hostels) ?>
            </p>
        </div>

        <a href="#hostelForm" class="add-btn">

            <i class="fas fa-plus"></i>
            Add Hostel

        </a>

    </div>


    <!-- MESSAGES -->

    <?php if ($message): ?>

        <div class="alert alert-info">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <!-- ADD / EDIT HOSTEL FORM -->

    <section class="student-form" id="hostelForm">

        <h3>

            <?php if ($editHostel): ?>

                <i class="fas fa-edit"></i>
                Edit Hostel

            <?php else: ?>

                <i class="fas fa-building"></i>
                Add New Hostel

            <?php endif; ?>

        </h3>


        <form method="POST" action="hostels.php<?= $editHostel ? '?edit=' . (int)$editHostel['Hostel_ID'] . '#hostelForm' : '#hostelForm' ?>">

            <?php if ($editHostel): ?>

                <input type="hidden"
                    name="hostel_id"
                    value="<?= (int)$editHostel['Hostel_ID'] ?>">

            <?php endif; ?>


            <div class="row">

                <div class="col-md-6">

                    <label>Hostel Name</label>

                    <input type="text"
                        name="hostel_name"
                        class="form-control"
                        value="<?= htmlspecialchars($editHostel['Hostel_Name'] ?? '') ?>"
                        required>

                </div>


                <div class="col-md-6">

                    <label>Location</label>

                    <input type="text"
                        name="location"
                        class="form-control"
                        value="<?= htmlspecialchars($editHostel['Location'] ?? '') ?>"
                        required>

                </div>


                <div class="col-md-6">

                    <label>Total Rooms</label>

                    <input type="number"
                        name="total_rooms"
                        class="form-control"
                        min="1"
                        value="<?= htmlspecialchars($editHostel['Total_Rooms'] ?? '') ?>"
                        required>

                </div>


                <div class="col-md-6">

                    <label>Warden Name</label>

                    <input type="text"
                        name="warden_name"
                        class="form-control"
                        value="<?= htmlspecialchars($editHostel['Warden_Name'] ?? '') ?>"
                        required>

                </div>


                <div class="col-md-6">

                    <label>Contact Number</label>

                    <input type="text"
                        name="contact_number"
                        class="form-control"
                        value="<?= htmlspecialchars($editHostel['Contact_Number'] ?? '') ?>"
                        required>

                </div>

            </div>


            <?php if ($editHostel): ?>

                <button type="submit"
                    name="update_hostel"
                    class="add-btn">

                    <i class="fas fa-save"></i>
                    Update Hostel

                </button>

                <a href="hostels.php"
                    class="btn btn-secondary ms-2">

                    Cancel

                </a>

            <?php else: ?>

                <button type="submit"
                    name="add_hostel"
                    class="add-btn">

                    <i class="fas fa-save"></i>
                    Save Hostel

                </button>

            <?php endif; ?>

        </form>

    </section>


    <!-- HOSTEL RECORDS -->

    <section class="student-table">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

            <h3 class="mb-0">Hostel Records</h3>

            <input type="text"
                id="hostelSearch"
                class="form-control"
                style="max-width: 320px;"
                placeholder="Search hostels by name or location...">

        </div>


        <div class="table-container">

            <table>

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Hostel Name</th>
                        <th>Location</th>
                        <th>Total Rooms</th>
                        <th>Warden Name</th>
                        <th>Contact Number</th>
                        <th>Actions</th>
                    </tr>

                </thead>


                <tbody>

                    <?php if (count($hostels) > 0): ?>

                        <?php foreach ($hostels as $hostel): ?>

                            <tr>

                                <td>
                                    #<?= htmlspecialchars($hostel['Hostel_ID']) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($hostel['Hostel_Name']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($hostel['Location']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($hostel['Total_Rooms']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($hostel['Warden_Name']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($hostel['Contact_Number']) ?>
                                </td>

                                <td>

                                    <a href="hostels.php?edit=<?= (int)$hostel['Hostel_ID'] ?>#hostelForm"
                                        class="btn btn-sm btn-primary">

                                        <i class="fas fa-edit"></i>
                                        Edit

                                    </a>


                                    <form method="POST"
                                        action="hostels.php"
                                        style="display:inline;">

                                        <input type="hidden"
                                            name="delete_id"
                                            value="<?= (int)$hostel['Hostel_ID'] ?>">

                                        <button type="submit"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Are you sure you want to delete this hostel?')">

                                            <i class="fas fa-trash"></i>
                                            Delete

                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center">
                                No hostel records found.
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

const searchInput = document.getElementById('hostelSearch');

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