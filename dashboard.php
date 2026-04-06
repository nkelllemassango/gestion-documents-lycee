<?php
require_once __DIR__ . '/../config/init.php';
requireLogin();

$user = getUser();

// Sécurité : si la session est corrompue, getUser() peut retourner null
// On redirige vers login pour forcer une reconnexion propre
if (!$user || empty($user['nom_role'])) {
    session_destroy();
    header('Location: ' . BASE_URL . '/views/login.php');
    exit;
}

$role   = $user['nom_role'];
$rc     = roleColor($role);
$css    = roleCss($role);
$pdo    = getDB();
$uid    = $user['id_user'];
$unread = getUnreadCount($uid);

// Stats universelles
$totalDocs  = (int)$pdo->query("SELECT COUNT(*) FROM document")->fetchColumn();
$attente    = (int)$pdo->query("SELECT COUNT(*) FROM document WHERE statut='en attente'")->fetchColumn();
$signe      = (int)$pdo->query("SELECT COUNT(*) FROM signature WHERE id_user = $uid")->fetchColumn();
$archive    = (int)$pdo->query("SELECT COUNT(*) FROM document WHERE statut='archivé'")->fetchColumn();

// Mes docs
$s = $pdo->prepare("SELECT COUNT(*) FROM document WHERE expediteur_id=?"); $s->execute([$uid]);
$myDocs = (int)$s->fetchColumn();

// Admin extra
$totalUsers  = (int)$pdo->query("SELECT COUNT(*) FROM user WHERE statut='actif'")->fetchColumn();
$totalNotifs = (int)$pdo->query("SELECT COUNT(*) FROM notification WHERE statut='non lu'")->fetchColumn();

// Docs récents (selon rôle)
if ($role === 'Administrateur') {
    $recentStmt = $pdo->query("SELECT d.*,c.nom_categorie,u1.nom_user exp_nom,u2.nom_user dest_nom FROM document d LEFT JOIN categorie c ON d.categorie_id=c.id_categorie LEFT JOIN user u1 ON d.expediteur_id=u1.id_user LEFT JOIN user u2 ON d.destinataire_id=u2.id_user ORDER BY d.date DESC LIMIT 8");
} else {
    $recentStmt = $pdo->prepare("SELECT d.*,c.nom_categorie,u1.nom_user exp_nom,u2.nom_user dest_nom FROM document d LEFT JOIN categorie c ON d.categorie_id=c.id_categorie LEFT JOIN user u1 ON d.expediteur_id=u1.id_user LEFT JOIN user u2 ON d.destinataire_id=u2.id_user WHERE d.expediteur_id=? OR d.destinataire_id=? ORDER BY d.date DESC LIMIT 8");
    $recentStmt->execute([$uid,$uid]);
}
$recent = $recentStmt->fetchAll();

// Charts (admin)
$byStatus = $pdo->query("SELECT statut,COUNT(*) cnt FROM document GROUP BY statut")->fetchAll();
$monthly  = $pdo->query("SELECT DATE_FORMAT(date,'%b') mo,COUNT(*) cnt FROM document WHERE date>=DATE_SUB(NOW(),INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(date,'%Y-%m') ORDER BY date")->fetchAll();

$statusMap=['envoyé'=>'b-envoye','en attente'=>'b-attente','signé'=>'b-signe','refusé'=>'b-refuse','archivé'=>'b-archive','validé'=>'b-vélidé'];
$statusLabel=['envoyé'=>'Envoyé','en attente'=>'En attente','signé'=>'Signé','refusé'=>'Refusé','archivé'=>'Archivé','validé'=>'validé'];
$hour     = (int)date('H');
$greeting = $hour<12?'Bonjour':($hour<18?'Bon après-midi':'Bonsoir');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tableau de bord — <?= htmlspecialchars($role) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<script>const BASE_URL='<?= BASE_URL ?>';</script>
</head>
<body class="<?= $css ?>">
<?php include ROOT_PATH.'/views/partials/sidebar.php'; ?>
<?php include ROOT_PATH.'/views/partials/navbar.php'; ?>

<main class="app-main">
    <!-- Page header -->
    <div class="page-header">
        <div>
            <div class="page-title">
                <i class="fas fa-th-large"></i>
                Tableau de bord
            </div>
            <div class="page-sub">
                <?= $greeting ?>, <strong><?= htmlspecialchars($user['nom_user']) ?></strong> —
                <?= date('l d F Y') ?>
            </div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a href="<?= BASE_URL ?>/views/documents/ajouter.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouveau document
            </a>
            <?php if ($role==='Administrateur'): ?>
            <a href="<?= BASE_URL ?>/views/utilisateurs/ajouter.php" class="btn btn-outline">
                <i class="fas fa-user-plus"></i> Ajouter utilisateur
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- STAT CARDS -->
    <div class="stats-grid">
        <?php if ($role==='Administrateur'): ?>
        <div class="stat-card">
            <div class="stat-icon" style="background:#eff6ff;color:#1d4ed8"><i class="fas fa-users"></i></div>
            <div class="stat-val"><?= $totalUsers ?></div>
            <div class="stat-lbl">Utilisateurs actifs</div>
            <span class="stat-trend trend-up"><i class="fas fa-arrow-up"></i> Actifs</span>
        </div>
        <?php endif; ?>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2;color:#0f172a"><i class="fas fa-file-alt"></i></div>
            <div class="stat-val"><?= $role==='Administrateur' ? $totalDocs : $myDocs ?></div>
            <div class="stat-lbl"><?= $role==='Administrateur' ? 'Total documents' : 'Mes documents' ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef9c3;color:#a16207"><i class="fas fa-clock"></i></div>
            <div class="stat-val"><?= $attente ?></div>
            <div class="stat-lbl">En attente</div>
            <?php if ($attente>0): ?><span class="stat-trend trend-warn"><i class="fas fa-exclamation"></i> Urgent</span><?php endif; ?>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#dcfce7;color:#16a34a"><i class="fas fa-check-double"></i></div>
            <div class="stat-val"><?= $signe ?></div>
            <div class="stat-lbl">Documents signés</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#f3e8ff;color:#7e22ce"><i class="fas fa-archive"></i></div>
            <div class="stat-val"><?= $archive ?></div>
            <div class="stat-lbl">Archivés</div>
        </div>
        <?php if ($role==='Administrateur'): ?>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-bell"></i></div>
            <div class="stat-val"><?= $totalNotifs ?></div>
            <div class="stat-lbl">Notifs non lues</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- CHARTS (admin) -->
    <?php if ($role==='Administrateur'): ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:22px">
        <div class="chart-wrap">
            <div class="chart-title"><i class="fas fa-chart-pie"></i> Documents par statut</div>
            <div style="height:200px"><canvas id="chartStatus"></canvas></div>
        </div>
        <div class="chart-wrap">
            <div class="chart-title"><i class="fas fa-chart-bar"></i> Activité mensuelle</div>
            <div style="height:200px"><canvas id="chartMonthly"></canvas></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- RECENT DOCS -->
    <div class="data-card">
        <div class="data-card-head">
            <div class="data-card-title"><i class="fas fa-clock"></i> Documents récents</div>
            <div style="display:flex;gap:10px;align-items:center">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Rechercher…" data-search="recentTbl">
                </div>
                <a href="<?= BASE_URL ?>/views/documents/index.php" class="btn btn-ghost btn-sm">Voir tout</a>
            </div>
        </div>
        <div class="no-pad">
            <?php if (empty($recent)): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <p>Aucun document pour le moment</p>
                <a href="<?= BASE_URL ?>/views/documents/ajouter.php" class="btn btn-primary" style="margin-top:14px"><i class="fas fa-plus"></i> Créer un document</a>
            </div>
            <?php else: ?>
            <table class="tbl" id="recentTbl">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Document</th>
                        <th>Catégorie</th>
                        <th>Expéditeur</th>
                        <th>Destinataire</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $d): ?>
                    <tr>
                        <td style="color:#94a3b8;font-size:.75rem"><?= str_pad($d['id_doc'],4) ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:9px">
                                <div style="width:34px;height:34px;border-radius:8px;background:var(--al);display:flex;align-items:center;justify-content:center;color:var(--at);font-size:.82rem;flex-shrink:0">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:.85rem"><?= htmlspecialchars($d['nom_doc']) ?></div>
                                    <?php if ($d['description']): ?><div style="font-size:.73rem;color:#94a3b8"><?= htmlspecialchars(substr($d['description'],0,40)) ?>…</div><?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><span style="background:#f1f5f9;padding:3px 10px;border-radius:20px;font-size:.75rem"><?= htmlspecialchars($d['nom_categorie'] ?? '—') ?></span></td>
                        <td style="font-size:.83rem"><?= htmlspecialchars($d['exp_nom'] ?? '—') ?></td>
                        <td style="font-size:.83rem"><?= htmlspecialchars($d['dest_nom'] ?? '—') ?></td>
                        <td><span class="badge <?= $statusMap[$d['statut']] ?? 'b-attente' ?>"><?= $statusLabel[$d['statut']] ?? $d['statut'] ?></span></td>
                        <td style="font-size:.78rem;color:#94a3b8;white-space:nowrap"><?= date('d/m/Y', strtotime($d['date'])) ?></td>
                        <td>
                            <div style="display:flex;gap:5px">
                                <a href="<?= BASE_URL ?>/views/documents/detail.php?id=<?= $d['id_doc'] ?>" class="btn btn-info btn-sm" title="Voir"><i class="fas fa-eye"></i></a>
                                <?php if ($role==='Administrateur' || $d['expediteur_id']==$uid): ?>
                                <a href="<?= BASE_URL ?>/views/documents/modifier.php?id=<?= $d['id_doc'] ?>" class="btn btn-warn btn-sm" title="Modifier"><i class="fas fa-edit"></i></a>
                                <?php endif; ?>
                                <?php if (in_array($d['statut'],['en attente','envoyé'])): ?>
                                <a href="<?= BASE_URL ?>/views/documents/signer.php?id=<?= $d['id_doc'] ?>" class="btn btn-success btn-sm" title="Signer"><i class="fas fa-pen-nib"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($role==='Administrateur'): ?>
    <!-- RECENT USERS (admin only) -->
    <?php $users = $pdo->query("SELECT u.*,r.nom_role FROM user u JOIN roles r ON u.id_role=r.id_role ORDER BY u.date_creation DESC LIMIT 5")->fetchAll(); ?>
    <div class="data-card">
        <div class="data-card-head">
            <div class="data-card-title"><i class="fas fa-users"></i> Utilisateurs récents</div>
            <a href="<?= BASE_URL ?>/views/utilisateurs/index.php" class="btn btn-ghost btn-sm">Gérer →</a>
        </div>
        <div class="no-pad">
            <table class="tbl">
                <thead><tr>
                <th>Utilisateur</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Statut</th>
                <th>Créé le</th>
                <th>Action</th></tr>
            </thead>
                <tbody>
                    <?php foreach ($users as $u):
                        $ui = initiales($u['nom_user']);
                        $uc = roleColor($u['nom_role']);
                        $ph=photoUrl($u['photo']??'');
                    ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:9px;">
                                <div style="width:36px;height:36px;border-radius:50%;;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;overflow:hidden;flex-shrink:0">
                                    <?php if ($ph): ?><img src="<?= $ph ?>" style="width:100%;height:100%;object-fit:cover" alt=""><?php else: ?><?= $ui ?><?php endif; ?>
                                </div>
                                <span style="font-weight:600;font-size:.85rem"><?= htmlspecialchars($u['nom_user']) ?></span>
                            </div>
                        </td>
                        <td style="font-size:.82rem;color:#64748b"><?= htmlspecialchars($u['email']) ?></td>
                        <td><span style="background:;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700"><?= htmlspecialchars($u['nom_role']) ?></span></td>
                        <td><span class="badge <?= $u['statut']==='actif'?'b-actif':'b-inactif' ?>"><?= $u['statut'] ?></span></td>
                        <td style="font-size:.78rem;color:#94a3b8"><?= date('d/m/Y', strtotime($u['date_creation'])) ?></td>
                        <td><a href="<?= BASE_URL ?>/views/utilisateurs/modifier.php?id=<?= $u['id_user'] ?>" class="btn btn-warn btn-sm"><i class="fas fa-edit"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
document.getElementById('pageTitle').textContent = 'Tableau de bord';
<?php if ($role==='Administrateur'): ?>
const primary = '';
// Status doughnut
new Chart(document.getElementById('chartStatus'),{type:'doughnut',data:{
    labels:<?= json_encode(array_column($byStatus,'statut')) ?>,
    datasets:[{data:<?= json_encode(array_column($byStatus,'cnt')) ?>,backgroundColor:['#3b82f6','#eab308','#22c55e','#ef4444','#a855f7'],borderWidth:0,hoverOffset:5}]
},options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{position:'bottom',labels:{font:{family:'Outfit'},padding:12,usePointStyle:true,boxWidth:9}}}}});
// Monthly bar
new Chart(document.getElementById('chartMonthly'),{type:'bar',data:{
    labels:<?= json_encode(array_column($monthly,'mo')) ?>,
    datasets:[{label:'Documents',data:<?= json_encode(array_column($monthly,'cnt')) ?>,backgroundColor:primary+'55',borderColor:primary,borderWidth:2,borderRadius:6}]
},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'#f1f5f9'},ticks:{font:{family:'Outfit'}}},x:{grid:{display:false},ticks:{font:{family:'Outfit'}}}}}});
<?php endif; ?>
</script>
</body>
</html>