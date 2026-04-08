<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    return;
}
?>

<!-- Update Banner -->
<div id="updateBanner" style="display:none; background:#1a73e8; color:#fff; padding:12px 20px;
     border-radius:10px; margin:12px 0; display:none; align-items:center;
     justify-content:space-between; flex-wrap:wrap; gap:10px; font-family:'Segoe UI',sans-serif;">
    <div style="display:flex; align-items:center; gap:10px;">
        <span style="font-size:1.3rem;">🔔</span>
        <div>
            <strong>Update Available</strong>
            <div style="font-size:0.82rem; opacity:0.9;" id="updateHashInfo"></div>
        </div>
    </div>
    <div style="display:flex; gap:8px; align-items:center;">
        <a href="update_logs.php" style="color:#fff; font-size:0.82rem; opacity:0.85; text-decoration:underline;">View Logs</a>
        <button onclick="doUpdate()" id="updateBtn"
            style="background:#fff; color:#1a73e8; border:none; padding:8px 16px;
                   border-radius:6px; font-weight:600; cursor:pointer; font-size:0.85rem;">
            ⬇ Update Now
        </button>
    </div>
</div>

<!-- Result Toast -->
<div id="updateToast" style="display:none; position:fixed; bottom:24px; right:24px; z-index:9999;
     padding:12px 20px; border-radius:8px; color:#fff; font-size:0.85rem;
     font-family:'Segoe UI',sans-serif; max-width:300px; box-shadow:0 4px 12px rgba(0,0,0,0.2);">
</div>

<script>
(function checkUpdate() {
    fetch('update_check.php')
        .then(r => r.json())
        .then(data => {
            if (data.status === 'update_available') {
                const banner = document.getElementById('updateBanner');
                const info = document.getElementById('updateHashInfo');
                const btn = document.getElementById('updateBtn');

                banner.style.display = 'flex';

                let text = 'Branch: ' + (data.branch || '?') +
                           ' | Current: ' + data.local_hash +
                           ' → Latest: ' + data.remote_hash;

                if (data.dirty) {
                    text += ' | Local changes detected';
                    btn.disabled = true;
                    btn.textContent = '⚠ Resolve Local Changes';
                    btn.style.opacity = '0.7';
                    btn.style.cursor = 'not-allowed';
                }

                info.textContent = text;
            }
        })
        .catch(() => {});
})();

function doUpdate() {
    const btn = document.getElementById('updateBtn');
    if (btn.disabled) return;

    btn.textContent = '⏳ Updating...';
    btn.disabled = true;

    fetch('do_update.php')
        .then(r => r.json())
        .then(data => {
            const toast = document.getElementById('updateToast');

            if (data.status === 'success') {
                toast.style.background = '#2ecc71';
                toast.textContent = '✅ ' + data.message + ' (' + data.hash_before + ' → ' + data.hash_after + ')';
                document.getElementById('updateBanner').style.display = 'none';
            } else {
                toast.style.background = '#e74c3c';
                toast.textContent = '❌ ' + data.message;
                btn.textContent = '⬇ Update Now';
                btn.disabled = false;
            }

            toast.style.display = 'block';

            setTimeout(() => {
                toast.style.display = 'none';
                if (data.status === 'success') {
                    location.reload();
                }
            }, 3500);
        })
        .catch(() => {
            const toast = document.getElementById('updateToast');
            toast.style.background = '#e74c3c';
            toast.textContent = '❌ Update request failed.';
            toast.style.display = 'block';

            btn.textContent = '⬇ Update Now';
            btn.disabled = false;

            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        });
}
</script>
