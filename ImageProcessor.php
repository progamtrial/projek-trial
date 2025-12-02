<?php
class ImageProcessor {
    private $targetWidth = 1920;
    private $targetHeight = 1080;
    private $quality = 90;

    public function __construct($width = 1920, $height = 1080, $quality = 90) {
        $this->targetWidth = $width;
        $this->targetHeight = $height;
        $this->quality = $quality;
    }

    /**
     * Process image - resize dan crop ke 1920x1080
     */
    public function processImage($sourcePath, $destPath) {
        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) return false;

        list($sourceWidth, $sourceHeight, $imageType) = $imageInfo;

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = @imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = @imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = @imagecreatefromgif($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $sourceImage = @imagecreatefromwebp($sourcePath);
                } else {
                    return false;
                }
                break;
            default:
                return false;
        }

        if (!$sourceImage) return false;

        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = $this->targetWidth / $this->targetHeight;

if ($targetRatio < $sourceRatio) { // Gambar lebih lebar (landscape)
            $newHeight = $this->targetHeight;
            $newWidth = intval($sourceWidth * ($newHeight / $sourceHeight));
        } else { // Gambar lebih tinggi (portrait) atau sama
            $newWidth = $this->targetWidth;
            $newHeight = intval($sourceHeight * ($newWidth / $sourceWidth));
        }

        $targetImage = imagecreatetruecolor($this->targetWidth, $this->targetHeight);

        if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            $transparent = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
            imagefill($targetImage, 0, 0, $transparent);
        }

        $tempImage = imagecreatetruecolor($newWidth, $newHeight);
        
        if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
            imagealphablending($tempImage, false);
            imagesavealpha($tempImage, true);
        }

        imagecopyresampled($tempImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);

        // Menghitung koordinat crop untuk memotong gambar dari tengah
        $cropX = intval(($newWidth - $this->targetWidth) / 2);
        $cropY = intval(($newHeight - $this->targetHeight) / 2);

        imagecopy($targetImage, $tempImage, 0, 0, $cropX, $cropY, $this->targetWidth, $this->targetHeight);

        $result = imagejpeg($targetImage, $destPath, $this->quality);

        imagedestroy($sourceImage);
        imagedestroy($tempImage);
        imagedestroy($targetImage);

        return $result;
    }

    /**
     * Generate video thumbnail
     * Return: thumbnail path atau false
     */
    public function generateVideoThumbnail($videoPath, $thumbnailPath) {
        // Cek apakah FFmpeg tersedia
        if (!$this->isFFmpegAvailable()) {
            // Jika FFmpeg tidak ada, buat placeholder thumbnail
            return $this->createVideoPlaceholder($thumbnailPath);
        }

        // Generate thumbnail dengan FFmpeg
        $command = sprintf(
            'ffmpeg -i %s -ss 00:00:01 -vframes 1 -vf scale=%d:%d %s 2>&1',
            escapeshellarg($videoPath),
            $this->targetWidth,
            $this->targetHeight,
            escapeshellarg($thumbnailPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($thumbnailPath)) {
            return true;
        }

        // Fallback ke placeholder jika gagal
        return $this->createVideoPlaceholder($thumbnailPath);
    }

    /**
     * Cek apakah FFmpeg tersedia
     */
    private function isFFmpegAvailable() {
        exec('ffmpeg -version 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Buat placeholder thumbnail untuk video
     */
    private function createVideoPlaceholder($thumbnailPath) {
        $image = imagecreatetruecolor($this->targetWidth, $this->targetHeight);
        
        // Background gradient
        $startColor = imagecolorallocate($image, 102, 126, 234);
        $endColor = imagecolorallocate($image, 118, 75, 162);
        
        for ($i = 0; $i < $this->targetHeight; $i++) {
            $ratio = $i / $this->targetHeight;
            $r = 102 + ($ratio * (118 - 102));
            $g = 126 + ($ratio * (75 - 126));
            $b = 234 + ($ratio * (162 - 234));
            $color = imagecolorallocate($image, (int)$r, (int)$g, (int)$b);
            imageline($image, 0, $i, $this->targetWidth, $i, $color);
        }
        
        // Add play icon
        $white = imagecolorallocate($image, 255, 255, 255);
        
        // Draw circle
        $centerX = $this->targetWidth / 2;
        $centerY = $this->targetHeight / 2;
        $radius = 100;
        imageellipse($image, $centerX, $centerY, $radius * 2, $radius * 2, $white);
        imageellipse($image, $centerX, $centerY, ($radius * 2) - 4, ($radius * 2) - 4, $white);
        
        // Draw play triangle
        $triangle = [
            $centerX - 30, $centerY - 40,
            $centerX - 30, $centerY + 40,
            $centerX + 40, $centerY
        ];
        imagefilledpolygon($image, $triangle, $white);
        
        // Add text "VIDEO"
        $fontSize = 5;
        $text = "VIDEO";
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $textX = ($this->targetWidth - $textWidth) / 2;
        $textY = $centerY + 120;
        imagestring($image, $fontSize, (int)$textX, $textY, $text, $white);
        
        $result = imagejpeg($image, $thumbnailPath, $this->quality);
        imagedestroy($image);
        
        return $result;
    }

    /**
     * Format file size
     */
    public function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Validate video file
     */
    public function isValidVideo($filePath) {
        $validTypes = ['video/mp4', 'video/webm', 'video/ogg'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        return in_array($mimeType, $validTypes);
    }

    /**
     * Get video duration (requires FFmpeg)
     */
    public function getVideoDuration($videoPath) {
        if (!$this->isFFmpegAvailable()) {
            return 0;
        }

        $command = sprintf(
            'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
            escapeshellarg($videoPath)
        );

        exec($command, $output);
        
        return isset($output[0]) ? (int)floatval($output[0]) : 0;
    }
}
?>