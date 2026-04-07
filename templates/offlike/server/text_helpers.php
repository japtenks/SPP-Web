
<?php
function spp_class_palette() {
  static $palette = null;
  if ($palette !== null) {
    return $palette;
  }

  $palette = [
    1  => ['name' => 'Warrior',      'slug' => 'warrior',      'color' => '#C79C6E', 'rgb' => '199,156,110'],
    2  => ['name' => 'Paladin',      'slug' => 'paladin',      'color' => '#F58CBA', 'rgb' => '245,140,186'],
    3  => ['name' => 'Hunter',       'slug' => 'hunter',       'color' => '#ABD473', 'rgb' => '171,212,115'],
    4  => ['name' => 'Rogue',        'slug' => 'rogue',        'color' => '#FFF569', 'rgb' => '255,245,105'],
    5  => ['name' => 'Priest',       'slug' => 'priest',       'color' => '#FFFFFF', 'rgb' => '255,255,255'],
    6  => ['name' => 'Death Knight', 'slug' => 'deathknight',  'color' => '#C41F3B', 'rgb' => '196,31,59'],
    7  => ['name' => 'Shaman',       'slug' => 'shaman',       'color' => '#0070DE', 'rgb' => '0,112,222'],
    8  => ['name' => 'Mage',         'slug' => 'mage',         'color' => '#69CCF0', 'rgb' => '105,204,240'],
    9  => ['name' => 'Warlock',      'slug' => 'warlock',      'color' => '#9482C9', 'rgb' => '148,130,201'],
    11 => ['name' => 'Druid',        'slug' => 'druid',        'color' => '#FF7D0A', 'rgb' => '255,125,10'],
  ];

  return $palette;
}

function spp_class_palette_by_name() {
  static $byName = null;
  if ($byName !== null) {
    return $byName;
  }

  $byName = [];
  foreach (spp_class_palette() as $classId => $meta) {
    $byName[$meta['name']] = $meta + ['id' => $classId];
  }
  return $byName;
}

function highlight_class_names($text) {
  $classesToCSS = [];
  foreach (spp_class_palette_by_name() as $name => $meta) {
    $classesToCSS[$name] = $name === 'Death Knight' ? 'is-dk' : 'is-' . $meta['slug'];
  }

  $text = str_replace(['<b>','</b>'],'',$text);

  return preg_replace_callback(
    '/\b(' . implode('|', array_map('preg_quote', array_keys($classesToCSS))) . ')(s)?\b/i',
    function($m) use ($classesToCSS){
      $name = ucfirst(strtolower($m[1]));
      $css  = $classesToCSS[$name] ?? '';
      $suffix = $m[2] ?? '';
      return "<span class='{$css}'><b>{$name}{$suffix}</b></span>";
    },
    $text
  );
}
