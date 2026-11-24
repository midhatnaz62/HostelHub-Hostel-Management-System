<?php
require_once 'auth.php';

// Only Admin can access Payments Management
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
$editPayment = null;


// ADD PAYMENT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_payment'])) {

    try {
        $stmt = $conn->prepare(
            "INSERT INTO Payments
            (Student_ID, Amount, Payment_Date, Payment_For, Payment_Method, Status)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $_POST['student_id'],
            $_POST['amount'],
            $_POST['payment_date'],
            $_POST['payment_for'],
            $_POST['payment_method'],
            $_POST['status']
        ]);

        $message = "Payment added successfully!";

    } catch (PDOException $e) {
        $message = "Unable to add payment. Please check the details.";
    }
}


// UPDATE PAYMENT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_payment'])) {

    try {
        $stmt = $conn->prepare(
            "UPDATE Payments SET
            Student_ID = ?,
            Amount = ?,
            Payment_Date = ?,
            Payment_For = ?,
            Payment_Method = ?,
            Status = ?
            WHERE Payment_ID = ?"
        );

        $stmt->execute([
            $_POST['student_id'],
            $_POST['amount'],
            $_POST['payment_date'],
            $_POST['payment_for'],
            $_POST['payment_method'],
            $_POST['status'],
            $_POST['payment_id']
        ]);

        header("Location: payments.php?updated=1");
        exit;

    } catch (PDOException $e) {
        $message = "Unable to update payment. Please check the details.";
    }
}


// DELETE PAYMENT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {

    try {
        $stmt = $conn->prepare(
            "DELETE FROM Payments WHERE Payment_ID = ?"
        );

        $stmt->execute([(int) $_POST['delete_id']]);

        $message = "Payment deleted successfully!";

    } catch (PDOException $e) {
        $message = "Unable to delete payment.";
    }
}


// UPDATE MESSAGE
if (isset($_GET['updated'])) {
    $message = "Payment updated successfully!";
}


// FETCH STUDENTS
$stmt = $conn->query(
    "SELECT Student_ID, Name FROM Students ORDER BY Name"
);

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);


// FETCH PAYMENT FOR EDITING
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {

    $stmt = $conn->prepare(
        "SELECT * FROM Payments WHERE Payment_ID = ?"
    );

    $stmt->execute([(int) $_GET['edit']]);

    $editPayment = $stmt->fetch(PDO::FETCH_ASSOC);
}


// FETCH PAYMENT RECORDS
$stmt = $conn->query(
    "SELECT Payments.*, Students.Name
     FROM Payments
     INNER JOIN Students
     ON Payments.Student_ID = Students.Student_ID
     ORDER BY Payment_ID DESC"
);

$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);


// PAYMENT STATISTICS
$stmt = $conn->query(
    "SELECT
        COALESCE(SUM(CASE WHEN Status = 'Paid' THEN Amount ELSE 0 END), 0) AS Total_Collected,
        COALESCE(SUM(CASE WHEN Status = 'Pending' THEN Amount ELSE 0 END), 0) AS Pending_Amount,
        COUNT(*) AS Total_Payments
     FROM Payments"
);

$stats = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Payments Management | HostelHub</title>

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

            <h1 class="page-title">Payments Management</h1>

            <p class="page-subtitle">
                Manage student fees and payment records.
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

            <h2>All Payments</h2>

            <p class="page-subtitle">
                Total Payments: <?= htmlspecialchars($stats['Total_Payments']) ?>
            </p>

        </div>


        <a href="#addPayment" class="add-btn">

            <i class="fas fa-plus"></i>
            Add Payment

        </a>

    </div>


    <!-- MESSAGE -->

    <?php if ($message): ?>

        <div class="alert alert-info">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <!-- PAYMENT STATISTICS -->

    <div class="row mb-4">


        <div class="col-md-4 mb-3">

            <div class="panel p-3">

                <h6 class="text-muted">
                    <i class="fas fa-file-invoice-dollar"></i>
                    Total Payments
                </h6>

                <h3>
                    <?= htmlspecialchars($stats['Total_Payments']) ?>
                </h3>

            </div>

        </div>


        <div class="col-md-4 mb-3">

            <div class="panel p-3">

                <h6 class="text-muted">
                    <i class="fas fa-money-bill-wave"></i>
                    Total Collected
                </h6>

                <h3>
                    Rs. <?= number_format($stats['Total_Collected']) ?>
                </h3>

            </div>

        </div>


        <div class="col-md-4 mb-3">

            <div class="panel p-3">

                <h6 class="text-muted">
                    <i class="fas fa-clock"></i>
                    Pending Amount
                </h6>

                <h3>
                    Rs. <?= number_format($stats['Pending_Amount']) ?>
                </h3>

            </div>

        </div>


    </div>


    <!-- ADD / EDIT PAYMENT FORM -->

    <section class="student-form" id="addPayment">

        <h3>

            <?php if ($editPayment): ?>

                <i class="fas fa-edit"></i>
                Edit Payment

            <?php else: ?>

                <i class="fas fa-plus-circle"></i>
                Add New Payment

            <?php endif; ?>

        </h3>


        <form method="POST">


            <?php if ($editPayment): ?>

                <input type="hidden"
                    name="payment_id"
                    value="<?= $editPayment['Payment_ID'] ?>">

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
                                <?= $editPayment && $editPayment['Student_ID'] == $student['Student_ID'] ? 'selected' : '' ?>>

                                <?= htmlspecialchars($student['Name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-6">

                    <label>Amount (Rs.)</label>

                    <input type="number"
                        name="amount"
                        class="form-control"
                        min="1"
                        step="0.01"
                        value="<?= $editPayment ? htmlspecialchars($editPayment['Amount']) : '' ?>"
                        required>

                </div>


                <div class="col-md-6">

                    <label>Payment Date</label>

                    <input type="date"
                        name="payment_date"
                        class="form-control"
                        value="<?= $editPayment ? htmlspecialchars($editPayment['Payment_Date']) : date('Y-m-d') ?>"
                        required>

                </div>


                <div class="col-md-6">

                    <label>Payment For</label>

                    <?php $paymentOptions = ['Hostel Fee', 'Mess Fee', 'Other']; ?>

                    <select name="payment_for"
                        class="form-select"
                        required>

                        <?php foreach ($paymentOptions as $option): ?>

                            <option value="<?= $option ?>"
                                <?= $editPayment && $editPayment['Payment_For'] == $option ? 'selected' : '' ?>>

                                <?= $option ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-6">

                    <label>Payment Method</label>

                    <?php $methods = ['Cash', 'Bank Transfer', 'Online', 'Card']; ?>

                    <select name="payment_method"
                        class="form-select"
                        required>

                        <?php foreach ($methods as $method): ?>

                            <option value="<?= $method ?>"
                                <?= $editPayment && $editPayment['Payment_Method'] == $method ? 'selected' : '' ?>>

                                <?= $method ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-6">

                    <label>Payment Status</label>

                    <select name="status"
                        class="form-select"
                        required>

                        <option value="Paid"
                            <?= $editPayment && $editPayment['Status'] == 'Paid' ? 'selected' : '' ?>>

                            Paid

                        </option>

                        <option value="Pending"
                            <?= $editPayment && $editPayment['Status'] == 'Pending' ? 'selected' : '' ?>>

                            Pending

                        </option>

                    </select>

                </div>


            </div>


            <?php if ($editPayment): ?>

                <button type="submit"
                    name="update_payment"
                    class="add-btn">

                    <i class="fas fa-save"></i>
                    Update Payment

                </button>


                <a href="payments.php" class="btn btn-secondary">
                    Cancel
                </a>


            <?php else: ?>

                <button type="submit"
                    name="add_payment"
                    class="add-btn">

                    <i class="fas fa-plus"></i>
                    Add Payment

                </button>

            <?php endif; ?>


        </form>

    </section>


    <!-- PAYMENT RECORDS -->

    <section class="student-table">


        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

            <h3 class="mb-0">

                <i class="fas fa-file-invoice-dollar"></i>
                Payment Records

            </h3>


            <input type="text"
                id="paymentSearch"
                class="form-control"
                style="max-width: 320px;"
                placeholder="Search by student, payment ID, method or status...">

        </div>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>Payment ID</th>
                        <th>Student Name</th>
                        <th>Amount</th>
                        <th>Payment Date</th>
                        <th>Payment For</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (count($payments) > 0): ?>

                        <?php foreach ($payments as $payment): ?>

                            <tr>


                                <td>

                                    #<?= htmlspecialchars($payment['Payment_ID']) ?>

                                </td>


                                <td>

                                    <strong>
                                        <?= htmlspecialchars($payment['Name']) ?>
                                    </strong>

                                </td>


                                <td>

                                    Rs. <?= number_format($payment['Amount']) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars($payment['Payment_Date']) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars($payment['Payment_For']) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars($payment['Payment_Method']) ?>

                                </td>


                                <td>

                                    <?php if ($payment['Status'] == 'Paid'): ?>

                                        <span class="badge bg-success">
                                            Paid
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-warning text-dark">
                                            Pending
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>


                                    <a href="payments.php?edit=<?= $payment['Payment_ID'] ?>"
                                        class="btn btn-sm btn-primary">

                                        <i class="fas fa-edit"></i>
                                        Edit

                                    </a>


                                    <form method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Are you sure you want to delete this payment?');">


                                        <input type="hidden"
                                            name="delete_id"
                                            value="<?= $payment['Payment_ID'] ?>">


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

                            <td colspan="8" class="text-center">
                                No payment records found.
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

const searchInput = document.getElementById('paymentSearch');

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