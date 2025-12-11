<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Social Media Platform Configurations
    |--------------------------------------------------------------------------
    |
    | This file contains the specifications for each social media platform
    | that the mockup system supports, including image dimensions, aspect
    | ratios, and other platform-specific settings.
    |
    */

    'facebook' => [
        'feed' => [
            'id' => 'facebook_feed',
            'name' => 'Facebook Feed',
            'icon' => 'facebook',
            'dimensions' => [
                'landscape' => ['width' => 1200, 'height' => 630, 'ratio' => '1.91:1'],
                'square' => ['width' => 1080, 'height' => 1080, 'ratio' => '1:1'],
                'portrait' => ['width' => 1080, 'height' => 1350, 'ratio' => '4:5'],
            ],
            'recommended' => 'landscape',
            'max_caption_length' => 63206,
            'caption_truncate_at' => 125, // "See more" appears after this
        ],
        'story' => [
            'id' => 'facebook_story',
            'name' => 'Facebook Story',
            'icon' => 'facebook',
            'dimensions' => [
                'default' => ['width' => 1080, 'height' => 1920, 'ratio' => '9:16'],
            ],
            'recommended' => 'default',
            'max_duration' => 20, // seconds for video
        ],
    ],

    'instagram' => [
        'feed' => [
            'id' => 'instagram_feed',
            'name' => 'Instagram Feed',
            'icon' => 'instagram',
            'dimensions' => [
                'square' => ['width' => 1080, 'height' => 1080, 'ratio' => '1:1'],
                'portrait' => ['width' => 1080, 'height' => 1350, 'ratio' => '4:5'],
                'landscape' => ['width' => 1080, 'height' => 566, 'ratio' => '1.91:1'],
            ],
            'recommended' => 'square',
            'max_caption_length' => 2200,
            'caption_truncate_at' => 125, // "more" appears after this
            'max_hashtags' => 30,
        ],
        'story' => [
            'id' => 'instagram_story',
            'name' => 'Instagram Story',
            'icon' => 'instagram',
            'dimensions' => [
                'default' => ['width' => 1080, 'height' => 1920, 'ratio' => '9:16'],
            ],
            'recommended' => 'default',
            'max_duration' => 15, // seconds for video
        ],
        'reel' => [
            'id' => 'instagram_reel',
            'name' => 'Instagram Reel',
            'icon' => 'instagram',
            'dimensions' => [
                'default' => ['width' => 1080, 'height' => 1920, 'ratio' => '9:16'],
            ],
            'recommended' => 'default',
            'max_duration' => 90, // seconds
            'min_duration' => 3, // seconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported File Types
    |--------------------------------------------------------------------------
    */

    'supported_image_types' => [
        'image/jpeg',
        'image/png',
        'image/webp',
    ],

    'supported_video_types' => [
        'video/mp4',
        'video/quicktime', // .mov
        'video/x-msvideo', // .avi
    ],

    'max_image_size' => 10 * 1024 * 1024, // 10MB
    'max_video_size' => 100 * 1024 * 1024, // 100MB

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Settings
    |--------------------------------------------------------------------------
    */

    'thumbnails' => [
        'small' => ['width' => 150, 'height' => 150],
        'medium' => ['width' => 400, 'height' => 400],
        'large' => ['width' => 800, 'height' => 800],
    ],
];
