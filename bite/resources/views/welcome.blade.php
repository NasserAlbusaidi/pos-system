<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    {{-- Primary Meta Tags --}}
    <title>Bite POS — Modern Restaurant POS System for Oman</title>
    <meta name="title" content="Bite POS — Modern Restaurant POS System for Oman">
    <meta name="description" content="All-in-one POS system built for restaurants and cafes in Oman. POS terminal, kitchen display, QR guest ordering, menu builder, and real-time reports. Start free.">
    <meta name="keywords" content="POS system Oman, restaurant POS, cafe POS Muscat, point of sale Oman, kitchen display system, QR ordering, restaurant management software">
    <meta name="author" content="Bite Systems">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url('/') }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="Bite POS — Modern Restaurant POS System for Oman">
    <meta property="og:description" content="All-in-one POS system built for restaurants and cafes in Oman. POS terminal, kitchen display, QR guest ordering, and real-time reports.">
    <meta property="og:image" content="{{ url('/og-image.png') }}">
    <meta property="og:locale" content="en_US">
    <meta property="og:site_name" content="Bite POS">

    {{-- Twitter --}}
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url('/') }}">
    <meta property="twitter:title" content="Bite POS — Modern Restaurant POS System for Oman">
    <meta property="twitter:description" content="All-in-one POS system built for restaurants and cafes in Oman. POS terminal, kitchen display, QR guest ordering, and real-time reports.">
    <meta property="twitter:image" content="{{ url('/og-image.png') }}">

    {{-- Arabic / RTL future support --}}
    <meta name="language" content="English">
    <link rel="alternate" hreflang="en" href="{{ url('/') }}">
    {{-- <link rel="alternate" hreflang="ar" href="{{ url('/ar') }}"> --}}

    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    {{-- JSON-LD Structured Data --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "SoftwareApplication",
        "name": "Bite POS",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "Web",
        "description": "All-in-one POS system built for restaurants and cafes in Oman. POS terminal, kitchen display, QR guest ordering, menu builder, and real-time reports.",
        "url": "{{ url('/') }}",
        "offers": {
            "@@type": "Offer",
            "price": "0",
            "priceCurrency": "OMR",
            "description": "Free plan available. Pro plan 20 OMR/month."
        },
        "provider": {
            "@@type": "Organization",
            "name": "Bite Systems",
            "url": "{{ url('/') }}",
            "address": {
                "@@type": "PostalAddress",
                "addressLocality": "Muscat",
                "addressCountry": "OM"
            },
            "contactPoint": {
                "@@type": "ContactPoint",
                "contactType": "sales",
                "availableLanguage": ["English", "Arabic"]
            }
        },
        "featureList": [
            "POS Terminal",
            "Kitchen Display System",
            "QR Guest Ordering",
            "Menu Builder",
            "Real-time Reports & Analytics",
            "Mobile-First Design"
        ]
    }
    </script>

    {{-- FAQ Structured Data --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "FAQPage",
        "mainEntity": [
            {
                "@@type": "Question",
                "name": "What hardware do I need to use Bite POS?",
                "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "Any device with a modern web browser — iPad, Android tablet, laptop, or desktop. No special POS hardware required. For printing, we support standard receipt printers via PrintNode."
                }
            },
            {
                "@@type": "Question",
                "name": "Does Bite POS work without internet?",
                "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "Bite POS is a cloud-based system that requires internet for full functionality. However, we include offline support for basic operations so your service never stops during brief outages."
                }
            },
            {
                "@@type": "Question",
                "name": "How long does it take to set up?",
                "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "Most restaurants are up and running within 30 minutes. Create your account, add your menu, and start taking orders. Our team is available on WhatsApp for setup help."
                }
            },
            {
                "@@type": "Question",
                "name": "Does Bite POS support Omani Rial (OMR)?",
                "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "Yes. Bite POS is built specifically for Oman and uses OMR with proper 3-decimal-place formatting throughout the system."
                }
            }
        ]
    }
    </script>


    {{-- Fonts (self-hosted — Bai Jamjuree, the Bite brand typeface. CSP allows font-src 'self' only, so no Google Fonts CDN). --}}
    <style>
        @font-face { font-family: 'Bai Jamjuree'; src: url('/fonts/BaiJamjuree-400.woff2') format('woff2'); font-weight: 400; font-style: normal; font-display: swap; }
        @font-face { font-family: 'Bai Jamjuree'; src: url('/fonts/BaiJamjuree-500.woff2') format('woff2'); font-weight: 500; font-style: normal; font-display: swap; }
        @font-face { font-family: 'Bai Jamjuree'; src: url('/fonts/BaiJamjuree-600.woff2') format('woff2'); font-weight: 600; font-style: normal; font-display: swap; }
        @font-face { font-family: 'Bai Jamjuree'; src: url('/fonts/BaiJamjuree-700.woff2') format('woff2'); font-weight: 700; font-style: normal; font-display: swap; }
        @font-face { font-family: 'Bai Jamjuree'; src: url('/fonts/BaiJamjuree-700Italic.woff2') format('woff2'); font-weight: 700; font-style: italic; font-display: swap; }
    </style>

    {{-- Design tokens + base + interaction/responsive styles.
         Ported from Claude Design "Bite design system foundation" (tokens/*.css).
         Kept self-contained (no @vite) so the public landing page has no build dependency. --}}
    <style>
        :root {
            /* Bite brand palette */
            --bite-forest:#004225; --bite-pine:#0B6B2E; --bite-green:#37B34A; --bite-lime:#7AC70C; --bite-olive:#B7C40D;
            --bite-lime-300:#98D641; --bite-lime-200:#C3E88A; --bite-lime-100:#E6F4CE;
            --bite-cream:#F6F8F1; --bite-mist:#ECF1E6; --bite-line:#DCE4D2; --bite-ash:#6E7A66; --bite-charcoal:#16241B;
            /* Type */
            --font-display:'Bai Jamjuree', system-ui, sans-serif; --font-body:'Bai Jamjuree', system-ui, sans-serif;
            /* Radius */
            --radius-sm:8px; --radius-md:14px; --radius-lg:22px; --radius-xl:32px; --radius-2xl:48px; --radius-pill:999px;
            --radius-blob:42% 58% 60% 40% / 48% 42% 58% 52%;
            /* Shadows (green-tinted) */
            --shadow-xs:0 1px 2px rgba(0,66,37,.08); --shadow-sm:0 2px 8px rgba(0,66,37,.10);
            --shadow-md:0 8px 24px rgba(0,66,37,.12); --shadow-lg:0 18px 48px rgba(0,66,37,.16);
            --shadow-offset:6px 6px 0 var(--bite-pine);
            /* Motion */
            --ease-out:cubic-bezier(0.22,1,0.36,1); --ease-bounce:cubic-bezier(0.34,1.56,0.64,1);
            --dur-fast:140ms; --dur-base:240ms; --dur-slow:420ms;
        }
        *,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }
        html { scroll-behavior:smooth; scroll-padding-top:90px; -webkit-text-size-adjust:100%; }
        body { font-family:var(--font-body); background:var(--bite-cream); }
        img,svg { display:block; max-width:100%; }
        a { -webkit-tap-highlight-color:transparent; }
        @keyframes blobFloat { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-18px); } }
        @keyframes scanRise { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
        ::selection { background:var(--bite-lime); color:var(--bite-forest); }

        /* Interaction — ported from the design's style-hover / style-active attributes. */
        .lp-navlink { transition:color var(--dur-fast); }
        .lp-navlink:hover { color:var(--bite-green) !important; }
        .lp-ghost:hover { background:var(--bite-mist) !important; }
        .lp-pill-lime:hover { filter:brightness(1.06); }
        .lp-pill-lime:active { transform:scale(.95); }
        .lp-pill-forest:hover { filter:brightness(1.12); }
        .lp-pill-forest:active { transform:scale(.96); }
        .lp-pill-outline-forest:hover { background:var(--bite-mist) !important; }
        .lp-pill-outline-forest:active { transform:scale(.96); }
        .lp-pill-outline-white:hover { background:rgba(255,255,255,.08) !important; }
        .lp-card { transition:transform var(--dur-base) var(--ease-out), box-shadow var(--dur-base) var(--ease-out); }
        .lp-card:hover { transform:translateY(-5px); box-shadow:var(--shadow-md) !important; }
        .lp-footlink { transition:color var(--dur-fast); }
        .lp-footlink:hover { color:var(--bite-lime) !important; }

        /* Responsive */
        @media (max-width: 900px) {
            .lp-hero-grid, .lp-snap-grid, .lp-ordering-grid { grid-template-columns:1fr !important; gap:48px !important; }
            .lp-snap-arrow { display:none !important; }
            .lp-features-grid { grid-template-columns:1fr 1fr !important; }
            .lp-steps-grid { grid-template-columns:repeat(2,1fr) !important; row-gap:40px !important; }
            .lp-footer-grid { grid-template-columns:1fr 1fr !important; }
            .lp-hero-h1 { font-size:50px !important; }
            .lp-nav-links { display:none !important; }
        }
        @media (max-width: 600px) {
            .lp-container { padding-left:22px !important; padding-right:22px !important; }
            .lp-section { padding-top:64px !important; padding-bottom:64px !important; }
            .lp-features-grid, .lp-pricing-grid, .lp-footer-grid { grid-template-columns:1fr !important; }
            .lp-steps-grid { row-gap:34px !important; }
            .lp-hero-h1 { font-size:38px !important; }
            .lp-h2 { font-size:34px !important; }
            .lp-hero-inner { padding-top:56px !important; padding-bottom:64px !important; }
            .lp-cta-row { flex-direction:column !important; align-items:flex-start !important; }
            .lp-nav-cta-ghost { display:none !important; }
        }
    </style>
</head>
<body>

<div id="top" style="font-family: var(--font-body); color: var(--bite-charcoal); background: var(--bite-cream); width: 100%; min-height: 100%; -webkit-font-smoothing: antialiased; overflow-x: hidden;">

    {{-- ===== NAV ===== --}}
    <div style="position: sticky; top: 0; z-index: 60; background: color-mix(in srgb, var(--bite-cream) 86%, transparent); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-bottom: 1px solid var(--bite-line);">
        <div class="lp-container" style="max-width: 1120px; margin: 0 auto; padding: 0 40px; height: 70px; display: flex; align-items: center; justify-content: space-between; gap: 24px;">
            <a href="#top" style="font-family: var(--font-display); font-weight: 700; font-size: 25px; letter-spacing: -0.02em; color: var(--bite-forest); text-decoration: none;">Bite<span style="color: var(--bite-lime);">.</span></a>
            <div class="lp-nav-links" style="display: flex; gap: 28px; align-items: center; font-size: 15px; font-weight: 500;">
                <a href="#product" class="lp-navlink" style="color: var(--bite-forest); text-decoration: none;">Product</a>
                <a href="#snap" class="lp-navlink" style="color: var(--bite-forest); text-decoration: none;">Snap-to-Menu</a>
                <a href="#how" class="lp-navlink" style="color: var(--bite-forest); text-decoration: none;">How it works</a>
                <a href="#pricing" class="lp-navlink" style="color: var(--bite-forest); text-decoration: none;">Pricing</a>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <a href="{{ route('login') }}" class="lp-ghost lp-nav-cta-ghost" style="display: inline-flex; align-items: center; padding: 10px 18px; border-radius: var(--radius-pill); font-weight: 600; font-size: 15px; color: var(--bite-forest); text-decoration: none; transition: background var(--dur-fast);">Log in</a>
                <a href="{{ route('register') }}" class="lp-pill-lime" style="display: inline-flex; align-items: center; padding: 11px 22px; border-radius: var(--radius-pill); font-weight: 600; font-size: 15px; background: var(--bite-lime); color: var(--bite-forest); text-decoration: none; border: 2px solid var(--bite-lime); transition: filter var(--dur-fast), transform var(--dur-fast) var(--ease-bounce);">Start free trial</a>
            </div>
        </div>
    </div>

    {{-- ===== HERO ===== --}}
    <div style="position: relative; background: var(--bite-forest); color: #fff; overflow: hidden;">
        <div style="position: absolute; width: 560px; height: 560px; background: #063a20; left: -190px; top: -170px; border-radius: var(--radius-blob); animation: blobFloat 12s var(--ease-out) infinite;"></div>
        <div style="position: absolute; width: 300px; height: 300px; background: var(--bite-pine); right: -70px; bottom: -130px; border-radius: 46% 54% 58% 42% / 52% 44% 56% 48%; animation: blobFloat 15s var(--ease-out) infinite;"></div>
        <div style="position: absolute; width: 60px; height: 60px; background: var(--bite-lime); right: 46%; bottom: 30px; border-radius: 50%;"></div>
        <div style="position: absolute; width: 32px; height: 32px; background: var(--bite-olive); left: 8%; bottom: 90px; border-radius: 50%;"></div>
        <div class="lp-container lp-hero-inner lp-hero-grid" style="position: relative; z-index: 2; max-width: 1120px; margin: 0 auto; padding: 84px 40px 92px; display: grid; grid-template-columns: 1.04fr 0.96fr; gap: 48px; align-items: center;">
            <div>
                <div style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.12em; color: var(--bite-lime);">Restaurant POS · QR ordering · Kitchen</div>
                <h1 class="lp-hero-h1" style="font-family: var(--font-display); font-weight: 700; font-size: 64px; line-height: 1.02; letter-spacing: -0.03em; margin: 18px 0 0; color: #fff;">Your floor, your kitchen, your menu — on <span style="color: var(--bite-lime);">one Bite.</span></h1>
                <p style="font-size: 20px; line-height: 1.55; color: rgba(255,255,255,.82); max-width: 540px; margin: 24px 0 0;">Bite runs your point of sale, kitchen display, and QR-code menu from one place. Snap a photo of your printed menu and you're taking orders the same day.</p>
                <div class="lp-cta-row" style="display: flex; gap: 14px; flex-wrap: wrap; margin-top: 32px;">
                    <a href="{{ route('register') }}" class="lp-pill-lime" style="display: inline-flex; align-items: center; gap: 8px; padding: 16px 30px; border-radius: var(--radius-pill); font-weight: 600; font-size: 17px; background: var(--bite-lime); color: var(--bite-forest); text-decoration: none; border: 2px solid var(--bite-lime); transition: filter var(--dur-fast), transform var(--dur-fast) var(--ease-bounce);">Start 14-day free trial
                        <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                    <a href="#snap" class="lp-pill-outline-white" style="display: inline-flex; align-items: center; gap: 8px; padding: 16px 28px; border-radius: var(--radius-pill); font-weight: 600; font-size: 17px; background: transparent; color: #fff; text-decoration: none; border: 2px solid rgba(255,255,255,.4); transition: background var(--dur-fast);">See Snap-to-Menu</a>
                </div>
                <div style="display: flex; gap: 36px; margin-top: 38px; flex-wrap: wrap;">
                    <div><b style="display: block; font-family: var(--font-display); font-size: 28px; font-weight: 700; color: #fff;">14 days</b><span style="font-size: 14px; color: rgba(255,255,255,.66);">free · no card needed</span></div>
                    <div><b style="display: block; font-family: var(--font-display); font-size: 28px; font-weight: 700; color: #fff;">1 day</b><span style="font-size: 14px; color: rgba(255,255,255,.66);">from photo to live menu</span></div>
                    <div><b style="display: block; font-family: var(--font-display); font-size: 28px; font-weight: 700; color: #fff;">OMR {{ config('billing.plans.pro.price') }}</b><span style="font-size: 14px; color: rgba(255,255,255,.66);">a month after that</span></div>
                </div>
            </div>

            {{-- POS tablet mock --}}
            <div style="position: relative; display: flex; justify-content: center;">
                <div style="position: absolute; left: -26px; top: 2px; z-index: 3; background: #fff; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); padding: 12px 16px; display: flex; align-items: center; gap: 10px;">
                    <span style="width: 38px; height: 38px; border-radius: 50%; flex: none; display: flex; align-items: center; justify-content: center; background: var(--bite-lime-100); color: var(--bite-green);"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                    <div><div style="font-weight: 700; font-size: 14px; color: var(--bite-charcoal); white-space: nowrap;">Order paid</div><div style="font-size: 12px; color: var(--bite-ash); white-space: nowrap;">Table 12 · 30s</div></div>
                </div>
                <div style="position: absolute; right: -22px; bottom: 30px; z-index: 3; background: #fff; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); padding: 12px 16px; display: flex; align-items: center; gap: 10px;">
                    <span style="width: 38px; height: 38px; border-radius: 50%; flex: none; display: flex; align-items: center; justify-content: center; background: var(--bite-forest); color: #fff;"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V18H6Z"/><path d="M6 17h12"/></svg></span>
                    <div><div style="font-weight: 700; font-size: 14px; color: var(--bite-charcoal); white-space: nowrap;">Sent to kitchen</div><div style="font-size: 12px; color: var(--bite-ash); white-space: nowrap;">3 items firing</div></div>
                </div>
                <div style="width: 408px; max-width: 100%; background: #fff; border-radius: 30px; padding: 14px; box-shadow: var(--shadow-lg); transform: rotate(-2deg);">
                    <div style="background: var(--bite-cream); border-radius: 18px; overflow: hidden;">
                        <div style="background: var(--bite-forest); color: #fff; padding: 14px 16px; display: flex; align-items: center; justify-content: space-between;"><span style="font-family: var(--font-display); font-weight: 700; font-size: 16px;">Table 12</span><span style="font-size: 13px; opacity: .8;">2 guests</span></div>
                        <div style="padding: 14px; display: flex; flex-direction: column; gap: 10px;">
                            <div style="display: flex; align-items: center; gap: 12px; background: #fff; border: 1px solid var(--bite-line); border-radius: var(--radius-md); padding: 11px 12px;"><span style="width: 42px; height: 42px; border-radius: 11px; flex: none; background: linear-gradient(135deg,#7AC70C,#37B34A);"></span><div style="flex: 1; min-width: 0;"><div style="font-weight: 600; font-size: 14px; color: var(--bite-charcoal);">Halloumi wrap</div><div style="font-size: 12px; color: var(--bite-ash);">Extra chili</div></div><span style="font-weight: 700; color: var(--bite-forest); font-size: 14px;">2.400</span></div>
                            <div style="display: flex; align-items: center; gap: 12px; background: #fff; border: 1px solid var(--bite-line); border-radius: var(--radius-md); padding: 11px 12px;"><span style="width: 42px; height: 42px; border-radius: 11px; flex: none; background: linear-gradient(135deg,#B7C40D,#7AC70C);"></span><div style="flex: 1; min-width: 0;"><div style="font-weight: 600; font-size: 14px; color: var(--bite-charcoal);">Garden bowl</div><div style="font-size: 12px; color: var(--bite-ash);">No onion</div></div><span style="font-weight: 700; color: var(--bite-forest); font-size: 14px;">3.400</span></div>
                            <div style="display: flex; align-items: center; gap: 12px; background: #fff; border: 1px solid var(--bite-line); border-radius: var(--radius-md); padding: 11px 12px;"><span style="width: 42px; height: 42px; border-radius: 11px; flex: none; background: linear-gradient(135deg,#0B6B2E,#37B34A);"></span><div style="flex: 1; min-width: 0;"><div style="font-weight: 600; font-size: 14px; color: var(--bite-charcoal);">Mint lemonade</div><div style="font-size: 12px; color: var(--bite-ash);">x2</div></div><span style="font-weight: 700; color: var(--bite-forest); font-size: 14px;">1.800</span></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px;"><span style="color: var(--bite-ash); font-size: 13px;">Total · incl. VAT</span><span style="font-family: var(--font-display); font-weight: 700; font-size: 22px; color: var(--bite-forest);">OMR 7.600</span></div>
                        <div style="background: var(--bite-lime); color: var(--bite-forest); text-align: center; font-weight: 700; padding: 13px; border-radius: var(--radius-pill); margin: 0 14px 14px;">Charge OMR 7.600</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== SNAP-TO-MENU ===== --}}
    <section id="snap" class="lp-section" style="padding: 96px 0; position: relative; overflow: hidden;">
        <div class="lp-container" style="max-width: 1120px; margin: 0 auto; padding: 0 40px;">
            <div style="max-width: 720px;">
                <div style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.12em; color: var(--bite-green); display: inline-flex; align-items: center; gap: 8px;"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9Z"/><path d="M19 3v4M21 5h-4M5 17v4M7 19H3"/></svg>Snap-to-Menu AI</div>
                <h2 class="lp-h2" style="font-family: var(--font-display); font-size: 46px; font-weight: 700; letter-spacing: -0.02em; color: var(--bite-forest); line-height: 1.05; margin: 12px 0 0;">Photograph your menu. Get a digital one.</h2>
                <p style="font-size: 18px; line-height: 1.6; color: var(--bite-ash); margin: 16px 0 0;">No retyping, no spreadsheets. Snap a photo of your printed menu and Bite reads it — pulling out categories, dishes, and prices into a live menu you can edit and publish in minutes.</p>
            </div>

            <div class="lp-snap-grid" style="display: grid; grid-template-columns: 1fr 56px 1fr; gap: 0; align-items: center; margin-top: 52px;">
                {{-- photo --}}
                <div style="position: relative; display: flex; justify-content: center;">
                    <div style="position: relative; width: 320px; max-width: 100%; background: #FBF7EC; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); padding: 28px 26px 34px; transform: rotate(-2.2deg); overflow: hidden; border: 1px solid #ECE3CF;">
                        <div style="text-align: center; font-family: var(--font-display); font-weight: 700; font-size: 22px; color: #3a3320; letter-spacing: 0.04em;">CAFÉ MENU</div>
                        <div style="height: 2px; background: #d9cdaf; margin: 12px 0 18px;"></div>
                        <div style="font-weight: 700; font-size: 13px; color: #6a5f3e; letter-spacing: 0.12em; text-transform: uppercase; margin-bottom: 10px;">Mains</div>
                        <div style="display: flex; flex-direction: column; gap: 11px;">
                            <div style="display: flex; justify-content: space-between; gap: 12px;"><span style="height: 9px; width: 56%; background: #d8cca9; border-radius: 3px;"></span><span style="height: 9px; width: 22px; background: #cabf9c; border-radius: 3px;"></span></div>
                            <div style="display: flex; justify-content: space-between; gap: 12px;"><span style="height: 9px; width: 68%; background: #d8cca9; border-radius: 3px;"></span><span style="height: 9px; width: 22px; background: #cabf9c; border-radius: 3px;"></span></div>
                        </div>
                        <div style="font-weight: 700; font-size: 13px; color: #6a5f3e; letter-spacing: 0.12em; text-transform: uppercase; margin: 18px 0 10px;">Cold drinks</div>
                        <div style="display: flex; flex-direction: column; gap: 11px;">
                            <div style="display: flex; justify-content: space-between; gap: 12px;"><span style="height: 9px; width: 50%; background: #d8cca9; border-radius: 3px;"></span><span style="height: 9px; width: 22px; background: #cabf9c; border-radius: 3px;"></span></div>
                            <div style="display: flex; justify-content: space-between; gap: 12px;"><span style="height: 9px; width: 60%; background: #d8cca9; border-radius: 3px;"></span><span style="height: 9px; width: 22px; background: #cabf9c; border-radius: 3px;"></span></div>
                        </div>
                    </div>
                </div>

                {{-- arrow --}}
                <div class="lp-snap-arrow" style="display: flex; align-items: center; justify-content: center; color: var(--bite-green);">
                    <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </div>

                {{-- extracted --}}
                <div style="background: #fff; border: 1px solid var(--bite-line); border-radius: var(--radius-xl); box-shadow: var(--shadow-md); padding: 22px; min-height: 320px; display: flex; flex-direction: column;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                        <span style="display: inline-flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--bite-forest); background: var(--bite-lime-100); padding: 6px 12px; border-radius: var(--radius-pill);"><span style="width: 7px; height: 7px; border-radius: 50%; background: var(--bite-green);"></span>4 dishes detected</span>
                        <span style="font-size: 12px; color: var(--bite-ash);">99% match</span>
                    </div>
                    <div id="lp-snap-rows" style="display: flex; flex-direction: column; gap: 8px; flex: 1;">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--bite-green); animation: scanRise .5s var(--ease-out) both;">Mains</div>
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; background: var(--bite-cream); border-radius: var(--radius-md); padding: 11px 13px; animation: scanRise .5s var(--ease-out) both; animation-delay: .06s;"><div><div style="font-weight: 600; font-size: 14px; color: var(--bite-charcoal);">Halloumi wrap</div><div style="font-size: 11px; color: var(--bite-ash);">Mains · editable</div></div><span style="font-family: var(--font-display); font-weight: 700; font-size: 14px; color: var(--bite-forest);">OMR 2.400</span></div>
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; background: var(--bite-cream); border-radius: var(--radius-md); padding: 11px 13px; animation: scanRise .5s var(--ease-out) both; animation-delay: .12s;"><div><div style="font-weight: 600; font-size: 14px; color: var(--bite-charcoal);">Lamb kofta plate</div><div style="font-size: 11px; color: var(--bite-ash);">Mains · editable</div></div><span style="font-family: var(--font-display); font-weight: 700; font-size: 14px; color: var(--bite-forest);">OMR 3.200</span></div>
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--bite-green); margin-top: 6px; animation: scanRise .5s var(--ease-out) both; animation-delay: .18s;">Cold drinks</div>
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; background: var(--bite-cream); border-radius: var(--radius-md); padding: 11px 13px; animation: scanRise .5s var(--ease-out) both; animation-delay: .24s;"><div><div style="font-weight: 600; font-size: 14px; color: var(--bite-charcoal);">Fresh mint lemonade</div><div style="font-size: 11px; color: var(--bite-ash);">Cold drinks · editable</div></div><span style="font-family: var(--font-display); font-weight: 700; font-size: 14px; color: var(--bite-forest);">OMR 1.200</span></div>
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; background: var(--bite-cream); border-radius: var(--radius-md); padding: 11px 13px; animation: scanRise .5s var(--ease-out) both; animation-delay: .30s;"><div><div style="font-weight: 600; font-size: 14px; color: var(--bite-charcoal);">Iced karak</div><div style="font-size: 11px; color: var(--bite-ash);">Cold drinks · editable</div></div><span style="font-family: var(--font-display); font-weight: 700; font-size: 14px; color: var(--bite-forest);">OMR 1.000</span></div>
                    </div>
                    <button type="button" id="lp-rescan" class="lp-pill-forest" style="margin-top: 18px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 13px; border-radius: var(--radius-pill); border: 2px solid var(--bite-forest); background: var(--bite-forest); color: #fff; font-family: var(--font-body); font-weight: 600; font-size: 15px; cursor: pointer; transition: filter var(--dur-fast), transform var(--dur-fast) var(--ease-bounce);">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/></svg>
                        Replay the scan
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== HOW IT WORKS ===== --}}
    <section id="how" class="lp-section" style="padding: 96px 0; background: var(--bite-mist); border-top: 1px solid var(--bite-line);">
        <div class="lp-container" style="max-width: 1120px; margin: 0 auto; padding: 0 40px;">
            <div style="text-align: center; max-width: 680px; margin: 0 auto;">
                <div style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.12em; color: var(--bite-green);">Up and running in a day</div>
                <h2 class="lp-h2" style="font-family: var(--font-display); font-size: 46px; font-weight: 700; letter-spacing: -0.02em; color: var(--bite-forest); line-height: 1.05; margin: 12px 0 0;">From sign-up to first order</h2>
                <p style="font-size: 18px; line-height: 1.6; color: var(--bite-ash); margin: 16px 0 0;">Bite's onboarding wizard walks you through five short steps. Most cafés are taking live orders the same afternoon.</p>
            </div>
            <div class="lp-steps-grid" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 18px; margin-top: 56px;">
                <div style="text-align: center;">
                    <div style="width: 60px; height: 60px; margin: 0 auto 18px; border-radius: 50%; background: var(--bite-lime); color: var(--bite-forest); display: flex; align-items: center; justify-content: center; font-family: var(--font-display); font-weight: 700; font-size: 24px;">1</div>
                    <h3 style="font-family: var(--font-display); font-size: 18px; color: var(--bite-forest); margin: 0 0 8px;">Create your shop</h3>
                    <p style="font-size: 14px; line-height: 1.55; color: var(--bite-ash); margin: 0;">Name it and pick your brand colour. Bite themes everything to match.</p>
                </div>
                <div style="text-align: center;">
                    <div style="width: 60px; height: 60px; margin: 0 auto 18px; border-radius: 50%; background: var(--bite-lime); color: var(--bite-forest); display: flex; align-items: center; justify-content: center; font-family: var(--font-display); font-weight: 700; font-size: 24px;">2</div>
                    <h3 style="font-family: var(--font-display); font-size: 18px; color: var(--bite-forest); margin: 0 0 8px;">Build your menu</h3>
                    <p style="font-size: 14px; line-height: 1.55; color: var(--bite-ash); margin: 0;">Snap a photo with Snap-to-Menu, or add items and modifiers by hand.</p>
                </div>
                <div style="text-align: center;">
                    <div style="width: 60px; height: 60px; margin: 0 auto 18px; border-radius: 50%; background: var(--bite-lime); color: var(--bite-forest); display: flex; align-items: center; justify-content: center; font-family: var(--font-display); font-weight: 700; font-size: 24px;">3</div>
                    <h3 style="font-family: var(--font-display); font-size: 18px; color: var(--bite-forest); margin: 0 0 8px;">Pick a theme</h3>
                    <p style="font-size: 14px; line-height: 1.55; color: var(--bite-ash); margin: 0;">Warm, modern, or dark — preview your guest menu live as you choose.</p>
                </div>
                <div style="text-align: center;">
                    <div style="width: 60px; height: 60px; margin: 0 auto 18px; border-radius: 50%; background: var(--bite-lime); color: var(--bite-forest); display: flex; align-items: center; justify-content: center; font-family: var(--font-display); font-weight: 700; font-size: 24px;">4</div>
                    <h3 style="font-family: var(--font-display); font-size: 18px; color: var(--bite-forest); margin: 0 0 8px;">Print QR codes</h3>
                    <p style="font-size: 14px; line-height: 1.55; color: var(--bite-ash); margin: 0;">Drop them on the tables and add your staff with their own PINs.</p>
                </div>
                <div style="text-align: center;">
                    <div style="width: 60px; height: 60px; margin: 0 auto 18px; border-radius: 50%; background: var(--bite-forest); color: #fff; display: flex; align-items: center; justify-content: center; font-family: var(--font-display); font-weight: 700; font-size: 24px;">5</div>
                    <h3 style="font-family: var(--font-display); font-size: 18px; color: var(--bite-forest); margin: 0 0 8px;">Go live</h3>
                    <p style="font-size: 14px; line-height: 1.55; color: var(--bite-ash); margin: 0;">Orders fire straight to the kitchen. You're open for business.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== QR ORDERING ===== --}}
    <section id="ordering" class="lp-section" style="padding: 96px 0; background: var(--bite-forest); color: #fff; position: relative; overflow: hidden;">
        <div style="position: absolute; width: 380px; height: 380px; background: #063a20; right: -120px; top: -90px; border-radius: var(--radius-blob);"></div>
        <div style="position: absolute; width: 50px; height: 50px; background: var(--bite-lime); left: 7%; bottom: 70px; border-radius: 50%;"></div>
        <div class="lp-container lp-ordering-grid" style="position: relative; z-index: 2; max-width: 1120px; margin: 0 auto; padding: 0 40px; display: grid; grid-template-columns: 1fr 0.82fr; gap: 56px; align-items: center;">
            <div>
                <div style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.12em; color: var(--bite-lime);">What your guests see</div>
                <h2 class="lp-h2" style="font-family: var(--font-display); font-size: 46px; font-weight: 700; letter-spacing: -0.02em; color: #fff; line-height: 1.05; margin: 12px 0 0;">A menu that takes the order for you</h2>
                <p style="font-size: 18px; line-height: 1.6; color: rgba(255,255,255,.8); margin: 16px 0 0; max-width: 480px;">Guests scan the QR code on the table, browse in their own language, and order from their phone. The ticket lands in your kitchen instantly — no app to download, no waiting to flag a server.</p>
                <div style="display: flex; flex-direction: column; gap: 14px; margin-top: 28px;">
                    <div style="display: flex; align-items: center; gap: 14px;"><span style="width: 40px; height: 40px; border-radius: 12px; flex: none; display: flex; align-items: center; justify-content: center; background: rgba(122,199,12,.16); color: var(--bite-lime);"><svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8l4 4M3 14h7M8 4h7M21 22l-5-11-5 11M14.5 18h5"/></svg></span><div><div style="font-weight: 600; font-size: 16px; color: #fff;">Bilingual, English and Arabic</div><div style="font-size: 14px; color: rgba(255,255,255,.66);">Guests switch language with one tap.</div></div></div>
                    <div style="display: flex; align-items: center; gap: 14px;"><span style="width: 40px; height: 40px; border-radius: 12px; flex: none; display: flex; align-items: center; justify-content: center; background: rgba(122,199,12,.16); color: var(--bite-lime);"><svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></span><div><div style="font-weight: 600; font-size: 16px; color: #fff;">Group ordering</div><div style="font-size: 14px; color: rgba(255,255,255,.66);">The whole table adds to one shared cart.</div></div></div>
                    <div style="display: flex; align-items: center; gap: 14px;"><span style="width: 40px; height: 40px; border-radius: 12px; flex: none; display: flex; align-items: center; justify-content: center; background: rgba(122,199,12,.16); color: var(--bite-lime);"><svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span><div><div style="font-weight: 600; font-size: 16px; color: #fff;">Live order tracking</div><div style="font-size: 14px; color: rgba(255,255,255,.66);">A private link shows guests their order status.</div></div></div>
                </div>
            </div>

            {{-- phone mock (warm theme) --}}
            <div style="display: flex; justify-content: center;">
                <div style="width: 290px; max-width: 100%; background: #0a0d0b; border-radius: 40px; padding: 12px; box-shadow: var(--shadow-lg);">
                    <div style="border-radius: 30px; overflow: hidden; background: #FBF7EF;">
                        <div style="background: #004225; color: #FFFFFF; padding: 30px 18px 16px; display: flex; align-items: center; justify-content: space-between;">
                            <div><div style="font-size: 11px; opacity: .7; letter-spacing: 0.06em; text-transform: uppercase;">Table 7</div><div style="font-family: var(--font-display); font-weight: 700; font-size: 19px;">Olive &amp; Thyme</div></div>
                            <div style="display: flex; border: 1px solid rgba(255,255,255,.4); border-radius: var(--radius-pill); overflow: hidden; font-size: 11px; font-weight: 700;"><span style="padding: 5px 10px; background: #7AC70C; color: #004225;">EN</span><span style="padding: 5px 10px; color: #FFFFFF;">ع</span></div>
                        </div>
                        <div style="display: flex; gap: 8px; padding: 14px 16px 4px; overflow: hidden;">
                            <span style="font-size: 12px; font-weight: 600; padding: 6px 13px; border-radius: var(--radius-pill); background: #7AC70C; color: #004225; white-space: nowrap;">Mains</span>
                            <span style="font-size: 12px; font-weight: 600; padding: 6px 13px; border-radius: var(--radius-pill); background: #FFFFFF; color: #8A7E6B; white-space: nowrap;">Salads</span>
                            <span style="font-size: 12px; font-weight: 600; padding: 6px 13px; border-radius: var(--radius-pill); background: #FFFFFF; color: #8A7E6B; white-space: nowrap;">Drinks</span>
                        </div>
                        <div style="padding: 12px 16px; display: flex; flex-direction: column; gap: 10px;">
                            <div style="display: flex; gap: 12px; background: #FFFFFF; border: 1px solid #ECE3D4; border-radius: 16px; padding: 10px; align-items: center;"><span style="width: 50px; height: 50px; border-radius: 12px; flex: none; background: linear-gradient(135deg,#7AC70C,#37B34A);"></span><div style="flex: 1; min-width: 0;"><div style="font-weight: 600; font-size: 14px; color: #2A2118;">Halloumi wrap</div><div style="font-size: 12px; color: #8A7E6B;">OMR 2.400</div></div><span style="width: 30px; height: 30px; flex: none; border-radius: 50%; background: #7AC70C; color: #004225; display: flex; align-items: center; justify-content: center;"><svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span></div>
                            <div style="display: flex; gap: 12px; background: #FFFFFF; border: 1px solid #ECE3D4; border-radius: 16px; padding: 10px; align-items: center;"><span style="width: 50px; height: 50px; border-radius: 12px; flex: none; background: linear-gradient(135deg,#B7C40D,#7AC70C);"></span><div style="flex: 1; min-width: 0;"><div style="font-weight: 600; font-size: 14px; color: #2A2118;">Garden bowl</div><div style="font-size: 12px; color: #8A7E6B;">OMR 3.400</div></div><span style="width: 30px; height: 30px; flex: none; border-radius: 50%; background: #7AC70C; color: #004225; display: flex; align-items: center; justify-content: center;"><svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span></div>
                        </div>
                        <div style="margin: 4px 14px 16px; background: #7AC70C; color: #004225; border-radius: var(--radius-pill); padding: 12px; display: flex; align-items: center; justify-content: space-between; font-weight: 700; font-size: 14px;"><span>View cart · 3</span><span>OMR 6.800</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== FEATURE GRID ===== --}}
    <section id="product" class="lp-section" style="padding: 96px 0;">
        <div class="lp-container" style="max-width: 1120px; margin: 0 auto; padding: 0 40px;">
            <div style="text-align: center; max-width: 680px; margin: 0 auto;">
                <div style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.12em; color: var(--bite-green);">One system, end to end</div>
                <h2 class="lp-h2" style="font-family: var(--font-display); font-size: 46px; font-weight: 700; letter-spacing: -0.02em; color: var(--bite-forest); line-height: 1.05; margin: 12px 0 0;">Everything your café runs on</h2>
                <p style="font-size: 18px; line-height: 1.6; color: var(--bite-ash); margin: 16px 0 0;">From the first tap on the menu to the close-of-day cash count — Bite keeps the floor, the kitchen, and the back office on the same page.</p>
            </div>
            <div class="lp-features-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; margin-top: 54px;">
                <div class="lp-card" style="background: #fff; border: 1px solid var(--bite-line); border-radius: var(--radius-xl); padding: 30px; box-shadow: var(--shadow-sm);">
                    <span style="width: 54px; height: 54px; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; margin-bottom: 18px; background: var(--bite-lime-100); color: var(--bite-green);"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg></span>
                    <h3 style="font-family: var(--font-display); font-size: 21px; color: var(--bite-forest); margin: 0 0 9px;">Point of sale</h3>
                    <p style="font-size: 15px; line-height: 1.6; color: var(--bite-ash); margin: 0;">A fast, tap-friendly terminal built for the rush. Split bills, apply offers, and reconcile cash at close.</p>
                </div>
                <div class="lp-card" style="background: #fff; border: 1px solid var(--bite-line); border-radius: var(--radius-xl); padding: 30px; box-shadow: var(--shadow-sm);">
                    <span style="width: 54px; height: 54px; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; margin-bottom: 18px; background: var(--bite-lime-100); color: var(--bite-green);"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V18H6Z"/><path d="M6 17h12"/></svg></span>
                    <h3 style="font-family: var(--font-display); font-size: 21px; color: var(--bite-forest); margin: 0 0 9px;">Kitchen display</h3>
                    <p style="font-size: 15px; line-height: 1.6; color: var(--bite-ash); margin: 0;">Orders fire straight to the line the moment they're placed — no paper tickets, no missed modifiers.</p>
                </div>
                <div class="lp-card" style="background: #fff; border: 1px solid var(--bite-line); border-radius: var(--radius-xl); padding: 30px; box-shadow: var(--shadow-sm);">
                    <span style="width: 54px; height: 54px; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; margin-bottom: 18px; background: var(--bite-lime-100); color: var(--bite-green);"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg></span>
                    <h3 style="font-family: var(--font-display); font-size: 21px; color: var(--bite-forest); margin: 0 0 9px;">Split payments</h3>
                    <p style="font-size: 15px; line-height: 1.6; color: var(--bite-ash); margin: 0;">Card or cash, split across the table, settled in one tap — with clean reporting behind every sale.</p>
                </div>
                <div class="lp-card" style="background: #fff; border: 1px solid var(--bite-line); border-radius: var(--radius-xl); padding: 30px; box-shadow: var(--shadow-sm);">
                    <span style="width: 54px; height: 54px; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; margin-bottom: 18px; background: var(--bite-lime-100); color: var(--bite-green);"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.5 13.5 17 22l-5-3-5 3 1.5-8.5"/></svg></span>
                    <h3 style="font-family: var(--font-display); font-size: 21px; color: var(--bite-forest); margin: 0 0 9px;">Loyalty points</h3>
                    <p style="font-size: 15px; line-height: 1.6; color: var(--bite-ash); margin: 0;">Phone-based points — one for every OMR spent — that quietly bring your regulars back through the door.</p>
                </div>
                <div class="lp-card" style="background: #fff; border: 1px solid var(--bite-line); border-radius: var(--radius-xl); padding: 30px; box-shadow: var(--shadow-sm);">
                    <span style="width: 54px; height: 54px; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; margin-bottom: 18px; background: var(--bite-lime-100); color: var(--bite-green);"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 17l9 5 9-5"/></svg></span>
                    <h3 style="font-family: var(--font-display); font-size: 21px; color: var(--bite-forest); margin: 0 0 9px;">Menu builder</h3>
                    <p style="font-size: 15px; line-height: 1.6; color: var(--bite-ash); margin: 0;">Categories, modifier groups, and time-based pricing rules — happy-hour prices that switch themselves.</p>
                </div>
                <div class="lp-card" style="background: #fff; border: 1px solid var(--bite-line); border-radius: var(--radius-xl); padding: 30px; box-shadow: var(--shadow-sm);">
                    <span style="width: 54px; height: 54px; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; margin-bottom: 18px; background: var(--bite-lime-100); color: var(--bite-green);"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 16v-4M12 16V8M17 16v-6"/></svg></span>
                    <h3 style="font-family: var(--font-display); font-size: 21px; color: var(--bite-forest); margin: 0 0 9px;">Live reports</h3>
                    <p style="font-size: 15px; line-height: 1.6; color: var(--bite-ash); margin: 0;">See sales, top dishes, and peak hours in real time — and act on them before service ends.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== PRICING ===== --}}
    <section id="pricing" class="lp-section" style="padding: 96px 0; background: var(--bite-mist); border-top: 1px solid var(--bite-line);">
        <div class="lp-container" style="max-width: 1120px; margin: 0 auto; padding: 0 40px;">
            <div style="text-align: center; max-width: 680px; margin: 0 auto;">
                <div style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.12em; color: var(--bite-green);">Simple, honest pricing</div>
                <h2 class="lp-h2" style="font-family: var(--font-display); font-size: 46px; font-weight: 700; letter-spacing: -0.02em; color: var(--bite-forest); line-height: 1.05; margin: 12px 0 0;">Start free. Upgrade when you grow.</h2>
                <p style="font-size: 18px; line-height: 1.6; color: var(--bite-ash); margin: 16px 0 0;">Every new shop gets {{ config('billing.trial_days') }} days of Pro, free — no card needed. Keep the Free plan forever, or switch to Pro any time.</p>
            </div>

            <div class="lp-pricing-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 22px; max-width: 760px; margin: 54px auto 0; align-items: stretch;">
                {{-- Free --}}
                <div style="background: #fff; border: 1px solid var(--bite-line); border-radius: var(--radius-xl); padding: 34px; display: flex; flex-direction: column; box-shadow: var(--shadow-sm);">
                    <div style="font-family: var(--font-display); font-weight: 600; font-size: 15px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--bite-green);">Free</div>
                    <div style="display: flex; align-items: baseline; gap: 6px; margin: 14px 0 2px;"><span style="font-family: var(--font-display); font-weight: 700; font-size: 48px; color: var(--bite-forest);">OMR 0</span></div>
                    <div style="font-size: 14px; color: var(--bite-ash);">forever · 1 staff · up to {{ config('billing.plans.free.product_limit') }} products</div>
                    <ul style="list-style: none; padding: 0; margin: 24px 0 28px; display: flex; flex-direction: column; gap: 13px;">
                        <li style="display: flex; align-items: center; gap: 10px; font-size: 15px; color: var(--bite-charcoal);"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="var(--bite-green)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex:none;"><path d="M20 6 9 17l-5-5"/></svg>POS terminal</li>
                        <li style="display: flex; align-items: center; gap: 10px; font-size: 15px; color: var(--bite-charcoal);"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="var(--bite-green)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex:none;"><path d="M20 6 9 17l-5-5"/></svg>Guest QR menu</li>
                        <li style="display: flex; align-items: center; gap: 10px; font-size: 15px; color: var(--bite-charcoal);"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="var(--bite-green)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex:none;"><path d="M20 6 9 17l-5-5"/></svg>Kitchen display</li>
                    </ul>
                    <a href="{{ route('register') }}" class="lp-pill-outline-forest" style="margin-top: auto; display: inline-flex; align-items: center; justify-content: center; padding: 14px; border-radius: var(--radius-pill); font-weight: 600; font-size: 16px; background: transparent; color: var(--bite-forest); text-decoration: none; border: 2px solid var(--bite-forest); transition: background var(--dur-fast), transform var(--dur-fast) var(--ease-bounce);">Get started free</a>
                </div>

                {{-- Pro --}}
                <div style="background: var(--bite-forest); color: #fff; border: 2px solid var(--bite-forest); border-radius: var(--radius-xl); padding: 34px; display: flex; flex-direction: column; box-shadow: var(--shadow-lg); position: relative; overflow: hidden;">
                    <div style="position: absolute; right: -50px; bottom: -50px; width: 150px; height: 150px; background: #063a20; border-radius: var(--radius-blob);"></div>
                    <div style="position: relative; z-index: 2; display: flex; align-items: center; justify-content: space-between;">
                        <div style="font-family: var(--font-display); font-weight: 600; font-size: 15px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--bite-lime);">Pro</div>
                        <span style="background: var(--bite-lime); color: var(--bite-forest); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; padding: 5px 12px; border-radius: var(--radius-pill);">{{ config('billing.trial_days') }}-day free trial</span>
                    </div>
                    <div style="position: relative; z-index: 2; display: flex; align-items: baseline; gap: 6px; margin: 14px 0 2px;"><span style="font-family: var(--font-display); font-weight: 700; font-size: 48px; color: #fff;">OMR {{ config('billing.plans.pro.price') }}</span><span style="font-size: 16px; color: rgba(255,255,255,.7);">/ month</span></div>
                    <div style="position: relative; z-index: 2; font-size: 14px; color: rgba(255,255,255,.7);">unlimited staff &amp; products</div>
                    <ul style="position: relative; z-index: 2; list-style: none; padding: 0; margin: 24px 0 28px; display: flex; flex-direction: column; gap: 13px;">
                        <li style="display: flex; align-items: center; gap: 10px; font-size: 15px; color: rgba(255,255,255,.92);"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="var(--bite-lime)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex:none;"><path d="M20 6 9 17l-5-5"/></svg>Everything in Free</li>
                        <li style="display: flex; align-items: center; gap: 10px; font-size: 15px; color: rgba(255,255,255,.92);"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="var(--bite-lime)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex:none;"><path d="M20 6 9 17l-5-5"/></svg>Unlimited staff &amp; products</li>
                        <li style="display: flex; align-items: center; gap: 10px; font-size: 15px; color: rgba(255,255,255,.92);"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="var(--bite-lime)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex:none;"><path d="M20 6 9 17l-5-5"/></svg>Reports &amp; analytics</li>
                        <li style="display: flex; align-items: center; gap: 10px; font-size: 15px; color: rgba(255,255,255,.92);"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="var(--bite-lime)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex:none;"><path d="M20 6 9 17l-5-5"/></svg>Menu engineering &amp; pricing rules</li>
                        <li style="display: flex; align-items: center; gap: 10px; font-size: 15px; color: rgba(255,255,255,.92);"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="var(--bite-lime)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex:none;"><path d="M20 6 9 17l-5-5"/></svg>Priority support</li>
                    </ul>
                    <a href="{{ route('register') }}" class="lp-pill-lime" style="position: relative; z-index: 2; margin-top: auto; display: inline-flex; align-items: center; justify-content: center; padding: 14px; border-radius: var(--radius-pill); font-weight: 600; font-size: 16px; background: var(--bite-lime); color: var(--bite-forest); text-decoration: none; border: 2px solid var(--bite-lime); transition: filter var(--dur-fast), transform var(--dur-fast) var(--ease-bounce);">Start free trial</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== FINAL CTA ===== --}}
    <section class="lp-section" style="padding: 92px 0; background: var(--bite-lime); position: relative; overflow: hidden;">
        <div style="position: absolute; width: 280px; height: 280px; background: #a7d63a; right: -90px; bottom: -120px; border-radius: var(--radius-blob);"></div>
        <div style="position: absolute; width: 46px; height: 46px; background: var(--bite-forest); left: 9%; top: 50px; border-radius: 50%;"></div>
        <div class="lp-container lp-cta-row" style="position: relative; z-index: 2; max-width: 1120px; margin: 0 auto; padding: 0 40px; display: flex; align-items: center; justify-content: space-between; gap: 40px; flex-wrap: wrap;">
            <div>
                <h2 style="font-family: var(--font-display); font-weight: 700; font-size: 44px; letter-spacing: -0.02em; color: var(--bite-forest); line-height: 1.05; margin: 0;">Ready to serve every bite?</h2>
                <p style="font-size: 18px; color: var(--bite-pine); margin: 12px 0 0; max-width: 480px;">Snap your menu, print your codes, and take your first order today. Free for {{ config('billing.trial_days') }} days.</p>
            </div>
            <a href="{{ route('register') }}" class="lp-pill-forest" style="display: inline-flex; align-items: center; gap: 8px; padding: 17px 36px; border-radius: var(--radius-pill); font-weight: 700; font-size: 18px; background: var(--bite-forest); color: #fff; text-decoration: none; border: 2px solid var(--bite-forest); box-shadow: var(--shadow-offset); transition: filter var(--dur-fast), transform var(--dur-fast) var(--ease-bounce);">Start free trial</a>
        </div>
    </section>

    {{-- ===== FOOTER ===== --}}
    <div style="background: var(--bite-charcoal); color: rgba(255,255,255,.68); padding: 60px 0 34px;">
        <div class="lp-container" style="max-width: 1120px; margin: 0 auto; padding: 0 40px;">
            <div class="lp-footer-grid" style="display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 36px;">
                <div>
                    <div style="font-family: var(--font-display); font-weight: 700; font-size: 28px; letter-spacing: -0.02em; color: #fff; margin-bottom: 14px;">Bite<span style="color: var(--bite-lime);">.</span></div>
                    <p style="font-size: 14px; line-height: 1.6; max-width: 280px; margin: 0;">The all-in-one point of sale and ordering platform built for modern restaurants and cafés.</p>
                </div>
                <div>
                    <h5 style="color: #fff; font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em; margin: 0 0 16px; font-family: var(--font-display);">Product</h5>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 11px; font-size: 14px;">
                        <li><a href="#product" class="lp-footlink" style="color: inherit; text-decoration: none;">Point of sale</a></li>
                        <li><a href="#snap" class="lp-footlink" style="color: inherit; text-decoration: none;">Snap-to-Menu</a></li>
                        <li><a href="#ordering" class="lp-footlink" style="color: inherit; text-decoration: none;">QR ordering</a></li>
                        <li><a href="#pricing" class="lp-footlink" style="color: inherit; text-decoration: none;">Pricing</a></li>
                    </ul>
                </div>
                <div>
                    <h5 style="color: #fff; font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em; margin: 0 0 16px; font-family: var(--font-display);">Company</h5>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 11px; font-size: 14px;">
                        <li><a href="#top" class="lp-footlink" style="color: inherit; text-decoration: none;">About</a></li>
                        <li><a href="#top" class="lp-footlink" style="color: inherit; text-decoration: none;">Careers</a></li>
                        <li><a href="#top" class="lp-footlink" style="color: inherit; text-decoration: none;">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h5 style="color: #fff; font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em; margin: 0 0 16px; font-family: var(--font-display);">Support</h5>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 11px; font-size: 14px;">
                        <li><a href="#top" class="lp-footlink" style="color: inherit; text-decoration: none;">Help centre</a></li>
                        <li><a href="#how" class="lp-footlink" style="color: inherit; text-decoration: none;">Setup guide</a></li>
                        <li><a href="{{ route('login') }}" class="lp-footlink" style="color: inherit; text-decoration: none;">Log in</a></li>
                    </ul>
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 46px; padding-top: 26px; border-top: 1px solid rgba(255,255,255,.12); font-size: 13px; flex-wrap: wrap; gap: 12px;">
                <span>© {{ date('Y') }} Bite. All rights reserved.</span>
                <span style="color: var(--bite-lime); font-weight: 600;">Every byte to every bite</span>
            </div>
        </div>
    </div>

</div>

<script>
    /* Replay the Snap-to-Menu reveal cascade on demand (degrades gracefully without JS). */
    (function () {
        var btn = document.getElementById('lp-rescan');
        var rows = document.getElementById('lp-snap-rows');
        if (!btn || !rows) return;
        btn.addEventListener('click', function () {
            Array.prototype.forEach.call(rows.children, function (el) {
                el.style.animation = 'none';
                void el.offsetWidth; /* force reflow so the animation restarts */
                el.style.animation = '';
            });
        });
    })();
</script>
</body>
</html>
