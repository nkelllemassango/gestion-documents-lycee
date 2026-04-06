<?php
/**
 * controllers/SignatureController.php
 * GestDoc LBB — Signature numérique
 *
 * Approche robuste sans dépendance externe :
 *   1. Sauvegarde la signature PNG
 *   2. Crée un PDF signé via PdfSigner (lib native PHP)
 *   3. Met à jour la BDD
 *   4. Envoie le SMS
 */

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../lib/PdfSigner.php';

// SmsService chargé automatiquement par init.php

requireLogin();

$action = $_GET['action'] ?? '';
$pdo    = getDB();
$user   = getUser();
$uid    = $user['id_user'];

// ================================================================
// ACTION : SIGNER UN DOCUMENT
// ================================================================
if ($action === 'signer' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $_SESSION['doc_err'] = 'Token de sécurité invalide.';
        header('Location: ' . BASE_URL . '/views/documents/index.php'); exit;
    }

    $idDoc   = (int)($_POST['id_doc'] ?? 0);
    $sigData = trim($_POST['signature_data'] ?? '');

    if (!$idDoc || !$sigData) {
        $_SESSION['doc_err'] = 'Données de signature manquantes.';
        header('Location: ' . BASE_URL . '/views/documents/detail.php?id=' . $idDoc); exit;
    }

    // Charger le document + infos expéditeur
    $stmt = $pdo->prepare(
        "SELECT d.*, u.nom_user exp_nom, u.telephone exp_tel
         FROM document d
         LEFT JOIN user u ON d.expediteur_id = u.id_user
         WHERE d.id_doc = ?"
    );
    $stmt->execute([$idDoc]);
    $doc = $stmt->fetch();

    if (!$doc) {
        $_SESSION['doc_err'] = 'Document introuvable.';
        header('Location: ' . BASE_URL . '/views/documents/index.php'); exit;
    }

    // ── 1. Sauvegarder l'image de signature PNG ───────────────
    $sigDir = ROOT_PATH . '/assets/signatures/';
    if (!is_dir($sigDir)) mkdir($sigDir, 0755, true);

    $sigDataClean = preg_replace('#^data:image/\w+;base64,#i', '', $sigData);
    $sigBytes     = base64_decode($sigDataClean);

    if (!$sigBytes || strlen($sigBytes) < 100) {
        $_SESSION['doc_err'] = 'Image de signature invalide.';
        header('Location: ' . BASE_URL . '/views/documents/detail.php?id=' . $idDoc); exit;
    }

    $sigFilename = 'sig_' . $uid . '_' . $idDoc . '_' . time() . '.png';
    $sigPath     = $sigDir . $sigFilename;
    file_put_contents($sigPath, $sigBytes);

    // ── 2. Apposer la signature sur le PDF ────────────────────
    $fichierSigne    = null;
    $fichierOrig     = $doc['fichier'] ?? '';
    $fichierOrigPath = ROOT_PATH . '/uploads/documents/' . $fichierOrig;
    $ext             = $fichierOrig ? strtolower(pathinfo($fichierOrig, PATHINFO_EXTENSION)) : '';

    if ($fichierOrig && file_exists($fichierOrigPath) && $ext === 'pdf') {
        $outDir      = ROOT_PATH . '/uploads/documents/';
        $outFilename = 'signed_' . $idDoc . '_' . time() . '.pdf';
        $outPath     = $outDir . $outFilename;

        $ok = PdfSigner::apposer(
            $fichierOrigPath,
            $sigPath,
            $user['nom_user'],
            $user['nom_role'] ?? '',
            $outPath
        );

        if ($ok && file_exists($outPath) && filesize($outPath) > 500) {
            $fichierSigne = $outFilename;
        }
    }

    // ── 3. Insertion en BDD ───────────────────────────────────
    $pdo->prepare(
        "INSERT INTO signature (id_doc, id_user, date_sign, image_signature, approuve)
         VALUES (?, ?, NOW(), ?, 'signé')"
    )->execute([$idDoc, $uid, $sigFilename]);

    $idSign = $pdo->lastInsertId();

    $pdo->prepare(
        "INSERT IGNORE INTO poser (id_doc, id_sign, approuve, date) VALUES (?, ?, 'signé', NOW())"
    )->execute([$idDoc, $idSign]);

    // Mise à jour statut + fichier signé
    try {
        if ($fichierSigne) {
            $pdo->prepare("UPDATE document SET statut='signé', fichier_signe=? WHERE id_doc=?")
                ->execute([$fichierSigne, $idDoc]);
        } else {
            $pdo->prepare("UPDATE document SET statut='signé' WHERE id_doc=?")->execute([$idDoc]);
        }
    } catch (\PDOException $e) {
        // colonne fichier_signe pas encore créée → la créer
        try {
            $pdo->exec("ALTER TABLE document ADD COLUMN fichier_signe VARCHAR(255) DEFAULT NULL");
            if ($fichierSigne) {
                $pdo->prepare("UPDATE document SET statut='signé', fichier_signe=? WHERE id_doc=?")
                    ->execute([$fichierSigne, $idDoc]);
            } else {
                $pdo->prepare("UPDATE document SET statut='signé' WHERE id_doc=?")->execute([$idDoc]);
            }
        } catch (\PDOException $e2) {
            $pdo->prepare("UPDATE document SET statut='signé' WHERE id_doc=?")->execute([$idDoc]);
        }
    }

    // ── 4. Notification + SMS à l'expéditeur ─────────────────
    if ($doc['expediteur_id'] && $doc['expediteur_id'] != $uid) {
        $contenu = $user['nom_user'] . ' a signé votre document : "' . $doc['nom_doc'] . '".';
        $pdo->prepare(
            "INSERT INTO notification (id_user, id_doc, contenu, statut, date_notification)
             VALUES (?, ?, ?, 'non lu', NOW())"
        )->execute([$doc['expediteur_id'], $idDoc, $contenu]);

        if (class_exists('SmsService') && !empty($doc['exp_tel'])) {
            SmsService::alerterSigné(
                $doc['exp_tel'],
                $doc['exp_nom'],
                $doc['nom_doc'],
                $user['nom_user']
            );
        }
    }

    $msg = $fichierSigne
        ? 'Document signé avec succès — PDF avec signature généré.'
        : 'Document signé avec succès.';
    $_SESSION['doc_ok'] = $msg;
    header('Location: ' . BASE_URL . '/views/documents/detail.php?id=' . $idDoc);
    exit;
}

header('Location: ' . BASE_URL . '/views/documents/index.php'); exit;