<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$servername = "localhost";
$username = "u459954629_hostinger";
$password = "Root@2004@2004";
$dbname = "u459954629_ecommercestore";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Database connection failed: " . $conn->connect_error);

// --- IMAGE UPLOAD HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $upload_dir = __DIR__ . '/admin/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $file = $_FILES['image'];
    if ($file['error'] === 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_name = 'watch_' . uniqid() . '.' . $ext;

        if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
            $name = $_POST['name'] ?? 'Unnamed Watch';
            $price = $_POST['price'] ?? 0;
            $description = $_POST['description'] ?? '';

            $stmt = $conn->prepare("INSERT INTO watches (name, price, description, image) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sdss", $name, $price, $description, $new_name);
            $stmt->execute();
        }
    }
}

// --- GET ACTIVE SALE ---
$active_sale_query = "SELECT * FROM sales WHERE status='active' LIMIT 1";
$active_sale_result = $conn->query($active_sale_query);
$active_sale = $active_sale_result ? $active_sale_result->fetch_assoc() : null;

// --- GET ALL WATCHES ---
$watches_query = "SELECT * FROM watches";
$watches_result = $conn->query($watches_query);

$watches = [];
$upload_base_url = "https://wristwin.shop/admin/";

if ($watches_result && $watches_result->num_rows > 0) {
    while ($row = $watches_result->fetch_assoc()) {

        if (!empty($row['image'])) {
            $row['image'] = $upload_base_url . $row['image'];
        } else {
            $row['image'] = $upload_base_url . "default.png";
        }

        if ($active_sale && isset($active_sale['discount_percent'])) {
            $discount = $active_sale['discount_percent'];
            $row['discounted_price'] = round($row['price'] * (1 - $discount / 100), 2);
            $row['discount'] = $discount;
        } else {
            $row['discounted_price'] = null;
            $row['discount'] = 0;
        }

        $watches[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Wrist Win Watches - Home</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<link rel="stylesheet" href="static/styling/user_styling/home.css">
<!-- Favicon for browsers -->
<link rel="icon" type="image/png" sizes="32x32" href="https://wristwin.shop/static/icon.png">
<link rel="icon" type="image/png" sizes="16x16" href="https://wristwin.shop/static/icon.png">

<!-- Apple Touch Icon for iOS -->
<link rel="apple-touch-icon" sizes="180x180" href="https://wristwin.shop/static/icon.png">

<!-- Optional: Safari pinned tab -->
<link rel="mask-icon" href="https://wristwin.shop/static/icon.svg" color="#0072ff">

</head>
<body>

<!-- Navbar -->


<nav class="nav">
    <div class="nav-left">
        <a href="/"><img class="logo" src="static/logo.webp" alt="wrist-win"></a>
    </div>

    <div class="nav-right">
        <div class="nav-links">
            <a class="buttons" href="home.php">Home</a>
             <a class="buttons" href="shop.php">Shop</a>
             <a class="buttons" href="contact.php">Contact</a>

             <a href="cart.php" class="cart-icon" id="cart-icon"><i class="fa-solid fa-cart-shopping"></i><span class="cart-badge" id="cart-count">0</span></a>
        </div>
    </div>
</nav>


<!-- Hero Slider -->
<div class="slider">
    <div class="slides">
        <div class="slide">
            <div class="banner">
                <a><img src="static/banner.webp" alt="wrist-win"></a>
            </div>
        </div>
    </div>
</div>

<!-- Products Section -->
<div class="home-header">
    <h1>Selling Products</h1>

    <?php if ($active_sale && isset($active_sale['sale_name'], $active_sale['discount_percent'])): ?>
        <h2 class="gradient-text">
            🔥 <?= htmlspecialchars($active_sale['sale_name']) ?> - <?= htmlspecialchars($active_sale['discount_percent']) ?>% OFF!
        </h2>
    <?php endif; ?>
</div>

<section class="products">
    <div class="product-grid">
        <?php if (count($watches) > 0): ?>
            <?php foreach($watches as $watch): ?>
                <div class="product-card">
                    <div class="image-container">
                        <?php if ($watch['discounted_price']): ?>
                            <span class="sale-badge">SALE</span>
                        <?php endif; ?>
                        <img src="<?= htmlspecialchars($watch['image']) ?>" alt="<?= htmlspecialchars($watch['name']) ?>">
                    </div>
                    <div class="product-info">
                        <h3><?= htmlspecialchars($watch['name']) ?></h3>
                        <p><?= htmlspecialchars($watch['description']) ?></p>
                        <?php if ($watch['discounted_price']): ?>
                            <div class="price">
                                <span class="original-price">Rs. <?= $watch['price'] ?></span>
                                <span class="discounted-price">Rs. <?= $watch['discounted_price'] ?></span>
                            </div>
                        <?php else: ?>
                            <div class="price"><span class="discounted-price">PKR. <?= $watch['price'] ?></span></div>
                        <?php endif; ?>
                        <a href="shop.php"><button>Shop Now</button></a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color:#ffffff;">No watches available yet.</p>
        <?php endif; ?>
    </div>
</section>

<!-- Footer -->
<footer>
    <div class="footer-container">
        <div>
            <h3>Customer Support</h3>
            <a href="shipping_policy.php">Shipping Policy</a>
            <a href="refund_policy.php">Refund Policy</a>
            <a href="privacy_policy.php">Privacy Policy</a>
            <a href="terms_of_service.php">Terms of Service</a>
            <a href="contact.php">Contact Information</a>
        </div>
        <div>
            <h3>About Wrist Win</h3>
            <p>Luxury Watches crafted with passion and precision. Experience timeless elegance for Men & Women.</p>
        </div>
        <div>
            <h3>Follow Us</h3>
            <a href="https://wa.me/923231508088" target="_blank" style="color:#25D366;"><i class="fab fa-whatsapp"></i> Whatsapp</a>
            <a href="https://www.instagram.com/wristwin" target="_blank" style="color:#E1306C;"><i class="fab fa-instagram"></i> Instagram</a>
            <a href="https://www.facebook.com/share/1ANaokHvx8/?mibextid=wwXIfr" target="_blank" style="color:#1877F2;"><i class="fab fa-facebook"></i> Facebook</a>
            <a href="https://www.tiktok.com/@wristwin" target="_blank" style="color:#ffffff;"><i class="fab fa-tiktok"></i> TikTok</a>
            <a href="mailto:businessinfo.pk47@gmail.com" style="color:#D14836;"><i class="fa-solid fa-envelope"></i> Gmail</a>
        </div>
    </div>
    <p>© 2025 Wrist Win Watches — Crafted with elegance & love.</p>
</footer>

<script>
    function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem("cart")) || [];
    const count = cart.reduce((acc, item) => acc + item.quantity, 0);
    document.getElementById("cart-count").textContent = count;
    }

    /* Call when page loads */
    document.addEventListener("DOMContentLoaded", updateCartCount);

    /* Call when storage changes (other pages like shop.php) */
    window.addEventListener("storage", updateCartCount);

    // Product scroll animation
    const products = document.querySelectorAll('.product-card');
    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if(entry.isIntersecting){ entry.target.classList.add('show'); obs.unobserve(entry.target); }
        });
    }, {threshold: 0.2});
    products.forEach(p => observer.observe(p));
</script>

</body>
</html>
