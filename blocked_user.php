<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Blocked</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #f4f4f4;
            padding: 50px;
        }
        .blocked-message {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #dc3545;
        }
        p {
            color: #333;
            font-size: 16px;
        }
        .actions {
            margin-top: 20px;
        }
        a {
            display: inline-block;
            margin: 10px;
            padding: 10px 20px;
            background: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }
        a:hover {
            background: #0056b3;
        }
        .close-button {
            background: #6c757d;
        }
        .close-button:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="blocked-message">
        <h1>Your Account Has Been Blocked</h1>
        <p>We're sorry, but your account has been blocked. Please contact our support team for more information or to resolve this issue.</p>
        <div class="actions">
            <a href="contact_form.php">Contact Support</a>
            <a href="index.php" class="close-button">Close</a>
        </div>
    </div>
</body>
</html>
