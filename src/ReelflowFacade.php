<?php

namespace Code3\ReelFlow;

class ReelflowFacade
{
    private static ?ReelFlow $instance = null;

    public static function getInstance(): ReelFlow
    {
        if (self::$instance === null) {
            self::$instance = new ReelFlow();
        }
        return self::$instance;
    }

    public static function getVideoInfo(string $url): VideoInfo
    {
        return self::getInstance()->getVideoInfo($url);
    }
} 