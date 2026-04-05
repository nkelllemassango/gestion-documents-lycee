<?php
require_once __DIR__ . '/../config/init.php';
if (isLoggedIn()) { 
    header('Location: '.BASE_URL.'/views/dashboard.php'); 
    exit; 
}
$error   = $_SESSION['login_error']   ?? null; unset($_SESSION['login_error']);
$success = $_SESSION['login_success'] ?? null; unset($_SESSION['login_success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Connexion — GestDoc LBB</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Outfit',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#eef2f7;padding:20px}
.card{display:flex;max-width:880px;width:100%;border-radius:20px;overflow:hidden;box-shadow:0 28px 72px rgba(0,0,0,.17),0 8px 20px rgba(0,0,0,.09)}
/* LEFT */
.left{flex:1;background:linear-gradient(155deg,#06101e 0%,#0f172a 45%,#1e3a5f 100%);padding:52px 44px;display:flex;flex-direction:column;justify-content:center;align-items:center;position:relative;overflow:hidden}
.left::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);background-size:40px 40px}
.left::after{content:'';position:absolute;inset:0;background:radial-gradient(circle at 20% 80%,rgba(59,130,246,.15) 0,transparent 50%),radial-gradient(circle at 80% 15%,rgba(255,255,255,.06) 0,transparent 45%)}
.lz{position:relative;z-index:1;width:100%}
.emblem{width:88px;height:88px;border-radius:50%;background:rgba(255,255,255,.1);border:2px solid rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.2rem;margin:0 auto 22px;box-shadow:0 8px 28px rgba(0,0,0,.2);position:relative}
.emblem::after{content:'';position:absolute;inset:-7px;border-radius:50%;border:1px solid rgba(255,255,255,.08)}
h1{font-size:1.7rem;font-weight:800;color:#fff;text-align:center;line-height:1.25;margin-bottom:10px;letter-spacing:-.3px}
.tagline{font-size:.84rem;color:rgba(255,255,255,.58);text-align:center;line-height:1.7;margin-bottom:36px;max-width:280px;margin-left:auto;margin-right:auto}
.feat{display:flex;flex-direction:column;gap:11px}
.feat-item{display:flex;align-items:center;gap:12px;padding:11px 14px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:10px}
.feat-ic{width:32px;height:32px;background:rgba(255,255,255,.13);border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.85rem;color:rgba(255,255,255,.9);flex-shrink:0}
.feat-item span{font-size:.81rem;color:rgba(255,255,255,.82);font-weight:500}
.dots{display:flex;gap:7px;justify-content:center;margin-top:34px}
.dot{width:6px;height:6px;border-radius:50%;background:rgba(255,255,255,.22)}
.dot.active{width:20px;border-radius:3px;background:rgba(255,255,255,.7)}
/* RIGHT */
.right{width:420px;flex-shrink:0;background:#fff;padding:52px 46px;display:flex;align-items:center;justify-content:center}
.form-wrap{width:100%}
.eyebrow{font-size:.68rem;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:#3b82f6;margin-bottom:8px}
h2{font-size:1.6rem;font-weight:800;color:#0f172a;letter-spacing:-.3px;margin-bottom:5px}
.sub{font-size:.84rem;color:#94a3b8;line-height:1.6;margin-bottom:28px}
.alert{display:flex;align-items:flex-start;gap:9px;padding:11px 14px;border-radius:9px;font-size:.82rem;font-weight:500;margin-bottom:16px;animation:si .25s ease}
@keyframes si{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
.alert-err{background:#fee2e2;color:#dc2626;border-left:3px solid #ef4444}
.alert-ok{background:#dcfce7;color:#16a34a;border-left:3px solid #22c55e}
.field{margin-bottom:17px}
.field label{display:flex;align-items:center;gap:6px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#475569;margin-bottom:7px}
.field label i{color:#94a3b8;font-size:.7rem}
.inp-wrap{position:relative}
.inp-wrap input{width:100%;padding:12px 42px 12px 14px;border:2px solid #e2e8f0;border-radius:9px;font-size:.9rem;font-family:'Outfit',sans-serif;color:#1e293b;background:#f8fafc;outline:none;transition:border .2s,box-shadow .2s}
.inp-wrap input:focus{border-color:#3b82f6;box-shadow:0 0 0 4px rgba(59,130,246,.1);background:#fff}
.inp-wrap input::placeholder{color:#c0cbe0}
.eye-btn{position:absolute;right:12px;top:50%;transform:translateY(-50%);border:none;background:none;cursor:pointer;color:#94a3b8;padding:5px;border-radius:6px;transition:color .15s;display:flex;align-items:center}
.eye-btn:hover{color:#3b82f6}
.chk-row{display:flex;align-items:center;gap:8px;margin:2px 0 22px;font-size:.82rem;color:#64748b;cursor:pointer}
.chk-row input{width:15px;height:15px;accent-color:#0f172a;cursor:pointer}
.btn-sub{width:100%;padding:14px;background:linear-gradient(135deg,#0f172a,#3b82f6);border:none;border-radius:9px;color:#fff;font-size:.93rem;font-weight:700;font-family:'Outfit',sans-serif;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:9px;box-shadow:0 6px 18px rgba(15,23,42,.3);transition:transform .2s,box-shadow .2s}
.btn-sub:hover{transform:translateY(-2px);box-shadow:0 10px 26px rgba(15,23,42,.4)}
.btn-sub:disabled{opacity:.7;cursor:not-allowed;transform:none}
.divider{display:flex;align-items:center;gap:12px;margin:20px 0;font-size:.75rem;color:#c8d4e0}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#e2e8f0}
.reg-box{padding:14px 16px;background:#f0f9ff;border:1px dashed #bae6fd;border-radius:9px;text-align:center}
.reg-box p{font-size:.79rem;color:#64748b;margin-bottom:6px}
.reg-box a{font-size:.82rem;font-weight:700;color:#0f172a;display:inline-flex;align-items:center;gap:5px}
.reg-box a:hover{color:#3b82f6}
.footer-note{margin-top:26px;text-align:center;font-size:.7rem;color:#c8d4e0}
@media(max-width:700px){.card{flex-direction:column;border-radius:0;box-shadow:none}.left{padding:36px 28px}.h1{font-size:1.4rem}.feat{display:none}.right{width:100%;padding:36px 28px}}
</style>
</head>
<body>
<div class="card">
    <div class="left">
        <div class="lz">
            <div class="emblem">🎓</div>
            <h1>Lycée Bilingue<br>de Bonaberi</h1>
            <p class="tagline">Plateforme de gestion documentaire avec signature numérique sécurisée</p>
            <div class="feat">
                <div class="feat-item"><div class="feat-ic"><i class="fas fa-pen-nib"></i></div><span>Signature numérique intégrée</span></div>
                <div class="feat-item"><div class="feat-ic"><i class="fas fa-users"></i></div><span>Gestion multi-rôles & utilisateurs</span></div>
                <div class="feat-item"><div class="feat-ic"><i class="fas fa-bell"></i></div><span>Notifications en temps réel</span></div>
                <div class="feat-item"><div class="feat-ic"><i class="fas fa-archive"></i></div><span>Archivage & traçabilité complète</span></div>
            </div>
            <div class="dots"><div class="dot active"></div><div class="dot"></div><div class="dot"></div></div>
        </div>
    </div>
    <div class="right">
        <div class="form-wrap">
            <div class="eyebrow">Espace sécurisé</div>
            <h2>Bienvenue 👋</h2>
            <p class="sub">Connectez-vous à votre espace de travail</p>

            <?php if ($error): ?><div class="alert alert-err"><i class="fas fa-exclamation-circle"></i><span><?= htmlspecialchars($error) ?></span></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-ok"><i class="fas fa-check-circle"></i><span><?= htmlspecialchars($success) ?></span></div><?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/controllers/AuthController.php?action=login" id="loginForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div class="field">
                    <label><i class="fas fa-envelope"></i> Adresse email</label>
                    <div class="inp-wrap">
                        <input type="email" name="email" placeholder="nom@lycee-bonaberi.cm"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email" required>
                    </div>
                </div>
                <div class="field">
                    <label><i class="fas fa-lock"></i> Mot de passe</label>
                    <div class="inp-wrap">
                        <input type="password" name="password" id="pwd" placeholder="Votre mot de passe" autocomplete="current-password" required>
                        <button type="button" class="eye-btn" onclick="tglPwd()" title="Afficher/masquer">
                            <i class="fas fa-eye" id="eyeI"></i>
                        </button>
                    </div>
                </div>
                <label class="chk-row"><input type="checkbox" name="remember"> Se souvenir de moi</label>
                <button type="submit" class="btn-sub" id="loginBtn">
                    <i class="fas fa-sign-in-alt" id="btnI"></i>
                    <span id="btnT">Se connecter</span>
                </button>
            </form>

            <div class="divider">ou</div>
            <div class="reg-box">
                <p>Vous êtes le proviseur ? Première connexion ?</p>
                <a href="<?= BASE_URL ?>/views/inscription.php"><i class="fas fa-user-shield"></i> Créer le compte administrateur</a>
            </div>
            <p class="footer-note">&copy; <?= date('Y') ?> Lycée Bilingue de Bonaberi — GestDoc</p>
        </div>
    </div>
</div>
<script>
function tglPwd(){const i=document.getElementById('pwd'),ic=document.getElementById('eyeI');i.type=i.type==='password'?'text':'password';ic.classList.toggle('fa-eye');ic.classList.toggle('fa-eye-slash')}
document.getElementById('loginForm').addEventListener('submit',function(){const b=document.getElementById('loginBtn');b.disabled=true;document.getElementById('btnI').className='fas fa-spinner fa-spin';document.getElementById('btnT').textContent='Connexion…'});
setTimeout(()=>document.querySelectorAll('.alert').forEach(a=>{a.style.transition='opacity .5s';a.style.opacity='0';setTimeout(()=>a.remove(),500)}),5000);
</script>
</body>
</html>
