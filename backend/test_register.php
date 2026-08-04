<?php
$ch = curl_init('http://localhost:8000/api/register/consumer');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
$data = [
    'full_name' => 'John Doe',
    'email' => 'john2@example.com',
    'phone_number' => '09123456789',
    'password' => 'password',
    'password_confirmation' => 'password'
];
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo 'HTTP: ' . $httpcode . "\n";
echo $response;
