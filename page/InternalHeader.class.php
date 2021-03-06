<?php



/**
 * Клас 'page_InternalHeader' - Шаблон за header с меню за вътрешните страници
 *
 * Файлът може да се подмени с друг
 *
 *
 * @category  ef
 * @package   page
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class page_InternalHeader extends core_ET {
    
    
    /**
     * Добавя елемент от менюто
     */
    function addMenuItem($title, $row, $ctr, $act = 'default')
    {
        static $noFirst = array();
        
        if (!Mode::is('screenMode', 'narrow')) {
            if($noFirst[$row]) {
                $this->header->append(" | ", $row);
            } else {
                $this->header->append("\n»&nbsp;", $row);
            }
        } else {
            if($noFirst[$row]) {
                $this->header->append("\n<br>» ", $row);
            } else {
                $this->header->append("\n» ", $row);
            }
        }
        
        $noFirst[$row] = TRUE;
        
        if(Mode::get('pageMenu') == $title) {
            $attr = array('class' => 'menuItem selected');
        } else {
            $attr = array('class' => 'menuItem');
        }
        
        if($ctr) {
            $url = is_array($ctr) ? toUrl($ctr) : toUrl(array($ctr, $act));
            $this->header->append(ht::createLink(tr($title), $url, FALSE, $attr), $row);
        } else {
            $this->header->append(tr($title) , $row);
        }
        
        $this->header->append("\n", $row);
    }
}