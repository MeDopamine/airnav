<?php
require_once __DIR__ . '/../../auth.php';
require_login();
if (!is_admin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'data' => [], 'msg' => 'Akses ditolak']);
    exit;
}
include '../../db/db.php';
header('Content-Type: application/json');
// Return aggregates per jenis_premi: sum of jml_premi_krywn, jml_premi_pt, and total_premi
$sql = "SELECT IFNULL(jenis_premi,'') AS jenis_premi,
               COALESCE(SUM(CASE WHEN jml_premi_krywn REGEXP '^-?[0-9\\.\\,]+' THEN REPLACE(REPLACE(jml_premi_krywn, '.', ''), ',', '.')+0 ELSE jml_premi_krywn END),0) AS sum_krywn,
               COALESCE(SUM(CASE WHEN jml_premi_pt REGEXP '^-?[0-9\\.\\,]+' THEN REPLACE(REPLACE(jml_premi_pt, '.', ''), ',', '.')+0 ELSE jml_premi_pt END),0) AS sum_pt,
               COALESCE(SUM(CASE WHEN total_premi REGEXP '^-?[0-9\\.\\,]+' THEN REPLACE(REPLACE(total_premi, '.', ''), ',', '.')+0 ELSE total_premi END),0) AS sum_total
        FROM data_peserta
        GROUP BY jenis_premi
        ORDER BY jenis_premi ASC";
$res = mysqli_query($conn, $sql);
$out = [];
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        // ensure numeric values are returned as numbers
        $out[] = [
            'jenis' => $r['jenis_premi'],
            'sum_krywn' => 0 + $r['sum_krywn'],
            'sum_pt' => 0 + $r['sum_pt'],
            'sum_total' => 0 + $r['sum_total']
        ];
    }
    mysqli_free_result($res);
}
echo json_encode(['ok' => true, 'data' => $out]);
