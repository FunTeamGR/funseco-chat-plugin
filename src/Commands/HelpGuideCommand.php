<?php
namespace Funseco\Chat\Commands;

use Funseco\Chat\HelpGuideWindow;
use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;

/** /helpme (alias /guide): the paged server guide; "/helpme admin" opens the admin guide for staff. */
class HelpGuideCommand implements Command, Described
{
    use DeclaresCommand;

    protected string $name = 'helpme';
    protected string $description = 'Opens the Help Window';
    protected string $usage = '[admin]';
    protected array $aliases = ['guide'];

    public function handle(Player $player, array $args): void
    {
        $window = app(HelpGuideWindow::class);

        if (implode(' ', $args) !== 'admin') {
            $window->open($player, 'user');
        } elseif (HelpGuideWindow::isStaff($player)) {
            $window->open($player, 'admin');
        } else {
            // raw text, no colour conversion
            \Server::query()->ChatSendServerMessageToLogin('$f00> Access Denied. Admins only.', $player->login);
        }
    }
}
