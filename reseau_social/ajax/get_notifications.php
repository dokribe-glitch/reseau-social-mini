<?php
require_once '../config/session.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!estConnecte()) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit();
}

$user_id = getUserId();
$total_notifications = 0;

// Compter les demandes d'amis en attente
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM amis WHERE ami_id = ? AND statut = 'en_attente'");
$stmt->execute([$user_id]);
$demandes_amis = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
$total_notifications += $demandes_amis;

// Compter les messages non lus
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages_prives WHERE destinataire_id = ? AND lu = FALSE");
$stmt->execute([$user_id]);
$messages_non_lus = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
$total_notifications += $messages_non_lus;

// Compter les nouveaux likes (dernières 24h)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM likes l
    JOIN publications p ON l.publication_id = p.id
    WHERE p.utilisateur_id = ? 
      AND l.utilisateur_id != ?
      AND l.date_like > DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$stmt->execute([$user_id, $user_id]);
$nouveaux_likes = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

echo json_encode([
    'success' => true,
    'count' => $total_notifications,
    'unread_messages' => $messages_non_lus,
    'friend_requests' => $demandes_amis,
    'new_likes' => $nouveaux_likes
]);
?>
