<?php
include 'config/config.php';
session_start();

// Define allowed user IDs for accessing the teacher dashboard
$allowed_user_ids = [127]; // Replace with actual allowed user IDs

// Check if the user is logged in, has a role of 'teacher', and is allowed to access
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] != 'teacher' || !in_array($_SESSION['user_id'], $allowed_user_ids)) {
    $_SESSION['access_error'] = "You do not have access to the teacher dashboard. Please contact admin";
    header("Location: login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Debugging: check $_SESSION['user_id']
if (!isset($_SESSION['user_id'])) {
    echo "Session user_id not set."; // You can also use logging instead of echoing
    exit();
}

$user_id = $_SESSION['user_id'];

// Query to fetch the full name of the teacher
$full_name_sql = "SELECT full_name FROM teachers WHERE user_id = ?";
$stmt = $conn->prepare($full_name_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$full_name_result = $stmt->get_result();

if ($full_name_result && $full_name_result->num_rows == 1) {
    $full_name_row = $full_name_result->fetch_assoc();
    $full_name = $full_name_row['full_name'];
} else {
    echo "Error fetching full name: " . $conn->error;
    exit();
}

// Fetch results grouped by students and courses
$sql = "SELECT s.student_id, s.full_name AS student_name, c.course_name, r.marks, r.grade 
        FROM students s
        LEFT JOIN results r ON s.student_id = r.student_id
        LEFT JOIN courses c ON r.course_id = c.course_id
        ORDER BY s.full_name, c.course_name";

$result = $conn->query($sql);

$results = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $student_id = $row['student_id'];
        $student_name = $row['student_name'];
        $course_name = $row['course_name'];
        $marks = $row['marks'];
        $grade = $row['grade'];

        // Organize results by student ID and course name
        if (!isset($results[$student_id])) {
            $results[$student_id] = [
                'student_name' => $student_name,
                'courses' => [],
                'total_marks' => 0,
                'pass_fail' => 'Pass' // Default to Pass
            ];
        }

        // Store each course and its marks
        $results[$student_id]['courses'][$course_name] = [
            'marks' => $marks,
            'grade' => $grade
        ];

        // Calculate total marks
        $results[$student_id]['total_marks'] += $marks;

        // Check if all marks are above 35 for pass status
        if ($marks < 35) {
            $results[$student_id]['pass_fail'] = 'Fail';
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
    <link rel="stylesheet" type="text/css" href="css/dashboardT.css">
    <title>Teacher Dashboard</title>
</head>
<body>
    <div class="message"><h2>Dashboard</h2></div>
    <div class="welcome-container">
        <h2>Welcome, </h2><h3><?php echo htmlspecialchars($full_name); ?></h3>
    </div>
    <div class="container">
        <h3>Student Result List</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Courses and Marks</th>
                        <th>Total Marks</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $student_id => $student_data): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student_data['student_name']); ?></td>
                            <td>
                                <div class="course-table">
                                    <?php foreach ($student_data['courses'] as $course_name => $course_data): ?>
                                        <div class="course-card">
                                            <strong><?php echo htmlspecialchars($course_name); ?></strong>
                                            <div class="marks">Marks: <?php echo htmlspecialchars($course_data['marks']); ?></div>
                                            <div class="grade">Grade: <?php echo htmlspecialchars($course_data['grade']); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($student_data['total_marks']); ?></td>
                            <td><?php echo htmlspecialchars($student_data['pass_fail']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="buttons">
            <a href="enter_marks.php"><button>Enter Results</button></a>
            <a href="login.php?logout=true"><button>Logout</button></a>
        </div>
    </div>
</body>
</html>
