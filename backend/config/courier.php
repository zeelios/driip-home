<?php

return [
    'ghn' => [
        'name'         => 'Giao Hàng Nhanh',
        'api_endpoint' => env('GHN_API_ENDPOINT', 'https://dev-online-gateway.ghn.vn/shiip/public-api'),
        'api_key'      => env('GHN_API_KEY'),
        'shop_id'      => env('GHN_SHOP_ID'),
    ],
    'ghtk' => [
        'name'         => 'Giao Hàng Tiết Kiệm',
        'api_endpoint' => env('GHTK_API_ENDPOINT', 'https://services.giaohangtietkiem.vn'),
        'api_key'      => env('GHTK_API_KEY'),
    ],
    'spx' => [
        'name'         => 'Shopee Express',
        'api_endpoint' => env('SPX_API_ENDPOINT', ''),
        'api_key'      => env('SPX_API_KEY'),
    ],
    'viettel' => [
        'name'         => 'Viettel Post',
        'api_endpoint' => env('VIETTEL_API_ENDPOINT', 'https://partner.viettelpost.vn/v2'),
        'api_key'      => env('VIETTEL_API_KEY'),
    ],
];
