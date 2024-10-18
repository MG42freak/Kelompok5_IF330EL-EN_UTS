<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user's todo lists
$stmt = $pdo->prepare("SELECT * FROM todo_lists WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$todo_lists = $stmt->fetchAll();

// Handle new list creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_list_title'])) {
    $new_list_title = filter_input(INPUT_POST, 'new_list_title', FILTER_SANITIZE_STRING);
    if (!empty($new_list_title)) {
        $stmt = $pdo->prepare("INSERT INTO todo_lists (user_id, title) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $new_list_title]);
        header("Location: dashboard.php");
        exit();
    }
}

// Handle list deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_list_id'])) {
    $delete_list_id = filter_input(INPUT_POST, 'delete_list_id', FILTER_SANITIZE_NUMBER_INT);
    $stmt = $pdo->prepare("DELETE FROM todo_lists WHERE id = ? AND user_id = ?");
    $stmt->execute([$delete_list_id, $_SESSION['user_id']]);
    header("Location: dashboard.php");
    exit();
}
?>