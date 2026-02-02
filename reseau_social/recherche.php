<?php
require_once 'config/session.php';
require_once 'config/database.php';

requireLogin();

$pageTitle = 'Rechercher des personnes';

$resultats = [];
$recherche = '';

// Envoyer une demande d'ami
if (isset($_GET['ajouter'])) {
    $ami_id = (int)$_GET['ajouter'];
    
    // Vérifier qu'on n'ajoute pas soi-même
    if ($ami_id !== getUserId()) {
        // Vérifier si la demande n'existe pas déjà
        $stmt = $pdo->prepare("SELECT * FROM amis WHERE (utilisateur_id = ? AND ami_id = ?) OR (utilisateur_id = ? AND ami_id = ?)");
        $stmt->execute([getUserId(), $ami_id, $ami_id, getUserId()]);
        
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO amis (utilisateur_id, ami_id) VALUES (?, ?)");
            $stmt->execute([getUserId(), $ami_id]);
        }
    }
    
    header('Location: recherche.php?q=' . urlencode($_GET['q'] ?? ''));
    exit();
}

// Recherche
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $recherche = trim($_GET['q']);
    
    $stmt = $pdo->prepare("
        SELECT u.*, 
               (SELECT statut FROM amis WHERE 
                   (utilisateur_id = ? AND ami_id = u.id) OR 
                   (utilisateur_id = u.id AND ami_id = ?)
               ) as statut_ami,
               (SELECT utilisateur_id FROM amis WHERE 
                   utilisateur_id = ? AND ami_id = u.id
               ) as demande_envoyee
        FROM utilisateurs u
        WHERE (u.pseudo LIKE ? OR u.nom_complet LIKE ? OR u.email LIKE ?)
          AND u.id != ?
        ORDER BY u.pseudo
        LIMIT 50
    ");
    
    $search_term = '%' . $recherche . '%';
    $stmt->execute([
        getUserId(), getUserId(), getUserId(),
        $search_term, $search_term, $search_term,
        getUserId()
    ]);
    $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <h2 class="mb-4"><i class="bi bi-search"></i> Rechercher des personnes</h2>
        
        <!-- Formulaire de recherche -->
        <div class="search-card">
            <form method="GET" action="">
                <div class="input-group input-group-lg">
                    <input type="text" class="form-control" name="q" 
                           placeholder="Rechercher par pseudo, nom ou email..." 
                           value="<?php echo htmlspecialchars($recherche); ?>" required>
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i> Rechercher
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Résultats -->
        <?php if (!empty($recherche)): ?>
            <div class="mt-4">
                <h4 class="mb-3">
                    <?php echo count($resultats); ?> résultat<?php echo count($resultats) > 1 ? 's' : ''; ?> 
                    pour "<?php echo htmlspecialchars($recherche); ?>"
                </h4>
                
                <?php if (empty($resultats)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Aucun utilisateur trouvé.
                    </div>
                <?php else: ?>
                    <?php foreach ($resultats as $user): ?>
                        <div class="search-result">
                            <div class="friend-info">
                                <img src="<?php echo $user['photo_profil'] ? 'uploads/profils/' . htmlspecialchars($user['photo_profil']) : 'https://via.placeholder.com/60'; ?>" 
                                     alt="Photo" class="friend-avatar">
                                <div>
                                    <a href="profil.php?id=<?php echo $user['id']; ?>" class="text-decoration-none">
                                        <strong><?php echo htmlspecialchars($user['nom_complet'] ?: $user['pseudo']); ?></strong>
                                    </a>
                                    <br>
                                    <small class="text-muted">@<?php echo htmlspecialchars($user['pseudo']); ?></small>
                                    <?php if ($user['bio']): ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars(substr($user['bio'], 0, 80)); ?><?php echo strlen($user['bio']) > 80 ? '...' : ''; ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <?php if ($user['statut_ami'] === 'accepte'): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> Ami
                                    </span>
                                    <a href="messages.php?user=<?php echo $user['id']; ?>" class="btn btn-info btn-sm">
                                        <i class="bi bi-chat"></i> Message
                                    </a>
                                <?php elseif ($user['statut_ami'] === 'en_attente' && $user['demande_envoyee']): ?>
                                    <span class="badge bg-warning">
                                        <i class="bi bi-clock"></i> En attente
                                    </span>
                                <?php elseif ($user['statut_ami'] === 'en_attente'): ?>
                                    <a href="amis.php" class="btn btn-warning btn-sm">
                                        <i class="bi bi-person-plus"></i> Accepter demande
                                    </a>
                                <?php else: ?>
                                    <a href="?ajouter=<?php echo $user['id']; ?>&q=<?php echo urlencode($recherche); ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="bi bi-person-plus"></i> Ajouter
                                    </a>
                                <?php endif; ?>
                                
                                <a href="profil.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-person"></i> Profil
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="text-center mt-5">
                <i class="bi bi-search" style="font-size: 80px; color: #ccc;"></i>
                <p class="text-muted mt-3">Utilisez la barre de recherche pour trouver des personnes</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
