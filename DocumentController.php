<?php
require_once __DIR__ . '/../config/init.php';
// SmsService est chargé automatiquement par init.php
requireLogin();
$user = getUser();
$pdo  = getDB();
$act  = $_GET['action'] ?? '';

match($act){
    'ajouter'  => ajouterDoc(),
    'modifier' => modifierDoc(),
    'supprimer'=> supprimerDoc(),
    'archiver' => archiverDoc(),
    'envoyer'  => envoyerDoc(),
    'refuser'  => refuserDoc(),
    default    => go('/views/documents/index.php')
};

function ajouterDoc(): void {
    global $user,$pdo;
    if ($_SERVER['REQUEST_METHOD']!=='POST'||!csrf_verify($_POST['csrf_token']??'')) { go('/views/documents/ajouter.php'); return; }

    $titre= trim($_POST['titre']??'');
    $desc = trim($_POST['description']??'');
    $cat  = (int)($_POST['categorie_id']??0);
    $dest = (int)($_POST['destinataire_id']??0)?:(null);
    $stat = in_array($_POST['statut']??'',['en attente','envoyé'])?$_POST['statut']:'en attente';

    if (!$titre||!$cat) { flash('doc_err','Titre et catégorie obligatoires.'); go('/views/documents/ajouter.php'); return; }

    $fichier=null;
    if (!empty($_FILES['fichier']['name'])) {
        $ext=strtolower(pathinfo($_FILES['fichier']['name'],PATHINFO_EXTENSION));
        if (in_array($ext,['pdf','doc','docx','jpg','jpeg','png'])&&$_FILES['fichier']['size']<10*1024*1024) {
            $fn='doc_'.time().'_'.uniqid().'.'.$ext;
            if (move_uploaded_file($_FILES['fichier']['tmp_name'],ROOT_PATH.'/uploads/documents/'.$fn)) $fichier=$fn;
        }
    }
    // Camera capture
    if (!$fichier&&!empty($_POST['capture'])) {
        $data=$_POST['capture'];
        if (str_starts_with($data,'data:image/jpeg;base64,')) {
            $fn='cap_'.time().'_'.uniqid().'.jpg';
            file_put_contents(ROOT_PATH.'/uploads/captures/'.$fn,base64_decode(substr($data,23)));
            $fichier='../captures/'.$fn;
        }
    }

    $pdo->prepare("INSERT INTO document (nom_doc,description,categorie_id,fichier,expediteur_id,destinataire_id,statut) VALUES (?,?,?,?,?,?,?)")
        ->execute([$titre,$desc,$cat,$fichier,$user['id_user'],$dest,$stat]);
    $docId=$pdo->lastInsertId();

    if ($dest) {
        notif($dest, $user['nom_user'].' vous a envoyé un document : '.$titre, $docId);
        // Alerte SMS au destinataire
        if (class_exists('SmsService')) {
            $destRow = $pdo->prepare("SELECT nom_user, telephone FROM user WHERE id_user = ?");
            $destRow->execute([$dest]);
            $destInfo = $destRow->fetch();
            if ($destInfo && !empty($destInfo['telephone'])) {
                SmsService::alerterSignature(
                    $destInfo['telephone'],
                    $destInfo['nom_user'],
                    $titre,
                    $user['nom_user']
                );
            }
        }
    }

    flash('doc_ok','Document créé avec succès.');
    go('/views/documents/detail.php?id='.$docId);
}

function modifierDoc(): void {
    global $user,$pdo;
    if ($_SERVER['REQUEST_METHOD']!=='POST'||!csrf_verify($_POST['csrf_token']??'')) { go('/views/documents/index.php'); return; }
    $id=(int)($_POST['id_doc']??0);
    $doc=getDoc($id); if (!$doc) { go('/views/documents/index.php'); return; }
    if ($user['nom_role']!=='Administrateur'&&$doc['expediteur_id']!=$user['id_user']) { go('/views/documents/index.php'); return; }

    $titre=trim($_POST['titre']??'');
    $desc =trim($_POST['description']??'');
    $cat  =(int)($_POST['categorie_id']??0);
    $dest =(int)($_POST['destinataire_id']??0)?:(null);
    $stat =in_array($_POST['statut']??'',['envoyé','en attente','signé','refusé','archivé'])?$_POST['statut']:$doc['statut'];

    $fichier=$doc['fichier'];
    if (!empty($_FILES['fichier']['name'])) {
        $ext=strtolower(pathinfo($_FILES['fichier']['name'],PATHINFO_EXTENSION));
        if (in_array($ext,['pdf','doc','docx','jpg','jpeg','png'])) {
            $fn='doc_'.time().'_'.uniqid().'.'.$ext;
            if (move_uploaded_file($_FILES['fichier']['tmp_name'],ROOT_PATH.'/uploads/documents/'.$fn)) $fichier=$fn;
        }
    }
    $pdo->prepare("UPDATE document SET nom_doc=?,description=?,categorie_id=?,fichier=?,destinataire_id=?,statut=? WHERE id_doc=?")
        ->execute([$titre,$desc,$cat,$fichier,$dest,$stat,$id]);
    flash('doc_ok','Document modifié.'); go('/views/documents/detail.php?id='.$id);
}

function supprimerDoc(): void {
    global $user,$pdo;
    $id=(int)($_GET['id']??0);
    $doc=getDoc($id); if (!$doc) { go('/views/documents/index.php'); return; }
    if ($user['nom_role']!=='Administrateur'&&$doc['expediteur_id']!=$user['id_user']) { go('/views/documents/index.php'); return; }
    $pdo->prepare("DELETE FROM document WHERE id_doc=?")->execute([$id]);
    flash('doc_ok','Document supprimé.'); go('/views/documents/index.php');
}

function archiverDoc(): void {
    global $user,$pdo;
    if (!in_array($user['nom_role'],['Administrateur','Intendant'])) { go('/views/documents/index.php'); return; }
    $id=(int)($_GET['id']??0);
    $pdo->prepare("UPDATE document SET statut='archivé' WHERE id_doc=?")->execute([$id]);
    flash('doc_ok','Document archivé.'); go('/views/documents/index.php');
}

function envoyerDoc(): void {
    global $user,$pdo;
    if ($_SERVER['REQUEST_METHOD']!=='POST') { go('/views/documents/index.php'); return; }
    $id  =(int)($_POST['id_doc']??0);
    $dest=(int)($_POST['destinataire_id']??0);
    if (!$id||!$dest) { go('/views/documents/index.php'); return; }
    $doc=getDoc($id);
    $pdo->prepare("UPDATE document SET destinataire_id=?,statut='envoyé' WHERE id_doc=?")->execute([$dest,$id]);
    notif($dest,$user['nom_user'].' vous a envoyé le document : '.($doc['nom_doc']??''),$id);
    flash('doc_ok','Document envoyé.'); go('/views/documents/detail.php?id='.$id);
}

function refuserDoc(): void {
    global $user,$pdo;
    if (!in_array($user['nom_role'],['Administrateur','Intendant'])) { go('/views/documents/index.php'); return; }
    $id=(int)($_GET['id']??0);
    $doc=getDoc($id);
    $pdo->prepare("UPDATE document SET statut='refusé' WHERE id_doc=?")->execute([$id]);
    if ($doc['expediteur_id']) notif($doc['expediteur_id'],$user['nom_user'].' a refusé votre document : '.($doc['nom_doc']??''),$id);
    flash('doc_ok','Document refusé.'); go('/views/documents/detail.php?id='.$id);
}

function getDoc(int $id): array|false {
    global $pdo;
    $s=$pdo->prepare("SELECT * FROM document WHERE id_doc=?"); $s->execute([$id]); return $s->fetch();
}
function notif(int $uid,string $msg,int $docId=null): void {
    global $pdo;
    $pdo->prepare("INSERT INTO notification (id_user,contenu,id_doc) VALUES (?,?,?)")->execute([$uid,$msg,$docId]);
}
function flash(string $k,string $v): void { $_SESSION[$k]=$v; }
function go(string $p): void { header('Location: '.BASE_URL.$p); exit; }
