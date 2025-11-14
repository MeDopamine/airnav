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
    $sql = "SELECT DISTINCT jenis_premi FROM data_peserta WHERE periode='" . $periode . "' ORDER BY jenis_premi ASC";
    $res = mysqli_query($conn, $sql);
    $jenis_list = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $jenis_list[] = ['jenis_premi' => $row['jenis_premi']];
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
               COALESCE(SUM(CAST(REPLACE(REPLACE(CAST(jml_premi_krywn AS CHAR), '.', ''), ',', '.') AS DECIMAL(15,2))),0) AS sum_krywn,
               COALESCE(SUM(CAST(REPLACE(REPLACE(CAST(jml_premi_pt AS CHAR), '.', ''), ',', '.') AS DECIMAL(15,2))),0) AS sum_pt,
               COALESCE(SUM(CAST(REPLACE(REPLACE(CAST(total_premi AS CHAR), '.', ''), ',', '.') AS DECIMAL(15,2))),0) AS sum_total,
               COUNT(*) AS jumlah_peserta,
               MAX(created_at) AS created_at
        FROM data_peserta
        GROUP BY periode, jenis_premi
        ORDER BY periode DESC, jenis_premi ASC";

$res = mysqli_query($conn, $sql);
$out = [];
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        $out[] = [
            'periode' => $r['periode'],
            'jenis' => $r['jenis_premi'],
            'sum_krywn' => 0 + $r['sum_krywn'],
            'sum_pt' => 0 + $r['sum_pt'],
            'sum_total' => 0 + $r['sum_total'],
            'jumlah_peserta' => (int)$r['jumlah_peserta'],
            'created_at' => $r['created_at']
        ];
    }
    mysqli_free_result($res);
}
echo json_encode(['ok' => true, 'data' => $out]);
?>
