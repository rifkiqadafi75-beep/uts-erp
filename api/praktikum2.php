<?php
require_once '../config.php';
$action = $_GET['action'] ?? 'list';

if ($action === 'arsitektur') {
    $r = $conn->query("SELECT * FROM p2_komponen_arsitektur ORDER BY urutan");
    jsonResponse($r->fetch_all(MYSQLI_ASSOC));
}
if ($action === 'akses') {
    $r = $conn->query("SELECT * FROM p2_hak_akses");
    jsonResponse($r->fetch_all(MYSQLI_ASSOC));
}
if ($action === 'gangguan') {
    $r = $conn->query("SELECT * FROM p2_skenario_gangguan");
    jsonResponse($r->fetch_all(MYSQLI_ASSOC));
}
if ($action === 'backup' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $b = getBody();
    $jenis = $b['jenis'] ?? 'full';
    $lokasi = $b['lokasi'] ?? '/backup/erp_' . date('Ymd_His') . '.sql';
    $ukuran = $b['ukuran'] ?? rand(10, 200);
    $stmt = $conn->prepare("INSERT INTO p2_backup_log (jenis, lokasi, ukuran_mb, status) VALUES (?,?,?,'sukses')");
    $stmt->bind_param('ssd', $jenis, $lokasi, $ukuran);
    $stmt->execute();
    jsonResponse(['success' => true, 'message' => "Backup $jenis berhasil"]);
}
if ($action === 'backup_log') {
    $r = $conn->query("SELECT * FROM p2_backup_log ORDER BY id DESC LIMIT 10");
    jsonResponse($r->fetch_all(MYSQLI_ASSOC));
}
jsonResponse(['error' => 'Aksi tidak dikenal'], 400);
?>  