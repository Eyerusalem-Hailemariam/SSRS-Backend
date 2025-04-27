<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Staff Account Created</title>
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
    .footer {
      font-size: 12px;
      color: #888;
      margin-top: 40px;
      text-align: center;
    }
    h3 {
      margin-top: 0;
      color: #2e2e2e;
    }
    p strong {
      color: #2d89ef;
    }
  </style>
</head>
<body>
  <div class="email-container">
    <h3>Hello {{ $name }},</h3>
    <p>Your staff account has been created successfully.</p>
    <p><strong>Email:</strong> {{ $email }}</p>
    <p><strong>Temporary Password:</strong> {{ $tempPassword }}</p>
    <p>Please log in and change your password as soon as possible.</p>
    <br>
    <p>Thank you,<br>The Self Serve Restaurant System Team</p>

    <div class="footer">
      © {{ date('Y') }}. All rights reserved.
    </div>
  </div>
</body>
</html>
