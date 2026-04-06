<?php
/**
 * INIT.PHP — Configuration centrale
 * Inclure en premier dans TOUTES les pages PHP
 */

// ── SESSION ──────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

// ── CHEMINS ───────────────────────────────────────────────────
// __DIR__ = /…/gestion_documents/config
// dirname(__DIR__) = /…/gestion_documents  ← racine projet
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));

// ── BASE_URL dynamique ────────────────────────────────────────
if (!defined('BASE_URL')) {
    $proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $rootP   = str_replace('\\','/', ROOT_PATH);
    $docRoot = rtrim(str_replace('\\','/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    if ($docRoot && str_starts_with($rootP, $docRoot)) {
        $web = substr($rootP, strlen($docRoot));
    } else {
        // fallback: chercher le nom du dossier dans SCRIPT_NAME
        $script = str_replace('\\','/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $proj   = basename($rootP);
        $parts  = explode('/', trim($script, '/'));
        $idx    = array_search($proj, $parts);
        $web    = $idx !== false ? '/'.implode('/', array_slice($parts,0,$idx+1)) : '/'.array_shift($parts);
    }
    define('BASE_URL', $proto.'://'.$host.rtrim($web,'/'));
}

// ── BASE DE DONNÉES ───────────────────────────────────────────
// ⚠️  Adaptez DB_USER / DB_PASS à votre serveur local
if (!defined('DB_HOST')) define('DB_HOST','localhost');
if (!defined('DB_USER')) define('DB_USER','root');
if (!defined('DB_PASS')) define('DB_PASS','');
if (!defined('DB_NAME')) define('DB_NAME','gestion_documents');
define('SMS_PROVIDER',     'orange');
define('SMS_ORANGE_TOKEN', 'ton_token_bearer');  // depuis developer.orange.com
define('SMS_ORANGE_FROM',  '+237600000000');     // ton numéro Orange

function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        $pdo = new PDO(
            "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES=>false,
             PDO::MYSQL_ATTR_INIT_COMMAND=>"SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"]
        );
    } catch (PDOException $e) {
        $m = htmlspecialchars($e->getMessage());
        die("<div style='font-family:sans-serif;padding:30px;background:#fff3cd;border-left:4px solid #ff9800;border-radius:8px;margin:20px'>
            <h3>⚠️ Erreur connexion MySQL</h3><p>$m</p>
            <p style='font-size:.85rem;color:#666'>Vérifiez DB_USER/DB_PASS dans <code>config/init.php</code> et importez <code>config/schema.sql</code></p>
        </div>");
    }
    return $pdo;
}

// ── AUTH HELPERS ──────────────────────────────────────────────
function isLoggedIn(): bool { return !empty($_SESSION['user_id']); }

function requireLogin(): void {
    if (!isLoggedIn()) { header('Location: '.BASE_URL.'/views/login.php'); exit; }
}

function requireRole(array $roles): void {
    requireLogin();
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        header('Location: '.BASE_URL.'/views/dashboard.php'); exit;
    }
}

function getUser(): ?array {
    if (!isLoggedIn()) return null;
    static $cache = null;
    if ($cache) return $cache;
    $s = getDB()->prepare("SELECT u.*,r.nom_role FROM user u JOIN roles r ON u.id_role=r.id_role WHERE u.id_user=? AND u.statut='actif' LIMIT 1");
    $s->execute([$_SESSION['user_id']]);
    $row = $s->fetch();
    if (!$row) {
        // Utilisateur introuvable ou désactivé : invalider la session
        session_unset();
        session_destroy();
        return null;
    }
    // Synchroniser le rôle en session
    $_SESSION['role'] = $row['nom_role'];
    $cache = $row;
    return $cache;
}

function getUserRole(): string { return $_SESSION['role'] ?? ''; }

// ── CSRF ──────────────────────────────────────────────────────
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrf_verify(string $t): bool {
    return !empty($_SESSION['csrf_token']) && !empty($t) && hash_equals($_SESSION['csrf_token'], $t);
}

// ── NOTIFICATIONS ─────────────────────────────────────────────
function getUnreadCount(int $uid): int {
    $s = getDB()->prepare("SELECT COUNT(*) FROM notification WHERE id_user=? AND statut='non lu'");
    $s->execute([$uid]); return (int)$s->fetchColumn();
}
function getNotifs(int $uid, int $lim=10): array {
    $s = getDB()->prepare("SELECT n.*,d.nom_doc FROM notification n LEFT JOIN document d ON n.id_doc=d.id_doc WHERE n.id_user=? ORDER BY n.date_notification DESC LIMIT ?");
    $s->execute([$uid,$lim]); return $s->fetchAll();
}

// ── ROLE HELPERS ──────────────────────────────────────────────
function roleColor(string $r): array {
    return ['Administrateur'=>['primary'=>'#0f172a','accent'=>'#3b82f6','light'=>'#eff6ff','text'=>'#1e40af'],
            'Secrétaire'    =>['primary'=>'#064e3b','accent'=>'#10b981','light'=>'#ecfdf5','text'=>'#065f46'],
            'Censeur'       =>['primary'=>'#4c1d95','accent'=>'#8b5cf6','light'=>'#f5f3ff','text'=>'#5b21b6'],
            'Intendant'     =>['primary'=>'#7c2d12','accent'=>'#f97316','light'=>'#fff7ed','text'=>'#9a3412'],
            'Enseignant'    =>['primary'=>'#134e4a','accent'=>'#14b8a6','light'=>'#f0fdfa','text'=>'#0f766e'],
           ][$r] ?? ['primary'=>'#1e293b','accent'=>'#64748b','light'=>'#f8fafc','text'=>'#475569'];
}
function roleIcon(string $r): string {
    return ['Administrateur'=>'fa-user-shield','Secrétaire'=>'fa-user-tie',
            'Censeur'=>'fa-chalkboard-teacher','Intendant'=>'fa-briefcase','Enseignant'=>'fa-graduation-cap'][$r] ?? 'fa-user';
}
function roleCss(string $r): string {
    return ['Administrateur'=>'admin','Secrétaire'=>'secretaire','Censeur'=>'censeur',
            'Intendant'=>'intendant','Enseignant'=>'enseignant'][$r] ?? 'admin';
}
function initiales(string $n): string {
    $p=explode(' ',trim($n)); $i=strtoupper(substr($p[0],0,1));
    if(isset($p[1])) $i.=strtoupper(substr($p[1],0,1)); return $i;
}
function photoUrl(?string $photo): string {
    if ($photo && file_exists(ROOT_PATH.'/uploads/'.$photo)) return BASE_URL.'/uploads/'.$photo;
    return '';
}

// ── ALIAS CSRF (compatibilité avec les deux nommages) ─────────
if (!function_exists('verifyCsrf')) {
    function verifyCsrf(string $t): bool { return csrf_verify($t); }
}

// ── SERVICE SMS ───────────────────────────────────────────────
// Chargement automatique du service SMS
$_smsSvcPath = ROOT_PATH . '/services/SmsService.php';
if (file_exists($_smsSvcPath) && !class_exists('SmsService')) {
    require_once $_smsSvcPath;
}
unset($_smsSvcPath);

// ── CONFIGURATION SMS ─────────────────────────────────────────
// Choisissez votre fournisseur : 'orange', 'twilio', ou 'log' (mode test)
if (!defined('SMS_PROVIDER'))     define('SMS_PROVIDER',     'log');

// Orange Cameroun SMS API (https://developer.orange.com)
// Remplacez ces valeurs par vos identifiants Orange Developer
if (!defined('SMS_ORANGE_TOKEN')) define('SMS_ORANGE_TOKEN', '');   // Votre Bearer token OAuth2
if (!defined('SMS_ORANGE_FROM'))  define('SMS_ORANGE_FROM',  '+237000000000'); // Votre numéro Orange
if (!defined('SMS_ORANGE_SENDER'))define('SMS_ORANGE_SENDER','GestDoc');

// Twilio (alternative internationale — https://twilio.com)
if (!defined('SMS_TWILIO_SID'))   define('SMS_TWILIO_SID',   '');   // Account SID Twilio
if (!defined('SMS_TWILIO_TOKEN')) define('SMS_TWILIO_TOKEN',  '');   // Auth Token Twilio
if (!defined('SMS_TWILIO_FROM'))  define('SMS_TWILIO_FROM',   '');   // Numéro Twilio (+1xxxxxxxxxx)