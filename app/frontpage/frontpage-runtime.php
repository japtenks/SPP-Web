<?php

if (!function_exists('spp_frontpage_increment_hit_counter')) {
    function spp_frontpage_increment_hit_counter($config): void
    {
        if (!$config || !(int)$config->components->right_section->hitcounter) {
            return;
        }

        $counterPath = spp_template_path('hitcounter.txt');
        $hits = (int)@file_get_contents($counterPath);
        $hits++;
        @file_put_contents($counterPath, (string)$hits);
    }
}

