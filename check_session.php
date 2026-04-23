<?php
session_start();
header('Content-Type: application/json');

$response = ['logged_in' => false];

if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $response['logged_in'] = true;
    $response['username'] = $_SESSION['username'];
    $response['role'] = $_SESSION['role'];
}

echo json_encode($response);
?>