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
}
if ($user_type !== 'users' && $user_type !== 'instructors') {
    header("Location: index.php");
    exit();
}
if ($user_type === 'instructors') {
    $current_time = date('Y-m-d H:i:s');

    if (!empty($exam['end_datetime']) && $current_time > $exam['end_datetime']) {
        header("Location: exams.php");
        exit;
    }
}
$exam_id = intval($_GET['exam_id']);

$result = $con->query("SELECT * FROM exams WHERE exam_id = $exam_id");
$exam = $result->fetch_assoc();

$questions = json_decode($exam['questions'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $exam_id = $_POST['exam_id'];
    $student_username = $_POST['student_username'];

    // Get original exam questions
    $res = $con->query("SELECT questions FROM exams WHERE exam_id = $exam_id");
    $exam = $res->fetch_assoc();
    $questions = json_decode($exam['questions'], true);

    $answers = [];
    $score = 0;
    $total = count($questions);

    foreach ($questions as $i => $q) {
        $student_answer = $_POST['answer_' . $i] ?? '';
        $answers[] = [
            "question" => $q['text'],
            "student_answer" => $student_answer,
            "correct_answer" => $q['answer'] ?? ''
        ];

        if (isset($q['answer']) && $student_answer !== '') {
            if ($q['type'] === 'essay') {

                similar_text(mb_strtolower(trim($q['answer'])), mb_strtolower(trim($student_answer)), $percent);
                if ($percent >= 50) {
                    $score++;
                }
            } else {
                // Exact match for multiple choice / true-false
                if (trim(mb_strtolower($student_answer)) === trim(mb_strtolower($q['answer']))) {
                    $score++;
                }
            }
        }
    }

    $answers_json = json_encode($answers);
    $percentage = ($total > 0) ? round(($score / $total) * 100, 2) : 0;

    $stmt = $con->prepare("INSERT INTO exam_submissions (exam_id, student_username, answers, score) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $exam_id, $student_username, $answers_json, $percentage);
    $stmt->execute();

    header("Location: examResult.php?exam_id=$exam_id&score=$score&total=$total&percentage=$percentage");
    exit();
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
                        <i class="bi-journal-text  me-2"></i> <?= $lang['sidebar_exams'] ?>
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
            <div class="flex-grow-1 p-4">
                <h2 class="mb-4"><?= $lang['exams_heading'] ?> <?php echo htmlspecialchars($exam['title']); ?></h2>

                <?php if ($user_type == 'users'): ?>

                    <form method="POST" action="takeExam.php">
                        <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                        <input type="hidden" name="student_username" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">

                        <?php foreach ($questions as $i => $q): ?>
                            <div class="card mb-3 p-3">
                                <h5 class="card-title"><?= $lang['add_exam_label_options'] ?> <?php echo $i + 1; ?>:</h5>
                                <p class="card-text"><?php echo htmlspecialchars($q['text']); ?></p>

                                <?php if ($q['type'] == 'multiple_choice'): ?>
                                    <?php foreach ($q['choices'] as $c): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="answer_<?php echo $i; ?>" value="<?php echo htmlspecialchars($c); ?>">
                                            <label class="form-check-label"><?php echo htmlspecialchars($c); ?></label>
                                        </div>
                                    <?php endforeach; ?>

                                <?php elseif ($q['type'] == 'true_false'): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="answer_<?php echo $i; ?>" value="True">
                                        <label class="form-check-label"><?= $lang['true'] ?></label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="answer_<?php echo $i; ?>" value="False">
                                        <label class="form-check-label"><?= $lang['false'] ?></label>
                                    </div>

                                <?php else: ?>
                                    <div class="mb-3">
                                        <textarea class="form-control" name="answer_<?php echo $i; ?>" rows="3"></textarea>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <button type="submit" name="submit" class="btn btn-primary btn-lg"><?= $lang['submit_answers'] ?></button>
                    </form>

                <?php elseif ($user_type == 'instructors'):
                    $sub = $con->query("SELECT * FROM exam_submissions WHERE exam_id = $exam_id");
                ?>

                    <h3 class="mb-4"><?= $lang['students_took_this_exam'] ?></h3>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th><?= $lang['student_username'] ?></th>
                                <th><?= $lang['grade'] ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $sub->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['student_username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['score']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                <?php endif; ?>

            </div>

        </div>



    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>