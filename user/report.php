<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// --- Auth guard ---
if (!is_logged_in() || !is_user()) {
    header('Location: ../login.php');
    exit();
}

// --- Require POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit();
}

// --- CSRF check ---
verify_csrf_token();

// --- Sanitize and validate inputs ---
$pet_id = isset($_POST['pet_id']) ? (int)$_POST['pet_id'] : 0;
$reason = trim($_POST['reason'] ?? '');
$reporter_id = (int)$_SESSION['user_id'];

if ($pet_id <= 0 || $reason === '') {
    // If validation fails, safely redirect back to dashboard 
    // since we don't have a reliable pet_id or reason.
    header('Location: dashboard.php');
    exit();
}

// --- Insert Report ---
try {
    $stmt = $pdo->prepare("
        INSERT INTO reports (reporter_id, pet_id, reason) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$reporter_id, $pet_id, $reason]);
    
    // Redirect back to pet_detail with success flag
    header('Location: pet_detail.php?id=' . $pet_id . '&reported=1');
    exit();
} catch (PDOException $e) {
    // In case of error (e.g. invalid pet_id foreign key), redirect to dashboard
    header('Location: dashboard.php');
    exit();
}
