<?php
// Create a default admin user if none exists. Run this once from CLI or browser.
require_once __DIR__ . '/db.php';

$email = 'admin@localhost';
$name = 'Administrator';
$password = 'admin123'; // CHANGE THIS after first login
$hash = password_hash($password, PASSWORD_BCRYPT);

$res = mysqli_query($conn, "SELECT id FROM users WHERE email = '" . mysqli_real_escape_string($conn, $email) . "' LIMIT 1");
if (mysqli_num_rows($res) > 0) {
    echo "Admin sudah ada.\n";
    exit;
}

$stmt = mysqli_prepare($conn, 'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, "admin")');
mysqli_stmt_bind_param($stmt, 'sss', $name, $email, $hash);
if (mysqli_stmt_execute($stmt)) {
    echo "Admin dibuat: $email / $password\n";
} else {
    echo "Gagal membuat admin: " . mysqli_error($conn) . "\n";
}
mysqli_close($conn);
?>
