<?php
require_once __DIR__ . '/../config/init.php';
requireLogin();
$user = getUser();
$pdo  = getDB();
$act  = $_GET['action'] ?? '';

match($act){
    'ajouter'       => ajouterUser(),
    'modifier'      => modifierUser(),
    'supprimer'     => supprimerUser(),
    'toggle'        => toggleUser(),
    'updateProfile' => updateProfile(),
    'changePassword'=> changePassword(),
    'uploadPhoto'   => uploadPhoto(),
    default         => go('/views/utilisateurs/index.php')
};

function ajouterUser(): void {
    global $user, $pdo;
    if (!in_array($user['nom_role'],['Administrateur','Intendant'])) go('/views/dashboard.php');
    if ($_SERVER['REQUEST_METHOD']!=='POST'||!csrf_verify($_POST['csrf_token']??'')) { go('/views/utilisateurs/ajouter.php'); return; }

    $nom   = trim($_POST['nom']??'');
    $email = trim(strtolower($_POST['email']??''));
    $pass  = $_POST['password']??'';
    $role  = (int)($_POST['id_role']??0);
    // Numéro de téléphone pour alertes SMS
    $tel   = preg_replace('/[\s\-\.\(\)]/', '', trim($_POST['telephone']??''));
    if ($tel && !str_starts_with($tel, '+')) {
        // Ajouter indicatif Cameroun automatiquement si numéro local (9 chiffres)
        if (preg_match('/^6\d{8}$/', $tel)) $tel = '+237' . $tel;
    }

    if (!$nom||!$email||!$pass||!$role) { flash('usr_err','Remplissez tous les champs.'); go('/views/utilisateurs/ajouter.php'); return; }
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) { flash('usr_err','Email invalide.'); go('/views/utilisateurs/ajouter.php'); return; }
    if (strlen($pass)<6) { flash('usr_err','Mot de passe : 6 caractères minimum.'); go('/views/utilisateurs/ajouter.php'); return; }

    $s=$pdo->prepare("SELECT COUNT(*) FROM user WHERE LOWER(email)=?"); $s->execute([$email]);
    if ($s->fetchColumn()>0) { flash('usr_err','Email déjà utilisé.'); go('/views/utilisateurs/ajouter.php'); return; }

    $hash = password_hash($pass,PASSWORD_BCRYPT,['cost'=>12]);
    $pdo->prepare("INSERT INTO user (nom_user,email,password,id_role,statut,telephone) VALUES (?,?,?,?,'actif',?)")
        ->execute([$nom,$email,$hash,$role, $tel ?: null]);

    flash('usr_ok','Utilisateur créé avec succès.');
    go('/views/utilisateurs/index.php');
}

function modifierUser(): void {
    global $user,$pdo;
    if (!in_array($user['nom_role'],['Administrateur','Intendant'])) go('/views/dashboard.php');
    if ($_SERVER['REQUEST_METHOD']!=='POST'||!csrf_verify($_POST['csrf_token']??'')) { go('/views/utilisateurs/index.php'); return; }

    $id    = (int)($_POST['id_user']??0);
    $nom   = trim($_POST['nom']??'');
    $email = trim(strtolower($_POST['email']??''));
    $role  = (int)($_POST['id_role']??0);
    $stat  = in_array($_POST['statut']??'',['actif','inactif'])?$_POST['statut']:'actif';
    // Numéro de téléphone pour alertes SMS
    $tel   = preg_replace('/[\s\-\.\(\)]/', '', trim($_POST['telephone']??''));
    if ($tel && !str_starts_with($tel, '+')) {
        if (preg_match('/^6\d{8}$/', $tel)) $tel = '+237' . $tel;
    }

    $pdo->prepare("UPDATE user SET nom_user=?,email=?,id_role=?,statut=?,telephone=? WHERE id_user=?")
        ->execute([$nom,$email,$role,$stat, $tel ?: null,$id]);

    if (!empty($_POST['password'])) {
        $h=password_hash($_POST['password'],PASSWORD_BCRYPT,['cost'=>12]);
        $pdo->prepare("UPDATE user SET password=? WHERE id_user=?")->execute([$h,$id]);
    }
    flash('usr_ok','Utilisateur modifié.');
    go('/views/utilisateurs/index.php');
}

function supprimerUser(): void {
    global $user,$pdo;
    if ($user['nom_role']!=='Administrateur') go('/views/dashboard.php');
    $id=(int)($_GET['id']??0);
    if ($id===$user['id_user']) { flash('usr_err','Impossible de supprimer votre propre compte.'); go('/views/utilisateurs/index.php'); return; }
    $pdo->prepare("DELETE FROM user WHERE id_user=?")->execute([$id]);
    flash('usr_ok','Utilisateur supprimé.');
    go('/views/utilisateurs/index.php');
}

function toggleUser(): void {
    global $user,$pdo;
    if (!in_array($user['nom_role'],['Administrateur','Intendant'])) go('/views/dashboard.php');
    $id=(int)($_GET['id']??0);
    $s=$pdo->prepare("SELECT statut FROM user WHERE id_user=?"); $s->execute([$id]);
    $cur=$s->fetchColumn();
    $new=$cur==='actif'?'inactif':'actif';
    $pdo->prepare("UPDATE user SET statut=? WHERE id_user=?")->execute([$new,$id]);
    flash('usr_ok','Statut mis à jour.');
    go('/views/utilisateurs/index.php');
}

function updateProfile(): void {
    global $user,$pdo;
    if ($_SERVER['REQUEST_METHOD']!=='POST'||!csrf_verify($_POST['csrf_token']??'')) { go('/views/profil/index.php?tab=edit'); return; }
    $nom   = trim($_POST['nom']??'');
    $email = trim(strtolower($_POST['email']??''));
    $tel   = preg_replace('/[\s\-\.\(\)]/', '', trim($_POST['telephone']??''));
    if ($tel && !str_starts_with($tel, '+')) {
        if (preg_match('/^6\d{8}$/', $tel)) $tel = '+237' . $tel;
    }
    if (!$nom||!$email) { flash('prf_err','Remplissez tous les champs.'); go('/views/profil/index.php?tab=edit'); return; }
    $pdo->prepare("UPDATE user SET nom_user=?,email=?,telephone=? WHERE id_user=?")->execute([$nom,$email,$tel ?: null,$user['id_user']]);
    flash('prf_ok','Profil mis à jour.');
    go('/views/profil/index.php?tab=edit');
}

function changePassword(): void {
    global $user,$pdo;
    if ($_SERVER['REQUEST_METHOD']!=='POST'||!csrf_verify($_POST['csrf_token']??'')) { go('/views/profil/index.php?tab=password'); return; }
    $cur  = $_POST['current']??'';
    $new  = $_POST['new_pass']??'';
    $conf = $_POST['confirm']??'';
    $s=$pdo->prepare("SELECT password FROM user WHERE id_user=?"); $s->execute([$user['id_user']]);
    $h=$s->fetchColumn();
    if (!password_verify($cur,$h)) { flash('prf_err','Mot de passe actuel incorrect.'); go('/views/profil/index.php?tab=password'); return; }
    if ($new!==$conf) { flash('prf_err','Les mots de passe ne correspondent pas.'); go('/views/profil/index.php?tab=password'); return; }
    if (strlen($new)<8) { flash('prf_err','Minimum 8 caractères.'); go('/views/profil/index.php?tab=password'); return; }
    $nh=password_hash($new,PASSWORD_BCRYPT,['cost'=>12]);
    $pdo->prepare("UPDATE user SET password=? WHERE id_user=?")->execute([$nh,$user['id_user']]);
    flash('prf_ok','Mot de passe modifié.');
    go('/views/profil/index.php?tab=password');
}

function uploadPhoto(): void {
    global $user,$pdo;
    if ($_SERVER['REQUEST_METHOD']!=='POST'||!csrf_verify($_POST['csrf_token']??'')) { go('/views/profil/index.php'); return; }
    if (!empty($_FILES['photo']['name'])) {
        $ext=strtolower(pathinfo($_FILES['photo']['name'],PATHINFO_EXTENSION));
        if (in_array($ext,['jpg','jpeg','png','gif','webp'])&&$_FILES['photo']['size']<5*1024*1024) {
            $fn='profile_'.$user['id_user'].'_'.time().'.'.$ext;
            $dest=ROOT_PATH.'/uploads/'.$fn;
            if (move_uploaded_file($_FILES['photo']['tmp_name'],$dest)) {
                $pdo->prepare("UPDATE user SET photo=? WHERE id_user=?")->execute([$fn,$user['id_user']]);
                flash('prf_ok','Photo mise à jour.');
            }
        }
    }
    go('/views/profil/index.php');
}

function flash(string $k,string $v): void { $_SESSION[$k]=$v; }
function go(string $p): void { header('Location: '.BASE_URL.$p); exit; }