<?php
require_once 'auth.php';

// Only Admin and Staff can access Room Allocations
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
$editAllocation = null;


// ADD ROOM ALLOCATION
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_allocation'])) {

    $studentID = (int) $_POST['student_id'];
    $roomID = (int) $_POST['room_id'];
    $allocationDate = $_POST['allocation_date'];
    $leavingDate = !empty($_POST['leaving_date']) ? $_POST['leaving_date'] : null;
    $status = $_POST['status'];

    try {

        $conn->beginTransaction();

        $stmt = $conn->prepare(
            "SELECT Capacity, Occupied_Beds FROM Rooms
             WHERE Room_ID = ? FOR UPDATE"
        );

        $stmt->execute([$roomID]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            throw new Exception("Room not found.");
        }

        if ($status === 'Active' && $room['Occupied_Beds'] >= $room['Capacity']) {
            throw new Exception("This room is already full!");
        }

        $stmt = $conn->prepare(
            "INSERT INTO Room_Allocations
            (Student_ID, Room_ID, Allocation_Date, Leaving_Date, Status)
            VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $studentID,
            $roomID,
            $allocationDate,
            $leavingDate,
            $status
        ]);

        if ($status === 'Active') {

            $stmt = $conn->prepare(
                "UPDATE Rooms
                 SET Occupied_Beds = Occupied_Beds + 1
                 WHERE Room_ID = ?"
            );

            $stmt->execute([$roomID]);
        }

        $conn->commit();

        $message = "Room allocated successfully!";

    } catch (Exception $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $message = "Unable to allocate room: " . $e->getMessage();

    } catch (PDOException $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $message = "Database error. Please check the allocation details.";
    }
}


// EDIT ROOM ALLOCATION
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_allocation'])) {

    $allocationID = (int) $_POST['allocation_id'];
    $studentID = (int) $_POST['student_id'];
    $roomID = (int) $_POST['room_id'];
    $allocationDate = $_POST['allocation_date'];
    $leavingDate = !empty($_POST['leaving_date']) ? $_POST['leaving_date'] : null;
    $status = $_POST['status'];

    try {

        $conn->beginTransaction();

        // Get existing allocation
        $stmt = $conn->prepare(
            "SELECT * FROM Room_Allocations
             WHERE Allocation_ID = ? FOR UPDATE"
        );

        $stmt->execute([$allocationID]);
        $oldAllocation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$oldAllocation) {
            throw new Exception("Allocation record not found.");
        }

        $oldRoomID = (int) $oldAllocation['Room_ID'];
        $oldStatus = $oldAllocation['Status'];

        // Lock old and new room records
        $stmt = $conn->prepare(
            "SELECT Room_ID, Capacity, Occupied_Beds
             FROM Rooms
             WHERE Room_ID IN (?, ?)
             ORDER BY Room_ID
             FOR UPDATE"
        );

        $stmt->execute([$oldRoomID, $roomID]);

        $lockedRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $roomData = [];

        foreach ($lockedRooms as $room) {
            $roomData[$room['Room_ID']] = $room;
        }

        if (!isset($roomData[$roomID])) {
            throw new Exception("Selected room not found.");
        }

        // Calculate available occupancy in target room
        $targetOccupied = (int) $roomData[$roomID]['Occupied_Beds'];

        // Remove old allocation from count if same room and previously active
        if ($oldRoomID === $roomID && $oldStatus === 'Active') {
            $targetOccupied--;
        }

        // Check capacity only for Active allocations
        if ($status === 'Active' && $targetOccupied >= (int)$roomData[$roomID]['Capacity']) {
            throw new Exception("Selected room is already full!");
        }

        // Update allocation record
        $stmt = $conn->prepare(
            "UPDATE Room_Allocations SET
             Student_ID = ?,
             Room_ID = ?,
             Allocation_Date = ?,
             Leaving_Date = ?,
             Status = ?
             WHERE Allocation_ID = ?"
        );

        $stmt->execute([
            $studentID,
            $roomID,
            $allocationDate,
            $leavingDate,
            $status,
            $allocationID
        ]);

        // Decrease old room occupancy if previous allocation was active
        if ($oldStatus === 'Active') {

            $stmt = $conn->prepare(
                "UPDATE Rooms
                 SET Occupied_Beds = GREATEST(Occupied_Beds - 1, 0)
                 WHERE Room_ID = ?"
            );

            $stmt->execute([$oldRoomID]);
        }

        // Increase new room occupancy if updated allocation is active
        if ($status === 'Active') {

            $stmt = $conn->prepare(
                "UPDATE Rooms
                 SET Occupied_Beds = Occupied_Beds + 1
                 WHERE Room_ID = ?"
            );

            $stmt->execute([$roomID]);
        }

        $conn->commit();

        $message = "Allocation updated successfully!";

    } catch (Exception $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $message = "Unable to update allocation: " . $e->getMessage();

    } catch (PDOException $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $message = "Database error while updating allocation.";
    }
}


// DELETE ROOM ALLOCATION
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {

    $deleteID = (int) $_POST['delete_id'];

    try {

        $conn->beginTransaction();

        $stmt = $conn->prepare(
            "SELECT Room_ID, Status
             FROM Room_Allocations
             WHERE Allocation_ID = ? FOR UPDATE"
        );

        $stmt->execute([$deleteID]);
        $allocation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($allocation) {

            $stmt = $conn->prepare(
                "DELETE FROM Room_Allocations
                 WHERE Allocation_ID = ?"
            );

            $stmt->execute([$deleteID]);

            if ($allocation['Status'] === 'Active') {

                $stmt = $conn->prepare(
                    "UPDATE Rooms
                     SET Occupied_Beds = GREATEST(Occupied_Beds - 1, 0)
                     WHERE Room_ID = ?"
                );

                $stmt->execute([$allocation['Room_ID']]);
            }

            $message = "Allocation deleted successfully!";

        } else {

            $message = "Allocation record not found.";
        }

        $conn->commit();

    } catch (Exception $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $message = "Unable to delete allocation.";

    } catch (PDOException $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $message = "Database error while deleting allocation.";
    }
}


// FETCH ALLOCATION FOR EDIT
if (isset($_GET['edit'])) {

    $editID = (int) $_GET['edit'];

    $stmt = $conn->prepare(
        "SELECT * FROM Room_Allocations
         WHERE Allocation_ID = ?"
    );

    $stmt->execute([$editID]);

    $editAllocation = $stmt->fetch(PDO::FETCH_ASSOC);
}


// FETCH STUDENTS
$students = $conn->query(
    "SELECT Student_ID, Name FROM Students ORDER BY Name"
)->fetchAll(PDO::FETCH_ASSOC);


// FETCH ROOMS
$rooms = $conn->query(
    "SELECT Room_ID, Room_Number, Capacity, Occupied_Beds
     FROM Rooms ORDER BY Room_Number"
)->fetchAll(PDO::FETCH_ASSOC);


// FETCH ALLOCATIONS
$stmt = $conn->query(
    "SELECT Room_Allocations.*, Students.Name, Rooms.Room_Number
     FROM Room_Allocations
     INNER JOIN Students
     ON Room_Allocations.Student_ID = Students.Student_ID
     INNER JOIN Rooms
     ON Room_Allocations.Room_ID = Rooms.Room_ID
     ORDER BY Allocation_ID DESC"
);

$allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Room Allocations | HostelHub</title>

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

            <h1 class="page-title">Room Allocations</h1>

            <p class="page-subtitle">
                Manage student room assignments.
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

            <h2>All Allocations</h2>

            <p class="page-subtitle">
                Total Allocations: <?= count($allocations) ?>
            </p>

        </div>


        <a href="#allocationForm" class="add-btn">

            <i class="fas fa-plus"></i>
            Allocate Room

        </a>

    </div>


    <!-- MESSAGES -->

    <?php if ($message): ?>

        <div class="alert alert-info">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <!-- ADD / EDIT ALLOCATION FORM -->

    <section class="student-form" id="allocationForm">

        <h3>

            <?php if ($editAllocation): ?>

                <i class="fas fa-edit"></i>
                Edit Room Allocation

            <?php else: ?>

                <i class="fas fa-bed"></i>
                Allocate Room to Student

            <?php endif; ?>

        </h3>


        <form method="POST" action="room_allocations.php<?= $editAllocation ? '?edit=' . (int)$editAllocation['Allocation_ID'] . '#allocationForm' : '#allocationForm' ?>">

            <?php if ($editAllocation): ?>

                <input type="hidden"
                    name="allocation_id"
                    value="<?= (int)$editAllocation['Allocation_ID'] ?>">

            <?php endif; ?>


            <div class="row">


                <div class="col-md-6">

                    <label>Select Student</label>

                    <select name="student_id"
                        class="form-select"
                        required>

                        <option value="">Choose Student</option>

                        <?php foreach ($students as $student): ?>

                            <option value="<?= $student['Student_ID'] ?>"
                                <?= ($editAllocation['Student_ID'] ?? '') == $student['Student_ID'] ? 'selected' : '' ?>>

                                <?= htmlspecialchars($student['Name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-6">

                    <label>Select Room</label>

                    <select name="room_id"
                        class="form-select"
                        required>

                        <option value="">Choose Room</option>

                        <?php foreach ($rooms as $room): ?>

                            <?php
                            $availableBeds = (int)$room['Capacity'] - (int)$room['Occupied_Beds'];

                            $isCurrentRoom = ($editAllocation['Room_ID'] ?? '') == $room['Room_ID'];

                            $canSelect = $availableBeds > 0 || $isCurrentRoom;
                            ?>

                            <?php if ($canSelect): ?>

                                <option value="<?= $room['Room_ID'] ?>"
                                    <?= $isCurrentRoom ? 'selected' : '' ?>>

                                    Room <?= htmlspecialchars($room['Room_Number']) ?>

                                    (<?= $availableBeds ?> beds available)

                                </option>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-6">

                    <label>Allocation Date</label>

                    <input type="date"
                        name="allocation_date"
                        class="form-control"
                        value="<?= htmlspecialchars($editAllocation['Allocation_Date'] ?? date('Y-m-d')) ?>"
                        required>

                </div>


                <div class="col-md-6">

                    <label>Leaving Date</label>

                    <input type="date"
                        name="leaving_date"
                        class="form-control"
                        value="<?= htmlspecialchars($editAllocation['Leaving_Date'] ?? '') ?>">

                </div>


                <div class="col-md-6">

                    <label>Status</label>

                    <select name="status"
                        class="form-select"
                        required>

                        <option value="Active"
                            <?= ($editAllocation['Status'] ?? 'Active') == 'Active' ? 'selected' : '' ?>>
                            Active
                        </option>

                        <option value="Completed"
                            <?= ($editAllocation['Status'] ?? '') == 'Completed' ? 'selected' : '' ?>>
                            Completed
                        </option>

                    </select>

                </div>

            </div>


            <?php if ($editAllocation): ?>

                <button type="submit"
                    name="update_allocation"
                    class="add-btn">

                    <i class="fas fa-save"></i>
                    Update Allocation

                </button>

                <a href="room_allocations.php"
                    class="btn btn-secondary ms-2">

                    Cancel

                </a>

            <?php else: ?>

                <button type="submit"
                    name="add_allocation"
                    class="add-btn">

                    <i class="fas fa-save"></i>
                    Allocate Room

                </button>

            <?php endif; ?>

        </form>

    </section>


    <!-- ALLOCATION RECORDS -->

    <section class="student-table">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

            <h3 class="mb-0">

                <i class="fas fa-list"></i>
                Allocation Records

            </h3>


            <input type="text"
                id="allocationSearch"
                class="form-control"
                style="max-width: 320px;"
                placeholder="Search by student name, room or status...">

        </div>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>Allocation ID</th>
                        <th>Student Name</th>
                        <th>Room Number</th>
                        <th>Allocation Date</th>
                        <th>Leaving Date</th>
                        <th>Status</th>
                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (count($allocations) > 0): ?>

                        <?php foreach ($allocations as $allocation): ?>

                            <tr>

                                <td>
                                    #<?= htmlspecialchars($allocation['Allocation_ID']) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($allocation['Name']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($allocation['Room_Number']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($allocation['Allocation_Date']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($allocation['Leaving_Date'] ?? 'N/A') ?>
                                </td>

                                <td>

                                    <?php
                                    $allocationBadge = $allocation['Status'] === 'Active'
                                        ? 'bg-success'
                                        : 'bg-primary';
                                    ?>

                                    <span class="badge <?= $allocationBadge ?>">
                                        <?= htmlspecialchars($allocation['Status']) ?>
                                    </span>

                                </td>

                                <td>

                                    <a href="room_allocations.php?edit=<?= (int)$allocation['Allocation_ID'] ?>#allocationForm"
                                        class="btn btn-sm btn-primary">

                                        <i class="fas fa-edit"></i>
                                        Edit

                                    </a>


                                    <form method="POST"
                                        action="room_allocations.php"
                                        style="display:inline;"
                                        onsubmit="return confirm('Are you sure you want to delete this allocation?');">

                                        <input type="hidden"
                                            name="delete_id"
                                            value="<?= (int)$allocation['Allocation_ID'] ?>">

                                        <button type="submit"
                                            class="btn btn-sm btn-danger">

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
                                No allocation records found.
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

const searchInput = document.getElementById('allocationSearch');

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