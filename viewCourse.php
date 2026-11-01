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
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$course = null;
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
            $username = $row['username'];
            $email = $row['email'];
            $student_id = $row['id'];
            $user_type = $table;
            $_SESSION['user_type'] = $table;
            break;
        }
        
    }

    if (!$row) {
        echo "User not found.";
        exit();
    }
}else {
    header("Location: index.php");
    exit();
}
$course_id = intval($_GET['id'] ?? 0);
$course_sql = "SELECT * FROM courses WHERE course_id = $course_id";
$course_result = mysqli_query($con, $course_sql);
$course = mysqli_fetch_assoc($course_result);
if (!$course) {
    header("Location: courses.php");
    exit();
}
if ($user_type === 'instructors' && $course['created_by'] !== $email) {
    header("Location: courses.php");
    exit();
}
// Count how many students enrolled
$count_sql = "SELECT COUNT(*) AS student_count FROM enrollments WHERE course_id = $course_id";
$count_result = mysqli_query($con, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$student_count = $count_row['student_count'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_course']) && $user_type === 'instructors') {
    $course_name = $_POST['course_name'];
    $course_code = $_POST['course_code'];
    $credit_hours = $_POST['credit_hours'];
    $description = $_POST['description'];
    $content = $_POST['content'];

    $update_sql = "UPDATE courses SET course_name = ?, course_code = ?, credit_hours = ?, description = ?, content = ? WHERE course_id = ? AND created_by = ?";
    $stmt = mysqli_prepare($con, $update_sql);
    mysqli_stmt_bind_param($stmt, "ssissis", $course_name, $course_code, $credit_hours, $description, $content, $course_id, $email);

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Course updated successfully.'); window.location.href='viewCourse.php?id=" . urlencode($course_id) . "';</script>";
        exit();
    } else {
        echo "<script>alert('Failed to update course. Please try again.'); window.location.href='viewCourse.php?id=" . urlencode($course_id) . "';</script>";
        exit();
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id']) && $user_type === 'instructors') {
    $course_id_to_delete = $_POST['course_id'];

    // Delete the course
    $delete_sql = "DELETE FROM courses WHERE course_id = ? AND created_by = ?";
    $stmt = mysqli_prepare($con, $delete_sql);
    mysqli_stmt_bind_param($stmt, "is", $course_id_to_delete, $email);

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Course deleted successfully.'); window.location.href='courses.php';</script>";
        exit();
    } else {
        echo "<script>alert('Failed to delete course. Please try again.'); window.location.href='viewCourse.php?id=" . urlencode($course_id_to_delete) . "';</script>";
        exit();
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
            <?php
            $course_sql = "SELECT * FROM courses WHERE course_id = ?";
            $course_stmt = mysqli_prepare($con, $course_sql);
            mysqli_stmt_bind_param($course_stmt, "i", $id);
            mysqli_stmt_execute($course_stmt);
            $course_result = mysqli_stmt_get_result($course_stmt);
            if ($course_result && mysqli_num_rows($course_result) > 0) {
                $course = mysqli_fetch_assoc($course_result);
            ?>
                <?php if ($user_type === 'users'): ?>
                    <div class="container my-4">
                        <div class="card shadow-sm border-start border-1">
                            <div class="card-header text-white" style="background-color: #14007B;">
                                <h2 class="mb-0"><?php echo htmlspecialchars($course['course_name']); ?></h2>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <div class="fw-bold text-secondary"><?= $lang['add_subject_label_code'] ?></div>
                                    <div><?php echo htmlspecialchars($course['course_code']); ?></div>
                                </div>
                                <div class="mb-2">
                                    <div class="fw-bold text-secondary"><?= $lang['add_subject_label_hours'] ?></div>
                                    <div><?php echo htmlspecialchars($course['credit_hours']); ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="fw-bold text-secondary"><?= $lang['add_subject_label_description'] ?></div>
                                    <div><?php echo nl2br(htmlspecialchars($course['description'])); ?></div>
                                </div>
                                <div>
                                    <h4 class="fw-bold text-secondary"><?= $lang['add_subject_label_content'] ?></h4>
                                    <div><?php echo nl2br(htmlspecialchars($course['content'])); ?></div>
                                </div>
                                <div>
                                    <h4 class="fw-bold text-secondary"><?= $lang['subject_label_file'] ?></h4>
                                    <?php if ($course['file_path']): ?>
                                        <p><a href="<?php echo $course['file_path']; ?>" download><?= $lang['download_courses_file'] ?></a></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>


                    <?php elseif ($email == $course['created_by']): ?>
                        <div class="container my-5">
                            <div class="card shadow-sm">
                                <div class="card-header text-white" style="background-color: #14007B;">
                                    <h4 class="mb-0"><?= $lang['edit_course'] ?></h4>
                                </div>
                                <div class="card-body">
                                    <form action="viewCourse.php?id=<?php echo urlencode($course['course_id']); ?>" method="post">
                                        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['course_id']); ?>">

                                        <div class="mb-3">
                                            <label for="course_name" class="form-label"><?= $lang['add_subject_label_title'] ?></label>
                                            <input type="text" id="course_name" name="course_name"
                                                class="form-control"
                                                value="<?php echo htmlspecialchars($course['course_name']); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="course_code" class="form-label"><?= $lang['add_subject_label_code'] ?></label>
                                            <input type="text" id="course_code" name="course_code"
                                                class="form-control"
                                                value="<?php echo htmlspecialchars($course['course_code']); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="credit_hours" class="form-label"><?= $lang['add_subject_label_hours'] ?></label>
                                            <input type="number" id="credit_hours" name="credit_hours"
                                                class="form-control"
                                                value="<?php echo htmlspecialchars($course['credit_hours']); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label"><?= $lang['add_subject_label_description'] ?></label>
                                            <textarea id="description" name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($course['description']); ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label for="content" class="form-label"><?= $lang['add_subject_label_content'] ?></label>
                                            <textarea id="content" name="content" class="form-control" rows="6" required><?php echo htmlspecialchars($course['content']); ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <h4 class="fw-bold text-secondary"><?= $lang['enrolled_students'] ?>:</h4>
                                            <div><?php echo $student_count; ?></div>
                                        </div>
                                        <div class="d-grid">
                                            <button type="submit" name="update_course" class="btn btn-lg text-white" style="background-color: #14007B;">
                                                <?= $lang['update_course'] ?>
                                            </button>
                                        </div>
                                    </form>
                                </div>

                            </div>

                            <div class="mt-4 d-flex justify-content-center">
                                <form action="viewCourse.php?id=<?php echo urlencode($course['course_id']); ?>" method="post" onsubmit="return confirm('<?= $lang['delete_course_cofirmation'] ?>');">
                                    <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['course_id']); ?>">
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <?= $lang['delete_course'] ?>
                                    </button>
                                </form>
                            </div>

                        </div>

                    <?php else:
                    header("Location: courses.php");
                    exit();
                endif; ?>
                    </div>



                <?php
            } else {
                echo  $lang['course_not_found'];
            }
            mysqli_stmt_close($course_stmt);
                ?>
        </div>


    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>