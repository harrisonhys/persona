<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Service</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }

        .icon {
            width: 60px;
            height: 60px;
            background: #4a5568;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .icon svg {
            width: 35px;
            height: 35px;
            fill: white;
        }

        h1 {
            color: #2d3748;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #718096;
            font-size: 1rem;
            margin-bottom: 30px;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #e6f7ed;
            color: #2f855a;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 30px;
        }

        .status::before {
            content: '';
            width: 8px;
            height: 8px;
            background: #48bb78;
            border-radius: 50%;
        }

        .info {
            background: #f7fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #718096;
            font-size: 0.9rem;
        }

        .info-value {
            color: #2d3748;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #a0aec0;
            font-size: 0.85rem;
        }

        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }

            h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/>
            </svg>
        </div>

        <h1>API Service</h1>
        <p class="subtitle">GraphQL API Endpoint</p>

        <div class="status">Service Online</div>

        <div class="info">
            <div class="info-item">
                <span class="info-label">Endpoint</span>
                <span class="info-value">/graphql</span>
            </div>
            <div class="info-item">
                <span class="info-label">Method</span>
                <span class="info-value">POST</span>
            </div>
            <div class="info-item">
                <span class="info-label">Authentication</span>
                <span class="info-value">Required</span>
            </div>
        </div>

        <div class="footer">
            <p>For authorized access only</p>
        </div>
    </div>
</body>
</html>