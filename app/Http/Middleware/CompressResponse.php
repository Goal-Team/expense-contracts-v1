<?php

namespace App\Http\Middleware;

use App\Support\ResponseCompressor;
use Closure;
use Illuminate\Http\Request;

/**
 * Gzips the HTML document before IIS sends it, with fixed settings.
 *
 * THIS IS THE OLD ONE. CompressResponsex replaces it and reads the same three
 * numbers from config/compression.php instead of hardcoding them. This class
 * stays so the two can be compared on the same page and the same data. Swap the
 * one line in app/Http/Kernel.php to switch between them.
 *
 * The gzip work itself now lives in App\Support\ResponseCompressor, which both
 * middlewares call. The numbers below are unchanged, so this class behaves
 * exactly as it did before - it just no longer holds its own copy of the code.
 *
 * WHY THIS IS PHP AND NOT AN IIS SETTING
 * IIS compresses static files here and skips every PHP response. Static and
 * dynamic compression are two different IIS modules. The static one is
 * installed; the dynamic one is not - compdyn.dll is absent from
 * C:\WINDOWS\System32\inetsrv, and applicationHost.config holds no
 * <dynamicTypes> list. So no line in any web.config can switch it on. Turning
 * it on needs a Windows role feature installed by an administrator.
 * See .scratch/contract-detail-page-perf/issues/17-gzip-the-html-document.md.
 *
 * WHAT IT COSTS
 * Level 6 turns the 326 KB contract detail page into 35 KB - 9.2x - for about
 * 5 ms of CPU. Measured on the real document.
 *
 * WHAT IT DELIBERATELY LEAVES ALONE
 * See App\Support\ResponseCompressor. Streamed and binary responses,
 * already-encoded responses, empty responses, tiny bodies, content types that
 * are already compressed, and clients that did not ask for gzip.
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

        $compressor = new ResponseCompressor(self::LEVEL, self::MIN_BYTES, self::TYPES);

        return $compressor->apply($request, $response);
    }
}
