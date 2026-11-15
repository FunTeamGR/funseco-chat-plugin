<?php
namespace Funseco\Chat;

use Funseco\Core\SharedKernel\PublishesPluginConfig;
use Illuminate\Support\Facades\Event;
use Funseco\Core\SharedKernel\Settings\Setting;
use Funseco\Core\SharedKernel\Settings\SettingsRegistry;
use Illuminate\Support\ServiceProvider;
use Funseco\Chat\Commands\HelpAllCommand;
use Funseco\Chat\Commands\HelpCommand;
use Funseco\Chat\Commands\InfoCommands;
use Funseco\Chat\Commands\PlayersCommand;
use Funseco\Chat\Commands\ServerCommand;
use Funseco\Chat\Commands\SocialCommands;
use Funseco\Chat\Commands\ListCommands;
use Funseco\Chat\Commands\MiscCommands;
use Funseco\Chat\Commands\PmaCommand;
use Funseco\Chat\Commands\PmLogCommand;
use Funseco\Chat\Commands\SettingsCommand;
use Funseco\Chat\Commands\StatsCommand;
use Funseco\Chat\Commands\TrackMediaCommand;
use Funseco\Chat\Listeners\PlayerProfileListener;
use Funseco\Chat\Listeners\WinMessagesListener;
use Funseco\Core\Services\ManiaLink\ManiaLinkService;
use Funseco\Core\SharedKernel\Events\MapStarted;
use Funseco\Core\SharedKernel\Events\PlayerConnected;
use Funseco\Core\SharedKernel\Events\PlayerDisconnected;
use Funseco\Core\SharedKernel\Events\PlayerWon;

/**
 * Player chat commands (XAseco chat.*.php / plugin.rasp_chat.php).
 * Not to be confused with core's ChatService, which routes the commands.
 */
class ChatCommandsServiceProvider extends ServiceProvider
{
    use PublishesPluginConfig;

    private const INFO = [
        'time'     => ['Shows the current server time & date', ''],
        'track'    => ['Shows info about the current track', ''],
        'nextmap'  => ['Shows name of the next challenge', ''],
        'playtime' => ['Shows time current track has been playing', ''],
        'wins'     => ['Shows wins for current player', ''],
        'laston'   => ['Shows when a player was last online', '[login/id]'],
    ];

    private const SOCIAL = [
        'pm'     => ['Sends a private message to login or Player_ID', '<login/id> <message>'],
        'me'     => ['Can be used to express emotions', '<text>'],
        'bootme' => ['Boot yourself from the server', ''],
        'afk'    => ['Sends an Away From Keyboard message to everyone', ''],
    ];

    private const LISTS = [
        'clans'   => 'Displays list of online clans/nicks',
        'topwins' => 'Displays top 100 victorious players',
        'active'  => 'Displays top 100 most active players',
        'lastwin' => 'Re-opens the last closed multi-page window',
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/chat-commands.php', 'chat-commands');
        $this->publishPluginConfig(__DIR__.'/..');
        // Read lazily through config() at use time: live. presets/messages/afk/help_guide pages are templates or read at boot: not declared.
        $this->app->make(SettingsRegistry::class)->declare('chat', 'chat-commands', [
            Setting::string('admin_contact', 'Admin contact shown by /server'),
            Setting::int('recs_per_track', 'Records per track shown by /server', min: 1),
            Setting::bool('feature_votes', '/server shows voting info'),
            Setting::bool('help_guide.hide_missing', 'Help guide hides unavailable commands'),
        ]);
    }

    public function boot(): void
    {
        foreach ([HelpCommand::class, HelpAllCommand::class, \Funseco\Chat\Commands\HelpGuideCommand::class, PlayersCommand::class, ServerCommand::class] as $command) {
            \Chat::register($command);
        }

        foreach (self::INFO as $name => [$description, $usage]) {
            \Chat::register(new InfoCommands($name, $description, $usage));
        }
        foreach (self::SOCIAL as $name => [$description, $usage]) {
            \Chat::register(new SocialCommands($name, $description, $usage));
        }
        foreach (config('chat-commands.presets') as $name => [$description, , $withArg]) {
            \Chat::register(new SocialCommands($name, $description, $withArg ? '[name]' : ''));
        }

        foreach (self::LISTS as $name => $description) {
            \Chat::register(new ListCommands($name, $description));
        }
        foreach ([StatsCommand::class, SettingsCommand::class, PmaCommand::class, PmLogCommand::class] as $command) {
            \Chat::register($command);
        }
        \Chat::register(new MiscCommands('statsall', 'Displays world statistics of a player'));
        \Chat::register(new MiscCommands('official', 'Shows a helpful message ;-)'));
        \Chat::register(new MiscCommands('asmomsg', 'Broadcasts bold text to all players, {1} is your nickname', '<text>'));
        \Chat::register(new TrackMediaCommand('song', "Shows filename of current track's song"));
        \Chat::register(new TrackMediaCommand('mod', "Shows (file)name of current track's mod"));

        app(ManiaLinkService::class)->addActions(array_keys(StatsCommand::CLICKS), StatsClickAction::class);
        app(ManiaLinkService::class)->addActions(array_slice(HelpGuideWindow::actions(), 1), HelpGuideActions::class);
        app(ManiaLinkService::class)->addAction('help_guide');
        Event::listen(PlayerConnected::class, [PlayerProfileListener::class, 'connected']);
        Event::listen(PlayerDisconnected::class, [PlayerProfileListener::class, 'disconnected']);
        Event::listen(PlayerWon::class, WinMessagesListener::class);

        Event::listen(MapStarted::class, fn () => state()->forever(InfoCommands::MAP_STARTED_AT, time()));
    }
}
