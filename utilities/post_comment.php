<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    http_response_code(400);
    exit('Bad request');
}

$text = trim((string)($_POST['comment'] ?? ''));
$rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));

if ($text === '') {
    header('Location: /hatrox-project/public/about.php?err=1#testimonialForm');
    exit;
}

$_SESSION['testimonial_draft'] = [
    'comment' => $text,
    'rating'  => $rating,
];

if (empty($_SESSION['user_id'])) {
    $loginRedirect = '/hatrox-project/public/login.php?redirect=' . urlencode('/hatrox-project/public/about.php#testimonialForm');
    header('Location: ' . $loginRedirect);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$name = trim((string)($_SESSION['full_name'] ?? ''));
$email = trim((string)($_SESSION['email'] ?? ''));

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if ($stmt = $mysqli->prepare('SELECT full_name, email FROM users WHERE id = ?')) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($dbName, $dbEmail);
        if ($stmt->fetch()) {
            $name  = (string)$dbName;
            $email = (string)$dbEmail;
        }
        $stmt->close();
    }
}

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /hatrox-project/public/about.php?err=1#testimonialForm');
    exit;
}

$hasCompletedOrder = false;
if ($stmt = $mysqli->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = "completed"')) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($cnt);
    if ($stmt->fetch() && (int)$cnt > 0) {
        $hasCompletedOrder = true;
    }
    $stmt->close();
}

if (!$hasCompletedOrder) {
    header('Location: /hatrox-project/public/about.php?err=purchase#testimonialForm');
    exit;
}

if ($stmt = $mysqli->prepare('INSERT INTO comments (user_id, user_name, email, comment_text, rating, is_approved) VALUES (?, ?, ?, ?, ?, 0)')) {
    $stmt->bind_param('isssi', $user_id, $name, $email, $text, $rating);
    $stmt->execute();
    $stmt->close();
}

unset($_SESSION['testimonial_draft']);

header('Location: /hatrox-project/public/about.php?submitted=1#testimonialForm');
exit;

?>

