<?php
namespace Funseco\Chat\Commands;

use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\UI\Window;

/**
 * /helpall — chat.help.php showHelp(dispall): window of commands with descriptions.
 */
class HelpAllCommand implements Command, Described
{
    use DeclaresCommand;

    protected string $name = 'helpall';
    protected string $description = 'Displays help for available commands';

    public function handle(Player $player, array $args): void
    {
        $lines = array_map(
            fn ($c) => ['$f00/'.$c['name'], $c['description']],
            HelpCommand::playerCommands($player),
        );

        Window::show($player, 'Currently supported chat commands:', $lines, [1.3, 0.3, 1.0], ['Icons64x64_1', 'TrackInfo', -0.01]);
    }
}
