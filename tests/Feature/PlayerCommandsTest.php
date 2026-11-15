<?php
namespace Funseco\Chat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Funseco\Core\Testing\FakesServer;
use Funseco\Chat\Tests\TestCase;
use Funseco\Chat\Commands\InfoCommands;
use Funseco\Core\Models\Map;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentMap;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\Services\Cache\CurrentSpectators;
use Funseco\Core\Services\Cache\ServerInfo;
use Funseco\Core\Services\Chat\Facades\Chat;

class PlayerCommandsTest extends TestCase
{
    use RefreshDatabase;
    use FakesServer;

    private Player $racer;
    private Player $boss;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeServer();
        CurrentPlayers::clear();
        CurrentSpectators::clear();
        config(['adminops.masteradmins' => ['boss']]);

        $this->racer = Player::factory()->create(['login' => 'racer', 'nickname' => '$f00Racer', 'nation' => 'GRE', 'wins' => 3]);
        $this->boss  = Player::factory()->create(['login' => 'boss', 'nickname' => 'Boss', 'nation' => 'NED']);
        CurrentPlayers::add($this->racer);
        CurrentPlayers::add($this->boss);
    }

    private function map(): Map
    {
        $map = new Map([
            'uid' => 'u1', 'name' => '$fffSpeed', 'file_name' => 'a.gbx', 'author' => 'bob', 'environment' => 'Stadium',
            'mood' => 'Day', 'author_time' => 30120, 'gold_time' => 32000, 'silver_time' => 36000, 'bronze_time' => 45000,
            'copper_price' => 120,
        ]);
        CurrentMap::set($map);

        return $map;
    }

    public function test_globally_muted_player_cannot_broadcast()
    {
        $this->app->instance(\Funseco\Core\SharedKernel\MuteList::class, new class implements \Funseco\Core\SharedKernel\MuteList {
            public function isMuted(Player $listener, Player $speaker): bool { return false; }
            public function isGloballyMuted(Player $speaker): bool { return $speaker->login === 'racer'; }
        });

        Chat::resolve('/gg', 'racer');
        Chat::resolve('/me waves', 'racer');
        Chat::resolve('/gg', 'boss');

        $this->assertCount(1, $this->sent('ChatSendServerMessage'), 'only boss broadcast');
        $this->assertSame([
            format_colors('{#server}> {#highlite}/gg{#error} disabled because you are on the global mute list!'),
            format_colors('{#server}> {#highlite}/me{#error} disabled because you are on the global mute list!'),
        ], $this->messages());
    }

    public function test_help_lists_player_commands_without_admin_group()
    {
        Chat::register(new InfoCommands('admin zzkick', 'Admin-only test command'));

        Chat::resolve('/help', 'boss');

        [$help, $login] = $this->sent('ChatSendToLogin')[0];
        $this->assertSame('boss', $login);
        $this->assertStringContainsString('Currently supported chat commands:', $help);
        $this->assertStringContainsString('help, helpall, ', $help);
        $this->assertStringContainsString('players', $help);
        $this->assertStringNotContainsString('admin', $help);
        $this->assertStringContainsString('/helpall', $this->messages()[0]);
    }

    public function test_helpall_shows_window_with_descriptions()
    {
        Chat::resolve('/helpall', 'racer');

        $this->assertStringContainsString('Currently supported chat commands:', $this->window());
        $this->assertStringContainsString('$f00/afk', $this->window());
        $this->assertStringContainsString('Sends an Away From Keyboard message to everyone', $this->window());

        // the list is paged: /bootme (and its description) is on page 1 or a later one
        $player = CurrentPlayers::get('racer');
        $seen = false;
        for ($page = 1; $page < 30 && !$seen; $page++) {
            $seen = str_contains($this->window(), '$f00/bootme') && str_contains($this->window(), 'Boot yourself from the server');
            if (!$seen) {
                \Funseco\Core\SharedKernel\UI\Window::navigate($player, 'next');
            }
        }
        $this->assertTrue($seen, '/bootme not found on any page of /helpall');
    }

    public function test_players_window_numbers_players_like_the_login_or_id_helper()
    {
        Chat::resolve('/players', 'racer');

        $xml = $this->window();
        $this->assertStringContainsString('Players On This Server:', $xml);
        $this->assertStringContainsString('01.', $xml);
        $this->assertStringContainsString('$f00Racer$z / $00fracer', $xml);
        $this->assertStringContainsString('Boss$z / $0c0boss', $xml); // admins in logina colour
        $this->assertSame('boss', CurrentPlayers::findByLoginOrIndex('2')->login);
    }

    public function test_players_filter_keeps_ids_and_reports_no_match()
    {
        Chat::resolve('/players bo', 'racer');
        $this->assertStringContainsString('02.', $this->window());
        $this->assertStringNotContainsString('racer', $this->window());

        Chat::resolve('/players nobody', 'racer');
        $this->assertStringContainsString('No player(s) found!', $this->messages()[0]);
    }

    public function test_server_window_shows_server_info()
    {
        ServerInfo::set(['server_name' => 'Test Server', 'server_comment' => 'Fun', 'max_players' => 32, 'max_spectators' => 8]);
        $this->responses = [
            'GetNetworkStats'    => ['Uptime' => 90061],
            'GetCurrentGameInfo' => ['GameMode' => 1, 'NbChallenge' => 210, 'TimeAttackLimit' => 300000],
            'GetLadderServerLimits' => ['LadderServerLimitMin' => 0, 'LadderServerLimitMax' => 50000],
        ];

        Chat::resolve('/server', 'racer');

        $xml = $this->window();
        $this->assertStringContainsString('Welcome to: Test Server', $xml);
        $this->assertStringContainsString('1 day 01:01:01', $xml);
        $this->assertStringContainsString('TimeAttack', $xml);
        $this->assertStringContainsString('5:00.00', $xml);
        $this->assertStringContainsString('210', $xml);
        $this->assertStringContainsString('Visited by $f802 $gPlayers from $f402 $gNations', $xml);
    }

    public function test_time()
    {
        Chat::resolve('/time', 'racer');

        $this->assertStringContainsString('Current Server Time: $fff$i '.date('H:i'), $this->messages()[0]);
    }

    public function test_track()
    {
        $this->map();

        Chat::resolve('/track', 'racer');

        $this->assertSame(
            '$ff0> Current track $fffSpeed $ff0by $fffbob  $ff0Author: $fff0:30.12 $ff0Gold: $fff0:32.00 $ff0Silver: $fff0:36.00 $ff0Bronze: $fff0:45.00 $ff0Cost: $fff120',
            $this->messages()[0],
        );
    }

    public function test_nextmap_asks_the_server()
    {
        $this->responses = ['GetNextChallengeIndex' => 4, 'GetChallengeList' => [['Name' => '$f00Next', 'Environnement' => 'Stadium']]];

        Chat::resolve('/nextmap', 'racer');

        $this->assertSame([1, 4], $this->sent('GetChallengeList')[0]);
        $this->assertSame('$ff0> The next Challenge will be: $fffNext', $this->messages()[0]);
    }

    public function test_playtime_counts_from_map_start()
    {
        $this->map();
        state()->forever(InfoCommands::MAP_STARTED_AT, time() - 125);

        Chat::resolve('/playtime', 'racer');

        $this->assertSame('$ff0> Current track $fffSpeed$ff0 has been played for $fff00:02:05', $this->messages()[0]);
    }

    public function test_wins()
    {
        Chat::resolve('/wins', 'racer');
        Chat::resolve('/wins', 'boss');

        $this->assertSame('$ff0> $0f3You have already won $fff3$0f3 races!', $this->messages()[0]);
        $this->assertSame('$ff0> $0f3You have already won $fff0$0f3 races.', $this->messages()[1]);
    }

    public function test_laston_for_self_offline_and_unknown_players()
    {
        Player::factory()->create(['login' => 'gone', 'nickname' => 'Gone', 'updated_at' => '2025-01-02 03:04:05']);

        Chat::resolve('/laston gone', 'racer');
        Chat::resolve('/laston nope', 'racer');
        Chat::resolve('/laston', 'racer');

        $this->assertSame('$ff0> Player $fffGone$z$s$ff0 was last online on: $fff2025-01-02 03:04', $this->messages()[0]);
        $this->assertStringContainsString('nope $f00$iis not a valid player!', $this->messages()[1]);
        $this->assertStringContainsString('Player $fff$f00Racer', $this->messages()[2]);
    }

    public function test_pm_by_id_goes_to_both_players()
    {
        Chat::resolve('/pm 2 see you', 'racer');

        $this->assertSame(['boss', 'racer'], array_column($this->sent('ChatSendServerMessageToLogin'), 1));
        $this->assertSame('$f00$i-pm-$g[$f00Racer$z$s$i->Boss$z$s$i]$i $ff0$isee you', $this->messages()[0]);
    }

    public function test_pm_errors()
    {
        Chat::resolve('/pm 9 hi', 'racer');
        Chat::resolve('/pm boss', 'racer');

        $this->assertStringContainsString('Player_ID not found!', $this->messages()[0]);
        $this->assertStringContainsString('No message!', $this->messages()[1]);
    }

    public function test_me()
    {
        Chat::resolve('/me is fast', 'racer');

        $this->assertSame('$i$f00Racer$z$s$i $fa0is fast', $this->messages('ChatSendServerMessage')[0]);
    }

    public function test_bootme_announces_and_kicks()
    {
        Chat::resolve('/bootme', 'racer');

        $this->assertSame('$ff0>> $fff$f00Racer$z$s$39f just crushed and died. R.I.P.', $this->messages('ChatSendServerMessage')[0]);
        $this->assertSame(['racer', '$39fThanks for playing, please come back soon!$z'], $this->sent('Kick')[0]);
    }

    public function test_presets_with_and_without_argument()
    {
        Chat::resolve('/hi', 'racer');
        Chat::resolve('/gg boss', 'racer');
        Chat::resolve('/lol whatever', 'racer');

        $this->assertSame([
            '$g[$f00Racer$z$s] $ff0$iYo all !',
            '$g[$f00Racer$z$s] $ff0$iGood Game boss !',
            '$g[$f00Racer$z$s] $ff0$iRoflmao !',
        ], $this->messages('ChatSendServerMessage'));
    }

    public function test_every_preset_is_registered()
    {
        foreach (['hi', 'bye', 'thx', 'lol', 'lool', 'brb', 'gg', 'gr', 'n1', 'bgm'] as $preset) {
            Chat::resolve('/'.$preset, 'racer');
        }

        $this->assertCount(10, $this->messages('ChatSendServerMessage'));
    }

    public function test_afk_announces_and_forces_spectator()
    {
        config(['funseco.afk_force_spec' => true]);

        Chat::resolve('/afk', 'racer');

        [$announce, $bot] = $this->messages('ChatSendServerMessage');
        $this->assertStringStartsWith('$g[$f00Racer$z$s] $ff0$i', $announce);
        $this->assertStringContainsString('Bot$fff]', $bot);
        $this->assertSame([['racer', 1], ['racer', 0]], $this->sent('ForceSpectator'));
        $this->assertSame([['racer', '', 2]], $this->sent('ForceSpectatorTarget'));
        $this->assertSame([['racer']], $this->sent('SpectatorReleasePlayerSlot'));
    }
}
