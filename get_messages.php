<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

$sender_id = filter_input(INPUT_GET, 'sender_id', FILTER_VALIDATE_INT);
$receiver_id = filter_input(INPUT_GET, 'receiver_id', FILTER_VALIDATE_INT);

if (!$sender_id || !$receiver_id) {
    echo json_encode([]); // Return empty array if IDs are not valid
    exit;
}

// Ensure the request is coming from the logged-in user or admin for security
if (!isset($_SESSION['id']) || ($_SESSION['id'] != $sender_id && $_SESSION['id'] != $receiver_id)) {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

// Fetch messages where (sender_id = X and receiver_id = Y) OR (sender_id = Y and receiver_id = X)
$sql = "SELECT sender_id, receiver_id, message, timestamp FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY timestamp ASC";

$messages = [];
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_bind_result($stmt, $s_id, $r_id, $msg, $ts);
        while (mysqli_stmt_fetch($stmt)) {
            $messages[] = [
                'sender_id' => $s_id,
                'receiver_id' => $r_id,
                'message' => $msg,
                'timestamp' => $ts
            ];
        }
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($conn);

echo json_encode($messages);
?>