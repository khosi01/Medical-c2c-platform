<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$userName   = $_SESSION['user_name'] ?? '';
$base       = '/medical-c2c-platform';

$cartCount    = !empty($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$wishCount    = !empty($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;
?>

<nav style="
  background: white;
  border-bottom: 1px solid #e4f4f6;
  box-shadow: 0 2px 12px rgba(3,104,115,0.06);
  position: sticky; top: 0; z-index: 100;
  font-family: 'Poppins', sans-serif;
">
    <div style="
    display: flex; align-items: center; gap: 16px;
    padding: 10px 28px; max-width: 1400px; margin: 0 auto;
    height: 62px;
  ">

        <!-- Logo -->
        <a href="<?php echo $base; ?>/index.php"
            style="display:flex; align-items:center; gap:8px; text-decoration:none; flex-shrink:0;">
            <img src="<?php echo $base; ?>/assets/images/Logo.jpg" alt="MedMarket"
                style="width:34px; height:34px; border-radius:8px; object-fit:cover; border:1px solid rgba(3,104,115,0.2);">
            <span style="font-family:'DM Serif Display',serif; color:#036873; font-size:1.1rem; font-style:italic; white-space:nowrap;">
                Med<em>Market</em>
            </span>
        </a>

        <!-- Search -->
        <form method="GET" action="<?php echo $base; ?>/products/browse.php"
            style="flex:1; max-width:480px;">
            <div style="position:relative;">
                <i class="bi bi-search"
                    style="position:absolute; left:13px; top:50%; transform:translateY(-50%); color:#8bbfc4; font-size:.9rem;"></i>
                <input type="text" name="q"
                    value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                    placeholder="Search medical books, equipment..."
                    style="
                 width:100%; padding:9px 14px 9px 36px;
                 border:1.5px solid #c8edf0; border-radius:50px;
                 font-family:'Poppins',sans-serif; font-size:0.85rem;
                 background:#f4fcfd; outline:none; color:#333;
                 transition: border-color 0.2s;
               "
                    onfocus="this.style.borderColor='#036873'"
                    onblur="this.style.borderColor='#c8edf0'">
            </div>
        </form>

        <div style="flex:1;"></div>
        <?php if ($isLoggedIn): ?>

            <!-- Sell -->
            <a href="<?php echo $base; ?>/products/add-product.php"
                style="
         display:inline-flex; align-items:center; gap:6px;
         background:#036873; color:white; padding:8px 18px;
         border-radius:50px; text-decoration:none;
         font-size:0.85rem; font-weight:600; white-space:nowrap;
         transition: background 0.2s; flex-shrink:0;
       "
                onmouseover="this.style.background='#024a52'"
                onmouseout="this.style.background='#036873'">
                <i class="bi bi-plus-lg"></i> Sell Item
            </a>

            <!-- Icon group -->
            <div style="display:flex; align-items:center; gap:4px; flex-shrink:0;">

                <!-- Cart -->
                <a href="<?php echo $base; ?>/transactions/cart.php"
                    title="My Cart"
                    style="position:relative; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#036873; text-decoration:none; font-size:1.15rem; transition:background 0.15s;"
                    onmouseover="this.style.background='#e8f6f7'"
                    onmouseout="this.style.background='transparent'">
                    <i class="bi bi-cart3"></i>
                    <?php if ($cartCount > 0): ?>
                        <span style="position:absolute; top:4px; right:4px; background:#e74c3c; color:white; font-size:.55rem; font-weight:700; width:15px; height:15px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:1.5px solid white;">
                            <?php echo $cartCount; ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- Wishlist -->
                <a href="<?php echo $base; ?>/user/wishlist.php"
                    title="Wishlist"
                    style="position:relative; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#036873; text-decoration:none; font-size:1.15rem; transition:background 0.15s;"
                    onmouseover="this.style.background='#e8f6f7'"
                    onmouseout="this.style.background='transparent'">
                    <i class="bi bi-heart"></i>
                    <?php if ($wishCount > 0): ?>
                        <span style="position:absolute; top:4px; right:4px; background:#e74c3c; color:white; font-size:.55rem; font-weight:700; width:15px; height:15px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:1.5px solid white;">
                            <?php echo $wishCount; ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- Messages -->
                <a href="<?php echo $base; ?>/messages.php"
                    title="Messages"
                    style="width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#036873; text-decoration:none; font-size:1.15rem; transition:background 0.15s;"
                    onmouseover="this.style.background='#e8f6f7'"
                    onmouseout="this.style.background='transparent'">
                    <i class="bi bi-chat-square-text"></i>
                </a>

                <!-- Profile -->
                <a href="<?php echo $base; ?>/user/profile.php"
                    title="My Profile"
                    style="width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:#e8f6f7; color:#036873; text-decoration:none; font-weight:700; font-size:0.95rem; border:2px solid #c2eaed; margin-left:4px; flex-shrink:0; overflow:hidden;">
                    <?php
                    if (!empty($_SESSION['profile_pic'])):
                    ?>
                        <img src="<?php echo $base; ?>/uploads/profiles/<?php echo htmlspecialchars($_SESSION['profile_pic']); ?>"
                            style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($userName, 0, 1)); ?>
                    <?php endif; ?>
                </a>

            </div>

        <?php else: ?>

            <!-- Guest buttons -->
            <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                <a href="<?php echo $base; ?>/auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>"
                    style="color:#036873; font-weight:600; text-decoration:none; font-size:0.88rem; padding:8px 16px; border-radius:50px; border:1.5px solid #036873;">
                    Sign In
                </a>
                <a href="<?php echo $base; ?>/auth/register.php"
                    style="background:#036873; color:white; font-weight:600; text-decoration:none; font-size:0.88rem; padding:9px 18px; border-radius:50px;">
                    Create Account
                </a>
            </div>

        <?php endif; ?>

    </div>
</nav>