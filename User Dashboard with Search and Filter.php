<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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

// Handle search and filter
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
$filter = filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_STRING);

$query = "SELECT tl.*, COUNT(t.id) as task_count, SUM(t.is_completed) as completed_count 
          FROM todo_lists tl 
          LEFT JOIN tasks t ON tl.id = t.list_id 
          WHERE tl.user_id = ?";
$params = [$_SESSION['user_id']];

if (!empty($search)) {
    $query .= " AND (tl.title LIKE ? OR t.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " GROUP BY tl.id";

if ($filter === 'completed') {
    $query .= " HAVING completed_count = task_count AND task_count > 0";
} elseif ($filter === 'incomplete') {
    $query .= " HAVING completed_count < task_count OR task_count = 0";
}

$query .= " ORDER BY tl.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$todo_lists = $stmt->fetchAll();
?>
