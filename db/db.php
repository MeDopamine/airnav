<?php
// Koneksi ke database MySQL
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'airnav';

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die('Koneksi gagal: ' . mysqli_connect_error());
}
?>
