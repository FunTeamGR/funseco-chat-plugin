<?php
namespace Funseco\Chat\Commands;

use Funseco\Chat\Support\PmHistory;
use Funseco\Chat\Support\Text;
use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Core\SharedKernel\UI\Window;

/** /pmlog: the caller's recent private messages, oldest first, long ones wrapped. */
class PmLogCommand implements Command, Described
{
    use DeclaresCommand;

    protected string $name = 'pmlog';
    protected string $description = 'Displays log of your recent private messages';

    private const WRAP = 70;
    private const CUT = 73;

    public function handle(Player $player, array $args): void
    {
        $history = PmHistory::all($player->login);
        if (!$history) {
            Text::tell($player->login, 'no_pmlog');
            return;
        }

        $times = (bool) config('funseco.chatpmlog_times', true);
        $rows = [];
        foreach ($history as $entry) {
            $prefix = '$z'.($times ? '<{#server}'.$entry['time'].'$z> ' : '').'[{#black}'.$entry['nick'].'$z] ';
            foreach (self::pieces($entry['text']) as $piece) {
                $rows[] = [$prefix.$piece];
            }
        }

        Window::show($player, 'Your recent PM history:', $rows, [1.2], ['Icons64x64_1', 'Outbox']);
    }

    /** Colours stripped, wrapped at 70 without splitting words, continuations start with "...", each cut to 73. */
    public static function pieces(string $text): array
    {
        $wrapped = wordwrap(Text::stripColors($text), self::WRAP, "\n...", false);

        return array_map(fn ($piece) => mb_substr($piece, 0, self::CUT), explode("\n", $wrapped));
    }
}
