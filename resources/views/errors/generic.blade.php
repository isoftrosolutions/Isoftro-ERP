<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - iSoftro ERP</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8f9fa;
            color: #212529;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .error-container {
            text-align: center;
            max-width: 500px;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            color: #dc3545;
            margin: 0;
        }
        .error-message {
            font-size: 1.2rem;
            margin: 1rem 0;
            color: #6c757d;
        }
        .error-details {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">Oops!</h1>
        <p class="error-message">{{ $message ?? 'Something went wrong. Please try again.' }}</p>
        <p class="error-details">If this problem persists, please contact our support team.</p>
        <a href="/" style="color: #007bff; text-decoration: none;">← Back to Home</a>
    </div>
</body>
</html>