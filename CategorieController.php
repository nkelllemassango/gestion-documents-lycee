<?php
require_once __DIR__ . '/../config/init.php';
requireLogin();
if (!in_array(getUserRole(),['Administrateur','Secrétaire'])) { header('Location: '.BASE_URL.'/views/dashboard.php'); exit; }
$pdo = getDB();
$act = $_GET['action'] ?? '';

match($act){
    'ajouter'  => ajouterCat(),
    'modifier' => modifierCat(),
    'supprimer'=> supprimerCat(),
    default    => go('/views/categories/index.php')
};

function ajouterCat(): void {
    global $pdo;
    if ($_SERVER['REQUEST_METHOD']!=='POST'||!csrf_verify($_POST['csrf_token']??'')) { go('/views/categories/index.php'); return; }
    $nom=trim($_POST['nom']??'');
    if (!$nom) { flash('cat_err','Nom obligatoire.'); go('/views/categories/index.php'); return; }
    $pdo->prepare("INSERT INTO categorie (nom_categorie) VALUES (?)")->execute([$nom]);
    flash('cat_ok','Catégorie créée.'); go('/views/categories/index.php');
}
function modifierCat(): void {
    global $pdo;
    if ($_SERVER['REQUEST_METHOD']!=='POST'||!csrf_verify($_POST['csrf_token']??'')) { go('/views/categories/index.php'); return; }
    $id=(int)($_POST['id']??0); $nom=trim($_POST['nom']??'');
    if (!$nom) { flash('cat_err','Nom obligatoire.'); go('/views/categories/index.php'); return; }
    $pdo->prepare("UPDATE categorie SET nom_categorie=? WHERE id_categorie=?")->execute([$nom,$id]);
    flash('cat_ok','Catégorie modifiée.'); go('/views/categories/index.php');
}
function supprimerCat(): void {
    global $pdo;
    $id=(int)($_GET['id']??0);
    $s=$pdo->prepare("SELECT COUNT(*) FROM document WHERE categorie_id=?"); $s->execute([$id]);
    if ($s->fetchColumn()>0) { flash('cat_err','Catégorie utilisée par des documents.'); go('/views/categories/index.php'); return; }
    $pdo->prepare("DELETE FROM categorie WHERE id_categorie=?")->execute([$id]);
    flash('cat_ok','Catégorie supprimée.'); go('/views/categories/index.php');
}
function flash(string $k,string $v): void { $_SESSION[$k]=$v; }
function go(string $p): void { header('Location: '.BASE_URL.$p); exit; }
