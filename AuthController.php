<?php
require_once __DIR__ . '/../config/init.php';

$action = $_GET['action'] ?? '';
match($action) {
    'login'    => handleLogin(),
    'logout'   => handleLogout(),
    'register' => handleRegister(),
    default    => redirect('/views/login.php')
};

/* ══ LOGIN ══════════════════════════════════════════════════ */
function handleLogin(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('/views/login.php'); return; }

    // CSRF
    $tok = $_POST['csrf_token'] ?? '';
    $ses = $_SESSION['csrf_token'] ?? '';
    if (!$ses || !$tok || !hash_equals($ses, $tok)) {
        flash('login_error', 'Session expirée, réessayez.'); redirect('/views/login.php'); return;
    }
    unset($_SESSION['csrf_token']);

    $email = trim(strtolower($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';

    if (!$email || !$pass) {
        flash('login_error', 'Remplissez tous les champs.'); redirect('/views/login.php'); return;
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare(
        "SELECT u.*, r.nom_role FROM user u
         JOIN roles r ON u.id_role = r.id_role
         WHERE LOWER(u.email) = ? LIMIT 1"
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        flash('login_error', 'Aucun compte trouvé avec cet email.'); redirect('/views/login.php'); return;
    }
    if ($user['statut'] !== 'actif') {
        flash('login_error', 'Compte désactivé. Contactez l\'administrateur.'); redirect('/views/login.php'); return;
    }

    $hash = $user['password'];
    $isH  = str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$');
    $ok   = $isH ? password_verify($pass, $hash) : ($pass === $hash);

    // Migration clair → bcrypt
    if ($ok && !$isH) {
        $nh = password_hash($pass, PASSWORD_BCRYPT, ['cost'=>12]);
        $pdo->prepare("UPDATE user SET password=? WHERE id_user=?")->execute([$nh, $user['id_user']]);
    }

    if (!$ok) {
        flash('login_error', 'Mot de passe incorrect.'); redirect('/views/login.php'); return;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id_user'];
    $_SESSION['role']    = $user['nom_role'];
    $_SESSION['nom']     = $user['nom_user'];

    redirect('/views/dashboard.php');
}

/* ══ LOGOUT ═════════════════════════════════════════════════ */
function handleLogout(): void {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    session_destroy();
    redirect('/views/login.php');
}

/* ══ REGISTER (ADMIN ONLY) ═══════════════════════════════════ */
function handleRegister(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('/views/inscription.php'); return; }

    $tok = $_POST['csrf_token'] ?? '';
    $ses = $_SESSION['csrf_token'] ?? '';
    if (!$ses || !$tok || !hash_equals($ses, $tok)) {
        flash('reg_error', 'Session expirée, réessayez.'); redirect('/views/inscription.php'); return;
    }
    unset($_SESSION['csrf_token']);

    $nom     = trim($_POST['nom'] ?? '');
    $email   = trim(strtolower($_POST['email'] ?? ''));
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (!$nom || !$email || !$pass || !$confirm) {
        flash('reg_error', 'Tous les champs sont obligatoires.'); redirect('/views/inscription.php'); return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('reg_error', 'Email invalide.'); redirect('/views/inscription.php'); return;
    }
    if ($pass !== $confirm) {
        flash('reg_error', 'Les mots de passe ne correspondent pas.'); redirect('/views/inscription.php'); return;
    }
    if (strlen($pass) < 8) {
        flash('reg_error', 'Mot de passe : 8 caractères minimum.'); redirect('/views/inscription.php'); return;
    }

    $pdo = getDB();

    // Un seul admin autorisé
    $exists = $pdo->query(
        "SELECT COUNT(*) FROM user u JOIN roles r ON u.id_role=r.id_role WHERE r.nom_role='Administrateur'"
    )->fetchColumn();
    if ($exists > 0) {
        flash('login_error', 'Un administrateur existe déjà. Connectez-vous.'); redirect('/views/login.php'); return;
    }

    // Email unique
    if ($pdo->prepare("SELECT COUNT(*) FROM user WHERE LOWER(email)=?")->execute([$email]) &&
        $pdo->prepare("SELECT COUNT(*) FROM user WHERE LOWER(email)=?")->fetchColumn() > 0) {}
    $s = $pdo->prepare("SELECT COUNT(*) FROM user WHERE LOWER(email)=?"); $s->execute([$email]);
    if ($s->fetchColumn() > 0) {
        flash('reg_error', 'Cet email est déjà utilisé.'); redirect('/views/inscription.php'); return;
    }

    // S'assurer que les rôles existent
    $pdo->exec("INSERT IGNORE INTO roles (nom_role) VALUES ('Administrateur'),('Secrétaire'),('Censeur'),('Intendant'),('Enseignant')");

    $role = $pdo->query("SELECT id_role FROM roles WHERE nom_role='Administrateur' LIMIT 1")->fetch();
    if (!$role) {
        flash('reg_error', 'Erreur interne : rôle introuvable.'); redirect('/views/inscription.php'); return;
    }

    $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost'=>12]);
    $pdo->prepare("INSERT INTO user (nom_user,email,password,id_role,statut) VALUES (?,?,?,?,'actif')")
        ->execute([$nom, $email, $hash, $role['id_role']]);

    flash('login_success', '✅ Inscription réussie ! Connectez-vous maintenant.');
    redirect('/views/login.php');
}

/* ── helpers ────────────────────────────────────────────────── */
function flash(string $k, string $v): void { $_SESSION[$k] = $v; }
function redirect(string $path): void { header('Location: '.BASE_URL.$path); exit; }
