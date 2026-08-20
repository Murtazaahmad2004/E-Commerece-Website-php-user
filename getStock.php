<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$servername = "sql12.freesqldatabase.com";
$username = "sql12835678";
$password = "YLfYA7BLl4";
$dbname = "sql12835678";
$dbport = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, $dbport);
if ($conn->connect_error) {
    echo json_encode(["error" => $conn->connect_error]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$productIds = $input["product_ids"] ?? [];

if (empty($productIds)) {
    echo json_encode([]);
    exit;
}

$idList = implode(",", array_map("intval", $productIds));

/* -------- CHECK ACTIVE SALE -------- */
$saleQuery = "
SELECT discount_percent 
FROM sales
WHERE status='active'
AND (start_date IS NULL OR start_date <= CURDATE())
AND (end_date IS NULL OR end_date >= CURDATE())
LIMIT 1
";
$saleResult = $conn->query($saleQuery);
$saleRow = $saleResult ? $saleResult->fetch_assoc() : null;
$discount = $saleRow ? (int)$saleRow['discount_percent'] : 0;

/* -------- PRODUCTS -------- */
$query = "SELECT id, name, price, stock, image FROM watches WHERE id IN ($idList)";
$result = $conn->query($query);

$data = [];
while ($row = $result->fetch_assoc()) {

    $originalPrice = (float)$row['price'];
    $finalPrice = ($discount > 0)
        ? $originalPrice - ($originalPrice * $discount / 100)
        : $originalPrice;

    $data[$row["id"]] = [
        "name" => $row["name"],
        "price" => round($finalPrice),          // FINAL PRICE
        "original_price" => $originalPrice,     // FOR DISPLAY
        "discount" => $discount,
        "stock" => $row["stock"],
        "image" => $row["image"]
    ];
}

header("Content-Type: application/json");
echo json_encode($data);
