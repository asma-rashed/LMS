<?php
session_start();
include 'config/database.php';

// User must be logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['user_type'])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
$user_type = $_SESSION['user_type']; // "users" or "instructors"

$success = "";
$error = "";

// Fetch current password hash
$stmt = mysqli_prepare($con, "SELECT password FROM $user_type WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    die("User not found.");
}

$current_hashed = $user['password'];


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current = trim($_POST['current_password']);
    $new = trim($_POST['new_password']);
    $confirm = trim($_POST['confirm_password']);

    // 1. Check empty fields
    if (empty($current) || empty($new) || empty($confirm)) {
        $error = "Please fill in all fields.";
    }
    // 2. Verify old password
    elseif (!password_verify($current, $current_hashed)) {
        $error = "Current password is incorrect.";
    }
    // 3. Check new password match
    elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    }
    // 4. Check password length
    elseif (strlen($new) < 6) {
        $error = "New password must be at least 6 characters.";
    } 
    else {
        // Everything good → update password
        $new_hashed = password_hash($new, PASSWORD_DEFAULT);

        $update = mysqli_prepare($con, "UPDATE $user_type SET password=? WHERE username=?");
        mysqli_stmt_bind_param($update, "ss", $new_hashed, $username);

        if (mysqli_stmt_execute($update)) {
            $success = "Your password has been updated successfully.";
        } else {
            $error = "Error updating password.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container mt-5" style="max-width: 500px;">
        <div class="card shadow">
            <div class="card-body">

                <h4 class="mb-3 text-center">Change Password</h4>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="post" action="change_password.php">

                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Update Password</button>

                </form>

                <a href="profile.php" class="btn btn-link mt-3 w-100">Back to Profile</a>

            </div>
        </div>
    </div>

</body>

</html>
