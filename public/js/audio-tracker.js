/**
 * WaveTalk Edu - Tracker de progression audio
 * Envoie la position d'écoute toutes les 10 secondes
 */

class AudioProgressTracker {
    constructor(audioElement, chapterId, userId) {
        this.audio = audioElement;
        this.chapterId = chapterId;
        this.userId = userId;
        this.lastPosition = 0;
        this.totalListeningTime = 0;
        this.trackingInterval = null;
        this.isTracking = false;
        
        this.init();
    }
    
    init() {
        // Démarrer le tracking quand la lecture commence
        this.audio.addEventListener('play', () => this.startTracking());
        this.audio.addEventListener('pause', () => this.stopTracking());
        this.audio.addEventListener('ended', () => this.onAudioEnded());
        
        // Envoyer la position initiale
        setTimeout(() => this.sendProgress(), 2000);
    }
    
    startTracking() {
        if (this.isTracking) return;
        
        this.isTracking = true;
        console.log(`[AudioTracker] Début du tracking pour le chapitre ${this.chapterId}`);
        
        // Envoyer la progression toutes les 10 secondes
        this.trackingInterval = setInterval(() => {
            this.sendProgress();
        }, 10000); // 10 secondes
        
        // Calculer le temps d'écoute total
        this.audio.addEventListener('timeupdate', this.calculateListeningTime.bind(this));
    }
    
    stopTracking() {
        if (!this.isTracking) return;
        
        this.isTracking = false;
        clearInterval(this.trackingInterval);
        this.sendProgress(); // Dernier envoi
        console.log(`[AudioTracker] Tracking arrêté pour le chapitre ${this.chapterId}`);
    }
    
    calculateListeningTime() {
        const currentTime = this.audio.currentTime;
        const delta = currentTime - this.lastPosition;
        
        // On compte seulement si l'avancement est positif et inférieur à 5 secondes
        // (pour éviter de compter les sauts manuels)
        if (delta > 0 && delta < 5) {
            this.totalListeningTime += delta;
        }
        
        this.lastPosition = currentTime;
    }
    
    sendProgress() {
        const currentTime = Math.floor(this.audio.currentTime);
        const duration = Math.floor(this.audio.duration);
        const isCompleted = this.audio.ended || currentTime >= duration - 5; // Terminé si à moins de 5s de la fin
        
        const data = {
            user_id: this.userId,
            chapter_id: this.chapterId,
            current_position: currentTime,
            duration: duration,
            listening_time: Math.floor(this.totalListeningTime),
            is_completed: isCompleted
        };
        
        // Envoyer à l'API
        fetch('../api/track_progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                console.log(`[AudioTracker] Progression sauvegardée: ${currentTime}s`);
                
                // Si le chapitre est terminé, rediriger vers le quiz
                if (isCompleted && result.quiz_available) {
                    this.showQuizNotification(result.quiz_url);
                }
            }
        })
        .catch(error => {
            console.error('[AudioTracker] Erreur:', error);
        });
    }
    
    onAudioEnded() {
        this.stopTracking();
        this.sendProgress(); // Envoyer une dernière fois
        console.log(`[AudioTracker] Audio terminé pour le chapitre ${this.chapterId}`);
        
        // Marquer comme complété dans l'interface
        const completeEvent = new CustomEvent('chapterCompleted', { 
            detail: { chapterId: this.chapterId } 
        });
        document.dispatchEvent(completeEvent);
    }
    
    showQuizNotification(quizUrl) {
        // Créer une notification pour le quiz
        const notification = document.createElement('div');
        notification.className = 'quiz-notification';
        notification.innerHTML = `
            <div class="notification-content">
                <h3>🎉 Chapitre terminé !</h3>
                <p>Testez vos connaissances avec le quiz.</p>
                <div class="notification-buttons">
                    <a href="${quizUrl}" class="btn btn-primary">Passer le quiz</a>
                    <button class="btn btn-secondary" onclick="this.parentElement.parentElement.remove()">Plus tard</button>
                </div>
            </div>
        `;
        
        // Style basique
        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            border: 2px solid #4CAF50;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            max-width: 300px;
        `;
        
        document.body.appendChild(notification);
        
        // Disparaître après 10 secondes
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 10000);
    }
}

// Initialisation automatique si les données sont présentes
document.addEventListener('DOMContentLoaded', function() {
    const audioElement = document.querySelector('audio[data-chapter-id]');
    
    if (audioElement) {
        const chapterId = audioElement.getAttribute('data-chapter-id');
        const userId = document.body.getAttribute('data-user-id') || '1'; // Récupérer de la session
        
        window.audioTracker = new AudioProgressTracker(audioElement, chapterId, userId);
    }
});