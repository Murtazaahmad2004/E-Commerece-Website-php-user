<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cart - Wrist Win</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="static/styling/user_styling/cart.css">
</head>
<body>

<!-- Navbar -->
<nav>
    <div class="logo"><a href="home.php"><img src="static/logo.webp" alt="wrist-Win"></a></div>
    <div class="nav-links">
        <a class="buttons" href="home.php">Home</a>
        <a class="buttons" href="shop.php">Shop</a>
        <a class="buttons" href="contact.php">Contact</a>
        <a class="buttons" href="cart.php"><i class="fa-solid fa-cart-shopping"></i></a>
    </div>
</nav>

<!-- Cart Container -->
<div class="cart-container">
    <h2>Your Shopping Cart</h2>
    <div id="cartItems"></div>
    <div class="cart-summary">
        <p><strong>Total:</strong> <span id="cartTotal">PKR 0</span></p>
        <button class="checkout-btn" onclick="window.location.href='checkout.php'">Proceed to Checkout</button>
    </div>
</div>

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
            <p>Luxury Watches crafted with passion and precision. Experience timeless elegance.</p>
        </div>
        <div>
            <h3>Follow Us</h3>
            <a href="https://wa.me/923231508088" target="_blank" style="color:#25D366;"><i class="fab fa-whatsapp"></i> Whatsapp</a>
            <a href="https://www.instagram.com/wristwin" target="_blank" style="color:#E1306C;"><i class="fab fa-instagram"></i> Instagram</a>
            <a href="https://www.facebook.com/share/1ANaokHvx8/?mibextid=wwXIfr" target="_blank" style="color:#1877F2;"><i class="fab fa-facebook"></i> Facebook</a>
            <a href="https://www.tiktok.com/@wristwin" target="_blank"><i class="fab fa-tiktok"></i> TikTok</a>
            <a href="mailto:businessinfo.pk47@gmail.com" style="color:#D14836;"><i class="fa-solid fa-envelope"></i> Gmail</a>
        </div>
    </div>
    <p>© 2025 Wrist Win Watches — Crafted with elegance & love.</p>
</footer>


<script>

// ----------------------
// Format PKR
// ----------------------
function formatPKR(amount) {
    return "PKR " + Number(amount || 0).toLocaleString();
}

let stockData = {};
let stockInterval;

// ----------------------
// Load stock from server
// ----------------------
async function loadStock() {
    const cart = JSON.parse(localStorage.getItem("cart")) || [];
    if (cart.length === 0) {
        renderCart();
        return;
    }

    const productIds = cart.map(item => item.id);

    try {
        const response = await fetch("getStock.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ product_ids: productIds })
        });

        const data = await response.json();
        console.log("Stock response:", data);
        stockData = data;
    } 
    catch (err) {
        console.error("Stock load failed:", err);
    }

    renderCart();
}

// ----------------------
// Render Cart
// ----------------------
function renderCart() {
    const cart = JSON.parse(localStorage.getItem("cart")) || [];
    const cartContainer = document.getElementById("cartItems");
    const cartTotal = document.getElementById("cartTotal");

    cartContainer.innerHTML = "";
    let total = 0;

    if (cart.length === 0) {
        cartContainer.innerHTML = "<p style='text-align:center;color:#fff;'>Your cart is empty.</p>";
        cartTotal.textContent = "PKR 0";
        return;
    }

    cart.forEach((item, index) => {
        const stockItem = stockData[item.id] || {
            id: item.id,
            name: item.name,
            price: item.price,
            stock: item.stock,
            image: item.image
        };

        if (item.quantity > stockItem.stock) item.quantity = stockItem.stock;

        total += stockItem.price * item.quantity;

        cartContainer.innerHTML += `
        <div class="cart-item">
            <img src="https://admin.wristwin.shop/${stockItem.image}" alt="${stockItem.name}">
            <div class="cart-details">
                <h3>${stockItem.name}</h3>
                <p>${formatPKR(stockItem.price)}</p>
                <p style="color:yellow;">${stockItem.stock > 0 ? "Stock: " + stockItem.stock : "Out of Stock"}</p>
            </div>

            <div class="cart-quantity">
                <button onclick="changeQty(${index}, -1)" ${stockItem.stock === 0 ? "disabled" : ""}>−</button>
                <span id="qty-${index}">${item.quantity}</span>
                <button onclick="changeQty(${index}, 1)" id="plus-${index}" ${(item.quantity >= stockItem.stock || stockItem.stock === 0) ? "disabled" : ""}>+</button>
            </div>

            <button class="remove-btn" onclick="removeItem(${index})">Remove</button>
        </div>`;
    });

    cartTotal.textContent = formatPKR(total);

    cart.forEach((item, index) => {
        const stockItem = stockData[item.id] || { stock: 0 };
        const plusBtn = document.getElementById(`plus-${index}`);
        if (plusBtn) plusBtn.disabled = item.quantity >= stockItem.stock;
    });
}

// ----------------------
// Change Quantity
// ----------------------
function changeQty(index, change) {
    const cart = JSON.parse(localStorage.getItem("cart")) || [];
    const item = cart[index];
    const stockItem = stockData[item.id] || { stock: 0 };

    if (change === 1 && item.quantity >= stockItem.stock) return;
    if (change === -1 && item.quantity <= 1) return;

    item.quantity += change;
    localStorage.setItem("cart", JSON.stringify(cart));
    renderCart();
}

// ----------------------
// Remove Item
// ----------------------
function removeItem(index) {
    const cart = JSON.parse(localStorage.getItem("cart")) || [];
    cart.splice(index, 1);
    localStorage.setItem("cart", JSON.stringify(cart));
    renderCart();
}

// ----------------------
// Auto Stock Updates
// ----------------------
function startLiveStockUpdates() {
    stockInterval = setInterval(loadStock, 5000);
}

// Init
loadStock();
startLiveStockUpdates();

</script>

</body>
</html>
