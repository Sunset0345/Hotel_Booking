<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<div style="max-width:820px;margin:6rem auto;padding:18px;background:#fff;border-radius:10px;color:#111">
  <h2 style="margin-top:0">Profile</h2>
  <div style="display:flex;gap:18px;align-items:center">
    <div style="width:84px;height:84px;border-radius:999px;background:#e6eefc;display:flex;align-items:center;justify-content:center;font-weight:700;color:#0b74de;font-size:1.25rem">
      <?php echo strtoupper(substr($user['full_name'],0,1)); ?>
    </div>
    <div>
      <div style="font-weight:700;font-size:1.1rem"><?php echo htmlspecialchars($user['full_name']); ?></div>
      <div style="color:#666"><?php echo htmlspecialchars($user['email']); ?></div>
    </div>
  </div>
  <div style="margin-top:1rem">
    <p>You can edit your profile from the account settings (not implemented).</p>
  </div>
</div>