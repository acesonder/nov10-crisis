<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crisis Management System - Welcome</title>
    <link rel="stylesheet" href="/nov10-crisis/assets/css/main.css">
    <style>
        .hero {
            background: linear-gradient(135deg, #4a90e2, #7b68ee);
            color: white;
            padding: 4rem 0;
            text-align: center;
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 1s ease;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            animation: slideUp 0.8s ease;
            color: white;
        }
        
        .hero p {
            font-size: 1.25rem;
            max-width: 800px;
            margin: 0 auto 2rem;
            animation: slideUp 1s ease;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: slideUp 1.2s ease;
        }
        
        .features {
            padding: 4rem 0;
            background: var(--bg-secondary);
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }
        
        .feature-card {
            background: var(--bg-primary);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            text-align: center;
            transition: transform var(--transition-normal), box-shadow var(--transition-normal);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }
        
        .feature-card h3 {
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .stats {
            padding: 3rem 0;
            background: linear-gradient(135deg, #4a90e2, #7b68ee);
            color: white;
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .stat-item {
            padding: 1.5rem;
        }
        
        .stat-value {
            font-size: 3rem;
            font-weight: bold;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="hero">
        <div class="container">
            <h1>Crisis Management System</h1>
            <p>Comprehensive support and resource management for individuals in crisis. Connect with services, track progress, and find the help you need - all in one place.</p>
            <div class="hero-buttons">
                <a href="/nov10-crisis/pages/register.php" class="btn btn-light btn-lg">Get Started</a>
                <a href="/nov10-crisis/pages/login.php" class="btn btn-secondary btn-lg">Sign In</a>
                <a href="/nov10-crisis/pages/admin/login.php" class="btn btn-dark btn-lg">Admin Access</a>
            </div>
        </div>
    </div>
    
    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2 class="text-center">How We Can Help</h2>
            <p class="text-center text-secondary">Our comprehensive system provides support across multiple areas</p>
            
            <div class="features-grid">
                <div class="feature-card fade-in">
                    <span class="feature-icon">📋</span>
                    <h3>Smart Assessments</h3>
                    <p>Comprehensive intake assessments for substance use, homelessness, and mental health. All questions are optional and tailored to your needs.</p>
                </div>
                
                <div class="feature-card fade-in">
                    <span class="feature-icon">🤝</span>
                    <h3>Service Referrals</h3>
                    <p>Automatic matching with service providers based on your assessment. Connect with housing, healthcare, job training, and more.</p>
                </div>
                
                <div class="feature-card fade-in">
                    <span class="feature-icon">✅</span>
                    <h3>Goal Tracking</h3>
                    <p>Set and track personal goals with support from case managers. Monitor your progress and celebrate achievements.</p>
                </div>
                
                <div class="feature-card fade-in">
                    <span class="feature-icon">💬</span>
                    <h3>Secure Messaging</h3>
                    <p>Communicate securely with staff, case managers, and service providers. Real-time notifications keep you informed.</p>
                </div>
                
                <div class="feature-card fade-in">
                    <span class="feature-icon">🛏️</span>
                    <h3>Resource Management</h3>
                    <p>Book beds, schedule showers, reserve laundry times. Manage essential resources all in one place.</p>
                </div>
                
                <div class="feature-card fade-in">
                    <span class="feature-icon">📱</span>
                    <h3>Mobile Friendly</h3>
                    <p>Access services from any device. Our responsive design works seamlessly on desktop, tablet, and mobile.</p>
                </div>
                
                <div class="feature-card fade-in">
                    <span class="feature-icon">📄</span>
                    <h3>Document Sharing</h3>
                    <p>Securely store and share important documents with your case manager and service providers.</p>
                </div>
                
                <div class="feature-card fade-in">
                    <span class="feature-icon">🎨</span>
                    <h3>Customizable Interface</h3>
                    <p>Choose your preferred theme and color scheme. Personalize your experience for comfort and accessibility.</p>
                </div>
                
                <div class="feature-card fade-in">
                    <span class="feature-icon">⚡</span>
                    <h3>Real-Time Updates</h3>
                    <p>Stay informed with instant notifications about appointments, messages, and important updates.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <h2>Making a Difference</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-value">1,500+</span>
                    <span class="stat-label">Clients Served</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">50+</span>
                    <span class="stat-label">Service Partners</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">5,000+</span>
                    <span class="stat-label">Goals Achieved</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">24/7</span>
                    <span class="stat-label">Support Available</span>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Quick Access Section -->
    <section class="features">
        <div class="container">
            <h2 class="text-center">Quick Access</h2>
            <div class="row mt-4">
                <div class="col-12 col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3>For Clients</h3>
                            <p>Access assessments, track goals, connect with services, and manage your resources.</p>
                            <a href="/nov10-crisis/pages/register.php" class="btn btn-primary">Register Now</a>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3>For Staff</h3>
                            <p>Manage cases, coordinate services, track incidents, and support client success.</p>
                            <a href="/nov10-crisis/pages/login.php" class="btn btn-primary">Staff Login</a>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3>For Service Providers</h3>
                            <p>Receive referrals, manage capacity, communicate with clients and case managers.</p>
                            <a href="/nov10-crisis/pages/login.php" class="btn btn-primary">Provider Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer style="background: var(--dark-color); color: white; padding: 2rem 0; text-align: center; margin-top: 3rem;">
        <div class="container">
            <p>&copy; 2024 Crisis Management System. All rights reserved.</p>
            <p style="opacity: 0.7; margin-top: 0.5rem;">Helping communities support those in need.</p>
        </div>
    </footer>
    
    <script src="/nov10-crisis/assets/js/main.js"></script>
</body>
</html>
