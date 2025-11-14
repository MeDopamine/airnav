<?php
include_once __DIR__ . '/../../auth.php';
require_login();

$user = current_user();
// only allow users to access their profile; admins can also view via dashboard
if ($user['role'] !== 'user' && $user['role'] !== 'admin') {
    echo 'Akses ditolak.';
    exit;
}

require_once __DIR__ . '/../../db/db.php';
// load dashboard partials
include_once __DIR__ . '/../partials/_init.php';

$registrasi = null;
// Try to find registrasi_peserta by matching email first
$s1 = mysqli_prepare($conn, 'SELECT * FROM registrasi_peserta WHERE email = ? ORDER BY id DESC LIMIT 1');
mysqli_stmt_bind_param($s1, 's', $user['email']);
mysqli_stmt_execute($s1);
$r1 = mysqli_stmt_get_result($s1);
$registrasi = mysqli_fetch_assoc($r1);

// If not found by email, try matching by name
if (!$registrasi) {
    $s2 = mysqli_prepare($conn, 'SELECT * FROM registrasi_peserta WHERE nama = ? ORDER BY id DESC LIMIT 1');
    mysqli_stmt_bind_param($s2, 's', $user['name']);
    mysqli_stmt_execute($s2);
    $r2 = mysqli_stmt_get_result($s2);
    $registrasi = mysqli_fetch_assoc($r2);
}

// Also fetch related data_peserta rows by nik (one-to-many) if registrasi has nik
$relatedData = [];
if ($registrasi && !empty($registrasi['nik'])) {
    $nik = $registrasi['nik'];
    $q = mysqli_prepare($conn, 'SELECT id, nik, periode, total_premi, status_data, created_at FROM data_peserta WHERE nik = ? ORDER BY created_at DESC LIMIT 10');
    mysqli_stmt_bind_param($q, 's', $nik);
    mysqli_stmt_execute($q);
    $resq = mysqli_stmt_get_result($q);
    while ($r = mysqli_fetch_assoc($resq)) $relatedData[] = $r;
}

// determine approval state (reuse same logic as dashboard)
$approved = 0;
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

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Profil Anda</title>
    <!-- Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/tailwind.output.css">
    <link rel="icon" href="https://placehold.co/32x32/0033A0/FFFFFF?text=P" type="image/png">
    <style>
        .datepicker {
            /* Z-index SweetAlert modal adalah 1060.
               Kita atur ini lebih tinggi agar muncul di depan. */
            z-index: 1070 !important;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <?php render_partial('sidebar_user'); ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <?php render_partial('header'); ?>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6 md:p-8">
                <div class="max-w-3xl mx-auto">
                    <div class="bg-white rounded-xl shadow-lg p-6 md:p-8 border border-gray-100">
                        <h2 class="text-2xl font-semibold mb-2 text-gray-900 flex items-center gap-4">
                            <span>Profil Anda</span>
                            <?php if ($approved): ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">Terverifikasi</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-50 text-yellow-800 border border-yellow-200" style="animation: pulse 1.5s infinite;">Sedang Verifikasi</span>
                            <?php endif; ?>
                        </h2>
                        <p class="text-sm text-gray-600 mb-6">Informasi akun dan data registrasi peserta Anda.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-6">
                            <div>
                                <label class="block text-xs text-gray-400 uppercase tracking-wide">NAMA</label>
                                <div id="pf-nama" class="mb-3 text-lg text-gray-900 font-semibold"><?php echo htmlspecialchars($registrasi['nama'] ?? $user['name']); ?></div>
                            </div>

                            <div>
                                <label class="block text-xs text-gray-400 uppercase tracking-wide">EMAIL</label>
                                <div id="pf-email" class="mb-3 text-lg text-gray-900 font-semibold"><?php echo htmlspecialchars($registrasi['email'] ?? $user['email']); ?></div>
                            </div>

                            <div>
                                <label class="block text-xs text-gray-400 uppercase tracking-wide">NOMOR HP</label>
                                <div id="pf-nohp" class="mb-3 text-lg text-gray-900 font-semibold"><?php echo htmlspecialchars($registrasi['no_hp'] ?? ''); ?></div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-400 uppercase tracking-wide">TANGGAL LAHIR</label>
                                <div id="pf-tgl_lahir" class="mb-3 text-lg text-gray-900 font-semibold"><?php
                                    $display_tgl = '-';
                                    if (!empty($registrasi['tgl_lahir']) && $registrasi['tgl_lahir'] !== '0000-00-00') {
                                        $dt = DateTime::createFromFormat('Y-m-d', $registrasi['tgl_lahir']);
                                        if ($dt) $display_tgl = $dt->format('d M Y');
                                        else $display_tgl = htmlspecialchars($registrasi['tgl_lahir']);
                                    }
                                    echo $display_tgl;
                                ?></div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-400 uppercase tracking-wide">NIK</label>
                                <div id="pf-nik" class="mb-3 text-lg text-gray-900 font-semibold"><?php echo htmlspecialchars($registrasi['nik'] ?? '-'); ?></div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-400 uppercase tracking-wide">KELAMIN</label>
                                <div id="pf-kelamin" class="mb-3 text-lg text-gray-900 font-semibold"><?php
                                    $kel = $registrasi['kelamin'] ?? null;
                                    if ($kel === 'L') {
                                        echo 'Laki-laki';
                                    } elseif ($kel === 'P') {
                                        echo 'Perempuan';
                                    } else {
                                        echo '-';
                                    }
                                ?></div>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center gap-4">
                            <a href="edit_profile.php" class="text-blue-600 hover:text-blue-800 font-medium text-sm">Edit Profil</a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <?php render_partial('footer'); ?>
</body>
</html>
