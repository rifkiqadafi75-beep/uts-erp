<?php
require_once '../config.php';
$action = $_GET['action'] ?? 'list';

if ($action === 'bahan') {
    $r = $conn->query("SELECT * FROM p5_bahan");
    jsonResponse($r->fetch_all(MYSQLI_ASSOC));
}
if ($action === 'bom') {
    $r = $conn->query("SELECT * FROM p5_bom");
    jsonResponse($r->fetch_all(MYSQLI_ASSOC));
}
if ($action === 'hitung') {
    // Kebutuhan produksi 300 unit
    $produksi = (int)($_GET['produksi'] ?? 300);
    $bahan = $conn->query("SELECT * FROM p5_bahan")->fetch_all(MYSQLI_ASSOC);
    $bom = $conn->query("SELECT * FROM p5_bom")->fetch_all(MYSQLI_ASSOC);

    $hasil = [];
    foreach ($bom as $b) {
        $kode = $b['bahan_kode'];
        $kebutuhanBruto = $produksi * $b['qty_per_unit'];
        $info = null;
        foreach ($bahan as $h) if ($h['kode'] === $kode) $info = $h;
        if (!$info) continue;

        $tersedia = $info['stok_tersedia'] + $info['pesanan_belum_diterima'];
        $netto = max(0, $kebutuhanBruto - $tersedia);
        $cukup = $netto <= 0;

        $hasil[] = [
            'bahan' => $kode,
            'nama' => $info['nama'],
            'qty_per_unit' => $b['qty_per_unit'],
            'kebutuhan_bruto' => $kebutuhanBruto,
            'stok' => $info['stok_tersedia'],
            'on_order' => $info['pesanan_belum_diterima'],
            'kebutuhan_netto' => $netto,
            'lead_time' => $info['waktu_pengadaan_hari'],
            'cukup' => $cukup,
            'perlu_pesan' => !$cukup ? $netto : 0
        ];
    }
    jsonResponse(['produksi' => $produksi, 'hasil' => $hasil]);
}
if ($action === 'buat_po' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $b = getBody();
    $noPo = 'PO-' . date('YmdHis');
    $kode = $b['bahan_kode'] ?? 'BAHAN-A';
    $jumlah = (int)($b['jumlah'] ?? 0);
    $stmt = $conn->prepare("INSERT INTO p5_purchase_order (no_po, bahan_kode, jumlah, status, tanggal) VALUES (?,?,?,'dikirim',CURDATE())");
    $stmt->bind_param('ssi', $noPo, $kode, $jumlah);
    $stmt->execute();
    jsonResponse(['success' => true, 'no_po' => $noPo]);
}
if ($action === 'terima' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $b = getBody();
    $id = (int)($b['id'] ?? 0);
    $jumlahTerima = (int)($b['jumlah'] ?? 0);

    $po = $conn->query("SELECT * FROM p5_purchase_order WHERE id=$id")->fetch_assoc();
    if (!$po) jsonResponse(['error' => 'PO tidak ditemukan'], 404);

    $diterimaBaru = $po['diterima'] + $jumlahTerima;
    $status = $diterimaBaru >= $po['jumlah'] ? 'diterima_penuh' : 'diterima_sebagian';

    $conn->query("UPDATE p5_purchase_order SET diterima=$diterimaBaru, status='$status' WHERE id=$id");
    $conn->query("UPDATE p5_bahan SET stok_tersedia = stok_tersedia + $jumlahTerima WHERE kode='{$po['bahan_kode']}'");

    jsonResponse([
        'success' => true,
        'no_po' => $po['no_po'],
        'diterima_sekarang' => $jumlahTerima,
        'total_diterima' => $diterimaBaru,
        'status' => $status
    ]);
}
if ($action === 'po') {
    $r = $conn->query("SELECT * FROM p5_purchase_order ORDER BY id DESC");
    jsonResponse($r->fetch_all(MYSQLI_ASSOC));
}
if ($action === 'analisis_telat') {
    jsonResponse([
        'kondisi' => 'Pemasok Bahan B kirim 8 hari, produksi harus 6 hari',
        'opsi' => [
            ['tindakan' => 'Cari pemasok alternatif Bahan B', 'waktu' => '2-3 hari', 'biaya' => 'Sedang', 'risiko' => 'Rendah', 'rekomendasi' => true],
            ['tindakan' => 'Gunakan stok dari lokasi/gudang lain', 'waktu' => '1 hari', 'biaya' => 'Rendah', 'risiko' => 'Sedang', 'rekomendasi' => true],
            ['tindakan' => 'Jadwal ulang produksi (+2 hari)', 'waktu' => '0 hari', 'biaya' => 'Tinggi (denda)', 'risiko' => 'Tinggi', 'rekomendasi' => false]
        ],
        'keputusan' => 'Cari pemasok alternatif + gunakan stok cadangan, karena risiko keterlambatan lebih mahal'
    ]);
}
jsonResponse(['error' => 'Aksi tidak dikenal'], 400);
?>