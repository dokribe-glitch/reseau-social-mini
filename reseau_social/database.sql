-- Script de création de la base de données pour le Réseau Social Mini

-- Créer la base de données
CREATE DATABASE IF NOT EXISTS reseau_social CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reseau_social;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pseudo VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    nom_complet VARCHAR(200),
    bio TEXT,
    photo_profil VARCHAR(255),
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pseudo (pseudo),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des publications
CREATE TABLE IF NOT EXISTS publications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT NOT NULL,
    contenu TEXT NOT NULL,
    image VARCHAR(255),
    date_publication TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_utilisateur (utilisateur_id),
    INDEX idx_date (date_publication)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des amis
CREATE TABLE IF NOT EXISTS amis (
    utilisateur_id INT NOT NULL,
    ami_id INT NOT NULL,
    statut ENUM('en_attente', 'accepte') DEFAULT 'en_attente',
    date_demande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (utilisateur_id, ami_id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (ami_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_statut (statut),
    INDEX idx_ami_id (ami_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des messages privés
CREATE TABLE IF NOT EXISTS messages_prives (
    id INT PRIMARY KEY AUTO_INCREMENT,
    expediteur_id INT NOT NULL,
    destinataire_id INT NOT NULL,
    message TEXT NOT NULL,
    lu BOOLEAN DEFAULT FALSE,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expediteur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (destinataire_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_expediteur (expediteur_id),
    INDEX idx_destinataire (destinataire_id),
    INDEX idx_lu (lu),
    INDEX idx_date (date_envoi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des likes
CREATE TABLE IF NOT EXISTS likes (
    publication_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    date_like TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (publication_id, utilisateur_id),
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_date (date_like)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer quelques utilisateurs de test (mot de passe: password123)
INSERT INTO utilisateurs (pseudo, email, nom_complet, mot_de_passe, bio) VALUES
('john_doe', 'john@example.com', 'John Doe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Développeur passionné de technologie'),
('jane_smith', 'jane@example.com', 'Jane Smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Designer créative et amatrice de photographie'),
('bob_martin', 'bob@example.com', 'Bob Martin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Entrepreneur et voyageur'),
('alice_wonder', 'alice@example.com', 'Alice Wonder', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Artiste et musicienne');

-- Créer quelques relations d'amitié
INSERT INTO amis (utilisateur_id, ami_id, statut) VALUES
(1, 2, 'accepte'),
(1, 3, 'accepte'),
(2, 3, 'accepte'),
(3, 4, 'en_attente');

-- Créer quelques publications de test
INSERT INTO publications (utilisateur_id, contenu) VALUES
(1, 'Bonjour à tous ! Ravi de rejoindre ce réseau social 🎉'),
(2, 'Superbe journée pour coder ! 💻'),
(1, 'Qui veut faire une partie de tennis ce week-end ? 🎾'),
(3, 'Nouveau voyage prévu en Asie ! Trop hâte 🌏✈️'),
(2, 'Mon dernier projet design est enfin terminé ! 🎨');

-- Ajouter quelques likes
INSERT INTO likes (publication_id, utilisateur_id) VALUES
(1, 2),
(1, 3),
(2, 1),
(2, 3),
(3, 2),
(4, 1),
(4, 2),
(5, 1);

-- Ajouter quelques messages
INSERT INTO messages_prives (expediteur_id, destinataire_id, message) VALUES
(1, 2, 'Salut Jane ! Comment vas-tu ?'),
(2, 1, 'Salut John ! Je vais bien merci, et toi ?'),
(1, 2, 'Ça va super ! Tu es libre ce week-end ?'),
(3, 1, 'Hey John, tu as vu mon nouveau projet ?');

-- Afficher un message de confirmation
SELECT 'Base de données créée avec succès !' as message;
SELECT CONCAT('Utilisateurs créés: ', COUNT(*)) as info FROM utilisateurs;
SELECT CONCAT('Publications créées: ', COUNT(*)) as info FROM publications;
SELECT CONCAT('Relations d\'amitié créées: ', COUNT(*)) as info FROM amis;
