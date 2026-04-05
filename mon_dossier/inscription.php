<?php
require_once __DIR__ . '/../config/init.php';
if (isLoggedIn()) { header('Location: '.BASE_URL.'/views/dashboard.php'); exit; }

// Vérifier si un admin existe déjà
try {
    $pdo = getDB();
    $adminExists = (int)$pdo->query("SELECT COUNT(*) FROM user u JOIN roles r ON u.id_role=r.id_role WHERE r.nom_role='Administrateur'")->fetchColumn() > 0;
    if ($adminExists) {
        $_SESSION['login_error'] = 'Un administrateur existe déjà. Connectez-vous.';
        header('Location: '.BASE_URL.'/views/login.php'); exit;
    }
} catch (Exception $e) { $adminExists = false; }

$error = $_SESSION['reg_error'] ?? null; unset($_SESSION['reg_error']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Inscription Admin — GestDoc LBB</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Outfit',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#eef2f7;padding:20px}
    .card{display:flex;max-width:880px;width:100%;border-radius:20px;overflow:hidden;box-shadow:0 28px 72px rgba(0,0,0,.17)}
    .left{flex:1;background:linear-gradient(155deg,#06101e 0%,#0f172a 45%,#1b4a2e 100%);padding:52px 44px;display:flex;flex-direction:column;justify-content:center;align-items:center;position:relative;overflow:hidden}
    .left::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);background-size:40px 40px}
    .lz{position:relative;z-index:1;width:100%;text-align:center}
    .emblem{width:88px;height:88px;border-radius:50%;background:rgba(255,255,255,.12);border:2px solid rgba(255,255,255,.22);display:flex;align-items:center;justify-content:center;font-size:2.2rem;margin:0 auto 22px}
    h1{font-size:1.6rem;font-weight:800;color:#fff;line-height:1.25;margin-bottom:10px}
    .tagline{font-size:.84rem;color:rgba(255,255,255,.6);line-height:1.7;max-width:280px;margin:0 auto 32px}
    .info-box{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:16px 18px;text-align:left}
    .info-box p{font-size:.82rem;color:rgba(255,255,255,.8);line-height:1.7}
    .info-box i{color:#22c55e;margin-right:7px}
    .right{width:440px;flex-shrink:0;background:#fff;padding:48px 44px;display:flex;align-items:center;justify-content:center}
    .form-wrap{width:100%}
    .eyebrow{font-size:.68rem;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:#16a34a;margin-bottom:8px}
    h2{font-size:1.55rem;font-weight:800;color:#0f172a;letter-spacing:-.3px;margin-bottom:5px}
    .sub{font-size:.84rem;color:#94a3b8;line-height:1.6;margin-bottom:26px}
    .alert{display:flex;align-items:flex-start;gap:9px;padding:11px 14px;border-radius:9px;font-size:.82rem;margin-bottom:16px}
    .alert-err{background:#fee2e2;color:#dc2626;border-left:3px solid #ef4444}
    .field{margin-bottom:15px}
    .field label{display:flex;align-items:center;gap:6px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#475569;margin-bottom:6px}
    .field label i{color:#94a3b8;font-size:.7rem}
    .field input{width:100%;padding:12px 14px;border:2px solid #e2e8f0;border-radius:9px;font-size:.9rem;font-family:'Outfit',sans-serif;color:#1e293b;background:#f8fafc;outline:none;transition:border .2s,box-shadow .2s}
    .field input:focus{border-color:#16a34a;box-shadow:0 0 0 4px rgba(22,163,74,.1);background:#fff}
    .field input::placeholder{color:#c0cbe0}
    .notice{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 14px;font-size:.8rem;color:#15803d;margin-bottom:16px;display:flex;gap:8px;align-items:flex-start}
    .btn-sub{width:100%;padding:14px;background:linear-gradient(135deg,#064e3b,#16a34a);border:none;border-radius:9px;color:#fff;font-size:.93rem;font-weight:700;font-family:'Outfit',sans-serif;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:9px;box-shadow:0 6px 18px rgba(6,78,59,.3);transition:transform .2s,box-shadow .2s;margin-top:4px}
    .btn-sub:hover{transform:translateY(-2px);box-shadow:0 10px 26px rgba(6,78,59,.4)}
    .btn-sub:disabled{opacity:.7;cursor:not-allowed;transform:none}
    .login-link{text-align:center;margin-top:20px;font-size:.83rem;color:#64748b}
    .login-link a{color:#0f172a;font-weight:700}
    .login-link a:hover{color:#16a34a}
    .footer-note{margin-top:24px;text-align:center;font-size:.7rem;color:#c8d4e0}
    @media(max-width:700px){.card{flex-direction:column;border-radius:0}.left{padding:32px 28px}.right{width:100%;padding:36px 28px}}
    </style>
</head>
<body>
<div class="card">
    <div class="left">
        <div class="lz">
            <div class="emblem">🛡️</div>
            <h1>Créer le compte<br>Administrateur</h1>
            <p class="tagline">Le proviseur est le super-administrateur du système. Il gèrera tous les autres utilisateurs.</p>
            <div class="info-box">
                <p><i class="fas fa-check-circle"></i>Un seul compte admin autorisé</p>
                <p><i class="fas fa-check-circle"></i>Vous créez ensuite tous les autres comptes</p>
                <p><i class="fas fa-check-circle"></i>Mot de passe minimum 8 caractères</p>
                <p><i class="fas fa-check-circle"></i>Accès complet à toutes les fonctionnalités</p>
            </div>
        </div>
    </div>
    <div class="right">
        <div class="form-wrap">
            <div class="eyebrow">Inscription Admin</div>
            <h2>Compte Proviseur ✍️</h2>
            <p class="sub">Remplissez ce formulaire pour créer votre accès administrateur</p>

            <?php if ($error): ?><div class="alert alert-err"><i class="fas fa-exclamation-circle"></i><span><?= htmlspecialchars($error) ?></span></div><?php endif; ?>

            <div class="notice">
                <i class="fas fa-info-circle" style="flex-shrink:0;margin-top:1px"></i>
                <span>Après inscription, vous serez redirigé vers la connexion. Vous pourrez ensuite ajouter les autres utilisateurs depuis votre tableau de bord.</span>
            </div>

            <form method="POST" action="<?= BASE_URL ?>/controllers/AuthController.php?action=register" id="regForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="field">
                    <label><i class="fas fa-user"></i> Nom complet</label>
                    <input type="text" name="nom" placeholder="Prénom et Nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
                </div>
                <div class="field">
                    <label><i class="fas fa-envelope"></i> Adresse email</label>
                    <input type="email" name="email" placeholder="proviseur@lycee-bonaberi.cm" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="field">
                    <label><i class="fas fa-lock"></i> Mot de passe</label>
                    <input type="password" name="password" placeholder="Minimum 8 caractères" required minlength="8">
                </div>
                <div class="field">
                    <label><i class="fas fa-lock"></i> Confirmer le mot de passe</label>
                    <input type="password" name="confirm" placeholder="Répéter le mot de passe" required>
                </div>
                <button type="submit" class="btn-sub" id="regBtn">
                    <i class="fas fa-user-shield" id="regI"></i>
                    <span id="regT">Créer mon compte administrateur</span>
                </button>
            </form>
            <div class="login-link">Déjà inscrit ? <a href="<?= BASE_URL ?>/views/login.php">Se connecter</a></div>
            <p class="footer-note">&copy; <?= date('Y') ?> Lycée Bilingue de Bonaberi — GestDoc</p>
        </div>
    </div>
</div>
<script>
document.getElementById('regForm').addEventListener('submit',function(e){
    const p=this.querySelector('[name=password]').value,c=this.querySelector('[name=confirm]').value;
    if(p!==c){e.preventDefault();alert('Les mots de passe ne correspondent pas.');return}
    document.getElementById('regBtn').disabled=true;
    document.getElementById('regI').className='fas fa-spinner fa-spin';
    document.getElementById('regT').textContent='Création en cours…';
});
</script>
</body>
</html>
