<?php
session_start();

include 'config/database.php';
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Load the selected language file
$lang = include $_SESSION['lang'] === 'en' ? 'languages/lang_en.php' : 'languages/lang_ar.php';
$error = '';

if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!isset($_POST['user_type'])) {
        $error = "Error: User type is not set.";
    } else {
        $user_type = $_POST['user_type'];
        $default_table = ($user_type === 'instructors') ? 'instructors' : 'users';

        if (!$con) {
            die("Database connection failed: " . mysqli_connect_error());
        }

        // 🔹 Step 1: Check if username exists in either table
        $tables = ['users', 'instructors'];
        $user_found = false;
        $found_table = '';

        foreach ($tables as $table) {
            $sql = "SELECT * FROM $table WHERE username = ?";
            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                $user_found = true;
                $found_table = $table;
                $row = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
                break;
            }

            mysqli_stmt_close($stmt);
        }

        // 🔹 Step 2: If found, verify password if same type, else show message
        if ($user_found) {
            if ($found_table === $default_table) {
                // Try login
                if (password_verify($password, $row['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['user_type'] = $found_table;
                    header("Location: profile.php");
                    exit();
                } else {
                    $error = "Incorrect username, password or user type.";
                }
            } else {
                
                $error = "Incorrect username, password or user type.";
            }
        }
        else {
            $error = "Incorrect username, password or user type.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <title>LMS</title>
</head>

<body style="background: linear-gradient(135deg, #7b68d7 10%, #14007B 90%); min-height: 100vh;">
    <a href="lang_switch.php" class="btn btn-outline-light mb-3" style="position: fixed; top: 10px; right: 10px;">
                <?= $_SESSION['lang'] === 'en' ? 'العربية' : 'English' ?>
            </a>
    <div class="container d-flex align-items-center min-vh-100">
        <div class="row w-100">
            <div class="col-md-6 text-white p-3 d-flex flex-column align-items-center">
                
                
            </div>


            <div class="col-md-6 text-white p-3 d-flex justify-content-end">
                <div class="card shadow p-4" style="width: 400px;">

                    <ul class="nav nav-tabs mb-3" id="userTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="student-tab" data-bs-toggle="tab" data-bs-target="#student" type="button" role="tab"><?= $lang['index_student_tab'] ?></button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="teacher-tab" data-bs-toggle="tab" data-bs-target="#teacher" type="button" role="tab"> <?= $lang['index_teacher_tab'] ?> </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="userTabsContent">
                        <div class="tab-pane fade show active" id="student" role="tabpanel">
                            <h5 class="text-center mb-3"><?= $lang['index_student_login'] ?></h5>
                            <form action="index.php" method="post">
                                <input type="hidden" name="user_type" value="users">
                                <div class="form-floating mb-3">
                                    <input type="text" name="username" class="form-control" id="studentName" placeholder="Username">
                                    <label for="studentName"><?= $lang['username'] ?></label>
                                    <div class="text-danger small mt-1" id="studentNameError"></div>
                                </div>
                                <div class="form-floating mb-3">

                                    <input type="password" name="password" class="form-control" id="studentPass" placeholder="Password">
                                    <label for="studentPass"><?= $lang['password'] ?></label>
                                    <div class="text-danger small mt-1" id="studentPassError"></div>
                                </div>
                                <button class="btn w-100 text-white" style="background-color: #14007B;" type="submit" name="login"><?= $lang['login'] ?></button>
                            </form>
                        </div>


                        <div class="tab-pane fade" id="teacher" role="tabpanel">
                            <h5 class="text-center mb-3"><?= $lang['index_teacher_login'] ?></h5>
                            <form action="index.php" method="post">
                                <input type="hidden" name="user_type" value="instructors">
                                <div class="form-floating mb-3">
                                    <input type="text" name="username" class="form-control" id="teacherName" placeholder="Username">
                                    <label for="teacherName"><?= $lang['username'] ?></label>
                                    <div class="text-danger small mt-1" id="teacherNameError"></div>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="password" name="password" class="form-control" id="teacherPass" placeholder="Password">
                                    <label for="teacherPass"><?= $lang['password'] ?></label>
                                    <div class="text-danger small mt-1" id="teacherPassError"></div>
                                </div>
                                <button class="btn text-white w-100" type="submit" name="login" style="background-color: #14007B;"><?= $lang['login'] ?></button>
                            </form>
                        </div>
                    </div>
                    <p><?= $lang['dont_have_acoount'] ?></p>
                    <?php if (!empty($error)): ?>
                        <p class="text-danger mt-2"><?php echo htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/js/validation.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>