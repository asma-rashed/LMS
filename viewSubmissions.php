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

$assignment_id = intval($_GET['id']);
$current_user = $_SESSION['username'];

// Check if instructor owns this assignment
$stmt = $con->prepare("SELECT * FROM assignments WHERE id = ? AND created_by = ?");
$stmt->bind_param("is", $assignment_id, $current_user);
$stmt->execute();
$result = $stmt->get_result();


if ($result->num_rows === 0) {
    echo "<script>alert('You are not allowed to view this assignment.'); window.location.href='assignments.php';</script>";
    exit();
}

$assignment = $result->fetch_assoc();

// Fetch all submissions for this assignment
$sub_stmt = $con->prepare("SELECT * FROM assignment_submissions WHERE assignment_id = ?");
$sub_stmt->bind_param("i", $assignment_id);
$sub_stmt->execute();
$sub_result = $sub_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submissions - <?php echo htmlspecialchars($assignment['title']); ?></title>
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
            <h2 class="mb-4"><?= $lang['submissions_for'] ?> <?php echo htmlspecialchars($assignment['title']); ?></h2>

            <?php if ($sub_result->num_rows > 0): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th><?= $lang['student_username'] ?></th>
                            <th><?= $lang['submitted_at'] ?></th>
                            <th><?= $lang['submission'] ?></th>
                            <th><?= $lang['submitted_file'] ?></th>
                            <th><?= $lang['grade'] ?></th>
                            <th><?= $lang['action'] ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($sub = $sub_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sub['student_username']); ?></td>
                                <td><?php echo htmlspecialchars($sub['submitted_at']); ?></td>
                                <td>

                                    <?php echo nl2br(htmlspecialchars($sub['answer_text'])); ?>

                                </td>
                                <td>
                                    <a href="<?php echo $sub['file_path']; ?>" target="_blank"><?= $lang['download_file'] ?></a>
                                </td>
                                <td><?php echo htmlspecialchars($sub['grade'] ?? '-'); ?></td>
                                <td>
                                    <a href="gradeSubmission.php?submission_id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-primary"><?= $lang['grade'] ?></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?= $lang['no_assignment_submissions'] ?></p>
            <?php endif; ?>

            <a href="assignments.php?id=<?php echo $assignment['course_id']; ?>" class="btn btn-secondary mt-3"><?= $lang['back_to_assignments'] ?></a>
        </div>
</body>

</html>