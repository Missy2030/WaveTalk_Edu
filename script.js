// Fonction pour animer les podcasts lors du chargement
function animatePodcasts() {
    let podcasts = document.querySelectorAll(".podcast");
    podcasts.forEach((pod, index) => {
        setTimeout(() => {
            pod.classList.add("show");
        }, index * 200);
    });
}

// Charger les podcasts et appliquer l'animation
function loadPodcasts() {
    let category = document.getElementById("category").value;
    fetch("get_podcasts.php?category=" + category)
        .then(response => response.text())
        .then(data => {
            document.getElementById("podcast-list").innerHTML = data;
            animatePodcasts();
        })
        .catch(error => console.error("Erreur:", error));
}