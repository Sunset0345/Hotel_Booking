<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login - Blue Lagoon Hotel</title>
    <style>
        body{margin:0;font-family:Inter, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;background:#071021;color:#fff}
        .wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:32px}
        .form-card{width:420px;background:linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.02));padding:28px;border-radius:12px;box-shadow:0 6px 20px rgba(0,0,0,0.5)}
        .form-card h2{margin:0 0 6px}
        .form-card p{margin:0 0 12px;color:rgba(255,255,255,0.8)}
        .field{margin-bottom:10px}
        input[type="email"], input[type="password"]{width:100%;padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,0.06);background:rgba(255,255,255,0.02);color:#fff}
        .btn{background:#2995ff;padding:10px 12px;border-radius:8px;color:#fff;border:none;cursor:pointer}
        .error{color:#ff8a8a;margin-bottom:10px}
        .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
        a.link{color:#9fbff0;text-decoration:none}
    </style>
</head>
<body>
    <div class="wrap">
        <div class="form-card">
            <div class="top">
                <div>
                    <h2>Admin Login</h2>
                    <p>Sign in to access admin dashboard</p>
                </div>
                <div>
                    <a class="link" href="<?php echo site_url(''); ?>">Home</a>
                </div>
            </div>

            <?php if(!empty($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if(!empty($debug) && is_array($debug)): ?>
                <div style="margin:10px 0;padding:10px;background:rgba(255,255,255,0.03);border-radius:8px;color:#cfe"> 
                    <strong>Debug:</strong>
                    <div>admin_found: <?php echo $debug['admin_found'] ? 'true' : 'false'; ?></div>
                    <div>stored_hash (truncated): <?php echo htmlspecialchars($debug['stored_hash']); ?></div>
                    <div>stored_hash_length: <?php echo intval($debug['password_length']); ?></div>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo site_url('admin/login'); ?>">
                <div class="field">
                    <input type="email" name="email" placeholder="admin@admin.admin" required />
                </div>
                <div class="field">
                    <input type="password" name="password" placeholder="Password" required />
                </div>
                <div style="display:flex;gap:8px;align-items:center">
                    <button class="btn">Sign in</button>
                    <a href="<?php echo site_url(''); ?>" style="color:#9fbff0">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
