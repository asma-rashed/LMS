<?php
session_start();
include 'config/database.php';
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Load the selected language file
$lang = include $_SESSION['lang'] === 'en' ? 'languages/lang_en.php' : 'languages/lang_ar.php';
$username = '';
$user_type = '';
$row = null;

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

    $tables = ['users', 'instructors'];
    foreach ($tables as $table) {
        $stmt = mysqli_prepare($con, "SELECT * FROM $table WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $id = $row['id'];
            $username = $row['username'];
            $email = $row['email'];
            $user_type = $table;
            $_SESSION['user_type'] = $table;
            break;
        }
    }

    if (!$row) {
        echo "User not found.";
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
// profile.php (The update logic)
$duplicate_error = '';

if (isset($_POST['update'])) {
    $id = trim($_POST['id']);
    $name = trim($_POST['name']);
    $new_username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $major = ($user_type === 'instructors') ? trim($_POST['major']) : null;


    // Get current logged-in user's original data
    $current_username = $row['username'];
    $current_email = $row['email'];

    // Check if the new username or email is used by another user
    $tables = ['users', 'instructors'];
    $duplicate_error = '';

    foreach ($tables as $table) {
        $check_sql = "SELECT username, email FROM $table WHERE username = ? OR email = ?";
        $stmt_check = mysqli_prepare($con, $check_sql);
        mysqli_stmt_bind_param($stmt_check, "ss", $new_username, $email);
        mysqli_stmt_execute($stmt_check);
        $result = mysqli_stmt_get_result($stmt_check);

        while ($check_row = mysqli_fetch_assoc($result)) {
            // Skip if the found record belongs to the current logged-in user
            // Use username OR email to identify the logged-in user
            if (
                !(
                    ($check_row['username'] === $current_username) ||
                    ($check_row['email'] === $current_email)
                )
            ) {
                $duplicate_error = $lang['duplicate_error'];
                break 2; // stop checking
            }
        }
        mysqli_stmt_close($stmt_check);
    }



    // Update if no duplicates
    if (empty($duplicate_error)) {
        if ($user_type === 'instructors') {
            // Instructor update (has major)
            $update_sql = "UPDATE instructors SET name=?, email=?, username=?, phone=?, major=? WHERE id=?";
            $stmt = mysqli_prepare($con, $update_sql);
            mysqli_stmt_bind_param($stmt, "sssssi", $name, $email, $new_username, $phone, $major, $id);
        } else {
            // Regular user update (no major)
            $update_sql = "UPDATE users SET name=?, email=?, username=?, phone=? WHERE id=?";
            $stmt = mysqli_prepare($con, $update_sql);
            mysqli_stmt_bind_param($stmt, "ssssi", $name, $email, $new_username, $phone, $id);
        }

        if (!$stmt) {
            die("Prepare failed: " . mysqli_error($con));
        }

        $exec_update = mysqli_stmt_execute($stmt);

        if ($exec_update) {
            $_SESSION['username'] = $new_username;
            header("Location: profile.php");
            $_SESSION['success_message'] = $lang['profile_updated'];
            exit;
        } else {
            $duplicate_error = "Update failed: " . mysqli_stmt_error($stmt);
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <title><?= $lang['profile_page'] ?></title>
</head>

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="d-flex flex-column flex-shrink-0 p-3 text-white" style="width: 220px; min-height: 100vh; background-color: #14007B;">
            <a href="#" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                <span class="fs-4">
                    <?php
                    if (isset($_SESSION['username'])) {
                        echo htmlspecialchars($_SESSION['username']);
                    }
                    ?>
                </span>
            </a>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="profile.php" class="nav-link text-white active">
                        <i class="bi bi-person-circle me-2"></i> <?= $lang['sidebar_profile'] ?>
                    </a>
                </li>
                <li>
                    <a href="courses.php" class="nav-link text-white">
                        <i class="bi bi-journal-bookmark me-2"></i> <?= $lang['sidebar_courses'] ?>
                    </a>
                </li>
                <li>
                    <a href="exams.php" class="nav-link text-white">
                        <i class="bi-journal-text me-2"></i> <?= $lang['sidebar_exams'] ?>
                    </a>
                </li>
                <li>
                    <a href="assignments.php" class="nav-link text-white">
                        <i class="bi bi-file-earmark-text me-2"></i> <?= $lang['sidebar_assignments'] ?>
                    </a>
                </li>
                <li>
                    <a href="reports.php" class="nav-link text-white">
                        <i class="bi bi-bar-chart-line me-2"></i> <?= $lang['sidebar_reports'] ?>

                    </a>
                </li>
                <li>
                    <a href="logout.php" class="nav-link text-white">
                        <i class="bi bi-box-arrow-right me-2"></i> <?= $lang['sidebar_logout'] ?>
                    </a>
                </li>
            </ul>
            <a href="lang_switch.php" class="btn btn-outline-light mb-3">
                <?= $_SESSION['lang'] === 'en' ? 'العربية' : 'English' ?>
            </a>
        </div>
        <!-- Main content -->
        <div class="flex-grow-1 p-4">
            <div class="row d-flex align-items-center mb-4 justify-content-center">
                


                <div class="w-100"></div>

                
            </div>
            <?php if (!empty($_SESSION['success_message'])): ?>
                <div class="alert alert-success mb-3" role="alert">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                </div>
                <?php $_SESSION['success_message'] = ''; // Clear after showing 
                ?>
            <?php endif; ?>
            <form action="profile.php" method="post" class="border p-4 rounded" id="updateForm" novalidate>
                <input type="hidden" name="id" value="<?= htmlspecialchars($row['id'] ?? '') ?>">

                <div class="mb-3">
                    <label class="form-label"><?= $lang['name'] ?></label>
                    <input type="text" class="form-control" name="name" id="name" value="<?= htmlspecialchars($row['name'] ?? '') ?>">
                    <div class="text-danger small" id="error-name"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= $lang['username'] ?></label>
                    <input type="text" class="form-control" name="username" id="username" value="<?= htmlspecialchars($row['username'] ?? '') ?>">
                    <div class="text-danger small" id="error-username"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= $lang['email'] ?></label>
                    <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars($row['email'] ?? '') ?>">
                    <div class="text-danger small" id="error-email"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= $lang['phone'] ?></label>
                    <input type="text" class="form-control" name="phone" id="phone" value="<?= htmlspecialchars($row['phone'] ?? '') ?>">
                    <div class="text-danger small" id="error-phone"></div>
                </div>

                <?php if ($user_type === 'instructors') { ?>
                    <div class="mb-3">
                        <label class="form-label"><?= $lang['major'] ?></label>
                        <input type="text" class="form-control" name="major" id="major" value="<?= htmlspecialchars($row['major'] ?? '') ?>">
                        <div class="text-danger small" id="error-major"></div>
                    </div>
                <?php } ?>

                
                <?php if (!empty($duplicate_error)): ?>
                    <div class="text-danger small mb-3"><?= htmlspecialchars($duplicate_error) ?></div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-primary" name="update"><?= $lang['submit'] ?></button>
                </form>
                <div class="m-3">
                    <a href="change_password.php" class="btn btn-warning">
                        <?= $lang['change_password'] ?>
                    </a>
                </div>

        </div>


    </div>
    <script src="assets/js/updateuservalidation.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>