<?php
// Koneksi ke database MySQL
$host = '127.0.0.1';
$user = 'wanda';
$pass = 'dobo1124';
$db = 'DEV_dashboard_airnav';
$port = 3333;
$conn = mysqli_connect($host, $user, $pass, $db, $port);
if (!$conn) {
    die('Koneksi gagal: ' . mysqli_connect_error());
}
