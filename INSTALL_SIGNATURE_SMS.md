# GestDoc LBB — Installation : Signature PDF + SMS

## Ce qui a été modifié/ajouté

| Fichier | Action |
|---------|--------|
| `controllers/SignatureController.php` | ✏️  Réécrit — apposition signature PDF + SMS |
| `controllers/DocumentController.php` | ✏️  Mis à jour — SMS à l'envoi |
| `services/SmsService.php` | 🆕 Créé — service SMS Orange/Twilio |
| `config/init.php` | ✏️  Mis à jour — config SMS incluse |
| `config/schema.sql` | ✏️  Mis à jour — colonnes telephone + fichier_signe |
| `views/documents/detail.php` | ✏️  Mis à jour — affichage PDF signé |

---

## Étape 1 — Migration base de données

Dans **phpMyAdmin**, exécutez ces deux commandes :

```sql
ALTER TABLE user
    ADD COLUMN IF NOT EXISTS telephone VARCHAR(20) DEFAULT NULL;

ALTER TABLE document
    ADD COLUMN IF NOT EXISTS fichier_signe VARCHAR(255) DEFAULT NULL;
```

---

## Étape 2 — Installer FPDI pour l'apposition sur PDF

FPDI permet d'importer les pages d'un PDF existant et d'y ajouter la signature.

### Avec Composer (recommandé)
```bash
cd C:/wamp64/www/gestion_documents
composer require setasign/fpdi fpdf/fpdf
```

### Sans Composer (manuel)
1. Téléchargez FPDF : http://www.fpdf.org (fichier fpdf.php)
2. Téléchargez FPDI : https://github.com/setasign/fpdi/releases
3. Placez dans : `gestion_documents/lib/fpdf/fpdf.php`
   et           : `gestion_documents/lib/fpdi/Fpdi.php`

---

## Étape 3 — Activer les SMS

### Option A : Mode test (SMS dans un fichier log)
Dans `config/init.php`, laissez :
```php
define('SMS_PROVIDER', 'log');
```
Les SMS seront écrits dans `logs/sms.log` — pratique pour tester.

### Option B : Orange Cameroun (production)
1. Créez un compte sur https://developer.orange.com
2. Souscrivez à l'API **SMS Cameroon**
3. Récupérez votre **Bearer Token OAuth2**
4. Dans `config/init.php` :
```php
define('SMS_PROVIDER',     'orange');
define('SMS_ORANGE_TOKEN', 'votre_token_ici');
define('SMS_ORANGE_FROM',  '+237votre_numero');
define('SMS_ORANGE_SENDER','GestDoc');
```

### Option C : Twilio (alternative)
1. Créez un compte sur https://twilio.com
2. Dans `config/init.php` :
```php
define('SMS_PROVIDER',    'twilio');
define('SMS_TWILIO_SID',  'ACxxxxxxxxxxxxxxx');
define('SMS_TWILIO_TOKEN','votre_auth_token');
define('SMS_TWILIO_FROM', '+1xxxxxxxxxx');
```

---

## Étape 4 — Ajouter les numéros de téléphone

Dans l'interface GestDoc, allez dans **Utilisateurs → Modifier**
et renseignez le numéro de téléphone de chaque utilisateur
au format international : **+237691000000**

Ou directement en SQL :
```sql
UPDATE user SET telephone = '+237691000000' WHERE email = 'exemple@lbb.cm';
```

---

## Fonctionnement de la signature

1. L'utilisateur ouvre un document → clique **Signer**
2. Il dessine sa signature sur le pad
3. En cliquant **Valider** :
   - La signature PNG est sauvegardée dans `assets/signatures/`
   - Le PDF est ouvert et la signature est **apposée en pied de page de CHAQUE page**
   - Un nouveau PDF `signed_XXX.pdf` est créé dans `uploads/documents/`
   - La BDD est mise à jour (statut = signé, fichier_signe = nom du PDF)
   - Un SMS est envoyé à l'expéditeur
4. En cliquant **Voir le document**, le PDF signé s'ouvre avec la signature visible

## Ce que contient le pied de page

```
┌─────────────────────────────────────────────────────────┐
│ ══════════════════════════════════════════════════════ │
│  [Image          │ SIGNÉ NUMÉRIQUEMENT  │  GestDoc LBB │
│   de la          │ NOM DU SIGNATAIRE    │  Lycée Bil.  │
│   signature]     │ Rôle                 │  Bonabéri   │
│                  │ Le 04/04/2026 à ...  │  ✓ VALIDE   │
└─────────────────────────────────────────────────────────┘
```
