<?php
require_once __DIR__ . '/../../config/init.php';
requireLogin();
if (!in_array(getUserRole(),['Administrateur','Intendant'])) { header('Location: '.BASE_URL.'/views/dashboard.php'); exit; }
$user   = getUser();
$css    = roleCss($user['nom_role']);
$pdo    = getDB();
$id     = (int)($_GET['id']??0);
$stmt   = $pdo->prepare("SELECT u.*,r.nom_role FROM user u JOIN roles r ON u.id_role=r.id_role WHERE u.id_user=?");
$stmt->execute([$id]); $target = $stmt->fetch();
if (!$target) { header('Location: '.BASE_URL.'/views/utilisateurs/index.php'); exit; }
$roles  = $pdo->query("SELECT * FROM roles ORDER BY nom_role")->fetchAll();
$unread = getUnreadCount($user['id_user']);
$err    = $_SESSION['usr_err']??null; unset($_SESSION['usr_err']);
$init   = initiales($target['nom_user']);
$rc     = roleColor($target['nom_role']);
$ph     = photoUrl($target['photo']??'');
?><!DOCTYPE html>
<html lang="fr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Modifier l'utilisateur</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<script>const BASE_URL='<?= BASE_URL ?>';</script>
</head><body class="<?= $css ?>">
<?php include ROOT_PATH.'/views/partials/sidebar.php'; include ROOT_PATH.'/views/partials/navbar.php'; ?>
<main class="app-main">
    <div class="page-header">
        <div>
            <div class="page-title"><i class="fas fa-user-edit"></i> Modifier l'utilisateur</div>
            <div class="page-sub"><?= htmlspecialchars($target['nom_user']) ?></div>
        </div>
        <a href="<?= BASE_URL ?>/views/utilisateurs/index.php" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>

    <?php if ($err): ?><div class="alert alert-error"><i class="fas fa-times-circle"></i><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 280px;gap:22px;max-width:900px">
        <div class="data-card">
            <div class="data-card-head"><div class="data-card-title"><i class="fas fa-id-card"></i> Informations</div></div>
            <div class="data-card-body">
                <form method="POST" action="<?= BASE_URL ?>/controllers/UserController.php?action=modifier" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id_user" value="<?= $target['id_user'] ?>">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-user"></i> Nom complet *</label>
                        <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($target['nom_user']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($target['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-mobile-alt"></i> Téléphone
                            <span style="font-size:.72rem;font-weight:400;color:#64748b;margin-left:6px">— alertes SMS</span>
                        </label>
                        <div style="position:relative">
                            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#64748b;font-size:.85rem;pointer-events:none">🇨🇲</span>
                            <input type="tel" name="telephone" class="form-control" style="padding-left:36px"
                                placeholder="6XXXXXXXX ou +237 6XX XXX XXX"
                                value="<?= htmlspecialchars($target['telephone']??'') ?>"
                                pattern="^(\+237)?[26][0-9]{8}$">
                        </div>
                        <?php if (!empty($target['telephone'])): ?>
                        <div style="font-size:.72rem;color:#16a34a;margin-top:4px">
                            <i class="fas fa-check-circle"></i> SMS activé — <?= htmlspecialchars($target['telephone']) ?>
                        </div>
                        <?php else: ?>
                        <div style="font-size:.72rem;color:#f97316;margin-top:4px">
                            <i class="fas fa-exclamation-triangle"></i> Aucun numéro — les alertes SMS ne seront pas envoyées
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-user-shield"></i> Rôle</label>
                            <select name="id_role" class="form-control" <?= $target['id_user']==$user['id_user']?'disabled':'' ?>>
                                <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id_role'] ?>" <?= $target['id_role']==$r['id_role']?'selected':'' ?>><?= htmlspecialchars($r['nom_role']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($target['id_user']==$user['id_user']): ?><input type="hidden" name="id_role" value="<?= $target['id_role'] ?>"><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-toggle-on"></i> Statut</label>
                            <select name="statut" class="form-control" <?= $target['id_user']==$user['id_user']?'disabled':'' ?>>
                                <option value="actif" <?= $target['statut']==='actif'?'selected':'' ?>>Actif</option>
                                <option value="inactif" <?= $target['statut']==='inactif'?'selected':'' ?>>Inactif</option>
                            </select>
                            <?php if ($target['id_user']==$user['id_user']): ?><input type="hidden" name="statut" value="<?= $target['statut'] ?>"><?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-lock"></i> Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                        <input type="password" name="password" class="form-control" placeholder="Laisser vide pour conserver" minlength="6">
                    </div>
                    <div style="display:flex;gap:10px">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                        <a href="<?= BASE_URL ?>/views/utilisateurs/index.php" class="btn btn-ghost">Annuler</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- User card -->
        <div class="data-card" style="align-self:start">
            <div style="background:linear-gradient(135deg,<?= $rc['primary'] ?>,<?= $rc['accent'] ?>);padding:28px 20px;text-align:center;color:#fff">
                <div style="width:68px;height:68px;border-radius:50%;border:3px solid rgba(255,255,255,.4);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:1.5rem;font-weight:700;background:rgba(255,255,255,.15);overflow:hidden">
                    <?php if ($ph): ?><img src="<?= $ph ?>" style="width:100%;height:100%;object-fit:cover" alt=""><?php else: ?><?= $init ?><?php endif; ?>
                </div>
                <div style="font-weight:700;font-size:.95rem"><?= htmlspecialchars($target['nom_user']) ?></div>
                <div style="font-size:.75rem;opacity:.75;margin-top:4px"><?= htmlspecialchars($target['nom_role']) ?></div>
            </div>
            <div style="padding:16px;display:flex;flex-direction:column;gap:10px;font-size:.82rem">
                <div style="display:flex;justify-content:space-between">
                    <span style="color:#64748b">Email</span>
                    <span style="font-weight:600;font-size:.76rem;text-align:right"><?= htmlspecialchars($target['email']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="color:#64748b">Téléphone</span>
                    <?php if (!empty($target['telephone'])): ?>
                    <span style="font-weight:600;font-size:.76rem;color:#16a34a">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($target['telephone']) ?>
                    </span>
                    <?php else: ?>
                    <span style="font-size:.75rem;color:#f97316"><i class="fas fa-times-circle"></i> Non renseigné</span>
                    <?php endif; ?>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="color:#64748b">Statut</span>
                    <span class="badge <?= $target['statut']==='actif'?'b-actif':'b-inactif' ?>"><?= $target['statut'] ?></span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:#64748b">Depuis</span>
                    <span style="font-weight:600"><?= date('d/m/Y',strtotime($target['date_creation'])) ?></span>
                </div>
            </div>
        </div>
    </div>
</main>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>document.getElementById('pageTitle').textContent='Modifier utilisateur';</script>
</body></html>