<?php

if (!function_exists('spp_wbuffbuilder_lookup_results')) {
    function spp_wbuffbuilder_lookup_results(PDO $worldPdo, string $term): array
    {
        $term = trim($term);
        if ($term === '') {
            return array();
        }

        $stmt = $worldPdo->prepare("
            SELECT Id, SpellName, Rank1
            FROM spell_template
            WHERE SpellName <> ''
              AND (SpellName LIKE :term OR Id = :idExact)
            ORDER BY
              CASE WHEN SpellName LIKE :prefix THEN 0 ELSE 1 END,
              SpellName ASC
            LIMIT 25
        ");
        $stmt->execute(array(
            ':term' => '%' . $term . '%',
            ':prefix' => $term . '%',
            ':idExact' => ctype_digit($term) ? (int)$term : -1,
        ));

        $results = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $label = trim((string)$row['SpellName']);
            $rank = trim((string)($row['Rank1'] ?? ''));
            if ($rank !== '') {
                $label .= ' (' . $rank . ')';
            }
            $results[] = array(
                'id' => (string)$row['Id'],
                'label' => $label,
                'name' => trim((string)$row['SpellName']),
                'rank' => $rank,
            );
        }

        return $results;
    }
}

if (!function_exists('spp_wbuffbuilder_spell_catalog')) {
    function spp_wbuffbuilder_spell_catalog(PDO $worldPdo): array
    {
        $liveCatalog = array();
        $seedIds = array(
            17626,17627,17628,22888,24425,16609,22817,22818,22820,15366,24382,17538,11405,17539,
            11348,11371,25661,3593,16323,16327,16329,10668,10669,12174,12176,12177,24799,10693,
            18194,11474,10692,24361,24363,26276,22730,10305
        );
        $stmt = $worldPdo->query('SELECT Id, SpellName, Rank1 FROM spell_template WHERE Id IN (' . implode(',', $seedIds) . ')');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $name = trim((string)$row['SpellName']);
            $rank = trim((string)($row['Rank1'] ?? ''));
            if ($name !== '') {
                $liveCatalog[(string)$row['Id']] = $rank !== '' ? ($name . ' (' . $rank . ')') : $name;
            }
        }

        $catalog = array(
            '17626' => 'Flask of the Titans',
            '17627' => 'Flask of Distilled Wisdom',
            '17628' => 'Flask of Supreme Power',
            '22888' => 'Rallying Cry of the Dragonslayer',
            '24425' => 'Spirit of Zandalar',
            '16609' => 'Warchief\'s Blessing',
            '22817' => 'Fengus\' Ferocity (Dire Maul)',
            '22818' => 'Mol\'dar\'s Moxie (Dire Maul)',
            '22820' => 'Slip\'kik\'s Savvy (Dire Maul)',
            '15366' => 'Songflower Serenade',
            '24382' => 'Sayge\'s Dark Fortune',
            '17538' => 'Elixir of the Mongoose',
            '11405' => 'Elixir of Giants',
            '17539' => 'Greater Arcane Elixir',
            '11348' => 'Elixir of Greater Agility',
            '11371' => 'Juju Power',
            '25661' => 'Rumsey Rum Black Label',
            '3593'  => 'Guide utility consumable',
            '16323' => 'Guide melee consumable',
            '16327' => 'Guide caster consumable',
            '16329' => 'Guide physical consumable',
            '10668' => 'Guide tank consumable',
            '10669' => 'Guide melee elixir',
            '12174' => 'Guide stamina / tank consumable',
            '12176' => 'Guide caster food or elixir',
            '12177' => 'Guide caster food or elixir',
            '24799' => 'Guide threat or melee consumable',
            '10693' => 'Guide healing consumable',
            '18194' => 'Guide mana or healer consumable',
            '11474' => 'Guide shadow or caster consumable',
            '10692' => 'Guide caster damage consumable',
            '24361' => 'Spirit of Zanza or shared raid buff',
            '24363' => 'Guide mana or caster world buff',
            '26276' => 'Guide caster weapon or oil buff',
            '22730' => 'Guide caster damage consumable',
        );

        foreach ($liveCatalog as $spellId => $spellLabel) {
            $catalog[$spellId] = $spellLabel;
        }

        return $catalog;
    }
}

if (!function_exists('spp_wbuffbuilder_class_catalog')) {
    function spp_wbuffbuilder_class_catalog(): array
    {
        return array(
            '1' => array('label' => 'Warrior', 'presets' => array(
                array('key' => 'warrior_0', 'spec' => 0, 'name' => 'pve arms', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17626, 22888, 24382, 24425, 16609, 22817, 22818, 15366, 17538, 11405, 11348, 24361, 11371, 25661, 3593, 16323, 16329, 10668)),
                array('key' => 'warrior_1', 'spec' => 1, 'name' => 'pve fury', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17626, 22888, 24382, 24425, 16609, 22817, 17538, 22818, 15366, 11405, 10669, 11348, 24361, 11371, 24799, 3593, 16323, 16329, 12174)),
                array('key' => 'warrior_2', 'spec' => 2, 'name' => 'pve prot', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17626, 22888, 24382, 24425, 16609, 22817, 17538, 22818, 15366, 11405, 10669, 11348, 24361, 11371, 24799, 3593, 16323, 16329, 12174)),
                array('key' => 'warrior_3', 'spec' => 3, 'name' => 'pvp arms', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17626, 22888, 24382, 24425, 16609, 22817, 22818, 15366, 17538, 11405, 11348, 24361, 11371, 25661, 3593, 16323, 16329, 10668, 12174, 10305)),
            )),
            '2' => array('label' => 'Paladin', 'presets' => array(
                array('key' => 'paladin_1', 'spec' => 1, 'name' => 'pve dps ret (geared ret)', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17627, 24382, 22888, 24425, 16609, 22817, 22818, 22820, 15366, 24363, 24361, 3593, 12176, 12177)),
                array('key' => 'paladin_2', 'spec' => 2, 'name' => 'pve heal holy (sanctuary)', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17628, 24382, 22888, 24425, 16609, 22817, 22818, 22820, 15366, 17538, 11405, 17539, 11348, 24361, 11371, 25661, 3593, 16323, 16329, 10668, 12174, 10305)),
                array('key' => 'paladin_3', 'spec' => 3, 'name' => 'pve heal holy (prot holy shock taunt)', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17628, 24382, 22888, 24425, 16609, 22817, 22818, 22820, 15366, 17538, 11405, 17539, 24363, 11348, 24361, 24799, 3593, 16323, 16329)),
            )),
            '3' => array('label' => 'Hunter', 'presets' => array(
                array('key' => 'hunter_0', 'spec' => 0, 'name' => 'pve dps mm (mm/sv)', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17627, 24382, 22888, 24425, 16609, 22817, 22818, 15366, 17538, 11405, 10669, 24361, 3593, 16329, 16327, 12174, 12176, 12177)),
            )),
            '4' => array('label' => 'Rogue', 'presets' => array(
                array('key' => 'rogue_0', 'spec' => 0, 'name' => 'pve dps assassination', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17626, 24382, 22888, 24425, 16609, 22817, 22818, 15366, 17538, 11405, 10669, 11348, 24361, 24799, 3593, 16323, 16329, 24799, 12174)),
            )),
            '5' => array('label' => 'Priest', 'presets' => array(
                array('key' => 'priest_1', 'spec' => 1, 'name' => 'pve heal holy', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17627, 24382, 22888, 24425, 16609, 22818, 22820, 15366, 24363, 24361, 3593, 10693, 18194, 16327, 12176, 12177)),
                array('key' => 'priest_2', 'spec' => 2, 'name' => 'pve dps shadow', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17627, 24382, 22888, 24425, 16609, 22818, 22820, 15366, 24363, 24361, 3593, 10693, 18194, 16327, 12176, 12177)),
                array('key' => 'priest_3', 'spec' => 3, 'name' => 'pvp dps disc', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17628, 24382, 22888, 24425, 16609, 22818, 22820, 15366, 11474, 24363, 24361, 3593, 10693, 18194, 16327, 12176, 12177)),
            )),
            '7' => array('label' => 'Shaman', 'presets' => array(
                array('key' => 'shaman_1', 'spec' => 1, 'name' => 'pve dps elem (nature\'s swiftness)', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17628, 24382, 22888, 24425, 16609, 22818, 22820, 15366, 17539, 10692, 24363, 24361, 3593, 18194, 16327, 12176, 12177)),
                array('key' => 'shaman_2', 'spec' => 2, 'name' => 'pve dps elem (hand of edward the odd)', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17628, 24382, 22888, 24425, 16609, 22817, 22818, 15366, 17538, 11405, 24363, 11348, 24361, 3593, 18194, 10668, 16323)),
            )),
            '8' => array('label' => 'Mage', 'presets' => array(
                array('key' => 'mage_0', 'spec' => 0, 'name' => 'pve dps arcane', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17628, 24382, 22888, 24425, 16609, 22818, 22820, 15366, 17539, 10692, 24363, 24361, 26276, 3593, 16327, 12176, 12177, 22730)),
            )),
            '9' => array('label' => 'Warlock', 'presets' => array(
                array('key' => 'warlock_0', 'spec' => 0, 'name' => 'pve dps demo (ds/ruin)', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17628, 24382, 22888, 24425, 16609, 22818, 22820, 15366, 11474, 10692, 24363, 24361, 3593, 16327, 12176, 12177, 22730)),
            )),
            '11' => array('label' => 'Druid', 'presets' => array()),
        );
    }
}

if (!function_exists('spp_wbuffbuilder_load_page_state')) {
    function spp_wbuffbuilder_load_page_state(array $args = array()): array
    {
        $realmMap = (array)($args['realm_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $get = (array)($args['get'] ?? $_GET);
        $realmId = !empty($realmMap) ? (int)spp_resolve_realm_id($realmMap) : 1;
        $worldBuffSpellCatalog = array();

        try {
            $worldPdo = spp_get_pdo('world', $realmId);

            if (isset($get['wbuff_lookup'])) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(spp_wbuffbuilder_lookup_results($worldPdo, (string)$get['wbuff_lookup']));
                exit;
            }

            $worldBuffSpellCatalog = spp_wbuffbuilder_spell_catalog($worldPdo);
        } catch (Throwable $e) {
            if (isset($get['wbuff_lookup'])) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(array());
                exit;
            }
        }

        return array(
            'worldBuffClasses' => spp_wbuffbuilder_class_catalog(),
            'worldBuffSpellCatalog' => $worldBuffSpellCatalog,
            'pathway_info' => array(
                array('title' => 'World Buff Builder', 'link' => ''),
            ),
        );
    }
}
