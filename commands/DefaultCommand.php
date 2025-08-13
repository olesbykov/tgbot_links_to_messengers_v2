<?php
namespace Commands;

use Core\Telegram;
use Core\DB;

class DefaultCommand {
    private Telegram $telegram;
    private array $config;
    // private DB $dbBot;
    // private DB $dbAll;

    public function __construct(Telegram $telegram, array $config) { // , DB $dbBot, DB $dbAll
        $this->telegram = $telegram;
        $this->config = $config;
        // $this->dbBot = $dbBot;
        // $this->dbAll = $dbAll;
    }

    public function execute($chatId, array $message): void
    {
        $donate = $this->config['donate_link'] ?? '';
        $webVer = $this->config['web_version_link'] ?? '';
        $debug = $this->config['debug'] ?? false;
        $adminChatId = $this->config['admin_chat_id'] ?? null;

        $keyboard = [
            'keyboard' => [[
                [
                    'text' => 'Отправить ваш номер телефона',
                    'request_contact' => true
                ],
            ]],
            'one_time_keyboard' => true,
            'resize_keyboard' => true
        ];

        // 1) Получаем текст или телефон из контакта
        $text = $message['text'] ?? '';
        $contactPhone = $message['contact']['phone_number'] ?? null;
        if ($contactPhone !== null && $contactPhone !== '') {
            $text = $contactPhone;
        }

        // 2) Нормализация текста
        $textForSearch = str_replace(["\r\n", "\r", "\n"], "<br/>", $text); // обновлённый шаблон (запрет на \n, \r)
        $textForSearch = str_replace(['(', ')'], '', $textForSearch); // замена скобок на пустое место, а не пробел
        $textForSearch = preg_replace('/\s+/', ' ', $textForSearch); // замена двойных пробелов на одинарные
        $textForSearch = preg_replace('/[\x{00A0}\x{2007}\x{202F}]/u', ' ', $textForSearch); // замена неразрывных пробелов
        $textForSearch = preg_replace('/[\x{2010}-\x{2015}]/u', '-', $textForSearch); // замена разных тире на дефис
        $textForSearch = trim($textForSearch); // замена лишних пробелов по краям

        // 3) Поиск телефонов
        $pattern = '/\+?\d{1,3}[\s.-]?((\d{2,6}))?[\s.-]?\d{1,3}[\s.-]?\d{1,4}[\s.-]?\d{1,4}|[\d\s.-]{10,18}/u';
        preg_match_all($pattern, $textForSearch, $matches);

        if ($debug && $adminChatId) {
            $this->telegram->sendMessage($adminChatId, '$matches ' . json_encode($matches, JSON_UNESCAPED_UNICODE));
        }

        $parts = [];
        foreach ($matches[0] as $rawPhone) {
            $phone = $this->normalizePhone($rawPhone);
            $note = null;

            if (strncmp($phone, 'ItsRuss', 7) === 0) {
                $phone = mb_substr($phone, 7);
                $note = PHP_EOL . '<i>Номер был распознан как российский и преобразован к формату номеров России и Казахстана с кодом <b>+7</b>. Если ваш номер в другой стране, пожалуйста, пришлите свой телефон с кодом страны с символом <b>+</b> в начале.</i>' . PHP_EOL;
            } elseif (strncmp($phone, 'ItsBelr', 7) === 0) {
                $phone = mb_substr($phone, 7);
                $note = PHP_EOL . '<i>Номер был распознан как телефон Республики Беларусь с кодом <b>+375</b>. Если ваш номер в другой стране, пожалуйста, пришлите свой телефон с кодом страны с символом <b>+</b> в начале.</i>' . PHP_EOL;
            } elseif ($phone === 'Start0') {
                $note = "Невозможно определить страну: номер записан в локальном формате." . PHP_EOL . PHP_EOL;
                $phone = '';
            }

            if ($phone === '') {
                $parts[] = ($note ?? '') . "Телефон не распознан";
                continue;
            }
            if (strlen($phone) < 10) {
                $parts[] = "Телефон подозрительно короткий";
                continue;
            }
            if (strlen($phone) > 15) {
                $parts[] = "Телефон содержит очень много символов";
                continue;
            }

            $goToTGText = 'Перейти в Телеграм';
            $goToWAText = 'Перейти в WA';
            $goToWAWebText = 'Перейти в WA Web';

            $block = "Распознан телефон <b>+{$phone}</b>" . ($note ?? '') . "

<a href=\"https://t.me/+{$phone}\">{$goToTGText}</a>


Telegram:
<code>https://t.me/+{$phone}</code>


<a href=\"https://wa.me/{$phone}\">{$goToWAText}</a>


<a href=\"https://web.whatsapp.com/send/?phone={$phone}&text&type=phone_number&app_absent=0\">{$goToWAWebText}</a>


WhatsApp:
<code>https://wa.me/{$phone}</code>
и
<code>whatsapp://send?phone={$phone}</code>

Веб-версия бота:
{$webVer}

Поддержать работу над ботом: 
{$donate} ";

            $parts[] = $block;
        }

        if (!$parts) {
            $parts[] = "Номера телефонов не найдены";
        }

        foreach ($parts as $part) {
            $this->telegram->sendMessage($chatId, $part, [
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
                'reply_markup' => json_encode($keyboard, JSON_UNESCAPED_UNICODE)
            ]);
        }
    }

    /**
     * Нормализация телефона
     */
    private function normalizePhone(string $input): string
    {
        $pattern = '/\+?\d{1,3}[\s.-]?((\d{2,6}))?[\s.-]?\d{1,3}[\s.-]?\d{1,4}[\s.-]?\d{1,4}|[\d\s.-]{10,18}/u'; // регулярка поиска телефона
        $hasPlus = mb_substr($input, 0, 1) === '+';

        preg_match_all($pattern, $input, $matches);
        if (empty($matches[0])) {
            $phone = preg_replace('/\D+/', '', $input);
        } else {
            $phone = preg_replace('/\D+/', '', $matches[0][0]);
        }

        if ($phone === '') {
            return '';
        }

        // Беларусь: начинается с 80 без +
        if (mb_substr($phone, 0, 2) === '80' && !$hasPlus) {
            $phone = '375' . mb_substr($phone, 2);
            return 'ItsBelr' . $phone;
        }

        // Россия/Казахстан: 10 цифр без кода страны
        if (strlen($phone) === 10 && !$hasPlus) {
            return 'ItsRuss7' . $phone;
        }

        // Россия/Казахстан: начинается с 8 и содержит 11 цифр
        if (mb_substr($phone, 0, 1) === '8' && strlen($phone) === 11 && !$hasPlus) {
            $phone = mb_substr($phone, 1);
            return 'ItsRuss7' . $phone;
        }

        if (mb_substr($phone, 0, 1) === '0') {
            return 'Start0';
        }

        return $phone;
    }
}
