<?php

namespace Code3\ReelFlow;

class VideoInfo
{
    private string $videoUrl;
    private string $width;
    private string $height;

    public function __construct(string $videoUrl, string $width, string $height)
    {
        $this->videoUrl = $videoUrl;
        $this->width = $width;
        $this->height = $height;
    }

    public function getVideoUrl(): string
    {
        return $this->videoUrl;
    }

    public function getWidth(): string
    {
        return $this->width;
    }

    public function getHeight(): string
    {
        return $this->height;
    }

    public function toArray(): array
    {
        return [
            'videoUrl' => $this->videoUrl,
            'width' => $this->width,
            'height' => $this->height
        ];
    }
} 