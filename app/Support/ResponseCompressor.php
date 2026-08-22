<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gzips a finished HTTP response.
 *
 * The compression itself, kept apart from the middleware that calls it so the
 * refusal rules below live in one place and can be tested on their own.
 * App\Http\Middleware\CompressResponse hands it the level, the minimum size
 * and the content types from config/compression.php.
 *
 * WHAT IT DELIBERATELY LEAVES ALONE
 *  - Any response that already carries a Content-Encoding.
 *  - StreamedResponse and BinaryFileResponse. Reading either one into a string
 *    to compress it would load a whole contract attachment into memory, and
 *    a StreamedResponse has no body to read at this point anyway.
 *  - Anything whose content type is not in the allowed list.
 *  - Bodies under the minimum size.
 *  - Clients that do not ask for gzip in Accept-Encoding.
 *  - Empty responses, such as 204 and 304.
 */
class ResponseCompressor
{
    /** Lowest and highest zlib level. A value outside this range is clamped. */
    private const MIN_LEVEL = 1;

    private const MAX_LEVEL = 9;

    private int $level;

    private int $minBytes;

    /** @var array<int, string> Lowercased content types worth compressing. */
    private array $types;

    /**
     * @param  array<int, string>  $types
     */
    public function __construct(int $level, int $minBytes, array $types)
    {
        $this->level = max(self::MIN_LEVEL, min(self::MAX_LEVEL, $level));
        $this->minBytes = max(0, $minBytes);
        $this->types = array_map('strtolower', $types);
    }

    /**
     * Gzip the response if it is worth gzipping. Returns the same response
     * either way, so a caller can always return what it gets back.
     */
    public function apply(Request $request, mixed $response): mixed
    {
        if (! $this->shouldCompress($request, $response)) {
            return $response;
        }

        $plain = $response->getContent();

        // getContent() returns false on a response that cannot give one.
        if (! is_string($plain) || strlen($plain) < $this->minBytes) {
            return $response;
        }

        $startedAt = microtime(true);
        $gzipped = gzencode($plain, $this->level);
        $costMs = (microtime(true) - $startedAt) * 1000;

        // gzencode returns false if zlib fails. Send the page uncompressed
        // rather than send nothing.
        if ($gzipped === false) {
            Log::warning('ResponseCompressor: gzencode failed, sending plain', [
                'path' => $request->path(),
                'bytes' => strlen($plain),
            ]);

            return $response;
        }

        // A body that grows is already compressed or too random to help.
        if (strlen($gzipped) >= strlen($plain)) {
            return $response;
        }

        $response->setContent($gzipped);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($gzipped));

        $this->addVaryAcceptEncoding($response);

        Log::debug('ResponseCompressor: gzipped', [
            'path' => $request->path(),
            'plain_bytes' => strlen($plain),
            'gzip_bytes' => strlen($gzipped),
            'cost_ms' => round($costMs, 2),
            'level' => $this->level,
        ]);

        return $response;
    }

    /**
     * Add Accept-Encoding to the response's Vary header without losing what is
     * already there. Matching is case-insensitive because header values are.
     *
     * Added, never set. headers->set() would replace a Vary the response
     * already carries - Cookie, Accept-Language - and dropping one of those is
     * how a shared cache serves one user's page to another. Nothing in this app
     * sets Vary today; this keeps that true if something starts.
     */
    private function addVaryAcceptEncoding(Response $response): void
    {
        $existing = array_filter(array_map(
            'trim',
            explode(',', (string) $response->headers->get('Vary', ''))
        ));

        foreach ($existing as $field) {
            // Already covered, either by name or by the wildcard.
            if (strcasecmp($field, 'Accept-Encoding') === 0 || $field === '*') {
                return;
            }
        }

        $existing[] = 'Accept-Encoding';

        $response->headers->set('Vary', implode(', ', $existing));
    }

    private function shouldCompress(Request $request, mixed $response): bool
    {
        if (! $response instanceof Response) {
            return false;
        }

        // No body to read, or a body we must not pull into memory.
        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return false;
        }

        // Something upstream already encoded it.
        if ($response->headers->has('Content-Encoding')) {
            return false;
        }

        // 204 and 304 carry no body.
        if ($response->isEmpty()) {
            return false;
        }

        if (! str_contains(strtolower($request->headers->get('Accept-Encoding', '')), 'gzip')) {
            return false;
        }

        $type = strtolower(trim(explode(';', (string) $response->headers->get('Content-Type', ''))[0]));

        return in_array($type, $this->types, true);
    }
}
