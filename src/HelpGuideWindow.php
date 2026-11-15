<?php
namespace Funseco\Chat;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Chat\Facades\Chat;
use Funseco\Core\Services\ManiaLink\Facades\ManiaLink;
use Funseco\Core\SharedKernel\Enums\Role;

/**
 * better_help: the two-mode paged guide window of /helpme and /guide (user 4 pages, admin 5).
 * Text comes from config('chat-commands.help_guide'). Sent to one player, never auto-closed,
 * no page persistence; arrows stay inside the mode that was opened.
 */
class HelpGuideWindow
{
    public const MODES = ['user' => 4, 'admin' => 5];

    /** Registered ManiaLink action names (id 'help_guide' is the window's manialink id). */
    public static function actions(): array
    {
        $names = ['help_guide', 'help_guide_close'];
        foreach (self::MODES as $mode => $pages) {
            for ($page = 1; $page <= $pages; $page++) {
                $names[] = "help_guide_{$mode}_$page";
            }
        }

        return $names;
    }

    public static function isStaff(Player $player): bool
    {
        return $player->effectiveRole()->allows(Role::Operator);
    }

    public function open(Player $player, string $mode, int $page = 1): void
    {
        $guide = (array) config("chat-commands.help_guide.$mode", []);
        $pages = (array) ($guide['pages'] ?? []);
        if (!$pages) {
            return;
        }
        $total = count($pages);
        $page = max(1, min($total, $page));

        $e = fn (string $text) => htmlspecialchars(clean_utf8($text));
        $id = ManiaLink::getAction('help_guide');

        $xml = '<manialink id="'.$id.'"><frame posn="-45 35 0" scale="0.9">'
            .'<quad posn="0 0 1" sizen="100 6" style="Bgs1InRace" substyle="BgTitle3_3"/>'
            .'<label posn="50 -1.5 2" halign="center" textsize="2" text="'.$e('$o'.($guide['title'] ?? '').' (Pg '.$page.'/'.$total.')').'"/>'
            .'<quad posn="0 -6 0" sizen="100 58" style="Bgs1InRace" substyle="BgList"/>'
            .'<quad posn="0 -64 1" sizen="100 8" style="Bgs1InRace" substyle="BgTitle3_3"/>'
            .'<quad posn="47 -65 3" sizen="6 6" style="Icons64x64_1" substyle="Close" action="'.ManiaLink::getAction('help_guide_close').'"/>';

        if ($page > 1) {
            $xml .= '<quad posn="2 -65 3" sizen="6 6" style="Icons64x64_1" substyle="ArrowPrev" action="'.ManiaLink::getAction('help_guide_'.$mode.'_'.($page - 1)).'"/>';
        }
        if ($page < $total) {
            $xml .= '<quad posn="92 -65 3" sizen="6 6" style="Icons64x64_1" substyle="ArrowNext" action="'.ManiaLink::getAction('help_guide_'.$mode.'_'.($page + 1)).'"/>';
        }

        foreach ($pages[$page - 1] as $column => [$heading, $entries]) {
            $x = $column === 0 ? 2 : 52;
            foreach ($this->lines($mode, $heading, (array) $entries) as $i => $line) {
                if ($line === '') {
                    continue;
                }
                $xml .= '<label posn="'.$x.' '.(-8 - $i * 3.5).' 1" sizen="48 4" text="'.$e($line).'"/>';
            }
        }

        ManiaLink::displayLogin($player, $xml.'</frame></manialink>', 0, false);
    }

    public function close(Player $player): void
    {
        ManiaLink::displayLogin($player, '<manialink id="'.ManiaLink::getAction('help_guide').'"></manialink>', 0, false);
    }

    /** @return string[] one column: heading, (admin: the red "/admin ..." line), then the entries */
    private function lines(string $mode, string $heading, array $entries): array
    {
        $lines = ['$o'.$heading.'$o'];
        if ($mode === 'admin') {
            $lines[] = '$f00/admin ...';
        }

        foreach ($entries as $entry) {
            [$cmds, $description] = array_pad(explode('|', (string) $entry, 2), 2, '');
            if ($this->missing($mode, $cmds)) {
                continue;
            }
            if ($cmds === '') {
                $lines[] = '';
            } elseif ($cmds[0] === '#') {
                $lines[] = '$o'.substr($cmds, 1).'$o';
            } elseif ($mode === 'admin') {
                $lines[] = $this->adminLine($cmds, $description);
            } else {
                $lines[] = preg_replace_callback('#/\w+#', fn ($m) => '$fff'.$m[0].'$z', $cmds).($description !== '' ? ' - '.$description : '');
            }
        }

        return $lines;
    }

    /** "$fff name$z" per name (leading space), separators kept: "kick / kickghost". */
    private function adminLine(string $names, string $description): string
    {
        return preg_replace_callback('/[a-z0-9]+/i', fn ($m) => '$fff '.$m[0].'$z', $names).($description !== '' ? ' - '.$description : '');
    }

    /** hide_missing: none of the entry's commands is registered. */
    private function missing(string $mode, string $cmds): bool
    {
        if (!config('chat-commands.help_guide.hide_missing') || $cmds === '' || $cmds[0] === '#') {
            return false;
        }

        $names = $mode === 'admin'
            ? array_map(fn ($n) => 'admin '.$n, preg_split('/[^a-z0-9]+/i', $cmds, -1, PREG_SPLIT_NO_EMPTY))
            : (preg_match_all('#/(\w+)#', $cmds, $m) ? $m[1] : []);

        foreach ($names as $name) {
            if (Chat::has($name)) {
                return false;
            }
        }

        return $names !== [];
    }
}
