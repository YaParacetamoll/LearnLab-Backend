<?php
require_once "initialize_head.php";
require_once "initialize_db.php";
require_once "initialize_aws.php";

function jsonResponse($response_code = 200, $message = "")
{
    http_response_code($response_code);
    return json_encode([
        "status" => http_response_code(),
        "message" => $message,
    ]);
}

function convertToWebP($file, $compression_quality = 80, $resizeMode = 'None')
{
    // check if file exists
    if (!file_exists($file)) {
        return false;
    }
    $file_type = exif_imagetype($file);


    if (function_exists('imagewebp')) {
        switch ($file_type) {
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($file);
                break;
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($file);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($file);
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;
            case IMAGETYPE_BMP:
                $image = imagecreatefrombmp($file);
                break;
            case IMAGETYPE_WBMP:
                $image = imagecreatefromwbmp($file);
                break;
            case IMAGETYPE_XBM:
                $image = imagecreatefromxbm($file);
                break;
            case IMAGETYPE_WEBP:  // Handle WebP input correctly
                $image = imagecreatefromwebp($file);
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;
            case IMAGETYPE_AVIF:  // Support for AVIF if available
                if (function_exists('imagecreatefromavif')) {
                    $image = imagecreatefromavif($file);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                    break;
                } else {
                    return false; // Or handle the missing function gracefully
                }
            default:
                return false;
        }

        $minWidth = 256; // For Profile mode
        $minHeight = 256; // For Profile mode
        $bannerWidth = 1920; // For Banner mode


        if ($resizeMode === 'None') {
            $resizedImage = $image;
        } else {
            $originalWidth = imagesx($image);
            $originalHeight = imagesy($image);

            if ($resizeMode === 'Profile') {
                // Calculate new dimensions, ensuring at least one dimension is the minimum
                $ratio = max($minWidth / $originalWidth, $minHeight / $originalHeight);  // Correct ratio calculation
                $newWidth = (int)round($originalWidth * $ratio); // Correct rounding for better results
                $newHeight = (int)round($originalHeight * $ratio);
            } else if ($resizeMode === 'Banner') {

                $newWidth = $bannerWidth;
                $newHeight = (int)round($originalHeight * ($newWidth / $originalWidth));

                if ($newHeight < $minHeight) {  // If smaller than minHeight
                    $newHeight = $minHeight;
                    $newWidth = (int)round($originalWidth * ($newHeight / $originalHeight)); // Adjust newWidth
                }
            } else {  // Handle invalid resize modes or defaults if needed
                $newWidth = $originalWidth;
                $newHeight = $originalHeight;
            }

            // Only resize if dimensions have changed (to prevent unnecessary processing):
            if ($newWidth !== $originalWidth || $newHeight !== $originalHeight) {
                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

                // Transparency for PNG, WebP, AVIF:
                if ($file_type === IMAGETYPE_PNG || $file_type === IMAGETYPE_WEBP || $file_type == IMAGETYPE_AVIF) {
                    imagealphablending($resizedImage, false);
                    imagesavealpha($resizedImage, true);
                }

                imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
            } else {
                $resizedImage = $image; // No resizing needed, use original image
            }
        }


        // Start output buffering
        ob_start();
        $result = imagewebp($resizedImage, null, $compression_quality);
        $webpData = ob_get_clean(); // Get the output and close the buffer


        if (false === $result) {
            imagedestroy($image);
            return false;
        }

        // Free up memory
        imagedestroy($resizedImage);
        imagedestroy($image);

        // Return the WebP image data
        return $webpData;
    }

    return false;
}

require_once "initialize_jwt.php";
