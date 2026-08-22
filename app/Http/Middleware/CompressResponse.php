<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gzips the HTML document before IIS sends it.
 *
 * WHY THIS IS PHP AND NOT AN IIS SETTING
 * IIS compresses static files here and skips every PHP response. Static and
 * dynamic compression are two different IIS modules. The static one is
 * installed; the dynamic one is not — `compdyn.dll` is absent from
 * C:\WINDOWS\System32\inetsrv, and applicationHost.config holds no
 * <dynamicTypes> list. So no line in any web.config can switch it on. Turning
 * it on needs a Windows role feature installed by an administrator.
 * See .scratch/contract-detail-page-perf/issues/17-gzip-the-html-document.md.
 *
 * This middleware does the same job inside PHP, in the repo, with no admin.
 *
 * WHAT IT COSTS
 * Level 6 turns the 326 KB contract detail page into 35 KB — 9.2x — for about
 * 5 ms of CPU. Measured on the real document.
 *
 * WHAT IT DELIBERATELY LEAVES ALONE
 *  - Any response that already carries a Content-Encoding.
 *  - StreamedResponse and BinaryFileResponse. Reading either one into a string
 *    to compress it would load a whole contract attachment into memory, and
 *    a StreamedResponse has no body to read at this point anyway.
 *  - Anything that is not text/html, application/json or text/plain. Fonts and
 *    images are already compressed inside, so gzipping them burns CPU for
 *    nothing.
 *  - Bodies under MIN_BYTES. Below about a kilobyte the gzip header costs more
 *    than the saving.
 *  - Clients that do not ask for gzip in Accept-Encoding.
 */
class CompressResponse
{
    /**
     * zlib level. 6 is gzencode's own default. Measured on the 326 KB contract
     * detail page: level 1 gives 42,790 bytes in 2.0 ms, level 6 gives 35,432
     * in 4.6 ms, level 9 gives 34,648 in 17.2 ms. Level 9 pays 12 ms more for
     * 784 bytes, so 6 is the knee of the curve.
     */
    private const LEVEL = 6;

    /**
     * Do not compress a body smaller than this. One TCP segment is about
     * 1,400 bytes, so nothing under it can save a round trip.
     */
    private const MIN_BYTES = 1024;

    /** Content types worth compressing. Matched against the type before the ';'. */
    private const TYPES = [
        'text/html',
        'text/plain',
        'text/css',
        'text/xml',
        'application/json',
        'application/javascript',
        'application/xml',
    ];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! $this->shouldCompress($request, $response)) {
            return $response;
        }

        $plain = $response->getContent();

        // getContent() returns false on a response that cannot give one.
        if (! is_string($plain) || strlen($plain) < self::MIN_BYTES) {
            return $response;
        }

        $startedAt = microtime(true);
        $gzipped = gzencode($plain, self::LEVEL);
        $costMs = (microtime(true) - $startedAt) * 1000;

        // gzencode returns false if zlib fails. Send the page uncompressed
        // rather than send nothing.
        if ($gzipped === false) {
            Log::warning('CompressResponse: gzencode failed, sending plain', [
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

        // Tells every cache that the body depends on the request's
        // Accept-Encoding, so a proxy cannot hand a gzipped body to a client
        // that did not ask for one.
        //
        // Added, never set. headers->set() would replace a Vary the response
        // already carries - Cookie, Accept-Language - and dropping one of those
        // is how a shared cache serves one user's page to another. Nothing in
        // this app sets Vary today; this keeps that true if something starts.
        $this->addVaryAcceptEncoding($response);

        Log::debug('CompressResponse: gzipped', [
            'path' => $request->path(),
            'plain_bytes' => strlen($plain),
            'gzip_bytes' => strlen($gzipped),
            'cost_ms' => round($costMs, 2),
        ]);

        return $response;
    }

    /**
     * Add Accept-Encoding to the response's Vary header without losing what is
     * already there. Matching is case-insensitive because header values are.
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

        return in_array($type, self::TYPES, true);
    }
}
