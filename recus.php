<?php
// views/documents/recus.php
require_once __DIR__ . '/../../config/init.php';
requireLogin();
$user=getUser();$role=$user['nom_role'];$uid=$user['id_user'];$css=roleCss($role);
$pdo=getDB();$unread=getUnreadCount($uid);
$s=$pdo->prepare("SELECT d.*,c.nom_categorie,u1.nom_user exp_nom FROM document d LEFT JOIN categorie c ON d.categorie_id=c.id_categorie LEFT JOIN user u1 ON d.expediteur_id=u1.id_user WHERE d.destinataire_id=? ORDER BY d.date DESC");
$s->execute([$uid]);$docs=$s->fetchAll();
$statusMap=['envoyé'=>'b-envoye','en attente'=>'b-attente','signé'=>'b-signe','refusé'=>'b-refuse','archivé'=>'b-archive'];
$statusLabel=['envoyé'=>'Envoyé','en attente'=>'En attente','signé'=>'Signé','refusé'=>'Refusé','archivé'=>'Archivé'];
?><!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Documents reçus</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css"><script>const BASE_URL='<?= BASE_URL ?>';</script></head>
<body class="<?= $css ?>"><?php include ROOT_PATH.'/views/partials/sidebar.php';include ROOT_PATH.'/views/partials/navbar.php'; ?>
<main class="app-main">
    <div class="page-header"><div><div class="page-title"><i class="fas fa-inbox"></i> Documents reçus</div><div class="page-sub"><?= count($docs) ?> document(s)</div></div></div>
    <div class="data-card"><div class="no-pad">
    <?php if(empty($docs)): ?><div class="empty-state"><i class="fas fa-inbox"></i><p>Aucun document reçu</p></div>
    <?php else: ?>
    <table class="tbl"><thead><tr><th>#</th><th>Document</th><th>Catégorie</th><th>De</th><th>Statut</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody><?php foreach($docs as $d): ?><tr>
        <td style="color:#94a3b8;font-size:.75rem"><?= str_pad($d['id_doc'],4,'0',STR_PAD_LEFT) ?></td>
        <td><div style="font-weight:600;font-size:.85rem"><?= htmlspecialchars($d['nom_doc']) ?></div></td>
        <td><span style="background:#f1f5f9;padding:3px 9px;border-radius:20px;font-size:.74rem"><?= htmlspecialchars($d['nom_categorie']??'—') ?></span></td>
        <td style="font-size:.83rem"><?= htmlspecialchars($d['exp_nom']??'—') ?></td>
        <td><span class="badge <?= $statusMap[$d['statut']]??'b-attente' ?>"><?= $statusLabel[$d['statut']]??$d['statut'] ?></span></td>
        <td style="font-size:.77rem;color:#94a3b8"><?= date('d/m/Y',strtotime($d['date'])) ?></td>
        <td><div style="display:flex;gap:5px">
            <a href="<?= BASE_URL ?>/views/documents/detail.php?id=<?= $d['id_doc'] ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
            <?php if(in_array($d['statut'],['en attente','envoyé'])): ?><a href="<?= BASE_URL ?>/views/documents/signer.php?id=<?= $d['id_doc'] ?>" class="btn btn-success btn-sm"><i class="fas fa-pen-nib"></i></a><?php endif; ?>
        </div></td>
    </tr><?php endforeach; ?></tbody></table><?php endif; ?>
    </div></div>
</main>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>document.getElementById('pageTitle').textContent='Documents reçus';</script>
</body></html>
