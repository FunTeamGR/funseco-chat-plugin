<?php
namespace Funseco\Chat\Listeners;

use Funseco\Chat\Support\Text;
use Funseco\Core\SharedKernel\Events\PlayerWon;

/** Congratulates a race winner: everyone on every global_win_multiple-th win, else just them. */
class WinMessagesListener
{
    public function handle(PlayerWon $event): void
    {
        $multiple = (int) config('funseco.global_win_multiple', 50);

        if ($multiple > 0 && $event->wins % $multiple === 0) {
            \Server::query()->ChatSendServerMessage(Text::message('win_global', Text::stripColors($event->player->nickname), $event->wins));
            return;
        }

        Text::tell($event->player->login, 'win', $event->wins);
    }
}
