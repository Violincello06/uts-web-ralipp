
<?php $basePath = $basePath ?? ''; ?>
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - Rental Kamera</title>

    <link rel="stylesheet" href="<?= $basePath ?>assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/lineicons.css" type="text/css" />
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/materialdesignicons.min.css" type="text/css" />
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/main.css" />
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/custom.css" />
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/darkmode.css" />

    <!-- Terapkan tema SEBELUM render untuk mencegah flash -->
    <script>
      (function() {
        var saved = localStorage.getItem('sg_theme') || 'light';
        document.documentElement.setAttribute('data-theme', saved);
      })();
    </script>
    <!-- Dynamic cosmic background insertion -->
    <script>
      document.addEventListener("DOMContentLoaded", function() {
        if (!document.querySelector('.bg-canvas')) {
          var bg = document.createElement('div');
          bg.className = 'bg-canvas';
          bg.innerHTML = '<div class="stars"></div><div class="orb-mid"></div>';
          document.body.insertBefore(bg, document.body.firstChild);
        }
      });
    </script>
  </head>