<?php
require_once __DIR__ . '/../../auth.php';
require_login();
if (!is_admin_or_admintl()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'data' => [], 'msg' => 'Akses ditolak']);
    exit;
}
include '../../db/db.php';
header('Content-Type: application/json');

$mode = isset($_GET['mode']) ? trim($_GET['mode']) : 'all';
$periode = isset($_GET['periode']) ? trim($_GET['periode']) : '';
$jenis = isset($_GET['jenis']) ? trim($_GET['jenis']) : '';

// Normalize periode to YYYYMM
if ($periode) {
    $digits = preg_replace('/[^0-9]/', '', $periode);
    if (preg_match('/^\d{4}-\d{2}$/', $periode)) {
        $periode = str_replace('-', '', $periode);
    } elseif (preg_match('/^\d{6}$/', $digits)) {
        $periode = $digits;
    }
    $periode = mysqli_real_escape_string($conn, $periode);
}

// Mode: periods - return DISTINCT periode only (no duplicates)
if ($mode === 'periods') {
    $sql = "SELECT DISTINCT periode FROM data_peserta ORDER BY periode DESC";
    $res = mysqli_query($conn, $sql);
    $periods = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $periods[] = ['periode' => $row['periode']];
        }
        mysqli_free_result($res);
    }
    echo json_encode(['ok' => true, 'data' => $periods]);
    exit;
}

// Mode: periode_jenis - return distinct jenis_premi for a specific periode
if ($mode === 'periode_jenis') {
    if (!$periode) {
        echo json_encode(['ok' => false, 'msg' => 'Periode required']);
        exit;
    }
    $sql = "SELECT DISTINCT jenis_premi FROM data_peserta WHERE periode='" . $periode . "' AND status_data = 1 ORDER BY jenis_premi ASC";
    $res = mysqli_query($conn, $sql);
    $jenis_premi_map = [
        '1' => 'JHT REGULAR', // JHT Regular
        '2' => 'JHT TOPUP', // JHT Topup
        '3' => 'PKP REGULAR', // PKP Regular
    ];
    $jenis_list = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $value = $row['jenis_premi'];
            $name = $jenis_premi_map[$value] ?? $value;
            $jenis_list[] = [
                'jenis_value' => $value,
                'jenis_name'  => $name
            ];
        }
        mysqli_free_result($res);
    }
    echo json_encode(['ok' => true, 'data' => $jenis_list]);
    exit;
}

// Mode: jenis_detail - return jumlah_peserta (count) and total_premi (sum) for a specific periode+jenis
if ($mode === 'jenis_detail') {
    if (!$periode) {
        echo json_encode(['ok' => false, 'msg' => 'Periode required']);
        exit;
    }
    $sql = "SELECT COUNT(*) as jumlah_peserta, SUM(total_premi) as total_premi FROM data_peserta WHERE periode='" . $periode . "'";
    if ($jenis !== '') {
        $jenis = mysqli_real_escape_string($conn, $jenis);
        $sql .= " AND jenis_premi='" . $jenis . "'";
    }
    $res = mysqli_query($conn, $sql);
    if ($res && $row = mysqli_fetch_assoc($res)) {
        mysqli_free_result($res);
        $jumlah = intval($row['jumlah_peserta']);
        $total = floatval($row['total_premi'] ?? 0);
        echo json_encode(['ok' => true, 'jumlah_peserta' => $jumlah, 'total_premi' => $total]);
        exit;
    }
    echo json_encode(['ok' => false, 'msg' => 'Query failed']);
    exit;
}

// Default mode: 'all' - return aggregates per periode-jenis combination
$sql = "SELECT periode,
               IFNULL(jenis_premi,'') AS jenis_premi,
               IFNULL(status_data, 0) AS status_data,
               COALESCE(SUM(CAST(REPLACE(REPLACE(CAST(jml_premi_krywn AS CHAR), '.', ''), ',', '.') AS DECIMAL(15,2))),0) AS sum_krywn,
               COALESCE(SUM(CAST(REPLACE(REPLACE(CAST(jml_premi_pt AS CHAR), '.', ''), ',', '.') AS DECIMAL(15,2))),0) AS sum_pt,
               COALESCE(SUM(CAST(REPLACE(REPLACE(CAST(total_premi AS CHAR), '.', ''), ',', '.') AS DECIMAL(15,2))),0) AS sum_total,
               COUNT(*) AS jumlah_peserta,
               MAX(created_at) AS created_at,
               CASE WHEN COUNT(CASE WHEN status_data = 1 THEN 1 END) = COUNT(*) THEN 1
                    WHEN COUNT(CASE WHEN status_data = 1 THEN 1 END) > 0 THEN 2
                    ELSE 0 END AS approval_status
        FROM data_peserta
        GROUP BY periode, jenis_premi, status_data
        ORDER BY periode DESC, jenis_premi ASC, status_data ASC";

$res = mysqli_query($conn, $sql);
$out = [];
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        $out[] = [
            'periode' => $r['periode'],
            'jenis' => $r['jenis_premi'],
            'status_data' => (int)$r['status_data'],
            'sum_krywn' => 0 + $r['sum_krywn'],
            'sum_pt' => 0 + $r['sum_pt'],
            'sum_total' => 0 + $r['sum_total'],
            'jumlah_peserta' => (int)$r['jumlah_peserta'],
            'created_at' => $r['created_at'],
            'approval_status' => (int)$r['approval_status']
        ];
    }
    mysqli_free_result($res);
}
echo json_encode(['ok' => true, 'data' => $out]);
