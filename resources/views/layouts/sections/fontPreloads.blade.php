{{--
  Starts the three fonts the template actually uses at the same moment as the stylesheets,
  instead of waiting for a stylesheet to be parsed before they are discovered.

  Why this exists. A font referenced from inside a CSS file cannot be requested until that CSS
  has arrived and been parsed. Measured on the dashboard, cold cache, 2026-08-21: the first
  resource starts at 1,934 ms, the icon stylesheet finishes at 2,115 ms, and the icon font only
  starts at 2,381 ms - about 450 ms of dead time on a request the browser could have started
  straight away.

  That dead time is visible, not just slow. The built icon CSS sets no font-display, so the
  browser default applies, which blocks: an <i class="ti ti-file"> renders as nothing at all
  until the font lands. Icons were invisible from 1,936 ms to 2,787 ms and then appeared. This
  is what "the icons load late and do not swap" was.

  font-display is deliberately left alone. `swap` would show a fallback box in place of every
  icon and then replace it - worse to look at than a short gap. Preloading shortens the gap
  instead of filling it with rubbish.

  Only these three. Preloading a font the page does not use is worse than not preloading at all:
  it competes for bandwidth and the browser warns about it. This list is what the dashboard was
  measured pulling. Every other weight of Font Awesome, and every other tabler format
  (eot/woff/ttf/svg), is a fallback for browsers this app does not support, and is never
  requested.

  crossorigin is required even though the fonts are same-origin. A font is fetched in CORS mode,
  so a preload without it is a different request from the real one and the file downloads twice.
--}}
<link rel="preload" as="font" type="font/woff2" crossorigin
      href="{{ Vite::asset('resources/assets/vendor/fonts/tabler/tabler-icons.woff2') }}" />
<link rel="preload" as="font" type="font/woff2" crossorigin
      href="{{ Vite::asset('resources/assets/vendor/fonts/fontawesome/fa-brands-400.woff2') }}" />
<link rel="preload" as="font" type="font/woff2" crossorigin
      href="{{ asset('assets/fonts/public-sans/public-sans-latin-variable.woff2') }}" />
