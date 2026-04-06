<?php
require_once __DIR__ . '/../../config/init.php';
requireLogin();
if (!in_array(getUserRole(),['Administrateur','Intendant'])) { header('Location: '.BASE_URL.'/views/dashboard.php'); exit; }
$user   = getUser();
$css    = roleCss($user['nom_role']);
$pdo    = getDB();
$users  = $pdo->query("SELECT u.*,r.nom_role FROM user u JOIN roles r ON u.id_role=r.id_role ORDER BY u.date_creation DESC")->fetchAll();
$roles  = $pdo->query("SELECT * FROM roles")->fetchAll();
$unread = getUnreadCount($user['id_user']);
$ok  = $_SESSION['usr_ok']??null;  unset($_SESSION['usr_ok']);
$err = $_SESSION['usr_err']??null; unset($_SESSION['usr_err']);
$roleColors=['Administrateur'=>['#eff6ff','#1d4ed8'],'Secrétaire'=>['#ecfdf5','#065f46'],'Intendant'=>['#fff7ed','#9a3412'],'Enseignant'=>['#f0fdfa','#0f766e']];
?>

<!DOCTYPE html>
<html lang="fr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Utilisateurs</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<script>const BASE_URL='<?= BASE_URL ?>';</script>
</head>
<body class="<?= $css ?>">
<?php include ROOT_PATH.'/views/partials/sidebar.php'; include ROOT_PATH.'/views/partials/navbar.php'; ?>
<main class="app-main">
    <div class="page-header">
        <div>
            <div class="page-title"><i class="fas fa-users"></i> Utilisateurs</div>
            <div class="page-sub"><?= count($users) ?> utilisateur(s) dans le système</div>
        </div>
        <?php if ($user['nom_role']==='Administrateur'): ?>
        <a href="<?= BASE_URL ?>/views/utilisateurs/ajouter.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Ajouter</a>
        <?php endif; ?>
    </div>

    <?php if ($ok): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-error"><i class="fas fa-times-circle"></i><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <!-- Stats by role -->
    <div class="stats-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:22px">
        <?php foreach ($roles as $r):
            $cnt=count(array_filter($users,fn($u)=>$u['nom_role']===$r['nom_role']));
            $rc=roleColor($r['nom_role']);
        ?>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef9c3;>"><i class="fas <?= roleIcon($r['nom_role']) ?>"></i></div>
            <div class="stat-val"><?= $cnt ?></div>
            <div class="stat-lbl"><?= htmlspecialchars($r['nom_role']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="data-card">
        <div class="data-card-head">
            <div class="data-card-title"><i class="fas fa-list"></i> Liste des utilisateurs</div>
            <div class="search-bar" style="min-width:220px"><i class="fas fa-search"></i><input type="text" placeholder="Rechercher…" data-search="userTbl"></div>
        </div>
        <div class="no-pad">
            <table class="tbl" id="userTbl">
                <thead><tr><th>#</th><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Créé le</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $u):
                        $ui=initiales($u['nom_user']);
                        $uc=roleColor($u['nom_role']);
                        $ph=photoUrl($u['photo']??'');
                    ?>
                    <tr>
                        <td style="color:#94a3b8;font-size:.75rem"><?= str_pad($u['id_user'],4,'0',STR_PAD_LEFT) ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:36px;height:36px;border-radius:50%;;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;overflow:hidden;flex-shrink:0">
                                    <?php if ($ph): ?><img src="<?= $ph ?>" style="width:100%;height:100%;object-fit:cover" alt=""><?php else: ?><?= $ui ?><?php endif; ?>
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:.85rem"><?= htmlspecialchars($u['nom_user']) ?></div>
                                    <?php if ($u['id_user']==$user['id_user']): ?><div style="font-size:.7rem;color:var(--a)">Vous</div><?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td style="font-size:.82rem;color:#64748b"><?= htmlspecialchars($u['email']) ?></td>
                        <td><span style="padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700"><i class="fas <?= roleIcon($u['nom_role']) ?>" style="margin-right:4px"></i><?= htmlspecialchars($u['nom_role']) ?></span></td>
                        <td>
                            <?php if ($user['nom_role']==='Administrateur'&&$u['id_user']!=$user['id_user']): ?>
                            <a href="<?= BASE_URL ?>/controllers/UserController.php?action=toggle&id=<?= $u['id_user'] ?>" style="text-decoration:none">
                            <?php endif; ?>
                            <span class="badge <?= $u['statut']==='actif'?'b-actif':'b-inactif' ?>"><?= $u['statut'] ?></span>
                            <?php if ($user['nom_role']==='Administrateur'&&$u['id_user']!=$user['id_user']): ?></a><?php endif; ?>
                        </td>
                        <td style="font-size:.78rem;color:#94a3b8"><?= date('d/m/Y',strtotime($u['date_creation'])) ?></td>
                        <td>
                            <div style="display:flex;gap:5px">
                                <a href="<?= BASE_URL ?>/views/utilisateurs/modifier.php?id=<?= $u['id_user'] ?>" class="btn btn-warn btn-sm" title="Modifier"><i class="fas fa-edit"></i></a>
                                <?php if ($user['nom_role']==='Administrateur'&&$u['id_user']!=$user['id_user']): ?>
                                <button onclick="confirmDel('<?= BASE_URL ?>/controllers/UserController.php?action=supprimer&id=<?= $u['id_user'] ?>','Supprimer cet utilisateur ?')" class="btn btn-danger btn-sm" title="Supprimer"><i class="fas fa-trash"></i></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>document.getElementById('pageTitle').textContent='Utilisateurs';</script>
</body></html>
