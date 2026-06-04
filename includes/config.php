<?php
// Database configuration
$servername = "localhost";
$db_username = "root";  // XAMPP default username
$db_password = "";      // XAMPP has no password by default
$dbname = "pet_adoption";

// Create connection (mysqli — used by existing admin pages)
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// PDO connection — used by new marketplace pages
try {
    $pdo = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
        $db_username,
        $db_password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("PDO Connection failed: " . $e->getMessage());
}

// Session start
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- CSRF Token Helpers ---

/**
 * Generate or retrieve a CSRF token for the current session.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Return a hidden input element containing the CSRF token.
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Verify the submitted CSRF token matches the session token.
 * Halts execution with 403 on mismatch.
 */
function verify_csrf_token(): void {
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
}
?>
