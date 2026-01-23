<?php
session_start();
date_default_timezone_set('Asia/Riyadh');
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Load the selected language file
$lang = include $_SESSION['lang'] === 'en' ? 'languages/lang_en.php' : 'languages/lang_ar.php';
include 'config/database.php';
// courses.php (The user retrieval logic)
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

if (isset($_POST['delete_exam'])) {
    $exam_id = intval($_POST['exam_id']);

    // Ensure this exam belongs to this instructor
    $stmt = mysqli_prepare($con, "SELECT * FROM exams WHERE exam_id = ? AND created_by = ?");
    mysqli_stmt_bind_param($stmt, "is", $exam_id, $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        // Delete the exam
        $delete_stmt = mysqli_prepare($con, "DELETE FROM exams WHERE exam_id = ?");
        mysqli_stmt_bind_param($delete_stmt, "i", $exam_id);
        if (mysqli_stmt_execute($delete_stmt)) {
            echo "<script>alert('Exam deleted successfully.'); window.location.href=window.location.href;</script>";
            exit();
        } else {
            echo "<script>alert('Error deleting exam.');</script>";
        }
    } else {
        echo "<script>alert('You cannot delete this exam.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <title><?= $lang['exams_heading'] ?></title>
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
                    <a href="exams.php" class="nav-link text-white active">
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
            <h2><?= $lang['exams_heading'] ?></h2>
            <?php
            if ($user_type === 'instructors') {
                // Instructor: show only their courses
                $check_query = "SELECT * FROM courses WHERE created_by = ?";
                $stmt = mysqli_prepare($con, $check_query);
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $courses = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
            } else {
                // Student: get all exams of courses the student is enrolled in
                $check_query = "
            SELECT e.*, c.course_name, c.course_code
            FROM exams e
            INNER JOIN enrollments en ON e.course_id = en.course_id
            INNER JOIN courses c ON e.course_id = c.course_id
            WHERE en.student_id = ?
        ";
                $stmt = mysqli_prepare($con, $check_query);
                mysqli_stmt_bind_param($stmt, "i", $row['id']);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $courses = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
            }
            ?>

            <div class="row">
                <?php if (count($courses) > 0): ?>
                    <?php if ($user_type === 'instructors'): ?>
                        <?php foreach ($courses as $course): ?>
                            <div class="col-12 mb-3">
                                <div class="card shadow-sm">
                                    <div class="card-body d-flex justify-content-between align-items-center">
                                        <div class="w-100">
                                            <p class="text-secondary mb-1"><?php echo htmlspecialchars($course['course_code']); ?></p>
                                            <a href="#" class="text-decoration-none" data-bs-toggle="collapse" data-bs-target="#exams-<?php echo $course['course_id']; ?>" aria-expanded="false">
                                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($course['course_name']); ?></h5>
                                            </a>

                                            <?php
                                            // Fetch exams for this course
                                            $exam_sql = "SELECT * FROM exams WHERE course_id = ?";
                                            $exam_stmt = mysqli_prepare($con, $exam_sql);
                                            mysqli_stmt_bind_param($exam_stmt, "i", $course['course_id']);
                                            mysqli_stmt_execute($exam_stmt);
                                            $exam_result = mysqli_stmt_get_result($exam_stmt);
                                            ?>

                                            <div class="collapse mt-2" id="exams-<?php echo $course['course_id']; ?>">
                                                <?php if ($exam_result && mysqli_num_rows($exam_result) > 0): ?>
                                                    <ul class="list-group list-group-flush">
                                                        <?php while ($exam = mysqli_fetch_assoc($exam_result)): ?>
                                                            <li class="list-group-item d-flex justify-content-between align-items-center w-100">
                                                                <a href="takeExam.php?exam_id=<?php echo urlencode($exam['exam_id']); ?>"><?php echo htmlspecialchars($exam['title']); ?></a>

                                                                <div class="d-flex">
                                                                    <a href="editExam.php?exam_id=<?php echo urlencode($exam['exam_id']); ?>" class="btn btn-sm btn-primary me-2"><?= $lang['edit_button'] ?> </a>
                                                                    <form method="post" onsubmit="return confirm('Are you sure you want to delete this exam?');">
                                                                        <input type="hidden" name="exam_id" value="<?php echo $exam['exam_id']; ?>">
                                                                        <button type="submit" name="delete_exam" class="btn btn-sm btn-danger"><?= $lang['delete_button'] ?></button>
                                                                    </form>
                                                                </div>
                                                            </li>
                                                            
                                                            <?php endwhile; ?>
                                                    </ul>
                                                    <a href="addExam.php?course_id=<?php echo urlencode($course['course_id']); ?>"><?= $lang['add_exam'] ?></a>
                                                <?php else: ?>
                                                    <p class="mt-2">
                                                        <?= $lang['no_exams_student'] ?> <br>
                                                        <a href="addExam.php?course_id=<?php echo urlencode($course['course_id']); ?>"><?= $lang['add_exam'] ?></a>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Student view -->
                        <div class="col-12 mb-3">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <?php if (count($courses) > 0): ?>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($courses as $exam):


                                                $current_time = date('Y-m-d H:i:s');

                                                if (!empty($exam['end_datetime']) && $current_time > $exam['end_datetime']) {
                                                    continue; 
                                                }
                                            ?>

                                                <li class="list-group-item d-flex justify-content-between align-items-center w-100">
                                                    <span><?php echo htmlspecialchars($exam['title']) . " (" . htmlspecialchars($exam['course_code']) . ")"; ?></span>

                                                    <?php
                                                    $stmt2 = mysqli_prepare($con, "SELECT * FROM exam_submissions WHERE exam_id = ? AND student_username = ?");
                                                    mysqli_stmt_bind_param($stmt2, "is", $exam['exam_id'], $username);
                                                    mysqli_stmt_execute($stmt2);
                                                    $result2 = mysqli_stmt_get_result($stmt2);

                                                    if (mysqli_num_rows($result2) > 0) { ?>
                                                        <button class="btn btn-sm btn-secondary" disabled><?= $lang["already_taken"] ?></button>
                                                    <?php
                                                    } else { ?>
                                                        <a href="takeExam.php?exam_id=<?php echo urlencode($exam['exam_id']); ?>" class="btn btn-sm btn-primary"><?= $lang["take_exam_btn"] ?></a>
                                                    <?php
                                                    }
                                                    ?>
                                                </li>

                                            <?php endforeach; ?>

                                        </ul>
                                    <?php else: ?>
                                        <p><?= $lang['no_exams_student'] ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p>
                            <?php
                            if ($user_type === 'instructors') {
                                echo $lang['no_exams_instructor'];
                            } else {
                                echo $lang['no_exams_student'];
                            }
                            ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>



    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>