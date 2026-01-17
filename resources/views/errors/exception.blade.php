<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
        }
        .container {
            text-align: center;
            padding: 40px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 500px;
        }
        .status-code {
            font-size: 72px;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 16px;
        }
        .title {
            font-size: 24px;
            margin-bottom: 16px;
        }
        .message {
            color: #666;
            margin-bottom: 24px;
        }
        .back-link {
            display: inline-block;
            padding: 12px 24px;
            background-color: #3498db;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        .back-link:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        @php
            $statusCode = $exception instanceof \Application\Http\Exceptions\HttpException
                ? $exception->getHttpStatus()
                : 500;
            $title = $exception instanceof \Application\Http\Exceptions\HttpException
                ? ($exception->getTitle() ?? 'Error')
                : 'Internal Server Error';
        @endphp
        <div class="status-code">{{ $statusCode }}</div>
        <h1 class="title">{{ $title }}</h1>
        <p class="message">
            @if($statusCode >= 500)
                A server error has occurred. Please try again later.
            @else
                {{ $exception->getMessage() }}
            @endif
        </p>
        <a href="/" class="back-link">Back to Home</a>
    </div>
</body>
</html>
