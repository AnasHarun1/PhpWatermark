<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <title>Secure Image</title>
</head>

<body>
    <div class="container">
        <h1>Digital Watermark Embedder</h1>

        <!-- Embed Secret Message -->
        <div class="section">
            <h2>Embed Secret Message</h2>
            <form action="process.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="image">Select Image:</label>
                    <input type="file" name="image" id="image" accept="image/png,image/jpeg" required>
                    <span class="file-info"></span>
                </div>
                <div class="form-group">
                    <label for="message">Secret Message:</label>
                    <textarea name="message" id="message" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="key">Encryption Key:</label>
                    <input type="password" name="key" id="key" required>
                </div>
                <button type="submit" name="action" value="embed" class="btn">Embed Message</button>
            </form>
        </div>

        <!-- Extract Secret Message -->
        <div class="section">
            <h2>Extract Secret Message</h2>
            <form action="process.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="watermarked-image">Select Watermarked Image:</label>
                    <input type="file" name="image" id="watermarked-image" accept="image/png,image/jpeg" required>
                    <span class="file-info"></span>
                </div>
                <div class="form-group">
                    <label for="decrypt-key">Decryption Key:</label>
                    <input type="password" name="key" id="decrypt-key" required>
                </div>
                <button type="submit" name="action" value="extract" class="btn">Extract Message</button>
            </form>
        </div>

        <!-- Watermark an Image-->
        <div class="section">
            <h2>Add Text Watermark</h2>
            <form action="watermark.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="watermark-image">Select Image:</label>
                    <input type="file" name="image" id="watermark-image" accept="image/png,image/jpeg" required>
                    <span class="file-info"></span>
                </div>
                <div class="form-group">
                    <label for="watermark-text">Watermark Text:</label>
                    <input type="text" name="watermark-text" id="watermark-text" required>
                </div>
                <button type="submit" name="action" value="watermark" class="btn">Add Watermark</button>
            </form>
        </div>

    </div>
    <script src="main.js"></script>
</body>

</html>