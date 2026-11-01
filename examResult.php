<?php
session_start();
include 'config/database.php';

// Only logged-in students can see their own result
if (!isset($_SESSION['username']) || ($_SESSION['user_type'] ?? '') !== 'users') {
    header("Location: index.php");
    exit();
}
if (!isset($_GET['exam_id'])) {
    header("Location: profile.php");
    exit();
}

$exam_id = intval($_GET['exam_id']);
$student_username = $_SESSION['username'];

// Get this student's latest submission for this exam
$stmt = $con->prepare("SELECT score, answers FROM exam_submissions WHERE exam_id = ? AND student_username = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("is", $exam_id, $student_username);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();

if (!$submission) {
    header("Location: exams.php");
    exit();
}

$percentage = $submission['score'];
$total = count(json_decode($submission['answers'], true) ?: []);
$score = $total > 0 ? (int) round($percentage * $total / 100) : 0;

// Get exam title
$stmt = $con->prepare("SELECT title FROM exams WHERE exam_id = ?");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();
$exam_title = htmlspecialchars($exam['title'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-body text-center">
                <h2 class="card-title text-success">✅ Exam Submitted Successfully!</h2>
                <h4 class="mt-3"><?php echo $exam_title; ?></h4>
                <p class="mt-2">Your Score: <strong><?php echo $score; ?></strong> / <strong><?php echo $total; ?></strong></p>
                <p>Percentage: <strong><?php echo $percentage; ?>%</strong></p>
                <a href="courses.php" class="btn btn-primary mt-3">Back to Courses</a>
            </div>
        </div>
    </div>
</body>
</html>
