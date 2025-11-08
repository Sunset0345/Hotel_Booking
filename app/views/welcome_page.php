<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to Hotel Booking System</title>
    <link rel="shortcut icon" href="data:image/x-icon;," type="image/x-icon">
    <style>
           html { scroll-behavior: smooth; scroll-padding-top: 96px; scroll-snap-type: y mandatory; }
        .hero {
            /* Fixed full-page background behind the content */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* Hotlinked image from Kayak (as requested). Consider downloading and hosting locally if you have rights. */
            background-image: url('https://www.kayak.com.ph/rimg/himg/e1/5b/f8/expediav2-42996-f561b3-210999.jpg?width=2000&height=1200&crop=true');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            z-index: -1;
        }
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(3,37,65,0.45), rgba(3,37,65,0.6));
        }
        .hero-content {
            position: relative;
            text-align: left;
            z-index: 2;
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 1rem;
            color: #fff;
                .info-grid {
                    display: grid;
                    grid-template-columns: 1fr; /* one card per row so each fills viewport */
                    gap: 3rem;
                    margin-top: 1.5rem;
                }
                .info-card {
                    background: rgba(255,255,255,0.82);
                    color: #0f172a;
                    padding: 2.25rem;
                    min-height: calc(100vh - 140px); /* leave space for topbar and hero heading */
                    border-radius: 14px;
                    box-shadow: 0 18px 46px rgba(2,6,23,0.16);
                    transform: translateY(28px) scale(0.98);
                    opacity: 0;
                    animation: popUp 750ms cubic-bezier(.2,.9,.2,1) forwards;
                    scroll-margin-top: 96px; /* ensure anchor lands below fixed topbar */
                    scroll-snap-align: start; /* snap this card to top of viewport when scrolling */
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                }
            margin-top: 1.75rem;
        }
        .info-card {
            background: rgba(255,255,255,0.82);
            color: #0f172a;
            padding: 2.25rem;
            min-height: 260px;
            border-radius: 14px;
            box-shadow: 0 18px 46px rgba(2,6,23,0.16);
            transform: translateY(28px) scale(0.98);
            opacity: 0;
            animation: popUp 750ms cubic-bezier(.2,.9,.2,1) forwards;
            scroll-margin-top: 96px; /* ensure anchor lands below fixed topbar */
        }
        .info-card h3 { margin-top: 0; }
        @keyframes popUp {
            from { transform: translateY(20px) scale(0.98); opacity: 0; }
            to   { transform: translateY(0) scale(1); opacity: 1; }
        }
    /* Staggered delays */
    .info-card:nth-child(1) { animation-delay: 200ms; }
    .info-card:nth-child(2) { animation-delay: 450ms; }
    .info-card:nth-child(3) { animation-delay: 700ms; }
    .info-card:nth-child(4) { animation-delay: 950ms; }
        .hero h1 {
            font-size: 3rem;
            margin: 0 0 0.5rem 0;
            line-height: 1.05;
        }
        .hero p.lead {
            font-size: 1.2rem;
            margin-bottom: 1.2rem;
            opacity: 0.95;
        }
        .hero-head {
            text-align: center;
            margin-bottom: 0.75rem;
        }
        .cta-group {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-primary {
            background: #ff7c38;
            color: #fff;
            padding: 0.75rem 1.2rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 700;
            box-shadow: 0 6px 18px rgba(255,124,56,0.25);
        }
        .btn-secondary {
            background: rgba(255,255,255,0.12);
            color: #fff;
            padding: 0.6rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.12);
        }
        @media (max-width: 900px) {
            .info-grid { grid-template-columns: 1fr; gap: 1rem; }
            .info-card { min-height: 180px; padding: 1.25rem; }
        }
        @media (max-width: 640px) {
            .hero h1 { font-size: 2rem; }
            .container { margin: 1rem; }
            .header { padding: 1rem; }
        }
        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(3,37,65,0.06);
            padding: 0.5rem 1rem;
            z-index: 1200;
            backdrop-filter: blur(6px);
        }
        .shortcuts {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .shortcut-link {
            color: #fff;
            text-decoration: none;
            padding: 0.45rem 0.65rem;
            border-radius: 6px;
            font-weight: 600;
            background: rgba(255,255,255,0.06);
        }
        .shortcut-link:hover { background: rgba(255,255,255,0.12); }
        .login-btn {
            background: #fff;
            color: #3B82F6;
            border: none;
            border-radius: 4px;
            padding: 0.5rem 1.2rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .login-btn:hover {
            background: #e0e7ef;
        }
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
            color: #334155;
            /* Keep hero visible behind content */
            background: #081a2b; /* fallback color while hero shows */
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            background: transparent;
            padding-top: 2rem;
        }

        .header {
            background: transparent;
            color: #fff;
            padding: 0.5rem 0 0 0;
            text-align: left;
        }

        .header h1 {
            margin: 0;
            font-size: 2.5rem;
        }

        .main {
            padding: 1.5rem 0 0 0;
            color: #fff;
        }

        h2 {
            color: #1e40af;
            margin-top: 2rem;
        }

        p {
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        code, pre {
            display: block;
            background: #f1f5f9;
            padding: 1rem;
            border-left: 4px solid #3b82f6;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #1e293b;
            overflow-x: auto;
        }

        ul {
            padding-left: 1.5rem;
            margin-bottom: 1rem;
        }

        li {
            margin-bottom: 0.5rem;
        }

        a {
            color: #2563eb;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .footer {
            font-size: 0.9rem;
            text-align: center;
            padding: 1rem;
            background: transparent;
            border-top: none;
            margin-top: 2rem;
            color: rgba(255,255,255,0.85);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
        }

        .card {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .card h3 {
            margin-top: 0;
            color: #0f172a;
        }
    </style>
</head>
<body>

    <div class="topbar">
        <div class="shortcuts">
            <a class="shortcut-link" href="#welcome-card">Welcome</a>
            <a class="shortcut-link" href="#about-card">About</a>
            <a class="shortcut-link" href="#contact-card">Contact</a>
            <a class="shortcut-link" href="#why-card">Why</a>
        </div>
    <a href="<?php echo site_url('auth/login'); ?>"><button class="login-btn">Login</button></a>
    </div>

    <div class="hero" role="region" aria-label="Hotel hero image">
        <div class="hero-overlay"></div>
    </div>

    <div class="container">
        <div class="hero-content">
            <div class="hero-head">
                <h1 style="margin:0 0 0.5rem 0;">Blue Lagoon Hotel</h1>
                <p class="lead">Relax by the sea. Luxurious rooms • Free breakfast • Prime location</p>
            </div>

            <!-- Welcome heading removed as requested -->

            <div class="info-grid">
                <div id="welcome-card" class="info-card">
                    <h3>Welcome</h3>
                    <p>Welcome to Blue Lagoon Hotel — a beachfront retreat offering exceptional comfort and service.</p>
                </div>
                <div id="about-card" class="info-card">
                    <h3>About Us</h3>
                    <p>We provide modern rooms, complimentary breakfast, and easy access to local attractions. Relax and recharge with us.</p>
                </div>
                <div id="contact-card" class="info-card">
                    <h3>Contact</h3>
                    <p>Address: 123 Ocean Avenue, Metro City<br>Phone: (123) 456-7890<br>Email: info@bluelagoonhotel.com</p>
                </div>
                <div id="why-card" class="info-card">
                    <h3>Why Choose Us?</h3>
                    <ul>
                        <li>Spacious and clean rooms</li>
                        <li>Free Wi-Fi and breakfast</li>
                        <li>24/7 front desk and security</li>
                        <li>Swimming pool, gym, and spa</li>
                        <li>Walking distance to attractions</li>
                    </ul>
                </div>
            </div>

            <div style="margin-top:1.25rem;">
                <a class="btn-primary" href="<?php echo site_url('rooms'); ?>">View Rooms</a>
            </div>

        </div>

        <div class="footer">
            Page rendered in <strong><?php echo lava_instance()->performance->elapsed_time('lavalust'); ?></strong> seconds.
            Memory usage: <?php echo lava_instance()->performance->memory_usage(); ?>.
        </div>
    </div>
</body>
</html>
