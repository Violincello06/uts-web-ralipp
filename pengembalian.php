<?php
session_start();
require_once 'koneksi.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

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
        'sukses': { title: 'Berhasil Disimpan', text: 'Pengembalian berhasil dicatat & stok kamera diperbarui.' },
        'hapus': { title: 'Berhasil Dihapus', text: 'Data pengembalian sudah terhapus.' }
      };
      const data = notifications['<?= $notif ?>'];
      if (data && typeof Swal !== 'undefined') {
        Swal.fire({
          title: data.title,
          text: data.text,
          icon: 'success',
          confirmButtonText: 'OK',
          confirmButtonColor: '#5865f2'
        });
      }
    }, 500);
  <?php endif; ?>
</script>
</body>
</html>