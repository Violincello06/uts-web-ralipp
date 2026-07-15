<?php
session_start();
require_once 'koneksi.php';

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die('Composer autoload tidak ditemukan. Jalankan "composer install" terlebih dahulu.');
}
require_once __DIR__ . '/vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if (($_SESSION['role'] ?? 'user') !== 'admin') {
    header("Location: user_dashboard.php");
    exit;
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Inisialisasi Spreadsheet
$spreadsheet = new Spreadsheet();

// Setup Styles
$primaryColor = '365CF5'; // Blue
$secondaryColor = '219653'; // Green
$warningColor = 'F2994A'; // Orange
$dangerColor = 'D32F2F'; // Red
$lightGray = 'F1F5F9';

$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 11
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => $primaryColor]
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CBD5E1']
        ]
    ]
];

$subHeaderStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 11
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '475569'] // Gray
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CBD5E1']
        ]
    ]
];

$borderStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'E2E8F0']
        ]
    ]
];

// Helper untuk Auto Fit Column Width
function autoFitColumns($sheet) {
    foreach ($sheet->getColumnIterator() as $column) {
        $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
    }
}

// ==========================================
// SHEET 1: RINGKASAN EKSEKUTIF
// ==========================================
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('Ringkasan Eksekutif');

// Judul
$sheet1->setCellValue('B2', 'LAPORAN KINERJA SNAPGEAR RENTAL');
$sheet1->mergeCells('B2:G2');
$sheet1->getStyle('B2')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1E293B'));
$sheet1->setCellValue('B3', 'Dibuat pada: ' . date('d F Y H:i'));
$sheet1->mergeCells('B3:G3');
$sheet1->getStyle('B3')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));

// Statistik Database
$total_kamera = $conn->query("SELECT COUNT(*) as total FROM kamera")->fetch_assoc()['total'];
$tersedia     = $conn->query("SELECT COUNT(*) as total FROM kamera WHERE status='tersedia'")->fetch_assoc()['total'];
$disewa       = $conn->query("SELECT COUNT(*) as total FROM kamera WHERE status='disewa'")->fetch_assoc()['total'];
$rusak        = $conn->query("SELECT COUNT(*) as total FROM kamera WHERE status='rusak'")->fetch_assoc()['total'];

$total_sewa    = $conn->query("SELECT COUNT(*) as total FROM penyewaan")->fetch_assoc()['total'];
$total_kembali = $conn->query("SELECT COUNT(*) as total FROM pengembalian")->fetch_assoc()['total'];
$pendapatan    = $conn->query("SELECT SUM(total_bayar) as total FROM pembayaran WHERE status='dikonfirmasi'")->fetch_assoc()['total'] ?? 0;
$estimasi_pend = $conn->query("SELECT SUM(total_bayar) as total FROM penyewaan")->fetch_assoc()['total'] ?? 0;

// Render KPI Cards (Visual block)
$sheet1->setCellValue('B5', 'METRIK UTAMA');
$sheet1->mergeCells('B5:D5');
$sheet1->getStyle('B5')->getFont()->setBold(true)->setSize(12)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($primaryColor));

$sheet1->setCellValue('B6', 'Status Kamera');
$sheet1->getStyle('B6')->getFont()->setBold(true);
$sheet1->fromArray([
    ['Total Unit Kamera', $total_kamera],
    ['Tersedia', $tersedia],
    ['Sedang Disewa', $disewa],
    ['Rusak', $rusak]
], null, 'B7');
$sheet1->getStyle('B7:C10')->applyFromArray($borderStyle);
$sheet1->getStyle('B7:B10')->getFont()->setBold(true);

$sheet1->setCellValue('E6', 'Statistik Transaksi');
$sheet1->getStyle('E6')->getFont()->setBold(true);
$sheet1->fromArray([
    ['Total Penyewaan', $total_sewa],
    ['Total Pengembalian', $total_kembali],
    ['Pendapatan Real (Lunas)', (float)$pendapatan],
    ['Estimasi Pendapatan', (float)$estimasi_pend]
], null, 'E7');
$sheet1->getStyle('E7:F10')->applyFromArray($borderStyle);
$sheet1->getStyle('E7:E10')->getFont()->setBold(true);
$sheet1->getStyle('F9:F10')->getNumberFormat()->setFormatCode('"Rp "#,##0');

// Top Kamera Terlaris
$sheet1->setCellValue('B12', 'KAMERA TERPOPULER (Paling Sering Disewa)');
$sheet1->mergeCells('B12:E12');
$sheet1->getStyle('B12')->getFont()->setBold(true)->setSize(12)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($primaryColor));

$sheet1->fromArray(['No', 'Kode Kamera', 'Nama Kamera', 'Merk', 'Tipe', 'Jumlah Disewa'], null, 'B13');
$sheet1->getStyle('B13:G13')->applyFromArray($subHeaderStyle);

$topKameraQuery = "
    SELECT k.kode_kamera, k.nama_kamera, k.merk, k.tipe, COUNT(p.id) as total_pinjam
    FROM penyewaan p
    JOIN kamera k ON p.id_kamera = k.id
    GROUP BY p.id_kamera
    ORDER BY total_pinjam DESC
    LIMIT 5
";
$topKamera = $conn->query($topKameraQuery);
$rowIndex = 14;
$no = 1;
if ($topKamera && $topKamera->num_rows > 0) {
    while ($tk = $topKamera->fetch_assoc()) {
        $sheet1->fromArray([
            $no++,
            $tk['kode_kamera'],
            $tk['nama_kamera'],
            $tk['merk'],
            $tk['tipe'],
            (int)$tk['total_pinjam']
        ], null, 'B' . $rowIndex);
        $sheet1->getStyle('B' . $rowIndex . ':G' . $rowIndex)->applyFromArray($borderStyle);
        $rowIndex++;
    }
} else {
    $sheet1->setCellValue('B14', 'Belum ada data transaksi.');
    $sheet1->mergeCells('B14:G14');
    $sheet1->getStyle('B14')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet1->getStyle('B14')->getFont()->setItalic(true);
    $sheet1->getStyle('B14:G14')->applyFromArray($borderStyle);
}

autoFitColumns($sheet1);

// ==========================================
// SHEET 2: DATA PENYEWAAN
// ==========================================
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Data Penyewaan');

$sheet2->setCellValue('A1', 'LAPORAN DATA PENYEWAAN');
$sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet2->setCellValue('A2', 'Diekspor pada: ' . date('d/m/Y H:i:s'));

$sheet2->fromArray([
    'No', 'Kode Sewa', 'Nama Penyewa', 'Kode Kamera', 'Nama Kamera', 
    'Merk', 'Tanggal Sewa', 'Tanggal Kembali', 'Lama (Hari)', 'Total Bayar', 'Status'
], null, 'A4');
$sheet2->getStyle('A4:K4')->applyFromArray($headerStyle);

$resSewa = $conn->query("
    SELECT p.*, k.nama_kamera, k.kode_kamera, k.merk 
    FROM penyewaan p 
    JOIN kamera k ON p.id_kamera = k.id 
    ORDER BY p.created_at DESC
");

$rowIndex = 5;
$no = 1;
if ($resSewa && $resSewa->num_rows > 0) {
    while ($row = $resSewa->fetch_assoc()) {
        $sheet2->fromArray([
            $no++,
            $row['kode_sewa'],
            $row['nama_penyewa'],
            $row['kode_kamera'],
            $row['nama_kamera'],
            $row['merk'],
            date('d/m/Y', strtotime($row['tanggal_sewa'])),
            date('d/m/Y', strtotime($row['tanggal_kembali'])),
            (int)$row['lama_sewa'],
            (float)$row['total_bayar'],
            ucfirst($row['status'])
        ], null, 'A' . $rowIndex);
        $sheet2->getStyle('A' . $rowIndex . ':K' . $rowIndex)->applyFromArray($borderStyle);
        $sheet2->getStyle('J' . $rowIndex)->getNumberFormat()->setFormatCode('"Rp "#,##0');
        $rowIndex++;
    }
}
autoFitColumns($sheet2);

// ==========================================
// SHEET 3: DATA PENGEMBALIAN
// ==========================================
$sheet3 = $spreadsheet->createSheet();
$sheet3->setTitle('Data Pengembalian');

$sheet3->setCellValue('A1', 'LAPORAN DATA PENGEMBALIAN');
$sheet3->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet3->setCellValue('A2', 'Diekspor pada: ' . date('d/m/Y H:i:s'));

$sheet3->fromArray([
    'No', 'Kode Sewa', 'Nama Penyewa', 'Kode Kamera', 'Nama Kamera', 
    'Rencana Kembali', 'Aktual Kembali', 'Kondisi Kamera', 'Catatan'
], null, 'A4');
$sheet3->getStyle('A4:I4')->applyFromArray($headerStyle);

$resKembali = $conn->query("
    SELECT pb.*, p.kode_sewa, p.nama_penyewa, p.tanggal_kembali AS tgl_rencana, k.nama_kamera, k.kode_kamera 
    FROM pengembalian pb 
    JOIN penyewaan p ON pb.id_penyewaan = p.id 
    JOIN kamera k ON p.id_kamera = k.id 
    ORDER BY pb.created_at DESC
");

$rowIndex = 5;
$no = 1;
if ($resKembali && $resKembali->num_rows > 0) {
    while ($row = $resKembali->fetch_assoc()) {
        $sheet3->fromArray([
            $no++,
            $row['kode_sewa'],
            $row['nama_penyewa'],
            $row['kode_kamera'],
            $row['nama_kamera'],
            date('d/m/Y', strtotime($row['tgl_rencana'])),
            date('d/m/Y', strtotime($row['tanggal_kembali_aktual'])),
            ucwords(str_replace('_', ' ', $row['kondisi_kamera'])),
            $row['catatan'] ?: '-'
        ], null, 'A' . $rowIndex);
        $sheet3->getStyle('A' . $rowIndex . ':I' . $rowIndex)->applyFromArray($borderStyle);
        $rowIndex++;
    }
}
autoFitColumns($sheet3);

// ==========================================
// SHEET 4: DATA PEMBAYARAN
// ==========================================
$sheet4 = $spreadsheet->createSheet();
$sheet4->setTitle('Data Konfirmasi Pembayaran');

$sheet4->setCellValue('A1', 'LAPORAN DATA KONFIRMASI PEMBAYARAN');
$sheet4->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet4->setCellValue('A2', 'Diekspor pada: ' . date('d/m/Y H:i:s'));

$sheet4->fromArray([
    'No', 'Kode Bayar', 'Nama Penyewa', 'Kode Kamera', 'Nama Kamera', 
    'Tgl Sewa', 'Tgl Kembali', 'Lama', 'Total Bayar', 'Metode Bayar', 'Status Pembayaran', 'Catatan Admin'
], null, 'A4');
$sheet4->getStyle('A4:L4')->applyFromArray($headerStyle);

$resBayar = $conn->query("
    SELECT pb.*, k.nama_kamera, k.kode_kamera 
    FROM pembayaran pb 
    JOIN kamera k ON pb.id_kamera = k.id 
    ORDER BY pb.created_at DESC
");

$rowIndex = 5;
$no = 1;
if ($resBayar && $resBayar->num_rows > 0) {
    while ($row = $resBayar->fetch_assoc()) {
        $sheet4->fromArray([
            $no++,
            $row['kode_bayar'],
            $row['nama_penyewa'],
            $row['kode_kamera'],
            $row['nama_kamera'],
            date('d/m/Y', strtotime($row['tanggal_sewa'])),
            date('d/m/Y', strtotime($row['tanggal_kembali'])),
            (int)$row['lama_sewa'] . ' Hari',
            (float)$row['total_bayar'],
            ucfirst($row['metode_bayar'] ?: 'Belum Dipilih'),
            ucfirst($row['status']),
            $row['catatan_admin'] ?: '-'
        ], null, 'A' . $rowIndex);
        $sheet4->getStyle('A' . $rowIndex . ':L' . $rowIndex)->applyFromArray($borderStyle);
        $sheet4->getStyle('I' . $rowIndex)->getNumberFormat()->setFormatCode('"Rp "#,##0');
        $rowIndex++;
    }
}
autoFitColumns($sheet4);

// ==========================================
// SHEET 5: KATALOG KAMERA
// ==========================================
$sheet5 = $spreadsheet->createSheet();
$sheet5->setTitle('Katalog Kamera');

$sheet5->setCellValue('A1', 'LAPORAN KOLEKSI UNIT KAMERA');
$sheet5->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet5->setCellValue('A2', 'Diekspor pada: ' . date('d/m/Y H:i:s'));

$sheet5->fromArray([
    'No', 'Kode Kamera', 'Nama Kamera', 'Merk', 'Tipe', 'Harga Sewa/Hari', 'Stok', 'Status Unit'
], null, 'A4');
$sheet5->getStyle('A4:H4')->applyFromArray($headerStyle);

$resKam = $conn->query("SELECT * FROM kamera ORDER BY kode_kamera ASC");

$rowIndex = 5;
$no = 1;
if ($resKam && $resKam->num_rows > 0) {
    while ($row = $resKam->fetch_assoc()) {
        $sheet5->fromArray([
            $no++,
            $row['kode_kamera'],
            $row['nama_kamera'],
            $row['merk'],
            $row['tipe'],
            (float)$row['harga_sewa'],
            (int)$row['stok'],
            ucfirst($row['status'])
        ], null, 'A' . $rowIndex);
        $sheet5->getStyle('A' . $rowIndex . ':H' . $rowIndex)->applyFromArray($borderStyle);
        $sheet5->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode('"Rp "#,##0');
        $rowIndex++;
    }
}
autoFitColumns($sheet5);

// Set active sheet ke sheet pertama
$spreadsheet->setActiveSheetIndex(0);

// Set Header Download
$fileName = 'laporan-keseluruhan-' . date('YmdHis') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
