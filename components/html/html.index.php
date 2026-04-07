<?php
if(INCLUDED!==true)exit;
// ==================== //
if (!function_exists('spp_render_readme_html')) {
    function spp_render_readme_inline($text) {
        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $escaped = preg_replace_callback('/`([^`]+)`/', function ($matches) {
            return '<code>' . $matches[1] . '</code>';
        }, $escaped);
        $escaped = preg_replace_callback('/\[(.*?)\]\((.*?)\)/', function ($matches) {
            $label = $matches[1];
            $href = htmlspecialchars_decode($matches[2], ENT_QUOTES);
            $safeHref = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
            return '<a href="' . $safeHref . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>';
        }, $escaped);

        return $escaped;
    }

    function spp_render_readme_html($markdown) {
        $lines = preg_split("/\r\n|\n|\r/", (string)$markdown);
        $html = array();
        $paragraph = array();
        $listType = null;
        $inCodeBlock = false;
        $codeLines = array();

        $flushParagraph = function () use (&$paragraph, &$html) {
            if (!empty($paragraph)) {
                $html[] = '<p>' . spp_render_readme_inline(implode(' ', $paragraph)) . '</p>';
                $paragraph = array();
            }
        };

        $closeList = function () use (&$listType, &$html) {
            if ($listType !== null) {
                $html[] = '</' . $listType . '>';
                $listType = null;
            }
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (preg_match('/^```([a-zA-Z0-9_-]+)?$/', $trimmed, $codeMatch)) {
                $flushParagraph();
                $closeList();
                if ($inCodeBlock) {
                    $classAttr = '';
                    if (!empty($codeMatch[1])) {
                        $classAttr = ' class="language-' . htmlspecialchars($codeMatch[1], ENT_QUOTES, 'UTF-8') . '"';
                    }
                    $html[] = '<pre><code' . $classAttr . '>' . htmlspecialchars(implode("\n", $codeLines), ENT_QUOTES, 'UTF-8') . '</code></pre>';
                    $codeLines = array();
                    $inCodeBlock = false;
                } else {
                    $inCodeBlock = true;
                    $codeLines = array();
                }
                continue;
            }

            if ($inCodeBlock) {
                $codeLines[] = $line;
                continue;
            }

            if ($trimmed === '') {
                $flushParagraph();
                $closeList();
                continue;
            }

            if (preg_match('/^(#{1,3})\s+(.+)$/', $trimmed, $headingMatch)) {
                $flushParagraph();
                $closeList();
                $level = strlen($headingMatch[1]);
                $html[] = '<h' . $level . '>' . spp_render_readme_inline($headingMatch[2]) . '</h' . $level . '>';
                continue;
            }

            if (preg_match('/^[-*]\s+(.+)$/', $trimmed, $listMatch)) {
                $flushParagraph();
                if ($listType !== 'ul') {
                    $closeList();
                    $listType = 'ul';
                    $html[] = '<ul>';
                }
                $html[] = '<li>' . spp_render_readme_inline($listMatch[1]) . '</li>';
                continue;
            }

            if (preg_match('/^\d+\.\s+(.+)$/', $trimmed, $listMatch)) {
                $flushParagraph();
                if ($listType !== 'ol') {
                    $closeList();
                    $listType = 'ol';
                    $html[] = '<ol>';
                }
                $html[] = '<li>' . spp_render_readme_inline($listMatch[1]) . '</li>';
                continue;
            }

            $paragraph[] = $trimmed;
        }

        if ($inCodeBlock) {
            $html[] = '<pre><code>' . htmlspecialchars(implode("\n", $codeLines), ENT_QUOTES, 'UTF-8') . '</code></pre>';
        }

        $flushParagraph();
        $closeList();

        return implode("\n", $html);
    }
}

if($_GET['text']=='license'){
    $pathway_info[] = array('title'=>'License','link'=>'');
    $content = lang_resource('gnu_gpl.html');
} elseif ($_GET['text']=='readme') {
    $pathway_info[] = array('title'=>'README','link'=>'');
    $readmePath = dirname(__DIR__, 2) . '/README.md';
    if (file_exists($readmePath)) {
        $content = spp_render_readme_html((string)file_get_contents($readmePath));
    } else {
        $content = '<p>README.md was not found in the site root.</p>';
    }
}
?>
