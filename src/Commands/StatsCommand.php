<?php
namespace Funseco\Chat\Commands;

use Funseco\Chat\Listeners\PlayerProfileListener;
use Funseco\Chat\Support\PlayerParam;
use Funseco\Chat\Support\Text;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Chat\Facades\Chat;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Core\Services\Funseco\ServerAccount;
use Funseco\Core\SharedKernel\Donations;
use Funseco\Core\SharedKernel\RecordBook;
use Funseco\Core\SharedKernel\ServerRanks;
use Funseco\Core\SharedKernel\UI\Window;

/**
 * /stats [login/id]: a card with server, record and ladder statistics of a player.
 * Ladder data comes from the server's detailed player info (zeros for offline players).
 * The Donations row (stored totals, read-only; no billing) only shows on United servers.
 */
class StatsCommand implements Command, Described
{
    use DeclaresCommand;

    protected string $name = 'stats';
    protected string $description = 'Displays statistics of current player';
    protected string $usage = '[login/id]';

    /** Clickable values: ManiaLink action => command it runs. */
    public const CLICKS = [
        'chat_stats_active'  => 'active',
        'chat_stats_top100'  => 'top100',
        'chat_stats_toprecs' => 'toprecs',
        'chat_stats_topwins' => 'topwins',
    ];

    public function handle(Player $player, array $args): void
    {
        $target = $args ? PlayerParam::resolve($player, (string) $args[0], true) : $player;
        if (!$target) {
            return;
        }

        app(ServerAccount::class)->united(function (bool $united) use ($player, $target) {
            if (!$target->isOnline()) {
                $this->show($player, $target, null, $united);
                return;
            }

            \Server::query()->GetDetailedPlayerInfo($target->login, function ($result) use ($player, $target, $united) {
                $info = $result[0] ?? null;
                $this->show($player, $target, is_array($info) && isset($info['Login']) ? $info : null, $united);
            });
        });
    }

    private function show(Player $caller, Player $target, ?array $info, bool $serverUnited = false): void
    {
        $stored = Player::where('login', $target->login)->first() ?? $target;
        $ladder = $info['LadderStats'] ?? [];
        $ranking = $ladder['PlayerRankings'][0] ?? [];
        $wins = (int) ($ladder['NbrMatchWins'] ?? 0);
        $draws = (int) ($ladder['NbrMatchDraws'] ?? 0);
        $losses = (int) ($ladder['NbrMatchLosses'] ?? 0);
        $hours = (int) ($info['HoursSinceZoneInscription'] ?? 0);
        $days = intdiv($hours, 24);
        $zone = (string) ($info['Path'] ?? '');
        $rank = app(ServerRanks::class)->positionOf($stored);

        $played = (int) $stored->time_played + PlayerProfileListener::sessionSeconds($stored->login);

        $targetUnited = (int) ($info['OnlineRights'] ?? 0) === 3;

        $lines = [
            ['Server Date', '{#black}'.date('M d, Y')],
            ['Server Time', '{#black}'.date('H:i:s T')],
            ['Time Played', $this->click('{#black}'.Text::hms($played), 'chat_stats_active')],
            ['Last Online', '{#black}'.($stored->updated_at ? $stored->updated_at->format('Y-m-d H:i') : '')],
            ['Server Rank', $this->click('{#black}'.($rank ? $rank[0].'/'.$rank[1].' Avg: '.sprintf('%4.1F', $rank[2]) : 'None'), 'chat_stats_top100')],
            ['Records', $this->click('{#black}'.$this->records($stored), 'chat_stats_toprecs')],
            ['Races Won', $this->click('{#black}'.(int) $stored->wins, 'chat_stats_topwins')],
            ['Ladder Rank', '{#black}'.Text::thousands((int) ($ranking['Ranking'] ?? 0))],
            ['Ladder Score', '{#black}'.round((float) ($ranking['Score'] ?? 0), 1)],
            ['Last Match', '{#black}'.round((float) ($ladder['LastMatchScore'] ?? 0), 1)],
            ['Wins', '{#black}'.Text::thousands($wins)],
            ['Draws', '{#black}'.Text::thousands($draws).($losses !== 0 ? '   $gW/L: {#black}'.round($wins / $losses, 3) : '')],
            ['Losses', '{#black}'.Text::thousands($losses)],
            ['Zone', '{#black}'.(str_starts_with($zone, 'World|') ? substr($zone, strlen('World|')) : $zone)],
            ['Inscribed', '{#black}'.$days.' day'.($days == 1 ? ' ' : 's ').($hours % 24).' hours'],
            ['Rights', '{#black}'.($targetUnited ? 'United' : 'Nations')],
            ...($serverUnited ? [['Donations', '{#black}'.($targetUnited ? app(Donations::class)->total($stored) : 'N/A')]] : []),
            ['Clan', '{#black}'.((string) $stored->team_name !== '' ? $stored->team_name.'$z' : '<none>')],
            ['Client', '{#black}'.($info['ClientVersion'] ?? '')],
        ];
        if ($caller->allowed('chat_statsip')) {
            $lines[] = ['IP', '{#black}'.($info['IPAddress'] ?? '')];
        }

        Window::show($caller, 'Stats for: '.$stored->nickname.'$z / {#login}'.$stored->login, $lines, [1.0, 0.3, 0.7],
            ['Icons128x128_1', 'Statistics', 0.03], count($lines), card: true);
    }

    /** Maps where the player holds a local record within the counted records. */
    private function records(Player $player): int
    {
        if (!app()->bound(RecordBook::class) || !$player->id) {
            return 0;
        }
        $max = (int) config('chat-commands.recs_per_track', 50);

        return count(array_filter(app(RecordBook::class)->ranksFor($player), fn ($rank) => $rank <= $max));
    }

    /** A value cell that runs a command when clicked, if lists are clickable and the command exists. */
    private function click(string $text, string $action): string|array
    {
        return config('funseco.clickable_lists', true) && Chat::has(self::CLICKS[$action]) ? [$text, $action] : $text;
    }
}
