# GestDoc — Lycée Bilingue de Bonaberi
## Plateforme de gestion documentaire avec signature numérique

---

## 🚀 Installation rapide

### 1. Placer le dossier dans votre serveur
```
XAMPP (Windows) : C:/xampp/htdocs/gestion_documents/
WAMP  (Windows) : C:/wamp64/www/gestion_documents/
Linux/Ubuntu    : /var/www/html/gestion_documents/
```

### 2. Importer la base de données
1. Ouvrez **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Cliquez **Nouvelle base** → créez `gestion_documents`
3. Onglet **Importer** → choisissez `config/schema.sql` → Exécuter

### 3. Configurer la connexion
Éditez `config/init.php` lignes 43-46 :
```php
define('DB_HOST','localhost');  // Votre hôte MySQL
define('DB_USER','root');       // Votre utilisateur MySQL
define('DB_PASS','');           // Votre mot de passe MySQL
define('DB_NAME','gestion_documents');
```

### 4. Permissions des dossiers (Linux/Mac)
```bash
chmod 755 uploads/documents/ uploads/captures/ uploads/preuves/ assets/signatures/
```

### 5. Accéder à l'application
```
http://localhost/gestion_documents/
```

---

## 📋 Flux d'utilisation

### Étape 1 — Inscription admin (une seule fois)
Accédez à `http://localhost/gestion_documents/views/inscription.php`

Le **Proviseur** crée son compte administrateur. Un seul compte admin est autorisé.

### Étape 2 — Connexion admin
Après inscription → redirection automatique vers la page de connexion.

### Étape 3 — Créer les autres utilisateurs
Une fois connecté en admin :
- `Tableau de bord → Ajouter utilisateur`
- Créez les comptes Secrétaire, Censeur, Intendant, Enseignant
- Chaque utilisateur reçoit son email + mot de passe temporaire

### Étape 4 — Connexion des autres utilisateurs
Chaque utilisateur se connecte avec son email et mot de passe → est redirigé vers **son propre tableau de bord** avec sa sidebar personnalisée.

---

## 🎨 Couleurs par rôle

| Rôle          | Couleur principale | Thème CSS     |
|---------------|-------------------|---------------|
| Administrateur | Bleu marine `#0f172a` | `body.admin` |
| Secrétaire    | Vert forêt `#064e3b` | `body.secretaire` |
| Censeur       | Violet `#4c1d95`   | `body.censeur` |
| Intendant     | Rouge-brique `#7c2d12` | `body.intendant` |
| Enseignant    | Teal `#134e4a`     | `body.enseignant` |

---

## 🔐 Sécurité

- Mots de passe hashés BCrypt (coût 12)
- Protection CSRF sur tous les formulaires POST
- Contrôle d'accès par rôle (requireRole())
- Requêtes PDO préparées (protection injection SQL)
- Sessions sécurisées avec httpOnly + SameSite=Lax
- Échappement HTML sur toutes les sorties

---

## 🗄️ Structure des fichiers

```
/gestion_documents
├── index.php                    → Redirection automatique
├── accueil.php                  → Page d'accueil publique
├── debug.php                    → Outil de diagnostic (⚠️ supprimer en prod)
│
├── config/
│   ├── init.php                 → Config centrale (DB + session + helpers)
│   └── schema.sql               → Script SQL complet
│
├── controllers/
│   ├── AuthController.php       → Connexion / Inscription / Déconnexion
│   ├── DocumentController.php   → CRUD documents + envoi + archivage
│   ├── UserController.php       → CRUD utilisateurs + profil
│   ├── CategorieController.php  → CRUD catégories
│   ├── SignatureController.php  → Signature numérique
│   └── NotificationController.php → Notifications AJAX
│
├── views/
│   ├── login.php                → Page connexion
│   ├── inscription.php          → Inscription admin uniquement
│   ├── dashboard.php            → Tableau de bord (rôle-based)
│   ├── notifications.php        → Liste notifications
│   ├── statistiques.php         → Stats & graphiques (admin)
│   ├── partials/
│   │   ├── navbar.php           → Barre navigation (commune)
│   │   └── sidebar.php          → Menu latéral (rôle-based)
│   ├── documents/               → CRUD documents
│   ├── utilisateurs/            → CRUD utilisateurs
│   ├── categories/              → CRUD catégories
│   └── profil/                  → Profil utilisateur
│
├── assets/
│   ├── css/style.css            → Design system complet
│   ├── js/app.js                → JavaScript principal
│   └── signatures/              → Images signatures PNG
│
└── uploads/
    ├── documents/               → Fichiers joints
    ├── captures/                → Photos capturées par caméra
    └── preuves/                 → Preuves diverses
```

---

## 🛠️ Problème de connexion ?

Accédez à `http://localhost/gestion_documents/debug.php` pour diagnostiquer.

**Causes fréquentes :**
1. Mauvais DB_USER / DB_PASS dans `config/init.php`
2. La base `gestion_documents` n'existe pas ou `schema.sql` non importé
3. Token CSRF expiré (vider le cache du navigateur)
4. Compte désactivé (vérifier dans phpMyAdmin : table `user`, colonne `statut`)

---

## 📦 Technologies utilisées

| Technologie | Usage |
|-------------|-------|
| PHP 8.x | Backend MVC simple |
| MySQL 8 | Base de données |
| PDO | Connexion DB sécurisée |
| Signature Pad 4.1 | Signature numérique |
| Chart.js 4.4 | Graphiques statistiques |
| Font Awesome 6.5 | Icônes |
| Google Fonts (Outfit + Lora) | Typographie |
| HTML5 getUserMedia | Capture caméra |
| AJAX fetch | Notifications temps réel |

---

© 2025 Lycée Bilingue de Bonaberi — GestDoc
