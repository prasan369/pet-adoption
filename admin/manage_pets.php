<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../login.php');
    exit();
}

$admin = get_user_by_id($_SESSION['user_id']);

// Handle pet operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = trim($_POST['name']);
        $type = trim($_POST['type']);
        $breed = trim($_POST['breed']);
        $age = (int)$_POST['age'];
        $gender = trim($_POST['gender']);
        $description = trim($_POST['description']);
        $price = (float)$_POST['price'];
        $location = trim($_POST['location']);
        
        $image = '';
        if (isset($_FILES['pet_image']) && $_FILES['pet_image']['error'] === 0) {
            $file_name = basename($_FILES['pet_image']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_ext)) {
                $image = 'uploads/' . uniqid() . '.' . $file_ext;
                move_uploaded_file($_FILES['pet_image']['tmp_name'], '../' . $image);
            }
        }
        
        $stmt = $conn->prepare("INSERT INTO pets (name, type, breed, age, gender, description, price, location, owner_id, pet_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $admin_id = $_SESSION['user_id'];
        $stmt->bind_param("sssiisdsds", $name, $type, $breed, $age, $gender, $description, $price, $location, $admin_id, $image);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Pet added successfully!';
        } else {
            $_SESSION['error'] = 'Error adding pet';
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $pet_id = (int)$_POST['pet_id'];
        $stmt = $conn->prepare("DELETE FROM pets WHERE id = ?");
        $stmt->bind_param("i", $pet_id);
        $stmt->execute();
        $_SESSION['success'] = 'Pet deleted successfully!';
    }
}

// Get all pets
$result = $conn->query("SELECT * FROM pets ORDER BY created_at DESC");
$pets = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pets - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
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
            <div class="top-bar">
                <h1>Manage Pets</h1>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
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
                        <input type="number" name="price" placeholder="Price" step="0.01" required>
                    </div>
                    <div class="form-row">
                        <input type="text" name="location" placeholder="Location" required>
                        <input type="file" name="pet_image" accept="image/*" required>
                    </div>
                    <textarea name="description" placeholder="Description" rows="3" required></textarea>
                    <button type="submit" class="btn btn-primary">Add Pet</button>
                </form>
            </div>
            
            <div class="content-section">
                <h2>All Pets</h2>
                <table class="pets-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Breed</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Price</th>
                            <th>Location</th>
                            <th>Adopted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pets as $pet): ?>
                            <tr>
                                <td><img src="../<?php echo htmlspecialchars($pet['pet_image']); ?>" alt="<?php echo htmlspecialchars($pet['name']); ?>" style="height: 50px; border-radius: 5px;"></td>
                                <td><?php echo htmlspecialchars($pet['name']); ?></td>
                                <td><?php echo htmlspecialchars($pet['type']); ?></td>
                                <td><?php echo htmlspecialchars($pet['breed']); ?></td>
                                <td><?php echo $pet['age']; ?> years</td>
                                <td><?php echo ucfirst(htmlspecialchars($pet['gender'])); ?></td>
                                <td>$<?php echo number_format($pet['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($pet['location']); ?></td>
                                <td><?php echo $pet['is_adopted'] ? '✓ Yes' : 'No'; ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                                        <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
