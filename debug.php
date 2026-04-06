<?php
/**
 * DEBUG.PHP — Outil de diagnostic
 * URL : http://localhost/gestion_documents/debug.php
 * ⚠️ SUPPRIMER CE FICHIER après résolution du problème
 */
require_once __DIR__ . '/config/init.php';

// Action : reset mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['new_password']) && !empty($_POST['user_id'])) {
    $pdo  = getDB();
    $id   = (int)$_POST['user_id'];
    $hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->prepare("UPDATE user SET password=?, statut='actif' WHERE id_user=?")->execute([$hash, $id]);
    $msg_ok = "✅ Mot de passe réinitialisé avec succès !";
}

try {
    $pdo   = getDB();
    $users = $pdo->query("SELECT u.*,r.nom_role FROM user u JOIN roles r ON u.id_role=r.id_role ORDER BY u.id_user")->fetchAll();
    $roles = $pdo->query("SELECT * FROM roles")->fetchAll();
    $db_ok = true;
} catch (Exception $e) {
    $db_ok = false;
    $db_err = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Diagnostic GestDoc</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',sans-serif;background:#f5f7fa;padding:24px;color:#1e293b}
.box{background:#fff;border-radius:12px;padding:22px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,.07)}
h1{color:#0f172a;margin-bottom:6px;font-size:1.4rem}
h2{font-size:.9rem;font-weight:700;color:#334155;margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid #f1f5f9}
table{width:100%;border-collapse:collapse;font-size:.84rem}
th{background:#0f172a;color:#fff;padding:9px 12px;text-align:left;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px}
td{padding:10px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
tr:hover td{background:#f8fafc}
.ok{color:#16a34a;font-weight:700}
.bad{color:#dc2626;font-weight:700}
.warn{color:#d97706;font-weight:700}
.alert-ok{background:#dcfce7;border-left:4px solid #22c55e;padding:13px;border-radius:8px;color:#166534;font-weight:600;margin-bottom:18px}
.alert-err{background:#fee2e2;border-left:4px solid #ef4444;padding:13px;border-radius:8px;color:#dc2626;margin-bottom:18px}
.alert-info{background:#dbeafe;border-left:4px solid #3b82f6;padding:13px;border-radius:8px;color:#1e40af;margin-bottom:18px;font-size:.85rem}
input[type=text],input[type=password],input[type=email]{width:100%;padding:10px 12px;border:2px solid #e2e8f0;border-radius:8px;font-size:.88rem;font-family:inherit;margin-top:5px}
input:focus{border-color:#3b82f6;outline:none}
button,a.btn{display:inline-flex;align-items:center;gap:7px;padding:10px 22px;border-radius:8px;border:none;font-size:.88rem;font-weight:600;cursor:pointer;font-family:inherit;text-decoration:none}
.btn-primary{background:#0f172a;color:#fff}
.btn-primary:hover{background:#1e293b}
.btn-danger{background:#ef4444;color:#fff}
.btn-danger:hover{background:#dc2626}
.btn-green{background:#22c55e;color:#fff}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:18px}
label{font-size:.78rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.4px;display:block;margin-top:12px}
code{background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:.82rem}
</style>
</head>
<body>

<div class="box">
    <h1>🔍 Diagnostic GestDoc</h1>
    <p style="color:#64748b;font-size:.84rem">Lycée Bilingue de Bonaberi — Outil de vérification</p>
</div>

<?php if (!empty($msg_ok)): ?>
<div class="alert-ok"><?= $msg_ok ?> <a href="<?= BASE_URL ?>/views/login.php" style="margin-left:12px;color:#166534;font-weight:700">→ Aller se connecter</a></div>
<?php endif; ?>

<!-- 1. Chemins système -->
<div class="box">
    <h2>⚙️ Configuration système</h2>
    <div class="grid2">
        <table>
            <tr><th colspan="2">Chemins & URLs</th></tr>
            <tr><td>ROOT_PATH</td><td><code><?= ROOT_PATH ?></code></td></tr>
            <tr><td>BASE_URL</td><td><code><?= BASE_URL ?></code></td></tr>
            <tr><td>DOCUMENT_ROOT</td><td><code><?= $_SERVER['DOCUMENT_ROOT'] ?? 'N/A' ?></code></td></tr>
            <tr><td>PHP Version</td><td><?= PHP_VERSION ?></td></tr>
            <tr><td>Session active</td><td class="<?= session_status()===PHP_SESSION_ACTIVE?'ok':'bad' ?>"><?= session_status()===PHP_SESSION_ACTIVE?'✅ OUI':'❌ NON' ?></td></tr>
        </table>
        <table>
            <tr><th colspan="2">Base de données</th></tr>
            <tr><td>Connexion</td><td class="<?= $db_ok?'ok':'bad' ?>"><?= $db_ok?'✅ OK':'❌ '.(htmlspecialchars($db_err??'')) ?></td></tr>
            <?php if ($db_ok): ?>
            <tr><td>Hôte</td><td><?= DB_HOST ?></td></tr>
            <tr><td>Base</td><td><?= DB_NAME ?></td></tr>
            <tr><td>Utilisateur</td><td><?= DB_USER ?></td></tr>
            <tr><td>Rôles créés</td><td class="<?= count($roles)>0?'ok':'bad' ?>"><?= count($roles)>0?'✅ '.count($roles).' rôles':'❌ Aucun rôle' ?></td></tr>
            <?php endif; ?>
        </table>
    </div>
    <?php if (!$db_ok): ?>
    <div class="alert-err" style="margin-top:14px">
        ❌ Connexion MySQL échouée.<br>
        Vérifiez <code>DB_USER</code>, <code>DB_PASS</code>, <code>DB_NAME</code> dans <code>config/init.php</code>
        et importez <code>config/schema.sql</code> dans phpMyAdmin.
    </div>
    <?php endif; ?>
</div>

<?php if ($db_ok): ?>

<!-- 2. Utilisateurs -->
<div class="box">
    <h2>👤 Utilisateurs enregistrés</h2>
    <?php if (empty($users)): ?>
    <div class="alert-info">
        ℹ️ Aucun utilisateur en base. Inscrivez l'administrateur d'abord :<br>
        <a href="<?= BASE_URL ?>/views/inscription.php" class="btn btn-primary" style="margin-top:10px">→ Page d'inscription</a>
    </div>
    <?php else: ?>
    <table>
        <thead><tr><th>ID</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Hash type</th><th>Longueur</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u):
            $hash    = $u['password'];
            $isHash  = str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$');
            $hashLen = strlen($hash);
        ?>
        <tr>
            <td><?= $u['id_user'] ?></td>
            <td><strong><?= htmlspecialchars($u['nom_user']) ?></strong></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['nom_role']) ?></td>
            <td class="<?= $u['statut']==='actif'?'ok':'bad' ?>"><?= $u['statut']==='actif'?'✅ Actif':'❌ Inactif' ?></td>
            <td class="<?= $isHash?'ok':'bad' ?>"><?= $isHash?'✅ BCrypt':'❌ Non-BCrypt' ?></td>
            <td class="<?= $hashLen>=60?'ok':'bad' ?>"><?= $hashLen ?> chars</td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- 3. Test de connexion -->
<div class="box">
    <h2>🧪 Tester email + mot de passe</h2>
    <p style="font-size:.83rem;color:#64748b;margin-bottom:16px">Entrez les identifiants que vous utilisez pour vous connecter. Le système diagnostiquera le problème exact.</p>

    <form method="POST" id="testForm">
        <input type="hidden" name="action" value="test">
        <div class="grid2">
            <div>
                <label>Email</label>
                <input type="email" name="test_email" value="<?= htmlspecialchars($_POST['test_email'] ?? '') ?>" placeholder="votre@email.cm">
            </div>
            <div>
                <label>Mot de passe</label>
                <input type="password" name="test_pass" placeholder="Votre mot de passe">
            </div>
        </div>
        <button type="submit" name="action" value="test" class="btn btn-primary" style="margin-top:14px">🔍 Tester la connexion</button>
    </form>

    <?php
    if (isset($_POST['action']) && $_POST['action'] === 'test') {
        $tEmail = trim(strtolower($_POST['test_email'] ?? ''));
        $tPass  = $_POST['test_pass'] ?? '';

        $stmt = $pdo->prepare("SELECT u.*,r.nom_role FROM user u JOIN roles r ON u.id_role=r.id_role WHERE LOWER(u.email)=? LIMIT 1");
        $stmt->execute([$tEmail]);
        $tUser = $stmt->fetch();
        echo '<div style="margin-top:18px;padding:18px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0">';
        echo '<h3 style="font-size:.9rem;margin-bottom:14px;color:#0f172a">📋 Résultat du test :</h3>';

        if (!$tUser) {
            echo '<p class="bad">❌ Étape 1 échouée : Aucun utilisateur avec cet email.</p>';
            echo '<p style="font-size:.82rem;color:#64748b;margin-top:8px">Emails disponibles : ';
            foreach ($users as $u) echo '<code>'.htmlspecialchars($u['email']).'</code> ';
            echo '</p>';
        } else {
            echo '<p class="ok">✅ Étape 1 : Utilisateur trouvé → <strong>'.htmlspecialchars($tUser['nom_user']).'</strong> ('.htmlspecialchars($tUser['nom_role']).')</p>';
            if ($tUser['statut'] !== 'actif') {
                echo '<p class="bad" style="margin-top:8px">❌ Étape 2 : Compte INACTIF.</p>';
            } else {
                echo '<p class="ok" style="margin-top:8px">✅ Étape 2 : Compte actif.</p>';
                $h   = $tUser['password'];
                $isH = str_starts_with($h, '$2y$') || str_starts_with($h, '$2a$');
                $ok  = $isH ? password_verify($tPass, $h) : ($tPass === $h);

                if ($ok) {
                    echo '<p class="ok" style="margin-top:8px">✅ Étape 3 : Mot de passe CORRECT !</p>';
                    echo '<div class="alert-ok" style="margin-top:12px">🎉 Tout est correct. <a href="'.BASE_URL.'/views/login.php" style="color:#166534;font-weight:700">Cliquez ici pour vous connecter →</a></div>';
                } else {
                    echo '<p class="bad" style="margin-top:8px">❌ Étape 3 : Mot de passe INCORRECT.</p>';
                    if (!$isH) echo '<p class="warn" style="margin-top:6px">⚠️ Hash non-BCrypt détecté. Utilisez le formulaire ci-dessous pour réinitialiser.</p>';
                }
            }

            // Reset form
            echo '<div style="margin-top:18px;padding:16px;background:#eff6ff;border-radius:9px;border:1px dashed #93c5fd">';
            echo '<h4 style="font-size:.85rem;color:#1e40af;margin-bottom:10px">🔑 Réinitialiser le mot de passe de '.htmlspecialchars($tUser['nom_user']).'</h4>';
            echo '<form method="POST">';
            echo '<input type="hidden" name="user_id" value="'.$tUser['id_user'].'">';
            echo '<label>Nouveau mot de passe (min. 8 caractères)</label>';
            echo '<input type="password" name="new_password" placeholder="Nouveau mot de passe sécurisé" required minlength="8">';
            echo '<button type="submit" class="btn btn-danger" style="margin-top:10px">🔄 Réinitialiser</button>';
            echo '</form></div>';
        }
        echo '</div>';
    }
    ?>
</div>

<!-- 4. Actions rapides -->
<div class="box">
    <h2>🚀 Actions rapides</h2>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="<?= BASE_URL ?>/views/login.php" class="btn btn-primary">→ Page de connexion</a>
        <a href="<?= BASE_URL ?>/views/inscription.php" class="btn btn-green">→ Page d'inscription</a>
        <a href="<?= BASE_URL ?>/accueil.php" class="btn btn-primary" style="background:#6366f1">→ Page d'accueil</a>
    </div>
    <div class="alert-info" style="margin-top:16px">
        ⚠️ <strong>Supprimez ce fichier <code>debug.php</code></strong> une fois le problème résolu. Il expose des informations sensibles.
    </div>
</div>

<?php endif; ?>
</body>
</html>
