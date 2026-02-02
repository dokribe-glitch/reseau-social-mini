<?php
require_once 'config/session.php';
require_once 'config/database.php';

requireLogin();

$pageTitle = 'Messages';

// Envoi d'un message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['envoyer'])) {
    $destinataire_id = (int)$_POST['destinataire_id'];
    $message = trim($_POST['message']);
    
    if (!empty($message) && $destinataire_id !== getUserId()) {
        $stmt = $pdo->prepare("INSERT INTO messages_prives (expediteur_id, destinataire_id, message) VALUES (?, ?, ?)");
        $stmt->execute([getUserId(), $destinataire_id, $message]);
        
        echo json_encode(['success' => true]);
        exit();
    }
}

// Marquer les messages comme lus
if (isset($_GET['marquer_lu'])) {
    $expediteur_id = (int)$_GET['marquer_lu'];
    $stmt = $pdo->prepare("UPDATE messages_prives SET lu = TRUE WHERE expediteur_id = ? AND destinataire_id = ?");
    $stmt->execute([$expediteur_id, getUserId()]);
}

// Utilisateur sélectionné
$selected_user_id = isset($_GET['user']) ? (int)$_GET['user'] : null;

// Récupérer la liste des conversations
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.pseudo, u.nom_complet, u.photo_profil,
           (SELECT COUNT(*) FROM messages_prives 
            WHERE expediteur_id = u.id AND destinataire_id = ? AND lu = FALSE) as non_lus,
           (SELECT message FROM messages_prives 
            WHERE (expediteur_id = u.id AND destinataire_id = ?) 
               OR (expediteur_id = ? AND destinataire_id = u.id)
            ORDER BY date_envoi DESC LIMIT 1) as dernier_message,
           (SELECT date_envoi FROM messages_prives 
            WHERE (expediteur_id = u.id AND destinataire_id = ?) 
               OR (expediteur_id = ? AND destinataire_id = u.id)
            ORDER BY date_envoi DESC LIMIT 1) as date_dernier_message
    FROM utilisateurs u
    WHERE u.id IN (
        SELECT DISTINCT 
            CASE 
                WHEN expediteur_id = ? THEN destinataire_id
                ELSE expediteur_id
            END as contact_id
        FROM messages_prives
        WHERE expediteur_id = ? OR destinataire_id = ?
    )
    AND u.id IN (
        SELECT ami_id FROM amis WHERE utilisateur_id = ? AND statut = 'accepte'
        UNION
        SELECT utilisateur_id FROM amis WHERE ami_id = ? AND statut = 'accepte'
    )
    ORDER BY date_dernier_message DESC
");
$stmt->execute([
    getUserId(), getUserId(), getUserId(), getUserId(), getUserId(),
    getUserId(), getUserId(), getUserId(),
    getUserId(), getUserId()
]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si un utilisateur est sélectionné, récupérer les messages
$messages = [];
$selected_user = null;
if ($selected_user_id) {
    // Vérifier que l'utilisateur est un ami
    $stmt = $pdo->prepare("
        SELECT * FROM amis 
        WHERE ((utilisateur_id = ? AND ami_id = ?) OR (utilisateur_id = ? AND ami_id = ?))
          AND statut = 'accepte'
    ");
    $stmt->execute([getUserId(), $selected_user_id, $selected_user_id, getUserId()]);
    
    if ($stmt->fetch()) {
        // Récupérer les infos de l'utilisateur
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
        $stmt->execute([$selected_user_id]);
        $selected_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Récupérer les messages
        $stmt = $pdo->prepare("
            SELECT m.*, u.pseudo, u.photo_profil
            FROM messages_prives m
            JOIN utilisateurs u ON m.expediteur_id = u.id
            WHERE (m.expediteur_id = ? AND m.destinataire_id = ?)
               OR (m.expediteur_id = ? AND m.destinataire_id = ?)
            ORDER BY m.date_envoi ASC
        ");
        $stmt->execute([getUserId(), $selected_user_id, $selected_user_id, getUserId()]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Marquer les messages comme lus
        $stmt = $pdo->prepare("UPDATE messages_prives SET lu = TRUE WHERE expediteur_id = ? AND destinataire_id = ?");
        $stmt->execute([$selected_user_id, getUserId()]);
    }
}

include 'includes/header.php';
?>

<div class="messages-container">
    <!-- Liste des conversations -->
    <div class="conversations-list">
        <h5 class="mb-3"><i class="bi bi-chat-dots"></i> Conversations</h5>
        
        <?php if (empty($conversations)): ?>
            <div class="text-center text-muted">
                <i class="bi bi-inbox" style="font-size: 40px;"></i>
                <p class="mt-2">Aucune conversation</p>
                <a href="amis.php" class="btn btn-sm btn-primary">Voir mes amis</a>
            </div>
        <?php else: ?>
            <?php foreach ($conversations as $conv): ?>
                <a href="?user=<?php echo $conv['id']; ?>" class="text-decoration-none">
                    <div class="conversation-item <?php echo $selected_user_id === $conv['id'] ? 'active' : ''; ?> <?php echo $conv['non_lus'] > 0 ? 'unread' : ''; ?>">
                        <img src="<?php echo $conv['photo_profil'] ? 'uploads/profils/' . htmlspecialchars($conv['photo_profil']) : 'https://via.placeholder.com/40'; ?>" 
                             alt="Photo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <strong><?php echo htmlspecialchars($conv['nom_complet'] ?: $conv['pseudo']); ?></strong>
                                <?php if ($conv['non_lus'] > 0): ?>
                                    <span class="badge bg-danger"><?php echo $conv['non_lus']; ?></span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">
                                <?php echo htmlspecialchars(substr($conv['dernier_message'], 0, 30)); ?>
                                <?php echo strlen($conv['dernier_message']) > 30 ? '...' : ''; ?>
                            </small>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Zone de chat -->
    <div class="messages-chat">
        <?php if ($selected_user): ?>
            <!-- En-tête du chat -->
            <div class="chat-header">
                <img src="<?php echo $selected_user['photo_profil'] ? 'uploads/profils/' . htmlspecialchars($selected_user['photo_profil']) : 'https://via.placeholder.com/40'; ?>" 
                     alt="Photo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                <div>
                    <a href="profil.php?id=<?php echo $selected_user['id']; ?>" class="text-decoration-none">
                        <strong><?php echo htmlspecialchars($selected_user['nom_complet'] ?: $selected_user['pseudo']); ?></strong>
                    </a>
                    <br>
                    <small class="text-muted">@<?php echo htmlspecialchars($selected_user['pseudo']); ?></small>
                </div>
            </div>
            
            <!-- Messages -->
            <div class="chat-messages" id="chat-messages">
                <?php foreach ($messages as $msg): ?>
                    <div class="message-bubble <?php echo $msg['expediteur_id'] === getUserId() ? 'message-sent' : 'message-received'; ?>">
                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        <div class="message-time">
                            <?php 
                            $date = new DateTime($msg['date_envoi']);
                            echo $date->format('H:i');
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Zone d'envoi -->
            <div class="chat-input">
                <form id="message-form" class="d-flex gap-2 w-100">
                    <input type="hidden" name="destinataire_id" value="<?php echo $selected_user['id']; ?>">
                    <input type="text" name="message" class="form-control" 
                           placeholder="Écrire un message..." required autocomplete="off">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send"></i>
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="d-flex align-items-center justify-content-center h-100">
                <div class="text-center text-muted">
                    <i class="bi bi-chat-dots" style="font-size: 80px;"></i>
                    <p class="mt-3">Sélectionnez une conversation pour commencer</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-scroll vers le bas
const chatMessages = document.getElementById('chat-messages');
if (chatMessages) {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Envoi de message en Ajax
const messageForm = document.getElementById('message-form');
if (messageForm) {
    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('envoyer', '1');
        
        fetch('messages.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    });
}

// Polling pour les nouveaux messages (toutes les 5 secondes)
<?php if ($selected_user_id): ?>
setInterval(function() {
    fetch('ajax/check_messages.php?user=<?php echo $selected_user_id; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.new_messages) {
                location.reload();
            }
        });
}, 5000);
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
