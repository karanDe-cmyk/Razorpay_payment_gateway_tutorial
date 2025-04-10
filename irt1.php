<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set("display_errors", 1);

include_once("../database.php");
$conn = db();

$data = json_decode(file_get_contents("php://input"), true);

$keyId = "rzp_test_TlxM6W37t3zETC"; // Replace with your Razorpay Key
$keySecret = "kWxmCHmtBYxdaLRLEjsT1hQ0";

// Function to make cURL request
function makeCurlRequest($url, $data = [], $isPost = true)
{
    global $keyId, $keySecret;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $keyId . ":" . $keySecret);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

    if ($isPost) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Default response
$response = ["success" => false, "message" => "Invalid request"];

// Create order
if (isset($data['amount'])) {
    $amount = intval($data['amount']); // Convert to paise
    $phone = $data['phone'];
    $unique_id = $data["unique_id"];

    // Insert order with "pending" status (NO success yet)
    $stmt = $conn->prepare("INSERT INTO itr (user_id, phone, amount, transactionId, status) VALUES (?, ?, ?, '', 'pending')");
    $stmt->bind_param("ssi", $unique_id, $phone, $amount);
    $stmt->execute();
    $orderId = $stmt->insert_id;
    $stmt->close();

    // Create Razorpay order
    $orderData = [
        'amount' => $amount * 100,
        'currency' => 'INR',
        'payment_capture' => 1
    ];

    $orderResponse = makeCurlRequest("https://api.razorpay.com/v1/orders", $orderData);

    if (!empty($orderResponse['id'])) {
        // Update the transactionId for the created order
        $stmt = $conn->prepare("UPDATE itr SET transactionId = ? WHERE id = ?");
        $stmt->bind_param("si", $orderResponse['id'], $orderId);
        $stmt->execute();
        $stmt->close();

        $response = [
            "success" => true,
            "order_id" => $orderResponse['id'],
            "amount" => $amount,
            "status" => "pending",  // Set as pending initially
        ];
    } else {
        $response = ["success" => false, "message" => "Order creation failed"];
    }
}

// Return JSON response
echo json_encode($response);
?>
