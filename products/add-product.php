<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth-check.php';

$base = '/medical-c2c-platform';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $seller_id   = $_SESSION['user_id'];
    $title       = trim($_POST['title']);
    $category    = trim($_POST['category']);
    $condition   = trim($_POST['p_condition']);
    $price       = trim($_POST['price']);
    $description = trim($_POST['description']);

    $target_dir = "../uploads/products/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $files   = $_FILES['images'];
    $uploaded = [];

    $validFiles = array_filter($files['name'], fn($n) => $n !== '');

    if (count($validFiles) === 0) {
        $message = "<div class='alert alert-danger'>Please upload at least one product image.</div>";
    } else {
        $valid = true;
        foreach ($files['name'] as $i => $fname) {
            if (empty($fname)) continue;
            $ext       = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
            $file_name = time() . "_" . $seller_id . "_" . $i . "." . $ext;
            $destination = $target_dir . $file_name;

            if (move_uploaded_file($files['tmp_name'][$i], $destination)) {
                $uploaded[] = $file_name;
            } else {
                error_log("Failed to move: " . $files['tmp_name'][$i] . " to " . $destination);
            }
        }

        if ($valid) {
            foreach ($files['name'] as $i => $fname) {
                if (empty($fname)) continue;
                $ext       = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
                $file_name = time() . "_" . $seller_id . "_" . $i . "." . $ext;
                if (move_uploaded_file($files['tmp_name'][$i], $target_dir . $file_name)) {
                    $uploaded[] = $file_name;
                }
            }

            if (empty($uploaded)) {
                $message = "<div class='alert alert-danger'>Failed to upload images.</div>";
            } else {
                $cover = $uploaded[0];
                $sql   = "INSERT INTO products (seller_id, title, category, p_condition, price, description, image_path, status)
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
                $stmt  = $pdo->prepare($sql);

                if ($stmt->execute([$seller_id, $title, $category, $condition, $price, $description, $cover])) {
                    $product_id = $pdo->lastInsertId();
                    $img_stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_cover) VALUES (?, ?, ?)");
                    foreach ($uploaded as $idx => $img) {
                        $img_stmt->execute([$product_id, $img, $idx === 0 ? 1 : 0]);
                    }

                    header("Location: ../user/my-products.php?success=1");
                    exit();
                } else {
                    $message = "<div class='alert alert-danger'>Failed to save product. Please try again.</div>";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List a Product | MedMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/style.css">
    <style>
        body {
            background: #f0fbfc;
            font-family: 'Poppins', sans-serif;
        }

        .listing-card {
            background: white;
            border: 1px solid #e8f5f7;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 2px 12px rgba(3, 104, 115, 0.05);
            margin-bottom: 20px;
        }

        .section-title {
            color: #036873;
            font-weight: 700;
            font-size: 1rem;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .med-input {
            border-radius: 10px;
            border: 1.5px solid #e0f4f6;
            padding: 11px 14px;
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
            min-height: 110px;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #444;
            margin-bottom: 6px;
            display: block;
        }

        /* Upload*/
        .upload-dropzone {
            border: 2px dashed #b2e4ea;
            border-radius: 14px;
            background: #f8feff;
            position: relative;
            min-height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        .upload-dropzone:hover {
            border-color: #036873;
        }

        .file-input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }

        .upload-label {
            text-align: center;
            pointer-events: none;
            padding: 20px;
        }

        .upload-label i {
            font-size: 2.2rem;
            color: #b2e4ea;
            display: block;
            margin-bottom: 8px;
        }

        .upload-label p {
            color: #aaa;
            font-size: 0.88rem;
            margin: 0;
        }

        .upload-label small {
            color: #bbb;
            font-size: 0.78rem;
        }

        /* Preview grid */
        #preview-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .preview-item {
            position: relative;
            width: 90px;
            height: 90px;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid #e0f4f6;
        }

        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-item.cover-img {
            border-color: #036873;
        }

        .cover-tag {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(3, 104, 115, .75);
            color: #fff;
            font-size: 0.6rem;
            font-weight: 700;
            text-align: center;
            padding: 2px 0;
            letter-spacing: .5px;
        }

        .remove-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 20px;
            height: 20px;
            background: rgba(0, 0, 0, .5);
            color: #fff;
            border-radius: 50%;
            border: none;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            line-height: 1;
        }

        .remove-btn:hover {
            background: rgba(200, 0, 0, .7);
        }

        .add-more-box {
            width: 90px;
            height: 90px;
            border-radius: 10px;
            border: 2px dashed #b2e4ea;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #b2e4ea;
            font-size: 1.5rem;
            gap: 2px;
            transition: border-color .2s;
        }

        .add-more-box:hover {
            border-color: #036873;
            color: #036873;
        }

        .add-more-box small {
            font-size: 0.62rem;
            color: #aaa;
        }

        /* Condition */
        .condition-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
            gap: 10px;
        }

        .btn-check:checked+.btn-outline-teal {
            background: #e4f7f9;
            border-color: #036873;
            color: #036873;
            font-weight: 600;
        }

        .btn-outline-teal {
            border: 1.5px solid #b2e4ea;
            color: #57a0a7;
            border-radius: 10px;
            padding: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .btn-outline-teal:hover {
            border-color: #036873;
            color: #036873;
        }

        /* Actions */
        .btn-publish {
            background: #036873;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 36px;
            font-weight: 700;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
        }

        .btn-publish:hover {
            background: #024f58;
        }

        .btn-draft {
            background: none;
            border: none;
            color: #aaa;
            font-family: 'Poppins', sans-serif;
            font-size: 0.88rem;
            cursor: pointer;
            text-decoration: underline;
        }

        .page-title {
            font-weight: 800;
            color: #036873;
            margin-bottom: 6px;
        }

        .page-subtitle {
            font-size: 0.85rem;
            color: #aaa;
            margin-bottom: 28px;
        }
    </style>
</head>

<body Style="font-family: 'Poppins', DM SERIF;">

    <?php include '../includes/navbar-dashboard.php'; ?>

    <div class="container py-4" style="max-width:760px;">

        <h2 class="page-title">List a New Product</h2>
        <p class="page-subtitle">Fill in the details below to publish your item on MedMarket.</p>

        <?php if ($message) echo $message; ?>

        <form action="add-product.php" method="POST" enctype="multipart/form-data">

            <!-- Photos -->
            <div class="listing-card">
                <div class="section-title"><i class="bi bi-images"></i> Product Photos <span style="color:#aaa;font-weight:400;font-size:0.8rem;margin-left:4px;">— up to 6, first is cover</span></div>

                <input type="file" name="images[]" id="img-upload" accept="image/*" multiple style="display:none;">

                <div class="upload-dropzone" id="dropzone">
                    <div class="upload-label">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <p>Click to upload photos</p>
                        <small>JPG, PNG or WEBP &nbsp;·&nbsp; up to 6 images</small>
                    </div>
                </div>

                <div id="preview-grid" style="display:none;">
                    <div class="add-more-box" id="add-more-btn" title="Add more photos">
                        <i class="bi bi-plus"></i>
                        <small>Add more</small>
                    </div>
                </div>
            </div>

            <!-- Basic Info -->
            <div class="listing-card">
                <div class="section-title"><i class="bi bi-info-circle"></i> Basic Information</div>

                <label class="form-label">Product Title *</label>
                <input type="text" name="title" class="med-input mb-3" placeholder="e.g. Littmann Classic III Stethoscope" required>

                <label class="form-label">Description *</label>
                <textarea name="description" class="med-input mb-3" placeholder="Describe your item — condition details, what's included, reason for selling..." required></textarea>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Category *</label>
                        <select name="category" class="med-input form-select" required>
                            <option value="" disabled selected>Select category</option>
                            <option value="Medical Books">Medical Books</option>
                            <option value="Equipment">Equipment</option>
                            <option value="Training Materials">Training Materials</option>
                            <option value="Lab Supplies">Lab Supplies</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Brand / Manufacturer</label>
                        <input type="text" name="brand" class="med-input" placeholder="e.g. Philips, Littmann">
                    </div>
                </div>

                <label class="form-label">Condition *</label>
                <div class="condition-grid">
                    <input type="radio" name="p_condition" value="New" id="c1" class="btn-check">
                    <label class="btn btn-outline-teal w-100" for="c1">New</label>
                    <input type="radio" name="p_condition" value="Like New" id="c2" class="btn-check">
                    <label class="btn btn-outline-teal w-100" for="c2">Like New</label>
                    <input type="radio" name="p_condition" value="Good" id="c3" class="btn-check" checked>
                    <label class="btn btn-outline-teal w-100" for="c3">Good</label>
                    <input type="radio" name="p_condition" value="Fair" id="c4" class="btn-check">
                    <label class="btn btn-outline-teal w-100" for="c4">Fair</label>
                </div>
            </div>

            <!-- Pricing & Delivery -->
            <div class="listing-card">
                <div class="section-title"><i class="bi bi-tags"></i> Pricing & Delivery</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Asking Price (ZAR) *</label>
                        <div style="position:relative;">
                            <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-weight:700;color:#036873;">R</span>
                            <input type="number" name="price" class="med-input" style="padding-left:28px;" placeholder="0.00" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Delivery Mode</label>
                        <select name="shipping" class="med-input form-select">
                            <option value="Paxi">Paxi (Pep to Pep)</option>
                            <option value="Aramex">Aramex (Store to Door)</option>
                            <option value="Postnet">Postnet to Postnet</option>
                            <option value="Courier">The Courier Guy</option>
                            <option value="Pickup">Local Pickup (Free)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-flex justify-content-between align-items-center mt-2 mb-5">
                <button type="button" class="btn-draft">Save as Draft</button>
                <button type="submit" class="btn-publish">Publish Listing</button>
            </div>

        </form>
    </div>

    <script>
        let selectedFiles = [];
        const MAX = 6;

        const input = document.getElementById('img-upload');
        const dropzone = document.getElementById('dropzone');
        const previewGrid = document.getElementById('preview-grid');
        const addMoreBtn = document.getElementById('add-more-btn');

        dropzone.addEventListener('click', () => input.click());
        addMoreBtn.addEventListener('click', () => input.click());

        input.addEventListener('change', handleFiles);

        function handleFiles() {
            const newFiles = Array.from(input.files);
            const remaining = MAX - selectedFiles.length;
            const toAdd = newFiles.slice(0, remaining);
            selectedFiles = [...selectedFiles, ...toAdd];
            renderPreviews();
            input.value = '';
        }

        function renderPreviews() {
            previewGrid.querySelectorAll('.preview-item').forEach(el => el.remove());

            selectedFiles.forEach((file, idx) => {
                const item = document.createElement('div');
                item.className = 'preview-item' + (idx === 0 ? ' cover-img' : '');

                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'remove-btn';
                removeBtn.innerHTML = '&times;';
                removeBtn.addEventListener('click', () => {
                    selectedFiles.splice(idx, 1);
                    renderPreviews();
                });

                item.appendChild(img);
                item.appendChild(removeBtn);

                if (idx === 0) {
                    const tag = document.createElement('div');
                    tag.className = 'cover-tag';
                    tag.textContent = 'COVER';
                    item.appendChild(tag);
                }

                previewGrid.insertBefore(item, addMoreBtn);
            });

            if (selectedFiles.length > 0) {
                dropzone.style.display = 'none';
                previewGrid.style.display = 'flex';
                addMoreBtn.style.display = selectedFiles.length >= MAX ? 'none' : 'flex';
            } else {
                dropzone.style.display = 'flex';
                previewGrid.style.display = 'none';
            }

            syncFilesToInput();
        }

        function syncFilesToInput() {
            const dt = new DataTransfer();
            selectedFiles.forEach(f => dt.items.add(f));
            try {
                input.files = dt.files;
            } catch (e) {
                console.warn('DataTransfer not supported:', e);
            }
        }

        document.querySelector('form').addEventListener('submit', function(e) {
            if (selectedFiles.length === 0) {
                e.preventDefault();
                alert('Please upload at least one product image.');
                return;
            }

            const dt = new DataTransfer();
            selectedFiles.forEach(f => dt.items.add(f));
            input.files = dt.files;
        });
    </script>

</body>

</html>