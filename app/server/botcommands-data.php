<?php

require_once dirname(__DIR__, 2) . '/core/xfer/com_db.php';
require_once dirname(__DIR__, 2) . '/core/xfer/com_search.php';

if (!function_exists('spp_botcommand_extract_tags')) {
    function spp_botcommand_extract_tags(array $command): array
    {
        $name = strtolower((string)($command['name'] ?? ''));
        $category = strtolower((string)($command['category'] ?? ''));
        $help = strtolower((string)($command['help'] ?? ''));
        $blob = $name . ' ' . $category . ' ' . $help;

    $states = array();
    if (strpos($blob, 'combat behavior') !== false || strpos($blob, '[c:co ') !== false || strpos($blob, 'combat state') !== false) {
        $states[] = 'co';
    }
    if (strpos($blob, 'non combat behavior') !== false || strpos($blob, '[c:nc ') !== false || strpos($blob, 'non-combat') !== false) {
        $states[] = 'nc';
    }
    if (strpos($blob, 'reaction behavior') !== false || strpos($blob, '[c:react ') !== false || strpos($blob, 'reaction state') !== false) {
        $states[] = 'react';
    }
    if (strpos($blob, 'dead state behavior') !== false || strpos($blob, '[c:dead ') !== false || strpos($blob, 'dead state') !== false) {
        $states[] = 'dead';
    }
    if (empty($states)) {
        $states[] = 'general';
    }

    $roles = array();
    if (preg_match('/\btank\b|\bthreat\b|\btaunt\b/', $blob)) {
        $roles[] = 'tank';
    }
    if (preg_match('/\bheal\b|\bhealer\b|\bholy\b|\brestoration\b|\bpreheal\b/', $blob)) {
        $roles[] = 'healer';
    }
    if (preg_match('/\bdps\b|\bdamage\b|\bboost\b|\bmelee\b|\branged\b|\battack\b/', $blob)) {
        $roles[] = 'dps';
    }
    if (empty($roles)) {
        $roles[] = 'general';
    }

    $classes = array();
    $classMap = array(
        'warrior' => array('warrior', 'arms', 'fury', 'protection'),
        'paladin' => array('paladin', 'retribution'),
        'hunter' => array('hunter', 'beast mastery', 'marksmanship', 'survival'),
        'rogue' => array('rogue', 'assassination', 'subtlety', 'combat rogue'),
        'priest' => array('priest', 'discipline', 'shadow priest'),
        'shaman' => array('shaman', 'elemental', 'enhancement'),
        'mage' => array('mage', 'arcane', 'frost mage', 'fire mage'),
        'warlock' => array('warlock', 'affliction', 'demonology', 'destruction'),
        'druid' => array('druid', 'balance', 'feral', 'restoration druid'),
        'deathknight' => array('death knight', 'deathknight', 'blood', 'unholy'),
    );
    foreach ($classMap as $className => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($blob, $keyword) !== false) {
                $classes[] = $className;
                break;
            }
        }
    }
    if (empty($classes)) {
        $classes[] = 'all';
    }

        return array(
            'states' => array_values(array_unique($states)),
            'roles' => array_values(array_unique($roles)),
            'classes' => array_values(array_unique($classes)),
        );
    }
}

if (!function_exists('spp_macro_options_from_commands')) {
    function spp_macro_options_from_commands(array $commands, array $keywords, array $fallback = array()): array
    {
        $options = array();
        foreach ($commands as $command) {
            $name = trim((string)($command['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $needle = strtolower($name);
            foreach ($keywords as $keyword) {
                if (strpos($needle, strtolower($keyword)) !== false) {
                    $options[$name] = array(
                        'label' => $name,
                        'value' => $name,
                    );
                    break;
                }
            }
        }

        if (empty($options)) {
            foreach ($fallback as $name) {
                $options[$name] = array(
                    'label' => $name,
                    'value' => $name,
                );
            }
        }

        ksort($options, SORT_NATURAL | SORT_FLAG_CASE);
        return array_values($options);
    }
}

function spp_macro_options_from_categories(array $commands, array $categories, array $fallback = array()): array
{
    $options = array();
    $wanted = array_map('strtolower', $categories);

    foreach ($commands as $command) {
        $category = strtolower((string)($command['category'] ?? ''));
        $name = trim((string)($command['name'] ?? ''));
        if ($name === '' || !in_array($category, $wanted, true)) {
            continue;
        }

        $options[$name] = array(
            'label' => $name,
            'value' => $name,
        );
    }

    if (empty($options)) {
        foreach ($fallback as $name) {
            $options[$name] = array(
                'label' => $name,
                'value' => $name,
            );
        }
    }

    ksort($options, SORT_NATURAL | SORT_FLAG_CASE);
    return array_values($options);
}

function spp_macro_filter_named_options(array $options, array $excludePatterns = array(), array $includePatterns = array()): array
{
    $filtered = array();

    foreach ($options as $option) {
        $value = strtolower(trim((string)($option['value'] ?? '')));
        if ($value === '') {
            continue;
        }

        $included = empty($includePatterns);
        foreach ($includePatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $included = true;
                break;
            }
        }
        if (!$included) {
            continue;
        }

        $excluded = false;
        foreach ($excludePatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $excluded = true;
                break;
            }
        }
        if ($excluded) {
            continue;
        }

        $filtered[] = $option;
    }

    return $filtered;
}

function spp_macro_remove_option_values(array $options, array $takenOptions): array
{
    $taken = array();
    foreach ($takenOptions as $option) {
        $value = strtolower(trim((string)($option['value'] ?? '')));
        if ($value !== '') {
            $taken[$value] = true;
        }
    }

    return array_values(array_filter($options, function ($option) use ($taken) {
        $value = strtolower(trim((string)($option['value'] ?? '')));
        return $value === '' || !isset($taken[$value]);
    }));
}

function spp_macro_expand_general_states(array $states): array
{
    $expanded = array();
    foreach ($states as $state) {
        if ($state === 'general') {
            $expanded = array_merge($expanded, array('co', 'nc', 'react', 'dead'));
            continue;
        }
        $expanded[] = $state;
    }

    if (empty($expanded)) {
        $expanded = array('co', 'nc');
    }

    return array_values(array_unique($expanded));
}

function spp_macro_merge_strategy_sets(array $base, array $extra): array
{
    foreach ($extra as $state => $options) {
        if (!isset($base[$state])) {
            $base[$state] = array();
        }
        $base[$state] = array_values(array_unique(array_merge($base[$state], $options)));
        natcasesort($base[$state]);
        $base[$state] = array_values($base[$state]);
    }

    return $base;
}

function spp_macro_class_strategy_sets(array $commands, string $classKey, array $fallbackSets = array()): array
{
    $classNeedles = array(
        'warrior' => array('warrior'),
        'paladin' => array('paladin'),
        'hunter' => array('hunter'),
        'rogue' => array('rogue'),
        'priest' => array('priest'),
        'shaman' => array('shaman'),
        'mage' => array('mage'),
        'warlock' => array('warlock'),
        'druid' => array('druid'),
        'deathknight' => array('deathknight', 'death knight'),
    );

    $dynamicSets = array(
        'co' => array(),
        'nc' => array(),
        'react' => array(),
        'dead' => array(),
    );

    foreach ($commands as $command) {
        if (strtolower((string)($command['category'] ?? '')) !== 'strategy') {
            continue;
        }

        $name = trim((string)($command['name'] ?? ''));
        if ($name === '') {
            continue;
        }

        $haystack = strtolower($name . ' ' . (string)($command['help'] ?? ''));
        $matchesClass = false;
        foreach (($classNeedles[$classKey] ?? array($classKey)) as $needle) {
            if (strpos($haystack, strtolower($needle)) !== false) {
                $matchesClass = true;
                break;
            }
        }
        if (!$matchesClass) {
            continue;
        }

        $states = spp_macro_expand_general_states($command['state_tags'] ?? array());
        foreach ($states as $state) {
            if (!isset($dynamicSets[$state])) {
                $dynamicSets[$state] = array();
            }
            $dynamicSets[$state][] = '+' . $name;
        }
    }

    return spp_macro_merge_strategy_sets($fallbackSets, $dynamicSets);
}

if (!function_exists('spp_server_build_botcommands_page_state')) {
    function spp_server_build_botcommands_page_state(array $args = array()): array
    {
        $pdo = $args['pdo'] ?? ($GLOBALS['pdo'] ?? null);
        $worldDb = (string)($args['world_db'] ?? ($GLOBALS['world_db'] ?? ''));
        $user = (array)($args['user'] ?? ($GLOBALS['user'] ?? array()));
        $activeCommandTab = (string)($args['active_tab'] ?? 'strategies');

        $botCommands = loadCommands($pdo, $worldDb, 'bot');
        $gmCommands = loadCommands($pdo, $worldDb, 'gm');
        $userGmLevel = (int)($user['gmlevel'] ?? 0);

        if (($user['id'] ?? 0) > 0) {
            $gmCommands = array_values(array_filter($gmCommands, function ($cmd) use ($userGmLevel) {
                return (int)($cmd['security'] ?? 0) <= $userGmLevel;
            }));
        }

        $botCommands = array_values(array_map(function ($command) {
    $tags = spp_botcommand_extract_tags($command);
    $command['state_tags'] = $tags['states'];
    $command['role_tags'] = $tags['roles'];
    $command['class_tags'] = $tags['classes'];
    return $command;
}, $botCommands));

$questMacroOptions = spp_macro_options_from_commands(
    $botCommands,
    array('quest'),
    array(
        'quest',
        'accept quest',
        'accept all quests',
        'accept quest share',
        'auto share quest',
        'clean quest log',
        'confirm quest',
        'drop quest',
        'query quest',
        'quest details',
        'quest objective completed',
        'quest reward',
    )
);

$rtscMacroOptions = spp_macro_options_from_commands(
    $botCommands,
    array('rtsc', 'formation', 'position', 'follow target'),
    array(
        'rtsc',
        'set formation',
        'position',
        'follow target',
    )
);

$movementMacroOptions = spp_macro_options_from_categories(
    $botCommands,
    array('action'),
    array(
        'follow',
        'stay',
        'guard',
        'free',
        'flee',
        'return',
        'do follow',
        'mount',
        'pull',
        'pull back',
    )
);
$movementMacroOptions = spp_macro_filter_named_options(
    $movementMacroOptions,
    array(
        '/\bquest\b/',
        '/\bshaman\b|\bwarrior\b|\bpaladin\b|\bhunter\b|\brogue\b|\bpriest\b|\bmage\b|\bwarlock\b|\bdruid\b|\bdeath ?knight\b/',
        '/\bspell\b|\btotem\b|\bcurse\b|\bpoison\b|\bblessing\b|\baura\b|\bheal\b|\bshadow\b|\bfrost\b|\bfire\b|\bpet\b/',
    ),
    array(
        '/\bfollow\b|\bstay\b|\bguard\b|\bfree\b|\bflee\b|\breturn\b|\bmount\b|\bpull\b|\bformation\b|\bposition\b|\brtsc\b|\bmove\b|\battack\b/'
    )
);
$movementMacroOptions = spp_macro_remove_option_values($movementMacroOptions, $rtscMacroOptions);
$movementMacroOptions = spp_macro_remove_option_values($movementMacroOptions, $questMacroOptions);

$macroStatePresetOptions = array(
    array('label' => 'co (combat)', 'value' => 'co'),
    array('label' => 'nc (non-combat)', 'value' => 'nc'),
    array('label' => 'react (reaction)', 'value' => 'react'),
    array('label' => 'dead', 'value' => 'dead'),
);

$macroClassPresetConfigs = array(
    'warrior' => array(
        'label' => 'warrior',
        'strategies' => array(
            'co' => array('+tank', '+tank assist', '+threat', '+dps', '+dps assist', '+charge', '-threat'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg'),
            'react' => array('+pvp', '+intervene', '+charge'),
            'dead' => array('+ghost'),
        ),
    ),
    'paladin' => array(
        'label' => 'paladin',
        'strategies' => array(
            'co' => array('+tank', '+tank assist', '+threat', '+offheal', '+dps assist', '+cast time', '+blessing', '-threat'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg', '+conserve mana'),
            'react' => array('+pvp', '+cleanse', '+blessing'),
            'dead' => array('+ghost'),
        ),
    ),
    'hunter' => array(
        'label' => 'hunter',
        'strategies' => array(
            'co' => array('+dps', '+dps assist', '+ranged', '+close', '+pet', '+traps', '-threat'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg'),
            'react' => array('+pvp', '+kite', '+flee'),
            'dead' => array('+ghost'),
        ),
    ),
    'rogue' => array(
        'label' => 'rogue',
        'strategies' => array(
            'co' => array('+dps', '+dps assist', '+stealth', '+close', '+behind', '-threat'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg'),
            'react' => array('+pvp', '+flee', '+stealth'),
            'dead' => array('+ghost'),
        ),
    ),
    'priest' => array(
        'label' => 'priest',
        'strategies' => array(
            'co' => array('+offheal', '+heal', '+dps assist', '+cast time', '+shadow', '+discipline'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg', '+conserve mana'),
            'react' => array('+pvp', '+dispel', '+preheal'),
            'dead' => array('+ghost'),
        ),
    ),
    'shaman' => array(
        'label' => 'shaman',
        'strategies' => array(
            'co' => array('+dps', '+offheal', '+totems', '+dps assist', '+cast time', '+enhancement'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg', '+conserve mana'),
            'react' => array('+pvp', '+purge', '+totems'),
            'dead' => array('+ghost'),
        ),
    ),
    'mage' => array(
        'label' => 'mage',
        'strategies' => array(
            'co' => array('+dps', '+dps assist', '+ranged', '+cast time', '+frost', '+aoe', '-threat'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg', '+conserve mana'),
            'react' => array('+pvp', '+flee', '+counterspell'),
            'dead' => array('+ghost'),
        ),
    ),
    'warlock' => array(
        'label' => 'warlock',
        'strategies' => array(
            'co' => array('+dps', '+dps assist', '+ranged', '+pet', '+curses', '+cast time', '-threat'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg', '+conserve mana'),
            'react' => array('+pvp', '+fear', '+pet'),
            'dead' => array('+ghost'),
        ),
    ),
    'druid' => array(
        'label' => 'druid',
        'strategies' => array(
            'co' => array('+offheal', '+dps', '+tank', '+dps assist', '+cast time', '+balance', '+feral'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg', '+conserve mana'),
            'react' => array('+pvp', '+flee', '+remove curse'),
            'dead' => array('+ghost'),
        ),
    ),
    'deathknight' => array(
        'label' => 'death knight',
        'strategies' => array(
            'co' => array('+tank', '+dps', '+tank assist', '+threat', '+dps assist', '+close'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg'),
            'react' => array('+pvp', '+flee', '+close'),
            'dead' => array('+ghost'),
        ),
    ),
);
foreach ($macroClassPresetConfigs as $classKey => $classConfig) {
    $macroClassPresetConfigs[$classKey]['strategies'] = spp_macro_class_strategy_sets(
        $botCommands,
        $classKey,
        $classConfig['strategies']
    );
}

$chatFilterFamilies = array(
    array(
        'title' => 'Strategy Filters',
        'description' => 'Select bots by strategies enabled in a specific bot state.',
        'tokens' => array('@co=', '@noco=', '@nc=', '@nonc=', '@react=', '@noreact=', '@dead=', '@nodead='),
        'examples' => array(
            '@nc=rpg' => 'Bots with the non-combat rpg strategy enabled.',
            '@nonc=travel' => 'Bots without the travel strategy in non-combat.',
            '@co=melee' => 'Bots with the melee strategy in combat.',
            '@react=pvp' => 'Bots with the pvp strategy in reaction state.',
            '@dead=<>' => 'Bots with the <> strategy in dead state.',
        ),
    ),
    array(
        'title' => 'Role and Combat Filters',
        'description' => 'Select bots by role or by whether they fight at melee or range.',
        'tokens' => array('@tank', '@dps', '@heal', '@notank', '@nodps', '@noheal', '@melee', '@ranged'),
        'examples' => array(
            '@tank' => 'Bots with a tank role/spec.',
            '@dps' => 'Bots that are neither tank nor healer.',
            '@heal' => 'Bots with a healing role/spec.',
            '@melee' => 'Bots that fight in melee.',
            '@ranged' => 'Bots that fight at range.',
        ),
    ),
    array(
        'title' => 'Class Filters',
        'description' => 'Select bots by class. Death Knight only applies where supported by the expansion.',
        'tokens' => array('@warrior', '@paladin', '@hunter', '@rogue', '@priest', '@shaman', '@mage', '@warlock', '@druid', '@deathknight'),
        'examples' => array(
            '@warrior' => 'Warrior bots only.',
            '@mage' => 'Mage bots only.',
            '@rogue' => 'Rogue bots only.',
            '@warlock' => 'Warlock bots only.',
        ),
    ),
    array(
        'title' => 'Raid Icon Filters',
        'description' => 'Select bots that are marked with, or targeting, a raid target icon.',
        'tokens' => array('@star', '@circle', '@diamond', '@triangle', '@moon', '@square', '@cross', '@skull'),
        'examples' => array(
            '@star' => 'Bots marked with or targeting star.',
            '@circle' => 'Bots marked with or targeting circle.',
            '@skull' => 'Bots marked with or targeting skull.',
        ),
    ),
    array(
        'title' => 'Level Filters',
        'description' => 'Select bots by exact level or level range.',
        'tokens' => array('@60', '@10-20'),
        'examples' => array(
            '@60' => 'Bots that are level 60.',
            '@10-20' => 'Bots between levels 10 and 20.',
        ),
    ),
    array(
        'title' => 'Group Filters',
        'description' => 'Select bots by group status, raid status, subgroup, or group leadership.',
        'tokens' => array('@group', '@group2', '@group4-6', '@nogroup', '@leader', '@raid', '@noraid', '@rleader'),
        'examples' => array(
            '@group' => 'Bots that are in a group.',
            '@group2' => 'Bots in subgroup 2.',
            '@group4-6' => 'Bots in subgroups 4 through 6.',
            '@leader' => 'Bots leading their current group.',
            '@raid' => 'Bots in a raid group.',
        ),
    ),
    array(
        'title' => 'Guild Filters',
        'description' => 'Select bots by guild membership, guild name, guild rank, or guild leadership.',
        'tokens' => array('@guild', '@guild=', '@rank=', '@noguild', '@gleader'),
        'examples' => array(
            '@guild' => 'Bots in any guild.',
            '@guild=raiders' => 'Bots in the guild named raiders.',
            '@rank=Initiate' => 'Bots with the rank Initiate.',
            '@noguild' => 'Bots with no guild.',
            '@gleader' => 'Bots that lead their guild.',
        ),
    ),
    array(
        'title' => 'State Filters',
        'description' => 'Select bots by repair status, bag space, or whether they are inside an instance.',
        'tokens' => array('@needrepair', '@bagfull', '@bagalmostfull', '@outside', '@inside'),
        'examples' => array(
            '@needrepair' => 'Bots below 20% durability.',
            '@bagfull' => 'Bots with no bag space left.',
            '@bagalmostfull' => 'Bots with low bag space.',
            '@outside' => 'Bots outside an instance.',
            '@inside' => 'Bots inside an instance.',
        ),
    ),
    array(
        'title' => 'Item Usage Filters',
        'description' => 'Select bots by how they value an item link or qualifier.',
        'tokens' => array('@use=', '@need=', '@greed=', '@sell='),
        'examples' => array(
            '@use=[itemlink]' => 'Bots with any meaningful use for the item.',
            '@need=[itemlink]' => 'Bots that would need-roll the item.',
            '@greed=[itemlink]' => 'Bots that would greed-roll the item.',
            '@sell=[itemlink]' => 'Bots that would vendor or AH the item.',
        ),
    ),
    array(
        'title' => 'Talent Spec Filters',
        'description' => 'Select bots by their primary talent specialization name.',
        'tokens' => array('@holy', '@frost', '@shadow', '@restoration', '@protection', '@balance'),
        'examples' => array(
            '@holy' => 'Holy-spec bots.',
            '@frost' => 'Frost-spec bots.',
            '@shadow' => 'Shadow-spec bots.',
        ),
    ),
    array(
        'title' => 'Location Filters',
        'description' => 'Select bots by current map or zone name.',
        'tokens' => array('@azeroth', '@eastern kingdoms', '@dun morogh'),
        'examples' => array(
            '@azeroth' => 'Bots in Azeroth overworld.',
            '@eastern kingdoms' => 'Bots in Eastern Kingdoms overworld.',
            '@dun morogh' => 'Bots in the Dun Morogh zone.',
        ),
    ),
    array(
        'title' => 'Random Filters',
        'description' => 'Randomly select a subset of bots, optionally using a fixed distribution.',
        'tokens' => array('@random', '@random=', '@fixedrandom', '@fixedrandom='),
        'examples' => array(
            '@random' => 'About a 50% chance that a bot responds.',
            '@random=25' => 'About a 25% chance that a bot responds.',
            '@fixedrandom' => 'A fixed 50% bot subset.',
            '@fixedrandom=25' => 'A fixed 25% bot subset.',
        ),
    ),
    array(
        'title' => 'Gear Filters',
        'description' => 'Select bots by broad gear tier bands derived from gearscore.',
        'tokens' => array('@tier1', '@tier2-3'),
        'examples' => array(
            '@tier1' => 'Bots around tier 1 gear.',
            '@tier2-3' => 'Bots around tier 2 or 3 gear.',
        ),
    ),
    array(
        'title' => 'Quest Filters',
        'description' => 'Select bots that currently have a specific quest.',
        'tokens' => array('@quest='),
        'examples' => array(
            '@quest=523' => 'Bots that currently have quest 523.',
            '@quest=[quest link]' => 'Bots that currently have the linked quest.',
        ),
    ),
);

$macroFilterOptions = array(
    array('group' => 'Strategy', 'label' => '@co=', 'token' => '@co=', 'needsValue' => true, 'placeholder' => 'dps'),
    array('group' => 'Strategy', 'label' => '@noco=', 'token' => '@noco=', 'needsValue' => true, 'placeholder' => 'threat'),
    array('group' => 'Strategy', 'label' => '@nc=', 'token' => '@nc=', 'needsValue' => true, 'placeholder' => 'rpg'),
    array('group' => 'Strategy', 'label' => '@nonc=', 'token' => '@nonc=', 'needsValue' => true, 'placeholder' => 'travel'),
    array('group' => 'Strategy', 'label' => '@react=', 'token' => '@react=', 'needsValue' => true, 'placeholder' => 'pvp'),
    array('group' => 'Strategy', 'label' => '@noreact=', 'token' => '@noreact=', 'needsValue' => true, 'placeholder' => 'pvp'),
    array('group' => 'Strategy', 'label' => '@dead=', 'token' => '@dead=', 'needsValue' => true, 'placeholder' => '<>'),
    array('group' => 'Strategy', 'label' => '@nodead=', 'token' => '@nodead=', 'needsValue' => true, 'placeholder' => '<>'),
    array('group' => 'Role and Combat', 'label' => '@tank', 'token' => '@tank', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Role and Combat', 'label' => '@dps', 'token' => '@dps', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Role and Combat', 'label' => '@heal', 'token' => '@heal', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Role and Combat', 'label' => '@notank', 'token' => '@notank', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Role and Combat', 'label' => '@nodps', 'token' => '@nodps', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Role and Combat', 'label' => '@noheal', 'token' => '@noheal', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Role and Combat', 'label' => '@melee', 'token' => '@melee', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Role and Combat', 'label' => '@ranged', 'token' => '@ranged', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@warrior', 'token' => '@warrior', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@paladin', 'token' => '@paladin', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@hunter', 'token' => '@hunter', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@rogue', 'token' => '@rogue', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@priest', 'token' => '@priest', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@shaman', 'token' => '@shaman', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@mage', 'token' => '@mage', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@warlock', 'token' => '@warlock', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@druid', 'token' => '@druid', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@deathknight', 'token' => '@deathknight', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Raid Icons', 'label' => '@star', 'token' => '@star', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Raid Icons', 'label' => '@circle', 'token' => '@circle', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Raid Icons', 'label' => '@diamond', 'token' => '@diamond', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Raid Icons', 'label' => '@triangle', 'token' => '@triangle', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Raid Icons', 'label' => '@moon', 'token' => '@moon', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Raid Icons', 'label' => '@square', 'token' => '@square', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Raid Icons', 'label' => '@cross', 'token' => '@cross', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Raid Icons', 'label' => '@skull', 'token' => '@skull', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Level', 'label' => '@60', 'token' => '@60', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Level', 'label' => '@10-20', 'token' => '@10-20', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Group', 'label' => '@group', 'token' => '@group', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Group', 'label' => '@group2', 'token' => '@group2', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Group', 'label' => '@group4-6', 'token' => '@group4-6', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Group', 'label' => '@nogroup', 'token' => '@nogroup', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Group', 'label' => '@leader', 'token' => '@leader', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Group', 'label' => '@raid', 'token' => '@raid', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Group', 'label' => '@noraid', 'token' => '@noraid', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Group', 'label' => '@rleader', 'token' => '@rleader', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Guild', 'label' => '@guild', 'token' => '@guild', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Guild', 'label' => '@guild=', 'token' => '@guild=', 'needsValue' => true, 'placeholder' => 'raiders'),
    array('group' => 'Guild', 'label' => '@rank=', 'token' => '@rank=', 'needsValue' => true, 'placeholder' => 'Initiate'),
    array('group' => 'Guild', 'label' => '@noguild', 'token' => '@noguild', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Guild', 'label' => '@gleader', 'token' => '@gleader', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'State', 'label' => '@needrepair', 'token' => '@needrepair', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'State', 'label' => '@bagfull', 'token' => '@bagfull', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'State', 'label' => '@bagalmostfull', 'token' => '@bagalmostfull', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'State', 'label' => '@outside', 'token' => '@outside', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'State', 'label' => '@inside', 'token' => '@inside', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Item Usage', 'label' => '@use=', 'token' => '@use=', 'needsValue' => true, 'placeholder' => '[itemlink]'),
    array('group' => 'Item Usage', 'label' => '@need=', 'token' => '@need=', 'needsValue' => true, 'placeholder' => '[itemlink]'),
    array('group' => 'Item Usage', 'label' => '@greed=', 'token' => '@greed=', 'needsValue' => true, 'placeholder' => '[itemlink]'),
    array('group' => 'Item Usage', 'label' => '@sell=', 'token' => '@sell=', 'needsValue' => true, 'placeholder' => '[itemlink]'),
    array('group' => 'Talent Spec', 'label' => '@holy', 'token' => '@holy', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Talent Spec', 'label' => '@frost', 'token' => '@frost', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Talent Spec', 'label' => '@shadow', 'token' => '@shadow', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Talent Spec', 'label' => '@restoration', 'token' => '@restoration', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Location', 'label' => '@azeroth', 'token' => '@azeroth', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Location', 'label' => '@eastern kingdoms', 'token' => '@eastern kingdoms', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Location', 'label' => '@dun morogh', 'token' => '@dun morogh', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Location', 'label' => '@custom zone/map', 'token' => '@', 'needsValue' => true, 'placeholder' => 'zone or map name'),
    array('group' => 'Random', 'label' => '@random', 'token' => '@random', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Random', 'label' => '@random=', 'token' => '@random=', 'needsValue' => true, 'placeholder' => '25'),
    array('group' => 'Random', 'label' => '@fixedrandom', 'token' => '@fixedrandom', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Random', 'label' => '@fixedrandom=', 'token' => '@fixedrandom=', 'needsValue' => true, 'placeholder' => '25'),
    array('group' => 'Gear', 'label' => '@tier1', 'token' => '@tier1', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Gear', 'label' => '@tier2-3', 'token' => '@tier2-3', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Quest', 'label' => '@quest=', 'token' => '@quest=', 'needsValue' => true, 'placeholder' => '523 or [quest link]'),
);

$macroPresets = array(
    array('group' => 'Movement and Control', 'key' => 'movement_family', 'label' => 'movement / control', 'command' => '', 'mode' => 'options', 'needsValue' => false, 'placeholder' => '', 'optionLabel' => 'Movement action', 'optionPlaceholder' => 'Choose a movement or control command', 'options' => $movementMacroOptions, 'customPlaceholder' => 'type any movement or control command'),
    array('group' => 'RTSC and Positioning', 'key' => 'rtsc_family', 'label' => 'rtsc / formation', 'command' => '', 'mode' => 'options', 'needsValue' => false, 'placeholder' => '', 'optionLabel' => 'RTSC action', 'optionPlaceholder' => 'Choose an RTSC or formation command', 'options' => $rtscMacroOptions, 'customPlaceholder' => 'type any rtsc or formation command'),
    array('group' => 'Utility', 'key' => 'grind', 'label' => 'grind', 'command' => 'grind', 'mode' => 'direct', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Utility', 'key' => 'loot', 'label' => 'loot', 'command' => 'loot', 'mode' => 'direct', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Utility', 'key' => 'quest', 'label' => 'quest command', 'command' => '', 'mode' => 'options', 'needsValue' => false, 'placeholder' => '', 'optionLabel' => 'Quest action', 'optionPlaceholder' => 'Choose a quest command', 'options' => $questMacroOptions, 'customPlaceholder' => 'type any quest command'),
    array('group' => 'Utility', 'key' => 'save_ai', 'label' => 'save ai', 'command' => 'save ai', 'mode' => 'direct', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Utility', 'key' => 'reset_ai', 'label' => 'reset ai', 'command' => 'reset ai', 'mode' => 'direct', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Strategy Setters', 'key' => 'co', 'label' => 'co <strategies>', 'command' => 'co', 'mode' => 'value', 'needsValue' => true, 'placeholder' => '+dps,+dps assist,-threat'),
    array('group' => 'Strategy Setters', 'key' => 'nc', 'label' => 'nc <strategies>', 'command' => 'nc', 'mode' => 'value', 'needsValue' => true, 'placeholder' => '+rpg,+quest,+grind'),
    array('group' => 'Strategy Setters', 'key' => 'react', 'label' => 'react <strategies>', 'command' => 'react', 'mode' => 'value', 'needsValue' => true, 'placeholder' => '+pvp'),
    array('group' => 'Strategy Setters', 'key' => 'dead', 'label' => 'dead <strategies>', 'command' => 'dead', 'mode' => 'value', 'needsValue' => true, 'placeholder' => '+ghost'),
);
foreach ($macroClassPresetConfigs as $classKey => $classConfig) {
    $macroPresets[] = array(
        'group' => 'Class Strategy Builders',
        'key' => 'class_' . $classKey,
        'label' => $classConfig['label'],
        'command' => '',
        'mode' => 'class_strategies',
        'needsValue' => false,
        'placeholder' => '',
        'optionLabel' => 'State',
        'optionPlaceholder' => 'Choose a state',
        'options' => $macroStatePresetOptions,
        'strategyOptions' => $classConfig['strategies'],
    );
}
$macroPresets[] = array('group' => 'Custom', 'key' => 'custom', 'label' => 'Custom command', 'command' => '', 'mode' => 'custom', 'needsValue' => true, 'placeholder' => 'type any bot whisper command');
$macroLayerFilterOptions = array_values(array_filter($macroFilterOptions, function ($option) {
    return empty($option['needsValue']);
}));
$gmSecurityValues = array();
$gmPrefixValues = array();
foreach ($gmCommands as $gmCommand) {
    $security = trim((string)($gmCommand['security'] ?? ''));
    if ($security !== '') {
        $gmSecurityValues[$security] = true;
    }
    $commandName = trim((string)($gmCommand['name'] ?? ''));
    if ($commandName !== '') {
        $parts = preg_split('/\s+/', $commandName);
        $prefix = strtolower((string)($parts[0] ?? ''));
        if ($prefix !== '') {
            $gmPrefixValues[$prefix] = true;
        }
    }
}
$gmSecurityValues = array_keys($gmSecurityValues);
sort($gmSecurityValues, SORT_NATURAL);
$gmPrefixValues = array_keys($gmPrefixValues);
sort($gmPrefixValues, SORT_NATURAL);

        $botCommandCards = array_values(array_map(function ($topic) {
            $topic['search_blob'] = strtolower(
                ($topic['name'] ?? '') . ' ' .
                ($topic['category'] ?? '') . ' ' .
                ($topic['subcategory'] ?? '') . ' ' .
                ($topic['security'] ?? '') . ' ' .
                ($topic['help'] ?? '') . ' ' .
                implode(' ', $topic['state_tags'] ?? array()) . ' ' .
                implode(' ', $topic['role_tags'] ?? array()) . ' ' .
                implode(' ', $topic['class_tags'] ?? array())
            );
            $topic['type_value'] = strtolower((string)($topic['category'] ?? ''));
            return $topic;
        }, $botCommands));

        $gmCommandCards = array_values(array_map(function ($cmd) {
            $parts = preg_split('/\s+/', trim((string)($cmd['name'] ?? '')));
            $cmd['prefix'] = strtolower((string)($parts[0] ?? ''));
            $cmd['search_blob'] = strtolower(
                ($cmd['name'] ?? '') . ' ' .
                ($cmd['security'] ?? '') . ' ' .
                ($cmd['help'] ?? '')
            );
            return $cmd;
        }, $gmCommands));

        $chatFilterCards = array_values(array_map(function ($family) {
            $family['search_blob'] = strtolower(
                $family['title'] . ' ' .
                $family['description'] . ' ' .
                implode(' ', $family['tokens']) . ' ' .
                implode(' ', array_keys($family['examples'])) . ' ' .
                implode(' ', array_values($family['examples']))
            );
            return $family;
        }, $chatFilterFamilies));

        $clientState = array(
            'chatFilterOptions' => $macroFilterOptions,
            'layerFilterOptions' => $macroLayerFilterOptions,
            'macroPresets' => $macroPresets,
        );

        return array(
            'activeCommandTab' => $activeCommandTab,
            'botCommandCards' => $botCommandCards,
            'gmCommandCards' => $gmCommandCards,
            'chatFilterCards' => $chatFilterCards,
            'gmSecurityValues' => $gmSecurityValues,
            'gmPrefixValues' => $gmPrefixValues,
            'botcommandsClientStateJson' => json_encode($clientState),
        );
    }
}

