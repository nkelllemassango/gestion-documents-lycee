<?php
require_once __DIR__ . '/../../config/init.php';
requireLogin();
if(!in_array(getUserRole(),['Administrateur','Secrétaire'])){header('Location: '.BASE_URL.'/views/dashboard.php');exit;}
$user=getUser();$css=roleCss($user['nom_role']);$pdo=getDB();$unread=getUnreadCount($user['id_user']);
$cats=$pdo->query("SELECT c.*,COUNT(d.id_doc) nb FROM categorie c LEFT JOIN document d ON d.categorie_id=c.id_categorie GROUP BY c.id_categorie ORDER BY c.nom_categorie")->fetchAll();
$ok=$_SESSION['cat_ok']??null;unset($_SESSION['cat_ok']);
$err=$_SESSION['cat_err']??null;unset($_SESSION['cat_err']);
$icons=['Administratif'=>'fa-building','Pédagogique'=>'fa-book','Financier'=>'fa-chart-line','Circulaire'=>'fa-bullhorn','Rapport'=>'fa-clipboard-list'];
$palette=['#3b82f6','#10b981','#f97316','#8b5cf6','#14b8a6','#ef4444','#eab308','#06b6d4'];
?><!DOCTYPE html>
<html lang="fr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Catégories</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<script>const BASE_URL='<?= BASE_URL ?>';</script>
</head><body class="<?= $css ?>">
<?php include ROOT_PATH.'/views/partials/sidebar.php';include ROOT_PATH.'/views/partials/navbar.php'; ?>
<main class="app-main">
    <div class="page-header">
        <div><div class="page-title"><i class="fas fa-tags"></i> Catégories</div><div class="page-sub"><?= count($cats) ?> catégorie(s)</div></div>
        <button onclick="document.getElementById('addModal').classList.add('open')" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter</button>
    </div>

    <?php if($ok): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if($err): ?><div class="alert alert-error"><i class="fas fa-times-circle"></i><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:18px">
        <?php foreach($cats as $i=>$c):
            $color=$palette[$i%count($palette)];
            $ic=$icons[$c['nom_categorie']]??'fa-folder';
        ?>
        <div class="data-card" style="overflow:visible">
            <div style="background:<?= $color ?>;padding:26px 20px;text-align:center;border-radius:var(--r) var(--r) 0 0">
                <div style="width:54px;height:54px;background:rgba(255,255,255,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:1.3rem;color:#fff"><i class="fas <?= $ic ?>"></i></div>
                <div style="color:#fff;font-weight:700;font-size:.95rem"><?= htmlspecialchars($c['nom_categorie']) ?></div>
                <div style="color:rgba(255,255,255,.7);font-size:.77rem;margin-top:4px"><?= $c['nb'] ?> document(s)</div>
            </div>
            <div style="padding:14px 16px;display:flex;gap:8px;justify-content:center">
                <button onclick="editCat(<?= $c['id_categorie'] ?>,'<?= htmlspecialchars(addslashes($c['nom_categorie'])) ?>')" class="btn btn-warn btn-sm"><i class="fas fa-edit"></i> Modifier</button>
                <?php if($c['nb']==0): ?>
                <button onclick="confirmDel('<?= BASE_URL ?>/controllers/CategorieController.php?action=supprimer&id=<?= $c['id_categorie'] ?>','Supprimer cette catégorie ?')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<!-- Add modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <div class="modal-head"><h5><i class="fas fa-plus" style="margin-right:8px"></i>Nouvelle catégorie</h5><button class="modal-close" onclick="document.getElementById('addModal').classList.remove('open')">×</button></div>
        <form method="POST" action="<?= BASE_URL ?>/controllers/CategorieController.php?action=ajouter" class="modal-body">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="form-group"><label class="form-label">Nom *</label><input type="text" name="nom" class="form-control" placeholder="Ex: Administratif, Pédagogique…" required></div>
            <div class="modal-foot"><button type="button" onclick="document.getElementById('addModal').classList.remove('open')" class="btn btn-ghost">Annuler</button><button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Créer</button></div>
        </form>
    </div>
</div>

<!-- Edit modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <div class="modal-head"><h5><i class="fas fa-edit" style="margin-right:8px"></i>Modifier la catégorie</h5><button class="modal-close" onclick="document.getElementById('editModal').classList.remove('open')">×</button></div>
        <form method="POST" action="<?= BASE_URL ?>/controllers/CategorieController.php?action=modifier" class="modal-body">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" id="editCatId">
            <div class="form-group"><label class="form-label">Nom *</label><input type="text" name="nom" id="editCatNom" class="form-control" required></div>
            <div class="modal-foot"><button type="button" onclick="document.getElementById('editModal').classList.remove('open')" class="btn btn-ghost">Annuler</button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button></div>
        </form>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
document.getElementById('pageTitle').textContent='Catégories';
function editCat(id,nom){document.getElementById('editCatId').value=id;document.getElementById('editCatNom').value=nom;document.getElementById('editModal').classList.add('open');}
</script>
</body></html>
