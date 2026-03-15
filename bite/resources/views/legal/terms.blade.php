<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service — Bite POS</title>
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

        <h1>Terms of Service</h1>
        <p class="last-updated">Last updated: {{ now()->format('F j, Y') }}</p>

        <p>These Terms of Service ("Terms") govern your use of the Bite POS platform operated by Bite Systems ("we", "us", "our"). By creating an account or using our service, you agree to these Terms.</p>

        <h2>1. Service Description</h2>
        <p>Bite POS is a cloud-based point-of-sale system for restaurants and cafes. The service includes a POS terminal, kitchen display system, QR-based guest ordering, menu management, and reporting tools.</p>

        <h2>2. Account Registration</h2>
        <ul>
            <li>You must provide accurate and complete information when creating an account.</li>
            <li>You are responsible for maintaining the security of your account credentials and staff PINs.</li>
            <li>You must be at least 18 years old to create an account.</li>
            <li>One person or business entity per account. You may operate multiple shop locations under one account.</li>
        </ul>

        <h2>3. Subscription and Billing</h2>
        <ul>
            <li>New accounts receive a 14-day free trial of the Pro plan.</li>
            <li>After the trial, you may continue on the Free plan or subscribe to the Pro plan.</li>
            <li>Pro plan subscriptions are billed monthly in Omani Rial (OMR).</li>
            <li>You may cancel your subscription at any time. Cancellation takes effect at the end of the current billing period.</li>
            <li>We reserve the right to change pricing with 30 days' notice.</li>
        </ul>

        <h2>4. Free Plan Limitations</h2>
        <p>The Free plan includes limited features: 1 staff member and up to 20 products. To access unlimited staff, unlimited products, reports, and priority support, upgrade to the Pro plan.</p>

        <h2>5. Acceptable Use</h2>
        <p>You agree not to:</p>
        <ul>
            <li>Use the service for any unlawful purpose.</li>
            <li>Attempt to access other users' accounts or data.</li>
            <li>Interfere with or disrupt the service or its infrastructure.</li>
            <li>Reverse-engineer, decompile, or attempt to extract the source code.</li>
            <li>Resell or redistribute the service without our written consent.</li>
        </ul>

        <h2>6. Your Data</h2>
        <p>You retain ownership of all data you enter into Bite POS (menu items, orders, staff information, etc.). We do not claim ownership of your business data. See our <a href="{{ route('legal.privacy') }}">Privacy Policy</a> for details on how we handle your data.</p>

        <h2>7. Service Availability</h2>
        <p>We strive to maintain high availability but do not guarantee uninterrupted service. We may perform scheduled maintenance with advance notice. We are not liable for losses caused by service outages or interruptions beyond our reasonable control.</p>

        <h2>8. Limitation of Liability</h2>
        <p>To the maximum extent permitted by law, Bite Systems shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of the service, including but not limited to lost profits, lost data, or business interruption.</p>

        <h2>9. Termination</h2>
        <ul>
            <li>You may close your account at any time.</li>
            <li>We may suspend or terminate your account for violation of these Terms, with notice when practicable.</li>
            <li>Upon termination, your data will be retained for 90 days to allow for export, then permanently deleted.</li>
        </ul>

        <h2>10. Changes to These Terms</h2>
        <p>We may update these Terms from time to time. We will notify you of material changes via email or an in-app notification at least 14 days before they take effect. Continued use of the service after changes constitutes acceptance.</p>

        <h2>11. Governing Law</h2>
        <p>These Terms are governed by the laws of the Sultanate of Oman. Any disputes shall be resolved in the courts of Muscat, Oman.</p>

        <h2>12. Contact</h2>
        <p>If you have questions about these Terms, contact us at <a href="mailto:nasserbusaidi@gmail.com">nasserbusaidi@gmail.com</a>.</p>
    </div>
</body>
</html>
