<?php
include 'config/config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['role'])) {
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

// Query to fetch results for the student using user_id to find student_id
$sql = "SELECT c.course_name, r.marks, r.grade, c.credit_points 
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
$total_grade_points = 0;
$total_credit_points = 0;

while ($row = $result->fetch_assoc()) {
    // Calculate Grade Points for each subject
    $credit_points = $row['credit_points'];
    $grade = $row['grade'];

    // Convert grades to grade points
    switch ($grade) {
        case 'A+':
            $grade_points = 10;
            break;
        case 'A':
            $grade_points = 9;
            break;
        case 'B+':
            $grade_points = 8;
            break;
        case 'B':
            $grade_points = 7;
            break;
        case 'C':
            $grade_points = 6;
            break;
        case 'P':
            $grade_points = 5;
            break;
        case 'F':
            $grade_points = 0;
            break;
        default:
            $grade_points = 0; // Handle other grades as needed
            break;
    }

    // Calculate total grade points and credit points
    $total_grade_points += $grade_points * $credit_points;
    $total_credit_points += $credit_points;

    // Store results for display
    $results[] = [
        'course_name' => $row['course_name'],
        'marks' => $row['marks'],
        'grade' => $row['grade']
    ];
}

// Calculate CGPA
if ($total_credit_points > 0) {
    $cgpa = number_format($total_grade_points / $total_credit_points, 1);
} else {
    $cgpa = 0; // Default value if no courses found or credits are zero
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" xml:lang="en">
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" type="text/css" href="css/dashboardS.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
</head>
<body>
    <div class="message"><h2 style="color: white;">Dashboard</h2></div>
    <div class="welcome-container">
        <img src="img/student.png" class="logo" alt="Logo">
        <h3 style="color: white;">Welcome, <?php echo htmlspecialchars($full_name); ?></h3>
    </div>
    <div class="container">
        <div class="left-container">
            <div class="charts">
                <div class="chart-container">
                    <canvas id="averageChart"></canvas>
                    <h1>Average Score = <?php echo htmlspecialchars($average_marks); ?>%</h1>
                </div>
                <div class="chart-container">
                    <canvas id="cgpaChart"></canvas>
                    <h1>CGPA = <?php echo htmlspecialchars($cgpa); ?> / 10</h1>
                </div>
            </div>
            <div class="buttons">
                <a href="view_results.php?user_id=<?php echo htmlspecialchars($_SESSION['user_id']); ?>"><button>View Marks</button></a>
                <a href="login.php"><button>Logout</button></a>
            </div>
        </div>
        <div class="right-container">
            <div class="chart-container bar-chart-container">
                <canvas id="marksChart"></canvas>
                <h1>Subject Marks</h1>
            </div>
        </div>
    </div>

    <script>
        var averageMarks = <?php echo $average_marks; ?>;
        var cgpa = <?php echo $cgpa; ?>;
        var marksData = <?php echo json_encode(array_column($results, 'marks')); ?>;
        var labels = <?php echo json_encode(array_column($results, 'course_name')); ?>;

        var ctx = document.getElementById('averageChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [averageMarks, 100 - averageMarks],
                    backgroundColor: ['#4CAF50', '#e0e0e0'],
                    hoverBackgroundColor: ['#FF6384', '#E65C19'],
                    borderWidth: 1
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
                    borderWidth: 1
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
                            stepSize: 10
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        grid: {
                            display: true
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
                            size: 5
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
