<?php
session_start();
require_once 'db.php';

$error = "";

// Redirect already logged-in users according to their role
if (isset($_SESSION['user_id'])) {

    $role = strtolower(trim($_SESSION['role'] ?? ''));

    if ($role === 'admin') {
        header("Location: index.php");
        exit;
    } 
    elseif ($role === 'staff') {
        header("Location: staff_dashboard.php");
        exit;
    } 
    elseif ($role === 'student') {
        header("Location: student_dashboard.php");
        exit;
    } 
    else {
        session_unset();
        session_destroy();
        session_start();

        $error = "Invalid user role!";
    }
}


// Login Process
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("
        SELECT * FROM Users
        WHERE Username = ?
        LIMIT 1
    ");

    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['Password'])) {

        $role = strtolower(trim($user['Role']));

        if (in_array($role, ['admin', 'staff', 'student'], true)) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['User_ID'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['role'] = $role;

            if ($role === 'admin') {
                header("Location: index.php");
                exit;
            } 
            elseif ($role === 'staff') {
                header("Location: staff_dashboard.php");
                exit;
            } 
            elseif ($role === 'student') {
                header("Location: student_dashboard.php");
                exit;
            }

        } else {
            $error = "Invalid user role!";
        }

    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | HostelHub</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        body {
            background: #F8FAFC;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: white;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }

        .login-title {
            color: #4F46E5;
            font-weight: 700;
        }

        .form-control {
            padding: 12px;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: #4F46E5;
            color: white;
            border: none;
            border-radius: 8px;
        }

        .login-btn:hover {
            background: #3730A3;
        }
    </style>
</head>

<body>

<div class="login-card">

    <div class="text-center mb-4">

        <h2 class="login-title">HostelHub</h2>

        <p class="text-muted">Hostel Management System</p>

        <h5 class="mt-4">Welcome Back!</h5>

        <p class="text-muted">Login to your account</p>

    </div>

    <?php if ($error): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <div class="mb-3">

            <label class="form-label">Username</label>

            <input type="text"
                   name="username"
                   class="form-control"
                   placeholder="Enter username"
                   required>

        </div>

        <div class="mb-4">

            <label class="form-label">Password</label>

            <input type="password"
                   name="password"
                   class="form-control"
                   placeholder="Enter password"
                   required>

        </div>

        <button type="submit" class="login-btn">
            Login
        </button>

    </form>

    <p class="text-center text-muted mt-4 mb-0">
        HostelHub &copy; <?= date('Y') ?>
    </p>

</div>

</body>
</html>