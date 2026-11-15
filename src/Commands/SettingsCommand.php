<?php
namespace Funseco\Chat\Commands;

use Funseco\Chat\Support\PlayerParam;
use Funseco\Chat\Support\Text;
use Funseco\Core\Models\Player;
use Funseco\Core\Models\PlayerExtra;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Core\SharedKernel\Enums\Role;
use Funseco\Core\SharedKernel\UI\Window;

/**
 * /settings [login/id]: the player's checkpoint settings, window style and panels.
 * Only callers with ability chat_settings may look at someone else's.
 * Style and panels: until the menu & looks area exists, the stored style or the configured
 * defaults (chat-commands.settings) are shown.
 */
class SettingsCommand implements Command, Described
{
    use DeclaresCommand;

    protected string $name = 'settings';
    protected string $description = 'Displays your personal settings';
    protected string $usage = '[login/id]';

    public function handle(Player $player, array $args): void
    {
        $target = $player;
        if ($args && $player->allowed('chat_settings')) {
            $target = PlayerParam::resolve($player, (string) $args[0], true);
            if (!$target) {
                return;
            }
        }

        $groups = array_filter([$this->checkpoints($target), $this->style($target), $this->panels($target)]);
        if (!$groups) {
            Text::tell($player->login, 'no_settings');
            return;
        }

        $lines = [];
        foreach (array_values($groups) as $i => $group) {
            if ($i > 0) {
                $lines[] = [];
            }
            array_push($lines, ...$group);
        }

        Window::show($player, 'Settings for: '.$target->nickname.'$z / {#login}'.$target->login, $lines, [1.0, 0.3, 0.7],
            ['Icons128x128_1', 'Inputs', 0.03], count($lines), card: true);
    }

    private function checkpoints(Player $target): array
    {
        $extra = $target->id ? PlayerExtra::where('player_id', $target->id)->first() : null;

        return $extra ? [
            ['Local CPS', '{#black}'.(int) $extra->cps],
            ['Dedimania CPS', '{#black}'.(int) $extra->dedi],
        ] : [];
    }

    private function style(Player $target): array
    {
        return [['Window Style', '{#black}'.app(\Funseco\Core\Services\Looks\PlayerLooks::class)->styleName($target)]];
    }

    private function panels(Player $target): array
    {
        $looks = app(\Funseco\Core\Services\Looks\PlayerLooks::class);

        $lines = [];
        foreach (['Admin', 'Donate', 'Records', 'Vote'] as $kind) {
            if ($kind === 'Admin' && $target->effectiveRole() === Role::Player) {
                continue;
            }
            $name = $looks->panel($target, strtolower($kind));
            $lines[] = [$kind.' Panel', '{#black}'.(str_starts_with($name, $kind) ? substr($name, strlen($kind)) : $name)];
        }

        return $lines;
    }
}
