<?php
require_once __DIR__ . '/../../auth.php';
require_login();
if (!is_admin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => false,
        'data' => [],
        'error' => 'Aksesditolak'
    ]);
    exit;
}
include '../../db/db.php';
header('Content-Type: application/json');
$periode = isset($_GET['periode']) ? mysqli_real_escape_string(
    $conn,
    $_GET['periode']
) : '';
$jenis = isset($_GET['jenis']) ? mysqli_real_escape_string(
    $conn,
    $_GET['jenis']
) : '';
//1.InisialisasidatasebagaiObjekbukanArray
// $data = [];
$data = new stdClass(); // Ini membuat $data menjadi Object {}
if ($periode && $jenis !== '') {
    $sql = "SELECT id, nik, periode, jenis_premi, jml_premi_krywn, jml_premi_pt, total_premi, pic, status_data, created_at FROM data_peserta WHERE periode='$periode' AND jenis_premi='$jenis' ORDER BY id DESC";
    $res = mysqli_query(
        $conn,
        $sql
    );
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            //2.Ambilstatus,pastikan0jikanull
            $status = (int)($row['status_data'] ?? 0);
            //3.Kelompokkandataberdasarkanstatus
            if (!isset($data->{$status})) {
                $data->{$status} = [];
            }
            $data->{$status}[] = $row;
        }
        mysqli_free_result($res);
    }
}
echo json_encode([
    'ok' => true,
    'data' => $data
]);
// log data
error_log("Data peserta by periode: " . json_encode($data));
