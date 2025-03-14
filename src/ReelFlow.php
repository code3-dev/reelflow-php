<?php

namespace Code3\ReelFlow;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;

class ReelFlow
{
    private const INSTAGRAM_BASE_URL = 'https://www.instagram.com';
    private const INSTAGRAM_ENDPOINTS = [
        'POST' => '/p',
        'GRAPHQL' => '/api/graphql'
    ];

    private const GRAPHQL_HEADERS = [
        'Accept' => '*/*',
        'Accept-Language' => 'en-US,en;q=0.5',
        'Content-Type' => 'application/x-www-form-urlencoded',
        'X-FB-Friendly-Name' => 'PolarisPostActionLoadPostQueryQuery',
        'X-CSRFToken' => 'RVDUooU5MYsBbS1CNN3CzVAuEP8oHB52',
        'X-IG-App-ID' => '1217981644879628',
        'X-FB-LSD' => 'AVqbxe3J_YA',
        'X-ASBD-ID' => '129477',
        'Sec-Fetch-Dest' => 'empty',
        'Sec-Fetch-Mode' => 'cors',
        'Sec-Fetch-Site' => 'same-origin',
        'User-Agent' => 'Mozilla/5.0 (Linux; Android 11; SAMSUNG SM-G973U) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/14.2 Chrome/87.0.4280.141 Mobile Safari/537.36'
    ];

    private const WEBPAGE_HEADERS = [
        'accept' => '*/*',
        'host' => 'www.instagram.com',
        'referer' => 'https://www.instagram.com/',
        'DNT' => '1',
        'Sec-Fetch-Dest' => 'document',
        'Sec-Fetch-Mode' => 'navigate',
        'Sec-Fetch-Site' => 'same-origin',
        'User-Agent' => 'Mozilla/5.0 (Linux; Android 11; SAMSUNG SM-G973U) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/14.2 Chrome/87.0.4280.141 Mobile Safari/537.36'
    ];

    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => self::INSTAGRAM_BASE_URL,
            'headers' => self::WEBPAGE_HEADERS
        ]);
    }

    /**
     * Get video information from an Instagram URL
     * 
     * @param string $url Instagram video/reel URL
     * @return VideoInfo Video information including direct URL, dimensions, description and username
     * @throws InstagramException
     */
    public function getVideoInfo(string $url): VideoInfo
    {
        $error = $this->validateInstagramURL($url);
        if ($error) {
            throw new InstagramException($error, 400);
        }

        $postId = $this->getPostIdFromUrl($url);
        if (!$postId) {
            throw new InstagramException('Could not extract post ID from URL', 400);
        }

        try {
            // Try webpage method first
            $videoInfo = $this->getVideoInfoFromHTML($postId);
            if ($videoInfo) {
                return $videoInfo;
            }

            // Fallback to GraphQL method
            $videoInfo = $this->getVideoInfoFromGraphQL($postId);
            if ($videoInfo) {
                return $videoInfo;
            }

            throw new InstagramException('Could not fetch video information', 404);
        } catch (\Exception $e) {
            if ($e instanceof InstagramException) {
                throw $e;
            }
            throw new InstagramException('Failed to process video information: ' . $e->getMessage(), 500);
        }
    }

    private function getVideoInfoFromHTML(string $postId): ?VideoInfo
    {
        try {
            $response = $this->client->get(self::INSTAGRAM_ENDPOINTS['POST'] . '/' . $postId, [
                'headers' => self::WEBPAGE_HEADERS
            ]);
            $html = $response->getBody()->getContents();
            
            $crawler = new Crawler($html);
            
            $videoElement = $crawler->filter('meta[property="og:video"]');
            if ($videoElement->count() === 0) {
                return null;
            }

            $videoUrl = $videoElement->attr('content');
            if (!$videoUrl) {
                return null;
            }

            $width = $crawler->filter('meta[property="og:video:width"]')->attr('content') ?? '';
            $height = $crawler->filter('meta[property="og:video:height"]')->attr('content') ?? '';
            
            // Get thumbnail URL
            $thumbnailUrl = $crawler->filter('meta[property="og:image"]')->attr('content') ?? '';
            
            // Get description and username
            $description = $crawler->filter('meta[property="og:description"]')->attr('content') ?? '';
            $username = '';
            
            // Try to extract username from description
            if (preg_match('/^([^:]+)/', $description, $matches)) {
                $username = trim($matches[1]);
                // Remove username from description
                $description = trim(preg_replace('/^[^:]+:\s*/', '', $description));
            }
            
            if (empty($width) || empty($height)) {
                throw new InstagramException('Failed to extract video dimensions', 500);
            }
            
            return new VideoInfo(
                $videoUrl,
                (int)$width,
                (int)$height,
                $description,
                $username,
                $thumbnailUrl
            );
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            throw new InstagramException('Instagram server error: ' . $e->getMessage(), 500);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new InstagramException('Failed to connect to Instagram server: ' . $e->getMessage(), 500);
        } catch (\Exception $e) {
            if ($e instanceof InstagramException) {
                throw $e;
            }
            return null;
        }
    }

    private function getVideoInfoFromGraphQL(string $postId): ?VideoInfo
    {
        try {
            $response = $this->client->post(self::INSTAGRAM_ENDPOINTS['GRAPHQL'], [
                'headers' => self::GRAPHQL_HEADERS,
                'form_params' => $this->getGraphQLParams($postId)
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InstagramException('Failed to parse server response', 500);
            }

            $mediaData = $data['data']['xdt_shortcode_media'] ?? null;

            if (!$mediaData) {
                return null;
            }

            if (!($mediaData['is_video'] ?? false)) {
                throw new InstagramException('This post is not a video', 400);
            }

            if (!isset($mediaData['video_url']) || !isset($mediaData['dimensions'])) {
                throw new InstagramException('Invalid video data received from server', 500);
            }

            $description = $mediaData['edge_media_to_caption']['edges'][0]['node']['text'] ?? '';
            $username = $mediaData['owner']['username'] ?? '';
            
            // Get thumbnail URL from GraphQL response
            $thumbnailUrl = $mediaData['display_url'] ?? '';

            return new VideoInfo(
                $mediaData['video_url'],
                $mediaData['dimensions']['width'] ?? 0,
                $mediaData['dimensions']['height'] ?? 0,
                $description,
                $username,
                $thumbnailUrl
            );
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            throw new InstagramException('Instagram server error: ' . $e->getMessage(), 500);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new InstagramException('Failed to connect to Instagram server: ' . $e->getMessage(), 500);
        } catch (\Exception $e) {
            if ($e instanceof InstagramException) {
                throw $e;
            }
            return null;
        }
    }

    private function getGraphQLParams(string $shortcode): array
    {
        return [
            'av' => '0',
            '__d' => 'www',
            '__user' => '0',
            '__a' => '1',
            '__req' => '3',
            '__hs' => '19624.HYP:instagram_web_pkg.2.1..0.0',
            'dpr' => '3',
            '__ccg' => 'UNKNOWN',
            '__rev' => '1008824440',
            '__s' => 'xf44ne:zhh75g:xr51e7',
            '__hsi' => '7282217488877343271',
            '__dyn' => '7xeUmwlEnwn8K2WnFw9-2i5U4e0yoW3q32360CEbo1nEhw2nVE4W0om78b87C0yE5ufz81s8hwGwQwoEcE7O2l0Fwqo31w9a9x-0z8-U2zxe2GewGwso88cobEaU2eUlwhEe87q7-0iK2S3qazo7u1xwIw8O321LwTwKG1pg661pwr86C1mwraCg',
            '__csr' => 'gZ3yFmJkillQvV6ybimnG8AmhqujGbLADgjyEOWz49z9XDlAXBJpC7Wy-vQTSvUGWGh5u8KibG44dBiigrgjDxGjU0150Q0848azk48N09C02IR0go4SaR70r8owyg9pU0V23hwiA0LQczA48S0f-x-27o05NG0fkw',
            '__comet_req' => '7',
            'lsd' => 'AVqbxe3J_YA',
            'jazoest' => '2957',
            '__spin_r' => '1008824440',
            '__spin_b' => 'trunk',
            '__spin_t' => '1695523385',
            'fb_api_caller_class' => 'RelayModern',
            'fb_api_req_friendly_name' => 'PolarisPostActionLoadPostQueryQuery',
            'variables' => json_encode([
                'shortcode' => $shortcode,
                'fetch_comment_count' => 'null',
                'fetch_related_profile_media_count' => 'null',
                'parent_comment_count' => 'null',
                'child_comment_count' => 'null',
                'fetch_like_count' => 'null',
                'fetch_tagged_user_count' => 'null',
                'fetch_preview_comment_count' => 'null',
                'has_threaded_comments' => 'false',
                'hoisted_comment_id' => 'null',
                'hoisted_reply_id' => 'null'
            ]),
            'server_timestamps' => 'true',
            'doc_id' => '10015901848480474'
        ];
    }

    private function validateInstagramURL(string $url): string
    {
        if (!$url) {
            return 'Instagram URL was not provided';
        }

        if (!str_contains($url, 'instagram.com/')) {
            return 'Invalid URL does not contain Instagram domain';
        }

        if (!str_starts_with($url, 'https://')) {
            return 'Invalid URL it should start with "https://www.instagram.com..."';
        }

        $postRegex = '#^https://(?:www\.)?instagram\.com/p/([a-zA-Z0-9_-]+)/?#';
        $reelRegex = '#^https://(?:www\.)?instagram\.com/reels?/([a-zA-Z0-9_-]+)/?#';

        if (!preg_match($postRegex, $url) && !preg_match($reelRegex, $url)) {
            return 'URL does not match Instagram post or reel';
        }

        return '';
    }

    private function getPostIdFromUrl(string $url): ?string
    {
        $postRegex = '#^https://(?:www\.)?instagram\.com/p/([a-zA-Z0-9_-]+)/?#';
        $reelRegex = '#^https://(?:www\.)?instagram\.com/reels?/([a-zA-Z0-9_-]+)/?#';

        if (preg_match($postRegex, $url, $matches) || preg_match($reelRegex, $url, $matches)) {
            return $matches[1];
        }
        return null;
    }
} 