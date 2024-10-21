<?php
session_start();
require_once 'db_connection.php';

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = sanitize_input($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Both email and password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            header("Location: user_dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    }
}