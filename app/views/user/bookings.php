<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<!-- Hero background similar to homepage -->
<div style="position:fixed;inset:0;z-index:-1;background-image:url('https://www.kayak.com.ph/rimg/himg/e1/5b/f8/expediav2-42996-f561b3-210999.jpg?width=2000&height=1200&crop=true');background-size:cover;background-position:center;">
  <div style="position:absolute;inset:0;background:linear-gradient(180deg, rgba(3,37,65,0.45), rgba(3,37,65,0.6));"></div>
</div>

<div style="max-width:980px;margin:8vh auto;padding:28px;background:rgba(255,255,255,0.92);border-radius:14px;color:#0b172a;box-shadow:0 18px 46px rgba(2,6,23,0.12);">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:12px">
    <div style="display:flex;align-items:center;gap:12px">
      <a href="/" onclick="(function(e){e.preventDefault(); if(window.history && history.length>1){ history.back(); } else { window.location.href = '/'; }})(event)" title="Go back" style="display:inline-flex;align-items:center;gap:8px;padding:8px 12px;background:#0b74de;color:#fff;border-radius:10px;text-decoration:none;box-shadow:0 6px 18px rgba(11,116,222,0.18);font-weight:600">
        <!-- simple left arrow -->
        <span style="display:inline-block;transform:translateY(1px);font-size:1.05rem">←</span>
        <span>Back</span>
      </a>
      <h2 style="margin:0">Your Bookings</h2>
    </div>
    <!-- optional space for future actions (filters, export) -->
    <div></div>
  </div>
  <?php if(empty($bookings)): ?>
    <div style="padding:14px;color:#666">You have no bookings yet.</div>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:12px">
            <?php foreach($bookings as $b): ?>
        <div style="border:1px solid #eef2f7;padding:14px;border-radius:10px;background:#ffffff">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <div>
              <div style="font-weight:700;color:#0b74de">Room <?php echo htmlspecialchars($b['room_number'] ?: '—'); ?><?php if(!empty($b['room_type'])): ?> · <span style="color:#6b7280"><?php echo htmlspecialchars($b['room_type']); ?></span><?php endif; ?></div>
              <div style="color:#6b7280;font-size:0.95rem">Booked on: <?php echo htmlspecialchars($b['date_booked']); ?></div>
            </div>
            <div style="text-align:right">
              <div style="font-weight:700;color:<?php echo (strtolower($b['status']) === 'approved') ? '#16a34a' : (strtolower($b['status']) === 'rejected' ? '#ef4444' : '#0b74de'); ?>"><?php echo htmlspecialchars($b['status']); ?></div>
              <div style="margin-top:8px">
                <?php $st = strtolower(trim($b['status'] ?? '')); if(in_array($st, ['approved','completed'])): ?>
                  <a href="<?php echo site_url('user/invoice/' . intval($b['booking_id'])); ?>" class="btn" style="padding:6px 10px;background:#0b74de;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Download Invoice</a>
                <?php else: ?>
                  <span style="display:inline-block;padding:6px 10px;background:#f3f4f6;color:#6b7280;border-radius:8px;font-size:0.9rem">Invoice (available after approval or completion)</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>