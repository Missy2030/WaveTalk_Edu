# 🎧 WaveTalk Édu - Plateforme d'apprentissage audio

Plateforme éducative innovante basée sur l'apprentissage par audio, destinée aux collégiens et lycéens.

## 🚀 Installation Rapide

### 1. Configuration de la base de données

```bash
# Créer la base de données
mysql -u root -p
CREATE DATABASE Wavetalk_Edu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE Wavetalk_Edu;
SOURCE setup_data_wavetalk.sql;
SOURCE OPTIMISER_BASE.sql;
EXIT;
```

### 2. Configuration de la connexion

Éditez `private/db_connection.php` :

```php
$username = 'root';      // Votre utilisateur MySQL
$password = '';          // Votre mot de passe MySQL
```

### 3. Tester localement

```bash
php -S localhost:8000 -t public
```

Ouvrez : http://localhost:8000

## 👤 Comptes de test

- **Élève** : `eleve@wavetalk.edu` / `password`
- **Parent** : `parent@wavetalk.edu` / `password`

## ✨ Fonctionnalités

- ✅ Apprentissage par audio
- ✅ Quiz interactifs
- ✅ Système de badges et gamification
- ✅ Suivi parental
- ✅ Certificats générés automatiquement
- ✅ PWA (mode hors-ligne)
- ✅ Multi-rôles (élève, parent, enseignant)

## 📁 Structure

```
WaveTalk_Edu/
├── public/              # Fichiers accessibles publiquement
│   ├── index.php        # Page d'accueil
│   ├── login.php        # Connexion
│   ├── student/         # Dashboard élève
│   ├── parent/          # Dashboard parent
│   └── teacher/         # Dashboard enseignant
├── private/             # Fichiers privés
│   └── db_connection.php # Connexion DB
└── includes/            # Fonctions communes
```

## 🔧 Optimisations appliquées

- ✅ Sessions sécurisées
- ✅ Suppression des doublons session_start()
- ✅ Service Worker optimisé
- ✅ Compression GZIP
- ✅ Cache navigateur
- ✅ Index SQL ajoutés
- ✅ Protection XSS/CSRF

## 📝 Prochaines étapes

1. Ajouter des fichiers audio dans `public/audio/`
2. Générer les icônes PWA dans `public/assets/icons/`
3. Personnaliser les cours et quiz
4. Déployer sur un serveur

## 🆘 Support

Pour toute question, ouvrir une issue sur GitHub.

---

**Version** : 2.0 (Corrigée et optimisée)
**Date** : Février 2026
