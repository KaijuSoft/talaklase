<?php

if (!function_exists('talaklase_app_version')) {
    function talaklase_app_version(): array
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        $versionFile = __DIR__ . '/../version.json';
        $defaults = [
            'version' => 'unknown',
            'engine' => 'unknown',
        ];

        if (!is_file($versionFile)) {
            return $cached = $defaults;
        }

        $raw = file_get_contents($versionFile);
        if ($raw === false) {
            return $cached = $defaults;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $cached = $defaults;
        }

        return $cached = array_merge($defaults, array_intersect_key($decoded, $defaults));
    }
}

$appVersion = talaklase_app_version();
?>

<footer class="app-footer" aria-label="Application footer">
  <div class="app-footer__content">
    <div class="app-footer__line">
      TalaKlase v<?= htmlspecialchars((string) $appVersion['version']) ?>
    </div>
    <div class="app-footer__line">
      Powered by TALA Engine <?= htmlspecialchars((string) $appVersion['engine']) ?>
    </div>
    <div class="app-footer__line app-footer__brand">
      Developed by KaijuSoft
    </div>
  </div>
</footer>
