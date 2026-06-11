<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../login.php');
    exit();
}

$admin = get_user_by_id($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name        = trim($_POST['name']);
        $type        = trim($_POST['type']);
        $breed       = trim($_POST['breed']);
        $age         = (int)$_POST['age'];
        $gender      = trim($_POST['gender']);
        $description = trim($_POST['description']);
        $location    = trim($_POST['area'] ?? $_POST['location'] ?? '');
        $latitude    = !empty($_POST['latitude'])  ? (float)$_POST['latitude']  : null;
        $longitude   = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;

        $filename = '';
        if (isset($_FILES['pet_image']) && $_FILES['pet_image']['error'] === 0) {
            $finfo   = finfo_open(FILEINFO_MIME_TYPE);
            $mime    = finfo_file($finfo, $_FILES['pet_image']['tmp_name']);
            finfo_close($finfo);
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

            if (in_array($mime, $allowed)) {
                $ext      = strtolower(pathinfo($_FILES['pet_image']['name'], PATHINFO_EXTENSION));
                $filename = uniqid() . '.' . $ext;
                if (!is_dir('../uploads/pets/')) mkdir('../uploads/pets/', 0755, true);
                move_uploaded_file($_FILES['pet_image']['tmp_name'], '../uploads/pets/' . $filename);
            }
        }

        $stmt = $pdo->prepare("INSERT INTO pets (name, type, breed, age, gender, description, location, area, latitude, longitude, owner_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $admin_id = $_SESSION['user_id'];
        $stmt->execute([$name, $type, $breed, $age, $gender, $description, $location, $location, $latitude, $longitude, $admin_id]);
        $pet_id = $pdo->lastInsertId();

        if ($filename) {
            $pdo->prepare("INSERT INTO pet_photos (pet_id, photo_path, is_primary) VALUES (?, ?, 1)")->execute([$pet_id, $filename]);
        }

        $_SESSION['success'] = 'Pet added successfully!';
        header('Location: manage_pets.php');
        exit();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $pet_id = (int)$_POST['pet_id'];
        $pdo->prepare("DELETE FROM pets WHERE id = ?")->execute([$pet_id]);
        $_SESSION['success'] = 'Pet deleted successfully!';
        header('Location: manage_pets.php');
        exit();
    }
}

$pets = $pdo->query("SELECT * FROM pets ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pets - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <style>
        #location-map { height: 260px; border-radius: 8px; margin-top: 8px; margin-bottom: 12px; border: 1px solid #3a3b3c; }
        .suggestions-dropdown { position:absolute; background:#2a2b2c; border:1px solid #3a3b3c; border-radius:8px; width:100%; z-index:9999; max-height:200px; overflow-y:auto; box-shadow:0 4px 20px rgba(0,0,0,0.4); top:100%; left:0; }
        .suggestion-item { padding:10px 14px; cursor:pointer; font-size:0.88rem; color:#e4e6eb; border-bottom:1px solid #3a3b3c; }
        .suggestion-item:last-child { border-bottom:none; }
        .suggestion-item:hover { background:#3a3b3c; color:#fff; }
        .location-wrap { position:relative; flex:1; min-width:200px; }
    </style>
</head>
<body class="admin-page">
<div class="admin-container">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>🐾 PetAdopt Admin</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="manage_pets.php" class="active">Manage Pets</a></li>
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="../logout.php" class="logout-link">Logout</a></li>
        </ul>
    </aside>

    <main class="admin-content">
        <div class="top-bar"><h1>Manage Pets</h1></div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- ADD PET FORM -->
        <div class="content-section">
            <h2>Add New Pet</h2>
            <form method="POST" enctype="multipart/form-data" class="form-inline">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <input type="text" name="name" placeholder="Pet Name" required>
                    <input type="text" name="type" placeholder="Type (Dog, Cat, etc)" required>
                    <input type="text" name="breed" placeholder="Breed" required>
                </div>
                <div class="form-row">
                    <input type="number" name="age" placeholder="Age" required>
                    <select name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>

                <!-- LOCATION WITH MAP -->
                <div class="form-row">
                    <div class="location-wrap">
                        <input type="text" id="area-search" placeholder="Search location e.g. Thamel, Kathmandu" autocomplete="off" style="width:100%;">
                        <div id="suggestions" class="suggestions-dropdown"></div>
                    </div>
                </div>
                <input type="hidden" name="area" id="area">
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <div id="location-map"></div>

                <div class="form-row">
                    <input type="file" name="pet_image" accept="image/jpeg,image/png,image/webp" required>
                </div>
                <textarea name="description" placeholder="Description" rows="3" required></textarea>
                <button type="submit" class="btn btn-primary">Add Pet</button>
            </form>
        </div>

        <!-- PETS TABLE -->
        <div class="content-section">
            <h2>All Pets</h2>
            <table class="pets-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Breed</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pets as $pet): ?>
                        <?php
                            $photoStmt = $pdo->prepare("SELECT photo_path FROM pet_photos WHERE pet_id = ? AND is_primary = 1 LIMIT 1");
                            $photoStmt->execute([$pet['id']]);
                            $photo  = $photoStmt->fetchColumn();
                            $imgSrc = $photo
                                ? '../uploads/pets/' . htmlspecialchars($photo)
                                : 'https://placehold.co/60x60/3a3b3c/8a8d91?text=No+Img';
                        ?>
                        <tr>
                            <td><?php echo $pet['id']; ?></td>
                            <td><img src="<?php echo $imgSrc; ?>" style="width:60px;height:60px;object-fit:cover;border-radius:8px;" alt="<?php echo htmlspecialchars($pet['name']); ?>"></td>
                            <td><?php echo htmlspecialchars($pet['name']); ?></td>
                            <td><?php echo htmlspecialchars($pet['type'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($pet['breed']); ?></td>
                            <td><?php echo $pet['age']; ?> yr</td>
                            <td><?php echo ucfirst(htmlspecialchars($pet['gender'] ?? '—')); ?></td>
                            <td><?php echo htmlspecialchars($pet['area'] ?? $pet['location'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($pet['status'] ?? 'active'); ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                                    <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Delete this pet?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pets)): ?>
                        <tr><td colspan="10" style="text-align:center;padding:30px;color:#8a8d91;">No pets found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="../js/admin.js"></script>
<script>
// Leaflet map
const map = L.map('location-map').setView([27.7172, 85.3240], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

let marker;
map.on('click', function(e) {
    const { lat, lng } = e.latlng;
    if (marker) marker.setLatLng(e.latlng);
    else marker = L.marker(e.latlng).addTo(map);
    document.getElementById('latitude').value  = lat;
    document.getElementById('longitude').value = lng;
});

// Nominatim search
let debounceTimer;
const searchInput   = document.getElementById('area-search');
const suggestionBox = document.getElementById('suggestions');

searchInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    const query = this.value;
    if (query.length < 3) { suggestionBox.innerHTML = ''; return; }
    debounceTimer = setTimeout(async () => {
        const res  = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&limit=5&countrycodes=np`, { headers: { 'Accept-Language': 'en' } });
        const data = await res.json();
        suggestionBox.innerHTML = '';
        data.forEach(place => {
            const parts     = place.display_name.split(',');
            const shortName = parts.slice(0, 3).join(',').trim();
            const div       = document.createElement('div');
            div.className   = 'suggestion-item';
            div.textContent = shortName;
            div.title       = place.display_name;
            div.addEventListener('click', () => {
                document.getElementById('area').value      = shortName;
                document.getElementById('latitude').value  = place.lat;
                document.getElementById('longitude').value = place.lon;
                searchInput.value = shortName;
                suggestionBox.innerHTML = '';
                const latlng = [parseFloat(place.lat), parseFloat(place.lon)];
                map.setView(latlng, 15);
                if (marker) marker.setLatLng(latlng);
                else marker = L.marker(latlng).addTo(map);
            });
            suggestionBox.appendChild(div);
        });
    }, 300);
});

document.addEventListener('click', (e) => {
    if (!e.target.closest('.location-wrap')) suggestionBox.innerHTML = '';
});
</script>
</body>
</html>