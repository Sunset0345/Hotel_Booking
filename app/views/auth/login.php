<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Login - Blue Lagoon Hotel</title>
<style>
    html { scroll-behavior: smooth; }
    body{ margin:0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", sans-serif; color:#fff; background:#081a2b; }
    .hero { position: fixed; inset:0; background-image: url('https://www.kayak.com.ph/rimg/himg/e1/5b/f8/expediav2-42996-f561b3-210999.jpg?width=2000&height=1200&crop=true'); background-size:cover; background-position:center; z-index:-1; }
    .hero-overlay{ position: absolute; inset:0; background: linear-gradient(180deg, rgba(3,37,65,0.45), rgba(3,37,65,0.6)); }
    .topbar{ position:fixed; top:0; left:0; right:0; display:flex; justify-content:flex-end; padding:0.6rem 1rem; z-index:20; }
    .login-btn{ background:#fff; color:#036; border:0; padding:0.5rem 1rem; border-radius:6px; font-weight:700; }
    .card{ max-width:420px; margin:8vh auto; background: rgba(255,255,255,0.88); color:#0f172a; padding:1.75rem; border-radius:12px; box-shadow:0 18px 46px rgba(2,6,23,0.14); }
    h2{ margin:0 0 0.5rem 0; }
    label{ display:block; margin-top:0.75rem; font-weight:600; }
    input[type=email], input[type=password], input[type=text]{ width:100%; padding:0.6rem; margin-top:0.25rem; border-radius:6px; border:1px solid #e6e6e6; }
    button[type=submit]{ margin-top:1rem; background:#ff7c38; color:#fff; padding:0.7rem 1rem; border:0; border-radius:8px; font-weight:700; width:100%; }
    .small{ font-size:0.9rem; margin-top:0.75rem; }
    a { color:#036; }
</style>
</head>
<body>
<div class="hero"><div class="hero-overlay"></div></div>
<div class="topbar"><a href="<?= site_url('/') ?>"><button class="login-btn">Home</button></a></div>
<div class="card" role="main" aria-labelledby="login-title">
    <h2 id="login-title">Login</h2>
    <?php if(isset($error)): ?>
        <div role="alert" style="color:#b91c1c;background:rgba(255,255,255,0.85);padding:0.5rem;border-radius:6px;margin-bottom:0.75rem"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="<?= site_url('auth/login') ?>" novalidate>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" autocomplete="username" required />
        <label for="password">Password</label>
        <input id="password" type="password" name="password" autocomplete="current-password" required />
        <button type="submit">Login</button>
    </form>
    <div style="margin-top:1rem; text-align:center;">
        <p class="small">Or continue with</p>
        <div style="display:flex; gap:0.5rem; justify-content:center; margin-top:0.5rem;">
            <a href="<?= site_url('auth/oauth/google') ?>" style="display:inline-block;background:#db4437;color:#fff;padding:0.5rem 0.9rem;border-radius:8px;text-decoration:none;font-weight:700;">Google</a>
            <a href="<?= site_url('auth/oauth/facebook') ?>" style="display:inline-block;background:#1877f2;color:#fff;padding:0.5rem 0.9rem;border-radius:8px;text-decoration:none;font-weight:700;">Facebook</a>
        </div>
    </div>
    <div class="small">Don't have an account? <a href="<?= site_url('auth/register') ?>">Sign up now</a></div>
</div>
</body>
</html>
