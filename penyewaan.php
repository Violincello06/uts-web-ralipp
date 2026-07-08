<?php
session_start();
require_once 'koneksi.php';
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die('Composer autoload tidak ditemukan. Jalankan "composer install" terlebih dahulu.');
}
require_once __DIR__ . '/vendor/autoload.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';
    if ($action === 'import') {
        if (isset($_FILES['file_excel']) && $_FILES['file_excel']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['file_excel']['tmp_name'];
            $fileName = $_FILES['file_excel']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['xlsx', 'xls', 'csv'];
            if (!in_array($fileExtension, $allowedExtensions)) {
                header("Location: penyewaan.php?notif=import_error&pesan=" . urlencode("Ekstensi berkas tidak valid. Hanya berkas .xlsx, .xls, dan .csv yang diperbolehkan."));
                exit;
            }
            
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmpPath);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
                
                $inserted = 0;
                $updated = 0;
                $skipped = 0;
                
                $parseDate = function($dateStr) {
                    if (empty($dateStr)) return null;
                    $dateStr = trim($dateStr);
                    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $dateStr, $matches)) {
                        return $matches[3] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    }
                    if (preg_match('/^\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}$/', $dateStr)) {
                        return date('Y-m-d', strtotime($dateStr));
                    }
                    $time = strtotime($dateStr);
                    return $time ? date('Y-m-d', $time) : null;
                };
                
                $startIndex = 1;
                for ($i = 0; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    if (isset($row[1]) && (strcasecmp(trim($row[1]), 'Kode Sewa') === 0 || strcasecmp(trim($row[1]), 'kode_sewa') === 0)) {
                        $startIndex = $i + 1;
                        break;
                    }
                }
                
                for ($i = $startIndex; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    if (count($row) < 4) {
                        $skipped++;
                        continue;
                    }
                    
                    $kode_sewa    = isset($row[1]) ? trim($row[1]) : '';
                    $nama_penyewa = isset($row[2]) ? trim($row[2]) : '';
                    $kamera_str   = isset($row[3]) ? trim($row[3]) : '';
                    $tgl_sewa_raw = isset($row[4]) ? trim($row[4]) : '';
                    $tgl_kem_raw  = isset($row[5]) ? trim($row[5]) : '';
                    $lama_sewa    = isset($row[6]) ? (int)$row[6] : 0;
                    $total_bayar  = isset($row[7]) ? $row[7] : 0;
                    $status       = isset($row[8]) ? strtolower(trim($row[8])) : 'dipinjam';
                    
                    if (empty($kode_sewa) || empty($nama_penyewa) || empty($kamera_str)) {
                        $skipped++;
                        continue;
                    }
                    
                    $tanggal_sewa = $parseDate($tgl_sewa_raw);
                    $tanggal_kembali = $parseDate($tgl_kem_raw);
                    
                    if (!$tanggal_sewa || !$tanggal_kembali) {
                        $skipped++;
                        continue;
                    }
                    
                    $id_kamera = null;
                    if (preg_match('/\(([^)]+)\)$/', $kamera_str, $matches)) {
                        $kode_kamera_parsed = trim($matches[1]);
                        $stmtKam = $conn->prepare("SELECT id FROM kamera WHERE kode_kamera = ?");
                        $stmtKam->bind_param("s", $kode_kamera_parsed);
                        $stmtKam->execute();
                        $resKam = $stmtKam->get_result()->fetch_assoc();
                        $stmtKam->close();
                        if ($resKam) {
                            $id_kamera = (int)$resKam['id'];
                        }
                    }
                    if (!$id_kamera) {
                        $nama_kamera_parsed = trim(preg_replace('/\s*\([^)]+\)$/', '', $kamera_str));
                        $stmtKam = $conn->prepare("SELECT id FROM kamera WHERE nama_kamera = ? LIMIT 1");
                        $stmtKam->bind_param("s", $nama_kamera_parsed);
                        $stmtKam->execute();
                        $resKam = $stmtKam->get_result()->fetch_assoc();
                        $stmtKam->close();
                        if ($resKam) {
                            $id_kamera = (int)$resKam['id'];
                        }
                    }
                    
                    if (!$id_kamera) {
                        $skipped++;
                        continue;
                    }
                    
                    if (!is_numeric($total_bayar)) {
                        $total_bayar = preg_replace('/[^\d]/', '', $total_bayar);
                    }
                    $total_bayar = (float) $total_bayar;
                    
                    if ($lama_sewa <= 0) {
                        $lama_sewa = (strtotime($tanggal_kembali) - strtotime($tanggal_sewa)) / 86400;
                    }
                    
                    if (!in_array($status, ['dipinjam', 'dikembalikan', 'terlambat'])) {
                        $status = 'dipinjam';
                    }
                    
                    $stmtCek = $conn->prepare("SELECT id, id_kamera, status FROM penyewaan WHERE kode_sewa = ?");
                    $stmtCek->bind_param("s", $kode_sewa);
                    $stmtCek->execute();
                    $existing = $stmtCek->get_result()->fetch_assoc();
                    $stmtCek->close();
                    
                    if ($existing) {
                        $existing_id = (int)$existing['id'];
                        $old_status = $existing['status'];
                        $old_id_kamera = (int)$existing['id_kamera'];
                        
                        $stmtUpdate = $conn->prepare("UPDATE penyewaan SET nama_penyewa = ?, id_kamera = ?, tanggal_sewa = ?, tanggal_kembali = ?, lama_sewa = ?, total_bayar = ?, status = ? WHERE id = ?");
                        $stmtUpdate->bind_param("sissidsi", $nama_penyewa, $id_kamera, $tanggal_sewa, $tanggal_kembali, $lama_sewa, $total_bayar, $status, $existing_id);
                        if ($stmtUpdate->execute()) {
                            $updated++;
                            
                            $was_active = in_array($old_status, ['dipinjam', 'terlambat']);
                            $is_active = in_array($status, ['dipinjam', 'terlambat']);
                            
                            if ($old_id_kamera === $id_kamera) {
                                if ($was_active && !$is_active) {
                                    $conn->query("UPDATE kamera SET stok = stok + 1, status = 'tersedia' WHERE id = $id_kamera");
                                } elseif (!$was_active && $is_active) {
                                    $conn->query("UPDATE kamera SET stok = IF(stok > 0, stok - 1, 0), status = IF(stok - 1 <= 0, 'disewa', status) WHERE id = $id_kamera");
                                }
                            } else {
                                if ($was_active) {
                                    $conn->query("UPDATE kamera SET stok = stok + 1, status = 'tersedia' WHERE id = $old_id_kamera");
                                }
                                if ($is_active) {
                                    $conn->query("UPDATE kamera SET stok = IF(stok > 0, stok - 1, 0), status = IF(stok - 1 <= 0, 'disewa', status) WHERE id = $id_kamera");
                                }
                            }
                        } else {
                            $skipped++;
                        }
                        $stmtUpdate->close();
                    } else {
                        $stmtInsert = $conn->prepare("INSERT INTO penyewaan (kode_sewa, nama_penyewa, id_kamera, tanggal_sewa, tanggal_kembali, lama_sewa, total_bayar, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmtInsert->bind_param("ssissids", $kode_sewa, $nama_penyewa, $id_kamera, $tanggal_sewa, $tanggal_kembali, $lama_sewa, $total_bayar, $status);
                        if ($stmtInsert->execute()) {
                            $inserted++;
                            
                            if (in_array($status, ['dipinjam', 'terlambat'])) {
                                $conn->query("UPDATE kamera SET stok = IF(stok > 0, stok - 1, 0), status = IF(stok - 1 <= 0, 'disewa', status) WHERE id = $id_kamera");
                            }
                        } else {
                            $skipped++;
                        }
                        $stmtInsert->close();
                    }
                }
                
                header("Location: penyewaan.php?notif=import_success&inserted=$inserted&updated=$updated&skipped=$skipped");
                exit;
            } catch (\Exception $e) {
                header("Location: penyewaan.php?notif=import_error&pesan=" . urlencode("Gagal membaca file: " . $e->getMessage()));
                exit;
            }
        } else {
            $errorCode = $_FILES['file_excel']['error'] ?? UPLOAD_ERR_NO_FILE;
            header("Location: penyewaan.php?notif=import_error&pesan=" . urlencode("Gagal mengunggah berkas. Kode Error: " . $errorCode));
            exit;
        }
    }
}

// Quick-return action removed: pengembalian sekarang diproses melalui form add_pengembalian.php

// Hapus penyewaan
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    $sewa = $conn->query("SELECT id_kamera, status FROM penyewaan WHERE id = $id")->fetch_assoc();
    if ($sewa) {
        if ($sewa['status'] == 'dipinjam') {
            $conn->query("UPDATE kamera SET stok = stok + 1 WHERE id = {$sewa['id_kamera']}");
        }
        $conn->query("DELETE FROM penyewaan WHERE id = $id");
        header("Location: penyewaan.php?notif=hapus"); exit;
    }
}

$notif  = $_GET['notif'] ?? '';
$cari   = trim($_GET['cari']   ?? '');
$filter = trim($_GET['status'] ?? '');

$where = "WHERE 1=1";
if (!empty($cari))   $where .= " AND (p.nama_penyewa LIKE '%".mysqli_real_escape_string($conn,$cari)."%' OR p.kode_sewa LIKE '%".mysqli_real_escape_string($conn,$cari)."%')";
if (!empty($filter)) $where .= " AND p.status = '".mysqli_real_escape_string($conn,$filter)."'";

$exportParams = $_GET;
unset($exportParams['export'], $exportParams['notif']);
$exportQuery = !empty($exportParams) ? '&' . http_build_query($exportParams) : '';
$export = $_GET['export'] ?? '';
if (in_array($export, ['word', 'xlsx', 'pdf'], true)) {
    if ($export === 'pdf') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $row = $conn->query("SELECT p.*, k.nama_kamera, k.kode_kamera, k.merk, k.tipe, k.harga_sewa FROM penyewaan p JOIN kamera k ON p.id_kamera = k.id WHERE p.id = $id")->fetch_assoc();
        if (!$row) {
            die("Data penyewaan tidak ditemukan.");
        }
        
        $dompdf = new \Dompdf\Dompdf();
        
        $html = '
        <html>
        <head>
            <style>
                body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #333; line-height: 1.4; font-size: 14px; }
                .receipt-container { width: 100%; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
                .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #365CF5; padding-bottom: 10px; }
                .header h1 { margin: 0; font-size: 24px; color: #365CF5; text-transform: uppercase; }
                .header p { margin: 5px 0 0 0; font-size: 12px; color: #666; }
                .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
                .info-table td { padding: 6px 0; vertical-align: top; }
                .info-table td.label { width: 35%; font-weight: bold; color: #555; }
                .info-table td.value { width: 65%; }
                .item-table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
                .item-table th { background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; padding: 10px; text-align: left; font-weight: bold; }
                .item-table td { padding: 10px; border-bottom: 1px solid #dee2e6; }
                .total-section { text-align: right; font-size: 16px; margin-top: 10px; }
                .total-amount { font-size: 20px; font-weight: bold; color: #365CF5; margin-top: 5px; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #777; border-top: 1px solid #eee; padding-top: 15px; }
                .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
                .badge-dipinjam { background-color: #fff3cd; color: #856404; }
                .badge-dikembalikan { background-color: #d4edda; color: #155724; }
                .badge-terlambat { background-color: #f8d7da; color: #721c24; }
            </style>
        </head>
        <body>
            <div class="receipt-container">
                <div class="header">
                    <h1>Bukti Transaksi Sewa</h1>
                    <p>RENTAL KAMERA PREMIUM &middot; Solusi Dokumentasi Terbaik Anda</p>
                    <p>Telp: (021) 123-45678 | Email: support@rentalkamera.com</p>
                </div>
                
                <table class="info-table">
                    <tr>
                        <td class="label">Kode Sewa</td>
                        <td class="value"><strong>' . htmlspecialchars($row['kode_sewa']) . '</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Nama Penyewa</td>
                        <td class="value">' . htmlspecialchars($row['nama_penyewa']) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal Sewa</td>
                        <td class="value">' . date('d F Y', strtotime($row['tanggal_sewa'])) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Rencana Kembali</td>
                        <td class="value">' . date('d F Y', strtotime($row['tanggal_kembali'])) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Status</td>
                        <td class="value">
                            <span class="badge badge-' . htmlspecialchars($row['status']) . '">' . htmlspecialchars($row['status']) . '</span>
                        </td>
                    </tr>
                </table>
                
                <table class="item-table">
                    <thead>
                        <tr>
                            <th>Kamera & Spesifikasi</th>
                            <th style="text-align: right;">Harga/Hari</th>
                            <th style="text-align: center;">Durasi</th>
                            <th style="text-align: right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>' . htmlspecialchars($row['nama_kamera']) . '</strong><br>
                                <span style="font-size: 11px; color: #666;">Kode: ' . htmlspecialchars($row['kode_kamera']) . ' | Merk: ' . htmlspecialchars($row['merk']) . ' | Tipe: ' . htmlspecialchars($row['tipe']) . '</span>
                            </td>
                            <td style="text-align: right;">Rp ' . number_format($row['harga_sewa'], 0, ',', '.') . '</td>
                            <td style="text-align: center;">' . htmlspecialchars($row['lama_sewa']) . ' Hari</td>
                            <td style="text-align: right;">Rp ' . number_format($row['total_bayar'], 0, ',', '.') . '</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="total-section">
                    <div>Total Bayar</div>
                    <div class="total-amount">Rp ' . number_format($row['total_bayar'], 0, ',', '.') . '</div>
                </div>
                
                <div class="footer">
                    <p>Terima kasih telah mempercayakan rental kamera kepada kami!</p>
                    <p>Harap tunjukkan bukti transaksi ini saat pengambilan dan pengembalian unit.</p>
                </div>
            </div>
        </body>
        </html>';
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $fileName = 'bukti-sewa-' . htmlspecialchars($row['kode_sewa']) . '.pdf';
        $dompdf->stream($fileName, ['Attachment' => 0]);
        exit;
    }

    $exportData = [];
    $exportResult = $conn->query("SELECT p.*, k.nama_kamera, k.kode_kamera FROM penyewaan p JOIN kamera k ON p.id_kamera = k.id $where ORDER BY p.created_at DESC");
    if ($exportResult && $exportResult->num_rows) {
        while ($row = $exportResult->fetch_assoc()) {
            $exportData[] = $row;
        }
    }

    if ($export === 'word') {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        $section->addText('Laporan Data Penyewaan', ['bold' => true, 'size' => 16]);
        $section->addTextBreak(1);
        $phpWord->addTableStyle('PenyewaanTable', ['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);
        $table = $section->addTable('PenyewaanTable');
        $headers = ['No','Kode Sewa','Nama Penyewa','Kamera','Tgl Sewa','Tgl Kembali','Lama','Total','Status'];
        $table->addRow();
        foreach ($headers as $header) {
            $table->addCell(1750)->addText($header, ['bold' => true]);
        }
        foreach ($exportData as $index => $row) {
            $table->addRow();
            $table->addCell(1750)->addText($index + 1);
            $table->addCell(1750)->addText($row['kode_sewa']);
            $table->addCell(1750)->addText($row['nama_penyewa']);
            $table->addCell(1750)->addText($row['nama_kamera'] . ' (' . $row['kode_kamera'] . ')');
            $table->addCell(1750)->addText(date('d/m/Y', strtotime($row['tanggal_sewa'])));
            $table->addCell(1750)->addText(date('d/m/Y', strtotime($row['tanggal_kembali'])));
            $table->addCell(1750)->addText($row['lama_sewa'] . ' hari');
            $table->addCell(1750)->addText('Rp ' . number_format($row['total_bayar'], 0, ',', '.'));
            $table->addCell(1750)->addText($row['status']);
        }
        $fileName = 'laporan-penyewaan-' . date('YmdHis') . '.docx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save('php://output');
        exit;
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Penyewaan');
    $sheet->setCellValue('A1', 'Laporan Data Penyewaan');
    $sheet->mergeCells('A1:I1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->fromArray(['No','Kode Sewa','Nama Penyewa','Kamera','Tgl Sewa','Tgl Kembali','Lama (hari)','Total','Status'], null, 'A3');
    $rowNumber = 4;
    foreach ($exportData as $index => $row) {
        $sheet->fromArray([
            $index + 1,
            $row['kode_sewa'],
            $row['nama_penyewa'],
            $row['nama_kamera'] . ' (' . $row['kode_kamera'] . ')',
            date('d/m/Y', strtotime($row['tanggal_sewa'])),
            date('d/m/Y', strtotime($row['tanggal_kembali'])),
            $row['lama_sewa'],
            $row['total_bayar'],
            $row['status']
        ], null, 'A' . $rowNumber);
        $rowNumber++;
    }
    $fileName = 'laporan-penyewaan-' . date('YmdHis') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
    exit;
}

$exportParams = $_GET;
unset($exportParams['export'], $exportParams['notif']);
$exportQuery = !empty($exportParams) ? '&' . http_build_query($exportParams) : '';
$export = $_GET['export'] ?? '';
if (in_array($export, ['word', 'xlsx'], true)) {
    $exportData = [];
    $exportResult = $conn->query("SELECT p.*, k.nama_kamera, k.kode_kamera FROM penyewaan p JOIN kamera k ON p.id_kamera = k.id $where ORDER BY p.created_at DESC");
    if ($exportResult && $exportResult->num_rows) {
        while ($row = $exportResult->fetch_assoc()) {
            $exportData[] = $row;
        }
    }

    if ($export === 'word') {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        $section->addText('Laporan Data Penyewaan', ['bold' => true, 'size' => 16]);
        $section->addTextBreak(1);
        $phpWord->addTableStyle('PenyewaanTable', ['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);
        $table = $section->addTable('PenyewaanTable');
        $headers = ['No','Kode Sewa','Nama Penyewa','Kamera','Tgl Sewa','Tgl Kembali','Lama','Total','Status'];
        $table->addRow();
        foreach ($headers as $header) {
            $table->addCell(1750)->addText($header, ['bold' => true]);
        }
        foreach ($exportData as $index => $row) {
            $table->addRow();
            $table->addCell(1750)->addText($index + 1);
            $table->addCell(1750)->addText($row['kode_sewa']);
            $table->addCell(1750)->addText($row['nama_penyewa']);
            $table->addCell(1750)->addText($row['nama_kamera'] . ' (' . $row['kode_kamera'] . ')');
            $table->addCell(1750)->addText(date('d/m/Y', strtotime($row['tanggal_sewa'])));
            $table->addCell(1750)->addText(date('d/m/Y', strtotime($row['tanggal_kembali'])));
            $table->addCell(1750)->addText($row['lama_sewa'] . ' hari');
            $table->addCell(1750)->addText('Rp ' . number_format($row['total_bayar'], 0, ',', '.'));
            $table->addCell(1750)->addText($row['status']);
        }
        $fileName = 'laporan-penyewaan-' . date('YmdHis') . '.docx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save('php://output');
        exit;
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Penyewaan');
    $sheet->setCellValue('A1', 'Laporan Data Penyewaan');
    $sheet->mergeCells('A1:I1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->fromArray(['No','Kode Sewa','Nama Penyewa','Kamera','Tgl Sewa','Tgl Kembali','Lama (hari)','Total','Status'], null, 'A3');
    $rowNumber = 4;
    foreach ($exportData as $index => $row) {
        $sheet->fromArray([
            $index + 1,
            $row['kode_sewa'],
            $row['nama_penyewa'],
            $row['nama_kamera'] . ' (' . $row['kode_kamera'] . ')',
            date('d/m/Y', strtotime($row['tanggal_sewa'])),
            date('d/m/Y', strtotime($row['tanggal_kembali'])),
            $row['lama_sewa'],
            $row['total_bayar'],
            $row['status']
        ], null, 'A' . $rowNumber);
        $rowNumber++;
    }
    $fileName = 'laporan-penyewaan-' . date('YmdHis') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
    exit;
}

$list = $conn->query("
    SELECT p.*, k.nama_kamera, k.kode_kamera
    FROM penyewaan p
    JOIN kamera k ON p.id_kamera = k.id
    $where
    ORDER BY p.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Penyewaan - Rental Kamera</title>
  <link rel="stylesheet" href="assets/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="assets/css/lineicons.css"/>
  <link rel="stylesheet" href="assets/css/materialdesignicons.min.css"/>
  <link rel="stylesheet" href="assets/css/main.css"/>
  <?php include 'partials/theme_head.php'; ?>
</head>
<body>

<?php include 'partials/sidebar.php'; ?>

<main class="main-wrapper">
  <?php include 'partials/topbar.php'; ?>

  <section class="section">
    <div class="container-fluid">

      <!-- Title -->
      <div class="title-wrapper pt-30">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="title"><h2>Data Penyewaan</h2></div>
          </div>
          <div class="col-md-6">
            <div class="breadcrumb-wrapper">
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="main.php">Dashboard</a></li>
                  <li class="breadcrumb-item active">Penyewaan</li>
                </ol>
              </nav>
            </div>
          </div>
        </div>
      </div>

      <!-- Notifikasi -->
      <?php if ($notif == 'tambah'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="lni lni-checkmark-circle me-2"></i> Data penyewaan berhasil ditambahkan!
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php elseif ($notif == 'edit'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="lni lni-checkmark-circle me-2"></i> Data penyewaan berhasil diperbarui!
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php elseif ($notif == 'hapus'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="lni lni-checkmark-circle me-2"></i> Data penyewaan berhasil dihapus!
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php elseif ($notif == 'kembali'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="lni lni-checkmark-circle me-2"></i> Kamera berhasil dikembalikan & stok diperbarui!
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php elseif ($notif == 'import_success'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="lni lni-checkmark-circle me-2"></i> <strong>Import Berhasil!</strong> Berhasil mengimpor data penyewaan (Ditambahkan: <?= (int)($_GET['inserted'] ?? 0) ?>, Diperbarui: <?= (int)($_GET['updated'] ?? 0) ?>, Dilewati: <?= (int)($_GET['skipped'] ?? 0) ?>).
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php elseif ($notif == 'import_error'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="lni lni-warning me-2"></i> <strong>Import Gagal!</strong> <?= htmlspecialchars($_GET['pesan'] ?? 'Terjadi kesalahan saat mengimpor data.') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Card Tabel -->
      <div class="card-style mb-30">

        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-20">
          <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <div class="input-style-1">
              <input type="text" name="cari" placeholder="Cari nama / kode sewa..." value="<?= htmlspecialchars($cari) ?>" style="min-width:220px;">
            </div>
            <div class="select-style-1">
              <div class="select-position">
                <select name="status">
                  <option value="">-- Semua Status --</option>
                  <option value="dipinjam"     <?= $filter=='dipinjam'     ? 'selected':'' ?>>Dipinjam</option>
                  <option value="dikembalikan" <?= $filter=='dikembalikan' ? 'selected':'' ?>>Dikembalikan</option>
                  <option value="terlambat"    <?= $filter=='terlambat'    ? 'selected':'' ?>>Terlambat</option>
                </select>
              </div>
            </div>
            <button type="submit" class="main-btn primary-btn btn-hover">
              <i class="lni lni-search-alt me-1"></i> Cari
            </button>
            <?php if (!empty($cari) || !empty($filter)): ?>
              <a href="penyewaan.php" class="main-btn deactive-btn-2">Reset</a>
            <?php endif; ?>
          </form>
          <div class="d-flex flex-wrap gap-2">
            <a href="penyewaan.php?export=word<?= htmlspecialchars($exportQuery) ?>" class="main-btn info-btn btn-hover">
              <i class="lni lni-cloud-download me-1"></i> Export Word
            </a>
            <a href="penyewaan.php?export=xlsx<?= htmlspecialchars($exportQuery) ?>" class="main-btn secondary-btn btn-hover">
              <i class="lni lni-cloud-download me-1"></i> Export Excel
            </a>
            <button type="button" class="main-btn primary-btn btn-hover" data-bs-toggle="modal" data-bs-target="#modalImport">
              <i class="lni lni-cloud-upload me-1"></i> Import Excel
            </button>
            <a href="add_penyewaan.php" class="main-btn success-btn btn-hover">
              <i class="lni lni-plus me-1"></i> Tambah Sewa
            </a>
          </div>
        </div>

        <!-- Tabel -->
        <div class="table-responsive">
          <table class="table top-selling-table">
            <thead>
              <tr>
                <th><h6 class="text-sm text-medium">No</h6></th>
                <th><h6 class="text-sm text-medium">Kode Sewa</h6></th>
                <th><h6 class="text-sm text-medium">Nama Penyewa</h6></th>
                <th><h6 class="text-sm text-medium">Kamera</h6></th>
                <th><h6 class="text-sm text-medium">Tgl Sewa</h6></th>
                <th><h6 class="text-sm text-medium">Tgl Kembali</h6></th>
                <th><h6 class="text-sm text-medium">Lama</h6></th>
                <th><h6 class="text-sm text-medium">Total</h6></th>
                <th><h6 class="text-sm text-medium">Status</h6></th>
                <th><h6 class="text-sm text-medium">Aksi</h6></th>
              </tr>
            </thead>
            <tbody>
            <?php if ($list && $list->num_rows > 0): ?>
              <?php $no = 1; while ($row = $list->fetch_assoc()): ?>
              <tr>
                <td><p class="text-sm"><?= $no++ ?></p></td>
                <td><p class="text-sm"><code><?= htmlspecialchars($row['kode_sewa']) ?></code></p></td>
                <td><p class="text-sm"><?= htmlspecialchars($row['nama_penyewa']) ?></p></td>
                <td>
                  <p class="text-sm"><?= htmlspecialchars($row['nama_kamera']) ?></p>
                  <span class="text-xs text-gray"><?= htmlspecialchars($row['kode_kamera']) ?></span>
                </td>
                <td><p class="text-sm"><?= date('d/m/Y', strtotime($row['tanggal_sewa'])) ?></p></td>
                <td><p class="text-sm"><?= date('d/m/Y', strtotime($row['tanggal_kembali'])) ?></p></td>
                <td><p class="text-sm"><?= $row['lama_sewa'] ?> hari</p></td>
                <td><p class="text-sm">Rp <?= number_format($row['total_bayar'], 0, ',', '.') ?></p></td>
                <td>
                  <?php if ($row['status'] == 'dipinjam'): ?>
                    <span class="badge bg-warning text-dark">Dipinjam</span>
                  <?php elseif ($row['status'] == 'dikembalikan'): ?>
                    <span class="badge bg-success">Dikembalikan</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Terlambat</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="action d-flex gap-2 flex-wrap">
                    <?php if ($row['status'] == 'dipinjam'): ?>
                      <a href="add_pengembalian.php?id_penyewaan=<?= $row['id'] ?>"
                         class="main-btn primary-btn-outline btn-hover btn-sm"
                         style="font-size:12px;padding:4px 10px;">
                        <i class="lni lni-checkmark"></i> Proses
                      </a>
                    <?php endif; ?>
                    <a href="penyewaan.php?export=pdf&id=<?= $row['id'] ?>"
                       target="_blank"
                       class="main-btn info-btn btn-hover btn-sm"
                       style="font-size:12px;padding:4px 10px;"
                       title="Cetak PDF Bukti Transaksi">
                      <i class="lni lni-file"></i> PDF
                    </a>
                    <a href="edit_penyewaan.php?id=<?= $row['id'] ?>"
                       class="main-btn warning-btn btn-hover btn-sm"
                       style="font-size:12px;padding:4px 10px;">
                      <i class="lni lni-pencil-alt"></i> Edit
                    </a>
                    <a href="penyewaan.php?hapus=<?= $row['id'] ?>"
                       class="main-btn danger-btn btn-hover btn-sm js-swal-delete"
                       style="font-size:12px;padding:4px 10px;"
                       data-message="Yakin hapus data ini?">
                      <i class="lni lni-trash-can"></i> Hapus
                    </a>
                  </div>
                </td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="10" class="text-center py-4 text-gray">
                  Belum ada data penyewaan. <a href="add_penyewaan.php">Tambah sekarang »</a>
                </td>
              </tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </section>

</main>

<div class="modal fade" id="modalImport" tabindex="-1" aria-labelledby="modalImportLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalImportLabel">Import Penyewaan dari Excel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="penyewaan.php" enctype="multipart/form-data">
        <input type="hidden" name="form_action" value="import">
        <div class="modal-body">
          <div class="input-style-1">
            <label>Pilih File Excel (.xlsx, .xls, .csv) <span class="text-danger">*</span></label>
            <input type="file" name="file_excel" accept=".xlsx, .xls, .csv" required />
          </div>
          <div class="text-muted small mt-2">
            <p><strong>Catatan Format:</strong></p>
            <ul class="list-unstyled ps-3">
              <li>- Format kolom harus sesuai dengan format export Excel:</li>
              <li>  <code>No | Kode Sewa | Nama Penyewa | Kamera | Tgl Sewa | Tgl Kembali | Lama (hari) | Total | Status</code></li>
              <li>- <strong>Kode Sewa</strong>, <strong>Nama Penyewa</strong>, dan <strong>Kamera</strong> wajib diisi.</li>
              <li>- Kolom <strong>Kamera</strong> diisi dengan Nama Kamera diikuti Kode Kamera dalam kurung, contoh: <code>Sony Alpha A7 III (KAM-001)</code>.</li>
              <li>- Tanggal sewa dan tanggal kembali disarankan berformat: <code>dd/mm/yyyy</code> atau <code>yyyy-mm-dd</code>.</li>
              <li>- Status yang valid: <code>dipinjam</code>, <code>dikembalikan</code>, <code>terlambat</code>.</li>
            </ul>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="main-btn secondary-btn btn-hover" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="main-btn primary-btn btn-hover">Mulai Import</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
  document.querySelectorAll('.js-swal-delete').forEach(link => {
    link.addEventListener('click', event => {
      event.preventDefault();
      const target = event.currentTarget;
      Swal.fire({
        title: 'Konfirmasi Hapus',
        text: target.dataset.message || 'Yakin hapus data ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true
      }).then(result => {
        if (result.isConfirmed) {
          window.location.href = target.href;
        }
      });
    });
  });
</script>
</body>
</html>