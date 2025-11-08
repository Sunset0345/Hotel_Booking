<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>User Panel - Blue Lagoon Hotel</title>
<style>
  /* Base styles */
  html { scroll-behavior: smooth; }
  body{ margin:0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", sans-serif; color:#fff; background:#081a2b; }
  
  /* Animations */
  @keyframes slideUp {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
  }
  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }
  @keyframes spin {
    to { transform: rotate(360deg); }
  }
  @keyframes messageSlide {
    from { transform: translateY(-10px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
  }
  
  /* Loading spinner */
  .loading-spinner {
    width: 24px;
    height: 24px;
    border: 3px solid rgba(255,255,255,0.1);
    border-top-color: #7dd3fc;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 12px;
  }
  .hero { position: fixed; inset:0; background-image: url('https://www.kayak.com.ph/rimg/himg/e1/5b/f8/expediav2-42996-f561b3-210999.jpg?width=2000&height=1200&crop=true'); background-size:cover; background-position:center; z-index:-1; }
  .hero-overlay{ position: absolute; inset:0; background: linear-gradient(180deg, rgba(3,37,65,0.45), rgba(3,37,65,0.6)); }
  .user-topbar{ position:fixed; top:0; left:0; right:0; display:flex; justify-content:flex-end; padding:0.6rem 1rem; z-index:20; }
  .logout-btn{ background:#fff; color:#036; border:0; padding:0.5rem 1rem; border-radius:6px; font-weight:700; }
  .card{ max-width:480px; margin:10vh auto; background: rgba(255,255,255,0.88); color:#0f172a; padding:2.25rem; border-radius:14px; box-shadow:0 18px 46px rgba(2,6,23,0.14); }
  h2{ margin:0 0 0.5rem 0; }
  .room-card:hover{ transform:translateY(-6px); transition:transform .18s ease; }
  .room-card{ transition:transform .18s ease; }
  /* Flash details panel */
  #roomFlash{ position:fixed; right:18px; bottom:18px; min-width:260px; max-width:360px; background:rgba(2,6,23,0.92); color:#fff; padding:14px 16px; border-radius:10px; box-shadow:0 12px 32px rgba(2,6,23,0.32); opacity:0; transform:translateY(12px) scale(.98); pointer-events:none; transition:opacity .22s ease,transform .22s ease; z-index:60; }
  #roomFlash.visible{ opacity:1; transform:translateY(0) scale(1); pointer-events:auto; }
  #roomFlash .title{ font-weight:700; color:#7dd3fc; margin-bottom:6px }
  #roomFlash .meta{ color:rgba(255,255,255,0.85); font-size:0.95rem }
  /* Modal overlay styles */
  .overlay{ position:fixed; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(2,6,23,0.6); z-index:70; opacity:0; pointer-events:none; transition:opacity .22s ease; }
  .overlay.show{ opacity:1; pointer-events:auto; }
  .room-modal{ width:min(920px,calc(100% - 48px)); background:linear-gradient(180deg,#04293f,#06324a); padding:18px; border-radius:12px; box-shadow:0 18px 46px rgba(2,6,23,0.28); transform:scale(.98); opacity:0; transition:transform .22s ease,opacity .22s ease; color:#fff; }
  .room-modal.show{ transform:scale(1); opacity:1; }
  /* Zoom clone used for morph animation */
  .zoom-clone{ position:fixed; z-index:90; box-shadow:0 18px 46px rgba(2,6,23,0.32); overflow:auto; border-radius:12px; transition: left .28s ease, top .28s ease, width .28s ease, height .28s ease, opacity .18s ease, transform .28s ease; }
  .zoom-clone .inner{ padding:16px; color:#fff; background:linear-gradient(180deg,#04293f,#06324a); height:100%; box-sizing:border-box }
  .zoom-clone img{ max-width:100%; border-radius:8px; display:block; margin-bottom:10px }
  /* dim other rooms while one is expanded (do not dim the expanded card itself) */
  .rooms-dim .room-card:not(.expanded){ opacity:0.12; filter:grayscale(.18); pointer-events:none; transition:opacity .18s ease; }
  /* expanded card appearance: box shape with black text */
  .room-card.expanded{ background:#ffffff !important; color:#000 !important; border-radius:12px; box-shadow:0 18px 46px rgba(2,6,23,0.12); overflow:auto; }
  .room-card.expanded *{ color:#000 !important; }
  .room-card.expanded img{ max-width:100%; height:auto; display:block; margin-bottom:12px; border-radius:8px; }
  /* make expanded card look like a portrait bond paper */
  .room-card.expanded{ border:1px solid #e6e6e6; padding:28px; max-width:100%; box-sizing:border-box; }
  .room-card.expanded h3{ font-size:1.25rem; margin-bottom:6px; }
  .room-card.expanded .book-link{ background:#0b74de;color:#fff !important; }
  /* layout so actions stick to bottom of expanded card */
  .room-card.expanded{ display:flex; flex-direction:column; }
  .room-card.expanded .expanded-inner{ flex:1 1 auto; overflow:auto; }
  .room-card.expanded .expanded-actions{ margin-top:auto; padding-top:12px; }
</style>
</head>
<body>
<div class="hero"><div class="hero-overlay"></div></div>
<div class="user-topbar">
  <div style="position:relative; margin-right:10px">
    <button id="profileBtn" aria-haspopup="true" aria-expanded="false" title="Account" aria-label="Open account menu" style="background:transparent;padding:0;border:0;cursor:pointer">
      <div style="width:40px;height:40px;border-radius:50%;background:#eaf4ff;display:flex;align-items:center;justify-content:center;color:#0b74de;font-weight:700;font-size:1rem;box-shadow:0 2px 6px rgba(2,6,23,0.08)">
        <?php echo htmlspecialchars(strtoupper(substr($user['full_name'],0,1))); ?>
      </div>
    </button>
    <div id="profileMenu" role="menu" aria-labelledby="profileBtn" style="position:absolute;right:0;top:calc(100% + 8px);background:#fff;color:#0b172a;border-radius:8px;box-shadow:0 8px 26px rgba(2,6,23,0.12);display:none;min-width:190px;overflow:hidden">
      <a role="menuitem" href="<?php echo site_url('user/profile'); ?>" style="display:block;padding:10px 14px;text-decoration:none;color:#0b172a;border-bottom:1px solid #f1f5f9">Profile</a>
      <a role="menuitem" href="<?php echo site_url('user/bookings'); ?>" style="display:block;padding:10px 14px;text-decoration:none;color:#0b172a;border-bottom:1px solid #f1f5f9">Bookings</a>
  <a id="profileMessagesLink" role="menuitem" href="<?php echo site_url('user/messages'); ?>" style="display:block;padding:10px 14px;text-decoration:none;color:#0b172a;border-bottom:1px solid #f1f5f9">Messages</a>
      <a role="menuitem" href="<?php echo site_url('auth/logout'); ?>" style="display:block;padding:10px 14px;text-decoration:none;color:#b91c1c">Logout</a>
    </div>
  </div>
</div>

<script>
  (function(){
    var btn = document.getElementById('profileBtn');
    var menu = document.getElementById('profileMenu');
    function openMenu(){ menu.style.display = 'block'; btn.setAttribute('aria-expanded','true'); }
    function closeMenu(){ menu.style.display = 'none'; btn.setAttribute('aria-expanded','false'); }
    btn.addEventListener('click', function(e){ e.stopPropagation(); if(menu.style.display === 'block'){ closeMenu(); } else { openMenu(); } });
    document.addEventListener('click', function(){ if(menu.style.display === 'block') closeMenu(); });
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeMenu(); });
  })();
</script>
<!-- Headings moved outside the main card as requested -->
<h2 id="user-title" style="text-align:center;margin:2.75rem 0 0.5rem 0;">Welcome to Blue Lagoon Hotel</h2>
<h3 style="text-align:center;margin:0 0 1.75rem 0;color:rgba(255,255,255,0.9)">Rooms Available for Booking</h3>

  <!-- Rooms moved to their own container outside the previous .card wrapper -->
  <div id="roomsArea" role="main" aria-labelledby="user-title" style="max-width:1200px;margin:1.5rem auto;padding:0 12px;">
    <div style="display:flex;flex-wrap:wrap;gap:18px;justify-content:center">
    <?php if(!empty($rooms)): ?>
      <?php foreach($rooms as $r): ?>
        <?php
          // Determine rendered state: if booking_state === 'booked' hide; if 'pending' show translucent; otherwise available
          $booking_state = isset($r['booking_state']) ? $r['booking_state'] : 'available';
          if($booking_state === 'booked') continue; // approved booking — hide from user listing
          $is_pending = ($booking_state === 'pending');
        ?>
    <div class="room-card<?php echo $is_pending ? ' room-pending' : ''; ?>" data-room-number="<?php echo htmlspecialchars($r['room_number'], ENT_QUOTES); ?>" data-room-type="<?php echo htmlspecialchars($r['room_type'], ENT_QUOTES); ?>" data-price="<?php echo number_format($r['price_per_night'],2); ?>" data-capacity="<?php echo intval($r['capacity']); ?>" data-description="<?php echo htmlspecialchars($r['description'] ?? '', ENT_QUOTES); ?>" data-image="<?php echo !empty($r['image']) ? htmlspecialchars(base_url() . PUBLIC_DIR . '/' . $r['image'], ENT_QUOTES) : ''; ?>" style="width:320px;background:#f8fafc;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.08);overflow:hidden;cursor:pointer;<?php echo $is_pending ? 'opacity:0.48;filter:grayscale(.18);pointer-events: none;' : ''; ?>">
          <div style="height:160px;background:#e2e8f0;">
            <?php if(!empty($r['image'])): ?>
              <img src="<?php echo base_url() . PUBLIC_DIR . '/' . $r['image']; ?>" style="width:100%;height:160px;object-fit:cover" alt="room image" />
            <?php endif; ?>
          </div>
          <div style="padding:16px">
            <strong style="font-size:1.2rem;color:#0b74de"><?php echo htmlspecialchars($r['room_number']); ?></strong>
            <div style="color:#334155;margin-bottom:6px"><?php echo htmlspecialchars($r['room_type']); ?></div>
            <div style="margin-bottom:8px">$<?php echo number_format($r['price_per_night'],2); ?> · <?php echo intval($r['capacity']); ?> guests</div>
            <?php if($is_pending): ?>
              <span style="display:inline-block;padding:8px 14px;background:#94a3b8;color:#fff;border-radius:6px;font-weight:600;opacity:0.9">Pending</span>
            <?php else: ?>
              <a href="<?php echo site_url('bookings/create/'.$r['room_id']); ?>" class="btn book-link" style="padding:8px 14px;background:#0b74de;color:#fff;border-radius:6px;text-decoration:none;font-weight:500">Book</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No rooms found.</p>
    <?php endif; ?>
    </div>
  </div>
  <!-- room details modal -->
  <div id="roomOverlay" class="overlay" aria-hidden="true" style="display:none">
    <div id="roomModal" class="room-modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
      <button id="modalClose" aria-label="Close" style="position:absolute;right:12px;top:12px;background:transparent;border:0;color:#fff;font-size:1.25rem;cursor:pointer">✕</button>
      <div style="display:flex;gap:16px;align-items:flex-start;">
        <img id="modalImg" src="" alt="" style="width:160px;height:120px;object-fit:cover;border-radius:10px;display:none;border:1px solid rgba(0,0,0,0.06)" />
        <div style="flex:1;color:#fff">
          <h3 id="modalTitle" style="margin:0 0 6px 0;color:#7dd3fc"></h3>
          <div id="modalMeta" style="color:rgba(255,255,255,0.9);margin-bottom:8px"></div>
          <div id="modalDesc" style="color:rgba(255,255,255,0.95);font-size:0.98rem;line-height:1.35;max-height:40vh;overflow:auto"></div>
          <div style="margin-top:12px"><a id="modalBook" href="#" style="display:inline-block;padding:8px 12px;background:#0b74de;color:#fff;border-radius:6px;text-decoration:none;font-weight:600">Book</a></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Slide-in chat panel (opens from right, 1/4 screen) -->
  <style>
    .chat-overlay{ position:fixed; inset:0;background:rgba(0,0,0,0.36); z-index:140; opacity:0; pointer-events:none; transition:opacity .22s ease; }
    .chat-overlay.show{ opacity:1; pointer-events:auto; }
    .chat-panel{ position:fixed; right:0; top:0; height:100%; width:25vw; min-width:320px; max-width:420px; background:#fff; box-shadow:0 24px 48px rgba(2,6,23,0.24); transform:translateX(110%); transition:transform .32s ease; z-index:150; display:flex; flex-direction:column; }
    .chat-panel.open{ transform:translateX(0); }
    .chat-panel .chat-header{ padding:12px 14px; border-bottom:1px solid #eee; display:flex; align-items:center; justify-content:space-between; }
    .chat-panel .chat-messages{ padding:12px; overflow:auto; flex:1 1 auto; background:#f7fafc; }
    .chat-panel .chat-input{ padding:10px; border-top:1px solid #eee; display:flex; gap:8px; }
    .chat-panel .bubble{ display:inline-block; padding:8px 12px; border-radius:12px; max-width:80%; margin-bottom:8px; }
    .bubble.me{ background:#d1e7dd; align-self:flex-end; }
    .bubble.them{ background:#eef2ff; align-self:flex-start; }
    /* Enhanced booking panel with animations */
    .booking-overlay{ position:fixed; inset:0; z-index:155; opacity:0; pointer-events:none; transition:opacity .32s ease; }
    .booking-overlay.show{ opacity:1; pointer-events:auto; }
    .booking-panel{ position:fixed; inset:0; background:linear-gradient(180deg, rgba(3,37,65,0.75), rgba(3,37,65,0.85)); transform:translateY(100%); transition:all .42s cubic-bezier(0.4, 0.0, 0.2, 1); z-index:160; display:flex; flex-direction:column; }
    .booking-panel.open{ transform:translateY(0); }
    .booking-panel.open .booking-header{ transform:translateY(0); opacity:1; }
    .booking-panel.open .booking-content{ transform:translateY(0); opacity:1; }
    .booking-panel .booking-header{ padding:18px 24px; display:flex; align-items:center; justify-content:space-between; color:#fff; transform:translateY(-20px); opacity:0; transition:all .32s .1s cubic-bezier(0.4, 0.0, 0.2, 1); }
    .booking-panel .booking-title{ font-size:1.5rem; font-weight:600; }
    .booking-panel .booking-content{ padding:24px; overflow:auto; flex:1 1 auto; transform:translateY(30px); opacity:0; transition:all .32s .15s cubic-bezier(0.4, 0.0, 0.2, 1); }
    .booking-panel .booking-body{ max-width:760px; margin:0 auto; }
    /* Styled form inputs to match theme */
    .booking-panel form{ color:#fff; }
    .booking-panel .form-group{ margin-bottom:24px; }
    .booking-panel label{ display:block; font-weight:500; margin-bottom:8px; color:rgba(255,255,255,0.9); }
    .booking-panel input[type="date"],
    .booking-panel input[type="number"]{ 
      width:100%; 
      padding:12px; 
      background:rgba(255,255,255,0.12); 
      border:1px solid rgba(255,255,255,0.2);
      border-radius:8px;
      color:#fff;
      font-size:1rem;
      transition:all .2s ease;
    }
    .booking-panel input:focus{
      outline:none;
      background:rgba(255,255,255,0.16);
      border-color:rgba(255,255,255,0.4);
    }
    .booking-panel input::placeholder{ color:rgba(255,255,255,0.5); }
    .booking-panel .room-info{
      margin-bottom:28px;
      padding:16px 20px;
      background:rgba(255,255,255,0.1);
      border-radius:12px;
      color:rgba(255,255,255,0.9);
    }
    .booking-panel .room-info strong{ color:#7dd3fc; }
    .booking-panel button[type="submit"]{
      width:100%;
      padding:14px;
      background:#0b74de;
      color:#fff;
      border:0;
      border-radius:8px;
      font-size:1rem;
      font-weight:600;
      cursor:pointer;
      transition:all .2s ease;
    }
    .booking-panel button[type="submit"]:hover{ background:#0957a8; }
    .booking-panel button[type="submit"]:disabled{ background:#0957a8; opacity:0.7; cursor:not-allowed; }
    /* Success/error messages */
    .booking-panel .message{
      padding:16px;
      border-radius:10px;
      margin-bottom:24px;
      animation:messageSlide .3s ease;
    }
    .booking-panel .message.success{ background:rgba(45,212,191,0.15); color:#5eead4; }
    .booking-panel .message.error{ background:rgba(239,68,68,0.15); color:#fca5a5; }
    @keyframes messageSlide{
      from{ transform:translateY(-10px); opacity:0; }
      to{ transform:translateY(0); opacity:1; }
    }
  </style>

  <div id="chatOverlay" class="chat-overlay" aria-hidden="true"></div>
  <aside id="chatPanel" class="chat-panel" aria-hidden="true">
    <div class="chat-header">
      <div style="font-weight:700">Messages</div>
      <div>
        <button id="chatHistory" aria-label="Open history" title="History" style="background:transparent;border:0;margin-right:8px;cursor:pointer">📅</button>
        <button id="chatClose" aria-label="Close chat" style="background:transparent;border:0;font-size:1.25rem;cursor:pointer">✕</button>
      </div>
    </div>
    <div id="chatMessages" class="chat-messages" aria-live="polite"></div>
  <form id="chatForm" class="chat-input" method="post" action="<?php echo base_url('user/messages'); ?>">
      <input id="chatInput" name="message" type="text" placeholder="Write a message..." style="flex:1;padding:8px;border-radius:8px;border:1px solid #e5e7eb;" autocomplete="off" />
      <button id="chatSend" type="submit" style="padding:8px 12px;border-radius:8px;border:0;background:#0b74de;color:#fff">Send</button>
    </form>
  </aside>

  <!-- Full-screen booking slide panel (slides from right, shows hero background underneath) -->
  <div id="bookingOverlay" class="booking-overlay" aria-hidden="true"></div>
  <aside id="bookingPanel" class="booking-panel" aria-hidden="true">
    <div class="booking-header">
      <div style="font-weight:700">Book Room</div>
      <div>
        <button id="bookingClose" aria-label="Close booking" style="background:transparent;border:0;font-size:1.25rem;color:#fff;cursor:pointer">✕</button>
      </div>
    </div>
    <div class="booking-content">
      <div id="bookingInner" class="booking-body">
        <div style="padding:12px;color:rgba(255,255,255,0.9)">Loading&hellip;</div>
      </div>
    </div>
  </aside>

  <script>
    (function(){
      var overlay = document.getElementById('roomOverlay');
      var modal = document.getElementById('roomModal');
      var modalTitle = document.getElementById('modalTitle');
      var modalMeta = document.getElementById('modalMeta');
      var modalDesc = document.getElementById('modalDesc');
      var modalImg = document.getElementById('modalImg');
      var modalBook = document.getElementById('modalBook');
      var modalClose = document.getElementById('modalClose');

      function openModal(data){
        modalTitle.textContent = data.roomNumber + (data.roomType ? ' — ' + data.roomType : '');
        modalMeta.textContent = '$' + data.price + ' · ' + data.capacity + ' guests';
        modalDesc.textContent = data.description || '';
        if(data.image){ modalImg.src = data.image; modalImg.style.display = 'block'; } else { modalImg.style.display = 'none'; modalImg.removeAttribute('src'); }
        modalBook.setAttribute('href', data.bookUrl || '#');
        overlay.style.display = 'block';
        // small delay to allow CSS transition
        requestAnimationFrame(function(){ overlay.classList.add('show'); modal.classList.add('show'); overlay.setAttribute('aria-hidden','false'); });
        // prevent background scroll
        document.documentElement.style.overflow = 'hidden';
      }

      function closeModal(){
        overlay.classList.remove('show'); modal.classList.remove('show'); overlay.setAttribute('aria-hidden','true');
        document.documentElement.style.overflow = ''; // restore
        // wait for transition then hide
        setTimeout(function(){ overlay.style.display = 'none'; }, 260);
      }

      document.addEventListener('DOMContentLoaded', function(){
        var cards = document.querySelectorAll('.room-card');
        cards.forEach(function(c){
          c.addEventListener('click', function(e){
              var target = e.target;
              if(target.closest && target.closest('.book-link')) return; // follow book link
              var data = {
                roomNumber: c.getAttribute('data-room-number') || 'Room',
                roomType: c.getAttribute('data-room-type') || '',
                price: c.getAttribute('data-price') || '0.00',
                capacity: c.getAttribute('data-capacity') || '1',
                description: c.getAttribute('data-description') || '',
                image: c.getAttribute('data-image') || '',
                bookUrl: (function(){ var a = c.querySelector('.book-link'); return a ? a.getAttribute('href') : '#'; })()
              };
              morphOpen(c, data);
            });
        });

        // close handlers
        overlay.addEventListener('click', function(e){ if(e.target === overlay) closeModal(); });
        modalClose.addEventListener('click', closeModal);
        document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeModal(); });

        // Slide-in chat panel handlers
        var profileMessagesLink = document.getElementById('profileMessagesLink');
        var chatPanel = document.getElementById('chatPanel');
        var chatOverlay = document.getElementById('chatOverlay');
        var chatMessages = document.getElementById('chatMessages');
        var chatForm = document.getElementById('chatForm');
        var chatInput = document.getElementById('chatInput');
        var chatClose = document.getElementById('chatClose');

        function renderConversation(arr){
          chatMessages.innerHTML = '';
          if(!arr || !arr.length){
            chatMessages.innerHTML = '<div style="padding:18px;color:#666">No messages yet. Start a conversation with admin.</div>';
            chatMessages.scrollTop = chatMessages.scrollHeight;
            return;
          }
          arr.forEach(function(m){
            var d = document.createElement('div');
            d.style.display = 'flex';
            d.style.flexDirection = 'column';
            var bubble = document.createElement('div');
            bubble.className = 'bubble ' + (m.from_admin ? 'them' : 'me');
            bubble.innerHTML = escapeHtml(m.message).replace(/\n/g, '<br>');
            var meta = document.createElement('div');
            meta.style.fontSize = '0.75rem'; meta.style.color = '#666'; meta.style.marginTop = '6px';
            meta.textContent = (m.from_admin ? 'Admin' : (m.full_name || 'You')) + ' — ' + (m.date_sent || '');
            d.appendChild(bubble);
            d.appendChild(meta);
            // align
            bubble.style.alignSelf = m.from_admin ? 'flex-start' : 'flex-end';
            chatMessages.appendChild(d);
          });
          chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function openChat(){
          chatPanel.classList.add('open'); chatPanel.setAttribute('aria-hidden','false');
          chatOverlay.classList.add('show'); chatOverlay.setAttribute('aria-hidden','false');
              // fetch conversation via AJAX GET (default: full conversation)
              fetch('<?php echo base_url('user/messages'); ?>', { method: 'GET', credentials: 'same-origin', headers: { 'X-Requested-With':'XMLHttpRequest' } })
                .then(function(r){ return r.json(); })
                .then(function(j){ if(j && j.status === 'ok'){ renderConversation(j.conversation || []); } else { renderConversation([]); } })
                .catch(function(err){ console.error('chat load error',err); renderConversation([]); });
        }

        function closeChat(){ chatPanel.classList.remove('open'); chatPanel.setAttribute('aria-hidden','true'); chatOverlay.classList.remove('show'); chatOverlay.setAttribute('aria-hidden','true'); }

        if(profileMessagesLink){
          profileMessagesLink.addEventListener('click', function(e){ e.preventDefault(); openChat(); });
        }
        // history button: fetch last 12 weeks and show a simple selector
        var chatHistoryBtn = document.getElementById('chatHistory');
        function showWeeks(){
          chatMessages.innerHTML = '<div style="padding:12px;color:#666">Loading history&hellip;</div>';
          fetch('<?php echo base_url('user/messages'); ?>?weeks=1', { method: 'GET', credentials: 'same-origin', headers: { 'X-Requested-With':'XMLHttpRequest' } })
              .then(function(r){ return r.text(); })
              .then(function(txt){
                try{
                  var j = JSON.parse(txt);
                }catch(err){
                  chatMessages.innerHTML = '<div style="padding:12px;color:#c00">Unable to load history — server returned unexpected response:<pre style="white-space:pre-wrap;color:#fff;background:#111;padding:8px;border-radius:6px;margin-top:8px">'+escapeHtml(txt)+'</pre></div>';
                  return;
                }
                if(!j || j.status !== 'ok' || !j.weeks) { chatMessages.innerHTML = '<div style="padding:12px;color:#666">Unable to load history.</div>'; return; }
                var html = '<div style="padding:8px;">';
                html += '<div style="font-weight:700;margin-bottom:8px">Select week</div>';
                j.weeks.forEach(function(w){ html += '<button data-start="'+w.start+'" style="display:block;width:100%;text-align:left;padding:8px;border-radius:6px;border:1px solid #eee;margin-bottom:6px;background:#fff">'+w.label+' ('+w.count+')</button>'; });
                html += '</div>';
                chatMessages.innerHTML = html;
                // attach handlers
                Array.prototype.slice.call(chatMessages.querySelectorAll('button[data-start]')).forEach(function(b){ b.addEventListener('click', function(){ var s = this.getAttribute('data-start'); // fetch conversation for that week
                    chatMessages.innerHTML = '<div style="padding:12px;color:#666">Loading messages for '+s+'&hellip;</div>';
                    fetch('<?php echo base_url('user/messages'); ?>?week_start='+encodeURIComponent(s), { method: 'GET', credentials: 'same-origin', headers: { 'X-Requested-With':'XMLHttpRequest' } })
                      .then(function(r){ return r.text(); })
                      .then(function(t){
                        try{ var res = JSON.parse(t); } catch(e){ chatMessages.innerHTML = '<div style="padding:12px;color:#c00">Unable to load messages — server returned unexpected response:<pre style="white-space:pre-wrap;color:#fff;background:#111;padding:8px;border-radius:6px;margin-top:8px">'+escapeHtml(t)+'</pre></div>'; return; }
                        if(res && res.status === 'ok'){ renderConversation(res.conversation || []); } else { chatMessages.innerHTML = '<div style="padding:12px;color:#666">No messages for this week.</div>'; }
                      })
                      .catch(function(){ chatMessages.innerHTML = '<div style="padding:12px;color:#666">Unable to load messages.</div>'; });
                  }); });
              }).catch(function(err){ chatMessages.innerHTML = '<div style="padding:12px;color:#666">Unable to load history.</div>'; });
        }
        if(chatHistoryBtn){ chatHistoryBtn.addEventListener('click', function(e){ e.preventDefault(); openChat(); showWeeks(); }); }
        chatOverlay.addEventListener('click', closeChat);
        chatClose.addEventListener('click', closeChat);
        document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeChat(); });

        // chat send handler (AJAX POST) - reuse /user/messages endpoint which returns JSON for AJAX POST
        chatForm.addEventListener('submit', function(e){
          e.preventDefault();
          var val = chatInput.value.trim(); if(!val) return; chatSend.disabled = true; chatSend.style.opacity = '0.6';
          var data = new FormData(); data.append('message', val);
          fetch(chatForm.getAttribute('action'), { method: 'POST', credentials: 'same-origin', body: data, headers: { 'X-Requested-With':'XMLHttpRequest' } })
            .then(function(r){ return r.json(); })
            .then(function(json){ if(json && json.status === 'ok'){ // append message
                // server returns date_sent
                var m = { from_admin:0, message: json.message, date_sent: json.date_sent, full_name: '<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES); ?>' };
                renderConversation((function(){ var nodes = chatMessages.querySelectorAll('.bubble'); var arr = []; try{ /* preserve existing appended items by reading DOM? simpler: just append new */ }catch(e){} return []; })());
                // simple append
                var wrapper = document.createElement('div'); wrapper.style.display='flex'; wrapper.style.flexDirection='column';
                var bubble = document.createElement('div'); bubble.className='bubble me'; bubble.innerHTML = escapeHtml(m.message).replace(/\n/g,'<br>'); bubble.style.alignSelf='flex-end';
                var meta = document.createElement('div'); meta.style.fontSize='0.75rem'; meta.style.color='#666'; meta.style.marginTop='6px'; meta.textContent = (m.full_name || 'You') + ' — ' + (m.date_sent || '');
                wrapper.appendChild(bubble); wrapper.appendChild(meta); chatMessages.appendChild(wrapper); chatMessages.scrollTop = chatMessages.scrollHeight; chatInput.value='';
              } else { alert('Unable to send message'); } })
            .catch(function(err){ console.error(err); alert('Unable to send message'); })
            .finally(function(){ chatSend.disabled = false; chatSend.style.opacity=''; });
        });

        // Booking panel handlers
        var bookingOverlay = document.getElementById('bookingOverlay');
        var bookingPanel = document.getElementById('bookingPanel');
        var bookingInner = document.getElementById('bookingInner');
        var bookingClose = document.getElementById('bookingClose');

        function openBookingPanel(url){
          if(!url) return;
          
          // Prevent background scrolling
          document.documentElement.style.overflow = 'hidden';
          
          // Set up animation elements 
          bookingOverlay.classList.add('show');
          bookingOverlay.setAttribute('aria-hidden', 'false');
          
          requestAnimationFrame(() => {
            bookingPanel.classList.add('open');
            bookingPanel.setAttribute('aria-hidden', 'false');
            
            // Trigger panel content animation after panel starts sliding up
            setTimeout(() => {
              bookingPanel.querySelector('.booking-header').style.opacity = '1';
              bookingPanel.querySelector('.booking-header').style.transform = 'translateY(0)';
              bookingPanel.querySelector('.booking-content').style.opacity = '1';
              bookingPanel.querySelector('.booking-content').style.transform = 'translateY(0)';
            }, 100);
          });
          
          // Set initial loading state with spinner
          bookingInner.innerHTML = '<div style="padding:24px;text-align:center;color:rgba(255,255,255,0.7)"><div class="loading-spinner"></div>Loading booking form...</div>';
          
          // Fetch booking form content
          fetch(url, { method: 'GET', credentials: 'same-origin', headers: { 'X-Requested-With':'XMLHttpRequest' } })
            .then(function(r){ return r.json(); })
            .then(function(json){
              if(!json || json.status !== 'ok' || !json.html){ bookingInner.innerHTML = '<div style="padding:12px;color:#c00">Unable to load booking form.</div>'; return; }
              bookingInner.innerHTML = json.html;
              // attach submit handler
              var form = bookingInner.querySelector('form');
              if(form){
                form.addEventListener('submit', function(ev){
                  ev.preventDefault();
                  var submitBtn = form.querySelector('button[type="submit"]');
                  if(submitBtn) { submitBtn.disabled = true; submitBtn.style.opacity = '0.6'; }
                  var fd = new FormData(form);
                  fetch(form.getAttribute('action'), { method: 'POST', credentials: 'same-origin', body: fd, headers: { 'X-Requested-With':'XMLHttpRequest' } })
                    .then(function(r){ return r.json(); })
                    .then(function(resp){
                      if(!resp){ bookingInner.innerHTML = '<div style="padding:12px;background:#fee2e2;color:#7f1d1d;border-radius:8px">Unexpected server response</div>'; return; }
                      if(resp.status === 'ok'){
                        bookingInner.innerHTML = '<div style="padding:12px;background:#d1fae5;color:#064e3b;border-radius:8px">Booking successful — reference #' + (resp.booking_id || '') + '</div>';
                        setTimeout(closeBookingPanel, 1000);
                      } else if(resp.status === 'login_required'){
                        window.location.href = '<?php echo site_url('auth/login'); ?>';
                      } else {
                        var msg = resp.message || 'Unable to create booking';
                        bookingInner.innerHTML = '<div style="padding:12px;background:#fee2e2;color:#7f1d1d;border-radius:8px">' + msg + '</div>';
                      }
                    })
                    .catch(function(){ bookingInner.innerHTML = '<div style="padding:12px;background:#fee2e2;color:#7f1d1d;border-radius:8px">Unable to create booking</div>'; })
                    .finally(function(){ if(submitBtn) { submitBtn.disabled = false; submitBtn.style.opacity = ''; } });
                });
              }
            })
            .catch(function(){ bookingInner.innerHTML = '<div style="padding:12px;color:#c00">Unable to load booking form</div>'; });
        }

        function closeBookingPanel(){
          // Remove open class first to start exit animations
          bookingPanel.classList.remove('open');
          bookingPanel.setAttribute('aria-hidden', 'true');
          
          // Delay overlay fade out slightly
          setTimeout(() => {
            bookingOverlay.classList.remove('show');
            bookingOverlay.setAttribute('aria-hidden', 'true');
          }, 200);
          
          // Restore scrolling
          document.documentElement.style.overflow = '';
          
          // Reset content after animations complete
          setTimeout(() => {
            bookingInner.innerHTML = '<div style="padding:24px;text-align:center;color:rgba(255,255,255,0.7)"><div class="loading-spinner"></div>Ready to book</div>';
          }, 420);
        }

        bookingOverlay.addEventListener('click', closeBookingPanel);
        bookingClose.addEventListener('click', closeBookingPanel);

        // Intercept any clicks on .book-link anchors and open booking panel instead of navigating
        document.addEventListener('click', function(e){
          var a = e.target.closest && e.target.closest('.book-link');
          if(a){ e.preventDefault(); var href = a.getAttribute('href'); openBookingPanel(href); }
        });

        function escapeHtml(s){ if(!s) return ''; return s.replace(/[&<>\"]/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }

        // Morph / zoom handlers: clone card, animate to center, and allow closing
        function morphOpen(cardEl, data){
          // Toggle behavior: if already expanded, collapse on click; if in the process of expanding, ignore
          if(cardEl.classList.contains('expanded')){
            // simulate close button click if present
            var existingClose = cardEl.querySelector('button[aria-label="Close"]');
            if(existingClose) { existingClose.click(); }
            return;
          }
          if(cardEl.getAttribute('data-expanded') === '1') return;
          // expand the actual card element in-place (no clone)
          var rect = cardEl.getBoundingClientRect();
          var roomsArea = document.getElementById('roomsArea');
          // create placeholder to keep layout
          var placeholder = document.createElement('div');
          placeholder.style.width = rect.width + 'px';
          placeholder.style.height = rect.height + 'px';
          placeholder.style.display = 'block';
          placeholder.style.flex = '0 0 auto';
          cardEl.parentNode.insertBefore(placeholder, cardEl.nextSibling);

          // save original inline style to restore later
          var origStyle = cardEl.getAttribute('style') || '';
          // promote card to fixed so it can overlay siblings
          cardEl.style.position = 'fixed';
          cardEl.style.left = rect.left + 'px';
          cardEl.style.top = rect.top + 'px';
          cardEl.style.width = rect.width + 'px';
          cardEl.style.height = rect.height + 'px';
          cardEl.style.margin = '0';
          cardEl.style.zIndex = '120';
          cardEl.style.transition = 'left .28s ease, top .28s ease, width .28s ease, height .28s ease, transform .28s ease, opacity .18s ease';

          // mark as expanding to avoid double-open
          cardEl.setAttribute('data-expanded','1');
          // dim other room cards
          if(roomsArea) roomsArea.classList.add('rooms-dim');

          // compute target size & position (portrait 'bondpaper' aspect ratio)
          var maxW = Math.min(920, window.innerWidth - 48);
          var maxH = Math.min(window.innerHeight - 96, Math.max(360, Math.round(window.innerHeight * 0.9)));
          // portrait aspect ratio (A4-like ~ sqrt(2):1 height/width)
          var ratio = 1.41421356237;
          // prefer using most of the viewport height, then derive width to match portrait ratio
          var desiredH = Math.min(maxH, Math.round(window.innerHeight * 0.85));
          var desiredW = Math.round(desiredH / ratio);
          // ensure desiredW does not exceed maxW
          if(desiredW > maxW){ desiredW = maxW; desiredH = Math.round(desiredW * ratio); }
          // also ensure we don't end up smaller than the card
          var targetW = Math.max(Math.min(desiredW, maxW), rect.width);
          var targetH = Math.max(Math.min(desiredH, maxH), rect.height);
          // center on viewport for a 'paper' look
          var targetLeft = Math.round((window.innerWidth - targetW) / 2);
          var targetTop = Math.round((window.innerHeight - targetH) / 2);
          targetLeft = Math.max(12, Math.min(targetLeft, window.innerWidth - targetW - 12));
          targetTop = Math.max(12, Math.min(targetTop, window.innerHeight - targetH - 12));


          // capture original inner HTML so we can restore it later
          var origInner = cardEl.innerHTML;
          // add a close button inside the card (so it's part of the real element)
          var closeBtn = document.createElement('button');
          closeBtn.innerHTML = '✕';
          closeBtn.setAttribute('aria-label','Close');
          closeBtn.style.cssText = 'position:absolute;right:10px;top:10px;background:transparent;border:0;color:#000;font-size:1.25rem;cursor:pointer;z-index:130';

          // build expanded content using admin-provided data and any dataset entries
          var expandedInner = document.createElement('div');
          expandedInner.className = 'expanded-inner';
          expandedInner.style.padding = '16px';
          expandedInner.style.display = 'block';
          expandedInner.style.boxSizing = 'border-box';
          // image
          if(data.image){ var expImg = document.createElement('img'); expImg.src = data.image; expImg.alt = data.roomNumber || 'Room image'; expandedInner.appendChild(expImg); }
          // title
          var titleEl = document.createElement('h3'); titleEl.textContent = data.roomNumber + (data.roomType ? ' — ' + data.roomType : ''); titleEl.style.margin = '0 0 8px 0'; titleEl.style.color = '#000'; expandedInner.appendChild(titleEl);
          // meta
          var metaEl = document.createElement('div'); metaEl.textContent = '$' + data.price + ' · ' + data.capacity + ' guests'; metaEl.style.marginBottom = '8px'; metaEl.style.color = '#111'; expandedInner.appendChild(metaEl);
          // description (full)
          var descEl = document.createElement('div'); descEl.innerHTML = data.description ? data.description : ''; descEl.style.color = '#111'; descEl.style.fontSize = '0.98rem'; descEl.style.lineHeight = '1.35'; descEl.style.maxHeight = '60vh'; descEl.style.overflow = 'auto'; expandedInner.appendChild(descEl);
          // list other dataset entries (if any) except those already shown
          var shown = { roomNumber:1, roomType:1, price:1, capacity:1, description:1, image:1, expanded:1 };
          var keys = Object.keys(cardEl.dataset || {});
          keys.forEach(function(k){ if(!shown[k]){ var row = document.createElement('div'); row.style.marginTop = '10px'; row.innerHTML = '<strong>' + k.replace(/([A-Z])/g, ' $1') + ':</strong> ' + cardEl.dataset[k]; expandedInner.appendChild(row); } });
          // actions - create a real button inside the expanded card that navigates to booking
          var actions = document.createElement('div'); actions.className = 'expanded-actions'; actions.style.marginTop = '12px';
          actions.style.display = 'block';
          // ensure actions sit at the bottom of the expanded card
          actions.style.marginTop = 'auto';
          actions.style.boxSizing = 'border-box';
          actions.style.width = '100%';
          var bookBtn = document.createElement('button');
          bookBtn.type = 'button';
          bookBtn.className = 'book-link';
          bookBtn.textContent = 'Book';
          bookBtn.style.cssText = 'display:inline-block;padding:10px 14px;background:#0b74de;color:#fff;border-radius:6px;border:0;cursor:pointer;font-weight:700';
          bookBtn.addEventListener('click', function(evt){
            evt.stopPropagation();
            if(!data.bookUrl || data.bookUrl === '#') return;
            openBookingPanel(data.bookUrl);
          });
          actions.appendChild(bookBtn);

          // replace card content with expanded content; append actions outside the scrollable inner so it sits at the very bottom
          cardEl.innerHTML = '';
          cardEl.appendChild(expandedInner);
          cardEl.appendChild(actions);
          cardEl.appendChild(closeBtn);

          // expand animation
          requestAnimationFrame(function(){
            cardEl.style.left = targetLeft + 'px';
            cardEl.style.top = targetTop + 'px';
            cardEl.style.width = targetW + 'px';
            cardEl.style.height = targetH + 'px';
            document.documentElement.style.overflow = 'hidden';
            cardEl.classList.add('expanded');
          });

          // close logic
          function cleanup(){
            cardEl.style.left = rect.left + 'px';
            cardEl.style.top = rect.top + 'px';
            cardEl.style.width = rect.width + 'px';
            cardEl.style.height = rect.height + 'px';
            cardEl.classList.remove('expanded');
            setTimeout(function(){
              // restore original inline styles
              if(origStyle) cardEl.setAttribute('style', origStyle); else cardEl.removeAttribute('style');
              // restore original inner HTML
              try { cardEl.innerHTML = origInner; } catch(err) { /* noop */ }
              // remove placeholder
              if(placeholder && placeholder.parentNode) placeholder.parentNode.removeChild(placeholder);
              // remove dim
              if(roomsArea) roomsArea.classList.remove('rooms-dim');
              // remove close button if still present (origInner should have restored original markup)
              if(closeBtn && closeBtn.parentNode) closeBtn.parentNode.removeChild(closeBtn);
              document.documentElement.style.overflow = '';
            }, 300);
            // clear expanded marker
            cardEl.removeAttribute('data-expanded');
            document.removeEventListener('keydown', escHandler);
            document.removeEventListener('click', outsideClick);
          }

          function escHandler(e){ if(e.key === 'Escape') cleanup(); }
          function outsideClick(e){ if(!cardEl.contains(e.target) && !e.target.closest('.book-link')) cleanup(); }
          // attach handlers
          document.addEventListener('keydown', escHandler);
          // delay adding outside click to avoid immediately closing from the opening click
          setTimeout(function(){ document.addEventListener('click', outsideClick); }, 20);
          closeBtn.addEventListener('click', function(e){ e.stopPropagation(); cleanup(); });
        }
      });
    })();
  </script>

  </body>
  </html>