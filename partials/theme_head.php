    <link rel="stylesheet" href="assets/css/custom.css" />
    <link rel="stylesheet" href="assets/css/darkmode.css" />
    <!-- Anti-flash: apply saved theme before render -->
    <script>(function(){var t=localStorage.getItem('sg_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
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
