<?php
require_once __DIR__ . '/../../config/init.php';
requireLogin();
$user=getUser();$role=$user['nom_role'];$uid=$user['id_user'];$css=roleCss($role);
$pdo=getDB();$unread=getUnreadCount($uid);
if($role==='Administrateur'){$docs=$pdo->query("SELECT d.*,c.nom_categorie,u1.nom_user exp_nom FROM document d LEFT JOIN categorie c ON d.categorie_id=c.id_categorie LEFT JOIN user u1 ON d.expediteur_id=u1.id_user WHERE d.statut='archivé' ORDER BY d.date DESC")->fetchAll();}
else{$s=$pdo->prepare("SELECT d.*,c.nom_categorie,u1.nom_user exp_nom FROM document d LEFT JOIN categorie c ON d.categorie_id=c.id_categorie LEFT JOIN user u1 ON d.expediteur_id=u1.id_user WHERE d.statut='archivé' AND (d.expediteur_id=? OR d.destinataire_id=?) ORDER BY d.date DESC");$s->execute([$uid,$uid]);$docs=$s->fetchAll();}
?><!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Archives</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css"><script>const BASE_URL='<?= BASE_URL ?>';</script></head>
<body class="<?= $css ?>"><?php include ROOT_PATH.'/views/partials/sidebar.php';include ROOT_PATH.'/views/partials/navbar.php'; ?>
<main class="app-main">
    <div class="page-header"><div><div class="page-title"><i class="fas fa-archive"></i> Archives</div><div class="page-sub"><?= count($docs) ?> document(s) archivé(s)</div></div></div>
    <div class="data-card"><div class="no-pad">
    <?php if(empty($docs)): ?><div class="empty-state"><i class="fas fa-archive"></i><p>Aucun document archivé</p></div>
    <?php else: ?>
    <table class="tbl" id="archTbl"><thead><tr><th>#</th><th>Document</th><th>Catégorie</th><th>Expéditeur</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody><?php foreach($docs as $d): ?><tr>
        <td style="color:#94a3b8;font-size:.75rem"><?= str_pad($d['id_doc'],4,'0',STR_PAD_LEFT) ?></td>
        <td><div style="display:flex;align-items:center;gap:9px">
            <div style="width:32px;height:32px;border-radius:8px;background:#f3e8ff;display:flex;align-items:center;justify-content:center;color:#7e22ce;font-size:.8rem"><i class="fas fa-archive"></i></div>
            <span style="font-weight:600;font-size:.84rem"><?= htmlspecialchars($d['nom_doc']) ?></span>
        </div></td>
        <td><span style="background:#f1f5f9;padding:3px 9px;border-radius:20px;font-size:.74rem"><?= htmlspecialchars($d['nom_categorie']??'—') ?></span></td>
        <td style="font-size:.83rem"><?= htmlspecialchars($d['exp_nom']??'—') ?></td>
        <td style="font-size:.77rem;color:#94a3b8"><?= date('d/m/Y',strtotime($d['date'])) ?></td>
        <td><div style="display:flex;gap:5px">
            <a href="<?= BASE_URL ?>/views/documents/detail.php?id=<?= $d['id_doc'] ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
            <?php if(!empty($d['fichier'])): ?><a href="<?= BASE_URL ?>/uploads/documents/<?= htmlspecialchars($d['fichier']) ?>" download class="btn btn-ghost btn-sm"><i class="fas fa-download"></i></a><?php endif; ?>
        </div></td>
    </tr><?php endforeach; ?></tbody></table><?php endif; ?>
    </div></div>
</main>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>document.getElementById('pageTitle').textContent='Archives';</script>
</body></html>
