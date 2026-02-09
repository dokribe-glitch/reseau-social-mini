# Réseau Social Mini - Documentation

## Description
Une plateforme de réseau social complète développée en PHP/MySQL avec toutes les fonctionnalités essentielles d'un réseau social moderne.

## Fonctionnalités

### 1. Authentification
- ✅ Inscription avec validation des données
- ✅ Connexion sécurisée avec mots de passe hashés
- ✅ Déconnexion
- ✅ Gestion de session

### 2. Profil Utilisateur
- ✅ Photo de profil personnalisable
- ✅ Bio et nom complet
- ✅ Statistiques (publications, amis)
- ✅ Modification du profil

### 3. Fil d'Actualité
- ✅ Affichage des publications des amis
- ✅ Création de publications avec texte et image
- ✅ Upload d'images pour les publications
- ✅ Tri chronologique

### 4. Système d'Amis
- ✅ Recherche d'utilisateurs
- ✅ Envoi de demandes d'amitié
- ✅ Acceptation/refus des demandes
- ✅ Liste des amis
- ✅ Suppression d'amis

### 5. Interactions Sociales
- ✅ Like sur les publications
- ✅ Compteur de likes
- ✅ Animation des likes (Ajax)

### 6. Messagerie Privée
- ✅ Conversations en temps réel
- ✅ Liste des conversations
- ✅ Messages non lus avec compteur
- ✅ Interface de chat moderne
- ✅ Polling Ajax pour nouveaux messages

### 7. Notifications
- ✅ Notifications de demandes d'amis
- ✅ Notifications de nouveaux likes
- ✅ Notifications de messages non lus
- ✅ Compteurs de notifications en temps réel
- ✅ Polling Ajax toutes les 10 secondes

### 8. Recherche
- ✅ Recherche d'utilisateurs par pseudo, nom ou email
- ✅ Affichage du statut d'amitié
- ✅ Actions rapides (ajouter, voir profil)

## Structure du Projet

```
reseau_social/
├── config/
│   ├── database.php         # Configuration de la base de données
│   └── session.php          # Gestion des sessions
├── includes/
│   ├── header.php           # En-tête du site
│   └── footer.php           # Pied de page du site
├── ajax/
│   ├── like.php             # Gestion des likes
│   ├── check_messages.php   # Vérification des nouveaux messages
│   └── get_notifications.php # Récupération des notifications
├── css/
│   └── style.css            # Styles personnalisés
├── js/
│   ├── main.js              # JavaScript principal
│   └── notifications.js     # Gestion des notifications en temps réel
├── uploads/
│   ├── profils/             # Photos de profil
│   └── publications/        # Images des publications
├── index.php                # Fil d'actualité
├── login.php                # Page de connexion
├── register.php             # Page d'inscription
├── logout.php               # Déconnexion
├── profil.php               # Page de profil
├── amis.php                 # Gestion des amis
├── recherche.php            # Recherche d'utilisateurs
├── messages.php             # Messagerie privée
├── notifications.php        # Page des notifications
└── database.sql             # Script de création de la base de données
```

## Installation

### Prérequis
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache)

### Étapes d'installation

1. **Cloner ou télécharger le projet**
   ```bash
   # Placer les fichiers dans votre répertoire web (htdocs, www, etc.)
   ```

2. **Créer la base de données**
   ```bash
   # Se connecter à MySQL
   mysql -u root -p
   
   # Importer le fichier SQL
   source /chemin/vers/database.sql
   ```
   
   Ou via phpMyAdmin:
   - Créer une nouvelle base de données nommée `reseau_social`
   - Importer le fichier `database.sql`

3. **Configurer la connexion à la base de données**
   Éditer le fichier `config/database.php` :
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'reseau_social');
   define('DB_USER', 'root');
   define('DB_PASS', 'votre_mot_de_passe');
   ```

4. **Créer les dossiers d'uploads**
   ```bash
   mkdir -p uploads/profils
   mkdir -p uploads/publications
   chmod -R 755 uploads
   ```

5. **Accéder à l'application**
   ```
   http://localhost/reseau_social/
   ```

## Comptes et Connexion

### Inscription

L'utilisateur doit créer un compte via le formulaire d'inscription en remplissant les champs suivants :

- **Pseudo** (unique)
- **Email** (unique)
- **Nom complet**
- **Mot de passe** (au moins 6 caractères)
- **Confirmer le mot de passe**

Une fois le formulaire validé, le compte est enregistré dans la base de données avec le mot de passe sécurisé (hashé).

### Connexion

Après l'inscription, l'utilisateur peut se connecter à l'application en utilisant :

- Son **pseudo** ou son **email**
- Le **mot de passe** choisi lors de l'inscription

**Compte de test disponible :**
- **Pseudo** : `me1`
- **Mot de passe** : `123456`

> 💡 **Astuce** : Créez plusieurs comptes pour tester les fonctionnalités d'interaction (amis, messages, publications).

### Fonctionnalités après connexion

Une fois connecté, l'utilisateur a accès à toutes les fonctionnalités :
- ✅ Fil d'actualité
- ✅ Gestion des amis
- ✅ Publications (texte + images)
- ✅ Messagerie privée
- ✅ Notifications en temps réel

## Technologies Utilisées

### Backend
- **PHP 7.4+** : Langage principal
- **MySQL** : Base de données
- **PDO** : Accès sécurisé à la base de données

### Frontend
- **HTML5** : Structure
- **CSS3** : Stylisation
- **Bootstrap 5.3** : Framework CSS
- **JavaScript (Vanilla)** : Interactions
- **jQuery** : Ajax et manipulation DOM
- **Bootstrap Icons** : Icônes

### Sécurité
- ✅ Protection contre les injections SQL (requêtes préparées PDO)
- ✅ Protection XSS (htmlspecialchars)
- ✅ Mots de passe hashés (password_hash/password_verify)
- ✅ Validation des formulaires côté serveur
- ✅ Gestion sécurisée des sessions
- ✅ Protection des uploads de fichiers

## Fonctionnalités Avancées

### Notifications en Temps Réel
Le système utilise le polling Ajax pour vérifier les nouvelles notifications toutes les 10 secondes :
- Demandes d'amis
- Nouveaux messages
- Nouveaux likes

### Upload d'Images
- Support des formats : JPG, PNG, GIF, WEBP
- Noms de fichiers uniques générés automatiquement
- Séparation des dossiers (profils / publications)

### Interface Responsive
- Design adaptatif pour mobile, tablette et desktop
- Navigation optimisée sur tous les appareils

### Système de Like
- Like/Unlike instantané avec Ajax
- Mise à jour en temps réel du compteur
- Animation visuelle

## Améliorations Possibles

### Court Terme
-  Commentaires sur les publications
-  Partage de publications
-  Modification/suppression de publications
-  Recherche avancée avec filtres

### Moyen Terme
-  Groupes/communautés
-  Événements
-  Stories (publications éphémères)
-  Vidéos dans les publications
-  Émojis et réactions variées

### Long Terme
-  Chat en temps réel (WebSocket)
-  Appels vidéo
-  Application mobile native
-  API REST
-  Système de recommandations (IA)

## Dépannage

### Problème de connexion à la base de données
```
Solution : Vérifier les identifiants dans config/database.php
```

### Images non affichées
```
Solution : Vérifier les permissions des dossiers uploads/
chmod -R 755 uploads
```

### Erreur 404 sur les pages
```
Solution : Vérifier la configuration du serveur web et le chemin d'accès
```

### Session non persistante
```
Solution : Vérifier que session_start() est bien appelé et que les cookies sont activés
```

## Démo en ligne

🌐 **Site en ligne** : https://kribe.dwm.ma/reseau_social/
