<?php
// MEDIQ - Smart Healthcare Serial & Live Queue Platform
// Modern Landing Page matching demo layout
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MEDIQ — Smart Healthcare Serial & Live Chamber Queue</title>
  <meta name="description" content="Bangladesh's #1 digital doctor appointment and live chamber queue tracking platform. Book serials in seconds, track queue from home, and eliminate hospital waiting time.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #246BFD;
      --primary-dark: #1A56DB;
      --primary-light: #EFF4FF;
      --primary-soft: #F5F8FF;
      --accent-green: #10B981;
      --accent-green-light: #ECFDF5;
      --accent-amber: #F59E0B;
      --accent-amber-light: #FFFBEB;
      --text-main: #0F172A;
      --text-muted: #64748B;
      --text-light: #94A3B8;
      --bg-page: #FAFBFC;
      --bg-card: #FFFFFF;
      --border-color: #E2E8F0;
      --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
      --shadow-md: 0 4px 20px -2px rgba(36, 107, 253, 0.08);
      --shadow-lg: 0 20px 40px -10px rgba(15, 23, 42, 0.08);
      --radius-lg: 24px;
      --radius-md: 16px;
      --radius-sm: 10px;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
      background-color: var(--bg-page);
      color: var(--text-main);
      line-height: 1.6;
      overflow-x: hidden;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 24px;
    }

    /* Top Sticky Navigation */
    .navbar {
      position: sticky;
      top: 0;
      z-index: 1000;
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(14px);
      border-bottom: 1px solid rgba(226, 232, 240, 0.8);
      transition: all 0.3s ease;
    }
    .nav-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 76px;
    }
    .brand-logo {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
    }
    .logo-icon {
      width: 42px;
      height: 42px;
      background: linear-gradient(135deg, #246BFD 0%, #1A56DB 100%);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 20px;
      box-shadow: 0 6px 16px rgba(36, 107, 253, 0.3);
    }
    .logo-text {
      font-size: 24px;
      font-weight: 900;
      color: var(--primary);
      letter-spacing: 0.5px;
    }
    .logo-tag {
      font-size: 11px;
      background: var(--primary-light);
      color: var(--primary);
      padding: 2px 8px;
      border-radius: 99px;
      font-weight: 700;
      text-transform: uppercase;
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 32px;
      list-style: none;
    }
    .nav-links a {
      text-decoration: none;
      color: var(--text-muted);
      font-size: 14.5px;
      font-weight: 600;
      transition: color 0.2s;
    }
    .nav-links a:hover {
      color: var(--primary);
    }

    .nav-actions {
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 12px 24px;
      border-radius: 99px;
      font-size: 14px;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.25s ease;
      cursor: pointer;
      border: none;
    }
    .btn-outline {
      background: #fff;
      color: var(--text-main);
      border: 1.5px solid var(--border-color);
    }
    .btn-outline:hover {
      border-color: var(--primary);
      color: var(--primary);
      background: var(--primary-soft);
    }
    .btn-primary {
      background: var(--primary);
      color: #fff;
      box-shadow: 0 6px 18px rgba(36, 107, 253, 0.28);
    }
    .btn-primary:hover {
      background: var(--primary-dark);
      transform: translateY(-1px);
      box-shadow: 0 8px 24px rgba(36, 107, 253, 0.35);
    }

    /* Hero Section */
    .hero-section {
      padding: 70px 0 80px 0;
      position: relative;
      overflow: hidden;
    }
    .hero-bg-circle {
      position: absolute;
      border-radius: 50%;
      pointer-events: none;
      filter: blur(80px);
      z-index: 0;
    }
    .hero-bg-1 {
      width: 500px;
      height: 500px;
      background: rgba(36, 107, 253, 0.08);
      top: -100px;
      right: 10%;
    }
    .hero-bg-2 {
      width: 400px;
      height: 400px;
      background: rgba(245, 158, 11, 0.06);
      top: 150px;
      left: -80px;
    }

    .hero-grid {
      display: grid;
      grid-template-columns: 1.15fr 0.85fr;
      gap: 60px;
      align-items: center;
      position: relative;
      z-index: 1;
    }
    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--primary-light);
      color: var(--primary);
      padding: 8px 16px;
      border-radius: 99px;
      font-size: 13px;
      font-weight: 700;
      margin-bottom: 24px;
    }
    .hero-title {
      font-size: 54px;
      font-weight: 900;
      line-height: 1.14;
      letter-spacing: -1.5px;
      color: var(--text-main);
      margin-bottom: 24px;
    }
    .hero-title span {
      color: var(--primary);
      background: linear-gradient(135deg, #246BFD 0%, #00B4D8 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .hero-bullets {
      list-style: none;
      margin-bottom: 36px;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }
    .hero-bullets li {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 16px;
      font-weight: 600;
      color: var(--text-main);
    }
    .bullet-num {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: var(--primary-light);
      color: var(--primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 800;
      flex-shrink: 0;
    }

    /* Store Download Buttons */
    .download-group {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 14px;
      margin-bottom: 38px;
    }
    .store-btn {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      background: #0F172A;
      color: #fff;
      padding: 12px 22px;
      border-radius: 14px;
      text-decoration: none;
      transition: all 0.2s ease;
      box-shadow: 0 4px 14px rgba(15, 23, 42, 0.15);
    }
    .store-btn:hover {
      background: #1E293B;
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(15, 23, 42, 0.22);
    }
    .store-btn i { font-size: 26px; }
    .store-btn-text {
      display: flex;
      flex-direction: column;
      text-align: left;
    }
    .store-btn-text small {
      font-size: 10px;
      text-transform: uppercase;
      color: #94A3B8;
      font-weight: 600;
      line-height: 1;
    }
    .store-btn-text strong {
      font-size: 14px;
      font-weight: 700;
      line-height: 1.3;
    }
    .apk-btn {
      background: linear-gradient(135deg, #10B981 0%, #059669 100%);
      color: #fff;
      padding: 12px 22px;
      border-radius: 14px;
      display: inline-flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      font-weight: 700;
      box-shadow: 0 6px 18px rgba(16, 185, 129, 0.28);
      transition: all 0.2s ease;
    }
    .apk-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(16, 185, 129, 0.35);
    }
    .apk-btn i { font-size: 26px; }

    /* Trust Proof Chips */
    .trust-chips {
      display: flex;
      align-items: center;
      gap: 20px;
      padding-top: 10px;
    }
    .trust-card {
      background: #fff;
      border: 1px solid var(--border-color);
      border-radius: 14px;
      padding: 10px 16px;
      display: flex;
      align-items: center;
      gap: 12px;
      box-shadow: var(--shadow-sm);
    }
    .trust-icon {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
    }
    .trust-icon.green { background: #ECFDF5; color: #10B981; }
    .trust-icon.gold { background: #FEF3C7; color: #D97706; }
    .trust-data strong {
      display: block;
      font-size: 14px;
      font-weight: 800;
      color: var(--text-main);
    }
    .trust-data span {
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 500;
    }

    /* Phone Mockup Frame */
    .hero-phone-container {
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
    }
    .phone-mockup {
      width: 320px;
      height: 640px;
      background: #1E293B;
      border-radius: 46px;
      padding: 12px;
      box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.3), 0 0 0 1px rgba(255,255,255,0.1) inset;
      position: relative;
      z-index: 2;
    }
    .phone-screen {
      width: 100%;
      height: 100%;
      background: #FFFFFF;
      border-radius: 36px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      position: relative;
    }
    .phone-notch {
      width: 120px;
      height: 22px;
      background: #1E293B;
      position: absolute;
      top: 0;
      left: 50%;
      transform: translateX(-50%);
      border-bottom-left-radius: 14px;
      border-bottom-right-radius: 14px;
      z-index: 10;
    }
    .screen-header {
      padding: 34px 18px 12px 18px;
      background: #FFFFFF;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #F1F5F9;
    }
    .screen-header-left {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .screen-avatar {
      width: 36px;
      height: 36px;
      background: var(--primary-light);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      color: var(--primary);
      font-size: 14px;
    }
    .screen-body {
      padding: 16px;
      flex: 1;
      overflow-y: hidden;
      background: #F8FAFC;
    }

    .app-queue-card {
      background: #FFFFFF;
      border-radius: 18px;
      padding: 16px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.05);
      border: 1px solid #EEF2F6;
      margin-bottom: 14px;
    }
    .queue-status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #ECFDF5;
      color: #059669;
      font-size: 11px;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 99px;
      margin-bottom: 10px;
    }
    .queue-status-dot {
      width: 8px;
      height: 8px;
      background: #10B981;
      border-radius: 50%;
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
      animation: pulse 1.8s infinite;
    }
    @keyframes pulse {
      0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
      70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
      100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
    .app-doc-info {
      display: flex;
      gap: 10px;
      align-items: center;
      margin-bottom: 14px;
    }
    .doc-mini-img {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: var(--primary-light);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary);
      font-weight: 900;
      font-size: 16px;
    }
    .token-display-box {
      background: #F8FAFC;
      border-radius: 12px;
      padding: 12px;
      display: flex;
      justify-content: space-around;
      text-align: center;
    }
    .token-item strong {
      display: block;
      font-size: 22px;
      font-weight: 900;
      color: var(--primary);
      line-height: 1;
    }
    .token-item span {
      font-size: 10px;
      color: var(--text-muted);
      text-transform: uppercase;
      font-weight: 700;
    }

    .float-card {
      position: absolute;
      background: #FFFFFF;
      border-radius: 16px;
      padding: 12px 18px;
      box-shadow: 0 14px 34px rgba(15, 23, 42, 0.12);
      border: 1px solid #E2E8F0;
      display: flex;
      align-items: center;
      gap: 12px;
      z-index: 3;
      animation: float 4s ease-in-out infinite;
    }
    .float-1 { top: 60px; left: -35px; }
    .float-2 { bottom: 90px; right: -30px; animation-delay: 2s; }
    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-8px); }
    }

    .section-head {
      text-align: center;
      max-width: 680px;
      margin: 0 auto 56px auto;
    }
    .section-subtitle {
      font-size: 13px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 1.2px;
      color: var(--primary);
      margin-bottom: 10px;
    }
    .section-title {
      font-size: 38px;
      font-weight: 900;
      color: var(--text-main);
      letter-spacing: -1px;
      line-height: 1.2;
    }
    .section-desc {
      font-size: 16px;
      color: var(--text-muted);
      margin-top: 12px;
    }

    /* Why Simple is Better */
    .why-section {
      padding: 90px 0;
      background: #FFFFFF;
      border-top: 1px solid var(--border-color);
      border-bottom: 1px solid var(--border-color);
    }
    .why-grid {
      display: grid;
      grid-template-columns: 0.9fr 1.1fr;
      gap: 60px;
      align-items: center;
    }
    .why-visual-box {
      background: linear-gradient(135deg, #EFF4FF 0%, #DBEAFE 100%);
      border-radius: 36px;
      padding: 40px;
      position: relative;
      text-align: center;
    }
    .why-feature-list {
      display: flex;
      flex-direction: column;
      gap: 28px;
    }
    .why-feature-item {
      display: flex;
      gap: 20px;
    }
    .feature-icon-circle {
      width: 52px;
      height: 52px;
      border-radius: 16px;
      background: var(--primary-light);
      color: var(--primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      flex-shrink: 0;
    }
    .why-feature-item h3 {
      font-size: 19px;
      font-weight: 800;
      color: var(--text-main);
      margin-bottom: 6px;
    }
    .why-feature-item p {
      font-size: 14.5px;
      color: var(--text-muted);
      line-height: 1.5;
    }

    /* Steps Section */
    .steps-section { padding: 100px 0; }
    .steps-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 24px;
    }
    .step-card {
      background: #FFFFFF;
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 32px 24px;
      position: relative;
      transition: all 0.3s ease;
      box-shadow: var(--shadow-sm);
    }
    .step-card:hover {
      transform: translateY(-6px);
      box-shadow: var(--shadow-lg);
      border-color: var(--primary);
    }
    .step-badge {
      width: 40px;
      height: 40px;
      background: var(--primary-light);
      color: var(--primary);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 900;
      font-size: 16px;
      margin-bottom: 20px;
    }
    .step-card h4 {
      font-size: 18px;
      font-weight: 800;
      margin-bottom: 10px;
      color: var(--text-main);
    }
    .step-card p {
      font-size: 14px;
      color: var(--text-muted);
      line-height: 1.5;
    }

    /* Inside MEDIQ Screens */
    .screens-section {
      padding: 90px 0;
      background: #FFFFFF;
      border-top: 1px solid var(--border-color);
    }
    .app-preview-cards {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 28px;
    }
    .preview-card {
      background: #FAFBFC;
      border: 1px solid var(--border-color);
      border-radius: 24px;
      padding: 28px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .preview-tag {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 99px;
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      margin-bottom: 12px;
    }
    .tag-blue { background: var(--primary-light); color: var(--primary); }
    .tag-green { background: #ECFDF5; color: #10B981; }
    .tag-purple { background: #F3E8FF; color: #9333EA; }

    .preview-card h3 {
      font-size: 20px;
      font-weight: 800;
      margin-bottom: 10px;
      color: var(--text-main);
    }
    .preview-card p {
      font-size: 14px;
      color: var(--text-muted);
      line-height: 1.5;
      margin-bottom: 20px;
    }
    .preview-feature-bullets {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 8px;
      font-size: 13px;
      color: var(--text-main);
      font-weight: 600;
    }
    .preview-feature-bullets li i {
      color: var(--accent-green);
      margin-right: 6px;
    }

    /* Download CTA */
    .download-cta-section { padding: 80px 0 100px 0; }
    .cta-card {
      background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%);
      border-radius: 36px;
      padding: 60px;
      color: #FFFFFF;
      display: grid;
      grid-template-columns: 1.2fr 0.8fr;
      align-items: center;
      gap: 40px;
      position: relative;
      overflow: hidden;
      box-shadow: var(--shadow-lg);
    }
    .cta-card::before {
      content: "";
      position: absolute;
      top: -100px;
      right: -100px;
      width: 350px;
      height: 350px;
      background: radial-gradient(circle, rgba(36, 107, 253, 0.4) 0%, transparent 70%);
      border-radius: 50%;
    }
    .cta-title {
      font-size: 42px;
      font-weight: 900;
      line-height: 1.15;
      margin-bottom: 16px;
    }
    .cta-desc {
      font-size: 16px;
      color: #94A3B8;
      margin-bottom: 32px;
      line-height: 1.6;
    }

    /* Portals Access */
    .portals-section {
      padding: 60px 0;
      background: #F1F5F9;
      border-top: 1px solid var(--border-color);
    }
    .portals-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
    }
    .portal-box {
      background: #fff;
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 22px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      text-decoration: none;
      transition: all 0.2s;
    }
    .portal-box:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-md);
      border-color: var(--primary);
    }
    .portal-box h4 {
      font-size: 16px;
      font-weight: 800;
      color: var(--text-main);
      margin-bottom: 4px;
    }
    .portal-box p {
      font-size: 12.5px;
      color: var(--text-muted);
    }
    .portal-arrow {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: var(--primary-light);
      color: var(--primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
    }

    /* Footer */
    footer {
      background: #FFFFFF;
      border-top: 1px solid var(--border-color);
      padding: 60px 0 30px 0;
    }
    .footer-grid {
      display: grid;
      grid-template-columns: 1.5fr repeat(3, 1fr);
      gap: 40px;
      margin-bottom: 40px;
    }
    .footer-brand p {
      font-size: 14px;
      color: var(--text-muted);
      margin-top: 14px;
      max-width: 300px;
    }
    .footer-col h5 {
      font-size: 14px;
      font-weight: 800;
      color: var(--text-main);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 16px;
    }
    .footer-col ul {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .footer-col a {
      text-decoration: none;
      color: var(--text-muted);
      font-size: 13.5px;
      transition: color 0.2s;
    }
    .footer-col a:hover { color: var(--primary); }
    .footer-bottom {
      border-top: 1px solid var(--border-color);
      padding-top: 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 13px;
      color: var(--text-light);
    }

    @media (max-width: 992px) {
      .hero-grid { grid-template-columns: 1fr; text-align: center; }
      .hero-title { font-size: 38px; }
      .hero-bullets { align-items: center; }
      .download-group { justify-content: center; }
      .trust-chips { justify-content: center; }
      .why-grid { grid-template-columns: 1fr; }
      .steps-grid { grid-template-columns: repeat(2, 1fr); }
      .app-preview-cards { grid-template-columns: 1fr; }
      .cta-card { grid-template-columns: 1fr; padding: 36px; text-align: center; }
      .portals-grid { grid-template-columns: 1fr; }
      .footer-grid { grid-template-columns: 1fr 1fr; }
      .nav-links { display: none; }
    }
  </style>
</head>
<body>

  <!-- Top Sticky Navigation -->
  <nav class="navbar">
    <div class="container nav-inner">
      <a href="#" class="brand-logo">
        <div class="logo-icon">
          <i class="fa-solid fa-heart-pulse"></i>
        </div>
        <div>
          <span class="logo-text">MEDIQ</span>
          <span class="logo-tag">Live Queue</span>
        </div>
      </a>

      <ul class="nav-links">
        <li><a href="#how-it-works">How it Works</a></li>
        <li><a href="#why-mediq">Our Service</a></li>
        <li><a href="#inside-app">Inside MEDIQ</a></li>
        <li><a href="#download">Download App</a></li>
        <li><a href="#portals">Web Portals</a></li>
      </ul>

      <div class="nav-actions">
        <a href="doctor_web/public/login.php" class="btn btn-outline">
          <i class="fa-solid fa-user-doctor"></i> Doctor Web
        </a>
        <a href="downloads/mediq-latest.apk" download class="btn btn-primary">
          <i class="fa-solid fa-download"></i> Download APK
        </a>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="hero-bg-circle hero-bg-1"></div>
    <div class="hero-bg-circle hero-bg-2"></div>

    <div class="container hero-grid">
      <!-- Left Hero Content -->
      <div class="hero-content">
        <div class="hero-badge">
          <i class="fa-solid fa-bolt-lightning"></i> 1st Real-Time Chamber Queue in Bangladesh
        </div>

        <h1 class="hero-title">
          Your favourite <span>online clinic & chamber queue.</span>
        </h1>

        <ul class="hero-bullets">
          <li>
            <div class="bullet-num">1</div>
            <div><strong>Zero Waiting Time</strong> — Real-time live token tracker from your home</div>
          </li>
          <li>
            <div class="bullet-num">2</div>
            <div><strong>BMDC Certified Doctors</strong> — Verified specialist consultation profiles</div>
          </li>
          <li>
            <div class="bullet-num">3</div>
            <div><strong>Instant Serial Booking</strong> — Guaranteed tokens without chamber rush</div>
          </li>
        </ul>

        <!-- Download Buttons -->
        <div class="download-group">
          <!-- Direct Android APK Download -->
          <a href="downloads/mediq-latest.apk" download class="apk-btn">
            <i class="fa-brands fa-android"></i>
            <div class="store-btn-text">
              <small>Direct Install</small>
              <strong>Download Android APK</strong>
            </div>
          </a>

          <!-- Google Play Badge -->
          <a href="#download" class="store-btn">
            <i class="fa-brands fa-google-play"></i>
            <div class="store-btn-text">
              <small>Get it on</small>
              <strong>Google Play</strong>
            </div>
          </a>

          <!-- Apple App Store Badge -->
          <a href="#download" class="store-btn">
            <i class="fa-brands fa-apple"></i>
            <div class="store-btn-text">
              <small>Download on</small>
              <strong>App Store</strong>
            </div>
          </a>
        </div>

        <!-- Trust Metric Chips -->
        <div class="trust-chips">
          <div class="trust-card">
            <div class="trust-icon green">
              <i class="fa-solid fa-circle-down"></i>
            </div>
            <div class="trust-data">
              <strong>50,000+</strong>
              <span>App Downloads</span>
            </div>
          </div>

          <div class="trust-card">
            <div class="trust-icon gold">
              <i class="fa-solid fa-star"></i>
            </div>
            <div class="trust-data">
              <strong>4.9 ★ (12k+)</strong>
              <span>Patient Reviews</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Interactive Phone Mockup Frame -->
      <div class="hero-phone-container">
        <!-- Floating Card 1 -->
        <div class="float-card float-1">
          <div class="queue-status-dot"></div>
          <div>
            <strong style="font-size: 13px; display: block; color: var(--text-main);">Chamber is Live!</strong>
            <span style="font-size: 11px; color: var(--text-muted);">Doctor is seeing Token #07</span>
          </div>
        </div>

        <!-- Phone Device Frame -->
        <div class="phone-mockup">
          <div class="phone-notch"></div>
          <div class="phone-screen">
            <!-- Screen App Header -->
            <div class="screen-header">
              <div class="screen-header-left">
                <div class="screen-avatar">P</div>
                <div>
                  <div style="font-size: 11px; color: var(--text-muted); font-weight: 600;">Good Morning 👋</div>
                  <div style="font-size: 14px; font-weight: 800; color: var(--text-main);">Patient Portal</div>
                </div>
              </div>
              <div style="color: var(--primary); font-size: 18px;">
                <i class="fa-regular fa-bell"></i>
              </div>
            </div>

            <!-- Screen App Body -->
            <div class="screen-body">
              <div class="app-queue-card">
                <div class="queue-status-badge">
                  <div class="queue-status-dot"></div> Live Chamber Session
                </div>

                <div class="app-doc-info">
                  <div class="doc-mini-img">DR</div>
                  <div>
                    <div style="font-size: 14px; font-weight: 800; color: var(--text-main);">Dr. Farhan Rahman</div>
                    <div style="font-size: 11px; color: var(--text-muted);">MBBS, FCPS • Cardiology</div>
                  </div>
                </div>

                <div class="token-display-box">
                  <div class="token-item">
                    <span>Now Calling</span>
                    <strong>#07</strong>
                  </div>
                  <div style="width: 1px; background: #E2E8F0;"></div>
                  <div class="token-item">
                    <span>Your Serial</span>
                    <strong style="color: #10B981;">#09</strong>
                  </div>
                </div>

                <div style="margin-top: 12px; background: #EFF4FF; border-radius: 8px; padding: 8px; text-align: center;">
                  <span style="font-size: 11px; font-weight: 700; color: var(--primary);">
                    <i class="fa-regular fa-clock"></i> Your Turn in: ~8 mins (2 ahead)
                  </span>
                </div>
              </div>

              <!-- Doctors List Preview in Phone -->
              <div style="font-size: 12px; font-weight: 800; color: var(--text-main); margin-bottom: 8px;">
                Top Verified Specialists
              </div>
              <div style="background: #fff; border-radius: 12px; padding: 10px; display: flex; align-items: center; gap: 10px; border: 1px solid #EEF2F6; margin-bottom: 8px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: #ECFDF5; color: #10B981; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px;">AS</div>
                <div style="flex: 1;">
                  <div style="font-size: 12px; font-weight: 700;">Dr. Anika Sharma</div>
                  <div style="font-size: 10px; color: var(--text-muted);">Gynecology • ৳800</div>
                </div>
                <div style="background: var(--primary-light); color: var(--primary); padding: 4px 8px; border-radius: 6px; font-size: 10px; font-weight: 800;">Book</div>
              </div>

              <div style="background: #fff; border-radius: 12px; padding: 10px; display: flex; align-items: center; gap: 10px; border: 1px solid #EEF2F6;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px;">KH</div>
                <div style="flex: 1;">
                  <div style="font-size: 12px; font-weight: 700;">Dr. Kamal Hossain</div>
                  <div style="font-size: 10px; color: var(--text-muted);">Pediatrics • ৳600</div>
                </div>
                <div style="background: var(--primary-light); color: var(--primary); padding: 4px 8px; border-radius: 6px; font-size: 10px; font-weight: 800;">Book</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Floating Card 2 -->
        <div class="float-card float-2">
          <div style="width: 32px; height: 32px; background: #EFF4FF; color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px;">
            <i class="fa-solid fa-file-prescription"></i>
          </div>
          <div>
            <strong style="font-size: 13px; display: block; color: var(--text-main);">Digital Prescription</strong>
            <span style="font-size: 11px; color: var(--text-muted);">Instant QR Verification</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Why Simple is Better Section -->
  <section class="why-section" id="why-mediq">
    <div class="container why-grid">
      <div class="why-visual-box">
        <div style="font-size: 64px; color: var(--primary); margin-bottom: 20px;">
          <i class="fa-solid fa-stethoscope"></i>
        </div>
        <h3 style="font-size: 26px; font-weight: 900; color: var(--text-main); margin-bottom: 10px;">
          “হাসপাতালে বসে ঘণ্টার পর ঘণ্টা অপেক্ষা আর নয়”
        </h3>
        <p style="font-size: 15px; color: var(--text-muted); max-width: 380px; margin: 0 auto 20px auto;">
          MEDIQ brings transparency to Bangladesh healthcare. You know your token number, exact doctor arrival, and live queue count before you leave home.
        </p>
        <div style="display: inline-flex; align-items: center; gap: 8px; background: #fff; padding: 10px 18px; border-radius: 99px; box-shadow: var(--shadow-sm); font-weight: 700; color: var(--primary); font-size: 14px;">
          <i class="fa-solid fa-shield-halved"></i> 100% BMDC Verified Doctors
        </div>
      </div>

      <div>
        <div class="section-subtitle">Why simple is better</div>
        <h2 class="section-title" style="margin-bottom: 32px;">Smart, transparent & affordable care.</h2>

        <div class="why-feature-list">
          <div class="why-feature-item">
            <div class="feature-icon-circle">
              <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div>
              <h3>Always Real-Time & Live</h3>
              <p>Skip the crowded waiting lounge. Track doctor progress live on the app and step into the chamber right when your number is announced.</p>
            </div>
          </div>

          <div class="why-feature-item">
            <div class="feature-icon-circle" style="background: #ECFDF5; color: #10B981;">
              <i class="fa-solid fa-bangladeshi-taka-sign"></i>
            </div>
            <div>
              <h3>A Price That Doesn't Hurt</h3>
              <p>Transparent consultation fees (৳300 - ৳1200) with follow-up validity. No hidden broker charges or chamber commissions.</p>
            </div>
          </div>

          <div class="why-feature-item">
            <div class="feature-icon-circle" style="background: #FFFBEB; color: #D97706;">
              <i class="fa-solid fa-receipt"></i>
            </div>
            <div>
              <h3>Digital Prescriptions & Treatment Plans</h3>
              <p>Get personalized treatment records, medicine dosage guides, and verifiable QR codes stored permanently in your MEDIQ profile.</p>
            </div>
          </div>

          <div class="why-feature-item">
            <div class="feature-icon-circle" style="background: #F5F3FF; color: #7C3AED;">
              <i class="fa-solid fa-bell"></i>
            </div>
            <div>
              <h3>Smart SMS & Push Notifications</h3>
              <p>Receive automated reminders 30 minutes before your turn, and instant updates if your doctor gets delayed in traffic or emergency surgery.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Step by Step Guide -->
  <section class="steps-section" id="how-it-works">
    <div class="container">
      <div class="section-head">
        <div class="section-subtitle">A step by step guide</div>
        <h2 class="section-title">How MEDIQ Works in 4 Simple Steps</h2>
        <p class="section-desc">Experience seamless medical appointments without unnecessary waiting room anxiety.</p>
      </div>

      <div class="steps-grid">
        <div class="step-card">
          <div class="step-badge">1</div>
          <h4>Find Doctor</h4>
          <p>Search by specialty (Medicine, Gynecology, Pediatrics, Cardiology) or chamber area in your city.</p>
        </div>

        <div class="step-card">
          <div class="step-badge">2</div>
          <h4>Book Serial & Token</h4>
          <p>Pick your preferred date and receive an instant digital token number (e.g. Token #09).</p>
        </div>

        <div class="step-card">
          <div class="step-badge">3</div>
          <h4>Track Live from Home</h4>
          <p>See currently calling token numbers live on your phone. Head to the chamber right on time.</p>
        </div>

        <div class="step-card">
          <div class="step-badge">4</div>
          <h4>Consult & Digital Rx</h4>
          <p>Meet the doctor immediately with zero wait, and receive your digital prescription on the app.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Inside MEDIQ App Screens Showcase -->
  <section class="screens-section" id="inside-app">
    <div class="container">
      <div class="section-head">
        <div class="section-subtitle">Inside MEDIQ</div>
        <h2 class="section-title">One Unified App for Patients & Doctors</h2>
        <p class="section-desc">Engineered with Flutter for lightning-fast responsiveness on both Android and iOS.</p>
      </div>

      <div class="app-preview-cards">
        <!-- Card 1: Patient Discovery & Booking -->
        <div class="preview-card">
          <div>
            <span class="preview-tag tag-blue">Patient App</span>
            <h3>Search & Instant Booking</h3>
            <p>Smart filters for 20+ specialties, verified BMDC doctor profiles, real consultation fees, and family member serial management.</p>
          </div>
          <ul class="preview-feature-bullets">
            <li><i class="fa-solid fa-circle-check"></i> Specialty & area filtration</li>
            <li><i class="fa-solid fa-circle-check"></i> 7-day advance booking slots</li>
            <li><i class="fa-solid fa-circle-check"></i> Pessimistic lock prevents overbooking</li>
          </ul>
        </div>

        <!-- Card 2: Live Queue Engine -->
        <div class="preview-card">
          <div>
            <span class="preview-tag tag-green">Live Chamber Queue</span>
            <h3>Real-Time Live Tracker</h3>
            <p>Direct live sync with the doctor's consultation room. Dynamic wait time calculation and live status indicators.</p>
          </div>
          <ul class="preview-feature-bullets">
            <li><i class="fa-solid fa-circle-check"></i> Calling token notification alerts</li>
            <li><i class="fa-solid fa-circle-check"></i> Estimated arrival countdown</li>
            <li><i class="fa-solid fa-circle-check"></i> Emergency delay broadcasts</li>
          </ul>
        </div>

        <!-- Card 3: Doctor Console -->
        <div class="preview-card">
          <div>
            <span class="preview-tag tag-purple">Doctor Console</span>
            <h3>Chamber Calling Console</h3>
            <p>Doctor and assistant console to call next patient, start consultation, manage daily capacity, and prescribe digitally.</p>
          </div>
          <ul class="preview-feature-bullets">
            <li><i class="fa-solid fa-circle-check"></i> 1-Tap 'Call Next' patient</li>
            <li><i class="fa-solid fa-circle-check"></i> Rapid ON / PAUSED / OFF switch</li>
            <li><i class="fa-solid fa-circle-check"></i> Instant digital prescription builder</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- Download Call-to-Action Banner -->
  <section class="download-cta-section" id="download">
    <div class="container">
      <div class="cta-card">
        <div>
          <h2 class="cta-title">Ready to give MEDIQ a try?</h2>
          <p class="cta-desc">
            Download Bangladesh's smartest healthcare application today. Take control of your time, book serials with verified doctors, and never sit in a hospital waiting room again.
          </p>

          <div class="download-group" style="margin-bottom: 0;">
            <!-- Direct APK Button -->
            <a href="downloads/mediq-latest.apk" download class="apk-btn" style="padding: 14px 26px;">
              <i class="fa-brands fa-android"></i>
              <div class="store-btn-text">
                <small>Direct APK Download</small>
                <strong>Install for Android (v1.0)</strong>
              </div>
            </a>

            <!-- Google Play Store -->
            <a href="#download" class="store-btn">
              <i class="fa-brands fa-google-play"></i>
              <div class="store-btn-text">
                <small>Available soon on</small>
                <strong>Google Play</strong>
              </div>
            </a>

            <!-- Apple App Store -->
            <a href="#download" class="store-btn">
              <i class="fa-brands fa-apple"></i>
              <div class="store-btn-text">
                <small>Available soon on</small>
                <strong>Apple App Store</strong>
              </div>
            </a>
          </div>
        </div>

        <!-- Right Side Mockup Preview in CTA -->
        <div style="text-align: center;">
          <div style="display: inline-block; background: rgba(255,255,255,0.06); padding: 24px; border-radius: 28px; border: 1px solid rgba(255,255,255,0.1);">
            <div style="font-size: 52px; color: #10B981; margin-bottom: 12px;">
              <i class="fa-solid fa-mobile-screen-button"></i>
            </div>
            <h4 style="font-size: 18px; font-weight: 800; margin-bottom: 6px;">Zero Setup Required</h4>
            <p style="font-size: 13px; color: #94A3B8; max-width: 220px;">
              Install the APK directly onto your Android device or test via web portals.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Web Portals Quick Access Section -->
  <section class="portals-section" id="portals">
    <div class="container">
      <div style="text-align: center; margin-bottom: 24px;">
        <h3 style="font-size: 22px; font-weight: 800; color: var(--text-main);">Hospital & Platform Portals</h3>
        <p style="font-size: 14px; color: var(--text-muted);">Access specialized web consoles for doctors and platform administrators</p>
      </div>

      <div class="portals-grid">
        <a href="doctor_web/public/login.php" class="portal-box">
          <div>
            <h4>Doctor Web Console</h4>
            <p>Live Chamber Calling & Queue Manager</p>
          </div>
          <div class="portal-arrow">
            <i class="fa-solid fa-arrow-right"></i>
          </div>
        </a>

        <a href="admin_web/public/login.php" class="portal-box">
          <div>
            <h4>Platform Admin Portal</h4>
            <p>Doctor Verification & Platform Governance</p>
          </div>
          <div class="portal-arrow">
            <i class="fa-solid fa-arrow-right"></i>
          </div>
        </a>

        <a href="backend/public/index.php" class="portal-box">
          <div>
            <h4>REST API v1 Engine</h4>
            <p>API Health Check & Documentation</p>
          </div>
          <div class="portal-arrow">
            <i class="fa-solid fa-arrow-right"></i>
          </div>
        </a>
      </div>
    </div>
  </section>

  <!-- Modern Footer -->
  <footer>
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <a href="#" class="brand-logo">
            <div class="logo-icon" style="width: 36px; height: 36px; font-size: 18px;">
              <i class="fa-solid fa-heart-pulse"></i>
            </div>
            <span class="logo-text" style="font-size: 22px;">MEDIQ</span>
          </a>
          <p>
            Revolutionizing chamber consultations and patient queue management across Bangladesh with smart digital technology.
          </p>
        </div>

        <div class="footer-col">
          <h5>For Patients</h5>
          <ul>
            <li><a href="#how-it-works">Find Doctors</a></li>
            <li><a href="#how-it-works">Book Serial</a></li>
            <li><a href="#inside-app">Live Queue Tracker</a></li>
            <li><a href="#download">Download APK</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h5>For Doctors</h5>
          <ul>
            <li><a href="doctor_web/public/login.php">Doctor Console</a></li>
            <li><a href="admin_web/public/login.php">BMDC Verification</a></li>
            <li><a href="#why-mediq">Chamber Timings</a></li>
            <li><a href="#inside-app">Prescription Builder</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h5>Platform & Legal</h5>
          <ul>
            <li><a href="#">Privacy Policy</a></li>
            <li><a href="#">Terms of Service</a></li>
            <li><a href="#">Help Center & Hotline</a></li>
            <li><a href="backend/public/index.php">Developer API</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <div>&copy; <?php echo date('Y'); ?> MEDIQ Platform. All rights reserved.</div>
        <div style="display: flex; gap: 16px;">
          <a href="#" style="color: var(--text-muted);"><i class="fa-brands fa-facebook"></i></a>
          <a href="#" style="color: var(--text-muted);"><i class="fa-brands fa-youtube"></i></a>
          <a href="#" style="color: var(--text-muted);"><i class="fa-brands fa-linkedin"></i></a>
        </div>
      </div>
    </div>
  </footer>
</body>
</html>
