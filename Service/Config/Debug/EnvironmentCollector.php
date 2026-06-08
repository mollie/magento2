<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Config\Debug;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\State;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Mollie\Payment\Config;

class EnvironmentCollector implements CollectorInterface
{
    private const CDN_SIGNALS = [
        'Cloudflare'  => ['CF-Ray', 'CF-Connecting-IP'],
        'Fastly'      => ['Fastly-Client-IP', 'X-Served-By'],
        'CloudFront'  => ['X-Amz-Cf-Id'],
        'Akamai'      => ['X-Akamai-Edgescape', 'Akamai-Origin-Hop'],
        'Sucuri'      => ['X-Sucuri-ID', 'X-Sucuri-Cache'],
        'Varnish'     => ['X-Varnish'],
        'Imperva'     => ['X-Iinfo'],
    ];

    private const RELEVANT_EXTENSIONS = [
        'bcmath', 'curl', 'dom', 'gd', 'intl', 'mbstring', 'openssl', 'phar', 'soap', 'zip',
    ];

    private const INI_SETTINGS = [
        'memory_limit', 'max_execution_time', 'default_socket_timeout',
    ];

    public function __construct(
        private readonly ProductMetadataInterface $productMetadata,
        private readonly State $appState,
        private readonly Config $mollieConfig,
        private readonly Request $request,
    ) {
    }

    public function collect(): array
    {
        return ['environment.txt' => $this->render()];
    }

    public function getReadmeDescription(): string
    {
        return "- environment.txt\n"
            . "  Your Magento version and edition, PHP version, SAPI, OS, deploy mode,\n"
            . "  Mollie extension version, web server, CDN/proxy, PHP extensions,\n"
            . "  cURL/OpenSSL versions, and key PHP ini settings. No credentials.";
    }

    public function render(): string
    {
        $edition = (string)$this->productMetadata->getEdition();
        $mollieVersion = $this->mollieConfig->getVersion();

        $lines = [
            'Magento version: ' . $this->productMetadata->getVersion(),
            'Magento edition: ' . $this->describeEdition($edition),
            'PHP version: ' . PHP_VERSION,
            'PHP SAPI: ' . PHP_SAPI,
            'Mollie extension version: ' . ($mollieVersion !== null && $mollieVersion !== ''
                ? (string)$mollieVersion
                : 'unknown'),
            'OS: ' . php_uname('s') . ' ' . php_uname('r'),
            'Deploy mode: ' . $this->appState->getMode(),
            'Web server: ' . $this->detectWebServer(),
            'CDN / proxy: ' . $this->detectCdnAndProxies(),
            '',
        ];

        [$loaded, $missing] = $this->partitionExtensions();
        $lines[] = 'PHP extensions (loaded): ' . ($loaded !== [] ? implode(', ', $loaded) : 'none');
        $lines[] = 'PHP extensions (missing): ' . ($missing !== [] ? implode(', ', $missing) : 'none');
        $lines[] = 'cURL: ' . $this->describeCurl();
        $lines[] = 'OpenSSL: ' . (defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'unavailable');
        $lines[] = '';

        foreach (self::INI_SETTINGS as $setting) {
            $lines[] = $setting . ': ' . ini_get($setting);
        }

        return implode("\n", $lines) . "\n";
    }

    private function partitionExtensions(): array
    {
        $loaded = [];
        $missing = [];
        foreach (self::RELEVANT_EXTENSIONS as $ext) {
            if (extension_loaded($ext)) {
                $loaded[] = $ext;
            } else {
                $missing[] = $ext;
            }
        }
        return [$loaded, $missing];
    }

    private function describeCurl(): string
    {
        if (!function_exists('curl_version')) {
            return 'unavailable';
        }
        $info = curl_version();
        return $info['version'] . ' (ssl: ' . $info['ssl_version'] . ')';
    }

    private function detectWebServer(): string
    {
        $software = $this->request->getServer('SERVER_SOFTWARE');

        return $software !== null ? (string)$software : 'unknown';
    }

    private function detectCdnAndProxies(): string
    {
        $detected = [];

        foreach (self::CDN_SIGNALS as $name => $headers) {
            foreach ($headers as $header) {
                if ($this->request->getHeader($header)) {
                    $detected[] = $name;
                    break;
                }
            }
        }

        if ($detected === [] && $this->request->getHeader('X-Forwarded-For')) {
            $detected[] = 'unknown proxy (X-Forwarded-For present)';
        }

        return $detected !== [] ? implode(', ', $detected) : 'none detected';
    }

    private function describeEdition(string $edition): string
    {
        $friendly = match ($edition) {
            'Community' => 'Open Source',
            'Enterprise' => 'Commerce',
            default => $edition,
        };

        if ($friendly === $edition || $edition === '') {
            return $friendly;
        }

        return $friendly . ' (' . $edition . ')';
    }
}
