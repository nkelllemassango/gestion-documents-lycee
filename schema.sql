-- ============================================================
--  SCHEMA — Lycée Bilingue de Bonaberi — GestDoc
--  Importer via phpMyAdmin ou : mysql -u root -p < schema.sql
-- ============================================================
CREATE DATABASE IF NOT EXISTS gestion_documents
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_documents;

-- Rôles
CREATE TABLE IF NOT EXISTS roles (
    id_role   INT AUTO_INCREMENT PRIMARY KEY,
    nom_role  VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Utilisateurs
CREATE TABLE IF NOT EXISTS user (
    id_user       INT AUTO_INCREMENT PRIMARY KEY,
    nom_user      VARCHAR(255) NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    id_role       INT NOT NULL,
    statut        ENUM('actif','inactif') DEFAULT 'actif',
    photo         VARCHAR(255) DEFAULT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_role) REFERENCES roles(id_role) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Catégories
CREATE TABLE IF NOT EXISTS categorie (
    id_categorie  INT AUTO_INCREMENT PRIMARY KEY,
    nom_categorie VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Documents
CREATE TABLE IF NOT EXISTS document (
    id_doc          INT AUTO_INCREMENT PRIMARY KEY,
    nom_doc         VARCHAR(255) NOT NULL,
    description     TEXT,
    type            VARCHAR(50),
    fichier         VARCHAR(255),
    categorie_id    INT,
    expediteur_id   INT,
    destinataire_id INT,
    statut          ENUM('envoyé','en attente','signé','refusé','archivé') DEFAULT 'en attente',
    date            DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id)    REFERENCES categorie(id_categorie) ON DELETE SET NULL,
    FOREIGN KEY (expediteur_id)   REFERENCES user(id_user) ON DELETE SET NULL,
    FOREIGN KEY (destinataire_id) REFERENCES user(id_user) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Signatures
CREATE TABLE IF NOT EXISTS signature (
    id_sign         INT AUTO_INCREMENT PRIMARY KEY,
    id_doc          INT NOT NULL,
    id_user         INT NOT NULL,
    date_sign       DATETIME DEFAULT CURRENT_TIMESTAMP,
    image_signature VARCHAR(255),
    approuve        VARCHAR(100) DEFAULT 'en attente',
    FOREIGN KEY (id_doc)  REFERENCES document(id_doc)  ON DELETE CASCADE,
    FOREIGN KEY (id_user) REFERENCES user(id_user)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- Notifications
CREATE TABLE IF NOT EXISTS notification (
    id_notification   INT AUTO_INCREMENT PRIMARY KEY,
    id_user           INT NOT NULL,
    contenu           VARCHAR(500) NOT NULL,
    statut            VARCHAR(50) DEFAULT 'non lu',
    date_notification DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_doc            INT DEFAULT NULL,
    FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_doc)  REFERENCES document(id_doc) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Gestion (user ↔ document)
CREATE TABLE IF NOT EXISTS gerer (
    id_user INT NOT NULL,
    id_doc  INT NOT NULL,
    PRIMARY KEY (id_user, id_doc),
    FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_doc)  REFERENCES document(id_doc) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Poser (document ↔ signature)
CREATE TABLE IF NOT EXISTS poser (
    id_doc  INT NOT NULL,
    id_sign INT NOT NULL,
    approuve VARCHAR(100),
    date    DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_doc, id_sign),
    FOREIGN KEY (id_doc)  REFERENCES document(id_doc)  ON DELETE CASCADE,
    FOREIGN KEY (id_sign) REFERENCES signature(id_sign) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── DONNÉES PAR DÉFAUT ────────────────────────────────────────
INSERT IGNORE INTO roles (nom_role)
  VALUES ('Administrateur'),('Secrétaire'),('Censeur'),('Intendant'),('Enseignant');

INSERT IGNORE INTO categorie (nom_categorie)
  VALUES ('Administratif'),('Pédagogique'),('Financier'),('Circulaire'),('Rapport');

-- ── MIGRATION : champs téléphone + fichier signé ─────────────
-- Ajouter le numéro de téléphone aux utilisateurs (pour SMS)
ALTER TABLE user
    ADD COLUMN telephone VARCHAR(20) DEFAULT NULL
    COMMENT 'Numéro de téléphone pour alertes SMS (ex: +237691000000)';

-- Ajouter la colonne fichier_signe au document (PDF avec signature apposée)
ALTER TABLE document
    ADD COLUMN fichier_signe VARCHAR(255) DEFAULT NULL
    COMMENT 'Nom du fichier PDF généré avec signature en pied de page';
