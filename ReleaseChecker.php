<?php

class ReleaseChecker
{
    public function __construct(
        private string $versionFile,
        private string $manifestUrl
    ) {
    }

    public function check(): array
    {
        $currentVersion = $this->readLocalVersion();
        $manifest = $this->readManifest();
        $latestVersion = $manifest['version'];
        $updateAvailable = version_compare(
            $this->normalizeVersion($latestVersion),
            $this->normalizeVersion($currentVersion),
            '>'
        );

        return [
            'status' => $updateAvailable ? 'update_available' : 'up_to_date',
            'message' => $updateAvailable ? 'A new update is available.' : 'TalaKlase is up to date.',
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'update_available' => $updateAvailable,
            'download_url' => $manifest['download_url'] ?? null,
            'release_notes' => $manifest['release_notes'] ?? '',
        ];
    }

    private function readLocalVersion(): string
    {
        if (!is_file($this->versionFile)) {
            throw new RuntimeException('Installed version.json was not found.');
        }

        $version = json_decode((string) file_get_contents($this->versionFile), true);
        if (!is_array($version) || empty($version['version']) || !is_string($version['version'])) {
            throw new RuntimeException('Installed version.json is invalid.');
        }

        return $version['version'];
    }

    private function readManifest(): array
    {
        if (!filter_var($this->manifestUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Release manifest URL is invalid.');
        }

        $context = stream_context_create([
            'http' => ['timeout' => 10],
            'https' => ['timeout' => 10],
        ]);
        $contents = @file_get_contents($this->manifestUrl, false, $context);
        $manifest = $contents === false ? null : json_decode($contents, true);

        if (!is_array($manifest) || empty($manifest['version']) || !is_string($manifest['version'])) {
            throw new RuntimeException('Release manifest is unavailable or invalid.');
        }

        return $manifest;
    }

    private function normalizeVersion(string $version): string
    {
        return preg_replace('/^[^0-9]*/', '', $version) ?: '0.0.0';
    }
}
