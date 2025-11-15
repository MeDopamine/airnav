<?php
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/auth.php';

$errors = [];
$not_registered = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '') $errors[] = 'Email harus diisi.';
    if ($password === '') $errors[] = 'Password harus diisi.';

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, 'SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) {
            // Email not found — recommend registration
            $not_registered = true;
        } elseif (!password_verify($password, $row['password_hash'])) {
            $errors[] = 'Email atau password salah.';
        } else {
            // login
            login_user_from_row($row);
            // set approved flag in session based on numeric status_data in data_peserta or registrasi_peserta.is_verify
            $approved = 0;
            // First try data_peserta (if there is a premi/participant row tied to this userid)
            $stmt2 = mysqli_prepare($conn, 'SELECT status_data FROM data_peserta WHERE userid = ? ORDER BY id DESC LIMIT 1');
            $foundDataPeserta = false;
            if ($stmt2) {
                $userid_str = (string)$row['id'];
                mysqli_stmt_bind_param($stmt2, 's', $userid_str);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_bind_result($stmt2, $status_data);
                if (mysqli_stmt_fetch($stmt2)) {
                    $foundDataPeserta = true;
                    $approved = ((int)$status_data === 1) ? 1 : 0;
                }
                mysqli_stmt_close($stmt2);
            }
            // If no data_peserta exists for this user, fallback to registrasi_peserta.is_verify
            if (!$foundDataPeserta) {
                $sreg = mysqli_prepare($conn, 'SELECT is_verify FROM registrasi_peserta WHERE email = ? ORDER BY id DESC LIMIT 1');
                if ($sreg) {
                    mysqli_stmt_bind_param($sreg, 's', $row['email']);
                    mysqli_stmt_execute($sreg);
                    $rreg = mysqli_stmt_get_result($sreg);
                    $rrow = mysqli_fetch_assoc($rreg);
                    mysqli_stmt_close($sreg);
                    if ($rrow && !empty($rrow['is_verify'])) {
                        $approved = 1;
                    }
                }
            }
            $_SESSION['approved'] = $approved;
            // redirect users to their profile area, admins to main dashboard
            if (isset($row['role']) && $row['role'] === 'user') {
                header('Location: /dashboard/user/dashboard.php');
            } else {
                header('Location: /dashboard/index.php');
            }
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Masuk - AirNav</title>
    <!-- Icon -->
    <link rel="icon" href="https://placehold.co/32x32/0033A0/FFFFFF?text=M" type="image/png">
    <link rel="stylesheet" href="/dashboard/assets/css/tailwind.output.css">
    <style>
        body {
            font-family: Inter, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-100 flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-xl shadow-md p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">Masuk ke AirNav</h1>
            <p class="text-sm text-gray-500 mt-2">Masukkan email dan password Anda untuk melanjutkan</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 p-3 rounded">
                <ul class="text-sm">
                    <?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($not_registered)): ?>
            <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-700 p-3 rounded">
                <p class="text-sm">Email belum terdaftar. Silakan <a href="/register.php" class="font-medium text-blue-600">daftar</a> terlebih dahulu untuk membuat akun.</p>
            </div>
        <?php endif; ?>

        <div id="client-error" class="mb-4 hidden bg-red-50 border border-red-200 text-red-700 p-3 rounded"></div>

        <form method="post" action="" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input name="email" type="text" value="<?php echo htmlspecialchars($_POST['email'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Password</label>
                <input name="password" type="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>

            <div>
                <button type="submit" class="w-full py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md">Masuk</button>
            </div>
        </form>

        <!-- <div class="mt-4 text-center text-sm text-gray-600">
                        Belum punya akun? <a href="/register.php" class="text-blue-600 hover:underline">Daftar</a>
                    </div> -->
        <script>
            // Client-side validation (simple)
            (function() {
                const form = document.querySelector('form');
                form.addEventListener('submit', function(e) {
                    const email = form.email.value.trim();
                    const pass = form.password.value;
                    let errors = [];
                    if (!email) errors.push('Email harus diisi');
                    if (!pass) errors.push('Password harus diisi');
                    if (errors.length) {
                        e.preventDefault();
                        const container = document.getElementById('client-error');
                        container.innerHTML = '<ul class="text-sm">' + errors.map(er => '<li>' + er + '</li>').join('') + '</ul>';
                        container.classList.remove('hidden');
                        container.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                });
            })();
        </script>
    </div>
</body>

</html>