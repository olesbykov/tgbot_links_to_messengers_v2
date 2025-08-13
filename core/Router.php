<?php
namespace Core;

class Router {
    private $telegram;
    private $config;
    // private $dbBot;
    // private $dbAll;

    public function __construct(Telegram $telegram, array $config) { // , DB $dbBot, DB $dbAll
        $this->telegram = $telegram;
        $this->config = $config;
        // $this->dbBot = $dbBot;
        // $this->dbAll = $dbAll;
    }

    public function handle($update) {
        if (!isset($update['message'])) return;

        $message = $update['message'];
        $chatId = $message['chat']['id'];

        if (!empty($message['text']) && strpos($message['text'], '/') === 0) {
            $command = explode(' ', $message['text'])[0];
            $this->runCommand($command, $chatId, $message);
        } else {
            $this->runCommand('default', $chatId, $message);
        }
    }

    private function runCommand($command, $chatId, $message) {
        $className = '\\Commands\\' . ucfirst(strtolower(ltrim($command, '/'))) . 'Command';
        if (class_exists($className)) {
            $commandObj = new $className($this->telegram, $this->config); //, $this->dbBot, $this->dbAll
            $commandObj->execute($chatId, $message);
        } else {
            $defaultCommand = new \Commands\DefaultCommand($this->telegram, $this->config); //, $this->dbBot, $this->dbAll
            $defaultCommand->execute($chatId, $message);
        }
    }
}

