<?php

namespace src\app;

class Telegram
{

    private int $id;
    private string $username;
    private array $questions = array(
        'name' => "Wie darf ich Dich nennen?",
        'code' => "Wie lautet der Code?"
    );

    function __construct(string $id, string $username)
    {
        $this->checkIfIpIsFromTelegram($_SERVER["REMOTE_ADDR"]);
        $this->id = $id;
        $this->username = $username;
        Auth::logInFromTelegram($this->id, $this->username);
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

    private function sendNotAllowedInfo()
    {
        $this->sendMessageToSender("Die Funktion ist nicht für Dich freigeschaltet. \u{1F6B7}");
    }

    private function sendUnknownInfo()
    {
        if (Auth::isKnown()) $this->sendMessageToSender("Sorry, damit kann ich noch nichts anfangen. \u{1F635}");
    }

    private function sendNameQuestion($message)
    {
        $this->sendMessageToSender($message);
        $this->sendMessageToSender($this->questions['name'], reply_field: 'Dein Name');
    }

    public function receivePhoto(array $photo_list)
    {
        if (Auth::isEditor()) {
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
        } else $this->sendNotAllowedInfo();
    }

    public function receiveCommand(string $text)
    {
        if (str_starts_with($text, '/login')) {
            if (Auth::isEditor()) {
                $this->sendMessageToSender(sprintf("Hallo %s \u{1F60D}", Auth::$user));
                $this->sendMessageToSender($this->questions['code'], reply_field: 'Code');
            } else $this->sendNotAllowedInfo();
        } elseif (str_starts_with($text, '/start')) {
            if (Auth::isKnown()) {
                $message = sprintf("Hallo %s \u{1F60D}\r\nSchön, dass Du vorbei schaust.", Auth::$user);
                $this->sendMessageToSender($message);
            } else $this->sendNameQuestion('Ich kenne Dich noch nicht.');
        } elseif (str_starts_with($text, '/follow')) {
            if (Auth::isKnown()) {
                if (!Auth::isFollower()) {
                    $this->follow();
                } else $this->sendMessageToSender("Du folgst uns bereits. \u{1F60D}");
            } else $this->sendNameQuestion('Ich kenne Dich noch nicht.');
        } elseif (str_starts_with($text, '/stop')) {
            if (Auth::isKnown()) {
                if (Auth::isFollower()) {
                    if (Auth::isEditor() || Auth::isAdmin()) {
                        $this->sendMessageToSender("Das geht nicht, Du bist zu wichtig. \u{1F92A}");
                    } else {
                        Auth::setUserRole(Auth::$pk_user, null);
                        $this->sendMessageToSender("Du folgst uns jetzt nicht mehr. \u{1F641}");
                    }
                } else $this->sendMessageToSender("Du folgst uns bereits nicht mehr. \u{1F641}");
            } else $this->sendNameQuestion('Ich kenne Dich noch nicht.');
        } elseif (str_starts_with($text, '/name')) {
            if (Auth::isKnown()) {
                $this->sendNameQuestion('Du möchtest Deinen Namen ändern.');
            } else $this->sendNameQuestion('Ich kenne Dich noch nicht.');
        } else {
            if (Auth::isUnknown()) $this->sendNameQuestion('Ich kenne Dich noch nicht.');
            $this->sendUnknownInfo();
        }
    }

    private function isQuestion(string $replay_question, string $question): bool
    {
        return (preg_replace("/[^A-Za-z0-9 ]/", '', $replay_question) == preg_replace("/[^A-Za-z0-9 ]/", '', ($this->questions[$question])));
    }

    public function receiveTextReply(string $text, string $question)
    {
        if ($this->isQuestion($question, 'code')) {
            if ($text == $_ENV['CMS_CODE']) {
                $code = Auth::createLoginCode();
                $message = sprintf("Hier ist Dein Anmeldecode: ```%s```", $code);
                $message .= sprintf("\r\n\r\n%s/login/%s", URL, $code);
                $this->sendMessageToSender($message);
            }
        } else if ($this->isQuestion($question, 'name')) {
            if (strlen($text) > 100) {
                $this->sendNameQuestion('Dein Name ist leider zu lang. Er sollte maximal 100 Zeichen haben.');
            } else {
                if (Auth::isUnknown()) {
                    Auth::createUser($text, $this->id, $this->username);
                    $message = sprintf("Hallo %s, nett Dich kennen zu lernen. \u{1F44A} Ich bin der Bot von Jessi und Heikos Hausblog. \u{1F916} Möchtest Du uns folgen und bei neuen Einträgen eine Info von mir bekommen?", $text);
                    $buttons = array(array(
                        array('text' => 'Ja, ich möchte.', 'callback_data' => Telegram::getCallbackButton('follow', 'yes')),
                        array('text' => 'Nein', 'callback_data' => Telegram::getCallbackButton('follow', 'no'))
                    ));
                    $this->sendMessageToSender($message, buttons: $buttons);
                } else {
                    Auth::setUserName(Auth::$pk_user, $text);
                    $message = sprintf("Ab sofort nenne ich Dich %s. \u{1F642}", $text);
                    $this->sendMessageToSender($message);
                }
            }
        } else $this->sendUnknownInfo();
    }

    public function receiveText(string $text)
    {
        if (Auth::isUnknown()) $this->sendNameQuestion('Ich kenne Dich noch nicht.');
        $this->sendUnknownInfo();
    }

    public function receive()
    {
        if (Auth::isUnknown()) $this->sendNameQuestion('Ich kenne Dich noch nicht.');
        $this->sendUnknownInfo();
    }

    public function receiveButton($data)
    {

        $function = $data->function;
        $value = $data->value;
        if ($function == 'unlock_comment' && Auth::isEditor()) {
            $rows = Comments::publishComment($value);
            if ($rows > 0) {
                foreach (Telegram::getAllEditors() as $user) {
                    Telegram::sendMessage($user['telegram_id'], "\u{2705} Kommentar freigeschaltet");
                }
            }
        } elseif ($function == 'delete_comment' && Auth::isEditor()) {
            $rows = Comments::deleteComment($value);
            if ($rows > 0) {
                foreach (Telegram::getAllEditors() as $user) {
                    Telegram::sendMessage($user['telegram_id'], "\u{274c} Kommentar gelöscht");
                }
            }
        } elseif ($function == 'follow' && Auth::isKnown()) {
            if ($value == 'yes') $this->follow();
            else $this->sendMessageToSender('Ok, kein Problem. Wenn Du uns doch folgen möchtest, sende einfach /follow.');
        }
    }

    private function sendMessageToSender(string $message, bool $markdown = true, array $buttons = null, $reply_field = null)
    {
        Telegram::sendMessage($this->id, $message, $markdown, $buttons, $reply_field);
    }

    public static function sendMessage(string $chat_id, string $message, bool $markdown = true, array $buttons = null, $reply_field = null, $web_page_preview = false)
    {
        $url = Telegram::getApiUrl() . 'sendMessage';
        $encodedMarkup = json_encode(array('inline_keyboard' => $buttons));
        $data = array(
            'chat_id' => $chat_id,
            'text' => str_replace('_', '-', $message),
            'disable_web_page_preview' => !$web_page_preview
        );
        if ($buttons != null) {
            $data['reply_markup'] = $encodedMarkup;
        }
        if ($markdown) $data['parse_mode'] = 'Markdown';
        if ($reply_field != null) {
            $data['reply_markup'] = json_encode(array(
                'force_reply' => true,
                'input_field_placeholder' => $reply_field
            ));
        }
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

    public static function getAllEditors()
    {
        $sql = "select user, telegram_id from tbl_users where telegram_id is not null and role in ('Admin','Editor')";
        return Database::select($sql);
    }

    public static function getAllFollower()
    {
        $sql = "select user, telegram_id from tbl_users where telegram_id is not null and role is not null";
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
            array('text' => 'Löschen', 'callback_data' => Telegram::getCallbackButton('delete_comment', strval($pk_comment)))
        ));
        foreach (Telegram::getAllEditors() as $user) {
            $m = 'Hallo ' . $user['user'] . $message;
            Telegram::sendMessage($user['telegram_id'], $m, buttons: $buttons);
        }
    }

    private function follow(): void
    {
        Auth::setUserRole(Auth::$pk_user, 'Follower');
        $message = sprintf("Schön, dass Du uns folgst %s. \u{1F60D}\r\n\r\n\u{2139} Du kannst das jederzeit beenden, indem Du /stop sendest. Möchtest Du dann erneut folgen, sende einfach wieder /follow. Übrigens kannst Du auch Deinen Namen ändern, indem Du /name sendest. ", Auth::$user);
        $this->sendMessageToSender($message);
        foreach (Telegram::getAllEditors() as $user) {
            $m = 'Hallo ' . $user['user'] . ", es gib einen neuen Follower. \u{1F680}" . sprintf("\r\n\r\nName: %s\r\nUsername Telegram: %s", Auth::$user, Auth::$telegram_username);
            Telegram::sendMessage($user['telegram_id'], $m);
        }
    }

}
