<?php
session_start();
include 'config/database.php';
if (
    !isset($_GET['exam_id']) ||
    !isset($_GET['score']) ||
    !isset($_GET['total']) ||
    !isset($_GET['percentage'])
) {
    header("Location: profile.php");
    exit();
}
$exam_id = intval($_GET['exam_id']);
$score = intval($_GET['score']);
$total = intval($_GET['total']);
$percentage = floatval($_GET['percentage']);

// Get exam title
$exam_result = $con->query("SELECT title FROM exams WHERE exam_id = $exam_id");
$exam = $exam_result->fetch_assoc();
$exam_title = htmlspecialchars($exam['title']);
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
