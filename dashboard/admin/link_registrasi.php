<?php
require_once __DIR__ . '/../../auth.php';
require_login();
require_admin();
require_once __DIR__ . '/../../db/db.php';

header('Content-Type: text/html; charset=utf-8');

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'link') {
    $registrasi_id = intval($_POST['registrasi_id'] ?? 0);
    $nik = trim($_POST['nik'] ?? '');
    if ($registrasi_id <= 0 || $nik === '') {
        $message = ['type' => 'error', 'text' => 'Registrasi ID atau NIK tidak valid.'];
    } else {
        $upd = mysqli_prepare($conn, 'UPDATE registrasi_peserta SET nik = ? WHERE id = ?');
        mysqli_stmt_bind_param($upd, 'si', $nik, $registrasi_id);
        if (mysqli_stmt_execute($upd)) {
            $message = ['type' => 'success', 'text' => 'Berhasil mengaitkan NIK.'];
        } else {
            $message = ['type' => 'error', 'text' => 'Gagal mengaitkan: ' . mysqli_error($conn)];
        }
    }
}

// optional search for data_peserta
$q = trim($_GET['q'] ?? '');
$matches = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = mysqli_prepare($conn, "SELECT id, nik, periode, total_premi, created_at FROM data_peserta WHERE nik LIKE ? OR id LIKE ? OR periode LIKE ? LIMIT 50");
    mysqli_stmt_bind_param($stmt, 'sss', $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) $matches[] = $r;
}

// fetch registrasi_peserta that are not matched or have empty nik
// select only the columns we display so sensitive/unused fields (gaji, jml_premi) are not exposed
$sql = "SELECT r.id, r.nama, r.email, r.nik FROM registrasi_peserta r WHERE (r.nik IS NULL OR r.nik = '') OR NOT EXISTS (SELECT 1 FROM data_peserta d WHERE d.nik = r.nik) ORDER BY r.tgl DESC LIMIT 200";
$res = mysqli_query($conn, $sql);
$registrasis = [];
while ($row = mysqli_fetch_assoc($res)) $registrasis[] = $row;

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin - Kaitkan Registrasi</title>
    <link rel="stylesheet" href="/dashboard/assets/css/tailwind.output.css">
    <script src="/dashboard/assets/js/sweetalert2@11.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Kaitkan Registrasi Peserta</h1>
            <a href="/dashboard/index.php" class="text-sm text-blue-600">Kembali ke Dashboard</a>
        </div>

        <div class="bg-white p-4 rounded shadow mb-4">
            <form method="get" class="flex gap-2">
                <input name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Cari NIK / Periode / ID data_peserta" class="px-3 py-2 border rounded w-full">
                <button class="px-4 py-2 bg-indigo-600 text-white rounded">Cari</button>
            </form>
            <?php if (!empty($matches)): ?>
                <div class="mt-3 overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50"><tr><th class="px-2 py-2">ID</th><th class="px-2 py-2">NIK</th><th class="px-2 py-2">Periode</th><th class="px-2 py-2">Premi</th><th class="px-2 py-2">Tanggal</th></tr></thead>
                        <tbody>
                        <?php foreach ($matches as $m): ?>
                            <tr class="border-t"><td class="px-2 py-2"><?php echo htmlspecialchars($m['id']); ?></td><td class="px-2 py-2"><?php echo htmlspecialchars($m['nik']); ?></td><td class="px-2 py-2"><?php echo htmlspecialchars($m['periode']); ?></td><td class="px-2 py-2"><?php echo htmlspecialchars($m['total_premi']); ?></td><td class="px-2 py-2"><?php echo htmlspecialchars($m['created_at']); ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="bg-white p-4 rounded shadow">
            <h2 class="font-medium mb-3">Registrasi yang belum terkait</h2>
            <div class="overflow-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr><th class="px-2 py-2">ID</th><th class="px-2 py-2">Nama</th><th class="px-2 py-2">Email</th><th class="px-2 py-2">NIK</th><th class="px-2 py-2">Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($registrasis as $r): ?>
                        <tr class="border-t"><td class="px-2 py-2"><?php echo htmlspecialchars($r['id']); ?></td><td class="px-2 py-2"><?php echo htmlspecialchars($r['nama']); ?></td><td class="px-2 py-2"><?php echo htmlspecialchars($r['email']); ?></td><td class="px-2 py-2"><?php echo htmlspecialchars($r['nik']); ?></td>
                        <td class="px-2 py-2">
                            <form method="post" style="display:inline-block">
                                <input type="hidden" name="action" value="link">
                                <input type="hidden" name="registrasi_id" value="<?php echo htmlspecialchars($r['id']); ?>">
                                <input name="nik" placeholder="Masukkan NIK untuk kaitkan" class="px-2 py-1 border rounded" value="<?php echo htmlspecialchars($r['nik']); ?>">
                                <button class="ml-2 px-3 py-1 bg-green-600 text-white rounded">Kaitkan</button>
                            </form>
                        </td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        <?php if ($message): ?>
            Swal.fire({ toast: true, position: 'top-end', icon: '<?php echo $message['type'] === 'success' ? 'success' : 'error'; ?>', title: '<?php echo addslashes($message['text']); ?>', showConfirmButton: false, timer: 3500, timerProgressBar: true });
        <?php endif; ?>
    </script>
</body>
</html>
