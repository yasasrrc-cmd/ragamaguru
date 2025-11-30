<?php
// Your ChatBizz Access Token
$accessToken = "692556a44118d";


// Step 2: Send a test message
$sendUrl = "https://app.chatbizz.cc/api/send";

$testMessageData = [
    "number" => "94703929829", // Replace with recipient number
    "type" => "text",
    "message" => "Hello! This is a test message from PHP.test ",
    "instance_id" => '692923B0552B4',
    "access_token" => $accessToken
];

$ch = curl_init($sendUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testMessageData));

$response = curl_exec($ch);
if(curl_errno($ch)) {
    die("cURL error: " . curl_error($ch));
}

$sendResult = json_decode($response, true);
curl_close($ch);

echo "<h3>Send Message Response:</h3>";
echo "<pre>";
print_r($sendResult);
echo "</pre>";
?>
