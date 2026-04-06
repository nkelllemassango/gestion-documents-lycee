<?php
require_once __DIR__ . '/../../config/init.php';
requireLogin();
$user=getUser();$role=$user['nom_role'];$uid=$user['id_user'];$css=roleCss($role);
$pdo=getDB();$unread=getUnreadCount($uid);
$id=(int)($_GET['id']??0);
$stmt=$pdo->prepare("SELECT d.*,c.nom_categorie,u1.nom_user exp_nom FROM document d LEFT JOIN categorie c ON d.categorie_id=c.id_categorie LEFT JOIN user u1 ON d.expediteur_id=u1.id_user WHERE d.id_doc=?");
$stmt->execute([$id]);$doc=$stmt->fetch();
if(!$doc){header('Location: '.BASE_URL.'/views/documents/index.php');exit;}
?><!DOCTYPE html>
<html lang="fr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Signature numérique</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<script>const BASE_URL='<?= BASE_URL ?>';</script>
</head><body class="<?= $css ?>">
<?php include ROOT_PATH.'/views/partials/sidebar.php';include ROOT_PATH.'/views/partials/navbar.php'; ?>
<main class="app-main">
    <div class="page-header">
        <div><div class="page-title"><i class="fas fa-pen-nib"></i> Signature numérique</div><div class="page-sub">Document : <strong><?= htmlspecialchars($doc['nom_doc']) ?></strong></div></div>
        <a href="<?= BASE_URL ?>/views/documents/detail.php?id=<?= $id ?>" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 360px;gap:22px;max-width:1060px">
        <!-- Signature pad -->
        <div class="data-card">
            <div class="data-card-head"><div class="data-card-title"><i class="fas fa-signature"></i> Zone de signature</div></div>
            <div class="data-card-body">
                <div class="alert alert-info" style="margin-bottom:18px">
                    <i class="fas fa-info-circle"></i>
                    Signez dans la zone ci-dessous à la souris ou au doigt sur écran tactile.
                </div>

                <div class="sig-container">
                    <canvas id="sigCanvas" style="height:240px;touch-action:none"></canvas>
                    <div class="sig-toolbar">
                        <div style="display:flex;gap:8px;align-items:center">
                            <span style="font-size:.74rem;color:#64748b">Couleur :</span>
                            <div class="color-dot active" style="background:#1e293b" data-color="#1e293b" onclick="setColor(this)"></div>
                            <div class="color-dot" style="background:#1e40af" data-color="#1e40af" onclick="setColor(this)"></div>
                            <div class="color-dot" style="background:#166534" data-color="#166534" onclick="setColor(this)"></div>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="font-size:.74rem;color:#64748b">Épaisseur :</span>
                            <input type="range" id="penSize" min="1" max="6" value="2" style="width:70px" oninput="setPen(this.value)">
                        </div>
                        <button type="button" onclick="pad.clear()" class="btn btn-danger btn-sm"><i class="fas fa-eraser"></i> Effacer</button>
                    </div>
                </div>

                <form id="sigForm" method="POST" action="<?= BASE_URL ?>/controllers/SignatureController.php?action=signer" style="margin-top:18px">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id_doc" value="<?= $id ?>">
                    <input type="hidden" name="signature_data" id="sigData">
                    <div style="display:flex;gap:10px">
                        <button type="button" onclick="validerSig()" class="btn btn-primary" style="flex:1;justify-content:center"><i class="fas fa-check-circle"></i> Valider la signature</button>
                        <a href="<?= BASE_URL ?>/views/documents/detail.php?id=<?= $id ?>" class="btn btn-ghost">Annuler</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Doc info sidebar -->
        <div style="display:flex;flex-direction:column;gap:18px">
            <div class="data-card">
                <div style="background:linear-gradient(135deg,var(--p),var(--a));padding:28px 20px;text-align:center;color:#fff;border-radius:var(--r) var(--r) 0 0">
                    <i class="fas fa-file-contract" style="font-size:2.2rem;opacity:.8;display:block;margin-bottom:12px"></i>
                    <div style="font-weight:700;font-size:.95rem"><?= htmlspecialchars($doc['nom_doc']) ?></div>
                    <?php if($doc['nom_categorie']): ?><div style="font-size:.75rem;opacity:.7;margin-top:4px"><?= htmlspecialchars($doc['nom_categorie']) ?></div><?php endif; ?>
                </div>
                <div style="padding:16px;display:flex;flex-direction:column;gap:10px;font-size:.83rem">
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9">
                        <span style="color:#64748b">Expéditeur</span>
                        <span style="font-weight:600"><?= htmlspecialchars($doc['exp_nom']??'—') ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0">
                        <span style="color:#64748b">Date</span>
                        <span style="font-weight:600"><?= date('d/m/Y',strtotime($doc['date'])) ?></span>
                    </div>
                </div>
            </div>

            <div class="data-card">
                <div class="data-card-head"><div class="data-card-title"><i class="fas fa-user-check"></i> Signataire</div></div>
                <div class="data-card-body">
                    <div style="display:flex;align-items:center;gap:12px;padding:12px;background:var(--al);border-radius:9px">
                        <div style="width:42px;height:42px;border-radius:50%;background:var(--a);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;flex-shrink:0"><?= initiales($user['nom_user']) ?></div>
                        <div>
                            <div style="font-weight:700;font-size:.9rem"><?= htmlspecialchars($user['nom_user']) ?></div>
                            <div style="font-size:.75rem;color:var(--at)"><?= htmlspecialchars($role) ?></div>
                            <div style="font-size:.72rem;color:#94a3b8;margin-top:2px"><?= date('d/m/Y H:i') ?></div>
                        </div>
                    </div>
                    <p style="font-size:.78rem;color:#64748b;line-height:1.6;margin-top:12px">
                        <i class="fas fa-shield-alt" style="color:var(--a);margin-right:5px"></i>
                        En signant, vous certifiez avoir pris connaissance du document et approuvez son contenu.
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/4.1.7/signature_pad.umd.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
document.getElementById('pageTitle').textContent='Signer un document';
const canvas=document.getElementById('sigCanvas');
let pad=new SignaturePad(canvas,{backgroundColor:'rgba(255,255,255,0)',penColor:'#1e293b',minWidth:1,maxWidth:3});

function resize(){const ratio=Math.max(window.devicePixelRatio||1,1),r=canvas.parentElement.getBoundingClientRect();canvas.width=r.width*ratio;canvas.height=240*ratio;canvas.getContext('2d').scale(ratio,ratio);pad.clear();}
window.addEventListener('resize',resize);resize();

function setColor(el){document.querySelectorAll('.color-dot').forEach(d=>d.classList.remove('active'));el.classList.add('active');pad.penColor=el.dataset.color;}
function setPen(v){pad.minWidth=v*.5;pad.maxWidth=v*1.5;}
function validerSig(){
    if(pad.isEmpty()){showToast('Veuillez signer avant de valider.','warn');return;}
    document.getElementById('sigData').value=pad.toDataURL('image/png');
    document.getElementById('sigForm').submit();
}
</script>
</body></html>
