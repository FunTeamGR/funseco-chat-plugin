<?php
namespace Funseco\Chat\Commands;

use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Chat\Support\PlayerParam;
use Funseco\Chat\Support\Text;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentMap;

/**
 * Small one-line info commands (plugin.track.php, chat.wins.php, chat.laston.php,
 * plugin.rasp_nextmap.php). Registered per command through container bindings:
 * 'chat.info.<name>' → new InfoCommands('<name>').
 */
/**
 * One instance per command name (configured in ChatCommandsServiceProvider).
 */
class InfoCommands implements Command, Described
{
    use DeclaresCommand;
    public const MAP_STARTED_AT = 'chat:mapStartedAt';

    public function __construct(
        private string $command,
        protected string $description = '',
        protected string $usage = '',
    ) {
        $this->name = $command;
    }

    protected string $name;

    public function handle(Player $player, array $args): void
    {
        $message = match ($this->command) {
            'time'     => Text::message('time', date('H:i:s T'), date('Y/M/d')),
            'track'    => $this->track(),
            'playtime' => $this->playtime(),
            'wins'     => $this->wins($player),
            'laston'   => $this->laston($player, $args),
            'nextmap'  => $this->nextmap($player),
        };

        if ($message !== null) {
            self::tell($player, $message);
        }
    }

    private function track(): ?string
    {
        $map = CurrentMap::get();
        if (!$map) {
            return null;
        }

        return Text::message('track', Text::stripColors($map->name), $map->author,
            format_score($map->author_time), format_score($map->gold_time),
            format_score($map->silver_time), format_score($map->bronze_time), $map->copper_price);
    }

    private function playtime(): ?string
    {
        $map = CurrentMap::get();
        if (!$map) {
            return null;
        }
        $played = time() - (int) state()->get(self::MAP_STARTED_AT, time());

        return Text::message('playtime', Text::stripColors($map->name), Text::timeH($played * 1000));
    }

    private function wins(Player $player): string
    {
        $wins = (int) (Player::where('login', $player->login)->value('wins') ?? $player->wins);

        return Text::message('wins', $wins, $wins == 1 ? '.' : ($wins > 1 ? 's!' : 's.'));
    }

    private function laston(Player $player, array $args): ?string
    {
        $target = $player;
        if ($args) {
            $target = PlayerParam::resolve($player, $args[0], true);
            if (!$target) {
                return null;
            }
        }
        $updated = Player::where('login', $target->login)->value('updated_at');

        return format_colors('{#server}> Player {#highlite}'.$target->nickname
            .'$z$s{#server} was last online on: {#highlite}'
            .($updated ? date('Y-m-d H:i', strtotime((string) $updated)) : ''));
    }

    private function nextmap(Player $player): null
    {
        \Server::query()->GetNextChallengeIndex(function ($result) use ($player) {
            \Server::query()->GetChallengeList(1, (int) ($result[0] ?? 0), function ($result) use ($player) {
                $track = $result[0][0] ?? null;
                if (!$track) {
                    return;
                }
                $name = Text::stripColors(str_replace(["\n", "\r"], '', $track['Name']));
                $env = $track['Environnement'] ?? 'Stadium';
                self::tell($player, $env === 'Stadium'
                    ? Text::message('nextmap', $name)
                    : Text::message('nextenvmap', $env, $name));
            });
        });

        return null;
    }

    private static function tell(Player $player, string $message): void
    {
        \Server::query()->ChatSendServerMessageToLogin($message, $player->login);
    }
}
