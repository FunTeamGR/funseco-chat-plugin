<?php
namespace Funseco\Chat\Listeners;

use Funseco\Chat\Support\PmHistory;
use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Events\PlayerConnected;
use Funseco\Core\SharedKernel\Events\PlayerDisconnected;
use Funseco\Core\SharedKernel\Values\Nation;

/**
 * Keeps the player row current on connect (last online, nickname, nation, clan) from the
 * server's detailed player info, and remembers when the session started (/stats time played).
 */
class PlayerProfileListener
{
    public function connected(PlayerConnected $event): void
    {
        $player = $event->player;
        state()->forever(self::sessionKey($player->login), time());
        if ($player->exists) {
            $player->touch(); // last online = now (also read by /laston)
        }

        \Server::query()->GetDetailedPlayerInfo($player->login, function ($result) use ($player) {
            $info = $result[0] ?? null;
            if (is_array($info) && isset($info['Login'])) {
                self::update($player, $info);
            }
        });
    }

    public function disconnected(PlayerDisconnected $event): void
    {
        if ($event->player->login === '') {
            return;
        }
        state()->forget(self::sessionKey($event->player->login));
        PmHistory::forget($event->player->login);
    }

    /** Nickname, nation (from the zone path) and clan; an empty clan keeps the stored one. */
    public static function update(Player $player, array $info): void
    {
        $player->nickname = (string) ($info['NickName'] ?? $player->nickname);
        $player->nation = Nation::fromZone($info['Path'] ?? '');
        $clan = (string) ($info['LadderStats']['TeamName'] ?? '');
        if ($clan !== '') {
            $player->team_name = $clan;
        }
        $player->save();
    }

    /** Seconds in the current session, 0 when not connected. */
    public static function sessionSeconds(string $login): int
    {
        $since = state()->get(self::sessionKey($login));

        return $since === null ? 0 : max(0, time() - (int) $since);
    }

    private static function sessionKey(string $login): string
    {
        return 'chat:connectedAt:'.$login;
    }
}
