<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MimecastMail\MimeMail;

/**
 * Instantiate a new instance of MimeMail
 * You will need credentials from Mimecast
 * AccessKey - SecretKey, AppId, AppKey
 */
$request = new MimeMail(
    'AccessKey HERE',
    'SecretKey HERE',
    'AppID  HERE',
    'AppKey HERE'
);

/**
 * You will get this data from the form post in Wordpress
 */
$payload = [
    'data' => [
        [
            'to' => [
                [
                    'emailAddress' => 'hanifdawjee@gmail.com',
                    'displayName' => 'Hanif Dawjee',
                ],
            ],
            'htmlBody' => [
                'content' => 'This is the content of the email....',
            ],
            'subject' => 'Test email',
        ],
    ],
];

/**
 * How to make the request
 */
$request->makeRequest($payload);
