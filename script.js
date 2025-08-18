// Initialize role selection
document.addEventListener('DOMContentLoaded', function() {
    // Role selection
    if (document.getElementById('admin-option')) {
        selectRole('admin');
    }
    
    // Auto-hide notifications
    const notifications = document.querySelectorAll('.notification.show');
    notifications.forEach(notification => {
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    });
    
    // Confetti effect for success
    if (document.getElementById('success-notification')) {
        createConfetti();
    }
});

function selectRole(role) {
    const roleInput = document.getElementById('role');
    if (roleInput) roleInput.value = role;
    
    document.querySelectorAll('.role-option').forEach(el => {
        el.classList.remove('selected');
    });
    
    const option = document.getElementById(role + '-option');
    if (option) option.classList.add('selected');
}

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