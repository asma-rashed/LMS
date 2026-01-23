<?php
session_start();
include 'config/database.php';
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Load the selected language file
$lang = include $_SESSION['lang'] === 'en' ? 'languages/lang_en.php' : 'languages/lang_ar.php';

if (
    !isset($_SESSION['username']) ||
    $_SESSION['user_type'] !== 'instructors' ||
    !isset($_GET['course_id'])
) {
    header("Location: assignments.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = intval($_POST['course_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $created_by = $_SESSION['username'];
    $due_date = $_POST['due_date'];

    $file_path = null;
    if (!empty($_FILES['file']['name'])) {
        $target_dir = "uploads/assignments/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $target_file = $target_dir . time() . "_" . basename($_FILES["file"]["name"]);
        move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);
        $file_path = $target_file;
    }

    $stmt = $con->prepare("INSERT INTO assignments (course_id, title, description, file_path, created_by, due_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $course_id, $title, $description, $file_path, $created_by, $due_date);
    $stmt->execute();

    echo "<script>alert('Assignment added successfully!'); window.location.href='assignments.php?id=$course_id';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Assignment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
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
            <h2 class="mb-4 text-primary"><?= $lang['add_assignment_heading'] ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($_GET['course_id']); ?>">

                <div class="mb-3">
                    <label class="form-label"><?= $lang['add_assignment_label_title'] ?></label>
                    <input type="text" name="title" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= $lang['add_assignment_label_description'] ?></label>
                    <textarea name="description" class="form-control" rows="5" placeholder="<?= $lang['add_assignment_placeholder_description'] ?>"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= $lang['add_assignment_label_attach_file'] ?></label>
                    <input type="file" name="file" class="form-control">
                </div>
                
                 <div class="mb-3">
                    <label class="form-label"><?= $lang['add_assignment_label_due_date'] ?></label>
                    <input type="date" name="due_date" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary"><?= $lang['add_assignment_button_submit'] ?></button>
            </form>
        </div>
    </div>
</body>

</html>