<?php

namespace src\app;

class Auth
{

    public static ?int $pk_user = null;
    public static ?string $user = null;
    public static ?string $telegram_username = null;
    public static bool $admin = false;
    public static bool $editor = false;
    public static bool $follower = false;

    private static string $sql_login_with_code = <<<EOD
            select * from tbl_logins
            where created > date_add(utc_timestamp(), interval -5 minute)
            and used = 0 and code = ?;
        EOD;

    public static function isKnown(): bool
    {
        return Auth::$pk_user != null;
    }

    public static function isUnknown(): bool
    {
        return Auth::$pk_user == null;
    }

    public static function isAdmin(): bool
    {
        return Auth::$pk_user != null && Auth::$admin;
    }

    public static function isEditor(): bool
    {
        return Auth::$pk_user != null && Auth::$editor;
    }

    public static function isFollower(): bool
    {
        return Auth::$pk_user != null && Auth::$follower;
    }

    public static function logInFromTelegram(string $id, ?string $username)
    {
        $sql = <<<EOD
            select pk_user, user, telegram_username, role from tbl_users
                where telegram_id = ?
                and ( telegram_username = ? or ( telegram_username is null and ? is null) );
        EOD;
        $users = Database::select($sql, 'sss', [$id, $username, $username]);
        if (count($users) == 1) self::loadUserDataFromDatabaseResult($users[0]['pk_user'], $users[0]);
    }

    public static function loadUserDataFromDatabaseResult($pk_user, $user): void
    {
        Auth::$pk_user = $pk_user;
        Auth::$user = $user['user'];
        Auth::$telegram_username = $user['telegram_username'];
        Auth::$admin = ($user['role'] == 'Admin');
        Auth::$editor = ($user['role'] == 'Editor' || Auth::$admin);
        Auth::$follower = ($user['role'] == 'Follower' || Auth::$editor);
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
                values (?, ?, ?);
        EOD;
        $code = Auth::getLoginCode();
        $parameters = [
            Auth::$pk_user,
            now()->format('c'),
            $code
        ];
        Database::insert($sql, 'iss', $parameters);
        return $code;
    }

    public static function loadUser(int $pk_user)
    {
        $sql = "select user, telegram_username, role from tbl_users where pk_user = ?;";
        $users = Database::select($sql, 'i', [$pk_user]);
        if (count($users) == 1) self::loadUserDataFromDatabaseResult($pk_user, $users[0]);
    }

    private static function discardLogin(int $pk_login)
    {
        $sql = "update tbl_logins set used = 1 where pk_login = ?;";
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

    public static function createUser(
        string $user,
        string $telegram_id,
        string $telegram_username)
    {
        $sql = <<<EOD
            insert into tbl_users
                (user, telegram_id, telegram_username)
                values (?, ?, ?);
        EOD;
        $parameters = [
            $user,
            $telegram_id,
            $telegram_username
        ];
        Database::insert($sql, 'sss', $parameters);
    }

    public static function setUserRole(int $pk_user, ?string $role)
    {
        $sql = "update tbl_users set role = ? where pk_user = ?;";
        Database::update_or_delete($sql, 'si', [$role, $pk_user]);
    }

    public static function setUserName(int $pk_user, string $name)
    {
        $sql = "update tbl_users set user = ? where pk_user = ?;";
        Database::update_or_delete($sql, 'si', [$name, $pk_user]);
    }

}
