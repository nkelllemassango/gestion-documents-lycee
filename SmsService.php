<?php
/**
 * services/SmsService.php
 * GestDoc LBB — Envoi SMS avec diagnostic intégré
 *
 * CONFIGURATION dans config/init.php :
 *
 *   // Orange Cameroun (production)
 *   define('SMS_PROVIDER',      'orange');
 *   define('SMS_ORANGE_TOKEN',  'votre_token_bearer');
 *   define('SMS_ORANGE_FROM',   '+237600000000');
 *
 *   // OU Twilio
 *   define('SMS_PROVIDER',      'twilio');
 *   define('SMS_TWILIO_SID',    'ACxxxxxxxxxx');
 *   define('SMS_TWILIO_TOKEN',  'auth_token');
 *   define('SMS_TWILIO_FROM',   '+1xxxxxxxxxx');
 *
 *   // Mode test (défaut) → SMS dans logs/sms.log
 *   define('SMS_PROVIDER', 'log');
 */

class SmsService
{
    // ── Messages prédéfinis ────────────────────────────────────

    public static function alerterSignature(
        string $telephone, string $nomDest, string $nomDoc, string $nomExp
    ): bool {
        $msg = "GestDoc LBB\nBonjour {$nomDest},\n"
             . "{$nomExp} vous a transmis le document \"{$nomDoc}\" a signer.\n"
             . "Connectez-vous sur GestDoc pour le signer.";
        return self::envoyer($telephone, $msg);
    }

    public static function alerterSigne(
        string $telephone, string $nomExp, string $nomDoc, string $nomSig
    ): bool {
        $msg = "GestDoc LBB\nBonjour {$nomExp},\n"
             . "Votre document \"{$nomDoc}\" a ete signe par {$nomSig}.\n"
             . "Connectez-vous sur GestDoc pour le consulter.";
        return self::envoyer($telephone, $msg);
    }

    // Alias pour la compatibilité avec l'ancien nom
    public static function alerterSigné(
        string $telephone, string $nomExp, string $nomDoc, string $nomSig
    ): bool {
        return self::alerterSigne($telephone, $nomExp, $nomDoc, $nomSig);
    }

    public static function alerterRefus(
        string $telephone, string $nomExp, string $nomDoc
    ): bool {
        $msg = "GestDoc LBB\nBonjour {$nomExp},\n"
             . "Votre document \"{$nomDoc}\" a ete refuse.\n"
             . "Connectez-vous sur GestDoc pour plus d'informations.";
        return self::envoyer($telephone, $msg);
    }

    // ── Envoi central ──────────────────────────────────────────

    public static function envoyer(string $telephone, string $message): bool
    {
        // Normaliser le numéro
        $telephone = preg_replace('/[\s\-\.\(\)]/', '', $telephone);
        if (preg_match('/^6\d{8}$/', $telephone)) {
            $telephone = '+237' . $telephone;
        }
        if (empty($telephone)) {
            self::log("ERREUR : numéro de téléphone vide");
            return false;
        }

        $provider = defined('SMS_PROVIDER') ? SMS_PROVIDER : 'log';
        self::log("Tentative envoi SMS via '{$provider}' → {$telephone}");

        switch ($provider) {
            case 'orange': return self::envoyerOrange($telephone, $message);
            case 'twilio': return self::envoyerTwilio($telephone, $message);
            default:       return self::logSms($telephone, $message);
        }
    }

    // ── Orange Cameroun ────────────────────────────────────────

    private static function envoyerOrange(string $tel, string $msg): bool
    {
        // Vérifications préalables
        if (!function_exists('curl_init')) {
            self::log("ERREUR Orange : cURL non activé dans PHP. Activez extension=curl dans php.ini");
            return self::logSms($tel, $msg);
        }
        if (!defined('SMS_ORANGE_TOKEN') || !SMS_ORANGE_TOKEN) {
            self::log("ERREUR Orange : SMS_ORANGE_TOKEN non défini dans init.php");
            return self::logSms($tel, $msg);
        }

        $from   = defined('SMS_ORANGE_FROM')   ? SMS_ORANGE_FROM   : '';
        $sender = defined('SMS_ORANGE_SENDER') ? SMS_ORANGE_SENDER : 'GestDoc';

        if (!$from) {
            self::log("ERREUR Orange : SMS_ORANGE_FROM non défini dans init.php");
            return self::logSms($tel, $msg);
        }

        $url = 'https://api.orange.com/smsmessaging/v1/outbound/'
             . urlencode('tel:' . $from) . '/requests';

        $payload = json_encode([
            'outboundSMSMessageRequest' => [
                'address'                => 'tel:' . $tel,
                'senderAddress'          => 'tel:' . $from,
                'senderName'             => $sender,
                'outboundSMSTextMessage' => ['message' => $msg],
            ],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . SMS_ORANGE_TOKEN,
            ],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false, // tolérance WampServer local
        ]);

        $resp   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($err) {
            self::log("ERREUR Orange cURL : {$err}");
            return false;
        }
        if ($status >= 200 && $status < 300) {
            self::log("OK Orange : SMS envoyé à {$tel} (HTTP {$status})");
            return true;
        }

        self::log("ERREUR Orange HTTP {$status} → {$resp}");
        return false;
    }

    // ── Twilio ─────────────────────────────────────────────────

    private static function envoyerTwilio(string $tel, string $msg): bool
    {
        if (!function_exists('curl_init')) {
            self::log("ERREUR Twilio : cURL non activé dans PHP");
            return self::logSms($tel, $msg);
        }
        if (!defined('SMS_TWILIO_SID') || !SMS_TWILIO_SID
         || !defined('SMS_TWILIO_TOKEN') || !SMS_TWILIO_TOKEN
         || !defined('SMS_TWILIO_FROM') || !SMS_TWILIO_FROM) {
            self::log("ERREUR Twilio : SMS_TWILIO_SID / TOKEN / FROM non définis dans init.php");
            return self::logSms($tel, $msg);
        }

        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . SMS_TWILIO_SID . '/Messages.json';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_USERPWD        => SMS_TWILIO_SID . ':' . SMS_TWILIO_TOKEN,
            CURLOPT_POSTFIELDS     => http_build_query([
                'To'   => $tel,
                'From' => SMS_TWILIO_FROM,
                'Body' => $msg,
            ]),
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $resp   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($err) {
            self::log("ERREUR Twilio cURL : {$err}");
            return false;
        }
        if ($status >= 200 && $status < 300) {
            self::log("OK Twilio : SMS envoyé à {$tel} (HTTP {$status})");
            return true;
        }

        self::log("ERREUR Twilio HTTP {$status} → {$resp}");
        return false;
    }

    // ── Mode log (développement) ───────────────────────────────

    private static function logSms(string $tel, string $msg): bool
    {
        self::log("--- SMS (mode LOG) ---\nÀ : {$tel}\nMessage :\n{$msg}\n---");
        return true;
    }

    // ── Logger interne ─────────────────────────────────────────

    public static function log(string $line): void
    {
        $dir  = defined('ROOT_PATH') ? ROOT_PATH . '/logs' : __DIR__ . '/../logs';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $f    = $dir . '/sms.log';
        $entry = '[' . date('Y-m-d H:i:s') . '] ' . $line . "\n";
        @file_put_contents($f, $entry, FILE_APPEND | LOCK_EX);
        error_log('[GestDoc/SMS] ' . $line);
    }

    // ── Diagnostic (page de test) ──────────────────────────────

    /**
     * Appeler depuis sms_test.php pour diagnostiquer la config.
     * Retourne un tableau d'infos à afficher.
     */
    public static function diagnostiquer(): array
    {
        $r = [];

        // PHP
        $r[] = ['PHP version',   PHP_VERSION,   true];
        $r[] = ['cURL activé',   function_exists('curl_init') ? 'Oui ✓' : 'NON ✗ → activer extension=curl dans php.ini', function_exists('curl_init')];
        $r[] = ['GD activé',     extension_loaded('gd') ? 'Oui ✓' : 'Non (signature PDF en mode simple)', extension_loaded('gd')];

        // Provider
        $prov = defined('SMS_PROVIDER') ? SMS_PROVIDER : '(non défini = log)';
        $r[] = ['SMS_PROVIDER', $prov, true];

        if ($prov === 'orange') {
            $tok = defined('SMS_ORANGE_TOKEN') && SMS_ORANGE_TOKEN;
            $frm = defined('SMS_ORANGE_FROM')  && SMS_ORANGE_FROM;
            $r[] = ['SMS_ORANGE_TOKEN', $tok ? 'Défini ✓' : 'MANQUANT ✗', $tok];
            $r[] = ['SMS_ORANGE_FROM',  $frm ? SMS_ORANGE_FROM . ' ✓' : 'MANQUANT ✗', $frm];
        } elseif ($prov === 'twilio') {
            $sid = defined('SMS_TWILIO_SID')   && SMS_TWILIO_SID;
            $tok = defined('SMS_TWILIO_TOKEN') && SMS_TWILIO_TOKEN;
            $frm = defined('SMS_TWILIO_FROM')  && SMS_TWILIO_FROM;
            $r[] = ['SMS_TWILIO_SID',   $sid ? 'Défini ✓' : 'MANQUANT ✗', $sid];
            $r[] = ['SMS_TWILIO_TOKEN', $tok ? 'Défini ✓' : 'MANQUANT ✗', $tok];
            $r[] = ['SMS_TWILIO_FROM',  $frm ? SMS_TWILIO_FROM . ' ✓' : 'MANQUANT ✗', $frm];
        } else {
            $logPath = (defined('ROOT_PATH') ? ROOT_PATH : __DIR__.'/../') . '/logs/sms.log';
            $r[] = ['Mode LOG actif', 'SMS écrits dans logs/sms.log', true];
            $r[] = ['Fichier log',    $logPath, true];
        }

        // Connexion internet (test rapide)
        if (function_exists('curl_init')) {
            $ch = curl_init('https://api.orange.com');
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>5, CURLOPT_NOBODY=>true, CURLOPT_SSL_VERIFYPEER=>false]);
            curl_exec($ch);
            $connected = curl_getinfo($ch, CURLINFO_HTTP_CODE) > 0;
            curl_close($ch);
            $r[] = ['Connexion internet', $connected ? 'OK ✓' : 'Hors ligne ✗ (WampServer doit avoir accès internet)', $connected];
        }

        return $r;
    }
}