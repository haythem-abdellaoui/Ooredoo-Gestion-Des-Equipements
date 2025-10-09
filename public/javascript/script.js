// Animation dynamique lors de la soumission du formulaire
const form = document.querySelector('.login-form');
const btn = document.querySelector('.login-btn');


// Effet sur les bulles au survol du container
const bubbles = document.querySelectorAll('.bubbles span');
document.querySelector('.login-container').addEventListener('mousemove', (e) => {
  bubbles.forEach((bubble, i) => {
    const x = (e.clientX / window.innerWidth) * 100;
    const y = (e.clientY / window.innerHeight) * 100;
    bubble.style.transform = `translateY(-${y/2 + i*5}px) scale(${1 + x/200})`;
  });
});

document.querySelector('.login-container').addEventListener('mouseleave', () => {
  bubbles.forEach((bubble) => {
    bubble.style.transform = '';
  });
});

// Affichage/masquage du mot de passe
const passwordInput = document.getElementById('password');
const togglePasswordBtn = document.querySelector('.toggle-password');

if (togglePasswordBtn) {
  togglePasswordBtn.addEventListener('click', function() {
    const isPassword = passwordInput.type === 'password';
    passwordInput.type = isPassword ? 'text' : 'password';
    this.classList.toggle('active', !isPassword);
    // Changer l'icône (œil ouvert/fermé)
    this.innerHTML = isPassword
      ? `<svg viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12C2.73 7.61 7.11 4.5 12 4.5C16.89 4.5 21.27 7.61 23 12C21.27 16.39 16.89 19.5 12 19.5C7.11 19.5 2.73 16.39 1 12Z" stroke="#ff0033" stroke-width="2"/><circle cx="12" cy="12" r="3.5" stroke="#ff0033" stroke-width="2"/></svg>`
      : `<svg viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12C2.73 7.61 7.11 4.5 12 4.5C16.89 4.5 21.27 7.61 23 12C21.27 16.39 16.89 19.5 12 19.5C7.11 19.5 2.73 16.39 1 12Z" stroke="#ff0033" stroke-width="2"/><circle cx="12" cy="12" r="3.5" stroke="#ff0033" stroke-width="2"/><line x1="5" y1="19" x2="19" y2="5" stroke="#ff0033" stroke-width="2"/></svg>`;
    // Ajout/retrait de la classe pour réduire la largeur
    passwordInput.classList.toggle('password-toggled');
  });
} 