<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your OTP Code</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="color: #D4AF37; font-family: Arial, sans-serif; padding: 0; margin: 0;">

    <div class="otp-container" style="display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 0 50px; box-sizing: border-box;">
        <div class="otp-box" style="text-align: center; background-color: #FFFFFF; padding: 60px; border-radius: 10px; box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.5); position: relative; width: 100%; max-width: 600px; border: 2px solid #000;">
            <h3 style="font-size: 2.5rem; margin-bottom: 20px;"><strong>OTP VERIFICATION</strong></h3>
            <p style="font-size: 1.2rem;">Your OTP is <strong>{{ $otp }}</strong></p>
            <p style="font-size: 1.2rem;">If you did not request this change, kindly get in touch with our Customer Center.</p>

            <div style="text-align: left; padding-top: 50px;">
                <div>Thank You,</div>
                <div>RAALC Law Firm Team</div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
