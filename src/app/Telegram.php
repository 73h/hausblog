<?php

namespace src\app;

class Telegram
{

    private int $id;

    function __construct(string $id, string $username)
    {
        $this->checkIfIpIsFromTelegram($_SERVER["REMOTE_ADDR"]);
        $this->id = $id;
        Auth::logInFromTelegram($this->id, $username);
        if (!Auth::isLoggedIn()) {
            $this->sendMessageToSender("Sorry, ich kenne Dich nicht. \u{1F635}");
        }
    }

    private function checkIfIpIsFromTelegram(string $ip)
    {
        if (isProd()) {
            $json = json_decode(file_get_contents('http://ip-api.com/json/' . $ip));
            if ($json->status != 'success' || $json->as != 'AS62041 Telegram Messenger Inc') {
                Telegram::sendMessage($_ENV['TELEGRAM_ADMIN_ID'], "Incoming webhook call from unknown source!\r\n\r\n" . json_encode($json));
                exit;
            }
        }
    }

    private static function getApiUrl(bool $file = false): string
    {
        return 'https://api.telegram.org/' . ($file ? 'file/' : '') . 'bot' . $_ENV['TELEGRAM_TOKEN'] . '/';
    }

    private function getPhoto(array $photo): object
    {
        $url_photo_data = $this->getApiUrl() . 'getFile?file_id=' . $photo['file_id'];
        $photo_data = json_decode(file_get_contents($url_photo_data));
        $url_photo = $this->getApiUrl(true) . $photo_data->result->file_path;
        $photo_type = preg_replace('/^.+\.([a-zA-Z]{2,6})$/', '$1', $photo_data->result->file_path);
        return (object)[
            'photo' => file_get_contents($url_photo),
            'type' => $photo_type
        ];
    }

    public function receivePhoto(array $photo_list)
    {
        if (Auth::isLoggedIn()) {
            $photo = $this->getPhoto($photo_list[count($photo_list) - 1]);
            $thumbnail = $this->getPhoto($photo_list[1]);
            $title = uniqid();
            $image = new Photos();
            $image->insertPhoto(
                uploaded: now()->format('c'),
                title: $title,
                thumbnail: $thumbnail->photo,
                thumbnail_type: $thumbnail->type,
                photo: $photo->photo,
                photo_type: $photo->type
            );
            $this->sendMessageToSender("Danke " . Auth::$user . ", das Bild hab ich gespeichert. \u{1F680}");
        }
    }

    public function receiveCommand(string $text)
    {
        if (Auth::isLoggedIn()) {
            if (str_starts_with($text, '/login')) {
                $code = Auth::createLoginCode();
                $message = sprintf("Hallo %s \u{1F60D}\r\n\r\nHier ist Dein Anmeldecode: ```%s```", Auth::$user, $code);
                $message .= sprintf("\r\n\r\n%s/login/%s", URL, $code);
                $this->sendMessageToSender($message);
            } elseif (str_starts_with($text, '/start')) {
                $message = sprintf("Hallo %s \u{1F60D}\r\nSch??n, dass Du hier bist.", Auth::$user);
                $this->sendMessageToSender($message);
            }
        }
    }

    public function receiveButton($data)
    {
        if (Auth::isLoggedIn()) {
            $function = $data->function;
            $value = $data->value;
            if ($function == 'unlock_comment') {
                $rows = Comments::publishComment($value);
                if ($rows > 0) {
                    foreach (Telegram::getAllChatIds() as $user) {
                        Telegram::sendMessage($user['telegram_id'], "\u{2705} Kommentar freigeschaltet");
                    }
                }
            } elseif ($function == 'delete_comment') {
                $rows = Comments::deleteComment($value);
                if ($rows > 0) {
                    foreach (Telegram::getAllChatIds() as $user) {
                        Telegram::sendMessage($user['telegram_id'], "\u{274c} Kommentar gel??scht");
                    }
                }
            }
        }
    }

    private function sendMessageToSender($message)
    {
        Telegram::sendMessage($this->id, $message);
    }

    public static function sendMessage(string $chat_id, string $message, bool $markdown = true, array $buttons = null)
    {
        $url = Telegram::getApiUrl() . 'sendMessage';
        $encodedMarkup = json_encode(array('inline_keyboard' => $buttons));
        $data = array(
            'chat_id' => $chat_id,
            'text' => str_replace('_', '-', $message),
            'disable_web_page_preview' => true
        );
        if ($buttons != null) {
            $data['reply_markup'] = $encodedMarkup;
        }
        if ($markdown) $data['parse_mode'] = 'Markdown';
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            )
        );
        $context = stream_context_create($options);
        file_get_contents($url, false, $context);
    }

    public static function getAllChatIds()
    {
        $sql = "select user, telegram_id from tbl_users where telegram_id is not null";
        return Database::select($sql);
    }

    public static function getCallbackButton(string $function, string $value): string
    {
        return json_encode(array('function' => $function, 'value' => $value));
    }

    public static function sendMessageForNewComment(int $pk_comment, string $creator, string $comment, string $article_title)
    {
        $message = ", jemand hat den Artikel \"" . $article_title . "\" kommentiert. \u{1F929}\r\n\r\n";
        $message .= "**" . $creator . "** schreibt:\r\n" . $comment;
        $buttons = array(array(
            array('text' => 'Freischalten', 'callback_data' => Telegram::getCallbackButton('unlock_comment', strval($pk_comment))),
            array('text' => 'L??schen', 'callback_data' => Telegram::getCallbackButton('delete_comment', strval($pk_comment)))
        ));
        foreach (Telegram::getAllChatIds() as $user) {
            $m = 'Hallo ' . $user['user'] . $message;
            Telegram::sendMessage($user['telegram_id'], $m, buttons: $buttons);
        }
    }

}
