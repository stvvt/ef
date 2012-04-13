<?php



/**
 * Клас  'type_Combodate' - Представя дати с избираеми по отделно части (Д/М/Г)
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Combodate extends type_Varchar {
    
    
    /**
     * Дължина на полето в mySql таблица
     */
    var $dbFieldLen = 10;     // XX-XX-XXXX
    
    /**
     * Атрибути на елемента "<TD>" когато в него се записва стойност от този тип
     */
    var $cellAttr = 'align="right"';
    
    
    /**
     * Получава дата от трите входни стойности
     */
    function fromVerbal($value)
    {
        if(count($value) == 3) {
            $date = $value[0] . '-' . $value[1] . '-' . $value[2];
            
            if($value[2] && $value[1] && $value[0]) {
                // TODO
            }
            
            return $date;
        }
    }
    
    
    /**
     * Показва датата във вербален формат
     */
    function toVerbal($value)
    {
        if(empty($value)) return NULL;
        
        if($value) {
            $div = $this->params['div'] ? $this->params['div'] : '-';
            
            list($d, $m, $y) = explode($div, $value);
            
            if(strlen($d) > 2) {
                $t = $d;
                $d = $y;
                $y = $t;
            }
            
            $d = str_pad($d, 2, "0", STR_PAD_LEFT);
            $m = str_pad($m, 2, "0", STR_PAD_LEFT);
            
            if($y>0 && $m>0 && $d>0) {
                $date = "{$d}{$div}{$m}{$div}{$y}";
            } elseif($y>0 && $m>0) {
                $date = "{$m}{$div}{$y}";
            } elseif($d>0 && $m>0) {
                $date = "{$d}{$div}{$m}";
            } elseif($y>0) {
                $date = "{$y}";
            }
            
            return $date;
        }
    }
    
    
    /**
     * Генерира поле за въвеждане на дата, състоящо се от
     * селектори за годината, месеца и деня
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $div = $this->params['div'] ? $this->params['div'] : '-';
        
        if($value) {
            list($d, $m, $y) = explode($div, $value);
            
            if(strlen($d) > 2) {
                $t = $d;
                $d = $y;
                $y = $t;
            }
        }
        
        $days = array('??' => '');
        
        for($i = 1; $i <= 31; $i++) $days[$i] = $i;
        
        $months = array('??' => '') + dt::getMonthOptions();
        
        $years = array('????' => '');
        $min = $this->params['minYear'] ? $this->params['minYear'] : 1900;
        $max = $this->params['maxYear'] ? $this->params['maxYear'] : 2030;
        
        for($i = $min; $i < $max; $i++) $years[$i] = $i;
        
        $tpl = ht::createSelect($name . '[]', $days, $d, $attr);
        $tpl->append(ht::createSelect($name . '[]', $months, $m, $attr));
        $tpl->append(ht::createSelect($name . '[]', $years, $y, $attr));
        $tpl = new ET("<span style=\"white-space:nowrap;\">[#1#]</span>", $tpl);
        
        return $tpl;
    }
}