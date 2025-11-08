<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<div style="padding:12px">
    <h2 style="margin-top:0">Users Management</h2>

    <div style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center">
        <div>Showing <?php echo count($users); ?> of <?php echo intval(isset($total) ? $total : 0); ?> users</div>
        <div><!-- Admins cannot create users here --></div>
    </div>

    <table style="width:100%;border-collapse:collapse;background:rgba(255,255,255,0.02)">
        <thead>
            <tr style="text-align:left;border-bottom:1px solid rgba(255,255,255,0.04)">
                <th style="padding:8px">ID</th>
                <th style="padding:8px">Name</th>
                <th style="padding:8px">Email</th>
                <th style="padding:8px">Phone</th>
                <th style="padding:8px">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($users)): foreach($users as $u): ?>
            <tr style="border-bottom:1px solid rgba(255,255,255,0.02)">
                <td style="padding:8px;vertical-align:top"><?php echo intval($u['user_id']); ?></td>
                <td style="padding:8px;vertical-align:top"><?php echo htmlspecialchars($u['full_name']); ?></td>
                <td style="padding:8px;vertical-align:top"><?php echo htmlspecialchars($u['email']); ?></td>
                <td style="padding:8px;vertical-align:top"><?php echo htmlspecialchars(isset($u['phone']) ? $u['phone'] : ''); ?></td>
                <td style="padding:8px;vertical-align:top">
                    <a href="<?php echo site_url('admin/users/edit/' . intval($u['user_id'])); ?>" class="btn small" data-ajax> Edit</a>
                    <?php $blocked = isset($u['is_blocked']) && $u['is_blocked'] ? true : false; ?>
                    <a href="<?php echo site_url('admin/users/block/' . intval($u['user_id'])); ?>" class="btn small" data-ajax-block style="background:<?php echo $blocked ? '#2ecc71' : '#ff6b6b'; ?>;margin-left:8px"><?php echo $blocked ? 'Unblock' : 'Block'; ?></a>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="5" style="padding:12px">No users found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if(isset($last_page) && $last_page > 1): ?>
    <div style="margin-top:12px;display:flex;gap:6px">
        <?php for($p=1;$p<=$last_page;$p++): ?>
            <a href="<?php echo site_url('admin/users') . '?page=' . $p; ?>" class="btn small" data-ajax style="<?php echo ($p == $current_page) ? 'background:#2995ff' : ''; ?>"><?php echo $p; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <script>
    (function(){
        // Attach AJAX handlers for edit/delete links that have data-ajax attribute
        const main = document.getElementById('admin-main');
        if(!main) return;

        main.querySelectorAll('a[data-ajax]').forEach(a => a.addEventListener('click', function(e){
            e.preventDefault();
            fetch(this.href, { headers:{'X-Requested-With':'XMLHttpRequest'} }).then(r=>r.text()).then(html=>{ main.innerHTML = html; }).catch(err=>{ alert('Unable to load'); });
        }));

        main.querySelectorAll('a[data-ajax-block]').forEach(a => a.addEventListener('click', function(e){
            e.preventDefault();
            const confirmText = this.textContent.trim().toLowerCase().startsWith('un') ? 'Unblock this user?' : 'Block this user?';
            if(!confirm(confirmText)) return;
            fetch(this.href, { method:'GET', headers:{'X-Requested-With':'XMLHttpRequest'} }).then(r=>r.text()).then(txt=>{
                if(txt.trim() === 'OK'){
                    // reload the users list
                    fetch('<?php echo site_url('admin/users'); ?>', { headers:{'X-Requested-With':'XMLHttpRequest'} }).then(r=>r.text()).then(html=>{ main.innerHTML = html; });
                }else{
                    alert('Action failed: '+txt);
                }
            });
        }));
    })();
    </script>
</div>