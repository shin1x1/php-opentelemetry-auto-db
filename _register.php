<?php
declare(strict_types=1);

use Shin1x1\OpenTelemetry\Auto\Db\Mysqli\MysqliInstrumentation;
use Shin1x1\OpenTelemetry\Auto\Db\Pdo\PdoInstrumentation;

MysqliInstrumentation::register();
PdoInstrumentation::register();
