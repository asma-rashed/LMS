<?php
session_start();
include 'config/database.php';
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Load the selected language file
$lang = include $_SESSION['lang'] === 'en' ? 'languages/lang_en.php' : 'languages/lang_ar.php';
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'instructors') {
    header("Location: index.php");
    exit();
}

$submission_id = intval($_GET['submission_id']);
$current_user = $_SESSION['username'];

// Fetch submission and assignment
$stmt = $con->prepare("
    SELECT s.*, a.title, a.course_id, a.created_by 
    FROM assignment_submissions s 
    INNER JOIN assignments a ON s.assignment_id = a.id 
    WHERE s.id = ? AND a.created_by = ?
");
$stmt->bind_param("is", $submission_id, $current_user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('You are not allowed to grade this submission.'); window.location.href='assignments.php';</script>";
    exit();
}

$submission = $result->fetch_assoc();

// Handle grading submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grade = trim($_POST['grade']);
    $feedback = trim($_POST['feedback']);

    $update_stmt = $con->prepare("UPDATE assignment_submissions SET grade = ?, feedback = ? WHERE id = ?");
    $update_stmt->bind_param("ssi", $grade, $feedback, $submission_id);
    $update_stmt->execute();

    echo "<script>alert('Submission graded successfully!'); window.location.href='viewSubmissions.php?id={$submission['id']}';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Submission - <?php echo htmlspecialchars($submission['title']); ?></title>
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
        <!-- Main content -->
        <div class="flex-grow-1 p-4">
            <h2 class="mb-4"><?= $lang['grade_submission'] ?> <?php echo htmlspecialchars($submission['student_username']); ?></h2>

            <div class="mb-3">
                <strong><?= $lang['assignment'] ?></strong> <?php echo htmlspecialchars($submission['title']); ?><br>
                <strong><?= $lang['submitted_at'] ?></strong> <?php echo htmlspecialchars($submission['submitted_at']); ?><br>
                <strong><?= $lang['submission'] ?></strong><br>
                <?php if (!empty($submission['file_path'])): ?>
                    <a href="<?php echo $submission['file_path']; ?>" target="_blank"><?= $lang['download_file'] ?></a>
                <?php else: ?>
                    <pre><?php echo htmlspecialchars($submission['answer_text']); ?></pre>
                <?php endif; ?>
            </div>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label"><?= $lang['grade'] ?></label>
                    <input type="number" name="grade" class="form-control" value="<?php echo htmlspecialchars($submission['grade']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= $lang['feedback'] ?></label>
                    <textarea name="feedback" class="form-control" rows="4"><?php echo htmlspecialchars($submission['feedback']); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary"><?= $lang['save_grade'] ?></button>
                <a href="viewSubmissions.php?id=<?php echo $submission['id']; ?>" class="btn btn-secondary"><?= $lang['back'] ?></a>
            </form>
        </div>
</body>

</html>