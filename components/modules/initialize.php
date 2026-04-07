<?php
if(INCLUDED!==true)exit;

//special module load function definitions//
function module_loadLanguages($modulename){
    $module_lang = array();
    $langDir = 'components/modules/'.$modulename.'/lang/';
    $langFilePath = $langDir . 'en.English.lang';

    if (!file_exists($langFilePath)) {
        return $module_lang;
    }

    $langfile = @file_get_contents($langFilePath);
    $langfile = str_replace("\n",'',$langfile);
    $langfile = str_replace("\r",'',$langfile);
    $langfile = explode('|=|',$langfile);
    foreach($langfile as $langstr){
        $langstra = explode(' :=: ',$langstr);
        if(isset($langstra[1]))$module_lang[$langstra[0]] = $langstra[1];
    }

    return $module_lang;
}

function php4_scandir($dir,$listDirectories=false, $skipDots=true) {
    $dirArray = array();
    if ($handle = opendir($dir)) {
        while (false !== ($file = readdir($handle))) {
            if (($file != "." && $file != "..") || $skipDots == true) {
                if($listDirectories == false) { if(is_dir($file)) { continue; } }
                array_push($dirArray,basename($file));
            }
        }
        closedir($handle);
    }
    return $dirArray;
}

function spp_normalize_module_menu_bucket($bucketName) {
    $legacyBuckets = array(
        '1-menuNews' => '1-News',
        '2-menuAccount' => '2-Account',
        '3-menuGameGuide' => '3-Game Guide',
        '4-menuWorkshop' => '4-Workshop',
        '6-menuForums' => '6-Forums',
        '7-menuArmory' => '7-Armory',
        '8-menuSupport' => '8-Support',
    );

    return $legacyBuckets[(string)$bucketName] ?? (string)$bucketName;
}
    
    function returnmodules() {
        $modules_directories = array();
    
        $modules_files = php4_scandir('components/modules/');
        foreach($modules_files as $file){
            if(is_dir('components/modules/'.$file) && $file != '.' && $file != '..') {
                 $modules_directories[] = $file;
            }
        }
    
        return $modules_directories;
    }
    
    //End special module functions//
    
    $modules_installed = returnmodules();
    
    $module_mainnav_links = array();
    $module_lang = array();
    
    foreach($modules_installed as $module){
        $module_menu = null;
        $module_sidebararray = null;
        @include('components/modules/'.$module.'/menuconfig.php');
        $moduleLabelKey = 'module_' . $module;
        $moduleLabel = $module_lang[$moduleLabelKey] ?? ($module . ' Module');
        $moduleSidebarMenu = spp_normalize_module_menu_bucket((string)($module_menu['sidebarmenu'] ?? ''));
    
        if($moduleSidebarMenu !== '') {
            $module_sidebararray = array(
                $moduleLabel,
                'index.php?n=modules&sub='.$module,
                $module_menu['view'],
            );
            $module_mainnav_links[$moduleSidebarMenu][] = $module_sidebararray;
        }
    
        if($module_menu['contextmenu']) {
            $module_contextmenuarray = array(
                'title' => $moduleLabel,
                'link' => 'index.php?n=modules&sub='.$module,
            );
        }
        
        $module_lang = array_merge($module_lang, module_loadLanguages($module));
        $moduleLabel = $module_lang[$moduleLabelKey] ?? ($module . ' Module');
        if ($moduleSidebarMenu !== '') {
            $module_mainnav_links[$moduleSidebarMenu][array_key_last($module_mainnav_links[$moduleSidebarMenu])][0] = $moduleLabel;
        }
    }

$mainnav_links = array_merge_recursive($mainnav_links, $module_mainnav_links);

$allowed_ext[] = 'modules';
?>
