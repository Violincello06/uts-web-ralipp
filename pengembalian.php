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
                header("Location: pengembalian.php?notif=import_error&pesan=" . urlencode("Ekstensi berkas tidak valid. Hanya berkas .xlsx, .xls, dan .csv yang diperbolehkan."));
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
                    if (count($row) < 7) {
                        $skipped++;
                        continue;
                    }
                    
                    $kode_sewa        = isset($row[1]) ? trim($row[1]) : '';
                    $tgl_kembali_raw  = isset($row[5]) ? trim($row[5]) : '';
                    $kondisi_kamera   = isset($row[6]) ? strtolower(trim($row[6])) : 'baik';
                    $catatan          = isset($row[7]) ? trim($row[7]) : '';
                    
                    if (empty($kode_sewa) || empty($tgl_kembali_raw)) {
                        $skipped++;
                        continue;
                    }
                    
                    $tanggal_kembali_aktual = $parseDate($tgl_kembali_raw);
                    if (!$tanggal_kembali_aktual) {
                        $skipped++;
                        continue;
                    }
                    
                    if (!in_array($kondisi_kamera, ['baik', 'rusak_ringan', 'rusak_berat'])) {
                        $kondisi_kamera = 'baik';
                    }
                    
                    $stmtSewa = $conn->prepare("SELECT id, id_kamera, tanggal_kembali, status FROM penyewaan WHERE kode_sewa = ?");
                    $stmtSewa->bind_param("s", $kode_sewa);
                    $stmtSewa->execute();
                    $sewa = $stmtSewa->get_result()->fetch_assoc();
                    $stmtSewa->close();
                    
                    if (!$sewa) {
                        $skipped++;
                        continue;
                    }
                    
                    $id_penyewaan = (int)$sewa['id'];
                    $id_kamera = (int)$sewa['id_kamera'];
                    
                    $stmtCek = $conn->prepare("SELECT id, kondisi_kamera FROM pengembalian WHERE id_penyewaan = ?");
                    $stmtCek->bind_param("i", $id_penyewaan);
                    $stmtCek->execute();
                    $resCek = $stmtCek->get_result()->fetch_assoc();
                    $stmtCek->close();
                    
                    if ($resCek) {
                        $existing_id = $resCek['id'];
                        $old_kondisi = $resCek['kondisi_kamera'];
                        
                        $stmtUpdate = $conn->prepare("UPDATE pengembalian SET tanggal_kembali_aktual = ?, kondisi_kamera = ?, catatan = ? WHERE id = ?");
                        $stmtUpdate->bind_param("sssi", $tanggal_kembali_aktual, $kondisi_kamera, $catatan, $existing_id);
                        if ($stmtUpdate->execute()) {
                            $updated++;
                            
                            $terlambat = $tanggal_kembali_aktual > $sewa['tanggal_kembali'];
                            $status_baru = $terlambat ? 'terlambat' : 'dikembalikan';
                            $conn->query("UPDATE penyewaan SET status='$status_baru' WHERE id = $id_penyewaan");
                            
                            if ($old_kondisi !== $kondisi_kamera) {
                                if ($kondisi_kamera === 'rusak_berat') {
                                    $conn->query("UPDATE kamera SET status = 'rusak' WHERE id = $id_kamera");
                                } elseif ($old_kondisi === 'rusak_berat') {
                                    $conn->query("UPDATE kamera SET status = 'tersedia' WHERE id = $id_kamera");
                                }
                            }
                        } else {
                            $skipped++;
                        }
                        $stmtUpdate->close();
                    } else {
                        $stmtInsert = $conn->prepare("INSERT INTO pengembalian (id_penyewaan, tanggal_kembali_aktual, kondisi_kamera, catatan) VALUES (?,?,?,?)");
                        $stmtInsert->bind_param("isss", $id_penyewaan, $tanggal_kembali_aktual, $kondisi_kamera, $catatan);
                        if ($stmtInsert->execute()) {
                            $inserted++;
                            
                            $terlambat = $tanggal_kembali_aktual > $sewa['tanggal_kembali'];
                            $status_baru = $terlambat ? 'terlambat' : 'dikembalikan';
                            $conn->query("UPDATE penyewaan SET status='$status_baru' WHERE id = $id_penyewaan");
                            
                            if ($sewa['status'] === 'dipinjam') {
                                $conn->query("UPDATE kamera SET stok = stok + 1, status = 'tersedia' WHERE id = $id_kamera");
                            }
                            
                            if ($kondisi_kamera === 'rusak_berat') {
                                $conn->query("UPDATE kamera SET status = 'rusak' WHERE id = $id_kamera");
                            }
                        } else {
                            $skipped++;
                        }
                        $stmtInsert->close();
                    }
                }
                
                header("Location: pengembalian.php?notif=import_success&inserted=$inserted&updated=$updated&skipped=$skipped");
                exit;
            } catch (\Exception $e) {
                header("Location: pengembalian.php?notif=import_error&pesan=" . urlencode("Gagal membaca file: " . $e->getMessage()));
                exit;
            }
        } else {
            $errorCode = $_FILES['file_excel']['error'] ?? UPLOAD_ERR_NO_FILE;
            header("Location: pengembalian.php?notif=import_error&pesan=" . urlencode("Gagal mengunggah berkas. Kode Error: " . $errorCode));
            exit;
        }
    }
}

// Hapus pengembalian
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    // Ambil data pengembalian & penyewaan terkait
    $pgb = $conn->query("
        SELECT pb.id_penyewaan, p.id_kamera, p.status, k.kondisi_kamera as kondisi_baru
        FROM pengembalian pb
        JOIN penyewaan p ON pb.id_penyewaan = p.id
        JOIN pengembalian k ON k.id = pb.id
        WHERE pb.id = $id
    ")->fetch_assoc();

    if ($pgb) {
        // Rollback: status penyewaan kembali dipinjam, stok kamera dikurangi lagi
        $conn->query("UPDATE penyewaan SET status='dipinjam' WHERE id = {$pgb['id_penyewaan']}");
        $conn->query("UPDATE kamera SET stok = stok - 1, status = IF(stok - 1 <= 0, 'disewa', 'tersedia') WHERE id = {$pgb['id_kamera']}");
        $conn->query("DELETE FROM pengembalian WHERE id = $id");
    }
    header("Location: pengembalian.php?notif=hapus"); exit;
}

$notif  = $_GET['notif'] ?? '';
$cari   = trim($_GET['cari']   ?? '');
$filter = trim($_GET['kondisi'] ?? '');

$where = "WHERE 1=1";
if (!empty($cari))   $where .= " AND (p.nama_penyewa LIKE '%".mysqli_real_escape_string($conn,$cari)."%' OR p.kode_sewa LIKE '%".mysqli_real_escape_string($conn,$cari)."%')";
if (!empty($filter)) $where .= " AND pb.kondisi_kamera = '".mysqli_real_escape_string($conn,$filter)."'";

$exportParams = $_GET;
unset($exportParams['export'], $exportParams['notif']);
$exportQuery = !empty($exportParams) ? '&' . http_build_query($exportParams) : '';
$export = $_GET['export'] ?? '';
if (in_array($export, ['word', 'xlsx'], true)) {
    $exportData = [];
    $exportResult = $conn->query("SELECT pb.*, p.kode_sewa, p.nama_penyewa, p.tanggal_kembali AS tgl_rencana, p.total_bayar, k.nama_kamera, k.kode_kamera FROM pengembalian pb JOIN penyewaan p ON pb.id_penyewaan = p.id JOIN kamera k ON p.id_kamera = k.id $where ORDER BY pb.created_at DESC");
    if ($exportResult && $exportResult->num_rows) {
        while ($row = $exportResult->fetch_assoc()) {
            $exportData[] = $row;
        }
    }

    if ($export === 'word') {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        $section->addText('Laporan Data Pengembalian', ['bold' => true, 'size' => 16]);
        $section->addTextBreak(1);
        $phpWord->addTableStyle('PengembalianTable', ['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);
        $table = $section->addTable('PengembalianTable');
        $headers = ['No','Kode Sewa','Nama Penyewa','Kamera','Tgl Rencana','Tgl Aktual','Kondisi','Catatan'];
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
            $table->addCell(1750)->addText(date('d/m/Y', strtotime($row['tgl_rencana'])));
            $table->addCell(1750)->addText(date('d/m/Y', strtotime($row['tanggal_kembali_aktual'])));
            $table->addCell(1750)->addText($row['kondisi_kamera']);
            $table->addCell(1750)->addText($row['catatan'] ?: '-');
        }
        $fileName = 'laporan-pengembalian-' . date('YmdHis') . '.docx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save('php://output');
        exit;
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Pengembalian');
    $sheet->setCellValue('A1', 'Laporan Data Pengembalian');
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->fromArray(['No','Kode Sewa','Nama Penyewa','Kamera','Tgl Rencana','Tgl Aktual','Kondisi','Catatan'], null, 'A3');
    $rowNumber = 4;
    foreach ($exportData as $index => $row) {
        $sheet->fromArray([
            $index + 1,
            $row['kode_sewa'],
            $row['nama_penyewa'],
            $row['nama_kamera'] . ' (' . $row['kode_kamera'] . ')',
            date('d/m/Y', strtotime($row['tgl_rencana'])),
            date('d/m/Y', strtotime($row['tanggal_kembali_aktual'])),
            $row['kondisi_kamera'],
            $row['catatan'] ?: '-'
        ], null, 'A' . $rowNumber);
        $rowNumber++;
    }
    $fileName = 'laporan-pengembalian-' . date('YmdHis') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
    exit;
}

$list = $conn->query("
    SELECT pb.*, p.kode_sewa, p.nama_penyewa, p.tanggal_kembali AS tgl_rencana,
           p.total_bayar, k.nama_kamera, k.kode_kamera
    FROM pengembalian pb
    JOIN penyewaan p  ON pb.id_penyewaan = p.id
    JOIN kamera k     ON p.id_kamera = k.id
    $where
    ORDER BY pb.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Pengembalian - Rental Kamera</title>
  <link rel="stylesheet" href="assets/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="assets/css/lineicons.css"/>
  <link rel="stylesheet" href="assets/css/materialdesignicons.min.css"/>
  <link rel="stylesheet" href="assets/css/main.css"/>
</head>
<body>

<?php include 'partials/sidebar.php'; ?>

<main class="main-wrapper">
  <?php include 'partials/topbar.php'; ?>

  <section class="section">
    <div class="container-fluid">

      <div class="title-wrapper pt-30">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="title"><h2>Data Pengembalian</h2></div>
          </div>
          <div class="col-md-6">
            <div class="breadcrumb-wrapper">
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="main.php">Dashboard</a></li>
                  <li class="breadcrumb-item active">Pengembalian</li>
                </ol>
              </nav>
            </div>
          </div>
        </div>
      </div>





        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-20">
          <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <div class="input-style-1">
              <input type="text" name="cari" placeholder="Cari nama / kode sewa..."
                     value="<?= htmlspecialchars($cari) ?>" style="min-width:220px;">
            </div>
            <div class="select-style-1">
              <div class="select-position">
                <select name="kondisi">
                  <option value="">-- Semua Kondisi --</option>
                  <option value="baik"         <?= $filter=='baik'         ? 'selected':'' ?>>Baik</option>
                  <option value="rusak_ringan"  <?= $filter=='rusak_ringan' ? 'selected':'' ?>>Rusak Ringan</option>
                  <option value="rusak_berat"   <?= $filter=='rusak_berat'  ? 'selected':'' ?>>Rusak Berat</option>
                </select>
              </div>
            </div>
            <button type="submit" class="main-btn primary-btn btn-hover">
              <i class="lni lni-search-alt me-1"></i> Cari
            </button>
            <?php if (!empty($cari) || !empty($filter)): ?>
              <a href="pengembalian.php" class="main-btn deactive-btn-2">Reset</a>
            <?php endif; ?>
          </form>
          <div class="d-flex flex-wrap gap-2">
            <a href="pengembalian.php?export=word<?= htmlspecialchars($exportQuery) ?>" class="main-btn info-btn btn-hover">
              <i class="lni lni-cloud-download me-1"></i> Export Word
            </a>
            <a href="pengembalian.php?export=xlsx<?= htmlspecialchars($exportQuery) ?>" class="main-btn secondary-btn btn-hover">
              <i class="lni lni-cloud-download me-1"></i> Export Excel
            </a>
            <button type="button" class="main-btn primary-btn btn-hover" data-bs-toggle="modal" data-bs-target="#modalImport">
              <i class="lni lni-cloud-upload me-1"></i> Import Excel
            </button>
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
                <th><h6 class="text-sm text-medium">Tgl Rencana Kembali</h6></th>
                <th><h6 class="text-sm text-medium">Tgl Aktual Kembali</h6></th>
                <th><h6 class="text-sm text-medium">Kondisi</h6></th>
                <th><h6 class="text-sm text-medium">Catatan</h6></th>
                <th><h6 class="text-sm text-medium">Aksi</h6></th>
              </tr>
            </thead>
            <tbody>
            <?php if ($list && $list->num_rows > 0):
              $no = 1; while ($row = $list->fetch_assoc()):
              $terlambat = $row['tanggal_kembali_aktual'] > $row['tgl_rencana'];
            ?>
            <tr>
              <td><p class="text-sm"><?= $no++ ?></p></td>
              <td><p class="text-sm"><code><?= htmlspecialchars($row['kode_sewa']) ?></code></p></td>
              <td><p class="text-sm"><?= htmlspecialchars($row['nama_penyewa']) ?></p></td>
              <td>
                <p class="text-sm"><?= htmlspecialchars($row['nama_kamera']) ?></p>
                <span class="text-xs text-gray"><?= $row['kode_kamera'] ?></span>
              </td>
              <td>
                <p class="text-sm <?= $terlambat ? 'text-danger':'' ?>">
                  <?= date('d/m/Y', strtotime($row['tgl_rencana'])) ?>
                  <?php if ($terlambat): ?>
                    <span class="badge bg-danger ms-1" style="font-size:10px;">Terlambat</span>
                  <?php endif; ?>
                </p>
              </td>
              <td><p class="text-sm"><?= date('d/m/Y', strtotime($row['tanggal_kembali_aktual'])) ?></p></td>
              <td>
                <?php if ($row['kondisi_kamera'] == 'baik'): ?>
                  <span class="badge bg-success">Baik</span>
                <?php elseif ($row['kondisi_kamera'] == 'rusak_ringan'): ?>
                  <span class="badge bg-warning text-dark">Rusak Ringan</span>
                <?php else: ?>
                  <span class="badge bg-danger">Rusak Berat</span>
                <?php endif; ?>
              </td>
              <td><p class="text-sm text-gray"><?= htmlspecialchars($row['catatan'] ?: '-') ?></p></td>
              <td>
                <div class="action d-flex gap-2">
                  <a href="edit_pengembalian.php?id=<?= $row['id'] ?>"
                     class="main-btn warning-btn btn-hover btn-sm"
                     style="font-size:12px;padding:4px 10px;">
                    <i class="lni lni-pencil-alt"></i> Edit
                  </a>
                  <a href="pengembalian.php?hapus=<?= $row['id'] ?>"
                     class="main-btn danger-btn btn-hover btn-sm"
                     style="font-size:12px;padding:4px 10px;"
                     onclick="return confirm('Hapus data ini? Status penyewaan akan kembali ke Dipinjam.')">
                    <i class="lni lni-trash-can"></i> Hapus
                  </a>
                </div>
              </td>
            </tr>
            <?php endwhile; else: ?>
              <tr>
                <td colspan="9" class="text-center py-4 text-gray">
                  Belum ada data pengembalian.
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

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
  // Tampilkan notifikasi popup menggunakan SweetAlert2
  <?php if (!empty($notif)): ?>
    setTimeout(() => {
      const notifications = {
        'sukses': { title: 'Berhasil Disimpan', text: 'Pengembalian berhasil dicatat & stok kamera diperbarui.', icon: 'success' },
        'hapus': { title: 'Berhasil Dihapus', text: 'Data pengembalian sudah terhapus.', icon: 'success' },
        'import_success': { title: 'Import Berhasil', text: 'Berhasil mengimpor data pengembalian (Ditambahkan: <?= (int)($_GET["inserted"] ?? 0) ?>, Diperbarui: <?= (int)($_GET["updated"] ?? 0) ?>, Dilewati: <?= (int)($_GET["skipped"] ?? 0) ?>).', icon: 'success' },
        'import_error': { title: 'Import Gagal', text: '<?= htmlspecialchars($_GET["pesan"] ?? "Terjadi kesalahan saat mengimpor data.") ?>', icon: 'error' }
      };
      const data = notifications['<?= $notif ?>'];
      if (data && typeof Swal !== 'undefined') {
        Swal.fire({
          title: data.title,
          text: data.text,
          icon: data.icon || 'success',
          confirmButtonText: 'OK',
          confirmButtonColor: '#5865f2'
        });
      }
    }, 500);
  <?php endif; ?>
</script>
<div class="modal fade" id="modalImport" tabindex="-1" aria-labelledby="modalImportLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalImportLabel">Import Pengembalian dari Excel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="pengembalian.php" enctype="multipart/form-data">
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
              <li>  <code>No | Kode Sewa | Nama Penyewa | Kamera | Tgl Rencana | Tgl Aktual | Kondisi | Catatan</code></li>
              <li>- <strong>Kode Sewa</strong> dan <strong>Tgl Aktual</strong> (Tgl Aktual Kembali) wajib diisi.</li>
              <li>- Kode Sewa harus terdaftar dalam data penyewaan di database.</li>
              <li>- Tanggal aktual kembali disarankan berformat: <code>dd/mm/yyyy</code> atau <code>yyyy-mm-dd</code>.</li>
              <li>- Kondisi yang valid: <code>baik</code>, <code>rusak_ringan</code>, <code>rusak_berat</code> (jika dikosongkan/salah akan otomatis diset <code>baik</code>). Jika diset <code>rusak_berat</code>, status kamera otomatis diubah menjadi <strong>Rusak</strong>.</li>
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

</body>
</html>