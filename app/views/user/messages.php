<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<div style="max-width:980px;margin:6rem auto;padding:0;background:transparent;color:#111">
  <div style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 6px 18px rgba(2,6,23,0.06)">
  <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #eef2f6;background:linear-gradient(90deg,#0b74de22,#ffffff)">
      <div style="display:flex;align-items:center;gap:12px">
        <div style="width:44px;height:44px;border-radius:50%;background:#0b74de;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700">A</div>
        <div>
          <div style="font-weight:700">Admin</div>
          <div style="font-size:0.85rem;color:#475569">Support — usually responds within a few hours</div>
        </div>
      </div>
      <div style="font-size:0.85rem;color:#64748b;display:flex;align-items:center;gap:8px">Conversation with support
        <button id="chat-refresh" type="button" title="Refresh messages" style="display:none;background:#0b74de;color:#fff;border:0;padding:6px 10px;border-radius:6px;font-size:0.8rem">Refresh</button>
      </div>
    </div>

    <div id="user-chat" style="display:flex;flex-direction:column;height:520px">
  <div id="chat-area" class="chat-area" style="flex:1 1 auto;overflow:auto;padding:18px;background:#f8fafc;scroll-behavior:smooth"></div>

      <form id="user-message-form" action="<?php echo site_url('user/messages'); ?>" method="post" style="padding:12px;border-top:1px solid #eef2f6;background:#fff;display:flex;gap:8px;align-items:flex-end">
        <textarea id="user-message-input" name="message" rows="2" placeholder="Write a message to admin..." style="flex:1;padding:10px;border-radius:8px;border:1px solid #e6eef6;resize:vertical;min-height:44px"></textarea>
        <button id="user-send-btn" type="submit" style="background:#0b74de;color:#fff;padding:10px 14px;border-radius:8px;border:0">Send</button>
      </form>
    </div>
  </div>
</div>

<script>
// User messenger UI: render conversation, poll for updates, and send messages via AJAX.
(function(){
  var state = {
    conversation: <?php echo json_encode($conversation ?: []); ?>,
    user_id: <?php echo intval($user['user_id']); ?>,
    polling: null
  };

  var chatArea = document.getElementById('chat-area');
  var form = document.getElementById('user-message-form');
  var input = document.getElementById('user-message-input');
  var sendBtn = document.getElementById('user-send-btn');

  function esc(s){ return String(s||'').replace(/[&<>\"]/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }

  function fmtDate(str){
    if(!str) return '';
    var d = new Date(str.replace(/-/g,'/')); // Safari compatibility
    if(isNaN(d.getTime())) return str;
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var h = d.getHours();
    var m = d.getMinutes();
    var ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12; if(h === 0) h = 12;
    var mm = (m < 10 ? '0' : '') + m;
    return months[d.getMonth()] + ' ' + d.getDate() + ', ' + h + ':' + mm + ' ' + ampm;
  }

  function renderConversation(list, opts){
    opts = opts || {};
    var prevHeight = chatArea.scrollHeight;
    var atBottom = Math.abs(chatArea.scrollHeight - chatArea.scrollTop - chatArea.clientHeight) < 12;
    chatArea.innerHTML = '';
    if(!list || list.length === 0){
      chatArea.innerHTML = '<div style="padding:18px;color:#64748b">No messages yet — say hello to support.</div>';
      return;
    }
    list.forEach(function(m){
      // Normalize from_admin to integer for consistency (DB may return string)
      m.from_admin = (m.from_admin === 1 || m.from_admin === '1') ? 1 : 0;
      var wrapper = document.createElement('div');
      wrapper.style.marginBottom = '14px';
      wrapper.style.display = 'flex';
      wrapper.style.flexDirection = 'column';
      wrapper.style.alignItems = m.from_admin ? 'flex-start' : 'flex-end';

      // sender label
      var label = document.createElement('div');
      label.textContent = m.from_admin ? 'Admin' : 'You';
      label.style.fontSize = '0.7rem';
      label.style.fontWeight = '600';
      label.style.letterSpacing = '.5px';
      label.style.margin = m.from_admin ? '0 0 4px 4px' : '0 4px 4px 0';
      label.style.color = m.from_admin ? '#4f46e5' : '#0d9488';

      var bubble = document.createElement('div');
      bubble.style.background = m.from_admin ? '#eef2ff' : '#d1e7dd';
      bubble.style.padding = '10px 14px';
      bubble.style.borderRadius = '12px';
      bubble.style.maxWidth = '82%';
      bubble.style.color = '#0f172a';
      bubble.style.fontSize = '0.95rem';
      bubble.style.boxShadow = '0 2px 4px rgba(15,23,42,0.08)';
      bubble.innerHTML = esc(m.message).replace(/\n/g,'<br>');

      var meta = document.createElement('div');
      meta.style.fontSize = '0.7rem';
      meta.style.color = '#6b7280';
      meta.style.marginTop = '6px';
      meta.textContent = fmtDate(m.date_sent);

      wrapper.appendChild(label);
      wrapper.appendChild(bubble);
      wrapper.appendChild(meta);
      chatArea.appendChild(wrapper);
    });
    // Smooth scroll if new content added or explicitly requested
    if(opts.forceBottom || atBottom){
      chatArea.scrollTop = chatArea.scrollHeight;
    }
  }

  // initial render from server-provided data
  renderConversation(state.conversation);

  // polling: fetch latest conversation every 5 seconds
  var pollFailures = 0;
  var refreshBtn = document.getElementById('chat-refresh');
  function poll(){
    fetch('<?php echo site_url('user/messages'); ?>', { method: 'GET', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function(res){ return res.json(); })
      .then(function(json){
        if(json && json.status === 'ok'){
          pollFailures = 0; if(refreshBtn) refreshBtn.style.display = 'none';
          var conv = json.conversation || [];
          if(conv.length !== state.conversation.length || (conv.length && state.conversation.length && conv[conv.length-1].message !== state.conversation[state.conversation.length-1].message)){
            state.conversation = conv;
            renderConversation(conv, { forceBottom: true });
          }
        } else {
          pollFailures++; if(pollFailures >= 2 && refreshBtn) refreshBtn.style.display = 'inline-block';
        }
      })
      .catch(function(){ pollFailures++; if(pollFailures >= 2 && refreshBtn) refreshBtn.style.display = 'inline-block'; });
  }

  if(refreshBtn){
    refreshBtn.addEventListener('click', function(){
      refreshBtn.disabled = true; refreshBtn.textContent = 'Refreshing…';
      fetch('<?php echo site_url('user/messages'); ?>', { method: 'GET', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r){ return r.json(); })
        .then(function(json){
          if(json && json.status === 'ok'){
            state.conversation = json.conversation || [];
            renderConversation(state.conversation, { forceBottom: true });
            pollFailures = 0; refreshBtn.style.display = 'none';
          } else {
            refreshBtn.textContent = 'Retry';
          }
        })
        .catch(function(){ refreshBtn.textContent = 'Retry'; })
        .finally(function(){ refreshBtn.disabled = false; });
    });
  }

  state.polling = setInterval(poll, 5000);

  // send handler
  form.addEventListener('submit', function(e){
    e.preventDefault();
    var val = input.value.trim();
    if(!val) return;
    sendBtn.disabled = true; sendBtn.style.opacity = '0.6';

    var data = new FormData(); data.append('message', val);

    fetch(form.getAttribute('action'), { method: 'POST', body: data, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function(res){ return res.json(); })
      .then(function(json){
        if(json && json.status === 'ok'){
          // optimistic append
          var m = { from_admin: 0, message: json.message, date_sent: json.date_sent };
          state.conversation.push(m);
          renderConversation(state.conversation, { forceBottom: true });
          input.value = '';
        } else {
          alert('Unable to send message.');
        }
      }).catch(function(err){ console.error(err); alert('Unable to send message.'); })
      .finally(function(){ sendBtn.disabled = false; sendBtn.style.opacity = ''; });
  });

  // ensure we stop polling when navigating away
  window.addEventListener('beforeunload', function(){ if(state.polling) clearInterval(state.polling); });
})();
</script>