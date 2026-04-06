<?php
require_once __DIR__ . '/../config/init.php';
requireLogin();
$user=getUser();$role=$user['nom_role'];$uid=$user['id_user'];$css=roleCss($role);
$pdo=getDB();$unread=getUnreadCount($uid);
if($role==='Administrateur'){
    $notifs=$pdo->query("SELECT n.*,u.nom_user,d.nom_doc FROM notification n LEFT JOIN user u ON n.id_user=u.id_user LEFT JOIN document d ON n.id_doc=d.id_doc ORDER BY n.date_notification DESC LIMIT 100")->fetchAll();
} else {
    $s=$pdo->prepare("SELECT n.*,u.nom_user,d.nom_doc FROM notification n LEFT JOIN user u ON n.id_user=u.id_user LEFT JOIN document d ON n.id_doc=d.id_doc WHERE n.id_user=? ORDER BY n.date_notification DESC LIMIT 100");
    $s->execute([$uid]);$notifs=$s->fetchAll();
}
// Mark all read on visit
$pdo->prepare("UPDATE notification SET statut='lu' WHERE id_user=?")->execute([$uid]);
?><!DOCTYPE html>
<html lang="fr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Notifications</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<script>const BASE_URL='<?= BASE_URL ?>';</script>
</head><body class="<?= $css ?>">
<?php include ROOT_PATH.'/views/partials/sidebar.php';include ROOT_PATH.'/views/partials/navbar.php'; ?>
<main class="app-main">
    <div class="page-header">
        <div><div class="page-title"><i class="fas fa-bell"></i> Notifications</div><div class="page-sub"><?= count($notifs) ?> notification(s)</div></div>
    </div>
    <?php if(empty($notifs)): ?>
    <div class="data-card"><div class="empty-state" style="padding:72px 20px"><i class="fas fa-bell-slash"></i><p>Aucune notification</p></div></div>
    <?php else: ?>
    <div class="data-card">
        <div class="no-pad">
            <?php foreach($notifs as $n): ?>
            <div style="display:flex;gap:14px;padding:16px 22px;border-bottom:1px solid #f8fafc;transition:background .15s" onmouseover="this.style.background='#fafbff'" onmouseout="this.style.background=''">
                <div style="width:42px;height:42px;border-radius:50%;background:var(--al);display:flex;align-items:center;justify-content:center;color:var(--at);font-size:.95rem;flex-shrink:0"><i class="fas fa-bell"></i></div>
                <div style="flex:1">
                    <div style="font-size:.85rem;color:#374151;line-height:1.5"><?= htmlspecialchars($n['contenu']) ?></div>
                    <div style="display:flex;gap:14px;margin-top:5px;flex-wrap:wrap">
                        <?php if($n['nom_doc']): ?><span style="font-size:.75rem;color:var(--at);font-weight:600"><i class="fas fa-file-alt" style="margin-right:4px"></i><?= htmlspecialchars($n['nom_doc']) ?></span><?php endif; ?>
                        <span style="font-size:.73rem;color:#94a3b8"><i class="fas fa-clock" style="margin-right:4px"></i><?= date('d/m/Y H:i',strtotime($n['date_notification'])) ?></span>
                        <?php if($role==='Administrateur'&&$n['nom_user']): ?><span style="font-size:.73rem;color:#64748b"><i class="fas fa-user" style="margin-right:4px"></i><?= htmlspecialchars($n['nom_user']) ?></span><?php endif; ?>
                    </div>
                </div>
                <?php if($n['id_doc']): ?><a href="<?= BASE_URL ?>/views/documents/detail.php?id=<?= $n['id_doc'] ?>" class="btn btn-info btn-sm" style="align-self:center"><i class="fas fa-eye"></i></a><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</main>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>document.getElementById('pageTitle').textContent='Notifications';</script>
</body></html>
