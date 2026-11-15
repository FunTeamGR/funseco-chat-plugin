<?php
namespace Funseco\Chat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Funseco\Core\Testing\FakesServer;
use Funseco\Chat\Tests\TestCase;
use Funseco\Chat\Commands\PmLogCommand;
use Funseco\Chat\Support\PmHistory;
use Funseco\Chat\Support\TrackMedia;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\Services\Chat\Facades\Chat;
use Funseco\Core\SharedKernel\Events\PlayerWon;
use Funseco\Core\SharedKernel\Values\Nation;

class ChatExtrasTest extends TestCase
{
    use RefreshDatabase;
    use FakesServer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeServer();
        CurrentPlayers::clear();
        config(['adminops.masteradmins' => ['boss']]);
        $this->racer = Player::factory()->create(['login' => 'racer', 'nickname' => 'Racer', 'wins' => 5, 'time_played' => 3700, 'team_name' => 'Zed']);
        $this->boss = Player::factory()->create(['login' => 'boss', 'nickname' => 'Boss', 'wins' => 9, 'time_played' => 100, 'team_name' => '']);
        CurrentPlayers::add($this->racer);
        CurrentPlayers::add($this->boss);
    }

    public function test_official_and_statsall_replies()
    {
        Chat::resolve('/official', 'racer');
        Chat::resolve('/statsall', 'racer');
        [$a, $b] = $this->messages();
        $this->assertStringContainsString('next track to become official', $a);
        $this->assertStringContainsString('Command unavailable', $b);
    }

    public function test_asmomsg_broadcasts_bold_with_nickname()
    {
        Chat::resolve('/asmomsg hello {1}', 'racer');
        $this->assertSame('$bhello Racer', $this->messages('ChatSendServerMessage')[0]);
    }

    public function test_asmomsg_has_no_global_mute_check()
    {
        $this->app->instance(\Funseco\Core\SharedKernel\MuteList::class, new class implements \Funseco\Core\SharedKernel\MuteList {
            public function isMuted(\Funseco\Core\Models\Player $listener, \Funseco\Core\Models\Player $speaker): bool { return false; }
            public function isGloballyMuted(\Funseco\Core\Models\Player $speaker): bool { return true; }
        });
        Chat::resolve('/asmomsg hi', 'racer');
        $this->assertSame(['$bhi'], $this->messages('ChatSendServerMessage'));
    }

    public function test_lastwin_without_window()
    {
        Chat::resolve('/lastwin', 'racer');
        $this->assertStringContainsString('No multi-page window', $this->messages()[0]);
    }

    public function test_topwins_lists_winners_and_lastwin_reopens()
    {
        Chat::resolve('/topwins', 'racer');
        $this->assertStringContainsString('Boss', $this->window());
        $this->assertStringNotContainsString('<manialink id="0"/>', $this->window());
        Chat::resolve('/lastwin', 'racer');
        $this->assertSame([], array_filter($this->messages(), fn ($m) => str_contains($m, 'No multi-page')));
    }

    public function test_active_formats_time()
    {
        Chat::resolve('/active', 'boss');
        $this->assertStringContainsString('01:01:40', $this->window());
    }

    public function test_clans_lists_online_players()
    {
        Chat::resolve('/clans', 'boss');
        $this->assertStringContainsString('Zed', $this->window());
        $this->assertStringContainsString('&lt;none&gt;', $this->window());
    }

    public function test_pm_history_and_pmlog()
    {
        Chat::resolve('/pmlog', 'racer');
        $this->assertStringContainsString('No PM history', $this->messages()[0]);

        Chat::resolve('/pm boss hi there', 'racer');
        $this->assertCount(1, PmHistory::all('boss'));
        $this->assertCount(1, PmHistory::all('racer'));

        Chat::resolve('/pmlog', 'boss');
        $this->assertStringContainsString('hi there', $this->window());
    }

    public function test_pmlog_wraps_long_lines()
    {
        $pieces = PmLogCommand::pieces(str_repeat('word ', 30));
        $this->assertGreaterThan(1, count($pieces));
        $this->assertStringStartsWith('...', $pieces[1]);
    }

    public function test_pma_needs_ability_and_reaches_admin_once()
    {
        Chat::resolve('/pma boss hi', 'racer');
        $this->assertStringContainsString('admin list', $this->messages()[0]);

        $this->calls = [];
        Chat::resolve('/pma racer secret', 'boss');
        $logins = array_map(fn ($a) => $a[1], $this->sent('ChatSendServerMessageToLogin'));
        sort($logins);
        $this->assertSame(['boss', 'racer'], $logins);
    }

    public function test_win_messages()
    {
        event(new PlayerWon($this->racer, 7));
        $this->assertStringContainsString('you won your', $this->messages()[0]);
        event(new PlayerWon($this->racer, 50));
        $this->assertStringContainsString('won his/her', $this->messages('ChatSendServerMessage')[0]);
    }

    public function test_settings_shows_defaults_for_self_only()
    {
        Chat::resolve('/settings boss', 'racer');
        $this->assertStringContainsString('Racer', $this->window());
        $this->assertStringContainsString('DarkBlur', $this->window());
        $this->assertStringNotContainsString('Admin Panel', $this->window());
    }

    public function test_song_and_mod_parse_header()
    {
        $xml = '<header type="challenge"><desc mod="MyMod"/><deps>'
            .'<dep file="ChallengeMusics\\song.ogg" url="http://x/s.ogg"/><dep file="Skins\\Stadium\\Mod\\m.zip" url="http://x/m.zip"/></deps></header>';
        $media = TrackMedia::parse("junk$xml");
        $this->assertSame('song.ogg', $media['song_file']);
        $this->assertSame('http://x/s.ogg', $media['song_url']);
        $this->assertSame('MyMod', $media['mod_name']);
        $this->assertSame('Skins\\Stadium\\Mod\\m.zip', $media['mod_file']);
    }

    public function test_song_without_map_data_says_none()
    {
        Chat::resolve('/song', 'racer');
        $this->assertStringContainsString('No track song found', $this->messages()[0]);
        Chat::resolve('/mod', 'racer');
        $this->assertStringContainsString('No track mod found', $this->messages()[1]);
    }

    public function test_nation_codes()
    {
        $this->assertSame('GRE', Nation::fromZone('World|Greece|Attica'));
        $this->assertSame('OTH', Nation::fromZone(''));
    }

    public function test_stats_card_shows_rows()
    {
        Chat::resolve('/stats', 'racer');
        $w = $this->window();
        $this->assertStringContainsString('Time Played', $w);
        $this->assertStringContainsString('Races Won', $w);
        $this->assertStringNotContainsString('Donations', $w);
    }
}
