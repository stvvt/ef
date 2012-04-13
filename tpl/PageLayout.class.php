<?php



/**
 * Клас 'tpl_PageLayout' - Лейаута на страница от приложението
 *
 * Файлът може да се подмени с друг
 *
 *
 * @category  ef
 * @package   tpl
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class tpl_PageLayout extends core_ET
{
    
    
    /**
     * @todo Чака за документация...
     */
    function tpl_PageLayout()
    {
        // Ако сме определили височината на прозореца, задаваме мин. височина на съдържанието 
        if(Mode::get('windowHeight') && !Mode::is('screenMode', 'narrow')) {
            $minHeighStyle = "style='min-height:" . (Mode::get('windowHeight') - 150) . "px;'";
        } else {
            $minHeighStyle = '';
        }
        
        // Задаваме лейаута на страницата
        $this->core_ET("<div class='clearfix21' style='display: inline-block; min-width:100%;'><div id=\"framecontentTop\"  class=\"container\">" .
            "[#PAGE_HEADER#]" .
            "</div>" .
            "<div id=\"maincontent\" {$minHeighStyle}><div>" .
            "<!--ET_BEGIN NAV_BAR--><div id=\"navBar\">[#NAV_BAR#]</div>\n<!--ET_END NAV_BAR--><div class='clearfix' style='min-height:10px;'></div>" .
            " <!--ET_BEGIN NOTIFICATION-->[#NOTIFICATION#]<!--ET_END NOTIFICATION-->" .
            "[#PAGE_CONTENT#]" .
            "</div></div>" .
            "<div id=\"framecontentBottom\" class=\"container\">" .
            "[#PAGE_FOOTER#]" .
            "</div></div>");
    }
}