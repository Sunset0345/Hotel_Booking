<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Account Created - Blue Lagoon Hotel</title>
<style>
    body{ margin:0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", sans-serif; color:#fff; background:#081a2b; }
    .card{ max-width:520px; margin:8vh auto; background: rgba(255,255,255,0.95); color:#0f172a; padding:1.75rem; border-radius:12px; }
    h2{ margin:0 0 0.5rem 0; }
    .small{ font-size:0.95rem; margin-top:0.75rem; }
    a.button{ display:inline-block; margin-top:1rem; background:#ff7c38; color:#fff; padding:0.6rem 1rem; border-radius:8px; text-decoration:none; font-weight:700; }
</style>
</head>
<body>
<div class="card">
    <h2>Account Created</h2>
    <p>Congratulations — your account has been created successfully<?= isset($email) ? ' for <strong>'.htmlspecialchars($email).'</strong>' : '' ?>.</p>
    <p class="small">You can now <a class="button" href="<?= site_url('auth/login') ?>">log in</a> to access your account.</p>
</div>
</body>
</html>
