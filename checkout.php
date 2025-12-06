<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// ✅ Database connection
$servername = "localhost";
$username = "u459954629_hostinger";
$password = "Root@2004@2004";
$dbname = "u459954629_ecommercestore";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]));
}

// ✅ Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $city = $_POST['city'] ?? '';
    $country = $_POST['country'] ?? '';
    $postal = $_POST['postal'] ?? '';
    $address = $_POST['address'] ?? '';
    $payment_method = "Cash on Delivery";

    $items_json = $_POST['items'] ?? '[]';
    $items = json_decode($items_json, true);

    if (empty($items)) {
        echo json_encode(["status" => "error", "message" => "Cart is empty"]);
        exit;
    }

    // ✅ Check active sale
    $sale_status = "Inactive";
    $sale_result = $conn->query("SELECT * FROM sales WHERE status='active' ORDER BY id DESC LIMIT 1");
    if ($sale_result && $sale_result->num_rows > 0) {
        $sale_status = "Active";
    }

    // ✅ Calculate total
    $total = 0;
    foreach ($items as $item) {
        $price = isset($item['display_price']) ? floatval($item['display_price']) : (isset($item['price']) ? floatval($item['price']) : 0);
        $qty = isset($item['quantity']) ? intval($item['quantity']) : 1;
        $total += $price * $qty;
    }

    // ✅ Begin transaction
    $conn->begin_transaction();
    try {
        // ✅ Update stock
        foreach ($items as $item) {
            $stmt = $conn->prepare("SELECT id, stock FROM watches WHERE name = ?");
            $stmt->bind_param("s", $item['name']);
            $stmt->execute();
            $result = $stmt->get_result();
            $watch_item = $result->fetch_assoc();

            if (!$watch_item) {
                throw new Exception("Watch not found: " . $item['name']);
            }

            if ($watch_item['stock'] < intval($item['quantity'])) {
                throw new Exception("Insufficient stock for: " . $item['name']);
            }

            $new_stock = $watch_item['stock'] - intval($item['quantity']);
            $update_stmt = $conn->prepare("UPDATE watches SET stock = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $new_stock, $watch_item['id']);
            $update_stmt->execute();
        }

        // ✅ Insert order
        $insert_stmt = $conn->prepare("
            INSERT INTO orders 
            (user_name, email, phone, country, city, postal, address, total, payment_method, items, sale_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $items_json_encoded = json_encode($items);
        $insert_stmt->bind_param(
            "sssssssssss", 
            $name, $email, $phone, $country, $city, $postal, $address, $total, $payment_method, $items_json_encoded, $sale_status
        );
        $insert_stmt->execute();

        $conn->commit();
        echo json_encode(["status" => "success"]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }

    $conn->close();
    exit;
}
?>

<!-- ✅ HTML Checkout Form -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Checkout - Wrist Win Watches</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="static/styling/user_styling/checkout.css">
</head>

<body>
  <!-- ✅ Navbar -->
    <nav>
         <div class="logo">
            <a href="home.php"><a href="home.php"><img src="static/logo.webp" alt="wrist-win"></a></a>
          </div>
         <div class="nav-links">
            <div class="nav-links">
               <a class="buttons" href="home.php">Home</a>
               <a class="buttons" href="shop.php">Shop</a>
               <a class="buttons" href="contact.php">Contact</a>
               <a class="buttons" href="cart.php"><i class="fa-solid fa-cart-shopping"></i></a>
            </div>
         </div>
      </nav> 

      <div class="banner">
        <marquee>
            <h1>🎁 Free Shipping & Cash on Delivery Available</h1>
        </marquee>
      </div>

  <div class="checkout-container">
    <h2>Checkout</h2>

    <form id="checkoutForm" method="POST" action="checkout">
      <div class="form-row">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="name" id="name" placeholder="Enter your full name" required />
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" id="email" placeholder="Enter your email address" required />
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Phone</label>
          <input type="tel" name="phone" id="phone" placeholder="03XX-XXXXXXX" required />
        </div>
        <div class="form-group">
          <label>City</label>
          <input type="text" name="city" id="city" placeholder="Enter your city" required />
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Country</label>
          <select name="country" id="country" required>
            <option value="Pakistan">Pakistan</option>
          </select>
        </div>
        <div class="form-group">
          <label>Postal Code</label>
          <input type="text" name="postal" id="postal" placeholder="Enter postal code" required />
        </div>
      </div>

      <div class="form-group">
        <label>Shipping Address</label>
        <textarea name="address" id="address" rows="3" placeholder="House No, Street, Area, etc." required></textarea>
      </div>

      <div class="order-section">
        <div class="order-summary" id="orderSummary"></div>
        <div class="payment-box">
          <h3>Payment Method</h3>
          <div class="cod-method">
            <i class="fa-solid fa-truck"></i>
            <span><b>Cash on Delivery</b> — Pay when your order arrives.</span>
          </div>
          <p>You’ll receive a confirmation call before delivery.</p>
        </div>
      </div>

      <input type="hidden" name="items" id="itemsInput" />

      <div class="btn-group">
        <button type="submit" class="btn confirm-btn">Place Order</button>
        <button type="button" class="btn cancel-btn" onclick="window.location.href='cart.php'">Cancel</button>
      </div>
    </form>
  </div>

  <div id="orderModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>

      <h3>✅ Order Placed!</h3>
      <button class="btn confirm-btn" onclick="window.location.href='shop.php'">Continue Shopping</button>

      <h3>🎉 Thank You for Your Purchase!</h3>
      <p class="thankyou-text">
        <strong>Your order has been placed successfully!</strong><br>
        You will receive a confirmation call shortly to verify your order.<br>
        Once confirmed, your order will be <strong>processed within 24 hours</strong> and shipped with <strong>free delivery across Pakistan</strong>.<br>
        Standard delivery time is <strong>3–6 business days</strong>, depending on your location.<br>
        For any shipping-related queries, please contact our <strong>support team</strong> anytime.<br><br>
        <i class="fa-solid fa-truck-fast"></i> Thank you for shopping with us — your satisfaction is our priority!
      </p>

    </div>
  </div>

<footer>
         <div class="footer-container">
            <!-- Left: Customer Support -->
            <div>
                  <h3>Customer Support</h3>
                  <a href="shipping_policy.php">Shipping Policy</a>
                  <a href="refund_policy.php">Refund Policy</a>
                  <a href="privacy_policy.php">Privacy Policy</a>
                  <a href="terms_of_service.php">Terms of Service</a>
                  <a href="contact.php">Contact Information</a>
            </div>

            <!-- Center: About Wrist Win -->
            <div>
                  <h3>About Wrist Win</h3>
                  <p>Luxury Watches crafted with passion and precision. Experience timeless elegance for Men & Women.</p>
            </div>

            <!-- Right: Follow Us -->
            <div>
                  <h3>Follow Us</h3>
                  <a href="https://wa.me/923231508088" target="_blank" style="color:#25D366;"><i class="fab fa-whatsapp"></i> Whatsapp</a>
                  <a href="https://www.instagram.com/wristwin" target="_blank" style="color:#E1306C;"><i class="fab fa-instagram"></i> Instagram</a>
                  <a href="https://www.facebook.com/share/1ANaokHvx8/?mibextid=wwXIfr" target="_blank" style="color:#1877F2;"><i class="fab fa-facebook"></i> Facebook</a>
                  <a href="https://www.tiktok.com/@wristwin" target="_blank" style="color:#ffffff;"><i class="fab fa-tiktok"></i> TikTok</a>
            </div>
         </div>
         <p>© 2025 Wrist Win Watches — Crafted with elegance & love.</p>
      </footer>

<script>
document.addEventListener("DOMContentLoaded", function () {

  // ✅ Load Cart Summary (Correct Price Calculation)
  const cart = JSON.parse(localStorage.getItem("cart")) || [];
  const summary = document.getElementById("orderSummary");
  const itemsInput = document.getElementById("itemsInput");

  if (summary) {
    if (cart.length === 0) {
      summary.innerHTML = "<p style='text-align:center;font-weight:bold;'>Your cart is empty.</p>";
    } else {
      let total = 0;

      summary.innerHTML = "<h3>Order Summary</h3>";

      cart.forEach(item => {

        // ✅ Safe Price Check (NaN fix)
        let price = Number(item.display_price || item.price || 0);
        let qty = Number(item.quantity || 1);
        let itemTotal = price * qty;

        total += itemTotal;

        summary.innerHTML += `
          <div class='order-item'>
            <img src="${item.image}" alt="${item.name}">
            <span>${item.name} (x${qty})</span>
            <span>₨ ${itemTotal.toLocaleString()}</span>
          </div>
        `;
      });

      summary.innerHTML += `
        <div class='order-item'>
          <span>Shipping</span>
          <span style='color:#28a745;'>Free</span>
        </div>

        <div class='order-total'>
          Total: ₨ ${total.toLocaleString()}
        </div>
      `;

      if (itemsInput) {
        itemsInput.value = JSON.stringify(cart);
      }
    }
  }

  // ✅ Modal Logic
  const modal = document.getElementById("orderModal");
  const span = document.querySelector(".close");

  if (span && modal) {
    span.addEventListener("click", () => modal.style.display = "none");
  }

  if (modal) {
    window.addEventListener("click", e => {
      if (e.target === modal) modal.style.display = "none";
    });
  }

  // ✅ Submit Logic
  const checkoutForm = document.getElementById("checkoutForm");

  if (checkoutForm) {
    checkoutForm.addEventListener("submit", async e => {
      e.preventDefault();

      const formData = new FormData(e.target);

      const response = await fetch("checkout.php", {
        method: "POST",
        body: formData
      });

      const result = await response.text();

      if (result === "success") {
        modal.style.display = "flex";
        localStorage.removeItem("cart");
      } else {
        alert("⚠️ Something went wrong. Please try again.");
      }
    });
  }

});
</script>
</body>
</html>