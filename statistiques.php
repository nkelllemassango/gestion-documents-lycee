<?php
require_once __DIR__ . '/../config/init.php';
requireRole(['Administrateur']);
$user=getUser();$css=roleCss($user['nom_role']);$pdo=getDB();$unread=getUnreadCount($user['id_user']);
$totalDocs=(int)$pdo->query("SELECT COUNT(*) FROM document")->fetchColumn();
$totalUsers=(int)$pdo->query("SELECT COUNT(*) FROM user WHERE statut='actif'")->fetchColumn();
$totalSigs=(int)$pdo->query("SELECT COUNT(*) FROM signature")->fetchColumn();
$totalNotifs=(int)$pdo->query("SELECT COUNT(*) FROM notification WHERE statut='non lu'")->fetchColumn();
$byStatus=$pdo->query("SELECT statut,COUNT(*) cnt FROM document GROUP BY statut")->fetchAll();
$byCat=$pdo->query("SELECT c.nom_categorie lbl,COUNT(d.id_doc) cnt FROM categorie c LEFT JOIN document d ON d.categorie_id=c.id_categorie GROUP BY c.id_categorie")->fetchAll();
$byRole=$pdo->query("SELECT r.nom_role lbl,COUNT(u.id_user) cnt FROM roles r LEFT JOIN user u ON u.id_role=r.id_role GROUP BY r.id_role")->fetchAll();
$monthly=$pdo->query("SELECT DATE_FORMAT(date,'%b %Y') mo,COUNT(*) cnt FROM document WHERE date>=DATE_SUB(NOW(),INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(date,'%Y-%m') ORDER BY date")->fetchAll();
$top=$pdo->query("SELECT u.nom_user,r.nom_role,COUNT(d.id_doc) cnt FROM user u JOIN roles r ON u.id_role=r.id_role LEFT JOIN document d ON d.expediteur_id=u.id_user GROUP BY u.id_user ORDER BY cnt DESC LIMIT 5")->fetchAll();
?><!DOCTYPE html><html lang="fr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Statistiques</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css"><script>const BASE_URL='<?= BASE_URL ?>';</script></head>
<body class="<?= $css ?>"><?php include ROOT_PATH.'/views/partials/sidebar.php';include ROOT_PATH.'/views/partials/navbar.php'; ?>
<main class="app-main">
    <div class="page-header"><div><div class="page-title"><i class="fas fa-chart-pie"></i> Statistiques</div><div class="page-sub">Vue d'ensemble du système</div></div></div>

    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
        <div class="stat-card"><div class="stat-icon" style="background:#dbeafe;color:#1d4ed8"><i class="fas fa-file-alt"></i></div><div class="stat-val"><?= $totalDocs ?></div><div class="stat-lbl">Total documents</div></div>
        <div class="stat-card"><div class="stat-icon" style="background:#eff6ff;color:#1e40af"><i class="fas fa-users"></i></div><div class="stat-val"><?= $totalUsers ?></div><div class="stat-lbl">Utilisateurs actifs</div></div>
        <div class="stat-card"><div class="stat-icon" style="background:#dcfce7;color:#16a34a"><i class="fas fa-pen-nib"></i></div><div class="stat-val"><?= $totalSigs ?></div><div class="stat-lbl">Signatures totales</div></div>
        <div class="stat-card"><div class="stat-icon" style="background:#fef9c3;color:#a16207"><i class="fas fa-bell"></i></div><div class="stat-val"><?= $totalNotifs ?></div><div class="stat-lbl">Notifs non lues</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:22px">
        <div class="chart-wrap"><div class="chart-title"><i class="fas fa-chart-pie"></i> Par statut</div><div style="height:200px"><canvas id="c1"></canvas></div></div>
        <div class="chart-wrap"><div class="chart-title"><i class="fas fa-tags"></i> Par catégorie</div><div style="height:200px"><canvas id="c2"></canvas></div></div>
        <div class="chart-wrap"><div class="chart-title"><i class="fas fa-users"></i> Utilisateurs par rôle</div><div style="height:200px"><canvas id="c3"></canvas></div></div>
    </div>

    <div class="chart-wrap" style="margin-bottom:22px"><div class="chart-title"><i class="fas fa-chart-line"></i> Activité mensuelle</div><div style="height:200px"><canvas id="c4"></canvas></div></div>

    <div class="data-card">
        <div class="data-card-head"><div class="data-card-title"><i class="fas fa-trophy"></i> Top contributeurs</div></div>
        <div class="no-pad"><table class="tbl"><thead><tr><th>Rang</th><th>Utilisateur</th><th>Rôle</th><th>Documents</th><th>Progression</th></tr></thead>
        <tbody><?php foreach($top as $i=>$u):
            $rc=roleColor($u['nom_role']);
            $max=max(array_column($top,'cnt'))?:1;$pct=$u['cnt']/$max*100;
            $medals=['🥇','🥈','🥉'];
        ?>
        <tr>
            <td style="font-size:1.2rem"><?= $medals[$i]??('#'.($i+1)) ?></td>
            <td><div style="display:flex;align-items:center;gap:9px">
                <div style="width:32px;height:32px;border-radius:50%;;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem"><?= initiales($u['nom_user']) ?></div>
                <span style="font-weight:600;font-size:.85rem"><?= htmlspecialchars($u['nom_user']) ?></span>
            </div></td>
            <td><span style="padding:3px 10px;border-radius:20px;font-size:.74rem;font-weight:700"><?= htmlspecialchars($u['nom_role']) ?></span></td>
            <td style="font-weight:700;color:var(--a);font-size:1rem"><?= $u['cnt'] ?></td>
            <td style="min-width:160px"><div style="background:#f1f5f9;border-radius:20px;height:7px;overflow:hidden"><div style="width:<?= $pct ?>%;height:100%;background:linear-gradient(90deg,var(--p),var(--a));border-radius:20px"></div></div></td>
        </tr><?php endforeach; ?></tbody></table></div>
    </div>
</main>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
document.getElementById('pageTitle').textContent='Statistiques';
const pal=['#3b82f6','#22c55e','#eab308','#ef4444','#a855f7','#14b8a6','#f97316'];
const opts={responsive:true,maintainAspectRatio:false};
new Chart(document.getElementById('c1'),{type:'doughnut',data:{labels:<?= json_encode(array_column($byStatus,'statut')) ?>,datasets:[{data:<?= json_encode(array_column($byStatus,'cnt')) ?>,backgroundColor:pal,borderWidth:0,hoverOffset:5}]},options:{...opts,cutout:'62%',plugins:{legend:{position:'bottom',labels:{font:{family:'Outfit'},padding:10,usePointStyle:true,boxWidth:8}}}}});
new Chart(document.getElementById('c2'),{type:'bar',data:{labels:<?= json_encode(array_column($byCat,'lbl')) ?>,datasets:[{data:<?= json_encode(array_column($byCat,'cnt')) ?>,backgroundColor:pal.map(c=>c+'aa'),borderColor:pal,borderWidth:2,borderRadius:6}]},options:{...opts,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'#f1f5f9'},ticks:{font:{family:'Outfit'}}},x:{grid:{display:false},ticks:{font:{family:'Outfit'},maxRotation:30}}}}});
new Chart(document.getElementById('c3'),{type:'polarArea',data:{labels:<?= json_encode(array_column($byRole,'lbl')) ?>,datasets:[{data:<?= json_encode(array_column($byRole,'cnt')) ?>,backgroundColor:pal.map(c=>c+'99')}]},options:{...opts,plugins:{legend:{position:'bottom',labels:{font:{family:'Outfit'},padding:8,usePointStyle:true,boxWidth:8}}}}});
new Chart(document.getElementById('c4'),{type:'line',data:{labels:<?= json_encode(array_column($monthly,'mo')) ?>,datasets:[{label:'Documents',data:<?= json_encode(array_column($monthly,'cnt')) ?>,borderColor:'#3b82f6',backgroundColor:'#3b82f622',fill:true,tension:.4,pointRadius:4,pointBackgroundColor:'#3b82f6'}]},options:{...opts,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'#f1f5f9'},ticks:{font:{family:'Outfit'}}},x:{grid:{color:'#f8fafc'},ticks:{font:{family:'Outfit'}}}}}});
</script>
</body></html>
