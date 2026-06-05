<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>MedMarket</title>


  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
  <nav class="navbar navbar-expand-lg site-navbar" style="background-color: #f8feff; border-bottom: 1px solid #D9D9D9;">
    <div class="container-fluid">
      <a class="brand-wrap" href="#">
        <div class="brand-logo">
          <img src="/medical-c2c-platform/assets/images/Logo.jpg" alt="Logo" width="40" height="40" style="border:1px solid rgba(3,104,115,0.5); border-radius:20%;">
        </div>

        <div class="brand-text"> <span class="brand-name" style="font-weight: lighter;">Med</span><span class="market-text">Market</span>
        </div>
      </a>

      <button
        class="navbar-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#mainNav"
        aria-controls="mainNav"
        aria-expanded="false"
        aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="mainNav">


        <ul class="navbar-nav nav-links mx-auto gap-1">
          <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="#">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/medical-c2c-platform/products/browse.php">Browse</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">About</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Contact</a>
          </li>
        </ul>


        <div class="d-flex gap-2 auth-buttons">
          <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/medical-c2c-platform/user/profile.php" class="btn-signin text-decoration-none">My Account</a>
            <a href="/medical-c2c-platform/auth/logout.php" class="btn-create text-decoration-none" style="font-family: Poppins; font-weight: bold; font-size:16.8px;">Logout</a>
          <?php else: ?>
            <a href="/medical-c2c-platform/auth/login.php" class="btn-signin text-decoration-none">Sign In</a>
            <a href="/medical-c2c-platform/auth/register.php" class="btn-create text-decoration-none" style="font-family: Poppins; font-weight: bold; font-size:16.8px;">Create Account</a>
          <?php endif; ?>
        </div>


      </div>
    </div>
  </nav>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>