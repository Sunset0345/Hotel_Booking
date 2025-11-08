<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<div style="padding:12px">
    <h2 style="margin-top:0">Edit User</h2>
    <form id="user-edit-form" method="post" action="<?php echo site_url('admin/users/update/' . intval($user['user_id'])); ?>">
        <div style="margin-bottom:8px">
            <label>Full name</label>
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" style="width:100%;padding:8px;border-radius:6px;border:1px solid rgba(255,255,255,0.06)" />
        </div>
        <div style="margin-bottom:8px">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" style="width:100%;padding:8px;border-radius:6px;border:1px solid rgba(255,255,255,0.06)" />
        </div>
        <div style="margin-bottom:8px">
            <label>New password <small style="color:rgba(255,255,255,0.6)">(leave blank to keep current)</small></label>
            <input type="password" name="password" value="" style="width:100%;padding:8px;border-radius:6px;border:1px solid rgba(255,255,255,0.06)" />
        </div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn" style="background:#2995ff">Save</button>
            <a href="<?php echo site_url('admin/users'); ?>" class="btn" data-ajax style="background:#888">Back</a>
        </div>
    </form>

    <script>
    (function(){
        const form = document.getElementById('user-edit-form');
        const main = document.getElementById('admin-main');
        if(!form || !main) return;
        form.addEventListener('submit', function(e){
            e.preventDefault();
            const formData = new FormData(form);
            fetch(form.action, { method: 'POST', body: formData, headers:{'X-Requested-With':'XMLHttpRequest'} }).then(r=>r.text()).then(txt=>{
                // reload users list after successful update
                fetch('<?php echo site_url('admin/users'); ?>', { headers:{'X-Requested-With':'XMLHttpRequest'} }).then(r=>r.text()).then(html=>{ main.innerHTML = html; });
            }).catch(err=>{ alert('Unable to save'); });
        });
    })();
    </script>
</div>