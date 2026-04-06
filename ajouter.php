<?php
require_once __DIR__ . '/../../config/init.php';
requireLogin();
if (!in_array(getUserRole(),['Administrateur','Intendant'])) { header('Location: '.BASE_URL.'/views/dashboard.php'); exit; }
$user   = getUser();
$css    = roleCss($user['nom_role']);
$pdo    = getDB();
$roles  = $pdo->query("SELECT * FROM roles ORDER BY nom_role")->fetchAll();
$unread = getUnreadCount($user['id_user']);
$err    = $_SESSION['usr_err']??null; unset($_SESSION['usr_err']);
?><!DOCTYPE html>
<html lang="fr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ajouter un utilisateur</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<script>const BASE_URL='<?= BASE_URL ?>';</script>
</head><body class="<?= $css ?>">
<?php include ROOT_PATH.'/views/partials/sidebar.php'; include ROOT_PATH.'/views/partials/navbar.php'; ?>
<main class="app-main">
    <div class="page-header">
        <div>
            <div class="page-title"><i class="fas fa-user-plus"></i> Ajouter un utilisateur</div>
            <div class="page-sub">Créer un nouveau compte utilisateur</div>
        </div>
        <a href="<?= BASE_URL ?>/views/utilisateurs/index.php" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>

    <?php if ($err): ?><div class="alert alert-error"><i class="fas fa-times-circle"></i><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div style="max-width:640px">
        <div class="data-card">
            <div class="data-card-head"><div class="data-card-title"><i class="fas fa-id-card"></i> Informations du compte</div></div>
            <div class="data-card-body">
                <form method="POST" action="<?= BASE_URL ?>/controllers/UserController.php?action=ajouter" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <div class="form-grid">
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label"><i class="fas fa-user"></i> Nom complet *</label>
                            <input type="text" name="nom" class="form-control" placeholder="Prénom et Nom" value="<?= htmlspecialchars($_POST['nom']??'') ?>" required>
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label"><i class="fas fa-envelope"></i> Adresse email *</label>
                            <input type="email" name="email" class="form-control" placeholder="nom@lycee-bonaberi.cm" value="<?= htmlspecialchars($_POST['email']??'') ?>" required>
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label">
                                <i class="fas fa-mobile-alt"></i> Numéro de téléphone
                                <span style="font-size:.72rem;font-weight:400;color:#64748b;margin-left:6px">— requis pour les alertes SMS</span>
                            </label>
                            <div style="position:relative">
                                <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#64748b;font-size:.85rem;pointer-events:none">🇨🇲</span>
                                <input type="tel" name="telephone" class="form-control" style="padding-left:36px"
                                    placeholder="6XXXXXXXX ou +237 6XX XXX XXX"
                                    value="<?= htmlspecialchars($_POST['telephone']??'') ?>"
                                    pattern="^(\+237)?[26][0-9]{8}$">
                            </div>
                            <div style="font-size:.72rem;color:#94a3b8;margin-top:4px">
                                <i class="fas fa-info-circle"></i>
                                Format accepté : <code>691234567</code> ou <code>+237691234567</code>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-lock"></i> Mot de passe *</label>
                            <input type="password" name="password" class="form-control" placeholder="Minimum 6 caractères" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-user-shield"></i> Rôle *</label>
                            <select name="id_role" class="form-control" required>
                                <option value="">-- Choisir un rôle --</option>
                                <?php foreach ($roles as $r): ?>
                                <?php if ($r['nom_role']==='Administrateur'&&$user['nom_role']!=='Administrateur') continue; ?>
                                <option value="<?= $r['id_role'] ?>" <?= ($_POST['id_role']??'')==$r['id_role']?'selected':'' ?>>
                                    <?= htmlspecialchars($r['nom_role']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="alert alert-info" style="margin-top:4px">
                        <i class="fas fa-info-circle"></i>
                        Le mot de passe temporaire sera communiqué à l'utilisateur. Il pourra le modifier depuis son profil.
                    </div>
                    <div style="display:flex;gap:10px;margin-top:8px">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Créer l'utilisateur</button>
                        <a href="<?= BASE_URL ?>/views/utilisateurs/index.php" class="btn btn-ghost">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>document.getElementById('pageTitle').textContent='Ajouter utilisateur';</script>
</body></html>