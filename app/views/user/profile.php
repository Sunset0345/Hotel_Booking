<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<!-- Hero background to match other pages -->
<div style="position:fixed;inset:0;z-index:-1;background-image:url('https://www.kayak.com.ph/rimg/himg/e1/5b/f8/expediav2-42996-f561b3-210999.jpg?width=2000&height=1200&crop=true');background-size:cover;background-position:center;">
  <div style="position:absolute;inset:0;background:linear-gradient(180deg, rgba(3,37,65,0.45), rgba(3,37,65,0.6));"></div>
</div>
<div style="max-width:920px;margin:6rem auto;padding:18px;background:#fff;border-radius:10px;color:#111;position:relative">
  <div style="margin-bottom:10px;">
    <button type="button" onclick="goBack()" style="background:transparent;border:1px solid #ddd;padding:6px 10px;border-radius:6px;cursor:pointer;font-size:0.95rem">← Back</button>
  </div>
  
  <div style="display:flex;gap:18px;align-items:center">
    <div>
      <!-- Clickable avatar: opens modal to choose upload or camera -->
      <button type="button" onclick="openAvatarModal()" style="background:none;border:none;padding:0;cursor:pointer">
      <?php if(!empty($user['avatar']) && is_file(PUBLIC_DIR . '/' . $user['avatar'])): ?>
        <img src="<?php echo base_url() . PUBLIC_DIR . '/' . $user['avatar']; ?>" style="width:112px;height:112px;border-radius:999px;object-fit:cover;border:3px solid #fff;box-shadow:0 6px 18px rgba(0,0,0,0.12);transform:translateY(-56px)" alt="avatar" />
      <?php else: ?>
        <div style="width:112px;height:112px;border-radius:999px;background:#e6eefc;display:flex;align-items:center;justify-content:center;font-weight:700;color:#0b74de;font-size:2rem;transform:translateY(-56px)">
          <?php echo strtoupper(substr($user['full_name'],0,1)); ?>
        </div>
      <?php endif; ?>
      </button>
    </div>
    <div style="display:flex;flex-direction:column;gap:6px">
      <div style="display:flex;align-items:center;gap:10px">
          <div style="font-weight:700;font-size:1.1rem;display:flex;align-items:center;gap:8px">
          <span id="displayFullName"><?php echo htmlspecialchars($user['full_name']); ?></span>
          <?php
            $verified = !empty($user['is_verified']) ? true : false;
            $pending = (!$verified && !empty($user['verification_requested'])) ? true : false;
          ?>
          <?php if($verified): ?>
            <span id="verificationBadge" title="Verified" style="display:inline-block;background:#0b7a3a;color:#fff;padding:3px 7px;border-radius:999px;font-size:0.75rem">Verified</span>
          <?php elseif($pending): ?>
            <span id="verificationBadge" title="Verification pending" style="display:inline-block;background:#b65d00;color:#fff;padding:3px 7px;border-radius:999px;font-size:0.75rem">Pending</span>
          <?php else: ?>
            <span id="verificationBadge" title="Not verified" style="display:inline-block;background:#888;color:#fff;padding:3px 7px;border-radius:999px;font-size:0.75rem">Not verified</span>
          <?php endif; ?>
        </div>
          <div style="display:flex;align-items:center;gap:8px">
          <button type="button" id="editProfileBtn" onclick="toggleEditProfile()" tabindex="0" role="button" style="position:relative;z-index:1002;cursor:pointer;outline:transparent;background:#0b74de;color:#fff;padding:6px 10px;border-radius:6px;border:none;font-size:0.9rem">Edit profile</button>
          <?php if(empty($verified)): ?>
            <button type="button" id="verifyNowTopBtn" onclick="showVerificationUI()" tabindex="0" role="button" style="position:relative;z-index:1002;cursor:pointer;pointer-events:auto;outline:2px solid rgba(11,116,58,0.15);background:#0b7a3a;color:#fff;padding:6px 10px;border-radius:6px;border:none;font-size:0.9rem">Verify now</button>
          <?php endif; ?>
        </div>
      </div>
      <div id="displayEmail" style="color:#666"><?php echo htmlspecialchars($user['email']); ?></div>
      <?php if(!empty($user['bio'])): ?><div style="margin-top:8px;color:#333;max-width:650px"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></div><?php endif; ?>
    </div>
  </div>
  <div style="margin-top:12px;color:#333">
    <?php if(!empty($user['phone'])): ?><div><strong>Phone:</strong> <span id="displayPhone"><?php echo htmlspecialchars($user['phone']); ?></span></div><?php endif; ?>
    <?php if(!empty($user['gender'])): ?><div><strong>Gender:</strong> <?php echo htmlspecialchars(ucfirst($user['gender'])); ?></div><?php endif; ?>
    <?php if(!empty($user['date_of_birth'])): ?><div><strong>Date of birth:</strong> <?php echo htmlspecialchars($user['date_of_birth']); ?></div><?php endif; ?>
    <?php if(!empty($user['address'])): ?><div style="margin-top:8px"><strong>Address:</strong> <div id="displayAddress" style="color:#555;margin-top:4px"><?php echo nl2br(htmlspecialchars($user['address'])); ?></div></div><?php endif; ?>
  </div>
  <div style="margin-top:1rem">
    <?php if(!empty($success)): ?>
      <div style="color:#0b7a3a;font-weight:600;margin-bottom:8px"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <p>You can edit your profile using the inline editor below. Changes to cover and avatar are applied immediately.</p>
  </div>
  </div>
</div>
<!-- Inline edit section (hidden by default; shown when user clicks Edit profile) -->
<div style="max-width:720px;margin:1.25rem auto;padding:18px;background:#fff;border-radius:10px;color:#111">
  <div id="editProfileArea" style="display:none;margin-top:12px">
    <h3 style="margin-top:0">Edit Profile</h3>
    <?php if(!empty($error)): ?>
      <div style="color:#b00020;margin-bottom:12px"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if(!empty($success)): ?>
      <div style="color:#0b7a3a;margin-bottom:12px"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <div style="display:flex;gap:18px;flex-wrap:wrap">
    <div style="flex:1 1 360px;min-width:280px">
      <form id="profileEditForm" method="post" action="<?php echo site_url('user/profile/edit'); ?>">
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
        <div style="margin-bottom:12px">
          <label style="display:block;font-weight:700;margin-bottom:6px">Bio</label>
          <textarea name="bio" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
        </div>
        <div>
          <button type="submit" form="profileEditForm" style="background:#0b74de;color:#fff;padding:8px 14px;border-radius:6px;border:none">Save changes</button>
        </div>
      </form>

      <div id="verificationArea" style="display:none;margin-top:18px;padding:12px;border:1px dashed #e6e6e6;border-radius:8px;background:#fafafa">
        <div style="font-weight:700;margin-bottom:8px">Identity verification</div>
        <?php $verified = !empty($user['is_verified']) ? true : false; ?>
        <div style="margin-bottom:8px">
          <strong>Status:</strong>
          <?php if($verified): ?>
            <span style="color:#0b7a3a;font-weight:700">Verified</span>
          <?php else: ?>
            <span style="color:#b65d00;font-weight:700">Not verified</span>
          <?php endif; ?>
        </div>
        <div style="color:#444;margin-bottom:8px">To be verified you must provide your full current address (above), upload a valid government-issued ID (front/back) and a selfie holding the ID.</div>
          <div id="verificationRequirements" style="margin-bottom:10px;display:none">
            <strong>Requirements</strong>
            <ul style="margin:6px 0 0 14px;padding:0;color:#333">
              <li id="reqAddress">Address: <span style="font-weight:700;color:#b65d00"><?php echo !empty($user['address']) ? 'Provided' : 'Missing'; ?></span></li>
              <li id="reqId">ID document: <span style="font-weight:700;color:#b65d00"><?php echo !empty($user['id_document']) ? 'Uploaded' : 'Missing'; ?></span></li>
              <li id="reqSelfie">Selfie with ID: <span style="font-weight:700;color:#b65d00"><?php echo !empty($user['id_selfie']) ? 'Uploaded' : 'Missing'; ?></span></li>
            </ul>
          </div>
          <div id="verificationUploads" style="display:none;gap:8px;flex-wrap:wrap;align-items:center">
            <form method="post" action="<?php echo site_url('user/profile/edit'); ?>" enctype="multipart/form-data" style="margin:0">
              <input type="file" name="id_document" accept="image/*,application/pdf" onchange="uploadVerificationFile(this,'id_document')" style="display:none" id="idDocInput">
              <button type="button" onclick="document.getElementById('idDocInput').click()" style="background:#0b74de;color:#fff;padding:8px 12px;border-radius:6px;border:none">Upload ID document</button>
            </form>
            <form method="post" action="<?php echo site_url('user/profile/edit'); ?>" enctype="multipart/form-data" style="margin:0">
              <input type="file" name="id_selfie" accept="image/*" capture="environment" onchange="uploadVerificationFile(this,'id_selfie')" style="display:none" id="idSelfieInput">
              <button type="button" onclick="openAvatarModal('id_selfie')" style="background:#06b;color:#fff;padding:8px 12px;border-radius:6px;border:none">Use camera / upload selfie</button>
            </form>
            <div style="align-self:center;color:#666">ID: <span id="idDocView"><?php if(!empty($user['id_document'])){ ?><a target="_blank" href="<?php echo base_url() . PUBLIC_DIR . '/' . $user['id_document']; ?>">View</a><?php } else { echo '—'; } ?></span></div>
            <div style="align-self:center;color:#666">Selfie: <span id="idSelfieView"><?php if(!empty($user['id_selfie'])){ ?><a target="_blank" href="<?php echo base_url() . PUBLIC_DIR . '/' . $user['id_selfie']; ?>">View</a><?php } else { echo '—'; } ?></span></div>
            <div style="display:flex;gap:8px;align-items:center;margin-left:6px">
              <button type="button" id="submitVerificationBtn" onclick="verifyNow(true)" style="background:#0b7a3a;color:#fff;padding:8px 12px;border-radius:6px;border:none">Submit verification request</button>
              <div id="verificationMessage" style="display:none;margin-top:8px;font-weight:600;margin-left:6px"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Avatar edit column removed per request -->
  </div>
</div>
<!-- Avatar / Camera Modal -->
<div id="avatarModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;padding:18px;border-radius:8px;max-width:720px;width:96%;">
    <h3 style="margin-top:0">Change profile picture</h3>
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
      <div style="flex:1 1 240px">
        <form id="avatar_modal_form" method="post" action="<?php echo site_url('user/profile/edit'); ?>" enctype="multipart/form-data" style="margin:0">
          <input id="avatar_modal_file_input" type="file" name="avatar" accept="image/*" style="display:none" onchange="this.form.submit()" />
          <button type="button" onclick="document.getElementById('avatar_modal_file_input').click()" style="width:100%;padding:12px;border-radius:6px;border:none;background:#0b74de;color:#fff">Upload from device</button>
        </form>
        <div style="display:flex;gap:8px;align-items:center">
          <button type="submit" form="profileEditForm" style="background:#0b74de;color:#fff;padding:8px 14px;border-radius:6px;border:none">Save changes</button>
          <button type="button" id="toggleVerificationBtn" onclick="toggleVerificationArea()" style="background:#06b;color:#fff;padding:8px 14px;border-radius:6px;border:none">Request verification</button>
        </div>
      </div>
      <div style="flex:1 1 240px;display:flex;gap:8px;align-items:center">
        <select id="videoSourceSelect" style="flex:1;padding:10px;border-radius:6px;border:1px solid #ddd;display:none"></select>
        <button type="button" id="cameraSelectBtn" onclick="startCameraBySelection()" style="display:none;padding:10px;border-radius:6px;border:none;background:#0b74de;color:#fff">Open</button>
      </div>
      <div style="flex:1 1 100px">
        <button type="button" onclick="closeAvatarModal()" style="width:100%;padding:12px;border-radius:6px;border:1px solid #ddd;background:#fff;color:#333">Cancel</button>
      </div>
    </div>
    <div id="cameraArea" style="display:none;margin-top:12px">
      <video id="avatarVideo" autoplay playsinline style="width:100%;max-height:360px;background:#000;border-radius:6px"></video>
      <canvas id="avatarCanvas" style="display:none;width:100%"></canvas>
        <div id="cameraError" style="display:none;color:#b00020;margin-top:8px"></div>
      <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <button type="button" onclick="takePhoto()" id="takePhotoBtn" style="background:#0b74de;color:#fff;padding:8px 12px;border-radius:6px;border:none">Take photo</button>
        <button type="button" id="uploadProfileBtn" onclick="uploadCurrentCanvas()" style="background:#16a34a;color:#fff;padding:8px 12px;border-radius:6px;border:none;display:none">Upload profile</button>
        <button type="button" id="cancelUploadBtn" onclick="cancelCapture()" style="background:#ef4444;color:#fff;padding:8px 12px;border-radius:6px;border:none;display:none">Cancel</button>
        <button type="button" onclick="stopCamera()" style="background:#888;color:#fff;padding:8px 12px;border-radius:6px;border:none">Close camera</button>
      </div>
      <div id="uploadDebug" style="display:block;margin-top:10px;max-height:180px;overflow:auto;background:#111;color:#fff;padding:8px;border-radius:6px;font-family:monospace;font-size:12px;white-space:pre-wrap"></div>
      <div id="cropHint" style="display:none;margin-top:8px;color:#666">Preview ready. Click "Upload profile" to save or "Cancel" to retake.</div>
    </div>
  </div>
  </div>
</div>

<script>
  function toggleEditProfile(show){
// AJAX profile submit: post form and update DOM from controller JSON
document.addEventListener('DOMContentLoaded', function(){
  try{
    var form = document.getElementById('profileEditForm'); if(!form) return;
    form.addEventListener('submit', function(e){
      e.preventDefault();
      var submitBtn = form.querySelector('button[type="submit"]');
      if(submitBtn){ submitBtn.disabled = true; submitBtn.textContent = 'Saving...'; }
      var fd = new FormData(form);
      // POST as AJAX so controller returns JSON
      fetch('<?php echo site_url("user/profile/edit"); ?>', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function(r){ var ct = (r.headers.get && r.headers.get('content-type')) || ''; if(ct.indexOf('application/json') !== -1) return r.json(); return r.text().then(function(t){ throw new Error('Non-JSON response'); }); })
        .then(function(j){
          if(j && (j.status === 'ok' || j.status === 'success')){
            var u = (j.data && j.data.user) ? j.data.user : {};
            try{ if(u.full_name){ var el = document.getElementById('displayFullName'); if(el) el.textContent = u.full_name; var title = document.getElementById('user-title'); if(title) title.textContent = 'Welcome to Blue Lagoon Hotel'; } }catch(e){}
            try{ if(u.email){ var eEl = document.getElementById('displayEmail'); if(eEl) eEl.textContent = u.email; } }catch(e){}
            try{ if(u.phone){ var pEl = document.getElementById('displayPhone'); if(pEl) pEl.textContent = u.phone; } }catch(e){}
            try{ var aEl = document.getElementById('displayAddress'); if(aEl){ aEl.innerHTML = u.address ? (u.address.replace(/\n/g,'<br>')) : '—'; } }catch(e){}
            // update verification badge
            try{
              var badge = document.getElementById('verificationBadge');
              if(badge){
                if(u.is_verified || u.is_verified === '1' || u.is_verified === 1){ badge.textContent = 'Verified'; badge.style.background = '#0b7a3a'; badge.title = 'Verified'; }
                else if(u.verification_requested || u.verification_requested === '1'){ badge.textContent = 'Pending'; badge.style.background = '#b65d00'; badge.title = 'Verification pending'; }
                else { badge.textContent = 'Not verified'; badge.style.background = '#888'; badge.title = 'Not verified'; }
              }
            }catch(e){}

            // show inline success message
            try{
              var editArea = document.getElementById('editProfileArea');
              if(editArea){
                var existing = editArea.querySelector('.inline-success'); if(existing) existing.parentNode.removeChild(existing);
                var successDiv = document.createElement('div'); successDiv.className = 'inline-success'; successDiv.style.color = '#0b7a3a'; successDiv.style.fontWeight = '600'; successDiv.style.marginBottom = '8px';
                successDiv.textContent = (j.data && (j.data.success || j.data.message)) ? (j.data.success || j.data.message) : 'Profile updated successfully.';
                editArea.insertBefore(successDiv, editArea.firstChild);
              }
            }catch(e){}
            try{ updateVerificationChecklist(); }catch(e){}
          } else {
            var err = (j && j.data && j.data.error) ? j.data.error : (j && j.error) ? j.error : 'Unable to update profile';
            alert(err);
          }
        })
        .catch(function(err){
          console.error('Profile AJAX error', err);
          alert('Unable to update profile. Please try again.');
        })
        .finally(function(){ if(submitBtn){ submitBtn.disabled = false; submitBtn.textContent = 'Save changes'; } });
    });
  }catch(e){ console.error('profile AJAX attach error', e); }
});

    var area = document.getElementById('editProfileArea');
    var btn = document.getElementById('editProfileBtn');
    if(!area || !btn) return;
    if(typeof show === 'undefined') show = (area.style.display === 'none');
    var verArea = document.getElementById('verificationArea');
    var topVerify = document.getElementById('verifyNowTopBtn');
    if(show){
      // when showing edit, hide verification area to keep sections separate
      try{ if(verArea) verArea.style.display = 'none'; }catch(e){}
      try{ if(topVerify){ topVerify.textContent = 'Verify now'; topVerify.style.background = '#0b7a3a'; } }catch(e){}
      area.style.display = 'block';
      btn.textContent = 'Cancel';
      btn.style.background = '#ef4444';
      // focus first input
      try{ var first = area.querySelector('input[name="full_name"]'); if(first) first.focus(); }catch(e){}
    } else {
      area.style.display = 'none';
      btn.textContent = 'Edit profile';
      btn.style.background = '#0b74de';
    }
  }
    function toggleVerificationArea(show){
      var area = document.getElementById('verificationArea');
      var btn = document.getElementById('toggleVerificationBtn');
      if(!area) return;
      if(typeof show === 'undefined'){
        try{ var cs = window.getComputedStyle(area); show = (cs && cs.display === 'none'); }catch(e){ show = (area.style.display === 'none' || area.style.display === ''); }
      }
      if(show){
        area.style.display = 'block';
        if(btn){ btn.textContent = 'Hide verification'; btn.style.background = '#ef4444'; }
      } else {
        area.style.display = 'none';
        if(btn){ btn.textContent = 'Request verification'; btn.style.background = '#06b'; }
      }
    }

    function toggleVerificationUploads(show){
      var uploads = document.getElementById('verificationUploads');
      var topBtn = document.getElementById('verifyNowTopBtn');
      var req = document.getElementById('verificationRequirements');
      if(!uploads || !topBtn) return;
      if(typeof show === 'undefined'){
        try{
          var cs = window.getComputedStyle(uploads);
          show = (cs && cs.display === 'none');
        }catch(e){
          show = (uploads.style.display === 'none' || uploads.style.display === '');
        }
      }
        if(show){
          // when showing verification uploads, hide the edit form to separate the flows
          try{ var editArea = document.getElementById('editProfileArea'); if(editArea){ editArea.style.display = 'none'; var editBtn = document.getElementById('editProfileBtn'); if(editBtn){ editBtn.textContent = 'Edit profile'; editBtn.style.background = '#0b74de'; } } }catch(e){}
          uploads.style.display = 'flex';
          if(req) req.style.display = 'block';
          topBtn.textContent = 'Hide verification';
          topBtn.style.background = '#ef4444';
          // also ensure the full verification area is visible
          try{ toggleVerificationArea(true); }catch(e){}
          // scroll into view so user sees requirements
          try{ var area = document.getElementById('verificationArea'); if(area) area.scrollIntoView({behavior:'smooth', block:'center'}); }catch(e){}
        } else {
          uploads.style.display = 'none';
          if(req) req.style.display = 'none';
          topBtn.textContent = 'Verify now';
          topBtn.style.background = '#0b7a3a';
        }
    }

    // Show the verification UI clearly (requirements + uploads). Use instead of toggle when we want to force display.
    function showVerificationUI(){
      try{
        // ensure the edit/profile container is visible (verificationArea is inside it)
        var editArea = document.getElementById('editProfileArea');
        if(editArea) editArea.style.display = 'block';
        // hide the profile form inside the edit area so only verification shows
        var profileForm = document.getElementById('profileEditForm');
        if(profileForm) profileForm.style.display = 'none';
        // ensure verification area visible
        var area = document.getElementById('verificationArea'); if(area) area.style.display = 'block';
        // show requirements
        var req = document.getElementById('verificationRequirements'); if(req) req.style.display = 'block';
        // show uploads
        var uploads = document.getElementById('verificationUploads'); if(uploads) uploads.style.display = 'flex';
        // update top button state
        var topBtn = document.getElementById('verifyNowTopBtn'); if(topBtn){ topBtn.textContent = 'Hide verification'; topBtn.style.background = '#ef4444'; }
        // update checklist values
        try{ updateVerificationChecklist(); }catch(e){}
        // scroll into view
        try{ if(area) area.scrollIntoView({behavior:'smooth', block:'center'}); }catch(e){}
      }catch(e){ console.error('showVerificationUI error', e); }
    }
</script>
<script>
  // (cover upload removed)

  // Avatar / camera handling
  var avatarStream = null;
  // The current upload target field name (e.g. 'avatar' or 'id_selfie')
  window.currentUploadTarget = 'avatar';
  function openAvatarModal(){
    var target = (arguments.length && arguments[0]) ? arguments[0] : 'avatar';
    window.currentUploadTarget = target || 'avatar';
    var m = document.getElementById('avatarModal'); if(!m) return; m.style.display='flex';
    // If opening modal specifically to capture a selfie, start the camera immediately
    try{
      var uploadFromDeviceBtn = document.querySelector('button[onclick*="avatar_modal_file_input"]');
      var saveBtn = document.querySelector('button[form="profileEditForm"]');
      var toggleVerBtn = document.getElementById('toggleVerificationBtn');
      var cameraOpenBtn = document.getElementById('openCameraBtn');

      if(window.currentUploadTarget === 'id_selfie'){
        // prefer to start camera directly for selfie flow to avoid opening file picker
        startCamera();
        // hide the "Upload from device" control (we want camera-only selfie here)
        try{ if(uploadFromDeviceBtn) uploadFromDeviceBtn.style.display = 'none'; }catch(e){}
        // hide the Save changes and Request verification buttons to simplify the modal
        try{ if(saveBtn) saveBtn.style.display = 'none'; }catch(e){}
        try{ if(toggleVerBtn) toggleVerBtn.style.display = 'none'; }catch(e){}

        // ensure there's a visible explicit "Open camera" button for fallback
        if(!cameraOpenBtn){
          cameraOpenBtn = document.createElement('button');
          cameraOpenBtn.id = 'openCameraBtn';
          cameraOpenBtn.type = 'button';
          cameraOpenBtn.textContent = 'Open camera';
          cameraOpenBtn.style.cssText = 'width:100%;padding:12px;border-radius:6px;border:none;background:#0b74de;color:#fff;margin-top:8px';
          cameraOpenBtn.onclick = function(){ startCamera(); };
          // insert near the top of modal controls
          var insertBefore = document.getElementById('cameraSelectBtn') || document.getElementById('avatar_modal_file_input');
          if(insertBefore && insertBefore.parentNode){ insertBefore.parentNode.insertBefore(cameraOpenBtn, insertBefore.nextSibling); }
        } else {
          cameraOpenBtn.style.display = 'inline-block';
        }
      } else {
        // ensure default avatar flow shows device upload and other controls
        try{ if(uploadFromDeviceBtn) uploadFromDeviceBtn.style.display = 'inline-block'; }catch(e){}
        try{ if(saveBtn) saveBtn.style.display = 'inline-block'; }catch(e){}
        try{ if(toggleVerBtn) toggleVerBtn.style.display = 'inline-block'; }catch(e){}
        try{ if(cameraOpenBtn) cameraOpenBtn.style.display = 'none'; }catch(e){}
      }
    }catch(e){ console.error('openAvatarModal: startCamera attempt failed', e); }
    // Ensure upload/cancel buttons exist so users can see the controls (disabled until a photo is taken)
    try{
      var uploadBtn = document.getElementById('uploadProfileBtn'); var cancelBtn = document.getElementById('cancelUploadBtn');
      var controls = document.querySelector('#cameraArea > div[style*="display:flex"]');
      if(!controls){ controls = document.querySelector('#cameraArea'); }
      if(!uploadBtn){
        uploadBtn = document.createElement('button'); uploadBtn.id = 'uploadProfileBtn'; uploadBtn.type='button';
        uploadBtn.style.cssText = 'background:#16a34a;color:#fff;padding:8px 12px;border-radius:6px;border:none;display:none';
        uploadBtn.textContent = 'Upload profile (take photo first)'; uploadBtn.onclick = uploadCurrentCanvas; uploadBtn.disabled = true;
        if(controls) controls.insertBefore(uploadBtn, controls.querySelector('#cancelUploadBtn'));
      }
      if(!cancelBtn){
        cancelBtn = document.createElement('button'); cancelBtn.id = 'cancelUploadBtn'; cancelBtn.type='button';
        cancelBtn.style.cssText = 'background:#ef4444;color:#fff;padding:8px 12px;border-radius:6px;border:none;display:none';
        cancelBtn.textContent = 'Cancel'; cancelBtn.onclick = cancelCapture; cancelBtn.disabled = true;
        if(controls) controls.insertBefore(cancelBtn, controls.querySelector('#cancelUploadBtn'));
      }
      // Make Upload visible but disabled so it's clear the control exists
      // update button label depending on target
      try{ if(window.currentUploadTarget === 'id_selfie'){ uploadBtn.textContent = 'Upload selfie'; } else { uploadBtn.textContent = 'Upload profile'; } }catch(e){}
      uploadBtn.style.display = 'inline-block'; uploadBtn.style.visibility='visible'; uploadBtn.style.opacity='0.6';
      cancelBtn.style.display = 'inline-block'; cancelBtn.style.visibility='visible'; cancelBtn.style.opacity='0.6';
    }catch(e){ console.error('openAvatarModal: button init error', e); }
  }
  function closeAvatarModal(){
    var m = document.getElementById('avatarModal'); if(!m) return; m.style.display='none'; stopCamera();
  }
  function startCamera(deviceId){
    var area = document.getElementById('cameraArea'); var v = document.getElementById('avatarVideo'); var errEl = document.getElementById('cameraError'); if(!v||!area) return;
    // clear previous error
    if(errEl){ errEl.style.display='none'; errEl.textContent = ''; }
    // if stream already exists, stop it first to free device
    if(avatarStream){ try{ avatarStream.getTracks().forEach(function(t){ t.stop(); }); }catch(e){} avatarStream = null; }
    area.style.display = 'block';
    v.style.display = 'block';
    try{ var _openBtn = document.getElementById('openCameraBtn'); if(_openBtn) _openBtn.style.display='none'; }catch(e){}
    // If a specific deviceId is provided, request that device
    var tryGetUserMedia = function(constraints){
      return navigator.mediaDevices.getUserMedia(constraints).then(function(s){
        avatarStream = s; v.srcObject = s; if(errEl){ errEl.style.display='none'; errEl.textContent = ''; }
      });
    };

    if(deviceId){
      tryGetUserMedia({ video: { deviceId: { exact: deviceId } }, audio: false })
          .catch(function(err){
            console.error('getUserMedia(deviceId) error:', err);
            if(errEl){ errEl.style.display='block'; errEl.textContent = 'Unable to open selected camera. Try a different device.'; }
            try{ var _ob = document.getElementById('openCameraBtn'); if(_ob) _ob.style.display='inline-block'; }catch(e){}
          });
      return;
    }

    // Try to prefer rear camera on mobile first, then fall back to user-facing camera
    tryGetUserMedia({ video: { facingMode: { exact: 'environment' } }, audio: false })
      .catch(function(){
        return tryGetUserMedia({ video: { facingMode: 'user' }, audio: false });
      })
      .catch(function(err){
        var msg = 'Unable to access camera: ' + (err && err.message ? err.message : err);
        msg += ' — Close other apps using the camera, check browser permissions, or try again.';
        if(errEl){ errEl.style.display='block'; errEl.textContent = msg; }
        console.error('getUserMedia error:', err);
        try{ var _openBtn2 = document.getElementById('openCameraBtn'); if(_openBtn2) _openBtn2.style.display='inline-block'; }catch(e){}
      });
  }

  // Enumerate devices and show selection UI when available
  function prepareCameraOptions(){
    openAvatarModal();
    var select = document.getElementById('videoSourceSelect'); var btn = document.getElementById('cameraSelectBtn'); var errEl = document.getElementById('cameraError');
    if(!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices){
      // fallback: directly start camera (best effort)
      startCamera();
      return;
    }
    navigator.mediaDevices.enumerateDevices().then(function(devices){
      var videoInputs = devices.filter(function(d){ return d.kind === 'videoinput'; });
      // Clear and populate
      select.innerHTML = '';
      if(videoInputs.length === 0){
        // no devices found, try starting camera normally
        startCamera();
        return;
      }
      videoInputs.forEach(function(d, idx){
        var opt = document.createElement('option');
        opt.value = d.deviceId;
        // label may be empty on some browsers until getUserMedia is allowed
        opt.text = d.label || ('Camera ' + (idx+1));
        select.appendChild(opt);
      });
      // show selection controls
      select.style.display = 'inline-block'; try{ if(btn) btn.style.display = 'inline-block'; }catch(e){}
      // if only one device, auto-open it
      if(videoInputs.length === 1){
        startCamera(videoInputs[0].deviceId);
      }
    }).catch(function(err){
      console.error('enumerateDevices error:', err);
      // try to start camera as fallback
      startCamera();
    });
  }

  function startCameraBySelection(){
    var select = document.getElementById('videoSourceSelect'); if(!select) return; var deviceId = select.value; if(!deviceId) return; startCamera(deviceId);
  }
  function stopCamera(){
    if(avatarStream){ avatarStream.getTracks().forEach(function(t){t.stop();}); avatarStream = null; }
    var area = document.getElementById('cameraArea'); if(area) area.style.display='none';
    var useBtn = document.getElementById('usePhotoBtn'); if(useBtn) useBtn.style.display='none';
    var canvas = document.getElementById('avatarCanvas'); if(canvas) canvas.style.display='none';
    var video = document.getElementById('avatarVideo'); if(video) video.srcObject = null;
    try{ var _ob2 = document.getElementById('openCameraBtn'); if(_ob2) _ob2.style.display='inline-block'; }catch(e){}
  }
  function takePhoto(){
    var v = document.getElementById('avatarVideo'); var c = document.getElementById('avatarCanvas'); if(!v||!c) return;
    c.width = v.videoWidth; c.height = v.videoHeight; var ctx = c.getContext('2d'); ctx.drawImage(v,0,0,c.width,c.height);
    c.style.display='block';
    // ensure canvas is behind the controls (avoid it visually covering buttons)
    try{ c.style.zIndex = '0'; c.style.position = c.style.position || 'relative'; }catch(e){}
    // show upload/cancel controls (no cropping)
    var uploadBtn = document.getElementById('uploadProfileBtn'); var cancelBtn = document.getElementById('cancelUploadBtn');
    // If buttons are missing for any reason, create them dynamically and append to the controls container
    try{
      var controls = document.getElementById('takePhotoBtn') ? document.getElementById('takePhotoBtn').parentNode : null;
      if(!uploadBtn){
        uploadBtn = document.createElement('button');
        uploadBtn.id = 'uploadProfileBtn';
        uploadBtn.type = 'button';
        uploadBtn.style.cssText = 'background:#16a34a;color:#fff;padding:8px 12px;border-radius:6px;border:none;';
        uploadBtn.textContent = 'Upload profile';
        uploadBtn.onclick = uploadCurrentCanvas;
        if(controls) controls.insertBefore(uploadBtn, document.getElementById('takePhotoBtn').nextSibling);
      }
      if(!cancelBtn){
        cancelBtn = document.createElement('button');
        cancelBtn.id = 'cancelUploadBtn';
        cancelBtn.type = 'button';
        cancelBtn.style.cssText = 'background:#ef4444;color:#fff;padding:8px 12px;border-radius:6px;border:none;';
        cancelBtn.textContent = 'Cancel';
        cancelBtn.onclick = cancelCapture;
        if(controls) controls.insertBefore(cancelBtn, document.getElementById('takePhotoBtn').nextSibling);
      }
    }catch(e){ console.error('takePhoto: error creating controls', e); }

    if(uploadBtn){ uploadBtn.style.display = 'inline-block'; uploadBtn.style.visibility='visible'; uploadBtn.style.opacity='1'; uploadBtn.disabled = false; uploadBtn.textContent = 'Upload profile'; uploadBtn.style.zIndex='1001'; }
    if(cancelBtn){ cancelBtn.style.display = 'inline-block'; cancelBtn.style.visibility='visible'; cancelBtn.style.opacity='1'; cancelBtn.disabled = false; cancelBtn.style.zIndex='1001'; }
    // Ensure controls container sits above the canvas
    try{ var controlsContainer = document.getElementById('takePhotoBtn') ? document.getElementById('takePhotoBtn').parentNode : null; if(controlsContainer){ controlsContainer.style.position='relative'; controlsContainer.style.zIndex='1001'; } }catch(e){}
    // defensive: in case some browsers/styles override display, ensure buttons visible shortly after
    setTimeout(function(){ try{ if(uploadBtn){ uploadBtn.style.display='inline-block'; uploadBtn.style.visibility='visible'; } if(cancelBtn){ cancelBtn.style.display='inline-block'; cancelBtn.style.visibility='visible'; } }catch(e){} }, 150);
    document.getElementById('takePhotoBtn').style.display = 'none';
    document.getElementById('cropHint').style.display = 'block';
    // hide video while showing preview
    v.style.display = 'none';
    try{
      var cs = window.getComputedStyle(uploadBtn || document.createElement('div'));
      console.log('takePhoto: uploadBtn=', !!uploadBtn, 'cancelBtn=', !!cancelBtn, 'uploadBtn.display=', cs.display, 'computedVisibility=', cs.visibility, 'canvasSize=', c.width, 'x', c.height);
    }catch(e){ console.log('takePhoto: debug log failed', e); }
  }
  function usePhoto(){
    // legacy direct use (not used now) -- keep as fallback
    var c = document.getElementById('avatarCanvas'); if(!c) return;
    c.toBlob(function(blob){
      if(!blob){ alert('Unable to capture image'); return; }
      uploadBlob(blob);
    }, 'image/jpeg', 0.9);
  }
  
  // Simplified capture: show preview and allow direct upload or cancel (no cropping)
  function uploadCurrentCanvas(){
    var canvas = document.getElementById('avatarCanvas'); if(!canvas) return;
    var uploadBtn = document.getElementById('uploadProfileBtn'); var cancelBtn = document.getElementById('cancelUploadBtn');
    if(uploadBtn) { uploadBtn.disabled = true; uploadBtn.textContent = 'Uploading...'; }
    if(cancelBtn) { cancelBtn.disabled = true; }
    canvas.toBlob(function(blob){ if(!blob){ if(uploadBtn){ uploadBtn.disabled=false; uploadBtn.textContent='Upload profile'; } if(cancelBtn){ cancelBtn.disabled=false; } alert('Unable to capture image'); return; } uploadBlob(blob); }, 'image/jpeg', 0.9);
  }

  function cancelCapture(){
    var canvas = document.getElementById('avatarCanvas');
    var v = document.getElementById('avatarVideo');
    if(!canvas) return;
    try{
      canvas.style.display = 'none';
      if(v) v.style.display = 'block';

      var uploadBtn = document.getElementById('uploadProfileBtn');
      if(uploadBtn){ uploadBtn.style.display = 'none'; uploadBtn.disabled = false; }

      var cancelBtn = document.getElementById('cancelUploadBtn');
      if(cancelBtn){ cancelBtn.style.display = 'none'; cancelBtn.disabled = false; }

      var takeBtn = document.getElementById('takePhotoBtn');
      if(takeBtn){ takeBtn.style.display = 'inline-block'; }

      var cropHint = document.getElementById('cropHint');
      if(cropHint){ cropHint.style.display = 'none'; }

      // ensure video is visible and focused where possible
      if(v){
        v.style.display = 'block';
        try{ v.focus(); }catch(e){}
      }
    }catch(e){ console.error('cancelCapture error', e); }
  }
  function uploadBlob(blob){
    function debugLog(){ try{ var el = document.getElementById('uploadDebug'); if(!el) return; for(var i=0;i<arguments.length;i++){ el.textContent += arguments[i] + "\n"; } el.scrollTop = el.scrollHeight; }catch(e){ console.log.apply(console, arguments); } }
    var fd = new FormData();
      // choose field name based on current target (defaults to 'avatar')
    var field = (window.currentUploadTarget || 'avatar');
    var filename = (field === 'id_selfie') ? 'selfie.jpg' : 'camera.jpg';
    fd.append(field, blob, filename);
      // determine upload URL and prefer same-origin path to avoid cross-origin/session issues
    try{
      // prefer the explicit POST route: /user/profile/edit (avoid posting to /user/profile which is GET-only)
      var profileEditUrl = '<?php echo site_url("user/profile/edit"); ?>';
      var uploadUrl = profileEditUrl;
      try{
        var parsed = new URL(profileEditUrl, window.location.href);
        if(parsed.origin !== window.location.origin){
          uploadUrl = parsed.pathname + parsed.search;
        }
      }catch(e){ /* ignore URL parse errors and use profileEditUrl */ }

      // prioritize explicit edit endpoints and reasonable fallbacks that point to the edit handler
      var currentPath = window.location.pathname + (window.location.search || '');
      var candidates = [uploadUrl, '/index.php/user/profile/edit', '/user/profile/edit', currentPath];

      var attemptIndex = 0;
      function tryAttempt(){
        if(attemptIndex >= candidates.length){
          debugLog('All attempts failed. See above.');
          alert('Upload failed: all upload endpoints returned errors. See debug box for details.');
          closeAvatarModal();
          return;
        }
        var tryUrl = candidates[attemptIndex++];
        debugLog('Attempting: ' + tryUrl + ' (field=' + field + ')');
        fetch(tryUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function(resp){
            // Try to parse JSON responses first (controller now returns JSON for AJAX)
            var contentType = resp.headers.get && resp.headers.get('Content-Type') || '';
            if(resp.status === 404){
              debugLog(' -> 404 from ' + tryUrl + ' (trying next candidate)');
              tryAttempt();
              return;
            }
            if(contentType.indexOf('application/json') !== -1){
              return resp.json().then(function(j){
                if(j && (j.status === 'ok' || j.status === 'success')){
                  try{ debugLog(' -> Upload JSON OK: ' + (j.data && (j.data.success || j.data.message) ? (j.data.success || j.data.message) : 'ok')); }catch(e){}
                  // Update UI from returned user data when available
                  try{
                    var data = j.data || {};
                    var u = data.user || {};
                    if(field === 'avatar'){
                      var avatarImg = document.querySelector('img[alt="avatar"]'); if(avatarImg && u.avatar){ avatarImg.src = '<?php echo base_url() . PUBLIC_DIR; ?>/' + u.avatar + '?' + Date.now(); }
                    } else if(field === 'id_selfie'){
                      var el = document.getElementById('idSelfieView'); if(el){ if(u.id_selfie){ el.innerHTML = '<a target="_blank" href="' + ('<?php echo base_url() . PUBLIC_DIR; ?>/' + u.id_selfie) + '">View</a>'; } }
                    } else if(field === 'id_document'){
                      var el2 = document.getElementById('idDocView'); if(el2){ if(u.id_document){ el2.innerHTML = '<a target="_blank" href="' + ('<?php echo base_url() . PUBLIC_DIR; ?>/' + u.id_document) + '">View</a>'; } }
                    }
                  }catch(e){ console.error('uploadBlob: update UI error', e); }
                  closeAvatarModal();
                  try{ updateVerificationChecklist(); }catch(e){}
                  return;
                }
                // treat JSON error payloads gracefully
                var errMsg = (j && j.data && j.data.error) ? j.data.error : (j && j.error) ? j.error : 'Upload failed';
                debugLog(' -> Upload JSON error: ' + errMsg);
                alert('Upload failed: ' + errMsg);
                closeAvatarModal();
                return;
              }).catch(function(err){
                // JSON parse failed; fall back to text handling
                return resp.text().then(function(t){ debugLog(' -> non-json response after JSON parse failure'); debugLog(t); alert('Upload failed: unexpected server response'); closeAvatarModal(); });
              });
            }
            // Fallback: handle non-JSON responses as before (show text snippet)
            return resp.text().then(function(t){
              var snippet = t ? (t.length > 2000 ? t.substr(0,2000) + '\n... (truncated)' : t) : '(no response body)';
              var msg = 'HTTP ' + resp.status + ' ' + resp.statusText + '\nURL: ' + tryUrl + '\n\n' + snippet;
              debugLog(' -> NON-OK response:\n' + msg);
              alert('Upload failed: server returned error. See debug box for response.');
              closeAvatarModal();
            });
          })
          .catch(function(err){
            debugLog(' -> fetch error for ' + tryUrl + ': ' + (err && err.message ? err.message : err));
            // try next candidate on network errors
            tryAttempt();
          });
      }

      tryAttempt();
    }catch(e){ console.error('uploadBlob unexpected error', e); alert('Upload failed: ' + (e.message||e)); closeAvatarModal(); }
  }
</script>
<script>
  // AJAX upload helper for verification files (ID document, selfie)
  function uploadVerificationFile(inputEl, fieldName){
    if(!inputEl || !inputEl.files || inputEl.files.length === 0) return;
    var file = inputEl.files[0];
    var msgEl = document.getElementById('verificationMessage');
    if(msgEl){ msgEl.style.display='block'; msgEl.style.color='#0b74de'; msgEl.textContent = 'Uploading...'; }
    var fd = new FormData();
    fd.append(fieldName, file, file.name || (fieldName + '.jpg'));
    // prefer explicit edit route
    var profileEditUrl = '<?php echo site_url("user/profile/edit"); ?>';
    var candidates = [profileEditUrl, '/index.php/user/profile/edit', '/user/profile/edit'];

    var attempt = 0;
    function tryNext(){
      if(attempt >= candidates.length){ if(msgEl){ msgEl.style.color='#b00020'; msgEl.textContent = 'Upload failed'; } return; }
      var url = candidates[attempt++];
      fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function(resp){
          if(resp.status === 404){ tryNext(); return; }
          var ct = resp.headers.get && resp.headers.get('Content-Type') || '';
          if(ct.indexOf('application/json') !== -1){
            return resp.json().then(function(j){
              if(j && (j.status === 'ok' || j.status === 'success')){
                if(msgEl){ msgEl.style.color='#0b7a3a'; msgEl.textContent = (j.data && (j.data.success || j.data.message)) ? (j.data.success || j.data.message) : 'Upload successful'; }
                // Update id/selfie view from returned user data when available
                try{
                  var u = (j.data && j.data.user) ? j.data.user : {};
                  if(fieldName === 'id_document'){
                    var el = document.getElementById('idDocView'); if(el && u.id_document){ el.innerHTML = '<a target="_blank" href="' + ('<?php echo base_url() . PUBLIC_DIR; ?>/' + u.id_document) + '">View</a>'; }
                  } else {
                    var el2 = document.getElementById('idSelfieView'); if(el2 && u.id_selfie){ el2.innerHTML = '<a target="_blank" href="' + ('<?php echo base_url() . PUBLIC_DIR; ?>/' + u.id_selfie) + '">View</a>'; }
                  }
                }catch(e){ console.error('uploadVerificationFile UI update error', e); }
                try{ updateVerificationChecklist(); }catch(e){}
                try{ inputEl.value = ''; }catch(e){}
                return;
              }
              var em = (j && j.data && j.data.error) ? j.data.error : (j && j.error) ? j.error : 'Upload failed';
              if(msgEl){ msgEl.style.color='#b00020'; msgEl.textContent = em; }
              console.error('uploadVerificationFile error json', em);
              return;
            }).catch(function(err){
              return resp.text().then(function(t){ if(msgEl){ msgEl.style.color='#b00020'; msgEl.textContent = 'Upload failed'; } console.error('uploadVerificationFile non-json response', t); });
            });
          }
          // fallback to text response parsing
          return resp.text().then(function(t){ if(msgEl){ msgEl.style.color='#0b7a3a'; msgEl.textContent = 'Upload completed'; } try{ updateVerificationChecklist(); }catch(e){} try{ inputEl.value = ''; }catch(e){} console.warn('uploadVerificationFile fallback text response', t); });
        })
        .catch(function(err){ console.error('uploadVerificationFile fetch err', err); tryNext(); });
    }
    tryNext();
  }
</script>
<script>
  // Trigger a verification request (AJAX)
  function verifyNow(){
    var btn = document.getElementById('verifyNowBtn') || document.getElementById('verifyNowTopBtn') || document.getElementById('submitVerificationBtn');
    var msgEl = document.getElementById('verificationMessage');
    if(btn) { btn.disabled = true; btn.textContent = 'Requesting...'; }
    if(msgEl){ msgEl.style.display = 'block'; msgEl.style.color = '#0b74de'; msgEl.textContent = 'Requesting verification...'; }

    // gather address from profile form if present
    var address = '';
    try{ var addrEl = document.querySelector('#profileEditForm textarea[name="address"]'); if(addrEl) address = addrEl.value || ''; }catch(e){}

    var fd = new FormData();
    fd.append('verification_requested', '1');
    if(address) fd.append('address', address);

    var profileEditUrl = '<?php echo site_url("user/profile/edit"); ?>';
    var candidates = [profileEditUrl, '/index.php/user/profile/edit', '/user/profile/edit'];
    var attempt = 0;

    function tryNext(){
      if(attempt >= candidates.length){ if(btn){ btn.disabled = false; btn.textContent = 'Verify now'; } if(msgEl){ msgEl.style.color = '#b00020'; msgEl.textContent = 'Verification request failed'; } return; }
      var url = candidates[attempt++];
      fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function(resp){
          if(resp.status === 404){ tryNext(); return; }
          var ct = resp.headers.get && resp.headers.get('Content-Type') || '';
          if(ct.indexOf('application/json') !== -1){
            return resp.json().then(function(j){
              if(j && (j.status === 'ok' || j.status === 'success')){
                if(msgEl){ msgEl.style.color='#0b7a3a'; msgEl.textContent = (j.data && (j.data.success || j.data.message)) ? (j.data.success || j.data.message) : 'Verification requested'; }
                // update badge to Pending if possible, or reflect verified state
                try{
                  var badge = document.querySelector('span[title="Not verified"], span[title="Verified"], span[title="Verification pending"]');
                  if(badge){ badge.textContent = 'Pending'; badge.style.background = '#b65d00'; badge.title = 'Verification pending'; }
                }catch(e){}
                if(btn){ btn.disabled = true; btn.textContent = 'Requested'; }
                // If server returned updated user data, hide top button if verified
                try{
                  var u = (j.data && j.data.user) ? j.data.user : {};
                  if(u.is_verified || u.is_verified === '1' || u.is_verified === 1){ var top = document.getElementById('verifyNowTopBtn'); if(top) top.style.display = 'none'; }
                }catch(e){}
                try{ updateVerificationChecklist(); }catch(e){}
                // ensure the verification UI remains visible after the request
                try{ var uploadsEl = document.getElementById('verificationUploads'); if(uploadsEl) uploadsEl.style.display = 'flex'; var reqEl = document.getElementById('verificationRequirements'); if(reqEl) reqEl.style.display = 'block'; }catch(e){}
                return;
              }
              var em = (j && j.data && j.data.error) ? j.data.error : (j && j.error) ? j.error : 'Verification request failed';
              if(msgEl){ msgEl.style.color = '#b00020'; msgEl.textContent = em; }
              console.error('verifyNow error json', em);
              if(btn){ btn.disabled = false; btn.textContent = 'Verify now'; }
              return;
            }).catch(function(err){
              return resp.text().then(function(t){ if(msgEl){ msgEl.style.color = '#b00020'; msgEl.textContent = 'Server error'; } console.error('verifyNow non-json response', t); if(btn){ btn.disabled = false; btn.textContent = 'Verify now'; } });
            });
          }
          // fallback to original text behavior
          resp.text().then(function(t){ if(msgEl){ msgEl.style.color = '#0b7a3a'; msgEl.textContent = 'Verification request submitted'; } try{ updateVerificationChecklist(); }catch(e){} });
        })
        .catch(function(err){ console.error('verifyNow fetch err', err); tryNext(); });
    }
    tryNext();
  }
</script>
<script>
  // Update the requirements checklist based on current values
  function updateVerificationChecklist(){
    try{
      var addrEl = document.querySelector('#profileEditForm textarea[name="address"]');
      var hasAddress = addrEl && addrEl.value && addrEl.value.trim().length > 4;
      var reqAddress = document.getElementById('reqAddress');
      if(reqAddress){ reqAddress.innerHTML = 'Address: <span style="font-weight:700;color:' + (hasAddress ? '#0b7a3a' : '#b65d00') + '">' + (hasAddress ? 'Provided' : 'Missing') + '</span>'; }

      var idDocView = document.getElementById('idDocView');
      var hasId = idDocView && idDocView.querySelector('a');
      var reqId = document.getElementById('reqId');
      if(reqId){ reqId.innerHTML = 'ID document: <span style="font-weight:700;color:' + (hasId ? '#0b7a3a' : '#b65d00') + '">' + (hasId ? 'Uploaded' : 'Missing') + '</span>'; }

      var idSelfieView = document.getElementById('idSelfieView');
      var hasSelfie = idSelfieView && idSelfieView.querySelector('a');
      var reqSelfie = document.getElementById('reqSelfie');
      if(reqSelfie){ reqSelfie.innerHTML = 'Selfie with ID: <span style="font-weight:700;color:' + (hasSelfie ? '#0b7a3a' : '#b65d00') + '">' + (hasSelfie ? 'Uploaded' : 'Missing') + '</span>'; }
    }catch(e){ console.error('updateVerificationChecklist error', e); }
  }
  // run on load
  try{ updateVerificationChecklist(); }catch(e){}
// attach safe listeners and debug info
document.addEventListener('DOMContentLoaded', function(){
  try{ console.log('profile.js: DOMContentLoaded'); }catch(e){}
  try{
    var topBtn = document.getElementById('verifyNowTopBtn');
    if(topBtn){
      // ensure click is handled even if inline handler is ignored
      topBtn.addEventListener('click', function(e){
        try{ console.log('verifyNowTopBtn clicked'); }catch(err){}
        try{ showVerificationUI(); }catch(err){ console.error('showVerificationUI error', err); }
      });
      try{ console.log('verifyNowTopBtn attached'); }catch(e){}
    } else {
      try{ console.log('verifyNowTopBtn not found on page'); }catch(e){}
    }
  }catch(e){ console.error('DOMContentLoaded attach error', e); }
});
</script>
<!-- Temporary debug banner shown when Verify now is clicked -->
<div id="verifyDebugBanner" style="display:none;position:fixed;left:8px;right:8px;bottom:20px;padding:10px 14px;background:#111;color:#fff;border-radius:6px;z-index:12000;text-align:center;font-weight:700;pointer-events:none">Verify button clicked</div>
<script>
// capture clicks at document level (use capture=true) to ensure we detect the event even if something stops propagation
document.addEventListener('click', function(e){
  try{
    var target = e.target || e.srcElement;
    var btn = document.getElementById('verifyNowTopBtn');
    if(!btn) return;
    if(target === btn || target.closest && target.closest('#verifyNowTopBtn')){
      try{ console.log('Captured click on verifyNowTopBtn via document listener'); }catch(e){}
      try{ showVerificationUI(); }catch(err){ console.error('showVerificationUI error (captured)', err); }
      try{
        var b = document.getElementById('verifyDebugBanner'); if(b){ b.style.display='block'; b.textContent = 'Verify clicked — showing verification UI'; setTimeout(function(){ b.style.display='none'; }, 2500); }
      }catch(e){ }
    }
  }catch(e){ console.error('document click capture error', e); }
}, true);
</script>
<script>
  // Navigate back with a safe fallback
  function goBack(){
    try{
      if(window.history && window.history.length > 1){
        window.history.back();
        return;
      }
    }catch(e){ /* ignore */ }
    // fallback to site root
    try{ window.location.href = '<?php echo site_url(); ?>'; }catch(e){ window.location.href = '/'; }
  }
</script>
<!-- Floating verification panel (always available) -->
<div id="verificationPanel" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:12000;align-items:center;justify-content:center">
  <div style="background:#fff;padding:18px;border-radius:8px;max-width:720px;width:96%;box-shadow:0 12px 40px rgba(0,0,0,0.35);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
      <strong style="font-size:1.05rem">Identity verification</strong>
      <button type="button" onclick="closeVerificationPanel()" style="background:#ef4444;color:#fff;border:none;padding:6px 10px;border-radius:6px">Close</button>
    </div>
    <div style="color:#444;margin-bottom:8px">To be verified you must provide your full current address (on your profile), upload a valid government-issued ID (front/back) and a selfie holding the ID.</div>
    <div id="panelVerificationRequirements" style="margin-bottom:10px">
      <strong>Requirements</strong>
      <ul style="margin:6px 0 0 14px;padding:0;color:#333">
        <li id="panelReqAddress">Address: <span style="font-weight:700;color:#b65d00">Missing</span></li>
        <li id="panelReqId">ID document: <span style="font-weight:700;color:#b65d00">Missing</span></li>
        <li id="panelReqSelfie">Selfie with ID: <span style="font-weight:700;color:#b65d00">Missing</span></li>
      </ul>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <input id="panelIdInput" type="file" accept="image/*,application/pdf" style="display:none" onchange="uploadVerificationFile(this,'id_document')">
      <button type="button" onclick="document.getElementById('panelIdInput').click()" style="background:#0b74de;color:#fff;padding:8px 12px;border-radius:6px;border:none">Upload ID document</button>
      <input id="panelSelfieInput" type="file" accept="image/*" capture="environment" style="display:none" onchange="uploadVerificationFile(this,'id_selfie')">
      <button type="button" onclick="openAvatarModal('id_selfie')" style="background:#06b;color:#fff;padding:8px 12px;border-radius:6px;border:none">Use camera / upload selfie</button>
      <div style="flex:1;color:#666">ID: <span id="panelIdView">—</span></div>
      <div style="flex:1;color:#666">Selfie: <span id="panelSelfieView">—</span></div>
    </div>
    <div style="margin-top:12px;display:flex;gap:8px;align-items:center">
      <button type="button" id="panelSubmitVerification" onclick="verifyNow(true)" style="background:#0b7a3a;color:#fff;padding:8px 12px;border-radius:6px;border:none">Submit verification request</button>
      <div id="panelVerificationMessage" style="color:#666;font-weight:600"></div>
    </div>
  </div>
</div>
<script>
  // helper: copy current checklist values into the floating panel
  function refreshPanelChecklist(){
    try{
      var addr = document.querySelector('#profileEditForm textarea[name="address"]');
      var hasAddress = addr && addr.value && addr.value.trim().length > 4;
      document.getElementById('panelReqAddress').innerHTML = 'Address: <span style="font-weight:700;color:' + (hasAddress ? '#0b7a3a' : '#b65d00') + '">' + (hasAddress ? 'Provided' : 'Missing') + '</span>';
    }catch(e){}
    try{ var idView = document.getElementById('idDocView'); var panelIdView = document.getElementById('panelIdView'); if(idView && panelIdView){ var a = idView.querySelector('a'); panelIdView.innerHTML = a ? ('<a target="_blank" href="' + a.href + '">View</a>') : '—'; } }catch(e){}
    try{ var selfieView = document.getElementById('idSelfieView'); var panelSelfieView = document.getElementById('panelSelfieView'); if(selfieView && panelSelfieView){ var a2 = selfieView.querySelector('a'); panelSelfieView.innerHTML = a2 ? ('<a target="_blank" href="' + a2.href + '">View</a>') : '—'; } }catch(e){}
  }
  // open panel when showVerificationUI is called
  var _oldShowVerificationUI = window.showVerificationUI || function(){};
  window.showVerificationUI = function(){ try{ _oldShowVerificationUI(); refreshPanelChecklist(); openVerificationPanel(); }catch(e){ console.error('showVerificationUI wrapper error', e); } };
</script>