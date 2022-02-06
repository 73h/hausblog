<?php

namespace src\app;

class Auth
{

    public static ?int $pk_user = null;
    public static ?string $user = null;

    public static function isLoggedIn(): bool
    {
        return Auth::$pk_user != null;
    }

    public static function logInFromTelegram(string $id, string $username)
    {
        $sql = <<<EOD
            select pk_user, user from tbl_users
                where telegram_id = ?
                and telegram_username = ?
        EOD;
        $users = Database::select($sql, 'ss', [$id, $username]);
        if (count($users) == 1) {
            Auth::$pk_user = $users[0]['pk_user'];
            Auth::$user = $users[0]['user'];
        }
    }
}