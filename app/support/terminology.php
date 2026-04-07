<?php

if (!function_exists('spp_terminology_public')) {
    /**
     * Public copy uses a narrower taxonomy than admin/operator screens:
     * - character = bot-controlled character
     * - human = non-rndbot account or character
     * - all = both groups together
     */
    function spp_terminology_public(): array
    {
        return array(
            'character' => 'Character',
            'characters' => 'Characters',
            'human' => 'Human',
            'humans' => 'Humans',
            'all' => 'All',
            'all_characters' => 'All Characters',
            'character_vs_human_split' => 'Character vs Human Split',
            'humans_only' => 'Humans Only',
            'humans_plus_guild_characters' => 'Humans + Guild Characters',
            'include_characters' => 'Include characters',
            'owned_by_humans' => 'Owned By Humans',
            'owned_by_all_characters' => 'Owned By All Characters',
        );
    }
}

if (!function_exists('spp_terminology_admin')) {
    function spp_terminology_admin(): array
    {
        return array(
            'bot' => 'Bot',
            'bots' => 'Bots',
            'player' => 'Player',
            'players' => 'Players',
        );
    }
}

