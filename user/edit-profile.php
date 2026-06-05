<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth-check.php';

$base = '/medical-c2c-platform';
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $location = trim($_POST['location']);
    $profession = trim($_POST['profession']);
    $bio = trim($_POST['bio'] ?? '');

    // Handle profile picture upload
    $profilePic = $user['profile_pic'];
    if (!empty($_FILES['profile_pic']['name'])) {
        $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array(strtolower($ext), $allowed)) {
            $dir = '../uploads/profiles/';
            if (!file_exists($dir)) mkdir($dir, 0777, true);
            $newName = 'profile_' . $userId . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dir . $newName)) {
                $profilePic = $newName;
            }
        }
    }

    // Handle cover photo upload
    $coverPhoto = $user['background_pic'] ?? '';
    if (!empty($_FILES['background_pic']['name'])) {
        $ext = pathinfo($_FILES['background_pic']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array(strtolower($ext), $allowed)) {
            $dir = '../uploads/covers/';
            if (!file_exists($dir)) mkdir($dir, 0777, true);
            $newName = 'cover_' . $userId . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['background_pic']['tmp_name'], $dir . $newName)) {
                $coverPhoto = $newName;
            }
        }
    }

    $update = $pdo->prepare("UPDATE users SET full_name=?, location=?, profession=?, bio=?, profile_pic=?, background_pic=? WHERE id=?");
    if ($update->execute([$fullName, $location, $profession, $bio, $profilePic, $coverPhoto, $userId])) {
        $_SESSION['user_name'] = $fullName;
        $success = 'Profile updated successfully!';
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
    } else {
        $error = 'Something went wrong. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | MedMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/style.css">
    <style>
        body {
            background: #f0fbfc;
            font-family: 'Poppins', sans-serif;
        }

        /* Navbar */
        .dash-navbar {
            background: #036873;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
        }

        .dash-nav-inner {
            display: flex;
            align-items: center;
            gap: 32px;
            padding: 0 30px;
            max-width: 1400px;
            margin: 0 auto;
            height: 65px;
        }

        .dash-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            flex-shrink: 0;
        }

        .dash-logo-text {
            font-family: 'DM Serif Display', serif;
            color: white;
            font-size: 1.15rem;
            font-style: italic;
        }

        .dash-nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
            flex: 1;
        }

        .dash-nav-link {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.75);
            transition: all 0.2s;
        }

        .dash-nav-link:hover,
        .dash-nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.15);
        }

        .dash-nav-right {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-shrink: 0;
        }

        .dash-bell {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            font-size: 1.3rem;
        }

        .dash-avatar-wrap {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.5);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
        }

        .dash-avatar-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Page */
        .page-header {
            margin-bottom: 24px;
        }

        .page-header h3 {
            font-weight: 800;
            color: #036873;
            margin: 0 0 4px;
        }

        .page-header p {
            font-size: 0.85rem;
            color: #aaa;
            margin: 0;
        }

        .edit-card {
            background: white;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 2px 16px rgba(3, 104, 115, 0.08);
            margin-bottom: 20px;
        }

        .edit-card-title {
            font-weight: 700;
            font-size: 1rem;
            color: #036873;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Photo Upload */
        .background-preview {
            height: 160px;
            border-radius: 12px;
            overflow: hidden;
            background: linear-gradient(135deg, #036873, #04a0af);
            position: relative;
            margin-bottom: 16px;
            cursor: pointer;
        }

        .background-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .background-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s;
            border-radius: 12px;
        }

        .background-preview:hover .background-overlay {
            opacity: 1;
        }

        .background-overlay span {
            color: white;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .avatar-upload-wrap {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .avatar-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            background: #036873;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            flex-shrink: 0;
            border: 3px solid #e4f7f9;
            cursor: pointer;
            position: relative;
        }

        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .avatar-preview:hover .avatar-overlay {
            opacity: 1;
        }

        .avatar-overlay i {
            color: white;
            font-size: 1.2rem;
        }

        .upload-hint {
            font-size: 0.8rem;
            color: #aaa;
            margin: 4px 0 0;
        }

        /* Form */
        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #444;
            margin-bottom: 6px;
        }

        .med-input {
            border-radius: 10px;
            border: 1.5px solid #e0f4f6;
            padding: 10px 14px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.88rem;
            width: 100%;
            transition: border-color 0.2s;
        }

        .med-input:focus {
            outline: none;
            border-color: #036873;
            box-shadow: 0 0 0 3px rgba(3, 104, 115, 0.08);
        }

        textarea.med-input {
            resize: vertical;
            min-height: 100px;
        }

        .form-select.med-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23036873' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 36px;
        }

        /* Buttons */
        .btn-save {
            background: #036873;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 10px 32px;
            font-weight: 600;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-save:hover {
            background: #024f58;
        }

        .btn-cancel {
            color: #888;
            border: 1.5px solid #e0e0e0;
            background: white;
            border-radius: 50px;
            padding: 10px 24px;
            font-weight: 600;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-cancel:hover {
            border-color: #036873;
            color: #036873;
        }

        .hidden-file {
            display: none;
        }

        @media (max-width:768px) {
            .dash-nav-links {
                display: none;
            }
        }
    </style>
</head>

<body Style="font-family: 'Poppins', DM SERIF;">

    <!-- Navbar -->
    <?php include '../includes/navbar-dashboard.php'; ?>

    <div class="container py-4" style="max-width:760px;">

        <!-- Header -->
        <div class="page-header">
            <h3>Edit Profile</h3>
            <p>Update your personal information and profile photos</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 mb-4" style="border-radius:12px;">
                <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" style="border-radius:12px;">
                <i class="bi bi-exclamation-circle-fill"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <!-- Photos Card -->
            <div class="edit-card">
                <div class="edit-card-title"><i class="bi bi-images"></i> Profile Photos</div>

                <!-- Cover Photo -->
                <label class="form-label">Cover Photo</label>
                <div class="background-preview" onclick="document.getElementById('cover-upload').click()">
                    <?php if (!empty($user['background_pic'])): ?>
                        <img src="<?php echo $base; ?>/uploads/covers/<?php echo htmlspecialchars($user['background_pic']); ?>"
                            alt="Cover"
                            id="cover-preview-img">
                    <?php else: ?>
                        <img src=""
                            alt=""
                            id="cover-preview-img"
                            style="display:none;">
                    <?php endif; ?>

                    <div class="background-overlay">
                        <span>
                            <i class="bi bi-camera"></i>
                            Change Cover Photo
                        </span>
                    </div>
                </div>
                <input type="file" name="background_pic" id="cover-upload" class="hidden-file" accept="image/*">

                <!-- Profile Picture -->
                <label class="form-label mt-3">Profile Picture</label>
                <div class="avatar-upload-wrap">
                    <div class="avatar-preview" onclick="document.getElementById('avatar-upload').click()">
                        <?php if (!empty($user['profile_pic'])): ?>
                            <img src="<?php echo $base; ?>/uploads/profiles/<?php echo htmlspecialchars($user['profile_pic']); ?>"
                                alt="Profile" id="avatar-preview-img">
                        <?php else: ?>
                            <span id="avatar-initial"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></span>
                            <img src="" alt="" id="avatar-preview-img" style="display:none;">
                        <?php endif; ?>
                        <div class="avatar-overlay"><i class="bi bi-camera"></i></div>
                    </div>
                    <div>
                        <div style="font-size:0.88rem; font-weight:600; color:#333;">Click to upload a new photo</div>
                        <div class="upload-hint">JPG, PNG or WEBP. Max 5MB.</div>
                    </div>
                </div>
                <input type="file" name="profile_pic" id="avatar-upload" class="hidden-file" accept="image/*">
            </div>

            <!-- Personal Info Card -->
            <div class="edit-card">
                <div class="edit-card-title"><i class="bi bi-person"></i> Personal Information</div>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" class="med-input"
                            value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Profession *</label>
                        <select name="profession" class="med-input form-select">
                            <?php foreach (['Doctor/Specialist', 'Nurse', 'Medical Student', 'Other Healthcare Professional'] as $prof): ?>
                                <option value="<?php echo $prof; ?>" <?php echo ($user['profession'] ?? '') === $prof ? 'selected' : ''; ?>>
                                    <?php echo $prof; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="med-input"
                            placeholder="e.g. Johannesburg, Sandton"
                            value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Bio</label>
                        <textarea name="bio" class="med-input"
                            placeholder="Tell buyers a bit about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex align-items-center gap-3 mt-2 mb-5">
                <button type="submit" class="btn-save">Save Changes</button>
                <a href="<?php echo $base; ?>/user/profile.php" class="btn-cancel">Cancel</a>
            </div>

        </form>
    </div>

    <script>
        document.getElementById('cover-upload').addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.getElementById('cover-preview-img');
                img.src = e.target.result;
                img.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });

        // Avatar preview
        document.getElementById('avatar-upload').addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.getElementById('avatar-preview-img');
                img.src = e.target.result;
                img.style.display = 'block';
                const initial = document.getElementById('avatar-initial');
                if (initial) initial.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    </script>
</body>

</html>