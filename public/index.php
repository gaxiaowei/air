<?php
$composerLoader = require __DIR__.'/../vendor/autoload.php';

$air = new Air\Air(realpath(__DIR__.'/../'));

$composerLoader->addPsr4('App\\', $air->getAppDirPath());
unset($composerLoader);

$air->server('ng')->run();