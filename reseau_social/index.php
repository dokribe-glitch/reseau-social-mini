<?php
require_once 'config/session.php';
require_once 'config/database.php';

requireLogin();

$pageTitle = 'Fil d\'actualité';

// Traitement de nouvelle publication
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publier'])) {
    $contenu = trim($_POST['contenu'] ?? '');
    $image = null;
    
    if (!empty($contenu)) {
        // Gestion de l'upload d'image
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/publications/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                $image = $filename;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO publications (utilisateur_id, contenu, image) VALUES (?, ?, ?)");
        $stmt->execute([getUserId(), $contenu, $image]);
        
        header('Location: index.php');
        exit();
    }
}

// Récupérer les publications des amis et de l'utilisateur
$stmt = $pdo->prepare("
    SELECT p.*, u.pseudo, u.nom_complet, u.photo_profil,
           (SELECT COUNT(*) FROM likes WHERE publication_id = p.id) as nombre_likes,
           (SELECT COUNT(*) > 0 FROM likes WHERE publication_id = p.id AND utilisateur_id = ?) as user_liked
    FROM publications p
    JOIN utilisateurs u ON p.utilisateur_id = u.id
    WHERE p.utilisateur_id = ? 
       OR p.utilisateur_id IN (
           SELECT ami_id FROM amis WHERE utilisateur_id = ? AND statut = 'accepte'
           UNION
           SELECT utilisateur_id FROM amis WHERE ami_id = ? AND statut = 'accepte'
       )
    ORDER BY p.date_publication DESC
    LIMIT 50
");
$stmt->execute([getUserId(), getUserId(), getUserId(), getUserId()]);
$publications = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <!-- Formulaire de nouvelle publication -->
        <div class="new-publication-card fade-in">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="d-flex gap-3">
                    <img src="<?php echo $_SESSION['photo_profil'] ? 'uploads/profils/' . htmlspecialchars($_SESSION['photo_profil']) : 'https://via.placeholder.com/50'; ?>" 
                         alt="Photo" class="publication-avatar">
                    <div class="flex-grow-1">
                        <textarea name="contenu" class="form-control border-0" 
                                  placeholder="Quoi de neuf, <?php echo htmlspecialchars(getUserPseudo()); ?> ?" 
                                  rows="3" required></textarea>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <label for="image-upload" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-image"></i> Photo
                        </label>
                        <input type="file" id="image-upload" name="image" accept="image/*" class="d-none">
                        <span id="image-name" class="text-muted ms-2"></span>
                    </div>
                    <button type="submit" name="publier" class="btn btn-primary">
                        <i class="bi bi-send"></i> Publier
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Liste des publications -->
        <?php if (empty($publications)): ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle"></i> Aucune publication pour le moment.
                <br>Ajoutez des amis pour voir leurs publications !
            </div>
        <?php else: ?>
            <?php foreach ($publications as $pub): ?>
                <div class="publication-card fade-in">
                    <!-- En-tête -->
                    <div class="publication-header">
                        <img src="<?php echo $pub['photo_profil'] ? 'uploads/profils/' . htmlspecialchars($pub['photo_profil']) : 'https://via.placeholder.com/50'; ?>" 
                             alt="Photo" class="publication-avatar">
                        <div>
                            <a href="profil.php?id=<?php echo $pub['utilisateur_id']; ?>" class="text-decoration-none">
                                <strong><?php echo htmlspecialchars($pub['nom_complet'] ?: $pub['pseudo']); ?></strong>
                            </a>
                            <br>
                            <small class="text-muted">
                                <?php 
                                $date = new DateTime($pub['date_publication']);
                                echo $date->format('d/m/Y à H:i');
                                ?>
                            </small>
                        </div>
                    </div>
                    
                    <!-- Contenu -->
                    <div class="publication-content">
                        <p><?php echo nl2br(htmlspecialchars($pub['contenu'])); ?></p>
                        
                        <?php if ($pub['image']): ?>
                            <img src="uploads/publications/<?php echo htmlspecialchars($pub['image']); ?>" 
                                 alt="Publication" class="publication-image">
                        <?php endif; ?>
                    </div>
                    
                    <!-- Actions -->
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

<script>
// Afficher le nom du fichier sélectionné
document.getElementById('image-upload').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name || '';
    document.getElementById('image-name').textContent = fileName;
});

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
