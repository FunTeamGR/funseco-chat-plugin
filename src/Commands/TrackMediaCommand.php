<?php
namespace Funseco\Chat\Commands;

use Funseco\Chat\Support\Text;
use Funseco\Chat\Support\TrackMedia;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentMap;
use Funseco\Core\Services\Chat\Facades\Chat;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;

/** /song and /mod [url|loc]: the song or mod the current track's file header names. */
class TrackMediaCommand implements Command, Described
{
    use DeclaresCommand;

    protected string $name;
    protected string $usage = '[url|loc]';

    public function __construct(private string $kind, protected string $description)
    {
        $this->name = $kind;
    }

    public function handle(Player $player, array $args): void
    {
        $map = CurrentMap::get();
        $media = $map ? TrackMedia::of($map) : null;
        $song = $this->kind === 'song';

        if (!$media || ($song ? $media['song_file'] : $media['mod_name']) === '') {
            $text = Text::message($song ? 'no_song' : 'no_mod');
            if ($song && Chat::has('music current')) {
                $text .= Text::message('song_try');
            }
            \Server::query()->ChatSendServerMessageToLogin($text, $player->login);
            return;
        }

        $track = Text::stripColors($map->name);
        $msg = $song
            ? Text::message('song', $track, $media['song_file'])
            : Text::message('mod', $track, $media['mod_name'], $media['mod_file']);
        $url = $song ? $media['song_url'] : $media['mod_url'];
        if (in_array(strtolower((string) ($args[0] ?? '')), ['url', 'loc'], true) && $url !== '') {
            $msg .= "\n".format_colors('{#highlite}$l['.$url.']'.$url.'$l');
        }
        \Server::query()->ChatSendServerMessageToLogin($msg, $player->login);
    }
}
