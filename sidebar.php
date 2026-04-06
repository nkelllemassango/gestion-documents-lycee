<?php
// views/partials/sidebar.php
$user  ??= getUser();
$role  = $user['nom_role'] ?? '';
$init  = $user ? initiales($user['nom_user']) : '?';
$photo = $user ? photoUrl($user['photo'] ?? '') : '';
$unread= $user ? getUnreadCount($user['id_user']) : 0;
?>
<aside class="sidebar" id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon"><i class="fas fa-graduation-cap"></i></div>
        <div class="sidebar-brand-name">
            GestDoc<br>
            <span class="sidebar-brand-sub">Lycée Bilingue Bonaberi</span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <!-- Principal (tous les rôles) -->
        <div class="nav-section-label">Principal</div>
        <a href="<?= BASE_URL ?>/views/dashboard.php" class="nav-link">
            <i class="fas fa-th-large ni"></i><span>Tableau de bord</span>
        </a>

        <?php if ($role === 'Administrateur'): ?>
        <!-- ADMIN -->
        <div class="nav-section-label">Administration</div>
        <a href="<?= BASE_URL ?>/views/utilisateurs/index.php" class="nav-link">
            <i class="fas fa-users ni"></i><span>Utilisateurs</span>
        </a>
        <a href="<?= BASE_URL ?>/views/utilisateurs/ajouter.php" class="nav-link">
            <i class="fas fa-user-plus ni"></i><span>Ajouter un utilisateur</span>
        </a>
        <a href="<?= BASE_URL ?>/views/utilisateurs/roles.php" class="nav-link">
            <i class="fas fa-user-shield ni"></i><span>Rôles & Accès</span>
        </a>

        <div class="nav-section-label">Documents</div>
        <a href="<?= BASE_URL ?>/views/documents/index.php" class="nav-link">
            <i class="fas fa-file-alt ni"></i><span>Tous les documents</span>
        </a>
        <a href="<?= BASE_URL ?>/views/documents/ajouter.php" class="nav-link">
            <i class="fas fa-plus-circle ni"></i><span>Nouveau document</span>
        </a>
        <a href="<?= BASE_URL ?>/views/documents/a_signer.php" class="nav-link">
            <i class="fas fa-pen-nib ni"></i><span>À signer</span>
            <?php if ($pend = getPendingCount()): ?><span class="nav-badge danger"><?= $pend ?></span><?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/views/documents/archives.php" class="nav-link">
            <i class="fas fa-archive ni"></i><span>Archives</span>
        </a>
        <a href="<?= BASE_URL ?>/views/categories/index.php" class="nav-link">
            <i class="fas fa-tags ni"></i><span>Catégories</span>
        </a>

        <div class="nav-section-label">Suivi</div>
        <a href="<?= BASE_URL ?>/views/notifications.php" class="nav-link">
            <i class="fas fa-bell ni"></i><span>Notifications</span>
            <?php if ($unread): ?><span class="nav-badge danger"><?= $unread ?></span><?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/views/statistiques.php" class="nav-link">
            <i class="fas fa-chart-pie ni"></i><span>Statistiques</span>
        </a>

        <?php elseif ($role === 'Secrétaire'): ?>
        <!-- SECRÉTAIRE -->
        <div class="nav-section-label">Documents</div>
        <a href="<?= BASE_URL ?>/views/documents/index.php" class="nav-link">
            <i class="fas fa-file-alt ni"></i><span>Mes documents</span>
        </a>
        <a href="<?= BASE_URL ?>/views/documents/ajouter.php" class="nav-link">
            <i class="fas fa-plus-circle ni"></i><span>Nouveau document</span>
        </a>
        <a href="<?= BASE_URL ?>/views/documents/envoyes.php" class="nav-link">
            <i class="fas fa-paper-plane ni"></i><span>Envoyés</span>
        </a>
        <a href="<?= BASE_URL ?>/views/documents/recus.php" class="nav-link">
            <i class="fas fa-inbox ni"></i><span>Reçus</span>
        </a>
        <a href="<?= BASE_URL ?>/views/documents/signes.php" class="nav-link">
            <i class="fas fa-check-circle ni"></i><span>Signés</span>
        </a>
        <div class="nav-section-label">Gestion</div>
        <a href="<?= BASE_URL ?>/views/categories/index.php" class="nav-link">
            <i class="fas fa-tags ni"></i><span>Catégories</span>
        </a>
        <div class="nav-section-label">Suivi</div>
        <a href="<?= BASE_URL ?>/views/notifications.php" class="nav-link">
            <i class="fas fa-bell ni"></i><span>Notifications</span>
            <?php if ($unread): ?><span class="nav-badge danger"><?= $unread ?></span><?php endif; ?>
        </a>

        <?php elseif ($role === 'Censeur'): ?>
        <!-- CENSEUR -->
        <div class="nav-section-label">Pédagogie</div>
        <a href="<?= BASE_URL ?>/views/documents/index.php" class="nav-link">
            <i class="fas fa-book ni"></i><span>Documents pédag.</span>
        </a>
        <a href="<?= BASE_URL ?>/views/documents/ajouter.php" class="nav-link">
            <i class="fas fa-plus-circle ni"></i><span>Nouveau document</span>
        </a>
        <a href="<?= BASE_URL ?>/views/documents/envoyes.php" class="nav-link">
            <i class="fas fa-paper-plane ni"></i><span>Envoyés</span>
        </a>
        <a href="<?= BASE_URL ?>/views/documents/recus.php" class="nav-link">
            <i class="fas fa-inbox ni"></i><span>Reçus</span>
        </a>
        <div class="nav-section-label">Suivi</div>
        <a href="<?= BASE_URL ?>/views/notifications.php" class="nav-link">
            <i class="fas fa-bell ni"></i><span>Notifications</span>
            <?php if ($unread): ?><span class="nav-badge danger"><?= $unread ?></span><?php endif; ?>
        </a>

        <?php elseif ($role === 'Intendant'): ?>
        <!-- INTENDANT -->
        <div class="nav-section-label">Administratif</div>
        <a href="<?= BASE_URL ?>/views/documents/index.php" class="nav-link">
            <i class="fas fa-file-invoice ni"></i><span>Mes documents</span>
        </a>
        <a href="<?= BASE_URL ?>/views/documents/ajouter.php" class="nav-link">
            <i class="fas fa-plus-circle ni"></i><span>Nouveau document</span>
        </a>
        <a href="<?= BASE_URL ?>/views/documents/a_signer.php" class="nav-link">
            <i class="fas fa-pen-nib ni"></i><span>À signer</span>
        </a>
        <a href="<?= BASE_URL ?>/views/documents/archives.php" class="nav-link">
            <i class="fas fa-archive ni"></i><span>Archives</span>
        </a>
        <div class="nav-section-label">Utilisateurs</div>
        <a href="<?= BASE_URL ?>/views/utilisateurs/index.php" class="nav-link">
            <i class="fas fa-users ni"></i><span>Utilisateurs</span>
        </a>
        <div class="nav-section-label">Suivi</div>
        <a href="<?= BASE_URL ?>/views/notifications.php" class="nav-link">
            <i class="fas fa-bell ni"></i><span>Notifications</span>
            <?php if ($unread): ?><span class="nav-badge danger"><?= $unread ?></span><?php endif; ?>
        </a>

        <?php elseif ($role === 'Enseignant'): ?>
        <!-- ENSEIGNANT -->
        <div class="nav-section-label">Mes documents</div>
        <a href="<?= BASE_URL ?>/views/documents/index.php" class="nav-link">
            <i class="fas fa-file-alt ni"></i><span>Mes documents</span>
        </a>
        <a href="<?= BASE_URL ?>/views/documents/ajouter.php" class="nav-link">
            <i class="fas fa-plus-circle ni"></i><span>Nouveau document</span>
        </a>
        <a href="<?= BASE_URL ?>/views/documents/recus.php" class="nav-link">
            <i class="fas fa-inbox ni"></i><span>Reçus</span>
        </a>
        <a href="<?= BASE_URL ?>/views/documents/a_signer.php" class="nav-link">
            <i class="fas fa-pen-nib ni"></i><span>À signer</span>
        </a>
        <div class="nav-section-label">Suivi</div>
        <a href="<?= BASE_URL ?>/views/notifications.php" class="nav-link">
            <i class="fas fa-bell ni"></i><span>Notifications</span>
            <?php if ($unread): ?><span class="nav-badge danger"><?= $unread ?></span><?php endif; ?>
        </a>
        <?php endif; ?>

        <!-- Compte (tous) -->
        <div class="nav-section-label" style="margin-top:8px">Compte</div>
        <a href="<?= BASE_URL ?>/views/profil/index.php" class="nav-link">
            <i class="fas fa-user-circle ni"></i><span>Mon profil</span>
        </a>
    </nav>

    <!-- Footer sidebar -->
    <div class="sidebar-footer">
        <div class="sidebar-user-mini">
            <div class="mini-avatar">
                <?php if ($photo): ?><img src="<?= htmlspecialchars($photo) ?>" alt=""><?php else: ?><?= $init ?><?php endif; ?>
            </div>
            <div class="mini-info">
                <div class="mini-name"><?= htmlspecialchars(explode(' ', $user['nom_user'] ?? '')[0]) ?></div>
                <div class="mini-role"><?= htmlspecialchars($role) ?></div>
            </div>
            <a href="<?= BASE_URL ?>/controllers/AuthController.php?action=logout" class="mini-logout" title="Déconnexion">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>
<?php
function getPendingCount(): int {
    try {
        $s = getDB()->query("SELECT COUNT(*) FROM document WHERE statut IN('en attente','envoyé')");
        return (int)$s->fetchColumn();
    } catch (Exception $e) { return 0; }
}
?>
