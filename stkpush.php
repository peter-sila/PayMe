<?php

session_start();

$errors = array();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = $_POST['phone'];
    $amount = $_POST['amount'];

    if (empty($phone)) {
        $errors['phone'] = 'A valid phone number is required';
    }

    if (empty($amount) || !is_numeric($amount)) {
        $errors['amount'] = 'A valid amount is required';
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: index.html');
        exit();
    }

    // Clean phone number
    $phone = preg_replace('/\D/', '', $phone); // remove all non-digits
    $phoneNumber = '254' . substr($phone, -9);

    // M-Pesa API credentials
    $consumerKey = getenv('consumerKey');
    $consumerSecret = getenv('consumerSecret');

    // Step 1: Get access token
    $oauth_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $credentials = base64_encode($consumerKey . ':' . $consumerSecret);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $oauth_url,
        CURLOPT_HTTPHEADER => ['Authorization: Basic ' . $credentials],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYHOST => 0, 
        CURLOPT_SSL_VERIFYPEER => false, 
    ]);
    $response = curl_exec($curl);

    if (curl_errno($curl)) {
        
        $errors['saf_host_error'] = "System error contact owner. Host error";
        header("Location: index.html");
        die('OAuth Curl error: ' . curl_error($curl));
    }

    $result = json_decode($response);
    if (!isset($result->access_token)) {
        die('Failed to get access token. Response: ' . $response);
    }

    $accessToken = $result->access_token;
    curl_close($curl);

    // Step 2: Make STK Push request
    $stk_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    $shortcode = '174379';
    $passkey = getenv('passkey');
    $timestamp = date('YmdHis');
    $password = base64_encode($shortcode . $passkey . $timestamp);

    $payload = [
        'BusinessShortCode' => $shortcode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phoneNumber,
        'PartyB' => $shortcode,
        'PhoneNumber' => $phoneNumber,
        'CallBackURL' => 'https://d1b69e6dc664.ngrok-free.app/Softwaredevs/payme/callback.php',
        'AccountReference' => 'Test123',
        'TransactionDesc' => 'Payment'
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $stk_url,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,

    ]);

    $stk_response = curl_exec($curl);
    if (curl_errno($curl)) {
        die('STK Push Curl error: ' . curl_error($curl));
    }

    curl_close($curl);

    $decoded_response = json_decode($stk_response, true);
    if (isset($decoded_response['ResponseCode']) && $decoded_response['ResponseCode'] == '0') {
        // Success
        $errors['stkpush_success'] = "Check your phone to enter your MPESA PIN to successfully send the money.";
        header('Location: index.html');
        exit;
    } else {
        echo 'STK Push Failed: ' . $stk_response;
        $errors['stkpush_fail'] = 'MPESA failed to initiate your request. TRY AGAIN';
    }
}
?>
