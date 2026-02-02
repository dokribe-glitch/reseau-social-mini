<?php
require_once 'config/session.php';
require_once 'config/database.php';

requireLogin();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : getUserId();

// Récupérer les infos de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: index.php');
    exit();
}

$pageTitle = htmlspecialchars($user['nom_complet'] ?: $user['pseudo']);

$est_mon_profil = ($user_id === getUserId());

// Mise à jour du profil
if ($est_mon_profil && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier'])) {
    $nom_complet = trim($_POST['nom_complet'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $photo_profil = $user['photo_profil'];
    
    // Gestion de l'upload de photo
    if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profils/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $extension = pathinfo($_FILES['photo_profil']['name'], PATHINFO_EXTENSION);
        $filename = 'profil_' . getUserId() . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['photo_profil']['tmp_name'], $filepath)) {
            // Supprimer l'ancienne photo
            if ($user['photo_profil'] && file_exists($upload_dir . $user['photo_profil'])) {
                unlink($upload_dir . $user['photo_profil']);
            }
            $photo_profil = $filename;
            $_SESSION['photo_profil'] = $filename;
        }
    }
    
    $stmt = $pdo->prepare("UPDATE utilisateurs SET nom_complet = ?, bio = ?, photo_profil = ? WHERE id = ?");
    $stmt->execute([$nom_complet, $bio, $photo_profil, getUserId()]);
    
    header('Location: profil.php?id=' . getUserId());
    exit();
}

// Vérifier le statut d'amitié
$statut_ami = null;
$demande_envoyee = false;
if (!$est_mon_profil) {
    $stmt = $pdo->prepare("
        SELECT statut, utilisateur_id 
        FROM amis 
        WHERE (utilisateur_id = ? AND ami_id = ?) OR (utilisateur_id = ? AND ami_id = ?)
    ");
    $stmt->execute([getUserId(), $user_id, $user_id, getUserId()]);
    $ami = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ami) {
        $statut_ami = $ami['statut'];
        $demande_envoyee = ($ami['utilisateur_id'] === getUserId());
    }
}

// Statistiques
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM publications WHERE utilisateur_id = ?");
$stmt->execute([$user_id]);
$nb_publications = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->prepare("
    SELECT COUNT(*) as count FROM amis 
    WHERE (utilisateur_id = ? OR ami_id = ?) AND statut = 'accepte'
");
$stmt->execute([$user_id, $user_id]);
$nb_amis = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Publications de l'utilisateur
$stmt = $pdo->prepare("
    SELECT p.*, u.pseudo, u.nom_complet, u.photo_profil,
           (SELECT COUNT(*) FROM likes WHERE publication_id = p.id) as nombre_likes,
           (SELECT COUNT(*) > 0 FROM likes WHERE publication_id = p.id AND utilisateur_id = ?) as user_liked
    FROM publications p
    JOIN utilisateurs u ON p.utilisateur_id = u.id
    WHERE p.utilisateur_id = ?
    ORDER BY p.date_publication DESC
");
$stmt->execute([getUserId(), $user_id]);
$publications = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <!-- En-tête du profil -->
        <div class="profile-header text-center">
            <img src="<?php echo $user['photo_profil'] ? 'uploads/profils/' . htmlspecialchars($user['photo_profil']) : 'https://via.placeholder.com/150'; ?>" 
                 alt="Photo de profil" class="profile-avatar-large">
            
            <h2 class="mt-3 mb-1"><?php echo htmlspecialchars($user['nom_complet'] ?: $user['pseudo']); ?></h2>
            <p class="mb-2">@<?php echo htmlspecialchars($user['pseudo']); ?></p>
            
            <?php if ($user['bio']): ?>
                <p class="mt-3"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
            <?php endif; ?>
            
            <div class="profile-stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $nb_publications; ?></div>
                    <div class="stat-label">Publications</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $nb_amis; ?></div>
                    <div class="stat-label">Amis</div>
                </div>
            </div>
            
            <div class="mt-3">
                <?php if ($est_mon_profil): ?>
                    <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#editModal">
                        <i class="bi bi-pencil"></i> Modifier le profil
                    </button>
                <?php else: ?>
                    <?php if ($statut_ami === 'accepte'): ?>
                        <span class="badge bg-success fs-6">
                            <i class="bi bi-check-circle"></i> Amis
                        </span>
                        <a href="messages.php?user=<?php echo $user_id; ?>" class="btn btn-light">
                            <i class="bi bi-chat"></i> Envoyer un message
                        </a>
                    <?php elseif ($statut_ami === 'en_attente' && $demande_envoyee): ?>
                        <span class="badge bg-warning fs-6">
                            <i class="bi bi-clock"></i> Demande envoyée
                        </span>
                    <?php elseif ($statut_ami === 'en_attente'): ?>
                        <a href="amis.php" class="btn btn-light">
                            <i class="bi bi-person-plus"></i> Accepter la demande
                        </a>
                    <?php else: ?>
                        <a href="recherche.php?ajouter=<?php echo $user_id; ?>" class="btn btn-light">
                            <i class="bi bi-person-plus"></i> Ajouter comme ami
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Publications -->
        <div class="mt-4">
            <h4 class="mb-3"><i class="bi bi-card-text"></i> Publications</h4>
            
            <?php if (empty($publications)): ?>
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle"></i> Aucune publication pour le moment.
                </div>
            <?php else: ?>
                <?php foreach ($publications as $pub): ?>
                    <div class="publication-card fade-in">
                        <div class="publication-header">
                            <img src="<?php echo $pub['photo_profil'] ? 'uploads/profils/' . htmlspecialchars($pub['photo_profil']) : 'https://via.placeholder.com/50'; ?>" 
                                 alt="Photo" class="publication-avatar">
                            <div>
                                <strong><?php echo htmlspecialchars($pub['nom_complet'] ?: $pub['pseudo']); ?></strong>
                                <br>
                                <small class="text-muted">
                                    <?php 
                                    $date = new DateTime($pub['date_publication']);
                                    echo $date->format('d/m/Y à H:i');
                                    ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="publication-content">
                            <p><?php echo nl2br(htmlspecialchars($pub['contenu'])); ?></p>
                            
                            <?php if ($pub['image']): ?>
                                <img src="uploads/publications/<?php echo htmlspecialchars($pub['image']); ?>" 
                                     alt="Publication" class="publication-image">
                            <?php endif; ?>
                        </div>
                        
                        <div class="publication-actions">
                            <button class="action-btn like-btn <?php echo $pub['user_liked'] ? 'liked' : ''; ?>" 
                                    data-publication-id="<?php echo $pub['id']; ?>">
                                <i class="bi bi-heart<?php echo $pub['user_liked'] ? '-fill' : ''; ?>"></i>
                                <span class="like-count"><?php echo $pub['nombre_likes']; ?></span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de modification du profil -->
<?php if ($est_mon_profil): ?>
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier le profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nom_complet" class="form-label">Nom complet</label>
                        <input type="text" class="form-control" id="nom_complet" name="nom_complet" 
                               value="<?php echo htmlspecialchars($user['nom_complet']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="bio" class="form-label">Bio</label>
                        <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="photo_profil" class="form-label">Photo de profil</label>
                        <input type="file" class="form-control" id="photo_profil" name="photo_profil" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="modifier" class="btn btn-primary">
                        <i class="bi bi-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Gestion des likes
document.querySelectorAll('.like-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const publicationId = this.dataset.publicationId;
        const likeCount = this.querySelector('.like-count');
        const icon = this.querySelector('i');
        
        fetch('ajax/like.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'publication_id=' + publicationId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                likeCount.textContent = data.nombre_likes;
                if (data.liked) {
                    this.classList.add('liked');
                    icon.classList.remove('bi-heart');
                    icon.classList.add('bi-heart-fill');
                } else {
                    this.classList.remove('liked');
                    icon.classList.remove('bi-heart-fill');
                    icon.classList.add('bi-heart');
                }
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
