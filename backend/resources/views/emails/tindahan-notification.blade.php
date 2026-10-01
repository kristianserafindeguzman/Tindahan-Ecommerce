<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Tindahan</title>
</head>

<body style="
    margin: 0;
    padding: 0;
    background-color: #f5f6f8;
    font-family: Arial, Helvetica, sans-serif;
    color: #222222;
">

<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background-color: #f5f6f8; padding: 35px 15px;">

    <tr>
        <td align="center">

            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                   style="
                       max-width: 600px;
                       background-color: #ffffff;
                       border-radius: 14px;
                       overflow: hidden;
                   ">

                {{-- Header --}}
                <tr>
                    <td align="center"
                        style="
                            padding: 28px 25px;
                            background-color: #ffffff;
                            border-bottom: 1px solid #eeeeee;
                        ">

                        <img
                            src="{{ config('services.tindahan.logo_url') }}"
                            alt="Tindahan"
                            width="150"
                            style="
                                display: block;
                                max-width: 150px;
                                height: auto;
                                border: 0;
                            "
                        >

                    </td>
                </tr>

                {{-- Main content --}}
                <tr>
                    <td style="padding: 35px 35px 30px;">

                        <h1 style="
                            margin: 0 0 20px;
                            font-size: 26px;
                            line-height: 1.3;
                            color: #222222;
                        ">
                            {{ $title }}
                        </h1>

                        <p style="
                            margin: 0 0 20px;
                            font-size: 16px;
                            line-height: 1.6;
                            color: #555555;
                        ">
                            Hello <strong>{{ $name }}</strong>!
                        </p>

                        <p style="
                            margin: 0 0 25px;
                            font-size: 16px;
                            line-height: 1.6;
                            color: #555555;
                        ">
                            {{ $body }}
                        </p>

                        {{-- Order information --}}
                        @if ($orderId)
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                   style="
                                       background-color: #f8f9fb;
                                       border-radius: 10px;
                                       margin-bottom: 28px;
                                   ">

                                <tr>
                                    <td style="padding: 20px;">

                                        <p style="
                                            margin: 0;
                                            font-size: 13px;
                                            color: #888888;
                                            text-transform: uppercase;
                                            letter-spacing: 0.5px;
                                        ">
                                            Order Number
                                        </p>

                                        <p style="
                                            margin: 6px 0 0;
                                            font-size: 21px;
                                            font-weight: bold;
                                            color: #222222;
                                        ">
                                            #{{ $orderId }}
                                        </p>

                                    </td>
                                </tr>

                            </table>
                        @endif

                        {{-- Action button --}}
                        @if ($actionUrl)
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center">

                                        <a href="{{ $actionUrl }}"
                                           style="
                                               display: inline-block;
                                               padding: 14px 28px;
                                               background-color: #e63946;
                                               color: #ffffff;
                                               text-decoration: none;
                                               font-size: 16px;
                                               font-weight: bold;
                                               border-radius: 8px;
                                           ">
                                            {{ $actionText }}
                                        </a>

                                    </td>
                                </tr>
                            </table>
                        @endif

                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td align="center"
                        style="
                            padding: 25px;
                            background-color: #f8f9fb;
                            border-top: 1px solid #eeeeee;
                        ">

                        <p style="
                            margin: 0 0 8px;
                            font-size: 13px;
                            color: #888888;
                        ">
                            This is an automated notification from Tindahan.
                        </p>

                        <p style="
                            margin: 0;
                            font-size: 13px;
                            color: #aaaaaa;
                        ">
                            © {{ date('Y') }} Tindahan. All rights reserved.
                        </p>

                    </td>
                </tr>

            </table>

        </td>
    </tr>

</table>

</body>
</html>
