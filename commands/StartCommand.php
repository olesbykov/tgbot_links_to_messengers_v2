<?php
namespace Commands;

use Core\Telegram;

class StartCommand {
    private $telegram;
    private $config;

    public function __construct(Telegram $telegram, array $config) {
        $this->telegram = $telegram;
        $this->config = $config;
    }

    public function execute(int $chatId, array $message): void {
        $donate = $this->config['donate_link'] ?? '';
        $webVer = $this->config['web_version_link'] ?? '';
        $reply = "<b>Добро пожаловать!</b>\nЭтот бот поможет сгенерировать ссылки на мессенджеры.\n\nВы можете нажать на кнопку и получить ссылки для своего телефона или ввести другой номер телефона.\n\n<b>Внимание:</b> номера на 8 распознаются как российские.\n\nПоддержать работу над ботом:\n$donate\n\nВеб-версия бота:\n$webVer";

        $keyboard = [
            "keyboard" => [
                [
                    ["text" => "Отправить ваш номер телефона", "request_contact" => true],
                ]
            ],
            "one_time_keyboard" => true,
            "resize_keyboard" => true
        ];
        $reply_markup = json_encode($keyboard);

        $this->telegram->sendMessage($chatId, $reply, $reply_markup, 'html');
    }
}
