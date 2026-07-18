<?php $basePath = $basePath ?? ''; ?>
<header class="header">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-5 col-md-5 col-6">
        <div class="header-left d-flex align-items-center">
          <div class="menu-toggle-btn mr-15">
            <button id="menu-toggle" class="main-btn primary-btn btn-hover">
              <i class="lni lni-chevron-left me-2"></i> Menu
            </button>
          </div>
        </div>
      </div>
      <div class="col-lg-7 col-md-7 col-6">
        <div class="header-right d-flex align-items-center justify-content-end">

          <!-- ===== THEME TOGGLE ===== -->
          <button id="themeToggleBtn" class="theme-toggle-btn" title="Ganti Mode Tampilan" aria-label="Toggle dark mode">
            <i class="lni lni-night" id="themeIcon"></i>
          </button>

          <div class="profile-box ml-15">
            <button class="dropdown-toggle bg-transparent border-0 d-flex align-items-center" type="button" id="profile" data-bs-toggle="dropdown" aria-expanded="false">
              <div class="profile-info d-none d-md-block text-end me-3">
                <h6 class="text-sm fw-bold text-dark"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['username']) ?></h6>
                <p class="text-xs text-muted">@<?= htmlspecialchars($_SESSION['username'] ?? '') ?></p>
              </div>
              <div class="avatar-image bg-primary d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden;">
                <?php if (!empty($_SESSION['avatar'])): ?>
                  <img src="<?= $basePath . htmlspecialchars($_SESSION['avatar']) ?>" alt="Avatar" style="width:40px;height:40px;object-fit:cover;" />
                <?php else: ?>
                  <i class="lni lni-user text-lg"></i>
                <?php endif; ?>
              </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end p-2 shadow-sm border-0 mt-2" aria-labelledby="profile">
              <li>
                <a class="dropdown-item py-2" href="<?= $basePath ?>profil.php">
                  <i class="lni lni-pencil-alt me-2"></i> Edit Profil
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item py-2 text-danger" href="<?= $basePath ?>logout.php">
                  <i class="lni lni-exit me-2"></i> Keluar Aplikasi
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>

<script>
(function() {
  // Set icon sesuai tema saat ini
  function syncIcon() {
    var theme = document.documentElement.getAttribute('data-theme') || 'light';
    var icon  = document.getElementById('themeIcon');
    if (!icon) return;
    icon.className = theme === 'dark' ? 'lni lni-sun' : 'lni lni-night';
    document.getElementById('themeToggleBtn').title =
      theme === 'dark' ? 'Mode Terang' : 'Mode Gelap';
  }

  syncIcon();

  document.getElementById('themeToggleBtn').addEventListener('click', function() {
    var current = document.documentElement.getAttribute('data-theme') || 'light';
    var next    = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('sg_theme', next);
    syncIcon();
  });
})();
</script>
