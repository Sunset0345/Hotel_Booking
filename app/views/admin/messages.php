<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<style>
    /* Admin messages panel expand/collapse animation */
    #admin-messages-root { display:flex; gap:12px; padding:12px; align-items:flex-start; }
    #admin-messages-root aside { width:320px; transition: width 280ms ease, transform 280ms ease; }
    #admin-messages-root section { flex:1; transition: width 280ms ease, transform 280ms ease; }
    /* Expanded: aside shrinks to 25% and section grows to 75% */
    #admin-messages-root.expanded aside { width:25%; min-width:220px; }
    #admin-messages-root.expanded section { width:75%; flex:none; }
    /* small visual for selected row */
    #admin-user-list li.selected-user { box-shadow: inset 3px 0 0 0 rgba(41,149,255,0.18); }
    /* Conversation message alignment */
    .chat-msg { margin-bottom:12px; display:flex; flex-direction:column; }
    .chat-msg.user { align-items:flex-start; }
    .chat-msg.admin { align-items:flex-end; }
    .chat-bubble { display:inline-block;padding:8px;border-radius:8px; max-width:78%; }
    .chat-bubble.user { background: rgba(255,255,255,0.06); }
    .chat-bubble.admin { background: rgba(11,116,222,0.12); }
</style>
<div id="admin-messages-root" style="display:flex;gap:12px;padding:12px">
    <aside style="width:320px;background:rgba(255,255,255,0.04);padding:10px;border-radius:8px;">
        <h3 style="margin-top:0">Users</h3>
        <ul id="admin-user-list" style="list-style:none;padding:0;margin:0">
            <?php if(empty($users)): ?>
                <li style="padding:8px;color:#999">No users found.</li>
            <?php else: ?>
                <?php foreach($users as $u): ?>
                    <li style="padding:8px;border-bottom:1px solid rgba(255,255,255,0.03);display:flex;justify-content:space-between;align-items:center">
                        <a href="#" data-user-id="<?php echo $u['user_id']; ?>" style="color:#fff;text-decoration:none;display:block">
                            <?php echo htmlspecialchars($u['full_name']); ?>
                        </a>
                        <?php if(isset($u['unread']) && intval($u['unread']) > 0): ?>
                            <span style="background:#e53935;color:#fff;padding:4px 8px;border-radius:12px;font-size:0.8rem"><?php echo intval($u['unread']); ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </aside>
    <section style="flex:1;background:rgba(255,255,255,0.03);padding:12px;border-radius:8px;display:flex;flex-direction:column;gap:8px">
        <div id="admin-selected-header" style="font-weight:700;font-size:1.1rem;margin-bottom:6px;color:#2995ff;min-height:24px">
            <?php if(!empty($selected_user)): ?>
                <?php $sel = null; foreach($users as $u){ if($u['user_id'] == $selected_user){ $sel = $u; break; } } ?>
                <?php if($sel): ?>
                    Conversation with <?php echo htmlspecialchars($sel['full_name']); ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div id="admin-conversation-panel" style="flex:1;overflow:auto;padding:8px;border-radius:6px;background:rgba(0,0,0,0.04)">
            <?php if(empty($conversation)): ?>
                <div style="padding:18px;color:#999">Select a user to view conversation.</div>
            <?php else: ?>
                <?php foreach($conversation as $m): ?>
                    <div style="margin-bottom:12px">
                        <div style="font-weight:600;margin-bottom:6px"><?php echo htmlspecialchars($m['full_name']); ?> <small style="color:#666">— <?php echo htmlspecialchars($m['date_sent']); ?></small></div>
                        <div style="background:<?php echo $m['from_admin'] ? 'rgba(11,116,222,0.12)' : 'rgba(255,255,255,0.06)'; ?>;display:inline-block;padding:8px;border-radius:8px;"><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form id="admin-message-form" method="post" style="display:flex;gap:8px">
            <input type="hidden" name="user_id" id="admin-selected-user" value="<?php echo intval($selected_user); ?>" />
            <input type="text" name="message" id="admin-message-input" placeholder="Type a message..." style="flex:1;padding:8px;border-radius:8px;border:1px solid rgba(255,255,255,0.06);background:transparent;color:#fff" disabled />
            <button type="submit" id="admin-message-send" style="padding:8px 12px;border-radius:8px;border:0;background:#2995ff;color:#fff" disabled>Send</button>
    </form>
    </section>
</div>
<div id="admin-debug" style="position:fixed;right:12px;bottom:12px;width:360px;height:180px;background:rgba(0,0,0,0.7);color:#0f0;overflow:auto;padding:8px;font-family:monospace;font-size:12px;border-radius:6px;z-index:9999;display:none"></div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var userList = document.getElementById('admin-user-list');
    var conversationPanel = document.getElementById('admin-conversation-panel');
    var selectedUserInput = document.getElementById('admin-selected-user');
    var messageForm = document.getElementById('admin-message-form');
    var msgInput = document.getElementById('admin-message-input');
    var msgSend = document.getElementById('admin-message-send');

    function setFormEnabled(enabled) {
        msgInput.disabled = !enabled;
        msgSend.disabled = !enabled;
        if(!enabled) msgInput.value = '';
    }
    setFormEnabled(!!selectedUserInput.value);

    function debugLog(msg){
        try{ console.log(msg); }catch(e){}
        var dbg = document.getElementById('admin-debug');
        if(dbg){ dbg.style.display = 'block'; dbg.textContent += msg + '\n'; dbg.scrollTop = dbg.scrollHeight; }
    }

    function debugFetch(url, options){
        options = options || {};
        options.headers = options.headers || {};
        if(!options.headers['X-Requested-With'] && !options.headers['x-requested-with']){
            options.headers['X-Requested-With'] = 'XMLHttpRequest';
        }
        debugLog('[debugFetch] URL: ' + url + (options && options.method ? ' METHOD:' + options.method : ''));
        var start = Date.now();
        return fetch(url, options)
            .then(function(resp){
                var took = Date.now() - start;
                var contentType = resp.headers.get('content-type') || '';
                debugLog('[debugFetch] Status: ' + resp.status + ' (' + took + 'ms) content-type: ' + contentType);
                return resp.text().then(function(text){
                    debugLog('[debugFetch] Response length: ' + (text ? text.length : 0));
                    return { status: resp.status, ok: resp.ok, text: text, contentType: contentType };
                });
            }).catch(function(err){
                debugLog('[debugFetch] Error: ' + err);
                throw err;
            });
    }
    userList.addEventListener('click', function(e) {
        var li = e.target.closest('li');
        if(!li) return;
        var anchor = li.querySelector('a[data-user-id]');
        if(!anchor) return;
        e.preventDefault();
        var userId = anchor.dataset.userId;

        // clear previous selection styles
        Array.from(userList.querySelectorAll('li')).forEach(function(r){
            r.classList.remove('selected-user');
            r.style.background = '';
        });
        li.classList.add('selected-user');
        li.style.background = 'rgba(41,149,255,0.06)';

    selectedUserInput.value = userId;
    setFormEnabled(true);
    var root = document.getElementById('admin-messages-root'); if(root) root.classList.add('expanded');
        var header = document.getElementById('admin-selected-header');
        header.textContent = 'Conversation with ' + anchor.textContent.trim();
        conversationPanel.innerHTML = '<div style="padding:18px;color:#999">Loading...</div>';

    var ajaxUrl = '<?php echo (config_item("index_page") ? config_item("index_page")."/" : "") . "admin/messages_api"; ?>?user_id=' + encodeURIComponent(userId);
        debugFetch(ajaxUrl).then(function(res){
            try{
                if(res.contentType && res.contentType.indexOf('application/json') === -1){ throw new Error('non-json'); }
                var obj = JSON.parse(res.text);
                if(obj && obj.conversation){
                    conversationPanel.innerHTML = renderConversation(obj.conversation);
                    
                    conversationPanel.scrollTop = conversationPanel.scrollHeight;

                    var badge = li.querySelector('span'); if(badge) badge.style.display = 'none';
                    return;
                }
            }catch(e){ /* not JSON */ }
            conversationPanel.innerHTML = res.text;
            conversationPanel.scrollTop = conversationPanel.scrollHeight;
            var badge2 = li.querySelector('span'); if(badge2) badge2.style.display = 'none';
        }).catch(function(err){
            conversationPanel.innerHTML = '<div style="padding:18px;color:#c00">Error loading conversation.</div>';
        });
        msgInput.value = '';
    });

    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var userId = selectedUserInput.value;
        var message = msgInput.value.trim();
        if(!userId || !message) return;
        msgSend.disabled = true;
        var formData = new FormData();
        formData.append('user_id', userId);
        formData.append('message', message);
        debugFetch('<?php echo (config_item("index_page") ? config_item("index_page")."/" : "") . "admin/messages"; ?>', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(res){
            var errorMsg = '';
            if(res.contentType && res.contentType.indexOf('application/json') === -1){
                errorMsg = '<div style="padding:14px;color:#c00;background:#fee;border-radius:8px;margin-bottom:8px">Server returned unexpected response. You may be logged out or there is a server error.</div>' + res.text;
                conversationPanel.innerHTML = errorMsg;
                msgSend.disabled = false;
                return;
            }
            var obj = null;
            try{ obj = JSON.parse(res.text); }catch(e){ obj = null; }
            if(obj && obj.status === 'error'){
                errorMsg = '<div style="padding:14px;color:#c00;background:#fee;border-radius:8px;margin-bottom:8px">'+(obj.msg ? obj.msg : 'Unable to send message.')+'</div>';
                if(obj.detail){ errorMsg += '<pre style="background:#fff0f0;color:#c00;padding:8px;border-radius:6px">'+escapeHtml(obj.detail)+'</pre>'; }
                conversationPanel.innerHTML = errorMsg;
                msgSend.disabled = false;
                return;
            }
            // Success: refresh conversation
            var ajaxUrl2 = '<?php echo (config_item("index_page") ? config_item("index_page")."/" : "") . "admin/messages_api"; ?>?user_id=' + encodeURIComponent(userId);
            debugFetch(ajaxUrl2).then(function(res2){
                try{
                    if(res2.contentType && res2.contentType.indexOf('application/json') === -1){ conversationPanel.innerHTML = res2.text; return; }
                    var obj2 = JSON.parse(res2.text);
                    if(obj2 && obj2.conversation){
                        conversationPanel.innerHTML = renderConversation(obj2.conversation);
                    } else {
                        conversationPanel.innerHTML = res2.text;
                    }
                }catch(e){
                    conversationPanel.innerHTML = res2.text;
                }
                msgInput.value = '';
                msgSend.disabled = false;
            });
        })
        .catch(function(err){
            conversationPanel.innerHTML = '<div style="padding:14px;color:#c00;background:#fee;border-radius:8px;margin-bottom:8px">Error sending message: '+escapeHtml(err)+'</div>';
            msgSend.disabled = false;
        });
    });
    // Polling: refresh conversation every 5 seconds when a user is selected
    setInterval(function(){
        var userId = selectedUserInput.value;
        if(userId){
            var ajaxUrl3 = '<?php echo (config_item("index_page") ? config_item("index_page")."/" : "") . "admin/messages_api"; ?>?user_id=' + encodeURIComponent(userId);
            debugFetch(ajaxUrl3).then(function(res){
                try{
                    if(res.contentType && res.contentType.indexOf('application/json') === -1){ conversationPanel.innerHTML = res.text; return; }
                    var obj = JSON.parse(res.text);
                    if(obj && obj.conversation){ conversationPanel.innerHTML = renderConversation(obj.conversation); }
                    else { conversationPanel.innerHTML = res.text; }
                }catch(e){ conversationPanel.innerHTML = res.text; }
            }).catch(function(){ /* ignore polling errors */ });
        }
    }, 5000);

    // adminName will be provided by server-side if available
    var ADMIN_NAME = '<?php echo isset($admin_name) ? addslashes($admin_name) : 'Admin'; ?>';
    function renderConversation(conv){
        if(!conv || !conv.length) return '<div style="padding:18px;color:#999">No messages yet.</div>';
        var html = '';
        conv.forEach(function(m){
            var isAdmin = parseInt(m.from_admin) === 1;
            var name = isAdmin ? ADMIN_NAME : (m.full_name || 'User');
            var date = m.date_sent || '';
            var body = m.message || '';
            var roleClass = isAdmin ? 'admin' : 'user';
            html += '<div class="chat-msg '+roleClass+'">';
            html += '<div style="font-weight:600;margin-bottom:6px">'+escapeHtml(name)+' <small style="color:#666">— '+escapeHtml(date)+'</small></div>';
            html += '<div class="chat-bubble '+roleClass+'">'+nl2br(escapeHtml(body))+'</div>';
            html += '</div>';
        });
        return html;
    }

    function escapeHtml(str){
        if(!str) return '';
        return String(str).replace(/[&<>\"]/g, function(s){
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s];
        });
    }

    function nl2br(str){
        return str.replace(/\r?\n/g, '<br/>');
    }
});
</script>