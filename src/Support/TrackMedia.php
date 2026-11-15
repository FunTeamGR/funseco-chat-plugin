<?php
namespace Funseco\Chat\Support;

use Funseco\Core\Models\Map;
use Funseco\Core\Services\Cache\ServerInfo;

/**
 * Song and mod of a track, read from the XML header inside its challenge file
 * (<deps><dep file=".." url=".."/></deps>, <desc mod="..">). Cached per map uid.
 */
class TrackMedia
{
    private const EMPTY = ['song_file' => '', 'song_url' => '', 'mod_name' => '', 'mod_file' => '', 'mod_url' => ''];

    /** The header sits near the start of the file, before the thumbnail. */
    private const READ_BYTES = 262144;

    /** @return array{song_file: string, song_url: string, mod_name: string, mod_file: string, mod_url: string} */
    public static function of(Map $map): array
    {
        $key = 'chat:trackmedia:'.$map->uid;
        $media = state()->get($key);
        if ($media === null) {
            $media = self::read($map);
            state()->forever($key, $media);
        }

        return $media;
    }

    public static function read(Map $map): array
    {
        $dir = ServerInfo::getTracksDirectory();
        if (!$dir || !$map->file_name) {
            return self::EMPTY;
        }
        $path = $dir.'/'.str_replace('\\', '/', $map->file_name);
        $data = is_readable($path) ? @file_get_contents($path, false, null, 0, self::READ_BYTES) : false;

        return $data === false ? self::EMPTY : self::parse($data);
    }

    public static function parse(string $data): array
    {
        $start = strpos($data, '<header ');
        $end = $start === false ? false : strpos($data, '</header>', $start);
        if ($end === false) {
            return self::EMPTY;
        }

        $previous = libxml_use_internal_errors(true);
        $doc = simplexml_load_string(substr($data, $start, $end - $start + strlen('</header>')));
        libxml_use_internal_errors($previous);
        if ($doc === false) {
            return self::EMPTY;
        }

        $media = self::EMPTY;
        $media['mod_name'] = (string) ($doc->desc['mod'] ?? '');
        foreach ($doc->deps->dep ?? [] as $dep) {
            $file = (string) $dep['file'];
            if (($pos = stripos($file, 'ChallengeMusics\\')) !== false) {
                $media['song_file'] = substr($file, $pos + strlen('ChallengeMusics\\'));
                $media['song_url'] = (string) $dep['url'];
            } elseif (stripos($file, '\\Mod\\') !== false) {
                $media['mod_file'] = $file;
                $media['mod_url'] = (string) $dep['url'];
            }
        }

        return $media;
    }
}
