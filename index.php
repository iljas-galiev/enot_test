<?php
function autoload($className)
{
    $className = (string)str_replace('\\', DIRECTORY_SEPARATOR, $className);
    require_once __DIR__ . DIRECTORY_SEPARATOR . $className . '.php';
}

spl_autoload_register('autoload');

require 'vendor/autoload.php';

/* Подключение файла конфигурации для настройки endpoint'ов, данных для входа и proxy */
$config = require 'config/main.php';
(new App\Runner($config))->start();
