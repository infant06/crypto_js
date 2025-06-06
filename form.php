<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Form - CryptoJS Encryption</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container">
        <h1>Secure Data Entry Form</h1>
        <div class="form-container">
            <form id="secureForm">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>

                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label for="sensitive_data">Sensitive Data:</label>
                    <input type="text" id="sensitive_data" name="sensitive_data" placeholder="Credit card, SSN, etc.">
                </div>

                <button type="submit" class="btn-submit">Submit Secure Data</button>
            </form>

            <div id="responseMessage" class="response-message"></div>
        </div>

        <div class="navigation">
            <a href="index.php" class="btn-nav">View Submitted Data</a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <script src="js/crypto.js"></script>
    <script src="js/form-handler.js"></script>
</body>

</html>