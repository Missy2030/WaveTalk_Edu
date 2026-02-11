document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.createElement('button');
    toggle.innerText = '🌙 Mode sombre';
    toggle.className = 'dark-toggle';
    document.body.appendChild(toggle);

    // Charger le thème depuis localStorage
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
        toggle.innerText = '☀️ Mode clair';
    }

    toggle.addEventListener('click', function () {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        toggle.innerText = isDark ? '☀️ Mode clair' : '🌙 Mode sombre';
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });
});
