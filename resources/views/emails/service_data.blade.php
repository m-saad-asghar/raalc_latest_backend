<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Data</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f8f8;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .logo {
            margin-bottom: 20px;
        }
        .content {
            font-size: 16px;
            line-height: 1.6;
        }
        .content h3 {
            margin-top: 0;
        }
        .data {
            margin-top: 20px;
            padding: 10px;
            background-color: #f4f4f4;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="https://your-logo-url.com/logo.png" alt="Logo" width="120" />
        </div>
        <h3>New Service Data Submission</h3>
        <div class="content">
            <p><strong>Name:</strong> {{ $formData['name'] }}</p>
            <p><strong>Email:</strong> {{ $formData['email'] }}</p>
            <p><strong>Phone:</strong> {{ $formData['phone'] }}</p>
            <p><strong>Comments:</strong> {{ $formData['comments'] }}</p>
            <p><strong>Total Amount:</strong> {{ $formData['amount'] }}</p>

            <h4>Services:</h4>
            <div class="data">
                @foreach ($formData['services'] as $service)
                    <p><strong>Service:</strong> {{ $service['service'] }} - <strong>Price:</strong> {{ $service['price'] }}</p>
                @endforeach
            </div>
        </div>
    </div>
</body>
</html>