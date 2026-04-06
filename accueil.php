<?php
require_once __DIR__ . '/config/init.php';
if (isLoggedIn()) { header('Location: '.BASE_URL.'/views/dashboard.php'); exit; }
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="Plateforme de gestion documentaire avec signature numérique — Lycée Bilingue de Bonaberi">
<title>GestDoc — Lycée Bilingue de Bonaberi</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Lora:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#0b1340;--blue:#3b82f6;--gold:#f59e0b;--white:#fff;--off:#f8fafc;--muted:#64748b;--r:12px;--sans:'Outfit',sans-serif;--serif:'Lora',serif}
html{scroll-behavior:smooth}
body{font-family:var(--sans);color:var(--navy);background:#fff;overflow-x:hidden}
a{text-decoration:none;color:inherit}
.container{max-width:1160px;margin:0 auto;padding:0 28px}
.reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease}
.reveal.vis{opacity:1;transform:none}
.rd1{transition-delay:.1s}.rd2{transition-delay:.2s}.rd3{transition-delay:.3s}.rd4{transition-delay:.4s}

/* NAVBAR */
#nb{position:fixed;top:0;left:0;right:0;z-index:1000;height:68px;transition:background .3s,box-shadow .3s}
#nb.scrolled{background:rgba(11,19,64,.97);backdrop-filter:blur(14px);box-shadow:0 2px 20px rgba(0,0,0,.25)}
.nb-inner{height:68px;display:flex;align-items:center;justify-content:space-between;gap:20px}
.nb-logo{display:flex;align-items:center;gap:11px;flex-shrink:0}
.nb-logo-ic{width:40px;height:40px;border-radius:9px;background:linear-gradient(135deg,var(--gold),#fbbf24);display:flex;align-items:center;justify-content:center;font-size:1rem;color:var(--navy)}
.nb-logo strong{display:block;color:#fff;font-size:.9rem;font-weight:700}
.nb-logo span{display:block;color:rgba(255,255,255,.5);font-size:.66rem}
.nb-links{display:flex;gap:4px;list-style:none;flex:1;justify-content:center}
.nb-links a{padding:8px 14px;border-radius:8px;font-size:.86rem;font-weight:500;color:rgba(255,255,255,.75);transition:all .2s}
.nb-links a:hover{background:rgba(255,255,255,.1);color:#fff}
.nb-acts{display:flex;gap:10px;flex-shrink:0}
.nb-btn-lo{padding:9px 20px;border-radius:8px;font-size:.84rem;font-weight:600;color:rgba(255,255,255,.85);border:1.5px solid rgba(255,255,255,.28);background:transparent;cursor:pointer;font-family:var(--sans);transition:all .2s}
.nb-btn-lo:hover{background:rgba(255,255,255,.1);color:#fff}
.nb-btn-reg{padding:9px 20px;border-radius:8px;font-size:.84rem;font-weight:700;background:var(--gold);color:var(--navy);border:none;cursor:pointer;font-family:var(--sans);box-shadow:0 4px 12px rgba(245,158,11,.35);transition:all .2s}
.nb-btn-reg:hover{background:#d97706;transform:translateY(-1px)}
.ham{display:none;flex-direction:column;gap:5px;background:none;border:none;cursor:pointer;padding:6px}
.ham span{display:block;width:23px;height:2px;background:#fff;border-radius:2px;transition:all .25s}
.ham.open span:nth-child(1){transform:translateY(7px) rotate(45deg)}
.ham.open span:nth-child(2){opacity:0}
.ham.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}
.mob-menu{display:none;position:fixed;top:68px;left:0;right:0;background:rgba(11,19,64,.98);backdrop-filter:blur(14px);padding:18px 24px 24px;z-index:999;border-top:1px solid rgba(255,255,255,.08)}
.mob-menu.open{display:block}
.mob-menu ul{list-style:none;margin-bottom:18px}
.mob-menu ul li{border-bottom:1px solid rgba(255,255,255,.06)}
.mob-menu ul li a{display:block;padding:13px 0;color:rgba(255,255,255,.8);font-size:.93rem;font-weight:500}
.mob-menu ul li a:hover{color:var(--gold)}
.mob-btns{display:flex;gap:10px}
.mob-btns a{flex:1;text-align:center;padding:12px;border-radius:9px;font-weight:600;font-size:.86rem}
.mob-btn-lo{background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.2)}
.mob-btn-reg{background:var(--gold);color:var(--navy)}

/* HERO */
.hero{min-height:100vh;background:linear-gradient(160deg,#060f25 0%,#0b1340 45%,#162060 100%);display:flex;align-items:center;padding-top:68px;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);background-size:44px 44px}
.hero-glow{position:absolute;border-radius:50%;filter:blur(80px);pointer-events:none}
.hero-glow.g1{width:500px;height:400px;background:rgba(245,158,11,.1);top:5%;right:0}
.hero-glow.g2{width:400px;height:300px;background:rgba(59,130,246,.08);bottom:5%;left:0}
.hero-grid{display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;position:relative;z-index:1;padding:80px 0 60px}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.3);color:var(--gold);padding:6px 14px;border-radius:30px;font-size:.74rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:22px;animation:fup .6s ease .1s both}
@keyframes fup{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
.hero-title{font-family:var(--serif);font-size:clamp(2.2rem,4.5vw,3.4rem);font-weight:700;color:#fff;line-height:1.15;margin-bottom:18px;animation:fup .6s ease .2s both}
.hero-title em{font-style:italic;color:var(--gold)}
.hero-desc{font-size:1rem;color:rgba(255,255,255,.68);line-height:1.8;margin-bottom:32px;max-width:460px;animation:fup .6s ease .3s both}
.hero-acts{display:flex;gap:12px;flex-wrap:wrap;animation:fup .6s ease .4s both}
.btn-gold{display:inline-flex;align-items:center;gap:8px;padding:13px 26px;border-radius:9px;background:var(--gold);color:var(--navy);font-weight:700;font-size:.9rem;box-shadow:0 6px 18px rgba(245,158,11,.35);transition:all .2s}
.btn-gold:hover{background:#d97706;transform:translateY(-2px);box-shadow:0 10px 26px rgba(245,158,11,.45)}
.btn-outline-w{display:inline-flex;align-items:center;gap:8px;padding:13px 26px;border-radius:9px;border:1.5px solid rgba(255,255,255,.4);color:#fff;font-weight:600;font-size:.9rem;transition:all .2s}
.btn-outline-w:hover{background:rgba(255,255,255,.1);border-color:#fff}
.hero-stats{display:flex;gap:32px;margin-top:44px;animation:fup .6s ease .5s both}
.stat-n{font-family:var(--serif);font-size:1.9rem;font-weight:700;color:#fff;line-height:1}
.stat-n span{color:var(--gold)}
.stat-l{font-size:.76rem;color:rgba(255,255,255,.5);margin-top:3px}
.stat-div{width:1px;background:rgba(255,255,255,.12)}

/* Doc preview card */
.hero-right{animation:fup .7s ease .3s both}
.doc-card{background:#fff;border-radius:16px;box-shadow:0 28px 72px rgba(0,0,0,.4);overflow:hidden;max-width:420px;position:relative}
.doc-card-head{background:linear-gradient(135deg,#0b1340,#1e40af);padding:18px 22px;display:flex;align-items:center;justify-content:space-between}
.doc-card-icon{width:38px;height:38px;background:rgba(255,255,255,.12);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:.95rem}
.doc-card-title{color:#fff;font-size:.85rem;font-weight:700}
.doc-card-meta{color:rgba(255,255,255,.5);font-size:.7rem;margin-top:2px}
.signed-badge{background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.3);padding:4px 10px;border-radius:20px;font-size:.7rem;font-weight:700;display:flex;align-items:center;gap:5px}
.signed-badge::before{content:'';width:5px;height:5px;border-radius:50%;background:#22c55e}
.doc-card-body{padding:20px 22px}
.doc-fl{margin-bottom:12px}
.doc-fl-label{font-size:.67rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px}
.doc-fl-val{font-size:.84rem;color:#1e293b;font-weight:500}
.doc-fl-val.blue{color:#1d4ed8}
.doc-sig{background:#f8fafc;border-radius:9px;padding:12px 14px;display:flex;align-items:center;gap:12px;margin-top:4px}
.sig-pad{flex:1;height:48px;background:#fff;border:1.5px dashed #c8d4e0;border-radius:7px;display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative}
.sig-line{position:absolute;width:80%;height:2px;background:linear-gradient(90deg,transparent,#1e3a8a 20%,#1e3a8a 80%,transparent);top:50%;transform:translateY(-50%);animation:sigAnim 1.5s ease-in-out infinite alternate}
@keyframes sigAnim{from{opacity:.4;transform:translateY(-50%) scaleX(.6)}to{opacity:1;transform:translateY(-50%) scaleX(1)}}
.sig-info{}
.sig-name{font-size:.8rem;font-weight:700;color:#1e293b}
.sig-date{font-size:.68rem;color:#94a3b8;margin-top:2px}
.sig-ver{font-size:.68rem;color:#22c55e;font-weight:600;display:flex;align-items:center;gap:4px;margin-top:2px}
.doc-card-foot{padding:12px 22px;background:#f8fafc;border-top:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between}
.doc-card-foot-txt{font-size:.73rem;color:#94a3b8}
.doc-share-btns{display:flex;gap:6px}
.doc-share-btn{width:28px;height:28px;border-radius:7px;background:#fff;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:.72rem;cursor:pointer;transition:all .2s}
.doc-share-btn:hover{background:#0b1340;color:#fff}
.float-b{position:absolute;background:#fff;border-radius:10px;padding:9px 12px;box-shadow:0 8px 24px rgba(0,0,0,.2);display:flex;align-items:center;gap:9px;font-size:.75rem;font-weight:600;color:#1e293b;animation:fl 3s ease-in-out infinite}
@keyframes fl{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
.float-b.b1{top:-16px;left:-18px;animation-delay:0s}
.float-b.b2{bottom:28px;right:-18px;animation-delay:1.5s}
.float-ic{width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.82rem;flex-shrink:0}

/* SECTIONS */
.section{padding:92px 0}
.sec-tag{display:inline-flex;align-items:center;gap:8px;font-size:.7rem;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:var(--blue);margin-bottom:12px}
.sec-tag::before{content:'';width:22px;height:2px;background:var(--blue);border-radius:2px}
.sec-title{font-family:var(--serif);font-size:clamp(1.8rem,3vw,2.6rem);font-weight:700;color:var(--navy);line-height:1.2}
.sec-sub{font-size:.95rem;color:var(--muted);line-height:1.75;margin-top:12px;max-width:560px}

/* FEATURES */
.feats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
.feat-card{background:var(--off);border:1px solid #e2e8f0;border-radius:14px;padding:28px 24px;position:relative;overflow:hidden;transition:all .3s;cursor:default}
.feat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--navy),var(--blue));transform:scaleX(0);transform-origin:left;transition:transform .3s}
.feat-card:hover{background:#fff;box-shadow:0 8px 32px rgba(0,0,0,.12);transform:translateY(-4px);border-color:transparent}
.feat-card:hover::before{transform:scaleX(1)}
.feat-num{position:absolute;top:18px;right:20px;font-family:var(--serif);font-size:3.5rem;font-weight:800;color:rgba(11,19,64,.05);line-height:1}
.feat-ic{width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;margin-bottom:18px}
.feat-card h3{font-size:1rem;font-weight:700;color:var(--navy);margin-bottom:8px}
.feat-card p{font-size:.84rem;color:var(--muted);line-height:1.7}

/* STEPS */
.steps-section{padding:92px 0;background:var(--navy);position:relative;overflow:hidden}
.steps-section::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);background-size:44px 44px}
.steps-inner{position:relative;z-index:1}
.steps-track{display:flex;gap:0;position:relative}
.steps-track::before{content:'';position:absolute;top:35px;left:8%;right:8%;height:2px;background:linear-gradient(90deg,var(--gold),var(--blue));opacity:.3}
.step{flex:1;text-align:center;padding:0 14px;position:relative}
.step-n{width:70px;height:70px;border-radius:50%;background:rgba(255,255,255,.06);border:2px solid rgba(255,255,255,.14);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:1.3rem;position:relative;z-index:1;transition:all .3s}
.step:hover .step-n{background:var(--gold);border-color:var(--gold);box-shadow:0 0 0 8px rgba(245,158,11,.15)}
.step-ord{position:absolute;top:-7px;right:calc(50% - 42px);background:var(--gold);color:var(--navy);width:20px;height:20px;border-radius:50%;font-size:.62rem;font-weight:800;display:flex;align-items:center;justify-content:center}
.step h4{font-size:.9rem;font-weight:700;color:#fff;margin-bottom:7px}
.step p{font-size:.78rem;color:rgba(255,255,255,.52);line-height:1.65}

/* ROLES */
.roles-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:18px}
.role-card{border-radius:14px;padding:26px 18px;text-align:center;position:relative;overflow:hidden;transition:all .3s;cursor:default;border:2px solid transparent}
.role-card:hover{transform:translateY(-5px);box-shadow:0 12px 36px rgba(0,0,0,.15)}
.role-ic{width:56px;height:56px;border-radius:50%;border:2px solid currentColor;display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin:0 auto 14px;position:relative;z-index:1;opacity:.85}
.role-card:hover .role-ic{opacity:1}
.role-card h4{font-size:.88rem;font-weight:700;margin-bottom:10px;position:relative;z-index:1}
.role-card ul{list-style:none;position:relative;z-index:1}
.role-card ul li{font-size:.73rem;color:var(--muted);padding:4px 0;display:flex;align-items:center;gap:5px}
.role-card ul li::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0;opacity:.6}

/* SECURITY */
.sec-layout{display:grid;grid-template-columns:1fr 1fr;gap:72px;align-items:center}
.sec-visual{background:linear-gradient(145deg,var(--navy),#1e40af);border-radius:16px;padding:40px;position:relative;overflow:hidden}
.sec-visual::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 70% 30%,rgba(255,255,255,.06) 0,transparent 55%)}
.sec-shield{width:76px;height:76px;background:rgba(255,255,255,.1);border:2px solid rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 26px;color:var(--gold);font-size:1.9rem;position:relative;z-index:1;box-shadow:0 0 0 12px rgba(255,255,255,.04),0 0 0 24px rgba(255,255,255,.02)}
.sec-items{display:flex;flex-direction:column;gap:12px;position:relative;z-index:1}
.sec-item{display:flex;align-items:center;gap:12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:11px 14px}
.sec-item-ic{width:34px;height:34px;background:rgba(245,158,11,.15);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:.85rem;flex-shrink:0}
.sec-item-t{color:#fff;font-size:.82rem;font-weight:500}
.sec-item-s{font-size:.7rem;color:rgba(255,255,255,.45);margin-top:1px}
.sec-pts{display:flex;flex-direction:column;gap:18px;margin-top:30px}
.sec-pt{display:flex;gap:14px}
.sec-pt-ic{width:40px;height:40px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0}
.sec-pt h5{font-size:.9rem;font-weight:700;color:var(--navy);margin-bottom:3px}
.sec-pt p{font-size:.81rem;color:var(--muted);line-height:1.6}

/* CTA */
.cta-section{padding:92px 0;background:linear-gradient(135deg,var(--navy),#1e3a8a);position:relative;overflow:hidden;text-align:center}
.cta-section::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 50% 50%,rgba(245,158,11,.12) 0,transparent 65%)}
.cta-inner{position:relative;z-index:1;max-width:620px;margin:0 auto}
.cta-inner h2{font-family:var(--serif);font-size:clamp(1.9rem,3.5vw,2.8rem);font-weight:700;color:#fff;line-height:1.2;margin-bottom:14px}
.cta-inner h2 em{font-style:italic;color:var(--gold)}
.cta-inner p{font-size:.95rem;color:rgba(255,255,255,.65);line-height:1.75;margin-bottom:32px}
.cta-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
.cta-note{margin-top:18px;font-size:.75rem;color:rgba(255,255,255,.38)}

/* FOOTER */
.footer{background:#040b1f;padding:68px 0 0}
.footer-top{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:44px;padding-bottom:52px;border-bottom:1px solid rgba(255,255,255,.08)}
.footer-brand p{font-size:.8rem;color:rgba(255,255,255,.42);line-height:1.75;max-width:280px;margin-top:16px}
.footer-social{display:flex;gap:8px;margin-top:20px}
.footer-social a{width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.5);font-size:.78rem;transition:all .2s}
.footer-social a:hover{background:var(--gold);color:var(--navy);border-color:transparent}
.footer-col h5{font-size:.8rem;font-weight:700;color:#fff;letter-spacing:.5px;margin-bottom:16px}
.footer-col ul{list-style:none;display:flex;flex-direction:column;gap:9px}
.footer-col ul li a{font-size:.78rem;color:rgba(255,255,255,.42);transition:color .2s}
.footer-col ul li a:hover{color:var(--gold)}
.footer-bottom{padding:20px 0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.footer-bottom p{font-size:.72rem;color:rgba(255,255,255,.28)}
.footer-bottom-r{display:flex;gap:18px}
.footer-bottom-r a{font-size:.72rem;color:rgba(255,255,255,.28);transition:color .2s}
.footer-bottom-r a:hover{color:rgba(255,255,255,.55)}

/* RESPONSIVE */
@media(max-width:1024px){.feats-grid{grid-template-columns:repeat(2,1fr)}.roles-grid{grid-template-columns:repeat(3,1fr)}.footer-top{grid-template-columns:1fr 1fr}}
@media(max-width:768px){.nb-links,.nb-acts{display:none}.ham{display:flex}.hero-grid{grid-template-columns:1fr;text-align:center;padding:60px 0 40px}.hero-acts{justify-content:center}.hero-stats{justify-content:center}.hero-right{display:none}.steps-track{flex-direction:column;gap:28px}.steps-track::before{display:none}.step{text-align:left;display:flex;gap:18px;align-items:flex-start}.step-n{margin:0;flex-shrink:0}.roles-grid{grid-template-columns:1fr 1fr}.sec-layout{grid-template-columns:1fr;gap:40px}.footer-top{grid-template-columns:1fr;gap:32px}.footer-bottom{flex-direction:column;text-align:center}.feats-grid{grid-template-columns:1fr}}
@media(max-width:480px){.roles-grid{grid-template-columns:1fr}.hero-stats{flex-direction:column;gap:14px}.stat-div{display:none}}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav id="nb">
    <div class="container">
        <div class="nb-inner">
            <a href="<?= BASE_URL ?>/" class="nb-logo">
                <div class="nb-logo-ic"><i class="fas fa-graduation-cap"></i></div>
                <div><strong>GestDoc LBB</strong><span>Lycée Bilingue de Bonaberi</span></div>
            </a>
            <ul class="nb-links">
                <li><a href="#fonctions">Fonctionnalités</a></li>
                <li><a href="#etapes">Fonctionnement</a></li>
                <li><a href="#roles">Rôles</a></li>
                <li><a href="#securite">Sécurité</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <div class="nb-acts">
                <a href="<?= BASE_URL ?>/views/login.php"><button class="nb-btn-lo"><i class="fas fa-sign-in-alt" style="margin-right:5px;font-size:.78rem"></i>Connexion</button></a>
                <a href="<?= BASE_URL ?>/views/inscription.php"><button class="nb-btn-reg"><i class="fas fa-user-plus" style="margin-right:5px;font-size:.78rem"></i>S'inscrire</button></a>
            </div>
            <button class="ham" id="ham" onclick="toggleMob()"><span></span><span></span><span></span></button>
        </div>
    </div>
</nav>
<div class="mob-menu" id="mobMenu">
    <ul>
        <li><a href="#fonctions" onclick="closeMob()">Fonctionnalités</a></li>
        <li><a href="#etapes" onclick="closeMob()">Fonctionnement</a></li>
        <li><a href="#roles" onclick="closeMob()">Rôles</a></li>
        <li><a href="#securite" onclick="closeMob()">Sécurité</a></li>
        <li><a href="#contact" onclick="closeMob()">Contact</a></li>
    </ul>
    <div class="mob-btns">
        <a href="<?= BASE_URL ?>/views/login.php" class="mob-btn-lo"><i class="fas fa-sign-in-alt" style="margin-right:6px"></i>Connexion</a>
        <a href="<?= BASE_URL ?>/views/inscription.php" class="mob-btn-reg"><i class="fas fa-user-plus" style="margin-right:6px"></i>S'inscrire</a>
    </div>
</div>

<!-- HERO -->
<section class="hero">
    <div class="hero-glow g1"></div><div class="hero-glow g2"></div>
    <div class="container">
        <div class="hero-grid">
            <div>
                <div class="hero-badge"><i class="fas fa-shield-halved"></i>Plateforme sécurisée & certifiée</div>
                <h1 class="hero-title">Gérez vos documents<br>avec une <em>signature</em><br>numérique officielle</h1>
                <p class="hero-desc">La plateforme de gestion documentaire du Lycée Bilingue de Bonaberi. Créez, signez, archivez et transmettez vos documents en toute sécurité.</p>
                <div class="hero-acts">
                    <a href="<?= BASE_URL ?>/views/inscription.php" class="btn-gold"><i class="fas fa-rocket"></i>Commencer maintenant</a>
                    <a href="#fonctions" class="btn-outline-w"><i class="fas fa-play-circle"></i>Voir les fonctionnalités</a>
                </div>
                <div class="hero-stats">
                    <div><div class="stat-n">5<span>+</span></div><div class="stat-l">Rôles d'accès</div></div>
                    <div class="stat-div"></div>
                    <div><div class="stat-n">9<span>+</span></div><div class="stat-l">Fonctionnalités</div></div>
                    <div class="stat-div"></div>
                    <div><div class="stat-n">100<span>%</span></div><div class="stat-l">Sécurisé</div></div>
                </div>
            </div>
            <div class="hero-right">
                <div style="position:relative;padding:20px">
                    <div class="float-b b1"><div class="float-ic" style="background:#e8f5e9;color:#22c55e"><i class="fas fa-check"></i></div>Document validé</div>
                    <div class="float-b b2"><div class="float-ic" style="background:#dbeafe;color:#1d4ed8"><i class="fas fa-bell"></i></div>Notification envoyée</div>
                    <div class="doc-card">
                        <div class="doc-card-head">
                            <div style="display:flex;align-items:center;gap:11px"><div class="doc-card-icon"><i class="fas fa-file-contract"></i></div><div><div class="doc-card-title">Circulaire N°14 — 2025</div><div class="doc-card-meta">Administratif • 12 mars 2025</div></div></div>
                            <div class="signed-badge">Signé</div>
                        </div>
                        <div class="doc-card-body">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                                <div class="doc-fl"><div class="doc-fl-label">Expéditeur</div><div class="doc-fl-val">Dir. Proviseur</div></div>
                                <div class="doc-fl"><div class="doc-fl-label">Destinataire</div><div class="doc-fl-val blue">Corps enseignant</div></div>
                            </div>
                            <div class="doc-fl" style="margin-bottom:14px"><div class="doc-fl-label">Objet</div><div class="doc-fl-val">Réunion pédagogique de fin de trimestre</div></div>
                            <div class="doc-sig">
                                <div class="sig-pad"><div class="sig-line"></div></div>
                                <div class="sig-info"><div class="sig-name">Dir. Proviseur</div><div class="sig-date">12/03/2025 • 09h47</div><div class="sig-ver"><i class="fas fa-check-circle"></i>Certifiée</div></div>
                            </div>
                        </div>
                        <div class="doc-card-foot">
                            <div class="doc-card-foot-txt"><i class="fas fa-lock" style="color:#22c55e;margin-right:4px"></i>Vérifiée & archivée</div>
                            <div class="doc-share-btns"><div class="doc-share-btn"><i class="fas fa-download"></i></div><div class="doc-share-btn"><i class="fas fa-print"></i></div><div class="doc-share-btn"><i class="fas fa-paper-plane"></i></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section class="section" id="fonctions">
    <div class="container">
        <div style="text-align:center;margin-bottom:56px" class="reveal">
            <div class="sec-tag">Ce que nous offrons</div>
            <h2 class="sec-title">9 fonctionnalités essentielles</h2>
            <p class="sec-sub" style="margin:12px auto 0">Une suite complète pour digitaliser et sécuriser toute la chaîne documentaire de votre établissement.</p>
        </div>
        <div class="feats-grid">
            <?php
            $feats=[
                ['fa-folder-open','#e0e7ff','#3730a3','Gestion documentaire','Créez, modifiez, supprimez et organisez vos documents administratifs et pédagogiques dans un espace centralisé selon votre rôle.'],
                ['fa-pen-nib','#dcfce7','#166534','Signature numérique','Signez directement à la souris ou sur écran tactile via Signature Pad. Chaque signature est enregistrée et liée de façon permanente au document.'],
                ['fa-paper-plane','#dbeafe','#1e40af','Envoi & transmission','Transmettez vos documents à un ou plusieurs destinataires. Notification automatique et mise à jour du statut en temps réel.'],
                ['fa-bell','#fef9c3','#a16207','Notifications dynamiques','Alertes AJAX en temps réel pour chaque action : envoi, signature, refus ou téléchargement. Badge compteur dans la navbar.'],
                ['fa-archive','#f3e8ff','#6b21a8','Archivage sécurisé','Archivez les documents traités sans les supprimer. Historique complet accessible et téléchargeable à tout moment.'],
                ['fa-camera','#cffafe','#0e7490','Capture par caméra','Photographiez votre document physique via la caméra de votre appareil grâce à l\'API getUserMedia().'],
                ['fa-file-pdf','#fee2e2','#991b1b','Génération PDF','Exportez et imprimez en PDF professionnel via DomPDF avec mise en page incluant la signature.'],
                ['fa-users-cog','#eff6ff','#1e40af','Gestion des rôles','L\'administrateur crée et gère tous les comptes, attribue les rôles, active ou désactive les accès.'],
                ['fa-chart-pie','#dcfce7','#166534','Statistiques & tableaux de bord','Visualisez l\'activité en temps réel : documents par statut, catégorie, rôle et courbes mensuelles.'],
            ];
            foreach($feats as $i=>[$ic,$bg,$cl,$t,$p]): $n=str_pad($i+1,2,'0',STR_PAD_LEFT);
            ?>
            <div class="feat-card reveal rd<?= ($i%4)+1 ?>">
                <div class="feat-num"><?= $n ?></div>
                <div class="feat-ic" style="background:<?= $bg ?>;color:<?= $cl ?>"><i class="fas <?= $ic ?>"></i></div>
                <h3><?= $t ?></h3><p><?= $p ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- STEPS -->
<section class="steps-section" id="etapes">
    <div class="container steps-inner">
        <div style="text-align:center;margin-bottom:56px" class="reveal">
            <div class="sec-tag" style="justify-content:center">Comment ça marche</div>
            <h2 class="sec-title" style="color:#fff">5 étapes simples</h2>
            <p class="sec-sub" style="color:rgba(255,255,255,.6);margin:12px auto 0">De la création à l'archivage, chaque document suit un parcours structuré et traçable.</p>
        </div>
        <div class="steps-track reveal">
            <?php $steps=[['fa-file-plus','Créer le document','Créez votre document, choisissez la catégorie et joignez un fichier ou photographiez un document physique.'],['fa-paper-plane','Envoyer & notifier','Transmettez au destinataire. Une notification automatique lui est envoyée avec le statut « Envoyé ».'],['fa-pen-nib','Signer numériquement','Le destinataire habilité appose sa signature numérique, enregistrée définitivement sur le document.'],['fa-check-double','Valider ou refuser','L\'admin valide (statut « Signé ») ou refuse avec notification automatique de l\'expéditeur.'],['fa-archive','Archiver & tracer','Le document est archivé. Tout l\'historique (qui, quand, quelle action) reste consultable.']]?>
            <?php foreach($steps as $i=>[$ic,$h,$p]): ?>
            <div class="step">
                <div class="step-n"><span class="step-ord"><?= $i+1 ?></span><i class="fas <?= $ic ?>" style="color:<?= $i%2===0?'var(--gold)':'#93c5fd' ?>;font-size:1.2rem"></i></div>
                <div><h4><?= $h ?></h4><p><?= $p ?></p></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ROLES -->
<section class="section" id="roles" style="background:var(--off)">
    <div class="container">
        <div style="text-align:center;margin-bottom:56px" class="reveal">
            <div class="sec-tag">Qui utilise la plateforme</div>
            <h2 class="sec-title">5 rôles d'utilisateurs</h2>
            <p class="sec-sub" style="margin:12px auto 0">Chaque membre dispose d'un espace personnalisé avec les droits adaptés à sa fonction.</p>
        </div>
        <div class="roles-grid">
            <?php $rls=[['Administrateur','#dbeafe','#1e40af','fa-user-shield',['Gestion complète','Tous les documents','Gérer les utilisateurs','Signature & validation','Statistiques système']],['Secrétaire','#dcfce7','#166534','fa-user-tie',['CRUD documents','Envoi & envoi groupé','Gestion catégories','Documents reçus/signés','Notifications']],['Censeur','#f3e8ff','#6b21a8','fa-chalkboard-teacher',['Docs pédagogiques','Envoi aux enseignants','Consulter & télécharger','Documents reçus','Notifications']],['Intendant','#fff7ed','#9a3412','fa-briefcase',['Docs administratifs','Archivage','Signature & refus','Gérer utilisateurs','Envoi au proviseur']],['Enseignant','#f0fdfa','#0f766e','fa-graduation-cap',['Ses propres documents','Signature','Documents reçus','Téléchargement','Notifications']]];
            foreach($rls as $i=>[$n,$bg,$cl,$ic,$perms]): ?>
            <div class="role-card reveal rd<?= ($i%4)+1 ?>" style="background:<?= $bg ?>;color:<?= $cl ?>;border-color:<?= $cl ?>22">
                <div class="role-ic"><i class="fas <?= $ic ?>"></i></div>
                <h4><?= $n ?></h4>
                <ul><?php foreach($perms as $p): ?><li><?= $p ?></li><?php endforeach; ?></ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- SECURITY -->
<section class="section" id="securite">
    <div class="container">
        <div class="sec-layout">
            <div class="reveal">
                <div class="sec-visual">
                    <div class="sec-shield"><i class="fas fa-shield-halved"></i></div>
                    <div class="sec-items">
                        <?php $sitems=[['fa-lock','Mots de passe bcrypt','Hashage sécurisé — coût 12'],['fa-key','Protection CSRF','Token unique par session'],['fa-user-check','Contrôle d\'accès par rôle','Chaque action vérifiée côté serveur'],['fa-database','Requêtes PDO préparées','Protection injection SQL']];
                        foreach($sitems as[$ic,$t,$s]): ?>
                        <div class="sec-item"><div class="sec-item-ic"><i class="fas <?= $ic ?>"></i></div><div><div class="sec-item-t"><?= $t ?></div><div class="sec-item-s"><?= $s ?></div></div></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="reveal rd2">
                <div class="sec-tag">Sécurité & fiabilité</div>
                <h2 class="sec-title">Votre établissement<br>protégé à chaque niveau</h2>
                <p class="sec-sub">La plateforme intègre les meilleures pratiques de sécurité pour garantir confidentialité, intégrité et traçabilité.</p>
                <div class="sec-pts">
                    <?php $spts=[['fa-fingerprint','#dcfce7','#166534','Authentification sécurisée','Sessions PHP sécurisées avec régénération d\'identifiant. Aucun accès sans authentification préalable.'],['fa-history','#dbeafe','#1e40af','Traçabilité complète','Chaque action est enregistrée avec l\'identité, la date et l\'heure exacte.'],['fa-signature','#fff7ed','#9a3412','Intégrité des signatures','Signatures enregistrées en PNG et associées de façon permanente et infalsifiable au document.'],['fa-code','#f3e8ff','#6b21a8','Protection XSS','Toutes les entrées sont validées côté serveur. Les sorties HTML sont systématiquement échappées.']];
                    foreach($spts as[$ic,$bg,$cl,$t,$p]): ?>
                    <div class="sec-pt"><div class="sec-pt-ic" style="background:<?= $bg ?>;color:<?= $cl ?>"><i class="fas <?= $ic ?>"></i></div><div><h5><?= $t ?></h5><p><?= $p ?></p></div></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section" id="contact">
    <div class="container">
        <div class="cta-inner reveal">
            <div class="sec-tag" style="justify-content:center;color:var(--gold)">Rejoindre la plateforme</div>
            <h2>Prêt à <em>digitaliser</em><br>votre gestion documentaire ?</h2>
            <p>Rejoignez le Lycée Bilingue de Bonaberi. L'administrateur s'inscrit en premier, puis crée tous les autres comptes.</p>
            <div class="cta-btns">
                <a href="<?= BASE_URL ?>/views/inscription.php" class="btn-gold" style="font-size:.95rem;padding:15px 30px"><i class="fas fa-user-plus"></i>Créer le compte administrateur</a>
                <a href="<?= BASE_URL ?>/views/login.php" class="btn-outline-w" style="font-size:.95rem;padding:15px 30px"><i class="fas fa-sign-in-alt"></i>Se connecter</a>
            </div>
            <p class="cta-note"><i class="fas fa-lock" style="margin-right:5px"></i>Seul le proviseur peut s'inscrire. Il crée ensuite les autres utilisateurs.</p>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-top">
            <div class="footer-brand">
                <div class="nb-logo"><div class="nb-logo-ic"><i class="fas fa-graduation-cap"></i></div><div><strong style="color:#fff;font-size:.88rem;font-weight:700">GestDoc LBB</strong><span style="display:block;color:rgba(255,255,255,.4);font-size:.64rem">Lycée Bilingue de Bonaberi</span></div></div>
                <p>Plateforme de gestion documentaire avec signature numérique sécurisée pour le Lycée Bilingue de Bonaberi.</p>
                <div class="footer-social"><a href="#"><i class="fab fa-facebook-f"></i></a><a href="#"><i class="fab fa-twitter"></i></a><a href="#"><i class="fab fa-linkedin-in"></i></a><a href="#"><i class="fas fa-envelope"></i></a></div>
            </div>
            <div class="footer-col"><h5>Plateforme</h5><ul><li><a href="#fonctions">Fonctionnalités</a></li><li><a href="#etapes">Fonctionnement</a></li><li><a href="#roles">Rôles</a></li><li><a href="#securite">Sécurité</a></li><li><a href="<?= BASE_URL ?>/views/login.php">Connexion</a></li></ul></div>
            <div class="footer-col"><h5>Documents</h5><ul><li><a href="#">Administratifs</a></li><li><a href="#">Pédagogiques</a></li><li><a href="#">Circulaires</a></li><li><a href="#">Rapports</a></li><li><a href="#">Archives</a></li></ul></div>
            <div class="footer-col"><h5>Contact</h5><ul><li><a href="#"><i class="fas fa-map-marker-alt" style="margin-right:6px"></i>Bonaberi, Douala</a></li><li><a href="#"><i class="fas fa-phone" style="margin-right:6px"></i>+237 XXX XXX XXX</a></li><li><a href="#"><i class="fas fa-envelope" style="margin-right:6px"></i>contact@lycee-bonaberi.cm</a></li><li><a href="#">Support technique</a></li></ul></div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Lycée Bilingue de Bonaberi. Tous droits réservés.</p>
            <div class="footer-bottom-r"><a href="#">Confidentialité</a><a href="#">Conditions</a><a href="#">Mentions légales</a></div>
        </div>
    </div>
</footer>

<script>
// Navbar scroll
const nb=document.getElementById('nb');
window.addEventListener('scroll',()=>nb.classList.toggle('scrolled',scrollY>40),{passive:true});
nb.classList.add('scrolled');

// Hamburger
function toggleMob(){const h=document.getElementById('ham'),m=document.getElementById('mobMenu');h.classList.toggle('open');m.classList.toggle('open');document.body.style.overflow=m.classList.contains('open')?'hidden':'';}
function closeMob(){document.getElementById('ham').classList.remove('open');document.getElementById('mobMenu').classList.remove('open');document.body.style.overflow='';}

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(a=>a.addEventListener('click',e=>{const t=document.querySelector(a.getAttribute('href'));if(t){e.preventDefault();closeMob();window.scrollTo({top:t.offsetTop-76,behavior:'smooth'});}}));

// Reveal on scroll
const ro=new IntersectionObserver(entries=>entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('vis');ro.unobserve(e.target);}}),{threshold:.1,rootMargin:'0px 0px -40px 0px'});
document.querySelectorAll('.reveal').forEach(el=>ro.observe(el));
</script>
</body>
</html>
