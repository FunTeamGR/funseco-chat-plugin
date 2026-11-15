<?php
namespace Funseco\Chat\Commands;

use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Chat\Support\Text;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Chat\Facades\Chat;

/**
 * /help — chat.help.php: one chat line with the names of the commands the player may use.
 */
class HelpCommand implements Command, Described
{
    use DeclaresCommand;

    protected string $name = 'help';
    protected string $description = 'Shows all available commands';

    public function handle(Player $player, array $args): void
    {
        $names = array_map(fn ($c) => $c['name'], self::playerCommands($player));

        \Server::query()->ChatSendToLogin(
            format_colors('{#interact}Currently supported chat commands:'.PHP_EOL).implode(', ', $names),
            $player->login,
        );

        if (config('funseco.help_explanation', true)) {
            \Server::query()->ChatSendServerMessageToLogin(Text::message('help_explanation'), $player->login);
        }
    }

    /** Non-admin commands the player's role allows ('admin …' is listed by /admin help). */
    public static function playerCommands(Player $player): array
    {
        return array_values(array_filter(
            Chat::commands($player),
            fn ($c) => $c['name'] !== 'admin' && !str_starts_with($c['name'], 'admin '),
        ));
    }
}
