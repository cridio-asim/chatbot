<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sender_id = filter_input(INPUT_POST, 'sender_id', FILTER_VALIDATE_INT);
    $receiver_id = filter_input(INPUT_POST, 'receiver_id', FILTER_VALIDATE_INT);
    $message = trim($_POST['message']);

    // Basic validation
    if (!$sender_id || !$receiver_id || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
        exit;
    }

    // Ensure the sender is the logged-in user or admin
    if (!isset($_SESSION['id']) || $_SESSION['id'] != $sender_id) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized sender.']);
        exit;
    }

    $sql = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "iis", $sender_id, $receiver_id, $message);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'Message sent.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to send message: ' . mysqli_error($conn)]);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement: ' . mysqli_error($conn)]);
    }
    mysqli_close($conn);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>