<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Movix') }} — Self-hosted movie streaming</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @fonts

    @vite(['resources/css/app.css'])

    <style>
        :root {
            --ink:      #f4f2ef;
            --ink-dim:  #a8a29a;
            --ink-mute: #6b6660;
            --stage:    #08080a;
            --stage-2:  #0d0d10;
            --line:     rgba(255, 255, 255, .08);
            --grad-a:   #7c3aed;
            --grad-b:   #ec4899;
            --serif:    'Instrument Serif', ui-serif, Georgia, serif;
            --sans:     'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
        }

        * { box-sizing: border-box; }

        html, body { height: 100%; }

        body {
            margin: 0;
            background: var(--stage);
            color: var(--ink);
            font-family: var(--sans);
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
            overflow-x: hidden;
        }

        /* ---- Stage / atmosphere -------------------------------------- */
        .stage {
            position: relative;
            min-height: 100svh;
            display: flex;
            flex-direction: column;
            isolation: isolate;
        }

        /* soft off-center brand glow */
        .glow {
            position: fixed;
            inset: 0;
            z-index: -2;
            pointer-events: none;
            background:
                radial-gradient(52vw 52vw at 72% 18%, color-mix(in oklab, var(--grad-a) 38%, transparent), transparent 62%),
                radial-gradient(46vw 46vw at 18% 96%, color-mix(in oklab, var(--grad-b) 30%, transparent), transparent 60%);
            filter: blur(30px) saturate(120%);
            opacity: .5;
            animation: drift 26s ease-in-out infinite alternate;
        }

        @keyframes drift {
            from { transform: translate3d(-2%, -1%, 0) scale(1); }
            to   { transform: translate3d(3%, 2%, 0) scale(1.08); }
        }

        /* vignette to keep focus centered, cinema-dark edges */
        .vignette {
            position: fixed;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            background: radial-gradient(120% 90% at 50% 42%, transparent 40%, rgba(0,0,0,.55) 100%);
        }

        /* film grain */
        .grain {
            position: fixed;
            inset: -50%;
            z-index: 0;
            pointer-events: none;
            opacity: .05;
            mix-blend-mode: screen;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='120' height='120'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2' stitchTiles='stitch'/></filter><rect width='100%25' height='100%25' filter='url(%23n)'/></svg>");
            animation: grain 6s steps(4) infinite;
        }

        @keyframes grain {
            0%,100% { transform: translate(0,0); }
            25%     { transform: translate(-4%, 3%); }
            50%     { transform: translate(3%, -5%); }
            75%     { transform: translate(-3%, 4%); }
        }

        /* viewfinder framing brackets */
        .frame {
            position: fixed;
            z-index: 1;
            width: 34px;
            height: 34px;
            border: 1px solid var(--line);
            pointer-events: none;
        }
        .frame.tl { top: 22px; left: 22px; border-right: 0; border-bottom: 0; }
        .frame.tr { top: 22px; right: 22px; border-left: 0; border-bottom: 0; }
        .frame.bl { bottom: 22px; left: 22px; border-right: 0; border-top: 0; }
        .frame.br { bottom: 22px; right: 22px; border-left: 0; border-top: 0; }

        /* ---- Chrome -------------------------------------------------- */
        .shell {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1120px;
            margin: 0 auto;
            padding: 34px clamp(28px, 6vw, 64px);
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            font-weight: 600;
            font-size: 1.06rem;
            letter-spacing: -.01em;
            text-decoration: none;
            color: var(--ink);
        }
        .brand svg { height: 21px; width: auto; border-radius: 6px; }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: .72rem;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--ink-mute);
        }
        .status .dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: #34d399;
            box-shadow: 0 0 0 0 rgba(52,211,153,.5);
            animation: pulse 2.4s ease-out infinite;
        }
        @keyframes pulse {
            0%   { box-shadow: 0 0 0 0 rgba(52,211,153,.5); }
            70%  { box-shadow: 0 0 0 7px rgba(52,211,153,0); }
            100% { box-shadow: 0 0 0 0 rgba(52,211,153,0); }
        }

        /* ---- Hero ---------------------------------------------------- */
        .hero {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 8vh 0 6vh;
            max-width: 820px;
        }

        .eyebrow {
            font-size: .74rem;
            letter-spacing: .32em;
            text-transform: uppercase;
            color: var(--ink-dim);
            display: inline-flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 30px;
        }
        .eyebrow::before {
            content: "";
            width: 42px; height: 1px;
            background: linear-gradient(90deg, var(--grad-a), var(--grad-b));
        }

        h1 {
            font-family: var(--serif);
            font-weight: 400;
            font-size: clamp(3rem, 8.5vw, 6.4rem);
            line-height: .96;
            letter-spacing: -.015em;
            margin: 0;
            color: var(--ink);
        }
        h1 .accent {
            font-style: italic;
            background: linear-gradient(105deg, var(--grad-a) 10%, var(--grad-b) 90%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .lede {
            margin: 30px 0 0;
            max-width: 33rem;
            font-size: clamp(1.02rem, 1.6vw, 1.18rem);
            line-height: 1.6;
            color: var(--ink-dim);
        }

        .actions {
            margin-top: 42px;
            display: flex;
            align-items: center;
            gap: 26px;
            flex-wrap: wrap;
        }

        .btn {
            --bg: var(--ink);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 26px;
            border-radius: 999px;
            background: var(--bg);
            color: var(--stage);
            font-weight: 600;
            font-size: .95rem;
            text-decoration: none;
            border: 1px solid transparent;
            transition: transform .25s cubic-bezier(.2,.8,.2,1), box-shadow .25s, background .25s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 40px -14px rgba(236,72,153,.55);
            background: linear-gradient(100deg, #fff, #ffe9f4);
        }
        .btn svg { width: 16px; height: 16px; }

        .ghost {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: var(--ink-dim);
            text-decoration: none;
            font-size: .95rem;
            font-weight: 500;
            transition: color .2s, gap .2s;
        }
        .ghost:hover { color: var(--ink); gap: 13px; }
        .ghost svg { width: 15px; height: 15px; }

        /* ---- Feature strip ------------------------------------------- */
        .rail {
            border-top: 1px solid var(--line);
            display: grid;
            grid-template-columns: repeat(4, 1fr);
        }
        .cell {
            padding: 26px 30px;
            border-right: 1px solid var(--line);
        }
        .cell:first-child { padding-left: 0; }
        .cell:last-child { padding-right: 0; border-right: 0; }
        .cell .k {
            font-family: var(--serif);
            font-style: italic;
            font-size: 1.55rem;
            line-height: 1;
            background: linear-gradient(120deg, var(--grad-a), var(--grad-b));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .cell .t {
            margin-top: 12px;
            font-weight: 600;
            font-size: .94rem;
        }
        .cell .d {
            margin-top: 5px;
            font-size: .82rem;
            line-height: 1.5;
            color: var(--ink-mute);
        }

        .footer {
            padding: 22px 0 4px;
            font-size: .78rem;
            color: var(--ink-mute);
            display: flex;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .footer .stack { letter-spacing: .04em; }

        /* ---- Entrance choreography ----------------------------------- */
        [data-rise] {
            opacity: 0;
            transform: translateY(16px);
            animation: rise .9s cubic-bezier(.2,.8,.2,1) forwards;
            animation-delay: var(--d, 0s);
        }
        @keyframes rise {
            to { opacity: 1; transform: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            .glow, .grain, .status .dot { animation: none; }
            [data-rise] { animation: none; opacity: 1; transform: none; }
        }

        @media (max-width: 760px) {
            .frame { display: none; }
            .rail { grid-template-columns: 1fr 1fr; }
            .cell { padding: 22px 20px; }
            .cell:nth-child(odd) { padding-left: 0; }
            .cell:nth-child(even) { padding-right: 0; border-right: 0; }
            .cell:nth-child(1), .cell:nth-child(2) { border-bottom: 1px solid var(--line); }
        }
        @media (max-width: 460px) {
            .status { display: none; }
        }
    </style>
</head>
<body>
    <div class="stage">
        <div class="glow"></div>
        <div class="vignette"></div>
        <div class="grain"></div>
        <span class="frame tl"></span>
        <span class="frame tr"></span>
        <span class="frame bl"></span>
        <span class="frame br"></span>

        <div class="shell">
            <header class="topbar" data-rise style="--d:.05s">
                <a href="{{ route('home') }}" class="brand">
                    <x-app-logo-icon />
                    Movix
                </a>
                <span class="status">
                    <span class="dot"></span>
                    Self-hosted · Online
                </span>
            </header>

            <main class="hero">
                <span class="eyebrow" data-rise style="--d:.15s">Private movie streaming</span>

                <h1 data-rise style="--d:.25s">
                    Your library,<br>
                    <span class="accent">streaming</span> from<br>
                    your own machine.
                </h1>

                <p class="lede" data-rise style="--d:.4s">
                    Movix turns a folder of videos into a private, browsable cinema. Finder-style
                    navigation, in-browser playback with instant seeking, and passwordless sign-in —
                    no transcoding, no cloud, nothing leaving your machine.
                </p>

                <div class="actions" data-rise style="--d:.55s">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn">
                            Open library
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </a>
                        <span class="ghost">Signed in as {{ Str::of(auth()->user()->name)->before(' ') ?: auth()->user()->email }}</span>
                    @else
                        <a href="{{ route('login') }}" class="btn">
                            Sign in
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </a>
                        <a href="{{ route('login') }}" class="ghost">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            Sign in with a passkey
                        </a>
                    @endauth
                </div>
            </main>

            <section class="rail" data-rise style="--d:.7s" aria-label="Features">
                <div class="cell">
                    <div class="k">01</div>
                    <div class="t">Finder-style browser</div>
                    <div class="d">Nested folders, breadcrumbs, sort &amp; instant search.</div>
                </div>
                <div class="cell">
                    <div class="k">02</div>
                    <div class="t">Instant-seek playback</div>
                    <div class="d">HTTP range streaming — smooth scrubbing, no waiting.</div>
                </div>
                <div class="cell">
                    <div class="k">03</div>
                    <div class="t">Resume anywhere</div>
                    <div class="d">Every video remembers exactly where you left off.</div>
                </div>
                <div class="cell">
                    <div class="k">04</div>
                    <div class="t">Passkeys &amp; 2FA</div>
                    <div class="d">Passwordless WebAuthn with two-factor fallback.</div>
                </div>
            </section>

            <footer class="footer" data-rise style="--d:.85s">
                <span class="stack">Laravel · Livewire · Flux · Tailwind</span>
                <span>© {{ date('Y') }} Movix — streams straight from disk</span>
            </footer>
        </div>
    </div>
</body>
</html>
