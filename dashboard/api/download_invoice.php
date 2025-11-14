<?php
require_once __DIR__ . '/../../auth.php';
require_login();

include __DIR__ . '/../../db/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Invalid id']);
    exit;
}

$sql = "SELECT * FROM invoice_airnav WHERE id = " . $id . " LIMIT 1";
$res = mysqli_query($conn, $sql);
if (!$res || mysqli_num_rows($res) == 0) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Invoice not found']);
    exit;
}
$row = mysqli_fetch_assoc($res);
mysqli_free_result($res);

$urlinvoice = '';
if (isset($row['urlinvoice']) && $row['urlinvoice']) $urlinvoice = $row['urlinvoice'];
if (!$urlinvoice && isset($row['url_invoice'])) $urlinvoice = $row['url_invoice'];

if (!$urlinvoice) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Invoice file not available']);
    exit;
}

// If urlinvoice is a remote URL, fetch and stream it through PHP (so we can keep access control)
if (preg_match('#^https?://#i', $urlinvoice)) {
    // If caller asked for AJAX probe, perform a HEAD request to determine availability
    if (isset($_GET['ajax'])) {
        $ch_head = curl_init($urlinvoice);
        curl_setopt($ch_head, CURLOPT_NOBODY, true);
        curl_setopt($ch_head, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch_head, CURLOPT_FAILONERROR, true);
        curl_exec($ch_head);
        $httpCode = curl_getinfo($ch_head, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch_head, CURLINFO_CONTENT_TYPE);
        $contentLength = curl_getinfo($ch_head, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        $effectiveUrl = curl_getinfo($ch_head, CURLINFO_EFFECTIVE_URL);
        curl_close($ch_head);
        if ($httpCode >= 200 && $httpCode < 300) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'remote' => true, 'filename' => basename(parse_url($effectiveUrl, PHP_URL_PATH)), 'content_type' => $contentType, 'size' => $contentLength]);
            exit;
        } else {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'Remote file not accessible']);
            exit;
        }
    }
    // Use cURL to fetch remote file
    $ch = curl_init($urlinvoice);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    $data = curl_exec($ch);
    if ($data === false) {
        http_response_code(502);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'msg' => 'Failed to fetch remote file']);
        curl_close($ch);
        exit;
    }
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    // Determine filename
    $fname = basename(parse_url($effectiveUrl, PHP_URL_PATH));
    if (!$fname) $fname = 'invoice_' . $id;

    header('Content-Description: File Transfer');
    header('Content-Type: ' . ($contentType ? $contentType : 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    echo $data;
    exit;
}

// Otherwise, treat as local file path (relative or absolute)
$filePath = $urlinvoice;
// Normalize and resolve path. If path looks absolute (starts with / or drive letter), use as-is.
$isAbsolute = false;
if (preg_match('#^/|^[A-Za-z]:[\\/]#', $filePath)) {
    $isAbsolute = true;
}
if ($isAbsolute) {
    $filePath = realpath($filePath);
} else {
    // relative to project root: build full path and normalize
    $fullPath = __DIR__ . '/../../' . ltrim($filePath, '/\\');
    // Use forward slashes for realpath consistency
    $fullPath = str_replace('\\', '/', $fullPath);
    $filePath = realpath($fullPath);
}

if (!$filePath || !file_exists($filePath)) {
    http_response_code(404);
    // Debug info
    $debug = "URL: $urlinvoice | Resolved: " . ($filePath ?: 'NULL') . " | Exists: " . (file_exists($filePath ?: '') ? 'YES' : 'NO');
    error_log("Download error for id=$id: " . $debug);
    
    // prefer JSON for callers that expect JSON
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'msg' => 'File not found', 'debug' => $debug]);
    } else {
        echo 'File not found: ' . $debug;
    }
    exit;
}

// If caller asked for AJAX probe on local file, return metadata
if (isset($_GET['ajax'])) {
    $fname = basename($filePath);
    $size = filesize($filePath);
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'remote' => false, 'filename' => $fname, 'content_type' => $mime, 'size' => $size]);
    exit;
}

// Basic safety: ensure file is within project folder
$base = realpath(__DIR__ . '/../../');
if (strpos($filePath, $base) !== 0) {
    http_response_code(403);
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'msg' => 'Forbidden']);
    } else {
        echo 'Forbidden';
    }
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $filePath);
finfo_close($finfo);

$fname = basename($filePath);
header('Content-Description: File Transfer');
header('Content-Type: ' . ($mime ? $mime : 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . $fname . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
