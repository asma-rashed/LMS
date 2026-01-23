<?php
session_start();
include 'config/database.php';

// Set language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

$lang = include ($_SESSION['lang'] === 'en'
    ? 'languages/lang_en.php'
    : 'languages/lang_ar.php');

// --- Variables ---
$duplicate_error = "";
$success_message = "";

// --- Registration Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $user_type = $_POST['user_type'];

    // Instructor major (students do not have this field)
    $major = ($user_type === 'instructors') ? trim($_POST['major']) : null;

    // Table name
    $table = ($user_type === 'instructors') ? 'instructors' : 'users';

    // Check duplicates
    $stmt = $con->prepare("SELECT id FROM $table WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $duplicate_error = "Error: Username or Email already exists.";
    } else {

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        if ($user_type === 'instructors') {
            $stmt = $con->prepare("INSERT INTO instructors (name, username, email, phone, password, major) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $username, $email, $phone, $hashed_password, $major);
        } else {
            $stmt = $con->prepare("INSERT INTO users (name, username, email, phone, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $username, $email, $phone, $hashed_password);
        }

        $stmt->execute();
        $_SESSION['success_message'] = "Registration successful! You can now log in.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Show success message once
if (!empty($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <title><?= $lang['register_page'] ?></title>
</head>

<body>
    <div class="container d-flex align-items-center min-vh-100 justify-content-center flex-column">

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success mb-3"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <div class="card shadow p-4 m-3" style="width: 100%; max-width: 600px;">

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#studentTab">
                        <?= $lang['index_student_tab'] ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#teacherTab">
                        <?= $lang['index_teacher_tab'] ?>
                    </button>
                </li>
            </ul>

            <div class="tab-content">

                <!-- STUDENT FORM -->
                <div class="tab-pane fade show active" id="studentTab">

                    <form method="post" class="border p-4 rounded" novalidate>
                        <input type="hidden" name="user_type" value="users">

                        <div class="mb-3">
                            <label class="form-label"><?= $lang['name'] ?></label>
                            <input type="text" class="form-control" name="name" id="s-name">
                            <div class="text-danger small" id="s-error-name"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= $lang['username'] ?></label>
                            <input type="text" class="form-control" name="username" id="s-username">
                            <div class="text-danger small" id="s-error-username"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= $lang['email'] ?></label>
                            <input type="email" class="form-control" name="email" id="s-email">
                            <div class="text-danger small" id="s-error-email"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= $lang['phone'] ?></label>
                            <input type="text" class="form-control" name="phone" id="s-phone">
                            <div class="text-danger small" id="s-error-phone"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= $lang['password'] ?></label>
                            <input type="password" class="form-control" name="password" id="s-password">
                            <div class="text-danger small" id="s-error-password"></div>
                        </div>

                        <?php if (!empty($duplicate_error)): ?>
                            <div class="text-danger small mb-3"><?= htmlspecialchars($duplicate_error) ?></div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary"><?= $lang['register'] ?></button>
                    </form>
                </div>

                <!-- INSTRUCTOR FORM -->
                <div class="tab-pane fade" id="teacherTab">

                    <form method="post" class="border p-4 rounded" novalidate>
                        <input type="hidden" name="user_type" value="instructors">

                        <div class="mb-3">
                            <label class="form-label"><?= $lang['name'] ?></label>
                            <input type="text" class="form-control" name="name" id="t-name">
                            <div class="text-danger small" id="t-error-name"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= $lang['username'] ?></label>
                            <input type="text" class="form-control" name="username" id="t-username">
                            <div class="text-danger small" id="t-error-username"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= $lang['email'] ?></label>
                            <input type="email" class="form-control" name="email" id="t-email">
                            <div class="text-danger small" id="t-error-email"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= $lang['phone'] ?></label>
                            <input type="text" class="form-control" name="phone" id="t-phone">
                            <div class="text-danger small" id="t-error-phone"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= $lang['major'] ?></label>
                            <input type="text" class="form-control" name="major" id="t-major">
                            <div class="text-danger small" id="t-error-major"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= $lang['password'] ?></label>
                            <input type="password" class="form-control" name="password" id="t-password">
                            <div class="text-danger small" id="t-error-password"></div>
                        </div>

                        <?php if (!empty($duplicate_error)): ?>
                            <div class="text-danger small mb-3"><?= htmlspecialchars($duplicate_error) ?></div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary"><?= $lang['register'] ?></button>
                    </form>
                </div>
            </div>

            <p><?= $lang['have_acoount'] ?></p>
        </div>
    </div>

    <script src="assets/js/updateuservalidation.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
