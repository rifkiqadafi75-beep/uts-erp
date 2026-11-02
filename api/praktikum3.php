<?php
require_once '../config.php';
$action = $_GET['action'] ?? 'list';

if ($action === 'permintaan') {
    $r = $conn->query("SELECT * FROM p3_permintaan ORDER BY id DESC");
    jsonResponse($r->fetch_all(MYSQLI_ASSOC));
}
if ($action === 'aturan') {
    $r = $conn->query("SELECT * FROM p3_aturan_bisnis ORDER BY kode");
    jsonResponse($r->fetch_all(MYSQLI_ASSOC));
}
if ($action === 'aktor') {
    $r = $conn->query("SELECT * FROM p3_aktor_tanggung_jawab");
    jsonResponse($r->fetch_all(MYSQLI_ASSOC));
}
if ($action === 'buat' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $b = getBody();
    $no = 'PR-' . date('YmdHis');
    $pengaju = $b['pengaju'] ?? 'Dosen';
    $dept = $b['departemen'] ?? 'Laboratorium';
    $barang = $b['nama_barang'] ?? 'Bahan Praktikum';
    $jumlah = (int)($b['jumlah'] ?? 10);
    $satuan = $b['satuan'] ?? 'unit';
    $stok = (int)($b['stok_tersedia'] ?? 0);
    $pemasok = $b['pemasok'] ?? 'CV Sumber Bahan';
    $nilaiAwal = (float)($b['nilai_awal'] ?? 1000000);

    $status = $stok >= $jumlah ? 'disetujui' : 'pilih_pemasok';
    $stmt = $conn->prepare("INSERT INTO p3_permintaan (no_permintaan, pengaju, departemen, nama_barang, jumlah, satuan, status, stok_tersedia, pemasok, nilai_awal) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param('ssssissssd', $no, $pengaju, $dept, $barang, $jumlah, $satuan, $status, $stok, $pemasok, $nilaiAwal);
    $stmt->execute();
    jsonResponse(['success' => true, 'no_permintaan' => $no, 'status' => $status]);
}
if ($action === 'penawaran_baru' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $b = getBody();
    $id = (int)($b['id'] ?? 0);
    $nilaiBaru = (float)($b['nilai_penawaran_baru'] ?? 0);

    $r = $conn->query("SELECT * FROM p3_permintaan WHERE id=$id")->fetch_assoc();
    if (!$r) jsonResponse(['error' => 'Data tidak ditemukan'], 404);

    $kenaikan = ($r['nilai_awal'] > 0) ? (($nilaiBaru - $r['nilai_awal']) / $r['nilai_awal'] * 100) : 0;
    $perluUlang = $kenaikan > 10 ? 1 : 0;
    $status = $perluUlang ? 'persetujuan' : $r['status'];

    $stmt = $conn->prepare("UPDATE p3_permintaan SET nilai_penawaran_baru=?, perlu_approval_ulang=?, status=? WHERE id=?");
    $stmt->bind_param('disi', $nilaiBaru, $perluUlang, $status, $id);
    $stmt->execute();

    jsonResponse([
        'success' => true,
        'nilai_awal' => $r['nilai_awal'],
        'nilai_baru' => $nilaiBaru,
        'kenaikan_persen' => round($kenaikan, 2),
        'perlu_approval_ulang' => $perluUlang === 1,
        'keterangan' => $perluUlang ? 'Nilai naik > 10%, approval ulang wajib (R5)' : 'Approval sebelumnya masih berlaku'
    ]);
}
jsonResponse(['error' => 'Aksi tidak dikenal'], 400);
?>