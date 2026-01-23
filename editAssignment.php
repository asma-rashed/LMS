<?php
session_start();
include 'config/database.php';
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Load the selected language file
$lang = include $_SESSION['lang'] === 'en' ? 'languages/lang_en.php' : 'languages/lang_ar.php';
// Only instructors can access
if (
    !isset($_SESSION['username']) ||
    $_SESSION['user_type'] !== 'instructors' ||
    !isset($_GET['id'])
) {
    header("Location: assignments.php");
    exit();
}

$assignment_id = intval($_GET['id']);
$current_user = $_SESSION['username'];

// Fetch assignment and verify ownership
$stmt = $con->prepare("SELECT * FROM assignments WHERE id = ? AND created_by = ?");
$stmt->bind_param("is", $assignment_id, $current_user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('You are not allowed to edit this assignment.'); window.location.href='assignments.php';</script>";
    exit();
}

$assignment = $result->fetch_assoc();

// ---------------------- UPDATE LOGIC ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = trim($_POST['due_date']); // new field
    $file_path = $assignment['file_path']; // keep old file if no new file uploaded

    // Handle new file upload
    if (!empty($_FILES['file']['name'])) {
        $target_dir = "uploads/assignments/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $target_file = $target_dir . time() . "_" . basename($_FILES["file"]["name"]);
        move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);

        // Delete old file if exists
        if (!empty($file_path) && file_exists($file_path)) {
            unlink($file_path);
        }

        $file_path = $target_file;
    }

    $update_stmt = $con->prepare("UPDATE assignments SET title = ?, description = ?, due_date = ?, file_path = ? WHERE id = ?");
    $update_stmt->bind_param("ssssi", $title, $description, $due_date, $file_path, $assignment_id);
    $update_stmt->execute();

    echo "<script>alert('Assignment updated successfully!'); window.location.href='assignments.php?id={$assignment['course_id']}';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Assignment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body>
    <div class="d-flex">

        <!-- Sidebar -->
        <div class="d-flex flex-column flex-shrink-0 p-3 text-white" style="width: 220px; min-height: 100vh; background-color: #14007B;">
            <span class="fs-4"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
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

        <!-- Main Content -->
        <div class="flex-grow-1 p-4">
            <h2 class="mb-4 text-primary"><?= $lang['edit_assignment_heading'] ?></h2>

            <form method="POST" enctype="multipart/form-data">

                <div class="mb-3">
                    <label class="form-label"><?= $lang['title_label'] ?></label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($assignment['title']); ?>" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= $lang['description_label'] ?></label>
                    <textarea name="description" class="form-control" rows="5"><?php echo htmlspecialchars($assignment['description']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= $lang['due_date_label'] ?></label>
                    <input type="date" name="due_date" class="form-control" value="<?php echo htmlspecialchars($assignment['due_date']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= $lang['current_file_label'] ?></label><br>

                    <?php if (!empty($assignment['file_path'])): ?>
                        <a href="<?php echo $assignment['file_path']; ?>" target="_blank" class="text-primary"><?= $lang['current_file_label'] ?></a>
                    <?php else: ?>
                        <p class="text-secondary"><?= $lang['no_file_text'] ?></p>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= $lang['replace_file_label'] ?></label>
                    <input type="file" name="file" class="form-control">
                </div>

                <button type="submit" class="btn btn-primary"><?= $lang['save_changes_button'] ?></button>
            </form>
        </div>

    </div>
</body>

</html>