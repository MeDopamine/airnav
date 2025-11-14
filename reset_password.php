<?php
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/auth.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$message = '';
$showForm = true;
if (!$token) {
    $message = 'Token tidak diberikan.';
    $showForm = false;
} else {
    // validate token
    $stmt = mysqli_prepare($conn, 'SELECT pr.id AS pr_id, pr.user_id, pr.expires_at, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $token);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    if (!$row) {
        $message = 'Token tidak valid.';
        $showForm = false;
    } elseif (strtotime($row['expires_at']) < time()) {
        $message = 'Token sudah kadaluarsa.';
        $showForm = false;
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pass = $_POST['password'] ?? '';
            $pass2 = $_POST['password_confirm'] ?? '';
            if (strlen($pass) < 6) {
                $message = 'Password minimal 6 karakter.';
            } elseif ($pass !== $pass2) {
                $message = 'Konfirmasi password tidak sesuai.';
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $up = mysqli_prepare($conn, 'UPDATE users SET password_hash = ? WHERE id = ?');
                mysqli_stmt_bind_param($up, 'si', $hash, $row['user_id']);
                mysqli_stmt_execute($up);
                // delete token
                $del = mysqli_prepare($conn, 'DELETE FROM password_resets WHERE id = ?');
                mysqli_stmt_bind_param($del, 'i', $row['pr_id']);
                mysqli_stmt_execute($del);
                $message = 'Password berhasil diubah. Anda dapat masuk sekarang.';
                $showForm = false;
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reset Password - AirNav</title>
  <link rel="stylesheet" href="/dashboard/assets/css/tailwind.output.css">
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center">
  <div class="max-w-md w-full bg-white rounded-xl shadow-md p-8">
    <h1 class="text-xl font-semibold mb-4">Reset Password</h1>
    <?php if ($message): ?>
      <div class="mb-4 text-sm text-gray-800"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($showForm): ?>
    <form method="post" class="space-y-4">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
      <div>
        <label class="block text-sm text-gray-700">Password Baru</label>
        <input name="password" type="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded" required>
      </div>
      <div>
        <label class="block text-sm text-gray-700">Konfirmasi Password</label>
        <input name="password_confirm" type="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded" required>
      </div>
      <div>
        <button class="w-full py-2 px-4 bg-blue-600 text-white rounded">Ubah Password</button>
      </div>
    </form>
    <?php endif; ?>
    <div class="mt-4 text-sm"><a href="/login.php" class="text-blue-600">Kembali ke login</a></div>
  </div>
</body>
</html>
