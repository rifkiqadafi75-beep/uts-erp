-- ============================================
-- DATABASE ERP PRAKTIKUM - Politeknik META Industri
-- ============================================
DROP DATABASE IF EXISTS erp_praktikum;
CREATE DATABASE erp_praktikum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE erp_praktikum;

-- ============ PRAKTIKUM 1: Evolusi & Integrasi ============
CREATE TABLE p1_departemen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    sistem_lama VARCHAR(100),
    masalah TEXT
);

CREATE TABLE p1_data_master (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    tipe ENUM('pelanggan','produk','pemasok','bahan_baku','karyawan') NOT NULL,
    departemen_pemilik VARCHAR(100),
    departemen_pengguna TEXT,
    keterangan TEXT
);

CREATE TABLE p1_simulasi_pesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_pesanan VARCHAR(30) UNIQUE NOT NULL,
    pelanggan VARCHAR(100),
    produk VARCHAR(100),
    jumlah INT,
    harga_satuan DECIMAL(15,2),
    total DECIMAL(15,2),
    status ENUM('draft','dikonfirmasi','produksi','selesai','batal') DEFAULT 'draft',
    dampak_stok INT DEFAULT 0,
    dampak_produksi VARCHAR(200),
    dampak_keuangan DECIMAL(15,2) DEFAULT 0,
    tantangan VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO p1_departemen (nama, sistem_lama, masalah) VALUES
('Penjualan', 'Spreadsheet', 'Data pesanan terlambat diperbarui'),
('Gudang', 'Aplikasi stok', 'Stok berbeda dengan catatan pembelian'),
('Produksi', 'Formulir manual', 'Jadwal produksi sering berubah'),
('Keuangan', 'Aplikasi akuntansi', 'Transaksi harus dimasukkan ulang');

INSERT INTO p1_data_master (kode, nama, tipe, departemen_pemilik, departemen_pengguna, keterangan) VALUES
('CUST-001', 'PT Maju Jaya', 'pelanggan', 'Penjualan', 'Penjualan, Keuangan', 'Master pelanggan'),
('PROD-001', 'Produk Jadi A', 'produk', 'Produksi', 'Penjualan, Gudang, Produksi', 'Master produk jadi'),
('SUP-001', 'CV Sumber Bahan', 'pemasok', 'Pembelian', 'Pembelian, Gudang, Keuangan', 'Master pemasok'),
('BAHAN-A', 'Bahan A', 'bahan_baku', 'Gudang', 'Gudang, Produksi, Pembelian', 'Master bahan baku A'),
('BAHAN-B', 'Bahan B', 'bahan_baku', 'Gudang', 'Gudang, Produksi, Pembelian', 'Master bahan baku B');

-- ============ PRAKTIKUM 2: Arsitektur Teknis ============
CREATE TABLE p2_komponen_arsitektur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    layer VARCHAR(50),
    komponen VARCHAR(100),
    deskripsi TEXT,
    urutan INT
);

CREATE TABLE p2_hak_akses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    departemen VARCHAR(50),
    modul_penjualan TINYINT DEFAULT 0,
    modul_gudang TINYINT DEFAULT 0,
    modul_produksi TINYINT DEFAULT 0,
    modul_pembelian TINYINT DEFAULT 0,
    modul_keuangan TINYINT DEFAULT 0
);

CREATE TABLE p2_skenario_gangguan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(10),
    nama VARCHAR(150),
    penanganan TEXT,
    dampak_operasional TEXT
);

CREATE TABLE p2_backup_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenis ENUM('full','incremental','restore') NOT NULL,
    lokasi VARCHAR(200),
    ukuran_mb DECIMAL(10,2),
    status ENUM('sukses','gagal') DEFAULT 'sukses',
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO p2_komponen_arsitektur (layer, komponen, deskripsi, urutan) VALUES
('Client', 'Browser Pengguna', 'Akses via web browser', 1),
('Presentation', 'Web Server / UI', 'Antarmuka aplikasi', 2),
('Application', 'Server Aplikasi ERP', 'Logika bisnis & API', 3),
('Database', 'MySQL Server', 'Penyimpanan data terpusat', 4),
('Backup', 'Storage Pencadangan', 'Backup harian otomatis', 5);

INSERT INTO p2_hak_akses (departemen, modul_penjualan, modul_gudang, modul_produksi, modul_pembelian, modul_keuangan) VALUES
('Penjualan', 1,0,0,0,0),
('Gudang', 1,1,1,1,0),
('Produksi', 0,1,1,0,0),
('Pembelian', 0,1,0,1,1),
('Keuangan', 1,0,0,1,1),
('Admin', 1,1,1,1,1);

INSERT INTO p2_skenario_gangguan (kode, nama, penanganan, dampak_operasional) VALUES
('G1', 'Database tidak dapat diakses', 'Restore dari backup terakhir, cek service MySQL, gunakan read-replica sementara', 'Transaksi tertunda, data master tidak dapat dibaca'),
('G2', 'Server aplikasi gangguan', 'Failover ke server aplikasi cadangan, restart service', 'Akses pengguna terputus sementara'),
('G3', 'Koneksi internet terputus', 'Gunakan mode offline terbatas / backup link 4G', 'Akses eksternal terganggu, internal tetap berjalan'),
('G4', 'Pengguna tidak berwenang', 'Cek RBAC, log audit, blokir sesi', 'Potensi kebocoran data keuangan'),
('G5', 'Data hilang karena backup gagal', 'Restore point terakhir, audit log, investigasi', 'Kehilangan data transaksi sampai restore');

-- ============ PRAKTIKUM 3: Proses Bisnis & Alur Persetujuan ============
CREATE TABLE p3_permintaan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_permintaan VARCHAR(30) UNIQUE NOT NULL,
    pengaju VARCHAR(100) NOT NULL,
    departemen VARCHAR(50),
    nama_barang VARCHAR(100),
    jumlah INT,
    satuan VARCHAR(20),
    status ENUM('diajukan','diverifikasi','stok_cek','pilih_pemasok','persetujuan','disetujui','ditolak','diterima','dibayar') DEFAULT 'diajukan',
    stok_tersedia INT DEFAULT 0,
    pemasok VARCHAR(100),
    nilai_awal DECIMAL(15,2) DEFAULT 0,
    nilai_penawaran_baru DECIMAL(15,2) DEFAULT 0,
    perlu_approval_ulang TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE p3_aturan_bisnis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(10) UNIQUE NOT NULL,
    aturan TEXT NOT NULL,
    kategori VARCHAR(50)
);

INSERT INTO p3_aturan_bisnis (kode, aturan, kategori) VALUES
('R1', 'Permintaan harus memiliki identitas pengaju', 'Validasi'),
('R2', 'Permintaan pembelian harus diperiksa terhadap stok', 'Stok'),
('R3', 'Pesanan pembelian harus disetujui oleh pihak yang berwenang', 'Approval'),
('R4', 'Pembayaran harus didukung dokumen yang sesuai', 'Keuangan'),
('R5', 'Jika nilai pembelian naik > 10% dari nilai awal, approval harus dilakukan ulang', 'Approval');

CREATE TABLE p3_aktor_tanggung_jawab (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aktor VARCHAR(100),
    tanggung_jawab TEXT,
    tahap VARCHAR(50)
);

INSERT INTO p3_aktor_tanggung_jawab (aktor, tanggung_jawab, tahap) VALUES
('Dosen', 'Mengajukan permintaan bahan', 'Pengajuan'),
('Kepala Laboratorium', 'Memverifikasi kebutuhan', 'Verifikasi'),
('Gudang', 'Memeriksa stok & menerima barang', 'Cek Stok & Penerimaan'),
('Bagian Pembelian', 'Memilih pemasok', 'Pengadaan'),
('Pimpinan', 'Memberikan persetujuan', 'Persetujuan'),
('Keuangan', 'Memproses pembayaran', 'Pembayaran');

-- ============ PRAKTIKUM 4: Modul Keuangan ============
CREATE TABLE p4_akun (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_akun VARCHAR(10) UNIQUE NOT NULL,
    nama_akun VARCHAR(100) NOT NULL,
    tipe ENUM('Aset','Kewajiban','Ekuitas','Pendapatan','Beban') NOT NULL,
    saldo_normal ENUM('Debit','Kredit') NOT NULL
);

CREATE TABLE p4_jurnal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_transaksi VARCHAR(10),
    tanggal DATE,
    keterangan TEXT,
    akun_debit VARCHAR(10),
    akun_kredit VARCHAR(10),
    nilai DECIMAL(15,2),
    status ENUM('valid','duplikat','dikoreksi') DEFAULT 'valid',
    koreksi_dari INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (koreksi_dari) REFERENCES p4_jurnal(id) ON DELETE SET NULL
);

INSERT INTO p4_akun (kode_akun, nama_akun, tipe, saldo_normal) VALUES
('101', 'Kas', 'Aset', 'Debit'),
('102', 'Piutang Usaha', 'Aset', 'Debit'),
('103', 'Persediaan', 'Aset', 'Debit'),
('201', 'Utang Usaha', 'Kewajiban', 'Kredit'),
('401', 'Penjualan', 'Pendapatan', 'Kredit'),
('501', 'Beban Operasional', 'Beban', 'Debit');

INSERT INTO p4_jurnal (no_transaksi, tanggal, keterangan, akun_debit, akun_kredit, nilai, status) VALUES
('T1', CURDATE(), 'Pembelian bahan baku secara kredit', '103', '201', 8000000, 'valid'),
('T2', CURDATE(), 'Pembayaran utang pemasok', '201', '101', 3000000, 'valid'),
('T3', CURDATE(), 'Penjualan produk secara kredit', '102', '401', 12000000, 'valid'),
('T4', CURDATE(), 'Penerimaan pembayaran pelanggan', '101', '102', 5000000, 'valid'),
('T5', CURDATE(), 'Pembayaran biaya operasional tunai', '501', '101', 1500000, 'valid');

-- ============ PRAKTIKUM 5: SCM & Pembelian ============
CREATE TABLE p5_bahan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(10) UNIQUE NOT NULL,
    nama VARCHAR(100),
    stok_tersedia INT,
    pesanan_belum_diterima INT,
    waktu_pengadaan_hari INT,
    satuan VARCHAR(20),
    pemasok VARCHAR(100)
);

CREATE TABLE p5_bom (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produk_jadi VARCHAR(100),
    bahan_kode VARCHAR(10),
    qty_per_unit INT,
    FOREIGN KEY (bahan_kode) REFERENCES p5_bahan(kode) ON DELETE CASCADE
);

CREATE TABLE p5_purchase_order (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_po VARCHAR(30) UNIQUE NOT NULL,
    bahan_kode VARCHAR(10),
    jumlah INT,
    status ENUM('draft','dikirim','diterima_sebagian','diterima_penuh','batal') DEFAULT 'draft',
    diterima INT DEFAULT 0,
    tanggal DATE,
    FOREIGN KEY (bahan_kode) REFERENCES p5_bahan(kode) ON DELETE CASCADE
);

INSERT INTO p5_bahan (kode, nama, stok_tersedia, pesanan_belum_diterima, waktu_pengadaan_hari, satuan, pemasok) VALUES
('BAHAN-A', 'Bahan A', 400, 100, 4, 'unit', 'CV Sumber Bahan'),
('BAHAN-B', 'Bahan B', 250, 0, 7, 'unit', 'PT Material Prima');

INSERT INTO p5_bom (produk_jadi, bahan_kode, qty_per_unit) VALUES
('Produk Jadi A', 'BAHAN-A', 3),
('Produk Jadi A', 'BAHAN-B', 2);