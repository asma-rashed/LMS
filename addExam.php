<?php
session_start();
date_default_timezone_set('Asia/Riyadh');
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



if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'instructors') {
    header('Location: exams.php');
    exit();
}

$course_id = null;

if (isset($_GET['course_id'])) {
    $course_id = $_GET['course_id'];
}

else if (isset($_POST['course_id'])) {
    $course_id = $_POST['course_id'];
}

if (!$course_id) {
    die("<script>alert('Missing course ID'); window.location.href='courses.php';</script>");
}




$course_id = intval($course_id);
$instructor_email = $row['email'];

$sql = "SELECT * FROM courses WHERE course_id = ? AND created_by = ?";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "is", $course_id, $instructor_email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: courses.php");
    exit();
}




if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addexam'])) {
    $title = trim($_POST['title']);
    $questions = trim($_POST['questions']);
    $created_by = $row['email'];
    $start = $_POST['start_datetime'];
    $end = $_POST['end_datetime'];

    $sql = "INSERT INTO exams (title, questions, course_id, created_by, start_datetime, end_datetime) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ssisss", $title, $questions, $course_id, $created_by, $start, $end);

    if ($stmt->execute()) {
        echo "<script>alert('Exam added successfuly!'); window.location.href='exams.php';</script>";
        exit();
    } else {
        echo "Could not save the exam. Please try again.";
        exit();
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">


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
            <div class="container">
                <div class="card shadow-lg border-0">
                    <div class="card-body">
                        <h2 class="mb-4 text-primary">
                            <?= $lang['add_exam_heading'] ?>
                        </h2>

                        <form method="POST" action="addExam.php" id="examForm" class="needs-validation" novalidate>
                            <input type="hidden" name="course_id" value="<?= $course_id ?>">

                            <!-- Exam Title -->
                            <div class="mb-4">
                                <label for="title" class="form-label fw-semibold"><?= $lang['add_exam_label_title'] ?></label>
                                <input type="text" id="title" name="title" class="form-control" placeholder="<?= $lang['add_exam_placeholder_title'] ?>" required>
                            </div>

                            <!-- Multiple Choice Questions -->
                            <section class="mb-5">
                                <h4 class="text-primary mb-3"><?= $lang['add_exam_section_multiple'] ?></h4>
                                <div id="multiple" class="mb-3"></div>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addMultiple()"><?= $lang['add_exam_button_add_multiple'] ?></button>
                            </section>

                            <!-- True/False Questions -->
                            <section class="mb-5">
                                <h4 class="text-success mb-3"><?= $lang['add_exam_section_truefalse'] ?></h4>
                                <div id="truefalse" class="mb-3"></div>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="addTrueFalse()"><?= $lang['add_exam_button_add_truefalse'] ?></button>
                            </section>

                            <!-- Essay Questions -->
                            <section class="mb-5">
                                <h4 class="text-warning mb-3"><?= $lang['add_exam_section_essay'] ?></h4>
                                <div id="essay" class="mb-3"></div>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="addEssay()"><?= $lang['add_exam_button_add_essay'] ?></button>
                            </section>

                            <input type="hidden" name="questions" id="hiddenQuestions">

                            <label><?= $lang['add_exam_label_start'] ?></label>
                            <input type="datetime-local" name="start_datetime" class="form-control" required>

                            <label><?= $lang['add_exam_label_end'] ?></label>
                            <input type="datetime-local" name="end_datetime" class="form-control" required>

                            <div class="d-grid mt-4">
                                <button type="submit" name="addexam" class="btn btn-primary btn-lg">
                                    <?= $lang['add_exam_button_save'] ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            
            <script>
                let questions = [];

                function addMultiple() {
                    const container = document.getElementById('multiple');
                    const qIndex = questions.length;

                    const div = document.createElement('div');
                    div.className = 'question border p-3 rounded mb-3 bg-light';
                    div.innerHTML = `
                <label class="form-label fw-semibold"><?= $lang['add_exam_label_question_text'] ?></label>
                <input type="text" id="qtext_${qIndex}" class="form-control mb-3" placeholder="<?= $lang['add_exam_placeholder_question_text'] ?>" required>

                <label class="form-label fw-semibold"><?= $lang['add_exam_label_options'] ?></label>
                <input type="text" id="opt1_${qIndex}" class="form-control mb-2" placeholder="<?= $lang['add_exam_placeholder_option1'] ?>" required>
                <input type="text" id="opt2_${qIndex}" class="form-control mb-2" placeholder="<?= $lang['add_exam_placeholder_option2'] ?>" required>
                <input type="text" id="opt3_${qIndex}" class="form-control mb-3" placeholder="<?= $lang['add_exam_placeholder_option3'] ?>" required>

                <label class="form-label fw-semibold"><?= $lang['add_exam_label_correct_answer'] ?></label>
                <input type="text" id="answer_${qIndex}" class="form-control" placeholder="<?= $lang['add_exam_placeholder_correct_answer'] ?>" required>
            `;
                    container.appendChild(div);

                    questions.push({
                        type: 'multiple_choice'
                    });
                    updateHiddenInput();
                }

                function addTrueFalse() {
                    const container = document.getElementById('truefalse');
                    const qIndex = questions.length;

                    const div = document.createElement('div');
                    div.className = 'question border p-3 rounded mb-3 bg-light';
                    div.innerHTML = `
                <label class="form-label fw-semibold"><?= $lang['add_exam_label_question_text'] ?></label>
                <input type="text" id="qtext_${qIndex}" class="form-control mb-3" placeholder="<?= $lang['add_exam_placeholder_question_text'] ?>" required>

                <label class="form-label fw-semibold"><?= $lang['add_exam_label_correct_answer'] ?></label>
                <select id="answer_${qIndex}" class="form-select" required>
                    <option value=""><?= $lang['add_exam_placeholder_correct_answer'] ?></option>
                    <option value="True">True</option>
                    <option value="False">False</option>
                </select>
            `;
                    container.appendChild(div);

                    questions.push({
                        type: 'true_false'
                    });
                    updateHiddenInput();
                }

                function addEssay() {
                    const container = document.getElementById('essay');
                    const qIndex = questions.length;

                    const div = document.createElement('div');
                    div.className = 'question border p-3 rounded mb-3 bg-light';
                    div.innerHTML = `
                <label class="form-label fw-semibold"><?= $lang['add_exam_label_question_text'] ?></label>
                <input type="text" id="qtext_${qIndex}" class="form-control mb-3" placeholder="<?= $lang['add_exam_placeholder_question_text'] ?>" required>

                <label class="form-label fw-semibold"><?= $lang['add_exam_label_correct_answer'] ?></label>
                <input type="text" id="answer_${qIndex}" class="form-control" placeholder="<?= $lang['add_exam_placeholder_essay_answer'] ?>" required>
            `;
                    container.appendChild(div);

                    questions.push({
                        type: 'essay'
                    });
                    updateHiddenInput();
                }

                document.getElementById('examForm').addEventListener('submit', function() {
                    const fullQuestions = questions.map((q, i) => {
                        const text = document.getElementById(`qtext_${i}`)?.value || '';
                        const answer = document.getElementById(`answer_${i}`)?.value || '';
                        let choices = null;

                        if (q.type === 'multiple_choice') {
                            choices = [
                                document.getElementById(`opt1_${i}`)?.value || '',
                                document.getElementById(`opt2_${i}`)?.value || '',
                                document.getElementById(`opt3_${i}`)?.value || ''
                            ];
                        }

                        return {
                            type: q.type,
                            text,
                            choices,
                            answer
                        };
                    });

                    document.getElementById('hiddenQuestions').value = JSON.stringify(fullQuestions);
                });

                function updateHiddenInput() {
                    document.getElementById('hiddenQuestions').value = JSON.stringify(questions);
                }
            </script>
        </div>


    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>