<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in() || !is_admin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$users_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$users = $users_result->fetch_assoc()['count'];

$pets_result = $conn->query("SELECT COUNT(*) as count FROM pets");
$pets = $pets_result->fetch_assoc()['count'];

$adoptions_result = $conn->query("SELECT COUNT(*) as count FROM pets WHERE is_adopted = TRUE");
$adoptions = $adoptions_result->fetch_assoc()['count'];

$pending_result = $conn->query("SELECT COUNT(*) as count FROM adoption_requests WHERE status = 'pending'");
$pending = $pending_result->fetch_assoc()['count'];

echo json_encode([
    'users' => $users,
    'pets' => $pets,
    'adoptions' => $adoptions,
    'pending' => $pending
]);
?>
