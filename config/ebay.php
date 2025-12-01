<?php

// Ebay API Settings
return [
    'app_id' => env('APP_ENV') == 'local' ? env('local_ebay_app_id') : env('ebay_app_id', null),
    'dev_id' => env('APP_ENV') == 'local' ? env('local_ebay_dev_id') : env('ebay_dev_id'),
    'cert_id' => env('APP_ENV') == 'local' ? env('local_ebay_cert_id') : env('ebay_cert_id'),

    'access_token_grant_url' =>  env('APP_ENV') == 'local' ? 'https://api.sandbox.ebay.com/identity/v1/oauth2/token' : 'https://api.ebay.com/identity/v1/oauth2/token',
    'ruName' => env('APP_ENV') == 'local' ? 'le_southwart-lesouthw-SlabLi-nsergyhf' : 'le_southwart-lesouthw-SlabLi-nxeph',
    'endpoints' => [
        'local' => [
            'identity' => 'https://apiz.sandbox.ebay.com/commerce/identity/v1/user/',
            'policies_fufill' => 'https://api.sandbox.ebay.com/sell/account/v1/fulfillment_policy?marketplace_id=EBAY_US'
        ],
        'production' => [
            'identity' => 'https://apiz.ebay.com/commerce/identity/v1/user/'
        ]
        ],
    'oauth_authorize_url_local' => 'https://auth.sandbox.ebay.com/oauth2/authorize',
    'oauth_authorize_url_production' => 'https://auth.ebay.com/oauth2/authorize',
    'scopes' => [
        'identity' => [
            'https://api.ebay.com/oauth/api_scope/commerce.identity.readonly',
            'https://api.ebay.com/oauth/api_scope/commerce.identity.email.readonly',
            'https://api.ebay.com/oauth/api_scope/commerce.identity.phone.readonly',
            'https://api.ebay.com/oauth/api_scope/commerce.identity.address.readonly',
            'https://api.ebay.com/oauth/api_scope/commerce.identity.name.readonly',
            'https://api.ebay.com/oauth/api_scope/commerce.identity.status.readonly',
        ],
        // 'buy' => [
        //     'https://api.ebay.com/oauth/api_scope/buy.offer.auction',
        // ],
        'sell' => [
            'https://api.ebay.com/oauth/api_scope/sell.inventory', // For createOrReplaceInventoryItem
            'https://api.ebay.com/oauth/api_scope/sell.inventory.readonly', // For getInventoryItem
            'https://api.ebay.com/oauth/api_scope/sell.account', // For creating/updating policies
            'https://api.ebay.com/oauth/api_scope/sell.account.readonly', // For reading policies
        ],
    ],
];
