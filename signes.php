<?php
require_once __DIR__ . '/../../config/init.php';
requireLogin();
$user=getUser();$role=$user['nom_role'];$uid=$user['id_user'];$css=roleCss($role);
$pdo=getDB();$unread=getUnreadCount($uid);
if($role==='Administrateur'){$stmt=$pdo->query("SELECT d.*,c.nom_categorie,u1.nom_user exp_nom,sig.nom_user signe_par,s.date_sign FROM document d LEFT JOIN categorie c ON d.categorie_id=c.id_categorie LEFT JOIN user u1 ON d.expediteur_id=u1.id_user LEFT JOIN signature s ON s.id_doc=d.id_doc LEFT JOIN user sig ON s.id_user=sig.id_user WHERE d.statut='signé' ORDER BY d.date DESC");}
else{$stmt=$pdo->prepare("SELECT d.*,c.nom_categorie,u1.nom_user exp_nom,sig.nom_user signe_par,s.date_sign FROM document d LEFT JOIN categorie c ON d.categorie_id=c.id_categorie LEFT JOIN user u1 ON d.expediteur_id=u1.id_user LEFT JOIN signature s ON s.id_doc=d.id_doc LEFT JOIN user sig ON s.id_user=sig.id_user WHERE d.statut='signé' AND (d.expediteur_id=? OR d.destinataire_id=?) ORDER BY d.date DESC");$stmt->execute([$uid,$uid]);}
$docs=$stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Signés</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css"><script>const BASE_URL='<?= BASE_URL ?>';</script>
</head>
<body class="<?= $css ?>"><?php include ROOT_PATH.'/views/partials/sidebar.php';include ROOT_PATH.'/views/partials/navbar.php'; ?>
<main class="app-main">
    <div class="page-header"><div><div class="page-title">
        <i class="fas fa-check-circle"></i> Documents signés</div><div class="page-sub"><?= count($docs) ?> document(s)</div></div></div>
    <div class="data-card"><div class="no-pad">
    <?php if(empty($docs)): ?><div class="empty-state"><i class="fas fa-signature"></i><p>Aucun document signé</p></div>
    <?php else: ?>
    <table class="tbl"><thead><tr><th>#</th><th>Document</th><th>Catégorie</th><th>Expéditeur</th><th>Signé par</th><th>Date signature</th><th>Actions</th></tr></thead>
    <tbody><?php foreach($docs as $d): ?><tr>
        <td style="color:#94a3b8;font-size:.75rem"><?= str_pad($d['id_doc'],4,'0',STR_PAD_LEFT) ?></td>
        <td style="font-weight:600;font-size:.85rem"><?= htmlspecialchars($d['nom_doc']) ?></td>
        <td><span style="background:#f1f5f9;padding:3px 9px;border-radius:20px;font-size:.74rem"><?= htmlspecialchars($d['nom_categorie']??'—') ?></span></td>
        <td style="font-size:.83rem"><?= htmlspecialchars($d['exp_nom']??'—') ?></td>
        <td style="font-size:.83rem;font-weight:600;color:#16a34a"><?= htmlspecialchars($d['signe_par']??'—') ?></td>
        <td style="font-size:.77rem;color:#94a3b8"><?= $d['date_sign']?date('d/m/Y H:i',strtotime($d['date_sign'])):'—' ?></td>
        <td><a href="<?= BASE_URL ?>/views/documents/detail.php?id=<?= $d['id_doc'] ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a></td>
    </tr><?php endforeach; ?></tbody></table><?php endif; ?>
    </div></div>
</main>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>document.getElementById('pageTitle').textContent='Signés';</script>
</body></html>
