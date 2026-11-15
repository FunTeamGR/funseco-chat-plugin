<?php
namespace Funseco\Chat\Support;

/**
 * XAseco text helpers (formatText, stripColors, formatTimeH).
 */
class Text
{
    /** XAseco formatText: replace {1}, {2}, ... with the given values. */
    public static function format(string $text, ...$params): string
    {
        $map = [];
        foreach ($params as $i => $param) {
            $map['{'.($i + 1).'}'] = (string) $param;
        }

        return strtr($text, $map);
    }

    /** Configured message (chat-commands.messages.$key) with parameters, colours resolved. */
    public static function message(string $key, ...$params): string
    {
        return self::format(format_colors((string) config("chat-commands.messages.$key", '')), ...$params);
    }

    /** XAseco stripColors: remove $xxx colour codes and $w/$o/... formatting. */
    public static function stripColors(?string $text): string
    {
        $text = str_replace('$$', "\0", (string) $text);
        $text = preg_replace('/\$(?:[0-9a-f][^$]{0,2}|[lh]\[[^\]]*\]|[^$])/i', '', $text);

        return str_replace("\0", '$$', $text);
    }

    /** XAseco formatTimeH: h:mm:ss from milliseconds. */
    public static function timeH(int $ms): string
    {
        $seconds = intdiv($ms, 1000);

        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }

    /** XAseco uptime style: "N day(s) hh:mm:ss". */
    public static function days(int $seconds): string
    {
        $days = intdiv($seconds, 86400);

        return $days.' day'.($days == 1 ? ' ' : 's ').self::timeH(($seconds % 86400) * 1000);
    }

    /** HH:MM:SS from seconds, hours not capped (1234:05:09). */
    public static function hms(int $seconds): string
    {
        $seconds = max(0, $seconds);

        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }

    /** Space thousands separator in the narrow font: 12345 → "12$n $m345". */
    public static function thousands(int|float $number): string
    {
        return str_replace(' ', '$n $m', number_format((float) $number, 0, '', ' '));
    }

    /** List rank cell: 2-digit padded position and a dot (01. … 99. 100.). */
    public static function rank(int $position): string
    {
        return str_pad((string) $position, 2, '0', STR_PAD_LEFT).'.';
    }

    /** Nickname for list windows: colours kept when funseco.lists_colornicks is on. */
    public static function listNick(?string $nickname): string
    {
        return config('funseco.lists_colornicks', true) ? (string) $nickname : self::stripColors($nickname);
    }

    /** Private reply with a configured message. */
    public static function tell(string $login, string $key, ...$params): void
    {
        \Server::query()->ChatSendServerMessageToLogin(self::message($key, ...$params), $login);
    }
}
