<?php
require_once __DIR__ . '/../../config/init.php';
requireLogin();
$user=getUser();$role=$user['nom_role'];$uid=$user['id_user'];$css=roleCss($role);
$pdo=getDB();$unread=getUnreadCount($uid);
if($role==='Administrateur'){$docs=$pdo->query("SELECT d.*,c.nom_categorie,u1.nom_user exp_nom FROM document d LEFT JOIN categorie c ON d.categorie_id=c.id_categorie LEFT JOIN user u1 ON d.expediteur_id=u1.id_user WHERE d.statut IN('en attente','envoyé') ORDER BY d.date ASC")->fetchAll();}
else{$s=$pdo->prepare("SELECT d.*,c.nom_categorie,u1.nom_user exp_nom FROM document d LEFT JOIN categorie c ON d.categorie_id=c.id_categorie LEFT JOIN user u1 ON d.expediteur_id=u1.id_user WHERE d.statut IN('en attente','envoyé') AND (d.destinataire_id=? OR d.expediteur_id=?) ORDER BY d.date ASC");$s->execute([$uid,$uid]);$docs=$s->fetchAll();}
?><!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>À signer</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css"><script>const BASE_URL='<?= BASE_URL ?>';</script></head>
<body class="<?= $css ?>"><?php include ROOT_PATH.'/views/partials/sidebar.php';include ROOT_PATH.'/views/partials/navbar.php'; ?>
<main class="app-main">
    <div class="page-header"><div><div class="page-title"><i class="fas fa-pen-nib"></i> Documents à signer</div><div class="page-sub"><?= count($docs) ?> document(s) en attente</div></div></div>
    <?php if(empty($docs)): ?>
    <div class="data-card"><div class="empty-state" style="padding:72px 20px">
        <i class="fas fa-check-circle" style="color:#22c55e"></i>
        <p>Aucun document en attente de signature. Tout est à jour !</p>
    </div></div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px">
        <?php foreach($docs as $d): ?>
        <div class="data-card">
            <div style="padding:20px;border-bottom:1px solid #f1f5f9">
                <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:12px">
                    <div style="width:42px;height:42px;background:var(--al);border-radius:9px;display:flex;align-items:center;justify-content:center;color:var(--at);font-size:1rem;flex-shrink:0"><i class="fas fa-file-alt"></i></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:700;font-size:.9rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($d['nom_doc']) ?></div>
                        <div style="font-size:.74rem;color:#94a3b8;margin-top:2px"><?= htmlspecialchars($d['nom_categorie']??'') ?></div>
                    </div>
                </div>
                <?php if($d['description']): ?><div style="font-size:.8rem;color:#64748b;line-height:1.5;background:#f8fafc;border-radius:7px;padding:9px;margin-bottom:10px"><?= htmlspecialchars(substr($d['description'],0,100)) ?>…</div><?php endif; ?>
                <div style="display:flex;justify-content:space-between;font-size:.76rem;color:#94a3b8">
                    <span><i class="fas fa-user" style="margin-right:4px"></i><?= htmlspecialchars($d['exp_nom']??'?') ?></span>
                    <span><i class="fas fa-calendar" style="margin-right:4px"></i><?= date('d/m/Y',strtotime($d['date'])) ?></span>
                </div>
            </div>
            <div style="padding:13px 20px;display:flex;gap:8px">
                <a href="<?= BASE_URL ?>/views/documents/signer.php?id=<?= $d['id_doc'] ?>" class="btn btn-success btn-sm" style="flex:1;justify-content:center"><i class="fas fa-pen-nib"></i> Signer</a>
                <a href="<?= BASE_URL ?>/views/documents/detail.php?id=<?= $d['id_doc'] ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                <?php if(in_array($role,['Administrateur','Intendant'])): ?>
                <a href="<?= BASE_URL ?>/controllers/DocumentController.php?action=refuser&id=<?= $d['id_doc'] ?>" onclick="return confirm('Refuser ce document ?')" class="btn btn-danger btn-sm"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>
<script src="assets/js/app.js"></script>
<script>document.getElementById('pageTitle').textContent='À signer';</script>
</body></html>
