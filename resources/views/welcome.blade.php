<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="color-scheme: light;">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Anao Health Information System</title>
    <link rel="icon" href="{{ asset('images/anao-seal.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{--g1:#065f46;--g2:#059669;--g3:#0d9488;--ink:#0f172a;--muted:#475569;--line:#e2e8f0}
        html{scroll-behavior:smooth}
        body{font-family:'Inter',system-ui,sans-serif;color:var(--ink);background:#f8fafc;overflow-x:hidden}
        a{text-decoration:none;color:inherit}
        .wrap{max-width:1180px;margin:0 auto;padding:0 24px}

        /* Nav */
        nav{position:sticky;top:0;z-index:50;background:rgba(255,255,255,.8);backdrop-filter:blur(12px);border-bottom:1px solid var(--line)}
        .nav-in{display:flex;align-items:center;justify-content:space-between;height:68px}
        .brand{display:flex;align-items:center;gap:12px}
        .brand img{width:42px;height:42px;object-fit:contain}
        .brand .t1{font-weight:700;font-size:15px;line-height:1.1}
        .brand .t2{font-size:12px;color:var(--g2);font-weight:500}
        .nav-cta{display:flex;gap:10px;align-items:center}
        .btn{display:inline-flex;align-items:center;gap:8px;font-weight:600;font-size:14px;padding:11px 20px;border-radius:10px;cursor:pointer;transition:.25s;border:1px solid transparent}
        .btn-primary{background:linear-gradient(135deg,var(--g2),var(--g3));color:#fff;box-shadow:0 6px 18px -6px rgba(5,150,105,.6)}
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 12px 24px -6px rgba(5,150,105,.7)}
        .btn-ghost{background:#fff;border-color:var(--line);color:#334155}
        .btn-ghost:hover{border-color:var(--g2);color:var(--g2);transform:translateY(-2px)}

        /* Hero */
        .hero{position:relative;padding:90px 0 70px;overflow:hidden}
        .hero-bg{position:absolute;inset:0;z-index:0;background:
            radial-gradient(60% 60% at 15% 10%,rgba(13,148,136,.12),transparent 60%),
            radial-gradient(50% 50% at 90% 20%,rgba(5,150,105,.14),transparent 60%),
            radial-gradient(60% 60% at 70% 100%,rgba(6,95,70,.1),transparent 60%)}
        .grid-lines{position:absolute;inset:0;z-index:0;opacity:.4;
            background-image:linear-gradient(var(--line) 1px,transparent 1px),linear-gradient(90deg,var(--line) 1px,transparent 1px);
            background-size:44px 44px;mask-image:radial-gradient(circle at 50% 40%,#000,transparent 75%)}
        .hero-in{position:relative;z-index:1;display:grid;grid-template-columns:1.1fr .9fr;gap:50px;align-items:center}
        .pill{display:inline-flex;align-items:center;gap:8px;background:#ecfdf5;color:#047857;font-weight:600;font-size:12.5px;padding:7px 14px;border-radius:999px;border:1px solid #a7f3d0}
        .pill .dot{width:7px;height:7px;border-radius:50%;background:#10b981;box-shadow:0 0 0 0 rgba(16,185,129,.6);animation:pulse 2s infinite}
        h1.hero-title{font-size:52px;line-height:1.05;font-weight:800;letter-spacing:-1.5px;margin:22px 0 18px}
        h1.hero-title .hl{background:linear-gradient(120deg,var(--g2),var(--g3));-webkit-background-clip:text;background-clip:text;color:transparent}
        .lead{font-size:18px;color:var(--muted);max-width:520px;line-height:1.6}
        .hero-cta{display:flex;gap:14px;margin-top:34px;flex-wrap:wrap}
        .trust{display:flex;gap:26px;margin-top:40px;flex-wrap:wrap}
        .trust .n{font-size:26px;font-weight:800;color:var(--g1)}
        .trust .l{font-size:12.5px;color:var(--muted)}

        /* Hero visual card */
        .visual{position:relative;display:flex;justify-content:center}
        .seal-card{position:relative;background:#fff;border:1px solid var(--line);border-radius:26px;padding:26px;box-shadow:0 30px 60px -25px rgba(6,95,70,.35);width:100%;max-width:380px;animation:floaty 6s ease-in-out infinite}
        .seal-card img{display:block;width:150px;height:150px;object-fit:contain;margin:0 auto}
        .seal-card .cap{text-align:center;margin-top:14px;font-weight:700}
        .seal-card .cap small{display:block;color:var(--g2);font-weight:500;font-size:12px;margin-top:2px}
        .spark{margin-top:18px;border-top:1px dashed var(--line);padding-top:16px}
        .spark svg{width:100%;height:60px;display:block}
        .spark path{stroke-dasharray:1000;stroke-dashoffset:1000;animation:draw 2.6s ease forwards .5s}
        .chip{position:absolute;background:#fff;border:1px solid var(--line);border-radius:14px;padding:10px 14px;box-shadow:0 12px 30px -12px rgba(15,23,42,.25);font-size:12.5px;font-weight:600;display:flex;align-items:center;gap:8px}
        .chip .ic{width:26px;height:26px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff}
        .chip.c1{top:6px;left:-26px;animation:floaty 5s ease-in-out infinite}
        .chip.c2{bottom:20px;right:-24px;animation:floaty 5.5s ease-in-out infinite .6s}

        /* Stats band */
        .band{background:linear-gradient(135deg,var(--g1),var(--g3));color:#fff}
        .band-in{display:grid;grid-template-columns:repeat(4,1fr);gap:24px;padding:38px 0}
        .band .n{font-size:30px;font-weight:800}
        .band .l{font-size:13px;opacity:.85;margin-top:4px}

        /* Features */
        .sec{padding:80px 0}
        .sec-head{text-align:center;max-width:640px;margin:0 auto 46px}
        .sec-head h2{font-size:34px;font-weight:800;letter-spacing:-1px}
        .sec-head p{color:var(--muted);margin-top:12px;font-size:16px}
        .cards{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
        .card{background:#fff;border:1px solid var(--line);border-radius:20px;padding:28px;transition:.3s}
        .card:hover{transform:translateY(-6px);box-shadow:0 24px 48px -24px rgba(6,95,70,.3);border-color:#a7f3d0}
        .card .ic{width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#ecfdf5,#d1fae5);color:var(--g1);display:flex;align-items:center;justify-content:center;margin-bottom:18px}
        .card h3{font-size:18px;font-weight:700}
        .card p{color:var(--muted);margin-top:8px;font-size:14.5px;line-height:1.6}

        footer{border-top:1px solid var(--line);background:#fff}
        .foot-in{display:flex;justify-content:space-between;align-items:center;padding:26px 0;color:var(--muted);font-size:13.5px;flex-wrap:wrap;gap:10px}

        /* Reveal */
        .reveal{opacity:0;transform:translateY(24px);transition:opacity .7s ease,transform .7s ease}
        .reveal.in{opacity:1;transform:none}

        @keyframes pulse{0%{box-shadow:0 0 0 0 rgba(16,185,129,.6)}70%{box-shadow:0 0 0 8px rgba(16,185,129,0)}100%{box-shadow:0 0 0 0 rgba(16,185,129,0)}}
        @keyframes floaty{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
        @keyframes draw{to{stroke-dashoffset:0}}

        @media(max-width:900px){
            .hero-in{grid-template-columns:1fr;gap:36px}
            h1.hero-title{font-size:38px}
            .band-in{grid-template-columns:repeat(2,1fr)}
            .cards{grid-template-columns:1fr}
            .chip{display:none}
        }
    </style>
</head>
<body>
    <nav>
        <div class="wrap nav-in">
            <div class="brand">
                <img src="{{ asset('images/anao-seal.png') }}" alt="Anao seal">
                <div>
                    <div class="t1">Anao Health Information System</div>
                    <div class="t2">Municipality of Anao, Tarlac</div>
                </div>
            </div>
            <div class="nav-cta">
                <a href="{{ route('portal.login') }}" class="btn btn-ghost">Citizen Portal</a>
                <a href="{{ route('login') }}" class="btn btn-primary">Staff Login</a>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-bg"></div>
        <div class="grid-lines"></div>
        <div class="wrap hero-in">
            <div>
                <span class="pill"><span class="dot"></span> Ylang-Ylang Capital of Tarlac</span>
                <h1 class="hero-title">One health record for<br><span class="hl">every citizen</span> of Anao</h1>
                <p class="lead">A unified information system for the Rural Health Unit and the Office of the Mayor: patient care, community profiling, and health forecasting per barangay and for the whole municipality.</p>
                <div class="hero-cta">
                    <a href="{{ route('login') }}" class="btn btn-primary">Staff Login &rarr;</a>
                    <a href="{{ route('portal.login') }}" class="btn btn-ghost">Citizen Portal</a>
                </div>
                <div class="trust">
                    <div><div class="n">18</div><div class="l">Barangays covered</div></div>
                    <div><div class="n">14</div><div class="l">Integrated modules</div></div>
                    <div><div class="n">100%</div><div class="l">Digital records</div></div>
                </div>
            </div>
            <div class="visual">
                <div class="seal-card">
                    <div class="chip c1"><span class="ic" style="background:#059669">&#10003;</span> Records synced</div>
                    <div class="chip c2"><span class="ic" style="background:#0d9488">&#8599;</span> Forecast ready</div>
                    <img src="{{ asset('images/anao-seal.png') }}" alt="Municipality of Anao seal">
                    <div class="cap">Municipality of Anao<small>Municipal Health Office</small></div>
                    <div class="spark">
                        <svg viewBox="0 0 320 60" preserveAspectRatio="none">
                            <path d="M0,45 L40,38 L80,42 L120,24 L160,30 L200,16 L240,22 L280,8 L320,14" fill="none" stroke="#059669" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="band">
        <div class="wrap band-in">
            <div><div class="n">Unified</div><div class="l">Patient, community & social records</div></div>
            <div><div class="n">Real-time</div><div class="l">Executive dashboards</div></div>
            <div><div class="n">Barangay</div><div class="l">Level monitoring & BHW field work</div></div>
            <div><div class="n">AI-ready</div><div class="l">Health forecasting foundation</div></div>
        </div>
    </section>

    <section class="sec">
        <div class="wrap">
            <div class="sec-head reveal">
                <h2>Built for the whole municipality</h2>
                <p>From the consultation table to the Mayor's dashboard, one connected system.</p>
            </div>
            <div class="cards">
                <div class="card reveal">
                    <div class="ic"><svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 3h-3v7.5H3v3h7.5V21h3v-7.5H21v-3h-7.5z"/></svg></div>
                    <h3>Unified Health Records</h3>
                    <p>Registration, triage, consultations, laboratory, radiology, and pharmacy tied to one citizen record.</p>
                </div>
                <div class="card reveal">
                    <div class="ic"><svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3l9 6v12h-6v-7h-6v7H3V9z"/></svg></div>
                    <h3>Community & Barangay Profiling</h3>
                    <p>Household surveys, BHW field visits, and social services across all 18 barangays of Anao.</p>
                </div>
                <div class="card reveal">
                    <div class="ic"><svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3v18h18v-2H5V3zm4 12h2v4H7zm4-6h2v10h-2zm4 3h2v7h-2z"/></svg></div>
                    <h3>Forecasting & Analytics</h3>
                    <p>Executive dashboards and health forecasting per barangay, engineered to plug into machine learning.</p>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="wrap foot-in">
            <div>&copy; {{ date('Y') }} Municipality of Anao, Tarlac &middot; Municipal Health Office</div>
            <div>Anao Health Information System</div>
        </div>
    </footer>

    <script>
        const io = new IntersectionObserver((es) => {
            es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
        }, { threshold: .15 });
        document.querySelectorAll('.reveal').forEach((el, i) => { el.style.transitionDelay = (i % 3 * 0.1) + 's'; io.observe(el); });
    </script>
</body>
</html>
