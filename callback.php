<?php
// Set response header
header("Content-Type: application/json");

// Read raw POST data
$stkCallBackResponse = file_get_contents('php://input');

// Log raw JSON for auditing
$logFile = "mpesaStkResponse.json";
file_put_contents($logFile, $stkCallBackResponse . PHP_EOL, FILE_APPEND);

// Decode JSON safely
$data = json_decode($stkCallBackResponse);

if (!isset($data->Body->stkCallback)) {
    http_response_code(400);
    echo json_encode(["ResultCode" => 1, "ResultDesc" => "Invalid Callback"]);
    exit;
}

$stkCallback = $data->Body->stkCallback;

// Sanitize basic fields
$MerchantRequestId  = htmlspecialchars($stkCallback->MerchantRequestId ?? '');
$CheckoutRequestId  = htmlspecialchars($stkCallback->CheckoutRequestId ?? '');
$ResultCode         = intval($stkCallback->ResultCode ?? -1);
$ResultDesc         = htmlspecialchars($stkCallback->ResultDesc ?? '');

// Default values
$Amount = 0.0;
$MpesaReceiptNumber = "";
$TransactionDate = "";
$PhoneNumber = "";

// Extract CallbackMetadata items safely
if (isset($stkCallback->CallbackMetadata->Item)) {
    foreach ($stkCallback->CallbackMetadata->Item as $item) {
        switch ($item->Name) {
            case "Amount":
                $Amount = floatval($item->Value);
                break;
            case "MpesaReceiptNumber":
                $MpesaReceiptNumber = htmlspecialchars($item->Value);
                break;
            case "TransactionDate":
                $TransactionDate = htmlspecialchars($item->Value);
                break;
            case "PhoneNumber":
                $PhoneNumber = htmlspecialchars($item->Value);
                break;
        }
    }
}

// Only proceed if transaction was successful
if ($ResultCode === 0) {
    // Include your database connection
    require_once 'db_connect.php'; // Make sure $conn is defined here

    // Example status
    $paymentstatus = "PAID";

    // Prepare and bind SQL statement securely
    $payment_sql = "INSERT INTO payments (amountpaid, paymentdate, transactionid, phonenumber, paymentstatus) 
                    VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($payment_sql);

    if ($stmt) {
        $stmt->bind_param("dssss", $Amount, $TransactionDate, $MpesaReceiptNumber, $PhoneNumber, $paymentstatus);
        $stmt->execute();
        $stmt->close();
    }

    $query_sql = "SELECT balance FROM balance WHERE id=1";
    $result = mysqli_query($conn, $query_sql);

    if ($result) {
        $row = mysqli_fetch_array($result);
        $balance = $row["balance"];
        $new_bal = $balance + $Amount;

        $insert_sql = "INSERT INTO balance (balance) VALUES (?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("d", $new_bal);
        $stmt->execute();
        $stmt->close();
    }

}

// Send success response back to Safaricom
echo json_encode(["ResultCode" => 0, "ResultDesc" => "Callback received successfully"]);
?>
