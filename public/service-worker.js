// service-worker.js - VERSION AVEC CACHE DES COURS
const CACHE_NAME = 'wavetalk-v2';
const COURSES_CACHE = 'wavetalk-courses';

// Fichiers à mettre en cache initialement
const urlsToCache = [
    '/',
    '/index.php',
    '/css/style.css',
    '/js/audio-tracker.js',
    '/offline.php',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

// Installer le service worker
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(urlsToCache))
            .then(() => self.skipWaiting())
    );
});

// Activer le service worker
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME && cacheName !== COURSES_CACHE) {
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Intercepter les requêtes
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    
    // Gestion des téléchargements de cours
    if (url.pathname.includes('/api/download_course.php')) {
        event.respondWith(handleCourseDownload(event));
        return;
    }
    
    // Pour les fichiers audio des cours téléchargés
    if (url.pathname.includes('/audio/')) {
        event.respondWith(serveAudioFromCache(event));
        return;
    }
    
    // Stratégie "network first, cache fallback" pour les pages
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    // Mettre en cache la page
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME)
                        .then(cache => cache.put(event.request, responseClone));
                    return response;
                })
                .catch(() => {
                    // Retourner la page offline depuis le cache
                    return caches.match(event.request)
                        .then(response => response || caches.match('/offline.php'));
                })
        );
        return;
    }
    
    // Stratégie "cache first" pour les assets statiques
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    return response;
                }
                
                return fetch(event.request)
                    .then(response => {
                        // Ne pas mettre en cache les requêtes non-GET ou non-OK
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }
                        
                        const responseToCache = response.clone();
                        caches.open(CACHE_NAME)
                            .then(cache => cache.put(event.request, responseToCache));
                        
                        return response;
                    })
                    .catch(() => {
                        // Pour les fichiers audio, retourner une réponse vide
                        if (event.request.url.includes('.mp3')) {
                            return new Response('', {
                                headers: { 'Content-Type': 'audio/mpeg' }
                            });
                        }
                        return null;
                    });
            })
    );
});

// Gestion des téléchargements de cours
function handleCourseDownload(event) {
    return fetch(event.request)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre les fichiers audio en cache
                cacheCourseAudio(data.data);
            }
            return new Response(JSON.stringify(data), {
                headers: { 'Content-Type': 'application/json' }
            });
        })
        .catch(error => {
            return new Response(JSON.stringify({
                success: false,
                message: 'Erreur réseau'
            }), {
                headers: { 'Content-Type': 'application/json' }
            });
        });
}

// Mettre en cache les fichiers audio d'un cours
function cacheCourseAudio(courseData) {
    caches.open(COURSES_CACHE).then(cache => {
        courseData.chapters.forEach(chapter => {
            if (chapter.audio_file) {
                const audioUrl = `/audio/${chapter.audio_file}`;
                cache.add(audioUrl).catch(console.error);
            }
        });
        
        // Stocker les métadonnées du cours
        const metadata = {
            id: courseData.course_id,
            title: courseData.title,
            downloaded_at: new Date().toISOString()
        };
        
        cache.put(`/api/course_metadata/${courseData.course_id}`, 
            new Response(JSON.stringify(metadata)));
    });
}

// Servir les fichiers audio depuis le cache
function serveAudioFromCache(event) {
    return caches.match(event.request)
        .then(response => {
            if (response) {
                return response;
            }
            
            // Si pas dans le cache, essayer de récupérer depuis le réseau
            return fetch(event.request)
                .then(networkResponse => {
                    // Mettre en cache pour la prochaine fois
                    const responseClone = networkResponse.clone();
                    caches.open(COURSES_CACHE)
                        .then(cache => cache.put(event.request, responseClone));
                    return networkResponse;
                })
                .catch(() => {
                    // Retourner une réponse audio vide
                    return new Response('', {
                        headers: { 'Content-Type': 'audio/mpeg' }
                    });
                });
        });
}

// Synchronisation en arrière-plan
self.addEventListener('sync', event => {
    if (event.tag === 'sync-progress') {
        event.waitUntil(syncProgress());
    }
});

// Synchroniser la progression
function syncProgress() {
    return getPendingProgress()
        .then(progressData => {
            if (progressData.length === 0) return;
            
            return fetch('/api/sync_progress.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ progress: progressData })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    clearPendingProgress();
                }
            });
        });
}

// Fonctions pour gérer la progression hors ligne
function getPendingProgress() {
    return new Promise(resolve => {
        resolve(JSON.parse(localStorage.getItem('pendingProgress') || '[]'));
    });
}

function clearPendingProgress() {
    localStorage.removeItem('pendingProgress');
}