<?php
include 'config/config.php';
session_start();

// Display any access error message
$access_error = '';
if (isset($_SESSION['access_error'])) {
    $access_error = $_SESSION['access_error'];
    unset($_SESSION['access_error']);
}

// Enable error reporting for debugging purposes
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle form submissions
$error = '';
$registration_success = '';

// Display registration success message if available
if (isset($_SESSION['registration_success'])) {
    $registration_success = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['login'])) {
        if (isset($_POST['login_input']) && isset($_POST['password'])) {
            $login_input = $_POST['login_input'];
            $password = $_POST['password'];
    
            // Query to fetch user based on username or email
            $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $login_input, $login_input);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                if ($password === $row['password']) { // Directly compare the password
                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['role'] = $row['role'];
    
                    if ($row['role'] == 'student') {
                        header("Location: student_dashboard.php");
                        exit();
                    } elseif ($row['role'] == 'teacher') {
                        header("Location: teacher_dashboard.php");
                        exit();
                    } else {
                        header("Location: login.php");
                        exit();
                    }
                } else {
                    $error = "Invalid username/email or password.";
                }
            } else {
                $error = "Invalid username/email or password.";
            }
        } else {
            $error = "Please enter both username/email and password.";
        }
    }

    // Check if student registration form is submitted
    if (isset($_POST['register_student'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $full_name = $_POST['full_name']; // Added full_name field
        $email = $_POST['email'];
        $branch = $_POST['branch'];
        $usn = $_POST['usn'];

        // Set role to 'student'
        $role = 'student';

        // Check if user already exists
        $sql_check_user = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql_check_user);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "User already exists.";
        } else {
            // Prepare and execute SQL insert statements
            $sql_insert_user = "INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql_insert_user);
            $stmt->bind_param("ssss", $username, $password, $role, $email); // Store plain password

            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;

                $sql_insert_student = "INSERT INTO students (user_id, full_name, branch, usn) 
                                       VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql_insert_student);
                $stmt->bind_param("isss", $user_id, $full_name, $branch, $usn);

                if ($stmt->execute()) {
                    $_SESSION['registration_success'] = "Student registration successful!";
                    header("Location: login.php");
                    exit();
                } else {
                    $error = "Error: " . $sql_insert_student . "<br>" . $conn->error;
                }
            } else {
                $error = "Error: " . $sql_insert_user . "<br>" . $conn->error;
            }
        }
    }

    // Check if teacher registration form is submitted
    if (isset($_POST['register_teacher'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];

        // Set role to 'teacher'
        $role = 'teacher';

        // Check if user already exists
        $sql_check_user = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql_check_user);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "User already exists.";
        } else {
            // Prepare and execute SQL insert statements
            $sql_insert_user = "INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql_insert_user);
            $stmt->bind_param("ssss", $username, $password, $role, $email); // Store plain password

            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;

                $sql_insert_teacher = "INSERT INTO teachers (user_id, full_name) 
                                       VALUES (?, ?)";
                $stmt = $conn->prepare($sql_insert_teacher);
                $stmt->bind_param("is", $user_id, $full_name);

                if ($stmt->execute()) {
                    $_SESSION['registration_success'] = "Teacher registration successful!";
                    header("Location: login.php");
                    exit();
                } else {
                    $error = "Error: " . $sql_insert_teacher . "<br>" . $conn->error;
                }
            } else {
                $error = "Error: " . $sql_insert_user . "<br>" . $conn->error;
            }
        }
    }
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Open+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <title>Login & Registration</title>
</head>
<body>
    <div class="hero">
        <h1>Student Result Portal</h1>
    </div>
    <div class="container">
        <img src="img/logo-login.png" class="logo fade-in" alt="Logo">
        <?php if ($access_error): ?>
            <div class="error"><?php echo htmlspecialchars($access_error); ?></div>
        <?php endif; ?>
        <div class="tabs">
            <div id="login-tab" class="tab active" onclick="showTab('login')">Login</div>
            <div id="register_student-tab" class="tab" onclick="showTab('register_student')">Register Student</div>
            <div id="register_teacher-tab" class="tab" onclick="showTab('register_teacher')">Register Teacher</div>
        </div>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php elseif ($registration_success): ?>
            <div class="success"><?php echo $registration_success; ?></div>
        <?php endif; ?>
        <form id="login" method="post" action="login.php" class="active">
            <input type="text" name="login_input" placeholder="Username or Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login"><i class="fa-solid fa-key fa-0.5x"></i>Login</button>
        </form>
        <form id="register_student" method="post" action="login.php">
            <input type="text" name="full_name" placeholder="Full Name" required>
            <input type="text" name="usn" placeholder="USN" required>
            <input type="text" name="branch" placeholder="Branch" required>
            <input type="text" name="email" placeholder="Email" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="register_student"><i class="fa-solid fa-pen fa-0.5x"></i>Register Student</button>
        </form>
        <form id="register_teacher" method="post" action="login.php">
            <input type="text" name="full_name" placeholder="Full Name" required>
            <input type="text" name="email" placeholder="Email" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="register_teacher"><i class="fa-solid fa-pen fa-0.5x"></i>Register Teacher</button>
        </form>
         <a type="submit" href="index.html"><i class="fa-solid fa-home fa-0.5x"></i>Home</a>
    </div>
    <script>
        function showTab(tabName) {
            var tabs = document.getElementsByClassName('tab');
            var forms = document.getElementsByTagName('form');
            var logo = document.querySelector('.logo');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
                forms[i].classList.remove('active');
            }
            document.getElementById(tabName + '-tab').classList.add('active');
            document.getElementById(tabName).classList.add('active');

            // Change logo with smooth morph transition
            logo.classList.add('fade-out');
            setTimeout(function() {
                if (tabName === 'login') {
                    logo.src = 'img/college.png';
                } else if (tabName === 'register_student') {
                    logo.src = 'img/student1.png';
                } else if (tabName === 'register_teacher') {
                    logo.src = 'img/teacher.png';
                }
                logo.classList.remove('fade-out');
                logo.classList.add('fade-in');
            }, 300);
        }

        window.onload = function() {
            showTab('login');
        };
    </script>
</body>
</html>
