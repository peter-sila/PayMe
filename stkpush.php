<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phoneNumber = $_POST['phone'];
    $amount = $_POST['amount'];

    // M-Pesa API credentials
    $consumerKey = 'CpAt5V328RRp9nUnobwDGV2pUAqZrVASBcADT18RByA8m8lN';
    $consumerSecret = 'kzrrHfwmhdKDhY8IEpGnEioQTD6gegbgCitiDXl9lBA3MNg1xwbKHBeHifxIs2X5';

    // Get the OAuth token
    $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $credentials = base64_encode($consumerKey . ':' . $consumerSecret);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec($curl);
    if ($response === false) {
        die('Curl error: ' . curl_error($curl));
    }

    $result = json_decode($response);
    if (isset($result->access_token)) {
        $accessToken = $result->access_token;
    } else {
        die('Failed to obtain access token. Response: ' . $response);
    }

    curl_close($curl);

    echo 'Success' . '<br>';
    // echo 'Access Token: ' . $accessToken . '<br>';


    // Prepare STK Push request
    $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    $timestamp = date('YmdHis');
    $shortcode = '174379';
    $lipaNaMpesaOnlinePasskey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
    $password = base64_encode($shortcode . $lipaNaMpesaOnlinePasskey . $timestamp);

    $curl_post_data = array(
        'BusinessShortCode' => $shortcode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phoneNumber,
        'PartyB' => $shortcode,
        'PhoneNumber' => $phoneNumber,
        'CallBackURL' => 'https://d854-154-155-185-159.ngrok-free.app/Lauch/PayMe/callback.php', 
        'AccountReference' => 'Test123',
        'TransactionDesc' => 'Payment'
    );

    $data_string = json_encode($curl_post_data);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

    $response = curl_exec($curl);

    if ($response === false) {
        die('Curl error: ' . curl_error($curl));
    }

    // echo 'STK Push Response: ' . $response;
    echo 'Success';
    header('Location: index.html');

    curl_close($curl);
}
?>
