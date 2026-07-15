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
    var t = localStorage.getItem('sg_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', t);
  })();
</script>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SnapGear – Sewa Kamera Berkualitas, Harga Terjangkau</title>
  <meta name="description" content="SnapGear – Tempat sewa kamera terpercaya. Kualitas bagus, harga murah, dan pelayanan terbaik untuk kebutuhan foto & video Anda." />
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
      --accent2:      #a78bfa;
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
      to   { transform: translate(60px,40px) rotate(12deg); }
    }
    @keyframes pulse-orb {
      0%,100% { transform: translate(-50%,-50%) scale(1);   opacity: 0.5; }
      50%      { transform: translate(-50%,-50%) scale(1.2); opacity: 1; }
    }

    /* ===== LAYOUT ===== */
    .page-wrapper { position: relative; z-index: 1; }

    /* ===== NAVBAR ===== */
    nav.navbar {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
      display: flex; align-items: center; justify-content: space-between;
      padding: 16px 60px;
      background: rgba(10,15,30,0.6);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--border);
      transition: all 0.3s ease;
    }
    nav.navbar.scrolled {
      padding: 10px 60px;
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
      width: 40px; height: 40px;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem; color: #fff; -webkit-text-fill-color: #fff;
      flex-shrink: 0;
      box-shadow: 0 4px 16px rgba(54,92,245,0.4);
    }
    .nav-links { display: flex; align-items: center; gap: 28px; }
    .nav-links a {
      color: var(--text-muted); font-size: 0.9rem; font-weight: 500;
      text-decoration: none; transition: color 0.2s;
    }
    .nav-links a:hover { color: #fff; }

    /* Active / highlighted nav link */
    .nav-links a.nav-sewa {
      color: var(--accent); font-weight: 700;
    }
    .nav-links a.nav-sewa:hover { color: #fff; }

    .btn-login {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 10px 26px;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: #fff !important;
      border-radius: 50px; font-weight: 600; font-size: 0.88rem;
      text-decoration: none; transition: all 0.3s ease;
      box-shadow: 0 4px 20px rgba(54,92,245,0.4);
      -webkit-text-fill-color: #fff;
    }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 30px rgba(54,92,245,0.6);
    }
    .nav-toggle {
      display: none; background: none; border: none;
      color: #fff; font-size: 1.5rem; cursor: pointer;
    }

    /* ===== HERO ===== */
    .hero {
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      text-align: center; padding: 130px 24px 80px;
    }
    .hero-content { max-width: 900px; }

    .hero-badge {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(54,92,245,0.15);
      border: 1px solid rgba(54,92,245,0.4);
      color: #93b4ff; padding: 8px 22px; border-radius: 50px;
      font-size: 0.82rem; font-weight: 600; letter-spacing: 0.5px;
      margin-bottom: 30px;
      animation: fade-up 0.6s ease-out both;
    }
    .hero-badge .dot {
      width: 7px; height: 7px; border-radius: 50%;
      background: var(--accent); animation: blink 1.5s ease-in-out infinite;
    }
    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }

    .hero h1 {
      font-size: clamp(3rem, 7.5vw, 5.5rem); font-weight: 900;
      line-height: 1.08; margin-bottom: 12px;
      animation: fade-up 0.6s 0.1s ease-out both;
      color: #fff;
    }
    .gradient-text {
      background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 50%, #a78bfa 100%);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    /* Tagline pills */
    .hero-tagline {
      display: flex; flex-wrap: wrap; justify-content: center; gap: 12px;
      margin: 20px 0 28px;
      animation: fade-up 0.6s 0.18s ease-out both;
    }
    .tag-pill {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(255,255,255,0.07);
      border: 1px solid rgba(54,92,245,0.35);
      color: #93b4ff; padding: 7px 18px; border-radius: 50px;
      font-size: 0.85rem; font-weight: 600;
    }
    .tag-pill .pill-icon { color: var(--accent); }

    .hero p {
      font-size: 1.1rem; color: var(--text-muted);
      max-width: 560px; margin: 0 auto 40px;
      animation: fade-up 0.6s 0.25s ease-out both;
    }
    .hero-actions {
      display: flex; align-items: center; justify-content: center;
      gap: 16px; flex-wrap: wrap;
      animation: fade-up 0.6s 0.35s ease-out both;
    }
    .btn-cta-primary {
      display: inline-flex; align-items: center; gap: 10px;
      padding: 17px 44px;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      color: #fff; border-radius: 50px;
      font-weight: 800; font-size: 1rem; text-decoration: none;
      transition: all 0.3s ease;
      box-shadow: 0 8px 32px rgba(54,92,245,0.4);
      -webkit-text-fill-color: #fff;
    }
    .btn-cta-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 16px 40px rgba(54,92,245,0.55);
    }
    .btn-cta-outline {
      display: inline-flex; align-items: center; gap: 10px;
      padding: 16px 36px; background: transparent; color: #fff;
      border: 2px solid rgba(255,255,255,0.25); border-radius: 50px;
      font-weight: 600; font-size: 1rem; text-decoration: none;
      transition: all 0.3s ease;
      -webkit-text-fill-color: #fff;
    }
    .btn-cta-outline:hover {
      border-color: var(--accent); color: var(--accent);
      background: rgba(0,198,255,0.06); transform: translateY(-2px);
      -webkit-text-fill-color: var(--accent);
    }

    /* Trust strip */
    .trust-strip {
      margin-top: 56px;
      display: flex; flex-wrap: wrap; justify-content: center; gap: 28px;
      animation: fade-up 0.6s 0.45s ease-out both;
    }
    .trust-item {
      display: flex; align-items: center; gap: 8px;
      color: var(--text-muted); font-size: 0.82rem; font-weight: 500;
    }
    .trust-item i { color: var(--accent); font-size: 1rem; }

    .scroll-hint {
      margin-top: 56px;
      display: flex; flex-direction: column; align-items: center; gap: 8px;
      color: var(--text-muted); font-size: 0.78rem;
      animation: fade-up 0.6s 0.55s ease-out both;
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
      padding: 44px 60px;
      border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
      background: rgba(255,255,255,0.03);
    }
    .stats-grid {
      display: flex; align-items: center; justify-content: center;
      flex-wrap: wrap; max-width: 960px; margin: 0 auto;
    }
    .stat-item {
      flex: 1; min-width: 150px; text-align: center; padding: 20px;
      border-right: 1px solid var(--border);
    }
    .stat-item:last-child { border-right: none; }
    .stat-num {
      font-size: 2.5rem; font-weight: 900; line-height: 1;
      background: linear-gradient(135deg, #fff, var(--accent));
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .stat-label { font-size: 0.82rem; color: var(--text-muted); margin-top: 6px; font-weight: 500; }

    /* ===== KAMERA SECTION ===== */
    .kamera-section { padding: 100px 60px; }
    .section-header { text-align: center; margin-bottom: 64px; }
    .section-tag {
      display: inline-block;
      background: rgba(54,92,245,0.15); border: 1px solid rgba(54,92,245,0.3);
      color: #93b4ff; padding: 6px 18px; border-radius: 50px;
      font-size: 0.78rem; font-weight: 700; letter-spacing: 1px;
      text-transform: uppercase; margin-bottom: 16px;
    }
    .section-header h2 { font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; margin-bottom: 16px; color: #fff; }
    .section-header p { font-size: 1rem; color: var(--text-muted); max-width: 480px; margin: 0 auto; }

    /* Camera cards */
    .kamera-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 24px; max-width: 1100px; margin: 0 auto;
    }
    .kamera-card {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(54,92,245,0.2);
      border-radius: 20px; overflow: hidden;
      transition: all 0.35s ease; position: relative;
    }
    .kamera-card:hover {
      transform: translateY(-8px);
      border-color: rgba(54,92,245,0.55);
      box-shadow: 0 20px 60px rgba(54,92,245,0.15);
    }
    .kamera-badge-popular {
      position: absolute; top: 14px; right: 14px;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      color: #fff; font-size: 0.72rem; font-weight: 800;
      padding: 4px 12px; border-radius: 50px;
      -webkit-text-fill-color: #fff;
    }
    .kamera-img {
      width: 100%; height: 180px;
      display: flex; align-items: center; justify-content: center;
      background: linear-gradient(135deg, rgba(54,92,245,0.12), rgba(0,198,255,0.08));
      font-size: 5rem;
    }
    .kamera-info { padding: 22px 24px; }
    .kamera-info h3 { font-size: 1.05rem; font-weight: 700; color: #fff; margin-bottom: 6px; }
    .kamera-info .kamera-desc { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 14px; line-height: 1.6; }
    .kamera-price {
      display: flex; align-items: center; justify-content: space-between;
    }
    .price-tag {
      font-size: 1.1rem; font-weight: 800;
      background: linear-gradient(135deg, #fff, var(--accent));
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .price-unit { font-size: 0.78rem; color: var(--text-muted); font-weight: 400; }
    .btn-sewa-mini {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 8px 18px; border-radius: 50px;
      background: rgba(54,92,245,0.15);
      border: 1px solid rgba(54,92,245,0.4);
      color: #93b4ff; font-size: 0.82rem; font-weight: 600;
      text-decoration: none; transition: all 0.25s;
      -webkit-text-fill-color: #93b4ff;
    }
    .btn-sewa-mini:hover {
      background: rgba(54,92,245,0.3); color: #fff;
      -webkit-text-fill-color: #fff;
    }

    /* ===== FEATURES / KEUNGGULAN ===== */
    .features { padding: 100px 60px; background: rgba(255,255,255,0.02); }
    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 24px; max-width: 1100px; margin: 0 auto;
    }
    .feature-card {
      background: var(--bg-card); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 36px 30px;
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
      width: 58px; height: 58px; border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem; margin-bottom: 20px;
    }
    .fi-orange { background: rgba(54,92,245,0.2);   color: #93b4ff; }
    .fi-yellow { background: rgba(0,198,255,0.15);   color: #7de3ff; }
    .fi-red    { background: rgba(139,92,246,0.2);   color: #c4b5fd; }
    .fi-green  { background: rgba(16,185,129,0.18);  color: #6ee7b7; }
    .feature-card h3 { font-size: 1.12rem; font-weight: 700; margin-bottom: 10px; color: #fff; }
    .feature-card p  { font-size: 0.88rem; color: var(--text-muted); line-height: 1.75; }

    /* ===== HOW IT WORKS ===== */
    .how-it-works { padding: 100px 60px; }
    .steps-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 32px; max-width: 1000px; margin: 0 auto;
    }
    .step-item { text-align: center; }
    .step-num {
      width: 64px; height: 64px; border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      color: #fff; font-size: 1.4rem; font-weight: 900;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 20px;
      box-shadow: 0 8px 28px rgba(54,92,245,0.35);
      -webkit-text-fill-color: #fff;
    }
    .step-item h4 { font-size: 1.05rem; font-weight: 700; margin-bottom: 8px; color: #fff; }
    .step-item p  { font-size: 0.88rem; color: var(--text-muted); }

    /* ===== TESTIMONI ===== */
    .testimoni { padding: 100px 60px; background: rgba(255,255,255,0.02); }
    .testi-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 24px; max-width: 1000px; margin: 0 auto;
    }
    .testi-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius); padding: 32px 28px;
      transition: all 0.3s ease;
    }
    .testi-card:hover { transform: translateY(-4px); border-color: rgba(54,92,245,0.4); }
    .testi-stars { color: var(--accent); font-size: 0.9rem; margin-bottom: 14px; }
    .testi-text { font-size: 0.92rem; color: var(--text-muted); line-height: 1.75; margin-bottom: 18px; font-style: italic; }
    .testi-author { display: flex; align-items: center; gap: 12px; }
    .testi-avatar {
      width: 42px; height: 42px; border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      display: flex; align-items: center; justify-content: center;
      font-weight: 800; font-size: 1rem; color: #fff;
      -webkit-text-fill-color: #fff; flex-shrink: 0;
    }
    .testi-name { font-size: 0.88rem; font-weight: 700; color: #fff; }
    .testi-role { font-size: 0.78rem; color: var(--text-muted); }

    /* ===== CTA ===== */
    .cta-section { padding: 100px 60px; text-align: center; }
    .cta-card {
      max-width: 820px; margin: 0 auto;
      background: linear-gradient(135deg, rgba(54,92,245,0.2) 0%, rgba(0,198,255,0.1) 100%);
      border: 1px solid rgba(54,92,245,0.35); border-radius: 28px;
      padding: 80px 60px; position: relative; overflow: hidden;
    }
    .cta-card::before {
      content: ''; position: absolute; top: -80px; right: -80px;
      width: 300px; height: 300px;
      background: radial-gradient(circle, rgba(0,198,255,0.15) 0%, transparent 70%);
      border-radius: 50%;
    }
    .cta-card h2 { font-size: 2.5rem; font-weight: 900; margin-bottom: 16px; color: #fff; }
    .cta-card p  { font-size: 1rem; color: #a8956a; margin-bottom: 36px; }

    /* ===== FOOTER ===== */
    footer {
      padding: 36px 60px; border-top: 1px solid var(--border);
      background: rgba(10,15,30,0.5);
    }
    .footer-inner {
      max-width: 1100px; margin: 0 auto;
      display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
      gap: 20px;
    }
    .footer-brand {
      font-size: 1.2rem; font-weight: 800;
      background: linear-gradient(135deg, #fff, var(--accent));
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .footer-links { display: flex; gap: 24px; flex-wrap: wrap; }
    .footer-links a { color: var(--text-muted); font-size: 0.85rem; text-decoration: none; transition: color 0.2s; }
    .footer-links a:hover { color: var(--accent); }
    .footer-copy { color: #64748b; font-size: 0.8rem; }

    /* ===== HUBUNGI KAMI ===== */
    .contact-section { padding: 100px 60px; position: relative; }
    .contact-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 24px; max-width: 1000px; margin: 0 auto;
    }
    .contact-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius); padding: 40px 32px;
      transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
      text-decoration: none;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }
    .contact-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 30px rgba(54,92,245,0.15);
      border-color: rgba(54,92,245,0.3);
    }
    .contact-icon {
      width: 60px; height: 60px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.8rem; margin-bottom: 24px;
      color: #fff; -webkit-text-fill-color: #fff;
      transition: all 0.3s ease;
    }
    .contact-card:hover .contact-icon {
      transform: scale(1.1) rotate(5deg);
    }
    .wa-icon { background: linear-gradient(135deg, #25D366, #128C7E); box-shadow: 0 6px 20px rgba(37,211,102,0.3); }
    .ig-icon { background: linear-gradient(135deg, #f9ce34, #ee2a7b, #6228d7); box-shadow: 0 6px 20px rgba(238,42,123,0.3); }
    .github-icon { background: linear-gradient(135deg, #24292e, #4a5568); box-shadow: 0 6px 20px rgba(36,41,46,0.3); }
    
    .contact-card h3 { font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 12px; }
    .contact-card p { font-size: 0.9rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 24px; flex-grow: 1; }
    .contact-btn {
      font-size: 0.88rem; font-weight: 600; padding: 10px 22px; border-radius: 50px;
      display: inline-flex; align-items: center; gap: 8px; transition: all 0.25s ease;
    }
    .btn-wa { background: rgba(37,211,102,0.1); color: #25D366; border: 1px solid rgba(37,211,102,0.25); }
    .btn-ig { background: rgba(238,42,123,0.1); color: #ee2a7b; border: 1px solid rgba(238,42,123,0.25); }
    .btn-github { background: rgba(255,255,255,0.05); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.15); }
    
    .contact-card:hover .btn-wa { background: #25D366; color: #fff; border-color: #25D366; -webkit-text-fill-color: #fff; }
    .contact-card:hover .btn-ig { background: linear-gradient(135deg, #f9ce34, #ee2a7b, #6228d7); color: #fff; border-color: transparent; -webkit-text-fill-color: #fff; }
    .contact-card:hover .btn-github { background: #fff; color: #0a0f1e; border-color: #fff; -webkit-text-fill-color: #0a0f1e; }

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
      nav.navbar, nav.navbar.scrolled { padding: 14px 20px; }
      .nav-links { display: none; }
      .nav-links.open {
        display: flex; flex-direction: column;
        position: fixed; inset: 0; top: 64px;
        background: rgba(10,15,30,0.98);
        align-items: center; justify-content: center; gap: 28px; z-index: 99;
      }
      .nav-toggle { display: block; }
      .kamera-section, .features, .how-it-works,
      .cta-section, .testimoni, .contact-section { padding: 60px 20px; }
      .stats-strip { padding: 28px 20px; }
      .stat-item { border-right: none; border-bottom: 1px solid var(--border); }
      .stat-item:last-child { border-bottom: none; }
      .cta-card { padding: 48px 24px; }
      .cta-card h2 { font-size: 1.9rem; }
      footer { padding: 28px 20px; }
      .hero-tagline { gap: 8px; }
      .trust-strip { gap: 18px; }
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
      <a href="#beranda">Beranda</a>
      <a href="#kamera" class="nav-sewa">📷 Sewa Kamera</a>
      <a href="#keunggulan">Keunggulan</a>
      <a href="#cara-sewa">Cara Sewa</a>
      <a href="#testimoni">Testimoni</a>
      <a href="#kontak">Hubungi Kami</a>
      <a href="login.php" class="btn-login">
        <i class="lni lni-enter"></i> Login
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
        🎯 Tempat Sewa Kamera #1 Terpercaya
      </div>
      <h1>
        Sewa Kamera di<br />
        <span class="gradient-text">SnapGear</span>
      </h1>

      <div class="hero-tagline">
        <span class="tag-pill"><span class="pill-icon">✅</span> Kualitas Bagus</span>
        <span class="tag-pill"><span class="pill-icon">💰</span> Harga Murah</span>
        <span class="tag-pill"><span class="pill-icon">🛡️</span> Terpercaya</span>
      </div>

      <p>
        Dapatkan kamera impian untuk foto &amp; video terbaik Anda.
        Pilihan unit lengkap, kondisi prima, harga bersahabat, dan siap antar ke lokasi Anda.
      </p>

      <div class="hero-actions">
        <a href="#kamera" class="btn-cta-primary" id="ctaSewa">
          <i class="lni lni-camera"></i>
          Sewa Kamera Sekarang
        </a>
        <a href="login.php" class="btn-cta-outline">
          <i class="lni lni-enter"></i>
          Masuk / Daftar
        </a>
      </div>

      <div class="trust-strip">
        <div class="trust-item"><i class="lni lni-checkmark-circle"></i> Unit Kondisi Prima</div>
        <div class="trust-item"><i class="lni lni-protection"></i> Aman & Bergaransi</div>
        <div class="trust-item"><i class="lni lni-support"></i> CS 24 Jam</div>
        <div class="trust-item"><i class="lni lni-delivery"></i> Antar Jemput</div>
      </div>

      <div class="scroll-hint">
        <div class="mouse"><div class="wheel"></div></div>
        <span>Scroll untuk lihat kamera</span>
      </div>
    </div>
  </section>

  <!-- STATS -->
  <div class="stats-strip animate-on-scroll">
    <div class="stats-grid">
      <div class="stat-item">
        <div class="stat-num">100+</div>
        <div class="stat-label">Unit Kamera</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">500+</div>
        <div class="stat-label">Pelanggan Puas</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">4.9★</div>
        <div class="stat-label">Rating Kepuasan</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">3Thn+</div>
        <div class="stat-label">Pengalaman</div>
      </div>
    </div>
  </div>

  <!-- SEWA KAMERA -->
  <section class="kamera-section" id="kamera">
    <div class="section-header animate-on-scroll">
      <div class="section-tag">📷 Pilihan Kamera</div>
      <h2>Kamera Pilihan Kami</h2>
      <p>Unit terlengkap untuk segala kebutuhan: wedding, portrait, landscape, vlog, dan lainnya.</p>
    </div>
    <div class="kamera-grid">
      <!-- Card 1 -->
      <div class="kamera-card animate-on-scroll">
        <span class="kamera-badge-popular">🔥 Terlaris</span>
        <div class="kamera-img">📷</div>
        <div class="kamera-info">
          <h3>Canon EOS R50</h3>
          <p class="kamera-desc">Mirrorless ringan, cocok untuk foto &amp; video konten kreator. Autofokus canggih, bodi kompak.</p>
          <div class="kamera-price">
            <div>
              <div class="price-tag">Rp 120.000</div>
              <div class="price-unit">/ hari</div>
            </div>
            <a href="login.php" class="btn-sewa-mini"><i class="lni lni-cart"></i> Sewa</a>
          </div>
        </div>
      </div>
      <!-- Card 2 -->
      <div class="kamera-card animate-on-scroll" style="transition-delay:0.1s">
        <div class="kamera-img">🎥</div>
        <div class="kamera-info">
          <h3>Sony A7 III</h3>
          <p class="kamera-desc">Full-frame profesional untuk foto pernikahan, portrait, dan video sinematik 4K. Kualitas premium.</p>
          <div class="kamera-price">
            <div>
              <div class="price-tag">Rp 250.000</div>
              <div class="price-unit">/ hari</div>
            </div>
            <a href="login.php" class="btn-sewa-mini"><i class="lni lni-cart"></i> Sewa</a>
          </div>
        </div>
      </div>
      <!-- Card 3 -->
      <div class="kamera-card animate-on-scroll" style="transition-delay:0.2s">
        <span class="kamera-badge-popular">✨ Baru</span>
        <div class="kamera-img">🔭</div>
        <div class="kamera-info">
          <h3>Nikon Z30</h3>
          <p class="kamera-desc">Vlogging kamera ringan tanpa viewfinder. Layar flip-out, stabilizer canggih, cocok untuk YouTube.</p>
          <div class="kamera-price">
            <div>
              <div class="price-tag">Rp 150.000</div>
              <div class="price-unit">/ hari</div>
            </div>
            <a href="login.php" class="btn-sewa-mini"><i class="lni lni-cart"></i> Sewa</a>
          </div>
        </div>
      </div>
      <!-- Card 4 -->
      <div class="kamera-card animate-on-scroll" style="transition-delay:0.3s">
        <div class="kamera-img">🎞️</div>
        <div class="kamera-info">
          <h3>GoPro Hero 12</h3>
          <p class="kamera-desc">Action camera anti air, cocok untuk petualangan, olahraga ekstrem, dan konten outdoor seru.</p>
          <div class="kamera-price">
            <div>
              <div class="price-tag">Rp 80.000</div>
              <div class="price-unit">/ hari</div>
            </div>
            <a href="login.php" class="btn-sewa-mini"><i class="lni lni-cart"></i> Sewa</a>
          </div>
        </div>
      </div>
    </div>

    <div style="text-align:center; margin-top: 48px;" class="animate-on-scroll">
      <a href="login.php" class="btn-cta-primary" style="display:inline-flex;">
        <i class="lni lni-camera"></i> Lihat Semua Koleksi Kamera
      </a>
    </div>
  </section>

  <!-- KEUNGGULAN -->
  <section class="features" id="keunggulan">
    <div class="section-header animate-on-scroll">
      <div class="section-tag">⭐ Keunggulan Kami</div>
      <h2>Kenapa Pilih SnapGear?</h2>
      <p>Kami hadir untuk memastikan pengalaman sewa kamera Anda menjadi mudah, aman, dan menyenangkan.</p>
    </div>
    <div class="features-grid">
      <div class="feature-card animate-on-scroll">
        <div class="feature-icon fi-orange"><i class="lni lni-dollar"></i></div>
        <h3>Harga Paling Murah</h3>
        <p>Tarif sewa transparan tanpa biaya tersembunyi. Pilih paket harian, mingguan, atau bulanan sesuai kebutuhan Anda.</p>
      </div>
      <div class="feature-card animate-on-scroll" style="transition-delay:0.1s">
        <div class="feature-icon fi-yellow"><i class="lni lni-star"></i></div>
        <h3>Kualitas Terjamin</h3>
        <p>Setiap unit dikalibrasi dan dicek kelayakannya sebelum disewakan. Anda mendapat kamera dalam kondisi prima.</p>
      </div>
      <div class="feature-card animate-on-scroll" style="transition-delay:0.2s">
        <div class="feature-icon fi-green"><i class="lni lni-protection"></i></div>
        <h3>Terpercaya &amp; Bergaransi</h3>
        <p>Sudah dipercaya ratusan pelanggan. Jika ada kendala teknis, kami ganti unit atau refund penuh. Tanpa drama.</p>
      </div>
      <div class="feature-card animate-on-scroll" style="transition-delay:0.3s">
        <div class="feature-icon fi-red"><i class="lni lni-delivery"></i></div>
        <h3>Antar &amp; Jemput Gratis</h3>
        <p>Kami mengantarkan kamera ke lokasi Anda tanpa biaya tambahan dalam kota. Hemat waktu, langsung foto!</p>
      </div>
      <div class="feature-card animate-on-scroll" style="transition-delay:0.4s">
        <div class="feature-icon fi-orange"><i class="lni lni-support"></i></div>
        <h3>CS Online 24 Jam</h3>
        <p>Tim kami siap membantu kapan saja. Ada pertanyaan soal kamera, tips foto, atau kendala? Hubungi kami!</p>
      </div>
      <div class="feature-card animate-on-scroll" style="transition-delay:0.5s">
        <div class="feature-icon fi-yellow"><i class="lni lni-files"></i></div>
        <h3>Proses Cepat &amp; Mudah</h3>
        <p>Booking online dalam hitungan menit. Pilih kamera, tentukan tanggal, konfirmasi—selesai! Tidak ribet sama sekali.</p>
      </div>
    </div>
  </section>

  <!-- CARA SEWA -->
  <section class="how-it-works" id="cara-sewa">
    <div class="section-header animate-on-scroll">
      <div class="section-tag">✦ Cara Sewa</div>
      <h2>Sewa Kamera dalam 4 Langkah</h2>
      <p>Prosesnya simpel, cepat, dan bisa dilakukan dari mana saja kapan saja.</p>
    </div>
    <div class="steps-grid">
      <div class="step-item animate-on-scroll">
        <div class="step-num">1</div>
        <h4>Pilih Kamera</h4>
        <p>Browsing koleksi kamera kami dan pilih yang sesuai kebutuhan serta budget Anda.</p>
      </div>
      <div class="step-item animate-on-scroll" style="transition-delay:0.15s">
        <div class="step-num">2</div>
        <h4>Tentukan Tanggal</h4>
        <p>Pilih tanggal mulai dan selesai penyewaan. Harga otomatis dihitung untuk Anda.</p>
      </div>
      <div class="step-item animate-on-scroll" style="transition-delay:0.3s">
        <div class="step-num">3</div>
        <h4>Konfirmasi &amp; Bayar</h4>
        <p>Konfirmasi pesanan dan lakukan pembayaran via transfer bank atau dompet digital.</p>
      </div>
      <div class="step-item animate-on-scroll" style="transition-delay:0.45s">
        <div class="step-num">4</div>
        <h4>Terima &amp; Foto!</h4>
        <p>Kamera diantar ke alamat Anda. Mulai berkarya dan abadikan momen terbaik!</p>
      </div>
    </div>
  </section>

  <!-- TESTIMONI -->
  <section class="testimoni" id="testimoni">
    <div class="section-header animate-on-scroll">
      <div class="section-tag">💬 Testimoni</div>
      <h2>Kata Pelanggan Kami</h2>
      <p>Ribuan pelanggan sudah mempercayakan kebutuhan kamera mereka kepada SnapGear.</p>
    </div>
    <div class="testi-grid">
      <div class="testi-card animate-on-scroll">
        <div class="testi-stars">★★★★★</div>
        <p class="testi-text">"Pelayanannya luar biasa! Kameranya bersih, baterai penuh, langsung siap pakai. Harganya pun sangat terjangkau. Puas banget sewa di SnapGear!"</p>
        <div class="testi-author">
          <div class="testi-avatar">A</div>
          <div>
            <div class="testi-name">Andi Saputra</div>
            <div class="testi-role">Fotografer Pernikahan</div>
          </div>
        </div>
      </div>
      <div class="testi-card animate-on-scroll" style="transition-delay:0.1s">
        <div class="testi-stars">★★★★★</div>
        <p class="testi-text">"Sewa GoPro untuk trip ke Lombok. Prosesnya cepat, diantar tepat waktu. Rekomendasiin banget buat temen-temen yang mau trip!"</p>
        <div class="testi-author">
          <div class="testi-avatar">R</div>
          <div>
            <div class="testi-name">Rina Dewi</div>
            <div class="testi-role">Travel Vlogger</div>
          </div>
        </div>
      </div>
      <div class="testi-card animate-on-scroll" style="transition-delay:0.2s">
        <div class="testi-stars">★★★★★</div>
        <p class="testi-text">"Sony A7 III-nya mantap! Foto prewed hasilnya di luar ekspektasi. CS-nya juga sabar jawab semua pertanyaan saya. 5 bintang!"</p>
        <div class="testi-author">
          <div class="testi-avatar">B</div>
          <div>
            <div class="testi-name">Bagas Wicaksono</div>
            <div class="testi-role">Konten Kreator</div>
          </div>
        </div>
      </div>
  </section>

  <!-- HUBUNGI KAMI -->
  <section class="contact-section" id="kontak">
    <div class="section-header animate-on-scroll">
      <div class="section-tag">✦ Kontak Kami</div>
      <h2>Ada Pertanyaan? Hubungi Kami</h2>
      <p>Tim kami siap melayani Anda 24 jam sehari. Silakan pilih platform yang paling nyaman bagi Anda.</p>
    </div>
    <div class="contact-grid">
      <!-- WhatsApp Card -->
      <a href="https://wa.me/6282125259423" target="_blank" class="contact-card animate-on-scroll">
        <div class="contact-icon wa-icon"><i class="lni lni-whatsapp"></i></div>
        <h3>WhatsApp Chat</h3>
        <p>Hubungi admin untuk sewa cepat, negosiasi harga, atau menanyakan ketersediaan unit kamera secara real-time.</p>
        <span class="contact-btn btn-wa">Hubungi via WA <i class="lni lni-arrow-right"></i></span>
      </a>

      <!-- Instagram Card -->
      <a href="https://instagram.com/ollecniloiv" target="_blank" class="contact-card animate-on-scroll" style="transition-delay:0.1s">
        <div class="contact-icon ig-icon"><i class="lni lni-instagram"></i></div>
        <h3>Instagram</h3>
        <p>Ikuti akun Instagram kami untuk melihat galeri foto hasil sewa, promo bulanan, serta tips fotografi terbaru.</p>
        <span class="contact-btn btn-ig">Ikuti @ollecniloiv <i class="lni lni-arrow-right"></i></span>
      </a>

      <!-- GitHub Card -->
      <a href="https://github.com/Violincello06" target="_blank" class="contact-card animate-on-scroll" style="transition-delay:0.2s">
        <div class="contact-icon github-icon"><i class="lni lni-github"></i></div>
        <h3>GitHub Profile</h3>
        <p>Mau lihat programer pro ga dek</p>
        <span class="contact-btn btn-github">Kunjungi GitHub <i class="lni lni-arrow-right"></i></span>
      </a>
    </div>
  </section>

  <!-- CTA -->
  <section class="cta-section">
    <div class="cta-card animate-on-scroll">
      <h2>Siap Mulai Berkarya? 📸</h2>
      <p>Sewa kamera terbaik sekarang dan abadikan setiap momen berharga dengan kualitas profesional.</p>
      <a href="#kamera" class="btn-cta-primary" style="display:inline-flex;">
        <i class="lni lni-camera"></i> Sewa Kamera Sekarang
      </a>
    </div>
  </section>

  <!-- FOOTER -->
  <footer>
    <div class="footer-inner">
      <div class="footer-brand">📷 SnapGear</div>
      <div class="footer-links">
        <a href="#beranda">Beranda</a>
        <a href="#kamera">Sewa Kamera</a>
        <a href="#keunggulan">Keunggulan</a>
        <a href="#cara-sewa">Cara Sewa</a>
        <a href="#testimoni">Testimoni</a>
        <a href="#kontak">Kontak</a>
        <a href="login.php">Login</a>
      </div>
      <div class="footer-copy">
        &copy; <?= date('Y') ?> <strong>SnapGear</strong> — Kualitas Bagus, Harga Murah &amp; Terpercaya
      </div>
    </div>
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
  }, { threshold: 0.1 });
  document.querySelectorAll('.animate-on-scroll').forEach(el => observer.observe(el));
</script>
</body>
</html>
