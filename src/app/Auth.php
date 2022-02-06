<?php

namespace src\app;

class Auth
{

    public static ?int $pk_user = null;
    public static ?string $user = null;

    private static string $sql_login_with_code = <<<EOD
            select * from tbl_logins
            where created > date_add(utc_timestamp(), interval -5 minute)
            and used = 0 and code = ?
        EOD;

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

    private static function getLoginCode(): string
    {
        $code = rand(111111, 999999);
        $logins = Database::select(Auth::$sql_login_with_code, 's', [$code]);
        if (count($logins) > 0) return Auth::createLoginCode();
        return $code;
    }

    public static function createLoginCode(): string
    {
        $sql = <<<EOD
            insert into tbl_logins
                (fk_user, created, code)
                values(?, ?, ?);
        EOD;
        $code = Auth::getLoginCode();
        $parameters = [
            Auth::$pk_user,
            now(),
            $code
        ];
        Database::insert($sql, 'iss', $parameters);
        return $code;
    }

    public static function loadUser(int $pk_user)
    {
        $sql = "select user from tbl_users where pk_user = ?";
        $users = Database::select($sql, 'i', [$pk_user]);
        if (count($users) == 1) {
            Auth::$pk_user = $pk_user;
            Auth::$user = $users[0]['user'];
        }
    }

    private static function discardLogin(int $pk_login)
    {
        $sql = "update tbl_logins set used = 1 where pk_login = ?";
        Database::update_or_delete($sql, 'i', [$pk_login]);
    }

    public static function logInWithCode(string $code)
    {
        $logins = Database::select(Auth::$sql_login_with_code, 's', [$code]);
        if (count($logins) == 1) {
            Auth::loadUser($logins[0]['fk_user']);
            Auth::discardLogin($logins[0]['pk_login']);
        }
    }

}