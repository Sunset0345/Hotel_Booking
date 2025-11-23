<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<!-- Hero background to match other pages -->
<div style="position:fixed;inset:0;z-index:-1;background-image:url('https://www.kayak.com.ph/rimg/himg/e1/5b/f8/expediav2-42996-f561b3-210999.jpg?width=2000&height=1200&crop=true');background-size:cover;background-position:center;">
  <div style="position:absolute;inset:0;background:linear-gradient(180deg, rgba(3,37,65,0.45), rgba(3,37,65,0.6));"></div>
</div>
<div style="max-width:720px;margin:6rem auto;padding:18px;background:#fff;border-radius:10px;color:#111">
  <h2 style="margin-top:0">Edit Profile</h2>
  <?php if(!empty($error)): ?>
    <div style="color:#b00020;margin-bottom:12px"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  <?php if(!empty($success)): ?>
    <div style="color:#0b7a3a;margin-bottom:12px"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>
  <div style="display:flex;gap:18px;flex-wrap:wrap">
    <div style="flex:1 1 360px;min-width:280px">
      <form method="post" action="<?php echo site_url('user/profile/edit'); ?>">
        <div style="margin-bottom:12px">
          <label style="display:block;font-weight:700;margin-bottom:6px">Full name</label>
          <input name="full_name" type="text" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px">
        </div>
        <div style="margin-bottom:12px">
          <label style="display:block;font-weight:700;margin-bottom:6px">Email</label>
          <input name="email" type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px">
        </div>
        <div style="margin-bottom:12px">
          <label style="display:block;font-weight:700;margin-bottom:6px">Phone</label>
          <input name="phone" type="tel" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px">
        </div>
        <div style="margin-bottom:12px">
          <label style="display:block;font-weight:700;margin-bottom:6px">Gender</label>
          <select name="gender" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px">
            <option value="" <?php echo empty($user['gender']) ? 'selected' : ''; ?>>Not specified</option>
            <option value="male" <?php echo (isset($user['gender']) && strtolower($user['gender']) === 'male') ? 'selected' : ''; ?>>Male</option>
            <option value="female" <?php echo (isset($user['gender']) && strtolower($user['gender']) === 'female') ? 'selected' : ''; ?>>Female</option>
            <option value="other" <?php echo (isset($user['gender']) && strtolower($user['gender']) === 'other') ? 'selected' : ''; ?>>Other</option>
          </select>
        </div>
        <div style="margin-bottom:12px">
          <label style="display:block;font-weight:700;margin-bottom:6px">Date of birth</label>
          <input name="date_of_birth" type="date" value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px">
        </div>
        <div style="margin-bottom:12px">
          <label style="display:block;font-weight:700;margin-bottom:6px">Address</label>
          <textarea name="address" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
        </div>
        <div>
          <button type="submit" style="background:#0b74de;color:#fff;padding:8px 14px;border-radius:6px;border:none">Save changes</button>
          <a href="<?php echo site_url('user/profile'); ?>" style="margin-left:12px;color:#666">Cancel</a>
        </div>
      </form>
    </div>

    <div style="width:260px;flex:0 0 260px">
      <form method="post" action="<?php echo site_url('user/profile/edit'); ?>" enctype="multipart/form-data">
        <div style="margin-bottom:12px">
          <label style="display:block;font-weight:700;margin-bottom:6px">Profile picture</label>
          <?php if(!empty($user['avatar']) && is_file(PUBLIC_DIR . '/' . $user['avatar'])): ?>
            <div style="margin-bottom:8px"><img src="<?php echo base_url() . PUBLIC_DIR . '/' . $user['avatar']; ?>" style="width:84px;height:84px;border-radius:999px;object-fit:cover;border:1px solid #eee" alt="avatar" /></div>
          <?php endif; ?>
          <input type="file" name="avatar" accept="image/*" style="width:100%" />
        </div>
        <div style="display:flex;gap:8px;align-items:center">
          <button type="submit" style="background:#0b74de;color:#fff;padding:8px 14px;border-radius:6px;border:none">Upload avatar</button>
          <?php if(!empty($user['avatar'])): ?>
            <button type="submit" name="remove_avatar" value="1" style="background:#ef4444;color:#fff;padding:8px 10px;border-radius:6px;border:none">Remove</button>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>