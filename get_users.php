<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Only allow admin to access this file
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['is_admin'] == 0) {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

$users = [];
$sql = "SELECT id, username FROM users WHERE is_admin = 0 ORDER BY username ASC"; // Select non-admin users

if ($result = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    mysqli_free_result($result);
}
mysqli_close($conn);

echo json_encode($users);
?>