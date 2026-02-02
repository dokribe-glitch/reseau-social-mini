<?php
require_once 'config/session.php';
require_once 'config/database.php';

requireLogin();

$pageTitle = 'Notifications';

// Créer une vue simplifiée des notifications basée sur les données existantes
$notifications = [];

// Nouvelles demandes d'amis
$stmt = $pdo->prepare("
    SELECT a.*, u.pseudo, u.nom_complet, u.photo_profil, a.date_demande as date
    FROM amis a
    JOIN utilisateurs u ON a.utilisateur_id = u.id
    WHERE a.ami_id = ? AND a.statut = 'en_attente'
    ORDER BY a.date_demande DESC
");
$stmt->execute([getUserId()]);
$demandes_amis = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($demandes_amis as $demande) {
    $notifications[] = [
        'type' => 'demande_ami',
        'utilisateur_id' => $demande['utilisateur_id'],
        'pseudo' => $demande['pseudo'],
        'nom_complet' => $demande['nom_complet'],
        'photo_profil' => $demande['photo_profil'],
        'message' => 'vous a envoyé une demande d\'ami',
        'date' => $demande['date'],
        'lien' => 'amis.php'
    ];
}

// Nouveaux likes sur mes publications
$stmt = $pdo->prepare("
    SELECT l.*, u.pseudo, u.nom_complet, u.photo_profil, p.contenu, l.date_like as date
    FROM likes l
    JOIN utilisateurs u ON l.utilisateur_id = u.id
    JOIN publications p ON l.publication_id = p.id
    WHERE p.utilisateur_id = ? AND l.utilisateur_id != ?
    ORDER BY l.date_like DESC
    LIMIT 20
");
$stmt->execute([getUserId(), getUserId()]);
$likes = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($likes as $like) {
    $notifications[] = [
        'type' => 'like',
        'utilisateur_id' => $like['utilisateur_id'],
        'pseudo' => $like['pseudo'],
        'nom_complet' => $like['nom_complet'],
        'photo_profil' => $like['photo_profil'],
        'message' => 'a aimé votre publication',
        'extrait' => substr($like['contenu'], 0, 50),
        'date' => $like['date'],
        'lien' => 'profil.php?id=' . getUserId()
    ];
}

// Nouveaux messages non lus
$stmt = $pdo->prepare("
    SELECT m.*, u.pseudo, u.nom_complet, u.photo_profil, m.date_envoi as date
    FROM messages_prives m
    JOIN utilisateurs u ON m.expediteur_id = u.id
    WHERE m.destinataire_id = ? AND m.lu = FALSE
    ORDER BY m.date_envoi DESC
    LIMIT 20
");
$stmt->execute([getUserId()]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($messages as $msg) {
    $notifications[] = [
        'type' => 'message',
        'utilisateur_id' => $msg['expediteur_id'],
        'pseudo' => $msg['pseudo'],
        'nom_complet' => $msg['nom_complet'],
        'photo_profil' => $msg['photo_profil'],
        'message' => 'vous a envoyé un message',
        'extrait' => substr($msg['message'], 0, 50),
        'date' => $msg['date'],
        'lien' => 'messages.php?user=' . $msg['expediteur_id']
    ];
}

// Trier toutes les notifications par date
usort($notifications, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <h2 class="mb-4"><i class="bi bi-bell-fill"></i> Notifications</h2>
        
        <?php if (empty($notifications)): ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle"></i> Vous n'avez aucune notification pour le moment.
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="notification-item fade-in">
                    <img src="<?php echo $notif['photo_profil'] ? 'uploads/profils/' . htmlspecialchars($notif['photo_profil']) : 'https://via.placeholder.com/50'; ?>" 
                         alt="Photo" class="notification-avatar">
                    <div class="flex-grow-1">
                        <p class="mb-1">
                            <a href="profil.php?id=<?php echo $notif['utilisateur_id']; ?>" class="text-decoration-none fw-bold">
                                <?php echo htmlspecialchars($notif['nom_complet'] ?: $notif['pseudo']); ?>
                            </a>
                            <?php echo $notif['message']; ?>
                        </p>
                        
                        <?php if (isset($notif['extrait'])): ?>
                            <p class="text-muted mb-1 small">
                                "<?php echo htmlspecialchars($notif['extrait']); ?><?php echo strlen($notif['extrait']) >= 50 ? '...' : ''; ?>"
                            </p>
                        <?php endif; ?>
                        
                        <small class="text-muted">
                            <i class="bi bi-clock"></i>
                            <?php 
                            $date = new DateTime($notif['date']);
                            $now = new DateTime();
                            $diff = $now->diff($date);
                            
                            if ($diff->days > 0) {
                                echo $diff->days . ' jour' . ($diff->days > 1 ? 's' : '');
                            } elseif ($diff->h > 0) {
                                echo $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
                            } elseif ($diff->i > 0) {
                                echo $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
                            } else {
                                echo 'À l\'instant';
                            }
                            ?>
                        </small>
                    </div>
                    <div>
                        <a href="<?php echo $notif['lien']; ?>" class="btn btn-sm btn-primary">
                            <?php if ($notif['type'] === 'demande_ami'): ?>
                                <i class="bi bi-person-check"></i> Voir
                            <?php elseif ($notif['type'] === 'message'): ?>
                                <i class="bi bi-reply"></i> Répondre
                            <?php else: ?>
                                <i class="bi bi-eye"></i> Voir
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
