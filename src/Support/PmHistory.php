<?php
namespace Funseco\Chat\Support;

use Funseco\Core\Models\Player;

/**
 * Each connected player's recent private messages (/pm, /pma) for /pmlog: at most 30,
 * oldest first, kept in memory until they disconnect.
 */
class PmHistory
{
    public const MAX = 30;

    public static function add(string $login, Player $sender, string $text): void
    {
        $entries = [...self::all($login), [
            'time' => date('H:i:s'),
            'nick' => str_ireplace('$w', '', (string) $sender->nickname),
            'text' => $text,
        ]];
        state()->forever(self::key($login), array_slice($entries, -self::MAX));
    }

    /** @return array<int, array{time: string, nick: string, text: string}> */
    public static function all(string $login): array
    {
        return state()->get(self::key($login), []);
    }

    public static function forget(string $login): void
    {
        state()->forget(self::key($login));
    }

    private static function key(string $login): string
    {
        return 'chat:pmlog:'.$login;
    }
}
