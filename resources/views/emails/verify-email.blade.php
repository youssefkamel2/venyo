<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Verification Code - Venyo</title>
</head>

<body
    style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f7;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
        style="background-color: #f4f4f7; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0"
                    style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.08);">

                    <!-- Header -->
                    <tr>
                        <td
                            style="background: linear-gradient(135deg, #5f2265 0%, #8b3a94 100%); padding: 40px; text-align: center;">
                            <h1
                                style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700; letter-spacing: -0.5px;">
                                venyo</h1>
                            <p style="margin: 10px 0 0; color: rgba(255,255,255,0.8); font-size: 14px;">Restaurant
                                Reservations</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 50px 40px;">
                            <h2
                                style="margin: 0 0 20px; color: #29272e; font-size: 24px; font-weight: 600; text-align: center;">
                                Verify Your Email Address</h2>

                            <p
                                style="margin: 0 0 25px; color: #616f7d; font-size: 16px; line-height: 1.7; text-align: center;">
                                Hi {{ $user->name }},
                            </p>

                            <p
                                style="margin: 0 0 30px; color: #616f7d; font-size: 16px; line-height: 1.7; text-align: center;">
                                Use the verification code below to confirm your email address.
                            </p>

                            <!-- Code Display -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0 30px;">
                                        <div
                                            style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 16px; padding: 25px 40px; display: inline-block;">
                                            <span
                                                style="font-size: 42px; font-weight: 700; letter-spacing: 12px; color: #5f2265; font-family: 'Courier New', monospace;">{{ $code }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <p
                                style="margin: 0 0 15px; color: #616f7d; font-size: 14px; line-height: 1.7; text-align: center;">
                                This code will expire in <strong>15 minutes</strong>.
                            </p>

                            <p
                                style="margin: 0; color: #9ca3af; font-size: 13px; line-height: 1.6; text-align: center;">
                                If you didn't create an account with Venyo, please ignore this email.
                            </p>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 40px;">
                            <hr style="border: none; border-top: 1px solid #eee; margin: 0;">
                        </td>
                    </tr>

                    <!-- Brand Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 25px 40px; text-align: center;">
                            <p style="margin: 0; color: #9ca3af; font-size: 12px;">
                                &copy; {{ date('Y') }} Venyo. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>