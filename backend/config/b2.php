<?php

return [
    'key_id'      => env('B2_APPLICATION_KEY_ID'),
    'key'         => env('B2_APPLICATION_KEY'),
    'bucket_id'   => env('B2_BUCKET_ID'),
    'bucket_name' => env('B2_BUCKET_NAME'),
    'endpoint'    => env('AWS_ENDPOINT', 'https://s3.us-east-005.backblazeb2.com'),
    'region'      => env('AWS_DEFAULT_REGION', 'us-east-005'),
    'public_url'  => env('B2_PUBLIC_URL'),  // CDN URL prefix
];
