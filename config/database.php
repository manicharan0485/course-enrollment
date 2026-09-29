<?php
// database.php - Database connection file

// Define database credentials
$host = "127.0.0.1";
$user = "root";
$password = " ";
$dbname = "course_enrollment";

// Create mysqli connection
$conn = new mysqli('127.0.0.1', 'root', ' ', 'course_enrollment');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Return the connection object
return $conn;
?>