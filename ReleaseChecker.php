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

        $contents = false;
        $responseCode = null;
        $finalUrl = $url;
        $curlError = null;

        if (function_exists('curl_init')) {
            [$contents, $responseCode, $finalUrl, $curlError] = $this->readManifestWithCurl($url);
        } else {
            [$contents, $responseCode, $finalUrl] = $this->readManifestWithStream($url);
        }

        if ($responseCode === 404) {
            throw new RuntimeException('No published GitHub Release was found.');
        }

        if ($contents === false || ($responseCode !== null && $responseCode >= 400)) {
            if ($curlError !== null) {
                error_log(sprintf(
                    'ReleaseChecker::readManifest cURL error for %s (final URL: %s, HTTP %s): %s',
                    $url,
                    $finalUrl,
                    (string) ($responseCode ?? 'unknown'),
                    $curlError
                ));
            }
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
     * @return array{0:false|string,1:?int,2:string,3:?string}
     */
    private function readManifestWithCurl(string $url): array
    {
        $handle = curl_init($url);

        if ($handle === false) {
            return [false, null, $url, 'Unable to initialize cURL.'];
        }

        curl_setopt_array($handle, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'TalaKlase Release Manager',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
            CURLOPT_HEADER => true,
        ]);

        $response = curl_exec($handle);
        $curlError = curl_error($handle);
        $curlErrorNo = curl_errno($handle);
        $responseCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $finalUrl = (string) (curl_getinfo($handle, CURLINFO_EFFECTIVE_URL) ?: $url);
        $headerSize = (int) (curl_getinfo($handle, CURLINFO_HEADER_SIZE) ?: 0);

        curl_close($handle);

        if ($response === false || $curlErrorNo !== 0) {
            return [false, $responseCode ?: null, $finalUrl, $curlError !== '' ? $curlError : 'cURL request failed.'];
        }

        $body = is_string($response) ? substr($response, $headerSize) : false;

        return [$body, $responseCode ?: null, $finalUrl, null];
    }

    /**
     * @return array{0:false|string,1:?int,2:string}
     */
    private function readManifestWithStream(string $url): array
    {
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
        $finalUrl = $this->extractFinalUrl($http_response_header ?? [], $url);

        return [$contents, $responseCode, $finalUrl];
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

    /**
     * @param array<int, string> $headers
     */
    private function extractFinalUrl(array $headers, string $fallbackUrl): string
    {
        foreach (array_reverse($headers) as $header) {
            if (preg_match('/^Location:\s*(.+)$/i', $header, $matches) === 1) {
                return trim($matches[1]);
            }
        }

        return $fallbackUrl;
    }

    private function normalizeVersion(string $version): string
    {
        return preg_replace('/^[^0-9]*/', '', $version) ?: '0.0.0';
    }
}
