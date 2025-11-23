<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<div style="padding:12px">
  <h3 style="margin-top:0">Verification Requests</h3>
  <?php if(empty($requests)): ?>
    <div style="color:#666">No pending verification requests.</div>
  <?php else: ?>
    <table style="width:100%;border-collapse:collapse">
      <thead>
        <tr>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">User</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Email</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Address</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">ID Document</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Selfie</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Requested</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($requests as $r): ?>
          <?php $isFocus = (!empty($focus_user) && intval($focus_user) === intval($r['user_id'])); ?>
          <tr id="vr-<?php echo intval($r['user_id']); ?>" style="<?php echo $isFocus ? 'background:#fff7e6' : ''; ?>">
            <td style="padding:8px;border-bottom:1px solid #f2f2f2"><?php echo htmlspecialchars($r['full_name']); ?></td>
            <td style="padding:8px;border-bottom:1px solid #f2f2f2"><?php echo htmlspecialchars($r['email']); ?></td>
            <td style="padding:8px;border-bottom:1px solid #f2f2f2"><?php echo nl2br(htmlspecialchars($r['address'] ?? '')); ?></td>
            <td style="padding:8px;border-bottom:1px solid #f2f2f2"><?php if(!empty($r['id_document'])): ?><a target="_blank" href="<?php echo base_url() . PUBLIC_DIR . '/' . $r['id_document']; ?>">View</a><?php else: ?>-<?php endif; ?></td>
            <td style="padding:8px;border-bottom:1px solid #f2f2f2"><?php if(!empty($r['id_selfie'])): ?><a target="_blank" href="<?php echo base_url() . PUBLIC_DIR . '/' . $r['id_selfie']; ?>">View</a><?php else: ?>-<?php endif; ?></td>
            <td style="padding:8px;border-bottom:1px solid #f2f2f2"><?php echo htmlspecialchars($r['verification_requested_at'] ?? ''); ?></td>
            <td style="padding:8px;border-bottom:1px solid #f2f2f2">
              <form method="post" action="<?php echo site_url('admin/verification_approve/' . intval($r['user_id'])); ?>" style="display:inline-block;margin-right:6px">
                <input type="hidden" name="admin_token" value="<?php echo htmlspecialchars($admin_action_token ?? '') ?>" />
                <button type="submit" style="background:#0b7a3a;color:#fff;padding:6px 10px;border-radius:6px;border:none">Approve</button>
              </form>
              <form method="post" action="<?php echo site_url('admin/verification_reject/' . intval($r['user_id'])); ?>" style="display:inline-block">
                <input type="hidden" name="admin_token" value="<?php echo htmlspecialchars($admin_action_token ?? '') ?>" />
                <input type="text" name="note" placeholder="Optional reason" style="padding:6px;border:1px solid #ddd;border-radius:6px;margin-right:6px;font-size:0.9rem">
                <button type="submit" style="background:#b65d00;color:#fff;padding:6px 10px;border-radius:6px;border:none">Reject</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
    <script>
      (function(){
        try{
          var focus = <?php echo json_encode(isset($focus_user) ? intval($focus_user) : null); ?>;
          if(focus){
            var el = document.getElementById('vr-' + focus);
            if(el){ el.scrollIntoView({behavior:'smooth', block:'center'}); el.style.boxShadow = '0 6px 18px rgba(0,0,0,0.08)'; }
          }
        }catch(e){}
      })();
    </script>
</div>
