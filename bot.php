<?php
$config = require __DIR__ . '/config.php';

require __DIR__ . '/core/Autoloader.php';
\Core\Autoloader::register();

use Core\Telegram;
use Core\Router;
use Core\DB;

// подключение к обеим базам
// $dbAll = new DB($config['db_all'] + ['debug' => $config['debug']]);
// $dbBot = new DB($config['db_bot'] + ['debug' => $config['debug']]);

$telegram = new Telegram($config['telegram_token']);
$router = new Router($telegram, $config); //, $dbBot, $dbAll


$update = json_decode(file_get_contents('php://input'), true);

if ($update) {
    $router->handle($update);
}
