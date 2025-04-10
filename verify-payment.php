<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set("display_errors", 1);

include_once("../database.php");
$conn = db();

$keyId = "rzp_test_TlxM6W37t3zETC"; // Replace with your Razorpay Key
$keySecret = "kWxmCHmtBYxdaLRLEjsT1hQ0";

// Read input JSON
$data = json_decode(file_get_contents("php://input"), true);
$paymentId = isset($data["payment_id"]) ? trim($data["payment_id"]) : "";
$orderId = isset($data["order_id"]) ? trim($data["order_id"]) : "";

// Function to make a cURL request
function makeCurlRequest($url, $auth) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Basic " . $auth
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ["error" => ["message" => $error]];
    }

    return json_decode($response, true);
}

// Verify Razorpay payment
if ($paymentId && $orderId) {
    $url = "https://api.razorpay.com/v1/payments/" . $paymentId;
    $auth = base64_encode($keyId . ":" . $keySecret);

    $paymentResponse = makeCurlRequest($url, $auth);

    error_log("Razorpay API Error Response: " . json_encode($paymentResponse));

    // Send response as JSON to frontend
    // header("Content-Type: application/json");

    // echo json_encode([
    //     "debug" => $paymentResponse,
    //     "success" => false,
    //     "message" => "Debugging API response"
    // ]);
    // exit;

    if (!empty($paymentResponse["status"]) && $paymentResponse["status"] === "captured") {
        // Update database to set status as "success"
        $stmt = $conn->prepare("UPDATE itr SET status = 'success' WHERE transactionId = ?");
        if ($stmt) {
            $stmt->bind_param("s", $orderId);
            $stmt->execute();
            $stmt->close();

            echo json_encode(["success" => true, "message" => "Payment verified successfully", "orderId" => $orderId]);
            exit;
        } else {
            error_log("Database error: " . $conn->error);
            echo json_encode(["success" => false, "message" => "Database update failed"]);
            exit;
        }
    } else {
        error_log("Payment verification failed: " . json_encode($paymentResponse));
        echo json_encode(["success" => false, "message" => "Payment verification failed"]);
        exit;
    }
} else {
    error_log("Invalid payment data received.");
    echo json_encode(["success" => false, "message" => "Invalid payment data"]);
    exit;
}
