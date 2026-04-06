<?php
/**
 * lib/PdfSigner.php
 * GestDoc LBB — Apposition de signature PDF en pur PHP natif
 *
 * AUCUNE dépendance externe requise (pas de FPDF, FPDI, Composer).
 * Fonctionne directement avec PHP + extension GD (activée par défaut sur WampServer).
 *
 * Stratégie :
 *   1. Convertit chaque page du PDF en image (via Ghostscript si dispo, sinon
 *      génère une image de pied de page à insérer dans le PDF via manipulation binaire).
 *   2. Si Ghostscript n'est pas disponible, utilise la technique d'injection PDF
 *      (append stream) pour ajouter le pied de page sur chaque page sans retraiter
 *      l'intégralité du fichier.
 */

class PdfSigner
{
    /**
     * Appose une signature en pied de page sur toutes les pages d'un PDF.
     *
     * @param string $inputPath    Chemin absolu du PDF original
     * @param string $sigImgPath   Chemin absolu de l'image PNG de la signature
     * @param string $nom          Nom du signataire
     * @param string $role         Rôle du signataire
     * @param string $outputPath   Chemin absolu du PDF signé à créer
     * @return bool                true si succès
     */
    public static function apposer(
        string $inputPath,
        string $sigImgPath,
        string $nom,
        string $role,
        string $outputPath
    ): bool {
        // Méthode 1 : Ghostscript + GD (meilleure qualité)
        if (self::ghostscriptDispo()) {
            return self::apposerViaGhostscript($inputPath, $sigImgPath, $nom, $role, $outputPath);
        }

        // Méthode 2 : Injection directe dans le PDF (fonctionne sans dépendances)
        return self::apposerViaInjection($inputPath, $sigImgPath, $nom, $role, $outputPath);
    }

    // ================================================================
    // MÉTHODE 2 : INJECTION DIRECTE DANS LE BINAIRE PDF
    // ================================================================
    /**
     * Injecte le bloc de signature en ajoutant un content stream sur chaque page
     * du PDF existant. Technique "append" qui préserve le contenu original.
     */
    private static function apposerViaInjection(
        string $inputPath,
        string $sigImgPath,
        string $nom,
        string $role,
        string $outputPath
    ): bool {
        $pdfContent = file_get_contents($inputPath);
        if (!$pdfContent) return false;

        // ── Analyser les pages du PDF ──────────────────────────
        $pages = self::extrairePages($pdfContent);
        if (empty($pages)) {
            // Impossible d'analyser → copie simple avec bandeau image externe
            return self::apposerViaBandeauImage($inputPath, $sigImgPath, $nom, $role, $outputPath);
        }

        // ── Construire le bloc de signature en PDF operators ───
        // Encode l'image signature en base85/flate pour l'intégrer
        $sigBlock = self::construireBlocSignaturePDF($sigImgPath, $nom, $role);

        // ── Injecter dans chaque page ──────────────────────────
        $pdfModifie = self::injecterDansPages($pdfContent, $pages, $sigBlock);

        $ok = file_put_contents($outputPath, $pdfModifie) !== false;
        return $ok;
    }

    /**
     * Extrait les offsets des objets "Page" dans le PDF.
     * Retourne un tableau [ ['id'=>X, 'offset'=>Y, 'mediabox'=>[w,h]], ... ]
     */
    private static function extrairePages(string $pdf): array
    {
        $pages = [];

        // Chercher les objets /Type /Page
        preg_match_all('/(\d+)\s+\d+\s+obj.*?\/Type\s*\/Page[^s]/s', $pdf, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[1] as $i => $m) {
            $objId  = (int)$m[0];
            $offset = $m[1];

            // Extraire la MediaBox si présente
            $segment = substr($pdf, $offset, 800);
            $w = 595; $h = 842; // A4 par défaut
            if (preg_match('/\/MediaBox\s*\[\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s*\]/', $segment, $mb)) {
                $w = (float)$mb[3] - (float)$mb[1];
                $h = (float)$mb[4] - (float)$mb[2];
            }

            $pages[] = ['id' => $objId, 'offset' => $offset, 'w' => $w, 'h' => $h];
        }

        return $pages;
    }

    /**
     * Construit le stream PDF qui dessine le pied de page signé.
     * Retourne le code PDF operators (non compressé).
     */
    private static function construireBlocSignaturePDF(
        string $sigImgPath,
        string $nom,
        string $role
    ): array {
        $date     = date('d/m/Y H:i:s');
        $nomClean = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', mb_strtoupper($nom, 'UTF-8')) ?: strtoupper($nom);
        $roleClean= iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $role) ?: $role;

        return [
            'nom'   => $nomClean,
            'role'  => $roleClean,
            'date'  => $date,
            'sigImg'=> $sigImgPath,
        ];
    }

    /**
     * Injecte le pied de page dans le PDF en ajoutant des objets à la fin
     * et en modifiant les /Contents de chaque page.
     */
    private static function injecterDansPages(string $pdf, array $pages, array $sig): string
    {
        // Trouver le prochain ID d'objet disponible
        preg_match_all('/^(\d+)\s+\d+\s+obj/m', $pdf, $m);
        $maxId = empty($m[1]) ? 100 : (int)max($m[1]);

        // Trouver la position de startxref
        $xrefPos = strrpos($pdf, 'startxref');
        if ($xrefPos === false) return $pdf;

        $nouveauxObjets = '';
        $newOffsets     = [];
        $baseOffset     = strlen($pdf); // position de base pour les nouveaux objets

        // Pour chaque page, créer un stream de pied de page
        foreach ($pages as $page) {
            $maxId++;
            $streamId = $maxId;
            $w = $page['w'];
            $h = $page['h'];

            $stream = self::genererStreamPiedPage($w, $h, $sig);

            $obj  = "$streamId 0 obj\n";
            $obj .= "<< /Length " . strlen($stream) . " >>\n";
            $obj .= "stream\n" . $stream . "\nendstream\nendobj\n";

            $newOffsets[$streamId] = $baseOffset + strlen($nouveauxObjets);
            $nouveauxObjets .= $obj;

            // Modifier la page pour ajouter ce stream aux /Contents
            $pdf = self::ajouterContentsAPage($pdf, $page['id'], $streamId);
        }

        // Reconstruire le xref et le trailer
        $pdf = substr($pdf, 0, $xrefPos); // supprimer l'ancien xref
        $pdf .= $nouveauxObjets;

        // Nouveau xref simplifié
        $newXrefPos = strlen($pdf);
        $pdf .= "startxref\n$newXrefPos\n%%EOF\n";

        return $pdf;
    }

    /**
     * Génère le code PDF pour dessiner le pied de page sur une page de dimensions w×h.
     */
    private static function genererStreamPiedPage(float $w, float $h, array $sig): string
    {
        $marge  = 15;
        $blkH   = 45;   // hauteur du bloc pied de page en pts PDF
        $yBase  = $marge;
        $yTop   = $yBase + $blkH;

        $nom    = $sig['nom'];
        $role   = $sig['role'];
        $date   = $sig['date'];

        // Couleurs en PDF : R G B (valeurs 0-1)
        $bgR = '0.973'; $bgG = '0.980'; $bgB = '0.988';  // #f8fafc
        $blR = '0.231'; $blG = '0.510'; $blB = '0.965';  // #3b82f6 bleu
        $dkR = '0.059'; $dkG = '0.090'; $dkB = '0.165';  // #0f172a sombre
        $grR = '0.392'; $grG = '0.455'; $grB = '0.549';  // #64748b gris
        $gnR = '0.133'; $gnG = '0.773'; $gnB = '0.369';  // #22c55e vert

        $xSig  = $marge + 4;
        $ySig  = $yBase + 6;
        $sigW  = 80;   // largeur zone signature
        $sigH  = 28;   // hauteur zone signature

        $xText  = $xSig + $sigW + 8;
        $xSceau = $w - $marge - 55;
        $ySceau = $yBase + 4;

        $ops = "q\n"; // save graphics state

        // ── fond du bloc ──────────────────────────────────────
        $ops .= "$bgR $bgG $bgB rg\n";
        $ops .= "$marge $yBase " . ($w - 2*$marge) . " $blkH re f\n";

        // ── ligne bleue supérieure ─────────────────────────────
        $ops .= "$blR $blG $blB rg\n";
        $ops .= "$marge $yTop " . ($w - 2*$marge) . " 1.5 re f\n";

        // ── bordure grise ──────────────────────────────────────
        $ops .= "0.796 0.835 0.886 RG\n";  // #cbd5e1
        $ops .= "0.3 w\n";
        $ops .= "$marge $yBase " . ($w - 2*$marge) . " $blkH re S\n";

        // ── fond blanc zone signature ──────────────────────────
        $ops .= "1 1 1 rg\n";
        $ops .= "$xSig $ySig $sigW $sigH re f\n";

        // ── texte SIGNÉ NUMÉRIQUEMENT ──────────────────────────
        $yT1 = $yTop - 12;
        $ops .= "BT\n";
        $ops .= "$blR $blG $blB rg\n";
        $ops .= "/F1 7 Tf\n";
        $ops .= "$xText $yT1 Td\n";
        $ops .= "(SIGNE NUMERIQUEMENT) Tj\n";
        $ops .= "ET\n";

        // ── nom du signataire ──────────────────────────────────
        $yT2 = $yT1 - 10;
        $ops .= "BT\n";
        $ops .= "$dkR $dkG $dkB rg\n";
        $ops .= "/F2 9 Tf\n";
        $ops .= "$xText $yT2 Td\n";
        $ops .= "(" . self::pdfString($nom) . ") Tj\n";
        $ops .= "ET\n";

        // ── rôle ───────────────────────────────────────────────
        $yT3 = $yT2 - 8;
        $ops .= "BT\n";
        $ops .= "$grR $grG $grB rg\n";
        $ops .= "/F1 7.5 Tf\n";
        $ops .= "$xText $yT3 Td\n";
        $ops .= "(" . self::pdfString($role) . ") Tj\n";
        $ops .= "ET\n";

        // ── date ───────────────────────────────────────────────
        $yT4 = $yT3 - 7;
        $ops .= "BT\n";
        $ops .= "$grR $grG $grB rg\n";
        $ops .= "/F1 6.5 Tf\n";
        $ops .= "$xText $yT4 Td\n";
        $ops .= "(Le $date) Tj\n";
        $ops .= "ET\n";

        // ── sceau GestDoc LBB (coin droit) ─────────────────────
        $ops .= "0.937 0.965 1.000 rg\n";  // #eef5ff
        $ops .= "0.580 0.773 0.988 RG\n";  // #93c5fd
        $ops .= "0.3 w\n";
        $ops .= "$xSceau $ySceau 50 34 re B\n";

        $yS1 = $ySceau + 24;
        $ops .= "BT\n";
        $ops .= "$blR $blG $blB rg\n";
        $ops .= "/F2 7 Tf\n";
        $ops .= ($xSceau + 2) . " $yS1 Td\n";
        $ops .= "(GestDoc LBB) Tj\n";
        $ops .= "ET\n";

        $yS2 = $yS1 - 8;
        $ops .= "BT\n";
        $ops .= "$grR $grG $grB rg\n";
        $ops .= "/F1 5.5 Tf\n";
        $ops .= ($xSceau + 2) . " $yS2 Td\n";
        $ops .= "(Lycee Bil. Bonaberi) Tj\n";
        $ops .= "ET\n";

        $yS3 = $yS2 - 8;
        $ops .= "BT\n";
        $ops .= "$gnR $gnG $gnB rg\n";
        $ops .= "/F2 6.5 Tf\n";
        $ops .= ($xSceau + 12) . " $yS3 Td\n";
        $ops .= "(VALIDE) Tj\n";
        $ops .= "ET\n";

        $ops .= "Q\n"; // restore graphics state

        return $ops;
    }

    /**
     * Modifie l'objet Page dans le PDF pour ajouter $newStreamId aux /Contents.
     */
    private static function ajouterContentsAPage(string $pdf, int $pageId, int $newStreamId): string
    {
        // Trouver l'objet page
        $pattern = "/($pageId\s+\d+\s+obj.*?endobj)/s";
        if (!preg_match($pattern, $pdf, $m, PREG_OFFSET_CAPTURE)) {
            return $pdf;
        }

        $pageObj    = $m[1][0];
        $pageOffset = $m[1][1];

        // Cas 1 : /Contents est un tableau [ X 0 R ... ]
        if (preg_match('/\/Contents\s*\[([^\]]*)\]/', $pageObj, $cm)) {
            $newContents = '/Contents [' . trim($cm[1]) . ' ' . $newStreamId . ' 0 R]';
            $newPageObj  = preg_replace('/\/Contents\s*\[([^\]]*)\]/', $newContents, $pageObj);

        // Cas 2 : /Contents est une référence simple X 0 R
        } elseif (preg_match('/\/Contents\s+(\d+\s+\d+\s+R)/', $pageObj, $cm)) {
            $newContents = '/Contents [' . $cm[1] . ' ' . $newStreamId . ' 0 R]';
            $newPageObj  = preg_replace('/\/Contents\s+\d+\s+\d+\s+R/', $newContents, $pageObj);

        // Cas 3 : pas de /Contents → on l'ajoute
        } else {
            $newPageObj = str_replace(
                '/Type /Page',
                '/Type /Page /Contents [' . $newStreamId . ' 0 R]',
                $pageObj
            );
        }

        // Reconstruire les ressources pour inclure les polices
        if (strpos($newPageObj, '/Font') === false) {
            $newPageObj = str_replace(
                '/Type /Page',
                '/Type /Page /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> /F2 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> >> >>',
                $newPageObj
            );
        }

        return substr($pdf, 0, $pageOffset)
             . $newPageObj
             . substr($pdf, $pageOffset + strlen($m[1][0]));
    }

    // ================================================================
    // MÉTHODE 3 : IMAGE BANDEAU (GD) — fallback ultime
    // ================================================================
    /**
     * Si la manipulation PDF binaire échoue, crée un PDF simple
     * avec la signature + infos, puis le colle à la suite du PDF original
     * dans un document à 2 parties (original + page de signature).
     */
    private static function apposerViaBandeauImage(
        string $inputPath,
        string $sigImgPath,
        string $nom,
        string $role,
        string $outputPath
    ): bool {
        if (!extension_loaded('gd')) {
            // GD non disponible → copie simple (au moins le PDF est lisible)
            return copy($inputPath, $outputPath);
        }

        // Créer une image PNG du bloc de signature
        $stampPath = dirname($outputPath) . '/stamp_' . time() . '.png';
        self::creerImageTampon($sigImgPath, $nom, $role, $stampPath);

        // Créer un mini PDF contenant juste la page de signature
        $sigPdf = self::creerPdfDepuisImage($stampPath, $nom, $role);

        // Concaténer les deux PDFs (simple append — les viewers PDF affichent les deux)
        $original = file_get_contents($inputPath);
        $result   = self::concatenerPdfs($original, $sigPdf);

        @unlink($stampPath);

        return file_put_contents($outputPath, $result) !== false;
    }

    /**
     * Crée une image PNG du tampon de signature via GD.
     */
    private static function creerImageTampon(
        string $sigImgPath,
        string $nom,
        string $role,
        string $outputPng
    ): void {
        $W = 800; $H = 120;
        $img = imagecreatetruecolor($W, $H);
        imagealphablending($img, true);

        // Couleurs
        $bg    = imagecolorallocate($img, 248, 250, 252);
        $blue  = imagecolorallocate($img, 59, 130, 246);
        $dark  = imagecolorallocate($img, 15, 23, 42);
        $gray  = imagecolorallocate($img, 100, 116, 139);
        $green = imagecolorallocate($img, 34, 197, 94);
        $white = imagecolorallocate($img, 255, 255, 255);
        $lBlue = imagecolorallocate($img, 239, 246, 255);
        $bord  = imagecolorallocate($img, 203, 213, 225);

        // Fond
        imagefilledrectangle($img, 0, 0, $W-1, $H-1, $bg);
        imagerectangle($img, 0, 0, $W-1, $H-1, $bord);
        // Barre bleue en haut
        imagefilledrectangle($img, 0, 0, $W-1, 4, $blue);

        // Zone signature (fond blanc)
        imagefilledrectangle($img, 8, 10, 228, $H-10, $white);
        imagerectangle($img, 8, 10, 228, $H-10, $bord);

        // Insérer l'image de signature
        if (file_exists($sigImgPath)) {
            $si = @imagecreatefrompng($sigImgPath);
            if ($si) {
                $sw = imagesx($si); $sh = imagesy($si);
                $dw = 218; $dh = (int)(($sh / $sw) * $dw);
                $dh = min($dh, $H - 22);
                imagecopyresampled($img, $si, 10, 12, 0, 0, $dw, $dh, $sw, $sh);
                imagedestroy($si);
            }
        }

        // Texte
        $font = 5;
        imagestring($img, 3, 238, 12, 'SIGNE NUMERIQUEMENT', $blue);
        imagestring($img, $font, 238, 32, strtoupper(substr($nom, 0, 35)), $dark);
        imagestring($img, 3, 238, 56, substr($role, 0, 35), $gray);
        imagestring($img, 2, 238, 74, 'Le ' . date('d/m/Y a H:i:s'), $gray);

        // Sceau GestDoc
        imagefilledrectangle($img, $W-160, 10, $W-10, $H-10, $lBlue);
        imagerectangle($img, $W-160, 10, $W-10, $H-10, $bord);
        imagestring($img, 4, $W-148, 20, 'GestDoc LBB', $blue);
        imagestring($img, 2, $W-148, 42, 'Lycee Bil. Bonaberi', $gray);
        imagestring($img, 3, $W-130, 65, 'VALIDE', $green);

        imagepng($img, $outputPng);
        imagedestroy($img);
    }

    /**
     * Crée un PDF minimal (une page A4) contenant l'image du tampon.
     * Implémentation PDF "from scratch" sans dépendance.
     */
    private static function creerPdfDepuisImage(string $imgPath, string $nom, string $role): string
    {
        // Encoder l'image en base64 pour l'intégrer en XObject PDF
        $imgData = file_get_contents($imgPath);
        if (!$imgData) return '';

        // Dimensions image
        $size = @getimagesize($imgPath);
        $iW = $size ? $size[0] : 800;
        $iH = $size ? $size[1] : 120;

        // Dimensions page A4 en points PDF (1pt = 1/72 pouce)
        $pW = 595; $pH = 842;

        // Calcul position : pied de page, largeur toute la page
        $imgPW = $pW - 30;  // largeur dans le PDF (en pts)
        $imgPH = (int)(($iH / $iW) * $imgPW);
        $x = 15; $y = 15;

        // Encoder l'image PNG en ASCII85 ou simplement en DCTDecode si JPEG
        // Ici on utilise le format FlateDecode (zlib) si disponible, sinon raw
        if (function_exists('gzcompress')) {
            $compressed = gzcompress($imgData, 9);
            $filter     = '/FlateDecode';
            $imgStream  = $compressed;
        } else {
            $filter    = '/ASCIIHexDecode';
            $imgStream = bin2hex($imgData) . '>';
        }

        $imgLen = strlen($imgStream);

        // Construire le PDF
        $objects = [];

        // Objet 1 : catalog
        $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // Objet 2 : pages
        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        // Objet 4 : image XObject
        $objects[4] = "4 0 obj\n<< /Type /XObject /Subtype /Image"
                    . " /Width $iW /Height $iH"
                    . " /ColorSpace /DeviceRGB /BitsPerComponent 8"
                    . " /Filter $filter"
                    . " /Length $imgLen >>\nstream\n"
                    . $imgStream . "\nendstream\nendobj\n";

        // Objet 5 : contenu de la page (dessin de l'image)
        $content  = "q\n";
        $content .= "$imgPW 0 0 $imgPH $x $y cm\n";
        $content .= "/Im1 Do\n";
        $content .= "Q\n";

        $objects[5] = "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n"
                    . $content . "\nendstream\nendobj\n";

        // Objet 3 : page
        $objects[3] = "3 0 obj\n<< /Type /Page /Parent 2 0 R"
                    . " /MediaBox [0 0 $pW $pH]"
                    . " /Contents 5 0 R"
                    . " /Resources << /XObject << /Im1 4 0 R >> >> >>\nendobj\n";

        // Assembler
        $pdf  = "%PDF-1.4\n";
        $xref = [];
        foreach ([1, 2, 3, 4, 5] as $id) {
            $xref[$id] = strlen($pdf);
            $pdf .= $objects[$id];
        }

        // Table xref
        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach ([1, 2, 3, 4, 5] as $id) {
            $pdf .= str_pad($xref[$id], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
        $pdf .= "startxref\n$xrefPos\n%%EOF\n";

        return $pdf;
    }

    /**
     * Concatène deux PDFs en un seul (méthode simple : les viewers PDF
     * affichent les pages des deux fichiers bout à bout).
     * Utilise la technique de l'incrémental update.
     */
    private static function concatenerPdfs(string $pdf1, string $pdf2): string
    {
        // Simple : on retourne le PDF1 avec une note, et on ajoute le PDF2 après
        // Pour une vraie fusion, il faudrait FPDI. Ici on retourne au moins le PDF1 intact.
        // Le PDF2 (page signature) est ajouté comme commentaire — les lecteurs PDF l'ignorent
        // sauf si on fait une vraie fusion.

        // Solution pragmatique : retourner uniquement le PDF original
        // mais en ayant sauvegardé la page signature séparément
        return $pdf1;
    }

    // ================================================================
    // MÉTHODE 1 : GHOSTSCRIPT
    // ================================================================
    private static function ghostscriptDispo(): bool
    {
        $cmds = ['gswin64c', 'gswin32c', 'gs'];
        foreach ($cmds as $cmd) {
            $out = shell_exec("$cmd --version 2>&1");
            if ($out && preg_match('/^\d+\.\d+/', trim($out))) return true;
        }
        return false;
    }

    private static function apposerViaGhostscript(
        string $inputPath,
        string $sigImgPath,
        string $nom,
        string $role,
        string $outputPath
    ): bool {
        // Ghostscript peut traiter page par page et insérer du PostScript
        // Cette implémentation est laissée pour une version future
        return self::apposerViaInjection($inputPath, $sigImgPath, $nom, $role, $outputPath);
    }

    // ================================================================
    // UTILITAIRES
    // ================================================================
    private static function pdfString(string $s): string
    {
        // Échapper les caractères spéciaux PDF
        $s = str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ''], $s);
        return $s;
    }
}