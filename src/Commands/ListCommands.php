<?php
namespace Funseco\Chat\Commands;

use Funseco\Chat\Support\Text;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Core\SharedKernel\UI\Window;

/**
 * List windows over player data: /clans (online players by clan), /topwins (most races won),
 * /active (most time played), and /lastwin (re-open the last list window).
 * One instance per command name (configured in ChatCommandsServiceProvider).
 */
class ListCommands implements Command, Described
{
    use DeclaresCommand;

    protected string $name;

    public function __construct(private string $command, protected string $description = '')
    {
        $this->name = $command;
    }

    public function handle(Player $player, array $args): void
    {
        match ($this->command) {
            'clans'   => $this->clans($player),
            'topwins' => $this->top($player, 'wins', 'Current TOP 100 Victors:', 0.15, 'no_victors', fn ($p) => (string) (int) $p->wins),
            'active'  => $this->top($player, 'time_played', 'TOP 100 Most Active Players:', 0.25, 'no_active', fn ($p) => Text::hms((int) $p->time_played)),
            'lastwin' => $this->lastwin($player),
        };
    }

    private function lastwin(Player $player): void
    {
        if (!Window::reopenLast($player)) {
            Text::tell($player->login, 'no_lastwin');
        }
    }

    /** Online players by clan name (byte order, colours included); no clan last. */
    private function clans(Player $player): void
    {
        $online = CurrentPlayers::all()->values()->all();
        $logins = array_map(fn ($p) => $p->login, $online);
        $stored = Player::whereIn('login', $logins)->pluck('team_name', 'login');

        $entries = array_map(fn ($p, $i) => [(string) ($stored[$p->login] ?? $p->team_name), $i, $p], $online, array_keys($online));
        usort($entries, fn ($a, $b) => (($a[0] === '') <=> ($b[0] === '')) ?: strcmp($a[0], $b[0]) ?: $a[1] <=> $b[1]);

        $rows = array_map(fn ($e) => [
            $e[0] !== '' ? '{#login}'.$e[0] : '{#login}{#grey}<none>',
            '{#black}'.Text::listNick($e[2]->nickname),
        ], $entries);

        $widths = config('funseco.lists_colornicks', true) ? [1.3, 0.65, 0.65] : [1.1, 0.65, 0.45];
        Window::show($player, 'Online Clans ({#login}clan $g/{#nick} nick$g):', $rows, $widths, ['Icons128x128_1', 'Buddies']);
    }

    /** Top 100 known players by a players column, descending; players with 0 are not listed. */
    private function top(Player $player, string $column, string $header, float $valueWidth, string $none, \Closure $value): void
    {
        $players = Player::where($column, '>', 0)->orderByDesc($column)->orderBy('id')->limit(100)->get();
        if ($players->isEmpty()) {
            Text::tell($player->login, $none);
            return;
        }

        $rows = $players->values()->map(fn ($p, $i) => [Text::rank($i + 1), '{#black}'.Text::listNick($p->nickname), $value($p)])->all();
        $nickWidth = config('funseco.lists_colornicks', true) ? 0.65 : 0.45;

        Window::show($player, $header, $rows, [0.1 + $nickWidth + $valueWidth, 0.1, $nickWidth, $valueWidth], ['BgRaceScore2', 'LadderRank']);
    }
}
