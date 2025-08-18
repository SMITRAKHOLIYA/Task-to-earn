<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task to Earn | Futuristic Task Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="floating floating-1"></div>
    <div class="floating floating-2"></div>
    <div class="floating floating-3"></div>
    
    <!-- Navigation Bar -->
    <nav class="main-nav">
        <div class="container">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="logo-text">Task to Earn</div>
            </div>
            
            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#how-it-works">How It Works</a>
                <a href="#testimonials">Testimonials</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php
                    $dashboardLink = ($_SESSION['role'] == 'admin') ? 'dashboard_admin.php' : 'dashboard_child.php';
                    ?>
                    <a href="<?= $dashboardLink ?>" class="btn btn-outline">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="logout.php" class="btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Transform Tasks <span class="gradient-text">Into Rewards</span></h1>
            <p class="hero-subtitle">A futuristic platform that motivates children to complete tasks and earn exciting rewards</p>
            <div class="hero-buttons">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-rocket"></i> Get Started
                    </a>
                <?php else: ?>
                    <a href="<?= $dashboardLink ?>" class="btn btn-primary">
                        <i class="fas fa-rocket"></i> Go to Dashboard
                    </a>
                <?php endif; ?>
                <a href="#features" class="btn btn-outline">
                    <i class="fas fa-binoculars"></i> Explore Features
                </a>
            </div>
        </div>
        <div class="hero-image">
            <div class="hero-illustration">
                <div class="particle particle-1"></div>
                <div class="particle particle-2"></div>
                <div class="particle particle-3"></div>
                <div class="hero-card">
                    <div class="card-points">+50 pts</div>
                    <div class="card-title">Clean Your Room</div>
                    <div class="card-checkmark">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
 <!-- Features Section -->
    <section class="features" id="features">
        <div class="section-header">
            <h2>Why Choose <span class="gradient-text">Task to Earn</span></h2>
            <p>Our platform combines gamification with task management for a fun, rewarding experience</p>
        </div>
        
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-gamepad"></i>
                </div>
                <h3>Gamified Experience</h3>
                <p>Earn points, unlock achievements, and level up as you complete tasks</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-gift"></i>
                </div>
                <h3>Exciting Rewards</h3>
                <p>Redeem your hard-earned points for real-world rewards and privileges</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Progress Tracking</h3>
                <p>Visual dashboards show your accomplishments and growth over time</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3>Parental Control</h3>
                <p>Parents can create tasks, set rewards, and monitor progress</p>
            </div>
        </div>
    </section>
    
    <!-- How It Works Section -->
    <section class="how-it-works" id="how-it-works">
        <div class="section-header">
            <h2>How It <span class="gradient-text">Works</span></h2>
            <p>Simple steps to transform chores into exciting challenges</p>
        </div>
        
        <div class="steps-container">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3>Parents Create Tasks</h3>
                    <p>Define tasks with clear descriptions and point values</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3>Children Complete Tasks</h3>
                    <p>Kids view their task list and mark items as completed</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3>Earn Points</h3>
                    <p>Points are automatically added to the child's account</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h3>Redeem Rewards</h3>
                    <p>Exchange points for pre-approved rewards and privileges</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Testimonials Section -->
    <section class="testimonials" id="testimonials">
        <div class="section-header">
            <h2>What <span class="gradient-text">Families Say</span></h2>
            <p>Hear from parents and children who transformed their routines</p>
        </div>
        
        <div class="testimonial-grid">
            <div class="testimonial-card">
                <div class="testimonial-content">
                    <div class="testimonial-quote">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p>Task to Earn has completely changed how my kids approach chores. They actually ask for tasks now!</p>
                </div>
                <div class="testimonial-author">
                    <div class="author-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="author-info">
                        <h4>Sarah Johnson</h4>
                        <p>Parent</p>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card">
                <div class="testimonial-content">
                    <div class="testimonial-quote">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p>I earned enough points for a new video game by doing my homework and chores. Best system ever!</p>
                </div>
                <div class="testimonial-author">
                    <div class="author-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="author-info">
                        <h4>Michael T.</h4>
                        <p>Age 12</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Call to Action Section -->
    <section class="cta">
        <div class="cta-content">
            <h2>Ready to Transform Your Family's Routine?</h2>
            <p>Join thousands of families who've made chores fun and rewarding</p>
            <a href="#" id="cta-login" class="btn btn-primary btn-large">
                <i class="fas fa-rocket"></i> Start Now
            </a>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-bottom">
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
            </div>
            <p>&copy; 2023 Task to Earn. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        // Role selection
        function selectRole(role) {
            document.getElementById('role').value = role;
            document.querySelectorAll('.role-option').forEach(el => {
                el.classList.remove('selected');
            });
            document.getElementById(role + '-option').classList.add('selected');
        }
        
        // Modal functionality
        const modal = document.getElementById("loginModal");
        const loginBtns = document.querySelectorAll("#login-toggle, #hero-login, #cta-login");
        const closeBtn = document.querySelector(".close");
        
        // Open modal when any login button is clicked
        loginBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                modal.style.display = "block";
            });
        });
        
        // Close modal
        closeBtn.addEventListener('click', function() {
            modal.style.display = "none";
        });
        
        // Close when clicking outside modal
        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        });
        
        // Initialize role selection
        document.addEventListener('DOMContentLoaded', function() {
            selectRole('admin');
            animateParticles();
        });
        
        // Particle animation
        function animateParticles() {
            const particles = document.querySelectorAll('.particle');
            
            particles.forEach((particle, index) => {
                const x = Math.random() * 100;
                const y = Math.random() * 100;
                particle.style.left = `${x}%`;
                particle.style.top = `${y}%`;
                const duration = 15 + Math.random() * 10;
                particle.style.animation = `float ${duration}s infinite ease-in-out ${index * 2}s`;
            });
        }
        
        // Floating animations
        const floaters = document.querySelectorAll('.floating');
        floaters.forEach((floater, index) => {
            const duration = 20 + Math.random() * 10;
            floater.style.animation = `float ${duration}s infinite ease-in-out ${index * 3}s`;
        });
    </script>
</body>
</html>
