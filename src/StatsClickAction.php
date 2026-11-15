<?php
namespace Funseco\Chat;

use Funseco\Chat\Commands\StatsCommand;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Chat\Facades\Chat;

/** Click on a /stats value: runs the list command behind it, as if typed. */
class StatsClickAction
{
    public function handle(Player $player, string $action): void
    {
        if (isset(StatsCommand::CLICKS[$action])) {
            Chat::run($player, StatsCommand::CLICKS[$action]);
        }
    }
}
