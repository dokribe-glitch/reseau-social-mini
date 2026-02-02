<?php
require_once 'config/session.php';
require_once 'config/database.php';

requireLogout();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nom_complet = trim($_POST['nom_complet'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $confirmer_mdp = $_POST['confirmer_mdp'] ?? '';
    
    // Validation
    if (empty($pseudo)) {
        $errors[] = "Le pseudo est requis.";
    } elseif (strlen($pseudo) < 3) {
        $errors[] = "Le pseudo doit contenir au moins 3 caractères.";
    }
    
    if (empty($email)) {
        $errors[] = "L'email est requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide.";
    }
    
    if (empty($mot_de_passe)) {
        $errors[] = "Le mot de passe est requis.";
    } elseif (strlen($mot_de_passe) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    }
    
    if ($mot_de_passe !== $confirmer_mdp) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }
    
    // Vérifier si le pseudo ou l'email existe déjà
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE pseudo = ? OR email = ?");
        $stmt->execute([$pseudo, $email]);
        if ($stmt->fetch()) {
            $errors[] = "Ce pseudo ou cet email est déjà utilisé.";
        }
    }
    
    // Insertion
    if (empty($errors)) {
        $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (pseudo, email, nom_complet, mot_de_passe) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute([$pseudo, $email, $nom_complet, $mot_de_passe_hash])) {
            $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
        } else {
            $errors[] = "Une erreur est survenue lors de l'inscription.";
        }
    }
}

$pageTitle = 'Inscription';
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
            <h2><i class="bi bi-people-fill"></i> Inscription</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <br><a href="login.php" class="alert-link">Se connecter maintenant</a>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="pseudo" class="form-label">Pseudo *</label>
                    <input type="text" class="form-control" id="pseudo" name="pseudo" 
                           value="<?php echo htmlspecialchars($_POST['pseudo'] ?? ''); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="nom_complet" class="form-label">Nom complet</label>
                    <input type="text" class="form-control" id="nom_complet" name="nom_complet" 
                           value="<?php echo htmlspecialchars($_POST['nom_complet'] ?? ''); ?>">
                </div>
                
                <div class="mb-3">
                    <label for="mot_de_passe" class="form-label">Mot de passe *</label>
                    <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
                    <small class="text-muted">Au moins 6 caractères</small>
                </div>
                
                <div class="mb-3">
                    <label for="confirmer_mdp" class="form-label">Confirmer le mot de passe *</label>
                    <input type="password" class="form-control" id="confirmer_mdp" name="confirmer_mdp" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="bi bi-person-plus"></i> S'inscrire
                </button>
                
                <p class="text-center mb-0">
                    Déjà un compte ? <a href="login.php">Se connecter</a>
                </p>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
