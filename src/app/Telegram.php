<?php

namespace src\app;

class Telegram
{

    private int $id;

    function __construct(string $id, string $username)
    {
        $this->id = $id;
        Auth::logInFromTelegram($this->id, $username);
        if (!Auth::isLoggedIn()) {
            $this->sendMessageToSender("Sorry, ich kenne Dich nicht. \u{1F635}");
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
                name: $title . '.' . $photo->type,
                uploaded: now(),
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
                $message = sprintf("Hallo %s \u{1F60D}\r\nSchön, dass Du hier bist.", Auth::$user);
                $this->sendMessageToSender($message);
            }
        }
    }

    private function sendMessageToSender($message)
    {
        Telegram::sendMessage($this->id, $message);
    }

    public static function sendMessage($chat_id, $message)
    {
        $url = Telegram::getApiUrl() . 'sendMessage';
        $data = array(
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true
        );
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

}
