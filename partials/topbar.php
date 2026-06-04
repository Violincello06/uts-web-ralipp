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
          <div class="profile-box ml-15">
            <button class="dropdown-toggle bg-transparent border-0 d-flex align-items-center" type="button" id="profile" data-bs-toggle="dropdown" aria-expanded="false">
              <div class="profile-info d-none d-md-block text-end me-3">
                <h6 class="text-sm fw-bold text-dark"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['username']) ?></h6>
                <p class="text-xs text-muted">@<?= htmlspecialchars($_SESSION['username'] ?? '') ?></p>
              </div>
              <div class="avatar-image bg-primary d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 40px; height: 40px; border-radius: 50%;">
                <i class="lni lni-user text-lg"></i>
              </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end p-2 shadow-sm border-0 mt-2" aria-labelledby="profile">
              <li>
                <a class="dropdown-item py-2 text-danger" href="logout.php">
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
