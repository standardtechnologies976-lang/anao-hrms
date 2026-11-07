<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="color-scheme: light;">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ filled($title ?? null) ? $title.' - '.config('app.name') : config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/anao-seal.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root{--g1:#064e3b;--g2:#059669;--g3:#0d9488;--line:#e2e8f0}
        .au-shell{display:grid;grid-template-columns:1.05fr .95fr;min-height:100svh;font-family:'Inter',system-ui,sans-serif}
        /* Left brand panel */
        .au-brand{position:relative;overflow:hidden;color:#fff;padding:56px;display:flex;flex-direction:column;justify-content:space-between;
            background:linear-gradient(135deg,#064e3b,#059669 55%,#0d9488);background-size:200% 200%;animation:auGrad 12s ease infinite}
        .au-brand::before{content:"";position:absolute;inset:0;opacity:.18;
            background-image:linear-gradient(rgba(255,255,255,.6) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.6) 1px,transparent 1px);
            background-size:40px 40px;mask-image:radial-gradient(circle at 30% 30%,#000,transparent 70%)}
        .au-orb{position:absolute;border-radius:50%;filter:blur(40px);opacity:.5}
        .au-orb.o1{width:280px;height:280px;background:#34d399;top:-60px;right:-40px;animation:auFloat 9s ease-in-out infinite}
        .au-orb.o2{width:220px;height:220px;background:#5eead4;bottom:-40px;left:-30px;animation:auFloat 11s ease-in-out infinite 1s}
        .au-b-top{position:relative;z-index:1;display:flex;align-items:center;gap:14px}
        .au-b-top img{width:56px;height:56px;object-fit:contain;background:#fff;border-radius:50%;padding:4px}
        .au-b-top .t1{font-weight:700;font-size:16px}
        .au-b-top .t2{font-size:12.5px;opacity:.85}
        .au-b-mid{position:relative;z-index:1}
        .au-b-mid h2{font-size:34px;font-weight:800;letter-spacing:-1px;line-height:1.1;opacity:0;animation:auUp .8s ease forwards .2s}
        .au-b-mid p{margin-top:14px;max-width:400px;opacity:.9;line-height:1.6;opacity:0;animation:auUp .8s ease forwards .35s}
        .au-feats{list-style:none;padding:0;margin:26px 0 0;display:flex;flex-direction:column;gap:12px}
        .au-feats li{display:flex;align-items:center;gap:10px;opacity:0;animation:auUp .7s ease forwards}
        .au-feats li:nth-child(1){animation-delay:.5s}.au-feats li:nth-child(2){animation-delay:.62s}.au-feats li:nth-child(3){animation-delay:.74s}
        .au-feats .ck{width:22px;height:22px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:12px}
        .au-spark{position:relative;z-index:1;margin-top:8px}
        .au-spark svg{width:100%;height:70px}
        .au-spark path{stroke-dasharray:1000;stroke-dashoffset:1000;animation:auDraw 2.8s ease forwards .8s}
        .au-b-foot{position:relative;z-index:1;font-size:12.5px;opacity:.8}
        /* Right form panel */
        .au-form{display:flex;align-items:center;justify-content:center;padding:40px;background:#f8fafc}
        .au-card{width:100%;max-width:400px;opacity:0;animation:auUp .7s ease forwards .15s}
        .au-card .mlogo{display:none}
        @media(max-width:860px){
            .au-shell{grid-template-columns:1fr}
            .au-brand{display:none}
            .au-card .mlogo{display:flex;flex-direction:column;align-items:center;gap:6px;margin-bottom:22px}
            .au-card .mlogo img{width:60px;height:60px;object-fit:contain}
            .au-card .mlogo .t{font-weight:700}
        }
        @keyframes auGrad{0%,100%{background-position:0 0}50%{background-position:100% 100%}}
        @keyframes auFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-24px)}}
        @keyframes auUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
        @keyframes auDraw{to{stroke-dashoffset:0}}
    </style>
</head>
<body>
    <div class="au-shell">
        <aside class="au-brand">
            <div class="au-orb o1"></div>
            <div class="au-orb o2"></div>
            <div class="au-b-top">
                <img src="{{ asset('images/anao-seal.png') }}" alt="Anao seal">
                <div>
                    <div class="t1">Anao Health Information System</div>
                    <div class="t2">Municipality of Anao, Tarlac</div>
                </div>
            </div>
            <div class="au-b-mid">
                <h2>Health data that works for every barangay.</h2>
                <p>Sign in to manage patient care, community profiling, and municipal health forecasting in one secure system.</p>
                <ul class="au-feats">
                    <li><span class="ck">&#10003;</span> Unified citizen health records</li>
                    <li><span class="ck">&#10003;</span> Barangay-level monitoring & BHW field work</li>
                    <li><span class="ck">&#10003;</span> Executive dashboards & forecasting</li>
                </ul>
                <div class="au-spark">
                    <svg viewBox="0 0 400 70" preserveAspectRatio="none">
                        <path d="M0,55 L50,46 L100,50 L150,30 L200,38 L250,20 L300,28 L350,10 L400,18" fill="none" stroke="rgba(255,255,255,.9)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="au-b-foot">&copy; {{ date('Y') }} Municipal Health Office &middot; Anao, Tarlac</div>
        </aside>

        <main class="au-form">
            <div class="au-card">
                <a href="{{ route('home') }}" class="mlogo" wire:navigate>
                    <img src="{{ asset('images/anao-seal.png') }}" alt="Anao seal">
                    <span class="t">Anao Health Information System</span>
                </a>
                {{ $slot }}
            </div>
        </main>
    </div>

    @persist('toast')
        <flux:toast.group><flux:toast /></flux:toast.group>
    @endpersist
    @fluxScripts
</body>
</html>
