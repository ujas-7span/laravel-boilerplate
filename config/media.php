<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Media Tags
    |--------------------------------------------------------------------------
    |
    | Registered media tags identify the functional purpose of uploaded assets.
    |
    */
    'tags' => [
        'profile' => 'profile',
        'avatar' => 'avatar',
        'banner' => 'banner',
        'thumbnail' => 'thumbnail',
        'document' => 'document',
        'attachment' => 'attachment',
        'chat' => 'chat',
        'default' => 'default',
    ],

    /*
    |--------------------------------------------------------------------------
    | Private Tags
    |--------------------------------------------------------------------------
    |
    | Tags listed here will be routed to the private filesystem disk.
    | Private assets require a short-lived signed GET URL for access.
    |
    */
    'private_tags' => [
        'document',
        'attachment',
        'chat',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Directories
    |--------------------------------------------------------------------------
    |
    | Destination directory in storage for each registered media tag.
    |
    */
    'directories' => [
        'profile' => 'users/profiles',
        'avatar' => 'users/avatars',
        'banner' => 'banners',
        'thumbnail' => 'thumbnails',
        'document' => 'documents',
        'attachment' => 'attachments',
        'chat' => 'chat/attachments',
        'default' => 'uploads',
    ],

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | The default public and private storage disks.
    | In production, set to 's3' and 's3-private' (or R2/MinIO).
    | In local development, defaults to 'public' and 'local'.
    |
    */
    'disks' => [
        'public' => env('MEDIA_DISK_PUBLIC', env('FILESYSTEM_DISK', 'public')),
        'private' => env('MEDIA_DISK_PRIVATE', 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | CDN Acceleration
    |--------------------------------------------------------------------------
    |
    | If enabled, public media URLs can be routed through a CDN distribution.
    |
    */
    'cdn_enable' => env('MEDIA_CDN_ENABLE', false),
    'cdn_url' => env('MEDIA_CDN_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Temporary Upload Expiration
    |--------------------------------------------------------------------------
    |
    | Unattached uploads recorded in temp_files are pruned after this duration.
    |
    */
    'signed_url_expiration_minutes' => (int) env('MEDIA_SIGNED_URL_EXPIRATION', 20),
    'temp_file_delete_after_days' => (int) env('TEMP_FILE_DELETE_AFTER_DAYS', 2),

    /*
    |--------------------------------------------------------------------------
    | Aggregate MIME Types
    |--------------------------------------------------------------------------
    |
    | Categorized MIME types for aggregate type resolution and validation.
    |
    */
    'aggregate_types' => [
        'image' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'image/x-icon',
        ],
        'document' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
        ],
        'audio' => [
            'audio/mpeg',
            'audio/mp3',
            'audio/wav',
            'audio/ogg',
            'audio/aac',
        ],
        'video' => [
            'video/mp4',
            'video/webm',
            'video/ogg',
            'video/quicktime',
        ],
        'archive' => [
            'application/zip',
            'application/x-zip-compressed',
            'application/x-tar',
            'application/gzip',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Extension to MIME Mapping
    |--------------------------------------------------------------------------
    |
    | Used to automatically infer MIME types when client only provides filename.
    |
    */
    'mime_types' => [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'csv' => 'text/csv',
        'txt' => 'text/plain',
        'zip' => 'application/zip',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'mp4' => 'video/mp4',
        'mov' => 'video/quicktime',
    ],
];
