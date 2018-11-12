<?php
$config = [
    'protocol' => [
        'tcp' => [
            'enable' => true,
            'bind' => '0.0.0.0',
            'port' => 8000,
            'pack' => \Air\Pack\LenJsonPack::class
        ],

        'http' => [
            'enable' => true,
            'bind' => '0.0.0.0',
            'port' => 8001,
            'pack' => null
        ]
    ],
];

return $config;