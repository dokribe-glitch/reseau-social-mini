<?php
require_once '../config/session.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!estConnecte()) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit();
}

if (isset($_GET['user'])) {
    $user_id = (int)$_GET['user'];
    
    // Verifier s'il y a de nouveaux messages non lus
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM messages_prives 
        WHERE expediteur_id = ? AND destinataire_id = ? AND lu = FALSE
    ");
    $stmt->execute([$user_id, getUserId()]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'new_messages' => $result['count'] > 0
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Paramètre manquant']);
}
?>
