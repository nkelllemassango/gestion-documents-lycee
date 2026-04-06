<?php
require_once __DIR__ . '/../../config/init.php';
requireLogin();
$user=getUser();$role=$user['nom_role'];$uid=$user['id_user'];$css=roleCss($role);
$pdo=getDB();$unread=getUnreadCount($uid);
$s=$pdo->prepare("SELECT d.*,c.nom_categorie,u2.nom_user dest_nom FROM document d LEFT JOIN categorie c ON d.categorie_id=c.id_categorie LEFT JOIN user u2 ON d.destinataire_id=u2.id_user WHERE d.expediteur_id=? AND d.statut='envoyé' ORDER BY d.date DESC");
$s->execute([$uid]);$docs=$s->fetchAll();
?><!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Envoyés</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css"><script>const BASE_URL='<?= BASE_URL ?>';</script></head>
<body class="<?= $css ?>"><?php include ROOT_PATH.'/views/partials/sidebar.php';include ROOT_PATH.'/views/partials/navbar.php'; ?>
<main class="app-main">
    <div class="page-header"><div><div class="page-title"><i class="fas fa-paper-plane"></i> Documents envoyés</div><div class="page-sub"><?= count($docs) ?> document(s)</div></div></div>
    <div class="data-card"><div class="no-pad">
    <?php if(empty($docs)): ?><div class="empty-state"><i class="fas fa-paper-plane"></i><p>Aucun document envoyé</p></div>
    <?php else: ?>
    <table class="tbl"><thead><tr><th>#</th><th>Document</th><th>Catégorie</th><th>Destinataire</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody><?php foreach($docs as $d): ?><tr>
        <td style="color:#94a3b8;font-size:.75rem"><?= str_pad($d['id_doc'],4,'0',STR_PAD_LEFT) ?></td>
        <td style="font-weight:600;font-size:.85rem"><?= htmlspecialchars($d['nom_doc']) ?></td>
        <td><span style="background:#f1f5f9;padding:3px 9px;border-radius:20px;font-size:.74rem"><?= htmlspecialchars($d['nom_categorie']??'—') ?></span></td>
        <td style="font-size:.83rem"><?= htmlspecialchars($d['dest_nom']??'—') ?></td>
        <td style="font-size:.77rem;color:#94a3b8"><?= date('d/m/Y',strtotime($d['date'])) ?></td>
        <td><a href="<?= BASE_URL ?>/views/documents/detail.php?id=<?= $d['id_doc'] ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a></td>
    </tr><?php endforeach; ?></tbody></table><?php endif; ?>
    </div></div>
</main>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>document.getElementById('pageTitle').textContent='Envoyés';</script>
</body></html>
