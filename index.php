<?php
session_start();
// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: main.php");
    exit;
}
?>
<!DOCTYPE html>
<!-- Theme applied immediately to avoid flash -->
<script>
  (function() {
    var t = localStorage.getItem('sg_theme') || 'light';
    document.documentElement.setAttribute('data-theme', t);
  })();
</script>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SnapGear – Sistem Manajemen Rental Kamera</title>
  <meta name="description" content="SnapGear adalah sistem manajemen rental kamera profesional. Kelola data kamera, penyewaan, dan pengembalian dengan mudah dan efisien." />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/lineicons.css" type="text/css" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --primary:      #365CF5;
      --primary-dark: #2344d6;
      --accent:       #00c6ff;
      --bg-dark:      #0a0f1e;
      --bg-card:      rgba(255,255,255,0.05);
      --text-light:   #e2e8f0;
      --text-muted:   #94a3b8;
      --border:       rgba(255,255,255,0.1);
      --radius:       16px;
    }

    html { scroll-behavior: smooth; }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-dark);
      color: var(--text-light);
      overflow-x: hidden;
      line-height: 1.6;
    }

    /* ===== ANIMATED BACKGROUND ===== */
    .bg-canvas {
      position: fixed; inset: 0; z-index: 0; overflow: hidden;
    }
    .bg-canvas::before {
      content: '';
      position: absolute;
      width: 800px; height: 800px;
      background: radial-gradient(circle, rgba(54,92,245,0.3) 0%, transparent 70%);
      top: -200px; left: -200px;
      animation: drift 18s ease-in-out infinite alternate;
    }
    .bg-canvas::after {
      content: '';
      position: absolute;
      width: 600px; height: 600px;
      background: radial-gradient(circle, rgba(0,198,255,0.2) 0%, transparent 70%);
      bottom: -100px; right: -100px;
      animation: drift 14s ease-in-out infinite alternate-reverse;
    }
    .orb-mid {
      position: absolute;
      width: 500px; height: 500px;
      background: radial-gradient(circle, rgba(139,92,246,0.15) 0%, transparent 70%);
      top: 50%; left: 50%;
      transform: translate(-50%,-50%);
      animation: pulse-orb 8s ease-in-out infinite;
    }
    .stars {
      position: absolute; inset: 0;
      background-image:
        radial-gradient(1px 1px at 10% 15%, rgba(255,255,255,0.6) 0%, transparent 100%),
        radial-gradient(1px 1px at 30% 60%, rgba(255,255,255,0.4) 0%, transparent 100%),
        radial-gradient(1px 1px at 55% 25%, rgba(255,255,255,0.5) 0%, transparent 100%),
        radial-gradient(1px 1px at 75% 80%, rgba(255,255,255,0.3) 0%, transparent 100%),
        radial-gradient(1px 1px at 90% 10%, rgba(255,255,255,0.6) 0%, transparent 100%),
        radial-gradient(1px 1px at 20% 90%, rgba(255,255,255,0.4) 0%, transparent 100%),
        radial-gradient(1px 1px at 65% 50%, rgba(255,255,255,0.3) 0%, transparent 100%),
        radial-gradient(1px 1px at 45% 75%, rgba(255,255,255,0.5) 0%, transparent 100%);
    }
    @keyframes drift {
      from { transform: translate(0,0) rotate(0deg); }
      to   { transform: translate(60px,40px) rotate(15deg); }
    }
    @keyframes pulse-orb {
      0%,100% { transform: translate(-50%,-50%) scale(1); opacity: 0.5; }
      50%      { transform: translate(-50%,-50%) scale(1.2); opacity: 1; }
    }

    /* ===== LAYOUT ===== */
    .page-wrapper { position: relative; z-index: 1; }

    /* ===== NAVBAR ===== */
    nav.navbar {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
      display: flex; align-items: center; justify-content: space-between;
      padding: 18px 60px;
      background: rgba(10,15,30,0.6);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--border);
      transition: all 0.3s ease;
    }
    nav.navbar.scrolled {
      padding: 12px 60px;
      background: rgba(10,15,30,0.95);
    }
    .nav-brand {
      font-size: 1.5rem; font-weight: 800;
      background: linear-gradient(135deg, #fff 0%, var(--accent) 100%);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      background-clip: text;
      text-decoration: none;
      display: flex; align-items: center; gap: 10px;
    }
    .nav-brand .brand-icon {
      width: 38px; height: 38px;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem; color: #fff; -webkit-text-fill-color: #fff;
      flex-shrink: 0;
    }
    .nav-links { display: flex; align-items: center; gap: 32px; }
    .nav-links a {
      color: var(--text-muted); font-size: 0.9rem; font-weight: 500;
      text-decoration: none; transition: color 0.2s;
    }
    .nav-links a:hover { color: #fff; }
    .btn-login {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 10px 28px;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: #fff !important;
      border-radius: 50px; font-weight: 600; font-size: 0.9rem;
      text-decoration: none; transition: all 0.3s ease;
      box-shadow: 0 4px 20px rgba(54,92,245,0.4);
    }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 30px rgba(54,92,245,0.6);
    }
    .nav-toggle {
      display: none; background: none; border: none;
      color: #fff; font-size: 1.5rem; cursor: pointer;
    }

    /* Theme toggle button for landing page */
    .lp-theme-btn {
      display: inline-flex; align-items: center; justify-content: center;
      width: 38px; height: 38px; border-radius: 50%;
      background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
      color: #fff; font-size: 1rem; cursor: pointer;
      transition: all 0.3s ease; flex-shrink: 0;
      -webkit-text-fill-color: #fff;
    }
    .lp-theme-btn:hover {
      background: rgba(255,255,255,0.2);
      transform: rotate(20deg) scale(1.1);
    }

    /* Light mode overrides for landing page */
    [data-theme="light"] body {
      background-color: #f0f4ff !important;
    }
    [data-theme="light"] .bg-canvas::before {
      background: radial-gradient(circle, rgba(54,92,245,0.15) 0%, transparent 70%);
    }
    [data-theme="light"] .bg-canvas::after {
      background: radial-gradient(circle, rgba(0,198,255,0.1) 0%, transparent 70%);
    }
    [data-theme="light"] .orb-mid {
      background: radial-gradient(circle, rgba(139,92,246,0.08) 0%, transparent 70%);
    }
    [data-theme="light"] .stars { opacity: 0.15; }
    [data-theme="light"] nav.navbar {
      background: rgba(240,244,255,0.8);
      border-bottom-color: rgba(54,92,245,0.15);
    }
    [data-theme="light"] nav.navbar.scrolled {
      background: rgba(240,244,255,0.97);
    }
    [data-theme="light"] .nav-brand {
      background: linear-gradient(135deg, #1e3a8a 0%, #365CF5 100%);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    [data-theme="light"] .nav-brand .brand-icon { -webkit-text-fill-color: #fff; }
    [data-theme="light"] .nav-links a { color: #475569; }
    [data-theme="light"] .nav-links a:hover { color: #1e3a8a; }
    [data-theme="light"] .lp-theme-btn {
      background: rgba(54,92,245,0.1);
      border-color: rgba(54,92,245,0.25);
      color: #365CF5; -webkit-text-fill-color: #365CF5;
    }
    [data-theme="light"] .hero h1 { color: #0f172a; }
    [data-theme="light"] .hero p { color: #475569; }
    [data-theme="light"] .hero-badge {
      background: rgba(54,92,245,0.08);
      border-color: rgba(54,92,245,0.3); color: #365CF5;
    }
    [data-theme="light"] .stats-strip {
      background: rgba(255,255,255,0.7);
      border-color: rgba(54,92,245,0.12);
    }
    [data-theme="light"] .stat-item { border-right-color: rgba(54,92,245,0.12); }
    [data-theme="light"] .stat-label { color: #64748b; }
    [data-theme="light"] .feature-card {
      background: rgba(255,255,255,0.85);
      border-color: rgba(54,92,245,0.12);
    }
    [data-theme="light"] .feature-card h3 { color: #0f172a; }
    [data-theme="light"] .feature-card p { color: #475569; }
    [data-theme="light"] .how-it-works { background: rgba(255,255,255,0.4); }
    [data-theme="light"] .step-item h4 { color: #0f172a; }
    [data-theme="light"] .step-item p { color: #475569; }
    [data-theme="light"] .section-header h2 { color: #0f172a; }
    [data-theme="light"] .section-header p { color: #475569; }
    [data-theme="light"] .scroll-hint { color: #64748b; }
    [data-theme="light"] .scroll-hint .mouse { border-color: rgba(54,92,245,0.4); }
    [data-theme="light"] .scroll-hint .wheel { background: rgba(54,92,245,0.5); }
    [data-theme="light"] footer {
      border-top-color: rgba(54,92,245,0.12);
      color: #64748b;
    }

    /* ===== HERO ===== */
    .hero {
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      text-align: center; padding: 120px 24px 80px;
    }
    .hero-content { max-width: 860px; }
    .hero-badge {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(54,92,245,0.15);
      border: 1px solid rgba(54,92,245,0.4);
      color: #93b4ff; padding: 8px 20px; border-radius: 50px;
      font-size: 0.82rem; font-weight: 600; letter-spacing: 0.5px;
      margin-bottom: 28px;
      animation: fade-up 0.6s ease-out both;
    }
    .hero-badge .dot {
      width: 6px; height: 6px; border-radius: 50%;
      background: var(--accent); animation: blink 1.5s ease-in-out infinite;
    }
    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }
    .hero h1 {
      font-size: clamp(2.8rem, 7vw, 5rem); font-weight: 900;
      line-height: 1.1; margin-bottom: 24px;
      animation: fade-up 0.6s 0.1s ease-out both;
    }
    .gradient-text {
      background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 50%, #a78bfa 100%);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .hero p {
      font-size: 1.15rem; color: var(--text-muted);
      max-width: 580px; margin: 0 auto 40px;
      animation: fade-up 0.6s 0.2s ease-out both;
    }
    .hero-actions {
      display: flex; align-items: center; justify-content: center;
      gap: 16px; flex-wrap: wrap;
      animation: fade-up 0.6s 0.3s ease-out both;
    }
    .btn-cta-primary {
      display: inline-flex; align-items: center; gap: 10px;
      padding: 16px 40px;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      color: #fff; border-radius: 50px;
      font-weight: 700; font-size: 1rem; text-decoration: none;
      transition: all 0.3s ease;
      box-shadow: 0 8px 32px rgba(54,92,245,0.4);
    }
    .btn-cta-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 16px 40px rgba(54,92,245,0.55);
    }
    .btn-cta-outline {
      display: inline-flex; align-items: center; gap: 10px;
      padding: 15px 36px; background: transparent; color: #fff;
      border: 2px solid rgba(255,255,255,0.25); border-radius: 50px;
      font-weight: 600; font-size: 1rem; text-decoration: none;
      transition: all 0.3s ease;
    }
    .btn-cta-outline:hover {
      border-color: var(--accent); color: var(--accent);
      background: rgba(0,198,255,0.06); transform: translateY(-2px);
    }
    .scroll-hint {
      margin-top: 60px;
      display: flex; flex-direction: column; align-items: center; gap: 8px;
      color: var(--text-muted); font-size: 0.78rem;
      animation: fade-up 0.6s 0.5s ease-out both;
    }
    .scroll-hint .mouse {
      width: 22px; height: 36px;
      border: 2px solid rgba(255,255,255,0.3); border-radius: 11px;
      display: flex; align-items: flex-start; justify-content: center; padding-top: 5px;
    }
    .scroll-hint .wheel {
      width: 3px; height: 7px; background: rgba(255,255,255,0.5); border-radius: 2px;
      animation: scroll-wheel 1.5s ease-in-out infinite;
    }
    @keyframes scroll-wheel {
      0%   { transform: translateY(0); opacity: 1; }
      100% { transform: translateY(8px); opacity: 0; }
    }

    /* ===== STATS ===== */
    .stats-strip {
      padding: 40px 60px;
      border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
      background: rgba(255,255,255,0.03);
    }
    .stats-grid {
      display: flex; align-items: center; justify-content: center;
      flex-wrap: wrap; max-width: 900px; margin: 0 auto;
    }
    .stat-item {
      flex: 1; min-width: 150px; text-align: center; padding: 20px;
      border-right: 1px solid var(--border);
    }
    .stat-item:last-child { border-right: none; }
    .stat-num {
      font-size: 2.4rem; font-weight: 900; line-height: 1;
      background: linear-gradient(135deg, #fff, var(--accent));
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .stat-label { font-size: 0.82rem; color: var(--text-muted); margin-top: 6px; font-weight: 500; }

    /* ===== FEATURES ===== */
    .features { padding: 100px 60px; }
    .section-header { text-align: center; margin-bottom: 64px; }
    .section-tag {
      display: inline-block;
      background: rgba(54,92,245,0.15); border: 1px solid rgba(54,92,245,0.3);
      color: #93b4ff; padding: 6px 16px; border-radius: 50px;
      font-size: 0.78rem; font-weight: 700; letter-spacing: 1px;
      text-transform: uppercase; margin-bottom: 16px;
    }
    .section-header h2 { font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; margin-bottom: 16px; }
    .section-header p { font-size: 1rem; color: var(--text-muted); max-width: 480px; margin: 0 auto; }
    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 24px; max-width: 1100px; margin: 0 auto;
    }
    .feature-card {
      background: var(--bg-card); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 36px 32px;
      transition: all 0.3s ease; position: relative; overflow: hidden;
    }
    .feature-card::before {
      content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
      background: linear-gradient(90deg, transparent, var(--primary), transparent);
      opacity: 0; transition: opacity 0.3s;
    }
    .feature-card:hover { transform: translateY(-6px); border-color: rgba(54,92,245,0.4); }
    .feature-card:hover::before { opacity: 1; }
    .feature-icon {
      width: 56px; height: 56px; border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem; margin-bottom: 20px;
    }
    .fi-blue   { background: rgba(54,92,245,0.2);  color: #93b4ff; }
    .fi-cyan   { background: rgba(0,198,255,0.15);  color: #7de3ff; }
    .fi-purple { background: rgba(139,92,246,0.2);  color: #c4b5fd; }
    .fi-green  { background: rgba(16,185,129,0.2);  color: #6ee7b7; }
    .feature-card h3 { font-size: 1.15rem; font-weight: 700; margin-bottom: 10px; }
    .feature-card p  { font-size: 0.9rem; color: var(--text-muted); line-height: 1.7; }

    /* ===== HOW IT WORKS ===== */
    .how-it-works { padding: 100px 60px; background: rgba(255,255,255,0.02); }
    .steps-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 32px; max-width: 1000px; margin: 0 auto;
    }
    .step-item { text-align: center; }
    .step-num {
      width: 60px; height: 60px; border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      color: #fff; font-size: 1.3rem; font-weight: 800;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 20px;
      box-shadow: 0 8px 24px rgba(54,92,245,0.35);
    }
    .step-item h4 { font-size: 1.05rem; font-weight: 700; margin-bottom: 8px; }
    .step-item p  { font-size: 0.88rem; color: var(--text-muted); }

    /* ===== CTA ===== */
    .cta-section { padding: 100px 60px; text-align: center; }
    .cta-card {
      max-width: 800px; margin: 0 auto;
      background: linear-gradient(135deg, rgba(54,92,245,0.2) 0%, rgba(0,198,255,0.1) 100%);
      border: 1px solid rgba(54,92,245,0.35); border-radius: 28px;
      padding: 72px 60px; position: relative; overflow: hidden;
    }
    .cta-card::before {
      content: ''; position: absolute; top: -80px; right: -80px;
      width: 300px; height: 300px;
      background: radial-gradient(circle, rgba(0,198,255,0.15) 0%, transparent 70%);
      border-radius: 50%;
    }
    .cta-card h2 { font-size: 2.4rem; font-weight: 800; margin-bottom: 16px; }
    .cta-card p  { font-size: 1rem; color: var(--text-muted); margin-bottom: 36px; }

    /* ===== FOOTER ===== */
    footer {
      padding: 32px 60px; border-top: 1px solid var(--border);
      text-align: center; color: var(--text-muted); font-size: 0.83rem;
    }
    footer a { color: #93b4ff; text-decoration: none; }
    footer a:hover { color: var(--accent); }

    /* ===== ANIMATIONS ===== */
    @keyframes fade-up {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .animate-on-scroll {
      opacity: 0; transform: translateY(30px);
      transition: opacity 0.6s ease, transform 0.6s ease;
    }
    .animate-on-scroll.visible { opacity: 1; transform: translateY(0); }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
      nav.navbar, nav.navbar.scrolled { padding: 16px 20px; }
      .nav-links { display: none; }
      .nav-links.open {
        display: flex; flex-direction: column;
        position: fixed; inset: 0; top: 64px;
        background: rgba(10,15,30,0.98);
        align-items: center; justify-content: center; gap: 28px; z-index: 99;
      }
      .nav-toggle { display: block; }
      .features, .how-it-works, .cta-section { padding: 60px 20px; }
      .stats-strip { padding: 30px 20px; }
      .stat-item { border-right: none; border-bottom: 1px solid var(--border); }
      .stat-item:last-child { border-bottom: none; }
      .cta-card { padding: 48px 24px; }
      .cta-card h2 { font-size: 1.8rem; }
      footer { padding: 28px 20px; }
    }
  </style>
</head>
<body>

<!-- Animated Background -->
<div class="bg-canvas">
  <div class="stars"></div>
  <div class="orb-mid"></div>
</div>

<div class="page-wrapper">

  <!-- NAVBAR -->
  <nav class="navbar" id="mainNav">
    <a href="index.php" class="nav-brand">
      <div class="brand-icon"><i class="lni lni-camera"></i></div>
      SnapGear
    </a>
    <div class="nav-links" id="navLinks">
      <a href="#fitur">Fitur</a>
      <a href="#cara-kerja">Cara Kerja</a>
      <button id="lpThemeBtn" class="lp-theme-btn" title="Ganti Mode" aria-label="Toggle tema">
        <i id="lpThemeIcon" class="lni lni-night"></i>
      </button>
      <a href="login.php" class="btn-login">
        <i class="lni lni-enter"></i> Login Admin
      </a>
    </div>
    <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">
      <i class="lni lni-menu"></i>
    </button>
  </nav>

  <!-- HERO -->
  <section class="hero" id="beranda">
    <div class="hero-content">
      <div class="hero-badge">
        <span class="dot"></span>
        Sistem Manajemen Rental Kamera Profesional
      </div>
      <h1>
        Kelola Rental Kamera<br />
        dengan <span class="gradient-text">SnapGear</span>
      </h1>
      <p>
        Platform manajemen rental kamera all-in-one. Pantau inventaris, kelola penyewaan,
        dan catat pengembalian kamera dengan sistem yang modern dan efisien.
      </p>
      <div class="hero-actions">
        <a href="login.php" class="btn-cta-primary" id="ctaLogin">
          <i class="lni lni-enter"></i>
          Masuk ke Dashboard
        </a>
        <a href="#fitur" class="btn-cta-outline">
          <i class="lni lni-arrow-down"></i>
          Lihat Fitur
        </a>
      </div>
      <div class="scroll-hint">
        <div class="mouse"><div class="wheel"></div></div>
        <span>Scroll untuk lihat lebih</span>
      </div>
    </div>
  </section>

  <!-- STATS -->
  <div class="stats-strip animate-on-scroll">
    <div class="stats-grid">
      <div class="stat-item">
        <div class="stat-num">100+</div>
        <div class="stat-label">Jenis Kamera</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">24/7</div>
        <div class="stat-label">Sistem Online</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">3</div>
        <div class="stat-label">Modul Utama</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">Real-time</div>
        <div class="stat-label">Update Status</div>
      </div>
    </div>
  </div>

  <!-- FEATURES -->
  <section class="features" id="fitur">
    <div class="section-header animate-on-scroll">
      <div class="section-tag">✦ Fitur Unggulan</div>
      <h2>Semua yang Anda Butuhkan<br />dalam Satu Platform</h2>
      <p>Dirancang khusus untuk bisnis rental kamera agar operasional lebih terorganisir dan profesional.</p>
    </div>
    <div class="features-grid">
      <div class="feature-card animate-on-scroll">
        <div class="feature-icon fi-blue"><i class="lni lni-camera"></i></div>
        <h3>Manajemen Kamera</h3>
        <p>Pantau seluruh inventaris kamera secara real-time. Lacak status tersedia, disewa, hingga dalam perbaikan dengan mudah.</p>
      </div>
      <div class="feature-card animate-on-scroll" style="transition-delay:0.1s">
        <div class="feature-icon fi-cyan"><i class="lni lni-clipboard"></i></div>
        <h3>Data Penyewaan</h3>
        <p>Catat dan kelola setiap transaksi penyewaan. Lihat riwayat lengkap siapa menyewa apa dan kapan secara terstruktur.</p>
      </div>
      <div class="feature-card animate-on-scroll" style="transition-delay:0.2s">
        <div class="feature-icon fi-purple"><i class="lni lni-reload"></i></div>
        <h3>Pengembalian Otomatis</h3>
        <p>Proses pengembalian kamera dengan kalkulasi otomatis. Status kamera terupdate secara langsung setelah pengembalian.</p>
      </div>
      <div class="feature-card animate-on-scroll" style="transition-delay:0.3s">
        <div class="feature-icon fi-green"><i class="lni lni-bar-chart"></i></div>
        <h3>Dashboard Analitik</h3>
        <p>Visualisasi data perbandingan status kamera dengan grafik interaktif. Buat keputusan bisnis berbasis data yang akurat.</p>
      </div>
      <div class="feature-card animate-on-scroll" style="transition-delay:0.4s">
        <div class="feature-icon fi-blue"><i class="lni lni-users"></i></div>
        <h3>Manajemen Pengguna</h3>
        <p>Sistem autentikasi aman dengan role admin. Kontrol akses dan kelola profil pengguna platform rental Anda.</p>
      </div>
      <div class="feature-card animate-on-scroll" style="transition-delay:0.5s">
        <div class="feature-icon fi-cyan"><i class="lni lni-files"></i></div>
        <h3>Import &amp; Export Data</h3>
        <p>Impor data kamera secara massal dan ekspor laporan penyewaan dengan mudah untuk kebutuhan administrasi bisnis.</p>
      </div>
    </div>
  </section>

  <!-- HOW IT WORKS -->
  <section class="how-it-works" id="cara-kerja">
    <div class="section-header animate-on-scroll">
      <div class="section-tag">✦ Cara Kerja</div>
      <h2>Mulai dalam 4 Langkah Mudah</h2>
      <p>Sistem SnapGear dirancang sesederhana mungkin agar langsung bisa digunakan tanpa pelatihan panjang.</p>
    </div>
    <div class="steps-grid">
      <div class="step-item animate-on-scroll">
        <div class="step-num">1</div>
        <h4>Login Admin</h4>
        <p>Masuk menggunakan akun admin yang sudah terdaftar di sistem SnapGear.</p>
      </div>
      <div class="step-item animate-on-scroll" style="transition-delay:0.15s">
        <div class="step-num">2</div>
        <h4>Input Data Kamera</h4>
        <p>Tambahkan inventaris kamera beserta spesifikasi, harga sewa, dan statusnya.</p>
      </div>
      <div class="step-item animate-on-scroll" style="transition-delay:0.3s">
        <div class="step-num">3</div>
        <h4>Kelola Transaksi</h4>
        <p>Catat penyewaan dan pengembalian. Sistem otomatis memperbarui status kamera.</p>
      </div>
      <div class="step-item animate-on-scroll" style="transition-delay:0.45s">
        <div class="step-num">4</div>
        <h4>Monitor Dashboard</h4>
        <p>Pantau semua aktivitas rental melalui dashboard analitik yang informatif.</p>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="cta-section">
    <div class="cta-card animate-on-scroll">
      <h2>Siap Mengelola Rental Kamera<br />Lebih Profesional?</h2>
      <p>Masuk ke dashboard admin sekarang dan mulai optimalkan bisnis rental kamera Anda.</p>
      <a href="login.php" class="btn-cta-primary" style="display:inline-flex;">
        <i class="lni lni-enter"></i> Login ke Dashboard Admin
      </a>
    </div>
  </section>

  <!-- FOOTER -->
  <footer>
    <p>&copy; <?= date('Y') ?> <strong>SnapGear</strong> — Sistem Manajemen Rental Kamera &nbsp;|&nbsp;
       Dibuat untuk <a href="#">UTS Web Development</a>
    </p>
  </footer>

</div><!-- end page-wrapper -->

<script>
  // Navbar scroll effect
  const nav = document.getElementById('mainNav');
  window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 60);
  });

  // Mobile nav toggle
  const navToggle = document.getElementById('navToggle');
  const navLinks  = document.getElementById('navLinks');
  navToggle.addEventListener('click', () => {
    navLinks.classList.toggle('open');
    navToggle.querySelector('i').className =
      navLinks.classList.contains('open') ? 'lni lni-close' : 'lni lni-menu';
  });
  navLinks.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => {
      navLinks.classList.remove('open');
      navToggle.querySelector('i').className = 'lni lni-menu';
    });
  });

  // Intersection observer for scroll animations
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
  }, { threshold: 0.12 });
  document.querySelectorAll('.animate-on-scroll').forEach(el => observer.observe(el));

  // ===== THEME TOGGLE (Landing Page) =====
  (function() {
    function syncLpIcon() {
      var theme = document.documentElement.getAttribute('data-theme') || 'light';
      var icon  = document.getElementById('lpThemeIcon');
      if (icon) icon.className = theme === 'dark' ? 'lni lni-sun' : 'lni lni-night';
      var btn = document.getElementById('lpThemeBtn');
      if (btn) btn.title = theme === 'dark' ? 'Mode Terang' : 'Mode Gelap';
    }
    syncLpIcon();
    var btn = document.getElementById('lpThemeBtn');
    if (btn) {
      btn.addEventListener('click', function() {
        var cur  = document.documentElement.getAttribute('data-theme') || 'light';
        var next = cur === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('sg_theme', next);
        syncLpIcon();
      });
    }
  })();
</script>
</body>
</html>
