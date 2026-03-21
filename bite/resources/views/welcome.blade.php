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

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Rubik';
            src: url('/fonts/Rubik-VariableFont_wght.ttf') format('truetype');
            font-weight: 300 900;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Rubik';
            src: url('/fonts/Rubik-Italic-VariableFont_wght.ttf') format('truetype');
            font-weight: 300 900;
            font-style: italic;
            font-display: swap;
        }
    </style>

    <style>
        /* ===== Design Tokens ===== */
        :root {
            --paper: 247 246 241;
            --ink: 18 25 36;
            --crema: 236 105 46;
            --canvas: 238 236 229;
            --panel: 255 255 252;
            --panel-muted: 228 227 220;
            --ink-soft: 79 86 97;
            --line: 195 199 203;
            --signal: 33 138 111;
            --alert: 186 59 48;
            --focus: 250 129 72;
        }

        /* ===== Reset & Base ===== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html {
            scroll-behavior: smooth;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            font-family: 'Rubik', system-ui, -apple-system, sans-serif;
            color: rgb(var(--ink));
            background:
                radial-gradient(circle at 10% 0%, rgb(var(--crema) / 0.08), transparent 32%),
                radial-gradient(circle at 90% 16%, rgb(var(--signal) / 0.06), transparent 38%),
                linear-gradient(130deg, rgb(var(--canvas)) 0%, rgb(var(--paper)) 55%, rgb(231 230 223) 100%);
            background-attachment: fixed;
            line-height: 1.6;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image: radial-gradient(rgb(255 255 255 / 0.2) 0.35px, transparent 0.35px);
            background-size: 4px 4px;
            opacity: 0.25;
            z-index: 0;
        }

        ::selection {
            background: rgb(var(--crema) / 0.25);
            color: rgb(var(--ink));
        }

        *:focus-visible {
            outline: 2px solid rgb(var(--focus));
            outline-offset: 2px;
        }

        img, svg { display: block; max-width: 100%; }

        /* ===== Typography ===== */
        h1, h2, h3, h4, h5 {
            font-family: 'Rubik', system-ui, sans-serif;
            letter-spacing: 0.01em;
            line-height: 1.05;
            text-wrap: balance;
        }

        p {
            text-wrap: pretty;
        }

        .font-mono {
            font-family: 'JetBrains Mono', 'SF Mono', monospace;
        }

        /* ===== Layout ===== */
        .landing-container {
            max-width: 1180px;
            margin: 0 auto;
            padding: 0 1.25rem;
        }

        @media (min-width: 640px) {
            .landing-container { padding: 0 2rem; }
        }

        .section-gap {
            padding: 5rem 0;
        }

        @media (min-width: 768px) {
            .section-gap { padding: 7rem 0; }
        }

        /* ===== Components ===== */
        .tag {
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            padding: 0.375rem 0.875rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            background-color: rgb(var(--panel-muted));
            color: rgb(var(--ink-soft));
            border: 1px solid rgb(var(--line) / 0.9);
        }

        .tag--crema {
            background-color: rgb(var(--crema) / 0.1);
            color: rgb(var(--crema));
            border-color: rgb(var(--crema) / 0.25);
        }

        .tag--signal {
            background-color: rgb(var(--signal) / 0.1);
            color: rgb(var(--signal));
            border-color: rgb(var(--signal) / 0.25);
        }

        .section-label {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.24em;
            color: rgb(var(--ink-soft));
        }

        .section-title {
            font-size: clamp(1.75rem, 4vw, 2.75rem);
            font-weight: 800;
            color: rgb(var(--ink));
            margin-top: 0.75rem;
        }

        .section-subtitle {
            font-size: 1.0625rem;
            line-height: 1.7;
            color: rgb(var(--ink-soft));
            max-width: 580px;
            margin-top: 1rem;
        }

        .surface-card {
            position: relative;
            overflow: hidden;
            border-radius: 1rem;
            border: 1px solid rgb(var(--line) / 0.95);
            background-color: rgb(var(--panel) / 0.96);
            backdrop-filter: blur(12px);
            box-shadow:
                0 24px 40px -30px rgb(8 13 23 / 0.12),
                0 1px 0 rgb(255 255 255 / 0.75) inset;
        }

        .surface-card::before {
            content: '';
            position: absolute;
            inset: 0 auto auto 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, rgb(var(--crema) / 0.9), transparent 70%);
            pointer-events: none;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border-radius: 0.5rem;
            border: 1px solid rgb(var(--ink));
            padding: 0.75rem 2rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            text-decoration: none;
            color: rgb(var(--panel));
            background-color: rgb(var(--ink));
            box-shadow: 0 8px 24px -14px rgb(10 15 24 / 0.9);
            transition-property: transform, background-color, border-color, box-shadow;
            transition-duration: 200ms;
            transition-timing-function: ease;
            cursor: pointer;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            background-color: rgb(var(--crema));
            border-color: rgb(var(--crema));
            box-shadow: 0 12px 24px -12px rgb(var(--crema) / 0.55);
        }

        .btn-primary:active {
            transform: scale(0.96);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border-radius: 0.5rem;
            border: 1px solid rgb(var(--line));
            padding: 0.75rem 2rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            text-decoration: none;
            color: rgb(var(--ink));
            background-color: rgb(var(--panel));
            transition-property: transform, border-color, box-shadow;
            transition-duration: 200ms;
            transition-timing-function: ease;
            cursor: pointer;
        }

        .btn-secondary:hover {
            transform: translateY(-1px);
            border-color: rgb(var(--ink));
            box-shadow: 0 8px 16px -14px rgb(10 15 24 / 0.7);
        }

        .btn-secondary:active {
            transform: scale(0.96);
        }

        .btn-whatsapp {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border-radius: 0.5rem;
            border: 1px solid rgb(33 138 111 / 0.3);
            padding: 0.75rem 2rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            text-decoration: none;
            color: #fff;
            background-color: rgb(var(--signal));
            transition-property: transform, box-shadow;
            transition-duration: 200ms;
            transition-timing-function: ease;
            cursor: pointer;
        }

        .btn-whatsapp:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px -12px rgb(var(--signal) / 0.5);
        }

        .btn-whatsapp:active {
            transform: scale(0.96);
        }

        /* ===== Animations ===== */
        @keyframes fade-up {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 0 0 rgb(var(--crema) / 0.4); }
            50% { box-shadow: 0 0 0 12px rgb(var(--crema) / 0); }
        }

        .animate-fade-up {
            animation: fade-up 600ms cubic-bezier(0.23, 1, 0.32, 1) both;
        }

        .animate-delay-1 { animation-delay: 100ms; }
        .animate-delay-2 { animation-delay: 200ms; }
        .animate-delay-3 { animation-delay: 300ms; }
        .animate-delay-4 { animation-delay: 400ms; }
        .animate-delay-5 { animation-delay: 500ms; }
        .animate-delay-6 { animation-delay: 600ms; }

        /* ===== Navigation ===== */
        .landing-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 1rem 0;
            transition-property: background-color, backdrop-filter, border-bottom-color, padding;
            transition-duration: 300ms;
            transition-timing-function: ease;
        }

        .landing-nav.scrolled {
            background: rgb(var(--panel) / 0.88);
            backdrop-filter: blur(16px) saturate(1.2);
            border-bottom: 1px solid rgb(var(--line) / 0.5);
            padding: 0.625rem 0;
        }

        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            text-decoration: none;
            color: rgb(var(--ink));
        }

        .nav-brand-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgb(var(--ink));
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgb(var(--panel));
            font-family: 'Rubik', sans-serif;
            font-weight: 800;
            font-size: 1.125rem;
        }

        .nav-brand-text {
            font-family: 'Rubik', sans-serif;
            font-weight: 800;
            font-size: 1.25rem;
            letter-spacing: -0.01em;
        }

        .nav-links {
            display: none;
            align-items: center;
            gap: 2rem;
            list-style: none;
        }

        @media (min-width: 768px) {
            .nav-links { display: flex; }
        }

        .nav-links a {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            text-decoration: none;
            color: rgb(var(--ink-soft));
            transition-property: color;
            transition-duration: 200ms;
            transition-timing-function: ease;
            padding: 0.75rem 0;
        }

        .nav-links a:hover {
            color: rgb(var(--crema));
        }

        .nav-cta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-login {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            text-decoration: none;
            color: rgb(var(--ink-soft));
            transition-property: color;
            transition-duration: 200ms;
            transition-timing-function: ease;
            padding: 0.75rem 0.5rem;
        }

        .nav-login:hover { color: rgb(var(--ink)); }

        /* Mobile menu */
        .mobile-menu-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: none;
            background: none;
            cursor: pointer;
            color: rgb(var(--ink));
        }

        @media (min-width: 768px) {
            .mobile-menu-btn { display: none; }
        }

        .mobile-nav {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 99;
            background: rgb(var(--panel) / 0.97);
            backdrop-filter: blur(20px);
            padding: 6rem 2rem 2rem;
        }

        .mobile-nav.open { display: flex; flex-direction: column; gap: 0.5rem; }

        .mobile-nav a {
            display: block;
            padding: 1rem 0;
            font-family: 'Rubik', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            color: rgb(var(--ink));
            border-bottom: 1px solid rgb(var(--line) / 0.4);
        }

        .mobile-nav a:hover { color: rgb(var(--crema)); }

        /* ===== Hero ===== */
        .hero {
            padding-top: 8rem;
            padding-bottom: 4rem;
            position: relative;
        }

        @media (min-width: 768px) {
            .hero { padding-top: 10rem; padding-bottom: 6rem; }
        }

        .hero-grid {
            display: grid;
            gap: 3rem;
            align-items: center;
        }

        @media (min-width: 960px) {
            .hero-grid {
                grid-template-columns: 1.15fr 1fr;
                gap: 4rem;
            }
        }

        .hero-headline {
            font-size: clamp(2.25rem, 5.5vw, 3.75rem);
            font-weight: 800;
            line-height: 0.96;
            letter-spacing: -0.02em;
            color: rgb(var(--ink));
        }

        .hero-headline .accent {
            color: rgb(var(--crema));
        }

        .hero-sub {
            font-size: 1.125rem;
            line-height: 1.7;
            color: rgb(var(--ink-soft));
            max-width: 500px;
            margin-top: 1.25rem;
        }

        .hero-ctas {
            display: flex;
            flex-wrap: wrap;
            gap: 0.875rem;
            margin-top: 2rem;
        }

        .hero-proof {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgb(var(--line) / 0.5);
        }

        .hero-proof-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgb(var(--signal));
            box-shadow: 0 0 0 4px rgb(var(--signal) / 0.18);
            flex-shrink: 0;
        }

        .hero-proof-text {
            font-size: 0.8125rem;
            color: rgb(var(--ink-soft));
        }

        .hero-proof-text strong {
            color: rgb(var(--ink));
            font-weight: 700;
        }

        /* Hero mockup */
        .hero-mockup {
            position: relative;
        }

        .mockup-frame {
            border-radius: 1rem;
            border: 1px solid rgb(var(--line));
            background: rgb(var(--panel));
            overflow: hidden;
            box-shadow:
                0 32px 64px -24px rgb(8 13 23 / 0.18),
                0 1px 0 rgb(255 255 255 / 0.75) inset;
        }

        .mockup-titlebar {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgb(var(--line) / 0.6);
            background: rgb(var(--canvas) / 0.5);
        }

        .mockup-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgb(var(--line));
        }

        .mockup-dot:first-child { background: rgb(var(--alert) / 0.7); }
        .mockup-dot:nth-child(2) { background: rgb(var(--crema) / 0.7); }
        .mockup-dot:nth-child(3) { background: rgb(var(--signal) / 0.7); }

        .mockup-body {
            padding: 1.25rem;
            min-height: 280px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0.625rem;
        }

        @media (min-width: 640px) {
            .mockup-body { min-height: 340px; padding: 1.5rem; }
        }

        .mockup-item {
            border-radius: 0.625rem;
            border: 1px solid rgb(var(--line) / 0.6);
            background: rgb(var(--canvas) / 0.4);
            padding: 0.875rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .mockup-item-icon {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: rgb(var(--crema) / 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
        }

        .mockup-item-name {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.5625rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgb(var(--ink-soft));
        }

        .mockup-item-price {
            font-family: 'Rubik', sans-serif;
            font-size: 0.9375rem;
            font-weight: 800;
            color: rgb(var(--ink));
            font-variant-numeric: tabular-nums;
        }

        .mockup-sidebar {
            grid-column: 1 / -1;
            border-radius: 0.625rem;
            border: 1px solid rgb(var(--crema) / 0.2);
            background: rgb(var(--crema) / 0.04);
            padding: 0.875rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .mockup-total-label {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.5625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: rgb(var(--ink-soft));
        }

        .mockup-total-value {
            font-family: 'Rubik', sans-serif;
            font-size: 1.25rem;
            font-weight: 800;
            color: rgb(var(--crema));
            font-variant-numeric: tabular-nums;
        }

        .mockup-badge {
            position: absolute;
            top: -12px;
            right: -8px;
            background: rgb(var(--signal));
            color: #fff;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.5625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            box-shadow: 0 4px 12px rgb(var(--signal) / 0.3);
            animation: pulse-glow 2s infinite;
            animation-name: none;
        }

        @media (min-width: 640px) {
            .mockup-badge { top: -14px; right: -12px; }
        }

        /* ===== Problem Section ===== */
        .problem { position: relative; }

        .problem-grid {
            display: grid;
            gap: 2rem;
        }

        @media (min-width: 768px) {
            .problem-grid {
                grid-template-columns: 1fr 1fr;
                gap: 3rem;
                align-items: center;
            }
        }

        .problem-title {
            font-size: clamp(1.5rem, 3.5vw, 2.25rem);
            font-weight: 800;
            line-height: 1.1;
        }

        .problem-title .highlight {
            color: rgb(var(--crema));
        }

        .problem-desc {
            font-size: 1rem;
            line-height: 1.75;
            color: rgb(var(--ink-soft));
            margin-top: 1.25rem;
        }

        .pain-points {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .pain-point {
            display: flex;
            gap: 0.875rem;
            align-items: flex-start;
        }

        .pain-icon {
            flex-shrink: 0;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            margin-top: 2px;
        }

        .pain-icon--bad {
            background: rgb(var(--alert) / 0.1);
            color: rgb(var(--alert));
        }

        .pain-icon--good {
            background: rgb(var(--signal) / 0.1);
            color: rgb(var(--signal));
        }

        .pain-text {
            font-size: 0.9375rem;
            color: rgb(var(--ink-soft));
            line-height: 1.6;
        }

        .pain-text strong {
            color: rgb(var(--ink));
            font-weight: 700;
        }

        /* ===== Features ===== */
        .features-grid {
            display: grid;
            gap: 1rem;
            margin-top: 3rem;
        }

        @media (min-width: 640px) {
            .features-grid { grid-template-columns: repeat(2, 1fr); gap: 1.25rem; }
        }

        @media (min-width: 960px) {
            .features-grid { grid-template-columns: repeat(3, 1fr); }
        }

        .feature-card {
            padding: 2rem 1.75rem;
            transition-property: transform, box-shadow;
            transition-duration: 250ms;
            transition-timing-function: ease;
        }

        .feature-card:hover {
            transform: translateY(-3px);
            box-shadow:
                0 32px 64px -24px rgb(8 13 23 / 0.15),
                0 1px 0 rgb(255 255 255 / 0.75) inset;
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.375rem;
            margin-bottom: 1.25rem;
        }

        .feature-icon--crema {
            background: rgb(var(--crema) / 0.1);
        }

        .feature-icon--signal {
            background: rgb(var(--signal) / 0.1);
        }

        .feature-icon--ink {
            background: rgb(var(--ink) / 0.08);
        }

        .feature-name {
            font-family: 'Rubik', sans-serif;
            font-size: 1.125rem;
            font-weight: 700;
            color: rgb(var(--ink));
        }

        .feature-desc {
            font-size: 0.875rem;
            line-height: 1.7;
            color: rgb(var(--ink-soft));
            margin-top: 0.5rem;
        }

        /* ===== How It Works ===== */
        .steps-grid {
            display: grid;
            gap: 2rem;
            margin-top: 3rem;
        }

        @media (min-width: 768px) {
            .steps-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 1.5rem;
            }
        }

        .step-card {
            text-align: center;
            padding: 2.5rem 2rem;
        }

        .step-number {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgb(var(--ink));
            color: rgb(var(--panel));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: 'Rubik', sans-serif;
            font-weight: 800;
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .step-title {
            font-family: 'Rubik', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: rgb(var(--ink));
        }

        .step-desc {
            font-size: 0.875rem;
            line-height: 1.7;
            color: rgb(var(--ink-soft));
            margin-top: 0.625rem;
        }

        .step-connector {
            display: none;
        }

        @media (min-width: 768px) {
            .step-connector {
                display: block;
                position: absolute;
                top: 50%;
                right: -0.75rem;
                transform: translateY(-50%);
                width: 1.5rem;
                height: 2px;
                background: rgb(var(--line));
            }
            .step-card { position: relative; }
            .step-card:last-child .step-connector { display: none; }
        }

        /* ===== Pricing ===== */
        .pricing-grid {
            display: grid;
            gap: 1.5rem;
            margin-top: 3rem;
        }

        @media (min-width: 768px) {
            .pricing-grid { grid-template-columns: repeat(2, 1fr); max-width: 800px; margin-left: auto; margin-right: auto; }
        }

        .pricing-card {
            padding: 2.5rem 2rem;
            display: flex;
            flex-direction: column;
        }

        .pricing-card--featured {
            border-color: rgb(var(--crema) / 0.4);
        }

        .pricing-card--featured::before {
            background: linear-gradient(90deg, rgb(var(--crema)), rgb(var(--focus)));
            height: 2px;
        }

        .pricing-name {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: rgb(var(--ink-soft));
        }

        .pricing-price {
            font-family: 'Rubik', sans-serif;
            font-weight: 800;
            margin-top: 0.75rem;
            display: flex;
            align-items: baseline;
            gap: 0.25rem;
        }

        .pricing-amount {
            font-size: 2.75rem;
            line-height: 1;
            color: rgb(var(--ink));
            font-variant-numeric: tabular-nums;
        }

        .pricing-period {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.6875rem;
            color: rgb(var(--ink-soft));
            font-weight: 500;
        }

        .pricing-desc {
            font-size: 0.875rem;
            color: rgb(var(--ink-soft));
            margin-top: 0.625rem;
            line-height: 1.6;
        }

        .pricing-features {
            list-style: none;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgb(var(--line) / 0.5);
            display: flex;
            flex-direction: column;
            gap: 0.625rem;
            flex: 1;
        }

        .pricing-feature {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            font-size: 0.875rem;
            color: rgb(var(--ink-soft));
        }

        .pricing-check {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: rgb(var(--signal) / 0.1);
            color: rgb(var(--signal));
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.625rem;
            font-weight: 700;
        }

        .pricing-cta {
            margin-top: 2rem;
        }

        /* ===== Social Proof ===== */
        .testimonials-grid {
            display: grid;
            gap: 1.5rem;
            margin-top: 3rem;
        }

        @media (min-width: 768px) {
            .testimonials-grid { grid-template-columns: repeat(3, 1fr); }
        }

        .testimonial-card {
            padding: 2rem 1.75rem;
        }

        .testimonial-text {
            font-size: 0.9375rem;
            line-height: 1.75;
            color: rgb(var(--ink));
            font-style: italic;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgb(var(--line) / 0.4);
        }

        .testimonial-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgb(var(--crema) / 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Rubik', sans-serif;
            font-weight: 800;
            font-size: 0.875rem;
            color: rgb(var(--crema));
            flex-shrink: 0;
        }

        .testimonial-name {
            font-size: 0.8125rem;
            font-weight: 700;
            color: rgb(var(--ink));
        }

        .testimonial-role {
            font-size: 0.6875rem;
            color: rgb(var(--ink-soft));
        }

        /* ===== FAQ ===== */
        .faq-list {
            max-width: 720px;
            margin: 3rem auto 0;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .faq-item {
            border-radius: 0.75rem;
            border: 1px solid rgb(var(--line) / 0.7);
            background: rgb(var(--panel) / 0.7);
            overflow: hidden;
            transition-property: border-color;
            transition-duration: 200ms;
            transition-timing-function: ease;
        }

        .faq-item:hover {
            border-color: rgb(var(--line));
        }

        .faq-trigger {
            width: 100%;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border: none;
            background: none;
            cursor: pointer;
            text-align: left;
            font-family: 'Rubik', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: rgb(var(--ink));
        }

        .faq-trigger:hover { color: rgb(var(--crema)); }
        .faq-trigger:active { transform: scale(0.96); }

        .faq-chevron {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            transition-property: transform;
            transition-duration: 300ms;
            transition-timing-function: ease;
            color: rgb(var(--ink-soft));
        }

        .faq-item.open .faq-chevron { transform: rotate(180deg); }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition-property: max-height;
            transition-duration: 300ms;
            transition-timing-function: ease;
        }

        .faq-item.open .faq-answer {
            max-height: 300px;
        }

        .faq-answer-inner {
            padding: 0 1.5rem 1.5rem;
            font-size: 0.9375rem;
            line-height: 1.75;
            color: rgb(var(--ink-soft));
        }

        /* ===== CTA Banner ===== */
        .cta-banner {
            text-align: center;
            padding: 4rem 2rem;
            border-color: rgb(var(--crema) / 0.3);
        }

        .cta-banner::before {
            background: linear-gradient(90deg, rgb(var(--crema)), rgb(var(--focus)));
            height: 2px;
        }

        .cta-title {
            font-size: clamp(1.5rem, 3.5vw, 2.25rem);
            font-weight: 800;
        }

        .cta-desc {
            font-size: 1.0625rem;
            color: rgb(var(--ink-soft));
            margin-top: 0.75rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.7;
        }

        .cta-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.875rem;
            justify-content: center;
            margin-top: 2rem;
        }

        /* ===== Footer ===== */
        .landing-footer {
            border-top: 1px solid rgb(var(--line) / 0.5);
            padding: 3rem 0 2rem;
        }

        .footer-grid {
            display: grid;
            gap: 2.5rem;
        }

        @media (min-width: 768px) {
            .footer-grid {
                grid-template-columns: 1.5fr 1fr 1fr 1fr;
                gap: 2rem;
            }
        }

        .footer-brand-desc {
            font-size: 0.875rem;
            color: rgb(var(--ink-soft));
            line-height: 1.7;
            margin-top: 0.75rem;
            max-width: 300px;
        }

        .footer-heading {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.5625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: rgb(var(--ink-soft));
            margin-bottom: 1rem;
        }

        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .footer-links a {
            font-size: 0.875rem;
            color: rgb(var(--ink-soft));
            text-decoration: none;
            transition-property: color;
            transition-duration: 200ms;
            transition-timing-function: ease;
            padding: 0.375rem 0;
            display: inline-block;
        }

        .footer-links a:hover { color: rgb(var(--crema)); }

        .footer-bottom {
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgb(var(--line) / 0.4);
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .footer-copy {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: rgb(var(--ink-soft));
        }

        .footer-legal {
            display: flex;
            gap: 1.5rem;
            list-style: none;
        }

        .footer-legal a {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.625rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgb(var(--ink-soft));
            text-decoration: none;
            transition-property: color;
            transition-duration: 200ms;
            transition-timing-function: ease;
            padding: 0.375rem 0;
            display: inline-block;
        }

        .footer-legal a:hover { color: rgb(var(--crema)); }

        /* ===== Decorative ===== */
        .blob {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            filter: blur(80px);
        }

        .blob--crema {
            background: rgb(var(--crema) / 0.15);
        }

        .blob--signal {
            background: rgb(var(--signal) / 0.1);
        }

        /* ===== Utility ===== */
        .text-center { text-align: center; }
        .mx-auto { margin-left: auto; margin-right: auto; }
        .relative { position: relative; }
        .overflow-hidden { overflow: hidden; }
        .z-1 { position: relative; z-index: 1; }
    </style>
</head>
<body>

    {{-- ==================== NAVIGATION ==================== --}}
    <nav class="landing-nav" id="nav">
        <div class="landing-container">
            <div class="nav-inner">
                <a href="#" class="nav-brand">
                    <div class="nav-brand-icon">B</div>
                    <span class="nav-brand-text">Bite</span>
                </a>

                <ul class="nav-links">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#pricing">Pricing</a></li>
                    <li><a href="#faq">FAQ</a></li>
                </ul>

                <div class="nav-cta">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn-primary">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="nav-login">Log In</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn-primary">Start Free</a>
                            @endif
                        @endauth
                    @endif
                    <button class="mobile-menu-btn" onclick="toggleMobileMenu()" aria-label="Toggle menu">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <line x1="3" y1="12" x2="21" y2="12"/>
                            <line x1="3" y1="18" x2="21" y2="18"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    {{-- Mobile Navigation --}}
    <div class="mobile-nav" id="mobileNav">
        <a href="#features" onclick="closeMobileMenu()">Features</a>
        <a href="#how-it-works" onclick="closeMobileMenu()">How It Works</a>
        <a href="#pricing" onclick="closeMobileMenu()">Pricing</a>
        <a href="#faq" onclick="closeMobileMenu()">FAQ</a>
        @if (Route::has('login'))
            @auth
                <a href="{{ url('/dashboard') }}">Dashboard</a>
            @else
                <a href="{{ route('login') }}">Log In</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}">Create Account</a>
                @endif
            @endauth
        @endif
    </div>

    {{-- ==================== HERO ==================== --}}
    <section class="hero">
        <div class="landing-container">
            <div class="hero-grid">
                <div class="animate-fade-up">
                    <span class="tag tag--crema">Built for restaurants in Oman</span>
                    <h1 class="hero-headline" style="margin-top: 1.25rem;">
                        The POS system your restaurant <span class="accent">actually needs.</span>
                    </h1>
                    <p class="hero-sub">
                        Take orders, run your kitchen, let guests order from their phones, and track everything in real time. One system, no headaches.
                    </p>
                    <div class="hero-ctas">
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn-primary">Start Free Trial</a>
                        @endif
                        <a href="https://wa.me/96891233177" target="_blank" rel="noopener" class="btn-whatsapp">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            Chat on WhatsApp
                        </a>
                    </div>
                    <div class="hero-proof">
                        <div class="hero-proof-dot"></div>
                        <p class="hero-proof-text">
                            <strong>No special hardware required.</strong> Works on any tablet, laptop, or phone.
                        </p>
                    </div>
                </div>

                {{-- POS Mockup --}}
                <div class="hero-mockup animate-fade-up animate-delay-2">
                    <div class="mockup-badge">Live Preview</div>
                    <div class="mockup-frame">
                        <div class="mockup-titlebar">
                            <div class="mockup-dot"></div>
                            <div class="mockup-dot"></div>
                            <div class="mockup-dot"></div>
                        </div>
                        <div class="mockup-body">
                            <div class="mockup-item">
                                <div class="mockup-item-icon">&#9749;</div>
                                <div class="mockup-item-name">Karak Chai</div>
                                <div class="mockup-item-price">0.500</div>
                            </div>
                            <div class="mockup-item">
                                <div class="mockup-item-icon">&#127828;</div>
                                <div class="mockup-item-name">Chicken Shawarma</div>
                                <div class="mockup-item-price">1.200</div>
                            </div>
                            <div class="mockup-item">
                                <div class="mockup-item-icon">&#127849;</div>
                                <div class="mockup-item-name">Kunafa</div>
                                <div class="mockup-item-price">1.800</div>
                            </div>
                            <div class="mockup-sidebar">
                                <div>
                                    <div class="mockup-total-label">Order Total</div>
                                    <div class="mockup-total-value">3.500 OMR</div>
                                </div>
                                <div class="btn-primary" style="padding: 0.5rem 1rem; font-size: 0.5rem; pointer-events: none;">
                                    Pay Now
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Decorative blobs --}}
        <div class="blob blob--crema" style="width: 300px; height: 300px; top: -80px; inset-inline-start: -100px; position: absolute;"></div>
        <div class="blob blob--signal" style="width: 250px; height: 250px; bottom: -60px; inset-inline-end: -80px; position: absolute;"></div>
    </section>

    {{-- ==================== PROBLEM STATEMENT ==================== --}}
    <section class="section-gap" id="problem">
        <div class="landing-container">
            <div class="problem-grid">
                <div class="animate-fade-up">
                    <span class="section-label">The Problem</span>
                    <h2 class="problem-title">
                        Expensive POS systems <span class="highlight">weren't built</span> for restaurants in Oman.
                    </h2>
                    <p class="problem-desc">
                        Most POS systems on the market charge hundreds per month, require proprietary hardware, and don't understand how restaurants here actually operate. You end up paying for features you'll never use.
                    </p>
                </div>
                <div class="animate-fade-up animate-delay-2">
                    <ul class="pain-points">
                        <li class="pain-point">
                            <div class="pain-icon pain-icon--bad">&#10005;</div>
                            <p class="pain-text"><strong>Locked into expensive hardware</strong> that costs 500+ OMR before you take a single order.</p>
                        </li>
                        <li class="pain-point">
                            <div class="pain-icon pain-icon--bad">&#10005;</div>
                            <p class="pain-text"><strong>Monthly fees that eat your margins</strong> with 50+ OMR/month contracts and hidden charges.</p>
                        </li>
                        <li class="pain-point">
                            <div class="pain-icon pain-icon--bad">&#10005;</div>
                            <p class="pain-text"><strong>No local support</strong> when things go wrong. Just a helpline in another timezone.</p>
                        </li>
                        <li class="pain-point" style="margin-top: 0.5rem;">
                            <div class="pain-icon pain-icon--good">&#10003;</div>
                            <p class="pain-text"><strong>Bite is different.</strong> Built in Oman, priced for Oman, supported via WhatsApp.</p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== FEATURES ==================== --}}
    <section class="section-gap" id="features" style="position: relative;">
        <div class="landing-container z-1">
            <div class="text-center">
                <span class="section-label">What You Get</span>
                <h2 class="section-title mx-auto" style="max-width: 600px;">Everything your restaurant needs. Nothing it doesn't.</h2>
                <p class="section-subtitle mx-auto">Six tools in one system. No plugins, no add-ons, no surprise charges.</p>
            </div>

            <div class="features-grid">
                <div class="surface-card feature-card animate-fade-up">
                    <div class="feature-icon feature-icon--crema">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="rgb(236,105,46)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
                    </div>
                    <h3 class="feature-name">POS Terminal</h3>
                    <p class="feature-desc">Beautiful, fast order-taking interface. Add items, apply modifiers, split bills, and process payments in seconds.</p>
                </div>

                <div class="surface-card feature-card animate-fade-up animate-delay-1">
                    <div class="feature-icon feature-icon--signal">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="rgb(33,138,111)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 3h-8l-2 4h12z"/></svg>
                    </div>
                    <h3 class="feature-name">Kitchen Display</h3>
                    <p class="feature-desc">Real-time ticket flow to your kitchen. Orders appear instantly with status tracking from preparing to ready.</p>
                </div>

                <div class="surface-card feature-card animate-fade-up animate-delay-2">
                    <div class="feature-icon feature-icon--crema">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="rgb(236,105,46)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 7h.01"/><path d="M17 7h.01"/><path d="M7 17h.01"/><path d="M17 17h.01"/><rect x="7" y="7" width="10" height="10"/></svg>
                    </div>
                    <h3 class="feature-name">Guest QR Ordering</h3>
                    <p class="feature-desc">Guests scan a QR code at their table, browse your menu on their phone, and place orders directly. No app download needed.</p>
                </div>

                <div class="surface-card feature-card animate-fade-up animate-delay-3">
                    <div class="feature-icon feature-icon--ink">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="rgb(18,25,36)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/></svg>
                    </div>
                    <h3 class="feature-name">Reports &amp; Analytics</h3>
                    <p class="feature-desc">Daily sales, top products, revenue trends, and staff performance. Know exactly how your restaurant is doing.</p>
                </div>

                <div class="surface-card feature-card animate-fade-up animate-delay-4">
                    <div class="feature-icon feature-icon--signal">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="rgb(33,138,111)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    </div>
                    <h3 class="feature-name">Menu Builder</h3>
                    <p class="feature-desc">Create categories, add products with images, set prices in OMR, and configure modifier groups. Changes go live instantly.</p>
                </div>

                <div class="surface-card feature-card animate-fade-up animate-delay-5">
                    <div class="feature-icon feature-icon--crema">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="rgb(236,105,46)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                    </div>
                    <h3 class="feature-name">Mobile-First Design</h3>
                    <p class="feature-desc">Works beautifully on iPads, Android tablets, laptops, and phones. Your staff can take orders from anywhere in the restaurant.</p>
                </div>
            </div>
        </div>

        <div class="blob blob--signal" style="width: 350px; height: 350px; top: 20%; inset-inline-start: -150px; position: absolute;"></div>
    </section>

    {{-- ==================== HOW IT WORKS ==================== --}}
    <section class="section-gap" id="how-it-works">
        <div class="landing-container">
            <div class="text-center">
                <span class="section-label">How It Works</span>
                <h2 class="section-title">Up and running in three steps.</h2>
                <p class="section-subtitle mx-auto">No installation, no downloads, no waiting for a technician. Start taking orders today.</p>
            </div>

            <div class="steps-grid">
                <div class="surface-card step-card animate-fade-up">
                    <div class="step-number">1</div>
                    <div class="step-connector"></div>
                    <h3 class="step-title">Set Up Your Menu</h3>
                    <p class="step-desc">Create your account, add categories and products, set prices in OMR, and upload images. Takes about 20 minutes.</p>
                </div>

                <div class="surface-card step-card animate-fade-up animate-delay-2">
                    <div class="step-number">2</div>
                    <div class="step-connector"></div>
                    <h3 class="step-title">Take Orders</h3>
                    <p class="step-desc">Open the POS terminal on any device. Your staff logs in with a 4-digit PIN and starts taking orders immediately.</p>
                </div>

                <div class="surface-card step-card animate-fade-up animate-delay-4">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Track Everything</h3>
                    <p class="step-desc">Orders flow to the kitchen display in real time. Review daily reports, monitor sales trends, and export data anytime.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== PRICING ==================== --}}
    <section class="section-gap" id="pricing" style="position: relative;">
        <div class="landing-container z-1">
            <div class="text-center">
                <span class="section-label">Simple Pricing</span>
                <h2 class="section-title">Start free. Scale when ready.</h2>
                <p class="section-subtitle mx-auto">No contracts, no hidden fees. Cancel anytime. All prices in Omani Rial.</p>
            </div>

            <div class="pricing-grid">
                {{-- Free --}}
                <div class="surface-card pricing-card animate-fade-up">
                    <div class="pricing-name">Free</div>
                    <div class="pricing-price">
                        <span class="pricing-amount">0</span>
                        <span class="pricing-period">OMR / month</span>
                    </div>
                    <p class="pricing-desc">Get started at no cost. Perfect for trying Bite with your team.</p>
                    <ul class="pricing-features">
                        <li class="pricing-feature">
                            <span class="pricing-check">&#10003;</span>
                            POS Terminal
                        </li>
                        <li class="pricing-feature">
                            <span class="pricing-check">&#10003;</span>
                            Guest Menu
                        </li>
                        <li class="pricing-feature">
                            <span class="pricing-check">&#10003;</span>
                            Kitchen Display
                        </li>
                        <li class="pricing-feature">
                            <span class="pricing-check">&#10003;</span>
                            1 staff member
                        </li>
                        <li class="pricing-feature">
                            <span class="pricing-check">&#10003;</span>
                            Up to 20 products
                        </li>
                    </ul>
                    <div class="pricing-cta">
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn-secondary" style="width: 100%;">Get Started Free</a>
                        @endif
                    </div>
                </div>

                {{-- Pro --}}
                <div class="surface-card pricing-card pricing-card--featured animate-fade-up animate-delay-2">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div class="pricing-name">Pro</div>
                        <span class="tag tag--signal" style="font-size: 0.5rem;">Most Popular</span>
                    </div>
                    <div class="pricing-price">
                        <span class="pricing-amount">20</span>
                        <span class="pricing-period">OMR / month</span>
                    </div>
                    <p class="pricing-desc">For restaurants that want the full platform with no limits.</p>
                    <ul class="pricing-features">
                        <li class="pricing-feature">
                            <span class="pricing-check">&#10003;</span>
                            Everything in Free
                        </li>
                        <li class="pricing-feature">
                            <span class="pricing-check">&#10003;</span>
                            Unlimited Staff
                        </li>
                        <li class="pricing-feature">
                            <span class="pricing-check">&#10003;</span>
                            Unlimited Products
                        </li>
                        <li class="pricing-feature">
                            <span class="pricing-check">&#10003;</span>
                            Reports &amp; Analytics
                        </li>
                        <li class="pricing-feature">
                            <span class="pricing-check">&#10003;</span>
                            Priority Support
                        </li>
                    </ul>
                    <div class="pricing-cta">
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn-primary" style="width: 100%;">Start 14-Day Free Trial</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="blob blob--crema" style="width: 300px; height: 300px; bottom: -100px; inset-inline-end: -100px; position: absolute;"></div>
    </section>

    {{-- ==================== SOCIAL PROOF ==================== --}}
    <section class="section-gap" id="testimonials">
        <div class="landing-container">
            <div class="text-center">
                <span class="section-label">What People Say</span>
                <h2 class="section-title">Trusted by restaurants across Oman.</h2>
            </div>

            <div class="surface-card animate-fade-up" style="text-align: center; padding: 3rem 2rem; max-width: 600px; margin: 0 auto;">
                <p style="color: rgb(var(--ink-soft)); font-size: 1.1rem; line-height: 1.6;">
                    We're just getting started. Customer testimonials coming soon as restaurants across Oman begin using Bite.
                </p>
            </div>
        </div>
    </section>

    {{-- ==================== FAQ ==================== --}}
    <section class="section-gap" id="faq">
        <div class="landing-container">
            <div class="text-center">
                <span class="section-label">Questions &amp; Answers</span>
                <h2 class="section-title">Frequently asked questions.</h2>
            </div>

            <div class="faq-list">
                <div class="faq-item" onclick="toggleFaq(this)">
                    <button class="faq-trigger">
                        What hardware do I need?
                        <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            Any device with a modern web browser &mdash; iPad, Android tablet, laptop, or desktop. No special POS hardware required. For receipt printing, we support standard thermal printers via PrintNode. You can start with just a tablet.
                        </div>
                    </div>
                </div>

                <div class="faq-item" onclick="toggleFaq(this)">
                    <button class="faq-trigger">
                        Does it work without internet?
                        <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            Bite POS is cloud-based and requires an internet connection for full functionality. However, we include offline support that keeps the interface available during brief outages so your service never stops completely. A stable Wi-Fi connection is recommended for daily operations.
                        </div>
                    </div>
                </div>

                <div class="faq-item" onclick="toggleFaq(this)">
                    <button class="faq-trigger">
                        How long does setup take?
                        <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            Most restaurants are up and running within 30 minutes. Create your account, add your menu categories and products, set up staff PINs, and you're ready to take orders. Our team is available on WhatsApp if you need any help during setup.
                        </div>
                    </div>
                </div>

                <div class="faq-item" onclick="toggleFaq(this)">
                    <button class="faq-trigger">
                        Does it support Omani Rial (OMR)?
                        <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            Yes. Bite POS is built specifically for the Omani market. All prices, totals, and reports use OMR with proper 3-decimal-place formatting (e.g., 1.500 OMR). No currency conversion issues.
                        </div>
                    </div>
                </div>

                <div class="faq-item" onclick="toggleFaq(this)">
                    <button class="faq-trigger">
                        Can my guests order from their phones?
                        <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            Yes. Print a QR code for each table, and guests can scan it to browse your menu and place orders directly from their phone. No app download required. Orders go straight to your kitchen display in real time.
                        </div>
                    </div>
                </div>

                <div class="faq-item" onclick="toggleFaq(this)">
                    <button class="faq-trigger">
                        Is there a contract or commitment?
                        <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            No contracts. No commitment. Start with a free 14-day trial, and pay monthly after that. You can cancel anytime with no penalties. We believe in earning your business every month.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== CTA BANNER ==================== --}}
    <section class="section-gap">
        <div class="landing-container">
            <div class="surface-card cta-banner animate-fade-up">
                <span class="section-label">Ready to Start?</span>
                <h2 class="cta-title" style="margin-top: 0.75rem;">Get your restaurant on Bite today.</h2>
                <p class="cta-desc">Start your free 14-day trial. No credit card required. Set up in under 30 minutes.</p>
                <div class="cta-buttons">
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn-primary">Start Free Trial</a>
                    @endif
                    <a href="https://wa.me/96891233177" target="_blank" rel="noopener" class="btn-whatsapp">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Talk to Us on WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== FOOTER ==================== --}}
    <footer class="landing-footer">
        <div class="landing-container">
            <div class="footer-grid">
                <div>
                    <a href="#" class="nav-brand">
                        <div class="nav-brand-icon">B</div>
                        <span class="nav-brand-text">Bite</span>
                    </a>
                    <p class="footer-brand-desc">
                        Modern POS system built for restaurants and cafes in Oman. Simple, affordable, and designed for how you actually work.
                    </p>
                </div>

                <div>
                    <h4 class="footer-heading">Product</h4>
                    <ul class="footer-links">
                        <li><a href="#features">Features</a></li>
                        <li><a href="#pricing">Pricing</a></li>
                        <li><a href="#how-it-works">How It Works</a></li>
                        <li><a href="#faq">FAQ</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="footer-heading">Company</h4>
                    <ul class="footer-links">
                        <li><a href="mailto:nasserbusaidi@gmail.com">Contact Us</a></li>
                        <li><a href="https://wa.me/96891233177" target="_blank" rel="noopener">WhatsApp Support</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="footer-heading">Get Started</h4>
                    <ul class="footer-links">
                        @if (Route::has('login'))
                            <li><a href="{{ route('login') }}">Log In</a></li>
                        @endif
                        @if (Route::has('register'))
                            <li><a href="{{ route('register') }}">Create Account</a></li>
                        @endif
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p class="footer-copy">&copy; {{ now()->year }} Bite Systems. Muscat, Oman.</p>
                <ul class="footer-legal">
                    <li><a href="{{ route('legal.privacy') }}">Privacy Policy</a></li>
                    <li><a href="{{ route('legal.terms') }}">Terms of Service</a></li>
                </ul>
            </div>
        </div>
    </footer>

    {{-- ==================== SCRIPTS ==================== --}}
    <script>
        // Navigation scroll effect
        const nav = document.getElementById('nav');
        window.addEventListener('scroll', () => {
            nav.classList.toggle('scrolled', window.scrollY > 40);
        });

        // Mobile menu
        function toggleMobileMenu() {
            document.getElementById('mobileNav').classList.toggle('open');
        }
        function closeMobileMenu() {
            document.getElementById('mobileNav').classList.remove('open');
        }

        // FAQ accordion
        function toggleFaq(item) {
            const wasOpen = item.classList.contains('open');
            // Close all
            document.querySelectorAll('.faq-item').forEach(el => el.classList.remove('open'));
            // Open clicked (unless it was already open)
            if (!wasOpen) item.classList.add('open');
        }

        // Scroll-triggered animations (Intersection Observer)
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        document.querySelectorAll('.animate-fade-up').forEach(el => {
            el.style.opacity = '0';
            el.style.animationPlayState = 'paused';
            observer.observe(el);
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href === '#') return;
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    const navHeight = nav.offsetHeight;
                    const targetPosition = target.getBoundingClientRect().top + window.scrollY - navHeight - 20;
                    window.scrollTo({ top: targetPosition, behavior: 'smooth' });
                }
            });
        });
    </script>
</body>
</html>
