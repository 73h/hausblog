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

    public function receiveImage(array $photo_list)
    {
        if (Auth::isLoggedIn()) {
            $photo = $photo_list[count($photo_list) - 1];
            $url_image_data = $this->getApiUrl() . 'getFile?file_id=' . $photo['file_id'];
            $image_data = json_decode(file_get_contents($url_image_data));
            $url_image = $this->getApiUrl(true) . $image_data->result->file_path;
            $type = preg_replace('/^.+\.([a-zA-Z]{2,6})$/', '$1', $image_data->result->file_path);
            $title = uniqid();
            $image = new Images();
            $image->insertImage(
                name: $title . '.' . $type,
                uploaded: now(),
                title: $title,
                image: file_get_contents($url_image),
                type: $type,
                width: $photo['width'],
                height: $photo['height']
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
                $message = sprintf("Hallo %s \u{1F60D}\r\nSchön, von Du hier bist.", Auth::$user);
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
