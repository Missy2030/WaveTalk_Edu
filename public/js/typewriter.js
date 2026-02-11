
class Typewriter {
    constructor(element, texts, options = {}) {
        this.element = element;
        this.texts = texts;
        this.options = {
            typingSpeed: 100, // ms per character
            deletingSpeed: 50,
            pauseTime: 2000, // ms between texts
            loop: true,
            cursor: '|',
            cursorBlinkSpeed: 500,
            ...options
        };
        
        this.currentTextIndex = 0;
        this.currentCharIndex = 0;
        this.isDeleting = false;
        this.isPaused = false;
        this.cursorVisible = true;
        
        this.init();
    }
    
    init() {
        // Créer le curseur
        this.cursorElement = document.createElement('span');
        this.cursorElement.className = 'typewriter-cursor';
        this.cursorElement.textContent = this.options.cursor;
        this.cursorElement.style.color = '#6366F1';
        this.cursorElement.style.fontWeight = 'bold';
        this.cursorElement.style.marginLeft = '2px';
        
        this.element.parentNode.insertBefore(this.cursorElement, this.element.nextSibling);
        
        // Démarrer l'animation
        this.type();
        
        // Animation du curseur
        setInterval(() => {
            this.cursorVisible = !this.cursorVisible;
            this.cursorElement.style.opacity = this.cursorVisible ? '1' : '0';
        }, this.options.cursorBlinkSpeed);
    }
    
    type() {
        const currentText = this.texts[this.currentTextIndex];
        
        if (this.isDeleting) {
            // Supprimer un caractère
            this.element.textContent = currentText.substring(0, this.currentCharIndex - 1);
            this.currentCharIndex--;
            
            if (this.currentCharIndex === 0) {
                this.isDeleting = false;
                this.currentTextIndex = (this.currentTextIndex + 1) % this.texts.length;
                setTimeout(() => this.type(), this.options.pauseTime);
                return;
            }
        } else {
            // Ajouter un caractère
            this.element.textContent = currentText.substring(0, this.currentCharIndex + 1);
            this.currentCharIndex++;
            
            if (this.currentCharIndex === currentText.length) {
                this.isDeleting = true;
                setTimeout(() => this.type(), this.options.pauseTime);
                return;
            }
        }
        
        const speed = this.isDeleting ? this.options.deletingSpeed : this.options.typingSpeed;
        setTimeout(() => this.type(), speed);
    }
}

// Fonction utilitaire pour initialiser le typewriter
function initWelcomeTypewriter() {
    const welcomeMessages = {
        student: [
            "Bienvenue sur WaveTalk Édu ! 📚",
            "Prêt à apprendre avec l'audio ? 🎧",
            "Découvre tes cours interactifs...",
            "Collectionne des badges ! 🏆",
            "Ton apprentissage commence ici ! 🚀"
        ],
        parent: [
            "Bienvenue sur WaveTalk Édu ! 👨‍👦",
            "Suivez la progression de vos enfants...",
            "Restez informé des succès ! 🏆",
            "L'éducation audio pour tous 🎧",
            "Accompagnez leur apprentissage 💪"
        ]
    };
    
    const userRole = document.body.getAttribute('data-user-role') || 'student';
    const messages = welcomeMessages[userRole] || welcomeMessages.student;
    
    const typewriterElement = document.getElementById('typewriter-text');
    if (typewriterElement) {
        new Typewriter(typewriterElement, messages, {
            typingSpeed: 80,
            deletingSpeed: 40,
            pauseTime: 1500,
            cursor: '▌'
        });
    }
}

// Initialiser quand la page est chargée
document.addEventListener('DOMContentLoaded', initWelcomeTypewriter);
