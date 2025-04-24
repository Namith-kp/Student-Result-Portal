<?php
include 'config/config.php';
session_start();

if ($_SESSION['role'] != 'teacher') {
    header("Location: index.html");
    exit();
}

// Fetch students in alphabetical order
$students_sql = "SELECT student_id, full_name FROM students ORDER BY full_name";
$students_result = $conn->query($students_sql);

// Fetch courses in alphabetical order
$courses_sql = "SELECT course_id, course_name FROM courses ORDER BY course_name";
$courses_result = $conn->query($courses_sql);
$error = '';
$success = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $course_id = $_POST['course_id'];
    $marks = $_POST['marks'];
    $grade = $marks >= 90 ? 'A+' : ($marks >= 80 ? 'A' : ($marks >= 70 ? 'B+' : ($marks >= 60 ? 'B' : 'C')));

    $sql = "INSERT INTO results (student_id, course_id, marks, grade) VALUES ('$student_id', '$course_id', '$marks', '$grade')";

    if ($conn->query($sql) === TRUE) {
        $success = "Marks entered successfully";
    } else {
        $error = "Error: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}
?>



<!DOCTYPE html>
<html>
<head>
    <title>Enter Results</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/enter-results.css">
</head>
<body>
    <div class="form-container">
        <h2>Enter Marks</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php elseif ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form action="enter_marks.php" method="post">
                <label for="student_id">Student Name:</label>
                <select class="form-control" id="student_id" name="student_id" required>
                    <option value="" disabled selected>Select a student</option>
                    <?php while ($student = $students_result->fetch_assoc()): ?>
                        <option value="<?php echo $student['student_id']; ?>"><?php echo htmlspecialchars($student['full_name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <label for="course_id">Course Name:</label>
                <select class="form-control" id="course_id" name="course_id" required>
                    <option value="" disabled selected>Select a course</option>
                    <?php while ($course = $courses_result->fetch_assoc()): ?>
                        <option value="<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['course_name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <label for="marks">Marks:</label>
                <input type="number" class="form-control" id="marks" name="marks" required>
            
            <button type="submit" class="btn btn-primary">Submit</button>
            
        </form>
        <a href="teacher_dashboard.php"><i class="fa-solid fa-arrow-left"></i>Back to Dashboard</a>
    </div>
</body>
</html>
