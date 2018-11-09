<?php
$config = [
    'protocol' => [
        'http' => [
            'enable' => true,
            'bind' => '0.0.0.0',
            'port' => 8001,
            'pack' => null
        ],

        'tcp' => [
            'enable' => true,
            'bind' => '0.0.0.0',
            'port' => 8000,
            'pack' => \Air\Pack\LenJsonPack::class
        ]
    ],
];

return $config;