<?php
namespace Funseco\Chat\Support;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentPlayers;

/**
 * XAseco getPlayerParam: a login or a /players number → player, with XAseco's error messages.
 */
class PlayerParam
{
    public static function resolve(Player $author, string $param, bool $offline = false): ?Player
    {
        $target = CurrentPlayers::findByLoginOrIndex($param);

        if (!$target && ctype_digit($param) && (int) $param < 300) {
            self::tell($author, '{#server}> {#error}Player_ID not found! Type {#highlite}$i/players {#error}to see all players.');
            return null;
        }

        if (!$target && $offline) {
            $target = Player::where('login', $param)->first();
        }

        if (!$target) {
            self::tell($author, '{#server}> {#highlite}'.$param.' {#error}is not a valid player! Use {#highlite}$i/players {#error}to find the correct login or Player_ID.');
        }

        return $target;
    }

    private static function tell(Player $author, string $message): void
    {
        \Server::query()->ChatSendServerMessageToLogin(format_colors($message), $author->login);
    }
}
