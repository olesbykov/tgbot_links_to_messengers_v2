<?php
namespace Commands;

use Core\Telegram;
use Core\DB;

class HelpCommand {
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

        $reply = "Больше можно не добавлять в телефонную книгу всех, кому надо только раз написать в WhatsApp или Telegram.

Введите номер телефона с кодом страны. Этот бот превратит телефонные номера в ссылки на мессенджеры.

Также бот генерирует ссылки – на Telegram и WhatsApp. Их можно скопировать и разместить в конце объявления вместо фразы «<code>пишите в Вацап 📱88004444858 📳😘</code>».

Кстати, такие записи бот тоже понимает. Можете попробовать:
<code>8 (812) 999-9-178 Дядя Фёдор, пёс или кот</code>

Или:
<code>Сапоги-скороходы +7 995 885-18-48</code>

(Примеры можно скопировать)

Бот возвращает ссылки для всех найденных номеров в тексте. Например, при пересылке сообщения боту  
«<code>Звоните нам: +7 (812) 999-91-78, +7 995 885-18-48 или приходите на Петроградский проспект 18</code>»
вы получите два сообщения, первое с ссылками для <code>+78129999178</code>, второе с ссылками для <code>+79958851848</code>.

Предложения по доработке принимаю в личку @OlesBykov.

Надеюсь, что @MessengerLinksBot сделает ваше общение приятнее.

Веб-версия:
{$webVer}

Поддержать работу над ботом: 
{$donate} ";

        $this->telegram->sendMessage($chatId, $reply, [
            'parse_mode' => 'html'
        ]);
    }
}
