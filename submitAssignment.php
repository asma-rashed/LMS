<?php
session_start();
include 'config/database.php';
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Load the selected language file
$lang = include $_SESSION['lang'] === 'en' ? 'languages/lang_en.php' : 'languages/lang_ar.php';
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'users') {
    header("Location: assignments.php");
    exit();
}

$assignment_id = intval($_GET['id']);

// Fetch assignment
$res = $con->query("SELECT * FROM assignments WHERE id = $assignment_id");
$assignment = $res->fetch_assoc();

// Due date check
$now = new DateTime();
$due = new DateTime($assignment['due_date']);
$due_passed = $now > $due;

// Fetch existing submission
$username = $_SESSION['username'];
$stmt = $con->prepare("SELECT * FROM assignment_submissions WHERE assignment_id = ? AND student_username = ?");
$stmt->bind_param("is", $assignment_id, $username);
$stmt->execute();
$existing_submission = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$due_passed) {

    $answer_text = trim($_POST['answer_text']);
    $file_path = $existing_submission['file_path'] ?? null;

    // Handle file upload
    if (!empty($_FILES['file']['name'])) {
        $target_dir = "uploads/submissions/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $target_file = $target_dir . time() . "_" . basename($_FILES["file"]["name"]);
        move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);
        $file_path = $target_file;
    }

    if ($existing_submission) {
        // UPDATE submission
        $stmt2 = $con->prepare("UPDATE assignment_submissions SET answer_text = ?, file_path = ? WHERE id = ?");
        $stmt2->bind_param("ssi", $answer_text, $file_path, $existing_submission['id']);
        $stmt2->execute();
    } else {
        // INSERT new submission
        $stmt2 = $con->prepare("INSERT INTO assignment_submissions (assignment_id, student_username, answer_text, file_path) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("isss", $assignment_id, $username, $answer_text, $file_path);
        $stmt2->execute();
    }

    echo "<script>alert('Assignment submitted successfully!'); window.location.href='assignments.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Assignment</title>
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
            <li><a href="profile.php" class="nav-link text-white"><i class="bi bi-person-circle me-2"></i> <?= $lang['sidebar_profile'] ?></a></li>
            <li><a href="courses.php" class="nav-link text-white"><i class="bi bi-journal-bookmark me-2"></i> <?= $lang['sidebar_courses'] ?></a></li>
            <li><a href="exams.php" class="nav-link text-white"><i class="bi bi-journal-text me-2"></i> <?= $lang['sidebar_exams'] ?></a></li>
            <li><a href="assignments.php" class="nav-link text-white"><i class="bi bi-file-earmark-text me-2"></i> <?= $lang['sidebar_assignments'] ?></a></li>
            <li><a href="reports.php" class="nav-link text-white"><i class="bi bi-bar-chart-line me-2"></i> <?= $lang['sidebar_reports'] ?></a></li>
            <li><a href="logout.php" class="nav-link text-white"><i class="bi bi-box-arrow-right me-2"></i> <?= $lang['sidebar_logout'] ?></a></li>
        </ul>
        <a href="lang_switch.php" class="btn btn-outline-light mb-3">
                <?= $_SESSION['lang'] === 'en' ? 'العربية' : 'English' ?>
            </a>
    </div>

    <div class="flex-grow-1 p-4">

        <h2 class="text-primary mb-3">
            <?php echo $existing_submission ? $lang['edit_your_submission'] : $lang['submit_assignment']; ?>
        </h2>

        <div class="card p-3 mb-4">
            <h4><?php echo htmlspecialchars($assignment['title']); ?></h4>
            <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>

            <?php if ($assignment['file_path']): ?>
                <p><a href="<?php echo $assignment['file_path']; ?>" download><?= $lang['download_assignment_file'] ?></a></p>
            <?php endif; ?>

            <p class="text-danger fw-bold">
                <?= $lang['add_assignment_label_due_date'] ?>: <?php echo $assignment['due_date']; ?>
                <?php if ($due_passed): ?> <?= $lang['deadline_passed'] ?><?php endif; ?>
            </p>
        </div>

        <?php if ($due_passed): ?>
            <div class="alert alert-danger"><?= $lang['cannot_edit_submit_due_passed'] ?></div>
        <?php else: ?>
            <form method="POST" enctype="multipart/form-data">

                <div class="mb-3">
                    <label class="form-label"><?= $lang['your_answer'] ?></label>
                    <textarea name="answer_text" class="form-control" rows="5" placeholder="Write your answer here..."><?php 
                        echo htmlspecialchars($existing_submission['answer_text'] ?? ""); 
                    ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= $lang['upload_a_file_optional'] ?></label>
                    <input type="file" name="file" class="form-control">
                </div>

                <?php if ($existing_submission && $existing_submission['file_path']): ?>
                    <p><?= $lang['ypur_uploaded_file'] ?> <a href="<?php echo $existing_submission['file_path']; ?>" download><?= $lang['download'] ?></a></p>
                <?php endif; ?>

                <button type="submit" class="btn btn-success">
                    <?php echo $existing_submission ? $lang['update_submission'] : $lang['submit_submission']; ?>
                </button>
            </form>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
