<?php
namespace Funseco\Chat\Commands;

use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Chat\Support\Text;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\Services\Chat\Facades\Chat;
use Funseco\Core\SharedKernel\Enums\Role;
use Funseco\Core\SharedKernel\UI\Window;

/**
 * /players [filter] — chat.players.php: Id / Nick / Login / Nation window.
 * Ids come from CurrentPlayers::indexed(), the same numbers login-or-id arguments accept;
 * a filter hides rows but keeps each player's id.
 */
class PlayersCommand implements Command, Described
{
    use DeclaresCommand;

    protected string $name = 'players';
    protected string $description = 'Displays current list of nicks/logins';
    protected string $usage = '[filter]';

    private const HEAD = ['Id', '{#nick}Nick $g/{#login} Login', '{#black}Nation'];

    public function handle(Player $player, array $args): void
    {
        $filter = implode(' ', $args);
        $rows = [];

        foreach (CurrentPlayers::indexed() as $id => $pl) {
            if ($filter !== ''
                && stripos(Text::stripColors($pl->nickname), $filter) === false
                && stripos($pl->login, $filter) === false) {
                continue;
            }
            $login = ($pl->effectiveRole() !== Role::Player ? '{#logina}' : '{#login}').$pl->login;
            $rows[] = [
                str_pad((string) $id, 2, '0', STR_PAD_LEFT).'.',
                '{#black}'.str_ireplace('$w', '', (string) $pl->nickname).'$z / '.$login,
                '{#black}'.$pl->nation,
            ];
        }

        if (!$rows) {
            \Server::query()->ChatSendServerMessageToLogin(format_colors('{#server}> {#error}No player(s) found!'), $player->login);
            return;
        }

        // XAseco repeats the column header on every page of 15 players
        $lines = [];
        foreach (array_chunk($rows, 15) as $page) {
            array_push($lines, self::HEAD, ...$page);
        }

        Window::show($player, 'Players On This Server:', $lines, [1.3, 0.1, 0.9, 0.3], ['Icons128x128_1', 'Buddies'], 16);
    }
}
