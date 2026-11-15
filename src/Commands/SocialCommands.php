<?php
namespace Funseco\Chat\Commands;

use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Chat\Support\PlayerParam;
use Funseco\Chat\Support\PrivateMessage;
use Funseco\Chat\Support\Text;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentSpectators;
use Funseco\Core\SharedKernel\MuteList;

/**
 * Chat between players (plugin.rasp_chat.php, chat.me.php): /pm, /me, /bootme, /afk and
 * the preset messages (/hi /bye /thx /lol /lool /brb /gg /gr /n1 /bgm).
 * One instance per command name (configured in ChatCommandsServiceProvider).
 */
class SocialCommands implements Command, Described
{
    use DeclaresCommand;
    public function __construct(
        private string $command,
        protected string $description = '',
        protected string $usage = '',
    ) {
        $this->name = $command;
    }

    protected string $name;

    public function handle(Player $player, array $args): void
    {
        // XAseco: everything but /pm and /bootme is refused for globally muted players.
        if (!in_array($this->command, ['pm', 'bootme'], true) && app(MuteList::class)->isGloballyMuted($player)) {
            \Server::query()->ChatSendServerMessageToLogin(Text::message('muted', '/'.$this->command), $player->login);
            return;
        }

        match ($this->command) {
            'pm'     => $this->pm($player, $args),
            'me'     => $this->broadcast(Text::format('$i{1}$z$s$i {#emotic}{2}', $player->nickname, implode(' ', $args))),
            'bootme' => $this->bootme($player),
            'afk'    => $this->afk($player),
            default  => $this->preset($player, $args),
        };
    }

    private function pm(Player $player, array $args): void
    {
        $target = PlayerParam::resolve($player, (string) array_shift($args));
        if (!$target) {
            return;
        }

        $text = implode(' ', $args);
        if ($text === '') {
            \Server::query()->ChatSendServerMessageToLogin(format_colors('{#server}> {#error}No message!'), $player->login);
            return;
        }

        PrivateMessage::send($player, $target, [$target, $player], $text);
    }

    private function bootme(Player $player): void
    {
        $this->broadcast(Text::message('bootme', $player->nickname));
        $dialog = config('chat-commands.messages.bootme_dialog');
        \Server::query()->Kick($player->login, $dialog ? format_colors($dialog.'$z') : '');
    }

    private function afk(Player $player): void
    {
        $messages = config('chat-commands.afk');
        $message = array_rand($messages);
        $this->broadcast('$g['.$player->nickname.'$z$s] {#interact}'.$message.' !');
        \Server::query()->ChatSendServerMessage($messages[$message]);

        if (!config('funseco.afk_force_spec')) {
            return;
        }
        if (!CurrentSpectators::has($player->login)) {
            // force into spectator, then let the player switch back
            \Server::query()->ForceSpectator($player->login, 1, function ($result) use ($player) {
                if ($result[0] ?? false) {
                    \Server::query()->ForceSpectator($player->login, 0);
                }
            });
        }
        // free camera, and free up the player slot
        \Server::query()->ForceSpectatorTarget($player->login, '', 2);
        \Server::query()->SpectatorReleasePlayerSlot($player->login);
    }

    private function preset(Player $player, array $args): void
    {
        [, $plain, $withArg] = config("chat-commands.presets.{$this->command}");
        $param = implode(' ', $args);
        $text = ($param !== '' && $withArg) ? Text::format($withArg, $param) : $plain;

        $this->broadcast('$g['.$player->nickname.'$z$s] {#interact}'.$text);
    }

    private function broadcast(string $message): void
    {
        \Server::query()->ChatSendServerMessage(format_colors($message));
    }
}
