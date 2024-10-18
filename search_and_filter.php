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
        header("Location: search_and_filter.php");
        exit();
    }
}

// Handle list deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_list_id'])) {
    $delete_list_id = filter_input(INPUT_POST, 'delete_list_id', FILTER_SANITIZE_NUMBER_INT);
    $stmt = $pdo->prepare("DELETE FROM todo_lists WHERE id = ? AND user_id = ?");
    $stmt->execute([$delete_list_id, $_SESSION['user_id']]);
    header("Location: search_and_filter.php");
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search</title>
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    
    <h3>Search and Filter</h3>
    <form method="get" action="">
        <input type="text" name="search" placeholder="Search lists and tasks" value="<?php echo htmlspecialchars($search); ?>">
        <select name="filter">
            <option value="">All Lists</option>
            <option value="completed" <?php echo $filter === 'completed' ? 'selected' : ''; ?>>Completed Lists</option>
            <option value="incomplete" <?php echo $filter === 'incomplete' ? 'selected' : ''; ?>>Incomplete Lists</option>
        </select>
        <input type="submit" value="Search and Filter">
    </form>

    <h3>Your Todo Lists</h3>
    <?php if (empty($todo_lists)): ?>
        <p>No todo lists found.</p>
    <?php else: ?>
        <ul>
        <?php foreach ($todo_lists as $list): ?>
            <li>
                <?php echo htmlspecialchars($list['title']); ?>
                (<?php echo $list['completed_count']; ?>/<?php echo $list['task_count']; ?> tasks completed)
                <form method="post" action="" style="display: inline;">
                    <input type="hidden" name="delete_list_id" value="<?php echo $list['id']; ?>">
                    <input type="submit" value="Delete" onclick="return confirm('Are you sure you want to delete this list?');">
                </form>
                <a href="view_list.php?id=<?php echo $list['id']; ?>">View Tasks</a>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h3>Create New Todo List</h3>
    <form method="post" action="">
        <input type="text" name="new_list_title" placeholder="Enter list title" required>
        <input type="submit" value="Create List">
    </form>

    <p><a href="user_dashboard.php">Back to Dashboard</a></p>
</body>
</html>
