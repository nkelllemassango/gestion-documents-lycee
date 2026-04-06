<?php
require_once __DIR__ . '/../../config/init.php';
requireLogin();
$user = getUser();
$role = $user['nom_role'];
$uid  = $user['id_user'];
$css  = roleCss($role);
$pdo  = getDB();
$unread = getUnreadCount($uid);

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . '/views/documents/index.php'); exit; }

$stmt = $pdo->prepare(
    "SELECT d.*, c.nom_categorie,
            u1.nom_user  AS exp_nom,
            u1.email     AS exp_email,
            u2.nom_user  AS dest_nom
     FROM document d
     LEFT JOIN categorie c  ON d.categorie_id   = c.id_categorie
     LEFT JOIN user      u1 ON d.expediteur_id  = u1.id_user
     LEFT JOIN user      u2 ON d.destinataire_id = u2.id_user
     WHERE d.id_doc = ?"
);
// Note : la colonne fichier_signe peut ne pas exister sur des anciennes installations.
// On la récupère silencieusement ci-dessous.
$stmt->execute([$id]);
$doc = $stmt->fetch();
if (!$doc) { header('Location: ' . BASE_URL . '/views/documents/index.php'); exit; }

$sigs = $pdo->prepare(
    "SELECT s.*, u.nom_user
     FROM signature s
     JOIN user u ON s.id_user = u.id_user
     WHERE s.id_doc = ?
     ORDER BY s.date_sign DESC"
);
$sigs->execute([$id]);
$signatures = $sigs->fetchAll();

$allUsers = $pdo->query(
    "SELECT u.id_user, u.nom_user, r.nom_role
     FROM user u
     JOIN roles r ON u.id_role = r.id_role
     WHERE u.statut = 'actif'
     ORDER BY u.nom_user"
)->fetchAll();

$ok  = isset($_SESSION['doc_ok'])  ? $_SESSION['doc_ok']  : null; unset($_SESSION['doc_ok']);
$err = isset($_SESSION['doc_err']) ? $_SESSION['doc_err'] : null; unset($_SESSION['doc_err']);

$statusMap   = ['envoyé'=>'b-envoye','en attente'=>'b-attente','signé'=>'b-signe','refusé'=>'b-refuse','archivé'=>'b-archive'];
$statusLabel = ['envoyé'=>'Envoyé','en attente'=>'En attente','signé'=>'Signé','refusé'=>'Refusé','archivé'=>'Archivé'];

// Infos fichier
$fichier     = $doc['fichier'] ?? '';
$fichierUrl  = $fichier ? BASE_URL . '/uploads/documents/' . rawurlencode($fichier) : '';
$ext         = $fichier ? strtolower(pathinfo($fichier, PATHINFO_EXTENSION)) : '';
$isImage     = in_array($ext, ['jpg','jpeg','png','gif','webp','bmp']);
$isPdf       = ($ext === 'pdf');
$isDoc       = in_array($ext, ['doc','docx']);
$hasFile     = !empty($fichier);

// Fichier PDF signé (avec signature apposée en pied de page)
$fichierSigne    = $doc['fichier_signe'] ?? '';
$fichierSigneUrl = $fichierSigne
    ? BASE_URL . '/uploads/documents/' . rawurlencode($fichierSigne)
    : '';
$hasFichierSigne = !empty($fichierSigne)
    && file_exists(ROOT_PATH . '/uploads/documents/' . $fichierSigne);

// Pour la visionneuse : utiliser le PDF signé si disponible
$viewerUrl = ($hasFichierSigne && $isPdf) ? $fichierSigneUrl : $fichierUrl;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($doc['nom_doc']) ?> — Détail</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<script>const BASE_URL = '<?= BASE_URL ?>';</script>
<style>
/* ══ VISUALISEUR ══════════════════════════════════════════════ */
.viewer-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0,0,0,.85);
    align-items: center;
    justify-content: center;
    padding: 16px;
    animation: vmIn .2s ease;
}
.viewer-modal.open { display: flex; }
@keyframes vmIn { from { opacity:0 } to { opacity:1 } }

.viewer-box {
    background: #fff;
    border-radius: 14px;
    width: 100%;
    max-width: 940px;
    max-height: 92vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 32px 80px rgba(0,0,0,.5);
}

.viewer-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    background: #0f172a;
    color: #fff;
    gap: 12px;
    flex-shrink: 0;
}
.viewer-head-title {
    font-size: .9rem;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
}
.viewer-head-actions { display: flex; gap: 8px; flex-shrink: 0; }
.viewer-btn {
    padding: 6px 14px;
    border-radius: 7px;
    border: none;
    font-size: .8rem;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all .15s;
    text-decoration: none;
}
.viewer-btn-dl  { background: #3b82f6; color: #fff; }
.viewer-btn-dl:hover { background: #2563eb; color: #fff; }
.viewer-btn-cls { background: rgba(255,255,255,.12); color: #fff; font-size: 1.1rem; padding: 5px 10px; }
.viewer-btn-cls:hover { background: #ef4444; }

.viewer-body {
    flex: 1;
    overflow: auto;
    background: #1e293b;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    min-height: 400px;
}

/* PDF */
.viewer-body iframe {
    width: 100%;
    height: 100%;
    min-height: 70vh;
    border: none;
    display: block;
}

/* Image */
.viewer-body img {
    max-width: 100%;
    max-height: 80vh;
    object-fit: contain;
    display: block;
    margin: auto;
    padding: 16px;
    border-radius: 4px;
}

/* Pas prévisualisable */
.viewer-nopreview {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14px;
    padding: 60px 32px;
    color: rgba(255,255,255,.7);
    text-align: center;
    width: 100%;
}
.viewer-nopreview i { font-size: 3.5rem; color: rgba(255,255,255,.35); }
.viewer-nopreview p { font-size: .88rem; max-width: 320px; line-height: 1.65; }

/* Bouton "Voir le document" */
.btn-view-doc {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: 9px;
    background: linear-gradient(135deg,var(--p,#0f172a),var(--a,#3b82f6));
    color: #fff;
    font-weight: 700;
    font-size: .85rem;
    border: none;
    cursor: pointer;
    font-family: inherit;
    transition: all .2s;
    text-decoration: none;
}
.btn-view-doc:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0,0,0,.2); color: #fff; }

/* Miniature dans la carte fichier */
.file-thumb {
    cursor: pointer;
    border-radius: 8px;
    overflow: hidden;
    max-height: 180px;
    text-align: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    transition: box-shadow .2s;
}
.file-thumb:hover { box-shadow: 0 4px 16px rgba(0,0,0,.12); }
.file-thumb img { max-width: 100%; max-height: 180px; object-fit: contain; display: block; margin: auto; }
</style>
</head>
<body class="<?= $css ?>">

<?php include ROOT_PATH . '/views/partials/sidebar.php'; ?>
<?php include ROOT_PATH . '/views/partials/navbar.php'; ?>

<main class="app-main">

    <!-- ══ EN-TÊTE ══════════════════════════════════════════════ -->
    <div class="page-header">
        <div>
            <div class="page-title">
                <i class="fas fa-file-alt"></i>
                <?= htmlspecialchars($doc['nom_doc']) ?>
            </div>
            <div style="display:flex;align-items:center;gap:10px;margin-top:6px;flex-wrap:wrap">
                <span class="badge <?= $statusMap[$doc['statut']] ?? 'b-attente' ?>">
                    <?= $statusLabel[$doc['statut']] ?? $doc['statut'] ?>
                </span>
                <?php if ($doc['nom_categorie']): ?>
                <span style="background:#f1f5f9;padding:3px 11px;border-radius:20px;font-size:.74rem">
                    <i class="fas fa-tag" style="margin-right:4px"></i>
                    <?= htmlspecialchars($doc['nom_categorie']) ?>
                </span>
                <?php endif; ?>
                <span style="font-size:.78rem;color:#94a3b8">
                    <i class="fas fa-calendar" style="margin-right:4px"></i>
                    <?= date('d/m/Y H:i', strtotime($doc['date'])) ?>
                </span>
            </div>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <!-- ✅ Bouton VOIR LE DOCUMENT -->
            <?php if ($hasFile): ?>
            <button onclick="openViewer()" class="btn-view-doc">
                <i class="fas fa-eye"></i> Voir le document
            </button>
            <?php endif; ?>

            <?php if ($role === 'Administrateur' || $doc['expediteur_id'] == $uid): ?>
            <a href="<?= BASE_URL ?>/views/documents/modifier.php?id=<?= $id ?>" class="btn btn-warn">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <?php endif; ?>

            <?php if ($hasFile): ?>
            <a href="<?= $fichierUrl ?>" download class="btn btn-info">
                <i class="fas fa-download"></i> Télécharger
            </a>
            <button onclick="window.print()" class="btn btn-ghost">
                <i class="fas fa-print"></i> Imprimer
            </button>
            <?php endif; ?>

            <?php if (in_array($doc['statut'], ['en attente', 'envoyé'])): ?>
            <a href="<?= BASE_URL ?>/views/documents/signer.php?id=<?= $id ?>" class="btn btn-success">
                <i class="fas fa-pen-nib"></i> Signer
            </a>
            <?php endif; ?>

            <?php if (in_array($role, ['Administrateur','Secrétaire','Censeur','Intendant'])): ?>
            <button onclick="document.getElementById('sendModal').classList.add('open')"
                    class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Envoyer
            </button>
            <?php endif; ?>

            <?php if (in_array($role, ['Administrateur','Intendant']) && $doc['statut'] !== 'refusé'): ?>
            <a href="<?= BASE_URL ?>/controllers/DocumentController.php?action=refuser&id=<?= $id ?>"
               onclick="return confirm('Refuser ce document ?')" class="btn btn-danger">
                <i class="fas fa-times-circle"></i> Refuser
            </a>
            <?php endif; ?>

            <a href="<?= BASE_URL ?>/views/documents/index.php" class="btn btn-ghost">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </div>

    <?php if ($ok):  ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-error"><i class="fas fa-times-circle"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:22px">

        <!-- ══ COLONNE GAUCHE ════════════════════════════════════ -->
        <div>

            <!-- ─ Détails ─ -->
            <div class="data-card" style="margin-bottom:20px">
                <div class="data-card-head">
                    <div class="data-card-title"><i class="fas fa-info-circle"></i> Détails du document</div>
                </div>
                <div class="data-card-body">

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px">
                        <!-- Expéditeur -->
                        <div>
                            <div style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px">Expéditeur</div>
                            <div style="display:flex;align-items:center;gap:9px">
                                <div style="width:32px;height:32px;border-radius:50%;background:var(--al);color:var(--at);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700">
                                    <?= initiales($doc['exp_nom'] ?? '?') ?>
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:.87rem"><?= htmlspecialchars($doc['exp_nom'] ?? '—') ?></div>
                                    <div style="font-size:.73rem;color:#94a3b8"><?= htmlspecialchars($doc['exp_email'] ?? '') ?></div>
                                </div>
                            </div>
                        </div>
                        <!-- Destinataire -->
                        <div>
                            <div style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px">Destinataire</div>
                            <?php if ($doc['dest_nom']): ?>
                            <div style="display:flex;align-items:center;gap:9px">
                                <div style="width:32px;height:32px;border-radius:50%;background:#f1f5f9;color:#475569;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700">
                                    <?= initiales($doc['dest_nom']) ?>
                                </div>
                                <div style="font-weight:600;font-size:.87rem">
                                    <?= htmlspecialchars($doc['dest_nom']) ?>
                                </div>
                            </div>
                            <?php else: ?>
                            <span style="color:#94a3b8;font-size:.84rem">— Aucun —</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Description -->
                    <?php if ($doc['description']): ?>
                    <div style="margin-bottom:18px">
                        <div style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px">Description</div>
                        <div style="background:#f8fafc;border-radius:9px;padding:14px;font-size:.87rem;color:#374151;line-height:1.7;border-left:3px solid var(--a)">
                            <?= nl2br(htmlspecialchars($doc['description'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ✅ FICHIER JOINT — avec prévisualisation miniature + bouton voir -->
                    <?php if ($hasFile): ?>
                    <div>
                        <div style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px">
                            Fichier joint
                        </div>

                        <!-- Miniature cliquable pour les images -->
                        <?php if ($isImage): ?>
                        <div class="file-thumb" onclick="openViewer()" title="Cliquer pour agrandir">
                            <img src="<?= htmlspecialchars($fichierUrl) ?>"
                                 alt="<?= htmlspecialchars($fichier) ?>">
                        </div>
                        <div style="display:flex;gap:8px;margin-top:10px">
                            <button onclick="openViewer()" class="btn-view-doc" style="flex:1;justify-content:center">
                                <i class="fas fa-expand-alt"></i> Voir en grand
                            </button>
                            <a href="<?= htmlspecialchars($fichierUrl) ?>" download class="btn btn-info btn-sm">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>

                        <!-- PDF : aperçu miniature -->
                        <?php elseif ($isPdf): ?>
                        <div style="padding:16px;background:#f8fafc;border-radius:9px;border:1px solid #e2e8f0">
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
                                <div style="width:42px;height:42px;border-radius:9px;background:<?= $hasFichierSigne ? '#dcfce7' : '#fee2e2' ?>;display:flex;align-items:center;justify-content:center;color:<?= $hasFichierSigne ? '#16a34a' : '#dc2626' ?>;font-size:1.2rem;flex-shrink:0">
                                    <i class="fas <?= $hasFichierSigne ? 'fa-file-signature' : 'fa-file-pdf' ?>"></i>
                                </div>
                                <div style="flex:1;min-width:0">
                                    <div style="font-weight:600;font-size:.84rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                        <?= htmlspecialchars($hasFichierSigne ? $fichierSigne : $fichier) ?>
                                    </div>
                                    <div style="font-size:.73rem;color:<?= $hasFichierSigne ? '#16a34a' : '#94a3b8' ?>">
                                        <?= $hasFichierSigne ? '✓ PDF avec signature apposée' : 'Document PDF' ?>
                                    </div>
                                </div>
                            </div>
                            <?php if ($hasFichierSigne): ?>
                            <div style="font-size:.75rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:7px;padding:7px 10px;margin-bottom:10px;color:#166534">
                                <i class="fas fa-shield-alt" style="margin-right:5px"></i>
                                La signature numérique est apposée en pied de page de chaque page du document.
                            </div>
                            <?php endif; ?>
                            <div style="display:flex;gap:8px">
                                <button onclick="openViewer()" class="btn-view-doc" style="flex:1;justify-content:center">
                                    <i class="fas fa-eye"></i> <?= $hasFichierSigne ? 'Voir le document signé' : 'Ouvrir le PDF' ?>
                                </button>
                                <a href="<?= htmlspecialchars($hasFichierSigne ? $fichierSigneUrl : $fichierUrl) ?>" download class="btn btn-info btn-sm" title="Télécharger">
                                    <i class="fas fa-download"></i>
                                </a>
                                <?php if ($hasFichierSigne): ?>
                                <a href="<?= htmlspecialchars($fichierUrl) ?>" download class="btn btn-ghost btn-sm" title="Télécharger l'original (sans signature)">
                                    <i class="fas fa-file-alt"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- DOC/DOCX -->
                        <?php elseif ($isDoc): ?>
                        <div style="padding:16px;background:#f8fafc;border-radius:9px;border:1px solid #e2e8f0">
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
                                <div style="width:42px;height:42px;border-radius:9px;background:#dbeafe;display:flex;align-items:center;justify-content:center;color:#1d4ed8;font-size:1.2rem;flex-shrink:0">
                                    <i class="fas fa-file-word"></i>
                                </div>
                                <div style="flex:1;min-width:0">
                                    <div style="font-weight:600;font-size:.84rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                        <?= htmlspecialchars($fichier) ?>
                                    </div>
                                    <div style="font-size:.73rem;color:#94a3b8">Document Word</div>
                                </div>
                            </div>
                            <div style="display:flex;gap:8px">
                                <button onclick="openViewer()" class="btn-view-doc" style="flex:1;justify-content:center">
                                    <i class="fas fa-eye"></i> Aperçu en ligne
                                </button>
                                <a href="<?= htmlspecialchars($fichierUrl) ?>" download class="btn btn-info btn-sm">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Autre type -->
                        <?php else: ?>
                        <div style="padding:14px;background:#f8fafc;border-radius:9px;border:1px solid #e2e8f0;display:flex;align-items:center;gap:12px">
                            <div style="width:42px;height:42px;border-radius:9px;background:var(--al);display:flex;align-items:center;justify-content:center;color:var(--at);font-size:1.1rem;flex-shrink:0">
                                <i class="fas fa-file"></i>
                            </div>
                            <div style="flex:1;min-width:0">
                                <div style="font-weight:600;font-size:.84rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                    <?= htmlspecialchars($fichier) ?>
                                </div>
                                <div style="font-size:.73rem;color:#94a3b8">Fichier joint</div>
                            </div>
                            <a href="<?= htmlspecialchars($fichierUrl) ?>" download class="btn btn-info btn-sm">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- ─ Signatures ─ -->
            <div class="data-card">
                <div class="data-card-head">
                    <div class="data-card-title">
                        <i class="fas fa-pen-nib"></i> Signatures (<?= count($signatures) ?>)
                    </div>
                </div>
                <div class="data-card-body">
                    <?php if (empty($signatures)): ?>
                    <div class="empty-state" style="padding:24px">
                        <i class="fas fa-pen-nib"></i>
                        <p>Aucune signature pour le moment</p>
                    </div>
                    <?php else: foreach ($signatures as $sig): ?>
                    <div style="display:flex;gap:14px;align-items:flex-start;padding:14px;background:#f8fafc;border-radius:9px;margin-bottom:10px">
                        <div style="width:38px;height:38px;border-radius:50%;background:var(--al);color:var(--at);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;flex-shrink:0">
                            <?= initiales($sig['nom_user']) ?>
                        </div>
                        <div style="flex:1">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                                <div>
                                    <span style="font-weight:700;font-size:.88rem"><?= htmlspecialchars($sig['nom_user']) ?></span>
                                    <span style="font-size:.74rem;color:#94a3b8;margin-left:8px">
                                        <?= date('d/m/Y H:i', strtotime($sig['date_sign'])) ?>
                                    </span>
                                </div>
                                <span class="badge b-signe">Signé</span>
                            </div>
                            <?php if (!empty($sig['image_signature'])): ?>
                            <img src="<?= BASE_URL ?>/assets/signatures/<?= htmlspecialchars($sig['image_signature']) ?>"
                                 alt="Signature de <?= htmlspecialchars($sig['nom_user']) ?>"
                                 style="max-height:60px;border:1px solid #e2e8f0;border-radius:7px;background:#fff;padding:5px;cursor:pointer"
                                 onclick="openImgViewer('<?= BASE_URL ?>/assets/signatures/<?= htmlspecialchars($sig['image_signature']) ?>', 'Signature de <?= htmlspecialchars(addslashes($sig['nom_user'])) ?>')">
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

        </div>

        <!-- ══ COLONNE DROITE ════════════════════════════════════ -->
        <div>

            <!-- Actions rapides -->
            <div class="data-card" style="margin-bottom:18px">
                <div class="data-card-head">
                    <div class="data-card-title"><i class="fas fa-bolt"></i> Actions rapides</div>
                </div>
                <div class="data-card-body" style="display:flex;flex-direction:column;gap:9px">

                    <?php if ($hasFile): ?>
                    <button onclick="openViewer()" class="btn-view-doc" style="justify-content:center;width:100%">
                        <i class="fas fa-eye"></i> Voir le document
                    </button>
                    <?php endif; ?>

                    <?php if (in_array($doc['statut'], ['en attente', 'envoyé'])): ?>
                    <a href="<?= BASE_URL ?>/views/documents/signer.php?id=<?= $id ?>"
                       class="btn btn-success" style="justify-content:center">
                        <i class="fas fa-pen-nib"></i> Signer
                    </a>
                    <?php endif; ?>

                    <?php if ($hasFile): ?>
                    <a href="<?= $fichierUrl ?>" download class="btn btn-info" style="justify-content:center">
                        <i class="fas fa-download"></i> Télécharger
                    </a>
                    <button onclick="window.print()" class="btn btn-ghost" style="justify-content:center">
                        <i class="fas fa-print"></i> Imprimer
                    </button>
                    <?php endif; ?>

                    <?php if ($role === 'Administrateur' && $doc['statut'] !== 'archivé'): ?>
                    <a href="<?= BASE_URL ?>/controllers/DocumentController.php?action=archiver&id=<?= $id ?>"
                       class="btn btn-ghost" style="justify-content:center">
                        <i class="fas fa-archive"></i> Archiver
                    </a>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Chronologie -->
            <div class="data-card">
                <div class="data-card-head">
                    <div class="data-card-title"><i class="fas fa-clock"></i> Chronologie</div>
                </div>
                <div class="data-card-body">
                    <div style="position:relative;padding-left:22px">
                        <?php $statusColors = ['signé'=>'#22c55e','refusé'=>'#ef4444','archivé'=>'#a855f7','en attente'=>'#eab308','envoyé'=>'#3b82f6']; ?>

                        <!-- Création -->
                        <div style="position:relative;margin-bottom:20px">
                            <div style="position:absolute;left:-22px;top:0;width:11px;height:11px;background:var(--a);border-radius:50%;border:2px solid #fff;box-shadow:0 0 0 2px var(--a)"></div>
                            <div style="position:absolute;left:-17px;top:11px;width:1px;height:calc(100% + 9px);background:#e2e8f0"></div>
                            <div style="font-size:.82rem;font-weight:700;color:#1e293b">Création</div>
                            <div style="font-size:.75rem;color:#94a3b8;margin-top:2px"><?= date('d/m/Y H:i', strtotime($doc['date'])) ?></div>
                            <div style="font-size:.75rem;color:#64748b">par <?= htmlspecialchars($doc['exp_nom'] ?? '?') ?></div>
                        </div>

                        <!-- Envoi -->
                        <?php if ($doc['dest_nom']): ?>
                        <div style="position:relative;margin-bottom:20px">
                            <div style="position:absolute;left:-22px;top:0;width:11px;height:11px;background:#3b82f6;border-radius:50%;border:2px solid #fff;box-shadow:0 0 0 2px #3b82f6"></div>
                            <div style="position:absolute;left:-17px;top:11px;width:1px;height:calc(100% + 9px);background:#e2e8f0"></div>
                            <div style="font-size:.82rem;font-weight:700;color:#1e293b">Envoyé</div>
                            <div style="font-size:.75rem;color:#94a3b8;margin-top:2px">
                                à <?= htmlspecialchars($doc['dest_nom']) ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Signatures dans la timeline -->
                        <?php foreach ($signatures as $sig): ?>
                        <div style="position:relative;margin-bottom:20px">
                            <div style="position:absolute;left:-22px;top:0;width:11px;height:11px;background:#22c55e;border-radius:50%;border:2px solid #fff;box-shadow:0 0 0 2px #22c55e"></div>
                            <div style="position:absolute;left:-17px;top:11px;width:1px;height:calc(100% + 9px);background:#e2e8f0"></div>
                            <div style="font-size:.82rem;font-weight:700;color:#1e293b">Signé</div>
                            <div style="font-size:.75rem;color:#94a3b8;margin-top:2px">
                                par <?= htmlspecialchars($sig['nom_user']) ?>
                            </div>
                            <div style="font-size:.73rem;color:#94a3b8">
                                <?= date('d/m/Y H:i', strtotime($sig['date_sign'])) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <!-- Statut actuel -->
                        <div style="position:relative">
                            <div style="position:absolute;left:-22px;top:0;width:11px;height:11px;
                                        background:<?= $statusColors[$doc['statut']] ?? '#94a3b8' ?>;
                                        border-radius:50%;border:2px solid #fff;
                                        box-shadow:0 0 0 2px <?= $statusColors[$doc['statut']] ?? '#94a3b8' ?>"></div>
                            <div style="font-size:.82rem;font-weight:700;color:#1e293b">Statut actuel</div>
                            <div style="margin-top:5px">
                                <span class="badge <?= $statusMap[$doc['statut']] ?? 'b-attente' ?>">
                                    <?= $statusLabel[$doc['statut']] ?>
                                </span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<!-- ══ MODAL ENVOI ════════════════════════════════════════════ -->
<div class="modal-overlay" id="sendModal">
    <div class="modal-box">
        <div class="modal-head">
            <h5><i class="fas fa-paper-plane" style="margin-right:8px"></i>Envoyer le document</h5>
            <button class="modal-close" onclick="document.getElementById('sendModal').classList.remove('open')">×</button>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/controllers/DocumentController.php?action=envoyer"
              class="modal-body">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="id_doc"     value="<?= $id ?>">
            <div class="form-group">
                <label class="form-label"><i class="fas fa-user"></i> Destinataire</label>
                <select name="destinataire_id" class="form-control" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($allUsers as $u): if ($u['id_user'] == $uid) continue; ?>
                    <option value="<?= $u['id_user'] ?>">
                        <?= htmlspecialchars($u['nom_user']) ?> — <?= htmlspecialchars($u['nom_role']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-foot">
                <button type="button"
                        onclick="document.getElementById('sendModal').classList.remove('open')"
                        class="btn btn-ghost">Annuler</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Envoyer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══ MODAL VISUALISEUR ══════════════════════════════════════ -->
<div class="viewer-modal" id="viewerModal">
    <div class="viewer-box">

        <div class="viewer-head">
            <div class="viewer-head-title">
                <i class="fas fa-file-alt" style="margin-right:8px;opacity:.6"></i>
                <?= htmlspecialchars($doc['nom_doc']) ?>
                <?php if ($fichier): ?>
                — <span style="opacity:.6;font-weight:400"><?= htmlspecialchars($fichier) ?></span>
                <?php endif; ?>
            </div>
            <div class="viewer-head-actions">
                <?php if ($hasFile): ?>
                <?php if ($hasFichierSigne && $isPdf): ?>
                <a href="<?= htmlspecialchars($fichierSigneUrl) ?>" download
                   class="viewer-btn viewer-btn-dl" title="Télécharger le PDF signé">
                    <i class="fas fa-file-signature"></i> Télécharger signé
                </a>
                <a href="<?= htmlspecialchars($fichierUrl) ?>" download
                   class="viewer-btn" style="background:rgba(255,255,255,.1);color:#fff;font-size:.78rem;padding:6px 10px;border-radius:6px;text-decoration:none" title="Document original">
                    <i class="fas fa-file-alt"></i> Original
                </a>
                <?php else: ?>
                <a href="<?= htmlspecialchars($fichierUrl) ?>" download
                   class="viewer-btn viewer-btn-dl">
                    <i class="fas fa-download"></i> Télécharger
                </a>
                <?php endif; ?>
                <?php endif; ?>
                <button class="viewer-btn viewer-btn-cls" onclick="closeViewer()" title="Fermer">×</button>
            </div>
        </div>

        <div class="viewer-body" id="viewerBody">
            <?php if ($isPdf): ?>
                <!-- PDF → iframe natif du navigateur (PDF signé si disponible) -->
                <?php if ($hasFichierSigne): ?>
                <div style="background:#f0fdf4;border-bottom:1px solid #bbf7d0;padding:8px 16px;font-size:.78rem;color:#166534;display:flex;align-items:center;gap:8px">
                    <i class="fas fa-file-signature"></i>
                    <span>Vous visualisez le document avec la signature numérique apposée en pied de page.</span>
                    <a href="<?= htmlspecialchars($fichierUrl) ?>" download style="margin-left:auto;color:#166534;text-decoration:underline;font-size:.75rem">Document original</a>
                </div>
                <?php endif; ?>
                <iframe src="<?= htmlspecialchars($viewerUrl) ?>#toolbar=1&navpanes=0&page=1"
                        title="<?= htmlspecialchars($doc['nom_doc']) ?>"></iframe>

            <?php elseif ($isImage): ?>
                <!-- Image → tag img -->
                <img src="<?= htmlspecialchars($fichierUrl) ?>"
                     alt="<?= htmlspecialchars($doc['nom_doc']) ?>">

            <?php elseif ($isDoc): ?>
                <!-- Word → Google Docs Viewer en ligne -->
                <iframe src="https://docs.google.com/gview?url=<?= urlencode((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off'?'https':'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>&embedded=true"
                        style="width:100%;min-height:70vh;border:none;display:block"
                        title="Aperçu Word"></iframe>

            <?php elseif ($hasFile): ?>
                <!-- Autre format — pas prévisualisable -->
                <div class="viewer-nopreview">
                    <i class="fas fa-file"></i>
                    <p>Ce type de fichier (<strong><?= strtoupper($ext) ?></strong>) ne peut pas être prévisualisé directement dans le navigateur.</p>
                    <a href="<?= htmlspecialchars($fichierUrl) ?>" download
                       class="btn-view-doc">
                        <i class="fas fa-download"></i> Télécharger le fichier
                    </a>
                </div>

            <?php else: ?>
                <div class="viewer-nopreview">
                    <i class="fas fa-folder-open"></i>
                    <p>Aucun fichier joint à ce document.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- ══ MODAL VISUALISEUR IMAGE SIGNATURE ══════════════════════ -->
<div class="viewer-modal" id="imgViewer">
    <div class="viewer-box" style="max-width:500px">
        <div class="viewer-head">
            <div class="viewer-head-title" id="imgViewerTitle">Signature</div>
            <div class="viewer-head-actions">
                <button class="viewer-btn viewer-btn-cls" onclick="document.getElementById('imgViewer').classList.remove('open')">×</button>
            </div>
        </div>
        <div class="viewer-body" style="background:#f8fafc;min-height:200px;padding:20px">
            <img id="imgViewerSrc" src="" alt="Signature"
                 style="max-width:100%;max-height:60vh;object-fit:contain;display:block;margin:auto;border:1px solid #e2e8f0;border-radius:8px;background:#fff;padding:8px">
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
document.getElementById('pageTitle').textContent = 'Détail document';

/* ── Ouvrir le visualiseur principal ── */
function openViewer() {
    document.getElementById('viewerModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

/* ── Fermer le visualiseur principal ── */
function closeViewer() {
    document.getElementById('viewerModal').classList.remove('open');
    document.body.style.overflow = '';
}

/* ── Visualiseur image signature ── */
function openImgViewer(src, title) {
    document.getElementById('imgViewerSrc').src   = src;
    document.getElementById('imgViewerTitle').textContent = title || 'Signature';
    document.getElementById('imgViewer').classList.add('open');
    document.body.style.overflow = 'hidden';
}

/* ── Fermer avec Échap ── */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeViewer();
        document.getElementById('imgViewer').classList.remove('open');
        document.getElementById('sendModal').classList.remove('open');
        document.body.style.overflow = '';
    }
});

/* ── Fermer en cliquant hors de la boîte ── */
document.getElementById('viewerModal').addEventListener('click', function(e) {
    if (e.target === this) closeViewer();
});
document.getElementById('imgViewer').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.remove('open');
        document.body.style.overflow = '';
    }
});
</script>
</body>
</html>
