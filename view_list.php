<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if list ID is provided
if (!isset($_GET['id'])) {
    header("Location: user_dashboard.php");
    exit();
}

$list_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

// Fetch list details
$stmt = $pdo->prepare("SELECT * FROM todo_lists WHERE id = ? AND user_id = ?");
$stmt->execute([$list_id, $_SESSION['user_id']]);
$list = $stmt->fetch();

if (!$list) {
    header("Location: dashboard.php");
    exit();
}

// Handle task actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_task'])) {
        $task_description = filter_input(INPUT_POST, 'task_description', FILTER_SANITIZE_STRING);
        if (!empty($task_description)) {
            $stmt = $pdo->prepare("INSERT INTO tasks (list_id, description) VALUES (?, ?)");
            $stmt->execute([$list_id, $task_description]);
        }
    } elseif (isset($_POST['toggle_task'])) {
        $task_id = filter_input(INPUT_POST, 'task_id', FILTER_SANITIZE_NUMBER_INT);
        $stmt = $pdo->prepare("UPDATE tasks SET is_completed = NOT is_completed WHERE id = ? AND list_id = ?");
        $stmt->execute([$task_id, $list_id]);
    } elseif (isset($_POST['delete_task'])) {
        $task_id = filter_input(INPUT_POST, 'task_id', FILTER_SANITIZE_NUMBER_INT);
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND list_id = ?");
        $stmt->execute([$task_id, $list_id]);
    }
    
    // Redirect to prevent form resubmission
    header("Location: view_list.php?id=" . $list_id);
    exit();
}

// Fetch tasks
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE list_id = ? ORDER BY created_at DESC");
$stmt->execute([$list_id]);
$tasks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($list['title']); ?> - Tasks</title>
</head>
<body>
    <h2><?php echo htmlspecialchars($list['title']); ?> - Tasks</h2>
    
    <h3>Add New Task</h3>
    <form method="post" action="">
        <input type="text" name="task_description" placeholder="Enter task description" required>
        <input type="submit" name="add_task" value="Add Task">
    </form>

    <h3>Tasks</h3>
    <?php if (empty($tasks)): ?>
        <p>No tasks in this list yet.</p>
    <?php else: ?>
        <ul>
        <?php foreach ($tasks as $task): ?>
            <li>
                <form method="post" action="" style="display: inline;">
                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                    <input type="checkbox" name="toggle_task" onchange="this.form.submit()" <?php echo $task['is_completed'] ? 'checked' : ''; ?>>
                </form>
                <?php echo htmlspecialchars($task['description']); ?>
                <form method="post" action="" style="display: inline;">
                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                    <input type="submit" name="delete_task" value="Delete" onclick="return confirm('Are you sure you want to delete this task?');">
                </form>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <p><a href="user_dashboard.php">Back to Dashboard</a></p>
</body>
</html>
