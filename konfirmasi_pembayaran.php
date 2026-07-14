<?php
session_start();
require_once 'koneksi.php';
require_once 'helpers/send_email.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (($_SESSION['role'] ?? 'user') === 'admin') { header("Location: main.php"); exit; }

$user_id   = (int) $_SESSION['user_id'];
$id_bayar  = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$id_bayar) { header("Location: user_dashboard.php"); exit; }

// Ambil data pembayaran milik user ini
$stmt = $conn->prepare("
    SELECT pb.*, k.nama_kamera, k.kode_kamera, k.merk, k.harga_sewa
    FROM pembayaran pb
    JOIN kamera k ON pb.id_kamera = k.id
    WHERE pb.id = ? AND pb.user_id = ?
");
$stmt->bind_param("ii", $id_bayar, $user_id);
$stmt->execute();
$bayar = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bayar) { header("Location: user_dashboard.php"); exit; }

// Jika sudah dikonfirmasi atau ditolak, arahkan ke status
if ($bayar['status'] !== 'menunggu') {
    header("Location: status_pembayaran.php");
    exit;
}

$error = '';
$success = false;

// Informasi rekening bank (bisa dikustomisasi)
$bank_info = [
    ['bank' => 'BCA',   'rekening' => '1234567890', 'atas_nama' => 'SnapGear Rental'],
    ['bank' => 'BNI',   'rekening' => '0987654321', 'atas_nama' => 'SnapGear Rental'],
    ['bank' => 'Mandiri','rekening' => '1122334455', 'atas_nama' => 'SnapGear Rental'],
];

// Proses form konfirmasi metode bayar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['metode_bayar'])) {
    $metode = $_POST['metode_bayar'];

    if (!in_array($metode, ['transfer', 'tunai'])) {
        $error = 'Metode pembayaran tidak valid.';
    } else {
        $bukti_path = null;

        if ($metode === 'transfer') {
            // Validasi upload bukti
            if (empty($_FILES['bukti_transfer']['name'])) {
                $error = 'Harap upload bukti transfer terlebih dahulu.';
            } else {
                $uploadDir  = __DIR__ . '/assets/uploads/bukti_transfer/';
                $ext        = strtolower(pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION));
                $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];

                if (!in_array($ext, $allowedExt)) {
                    $error = 'Format file tidak didukung. Gunakan JPG, PNG, atau PDF.';
                } elseif ($_FILES['bukti_transfer']['size'] > 3 * 1024 * 1024) {
                    $error = 'Ukuran file melebihi batas 3MB.';
                } else {
                    $filename   = 'bukti_' . $bayar['kode_bayar'] . '_' . time() . '.' . $ext;
                    $uploadPath = $uploadDir . $filename;

                    if (!move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $uploadPath)) {
                        $error = 'Gagal mengupload file. Coba lagi.';
                    } else {
                        $bukti_path = 'assets/uploads/bukti_transfer/' . $filename;
                    }
                }
            }
        }

        if (empty($error)) {
            // Update data pembayaran dengan metode & bukti
            if ($bukti_path) {
                $stmtU = $conn->prepare("UPDATE pembayaran SET metode_bayar = ?, bukti_transfer = ? WHERE id = ?");
                $stmtU->bind_param("ssi", $metode, $bukti_path, $id_bayar);
            } else {
                $stmtU = $conn->prepare("UPDATE pembayaran SET metode_bayar = ? WHERE id = ?");
                $stmtU->bind_param("si", $metode, $id_bayar);
            }
            $stmtU->execute();
            $stmtU->close();

            // ===== KIRIM NOTIFIKASI EMAIL KE ADMIN =====
            sendNotifikasiSewaCust([
                'kode_bayar'      => $bayar['kode_bayar'],
                'nama_penyewa'    => $bayar['nama_penyewa'],
                'nama_kamera'     => $bayar['nama_kamera'],
                'tanggal_sewa'    => date('d M Y', strtotime($bayar['tanggal_sewa'])),
                'tanggal_kembali' => date('d M Y', strtotime($bayar['tanggal_kembali'])),
                'lama_sewa'       => $bayar['lama_sewa'],
                'total_bayar'     => $bayar['total_bayar'],
                'metode_bayar'    => $metode,
                'catatan'         => $bayar['catatan'] ?? '',
            ]);
            // ===========================================

            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Konfirmasi Pembayaran - SnapGear</title>
  <link rel="stylesheet" href="assets/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="assets/css/lineicons.css"/>
  <link rel="stylesheet" href="assets/css/main.css"/>
  <?php include 'partials/theme_head.php'; ?>
  <style>
    .payment-card {
      border-radius: 16px;
      border: 1px solid rgba(0,0,0,0.07);
      background: #fff;
      transition: all 0.3s;
    }
    [data-theme="dark"] .payment-card {
      background: #1e2536 !important;
      border-color: #2a3045 !important;
    }
    .method-card {
      border: 2px solid #e5e7eb;
      border-radius: 14px;
      cursor: pointer;
      transition: all 0.25s ease;
      background: #fff;
      padding: 20px;
    }
    .method-card:hover { border-color: #365CF5; background: rgba(54,92,245,0.03); }
    .method-card.selected { border-color: #365CF5; background: rgba(54,92,245,0.06); }
    [data-theme="dark"] .method-card {
      background: #1a2035 !important;
      border-color: #2a3045 !important;
    }
    [data-theme="dark"] .method-card.selected { border-color: #5b7af8 !important; background: rgba(91,122,248,0.1) !important; }
    .bank-row {
      border-radius: 10px;
      background: #f8f9ff;
      border: 1px solid #e0e7ff;
      padding: 12px 16px;
      margin-bottom: 10px;
    }
    [data-theme="dark"] .bank-row {
      background: #0f1117 !important;
      border-color: #2a3045 !important;
    }
    .step-badge {
      width: 28px; height: 28px;
      border-radius: 50%;
      background: #365CF5;
      color: #fff;
      display: inline-flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 13px; margin-right: 10px;
    }
    .summary-table td { padding: 6px 0; }
    .summary-table td:first-child { color: #6b7280; font-size: 13px; min-width: 130px; }
    .summary-table td:last-child { font-weight: 600; font-size: 13px; }
    .total-row td { font-size: 1.05rem !important; color: #365CF5 !important; border-top: 2px solid #e5e7eb; padding-top: 10px !important; }
    [data-theme="dark"] .total-row td { color: #5b7af8 !important; }
    .success-wrap { text-align: center; padding: 40px 20px; }
    .success-icon { font-size: 4rem; color: #22c55e; margin-bottom: 20px; }
  </style>
</head>
<body>

<?php include 'partials/sidebar_user.php'; ?>

<main class="main-wrapper">
  <?php include 'partials/topbar.php'; ?>
  <section class="section">
    <div class="container-fluid">

      <div class="title-wrapper pt-30">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="title"><h2>Konfirmasi Pembayaran</h2></div>
          </div>
          <div class="col-md-6">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb justify-content-md-end">
                <li class="breadcrumb-item"><a href="user_dashboard.php">Sewa Kamera</a></li>
                <li class="breadcrumb-item active">Konfirmasi Pembayaran</li>
              </ol>
            </nav>
          </div>
        </div>
      </div>

      <?php if ($success): ?>
        <!-- ===================== SUKSES ===================== -->
        <div class="row justify-content-center">
          <div class="col-12 col-lg-6">
            <div class="payment-card shadow-sm p-40 text-center">
              <div class="success-icon"><i class="lni lni-checkmark-circle"></i></div>
              <h4 class="mb-15">Konfirmasi Terkirim!</h4>
              <p class="text-gray text-sm mb-20">
                Permintaan sewa Anda dengan kode <strong><?= htmlspecialchars($bayar['kode_bayar']) ?></strong> sedang
                menunggu verifikasi pembayaran oleh admin. Anda akan dihubungi jika ada update.
              </p>
              <div class="d-flex gap-2 justify-content-center flex-wrap">
                <a href="status_pembayaran.php" class="main-btn primary-btn btn-hover">
                  <i class="lni lni-list me-2"></i> Lihat Status Pembayaran
                </a>
                <a href="user_dashboard.php" class="main-btn secondary-btn btn-hover">
                  Kembali ke Katalog
                </a>
              </div>
            </div>
          </div>
        </div>

      <?php else: ?>
        <!-- ===================== FORM KONFIRMASI ===================== -->
        <div class="row">
          <!-- Ringkasan Pesanan -->
          <div class="col-12 col-lg-4 mb-30">
            <div class="payment-card shadow-sm p-25">
              <h6 class="text-medium mb-20"><i class="lni lni-clipboard me-2 text-primary"></i>Ringkasan Pesanan</h6>
              <table class="summary-table w-100">
                <tr><td>Kode Bayar</td><td><code><?= htmlspecialchars($bayar['kode_bayar']) ?></code></td></tr>
                <tr><td>Kamera</td><td><?= htmlspecialchars($bayar['nama_kamera']) ?></td></tr>
                <tr><td>Merk</td><td><?= htmlspecialchars($bayar['merk']) ?></td></tr>
                <tr><td>Tanggal Sewa</td><td><?= date('d M Y', strtotime($bayar['tanggal_sewa'])) ?></td></tr>
                <tr><td>Tanggal Kembali</td><td><?= date('d M Y', strtotime($bayar['tanggal_kembali'])) ?></td></tr>
                <tr><td>Lama Sewa</td><td><?= $bayar['lama_sewa'] ?> hari</td></tr>
                <tr><td>Harga/Hari</td><td>Rp <?= number_format($bayar['harga_sewa'], 0, ',', '.') ?></td></tr>
                <tr class="total-row"><td>Total Bayar</td><td>Rp <?= number_format($bayar['total_bayar'], 0, ',', '.') ?></td></tr>
              </table>
              <?php if (!empty($bayar['catatan'])): ?>
                <div class="mt-15 p-10" style="background:#f8f9ff;border-radius:8px;font-size:12px;">
                  <strong>Catatan:</strong> <?= htmlspecialchars($bayar['catatan']) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Form Pilihan Metode Bayar -->
          <div class="col-12 col-lg-8 mb-30">
            <div class="payment-card shadow-sm p-25">
              <h6 class="text-medium mb-20"><i class="lni lni-credit-cards me-2 text-primary"></i>Pilih Metode Pembayaran</h6>

              <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-20" role="alert">
                  <i class="lni lni-warning me-2"></i><?= htmlspecialchars($error) ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              <?php endif; ?>

              <form method="POST" enctype="multipart/form-data" id="formBayar">

                <!-- Pilihan Metode -->
                <div class="row mb-25">
                  <div class="col-6">
                    <label class="method-card d-flex flex-column align-items-center" id="card-transfer" onclick="selectMetode('transfer')">
                      <i class="lni lni-credit-cards" style="font-size:2.2rem;color:#365CF5;margin-bottom:10px;"></i>
                      <span class="fw-bold mb-5">Transfer Bank</span>
                      <span class="text-xs text-gray text-center">Upload bukti transfer ke rekening kami</span>
                      <input type="radio" name="metode_bayar" value="transfer" id="radio_transfer" class="d-none" required>
                    </label>
                  </div>
                  <div class="col-6">
                    <label class="method-card d-flex flex-column align-items-center" id="card-tunai" onclick="selectMetode('tunai')">
                      <i class="lni lni-money-location" style="font-size:2.2rem;color:#219653;margin-bottom:10px;"></i>
                      <span class="fw-bold mb-5">Tunai di Toko</span>
                      <span class="text-xs text-gray text-center">Bayar langsung saat mengambil kamera</span>
                      <input type="radio" name="metode_bayar" value="tunai" id="radio_tunai" class="d-none">
                    </label>
                  </div>
                </div>

                <!-- Konten Transfer -->
                <div id="section-transfer" style="display:none;">
                  <div class="mb-20">
                    <h6 class="text-sm text-medium mb-15"><span class="step-badge">1</span> Rekening Tujuan Transfer</h6>
                    <?php foreach ($bank_info as $b): ?>
                      <div class="bank-row d-flex justify-content-between align-items-center">
                        <div>
                          <span class="badge bg-primary me-2"><?= $b['bank'] ?></span>
                          <span class="fw-bold text-sm"><?= $b['rekening'] ?></span>
                        </div>
                        <span class="text-xs text-gray"><?= htmlspecialchars($b['atas_nama']) ?></span>
                      </div>
                    <?php endforeach; ?>
                    <div class="p-10 rounded mt-10" style="background:#fff3cd;border:1px solid #ffc107;">
                      <i class="lni lni-warning text-warning me-1"></i>
                      <span class="text-xs">Transfer tepat <strong>Rp <?= number_format($bayar['total_bayar'], 0, ',', '.') ?></strong> sesuai total di atas.</span>
                    </div>
                  </div>

                  <div class="mb-20">
                    <h6 class="text-sm text-medium mb-10"><span class="step-badge">2</span> Upload Bukti Transfer</h6>
                    <div class="input-style-1">
                      <label for="bukti_transfer">Pilih File Bukti Transfer <span class="text-danger">*</span></label>
                      <input type="file" name="bukti_transfer" id="bukti_transfer" accept=".jpg,.jpeg,.png,.pdf"
                             onchange="previewBukti(this)"/>
                      <p class="text-xs text-gray mt-5">Format: JPG, PNG, atau PDF. Maks. 3MB.</p>
                    </div>
                    <div id="bukti-preview" class="mt-10" style="display:none;">
                      <img id="preview-img" src="" alt="Preview" style="max-width:100%;max-height:200px;border-radius:8px;border:1px solid #e5e7eb;">
                    </div>
                  </div>
                </div>

                <!-- Konten Tunai -->
                <div id="section-tunai" style="display:none;">
                  <div class="p-20 rounded" style="background:#e6f7ed;border:1px solid #b7ebc6;">
                    <div class="d-flex align-items-start gap-3">
                      <i class="lni lni-store" style="font-size:2rem;color:#219653;flex-shrink:0;"></i>
                      <div>
                        <h6 class="mb-5 text-sm fw-bold" style="color:#219653;">Pembayaran Tunai di Toko</h6>
                        <p class="text-sm mb-5">Datang ke toko kami dan lakukan pembayaran sebesar:</p>
                        <p class="fw-bold mb-10" style="font-size:1.3rem;color:#219653;">Rp <?= number_format($bayar['total_bayar'], 0, ',', '.') ?></p>
                        <p class="text-xs text-gray mb-0">
                          Pembayaran akan diverifikasi oleh admin setelah Anda datang ke toko.
                          Kamera dapat diambil setelah pembayaran dikonfirmasi.
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="d-flex gap-2 mt-25">
                  <button type="submit" class="main-btn primary-btn btn-hover" id="btnKonfirmasi" disabled>
                    <i class="lni lni-checkmark-circle me-2"></i> Konfirmasi Pembayaran
                  </button>
                  <a href="user_dashboard.php" class="main-btn secondary-btn btn-hover">
                    Batal
                  </a>
                </div>
              </form>
            </div>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </section>
</main>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
  function selectMetode(metode) {
    document.getElementById('card-transfer').classList.remove('selected');
    document.getElementById('card-tunai').classList.remove('selected');
    document.getElementById('section-transfer').style.display = 'none';
    document.getElementById('section-tunai').style.display = 'none';

    document.getElementById('card-' + metode).classList.add('selected');
    document.getElementById('radio_' + metode).checked = true;
    document.getElementById('section-' + metode).style.display = 'block';
    document.getElementById('btnKonfirmasi').disabled = false;
  }

  function previewBukti(input) {
    const preview = document.getElementById('bukti-preview');
    const img     = document.getElementById('preview-img');
    if (input.files && input.files[0]) {
      const file = input.files[0];
      if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
          img.src = e.target.result;
          preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      } else {
        preview.style.display = 'none';
      }
    }
  }
</script>
</body>
</html>
