<?php
require_once 'auth.php';

// Only Admin and Staff can access Rooms Management
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
$editRoom = null;


// DELETE ROOM
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {

    $deleteID = (int) $_POST['delete_id'];

    try {

        $stmt = $conn->prepare(
            "DELETE FROM Rooms WHERE Room_ID = ?"
        );

        $stmt->execute([$deleteID]);

        $message = "Room deleted successfully!";

    } catch (PDOException $e) {

        $message = "Unable to delete room. It may have related allocation records.";

    }
}


// UPDATE ROOM
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_room'])) {

    $roomID = (int) $_POST['room_id'];

    try {

        // Get current occupied beds
        $checkStmt = $conn->prepare(
            "SELECT Occupied_Beds FROM Rooms WHERE Room_ID = ?"
        );

        $checkStmt->execute([$roomID]);

        $currentRoom = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentRoom) {

            $message = "Room not found.";

        } elseif ((int)$_POST['capacity'] < (int)$currentRoom['Occupied_Beds']) {

            $message = "Capacity cannot be less than currently occupied beds.";

        } else {

            $stmt = $conn->prepare(
                "UPDATE Rooms SET
                Hostel_ID = ?,
                Room_Number = ?,
                Floor_Number = ?,
                Room_Type = ?,
                Capacity = ?,
                Monthly_Fee = ?
                WHERE Room_ID = ?"
            );

            $stmt->execute([
                (int) $_POST['hostel_id'],
                trim($_POST['room_number']),
                (int) $_POST['floor_number'],
                $_POST['room_type'],
                (int) $_POST['capacity'],
                (float) $_POST['monthly_fee'],
                $roomID
            ]);

            $message = "Room updated successfully!";

        }

    } catch (PDOException $e) {

        $message = "Unable to update room. Please check the details.";

    }
}


// ADD NEW ROOM
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_room'])) {

    try {

        $stmt = $conn->prepare(
            "INSERT INTO Rooms
            (Hostel_ID, Room_Number, Floor_Number, Room_Type,
            Capacity, Occupied_Beds, Monthly_Fee)
            VALUES (?, ?, ?, ?, ?, 0, ?)"
        );

        $stmt->execute([
            (int) $_POST['hostel_id'],
            trim($_POST['room_number']),
            (int) $_POST['floor_number'],
            $_POST['room_type'],
            (int) $_POST['capacity'],
            (float) $_POST['monthly_fee']
        ]);

        $message = "Room added successfully!";

    } catch (PDOException $e) {

        $message = "Unable to add room. Please check the details.";

    }
}


// FETCH ROOM FOR EDIT
if (isset($_GET['edit'])) {

    $editID = (int) $_GET['edit'];

    $stmt = $conn->prepare(
        "SELECT * FROM Rooms WHERE Room_ID = ?"
    );

    $stmt->execute([$editID]);

    $editRoom = $stmt->fetch(PDO::FETCH_ASSOC);
}


// FETCH HOSTELS
$hostelStmt = $conn->query(
    "SELECT Hostel_ID, Hostel_Name FROM Hostels"
);

$hostelsList = $hostelStmt->fetchAll(PDO::FETCH_ASSOC);


// FETCH ROOMS
$stmt = $conn->query(
    "SELECT Rooms.*, Hostels.Hostel_Name
     FROM Rooms
     INNER JOIN Hostels
     ON Rooms.Hostel_ID = Hostels.Hostel_ID
     ORDER BY Room_ID DESC"
);

$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rooms Management | HostelHub</title>

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
            <h1 class="page-title">Rooms Management</h1>

            <p class="page-subtitle">
                Manage and organize hostel room records.
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
            <h2>All Rooms</h2>

            <p class="page-subtitle">
                Total Rooms: <?= count($rooms) ?>
            </p>
        </div>

        <a href="#roomForm" class="add-btn">

            <i class="fas fa-plus"></i>
            Add Room

        </a>

    </div>


    <!-- MESSAGES -->

    <?php if ($message): ?>

        <div class="alert alert-info">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <!-- ADD / EDIT ROOM FORM -->

    <section class="student-form" id="roomForm">

        <h3>

            <?php if ($editRoom): ?>

                <i class="fas fa-edit"></i>
                Edit Room

            <?php else: ?>

                <i class="fas fa-bed"></i>
                Add New Room

            <?php endif; ?>

        </h3>


        <form method="POST" action="rooms.php<?= $editRoom ? '?edit=' . (int)$editRoom['Room_ID'] . '#roomForm' : '#roomForm' ?>">

            <?php if ($editRoom): ?>

                <input type="hidden"
                    name="room_id"
                    value="<?= (int)$editRoom['Room_ID'] ?>">

            <?php endif; ?>


            <div class="row">


                <div class="col-md-6">

                    <label>Select Hostel</label>

                    <select name="hostel_id"
                        class="form-select"
                        required>

                        <option value="">Choose Hostel</option>

                        <?php foreach ($hostelsList as $hostel): ?>

                            <option value="<?= $hostel['Hostel_ID'] ?>"
                                <?= ($editRoom['Hostel_ID'] ?? '') == $hostel['Hostel_ID'] ? 'selected' : '' ?>>

                                <?= htmlspecialchars($hostel['Hostel_Name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-6">

                    <label>Room Number</label>

                    <input type="text"
                        name="room_number"
                        class="form-control"
                        value="<?= htmlspecialchars($editRoom['Room_Number'] ?? '') ?>"
                        required>

                </div>


                <div class="col-md-6">

                    <label>Floor Number</label>

                    <input type="number"
                        name="floor_number"
                        class="form-control"
                        min="0"
                        value="<?= htmlspecialchars($editRoom['Floor_Number'] ?? '') ?>"
                        required>

                </div>


                <div class="col-md-6">

                    <label>Room Type</label>

                    <select name="room_type"
                        class="form-select"
                        required>

                        <option value="">Choose Room Type</option>

                        <option value="Single"
                            <?= ($editRoom['Room_Type'] ?? '') == 'Single' ? 'selected' : '' ?>>
                            Single
                        </option>

                        <option value="Double"
                            <?= ($editRoom['Room_Type'] ?? '') == 'Double' ? 'selected' : '' ?>>
                            Double
                        </option>

                        <option value="Triple"
                            <?= ($editRoom['Room_Type'] ?? '') == 'Triple' ? 'selected' : '' ?>>
                            Triple
                        </option>

                    </select>

                </div>


                <div class="col-md-6">

                    <label>Room Capacity</label>

                    <input type="number"
                        name="capacity"
                        class="form-control"
                        min="1"
                        value="<?= htmlspecialchars($editRoom['Capacity'] ?? '') ?>"
                        required>

                </div>


                <div class="col-md-6">

                    <label>Monthly Fee (Rs.)</label>

                    <input type="number"
                        name="monthly_fee"
                        class="form-control"
                        min="0"
                        step="0.01"
                        value="<?= htmlspecialchars($editRoom['Monthly_Fee'] ?? '') ?>"
                        required>

                </div>

            </div>


            <?php if ($editRoom): ?>

                <button type="submit"
                    name="update_room"
                    class="add-btn">

                    <i class="fas fa-save"></i>
                    Update Room

                </button>

                <a href="rooms.php"
                    class="btn btn-secondary ms-2">

                    Cancel

                </a>

            <?php else: ?>

                <button type="submit"
                    name="add_room"
                    class="add-btn">

                    <i class="fas fa-save"></i>
                    Save Room

                </button>

            <?php endif; ?>

        </form>

    </section>


    <!-- ROOM RECORDS -->

    <section class="student-table">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

            <h3 class="mb-0">Room Records</h3>

            <input type="text"
                id="roomSearch"
                class="form-control"
                style="max-width: 320px;"
                placeholder="Search rooms by number, hostel or type...">

        </div>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Hostel</th>
                        <th>Room Number</th>
                        <th>Floor</th>
                        <th>Room Type</th>
                        <th>Capacity</th>
                        <th>Occupied Beds</th>
                        <th>Available Beds</th>
                        <th>Monthly Fee</th>
                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (count($rooms) > 0): ?>

                        <?php foreach ($rooms as $room): ?>

                            <tr>

                                <td>
                                    #<?= htmlspecialchars($room['Room_ID']) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($room['Hostel_Name']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($room['Room_Number']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($room['Floor_Number']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($room['Room_Type']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($room['Capacity']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($room['Occupied_Beds']) ?>
                                </td>

                                <td>
                                    <?= (int)$room['Capacity'] - (int)$room['Occupied_Beds'] ?>
                                </td>

                                <td>
                                    Rs. <?= number_format($room['Monthly_Fee'], 2) ?>
                                </td>

                                <td>

                                    <a href="rooms.php?edit=<?= (int)$room['Room_ID'] ?>#roomForm"
                                        class="btn btn-sm btn-primary">

                                        <i class="fas fa-edit"></i>
                                        Edit

                                    </a>


                                    <form method="POST"
                                        action="rooms.php"
                                        style="display:inline;">

                                        <input type="hidden"
                                            name="delete_id"
                                            value="<?= (int)$room['Room_ID'] ?>">

                                        <button type="submit"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Are you sure you want to delete this room?')">

                                            <i class="fas fa-trash"></i>
                                            Delete

                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="10" class="text-center">
                                No room records found.
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

const searchInput = document.getElementById('roomSearch');

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