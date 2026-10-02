<?php

declare(strict_types=1);

use Yiisoft\Yii\Runner\Http\HttpApplicationRunner;

require dirname(__DIR__) . '/vendor/autoload.php';

(new HttpApplicationRunner(rootPath: dirname(__DIR__), debug: false, checkEvents: false))->run();
