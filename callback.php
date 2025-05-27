<?php

header("Content-Type: application/json");

$stkCallBackResponse = file_get_contents('php://input');
$logFile = "mpesaStkResponse.json";
$log = fopen($logFile, 'a');
fwrite($log, $stkCallBackResponse);
fclose($log);

$data = json_decode($stkCallBackResponse);

$MerchantRequestId = $data->Body->stkCallback->MerchantRequestId;
$CheckoutRequestId = $data->Body->stkCallback->CheckoutRequestId;
$ResultCode = $data->Body->stkCallback->ResultCode;

$Amount = $data->Body->stkCallBack->CallBackMetadata->Item[0]->value;
$MpesaReceiptNumber = $data->Body->stkCallBack->CallBackMetadata->Item[1]->value;
$TransactionDate = $data->Body->stkCallBack->CallBackMetadata->Item[3]->value;
$PhoneNumber = $data->Body->stkCallBack->CallBackMetadata->Item[4]->value;


// check if transaction is successful
if ($ResultCode === 0) {
    // store the transaction in the database
    $payment_sql = "INSERT INTO payments (amountpaid, paymentdate, transactionid, phonenumber, paymentstatus) VALUES (?,?,?,?,?)";
    $stmt = $conn->prepare($payment_sql);
    $stmt->bind_param('bdsss', $Amount, $TransactionDate, $MpesaReceiptNumber, $PhoneNumber, $paymentstatus);
    
}

?>