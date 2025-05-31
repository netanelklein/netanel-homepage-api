<?php

namespace App\Services;

/**
 * Extension Detection and Fallback Service
 * Handles optional PHP extensions gracefully
 */
class ExtensionService 
{
    private static $extensionStatus = [];

    /**
     * Check if an extension is available
     */
    public static function isAvailable(string $extension): bool 
    {
        if (!isset(self::$extensionStatus[$extension])) {
            self::$extensionStatus[$extension] = extension_loaded($extension);
        }
        
        return self::$extensionStatus[$extension];
    }

    /**
     * Get available image processing libraries
     */
    public static function getImageProcessor(): ?string 
    {
        if (self::isAvailable('imagick')) {
            return 'imagick';
        } elseif (self::isAvailable('gd')) {
            return 'gd';
        }
        
        return null;
    }

    /**
     * Get available caching backend
     */
    public static function getCacheBackend(): string 
    {
        if (self::isAvailable('redis')) {
            return 'redis';
        } elseif (self::isAvailable('apcu')) {
            return 'apcu';
        }
        
        return 'file';
    }

    /**
     * Check if internationalization is available
     */
    public static function hasIntlSupport(): bool 
    {
        return self::isAvailable('intl');
    }

    /**
     * Get system capabilities report
     */
    public static function getCapabilities(): array 
    {
        return [
            'core_extensions' => [
                'gd' => self::isAvailable('gd'),
                'curl' => self::isAvailable('curl'),
                'xml' => self::isAvailable('xml'),
                'zip' => self::isAvailable('zip'),
                'openssl' => self::isAvailable('openssl'),
            ],
            'optional_extensions' => [
                'imagick' => self::isAvailable('imagick'),
                'redis' => self::isAvailable('redis'),
                'apcu' => self::isAvailable('apcu'),
                'intl' => self::isAvailable('intl'),
            ],
            'capabilities' => [
                'image_processing' => self::getImageProcessor(),
                'caching_backend' => self::getCacheBackend(),
                'internationalization' => self::hasIntlSupport(),
            ],
            'memory_info' => [
                'memory_limit' => ini_get('memory_limit'),
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
            ]
        ];
    }

    /**
     * Image processing with fallback
     */
    public static function processImage(string $inputPath, string $outputPath, array $options = []): bool 
    {
        $processor = self::getImageProcessor();
        
        switch ($processor) {
            case 'imagick':
                return self::processWithImageMagick($inputPath, $outputPath, $options);
            case 'gd':
                return self::processWithGD($inputPath, $outputPath, $options);
            default:
                return false;
        }
    }

    /**
     * ImageMagick processing
     */
    private static function processWithImageMagick(string $inputPath, string $outputPath, array $options): bool 
    {
        try {
            $imagick = new \Imagick($inputPath);
            
            if (isset($options['resize'])) {
                $imagick->resizeImage($options['resize'][0], $options['resize'][1], \Imagick::FILTER_LANCZOS, 1);
            }
            
            if (isset($options['quality'])) {
                $imagick->setImageCompressionQuality($options['quality']);
            }
            
            return $imagick->writeImage($outputPath);
        } catch (\Exception $e) {
            error_log("ImageMagick error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * GD processing (fallback)
     */
    private static function processWithGD(string $inputPath, string $outputPath, array $options): bool 
    {
        try {
            $imageInfo = getimagesize($inputPath);
            if (!$imageInfo) return false;
            
            $mime = $imageInfo['mime'];
            
            switch ($mime) {
                case 'image/jpeg':
                    $source = imagecreatefromjpeg($inputPath);
                    break;
                case 'image/png':
                    $source = imagecreatefrompng($inputPath);
                    break;
                default:
                    return false;
            }
            
            if (isset($options['resize'])) {
                $resized = imagecreatetruecolor($options['resize'][0], $options['resize'][1]);
                imagecopyresampled($resized, $source, 0, 0, 0, 0, 
                    $options['resize'][0], $options['resize'][1], 
                    imagesx($source), imagesy($source));
                $source = $resized;
            }
            
            $quality = $options['quality'] ?? 85;
            
            switch ($mime) {
                case 'image/jpeg':
                    return imagejpeg($source, $outputPath, $quality);
                case 'image/png':
                    return imagepng($source, $outputPath, min(9, max(0, (100 - $quality) / 10)));
            }
            
            return false;
        } catch (\Exception $e) {
            error_log("GD error: " . $e->getMessage());
            return false;
        }
    }
}
