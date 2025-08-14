// assets/js/script.js
// Role selection
function selectRole(role) {
    document.getElementById('role').value = role;
    document.querySelectorAll('.role-option').forEach(el => {
        el.classList.remove('selected');
    });
    document.getElementById(role + '-option').classList.add('selected');
}

// Initialize role selection
document.addEventListener('DOMContentLoaded', function() {
    // Default to admin selection
    selectRole('admin');
    
    // Show notification if exists
    const notification = document.getElementById('success-notification');
    if (notification) {
        setTimeout(() => {
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }, 500);
        
        // Create confetti effect
        createConfetti();
    }
});

// Create confetti effect
function createConfetti() {
    const container = document.body;
    const colors = ['#6c5ce7', '#00cec9', '#fdcb6e', '#e17055', '#00b894'];
    
    for (let i = 0; i < 50; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        confetti.style.left = Math.random() * 100 + 'vw';
        confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.width = (Math.random() * 10 + 5) + 'px';
        confetti.style.height = confetti.style.width;
        confetti.style.animationDuration = (Math.random() * 2 + 1) + 's';
        container.appendChild(confetti);
        
        setTimeout(() => {
            confetti.remove();
        }, 1500);
    }
}