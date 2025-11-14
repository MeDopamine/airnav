<?php
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/auth.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email === '') {
        $message = 'Masukkan email.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id, email FROM users WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($res);
        if (!$user) {
            $message = 'Jika email terdaftar, link reset akan ditampilkan (tidak mengungkapkan apakah email ada).';
        } else {
            $token = bin2hex(random_bytes(16));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            $ins = mysqli_prepare($conn, 'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
            mysqli_stmt_bind_param($ins, 'iss', $user['id'], $token, $expires);
            mysqli_stmt_execute($ins);
            // Display link since we don't have mailer
            $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/reset_password.php?token=' . $token;
            $message = 'Link reset (salin dan buka di browser): <br><a href="' . htmlspecialchars($resetLink) . '">' . htmlspecialchars($resetLink) . '</a>';
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Lupa Password - AirNav</title>
  <link rel="stylesheet" href="/dashboard/assets/css/tailwind.output.css">
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center">
  <div class="max-w-md w-full bg-white rounded-xl shadow-md p-8">
    <h1 class="text-xl font-semibold mb-4">Lupa Password</h1>
    <p class="text-sm text-gray-600 mb-4">Masukkan email Anda. Karena tidak ada mailer, link reset akan ditampilkan di layar jika email terdaftar.</p>
    <?php if ($message): ?>
      <div class="mb-4 text-sm text-gray-800" style="word-break:break-all"><?php echo $message; ?></div>
    <?php endif; ?>
    <form method="post" class="space-y-4">
      <div>
        <label class="block text-sm text-gray-700">Email</label>
        <input name="email" type="email" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded" required>
      </div>
      <div>
        <button class="w-full py-2 px-4 bg-blue-600 text-white rounded">Kirim Link Reset</button>
      </div>
    </form>
    <div class="mt-4 text-sm"><a href="/login.php" class="text-blue-600">Kembali ke login</a></div>
  </div>
</body>
</html>
