<?php
namespace Funseco\Chat;

use Funseco\Core\Models\Player;

/** Clicks in the /helpme window: close, and the page arrows (admin pages re-check the rights). */
class HelpGuideActions
{
    public function handle(Player $player, string $action): void
    {
        $window = app(HelpGuideWindow::class);

        if ($action === 'help_guide_close') {
            $window->close($player);
        } elseif (preg_match('/^help_guide_(user|admin)_(\d+)$/', $action, $m)) {
            if ($m[1] === 'admin' && !HelpGuideWindow::isStaff($player)) {
                $window->close($player);
                return;
            }
            $window->open($player, $m[1], (int) $m[2]);
        }
    }
}
