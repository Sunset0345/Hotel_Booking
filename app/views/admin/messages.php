<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<style>
#msgs-wrapper { display:flex; gap:12px; padding:12px; height:calc(100vh - 200px); }
#msgs-users { width:280px; background:rgba(255,255,255,0.03); border-radius:8px; padding:12px; overflow-y:auto; }
#msgs-chat { flex:1; background:rgba(255,255,255,0.03); border-radius:8px; padding:12px; display:flex; flex-direction:column; }
#msgs-header { font-weight:700; color:#2995ff; margin-bottom:12px; }
#msgs-content { flex:1; overflow-y:auto; padding:12px; background:rgba(0,0,0,0.04); border-radius:6px; margin-bottom:12px; }
.msgs-user-btn { width:100%; text-align:left; padding:10px; margin-bottom:6px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:#fff; cursor:pointer; font-size:0.95rem; }
.msgs-user-btn:hover { background:rgba(255,255,255,0.08); }
.msgs-user-btn.active { background:#2995ff; }
.msg-bubble { margin-bottom:12px; padding:10px; border-radius:8px; max-width:80%; }
.msg-bubble.user-msg { background:rgba(255,255,255,0.08); color:#fff; margin-right:auto; }
.msg-bubble.admin-msg { background:linear-gradient(135deg,#2995ff,#1e6fd8); color:#fff; margin-left:auto; }
.msg-meta { font-size:0.8rem; color:#999; margin-bottom:4px; }
#msgs-form { display:flex; gap:8px; }
#msgs-form input { flex:1; padding:10px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:#fff; }
#msgs-form input::placeholder { color:rgba(255,255,255,0.5); }
#msgs-form button { padding:10px 16px; background:#2995ff; border:0; border-radius:6px; color:#fff; font-weight:600; cursor:pointer; }
#msgs-form button:hover { background:#1e6fd8; }
#msgs-form button:disabled { background:#666; cursor:not-allowed; }
</style>

<div id="msgs-wrapper">
  <div id="msgs-users">
    <h3 style="margin-top:0; margin-bottom:12px;">Users</h3>
    <div id="msgs-list"></div>
  </div>
  
  <div id="msgs-chat">
    <div id="msgs-header">Select a user</div>
    <div id="msgs-content">Select a user from the list</div>
    <form id="msgs-form" onsubmit="window.msgs_sendMessage(event)">
      <input type="text" id="msgs-input" placeholder="Type message..." disabled />
      <button type="submit" id="msgs-btn" disabled>Send</button>
    </form>
  </div>
</div>

<!-- DEBUG INFO START -->
<!-- isset($users): <?php echo isset($users) ? 'true' : 'false'; ?> -->
<!-- is_array($users): <?php echo (isset($users) && is_array($users)) ? 'true' : 'false'; ?> -->
<!-- count($users): <?php echo (isset($users) && is_array($users)) ? count($users) : 'N/A'; ?> -->
<!-- $users value: <?php echo isset($users) ? json_encode($users) : 'undefined'; ?> -->
<!-- DEBUG INFO END -->

<script>
// DEBUG: Check what PHP passed to us
// Check if $users was set and what it contains
if (typeof console !== 'undefined') {
  console.log('[PHP Debug] isset($users):', <?php echo isset($users) ? 'true' : 'false'; ?>);
  console.log('[PHP Debug] is_array($users):', <?php echo (isset($users) && is_array($users)) ? 'true' : 'false'; ?>);
  console.log('[PHP Debug] count($users):', <?php echo (isset($users) && is_array($users)) ? count($users) : -1; ?>);
  console.log('[PHP Debug] gettype($users):', '<?php echo (isset($users) ? gettype($users) : 'not set'); ?>');
  console.log('[PHP Debug] $users raw value:', <?php echo isset($users) ? json_encode($users) : 'undefined'; ?>);
}

window.msgs_state = {
  selectedUserId: null,
  users: <?php 
    if (isset($users) && is_array($users) && count($users) > 0) {
      echo json_encode($users);
    } else {
      echo '[]';
    }
  ?>,
  ADMIN_NAME: '<?php echo isset($admin_name) ? addslashes($admin_name) : 'Admin'; ?>'
};
console.log('msgs_state.users:', window.msgs_state.users);
console.log('Number of users:', (window.msgs_state.users || []).length);

function msgs_escapeHtml(s) {
  if (!s) return '';
  return String(s).replace(/[&<>"]/g, function(c) {
    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];
  });
}

function msgs_renderUsers() {
  var html = '';
  if (!window.msgs_state.users || !window.msgs_state.users.length) {
    html = '<p style="color:#999">No users found</p>';
  } else {
    window.msgs_state.users.forEach(function(u) {
      var safeName = msgs_escapeHtml(u.full_name);
      html += '<button class="msgs-user-btn" data-user-id="' + u.user_id + '" data-user-name="' + safeName + '" style="position:relative">'
        + safeName;
      if (u.unread && parseInt(u.unread) > 0) {
        html += '<span style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:#e53935; color:#fff; padding:2px 6px; border-radius:10px; font-size:0.7rem">' + u.unread + '</span>';
      }
      html += '</button>';
    });
  }
  var listEl = document.getElementById('msgs-list');
  listEl.innerHTML = html;

  // Bind click handlers without inline JS to satisfy strict CSP (no unsafe-eval)
  listEl.querySelectorAll('.msgs-user-btn').forEach(function(btn){
    btn.addEventListener('click', function(e){
      var id = parseInt(this.getAttribute('data-user-id'), 10);
      var name = this.getAttribute('data-user-name') || 'User';
      window.msgs_selectUser(id, name, e);
    });
  });
}

window.msgs_selectUser = function(userId, userName, e) {
  window.msgs_state.selectedUserId = userId;
  
  // Mark button as active
  document.querySelectorAll('.msgs-user-btn').forEach(b => b.classList.remove('active'));
  if (e && e.target) {
    var btn = e.target.closest('button');
    if (btn) btn.classList.add('active');
  } else {
    // Fallback when called programmatically: activate by data-user-id
    var selBtn = document.querySelector('.msgs-user-btn[data-user-id="' + userId + '"]');
    if (selBtn) selBtn.classList.add('active');
  }
  
  // Update header and enable form
  document.getElementById('msgs-header').textContent = 'Chat with ' + userName;
  document.getElementById('msgs-input').disabled = false;
  document.getElementById('msgs-btn').disabled = false;
  
  // Load conversation
  document.getElementById('msgs-content').innerHTML = '<div style="padding:12px; color:#999">Loading...</div>';
  
  fetch('<?php echo site_url("admin/messages_api"); ?>?user_id=' + userId, {
    method: 'GET',
    credentials: 'same-origin',
    headers: {'X-Requested-With': 'XMLHttpRequest'}
  })
  .then(r => r.json())
  .then(data => {
    if (data.conversation) {
      msgs_renderConversation(data.conversation);
    } else {
      document.getElementById('msgs-content').innerHTML = '<div style="padding:12px; color:#c00">Error loading messages</div>';
    }
  })
  .catch(e => {
    document.getElementById('msgs-content').innerHTML = '<div style="padding:12px; color:#c00">Error: ' + msgs_escapeHtml(String(e)) + '</div>';
  });
};

function msgs_renderConversation(conv) {
  var html = '';
  if (!conv || !conv.length) {
    html = '<div style="padding:12px; color:#999">No messages yet</div>';
  } else {
    conv.forEach(function(m) {
      var isAdmin = parseInt(m.from_admin) === 1;
      var name = isAdmin ? window.msgs_state.ADMIN_NAME : (m.full_name || 'User');
      var cls = isAdmin ? 'admin-msg' : 'user-msg';
      html += '<div class="msg-meta">' + msgs_escapeHtml(name) + ' — ' + msgs_escapeHtml(m.date_sent) + '</div>';
      html += '<div class="msg-bubble ' + cls + '">' + msgs_escapeHtml(m.message).replace(/\n/g, '<br>') + '</div>';
    });
  }
  var panel = document.getElementById('msgs-content');
  panel.innerHTML = html;
  panel.scrollTop = panel.scrollHeight;
}

window.msgs_sendMessage = function(e) {
  e.preventDefault();
  var userId = window.msgs_state.selectedUserId;
  var msg = document.getElementById('msgs-input').value.trim();
  if (!userId || !msg) return;
  
  document.getElementById('msgs-btn').disabled = true;
  var fd = new FormData();
  fd.append('user_id', userId);
  fd.append('message', msg);
  
  fetch('<?php echo site_url("admin/messages"); ?>', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {'X-Requested-With': 'XMLHttpRequest'},
    body: fd
  })
  .then(r => r.json())
  .then(data => {
    if (data.status === 'ok' || !data.error) {
      document.getElementById('msgs-input').value = '';
      // Reload conversation
      fetch('<?php echo site_url("admin/messages_api"); ?>?user_id=' + userId, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {'X-Requested-With': 'XMLHttpRequest'}
      })
      .then(r => r.json())
      .then(d => {
        if (d.conversation) msgs_renderConversation(d.conversation);
      });
    }
    document.getElementById('msgs-btn').disabled = false;
  })
  .catch(e => {
    alert('Error sending message: ' + String(e));
    document.getElementById('msgs-btn').disabled = false;
  });
};

// Auto-refresh every 4 seconds
setInterval(function() {
  if (window.msgs_state.selectedUserId) {
    fetch('<?php echo site_url("admin/messages_api"); ?>?user_id=' + window.msgs_state.selectedUserId, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(r => r.json())
    .then(d => {
      if (d.conversation) msgs_renderConversation(d.conversation);
    })
    .catch(e => {});
  }
}, 4000);

// Initialize
console.log('[msgs] Initializing...');
console.log('[msgs] users array:', window.msgs_state.users);
console.log('[msgs] users length:', (window.msgs_state.users || []).length);
msgs_renderUsers();
console.log('[msgs] msgs_renderUsers() called');
// Auto-select first user if available
if (window.msgs_state.users && window.msgs_state.users.length > 0) {
  console.log('[msgs] Auto-selecting first user:', window.msgs_state.users[0]);
  setTimeout(function() {
    window.msgs_selectUser(window.msgs_state.users[0].user_id, window.msgs_state.users[0].full_name);
  }, 200);
} else {
  console.log('[msgs] No users available to auto-select');
}

</script>