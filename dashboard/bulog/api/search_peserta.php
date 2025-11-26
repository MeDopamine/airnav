<?php
// API untuk search data peserta berdasarkan nama (untuk bulog admin)
require_once __DIR__ . '/../../../auth.php';
require_login();
require_adminbl();

include '../../../db/db.php';
header('Content-Type: application/json');

$searchName = isset($_GET['name']) ? trim($_GET['name']) : '';

if (empty($searchName)) {
    echo json_encode([
        'ok' => false,
        'data' => [],
        'msg' => 'Nama peserta tidak boleh kosong'
    ]);
    exit;
}

// Escape input untuk SQL
$searchName = mysqli_real_escape_string($conn, $searchName);

// Query mencari data peserta berdasarkan nama, hanya yang status = 1 (aktif)
$sql = "SELECT 
            id,
            periode,
            jenis_premi,
            nik,
            CASE 
                WHEN nama IS NOT NULL AND nama != '' THEN nama
                ELSE 'N/A'
            END AS nama,
            tgl_lahir,
            tgl_diangkat,
            tmt_asuransi,
            isg,
            isik,
            jml_rapel,
            jml_premi_krywn,
            jml_premi_pt,
            total_premi,
            status,
            status_data,
            created_at,
            notas
        FROM data_peserta
        WHERE status = 1 AND (
            LOWER(nama) LIKE LOWER('%$searchName%') OR
            LOWER(nik) LIKE LOWER('%$searchName%')
        )
        ORDER BY periode DESC, created_at DESC
        LIMIT 500";

$res = mysqli_query($conn, $sql);

if (!$res) {
    echo json_encode([
        'ok' => false,
        'data' => [],
        'msg' => 'Database error: ' . mysqli_error($conn)
    ]);
    exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($res)) {
    $data[] = $row;
}
mysqli_free_result($res);

if (empty($data)) {
    echo json_encode([
        'ok' => true,
        'data' => [],
        'msg' => 'Tidak ada data peserta dengan pencarian: ' . htmlspecialchars($searchName)
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'data' => $data,
    'count' => count($data)
]);
?>
