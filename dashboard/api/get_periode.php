<?php
require_once __DIR__ . '/../../auth.php';
require_login();
// only admin may list periode
if (!is_admin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'data' => [], 'error' => 'Akses ditolak']);
    exit;
}
// API: get_periode.php
// Output: JSON array of all unique periode (desc)
include '../../db/db.php';
header('Content-Type: application/json');
// Return aggregates per periode: sum of jml_premi_krywn, jml_premi_pt, total_premi
$sql = "SELECT periode, jenis_premi,
            COALESCE(SUM(jml_premi_krywn),0) AS sum_krywn, 
            COALESCE(SUM(jml_premi_pt),0) AS sum_pt, 
            COALESCE(SUM(total_premi),0) AS sum_total
        FROM data_peserta 
        GROUP BY periode, jenis_premi 
        ORDER BY periode DESC";
$res = mysqli_query($conn, $sql);
$data = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        // normalize numeric values to numbers
        $row['sum_krywn'] = (float)$row['sum_krywn'];
        $row['sum_pt'] = (float)$row['sum_pt'];
        $row['sum_total'] = (float)$row['sum_total'];
        $data[] = $row;
    }
    mysqli_free_result($res);
}
echo json_encode(['ok'=>true, 'data'=>$data]);
