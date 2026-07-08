<?php
$currentPage = basename($_SERVER['PHP_SELF']);

// Hitung pembayaran menunggu untuk badge
global $conn;
$pendingBayar = 0;
if (isset($conn) && $conn) {
    $resPending = $conn->query("SELECT COUNT(*) as n FROM pembayaran WHERE status = 'menunggu'");
    if ($resPending) $pendingBayar = (int)($resPending->fetch_assoc()['n'] ?? 0);
}
?>
<aside class="sidebar-nav-wrapper">
      <div class="navbar-logo">
        <a href="main.php" class="fs-5 fw-bold text-dark text-decoration-none">SnapGear</a>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li class="nav-item <?= $currentPage == 'main.php' ? 'active' : '' ?>">
            <a href="main.php">
              <span class="icon"><i class="lni lni-dashboard"></i></span>
              <span class="text">Dashboard</span>
            </a>
          </li>
          <li class="nav-item <?= ($currentPage == 'kamera.php' || $currentPage == 'edit_kamera.php') ? 'active' : '' ?>">
            <a href="kamera.php">
              <span class="icon"><i class="lni lni-camera"></i></span>
              <span class="text">Data Kamera</span>
            </a>
          </li>
          <li class="nav-item <?= $currentPage == 'admin_pembayaran.php' ? 'active' : '' ?>">
            <a href="admin_pembayaran.php">
              <span class="icon"><i class="lni lni-credit-cards"></i></span>
              <span class="text">
                Konfirmasi Bayar
                <?php if ($pendingBayar > 0): ?>
                  <span class="badge bg-danger ms-1" style="font-size:10px;padding:2px 6px;border-radius:10px;vertical-align:middle;"><?= $pendingBayar ?></span>
                <?php endif; ?>
              </span>
            </a>
          </li>
          <li class="nav-item <?= ($currentPage == 'penyewaan.php' || $currentPage == 'add_penyewaan.php' || $currentPage == 'edit_penyewaan.php') ? 'active' : '' ?>">
            <a href="penyewaan.php">
              <span class="icon"><i class="lni lni-clipboard"></i></span>
              <span class="text">Daftar Penyewa</span>
            </a>
          </li>
          <li class="nav-item <?= ($currentPage == 'pengembalian.php' || $currentPage == 'add_pengembalian.php' || $currentPage == 'edit_pengembalian.php') ? 'active' : '' ?>">
            <a href="pengembalian.php">
              <span class="icon"><i class="lni lni-reload"></i></span>
              <span class="text">Daftar Pengembalian</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="logout.php">
              <span class="icon"><i class="lni lni-exit"></i></span>
              <span class="text">Keluar</span>
            </a>
          </li>
        </ul>
      </nav>
    </aside>
    <div class="overlay"></div>