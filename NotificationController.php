<?php
require_once __DIR__ . '/../config/init.php';
requireLogin();
header('Content-Type: application/json');
$user = getUser();
$pdo  = getDB();
$act  = $_GET['action'] ?? '';

switch ($act) {
    case 'get':
        $items  = getNotifs($user['id_user'], 12);
        $unread = getUnreadCount($user['id_user']);
        echo json_encode(['items'=>$items,'unread'=>$unread]); break;
    case 'count':
        echo json_encode(['count'=>getUnreadCount($user['id_user'])]); break;
    case 'markRead':
        $id=(int)($_POST['id']??0);
        $pdo->prepare("UPDATE notification SET statut='lu' WHERE id_notification=? AND id_user=?")->execute([$id,$user['id_user']]);
        echo json_encode(['ok'=>true]); break;
    case 'markAll':
        $pdo->prepare("UPDATE notification SET statut='lu' WHERE id_user=?")->execute([$user['id_user']]);
        echo json_encode(['ok'=>true]); break;
    default: echo json_encode(['error'=>'unknown action']);
}
