<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    $del = $conn->query("DELETE FROM kamera WHERE id = $id");
    if ($del) {
        header("Location: kamera.php?notif=hapus");
        exit;
    } else {
        $pesan_error = "Kamera tidak bisa dihapus!";
    }
}

$errorAdd = '';
$errorEdit = '';
$showModal = '';
$add_data = [
    'kode_kamera' => '',
    'nama_kamera' => '',
    'merk' => '',
    'tipe' => '',
    'harga_sewa' => '',
    'stok' => 1,
    'deskripsi' => '',
    'status' => 'tersedia'
];
$edit_data = [
    'id' => 0,
    'kode_kamera' => '',
    'nama_kamera' => '',
    'merk' => '',
    'tipe' => '',
    'harga_sewa' => '',
    'stok' => 0,
    'deskripsi' => '',
    'status' => 'tersedia'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';
    if ($action === 'add') {
        $kode_kamera = trim($_POST['kode_kamera']);
        $nama_kamera = trim($_POST['nama_kamera']);
        $merk        = trim($_POST['merk']);
        $tipe        = trim($_POST['tipe']);
        $harga_sewa  = trim($_POST['harga_sewa']);
        $stok        = (int) $_POST['stok'];
        $deskripsi   = trim($_POST['deskripsi']);
        $status      = $_POST['status'] ?? 'tersedia';

        if (empty($kode_kamera) || empty($nama_kamera) || empty($harga_sewa)) {
            $errorAdd = 'Kode kamera, nama kamera, dan harga sewa wajib diisi!';
        } elseif (!is_numeric($harga_sewa) || $harga_sewa < 0) {
            $errorAdd = 'Harga sewa harus berupa angka positif!';
        } elseif ($stok < 0) {
            $errorAdd = 'Stok tidak boleh negatif!';
        } else {
            $cek = $conn->prepare("SELECT id FROM kamera WHERE kode_kamera = ?");
            $cek->bind_param("s", $kode_kamera);
            $cek->execute();
            $cek->store_result();

            if ($cek->num_rows > 0) {
                $errorAdd = 'Kode kamera sudah digunakan, gunakan kode lain!';
            } else {
                $stmt = $conn->prepare("INSERT INTO kamera (kode_kamera, nama_kamera, merk, tipe, harga_sewa, stok, deskripsi, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssdiss", $kode_kamera, $nama_kamera, $merk, $tipe, $harga_sewa, $stok, $deskripsi, $status);

                if ($stmt->execute()) {
                    header("Location: kamera.php?notif=tambah");
                    exit;
                } else {
                    $errorAdd = 'Gagal menyimpan data, coba lagi!';
                }
            }
        }

        $showModal = 'add';
        $add_data = [
            'kode_kamera' => $kode_kamera,
            'nama_kamera' => $nama_kamera,
            'merk' => $merk,
            'tipe' => $tipe,
            'harga_sewa' => $harga_sewa,
            'stok' => $stok,
            'deskripsi' => $deskripsi,
            'status' => $status
        ];
    } elseif ($action === 'edit') {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $kode_kamera = trim($_POST['kode_kamera']);
        $nama_kamera = trim($_POST['nama_kamera']);
        $merk        = trim($_POST['merk']);
        $tipe        = trim($_POST['tipe']);
        $harga_sewa  = trim($_POST['harga_sewa']);
        $stok        = (int) $_POST['stok'];
        $deskripsi   = trim($_POST['deskripsi']);
        $status      = $_POST['status'] ?? 'tersedia';

        if ($id <= 0) {
            $errorEdit = 'Data kamera tidak ditemukan.';
        } elseif (empty($kode_kamera) || empty($nama_kamera) || empty($harga_sewa)) {
            $errorEdit = 'Kode kamera, nama kamera, dan harga sewa wajib diisi!';
        } elseif (!is_numeric($harga_sewa) || $harga_sewa < 0) {
            $errorEdit = 'Harga sewa harus berupa angka positif!';
        } elseif ($stok < 0) {
            $errorEdit = 'Stok tidak boleh negatif!';
        } else {
            $cek = $conn->prepare("SELECT id FROM kamera WHERE kode_kamera = ? AND id != ?");
            $cek->bind_param("si", $kode_kamera, $id);
            $cek->execute();
            $cek->store_result();

            if ($cek->num_rows > 0) {
                $errorEdit = 'Kode kamera sudah digunakan oleh kamera lain!';
            } else {
                $stmt = $conn->prepare("UPDATE kamera SET kode_kamera = ?, nama_kamera = ?, merk = ?, tipe = ?, harga_sewa = ?, stok = ?, deskripsi = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssssdissi", $kode_kamera, $nama_kamera, $merk, $tipe, $harga_sewa, $stok, $deskripsi, $status, $id);

                if ($stmt->execute()) {
                    header("Location: kamera.php?notif=edit");
                    exit;
                } else {
                    $errorEdit = 'Gagal menyimpan perubahan data.';
                }
            }
        }

        $showModal = 'edit';
        $edit_data = [
            'id' => $id,
            'kode_kamera' => $kode_kamera,
            'nama_kamera' => $nama_kamera,
            'merk' => $merk,
            'tipe' => $tipe,
            'harga_sewa' => $harga_sewa,
            'stok' => $stok,
            'deskripsi' => $deskripsi,
            'status' => $status
        ];
    } elseif ($action === 'hapus') {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id > 0) {
            $del = $conn->query("DELETE FROM kamera WHERE id = $id");
            if ($del) {
                header("Location: kamera.php?notif=hapus");
                exit;
            } else {
                $pesan_error = "Kamera tidak bisa dihapus!";
            }
        } else {
            $pesan_error = "Data kamera tidak valid untuk dihapus.";
        }
    }
}

$notif = isset($_GET['notif']) ? $_GET['notif'] : '';
$cari   = isset($_GET['cari'])   ? trim($_GET['cari'])   : '';
$filter = isset($_GET['status']) ? trim($_GET['status']) : '';

$last = $conn->query("SELECT kode_kamera FROM kamera ORDER BY id DESC LIMIT 1")->fetch_assoc();
$next_kode = 'KAM-001';
if ($last) {
    $num = (int) substr($last['kode_kamera'], 4) + 1;
    $next_kode = 'KAM-' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

if (empty($add_data['kode_kamera'])) {
    $add_data['kode_kamera'] = $next_kode;
}

$where = "WHERE 1=1";
if (!empty($cari))   $where .= " AND (nama_kamera LIKE '%".mysqli_real_escape_string($conn,$cari)."%' OR kode_kamera LIKE '%".mysqli_real_escape_string($conn,$cari)."%' OR merk LIKE '%".mysqli_real_escape_string($conn,$cari)."%')";
if (!empty($filter)) $where .= " AND status = '".mysqli_real_escape_string($conn,$filter)."'";

$limit = 10; // items per page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// hitung total untuk paginasi
$resCount = $conn->query("SELECT COUNT(*) AS cnt FROM kamera $where");
$total_rows = ($resCount && $resCount->num_rows) ? $resCount->fetch_assoc()['cnt'] : 0;
$total_pages = max(1, (int) ceil($total_rows / $limit));

$kamera_list = $conn->query("SELECT * FROM kamera $where ORDER BY created_at DESC LIMIT $offset, $limit");
?>
<!DOCTYPE html>

<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Data Kamera - Rental Kamera</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/lineicons.css" type="text/css" />
    <link rel="stylesheet" href="assets/css/materialdesignicons.min.css" type="text/css" />
    <link rel="stylesheet" href="assets/css/main.css" />
  </head>
  <body>
<?php include 'partials/sidebar.php'; ?>
    <div class="overlay"></div>

    <main class="main-wrapper">
      <?php include 'partials/topbar.php'; ?>

      <section class="section">
        <div class="container-fluid">
          <div class="title-wrapper pt-30">
            <div class="row align-items-center">
              <div class="col-md-6">
                <div class="title"><h2>Manajemen Data Kamera</h2></div>
              </div>
            </div>
          </div>

          <?php if($notif == 'tambah'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">Sukses menambahkan kamera baru!<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
          <?php elseif($notif == 'edit'): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">Data kamera berhasil diperbarui!<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
          <?php elseif($notif == 'hapus'): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">Kamera berhasil dihapus!<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
          <?php endif; ?>

          <div class="card-style mb-30">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-25">
              <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <div class="select-style-1 mb-0">
                  <div class="select-position">
                    <select name="status" onchange="this.form.submit()">
                      <option value="">Semua Status</option>
                      <option value="tersedia" <?= $filter == 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                      <option value="disewa" <?= $filter == 'disewa' ? 'selected' : '' ?>>Disewa</option>
                      <option value="rusak" <?= $filter == 'rusak' ? 'selected' : '' ?>>Rusak</option>
                    </select>
                  </div>
                </div>
                <div class="input-style-1 mb-0">
                  <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>" placeholder="Cari nama/kode..." />
                </div>
                <button type="submit" class="main-btn primary-btn btn-hover"><i class="lni lni-search-alt"></i></button>
              </form>
              <button type="button" class="main-btn success-btn btn-hover" data-bs-toggle="modal" data-bs-target="#modalAdd"><i class="lni lni-plus me-2"></i> Tambah Kamera</button>
            </div>

            <div class="table-wrapper table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th><h6>No</h6></th>
                    <th><h6>Kode</h6></th>
                    <th><h6>Nama Kamera</h6></th>
                    <th><h6>Merk / Tipe</h6></th>
                    <th><h6>Harga Sewa / Hari</h6></th>
                    <th><h6>Stok</h6></th>
                    <th><h6>Status</h6></th>
                    <th><h6>Aksi</h6></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if($kamera_list->num_rows > 0): $no = $offset + 1; ?>
                    <?php while($row = $kamera_list->fetch_assoc()): ?>
                    <tr>
                      <td><p><?= $no++ ?></p></td>
                      <td><p><code><?= htmlspecialchars($row['kode_kamera']) ?></code></p></td>
                      <td><p class="text-bold"><?= htmlspecialchars($row['nama_kamera']) ?></p></td>
                      <td><p><?= htmlspecialchars($row['merk']) ?> / <?= htmlspecialchars($row['tipe']) ?></p></td>
                      <td><p>Rp <?= number_format($row['harga_sewa'], 0, ',', '.') ?></p></td>
                      <td><p><?= $row['stok'] ?> unit</p></td>
                      <td>
                        <?php if($row['status'] == 'tersedia'): ?>
                          <span class="status-btn success-btn btn-sm">Tersedia</span>
                        <?php elseif($row['status'] == 'disewa'): ?>
                          <span class="status-btn warning-btn btn-sm">Disewa</span>
                        <?php else: ?>
                          <span class="status-btn danger-btn btn-sm">Rusak</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <div class="action gap-2">
                          <button type="button" class="text-warning btn-edit" data-bs-toggle="modal" data-bs-target="#modalEdit"
                            data-id="<?= $row['id'] ?>"
                            data-kode="<?= htmlspecialchars($row['kode_kamera'], ENT_QUOTES) ?>"
                            data-nama="<?= htmlspecialchars($row['nama_kamera'], ENT_QUOTES) ?>"
                            data-merk="<?= htmlspecialchars($row['merk'], ENT_QUOTES) ?>"
                            data-tipe="<?= htmlspecialchars($row['tipe'], ENT_QUOTES) ?>"
                            data-harga="<?= htmlspecialchars($row['harga_sewa'], ENT_QUOTES) ?>"
                            data-stok="<?= htmlspecialchars($row['stok'], ENT_QUOTES) ?>"
                            data-deskripsi="<?= htmlspecialchars($row['deskripsi'], ENT_QUOTES) ?>"
                            data-status="<?= htmlspecialchars($row['status'], ENT_QUOTES) ?>"
                            title="Edit Kamera"><i class="lni lni-pencil"></i></button>
                          <button type="button" class="text-danger btn-delete" data-bs-toggle="modal" data-bs-target="#modalDelete" data-id="<?= $row['id'] ?>" data-name="<?= htmlspecialchars($row['nama_kamera'], ENT_QUOTES) ?>" title="Hapus Kamera"><i class="lni lni-trash-can"></i></button>
                        </div>
                      </td>
                    </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr><td colspan="8" class="text-center"><p class="text-muted py-4">📷 Belum ada data kamera.</p></td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
              <!-- Pagination 1..10 -->
              <nav aria-label="Page navigation" class="mt-3">
                <ul class="pagination">
                  <?php
                  // pertahankan parameter GET selain page
                  $preserve = $_GET;
                  foreach (range(1, min(10, $total_pages)) as $i) {
                    $preserve['page'] = $i;
                    $link = htmlspecialchars($_SERVER['PHP_SELF']) . '?' . http_build_query($preserve);
                    $active = ($i == $page) ? ' active' : '';
                    echo "<li class=\"page-item$active\"><a class=\"page-link\" href=\"$link\">$i</a></li>";
                  }
                  ?>
                </ul>
              </nav>

              <!-- Modals -->
              <div class="modal fade" id="modalAdd" tabindex="-1" aria-labelledby="modalAddLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalAddLabel">Tambah Kamera</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="kamera.php">
                      <input type="hidden" name="form_action" value="add">
                      <div class="modal-body">
                        <?php if(!empty($errorAdd)): ?>
                          <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorAdd) ?></div>
                        <?php endif; ?>
                        <div class="row">
                          <div class="col-md-6">
                            <div class="input-style-1">
                              <label>Kode Kamera <span class="text-danger">*</span></label>
                              <input type="text" name="kode_kamera" value="<?= htmlspecialchars($add_data['kode_kamera']) ?>" required />
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="select-style-1">
                              <label>Status Operasional</label>
                              <div class="select-position">
                                <select name="status">
                                  <option value="tersedia" <?= $add_data['status'] == 'tersedia' ? 'selected' : '' ?>>Tersedia (Ready)</option>
                                  <option value="disewa" <?= $add_data['status'] == 'disewa' ? 'selected' : '' ?>>Sedang Disewa</option>
                                  <option value="rusak" <?= $add_data['status'] == 'rusak' ? 'selected' : '' ?>>Rusak / Maintenance</option>
                                </select>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="input-style-1">
                          <label>Nama Kamera <span class="text-danger">*</span></label>
                          <input type="text" name="nama_kamera" placeholder="Contoh: Sony Alpha A7 III" value="<?= htmlspecialchars($add_data['nama_kamera']) ?>" required />
                        </div>
                        <div class="row">
                          <div class="col-md-6">
                            <div class="input-style-1">
                              <label>Merk / Brand</label>
                              <input type="text" name="merk" placeholder="Sony, Canon, Nikon..." value="<?= htmlspecialchars($add_data['merk']) ?>" />
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="input-style-1">
                              <label>Tipe Kamera</label>
                              <input type="text" name="tipe" placeholder="DSLR, Mirrorless, Action Cam..." value="<?= htmlspecialchars($add_data['tipe']) ?>" />
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-md-6">
                            <div class="input-style-1">
                              <label>Harga Sewa / Hari (Rp) <span class="text-danger">*</span></label>
                              <input type="number" name="harga_sewa" min="0" value="<?= htmlspecialchars($add_data['harga_sewa']) ?>" required />
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="input-style-1">
                              <label>Stok (unit)</label>
                              <input type="number" name="stok" min="0" value="<?= htmlspecialchars($add_data['stok']) ?>" />
                            </div>
                          </div>
                        </div>
                        <div class="input-style-1">
                          <label>Deskripsi & Kelengkapan</label>
                          <textarea name="deskripsi" rows="4" placeholder="Spesifikasi singkat, kelengkapan lensa, dll..."> <?= htmlspecialchars($add_data['deskripsi']) ?></textarea>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="main-btn secondary-btn btn-hover" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="main-btn success-btn btn-hover">Simpan Kamera</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

              <div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalEditLabel">Edit Kamera</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="kamera.php">
                      <input type="hidden" name="form_action" value="edit">
                      <input type="hidden" name="id" id="edit_id" value="<?= htmlspecialchars($edit_data['id']) ?>">
                      <div class="modal-body">
                        <?php if(!empty($errorEdit)): ?>
                          <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorEdit) ?></div>
                        <?php endif; ?>
                        <div class="row">
                          <div class="col-md-6">
                            <div class="input-style-1">
                              <label>Kode Kamera <span class="text-danger">*</span></label>
                              <input type="text" name="kode_kamera" id="edit_kode_kamera" value="<?= htmlspecialchars($edit_data['kode_kamera']) ?>" required />
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="select-style-1">
                              <label>Status Operasional</label>
                              <div class="select-position">
                                <select name="status" id="edit_status">
                                  <option value="tersedia" <?= $edit_data['status'] == 'tersedia' ? 'selected' : '' ?>>Tersedia (Ready)</option>
                                  <option value="disewa" <?= $edit_data['status'] == 'disewa' ? 'selected' : '' ?>>Sedang Disewa</option>
                                  <option value="rusak" <?= $edit_data['status'] == 'rusak' ? 'selected' : '' ?>>Rusak / Maintenance</option>
                                </select>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="input-style-1">
                          <label>Nama Kamera <span class="text-danger">*</span></label>
                          <input type="text" name="nama_kamera" id="edit_nama_kamera" placeholder="Contoh: Sony Alpha A7 III" value="<?= htmlspecialchars($edit_data['nama_kamera']) ?>" required />
                        </div>
                        <div class="row">
                          <div class="col-md-6">
                            <div class="input-style-1">
                              <label>Merk / Brand</label>
                              <input type="text" name="merk" id="edit_merk" placeholder="Sony, Canon, Nikon..." value="<?= htmlspecialchars($edit_data['merk']) ?>" />
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="input-style-1">
                              <label>Tipe Kamera</label>
                              <input type="text" name="tipe" id="edit_tipe" placeholder="DSLR, Mirrorless, Action Cam..." value="<?= htmlspecialchars($edit_data['tipe']) ?>" />
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-md-6">
                            <div class="input-style-1">
                              <label>Harga Sewa / Hari (Rp) <span class="text-danger">*</span></label>
                              <input type="number" name="harga_sewa" id="edit_harga_sewa" min="0" value="<?= htmlspecialchars($edit_data['harga_sewa']) ?>" required />
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="input-style-1">
                              <label>Stok (unit)</label>
                              <input type="number" name="stok" id="edit_stok" min="0" value="<?= htmlspecialchars($edit_data['stok']) ?>" />
                            </div>
                          </div>
                        </div>
                        <div class="input-style-1">
                          <label>Deskripsi & Kelengkapan</label>
                          <textarea name="deskripsi" id="edit_deskripsi" rows="4" placeholder="Spesifikasi singkat, kelengkapan lensa, dll..."><?= htmlspecialchars($edit_data['deskripsi']) ?></textarea>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="main-btn secondary-btn btn-hover" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="main-btn warning-btn btn-hover">Simpan Perubahan</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

              <div class="modal fade" id="modalDelete" tabindex="-1" aria-labelledby="modalDeleteLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalDeleteLabel">Hapus Kamera</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="kamera.php">
                      <input type="hidden" name="form_action" value="hapus">
                      <input type="hidden" name="id" id="delete_id" value="">
                      <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus kamera <strong id="delete_name"></strong>?</p>
                        <?php if(!empty($pesan_error)): ?>
                          <div class="alert alert-danger" role="alert"><?= htmlspecialchars($pesan_error) ?></div>
                        <?php endif; ?>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="main-btn secondary-btn btn-hover" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="main-btn danger-btn btn-hover">Hapus</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
      document.getElementById('menu-toggle').addEventListener('click', () => {
        document.querySelector('.sidebar-nav-wrapper').classList.toggle('active');
        document.querySelector('.main-wrapper').classList.toggle('active');
        document.querySelector('.overlay').classList.toggle('active');
      });

      document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', () => {
          document.getElementById('edit_id').value = btn.dataset.id;
          document.getElementById('edit_kode_kamera').value = btn.dataset.kode;
          document.getElementById('edit_nama_kamera').value = btn.dataset.nama;
          document.getElementById('edit_merk').value = btn.dataset.merk;
          document.getElementById('edit_tipe').value = btn.dataset.tipe;
          document.getElementById('edit_harga_sewa').value = btn.dataset.harga;
          document.getElementById('edit_stok').value = btn.dataset.stok;
          document.getElementById('edit_deskripsi').value = btn.dataset.deskripsi;
          document.getElementById('edit_status').value = btn.dataset.status;
        });
      });

      document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', () => {
          document.getElementById('delete_id').value = btn.dataset.id;
          document.getElementById('delete_name').textContent = btn.dataset.name;
        });
      });

      <?php if ($showModal === 'add'): ?>
        const autoAdd = new bootstrap.Modal(document.getElementById('modalAdd'));
        autoAdd.show();
      <?php elseif ($showModal === 'edit'): ?>
        const autoEdit = new bootstrap.Modal(document.getElementById('modalEdit'));
        autoEdit.show();
      <?php endif; ?>
    </script>
  </body>
</html>