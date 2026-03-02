<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome to Bite</title>
</head>
<body style="margin:0; padding:0; background-color:#F7F6F1; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#121924;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F7F6F1; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#FFFFFE; border-radius:16px; border:1px solid #C3C7CB; overflow:hidden;">
                    {{-- Top accent bar --}}
                    <tr>
                        <td style="height:4px; background:linear-gradient(90deg, #EC6D2E, #218E6F);"></td>
                    </tr>

                    {{-- Logo --}}
                    <tr>
                        <td style="padding:32px 32px 0 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="width:36px; height:36px; background-color:#121924; border-radius:8px; text-align:center; vertical-align:middle; color:#FFFFFE; font-size:20px; font-weight:900; font-family:Georgia, serif;">B</td>
                                    <td style="padding-left:12px; font-size:22px; font-weight:800; letter-spacing:-0.02em;">Bite</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Welcome --}}
                    <tr>
                        <td style="padding:24px 32px 0 32px;">
                            <p style="margin:0 0 4px 0; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.18em; color:#4F5661;">Welcome Aboard</p>
                            <h1 style="margin:0; font-size:28px; font-weight:800; line-height:1.1; color:#121924;">Hi {{ $userName }},</h1>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:16px 32px 0 32px;">
                            <p style="margin:0 0 16px 0; font-size:15px; line-height:1.6; color:#4F5661;">
                                Your shop <strong style="color:#121924;">{{ $shopName }}</strong> is set up and ready to go. You have 14 days of full access to explore everything Bite offers — no credit card needed.
                            </p>
                        </td>
                    </tr>

                    {{-- Quick Start Steps --}}
                    <tr>
                        <td style="padding:8px 32px 0 32px;">
                            <p style="margin:0 0 12px 0; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.18em; color:#4F5661;">Quick Start</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:10px 14px; background-color:#F7F6F1; border-radius:10px; margin-bottom:8px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width:28px; font-size:14px; font-weight:800; color:#EC6D2E; vertical-align:top;">1.</td>
                                                <td style="font-size:14px; line-height:1.5; color:#121924;"><strong>Complete onboarding</strong> — set up your menu, tables, and branding</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr><td style="height:6px;"></td></tr>
                                <tr>
                                    <td style="padding:10px 14px; background-color:#F7F6F1; border-radius:10px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width:28px; font-size:14px; font-weight:800; color:#EC6D2E; vertical-align:top;">2.</td>
                                                <td style="font-size:14px; line-height:1.5; color:#121924;"><strong>Add your products</strong> — build your menu with categories and modifiers</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr><td style="height:6px;"></td></tr>
                                <tr>
                                    <td style="padding:10px 14px; background-color:#F7F6F1; border-radius:10px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width:28px; font-size:14px; font-weight:800; color:#EC6D2E; vertical-align:top;">3.</td>
                                                <td style="font-size:14px; line-height:1.5; color:#121924;"><strong>Take your first order</strong> — open the POS and start serving</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- CTA Button --}}
                    <tr>
                        <td style="padding:24px 32px 0 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $onboardingUrl }}" style="display:inline-block; padding:14px 32px; background-color:#121924; color:#FFFFFE; text-decoration:none; border-radius:8px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.18em;">Start Onboarding</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Support --}}
                    <tr>
                        <td style="padding:24px 32px 0 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:14px 16px; border:1px solid #C3C7CB; border-radius:10px;">
                                        <p style="margin:0 0 4px 0; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.18em; color:#4F5661;">Need Help?</p>
                                        <p style="margin:0; font-size:14px; line-height:1.5; color:#121924;">
                                            Our team is here for you. Reach out anytime on
                                            <a href="{{ $whatsappUrl }}" style="color:#218E6F; text-decoration:underline; font-weight:600;">WhatsApp</a>
                                            and we will help you get set up.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:24px 32px 32px 32px; border-top:1px solid #E4E3DC; margin-top:24px;">
                            <p style="margin:24px 0 0 0; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.16em; color:#4F5661;">
                                Bite POS &mdash; {{ date('Y') }} Bite Systems
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
