<?php
session_start();
include 'config/database.php';
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Load the selected language file
$lang = include $_SESSION['lang'] === 'en' ? 'languages/lang_en.php' : 'languages/lang_ar.php';
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscribe']) && $user_type === 'users') {
    $course_id = intval($_POST['course_id']);

    $check_query = "SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?";
    $stmt = mysqli_prepare($con, $check_query);
    mysqli_stmt_bind_param($stmt, "ii", $id, $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo "<script>alert('You are already subscribed to this course.'); window.location.href='courses.php';</script>";
    } else {
        $insert_query = "INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)";
        $stmt = mysqli_prepare($con, $insert_query);
        mysqli_stmt_bind_param($stmt, "ii", $id, $course_id);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Successfully subscribed to the course.'); window.location.href='courses.php';</script>";
        } else {
            echo "<script>alert('Subscription failed. Please try again.'); window.location.href='courses.php';</script>";
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unsubscribe']) && $user_type === 'users') {
    $course_id = intval($_POST['course_id']);

    $delete_query = "DELETE FROM enrollments WHERE student_id = ? AND course_id = ?";
    $stmt = mysqli_prepare($con, $delete_query);
    mysqli_stmt_bind_param($stmt, "ii", $id, $course_id);
    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Successfully unsubscribed from the course.'); window.location.href='courses.php';</script>";
    } else {
        echo "<script>alert('Unsubscription failed. Please try again.'); window.location.href='courses.php';</script>";
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
    <title><?= $lang['courses_heading'] ?></title>
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
                    <a href="courses.php" class="nav-link text-white active">
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
            <h2><?= $lang['courses_heading'] ?></h2>
            <?php
            
            if ($user_type === 'instructors') {
                $check_query = "SELECT * FROM courses WHERE created_by = ?";
                $stmt = mysqli_prepare($con, $check_query);
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $courses = mysqli_fetch_all($result, MYSQLI_ASSOC);
            } else {
                $check_query = "SELECT * FROM courses";
                $result = mysqli_query($con, $check_query);
                $courses = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
            }
            ?>

            <div class="row">
                <?php if (count($courses) > 0): ?>
                    <?php foreach ($courses as $course): ?>
                        <div class="col-12 mb-3">
                            <div class="card shadow-sm">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-1"><?php echo htmlspecialchars($course['course_code']); ?></p>
                                        <a href="viewCourse.php?id=<?php echo urlencode($course['course_id']); ?>" class="text-decoration-none">
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($course['course_name']); ?></h5>
                                        </a>
                                    </div>
                                    <div>
                                        <?php if ($user_type === 'users'):
                                            $check_enrollment_query = "SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?";
                                            $stmt = mysqli_prepare($con, $check_enrollment_query);
                                            mysqli_stmt_bind_param($stmt, "ii", $id, $course['course_id']);
                                            mysqli_stmt_execute($stmt);
                                            $enrollment_result = mysqli_stmt_get_result($stmt);
                                            $is_enrolled = mysqli_num_rows($enrollment_result) > 0;
                                            if ($is_enrolled):
                                        ?>
                                                <form action="courses.php" method="post">
                                                    <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['course_id']); ?>">
                                                    <button type="submit" name="unsubscribe" class="btn btn-sm btn-danger"><?= $lang['unsubscribe_button'] ?></button>
                                                </form>
                                            <?php else: ?>
                                                <form action="courses.php" method="post">
                                                    <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['course_id']); ?>">
                                                    <button type="submit" name="subscribe" class="btn btn-sm btn-success"><?= $lang['subscribe_button'] ?></button>
                                                </form>
                                        <?php endif;
                                        endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($user_type === 'instructors'): ?>
                        <div class="col-12 mt-3">
                            <a href="addCourse.php" class="btn text-white" style="background-color: #14007B;"><?= $lang['add_course_button'] ?></a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p>
                            <?php
                            if ($user_type === 'instructors') {
                                echo $lang['no_courses_instructor'];
                            } else {
                                echo $lang['no_courses_student'];
                            }
                            ?>
                        </p>
                        <?php if ($user_type === 'instructors'): ?>
                            <a href="addCourse.php" class="btn text-white" style="background-color: #14007B;"><?= $lang['add_course_button'] ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>


    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>