<?php
include 'config/config.php';
session_start();

// Check if student_id is provided in the URL
if (!isset($_GET['user_id'])) {
    echo "Student ID not provided.";
    exit();
}

$user_id = $_GET['user_id'];

// Fetch distinct courses with their latest results for the student
$sql = "SELECT c.course_name, MAX(r.marks) as marks, r.grade 
        FROM results r 
        JOIN courses c ON r.course_id = c.course_id 
        JOIN students s ON r.student_id = s.student_id
        WHERE s.user_id = '$user_id' 
        GROUP BY c.course_name";

$result = $conn->query($sql);

if (!$result) {
    echo "Error: " . $conn->error;
    exit();
}

$results = [];
while ($row = $result->fetch_assoc()) {
    $results[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Results</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/view-results.css">
</head>
<body>
    <div class="container">
        <h2>Result Marksheet</h2>
        <?php if (count($results) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Courses</th>
                            <th>Marks Obtained</th>
                            <th>Grade</th>
                            <th>Status</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($result['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($result['marks']); ?></td>
                                <td><?php echo htmlspecialchars($result['grade']); ?></td>
                                <td><div class = "message"><?php echo $result['marks'] >= 35 ? 'Pass' : 'Fail'; ?></div></td>
                                
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No results found.</p>
        <?php endif; ?>
        <a href="student_dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>

