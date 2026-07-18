<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')

        <style>
            :root {
                --stage:  #08080a;
                --line:   rgba(255, 255, 255, .08);
                --grad-a: #7c3aed;
                --grad-b: #ec4899;
                --serif:  'Instrument Serif', ui-serif, Georgia, serif;
            }

            .cinema {
                position: relative;
                min-height: 100svh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 40px 24px;
                isolation: isolate;
                overflow: hidden;
            }

            /* soft off-center brand glow */
            .cinema-glow {
                position: fixed;
                inset: 0;
                z-index: -2;
                pointer-events: none;
                background:
                    radial-gradient(48vw 48vw at 50% -8%, color-mix(in oklab, var(--grad-a) 40%, transparent), transparent 60%),
                    radial-gradient(42vw 42vw at 82% 100%, color-mix(in oklab, var(--grad-b) 30%, transparent), transparent 58%),
                    radial-gradient(42vw 42vw at 12% 92%, color-mix(in oklab, var(--grad-a) 22%, transparent), transparent 58%);
                filter: blur(30px) saturate(120%);
                opacity: .5;
                animation: cinema-drift 26s ease-in-out infinite alternate;
            }
            @keyframes cinema-drift {
                from { transform: translate3d(-1.5%, -1%, 0) scale(1); }
                to   { transform: translate3d(2%, 2%, 0) scale(1.07); }
            }

            .cinema-vignette {
                position: fixed;
                inset: 0;
                z-index: -1;
                pointer-events: none;
                background: radial-gradient(115% 80% at 50% 38%, transparent 42%, rgba(0,0,0,.6) 100%);
            }

            .cinema-grain {
                position: fixed;
                inset: -50%;
                z-index: 0;
                pointer-events: none;
                opacity: .05;
                mix-blend-mode: screen;
                background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='120' height='120'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2' stitchTiles='stitch'/></filter><rect width='100%25' height='100%25' filter='url(%23n)'/></svg>");
                animation: cinema-grain 6s steps(4) infinite;
            }
            @keyframes cinema-grain {
                0%,100% { transform: translate(0,0); }
                25%     { transform: translate(-4%, 3%); }
                50%     { transform: translate(3%, -5%); }
                75%     { transform: translate(-3%, 4%); }
            }

            .cinema-frame {
                position: fixed;
                z-index: 1;
                width: 30px;
                height: 30px;
                border: 1px solid var(--line);
                pointer-events: none;
            }
            .cinema-frame.tl { top: 22px; left: 22px; border-right: 0; border-bottom: 0; }
            .cinema-frame.tr { top: 22px; right: 22px; border-left: 0; border-bottom: 0; }
            .cinema-frame.bl { bottom: 22px; left: 22px; border-right: 0; border-top: 0; }
            .cinema-frame.br { bottom: 22px; right: 22px; border-left: 0; border-top: 0; }

            /* centered column */
            .cinema-col {
                position: relative;
                z-index: 2;
                width: 100%;
                max-width: 400px;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .cinema-brand {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                text-decoration: none;
                color: #f4f2ef;
                font-weight: 600;
                font-size: 1.05rem;
                letter-spacing: -.01em;
            }
            .cinema-brand svg { height: 22px; width: auto; border-radius: 6px; }

            .cinema-title {
                font-family: var(--serif);
                font-weight: 400;
                font-size: clamp(2.3rem, 7vw, 3rem);
                line-height: 1;
                letter-spacing: -.015em;
                color: #f4f2ef;
                text-align: center;
                margin: 26px 0 6px;
            }
            .cinema-title .accent {
                font-style: italic;
                background: linear-gradient(105deg, var(--grad-a) 10%, var(--grad-b) 90%);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }

            .cinema-sub {
                text-align: center;
                font-size: .9rem;
                line-height: 1.5;
                color: rgba(255,255,255,.5);
                margin: 0 0 32px;
            }

            /* glass form card */
            .cinema-card {
                width: 100%;
                border: 1px solid var(--line);
                border-radius: 20px;
                background: rgba(255,255,255,.025);
                backdrop-filter: blur(14px);
                -webkit-backdrop-filter: blur(14px);
                box-shadow: 0 30px 80px -40px rgba(0,0,0,.9), inset 0 1px 0 rgba(255,255,255,.05);
                padding: 30px 28px;
            }

            /* ---- Form controls — extends Flux with the stage design ---- */
            /* field labels (text inputs only — not the checkbox) */
            .cinema-card [data-flux-field]:has([data-flux-input]) > [data-flux-label],
            .cinema-card .cinema-label {
                font-size: .7rem !important;
                font-weight: 500;
                letter-spacing: .12em;
                text-transform: uppercase;
                color: rgba(255,255,255,.5) !important;
                margin-bottom: 0;
            }
            .cinema-card [data-flux-field]:has([data-flux-input]) > [data-flux-label] {
                margin-bottom: 9px !important;
            }

            /* "forgot password" — sits on the password label's baseline */
            .cinema-card .cinema-forgot {
                font-size: .72rem !important;
                letter-spacing: .02em;
            }

            .cinema-card input[data-flux-control] {
                height: 46px;
                border-radius: 12px;
                background: rgba(255,255,255,.04);
                border-color: rgba(255,255,255,.1);
                color: #f4f2ef;
                box-shadow: none;
                transition: border-color .2s, box-shadow .2s, background .2s;
            }
            .cinema-card input[data-flux-control]::placeholder { color: rgba(255,255,255,.28); }
            .cinema-card input[data-flux-control]:hover { border-color: rgba(255,255,255,.2); }
            .cinema-card input[data-flux-control]:focus {
                outline: none;
                background: rgba(255,255,255,.06);
                border-color: color-mix(in oklab, var(--grad-b) 55%, transparent);
                box-shadow: 0 0 0 3px color-mix(in oklab, var(--grad-b) 18%, transparent);
            }

            /* password reveal toggle */
            .cinema-card [data-flux-input] [data-flux-button] { color: rgba(255,255,255,.4); }
            .cinema-card [data-flux-input] [data-flux-button]:hover { color: #fff; }

            /* checkbox label */
            .cinema-card [data-flux-checkbox] ~ [data-flux-label] {
                font-size: .85rem;
                font-weight: 400;
                color: rgba(255,255,255,.6);
            }

            /* checkbox — brand fill when checked (was flat white) */
            .cinema-card ui-checkbox[data-checked] [data-flux-checkbox-indicator] {
                background: linear-gradient(120deg, var(--grad-a), var(--grad-b)) !important;
                border-color: transparent !important;
            }
            .cinema-card ui-checkbox[data-checked] [data-flux-checkbox-indicator] svg {
                color: #fff !important;
            }
            .cinema-card ui-checkbox:not([data-checked]) [data-flux-checkbox-indicator] {
                background: rgba(255,255,255,.05) !important;
                border-color: rgba(255,255,255,.16) !important;
            }

            /* primary submit — brand gradient pill */
            .cinema-card .cinema-btn {
                height: 46px !important;
                border-radius: 999px !important;
                border: 0 !important;
                font-weight: 600;
                color: #fff !important;
                background: linear-gradient(100deg, var(--grad-a), var(--grad-b)) !important;
                box-shadow: 0 12px 30px -14px rgba(236,72,153,.65) !important;
                transition: transform .2s cubic-bezier(.2,.8,.2,1), box-shadow .2s, filter .2s;
            }
            .cinema-card .cinema-btn:hover {
                transform: translateY(-1px);
                filter: brightness(1.07);
                box-shadow: 0 16px 42px -14px rgba(236,72,153,.85) !important;
            }

            /* secondary buttons (e.g. passkey) — translucent pill */
            .cinema-card [data-flux-button].w-full:not(.cinema-btn) {
                height: 46px !important;
                border-radius: 999px !important;
                border-color: rgba(255,255,255,.14) !important;
                background: rgba(255,255,255,.03) !important;
                color: rgba(255,255,255,.85) !important;
                transition: background .2s, color .2s, border-color .2s;
            }
            .cinema-card [data-flux-button].w-full:not(.cinema-btn):hover {
                background: rgba(255,255,255,.07) !important;
                border-color: rgba(255,255,255,.22) !important;
                color: #fff !important;
            }

            /* passkey separator — text flanked by two rules (never overlaps) */
            .cinema-card .my-6 {
                margin-top: 24px !important;
                margin-bottom: 4px !important;
            }
            .cinema-card .my-6 > .absolute { display: none !important; }
            .cinema-card .my-6 > .relative {
                display: flex;
                align-items: center;
                gap: 14px;
            }
            .cinema-card .my-6 > .relative::before,
            .cinema-card .my-6 > .relative::after {
                content: "";
                flex: 1 1 auto;
                height: 1px;
                background: rgba(255,255,255,.12);
            }
            .cinema-card .my-6 span {
                background: transparent !important;
                color: rgba(255,255,255,.38) !important;
                letter-spacing: .14em;
                white-space: nowrap;
                padding: 0 !important;
            }

            /* links */
            .cinema-card .cinema-link {
                color: rgba(255,255,255,.55) !important;
                text-decoration-color: rgba(255,255,255,.2) !important;
                transition: color .2s;
            }
            .cinema-card .cinema-link:hover { color: #fff !important; }

            .cinema-foot {
                margin-top: 26px;
                font-size: .74rem;
                letter-spacing: .12em;
                text-transform: uppercase;
                color: rgba(255,255,255,.32);
            }
            .cinema-foot .sep { color: rgba(255,255,255,.18); margin: 0 .5rem; }

            /* ---- Focus visibility — consistent brand ring, never white -- */
            /* text inputs keep their soft purple glow ring (above); everything
               else gets a matching purple outline instead of the default white. */
            .cinema a:focus-visible,
            .cinema button:focus-visible,
            .cinema [data-flux-button]:focus-visible,
            .cinema ui-checkbox:focus-visible {
                outline: 2px solid color-mix(in oklab, var(--grad-a) 50%, var(--grad-b)) !important;
                outline-offset: 3px !important;
            }

            @media (prefers-reduced-motion: reduce) {
                .cinema-glow, .cinema-grain { animation: none; }
            }
        </style>
    </head>
    <body class="min-h-screen antialiased" style="background: #08080a;">
        <div class="cinema">
            <div class="cinema-glow"></div>
            <div class="cinema-vignette"></div>
            <div class="cinema-grain"></div>
            <span class="cinema-frame tl"></span>
            <span class="cinema-frame tr"></span>
            <span class="cinema-frame bl"></span>
            <span class="cinema-frame br"></span>

            <div class="cinema-col">
                <a href="{{ route('home') }}" class="cinema-brand" wire:navigate>
                    <x-app-logo-icon />
                    {{ config('app.name', 'Movix') }}
                </a>

                <h1 class="cinema-title">
                    Welcome <span class="accent">back.</span>
                </h1>
                <p class="cinema-sub">
                    Sign in to your private cinema.
                </p>

                <div class="cinema-card">
                    {{ $slot }}
                </div>

                <p class="cinema-foot">
                    Passkeys <span class="sep">·</span> Two-factor <span class="sep">·</span> Self-hosted
                </p>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
