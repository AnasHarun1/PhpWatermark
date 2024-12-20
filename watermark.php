<?php

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
        $image = imagecreatefromjpeg($sourceImagePath);  // change to JPEG for GD library more stable
        if (!$image) {
            throw new Exception("Failed to create image resource.");
        }

        $width = imagesx($image);
        $height = imagesy($image);


        // Validate and Load font File
        $fontFile = realpath(__DIR__ . '/fonts/arial.ttf');
        if (!$fontFile || !file_exists($fontFile)) {
            throw new Exception("Failed to load font file,  file must be accessible , readable , and file path must be absolute : " . __DIR__ . "/fonts/arial.ttf, current  path : $fontFile");
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

        imagejpeg($image, $outputImagePath, 100);
        imagedestroy($image);

        return true;

    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {

        $uploadDir = 'images/watermark/';
        $uploadedFile = $uploadDir . basename($_FILES['image']['name']);

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }


        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadedFile)) {

            $watermarkText = $_POST['watermark-text'];
            $watermarkedFile = 'images/watermark/' . basename($uploadedFile);
            ImageWatermark::addWatermark($uploadedFile, $watermarkedFile, $watermarkText);
            $host = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
            $host .= $_SERVER['SERVER_NAME'];
            $downloadUrl = $host . dirname($_SERVER['PHP_SELF']) . "/" . $watermarkedFile;

            echo "
               <div style='text-align: center; margin-top: 50px; font-family: Arial, sans-serif;'>
              <div style='display: inline-block; background-color: #eaf8e6; color: #4caf50; padding: 20px; border-radius: 10px;'>
                      <h2 style='margin: 0;'>✅ Watermark Added Successfully</h2>
                 <p>Your image has been successfully watermarked</p>
                <a href='$downloadUrl' download='watermarked-image.png' style='display: inline-block; margin-top: 10px; padding: 10px 20px; background-color: #4caf50; color: white; text-decoration: none; font-weight: bold; border-radius: 5px;'>
                         ⬇ Download Watermarked Image
                  </a>
              </div>
              </div>
               ";

        } else {
            throw new Exception("File upload failed");
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