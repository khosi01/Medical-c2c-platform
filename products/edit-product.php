<?php
session_start();

require_once '../config/db.php';
require_once '../includes/auth-check.php';

$base = '/medical-c2c-platform';
$userId = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    die("Invalid product.");
}

$productId = $_GET['id'];

/* FETCH PRODUCT */
$stmt = $pdo->prepare("
    SELECT *
    FROM products
    WHERE id = ? AND seller_id = ?
");
$stmt->execute([$productId, $userId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found or access denied.");
}

$success = '';
$error = '';

/* UPDATE PRODUCT */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $condition = trim($_POST['p_condition']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);
    $imagePath = $product['image_path'];

    /* IMAGE UPLOAD */
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            $dir = '../uploads/products/';
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $newName = 'product_' . $userId . '_' . time() . '.' . $ext;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $newName)) {
                /* DELETE OLD IMAGE */
                if (!empty($product['image_path']) && file_exists($dir . $product['image_path'])) {
                    unlink($dir . $product['image_path']);
                }
                $imagePath = $newName;
            }
        }
    }

    $update = $pdo->prepare("
        UPDATE products SET
            title = ?,
            category = ?,
            p_condition = ?,
            price = ?,
            description = ?,
            image_path = ?,
            updated_at = NOW()
        WHERE id = ? AND seller_id = ?
    ");

    if ($update->execute([$title, $category, $condition, $price, $description, $imagePath, $productId, $userId])) {
        $success = "Product updated successfully!";

        /* REFRESH PRODUCT */
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
        $stmt->execute([$productId, $userId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = "Something went wrong.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product | MedMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://jsdelivr.net" rel="stylesheet">
    <link href="https://googleapis.com" rel="stylesheet">
    <style>
        body {
            background: #f8fbfb;
            font-family: 'Poppins', DM SERIF;
        }

        .edit-card {
            max-width: 700px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 2px 16px rgba(3, 104, 115, 0.08);
        }

        .section-title {
            font-weight: 800;
            color: #036873;
        }

        .form-label {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 0.88rem;
            margin-bottom: 6px;
        }

        .form-control,
        .form-select {
            border: 1px solid #e8f5f7;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.9rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #036873;
            box-shadow: 0 0 0 0.25rem rgba(3, 104, 115, 0.1);
        }

        .preview-wrapper {
            border: 1px dashed #b2e4ea;
            border-radius: 12px;
            padding: 12px;
            background: #f4fbfc;
            display: inline-block;
            margin-top: 12px;
        }

        .preview {
            max-width: 200px;
            border-radius: 8px;
            display: block;
        }

        /* Unified Figma Style Buttons */
        .btn-save {
            padding: 10px 22px;
            border-radius: 50px;
            background: #036873;
            color: white;
            border: none;
            font-size: 0.88rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .btn-save:hover {
            background: #024f58;
            color: white;
        }

        .btn-cancel {
            padding: 10px 22px;
            border-radius: 50px;
            border: 1.5px solid #036873;
            background: white;
            color: #036873;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .btn-cancel:hover {
            background: #f0fbfc;
            color: #036873;
        }

        /* Alert Refinements */
        .alert {
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 500;
        }
    </style>
</head>

<body>

    <?php include '../includes/navbar-dashboard.php'; ?>

    <div class="container">
        <div class="edit-card">
            <h3 class="mb-4 section-title">Edit Product</h3>

            <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Product Title</label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($product['title']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($product['category']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Condition</label>
                    <select name="p_condition" class="form-select" required>
                        <?php
                        $conditions = ['New', 'Like New', 'Good', 'Fair', 'Used'];
                        foreach ($conditions as $cond):
                        ?>
                            <option value="<?php echo $cond; ?>" <?php echo ($product['p_condition'] === $cond) ? 'selected' : ''; ?>>
                                <?php echo $cond; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Price (ZAR)</label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:#e4f7f9; border-color:#e8f5f7; color:#036873; font-weight:600; border-radius:10px 0 0 10px;">R</span>
                        <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $product['price']; ?>" style="border-radius:0 10px 10px 0;" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label">Product Image</label>
                    <input type="file" name="image" class="form-control" accept="image/*">

                    <?php if (!empty($product['image_path'])): ?>
                        <div class="preview-wrapper">
                            <img src="<?php echo $base; ?>/uploads/products/<?php echo htmlspecialchars($product['image_path']); ?>" class="preview" alt="Current Image">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex align-items-center gap-2 pt-2">
                    <button type="submit" class="btn-save">
                        <i class="bi bi-check2"></i> Save Changes
                    </button>
                    <a href="profile.php?tab=listings" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://jsdelivr.net"></script>
</body>

</html>