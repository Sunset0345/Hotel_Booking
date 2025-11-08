<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<div style="max-width:980px;margin:6rem auto;padding:18px;background:#fff;border-radius:10px;color:#111">
  <h2 style="margin-top:0">Messages</h2>
  <div style="display:flex;flex-direction:column;gap:12px">
  <?php if(!empty($error)): ?>
    <div style="padding:12px;border-radius:8px;background:#fee2e2;color:#7f1d1d;border:1px solid #fca5a5"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  <div id="conversationBox" style="max-height:420px;overflow:auto;padding:12px;border:1px solid #eee;border-radius:8px;background:#f8fafc">
      <?php if(empty($conversation)): ?>
        <div style="color:#666">No messages yet. Use the form below to contact admin.</div>
      <?php else: ?>
        <?php foreach($conversation as $m): ?>
          <div style="margin-bottom:12px;display:flex;flex-direction:column;align-items:<?php echo $m['from_admin'] ? 'flex-start' : 'flex-end'; ?>">
            <div style="background:<?php echo $m['from_admin'] ? '#eef2ff' : '#d1e7dd'; ?>;padding:8px 12px;border-radius:10px;max-width:80%;">
              <div style="font-size:0.95rem;color:#0f172a"><?php echo htmlspecialchars($m['message']); ?></div>
              <div style="font-size:0.8rem;color:#666;margin-top:6px"><?php echo htmlspecialchars($m['date_sent']); ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <form id="messageForm" method="post" action="<?php echo site_url('user/messages'); ?>">
      <textarea id="messageInput" name="message" rows="3" style="width:100%;padding:8px;border-radius:6px;border:1px solid #e5e7eb;" placeholder="Write a message to admin..."></textarea>
      <div style="text-align:right;margin-top:8px">
        <button id="sendBtn" type="submit" style="background:#0b74de;color:#fff;padding:8px 12px;border-radius:6px;border:0">Send</button>
      </div>
    </form>

    <script>
      (function(){
        var form = document.getElementById('messageForm');
        var input = document.getElementById('messageInput');
        var box = document.getElementById('conversationBox');
        var sendBtn = document.getElementById('sendBtn');

        function appendMessage(text, dateStr){
          var wrapper = document.createElement('div');
          wrapper.style.marginBottom = '12px';
          wrapper.style.display = 'flex';
          wrapper.style.flexDirection = 'column';
          wrapper.style.alignItems = 'flex-end';

          var inner = document.createElement('div');
          inner.style.background = '#d1e7dd';
          inner.style.padding = '8px 12px';
          inner.style.borderRadius = '10px';
          inner.style.maxWidth = '80%';
          inner.style.fontSize = '0.95rem';
          inner.style.color = '#0f172a';
          inner.textContent = text;

          var dt = document.createElement('div');
          dt.style.fontSize = '0.8rem';
          dt.style.color = '#666';
          dt.style.marginTop = '6px';
          dt.textContent = dateStr || '';

          inner.appendChild(dt);
          wrapper.appendChild(inner);
          box.appendChild(wrapper);
          // scroll to bottom
          box.scrollTop = box.scrollHeight;
        }

        form.addEventListener('submit', function(e){
          e.preventDefault();
          var val = input.value.trim();
          if(!val) return;
          // disable while sending
          sendBtn.disabled = true;
          sendBtn.style.opacity = '0.6';

          var data = new FormData();
          data.append('message', val);

          fetch(form.getAttribute('action'), {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          }).then(function(res){
            return res.json();
          }).then(function(json){
            if(json && json.status === 'ok'){
              appendMessage(json.message, json.date_sent);
              input.value = '';
            } else {
              alert('Unable to send message.');
            }
          }).catch(function(err){
            console.error(err);
            // fallback to normal form submit if AJAX failed
            try{ form.submit(); } catch(e){ alert('Unable to send message.'); }
          }).finally(function(){
            sendBtn.disabled = false;
            sendBtn.style.opacity = '';
          });
        });

        // ensure conversation is scrolled to bottom on load
        try{ box.scrollTop = box.scrollHeight; }catch(e){}
      })();
    </script>
  </div>
</div>