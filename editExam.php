<?php
session_start();
include 'config/database.php';
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Load the selected language file
$lang = include $_SESSION['lang'] === 'en' ? 'languages/lang_en.php' : 'languages/lang_ar.php';
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// Get user info
$tables = ['users', 'instructors'];
$row = null;
foreach ($tables as $table) {
    $stmt = mysqli_prepare($con, "SELECT * FROM $table WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $email = $row['email'];
        $user_type = $table;
        break;
    }
}
if (!$row) {
    header("Location: index.php");
    exit();
}

// Only instructors can edit
if ($user_type !== 'instructors') {
    header("Location: courses.php");
    exit();
}

// Fetch exam and check if this instructor is the creator
$stmt = mysqli_prepare($con, "SELECT * FROM exams WHERE exam_id = ? AND created_by = ?");
mysqli_stmt_bind_param($stmt, "is", $exam_id, $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (!$result || mysqli_num_rows($result) === 0) {
    header("Location: courses.php");
    exit();
}
$exam = mysqli_fetch_assoc($result);
$course_id = $exam['course_id'];
$exam_title = htmlspecialchars($exam['title']);
$questions = json_decode($exam['questions'], true);

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateexam'])) {
    $title = trim($_POST['title']);
    $questions_json = trim($_POST['questions']);

    $update_stmt = $con->prepare("UPDATE exams SET title = ?, questions = ? WHERE exam_id = ? AND created_by = ?");
    $update_stmt->bind_param("ssis", $title, $questions_json, $exam_id, $email);
    if ($update_stmt->execute()) {
        echo "<script>alert('Exam updated successfully!'); window.location.href='viewCourse.php?id=$course_id';</script>";
        exit();
    } else {
        echo "<script>alert('Failed to update exam.');</script>";
    }
    $update_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Exam</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        <div class="container">
            <div class="card shadow-lg border-0">
                <div class="card-body">
                    <h2 class="mb-4 text-primary"><?= $lang['edit_exam_heading'] ?></h2>

                    <form method="POST" id="examForm" class="needs-validation" novalidate>
                        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">

                        <!-- Exam Title -->
                        <div class="mb-4">
                            <label for="title" class="form-label fw-semibold"><?= $lang['exam_title_label'] ?></label>
                            <input type="text" id="title" name="title" class="form-control" value="<?php echo $exam_title; ?>" required>
                        </div>

                        <!-- Multiple Choice Questions -->
                        <section class="mb-5">
                            <h4 class="text-primary mb-3"><?= $lang['multiple_choice_heading'] ?></h4>
                            <div id="multiple" class="mb-3"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addMultiple()"><?= $lang['add_multiple'] ?></button>
                        </section>

                        <!-- True/False Questions -->
                        <section class="mb-5">
                            <h4 class="text-success mb-3"><?= $lang['true_false_heading'] ?></h4>
                            <div id="truefalse" class="mb-3"></div>
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="addTrueFalse()"><?= $lang['add_truefalse'] ?></button>
                        </section>

                        <!-- Essay Questions -->
                        <section class="mb-5">
                            <h4 class="text-warning mb-3"><?= $lang['essay_heading'] ?></h4>
                            <div id="essay" class="mb-3"></div>
                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="addEssay()"><?= $lang['add_essay'] ?></button>
                        </section>

                        <input type="hidden" name="questions" id="hiddenQuestions">

                        <div class="d-grid mt-4">
                            <button type="submit" name="updateexam" class="btn btn-primary btn-lg"><?= $lang['update_exam_btn'] ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            let questions = [];

            // Preload questions
            const existingQuestions = <?php echo json_encode($questions); ?>;
            existingQuestions.forEach((q, i) => {
                if (q.type === 'multiple_choice') {
                    addMultiple(q);
                } else if (q.type === 'true_false') {
                    addTrueFalse(q);
                } else if (q.type === 'essay') {
                    addEssay(q);
                }
            });

            function addMultiple(existing = null) {
                const container = document.getElementById('multiple');
                const qIndex = questions.length;
                const div = document.createElement('div');
                div.className = 'question border p-3 rounded mb-3 bg-light';
                const text = existing ? existing.text : '';
                const choices = existing ? existing.choices : ['', '', ''];
                const answer = existing ? existing.answer : '';
                div.innerHTML = `
                    <label class="form-label fw-semibold">Question Text:</label>
                    <input type="text" id="qtext_${qIndex}" class="form-control mb-3" value="${text}" required>

                    <label class="form-label fw-semibold">Options:</label>
                    <input type="text" id="opt1_${qIndex}" class="form-control mb-2" value="${choices[0] || ''}" required>
                    <input type="text" id="opt2_${qIndex}" class="form-control mb-2" value="${choices[1] || ''}" required>
                    <input type="text" id="opt3_${qIndex}" class="form-control mb-3" value="${choices[2] || ''}" required>

                    <label class="form-label fw-semibold">Correct Answer:</label>
                    <input type="text" id="answer_${qIndex}" class="form-control" value="${answer}" required>
                `;
                container.appendChild(div);
                questions.push({type: 'multiple_choice'});
                updateHiddenInput();
            }

            function addTrueFalse(existing = null) {
                const container = document.getElementById('truefalse');
                const qIndex = questions.length;
                const div = document.createElement('div');
                div.className = 'question border p-3 rounded mb-3 bg-light';
                const text = existing ? existing.text : '';
                const answer = existing ? existing.answer : '';
                div.innerHTML = `
                    <label class="form-label fw-semibold">Question Text:</label>
                    <input type="text" id="qtext_${qIndex}" class="form-control mb-3" value="${text}" required>

                    <label class="form-label fw-semibold">Correct Answer:</label>
                    <select id="answer_${qIndex}" class="form-select" required>
                        <option value="">Select answer</option>
                        <option value="True" ${answer === 'True' ? 'selected' : ''}>True</option>
                        <option value="False" ${answer === 'False' ? 'selected' : ''}>False</option>
                    </select>
                `;
                container.appendChild(div);
                questions.push({type: 'true_false'});
                updateHiddenInput();
            }

            function addEssay(existing = null) {
                const container = document.getElementById('essay');
                const qIndex = questions.length;
                const div = document.createElement('div');
                div.className = 'question border p-3 rounded mb-3 bg-light';
                const text = existing ? existing.text : '';
                div.innerHTML = `
                    <label class="form-label fw-semibold">Question Text:</label>
                    <input type="text" id="qtext_${qIndex}" class="form-control" value="${text}" required>
                `;
                container.appendChild(div);
                questions.push({type: 'essay'});
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
                    return {type: q.type, text, choices, answer};
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
