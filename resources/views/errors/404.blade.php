<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Page Not Allowed</title>
        <style>
            body {
                font-family: Arial, Helvetica, sans-serif;
                background: #f7f7f7;
                color: #222;
                margin: 0;
            }
            .container {
                max-width: 720px;
                margin: 10vh auto;
                padding: 32px;
                background: #fff;
                border: 1px solid #e6e6e6;
                border-radius: 8px;
                text-align: center;
            }
            h1 {
                font-size: 28px;
                margin-bottom: 8px;
            }
            p {
                margin: 8px 0 16px;
                color: #555;
            }
            a.button {
                display: inline-block;
                padding: 10px 18px;
                border-radius: 6px;
                background: #2563eb;
                color: #fff;
                text-decoration: none;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>{{ $message ?? 'You are not allowed to access this page.' }}</h1>
            <p>Please use the correct portal for this domain.</p>
            @if (!empty($suggestedUrl))
                <a class="button" href="{{ $suggestedUrl }}">Go to the correct page</a>
            @endif
        </div>
    </body>
</html>
