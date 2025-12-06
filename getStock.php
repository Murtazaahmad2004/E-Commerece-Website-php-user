<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "u459954629_hostinger";
$password = "Root@2004@2004";
$dbname = "u459954629_ecommercestore";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => $conn->connect_error]));
}

$inputJSON = file_get_contents("php://input");
$input = json_decode($inputJSON, true);
$productIds = $input["product_ids"] ?? [];

if (empty($productIds)) {
    echo json_encode([]);
    exit;
}

$idList = implode(",", array_map("intval", $productIds));

$query = "SELECT id, name, price, stock, image FROM watches WHERE id IN ($idList)";
$result = $conn->query($query);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[$row["id"]] = $row;
}

header("Content-Type: application/json");
echo json_encode($data);
