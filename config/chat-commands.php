<?php

// Message texts from XAseco's config.xml / rasp.xml (owner's server). {1}, {2}... are parameters.
return [
    'admin_contact' => env('TM_ADMIN_CONTACT', ''),
    'recs_per_track' => 50,      // rasp.settings.php $maxrecs, shown by /server
    'feature_votes' => true,     // rasp.settings.php $feature_votes: /server shows "Voting info /helpvote"

    'messages' => [
        'muted'            => '{#server}> {#highlite}{1}{#error} disabled because you are on the global mute list!',
        'help_explanation' => '{#server}> Press the {#highlite}C{#server} key to see the whole list, and use {#highlite}/helpall{#server} for details',
        'wins'             => '{#server}> {#record}You have already won {#highlite}{1}{#record} race{2}',
        'playtime'         => '{#server}> Current track {#highlite}{1}{#server} has been played for {#highlite}{2}',
        'track'            => '{#server}> Current track {#highlite}{1} {#server}by {#highlite}{2}  {#server}Author: {#highlite}{3} {#server}Gold: {#highlite}{4} {#server}Silver: {#highlite}{5} {#server}Bronze: {#highlite}{6} {#server}Cost: {#highlite}{7}',
        'time'             => '{#server}> {#interact}Current Server Time: {#highlite}$i {1}{#interact} on {#highlite}$i {2}',
        'nextmap'          => '{#server}> The next Challenge will be: {#highlite}{1}',
        'nextenvmap'       => '{#server}> The next Challenge will be: {#timelite}[{1}] {#highlite}{2}',
        'bootme'           => '{#server}>> {#highlite}{1}$z$s{#message} just crushed and died. R.I.P.',
        'no_lastwin'       => '{#server}> {#error}No multi-page window available!',
        'no_victors'       => '{#server}> {#error}No victorious players found!',
        'no_active'        => '{#server}> {#error}No active players found!',
        'no_pmlog'         => '{#server}> {#error}No PM history found!',
        'no_message'       => '{#server}> {#error}No message!',
        'no_settings'      => '{#server}> {#error}No personal settings available',
        'win'              => '{#server}> {#record}Congratulations, you won your {#highlite}{1}{#record}. race!',
        'win_global'       => '{#server}>> {#record}Congratulations, {#highlite}{1}{#record} won his/her {#highlite}{2}{#record}. race!',
        'official'         => '{#server}> {#highlite}That is not a valid command on ANY server - just wait until the next track to become official.',
        'statsall'         => '{#server}> {#error}Command unavailable, use {#highlite}$i /stats {#error}instead.',
        'no_song'          => '{#server}> {#error}No track song found!',
        'song_try'         => '  Try {#highlite}$i /music current {#error}instead.',
        'song'             => '{#server}> Track {#highlite}{1} {#server}plays song: {#highlite}{2}',
        'no_mod'           => '{#server}> {#error}No track mod found!',
        'mod'              => '{#server}> Track {#highlite}{1} {#server}uses mod: {#highlite}{2} {#server}({#highlite}{3}{#server})',
        'bootme_dialog'    => '{#message}Thanks for playing, please come back soon!',
    ],

    // plugin.rasp_chat.php presets: [message without argument, message with argument ({1}) or null]
    'presets' => [
        'hi'   => ['Sends a Hi message to everyone', 'Yo all !', 'Hello {1} !'],
        'bye'  => ['Sends a Bye message to everyone', 'I am leaving. Oh, and tell your mother I`ve sent the check !', 'Bye {1} !'],
        'thx'  => ['Sends a Thanks message to everyone', 'Thank you suckers !', 'Thanks {1} !'],
        'lol'  => ['Sends a Lol message to everyone', 'Roflmao !', null],
        'lool' => ['Sends a Lool message to everyone', 'LooOOooL !', null],
        'brb'  => ['Sends a Be Right Back message to everyone', 'Going to smoke!', null],
        'gg'   => ['Sends a Good Game message to everyone', 'You all rock !', 'Good Game {1} !'],
        'gr'   => ['Sends a Good Race message to everyone', 'What a race !', 'Good Race {1} !'],
        'n1'   => ['Sends a Nice One message to everyone', 'Thats good. Goooood !', 'Nice One {1} !'],
        'bgm'  => ['Sends a Bad Game message to everyone', 'Bad game for me :(', null],
    ],

    // /afk: one message picked at random, followed by the bot's reply (owner's plugin.rasp_chat.php)
    'afk' => [
        'Away from keyboard'      => '$[$f00Chuck Bot$fff] Chuck Noris doesn\'t go AFK, the keyboard goes AFCN.',
        'I am staring at the sky' => '$[$f00Yomama Bot$fff] You misspelled yo mama\'s ass',
        'Going to take a crap'    => '$[$f00Yomama Bot$fff] In yo mama\'s mouth?',
        'My mom calls me'         => '$[$f00Chuck Bot$fff] Chuck Noris doesn\'t go AFK, the keyboard goes AFCN.',
        'Alt tab on PornHub'      => '$[$f00Yomama Bot$fff] To watch yo mama?',
        'Going to suck a dick'    => '$[$f00Yomama Bot$fff] Just like yo mama...',
        'Spectating Asmo'         => '$[$f00Yomama Bot$fff] Doing nothing to yo mama...',
    ],

    // /helpme and /guide window (better_help). Pages of two columns: [heading, [entries]].
    // User entries: '/cmd, /cmd2|Description' (each /cmd is shown white); admin entries:
    // 'name / name2|Description' (names white, after a red "/admin ..." line); '' = blank line,
    // '#Text' = sub-heading. With hide_missing, entries whose commands are not registered are left out
    // (set false to list every line verbatim, as XAseco did).
    'help_guide' => [
        'hide_missing' => true,
        'user' => [
            'title' => 'Server Guide',
            'pages' => [
                [
                    ['Local Records', [
                        '/recs|List all records on track', '/newrecs|Show newly driven records', '/liverecs|Show records of online players',
                        '/best / /worst|Show your best/worst recs', '/summary|Summary of your records', '/topsums|Top 100 of top-3 holders',
                        '/toprecs|Top 100 ranked record holders', '/nextrec|Next better record to beat', '/diffrec|Difference to first record',
                        '/recrange|Diff first to last record', '/secrecs|List sector records', '/mysecrecs|List your sector records',
                        '/bestruns|Show best run tracking',
                    ]],
                    ['Dedimania Records', [
                        '/dedihelp|Dedi system info', '/dedirecs|List Dedi records on track', '/dedinew|Show newly driven Dedi recs',
                        '/dedilive|Dedi records of online players', '/dedipb|Your Dedi Personal Best', '/dedifirst|First Dedi record on track',
                        '/dedilast|Last Dedi record on track', '/dedinext|Next Dedi record to beat', '/dedidiff|Diff to first Dedi record',
                        '/dedirange|Diff first to last Dedi', '/dedistats|Dedi track statistics', '/dedicps|Toggle Dedi CP tracking',
                    ]],
                ],
                [
                    ['Server & Players', [
                        '/ranks|List of online ranks', '/players|List of online players', '/clans / /topclans|Clan rankings',
                        '/stats|Stats of current player', '/statsall|Global stats of a player', '/top10 / /top100|Best ranked players',
                        '/topwins|Most victorious players', '/active|Most active players', '/whoplayed|Who played current track',
                        '/whospecced|Who spectated current track', '/topplayers|Most active players list', '/laston|When player was last online',
                    ]],
                    ['Tracks & Jukebox', [
                        '/list|List tracks on server', '/jukebox|Juke a track (/jb)', '/add|Add track from TMX (<ID>)',
                        '/history|Recently played tracks', '/xlist|List tracks on TMX', '/tmxinfo|Show TMX info for track',
                        '/song / /mod|Show track song/mod', '/track|Info about current track', '/playtime|Time track has been playing',
                        '/cps / /sectms|Local CP/Sector times', '/pb|Your PB on current track',
                    ]],
                ],
                [
                    ['Chat & Messages', [
                        '/pm|Private message (<login> <txt>)', '/pma|PM to player & admins', '/pmlog|Log of recent PMs',
                        '/chatlog|Log of recent Chat', '/me / /me2|Express emotion', '/gg, /gr, /n1, /thx|Quick chats',
                        '/lol, /brb, /afk|Quick chats', '/hi, /bye, /bgm|Greetings', '/google|Google search in chat',
                        '/funpm|Fun private messaging', '/teampm|Team private messaging', '/clear|Clear chat window',
                    ]],
                    ['Votes & Settings', [
                        '/votehelp|Info on voting commands', '/skip|Vote to skip track', '/replay|Vote to replay track',
                        '/kick|Vote to kick player', '/ignore|Vote to ignore player', '/endround|Vote to end round',
                        '/bootme|Vote to spectate self', '/cancel|Cancel your current vote', '/style|Change window style',
                        '/recpanel|Change records panel', '/togglewidgets|Hide/Show widgets', '/official|Show official mode info',
                    ]],
                ],
                [
                    ['Fun & Emotes', [
                        '/slap, /hit|Attack players', '/hug, /kiss, /poke|Interact', '/laugh, /lol|Laughs', '/taunt, /burn, /loser|Taunts',
                        '/duke|Random Duke Nukem quote', '/face, /dildo, /donut|ASCII Art', '/ud|Urban Dictionary search', '/war|War commands manager',
                    ]],
                    ['Betting & Tools', [
                        '/bet|Bet coppers (/bet <amount>)', '/betwin|Display player wins', '/donate|Donate coppers to server',
                        '/karma|Show karma for track', '/timeleft|Show time left', '/forceplay|Force spectator to play',
                        '/dbtools|Database tools', '/fmods|Force Mods settings', '/dedisectms|Show Dedi Sectors', '/helpme|Open this window',
                    ]],
                ],
            ],
        ],
        'admin' => [
            'title' => 'Admin Guide',
            'pages' => [
                [
                    ['Server Management', [
                        'setservername', 'setcomment', 'setpwd', 'setspecpwd', 'setrefpwd', 'setmaxplayers', 'setmaxspecs',
                        'setgamemode', 'setrefmode', 'server|Detailed server settings', 'password|Show current passwords',
                    ]],
                    ['Track Flow', [
                        'nextmap|Load next', 'skipmap|Skip current', 'previous|Load previous', 'nextenv|Next in same env',
                        'restartmap|Restart track', 'replaymap|Replay (via Juke)', 'dropjukebox|Drop from Juke',
                        'clearjukebox|Clear Juke', 'clearhist|Clear History',
                    ]],
                ],
                [
                    ['Add Tracks', [
                        'add|Add from TMX (<ID>)', 'addthis|Add current track perm', 'addlocal|Add local file', '', '#Track Lists',
                        'writetracklist / readtracklist', 'shuffle|Randomize list', 'listdupes|Show duplicates',
                        'remove|Remove from rotation', 'erase|Remove & Delete file', 'removethis / erasethis',
                    ]],
                    ['Bans & Kicks', [
                        'warn|Send warning', 'kick / kickghost', 'ban / unban', 'banip / unbanip', 'black / unblack',
                        'addguest / removeguest', 'pass|Pass a vote', 'cancel|Cancel vote', 'endround|Force End Round',
                    ]],
                ],
                [
                    ['Lists Management', [
                        'showbanlist / cleanbanlist', 'showiplist / cleaniplist', 'showblacklist / cleanblacklist',
                        'showguestlist / cleanguestlist', 'writeiplist / readiplist', 'writeblacklist / readblacklist',
                        'writeguestlist / readguestlist', 'players|List known players',
                    ]],
                    ['Global & Access', [
                        'mergegbl|Merge global BL', 'access|Handle permissions', '', '#Muting', 'mute / unmute', 'ignore / unignore',
                        'mutelist / ignorelist', 'cleanmutes / cleanignores',
                    ]],
                ],
                [
                    ['Admin Management', [
                        'addadmin / removeadmin', 'addop / removeop', 'listmasters', 'listadmins', 'listops', 'adminability',
                        'opability', 'listabilities', 'writeabilities',
                    ]],
                    ['Teams & Spectators', [
                        'forceteam|Force Blue/Red', 'forcespec|Force Spec', 'specfree|Free Spec mode', 'forceshowopp|Show Opponents',
                        'disablerespawn', 'autotime', 'scorepanel / roundsfinish', 'match / acdl', 'wall|Popup message',
                    ]],
                ],
                [
                    ['Panels & Style', [
                        'panel|Admin panel select', 'style|Window style', 'admpanel / donpanel', 'recpanel / votepanel', '', '#Economy',
                        'coppers|Show amount', 'pay|Pay login',
                    ]],
                    ['System & Maintenance', [
                        'call / unlock', 'debug / uptodate', 'shutdown / shutdownall', 'relays / eyeset', 'delrec / prunerecs',
                        'pm|PM to all admins', 'pmlog|Admin PM log',
                    ]],
                ],
            ],
        ],
    ],

    // /settings defaults shown until the styles/panels area stores per-player choices (owner's values)
    'settings' => [
        'window_style' => 'DarkBlur',
        'panels' => ['admin' => 'AdminBelowChat', 'donate' => 'DonateBelowCPListRM', 'records' => 'RecordsRightBottom', 'vote' => 'VoteBelowChat'],
    ],
];
