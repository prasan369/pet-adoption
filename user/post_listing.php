<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// --- Auth guard ---
if (!is_logged_in() || !is_user()) {
    header('Location: ../login.php');
    exit();
}

$errors = [];
$old    = [];

// --- Handle POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    verify_csrf_token();

    // Collect & sanitize inputs
    $old['name']        = $name        = trim($_POST['name']        ?? '');
    $old['breed']       = $breed       = trim($_POST['breed']       ?? '');
    $old['age']         = $age         = (int)($_POST['age']        ?? 0);
    $old['gender']      = $gender      = trim($_POST['gender']      ?? '');
    $old['city']        = $city        = trim($_POST['city']        ?? '');
    $old['description'] = $description = trim($_POST['description'] ?? '');

    // Validate required text fields
    if ($name === '')        $errors[] = 'Dog name is required.';
    if ($breed === '')       $errors[] = 'Breed is required.';
    if ($age < 0 || $age > 30) $errors[] = 'Age must be between 0 and 30.';
    if (!in_array($gender, ['male', 'female'], true)) $errors[] = 'Select a valid gender.';
    if ($city === '')        $errors[] = 'City is required.';
    if ($description === '') $errors[] = 'Description is required.';

    // Validate photos
    $uploaded_paths = [];
    $allowed_mimes  = ['image/jpeg', 'image/png', 'image/webp'];
    $mime_to_ext    = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $upload_dir     = '../uploads/pets/';

    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (empty($_FILES['photos']['name'][0])) {
        $errors[] = 'At least one photo is required.';
    } else {
        $file_count = count($_FILES['photos']['name']);
        if ($file_count > 5) {
            $errors[] = 'You can upload a maximum of 5 photos.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);

            for ($i = 0; $i < $file_count; $i++) {
                if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) {
                    $errors[] = 'Upload error on file #' . ($i + 1) . '.';
                    continue;
                }

                $tmp  = $_FILES['photos']['tmp_name'][$i];
                $mime = $finfo->file($tmp);

                if (!in_array($mime, $allowed_mimes, true)) {
                    $errors[] = 'File #' . ($i + 1) . ': only JPG, PNG, and WebP are allowed.';
                    continue;
                }

                $ext      = $mime_to_ext[$mime];
                $new_name = uniqid('pet_', true) . '.' . $ext;
                $dest     = $upload_dir . $new_name;

                if (move_uploaded_file($tmp, $dest)) {
                    $uploaded_paths[] = 'uploads/pets/' . $new_name;
                } else {
                    $errors[] = 'Failed to save file #' . ($i + 1) . '.';
                }
            }
        }
    }

    // Insert into DB if no errors
    if (empty($errors) && !empty($uploaded_paths)) {
        try {
            $pdo->beginTransaction();

            // Insert pet
            $stmt = $pdo->prepare(
                "INSERT INTO pets (name, type, breed, age, gender, description, location, owner_id, status)
                 VALUES (?, 'Dog', ?, ?, ?, ?, ?, ?, 'active')"
            );
            $owner_id = $_SESSION['user_id'];
            $stmt->execute([$name, $breed, $age, $gender, $description, $city, $owner_id]);
            $pet_id = (int)$pdo->lastInsertId();

            // Insert photos
            $photo_stmt = $pdo->prepare(
                "INSERT INTO pet_photos (pet_id, photo_path, is_primary) VALUES (?, ?, ?)"
            );
            foreach ($uploaded_paths as $index => $path) {
                $is_primary = ($index === 0) ? 1 : 0;
                $photo_stmt->execute([$pet_id, $path, $is_primary]);
            }

            $pdo->commit();

            $_SESSION['success'] = 'Your dog has been listed for adoption!';
            header('Location: dashboard.php');
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();

            // Clean up uploaded files on failure
            foreach ($uploaded_paths as $path) {
                $full = '../' . $path;
                if (file_exists($full)) unlink($full);
            }

            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}

$user = get_user_by_id($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Dog — PawMarket</title>
    <meta name="description" content="List your dog for adoption on PawMarket.">
    <link rel="stylesheet" href="../css/marketplace.css">
</head>
<body class="marketplace">

    <!-- Navigation -->
    <nav class="mp-nav">
        <div class="mp-nav-inner">
            <a href="dashboard.php" class="mp-logo">🐾 Paw<span>Market</span></a>
            <button class="mp-hamburger" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
            <ul class="mp-nav-links">
                <li><a href="dashboard.php">Browse Dogs</a></li>
                <li><a href="my_requests.php">My Requests</a></li>
                <li><a href="my_listings.php">My Listings</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="post_listing.php" class="mp-nav-cta active">+ Post a Dog</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="../logout.php" class="mp-nav-logout">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main class="mp-container">
        <a href="dashboard.php" class="mp-back">← Back to Browse</a>

        <div class="mp-form-wrapper">
            <div class="mp-form-card">
                <h2>🐶 Post a Dog for Adoption</h2>

                <!-- Errors -->
                <?php if (!empty($errors)): ?>
                    <div class="mp-alert mp-alert-error">
                        <span>⚠️</span>
                        <div>
                            <?php foreach ($errors as $e): ?>
                                <div><?php echo htmlspecialchars($e); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="postForm">
                    <?php echo csrf_field(); ?>

                    <div class="mp-form-row">
                        <div class="mp-form-group">
                            <label for="dogName">Dog Name</label>
                            <input type="text" id="dogName" name="name" placeholder="e.g. Buddy"
                                   value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>" required>
                        </div>
                        <div class="mp-form-group">
                            <label for="dogBreed">Breed</label>
                            <input type="text" id="dogBreed" name="breed" placeholder="e.g. Golden Retriever"
                                   value="<?php echo htmlspecialchars($old['breed'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="mp-form-row">
                        <div class="mp-form-group">
                            <label for="dogAge">Age (years)</label>
                            <input type="number" id="dogAge" name="age" min="0" max="30" placeholder="e.g. 3"
                                   value="<?php echo htmlspecialchars($old['age'] ?? ''); ?>" required>
                        </div>
                        <div class="mp-form-group">
                            <label for="dogGender">Gender</label>
                            <select id="dogGender" name="gender" required>
                                <option value="" disabled <?php echo empty($old['gender']) ? 'selected' : ''; ?>>Select gender</option>
                                <option value="male" <?php echo ($old['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($old['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                    </div>

                    <div class="mp-form-group">
                        <label for="dogCity">City</label>
                        <input type="text" id="dogCity" name="city" placeholder="e.g. Kathmandu"
                               value="<?php echo htmlspecialchars($old['city'] ?? ''); ?>" required>
                    </div>

                    <div class="mp-form-group">
                        <label for="dogDesc">Description</label>
                        <textarea id="dogDesc" name="description" placeholder="Tell potential adopters about this dog — personality, health, habits…"
                                  required><?php echo htmlspecialchars($old['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="mp-form-group">
                        <label>Photos (up to 5 — first is primary)</label>
                        <div class="mp-upload-zone">
                            <input type="file" id="petPhotos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required>
                            <span class="mp-upload-icon">📷</span>
                            <p class="mp-upload-text"><strong>Click to upload</strong> or drag photos here</p>
                            <p class="mp-upload-hint">JPG, PNG or WebP · Max 5 files</p>
                        </div>
                        <div class="mp-preview-grid" id="previewGrid"></div>
                    </div>

                    <button type="submit" class="mp-btn mp-btn-primary" style="width:100%;">
                        🐾 Publish Listing
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script src="../js/marketplace.js"></script>
</body>
</html>
