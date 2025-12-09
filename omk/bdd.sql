-- BDD.sql
-- Base de données pour la gestion des Masters, Cours, Étudiants et Évaluations

-- Supprimer la base si elle existe déjà (optionnel)
DROP DATABASE IF EXISTS master_eval;

-- Créer la base
CREATE DATABASE master_eval
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE master_eval;

-- =========================
-- Table des Masters
-- =========================
CREATE TABLE master (
  id_master INT AUTO_INCREMENT PRIMARY KEY,
  code_master VARCHAR(50) NOT NULL UNIQUE,
  titre VARCHAR(255) NOT NULL,
  description TEXT
) ENGINE=InnoDB;

-- =========================
-- Table des Cours
-- =========================
CREATE TABLE cours (
  id_cours INT AUTO_INCREMENT PRIMARY KEY,
  code_cours VARCHAR(50) NOT NULL UNIQUE,
  titre VARCHAR(255) NOT NULL,
  description TEXT,
  id_master INT,
  CONSTRAINT fk_cours_master
    FOREIGN KEY (id_master) REFERENCES master(id_master)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================
-- Table des Étudiants
-- =========================
CREATE TABLE etudiant (
  id_etudiant INT AUTO_INCREMENT PRIMARY KEY,
  numero_etudiant VARCHAR(50) NOT NULL UNIQUE,
  nom VARCHAR(255) NOT NULL,
  prenom VARCHAR(255) NOT NULL,
  email VARCHAR(255),
  id_master INT,
  CONSTRAINT fk_etudiant_master
    FOREIGN KEY (id_master) REFERENCES master(id_master)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================
-- Table des Inscriptions (Cours ↔ Étudiants)
-- =========================
CREATE TABLE inscription (
  id_cours INT NOT NULL,
  id_etudiant INT NOT NULL,
  PRIMARY KEY (id_cours, id_etudiant),
  CONSTRAINT fk_inscription_cours
    FOREIGN KEY (id_cours) REFERENCES cours(id_cours)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_inscription_etudiant
    FOREIGN KEY (id_etudiant) REFERENCES etudiant(id_etudiant)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================
-- Table des Évaluations
-- =========================
CREATE TABLE evaluation (
  id_evaluation INT AUTO_INCREMENT PRIMARY KEY,
  id_cours INT NOT NULL,
  id_etudiant INT NOT NULL,
  type_evaluation VARCHAR(100) NOT NULL,   -- Examen, CC, Projet...
  date_evaluation DATE NOT NULL,
  coefficient DECIMAL(4,2) DEFAULT 1.00,
  CONSTRAINT fk_eval_cours
    FOREIGN KEY (id_cours) REFERENCES cours(id_cours)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_eval_etudiant
    FOREIGN KEY (id_etudiant) REFERENCES etudiant(id_etudiant)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================
-- Table des Notes
-- =========================
CREATE TABLE note (
  id_note INT AUTO_INCREMENT PRIMARY KEY,
  id_evaluation INT NOT NULL,
  valeur DECIMAL(5,2) NOT NULL,        -- ex : 14.50
  commentaire TEXT,
  type_note VARCHAR(100),              -- Examen, Projet...
  CONSTRAINT fk_note_eval
    FOREIGN KEY (id_evaluation) REFERENCES evaluation(id_evaluation)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================
-- Données de test
-- =========================

-- Master
INSERT INTO master (code_master, titre, description) VALUES
  ('M2-THYP', 'Master 2 Technologies de l''Hypermédia', 'Parcours orienté web, données et hypermédia.');

-- Cours
INSERT INTO cours (code_cours, titre, description, id_master) VALUES
  ('WEB101', 'Introduction au Web', 'Bases du fonctionnement du Web.', 1),
  ('SEM201', 'Web sémantique', 'Ontologies, RDF, SPARQL.', 1),
  ('JS301', 'Programmation JavaScript', 'JavaScript côté client.', 1);

-- Étudiants
INSERT INTO etudiant (numero_etudiant, nom, prenom, email, id_master) VALUES
  ('E001', 'Dupont', 'Alice', 'alice.dupont@example.org', 1),
  ('E002', 'Martin', 'Bob', 'bob.martin@example.org', 1),
  ('E003', 'Durand', 'Charlie', 'charlie.durand@example.org', 1);

-- Inscriptions aux cours
INSERT INTO inscription (id_cours, id_etudiant) VALUES
  (1, 1), -- Alice → Introduction au Web
  (1, 2), -- Bob   → Introduction au Web
  (2, 3), -- Charlie → Web sémantique
  (3, 1); -- Alice → Programmation JavaScript

-- Évaluations
INSERT INTO evaluation (id_cours, id_etudiant, type_evaluation, date_evaluation, coefficient) VALUES
  (1, 1, 'Examen', '2025-01-15', 1.00),
  (1, 2, 'Examen', '2025-01-15', 1.00),
  (2, 3, 'Projet', '2025-02-10', 2.00);

-- Notes
INSERT INTO note (id_evaluation, valeur, commentaire, type_note) VALUES
  (1, 14.50, 'Bonne maîtrise des notions de base.', 'Examen'),
  (2, 11.00, 'Résultat correct, quelques imprécisions.', 'Examen'),
  (3, 16.00, 'Très bon projet, bien structuré.', 'Projet');
