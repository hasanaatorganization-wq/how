<?php
// Set the default timezone for the script.
date_default_timezone_set('Africa/Nairobi');

// Check if the form has been submitted
if(isset($_POST['submit'])){

  // === Security Best Practices ===
  // NEVER hard-code sensitive credentials in your files.
  // Use environment variables instead for production applications.
  $consumerKey = '491lNwEulYh61NGQ291huGCL1vQPu3EvsJD4zTCWAxBYZOAN'; 
  $consumerSecret = 'IpOW61OlEAYDjkt406V6McAzUNW0swzNiAN5k7jdGkhDAFe1GRoG3lhnegxTTXfd'; 

  // Safaricom's public key (for STK Push)
  $BusinessShortCode = '174379';
  $Passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';  
  
  // === Correcting Input Handling and Validation ===
  // The original code used $_POST['+254716370469'], which is an invalid key for a POST variable.
  // You should get the phone number from a more generic, valid key like 'phone'.
  // Also, validate the input to prevent security vulnerabilities.
  $PartyA = isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : '';
  $Amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;
  
  // You might want to add more robust validation here, e.g., using a regex
  // to check if the phone number is in the correct format.
  if (empty($PartyA) || $Amount <= 0) {
      die("Error: Please provide a valid phone number and a non-zero amount.");
  }

  $AccountReference = 'Donation_HasanaatOrg';
  $TransactionDesc = 'HasanaatOrg';
 
  // Get the timestamp in the required format YYYYmmddhms
  $Timestamp = date('YmdHis');    
  
  // Get the base64 encoded password string
  $Password = base64_encode($BusinessShortCode.$Passkey.$Timestamp);

  // M-PESA API endpoints
  $access_token_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
  $initiate_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

  // Callback URL for transaction status
  $CallBackURL = 'https://hasanaatorganization.com/callback_url.php';  

  // === Getting the Access Token ===
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $access_token_url);
  curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json; charset=utf8']);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($curl, CURLOPT_HEADER, FALSE);
  curl_setopt($curl, CURLOPT_USERPWD, $consumerKey.':'.$consumerSecret);
  
  $result = curl_exec($curl);
  $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

  if ($result === false || $status != 200) {
      die("Error: Failed to get access token. cURL Error: " . curl_error($curl) . " HTTP Status: " . $status);
  }

  $result = json_decode($result);
  $access_token = isset($result->access_token) ? $result->access_token : null;
  
  if (empty($access_token)) {
      die("Error: Access token not found in the response.");
  }
  
  curl_close($curl);

  // === Initiating the STK Push Transaction ===
  $stkheader = ['Content-Type:application/json','Authorization:Bearer '.$access_token];

  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $initiate_url);
  curl_setopt($curl, CURLOPT_HTTPHEADER, $stkheader);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_POST, true);

  $curl_post_data = array(
    'BusinessShortCode' => $BusinessShortCode,
    'Password' => $Password,
    'Timestamp' => $Timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => $Amount,
    'PartyA' => $PartyA,
    'PartyB' => $BusinessShortCode,
    'PhoneNumber' => $PartyA,
    'CallBackURL' => $CallBackURL,
    'AccountReference' => $AccountReference,
    'TransactionDesc' => $TransactionDesc
  );

  $data_string = json_encode($curl_post_data);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
  
  $curl_response = curl_exec($curl);
  $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

  if ($curl_response === false || $status != 200) {
      die("Error: Failed to initiate STK Push. cURL Error: " . curl_error($curl) . " HTTP Status: " . $status);
  }
  
  curl_close($curl);
  
  // Output the response from the M-Pesa API
  echo $curl_response;
};
?>
