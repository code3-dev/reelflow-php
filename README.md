# 📹 ReelFlow - Instagram Reels Downloader

A modern, elegant PHP library for downloading Instagram reels with ease.

![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Status](https://img.shields.io/badge/status-stable-brightgreen)

## ✨ Features

- 🎯 Download videos from Instagram reels
- 🖼️ Get reel thumbnails
- 🚀 Simple and intuitive API
- 🔄 Automatic fallback mechanisms
- 🛡️ Comprehensive error handling
- 🔍 Smart reel URL validation

## 📦 Installation

Install via Composer:

```bash
composer require reelflow/reelflow-php
```

## 🚀 Quick Start

### Basic Usage

```php
use Code3\ReelFlow\ReelFlow;
use Code3\ReelFlow\InstagramException;

// Create an instance
$downloader = new ReelFlow();

try {
    // Get reel information
    $video = $downloader->getVideoInfo('https://www.instagram.com/reel/xyz123/');
    
    // Access reel details
    echo $video->getVideoUrl();      // Direct video URL
    echo $video->getThumbnailUrl();  // Thumbnail URL
    echo $video->getWidth();         // Video width
    echo $video->getHeight();        // Video height
    echo $video->getUsername();      // Creator's username
    echo $video->getDescription();   // Reel description
} catch (InstagramException $e) {
    echo "Error {$e->getStatusCode()}: {$e->getMessage()}";
}
```

### Using the Facade

```php
use Code3\ReelFlow\ReelflowFacade;

// Use the static facade method
$video = ReelflowFacade::getVideoInfo('https://www.instagram.com/reel/xyz123/');
```

## 📋 API Reference

### ReelFlow Class

#### `getVideoInfo(string $url): VideoInfo`

Retrieves video information from an Instagram reel URL.

- **Parameters:**
  - `$url` (string): The Instagram reel URL
- **Returns:** VideoInfo object
- **Throws:** InstagramException

### VideoInfo Class

#### Methods:
- `getVideoUrl(): string` - Get the direct video URL
- `getThumbnailUrl(): string` - Get thumbnail image URL
- `getWidth(): int` - Get video width
- `getHeight(): int` - Get video height
- `getUsername(): string` - Get the username of the reel creator
- `getDescription(): string` - Get the reel description
- `toArray(): array` - Get all info as array

#### Example using toArray():
```php
$video = $downloader->getVideoInfo($url);
$videoData = $video->toArray();

// Access data from array
echo $videoData['videoUrl'];     // Direct video URL
echo $videoData['thumbnailUrl']; // Thumbnail URL
echo $videoData['width'];        // Video width
echo $videoData['height'];       // Video height
echo $videoData['username'];     // Creator's username
echo $videoData['description'];  // Reel description
```

### InstagramException Class

Extends PHP's built-in Exception class with additional status code information.

#### Methods:
- `getStatusCode(): int` - Get HTTP status code
- `getMessage(): string` - Get error message

## 🌟 Examples

### Handling Different Reel URL Formats

```php
// Regular reel URL
$url1 = 'https://www.instagram.com/reel/xyz123/';

// Short reel URL
$url2 = 'https://www.instagram.com/reels/xyz123/';

// URL with parameters
$url3 = 'https://www.instagram.com/reel/xyz123/?utm_source=ig_web_copy_link';
```

### Error Handling

```php
try {
    $video = $downloader->getVideoInfo($url);
    
    if ($video) {
        // Process reel video
        $videoUrl = $video->getVideoUrl();
        // Download or stream video
    } else {
        echo "No reel information found";
    }
} catch (InstagramException $e) {
    switch ($e->getStatusCode()) {
        case 400:
            echo "Invalid reel URL format";
            break;
        case 404:
            echo "Reel not found";
            break;
        case 500:
            // Handle various 500 error cases
            if (strpos($e->getMessage(), 'Failed to parse server response') !== false) {
                echo "Error: Instagram API response was malformed";
            } elseif (strpos($e->getMessage(), 'Failed to connect to Instagram server') !== false) {
                echo "Error: Could not establish connection with Instagram";
            } elseif (strpos($e->getMessage(), 'Instagram server error') !== false) {
                echo "Error: Instagram server is experiencing issues";
            } elseif (strpos($e->getMessage(), 'Failed to extract video dimensions') !== false) {
                echo "Error: Could not retrieve video dimensions";
            } else {
                echo "Internal server error: " . $e->getMessage();
            }
            break;
        default:
            echo "Error: " . $e->getMessage();
    }
}
```

## 🛠️ Development

### Requirements

- PHP 7.4 or higher
- Composer
- GuzzleHttp Client
- Symfony DomCrawler

## 📞 Support

- GitHub Issues: [Open an issue](https://github.com/code3-dev/reelflow-php/issues)
- Email: [h3dev.pira@gmail.com](mailto:h3dev.pira@gmail.com)
- Telegram: [@h3dev](https://t.me/h3dev)

## 📄 License

MIT © [Hossein Pira](https://github.com/code3-dev)

---

<div align="center">

Made with ❤️ by [Hossein Pira](https://github.com/code3-dev)

</div>