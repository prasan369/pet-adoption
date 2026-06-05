<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// ── Auth guard ────────────────────────────────────────────────────────────────
if (!is_logged_in() || !is_user()) {
    header('Location: ../login.php');
    exit();
}

// ── Only accept POST ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: messages.php');
    exit();
}

// ── CSRF check ────────────────────────────────────────────────────────────────
verify_csrf_token();

$me          = (int)$_SESSION['user_id'];
$receiver_id = (int)($_POST['receiver_id'] ?? 0);
$pet_id      = (int)($_POST['pet_id']      ?? 0);
$body        = trim($_POST['message']      ?? '');

// ── Validate inputs ───────────────────────────────────────────────────────────
if ($receiver_id <= 0 || $pet_id <= 0 || $body === '' || $receiver_id === $me) {
    $_SESSION['msg_error'] = 'Invalid message. Please try again.';
    header("Location: messages.php?to={$receiver_id}&pet_id={$pet_id}");
    exit();
}

// ── Verify the receiver exists ────────────────────────────────────────────────
$chk_user = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$chk_user->execute([$receiver_id]);
if (!$chk_user->fetch()) {
    $_SESSION['msg_error'] = 'Recipient not found.';
    header('Location: messages.php');
    exit();
}

// ── Verify the pet exists ─────────────────────────────────────────────────────
$chk_pet = $pdo->prepare("SELECT id FROM pets WHERE id = ?");
$chk_pet->execute([$pet_id]);
if (!$chk_pet->fetch()) {
    $_SESSION['msg_error'] = 'Pet not found.';
    header('Location: messages.php');
    exit();
}

// ── Insert message ────────────────────────────────────────────────────────────
$ins = $pdo->prepare(
    "INSERT INTO messages (sender_id, receiver_id, pet_id, message)
     VALUES (?, ?, ?, ?)"
);
$ins->execute([$me, $receiver_id, $pet_id, $body]);

// ── Redirect back to the thread ───────────────────────────────────────────────
header("Location: messages.php?to={$receiver_id}&pet_id={$pet_id}");
exit();
