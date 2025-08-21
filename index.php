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
    <style>
        /* General Styles & Variables */
        :root {
            --color-primary: #8a2be2;
            --color-secondary: #00bcd4;
            --color-gradient-1: #8a2be2;
            --color-gradient-2: #00bcd4;
            --color-bg-dark: #121212;
            --color-text-light: #f0f0f0;
            --color-card-bg: #1e1e1e;
            --color-border: #333;
            --font-main: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            --shadow-light: 0 4px 15px rgba(0, 0, 0, 0.2);
            --shadow-card: 0 8px 30px rgba(0, 0, 0, 0.4);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--font-main);
            color: var(--color-text-light);
            background-color: var(--color-bg-dark);
            line-height: 1.6;
            overflow-x: hidden;
            position: relative;
        }

        /* Floating Background Elements */
        .floating {
            position: absolute;
            width: 30vw;
            height: 30vw;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--color-gradient-1), var(--color-gradient-2));
            filter: blur(80px);
            opacity: 0.15;
            z-index: -1;
            animation-duration: 25s;
            animation-iteration-count: infinite;
            animation-timing-function: ease-in-out;
        }
        .floating-1 { top: 10%; left: -10%; animation-name: float-1; }
        .floating-2 { top: 40%; right: -10%; animation-name: float-2; }
        .floating-3 { bottom: 5%; left: 20%; animation-name: float-3; }

        @keyframes float-1 { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(20vw, 15vh); } }
        @keyframes float-2 { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(-25vw, -10vh); } }
        @keyframes float-3 { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(15vw, -20vh); } }

        /* Utilities */
        .container {
            max-width: 1200px;
            margin: auto;
            padding: 0 2rem;
        }

        .gradient-text {
            background: linear-gradient(45deg, var(--color-gradient-1), var(--color-gradient-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn {
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: bold;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-light);
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--color-gradient-1), var(--color-gradient-2));
            color: var(--color-text-light);
            border: none;
            box-shadow: var(--shadow-light);
        }

        .btn-outline {
            background: transparent;
            color: var(--color-text-light);
            border: 2px solid var(--color-gradient-1);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        .btn-outline::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, var(--color-gradient-1), var(--color-gradient-2));
            transition: left 0.3s ease;
            z-index: -1;
        }
        .btn-outline:hover::before {
            left: 0;
        }

        /* Navigation Bar */
        .main-nav {
            padding: 1.5rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 1000;
            background-color: rgba(18, 18, 18, 0.8);
            backdrop-filter: blur(10px);
            transition: background-color 0.3s ease;
        }
        .main-nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .main-nav .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .main-nav .logo-icon {
            font-size: 1.5rem;
            color: var(--color-primary);
        }
        .main-nav .logo-text {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .main-nav .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .main-nav .nav-links a {
            color: var(--color-text-light);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .main-nav .nav-links a:hover {
            color: var(--color-secondary);
        }
        .main-nav .nav-toggle {
            display: none;
            font-size: 2rem;
            background: none;
            border: none;
            color: var(--color-text-light);
            cursor: pointer;
        }

        /* Hero Section */
        .hero {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
            padding-top: 6rem;
            position: relative;
            overflow: hidden;
        }
        .hero-content {
            z-index: 1;
            padding: 0 1rem;
        }
        .hero-title {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        .hero-subtitle {
            font-size: 1.25rem;
            max-width: 600px;
            margin: 0 auto 2rem;
            opacity: 0.8;
        }
        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
        }
        .hero-image {
            display: none; /* Hide on smaller screens */
        }
        
        .hero-illustration {
            position: absolute;
            top: 50%;
            right: 15%;
            transform: translateY(-50%);
            width: 350px;
            height: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-card);
        }
        .hero-card {
            background-color: var(--color-card-bg);
            padding: 1.5rem 2rem;
            border-radius: 20px;
            text-align: center;
            position: relative;
            z-index: 2;
            box-shadow: var(--shadow-card);
            animation: pulse 3s infinite ease-in-out;
        }
        .card-points {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--color-secondary);
            margin-bottom: 0.5rem;
        }
        .card-title {
            font-size: 1.1rem;
            font-weight: 500;
        }
        .card-checkmark {
            position: absolute;
            bottom: -20px;
            right: -20px;
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, var(--color-gradient-1), var(--color-gradient-2));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            border: 5px solid var(--color-bg-dark);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Particles within hero illustration */
        .particle {
            position: absolute;
            background: var(--color-secondary);
            border-radius: 50%;
            opacity: 0;
            animation: particle-float 15s infinite ease-in-out;
        }
        .particle-1 { width: 15px; height: 15px; top: 10%; left: 20%; animation-delay: 0s; }
        .particle-2 { width: 10px; height: 10px; top: 80%; left: 70%; animation-delay: 5s; }
        .particle-3 { width: 20px; height: 20px; top: 40%; left: 40%; animation-delay: 10s; }

        @keyframes particle-float {
            0%, 100% { transform: translate(0, 0) scale(0.5); opacity: 0; }
            50% { transform: translate(10px, -10px) scale(1); opacity: 0.7; }
        }

        /* Sections */
        section {
            padding: 6rem 0;
        }
        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .section-header h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .section-header p {
            font-size: 1.1rem;
            max-width: 700px;
            margin: auto;
            opacity: 0.8;
        }

        /* Features Section */
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        .feature-card {
            background-color: var(--color-card-bg);
            padding: 2.5rem;
            border-radius: 20px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid var(--color-border);
            position: relative;
            overflow: hidden;
        }
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, var(--color-gradient-1), var(--color-gradient-2));
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-card);
        }
        .feature-card:hover::before {
            opacity: 0.2;
        }
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(45deg, var(--color-gradient-1), var(--color-gradient-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
        }
        .feature-card p {
            opacity: 0.7;
        }

        /* How It Works Section */
        .steps-container {
            display: flex;
            flex-direction: column;
            gap: 3rem;
            align-items: center;
            position: relative;
        }
        .steps-container::before {
            content: '';
            position: absolute;
            width: 2px;
            height: 100%;
            background-color: var(--color-border);
            left: 50%;
            transform: translateX(-50%);
        }
        .step {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            max-width: 800px;
        }
        .step:nth-child(even) {
            flex-direction: row-reverse;
        }
        .step-number {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--color-gradient-1), var(--color-gradient-2));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            flex-shrink: 0;
            border: 5px solid var(--color-bg-dark);
            z-index: 1;
        }
        .step-content {
            background-color: var(--color-card-bg);
            padding: 1.5rem;
            border-radius: 15px;
            border: 1px solid var(--color-border);
            flex-grow: 1;
        }
        .step-content h3 {
            margin-bottom: 0.5rem;
        }

        /* Testimonials Section */
        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        .testimonial-card {
            background-color: var(--color-card-bg);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--color-border);
            position: relative;
            z-index: 1;
        }
        .testimonial-quote {
            position: absolute;
            top: 1rem;
            left: 1rem;
            font-size: 3rem;
            color: var(--color-gradient-1);
            opacity: 0.2;
        }
        .testimonial-card p {
            margin-bottom: 1.5rem;
            font-style: italic;
            opacity: 0.9;
        }
        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .author-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--color-gradient-1), var(--color-gradient-2));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .author-info h4 {
            font-size: 1.2rem;
        }
        .author-info p {
            font-size: 0.9rem;
            opacity: 0.6;
            margin-bottom: 0;
            font-style: normal;
        }

        /* Call to Action Section */
        .cta {
            background: linear-gradient(45deg, var(--color-bg-dark), rgba(18, 18, 18, 0.9)), 
                        url('https://example.com/cta-bg.jpg') no-repeat center center/cover; /* Placeholder URL */
            padding: 8rem 2rem;
            text-align: center;
        }
        .cta-content h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .cta-content p {
            font-size: 1.2rem;
            max-width: 600px;
            margin: auto;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        /* Footer */
        .footer {
            background-color: var(--color-card-bg);
            padding: 2rem 0;
            text-align: center;
            border-top: 1px solid var(--color-border);
        }
        .social-links {
            margin-bottom: 1rem;
            display: flex;
            justify-content: center;
            gap: 1.5rem;
        }
        .social-links a {
            color: var(--color-text-light);
            font-size: 1.5rem;
            transition: color 0.3s ease;
        }
        .social-links a:hover {
            color: var(--color-secondary);
        }
        .footer p {
            font-size: 0.9rem;
            opacity: 0.6;
        }

        /* Mobile Responsiveness */
        @media (max-width: 1024px) {
            .hero-title { font-size: 3rem; }
            .hero-illustration { display: none; }
        }

        @media (max-width: 768px) {
            .main-nav .nav-links {
                position: fixed;
                top: 0;
                right: -100%;
                width: 70%;
                height: 100%;
                background-color: rgba(18, 18, 18, 0.95);
                backdrop-filter: blur(20px);
                flex-direction: column;
                justify-content: center;
                gap: 2.5rem;
                transition: right 0.5s ease-in-out;
            }
            .main-nav .nav-links.active {
                right: 0;
            }
            .main-nav .nav-toggle { display: block; }
            .main-nav .container { padding: 0 1rem; }
            .hero { text-align: center; }
            .hero-buttons { flex-direction: column; }
            .hero-title { font-size: 2.5rem; }
            .hero-subtitle { font-size: 1rem; }
            .feature-card { padding: 2rem; }
            .section-header h2 { font-size: 2rem; }
            .step { flex-direction: column; text-align: center; }
            .step:nth-child(even) { flex-direction: column; }
            .steps-container::before { left: 50%; transform: translateX(-50%); height: calc(100% - 100px); top: 50px; }
            .testimonial-card { text-align: center; }
            .testimonial-author { justify-content: center; }
            .testimonial-quote { top: 1rem; left: 50%; transform: translateX(-50%); }
            .cta-content h2 { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <div class="floating floating-1"></div>
    <div class="floating floating-2"></div>
    <div class="floating floating-3"></div>
    
    <nav class="main-nav">
        <div class="container">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="logo-text">Task to Earn</div>
            </div>
            
            <button class="nav-toggle" id="nav-toggle" aria-label="Toggle navigation menu">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="nav-links" id="nav-links">
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
                    <a href="logout.php" class="btn btn-primary">
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
    
    <section class="features" id="features">
        <div class="container">
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
        </div>
    </section>
    
    <section class="how-it-works" id="how-it-works">
        <div class="container">
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
        </div>
    </section>
    
    <section class="testimonials" id="testimonials">
        <div class="container">
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
        </div>
    </section>
    
    <section class="cta">
        <div class="container cta-content">
            <h2>Ready to Transform Your Family's Routine?</h2>
            <p>Join thousands of families who've made chores fun and rewarding</p>
            <a href="login.php" class="btn btn-primary btn-large">
                <i class="fas fa-rocket"></i> Start Now
            </a>
        </div>
    </section>
    
    <footer class="footer">
        <div class="container">
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
        document.addEventListener('DOMContentLoaded', function() {
            const navToggle = document.getElementById('nav-toggle');
            const navLinks = document.getElementById('nav-links');

            navToggle.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
            
            navLinks.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    if (navLinks.classList.contains('active')) {
                        navLinks.classList.remove('active');
                    }
                });
            });
        });
    </script>
</body>
</html>