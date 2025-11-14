<?php
include_once __DIR__ . '/../../auth.php';
require_login();

$user = current_user();

// determine approval state: check latest data_peserta row for this user (by userid varchar)
$approved = 0;
// First check data_peserta for an approval flag
$stmtApp = mysqli_prepare($conn, 'SELECT status_data FROM data_peserta WHERE userid = ? ORDER BY id DESC LIMIT 1');
if ($stmtApp) {
    $userid_str = (string)$user['id'];
    mysqli_stmt_bind_param($stmtApp, 's', $userid_str);
    mysqli_stmt_execute($stmtApp);
    mysqli_stmt_bind_result($stmtApp, $status_data_val);
    if (mysqli_stmt_fetch($stmtApp)) {
        $approved = ((int)$status_data_val === 1) ? 1 : 0;
    }
    mysqli_stmt_close($stmtApp);
}
// If not approved via data_peserta, fallback to registrasi_peserta.is_verify
if (!$approved) {
    $sreg = mysqli_prepare($conn, 'SELECT is_verify FROM registrasi_peserta WHERE email = ? ORDER BY id DESC LIMIT 1');
    if ($sreg) {
        $email = $user['email'] ?? '';
        mysqli_stmt_bind_param($sreg, 's', $email);
        mysqli_stmt_execute($sreg);
        $r = mysqli_stmt_get_result($sreg);
        $rrow = mysqli_fetch_assoc($r);
        mysqli_stmt_close($sreg);
        if ($rrow && !empty($rrow['is_verify'])) {
            $approved = 1;
        }
    }
}

// load dashboard partials
include_once __DIR__ . '/../partials/_init.php';
require_once __DIR__ . '/../../db/db.php';

// determine display name: prefer registrasi_peserta.nama (by email), then user.nama, then user.name, then fallback to email
$displayName = '';
// try registrasi_peserta first
$s = mysqli_prepare($conn, 'SELECT nama FROM registrasi_peserta WHERE email = ? ORDER BY id DESC LIMIT 1');
mysqli_stmt_bind_param($s, 's', $user['email']);
mysqli_stmt_execute($s);
$r = mysqli_stmt_get_result($s);
$reg = mysqli_fetch_assoc($r);
if ($reg && !empty($reg['nama'])) {
    $displayName = $reg['nama'];
} elseif (!empty($user['nama'])) {
    $displayName = $user['nama'];
}
if (empty($displayName)) $displayName = $user['email'];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard - User</title>
    <!-- Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/tailwind.output.css">
    <link rel="icon" href="https://placehold.co/32x32/0033A0/FFFFFF?text=D" type="image/png">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <?php render_partial('sidebar_user'); ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <?php render_partial('header'); ?>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6 md:p-8">
                <div class="max-w-3xl mx-auto">
                    <!-- Page: Dashboard Utama (Home) -->
                    <div id="page-home" class="page-content space-y-6">
                        <div class="bg-white rounded-xl shadow-md p-8 md:p-10">
                            <h2 class="text-3xl font-bold mb-2">Selamat Datang, <a href="profile.php" id="home-user-name" class="text-blue-600 font-semibold hover:underline"><?php echo htmlspecialchars($displayName); ?></a>!</h2>
                            <p class="text-gray-600 mb-6">Selamat datang di dashboard asuransi Anda. Kelola profil dan lakukan verifikasi invoice dengan mudah.</p>
                            
                            <?php if (!$approved): ?>
                                <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4 mb-6">
                                    <div class="flex items-center gap-4">
                                        <div class="text-yellow-600 mt-0.5"><i class="fa-solid fa-triangle-exclamation fa-fade text-2xl" style="color:#D97706;"></i></div>
                                        <div>
                                            <p class="font-semibold text-yellow-800">Akun Sedang Diverifikasi</p>
                                            <p class="text-sm text-yellow-700">Akun Anda sedang diverifikasi oleh admin. Silakan tunggu sebelum melakukan verifikasi invoice</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($approved): ?>
                                <div id="verifikasi-success" class="bg-green-50 border border-green-300 rounded-lg p-4 mb-6">
                                    <div class="flex items-center gap-4">
                                        <div class="text-green-600 mt-0.5"><i class="fa-solid fa-circle-check fa-bounce text-2xl" style="color:#4CAF50;"></i></div>
                                        <div>
                                            <p class="font-semibold text-green-800">Akun Terverifikasi</p>
                                            <p class="text-sm text-green-700">Akun Anda telah terverifikasi oleh admin. Anda dapat melakukan verifikasi invoice sekarang</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 md:p-6 flex flex-col sm:flex-row items-center sm:justify-between gap-4">

                                <div class="flex-1">
                                    <h3 class="font-semibold text-lg text-blue-900">Invoice</h3>
                                    <p class="text-sm text-blue-800 mt-3 leading-relaxed">Lakukan verifikasi invoice baru Anda untuk periode ini</p>
                                </div>

                                <div class="flex-shrink-0">
                                    <a href="riwayat_invoice.php" class="inline-flex items-center justify-center px-6 py-3 rounded-full bg-blue-600 text-white font-semibold shadow-md hover:bg-blue-700">
                                        <i class="fa-solid fa-file-invoice mr-3"></i>
                                        <span>Lihat Invoice</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
        // Auto-hide the verification success message after 10 seconds (only when present)
        // Also show this message only once per user after approval (persists in localStorage)
        (function(){
            try {
                var verEl = document.getElementById('verifikasi-success');
                var currentApproved = <?php echo $approved ? 'true' : 'false'; ?>;
                var storageKey = 'airnav_verifikasi_shown_user_' + <?php echo json_encode((string)$user['id']); ?>;

                if (currentApproved) {
                    // If we've shown this before for this user, hide immediately
                    var shown = null;
                    try { shown = localStorage.getItem(storageKey); } catch(e) { shown = null; }
                    if (shown === '1') {
                        if (verEl) verEl.classList.add('hidden');
                    } else {
                        // show (it's rendered) and auto-hide after 10s, then mark as shown
                        if (verEl) {
                            verEl.classList.add('transition-opacity','duration-500');
                            setTimeout(function(){
                                verEl.style.opacity = '0';
                                setTimeout(function(){
                                    verEl.classList.add('hidden');
                                }, 500);
                            }, 5000);
                        }
                        try { localStorage.setItem(storageKey, '1'); } catch(e) { /* ignore */ }
                    }
                } else {
                    // reset flag so message will show next time they become approved
                    try { localStorage.setItem(storageKey, '0'); } catch(e) { /* ignore */ }
                    if (verEl) verEl.classList.add('hidden');
                }
            } catch(e) { /* no-op */ }
        })();
    </script>
</body>
</html>
