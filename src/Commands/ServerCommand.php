<?php
namespace Funseco\Chat\Commands;

use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Chat\Support\Text;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\ServerInfo;
use Funseco\Core\SharedKernel\UI\Window;

/**
 * /server — chat.server.php (TMF): server info window.
 */
class ServerCommand implements Command, Described
{
    use DeclaresCommand;

    protected string $name = 'server';
    protected string $description = 'Displays info about this server';

    private const MODES = ['Rounds', 'TimeAttack', 'Team', 'Laps', 'Stunts', 'Cup'];

    public function handle(Player $player, array $args): void
    {
        $data = [];
        $calls = [
            ['GetNetworkStats', []],
            ['GetCurrentGameInfo', [1]],
            ['GetCallVoteTimeOut', []],
            ['GetCallVoteRatio', []],
            ['GetLadderServerLimits', []],
        ];

        // one call after the other on the event loop, then show the window
        $next = function () use (&$next, &$calls, &$data, $player) {
            if (!$calls) {
                $this->show($player, $data);
                return;
            }
            [$method, $params] = array_shift($calls);
            \Server::query()->$method(...[...$params, function ($result) use ($method, &$data, $next) {
                $data[$method] = $result[0] ?? null;
                $next();
            }]);
        };
        $next();
    }

    private function show(Player $player, array $data): void
    {
        $info = ServerInfo::get();
        $game = $data['GetCurrentGameInfo'] ?? [];
        $mode = $game['GameMode'] ?? ServerInfo::getGameMode();
        $stats = [];

        $stats[] = ['Server Date', '{#black}'.date('M d, Y')];
        $stats[] = ['Server Time', '{#black}'.date('H:i:s T')];
        $field = 'Comment';
        foreach (explode(PHP_EOL, wordwrap((string) ($info['server_comment'] ?? ''), 35, PHP_EOL.'...')) as $line) {
            $stats[] = [$field, '{#black}'.$line];
            $field = '';
        }
        $stats[] = ['Uptime', '{#black}'.Text::days((int) ($data['GetNetworkStats']['Uptime'] ?? 0))];
        $stats[] = ['Track Count', '{#black}'.($game['NbChallenge'] ?? '?')];
        $stats[] = ['Game Mode', '{#black}'.(self::MODES[$mode] ?? 'Undefined')];
        $limit = match ($mode) {
            0 => ['Points Limit', '{#black}'.($game['RoundsPointsLimit'] ?? '')],
            1 => ['Time Limit', '{#black}'.format_score($game['TimeAttackLimit'] ?? 0)],
            2 => ['Points Limit', '{#black}'.($game['TeamPointsLimit'] ?? '')],
            3 => ['Time Limit', '{#black}'.format_score($game['LapsTimeLimit'] ?? 0)],
            4 => ['Time Limit', '{#black}'.format_score(5 * 60 * 1000)],
            5 => ['Points Limit', '{#black}'.($game['CupPointsLimit'] ?? '').'$g   R/C: {#black}'.($game['CupRoundsPerChallenge'] ?? '')],
            default => null,
        };
        if ($limit) {
            $stats[] = $limit;
        }
        $stats[] = ['Max Players', '{#black}'.($info['max_players'] ?? '?')];
        $stats[] = ['Max Specs', '{#black}'.($info['max_spectators'] ?? '?')];
        $stats[] = ['Recs/Track', '{#black}'.config('chat-commands.recs_per_track')];
        if (config('chat-commands.feature_votes')) {
            $stats[] = ['Voting info', '{#black}/helpvote'];
        } else {
            $stats[] = ['Vote Timeout', '{#black}'.format_score($data['GetCallVoteTimeOut']['CurrentValue'] ?? 0)];
            $stats[] = ['Vote Ratio', '{#black}'.round((float) ($data['GetCallVoteRatio'] ?? 0), 2)];
        }
        $stats[] = ['Rights', '{#black}Nations'];
        $ladder = $data['GetLadderServerLimits'] ?? [];
        $stats[] = ['Ladder Limits', '{#black}'.($ladder['LadderServerLimitMin'] ?? 0).'$g - {#black}'.($ladder['LadderServerLimitMax'] ?? 0)];
        if ($contact = config('chat-commands.admin_contact')) {
            $stats[] = ['Admin Contact', '{#black}'.$contact];
        }

        $totals = Player::query()->selectRaw('COUNT(id) as players, COUNT(DISTINCT nation) as nations, SUM(time_played) as played')->first();
        $stats[] = [];
        $stats[] = ['Visited by $f80'.(int) $totals->players.' $gPlayers from $f40'.(int) $totals->nations.' $gNations'];
        $stats[] = ['who together played: {#black}'.Text::days((int) $totals->played).' $g!'];

        Window::show($player, 'Welcome to: '.($info['server_name'] ?? ''), $stats, [1.0, 0.3, 0.7], ['Icons64x64_1', 'DisplaySettings', 0.01], 50);
    }
}
