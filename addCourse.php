<?php
session_start();
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

$lang = include $_SESSION['lang'] === 'en' ? 'languages/lang_en.php' : 'languages/lang_ar.php';
include 'config/database.php';

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
if ($user_type !== 'instructors') {
    header('Location: courses.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $course_name = trim($_POST['course_name']);
    $course_code = trim($_POST['course_code']);
    $credit_hours = trim($_POST['credit_hours']);
    $description = trim($_POST['description']);
    $content = trim($_POST['content']);
    $created_by = $email;

    $file_path = null;
    if (!empty($_FILES['file']['name'])) {
    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        die("<script>alert('Upload failed.'); history.back();</script>");
    }

    $allowed = ['pdf', 'doc', 'docx', 'zip', 'png', 'jpg'];
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed) || $_FILES['file']['size'] > 5 * 1024 * 1024) {
        die("<script>alert('File type or size not allowed.'); history.back();</script>");
    }

    $target_dir = "uploads/courses/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

    $target_file = $target_dir . bin2hex(random_bytes(8)) . "." . $ext;   // random name, no user filename

    if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
        $file_path = $target_file;
    }
}

    $stmt = mysqli_prepare($con, "
    INSERT INTO courses 
    (course_name, course_code, credit_hours, description, content, file_path, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "ssissss", $course_name, $course_code, $credit_hours, $description, $content, $file_path, $created_by);


    if (mysqli_stmt_execute($stmt)) {
        header('Location: courses.php');
        exit();
    } else {
        echo "Error: " . mysqli_error($con);
    }

    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <title>Courses</title>
</head>

<body>
    <div class="d-flex">
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
                    <a href="profile.php" class="nav-link text-white">
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

        <div class="flex-grow-1 p-4">
            <form action="addCourse.php" method="POST" id="addCourseForm" novalidate enctype="multipart/form-data">
                <input type="hidden" name="created_by" value="<?php echo htmlspecialchars($email); ?>">

                <div class="mb-3">
                    <label for="course_name" class="form-label"><?= $lang['add_subject_label_title'] ?></label>
                    <input type="text" class="form-control" id="course_name" name="course_name">
                    <div class="text-danger small" id="error-course_name"></div>
                </div>

                <div class="mb-3">
                    <label for="course_code" class="form-label"><?= $lang['add_subject_label_code'] ?></label>
                    <input class="form-control" id="course_code" name="course_code">
                    <div class="text-danger small" id="error-course_code"></div>
                </div>

                <div class="mb-3">
                    <label for="credit_hours" class="form-label"><?= $lang['add_subject_label_hours'] ?></label>
                    <input class="form-control" id="credit_hours" name="credit_hours">
                    <div class="text-danger small" id="error-credit_hours"></div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label"><?= $lang['add_subject_label_description'] ?></label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    <div class="text-danger small" id="error-description"></div>
                </div>

                <div class="mb-3">
                    <label for="content" class="form-label"><?= $lang['add_subject_label_content'] ?></label>
                    <textarea class="form-control" id="content" name="content" rows="5"></textarea>
                    <div class="text-danger small" id="error-content"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= $lang['add_assignment_label_attach_file'] ?></label>
                    <input type="file" name="file" class="form-control">
                </div>
                <button type="submit" name="add" class="btn text-white" style="background-color: #14007B;"><?= $lang['add_subject_button_submit'] ?></button>
            </form>



        </div>


    </div>
    <script src="courseValidation.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>