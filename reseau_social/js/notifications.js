// Notifications en temps réel avec polling Ajax

// Vérifier les notifications toutes les 10 secondes
function checkNotifications() {
    fetch('ajax/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour le compteur de notifications
                const notifBadge = document.getElementById('notifications-count');
                if (notifBadge) {
                    if (data.count > 0) {
                        notifBadge.textContent = data.count;
                        notifBadge.style.display = 'inline-block';
                    } else {
                        notifBadge.style.display = 'none';
                    }
                }
                
                // Mettre à jour le compteur de messages non lus
                const msgBadge = document.getElementById('unread-messages-count');
                if (msgBadge) {
                    if (data.unread_messages > 0) {
                        msgBadge.textContent = data.unread_messages;
                        msgBadge.style.display = 'inline-block';
                    } else {
                        msgBadge.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => console.error('Erreur lors de la vérification des notifications:', error));
}

// Lancer la vérification au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    checkNotifications();
    
    // Répéter toutes les 10 secondes
    setInterval(checkNotifications, 10000);
});
