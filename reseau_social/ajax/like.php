<?php
require_once '../config/session.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!estConnecte()) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publication_id'])) {
    $publication_id = (int)$_POST['publication_id'];
    $utilisateur_id = getUserId();
    
    // Verifier si l'utilisateur a deje like
    $stmt = $pdo->prepare("SELECT * FROM likes WHERE publication_id = ? AND utilisateur_id = ?");
    $stmt->execute([$publication_id, $utilisateur_id]);
    $like = $stmt->fetch();
    
    if ($like) {
        // Unlike
        $stmt = $pdo->prepare("DELETE FROM likes WHERE publication_id = ? AND utilisateur_id = ?");
        $stmt->execute([$publication_id, $utilisateur_id]);
        $liked = false;
    } else {
        // Like
        $stmt = $pdo->prepare("INSERT INTO likes (publication_id, utilisateur_id) VALUES (?, ?)");
        $stmt->execute([$publication_id, $utilisateur_id]);
        $liked = true;
    }
    
    // Compter le nombre de likes
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE publication_id = ?");
    $stmt->execute([$publication_id]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'nombre_likes' => $count
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
}
?>
