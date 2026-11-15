<?php
namespace Funseco\Chat\Commands;

use Funseco\Chat\Support\Text;
use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;

/** Small canned commands: /official, /statsall, /asmomsg. */
class MiscCommands implements Command, Described
{
    use DeclaresCommand;

    protected string $name;

    public function __construct(private string $command, protected string $description = '', protected string $usage = '')
    {
        $this->name = $command;
    }

    public function handle(Player $player, array $args): void
    {
        match ($this->command) {
            'official' => Text::tell($player->login, 'official'),
            'statsall' => Text::tell($player->login, 'statsall'),
            'asmomsg'  => $this->asmomsg($player, $args),
        };
    }

    /** Broadcast the text (bold) as the server; {1} is the caller's nickname. */
    private function asmomsg(Player $player, array $args): void
    {
        $text = Text::format(implode(' ', $args), $player->nickname);
        \Server::query()->ChatSendServerMessage(format_colors('$b'.$text));
    }
}
