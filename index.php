<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnify - Gamified Task Management for Kids</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        :root {
            --color-primary: #8a2be2;
            --color-secondary: #00bcd4;
            --color-gradient-1: #8a2be2;
            --color-gradient-2: #00bcd4;
            --color-bg-dark: #121212;
            --color-text-light: #f0f0f0;
            --color-card-bg: #1e1e1e;
            --color-border: #333;
            --font-main: 'Inter', sans-serif;
            --shadow-light: 0 4px 15px rgba(0, 0, 0, 0.2);
            --shadow-card: 0 8px 30px rgba(0, 0, 0, 0.4);
        }
        
        * {
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        html, body {
            scroll-behavior: smooth;
            height: 100%;
            overflow-x: hidden;
        }
        
        body {
            font-family: var(--font-main);
            color: var(--color-text-light);
            background-color: var(--color-bg-dark);
            line-height: 1.6;
            position: relative;
            overflow-y: auto;
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Enhanced Background */
        .enhanced-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            overflow: hidden;
        }

        .grid-pattern {
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(18, 18, 18, 0.9) 1px, transparent 1px),
                linear-gradient(90deg, rgba(18, 18, 18, 0.9) 1px, transparent 1px);
            background-size: 40px 40px;
            mask-image: radial-gradient(ellipse at center, black 20%, transparent 70%);
            -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 70%);
        }

        .connection-line {
            position: absolute;
            background: linear-gradient(90deg, var(--color-gradient-1), var(--color-gradient-2));
            height: 1px;
            opacity: 0.1;
            transform-origin: left center;
            animation: linePulse 8s infinite ease-in-out;
        }

        .connection-dot {
            position: absolute;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--color-secondary);
            opacity: 0.3;
            animation: dotPulse 4s infinite ease-in-out alternate;
        }

        @keyframes linePulse {
            0%, 100% { opacity: 0.05; transform: scaleX(0.2); }
            50% { opacity: 0.15; transform: scaleX(1); }
        }

        @keyframes dotPulse {
            0% { opacity: 0.1; transform: scale(0.8); }
            100% { opacity: 0.4; transform: scale(1.2); }
        }

        .geometric-shape {
            position: absolute;
            border: 1px solid;
            opacity: 0.05;
            animation: shapeRotate 30s infinite linear;
        }

        .shape-triangle {
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0 40px 70px 40px;
            border-color: transparent transparent var(--color-primary) transparent;
        }

        .shape-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border-color: var(--color-secondary);
        }

        .shape-square {
            width: 60px;
            height: 60px;
            border-color: var(--color-primary);
        }

        @keyframes shapeRotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .micro-particle {
            position: absolute;
            background: var(--color-secondary);
            width: 2px;
            height: 2px;
            border-radius: 50%;
            opacity: 0;
            animation: microFloat 15s infinite linear;
        }

        @keyframes microFloat {
            0% { 
                transform: translateY(0) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 0.3;
            }
            90% {
                opacity: 0.3;
            }
            100% { 
                transform: translateY(-100px) translateX(50px);
                opacity: 0;
            }
        }

        .animated-bg {
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
            position: relative;
            min-height: 100vh;
        }
        
        .grid-pattern-original {
            background-image: 
                linear-gradient(rgba(102, 126, 234, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(102, 126, 234, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            animation: gridMove 20s linear infinite;
            z-index: -1;
        }
        
        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        
        .floating-circle {
            position: fixed;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
            animation: float 6s ease-in-out infinite;
            z-index: -1;
        }
        
        .floating-circle:nth-child(1) {
            width: 100px;
            height: 100px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .floating-circle:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .floating-circle:nth-child(3) {
            width: 80px;
            height: 80px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .particle {
            position: fixed;
            width: 4px;
            height: 4px;
            background: #667eea;
            border-radius: 50%;
            animation: particle-float 8s linear infinite;
            z-index: -1;
        }
        
        @keyframes particle-float {
            0% { transform: translateY(100vh) translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100px) translateX(100px); opacity: 0; }
        }
        
        .geometric-shape-original {
            position: fixed;
            animation: rotate 10s linear infinite;
            z-index: -1;
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .pulse-dot {
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
        }
        
        .nav-blur {
            backdrop-filter: blur(10px);
            background: rgba(15, 15, 35, 0.8);
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-10px);
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        }
        
        .timeline-line {
            position: relative;
        }
        
        .timeline-line::before {
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #667eea, #764ba2);
            transform: translateX(-50%);
        }
        
        .step-circle {
            background: linear-gradient(135deg, #667eea, #764ba2);
            position: relative;
            z-index: 10;
        }
        
        /* Floating Background Elements */
        .floating {
            position: fixed;
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
        
        /* Button Styles */
        .btn {
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: bold;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
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
        
        /* Content area - ensures scrolling works */
        .content-area {
            position: relative;
            z-index: 1;
            min-height: 100vh;
        }
        
        section {
            position: relative;
            z-index: 2;
        }
        
        @media (max-width: 768px) {
            .timeline-line::before {
                left: 20px;
            }
            
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
                z-index: 1000;
            }
            .main-nav .nav-links.active {
                right: 0;
            }
            .main-nav .nav-toggle { display: block; }
        }
    </style>
</head>
<body class="animated-bg text-white">
    <!-- Enhanced Background Elements -->
    <div class="enhanced-bg">
        <div class="grid-pattern"></div>
        
        <!-- Connection lines -->
        <div class="connection-line" style="top: 30%; left: 10%; width: 200px; animation-delay: 0s;"></div>
        <div class="connection-line" style="top: 60%; left: 60%; width: 150px; animation-delay: 2s;"></div>
        <div class="connection-line" style="top: 20%; left: 70%; width: 180px; animation-delay: 4s;"></div>
        <div class="connection-line" style="top: 80%; left: 20%; width: 220px; animation-delay: 6s;"></div>
        
        <!-- Connection dots -->
        <div class="connection-dot" style="top: 30%; left: 10%; animation-delay: 0s;"></div>
        <div class="connection-dot" style="top: 30%; left: 210px; animation-delay: 1s;"></div>
        <div class="connection-dot" style="top: 60%; left: 60%; animation-delay: 2s;"></div>
        <div class="connection-dot" style="top: 60%; left: calc(60% + 150px); animation-delay: 3s;"></div>
        
        <!-- Geometric shapes -->
        <div class="geometric-shape shape-triangle" style="top: 15%; left: 15%; animation-delay: 0s;"></div>
        <div class="geometric-shape shape-circle" style="top: 75%; left: 80%; animation-delay: 5s;"></div>
        <div class="geometric-shape shape-square" style="top: 10%; left: 85%; animation-delay: 10s;"></div>
        <div class="geometric-shape shape-triangle" style="top: 85%; left: 10%; animation-delay: 15s; transform: rotate(180deg);"></div>
        
        <!-- Micro particles -->
        <div class="micro-particle" style="top: 20%; left: 20%; animation-delay: 0s;"></div>
        <div class="micro-particle" style="top: 40%; left: 40%; animation-delay: 2s;"></div>
        <div class="micro-particle" style="top: 60%; left: 60%; animation-delay: 4s;"></div>
        <div class="micro-particle" style="top: 80%; left: 80%; animation-delay: 6s;"></div>
        <div class="micro-particle" style="top: 30%; left: 70%; animation-delay: 1s;"></div>
        <div class="micro-particle" style="top: 70%; left: 30%; animation-delay: 3s;"></div>
    </div>

    <div class="floating floating-1"></div>
    <div class="floating floating-2"></div>
    <div class="floating floating-3"></div>
    
    <!-- Original Background Elements -->
    <div class="grid-pattern-original"></div>
    <div class="floating-circle"></div>
    <div class="floating-circle"></div>
    <div class="floating-circle"></div>
    
    <!-- Particles -->
    <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
    <div class="particle" style="left: 20%; animation-delay: 2s;"></div>
    <div class="particle" style="left: 30%; animation-delay: 4s;"></div>
    <div class="particle" style="left: 40%; animation-delay: 6s;"></div>
    <div class="particle" style="left: 50%; animation-delay: 1s;"></div>
    <div class="particle" style="left: 60%; animation-delay: 3s;"></div>
    <div class="particle" style="left: 70%; animation-delay: 5s;"></div>
    <div class="particle" style="left: 80%; animation-delay: 7s;"></div>
    
    <!-- Geometric Shapes -->
    <div class="geometric-shape-original" style="top: 15%; right: 15%; width: 30px; height: 30px; border: 2px solid #667eea; transform-origin: center;"></div>
    <div class="geometric-shape-original" style="bottom: 25%; left: 15%; width: 0; height: 0; border-left: 15px solid transparent; border-right: 15px solid transparent; border-bottom: 25px solid #764ba2; animation-duration: 8s;"></div>
    <div class="geometric-shape-original" style="top: 40%; right: 25%; width: 25px; height: 25px; background: #667eea; animation-duration: 12s;"></div>

    <!-- Main Content Area (Scrollable) -->
    <div class="content-area">
        <!-- Navigation -->
        <nav class="main-nav fixed top-0 left-0 right-0 z-50 nav-blur border-b border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <div class="text-2xl font-bold gradient-text">Earnify</div>
                    </div>
                    
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-8">
                            <a href="#home" class="hover:text-purple-400 transition-colors">Home</a>
                            <a href="#features" class="hover:text-purple-400 transition-colors">Features</a>
                            <a href="#how-it-works" class="hover:text-purple-400 transition-colors">How It Works</a>
                            <a href="#testimonials" class="hover:text-purple-400 transition-colors">Review</a>
                        </div>
                    </div>
                    
                    <div class="hidden md:flex items-center space-x-4">
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
                            <a href="login.php" class="px-4 py-2 text-sm font-medium text-white hover:text-purple-400 transition-colors">Login</a>
                            <a href="register.php" class="px-6 py-2 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all">Sign Up</a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="md:hidden">
                        <button id="mobile-menu-btn" class="text-white hover:text-purple-400">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden md:hidden bg-gray-900 bg-opacity-95">
                <div class="px-2 pt-2 pb-3 space-y-1">
                    <a href="#home" class="block px-3 py-2 text-white hover:text-purple-400">Home</a>
                    <a href="#features" class="block px-3 py-2 text-white hover:text-purple-400">Features</a>
                    <a href="#how-it-works" class="block px-3 py-2 text-white hover:text-purple-400">How It Works</a>
                    <a href="#testimonials" class="block px-3 py-2 text-white hover:text-purple-400">Reviews</a>
                    <div class="px-3 py-2 space-y-2">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php
                            $dashboardLink = ($_SESSION['role'] == 'admin') ? 'dashboard_admin.php' : 'dashboard_child.php';
                            ?>
                            <a href="<?= $dashboardLink ?>" class="block w-full text-left text-white hover:text-purple-400">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                            <a href="logout.php" class="block w-full px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="block w-full text-left text-white hover:text-purple-400">Login</a>
                            <a href="register.php" class="block w-full px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg">Sign Up</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section id="home" class="relative min-h-screen flex items-center justify-center pt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div class="mb-8">
                    <h1 class="text-5xl md:text-7xl font-bold mb-6">
                        Make Tasks <span class="gradient-text">Fun</span> for Kids
                    </h1>
                    <p class="text-xl md:text-2xl text-gray-300 mb-8 max-w-3xl mx-auto">
                        Transform daily chores into exciting quests with our gamified task management platform designed specifically for children.
                    </p>
                </div>
                
                <!-- Task Completion Card -->
                <div class="bg-gray-800 bg-opacity-50 backdrop-blur-sm rounded-2xl p-6 max-w-md mx-auto mb-8 border border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Today's Progress</h3>
                        <div class="pulse-dot w-3 h-3 bg-green-400 rounded-full"></div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <div class="flex justify-between text-sm mb-2">
                                <span>Tasks Completed</span>
                                <span>7/10</span>
                            </div>
                            <div class="w-full bg-gray-700 rounded-full h-2">
                                <div class="bg-gradient-to-r from-purple-600 to-blue-600 h-2 rounded-full" style="width: 70%"></div>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold gradient-text">350</div>
                            <div class="text-xs text-gray-400">Points</div>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php
                        $dashboardLink = ($_SESSION['role'] == 'admin') ? 'dashboard_admin.php' : 'dashboard_child.php';
                        ?>
                        <a href="<?= $dashboardLink ?>" class="px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-purple-600 to-blue-600 rounded-xl hover:from-purple-700 hover:to-blue-700 transition-all transform hover:scale-105">
                            Go to Dashboard
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-purple-600 to-blue-600 rounded-xl hover:from-purple-700 hover:to-blue-700 transition-all transform hover:scale-105">
                            Start Your Quest
                        </a>
                    <?php endif; ?>
                    <a href="#features" class="px-8 py-4 text-lg font-semibold text-white border-2 border-purple-600 rounded-xl hover:bg-purple-600 transition-all">
                        Watch Demo
                    </a>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-20 relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-bold mb-6">
                        Why Kids <span class="gradient-text">Love</span> Earnify
                    </h2>
                    <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                        Our platform combines the excitement of gaming with the satisfaction of completing real-world tasks.
                    </p>
                </div>
                
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="card-hover bg-gray-800 bg-opacity-50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700">
                        <div class="w-16 h-16 bg-gradient-to-r from-purple-600 to-blue-600 rounded-xl flex items-center justify-center mb-4">
                            <i class="fas fa-gamepad text-2xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-3">Gamified Experience</h3>
                        <p class="text-gray-300">Turn everyday tasks into exciting adventures with points, levels, and achievements.</p>
                    </div>
                    
                    <div class="card-hover bg-gray-800 bg-opacity-50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700">
                        <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-teal-500 rounded-xl flex items-center justify-center mb-4">
                            <i class="fas fa-gift text-2xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-3">Exciting Rewards</h3>
                        <p class="text-gray-300">Earn virtual rewards and unlock special privileges for completing tasks consistently.</p>
                    </div>
                    
                    <div class="card-hover bg-gray-800 bg-opacity-50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700">
                        <div class="w-16 h-16 bg-gradient-to-r from-orange-500 to-red-500 rounded-xl flex items-center justify-center mb-4">
                            <i class="fas fa-chart-line text-2xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-3">Progress Tracking</h3>
                        <p class="text-gray-300">Visual progress bars and statistics help kids see their improvement over time.</p>
                    </div>
                    
                    <div class="card-hover bg-gray-800 bg-opacity-50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700">
                        <div class="w-16 h-16 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl flex items-center justify-center mb-4">
                            <i class="fas fa-shield-alt text-2xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-3">Parental Control</h3>
                        <p class="text-gray-300">Parents can monitor progress, set tasks, and manage rewards from their dashboard.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section id="how-it-works" class="py-20 relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-bold mb-6">
                        How It <span class="gradient-text">Works</span>
                    </h2>
                    <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                        Getting started is simple! Follow these easy steps to begin your family's task management journey.
                    </p>
                </div>
                
                <div class="timeline-line relative">
                    <div class="grid md:grid-cols-3 gap-8 md:gap-12">
                        <div class="text-center md:text-left">
                            <div class="step-circle w-16 h-16 rounded-full flex items-center justify-center text-xl font-bold text-white mx-auto md:mx-0 mb-4">1</div>
                            <h3 class="text-xl font-semibold mb-3">Create Account</h3>
                            <p class="text-gray-300">Sign up for free and set up profiles for parents and children with age-appropriate settings.</p>
                        </div>
                        
                        <div class="text-center">
                            <div class="step-circle w-16 h-16 rounded-full flex items-center justify-center text-xl font-bold text-white mx-auto mb-4">2</div>
                            <h3 class="text-xl font-semibold mb-3">Add Tasks</h3>
                            <p class="text-gray-300">Parents create fun, engaging tasks with point values and deadlines that motivate kids.</p>
                        </div>
                        
                        <div class="text-center md:text-right">
                            <div class="step-circle w-16 h-16 rounded-full flex items-center justify-center text-xl font-bold text-white mx-auto md:mx-0 mb-4">3</div>
                            <h3 class="text-xl font-semibold mb-3">Earn Rewards</h3>
                            <p class="text-gray-300">Kids complete tasks, earn points, unlock achievements, and redeem exciting rewards.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section id="testimonials" class="py-20 relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-bold mb-6">
                        What Families <span class="gradient-text">Say</span>
                    </h2>
                    <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                        Discover how Earnify has transformed daily routines for families around the world.
                    </p>
                </div>
                
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="bg-gray-800 bg-opacity-50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700">
                        <div class="flex items-center mb-4">
                            <i class="fas fa-quote-left text-2xl text-purple-400 mr-3"></i>
                            <div class="flex text-yellow-400">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <p class="text-gray-300 mb-6">"My kids actually ask to do their chores now! The point system and rewards have completely changed our household dynamics."</p>
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-purple-600 to-blue-600 rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-semibold">SM</span>
                            </div>
                            <div>
                                <div class="font-semibold">Sarah Mitchell</div>
                                <div class="text-sm text-gray-400">Mother of 3</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-800 bg-opacity-50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700">
                        <div class="flex items-center mb-4">
                            <i class="fas fa-quote-left text-2xl text-purple-400 mr-3"></i>
                            <div class="flex text-yellow-400">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <p class="text-gray-300 mb-6">"The progress tracking helps me see how my children are developing responsibility. It's like a game they never want to stop playing!"</p>
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-teal-500 rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-semibold">DJ</span>
                            </div>
                            <div>
                                <div class="font-semibold">David Johnson</div>
                                <div class="text-sm text-gray-400">Father of 2</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-800 bg-opacity-50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700">
                        <div class="flex items-center mb-4">
                            <i class="fas fa-quote-left text-2xl text-purple-400 mr-3"></i>
                            <div class="flex text-yellow-400">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <p class="text-gray-300 mb-6">"Earnify has made our family more organized and our kids more motivated. The parental controls give me peace of mind."</p>
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-pink-500 to-purple-500 rounded-full flex items-center justify-center mr-3">
                                <span class="text-white font-semibold">ER</span>
                            </div>
                            <div>
                                <div class="font-semibold">Emily Rodriguez</div>
                                <div class="text-sm text-gray-400">Single Mom</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call to Action Section -->
        <section class="py-20 relative">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-3xl p-12">
                    <h2 class="text-4xl md:text-5xl font-bold mb-6 text-white">
                        Ready to Transform Your Family's Routine?
                    </h2>
                    <p class="text-xl text-purple-100 mb-8 max-w-2xl mx-auto">
                        Join thousands of families who have already discovered the magic of gamified task management. Start your free trial today!
                    </p>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php
                        $dashboardLink = ($_SESSION['role'] == 'admin') ? 'dashboard_admin.php' : 'dashboard_child.php';
                        ?>
                        <a href="<?= $dashboardLink ?>" class="px-10 py-4 text-lg font-semibold text-purple-600 bg-white rounded-xl hover:bg-gray-100 transition-all transform hover:scale-105">
                            Go to Dashboard
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="px-10 py-4 text-lg font-semibold text-purple-600 bg-white rounded-xl hover:bg-gray-100 transition-all transform hover:scale-105">
                            Start Free Trial
                        </a>
                    <?php endif; ?>
                    <p class="text-sm text-purple-200 mt-4">No credit card required • 14-day free trial</p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-12 border-t border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="mb-4 md:mb-0">
                        <div class="text-2xl font-bold gradient-text mb-2">Earnify</div>
                        <p class="text-gray-400">Making tasks fun for kids everywhere</p>
                    </div>
                    
                    <div class="flex space-x-6">
                        <a href="#" class="text-gray-400 hover:text-purple-400 transition-colors">
                            <i class="fab fa-facebook-f text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-purple-400 transition-colors">
                            <i class="fab fa-twitter text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-purple-400 transition-colors">
                            <i class="fab fa-instagram text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-purple-400 transition-colors">
                            <i class="fab fa-linkedin-in text-xl"></i>
                        </a>
                    </div>
                </div>
                
                <div class="mt-8 pt-8 border-t border-gray-800 text-center text-gray-400">
                    <p>&copy; 2024 Earnify. All rights reserved. Made with ❤️ for families.</p>
                </div>
            </div>
        </footer>
    </div> <!-- End of content-area -->

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
        
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    // Close mobile menu if open
                    mobileMenu.classList.add('hidden');
                }
            });
        });
        
        // Add scroll effect to navigation
        window.addEventListener('scroll', () => {
            const nav = document.querySelector('nav');
            if (window.scrollY > 100) {
                nav.classList.add('bg-opacity-95');
            } else {
                nav.classList.remove('bg-opacity-95');
            }
        });
        
        // Initialize particles with random positions and delays
        function initializeParticles() {
            const particles = document.querySelectorAll('.particle');
            particles.forEach((particle, index) => {
                particle.style.animationDelay = `${Math.random() * 8}s`;
                particle.style.left = `${Math.random() * 100}%`;
            });
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            initializeParticles();
            
            // Generate additional micro particles dynamically
            const enhancedBg = document.querySelector('.enhanced-bg');
            
            // Add more micro particles
            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.className = 'micro-particle';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 10 + 's';
                particle.style.animationDuration = (10 + Math.random() * 20) + 's';
                enhancedBg.appendChild(particle);
            }
        });
        
        // Add intersection observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Observe all cards and sections
        document.querySelectorAll('.card-hover, section').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>