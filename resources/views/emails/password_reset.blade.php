<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Password Reset Code</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f4f4f7;
      color: #333;
      margin: 0;
      padding: 0;
    }
    .email-container {
      max-width: 600px;
      margin: 40px auto;
      background-color: #ffffff;
      border-radius: 8px;
      padding: 40px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    .reset-code {
      font-size: 32px;
      font-weight: bold;
      color: #2d89ef;
      margin: 20px 0;
      letter-spacing: 4px;
    }
    .footer {
      font-size: 12px;
      color: #888;
      margin-top: 40px;
      text-align: center;
    }
    h2 {
      margin-top: 0;
      color: #2e2e2e;
    }
  </style>
</head>
<body>
  <div class="email-container">
    <h2>Hello,</h2>
    <p>We received a request to reset the password for your account.</p>
    <p>Please use the code below to proceed with resetting your password:</p>

    <div class="reset-code">{{ $token }}</div>

    <p>This code will expire shortly for your security. If you did not request a password reset, please disregard this message.</p>

    <p>Thank you,<br>The Self Serve Restaurant System Team</p>

    <div class="footer">
      © {{ date('Y') }}. All rights reserved.
    </div>
  </div>
</body>
</html>
