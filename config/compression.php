<?php

/*
|--------------------------------------------------------------------------
| Response compression
|--------------------------------------------------------------------------
|
| Settings for App\Http\Middleware\CompressResponsex. It gzips the finished
| HTTP response inside PHP.
|
| WHY THIS IS PHP AND NOT A WEB SERVER SETTING
| IIS on this server compresses static files and skips every PHP response.
| Static and dynamic compression are two different IIS modules. The static one
| is installed; the dynamic one is not - compdyn.dll is absent. So no line in
| any web.config can switch it on. See
| .scratch/contract-detail-page-perf/issues/17-gzip-the-html-document.md.
|
| Every value here has an env key, so a client can tune compression without
| editing code.
|
*/

return [

    /*
    | Master switch. Set RESPONSE_COMPRESSION_ENABLED=false to send every
    | response uncompressed. Use this if the web server starts to compress
    | dynamic responses itself, so the work is not done twice.
    */
    'enabled' => env('RESPONSE_COMPRESSION_ENABLED', true),

    /*
    | zlib level, 1 to 9. 6 is gzencode's own default and the knee of the
    | curve. Measured on the 326 KB contract detail page: level 1 gives 42,790
    | bytes in 2.0 ms, level 6 gives 35,432 in 4.6 ms, level 9 gives 34,648 in
    | 17.2 ms. Level 9 pays 12 ms more for 784 bytes.
    |
    | Values outside 1-9 are clamped, so a bad env value cannot break a page.
    */
    'level' => env('RESPONSE_COMPRESSION_LEVEL', 6),

    /*
    | Do not compress a body smaller than this many bytes. One TCP segment is
    | about 1,400 bytes, so nothing under it can save a round trip, and below
    | about a kilobyte the gzip header costs more than the saving.
    */
    'min_bytes' => env('RESPONSE_COMPRESSION_MIN_BYTES', 1024),

    /*
    | Content types worth compressing. Matched against the type before the
    | ';', lowercased. Everything else is left alone - fonts, images and PDFs
    | are already compressed inside, so gzipping them burns CPU for nothing.
    |
    | RESPONSE_COMPRESSION_TYPES overrides this whole list with a
    | comma-separated one, for a client who can only edit .env:
    |
    |   RESPONSE_COMPRESSION_TYPES="text/html,application/json"
    */
    'types' => array_filter(array_map(
        'trim',
        explode(',', (string) env('RESPONSE_COMPRESSION_TYPES', implode(',', [
            'text/html',
            'text/plain',
            'text/css',
            'text/xml',
            'application/json',
            'application/javascript',
            'application/xml',
        ])))
    )),

    /*
    | NO BROTLI HERE ON PURPOSE
    | Brotli beats gzip by 15-20%, but it needs the brotli PHP extension.
    | brotli_compress() does not exist on this PHP 8.3.8 build, so there is
    | nothing to switch on. Install the extension first, then add it.
    */

];
