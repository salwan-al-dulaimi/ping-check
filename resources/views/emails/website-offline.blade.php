<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Website Offline Notification</title>
</head>
<body>
    <p>Hello,</p>

    <p>Your website monitoring system detected a website outage:</p>

    <ul>
        <li><strong>Name:</strong> {{ $website->name }}</li>
        <li><strong>URL:</strong> <a href="{{ $website->url }}">{{ $website->url }}</a></li>
        <li><strong>Status code:</strong> {{ $statusCode ?? 'No response' }}</li>
        <li><strong>Detected at:</strong> {{ now()->toDateTimeString() }}</li>
    </ul>

    @if ($errorMessage)
        <p><strong>Error:</strong> {{ $errorMessage }}</p>
    @endif

    <p>Please check the service and restore the website if possible.</p>
</body>
</html>
