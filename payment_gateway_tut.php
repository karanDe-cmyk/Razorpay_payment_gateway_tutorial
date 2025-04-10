<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateway Tutorial</title>

</head>

<body>
    <!-- Bootstrap Simple Form -->
    <div class="container mt-5">
        <form id="paymentForm">
            <div class="mb-3">
                <label for="amount1" class="form-label">Amount</label>
                <input type="text" class="form-control" id="amount1" name="amount" placeholder="Enter Amount">
            </div>
            <div class="mb-3">
                <label for="contactNo" class="form-label">Contact Number</label>
                <input type="text" class="form-control" id="contactNo" name="contactNo" placeholder="Enter Contact Number">
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>

    <script>
        function processPayment() {
            let amount = document.getElementById("amount1").value.trim();
            let phone = document.getElementById("contactNo").value.trim();
            let unique_id = document.getElementById("condition").value.trim();

            // Validation checks
            if (!amount || isNaN(amount) || amount <= 0) {
                alert("Please enter a valid amount.");
                return;
            }
            if (!phone || phone.length !== 10 || isNaN(phone)) {
                alert("Please enter a valid 10-digit phone number.");
                return;
            }
            if (!unique_id) {
                alert("Unique ID is required.");
                return;
            }

            // Send AJAX request to create a Razorpay order
            fetch("http://localhost:3000/process/irt1.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        amount: amount,
                        phone: phone,
                        unique_id: unique_id,
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.order_id) {
                        let options = {
                            "key": "Razorpay_key", // Replace with your Razorpay key don't use secret key here
                            "amount": data.amount, // Amount in paisa
                            "currency": "INR",
                            "name": "Letsmakecompany",
                            "description": "Payment for IT Return",
                            "image": "./images/favicon.png",
                            "order_id": data.order_id,
                            "handler": function(response) {
                                fetch("http://localhost:3000/process/verify-payment.php", { // optional but recommended to verify payment
                                        method: "POST",
                                        headers: {
                                            "Content-Type": "application/json"
                                        },
                                        body: JSON.stringify({
                                            payment_id: response.razorpay_payment_id,
                                            order_id: response.razorpay_order_id
                                        })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        // console.log("Full Payment API Response:", data); // just for checking that response is coming or not
                                        if (data.success) {
                                            if (data.orderId) {
                                                alert("Payment Successful!");

                                                window.location.href = 'thankyou.php';// redirect url after success
                                            } else {
                                                console.error("Error: orderId is missing from API response");
                                                alert("Error retrieving order ID!");
                                            }
                                        } else {

                                            alert("Error creating payment order!");
                                        }
                                    })
                                    .catch(error => {
                                        console.error("Verification error:", error);
                                        alert("Error verifying payment. Please try again.");
                                    });
                            },
                            "theme": {
                                "color": "#003a9b"
                            }
                        };

                        let rzp = new Razorpay(options);
                        rzp.open();
                    } else {
                        alert("Error creating payment order. Please try again later.");
                    }
                })
                .catch(error => {
                    console.error("Payment error:", error);
                    alert("An error occurred. Please try again.");
                });
        }
    </script>
</body>

</html>