<?php
session_start();
include 'config/database.php';
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Load the selected language file
$lang = include $_SESSION['lang'] === 'en' ? 'languages/lang_en.php' : 'languages/lang_ar.php';
// require login
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
$user_type = $_SESSION['user_type'] ?? 'users'; // defensive

// helper: safe fetch single value
function fetch_one($con, $sql, $types = "", $params = [])
{
    $stmt = $con->prepare($sql);
    if ($types !== "") $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_array(MYSQLI_ASSOC);
    return $row ?: null;
}

// set timezone if you want accurate "now" (optional)
// date_default_timezone_set('Asia/Riyadh');
$now = date('Y-m-d H:i:s');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= $lang['reports_page_title'] ?></title>
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
                    <a href="reports.php" class="nav-link text-white active">
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
            <h1 class="mb-4"><?= $lang['reports_page_title'] ?></h1>

            <?php if ($user_type === 'instructors'): ?>

                <?php
                
                $instrRow = fetch_one($con, "SELECT email, username FROM instructors WHERE username = ?", "s", [$username]);

                $instr_email = $instrRow['email'] ?? null;
                $instr_username = $instrRow['username'] ?? $username;

                $sql = "SELECT * FROM courses WHERE created_by = ? OR created_by = ?";
                $stmt = $con->prepare($sql);
                $stmt->bind_param("ss", $instr_email, $instr_username);
                $stmt->execute();
                $courses_res = $stmt->get_result();
                $courses = $courses_res ? $courses_res->fetch_all(MYSQLI_ASSOC) : [];
                ?>

                <h3><?= $lang['your_courses'] ?></h3>

                <?php if (count($courses) === 0): ?>
                    <p><?= $lang['no_courses_found'] ?></p>
                <?php else: ?>

                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <?php foreach ($courses as $course): ?>

                            <?php
                            $course_id = $course['course_id'] ?? $course['id'] ?? null;

                            // total enrolled students
                            $totStmt = $con->prepare("SELECT COUNT(*) AS total_students FROM enrollments WHERE course_id = ?");
                            $totStmt->bind_param("i", $course_id);
                            $totStmt->execute();
                            $totR = $totStmt->get_result()->fetch_assoc();
                            $total_students = $totR['total_students'] ?? 0;

                            // average assignment grade for course:
                            // join assignment_submissions -> assignments to get course assignments
                            $avgAssignSql = "
                    SELECT AVG(CAST(grade AS DECIMAL(6,2))) AS avg_assign_grade
                    FROM assignment_submissions s
                    JOIN assignments a ON s.assignment_id = a.id
                    WHERE a.course_id = ?
                ";
                            $avgStmt = $con->prepare($avgAssignSql);
                            $avgStmt->bind_param("i", $course_id);
                            $avgStmt->execute();
                            $avgAssign = $avgStmt->get_result()->fetch_assoc();
                            $avg_assign_grade = $avgAssign['avg_assign_grade'] ?? null;

                            // average exam grade for course:
                            $avgExamSql = "
                    SELECT AVG(CAST(score AS DECIMAL(6,2))) AS avg_exam_grade
                    FROM exam_submissions s
                    JOIN exams e ON s.exam_id = e.exam_id
                    WHERE e.course_id = ?
                ";
                            $avgExStmt = $con->prepare($avgExamSql);
                            $avgExStmt->bind_param("i", $course_id);
                            $avgExStmt->execute();
                            $avgExam = $avgExStmt->get_result()->fetch_assoc();
                            $avg_exam_grade = $avgExam['avg_exam_grade'] ?? null;

                            // participation rates:
                            // total assignments and submitted assignments count
                            $totAssignStmt = $con->prepare("SELECT COUNT(*) AS total_assignments FROM assignments WHERE course_id = ?");
                            $totAssignStmt->bind_param("i", $course_id);
                            $totAssignStmt->execute();
                            $total_assignments = $totAssignStmt->get_result()->fetch_assoc()['total_assignments'] ?? 0;

                            $totAssignSubStmt = $con->prepare("SELECT COUNT(DISTINCT student_username) AS participants_assign FROM assignment_submissions s JOIN assignments a ON s.assignment_id=a.id WHERE a.course_id = ?");
                            $totAssignSubStmt->bind_param("i", $course_id);
                            $totAssignSubStmt->execute();
                            $participants_assign = $totAssignSubStmt->get_result()->fetch_assoc()['participants_assign'] ?? 0;

                            // total exams and participants
                            $totExamStmt = $con->prepare("SELECT COUNT(*) AS total_exams FROM exams WHERE course_id = ?");
                            $totExamStmt->bind_param("i", $course_id);
                            $totExamStmt->execute();
                            $total_exams = $totExamStmt->get_result()->fetch_assoc()['total_exams'] ?? 0;

                            $totExamPartStmt = $con->prepare("SELECT COUNT(DISTINCT student_username) AS participants_exam FROM exam_submissions s JOIN exams e ON s.exam_id = e.exam_id WHERE e.course_id = ?");
                            $totExamPartStmt->bind_param("i", $course_id);
                            $totExamPartStmt->execute();
                            $participants_exam = $totExamPartStmt->get_result()->fetch_assoc()['participants_exam'] ?? 0;

                            // compute participation percentages (guard division by zero)
                            $assign_part_pct = ($total_students > 0 && $total_assignments > 0) ? round(($participants_assign / $total_students) * 100, 2) : null;
                            $exam_part_pct   = ($total_students > 0 && $total_exams > 0) ? round(($participants_exam / $total_students) * 100, 2) : null;
                            ?>

                            <div class="col">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($course['course_name'] ?? $course['course_title'] ?? 'Untitled'); ?></h5>
                                        <p class="text-muted mb-1"><?php echo htmlspecialchars($course['course_code'] ?? ''); ?></p>

                                        <ul class="list-unstyled small">
                                            <li><strong><?= $lang['enrolled_students'] ?></strong> <?php echo $total_students; ?></li>
                                            <li><strong><?= $lang['avg_assignment_grade'] ?></strong> <?php echo $avg_assign_grade !== null ? $avg_assign_grade : '-'; ?></li>
                                            <li><strong><?= $lang['avg_exam_grade'] ?></strong> <?php echo $avg_exam_grade !== null ? $avg_exam_grade : '-'; ?></li>
                                            <li><strong><?= $lang['assignments_participation'] ?></strong> <?php echo $assign_part_pct !== null ? $assign_part_pct . '%' : 'No assignments'; ?></li>
                                            <li><strong><?= $lang['exams_participation'] ?></strong> <?php echo $exam_part_pct !== null ? $exam_part_pct . '%' : 'No exams'; ?></li>
                                        </ul>
                                        <?php
                                        $assignment_sql = "SELECT * FROM assignments WHERE course_id = ?";
                                        $assignment_stmt = mysqli_prepare($con, $assignment_sql);
                                        mysqli_stmt_bind_param($assignment_stmt, "i", $course['course_id']);
                                        mysqli_stmt_execute($assignment_stmt);
                                        $assignment_result = mysqli_stmt_get_result($assignment_stmt);
                                        if ($assignment_result && mysqli_num_rows($assignment_result) > 0):

                                            while ($assignment = mysqli_fetch_assoc($assignment_result)): ?>
                                                
                                                <!-- <a href="viewCourseReport.php?course_id=<?php echo urlencode($course_id); ?>" class="btn btn-sm btn-outline-primary">Detailed report</a> -->
                                                <a href="viewSubmissions.php?id=<?php echo urlencode($assignment['id']); ?>" class="btn btn-sm btn-outline-secondary ms-2"><?= $lang['view_submissions'] ?></a>
                                        <?php endwhile;
                                        endif; ?>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    </div>

                    <hr class="my-4">

                    <h3 class="mt-4"><?= $lang['select_course'] ?></h3>
                    <form method="GET" class="row g-2 mb-3">
                        <div class="col-auto">
                            <select name="detailed_course" class="form-select">
                                <option value="">-- Select course --</option>
                                <?php foreach ($courses as $c):
                                    $cid = $c['course_id'] ?? $c['id'] ?? '';
                                    $cname = $c['course_name'] ?? $c['course_title'] ?? $c['course_code'] ?? $cid;
                                ?>
                                    <option value="<?php echo $cid; ?>" <?php if (isset($_GET['detailed_course']) && $_GET['detailed_course'] == $cid) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($cname); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-primary"><?= $lang['show_students'] ?></button>
                        </div>
                    </form>

                    <?php
                    if (!empty($_GET['detailed_course'])):
                        $d_course = intval($_GET['detailed_course']);
                        // get enrolled students list (join enrollments -> users)
                        $students_stmt = $con->prepare("
                SELECT u.id, u.username, u.name
                FROM enrollments en
                LEFT JOIN users u ON u.id = en.student_id
                WHERE en.course_id = ?
            ");
                        $students_stmt->bind_param("i", $d_course);
                        $students_stmt->execute();
                        $students_res = $students_stmt->get_result();
                    ?>

                        <h4 class="mt-3"><?= $lang['students_performance'] ?></h4>

                        <?php if ($students_res->num_rows === 0): ?>
                            <p><?= $lang['no_students_enrolled'] ?></p>
                        <?php else: ?>
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th><?= $lang['student'] ?></th>
                                        <th><?= $lang['assignments_submitted_total'] ?></th>
                                        <th><?= $lang['assignment_avg'] ?></th>
                                        <th><?= $lang['exams_taken_total'] ?></th>
                                        <th><?= $lang['exam_avg'] ?></th>
                                        <th><?= $lang['participation'] ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($stu = $students_res->fetch_assoc()):
                                        $stu_id = $stu['id'];
                                        $stu_username = $stu['username'];

                                        // total assignments
                                        $totA = $con->prepare("SELECT COUNT(*) AS total_assign FROM assignments WHERE course_id = ?");
                                        $totA->bind_param("i", $d_course);
                                        $totA->execute();
                                        $total_assign = $totA->get_result()->fetch_assoc()['total_assign'] ?? 0;

                                        // assignments submitted by this student (count distinct assignment_id)
                                        $subA = $con->prepare("SELECT COUNT(*) AS submitted_count, AVG(CAST(grade AS DECIMAL(6,2))) AS avg_grade FROM assignment_submissions s JOIN assignments a ON s.assignment_id = a.id WHERE a.course_id = ? AND (s.student_username = ? OR s.student_username = ?)");
                                        // note: some sites store username, some student_id; we attempt username and id as fallback
                                        $subA->bind_param("iss", $d_course, $stu_username, $stu_id);
                                        $subA->execute();
                                        $subAres = $subA->get_result()->fetch_assoc();
                                        $submitted_count = $subAres['submitted_count'] ?? 0;
                                        $assign_avg = $subAres['avg_grade'] ?? null;

                                        // total exams
                                        $totE = $con->prepare("SELECT COUNT(*) AS total_exams FROM exams WHERE course_id = ?");
                                        $totE->bind_param("i", $d_course);
                                        $totE->execute();
                                        $total_exams = $totE->get_result()->fetch_assoc()['total_exams'] ?? 0;

                                        // exams taken and avg
                                        $subE = $con->prepare("SELECT COUNT(*) AS taken_count, AVG(CAST(score AS DECIMAL(6,2))) AS avg_score FROM exam_submissions s JOIN exams e ON s.exam_id = e.exam_id WHERE e.course_id = ? AND (s.student_username = ? OR s.student_username = ?)");
                                        $subE->bind_param("iss", $d_course, $stu_username, $stu_id);
                                        $subE->execute();
                                        $subEres = $subE->get_result()->fetch_assoc();
                                        $taken_count = $subEres['taken_count'] ?? 0;
                                        $exam_avg = $subEres['avg_score'] ?? null;

                                        // participation: average of assignment and exam participation for this student (simple)
                                        $assignment_participation = ($total_assignments = $total_assign) > 0 ? round(($submitted_count / $total_assign) * 100, 2) : null;
                                        $exam_participation = ($total_exams > 0) ? round(($taken_count / $total_exams) * 100, 2) : null;

                                        // overall participation: average of the two (ignoring nulls)
                                        $parts = [];
                                        if ($assignment_participation !== null) $parts[] = $assignment_participation;
                                        if ($exam_participation !== null) $parts[] = $exam_participation;
                                        $overall_participation = count($parts) ? round(array_sum($parts) / count($parts), 2) : null;
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stu['name'] ?? $stu['username']); ?></td>
                                            <td><?php echo $submitted_count . ' / ' . $total_assign; ?></td>
                                            <td><?php echo $assign_avg !== null ? $assign_avg : '-'; ?></td>
                                            <td><?php echo $taken_count . ' / ' . $total_exams; ?></td>
                                            <td><?php echo $exam_avg !== null ? $exam_avg : '-'; ?></td>
                                            <td><?php echo $overall_participation !== null ? $overall_participation . '%' : '-'; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                    <?php endif; // end detailed_course 
                    ?>

                <?php endif; // end courses exist 
                ?>

            <?php else: ?>

                <?php
                // --- STUDENT VIEW ---
                // find student id from users table (enrollments use student_id)
                $userRow = fetch_one($con, "SELECT id, username, name FROM users WHERE username = ?", "s", [$username]);
                $student_id = $userRow['id'] ?? null;

                // get enrolled courses
                $stmt = $con->prepare("
                    SELECT c.*, en.student_id
                    FROM enrollments en
                    JOIN courses c ON en.course_id = c.course_id
                    WHERE en.student_id = ?
                ");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $enrolled = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
                ?>

                <h3><?= $lang['my_courses_progress'] ?></h3>

                <?php if (count($enrolled) === 0): ?>
                    <p><?= $lang['you_not_enrolled'] ?></p>
                <?php else: ?>

                    <?php foreach ($enrolled as $course):
                        $course_id = $course['course_id'] ?? $course['id'] ?? null;

                        // assignments totals and submitted
                        $totAStmt = $con->prepare("SELECT COUNT(*) AS total_assign FROM assignments WHERE course_id = ?");
                        $totAStmt->bind_param("i", $course_id);
                        $totAStmt->execute();
                        $total_assign = $totAStmt->get_result()->fetch_assoc()['total_assign'] ?? 0;

                        $subAStmt = $con->prepare("SELECT COUNT(*) AS submitted_count, AVG(CAST(grade AS DECIMAL(6,2))) AS avg_grade FROM assignment_submissions s JOIN assignments a ON s.assignment_id = a.id WHERE a.course_id = ? AND (s.student_username = ? OR s.student_username = ?)");
                        $subAStmt->bind_param("iss", $course_id, $username, $student_id);
                        $subAStmt->execute();
                        $subARes = $subAStmt->get_result()->fetch_assoc();
                        $submitted_count = $subARes['submitted_count'] ?? 0;
                        $assign_avg = $subARes['avg_grade'] ?? null;

                        // exams totals and taken
                        $totEStmt = $con->prepare("SELECT COUNT(*) AS total_exams FROM exams WHERE course_id = ?");
                        $totEStmt->bind_param("i", $course_id);
                        $totEStmt->execute();
                        $total_exams = $totEStmt->get_result()->fetch_assoc()['total_exams'] ?? 0;

                        $subEStmt = $con->prepare("SELECT COUNT(*) AS taken_count, AVG(CAST(score AS DECIMAL(6,2))) AS avg_score FROM exam_submissions s JOIN exams e ON s.exam_id = e.exam_id WHERE e.course_id = ? AND (s.student_username = ? OR s.student_username = ?)");
                        $subEStmt->bind_param("iss", $course_id, $username, $student_id);
                        $subEStmt->execute();
                        $subERes = $subEStmt->get_result()->fetch_assoc();
                        $taken_count = $subERes['taken_count'] ?? 0;
                        $exam_avg = $subERes['avg_score'] ?? null;

                        // completion % and overall
                        $assign_completion = $total_assign > 0 ? round(($submitted_count / $total_assign) * 100, 2) : null;
                        $exam_completion   = $total_exams > 0 ? round(($taken_count / $total_exams) * 100, 2) : null;

                        // overall course progress: average of two completions (ignore nulls)
                        $parts = [];
                        if ($assign_completion !== null) $parts[] = $assign_completion;
                        if ($exam_completion !== null) $parts[] = $exam_completion;
                        $overall_progress = count($parts) ? round(array_sum($parts) / count($parts), 2) : 0;
                    ?>

                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($course['course_name'] ?? $course['course_title'] ?? 'Untitled'); ?></h5>
                                <p class="text-muted"><?php echo htmlspecialchars($course['course_code'] ?? ''); ?></p>

                                <div class="row">
                                    <div class="col-md-4">
                                        <p><strong><?= $lang['assignment'] ?></strong> <?php echo $submitted_count . ' / ' . $total_assign; ?></p>
                                        <p><strong><?= $lang['avg_assignment_grade'] ?></strong> <?php echo $assign_avg !== null ? $assign_avg : '-'; ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p><strong><?= $lang['exams_heading'] ?></strong> <?php echo $taken_count . ' / ' . $total_exams; ?></p>
                                        <p><strong><?= $lang['avg_exam_grade'] ?></strong> <?php echo $exam_avg !== null ? $exam_avg : '-'; ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p><strong><?= $lang['overall_progress'] ?></strong> <?php echo $overall_progress; ?>%</p>
                                        <div class="progress" style="height:18px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $overall_progress; ?>%;" aria-valuenow="<?php echo $overall_progress; ?>" aria-valuemin="0" aria-valuemax="100">
                                                <?php echo $overall_progress; ?>%
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <a href="viewCourse.php?id=<?php echo urlencode($course_id); ?>" class="btn btn-sm btn-outline-primary mt-3"><?= $lang['open_course'] ?></a>
                                <hr>

                                <h6 class="mt-3"><?= $lang['assignment'] ?>:</h6>
                                <?php
                                $Astmt = $con->prepare("
                                    SELECT a.title, s.grade, s.feedback, s.submitted_at
                                    FROM assignment_submissions s
                                    JOIN assignments a ON s.assignment_id = a.id
                                    WHERE a.course_id = ? AND s.student_username = ?
                                ");
                                $Astmt->bind_param("is", $course_id, $username);
                                $Astmt->execute();
                                $Ares = $Astmt->get_result();

                                if ($Ares->num_rows > 0): ?>
                                    <ul class="list-group mb-3">
                                        <?php while ($rowA = $Ares->fetch_assoc()): ?>
                                            <li class="list-group-item">
                                                <?php
                                        $assignment_sql = "SELECT * FROM assignments WHERE course_id = ?";
                                        $assignment_stmt = mysqli_prepare($con, $assignment_sql);
                                        mysqli_stmt_bind_param($assignment_stmt, "i", $course['course_id']);
                                        mysqli_stmt_execute($assignment_stmt);
                                        $assignment_result = mysqli_stmt_get_result($assignment_stmt);
                                        if ($assignment_result && mysqli_num_rows($assignment_result) > 0):

                                            while ($assignment = mysqli_fetch_assoc($assignment_result)): ?>
                                                <a href="submitAssignments.php?id<?php echo htmlspecialchars($assignment['id']); ?>"><?php echo htmlspecialchars($rowA['title']); ?></a><br>
                                                <?= $lang['grade'] ?>: <?php echo $rowA['grade'] !== null ? $rowA['grade'] : '-'; ?><br>
                                                <?= $lang['feedback'] ?>: <?php echo !empty($rowA['feedback']) ? htmlspecialchars($rowA['feedback']) : 'No feedback yet'; ?><br>
                                                <?= $lang['submitted_at'] ?>: <?php echo htmlspecialchars($rowA['submitted_at']); ?>
                                            </li>
                                        <?php endwhile;
                                        endif; ?>
                                        <?php endwhile; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-muted"><?= $lang['no_assignment_submissions'] ?></p>
                                <?php endif; ?>


                                <h6 class="mt-3"><?= $lang['exam_grades'] ?></h6>
                                <?php
                                $Estmt = $con->prepare("
                                    SELECT e.title, s.score
                                    FROM exam_submissions s
                                    JOIN exams e ON s.exam_id = e.exam_id
                                    WHERE e.course_id = ? AND s.student_username = ?
                                ");
                                $Estmt->bind_param("is", $course_id, $username);
                                $Estmt->execute();
                                $Eres = $Estmt->get_result();

                                if ($Eres->num_rows > 0): ?>
                                    <ul class="list-group mb-3">
                                        <?php while ($rowE = $Eres->fetch_assoc()): ?>
                                            <li class="list-group-item">
                                                <strong><?php echo htmlspecialchars($rowE['title']); ?></strong><br>
                                                <?= $lang['grade'] ?>: <?php echo $rowE['score'] !== null ? $rowE['score'] : '-'; ?><br>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-muted"><?= $lang['no_exam_submissions'] ?></p>
                                <?php endif; ?>

                            </div>
                        </div>


                    <?php endforeach; ?>

                <?php endif; ?>

            <?php endif; // end user_type check 
            ?>

        </div>
</body>

</html>