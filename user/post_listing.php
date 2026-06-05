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
    $old['area']        = $area        = trim($_POST['area']        ?? '');
    $old['description'] = $description = trim($_POST['description'] ?? '');

    // Safely cast coordinates — never insert raw strings
    $latitude  = isset($_POST['latitude'])  && $_POST['latitude']  !== '' ? (float)$_POST['latitude']  : null;
    $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;

    // Validate coordinate ranges
    if ($latitude !== null && ($latitude < -90 || $latitude > 90))   { $latitude = null; }
    if ($longitude !== null && ($longitude < -180 || $longitude > 180)) { $longitude = null; }

    // Validate required text fields
    if ($name === '')        $errors[] = 'Dog name is required.';
    if ($breed === '')       $errors[] = 'Breed is required.';
    if ($age < 0 || $age > 30) $errors[] = 'Age must be between 0 and 30.';
    if (!in_array($gender, ['male', 'female'], true)) $errors[] = 'Select a valid gender.';
    if ($area === '')        $errors[] = 'Please select a location from the suggestions.';
    if ($latitude === null || $longitude === null) $errors[] = 'Please select a location from the suggestions.';
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
                "INSERT INTO pets (name, type, breed, age, gender, description, location, area, latitude, longitude, owner_id, status)
                 VALUES (?, 'Dog', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')"
            );
            $owner_id = $_SESSION['user_id'];
            $stmt->execute([$name, $breed, $age, $gender, $description, $area, $area, $latitude, $longitude, $owner_id]);
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
    <link rel="stylesheet" href="../css/marketplace.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
                        <label for="area-search">Area / Neighbourhood</label>
                        <div style="position:relative;">
                            <input type="text"
                                   id="area-search"
                                   placeholder="Search your location e.g. Thamel, Kathmandu"
                                   autocomplete="off"
                                   value="<?php echo htmlspecialchars($old['area'] ?? ''); ?>">
                            <div id="suggestions" class="suggestions-dropdown"></div>
                        </div>
                        <input type="hidden" name="area"      id="area"      value="<?php echo htmlspecialchars($old['area'] ?? ''); ?>">
                        <input type="hidden" name="latitude"  id="latitude"  value="<?php echo htmlspecialchars($_POST['latitude']  ?? ''); ?>">
                        <input type="hidden" name="longitude" id="longitude" value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>">
                        <div id="location-map" style="height:300px;border-radius:10px;margin-top:10px;border:1px solid var(--border);"></div>
                        <p style="font-size:0.75rem;color:var(--text-muted);margin-top:6px;">📍 Search for your area above, then drag the pin to fine-tune</p>
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

    <script src="../js/marketplace.js?v=<?php echo time(); ?>"></script>
    <script>
    (function () {
        /* ---- Map setup ---- */
        const map = L.map('location-map').setView([27.7172, 85.3240], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        let marker = null;

        /* Restore pin on re-render (form error) */
        const savedLat = document.getElementById('latitude').value;
        const savedLng = document.getElementById('longitude').value;
        if (savedLat && savedLng) {
            const ll = L.latLng(parseFloat(savedLat), parseFloat(savedLng));
            marker = L.marker(ll, { draggable: true }).addTo(map);
            map.setView(ll, 15);
            marker.on('dragend', syncMarkerCoords);
        }

        function syncMarkerCoords() {
            const pos = marker.getLatLng();
            document.getElementById('latitude').value  = pos.lat;
            document.getElementById('longitude').value = pos.lng;
        }

        function placePin(latlng) {
            if (marker) {
                marker.setLatLng(latlng);
            } else {
                marker = L.marker(latlng, { draggable: true }).addTo(map);
                marker.on('dragend', syncMarkerCoords);
            }
            document.getElementById('latitude').value  = latlng.lat;
            document.getElementById('longitude').value = latlng.lng;
        }

        /* ---- Nominatim search ---- */
        const searchInput   = document.getElementById('area-search');
        const suggestionBox = document.getElementById('suggestions');
        const hiddenArea    = document.getElementById('area');
        let debounceTimer   = null;

        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const query = this.value.trim();

            /* Clear hidden area so validation fires if user edits after picking */
            hiddenArea.value = '';
            document.getElementById('latitude').value  = '';
            document.getElementById('longitude').value = '';

            if (query.length < 3) { suggestionBox.innerHTML = ''; return; }

            debounceTimer = setTimeout(async () => {
                try {
                    const url = 'https://nominatim.openstreetmap.org/search?'
                        + new URLSearchParams({ q: query, format: 'json', limit: 5, countrycodes: 'np' });
                    const res  = await fetch(url, { headers: { 'Accept-Language': 'en' } });
                    const data = await res.json();

                    suggestionBox.innerHTML = '';

                    if (data.length === 0) {
                        const none = document.createElement('div');
                        none.className   = 'suggestion-item suggestion-empty';
                        none.textContent = 'No results found in Nepal';
                        suggestionBox.appendChild(none);
                        return;
                    }

                    data.forEach(place => {
                        const div        = document.createElement('div');
                        div.className    = 'suggestion-item';

                        // Show short name: just first 3 parts of display_name
                        const parts = place.display_name.split(',');
                        const shortName = parts.slice(0, 3).join(',').trim();

                        div.textContent  = shortName;
                        div.title        = place.display_name; // full name on hover tooltip

                        div.addEventListener('click', () => {
                            const latlng = L.latLng(parseFloat(place.lat), parseFloat(place.lon));
                            map.setView(latlng, 15);
                            placePin(latlng);

                            /* Populate visible + hidden inputs */
                            searchInput.value  = shortName;
                            hiddenArea.value   = shortName;

                            /* Close dropdown */
                            suggestionBox.innerHTML = '';
                        });
                        suggestionBox.appendChild(div);
                    });
                } catch (err) {
                    console.warn('Nominatim error:', err);
                }
            }, 300);
        });

        /* Close dropdown when clicking outside */
        document.addEventListener('click', e => {
            if (!searchInput.contains(e.target) && !suggestionBox.contains(e.target)) {
                suggestionBox.innerHTML = '';
            }
        });

        /* ---- Client-side form validation ---- */
        document.getElementById('postForm').addEventListener('submit', function (e) {
            const lat = document.getElementById('latitude').value;
            const lng = document.getElementById('longitude').value;
            const area = hiddenArea.value.trim();

            if (!lat || !lng || !area) {
                e.preventDefault();
                /* Scroll to the search input and show an inline error */
                searchInput.focus();
                searchInput.style.borderColor = '#F02849';
                searchInput.style.boxShadow   = '0 0 0 3px rgba(240,40,73,0.2)';

                let errMsg = document.getElementById('location-error');
                if (!errMsg) {
                    errMsg           = document.createElement('p');
                    errMsg.id        = 'location-error';
                    errMsg.style.cssText = 'color:#F02849;font-size:0.82rem;margin-top:6px;';
                    searchInput.parentElement.insertAdjacentElement('afterend', errMsg);
                }
                errMsg.textContent = '⚠️ Please select a location from the suggestions.';
                searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        /* Clear error styling once user starts searching again */
        searchInput.addEventListener('focus', function () {
            this.style.borderColor = '';
            this.style.boxShadow   = '';
            const errMsg = document.getElementById('location-error');
            if (errMsg) errMsg.remove();
        });
    })();
    </script>
</body>
</html>
