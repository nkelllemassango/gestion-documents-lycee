<?php
require_once __DIR__ . '/../../config/init.php';
requireRole(['Administrateur']);
$user   = getUser(); $css = roleCss($user['nom_role']);
$pdo    = getDB();
$unread = getUnreadCount($user['id_user']);
$roles  = $pdo->query("SELECT r.*,COUNT(u.id_user) nb FROM roles r LEFT JOIN user u ON u.id_role=r.id_role GROUP BY r.id_role")->fetchAll();
$perms  = [
    'Administrateur'=>['Gestion complète','Tous les documents','Gérer les utilisateurs','Signature', 'validation','Statistiques système'],
    'Secrétaire'    =>['CRUD documents','Envoi & envoi groupé','Gestion catégories','Documents reçus/signés','Notifications'],
    'Censeur'       =>['Documents pédagogiques','Envoi aux enseignants','Consulter & télécharger','Documents reçus','Notifications'],
    'Intendant'     =>['Documents administratifs','Archivage','Signature & refus','Gérer utilisateurs','Envoi au proviseur'],
    'Enseignant'    =>['Ses propres documents','Signature','Documents reçus','Téléchargement','Notifications','Envoi au censeur'],
];
$matrix=[
    'Créer des documents'     =>[true,true,true,true,true],
    'Modifier des documents'  =>[true,true,true,true,true],
    'Valider un document'     =>[true,false,false,true,false],
    'Supprimer des documents' =>[true,true,true,true,true],
    'Signer des documents'    =>[true,false,true,true,true],
    'Refuser des documents'   =>[true,false,true,true,false],
    'Archiver des documents'  =>[true,false,false,true,false],
    'Envoyer des documents'   =>[true,true,true,true,true],
    'Gérer les utilisateurs'  =>[true,false,false,true,false],
    'Gérer les catégories'    =>[true,true,false,true,false],
    'Voir les statistiques'   =>[true,false,false,false,false],
    'Télécharger'             =>[true,true,true,true,true],
];
?><!DOCTYPE html>
<html lang="fr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Rôles & Accès</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<script>const BASE_URL='<?= BASE_URL ?>';</script>
</head><body class="<?= $css ?>">
<?php include ROOT_PATH.'/views/partials/sidebar.php'; include ROOT_PATH.'/views/partials/navbar.php'; ?>
<main class="app-main">
    <div class="page-header">
        <div><div class="page-title"><i class="fas fa-user-shield"></i> Rôles & Accès</div><div class="page-sub">Permissions du système par rôle</div></div>
    </div>

    <!-- Role cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:18px;margin-bottom:24px">
        <?php foreach ($roles as $r):
            $rc=roleColor($r['nom_role']);
            $p=$perms[$r['nom_role']]??[];
        ?>
        <div class="data-card" style="overflow:visible">
            <div style="background:linear-gradient(135deg,<?= $rc['primary'] ?>,<?= $rc['accent'] ?>);padding:22px 18px;text-align:center;color:#fff;border-radius:var(--r) var(--r) 0 0">
                <div style="width:52px;height:52px;border-radius:50%;background:rgba(255,255,255,.15);border:2px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:1.2rem">
                    <i class="fas <?= roleIcon($r['nom_role']) ?>"></i>
                </div>
                <div style="font-weight:700;font-size:.9rem"><?= htmlspecialchars($r['nom_role']) ?></div>
                <div style="font-size:.72rem;opacity:.7;margin-top:3px"><?= $r['nb'] ?> utilisateur(s)</div>
            </div>
            <div style="padding:14px 16px">
                <?php foreach ($p as $perm): ?>
                <div style="display:flex;align-items:center;gap:7px;font-size:.78rem;color:#374151;margin-bottom:5px">
                    <i class="fas fa-check" style="color:<?= $rc['accent'] ?>;font-size:.65rem;flex-shrink:0"></i><?= $perm ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Matrix -->
    <div class="data-card">
        <div class="data-card-head"><div class="data-card-title"><i class="fas fa-table"></i> Matrice des permissions</div></div>
        <div class="no-pad" style="overflow-x:auto">
            <table class="tbl" style="min-width:620px">
                <thead>
                    <tr>
                        <th>Fonctionnalité</th>
                        <?php foreach ($roles as $r): ?>
                        <th style="text-align:center;white-space:nowrap"><i class="fas <?= roleIcon($r['nom_role']) ?>" style="margin-right:4px"></i><?= htmlspecialchars($r['nom_role']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matrix as $feat=>$vals): ?>
                    <tr>
                        <td style="font-size:.84rem;font-weight:500"><?= $feat ?></td>
                        <?php foreach ($vals as $v): ?>
                        <td style="text-align:center">
                            <?php if ($v): ?>
                            <i class="fas fa-check-circle" style="color:#22c55e;font-size:.95rem"></i>
                            <?php else: ?>
                            <i class="fas fa-times-circle" style="color:#e2e8f0;font-size:.95rem"></i>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>document.getElementById('pageTitle').textContent='Rôles & Accès';</script>
</body></html>
