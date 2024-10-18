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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    
    <h3>Your Todo Lists</h3>
    <?php if (empty($todo_lists)): ?>
        <p>You don't have any todo lists yet. Create one below!</p>
    <?php else: ?>
        <ul>
        <?php foreach ($todo_lists as $list): ?>
            <li>
                <?php echo htmlspecialchars($list['title']); ?>
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

    <p><a href="logout.php">Logout</a></p>
</body>
</html>
