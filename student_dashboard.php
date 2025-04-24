<?php
include 'config/config.php';
session_start();

// Define allowed user IDs for accessing the teacher dashboard
$allowed_user_ids = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,
                    43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,
                    83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,
                    118,119,120,121,122,123,124,125,126,128]; // Replace with actual allowed user IDs

// Check if the user is logged in, has a role of 'teacher', and is allowed to access
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] != 'student' || !in_array($_SESSION['user_id'], $allowed_user_ids)) {
    $_SESSION['access_error'] = "You do not have access to the student dashboard. Please contact admin";
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

// Query to fetch the full name of the student
$full_name_sql = "SELECT full_name FROM students WHERE user_id = '$user_id'";
$full_name_result = $conn->query($full_name_sql);

if ($full_name_result && $full_name_result->num_rows == 1) {
    $full_name_row = $full_name_result->fetch_assoc();
    $full_name = $full_name_row['full_name'];
} else {
    echo "Error fetching full name: " . $conn->error;
    exit();
}

// Query to fetch results for the student including credit points
$sql = "SELECT c.course_name, c.credit_points, r.marks, r.grade 
        FROM results r 
        JOIN courses c ON r.course_id = c.course_id 
        JOIN students s ON r.student_id = s.student_id
        WHERE s.user_id = '$user_id'";

$result = $conn->query($sql);

// Check if query was successful
if (!$result) {
    echo "Error fetching results: " . $conn->error;
    exit();
}

$results = [];
$total_marks = 0;
$total_credit_points = 0;
$total_grade_points = 0;

while ($row = $result->fetch_assoc()) {
    $results[] = $row;
    $total_marks += $row['marks'];
    $total_credit_points += $row['credit_points'];
    // Assuming grade is out of 10 and we calculate grade points accordingly
    $grade_points = 0;

    // Map marks to grade points
    if ($row['marks'] >= 90) {
        $grade_points = 10;
    } elseif ($row['marks'] >= 80) {
        $grade_points = 9;
    } elseif ($row['marks'] >= 70) {
        $grade_points = 8;
    } elseif ($row['marks'] >= 60) {
        $grade_points = 7;
    } elseif ($row['marks'] >= 50) {
        $grade_points = 6;
    } elseif ($row['marks'] >= 40) {
        $grade_points = 5;
    } elseif ($row['marks'] >= 30) {
        $grade_points = 4;
    } elseif ($row['marks'] >= 20) {
        $grade_points = 3;
    } elseif ($row['marks'] >= 10) {
        $grade_points = 2;
    } else {
        $grade_points = 1;
    }

    $total_grade_points += $row['credit_points'] * $grade_points;
}

if ($total_credit_points > 0) {
    $average_marks = number_format($total_marks / count($results), 1);
    $cgpa = number_format($total_grade_points / $total_credit_points, 2);
} else {
    $average_marks = 0; // Set default value if no courses found
    $cgpa = 0; // Set default value if no courses found
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" xml:lang="en">
<head>
    <title>Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/dashboardS.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
</head>
<body>
    <header>
    <div class="message"><h2>Dashboard</h2></div>
    </header>
    <div class="welcome-container">
        <img src="img/student.png" class="logo" alt="Logo">
        <h2>Welcome,</h2><h3><?php echo htmlspecialchars($full_name); ?></h3>
    </div>
    <div class="container">
        <div class="box">
            <h>Congrats!🎉🎉 You did well</h>
            <div class="charts">
                <div class="chart-container">
                    <canvas id="averageChart"></canvas>
                    <h1>Average Score  <?php echo htmlspecialchars($average_marks); ?>%</h1>
                </div>
                <div class="chart-container">
                    <canvas id="cgpaChart"></canvas>
                    <h1>CGPA </br> <?php echo htmlspecialchars($cgpa); ?> / 10</h1>
                </div>
            </div>
        </div>
        <div class="box">
            <div class="chart-container bar-chart-container">
                <canvas id="marksChart"></canvas>
                <h1>Subject Marks</h1>
            </div>
            <div class="buttons">
                <a href="view_results.php?user_id=<?php echo htmlspecialchars($_SESSION['user_id']); ?>"><button>View Marks</button></a>
                <a href="logout.php"><button>Logout</button></a>
            </div>
        </div>
    </div>

    <script>
        var averageMarks = <?php echo $average_marks; ?>;
        var cgpa = <?php echo $cgpa; ?>;
        var marksData = <?php echo json_encode(array_column($results, 'marks')); ?>;
        var labels = <?php echo json_encode(array_column($results, 'course_name')); ?>;

        console.log("Average Marks:", averageMarks);
        console.log("CGPA:", cgpa);
        console.log("Marks Data:", marksData);

        var ctx = document.getElementById('averageChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [averageMarks, 100 - averageMarks],
                    backgroundColor: ['#4CAF50', '#e0e0e0'],
                    hoverBackgroundColor: ['#FF6384', '#E65C19'],
                    borderWidth: 1 // Adjust the width of the border
                }]
            },
            options: {
                title: {
                    display: false,
                    text: 'Average Marks'
                },
                animation: {
                    animateRotate: true,
                    animateScale: false
                }
            }
        });

        var ctx = document.getElementById('cgpaChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [cgpa, 10 - cgpa],
                    backgroundColor: ['#FF9800', '#e0e0e0'],
                    hoverBackgroundColor: ['#FF6384', '#36A2EB'],
                    borderWidth: 1 // Adjust the width of the border
                }]
            },
            options: {
                title: {
                    display: true,
                    text: 'CGPA'
                }
            }
        });

        var ctx = document.getElementById('marksChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Marks',
                    data: marksData,
                    backgroundColor: '#007bff'
                }]
            },
            options: {
                title: {
                    display: true,
                    text: 'Marks per Subject'
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 10 // Adjust tick step size as needed
                        },
                        grid: {
                            display: false // Hide y-axis grid lines
                        }
                    },
                    y: {
                        grid: {
                            display: true // Hide y-axis grid lines
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        font: {
                            size: 5 // Adjust font size as needed
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
