<?php
require_once '../config.php';
$action = $_GET['action'] ?? 'list';

if ($action === 'akun') {
    $r = $conn->query("SELECT * FROM p4_akun ORDER BY kode_akun");
    jsonResponse($r->fetch_all(MYSQLI_ASSOC));
}
if ($action === 'jurnal') {
    $r = $conn->query("SELECT * FROM p4_jurnal ORDER BY id");
    jsonResponse($r->fetch_all(MYSQLI_ASSOC));
}
if ($action === 'laporan') {
    $saldo = [];
    $akun = $conn->query("SELECT * FROM p4_akun ORDER BY kode_akun")->fetch_all(MYSQLI_ASSOC);

    foreach ($akun as $a) {
        $kode = $a['kode_akun'];
        $debit = (float)$conn->query("SELECT COALESCE(SUM(nilai),0) s FROM p4_jurnal WHERE akun_debit='$kode' AND status='valid'")->fetch_assoc()['s'];
        $kredit = (float)$conn->query("SELECT COALESCE(SUM(nilai),0) s FROM p4_jurnal WHERE akun_kredit='$kode' AND status='valid'")->fetch_assoc()['s'];

        if ($a['saldo_normal'] === 'Debit') $saldoAkhir = $debit - $kredit;
        else $saldoAkhir = $kredit - $debit;

        $saldo[] = [
            'kode_akun' => $kode,
            'nama_akun' => $a['nama_akun'],
            'tipe' => $a['tipe'],
            'debit' => $debit,
            'kredit' => $kredit,
            'saldo' => $saldoAkhir,
            'saldo_normal' => $a['saldo_normal']
        ];
    }
    jsonResponse(['akun' => $saldo, 'kas_awal' => 30000000]);
}
if ($action === 'duplikat' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $b = getBody();
    $noTrx = $b['no_transaksi'] ?? 'T4';
    $r = $conn->query("SELECT * FROM p4_jurnal WHERE no_transaksi='$noTrx' AND status='valid'")->fetch_assoc();
    if (!$r) jsonResponse(['error' => 'Transaksi tidak ditemukan'], 404);

    $stmt = $conn->prepare("INSERT INTO p4_jurnal (no_transaksi, tanggal, keterangan, akun_debit, akun_kredit, nilai, status) VALUES (?,?,?,?,?,?,'duplikat')");
    $ket = 'DUPLIKAT - ' . $r['keterangan'];
    $stmt->bind_param('sssssd', $noTrx, $r['tanggal'], $ket, $r['akun_debit'], $r['akun_kredit'], $r['nilai']);
    $stmt->execute();
    $dupId = $stmt->insert_id;

    jsonResponse([
        'success' => true,
        'duplikat_id' => $dupId,
        'pesan' => 'Transaksi ganda terdeteksi',
        'dampak' => "Saldo {$r['akun_debit']} & {$r['akun_kredit']} bertambah Rp " . number_format($r['nilai'], 0, ',', '.')
    ]);
}
if ($action === 'koreksi' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $b = getBody();
    $id = (int)($b['id'] ?? 0);
    $stmt = $conn->prepare("UPDATE p4_jurnal SET status='dikoreksi', keterangan=CONCAT(keterangan, ' [DIKOREKSI]') WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    jsonResponse(['success' => true, 'message' => "Transaksi #$id dikoreksi & dapat ditelusuri"]);
}
jsonResponse(['error' => 'Aksi tidak dikenal'], 400);
?>