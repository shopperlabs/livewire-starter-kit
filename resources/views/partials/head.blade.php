<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>
<meta name="robots" content="index, follow" />
<link rel="canonical" href="{{ url()->current() }}" />

<link rel="icon" href="/favicon.ico" sizes="any" />
<link rel="icon" href="/favicon.svg" type="image/svg+xml" />
<link rel="apple-touch-icon" href="/apple-touch-icon.png" />

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=figtree:400,600,800,900&display=swap" rel="stylesheet" />
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
