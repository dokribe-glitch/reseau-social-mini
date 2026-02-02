<?php
require_once 'config/session.php';
require_once 'config/database.php';

requireLogin();

$pageTitle = 'Mes Amis';

// Accepter une demande d'ami
if (isset($_GET['accepter'])) {
    $ami_id = (int)$_GET['accepter'];
    $stmt = $pdo->prepare("UPDATE amis SET statut = 'accepte' WHERE utilisateur_id = ? AND ami_id = ?");
    $stmt->execute([$ami_id, getUserId()]);
    header('Location: amis.php');
    exit();
}

// Refuser une demande d'ami
if (isset($_GET['refuser'])) {
    $ami_id = (int)$_GET['refuser'];
    $stmt = $pdo->prepare("DELETE FROM amis WHERE utilisateur_id = ? AND ami_id = ?");
    $stmt->execute([$ami_id, getUserId()]);
    header('Location: amis.php');
    exit();
}

// Retirer un ami
if (isset($_GET['retirer'])) {
    $ami_id = (int)$_GET['retirer'];
    $stmt = $pdo->prepare("DELETE FROM amis WHERE (utilisateur_id = ? AND ami_id = ?) OR (utilisateur_id = ? AND ami_id = ?)");
    $stmt->execute([getUserId(), $ami_id, $ami_id, getUserId()]);
    header('Location: amis.php');
    exit();
}

// Récupérer les demandes en attente
$stmt = $pdo->prepare("
    SELECT a.*, u.id, u.pseudo, u.nom_complet, u.photo_profil
    FROM amis a
    JOIN utilisateurs u ON a.utilisateur_id = u.id
    WHERE a.ami_id = ? AND a.statut = 'en_attente'
    ORDER BY a.date_demande DESC
");
$stmt->execute([getUserId()]);
$demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les amis acceptés
$stmt = $pdo->prepare("
    SELECT u.id, u.pseudo, u.nom_complet, u.photo_profil, u.bio
    FROM utilisateurs u
    WHERE u.id IN (
        SELECT ami_id FROM amis WHERE utilisateur_id = ? AND statut = 'accepte'
        UNION
        SELECT utilisateur_id FROM amis WHERE ami_id = ? AND statut = 'accepte'
    )
    ORDER BY u.pseudo
");
$stmt->execute([getUserId(), getUserId()]);
$amis = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <h2 class="mb-4"><i class="bi bi-person-hearts"></i> Mes Amis</h2>
        
        <!-- Demandes en attente -->
        <?php if (!empty($demandes)): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person-plus"></i> Demandes d'amitié (<?php echo count($demandes); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($demandes as $demande): ?>
                        <div class="friend-card">
                            <div class="friend-info">
                                <img src="<?php echo $demande['photo_profil'] ? 'uploads/profils/' . htmlspecialchars($demande['photo_profil']) : 'https://via.placeholder.com/60'; ?>" 
                                     alt="Photo" class="friend-avatar">
                                <div>
                                    <a href="profil.php?id=<?php echo $demande['id']; ?>" class="text-decoration-none">
                                        <strong><?php echo htmlspecialchars($demande['nom_complet'] ?: $demande['pseudo']); ?></strong>
                                    </a>
                                    <br>
                                    <small class="text-muted">@<?php echo htmlspecialchars($demande['pseudo']); ?></small>
                                </div>
                            </div>
                            <div>
                                <a href="?accepter=<?php echo $demande['id']; ?>" class="btn btn-success btn-sm">
                                    <i class="bi bi-check-lg"></i> Accepter
                                </a>
                                <a href="?refuser=<?php echo $demande['id']; ?>" class="btn btn-danger btn-sm">
                                    <i class="bi bi-x-lg"></i> Refuser
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Liste des amis -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-people-fill"></i> Mes amis (<?php echo count($amis); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($amis)): ?>
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i> Vous n'avez pas encore d'amis.
                        <br><a href="recherche.php" class="alert-link">Rechercher des personnes</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($amis as $ami): ?>
                        <div class="friend-card">
                            <div class="friend-info">
                                <img src="<?php echo $ami['photo_profil'] ? 'uploads/profils/' . htmlspecialchars($ami['photo_profil']) : 'https://via.placeholder.com/60'; ?>" 
                                     alt="Photo" class="friend-avatar">
                                <div>
                                    <a href="profil.php?id=<?php echo $ami['id']; ?>" class="text-decoration-none">
                                        <strong><?php echo htmlspecialchars($ami['nom_complet'] ?: $ami['pseudo']); ?></strong>
                                    </a>
                                    <br>
                                    <small class="text-muted">@<?php echo htmlspecialchars($ami['pseudo']); ?></small>
                                    <?php if ($ami['bio']): ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars(substr($ami['bio'], 0, 50)); ?><?php echo strlen($ami['bio']) > 50 ? '...' : ''; ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <a href="profil.php?id=<?php echo $ami['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="bi bi-person"></i> Profil
                                </a>
                                <a href="messages.php?user=<?php echo $ami['id']; ?>" class="btn btn-info btn-sm">
                                    <i class="bi bi-chat"></i> Message
                                </a>
                                <a href="?retirer=<?php echo $ami['id']; ?>" class="btn btn-outline-danger btn-sm" 
                                   onclick="return confirm('Voulez-vous vraiment retirer cet ami ?')">
                                    <i class="bi bi-person-dash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
