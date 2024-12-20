<?php
include 'encrypt.php';

class ImageSteganography
{
    public static function embedMessage($sourceImagePath, $outputImagePath, $encryptedMessage)
    {
        if (!file_exists($sourceImagePath)) {
            throw new Exception("Source image does not exist.");
        }
        $imageInfo = getimagesize($sourceImagePath);
        if (!$imageInfo) {
            throw new Exception("Invalid image file.");
        }
        $mimeType = $imageInfo['mime'];

        $image = ($mimeType === 'image/png') ? imagecreatefrompng($sourceImagePath) : imagecreatefromjpeg($sourceImagePath);
        if (!$image) {
            throw new Exception("Failed to create image resource.");
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $maxMessageLength = floor(($width * $height * 3) / 8);

        $messageBinary = str_pad(decbin(strlen($encryptedMessage)), 32, '0', STR_PAD_LEFT);
        foreach (str_split($encryptedMessage) as $char) {
            $messageBinary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        if (strlen($messageBinary) > $maxMessageLength) {
            throw new Exception("Message is too large for this image.");
        }

        $bitIndex = 0;
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($bitIndex >= strlen($messageBinary))
                    break 2;

                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $bitValue = $messageBinary[$bitIndex] === '1' ? 1 : 0;
                $b = ($b & 0xFE) | $bitValue;
                $bitIndex++;

                $newColor = imagecolorallocate($image, $r, $g, $b);
                imagesetpixel($image, $x, $y, $newColor);
            }
        }

        $outputDir = dirname($outputImagePath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        ($mimeType === 'image/png') ? imagepng($image, $outputImagePath) : imagejpeg($image, $outputImagePath, 100);
        imagedestroy($image);
        return true;
    }

    public static function extractMessage($watermarkedImagePath)
    {
        if (!file_exists($watermarkedImagePath)) {
            throw new Exception("Watermarked image does not exist.");
        }

        $imageInfo = getimagesize($watermarkedImagePath);
        if (!$imageInfo) {
            throw new Exception("Invalid image file.");
        }
        $mimeType = $imageInfo['mime'];

        $image = ($mimeType === 'image/png') ? imagecreatefrompng($watermarkedImagePath) : imagecreatefromjpeg($watermarkedImagePath);
        if (!$image) {
            throw new Exception("Failed to open image.");
        }

        $width = imagesx($image);
        $height = imagesy($image);

        $messageBits = '';
        $extractedLength = 0;
        $lengthExtracted = false;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $b = $rgb & 0xFF;

                if (!$lengthExtracted) {
                    $messageBits .= $b & 1;
                    if (strlen($messageBits) === 32) {
                        $extractedLength = bindec($messageBits);
                        $messageBits = '';
                        $lengthExtracted = true;
                    }
                } else {
                    $messageBits .= $b & 1;
                    if (strlen($messageBits) === ($extractedLength * 8)) {
                        break 2;
                    }
                }
            }
        }

        $extractedMessage = '';
        for ($i = 0; $i < strlen($messageBits); $i += 8) {
            $byte = substr($messageBits, $i, 8);
            if (strlen($byte) === 8) {
                $extractedMessage .= chr(bindec($byte));
            }
        }

        imagedestroy($image);
        return $extractedMessage;
    }
}

class ImageWatermark
{
    public static function addWatermark($sourceImagePath, $outputImagePath, $watermarkText)
    {
        if (!file_exists($sourceImagePath)) {
            throw new Exception("Source image does not exist.");
        }

        $imageInfo = getimagesize($sourceImagePath);
        if (!$imageInfo) {
            throw new Exception("Invalid image file.");
        }
        $mimeType = $imageInfo['mime'];
        $image = ($mimeType === 'image/png') ? imagecreatefrompng($sourceImagePath) : imagecreatefromjpeg($sourceImagePath);
        if (!$image) {
            throw new Exception("Failed to create image resource.");
        }

        $width = imagesx($image);
        $height = imagesy($image);


        // Validate font file exist & correct format (absolute path)
        $fontFile = realpath(__DIR__ . '/fonts/arial.ttf');
        if (!$fontFile || !file_exists($fontFile)) {
            throw new Exception("Failed to load font file,  file must be accessible , readable , and file path must be absolute : " . __DIR__ . "/fonts/arial.ttf, current  path : $fontFile"); //check all possibilities about problem fonts
        }

        $fontSize = $width / 30;
        $textColor = imagecolorallocatealpha($image, 255, 255, 255, 50);
        $angle = 0;

        $textBoundingBox = @imagettfbbox($fontSize, $angle, $fontFile, $watermarkText);

        if ($textBoundingBox === false) {
            throw new Exception("Could not process font file '$fontFile'. Check if it's valid true type format (.ttf) & ensure server permission  or make it readable by  copy-pasting in " . __DIR__ . "/fonts with message: " . (error_get_last()['message'] ?? 'unknown error'));
        }

        $textWidth = $textBoundingBox[2] - $textBoundingBox[0];
        $textHeight = $textBoundingBox[1] - $textBoundingBox[7];
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;


        imagettftext($image, $fontSize, $angle, $x, $y, $textColor, $fontFile, $watermarkText);

        $outputDir = dirname($outputImagePath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }


        ($mimeType === 'image/png') ? imagepng($image, $outputImagePath) : imagejpeg($image, $outputImagePath, 100);
        imagedestroy($image);

        return true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'];
        $key = $_POST['key'] ?? null;
        $uploadDir = ($action === 'embed') ? 'images/original/' : (($action === 'extract') ? 'images/watermarked/' : 'images/watermark/');

        $uploadedFile = $uploadDir . basename($_FILES['image']['name']);

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }


        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadedFile)) {


            if ($action === 'embed') {

                $message = $_POST['message'];
                $encryptedMessage = SecureEncryption::encrypt($message, $key);
                $watermarkedFile = 'images/watermarked/' . basename($uploadedFile);

                ImageSteganography::embedMessage($uploadedFile, $watermarkedFile, $encryptedMessage);

                echo "
                      <div style='text-align: center; margin-top: 50px; font-family: Arial, sans-serif;'>
                          <div style='display: inline-block; background-color: #eaf8e6; color: #4caf50; padding: 20px; border-radius: 10px;'>
                                <h2 style='margin: 0;'>✅ Embed Successful!</h2>
                              <p>Your message has been successfully embedded into the image.</p>
                             <a href='$watermarkedFile' download='watermarked-image.png'
                           style='display: inline-block; margin-top: 10px; padding: 10px 20px; background-color: #4caf50; color: white; text-decoration: none; font-weight: bold; border-radius: 5px;'>
                                ⬇ Download Watermarked Image
                            </a>
                              </div>
                   </div>
                   ";


            } elseif ($action === 'extract') {

                $extractedMessage = ImageSteganography::extractMessage($uploadedFile);
                $decryptedMessage = SecureEncryption::decrypt($extractedMessage, $key);
                echo "
                              <div style='text-align: center; margin-top: 50px; font-family: Arial, sans-serif;'>
                              <div style='display: inline-block; background-color: #eaf8e6; color: #4caf50; padding: 20px; border-radius: 10px;'>
                              <h2 style='margin: 0;'>✅ Extraction Successful!</h2>
                              <p>Extracted Message:</p>
                         <p style='font-weight: bold; background-color: #f1f1f1; padding: 10px; border-radius: 5px; color: #333;'>"
                    . htmlspecialchars($decryptedMessage) . "
                              </p>
                            </div>
                      </div>
                          ";

            } elseif ($action === 'watermark') {

                $watermarkText = $_POST['watermark-text'];
                $watermarkedFile = 'images/watermark/' . basename($uploadedFile);

                ImageWatermark::addWatermark($uploadedFile, $watermarkedFile, $watermarkText);

                echo "
                       <div style='text-align: center; margin-top: 50px; font-family: Arial, sans-serif;'>
                              <div style='display: inline-block; background-color: #eaf8e6; color: #4caf50; padding: 20px; border-radius: 10px;'>
                            <h2 style='margin: 0;'>✅ Watermark Added Successfully</h2>
                           <p>Your image has been successfully watermarked</p>
                        <a href='$watermarkedFile' download='watermarked-image.png' style='display: inline-block; margin-top: 10px; padding: 10px 20px; background-color: #4caf50; color: white; text-decoration: none; font-weight: bold; border-radius: 5px;'>
                                    ⬇ Download Watermarked Image
                            </a>
                           </div>
                     </div>
                    ";
            }

        } else {
            throw new Exception("File upload failed.");
        }
    } catch (Exception $e) {
        echo "
    <div style='text-align: center; margin-top: 50px; font-family: Arial, sans-serif;'>
         <div style='display: inline-block; background-color: #ffe6e6; color: #f44336; padding: 20px; border-radius: 10px;'>
                <h2 style='margin: 0;'>❌ Error!</h2>
                  <p>" . $e->getMessage() . "</p>
           </div>
   </div>
      ";
    }
}
?>