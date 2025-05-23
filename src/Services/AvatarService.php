<?php

namespace Souravmsh\LaravelWidget\Services;
use Exception;

class AvatarService
{

    /**
    * Create a random avatar image and return it as a base64 string.
    *
    * @param string $title The title or name to display on the avatar.
    * @param int $width The width of the avatar image.
    * @param int $height The height of the avatar image.
    * @return string Base64 encoded image data.
    * @sample of usage:
    * -------------------------------------------------------------
    *  <x-laravel-widget::avatar
    *      src="http://0.0.0.0:8804/assets/images/icon/photo.png"
    *      alt="Font 2"
    *  />
    * 
    *  or
    *
    *  <x-laravel-widget::avatar
    *     src="http://0.0.0.0:8804/assets/images/icon/logo.png"
    *     alt="Font Hunter"
    *     width="100"
    *     height="100"
    *     class="text-class"
    *     id="myImage"
    *     data-tg-title="Simple avatar"
    *  />
    */

    public static function create($title = "Avatar", $width = 48, $height = 48)
    {
        try {
            $title = self::getText($title);

            // Create image
            $image = imagecreate($width, $height);

            // Generate random background color
            $bgColor = self::_randomColor($image);

            // Calculate relative luminance to determine if text should be light or dark
            $luminance = self::_calculateLuminance($bgColor);

            // Choose the correct text color based on luminance
            $textColor = ($luminance > 0.5)
                ? imagecolorallocate($image, 0, 0, 0) // dark text
                : imagecolorallocate($image, 255, 255, 255); // light text

            // Set the background color
            imagefill($image, 0, 0, $bgColor['color']);

            // Calculate font size based on the smaller dimension of the image
            $fontSize = self::_calculateFontSize($width, $height, strlen($title));

            // Calculate text width and height for the built-in GD font
            $textWidth = imagefontwidth($fontSize) * strlen($title);
            $textHeight = imagefontheight($fontSize);

            // Calculate centered positions
            $x = ($width - $textWidth) / 2;
            $y = ($height - $textHeight) / 2;

            // Draw the text in the centered position
            imagestring($image, $fontSize, $x, $y, $title, $textColor);

            // Capture the image as a base64 string
            ob_start(); // Start output buffering
            imagepng($image); // Output image in PNG format to buffer
            $imageData = ob_get_contents(); // Get the image data from buffer
            ob_end_clean(); // Clean the buffer

            // Encode the image data in base64
            $base64Image = base64_encode($imageData);

            // Free up resources
            imagedestroy($image);

            return "data:image/png;base64,{$base64Image}";

        } catch (Exception $e) {
            return "";
        }
    }

    public static function imageExists($src)
    {
        if (empty($src)) {
            return false;
        }

        // Check if it's a data URL
        if (strpos($src, 'data:image') === 0) {
            return true;
        }

        // Check if URL or file exists
        if (filter_var($src, FILTER_VALIDATE_URL)) {
            $headers = @get_headers($src);
            return $headers && strpos($headers[0], '200') !== false;
        }

        return file_exists($src);
    }

    public static function getText($string)
    {
        // Check if string is empty
        if (empty($string)) {
            return 'A';
        }

        // Check if it's a single word
        if (strpos($string, ' ') === false) {
            // If a single word, extract all uppercase letters
            preg_match_all('/[A-Z]/', $string, $matches);
            $result = implode('', $matches[0]); // Join all uppercase letters
            return $result ?: strtoupper(substr($string, 0, 1)); // Fallback to first letter
        } else {
            // If multiple words, split the input and take the first uppercase letter of each word
            $words = explode(' ', $string);
            $initials = '';
    
            foreach ($words as $word) {
                if (!empty($word)) {
                    $initials .= strtoupper(substr($word, 0, 1));
                }
            }
    
            return $initials ?: 'A'; // Fallback to 'A' if no valid initials
        }
    }

    public static function getTextFromSrc($src)
    {
        // Extract filename from URL or path
        $filename = basename($src);
        // Remove extension
        $name = pathinfo($filename, PATHINFO_FILENAME);
        return self::getText($name);
    }

    private static function _randomColor($image) 
    {
        $red = mt_rand(0, 255);
        $green = mt_rand(0, 255);
        $blue = mt_rand(0, 255);

        return [
            'color' => imagecolorallocate($image, $red, $green, $blue),
            'r' => $red,
            'g' => $green,
            'b' => $blue,
        ];
    }

    private static function _calculateLuminance($color) 
    {
        $r = $color['r'] / 255;
        $g = $color['g'] / 255;
        $b = $color['b'] / 255;

        $r = ($r <= 0.03928) ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = ($g <= 0.03928) ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = ($b <= 0.03928) ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

        return $luminance;
    }

    private static function _calculateFontSize($width, $height, $textLength) 
    {
        // Simple heuristic: Use a fraction of the smaller dimension
        $smallerDim = min($width, $height);

        // Use 1/2 of the smaller dimension for font size
        $baseSize = floor($smallerDim / 2);

        // Adjust based on text length to avoid overflow
        $fontSize = max(1, $baseSize - ($textLength - 2));

        return $fontSize;
    }
}
