<?php

namespace Code3\ReelFlow;

class VideoInfo
{
    private string $videoUrl;
    private int $width;
    private int $height;
    private ?string $description;
    private ?string $username;
    private ?string $thumbnailUrl;

    public function __construct(
        string $videoUrl,
        int $width,
        int $height,
        ?string $description = null,
        ?string $username = null,
        ?string $thumbnailUrl = null
    ) {
        $this->videoUrl = $videoUrl;
        $this->width = $width;
        $this->height = $height;
        $this->description = $description;
        $this->username = $username;
        $this->thumbnailUrl = $thumbnailUrl;
    }

    public function getUrl(): string
    {
        return $this->videoUrl;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    public function toArray(): array
    {
        return [
            'url' => $this->videoUrl,
            'width' => $this->width,
            'height' => $this->height,
            'description' => $this->description ?? '',
            'username' => $this->username ?? '',
            'thumbnail_url' => $this->thumbnailUrl ?? ''
        ];
    }
} 