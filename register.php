<?php
include 'config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if ($role == 'student') {
        $sql = "INSERT INTO students (name, email, password) VALUES ('$name', '$email', '$password')";
    } else if ($role == 'teacher') {
        $sql = "INSERT INTO teachers (name, email, password) VALUES ('$name', '$email', '$password')";
    }

    if ($conn->query($sql) === TRUE) {
        echo "Registration successful";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}
?>
