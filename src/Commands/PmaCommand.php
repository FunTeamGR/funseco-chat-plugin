<?php
namespace Funseco\Chat\Commands;

use Funseco\Chat\Support\PlayerParam;
use Funseco\Chat\Support\PrivateMessage;
use Funseco\Chat\Support\Text;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Core\SharedKernel\Commands\Guarded;

/**
 * /pma <login/id> <message>: a private message to a player that every online chat_pma
 * holder also reads (the sender among them). An admin target receives it once.
 * Not blocked by the global mute list.
 */
class PmaCommand implements Command, Guarded, Described
{
    use DeclaresCommand;

    protected string $name = 'pma';
    protected ?string $ability = 'chat_pma';
    protected string $description = 'Sends a private message to player & admins';
    protected string $usage = '<login/id> <message>';

    public function handle(Player $player, array $args): void
    {
        $target = PlayerParam::resolve($player, (string) array_shift($args));
        if (!$target) {
            return;
        }

        $text = implode(' ', $args);
        if ($text === '') {
            Text::tell($player->login, 'no_message');
            return;
        }

        $admins = CurrentPlayers::all()->filter(fn (Player $p) => $p->allowed('chat_pma'))->values()->all();
        PrivateMessage::send($player, $target, [$target, ...$admins], $text, once: true);
    }
}
