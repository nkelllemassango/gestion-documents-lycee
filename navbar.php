<?php
// views/partials/navbar.php
// Nécessite que $user et $unread soient définis par la page parente
$user   ??= getUser();
$unread ??= $user ? getUnreadCount($user['id_user']) : 0;
$init   = $user ? initiales($user['nom_user']) : '?';
$photo  = $user ? photoUrl($user['photo'] ?? '') : '';
$role   = $user['nom_role'] ?? '';
$prenom = $user ? explode(' ', $user['nom_user'])[0] : '';
?>
<nav class="app-navbar" id="appNavbar">
    <div class="nav-left">
        <button class="sidebar-toggle" id="sidebarToggle" title="Réduire/Étendre">
            <i class="fas fa-bars"></i>
        </button>
        <div class="breadcrumb-nav">
            <i class="fas fa-home" style="color:#cbd5e1;font-size:.75rem"></i>
            <span style="margin:0 5px;color:#e2e8f0">/</span>
            <strong id="pageTitle">Tableau de bord</strong>
        </div>
    </div>

    <div class="nav-right">
        <!-- NOTIFICATIONS -->
        <div class="notif-wrap">
            <button class="notif-btn" id="notifBtn" title="Notifications">
                <i class="fas fa-bell"></i>
                <span class="notif-count-badge" id="notifBadge"
                      style="display:<?= $unread > 0 ? 'flex' : 'none' ?>">
                    <?= $unread ?>
                </span>
            </button>

            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-dd-head">
                    <h6><i class="fas fa-bell" style="color:var(--a);margin-right:6px"></i>Notifications</h6>
                    <button onclick="markAllRead()">Tout lire</button>
                </div>
                <div id="notifList" style="max-height:300px;overflow-y:auto">
                    <div class="empty-state" style="padding:20px">
                        <i class="fas fa-spinner fa-spin"></i><p>Chargement…</p>
                    </div>
                </div>
                <div class="notif-dd-foot">
                    <a href="<?= BASE_URL ?>/views/notifications.php">Voir toutes →</a>
                </div>
            </div>
        </div>

        <!-- USER MENU -->
        <div class="user-menu-wrap">
            <div class="user-nav-btn" id="userMenuBtn" title="Mon compte">
                <div class="user-nav-avatar" style="position:relative">
                    <?php if ($photo): ?>
                        <img src="<?= htmlspecialchars($photo) ?>" alt="">
                    <?php else: ?><?= $init ?><?php endif; ?>
                </div>
                <span class="online-dot"></span>
                <div class="user-nav-info">
                    <div class="welcome">Bienvenue,</div>
                    <div class="uname"><?= htmlspecialchars($prenom) ?></div>
                </div>
                <span class="role-chip-nav"><?= htmlspecialchars($role) ?></span>
                <i class="fas fa-chevron-down" style="font-size:.65rem;color:#94a3b8;margin-left:2px"></i>
            </div>

            <div class="user-dropdown" id="userDropdown">
                <div class="ud-header">
                    <div class="ud-avatar">
                        <?php if ($photo): ?><img src="<?= htmlspecialchars($photo) ?>" alt=""><?php else: ?><?= $init ?><?php endif; ?>
                    </div>
                    <div class="ud-name"><?= htmlspecialchars($user['nom_user'] ?? '') ?></div>
                    <div class="ud-role">
                        <i class="fas <?= roleIcon($role) ?>" style="margin-right:4px"></i>
                        <?= htmlspecialchars($role) ?>
                    </div>
                </div>
                <div class="ud-menu">
                    <a href="<?= BASE_URL ?>/views/profil/index.php?tab=apercu">
                        <i class="fas fa-eye"></i> Aperçu du profil
                    </a>
                    <a href="<?= BASE_URL ?>/views/profil/index.php?tab=password">
                        <i class="fas fa-lock"></i> Changer mot de passe
                    </a>
                    <a href="<?= BASE_URL ?>/views/profil/index.php?tab=edit">
                        <i class="fas fa-edit"></i> Modifier le profil
                    </a>
                    <a href="<?= BASE_URL ?>/views/profil/index.php?tab=settings">
                        <i class="fas fa-cog"></i> Paramètres
                    </a>
                    <hr>
                    <a href="<?= BASE_URL ?>/controllers/AuthController.php?action=logout" class="logout-lnk">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>
