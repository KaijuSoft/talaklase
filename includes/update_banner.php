<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    return;
}

if (!function_exists('can') || !can('manage_updates')) {
    return;
}
?>

<div id="updateBanner" style="display:none;background:#1a73e8;color:#fff;padding:12px 20px;border-radius:10px;margin:12px 0;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;font-family:'Segoe UI',sans-serif;">
    <div>
        <strong>Release Available</strong>
        <div style="font-size:0.82rem;opacity:0.9" id="releaseInfo"></div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <a href="update_logs.php" style="color:#fff;font-size:0.82rem;opacity:0.85;text-decoration:underline">Release Logs</a>
        <button onclick="installRelease()" id="releaseInstallButton" style="background:#fff;color:#1a73e8;border:none;padding:8px 16px;border-radius:6px;font-weight:600;cursor:pointer;font-size:0.85rem">Install Release</button>
    </div>
</div>

<div id="releaseToast" style="display:none;position:fixed;bottom:24px;right:24px;z-index:9999;padding:12px 20px;border-radius:8px;color:#fff;font-size:0.85rem;font-family:'Segoe UI',sans-serif;max-width:320px;box-shadow:0 4px 12px rgba(0,0,0,0.2)"></div>

<script>
(function checkRelease() {
    fetch('update_check.php')
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'update_available') return;
            const banner = document.getElementById('updateBanner');
            const info = document.getElementById('releaseInfo');
            if (!banner || !info) return;
            banner.style.display = 'flex';
            info.textContent = 'Channel: ' + (data.channel || 'stable')
                + ' | Current: ' + (data.current_version || '-')
                + ' | Latest: ' + (data.latest_version || '-')
                + (data.release_date ? ' | Released: ' + data.release_date : '');
        })
        .catch(() => {});
})();

function installRelease() {
    const button = document.getElementById('releaseInstallButton');
    const toast = document.getElementById('releaseToast');
    if (!button || !toast || button.disabled) return;

    button.textContent = 'Installing...';
    button.disabled = true;

    fetch('do_update.php')
        .then(response => response.json())
        .then(data => {
            toast.style.background = data.status === 'success' ? '#2ecc71' : '#e74c3c';
            toast.textContent = data.message || 'Release installation completed.';
            toast.style.display = 'block';
            if (data.status === 'success') {
                const banner = document.getElementById('updateBanner');
                if (banner) banner.style.display = 'none';
                setTimeout(() => location.reload(), 3500);
            } else {
                button.textContent = 'Install Release';
                button.disabled = false;
            }
        })
        .catch(() => {
            toast.style.background = '#e74c3c';
            toast.textContent = 'Release installation request failed.';
            toast.style.display = 'block';
            button.textContent = 'Install Release';
            button.disabled = false;
        });
}
</script>
