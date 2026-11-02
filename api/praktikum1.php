<?php
require_once '../config.php';
$action = $_GET['action'] ?? 'list';

if ($action === 'departemen') {
    $r = $conn->query("SELECT * FROM p1_departemen");
    jsonResponse($r->fetch_all(MYSQLI_ASSOC));
}
if ($action === 'data_master') {
    $r = $conn->query("SELECT * FROM p1_data_master ORDER BY tipe, kode");
    jsonResponse($r->fetch_all(MYSQLI_ASSOC));
}
if ($action === 'pesanan') {
    $r = $conn->query("SELECT * FROM p1_simulasi_pesanan ORDER BY id DESC");
    jsonResponse($r->fetch_all(MYSQLI_ASSOC));
}
if ($action === 'simulasi' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $b = getBody();
    $no = 'SO-' . date('YmdHis');
    $pelanggan = $b['pelanggan'] ?? 'Pelanggan Umum';
    $produk = $b['produk'] ?? 'Produk Jadi A';
    $jumlah = (int)($b['jumlah'] ?? 1);
    $harga = (float)($b['harga'] ?? 100000);
    $total = $jumlah * $harga;
    $tantangan = $b['tantangan'] ?? '';

    $dampakStok = $jumlah;
    $dampakProduksi = "Produksi dijadwalkan untuk $jumlah unit";
    $dampakKeuangan = $total;
    $status = 'dikonfirmasi';

    if ($tantangan === 'stok_kurang') {
        $dampakStok = $jumlah + 50;
        $dampakProduksi = "Stok tidak mencukupi, perlu pembelian bahan baku";
        $dampakKeuangan = $total + 500000;
        $status = 'draft';
    } elseif ($tantangan === 'ubah_jumlah') {
        $dampakProduksi = "Jadwal produksi diubah untuk $jumlah unit";
        $status = 'dikonfirmasi';
    } elseif ($tantangan === 'belum_bayar') {
        $dampakKeuangan = 0;
        $status = 'draft';
    } elseif ($tantangan === 'produksi_telat') {
        $dampakProduksi = "Produksi terlambat, estimasi +3 hari";
        $status = 'produksi';
    }

    $stmt = $conn->prepare("INSERT INTO p1_simulasi_pesanan (no_pesanan, pelanggan, produk, jumlah, harga_satuan, total, status, dampak_stok, dampak_produksi, dampak_keuangan, tantangan) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param('sssiddsisds', $no, $pelanggan, $produk, $jumlah, $harga, $total, $status, $dampakStok, $dampakProduksi, $dampakKeuangan, $tantangan);
    $stmt->execute();

    jsonResponse([
        'success' => true,
        'no_pesanan' => $no,
        'dampak' => [
            'stok' => "Stok berkurang $dampakStok unit",
            'produksi' => $dampakProduksi,
            'keuangan' => "Piutang bertambah Rp " . number_format($dampakKeuangan, 0, ',', '.')
        ]
    ]);
}
jsonResponse(['error' => 'Aksi tidak dikenal'], 400);
?>