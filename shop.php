<?php
// SHOW PHP ERRORS (important for debugging)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// DATABASE CONNECTION
$servername = "localhost";               // Usually localhost on Hostinger
$username = "u459954629_hostinger";     // Your MySQL user
$password = "Root@2004@2004";          // Your MySQL password
$dbname = "u459954629_ecommercestore";  // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("DB Connection Failed: " . $conn->connect_error); }

// --- IMAGE UPLOAD HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $upload_dir = __DIR__ . '/admin/uploads/'; // server path to uploads
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
        } else {
            echo "Failed to move uploaded file.";
        }
    } else {
        echo "Upload error code: " . $file['error'];
    }
}
// 1️⃣ Get active sale
$active_sale_sql = "SELECT * FROM sales WHERE status='active' LIMIT 1";
$active_sale_result = $conn->query($active_sale_sql);
$active_sale = $active_sale_result->fetch_assoc();

// 2️⃣ Get all watches
$watches_sql = "SELECT * FROM watches";
$watches_result = $conn->query($watches_sql);

$watches = [];
$upload_base_url = "https://wristwin.shop/admin/";

if ($watches_result->num_rows > 0) {
    while ($row = $watches_result->fetch_assoc()) {

        // --- Image Handling ---
        if (!empty($row['image'])) {
            $row['image'] = $upload_base_url . $row['image'];
        } else {
            $row['image'] = $upload_base_url . "default.png";
        }

        // --- Discount Handling ---
        if ($active_sale) {
            $discount_percent = $active_sale['discount_percent'];
            $row['discount'] = $discount_percent;
            $row['discounted_price'] = round($row['price'] * (1 - $discount_percent / 100), 2);
            $row['sale'] = ($row['stock'] > 0);
        } else {
            $row['discounted_price'] = null;
            $row['discount'] = 0;
            $row['sale'] = false;
        }

        $watches[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Wrist Win Watches</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="static/styling/user_styling/shop.css">
</head>

<body>
    <!-- Navbar -->
    <nav>
         <div class="logo">
            <a href="home.php"><img src="static/logo.webp" alt="wrist-win"></a>
        </div> 
         <div class="nav-controls">
            <div class="nav-search">
               <input type="text" id="search-input" placeholder="Search watches..." onkeyup="searchWatches()" />
               <button><i class="fa fa-search"></i></button>
            </div>
         </div>

         <div class="nav-links">
            <div class="nav-links">
               <a class="buttons" href="home.php">Home</a>
               <a class="buttons" href="shop.php">Shop</a>
               <a class="buttons" href="contact.php">Contact</a>

               <a href="cart.php" class="cart-icon" id="cart-icon"><i class="fa-solid fa-cart-shopping"></i><span class="cart-badge" id="cart-count">0</span></a>
            </div>
         </div>
      </nav>

    <!-- Header -->
    <section class="shop-header">
        <h1>Shop Our Watches</h1>
        <p>Explore luxury watches for Men & Women.</p>

        <?php if ($active_sale && isset($active_sale['sale_name'], $active_sale['discount_percent'])): ?>
        <h2 class="gradient-text">
            🔥 <?= htmlspecialchars($active_sale['sale_name']) ?> - <?= htmlspecialchars($active_sale['discount_percent']) ?>% OFF!
        </h2>
    <?php endif; ?>
    
    </section>
    
    <!-- Filters -->
    <div class="filter-buttons">
        <button class="active" onclick="filterCategory('all', this)">All</button>
        <button onclick="filterCategory('men', this)">Men</button>
        <button onclick="filterCategory('women', this)">Women</button>
    </div>

    <!-- Product GRID -->
    <section class="product-section">
        <div class="product-grid">

            <?php foreach ($watches as $watch): ?>
                <div class="product-card" data-category="<?= strtolower($watch['category']) ?>">

                    <!-- BADGES -->
                    <?php if ($watch['stock'] == 0): ?>
                        <span class="badge soldout">SOLD OUT</span>

                    <?php elseif ($watch['sale']): ?>
                        <span class="badge">SALE <?= $watch['discount'] ?>% OFF</span>
                    <?php endif; ?>

                    <img src="<?= htmlspecialchars($watch['image']) ?>" alt="<?= htmlspecialchars($watch['name']) ?>">

                    <div class="product-info">
                        <h3 class="product-name"><?= $watch['name'] ?></h3>
                        <p><?= $watch['description'] ?></p>

                        <?php if ($watch['sale'] && $watch['stock'] > 0): ?>
                            <div class="price">
                                <span class="original-price">PKR <?= $watch['price'] ?></span>
                                <span class="discounted-price">PKR <?= $watch['discounted_price'] ?></span>
                            </div>
                        <?php else: ?>
                            <div class="price">PKR <?= $watch['price'] ?></div>
                        <?php endif; ?>

                        <button onclick="addToCart(
                            <?= $watch['id'] ?>,
                            '<?= $watch['name'] ?>',
                            <?= $watch['price'] ?>,
                            <?= $watch['discounted_price'] ? $watch['discounted_price'] : 'null' ?>,
                            '<?= $watch['image'] ?>',
                            <?= $watch['stock'] ?>
                        )">Add to Cart</button>

                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </section>

    <!-- 🦋 Footer -->
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
               <p>Luxury Watches crafted with passion and purity. Experience timeless for Men & Women.</p>
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
         const cart = JSON.parse(localStorage.getItem("cart")) || [];
         function updateCartCount() {
             const cart = JSON.parse(localStorage.getItem("cart")) || [];
             const count = cart.reduce((acc, item) => acc + item.quantity, 0);
             document.getElementById("cart-count").textContent = count;
         }
function addToCart(id, name, original_price, discounted_price, image, stock) {
    let cart = JSON.parse(localStorage.getItem("cart")) || [];
    const existing = cart.find(item => item.id === id);

    if (existing) { 
        if (existing.quantity < stock) {          // ✅ Check stock limit
            existing.quantity += 1; 
        } else {
            alert(`Sorry! Maximum stock available is ${stock}.`);
        }
    } else { 
        if (stock > 0) {
            cart.push({ 
                id,
                name,
                original_price,
                display_price: discounted_price ? discounted_price : original_price,
                image,
                quantity: 1
            });
        } else {
            alert("Sorry! This item is out of stock.");
        }
    }

    localStorage.setItem("cart", JSON.stringify(cart));
    updateCartCount();

    document.getElementById("cart-icon").classList.add("bounce");
    setTimeout(() => {
        document.getElementById("cart-icon").classList.remove("bounce");
    }, 600);
}

         function searchWatches() {
           const input = document.getElementById("search-input").value.toLowerCase();
           document.querySelectorAll(".product-card").forEach(card => {
             const name = card.querySelector(".product-name").textContent.toLowerCase();
             card.style.display = name.includes(input) ? "" : "none";
           });
         }
         function filterCategory(category, btn) {
           document.querySelectorAll(".filter-buttons button").forEach(b => b.classList.remove("active"));
           btn.classList.add("active");
           document.querySelectorAll(".product-card").forEach(card => {
             const cat = card.getAttribute("data-category");
             card.style.display = (category === "all" || cat === category) ? "" : "none";
           });
         }
         updateCartCount();
         
         // Animate product cards on scroll
         const products = document.querySelectorAll('.product-card');
         const observer = new IntersectionObserver((entries, obs)=>{
           entries.forEach(entry=>{
              if(entry.isIntersecting){
                 entry.target.classList.add('show');
                 obs.unobserve(entry.target);
              }
           });
         },{threshold:0.2});
         products.forEach(p=>observer.observe(p));
      </script>

</body>
</html>