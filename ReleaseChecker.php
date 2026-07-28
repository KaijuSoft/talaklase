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
            'channel' => $manifest['channel'] ?? 'stable',
            'engine' => $manifest['engine'] ?? null,
            'release_date' => $manifest['release_date'] ?? null,
            'minimum_php' => $manifest['minimum_php'] ?? null,
            'minimum_mysql' => $manifest['minimum_mysql'] ?? null,
            'download_url' => $manifest['download_url'] ?? null,
            'release_notes' => $manifest['release_notes'] ?? '',
        ];
    }

    /**
     * Returns the validated release manifest for the installer.
     *
     * @return array<string, mixed>
     */
    public function readReleaseManifest(): array
    {
        return $this->readManifest();
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
        $url = filter_var($this->manifestUrl, FILTER_VALIDATE_URL);
        $scheme = is_string($url) ? strtolower((string) parse_url($url, PHP_URL_SCHEME)) : '';

        if ($url === false || !in_array($scheme, ['http', 'https'], true)) {
            throw new RuntimeException('Release manifest URL is invalid.');
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'ignore_errors' => true,
            ],
            'https' => [
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        $contents = @file_get_contents($url, false, $context);
        $responseCode = $this->responseCode($http_response_header ?? []);

        if ($responseCode === 404) {
            throw new RuntimeException('No published GitHub Release was found.');
        }

        if ($contents === false || ($responseCode !== null && $responseCode >= 400)) {
            throw new RuntimeException('Unable to download release manifest.');
        }

        try {
            $manifest = json_decode((string) $contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException('Release manifest contains invalid JSON.');
        }

        if (!is_array($manifest)) {
            throw new RuntimeException('Release manifest must contain a JSON object.');
        }

        if (empty($manifest['version']) || !is_string($manifest['version'])) {
            throw new RuntimeException('Release manifest is missing the required version field.');
        }

        if (isset($manifest['download_url']) && !is_string($manifest['download_url'])) {
            throw new RuntimeException('Release manifest contains an invalid download_url field.');
        }

        return $manifest;
    }

    /**
     * @param array<int, string> $headers
     */
    private function responseCode(array $headers): ?int
    {
        $responseCode = null;

        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/i', $header, $matches) === 1) {
                $responseCode = (int) $matches[1];
            }
        }

        return $responseCode;
    }

    private function normalizeVersion(string $version): string
    {
        return preg_replace('/^[^0-9]*/', '', $version) ?: '0.0.0';
    }
}
