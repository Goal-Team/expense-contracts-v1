<?php

namespace App\Http\Middleware;

use App\Support\ResponseCompressor;
use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;

/**
 * Gzips the HTTP response before IIS sends it, using config/compression.php.
 *
 * This is CompressResponse with the three fixed numbers turned into config, so
 * a client can tune the level, the minimum size and the content types without
 * editing code. The compression itself is one shared class, App\Support\
 * ResponseCompressor, which both middlewares call - no copied code, so the two
 * cannot drift apart.
 *
 * The defaults in config/compression.php are the values CompressResponse
 * hardcodes, so with no env keys set this behaves the same way.
 *
 * WHY THIS IS PHP AND NOT AN IIS SETTING
 * IIS compresses static files here and skips every PHP response. Static and
 * dynamic compression are two different IIS modules. The static one is
 * installed; the dynamic one is not - compdyn.dll is absent from
 * C:\WINDOWS\System32\inetsrv, and applicationHost.config holds no
 * <dynamicTypes> list. So no line in any web.config can switch it on.
 * See .scratch/contract-detail-page-perf/issues/17-gzip-the-html-document.md.
 *
 * WHY NOT A COMPOSER PACKAGE
 * Every package was checked. None fits this app. See
 * .scratch/contract-detail-page-perf/issues/23-response-compression-package.md.
 */
class CompressResponsex
{
    private bool $enabled;

    private ResponseCompressor $compressor;

    public function __construct(Config $config)
    {
        $this->enabled = (bool) $config->get('compression.enabled', true);

        $this->compressor = new ResponseCompressor(
            (int) $config->get('compression.level', 6),
            (int) $config->get('compression.min_bytes', 1024),
            (array) $config->get('compression.types', []),
        );
    }

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! $this->enabled) {
            return $response;
        }

        return $this->compressor->apply($request, $response);
    }
}
