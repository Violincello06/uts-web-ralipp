<?php
/**
 * partials/ticker_mini.php
 * Mini camera ticker - displayed at the bottom of all non-dashboard pages.
 * Include this file inside <main> just before its closing </main> tag.
 * Requires: $conn (MySQLi connection) to already be available in scope.
 */
$_mini_ticker = $conn->query("SELECT nama_kamera, merk, status FROM kamera ORDER BY nama_kamera ASC");
$_mini_num = $_mini_ticker ? $_mini_ticker->num_rows : 0;
// Constant speed: 3.5s per camera, min 12s
$_mini_duration = max(12, $_mini_num * 3.5);
?>
<div class="ticker-mini-bar">
  <div class="ticker-mini-label">
    <i class="lni lni-camera"></i>
    <span>Kamera</span>
  </div>
  <div class="ticker-mini-track">
    <div class="ticker-mini-content" style="animation-duration: <?= $_mini_duration ?>s;">
      <?php
      if ($_mini_ticker && $_mini_ticker->num_rows > 0):
        for ($__i = 0; $__i < 2; $__i++):
          $_mini_ticker->data_seek(0);
          while ($__cam = $_mini_ticker->fetch_assoc()):
            $__sc = 'sm-' . strtolower($__cam['status']);
      ?>
        <span class="ticker-mini-item">
          <span class="tm-brand"><?= htmlspecialchars($__cam['merk']) ?></span>
          <span class="tm-name"><?= htmlspecialchars($__cam['nama_kamera']) ?></span>
          <span class="tm-badge <?= $__sc ?>"><?= ucfirst($__cam['status']) ?></span>
        </span>
      <?php
          endwhile;
        endfor;
      else:
      ?>
        <span class="ticker-mini-item">Belum ada data kamera.</span>
      <?php endif; ?>
    </div>
  </div>
</div>
