<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$pageParam = $_GET['page'] ?? 'sewa';
?>
<aside class="sidebar-nav-wrapper">
      <div class="navbar-logo">
        <a href="user_dashboard.php" class="fs-5 fw-bold text-dark text-decoration-none">SnapGear</a>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li class="nav-item <?= ($currentPage == 'user_dashboard.php' && $pageParam == 'sewa') ? 'active' : '' ?>">
            <a href="user_dashboard.php?page=sewa">
              <span class="icon"><i class="lni lni-camera"></i></span>
              <span class="text">Sewa Kamera</span>
            </a>
          </li>
          <li class="nav-item <?= $currentPage == 'status_pembayaran.php' ? 'active' : '' ?>">
            <a href="status_pembayaran.php">
              <span class="icon"><i class="lni lni-credit-cards"></i></span>
              <span class="text">Status Pembayaran</span>
            </a>
          </li>
          <li class="nav-item <?= ($currentPage == 'user_dashboard.php' && $pageParam == 'riwayat') ? 'active' : '' ?>">
            <a href="user_dashboard.php?page=riwayat">
              <span class="icon"><i class="lni lni-clipboard"></i></span>
              <span class="text">Riwayat Sewa</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="../logout.php">
              <span class="icon"><i class="lni lni-exit"></i></span>
              <span class="text">Keluar</span>
            </a>
          </li>
        </ul>
      </nav>
    </aside>
    <div class="overlay"></div>
