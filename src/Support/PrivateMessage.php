<?php
namespace Funseco\Chat\Support;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Events\PrivateMessageSent;

/** Sends a /pm or /pma line to its recipients and records it in their PM history. */
class PrivateMessage
{
    /**
     * @param Player[] $recipients in order; with $once, a login listed twice receives it once
     */
    public static function send(Player $sender, Player $target, array $recipients, string $text, bool $once = false): void
    {
        $line = format_colors('{#error}-pm-$g['.str_ireplace('$w', '', (string) $sender->nickname).'$z$s$i->'
            .str_ireplace('$w', '', (string) $target->nickname).'$z$s$i]$i {#interact}'.$text);

        $logins = [];
        foreach ($recipients as $recipient) {
            if ($once && in_array($recipient->login, $logins, true)) {
                continue;
            }
            $logins[] = $recipient->login;
            \Server::query()->ChatSendServerMessageToLogin($line, $recipient->login);
            PmHistory::add($recipient->login, $sender, $text);
        }

        event(new PrivateMessageSent($sender, array_values(array_unique($logins)), $line));
    }
}
