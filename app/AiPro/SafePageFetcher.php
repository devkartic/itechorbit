<?php

namespace App\AiPro;

use App\AiPro\Models\Site;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\HttpFoundation\IpUtils;

class SafePageFetcher
{
    public function validate(Site $site, string $url): array
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        if (($parts['scheme'] ?? '') !== 'https' || ! $host || $host !== strtolower(parse_url($site->url, PHP_URL_HOST) ?? '')
            || isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && $parts['port'] !== 443)) {
            throw new RuntimeException('Only HTTPS URLs on the configured website host may be imported.');
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $addresses = [$host];
        } else {
            $records = dns_get_record($host, DNS_A | DNS_AAAA);
            $addresses = array_values(array_filter(array_map(fn ($record) => $record['ip'] ?? $record['ipv6'] ?? null, $records ?: [])));
        }
        if (! $addresses) {
            throw new RuntimeException('Website DNS could not be resolved.');
        }
        foreach ($addresses as $address) {
            if (! $this->publicAddress($address)) {
                throw new RuntimeException('Private or reserved network destinations are not allowed.');
            }
        }

        return [$host, $addresses];
    }

    public function publicAddress(string $address): bool
    {
        if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        $ip = IpUtils::class;
        if (str_contains($address, ':')) {
            return $ip::checkIp($address, '2000::/3') && ! $ip::checkIp($address, ['2001::/23', '2001:db8::/32', '2002::/16', '3fff::/20']);
        }

        return ! $ip::checkIp($address, ['0.0.0.0/8', '10.0.0.0/8', '100.64.0.0/10', '127.0.0.0/8', '169.254.0.0/16',
            '172.16.0.0/12', '192.0.0.0/24', '192.0.2.0/24', '192.88.99.0/24', '192.168.0.0/16', '198.18.0.0/15',
            '198.51.100.0/24', '203.0.113.0/24', '224.0.0.0/4', '240.0.0.0/4']);
    }

    public function fetch(Site $site, string $url, bool $robots = true): string
    {
        if ($robots) {
            $rules = $this->fetch($site, 'https://'.parse_url($site->url, PHP_URL_HOST).'/robots.txt', false);
            if (! $this->allowedByRobots($rules, parse_url($url, PHP_URL_PATH) ?: '/')) {
                throw new RuntimeException('This URL is excluded by robots.txt.');
            }
        }
        for ($redirect = 0; $redirect <= 3; $redirect++) {
            [$host, $addresses] = $this->validate($site, $url);
            if ($robots && ! $this->allowedByRobots($rules, parse_url($url, PHP_URL_PATH) ?: '/')) {
                throw new RuntimeException('Redirect target is excluded by robots.txt.');
            }
            if (! extension_loaded('curl')) {
                throw new RuntimeException('The cURL extension is required for safe imports.');
            }
            $address = $addresses[0];
            $pinned = str_contains($address, ':') ? '['.$address.']' : $address;
            $response = Http::withUserAgent('iTechOrbitAI/1.0')->connectTimeout(5)->timeout(15)
                ->withOptions(['proxy' => '', 'allow_redirects' => false, 'curl' => [CURLOPT_RESOLVE => [$host.':443:'.$pinned]],
                    'on_headers' => function ($response) {
                        if ((int) $response->getHeaderLine('Content-Length') > 2097152) {
                            throw new RuntimeException('Page exceeds the import size limit.');
                        }
                    }, 'progress' => function ($total, $downloaded) {
                        if ($downloaded > 2097152) {
                            throw new RuntimeException('Page exceeds the import size limit.');
                        }
                    }])->get($url);
            if ($response->redirect()) {
                $location = $response->header('Location');
                $url = $this->absolute($url, $location ?: '');

                continue;
            }
            if (! $robots && $response->status() === 404) {
                return '';
            }
            $response->throw();
            $type = strtolower($response->header('Content-Type') ?? '');
            if (! str_contains($type, 'text/') && ! str_contains($type, 'xml')) {
                throw new RuntimeException('Only HTML, text and XML pages can be imported.');
            }
            $body = $response->body();
            if (strlen($body) > 2097152) {
                throw new RuntimeException('Page exceeds the import size limit.');
            }

            return $body;
        }
        throw new RuntimeException('Too many redirects.');
    }

    public function absolute(string $base, string $url): string
    {
        return (string) UriResolver::resolve(new Uri($base), new Uri($url));
    }

    public function allowedByRobots(string $text, string $path): bool
    {
        $groups = [];
        $agents = [];
        $rules = [];
        $hasRules = false;
        foreach (explode("\n", $text."\nUser-agent: end") as $line) {
            $line = trim(explode('#', $line, 2)[0]);
            if (! str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = array_map('trim', explode(':', $line, 2));
            $name = strtolower($name);
            if ($name === 'user-agent') {
                if ($hasRules) {
                    $groups[] = [$agents, $rules];
                    $agents = [];
                    $rules = [];
                    $hasRules = false;
                }
                $agents[] = strtolower($value);
            } elseif (in_array($name, ['allow', 'disallow'], true) && $agents) {
                $hasRules = true;
                if ($value !== '') {
                    $rules[] = [$name, $value];
                }
            }
        }
        $specific = array_filter($groups, fn ($g) => in_array('itechorbitai', $g[0], true));
        $specific = $specific ?: array_filter($groups, fn ($g) => in_array('sitesageai', $g[0], true));
        $selected = $specific ?: array_filter($groups, fn ($g) => in_array('*', $g[0], true));
        $best = -1;
        $allowed = true;
        foreach ($selected as [, $rules]) {
            foreach ($rules as [$name, $pattern]) {
                $end = str_ends_with($pattern, '$');
                $pattern = $end ? substr($pattern, 0, -1) : $pattern;
                $regex = '#^'.str_replace('\*', '.*', preg_quote($pattern, '#')).($end ? '$' : '').'#';
                $length = strlen(str_replace('*', '', $pattern));
                if (preg_match($regex, $path) && ($length > $best || ($length === $best && $name === 'allow'))) {
                    $best = $length;
                    $allowed = $name === 'allow';
                }
            }
        }

        return $allowed;
    }
}
