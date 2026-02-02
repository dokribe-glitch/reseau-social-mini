<?php
require_once 'config/session.php';
require_once 'config/database.php';

requireLogout();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = trim($_POST['identifiant'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    
    if (empty($identifiant) || empty($mot_de_passe)) {
        $errors[] = "Tous les champs sont requis.";
    } else {
        // Rechercher l'utilisateur par pseudo ou email
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = ? OR email = ?");
        $stmt->execute([$identifiant, $identifiant]);
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
            // Connexion réussie
            $_SESSION['utilisateur_id'] = $utilisateur['id'];
            $_SESSION['pseudo'] = $utilisateur['pseudo'];
            $_SESSION['photo_profil'] = $utilisateur['photo_profil'];
            
            header('Location: index.php');
            exit();
        } else {
            $errors[] = "Identifiant ou mot de passe incorrect.";
        }
    }
}

$pageTitle = 'Connexion';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2><i class="bi bi-box-arrow-in-right"></i> Connexion</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="identifiant" class="form-label">Pseudo ou Email</label>
                    <input type="text" class="form-control" id="identifiant" name="identifiant" 
                           value="<?php echo htmlspecialchars($_POST['identifiant'] ?? ''); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="mot_de_passe" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="bi bi-box-arrow-in-right"></i> Se connecter
                </button>
                
                <p class="text-center mb-0">
                    Pas encore de compte ? <a href="register.php">S'inscrire</a>
                </p>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
