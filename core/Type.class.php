<?php



/**
 * Клас 'core_Type' - Прототип на класовете за типове
 *
 *
 * @category  all
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Type extends core_BaseClass
{
    
    
    /**
     * Конструктор. Дава възможност за инициализация
     */
    function core_Type($params = array())
    {
        if(is_array($params) && count($params)) {
            $this->params = $params;
        }
    }
    
    
    /**
     * Премахваме HTML елементите при визуализацията на всички типове,
     * които не предефинират тази функция
     */
    function toVerbal_($value)
    {
        if ($value === NULL) return NULL;
        
        $value = self::escape($value);
        
        if ($this->params['truncate'] && mb_strlen($value) > $this->params['truncate']) {
            $value = mb_substr($value, 0, $this->params['truncate']);
            $value .= "...";
        }
        
        if ($this->params['wordwrap'] && strlen($value)) {
            $value = wordwrap($value, $this->params['wordwrap'], "<br />\n");
        }
        
        return $value;
    }
    
    
    /**
     * Ескейпване на HTML таговете
     */
    static function escape($value)
    {
        $value = str_replace(array("&", "<"), array('&amp;', '&lt;'), $value);
        
        return $value;
    }
    
    
    /**
     * Връща стойността по подразбиране за съответния тип
     */
    function defVal()
    {
        return $this->defaultValue ? $this->defaultValue : '';
    }
    
    
    /**
     * Връща атрибутите на елемента TD необходими при таблично
     * представяне на стойността
     */
    function getCellAttr()
    {
        return $this->params['cellAttr'] ? $this->params['cellAttr'] : ($this->cellAttr ? $this->cellAttr : '');
    }
    
    
    /**
     * Този метод трябва да конвертира от вербално към вътрешно
     * представяне дадената стойност
     */
    function fromVerbal_($verbalValue)
    {
        return $verbalValue;
    }
    
    
    /**
     * Този метод трябва генерира хHTML код, който да представлява
     * полето за въвеждане на конкретния формат информация
     */
    function renderInput_($name, $value = '', $attr = array())
    {
        $value = $this->toVerbal($value);
        
        return ht::createTextInput($name, $value, $attr);
    }
    
    
    /**
     * Връща размера на полето в базата данни
     */
    function getDbFieldSize()
    {
        setIfNot($size, $this->params['size'], $this->params[0], $this->dbFieldLen);
        
        return $size;
    }
    
    
    /**
     * Връща атрибутите на MySQL полето
     */
    function getMysqlAttr()
    {
        $res = new stdClass();

        $res->size = $this->getDbFieldSize();
        
        $res->type = strtoupper($this->dbFieldType);
        
        // Ключовете на опциите на типа, са опциите в MySQL
        if(count($this->options)) {
            foreach($this->options as $key => $val) {
                $res->options[] = $key;
            }
        }
        
        if (is_array($this->params) && in_array('unsigned', array_map('strtolower', $this->params))) {
            $res->unsigned = TRUE;
        }
        
        if($this->params['collate']) {
            $res->collation = $this->params['collate'];
        } elseif($this->params['ci']) {
            $res->collation = 'utf8_general_ci';
        }
        
        return $res;
    }
    
    
    /**
     * Връща MySQL-ската стойност на стойността, така обезопасена,
     * че да може да участва в заявки
     */
    function toMysql($value, $db, $notNull, $defValue)
    {
        if($value === NULL) {
            if(!$notNull) {
                $mysqlVal = 'NULL';
            } else {
                if($defValue === NULL) {
                    $defValue = "''";
                }
                $mysqlVal = "'" . $db->escape($defValue) . "'";
            }
        } else {
            $mysqlVal = "'" . $db->escape($value) . "'";
        }
        
        return $mysqlVal;
    }
    
    
    /**
     * Проверява зададената стойност дали е допустима за този тип.
     * Стойността е във вътрешен формат (MySQL)
     * Връща масив с ключове 'warning', 'error' и 'value'.
     * Ако стойността е съмнителна 'warning' съдържа предупреждение
     * Ако стойността е невалидна 'error' съдържа съобщение за грешка
     * Ако стойността е валидна или съмнителна във 'value' може да се
     * съдържа 'нормализирана' стойност
     */
    function isValid($value)
    {
        if ($value !== NULL) {
            
            $res = array();
            
            // Проверка за максимална дължина
            $size = $this->getDbFieldSize();
            
            if ($size && mb_strlen($value) > $size) {
                $res['error'] = "Текстът е над допустимите|* {$size} |символа";
            }
            
            // Използваме валидираща функция, ако е зададена
            if (isset($this->params['valid'])) {
                cls::callFunctArr($this->params['valid'], array($value, &$res));
            }
            
            // Проверяваме дали отговаря на регулярен израз, ако е зададен
            if (!$res['error'] && isset($this->params['regexp'])) {
                if (!eregi($this->params['regexp'], $value)) {
                    $res['error'] = 'Синтактична грешка';
                }
            }
            
            // Проверяваме дали не е под минималната стойност, ако е зададена
            if (!$res['error'] && isset($this->params['min'])) {
                if($value < $this->params['min']) {
                    $res['error'] = 'Под допустимото' . "|* - '" .
                    $this->toVerbal($this->params['min']) . "'";
                }
            }
            
            // Проверяваме дали е над недостижимия минимум, ако е зададен
            if (!$res['error'] && isset($this->params['Min'])) {
                if($value <= $this->params['Min']) {
                    $res['error'] = 'Не е над' . "|* - '" .
                    $this->toVerbal($this->params['Min']) . "'";
                }
            }
            
            // Проверяваме дали не е над максималната стойност, ако е зададена
            if (!$res['error'] && isset($this->params['max'])) {
                if($value > $this->params['max']) {
                    $res['error'] = 'Над допустимото' . "|* - '" .
                    $this->toVerbal($this->params['max']) . "'";
                }
            }
            
            // Проверяваме дали е под недостижимия максимум, ако е зададен
            if (!$res['error'] && isset($this->params['Max'])) {
                if($value >= $this->params['Max']) {
                    $res['error'] = 'Не е под' . "|* - '" .
                    $this->toVerbal($this->params['Max']) . "'";
                }
            }
            
            return $res;
        }
    }
    
    
    /**
     * Създава input поле или комбо-бокс
     */
    function createInput($name, $value, $attr)
    {
        if(count($this->suggestions)) {
            $tpl = ht::createCombo($name, $value, $attr, $this->suggestions);
        } else {
            $tpl = ht::createTextInput($name, $value, $attr);
        }
        
        return $tpl;
    }
    
    
    /**
     * Метод-фабрика за създаване на обекти-форматъри. Освен името на класа-тип
     * '$name' може да съдържа в скоби и параметри на форматъра, като size,syntax,max,min
     */
    static function getByName($name)
    {
        if (is_object($name) && cls::isSubclass($name, "core_Type"))
        return $name;
        
        $leftBracketPos = strpos($name, "(");
        
        if ($leftBracketPos > 0) {
            $typeName = substr($name, 0, $leftBracketPos);
        } else {
            $typeName = $name;
        }
        
        // Ако няма долна черта в името на типа - 
        // значи е базов тип и се намира в папката 'type'
        if (!strpos($typeName, '_')) {
            $typeName = 'type_' . ucfirst($typeName);
        }
        
        $p = array();
        $typeName = trim($typeName);
        
        if ($leftBracketPos > 0) {
            $rightBracketPos = strrpos($name, ")");
            
            if ($rightBracketPos > $leftBracketPos) {
                $params = substr($name, $leftBracketPos + 1,
                    $rightBracketPos - $leftBracketPos - 1);
                $params = explode(",", $params);
                
                foreach ($params as $index => $value) {
                    $value = trim($value);
                    
                    if (strpos($value, "=") > 0) {
                        list($key, $val) = explode("=", $value);
                        $p[trim($key)] = trim($val);
                    } else {
                        if (count($p) == 0 && is_numeric($value) && ($typeName != 'type_Enum')) {
                            $p[] = $value;
                        } else {
                            $p[trim($value)] = trim($value);
                        }
                    }
                }
            } else {
                error("Грешка в описанието на типа", array('name' => $name));
            }
        }
        
        if ($typeName == 'type_Enum') {
            return cls::get($typeName, array(
                    'options' => $p
                ));
        } elseif($typeName == 'type_Set') {
            return cls::get($typeName, array(
                    'suggestions' =>  $p
                ));
        } else {
            return cls::get($typeName, array(
                    'params' => $p
                ));
        }
    }
}