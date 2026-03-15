<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy — Bite POS</title>
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🍊</text></svg>">

    <style>
        @font-face {
            font-family: 'Rubik';
            src: url('/fonts/Rubik-VariableFont_wght.ttf') format('truetype');
            font-weight: 300 900;
            font-style: normal;
            font-display: swap;
        }

        :root {
            --paper: 247 246 241;
            --ink: 18 25 36;
            --crema: 236 105 46;
            --ink-soft: 79 86 97;
            --line: 195 199 203;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Rubik', system-ui, -apple-system, sans-serif;
            color: rgb(var(--ink));
            background: rgb(var(--paper));
            line-height: 1.7;
            padding: 2rem 1rem;
        }

        .container {
            max-width: 720px;
            margin: 0 auto;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 2rem;
            color: rgb(var(--crema));
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .back-link:hover { text-decoration: underline; }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }

        .last-updated {
            color: rgb(var(--ink-soft));
            font-size: 0.85rem;
            margin-bottom: 2.5rem;
        }

        h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 2rem;
            margin-bottom: 0.75rem;
        }

        p, ul {
            margin-bottom: 1rem;
            color: rgb(var(--ink-soft));
        }

        ul {
            padding-left: 1.5rem;
        }

        li {
            margin-bottom: 0.35rem;
        }

        a {
            color: rgb(var(--crema));
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ url('/') }}" class="back-link">&larr; Back to Bite POS</a>

        <h1>Privacy Policy</h1>
        <p class="last-updated">Last updated: {{ now()->format('F j, Y') }}</p>

        <p>Bite Systems ("we", "us", "our") operates the Bite POS platform. This Privacy Policy explains how we collect, use, and protect your information when you use our service.</p>

        <h2>1. Information We Collect</h2>
        <p>We collect the following types of information:</p>
        <ul>
            <li><strong>Account information:</strong> Name, email address, phone number, and business details when you register.</li>
            <li><strong>Business data:</strong> Menu items, product details, categories, pricing, and staff information you enter into the system.</li>
            <li><strong>Transaction data:</strong> Order details, payment amounts, and timestamps generated through POS usage.</li>
            <li><strong>Usage data:</strong> Browser type, device information, IP address, and pages visited to improve our service.</li>
        </ul>

        <h2>2. How We Use Your Information</h2>
        <ul>
            <li>To provide and maintain the Bite POS service.</li>
            <li>To process transactions and manage your account.</li>
            <li>To send service-related notifications (e.g., billing, system updates).</li>
            <li>To generate reports and analytics for your business.</li>
            <li>To improve our platform and develop new features.</li>
            <li>To provide customer support.</li>
        </ul>

        <h2>3. Data Storage and Security</h2>
        <p>Your data is stored on secure cloud servers. We use industry-standard encryption for data in transit (TLS/SSL) and at rest. We implement access controls to ensure only authorized personnel can access your data.</p>

        <h2>4. Payment Processing</h2>
        <p>Subscription payments are processed by Stripe. We do not store your credit card details on our servers. Stripe's handling of your payment data is governed by their own privacy policy.</p>

        <h2>5. Data Sharing</h2>
        <p>We do not sell your personal information. We may share data with:</p>
        <ul>
            <li><strong>Service providers:</strong> Third-party services that help us operate (e.g., hosting, payment processing, error tracking).</li>
            <li><strong>Legal requirements:</strong> When required by law, regulation, or legal process.</li>
        </ul>

        <h2>6. Guest Ordering Data</h2>
        <p>When guests place orders through QR menus, we collect only the information necessary to process the order (items selected, order preferences). Guest orders are associated with your shop and are accessible through your dashboard.</p>

        <h2>7. Data Retention</h2>
        <p>We retain your data for as long as your account is active. If you close your account, we will delete your data within 90 days, except where retention is required by law (e.g., financial records).</p>

        <h2>8. Your Rights</h2>
        <p>You may:</p>
        <ul>
            <li>Access, update, or delete your account information at any time through your settings.</li>
            <li>Export your business data (orders, products, reports).</li>
            <li>Request deletion of your account and all associated data.</li>
        </ul>

        <h2>9. Cookies</h2>
        <p>We use essential cookies to maintain your session and remember your preferences. We do not use third-party advertising cookies.</p>

        <h2>10. Changes to This Policy</h2>
        <p>We may update this policy from time to time. We will notify you of significant changes via email or an in-app notification.</p>

        <h2>11. Contact</h2>
        <p>If you have questions about this Privacy Policy, contact us at <a href="mailto:nasserbusaidi@gmail.com">nasserbusaidi@gmail.com</a>.</p>
    </div>
</body>
</html>
