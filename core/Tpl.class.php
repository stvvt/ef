<?php
class core_Tpl extends core_BaseClass
{
    
    /**
     * Съдържание на шаблона
     * 
     * @var string
     */
    private $content;
    
    
    /**
     * Стойности за заместване
     * 
     * @var array
     */
    private $vars = array();
    
    
    /**
     * Означение на псевдо-плейсхолдъра, преставляващ цялото съдържание
     * 
     * @var string
     */
    private $globalPlace = '@'; 
    
    
    /**
     * Регулярен израз за разпознаване на плейсхолдъри
     * 
     * @var string
     */
    private $placesRegex = '/\[#([a-zA-Z0-9_]{1,})#\]/';
    
    /**
     * Конструктор
     * 
     * @param mixed $content string или core_Tpl
     */
    public function __construct($content = '')
    {
        if (is_string($content)) {
            $this->initFromString($content);
        }
        
        // Всички следващи аргументи, ако има такива се заместват на 
        // плейсхолдери с имена [#1#], [#2#] ...
        if (count($args = func_get_args()) > 1) {
            unset($args[0]);
            $this->placeArray($args);
        }
    }
    
    
    public function __toString()
    {
        $places = $values = array();
        
        if ($this->isReplaced($this->globalPlace)) {
            return $this->vars[$this->globalPlace]['replace'];
        }
        
        $masterAppend = $masterPrepend = '';
        
        foreach ($this->vars as $place=>$val) {
            $prepend = $replace = $append = '';
        
            if (isset($val['prepend'])) {
                $prepend = static::escape(array_reverse($val['prepend']));
            }
            if (isset($val['append'])) {
                $append = static::escape($val['append']);
            }
            if (isset($val['replace'])) {
                $replace = static::escape($val['replace']);
            }

            if ($place === $this->globalPlace) {
                $masterPrepend = $prepend;
                $masterAppend  = $append;
            } else {
                $places[] = $this->toPlace($place);
                $values[] = $prepend . $replace . $append;
            }
        }
        
        return $masterPrepend . str_replace($places, $values, $this->content) . $masterAppend;
    }
    
    
    /**
     * @param mixed $content string или core_Tpl
     * @param string $place
     * @param boolean $once
     */
    public function append($content, $place = NULL, $once = FALSE)
    {
        if (!isset($place)) {
            $place = $this->globalPlace;
        }

        if (!$this->isReplaced($place)) {
            if ($once) {
                $hash = $this->getHash($content);
                if (!$this->contentUsed($hash, $place)) {
                    $this->vars[$place]['append'][$hash] = $content;
                }
            } else {
                $this->vars[$place]['append'][] = $content;
            }
        }
    }
    
    
    /**
     * @param mixed $content string или core_Tpl
     * @param string $place
     * @param boolean $once
     */
    public function appendOnce($content, $place = NULL)
    {
        return $this->append($content, $place, TRUE);
    }
    
    
    public function append2Master()
    {
        
    }
    
    
    /**
     * @param mixed $content string или core_Tpl
     * @param string $place
     * @param boolean $once
     */
    public function prepend($content, $place = NULL, $once = FALSE)
    {
        if (!isset($place)) {
            $place = $this->globalPlace;
        }
        if (!$this->isReplaced($place)) {
            if ($once) {
                $hash = $this->getHash($content);
                if (!$this->contentUsed($hash, $place)) {
                    $this->vars[$place]['prepend'][$hash] = $content;
                }
            } else {
                $this->vars[$place]['prepend'][] = $content;
            }
        }
    }
    
    
    /**
     * @param mixed $content string или core_Tpl
     * @param string $place
     * @param boolean $once
     * @param boolean $global
     */
    public function replace($content, $place = NULL, $once = FALSE, $global = TRUE)
    {
        if (!isset($place)) {
            $place = $this->globalPlace;
        }
        if (!$this->isReplaced($place)) {
            $this->vars[$place]['replace'] = $content;
        }
    }

    
    /**
     * @param mixed $content string или core_Tpl
     * @param string $place
     * @param boolean $once
     */
    public function push($content, $place, $once = FALSE)
    {
        
    }
    

    /**
     * Връща даден блок
     * 
     * @param string $blockName
     * @return core_ET
     */
    public function getBlock($blockName)
    {
        
    }
    
    
    /**
     * Връща текстовото представяне на шаблона, след всички възможни субституции
     * 
     * @param core_ET $content
     * @param string $place
     * @param boolean $output
     * @param boolean $removeBlocks
     * @return string
     */
    public function getContent($content = NULL, $place = "CONTENT", $output = FALSE, $removeBlocks = TRUE)
    {
        
    }


    /**
     * Има ли плейсхолдър с това име?
     * 
     * @param string $place
     * @return boolean
     */
    public function isPlaceholderExists($placeholder)
    {

    }
    
    
    /**
     * Отпечатва текстовото съдържание на шаблона
     * 
     * @param mixed $content string или core_Tpl
     * @param string $place
     * 
     */
    public function output($content = '', $place = NULL)
    {

    }
    
    
    /**
     * Прави субституция на елементите на масив в плейсхолдери започващи
     * с посочения префикс. Ако е посочен блок-държач, субституцията се
     * прави само в неговите рамки.
     * 
     * @param array $data
     * $param string $block
     * @param string $prefix
     */
    public function placeArray($data, $block = NULL, $prefix = '')
    {
        foreach ($data as $n=>$v) {
            $this->replace($v, $n);
        }
    }
    

    /**
     * Прави субституция на елементите на масив в плейсхолдери започващи
     * с посочения префикс. Ако е посочен блок-държач, субституцията се
     * прави само в неговите рамки.
     * 
     * @param object $data
     * $param string $block
     * @param string $prefix
     */
    public function placeObject($data, $block = NULL, $prefix = NULL)
    {
        
    }
    
    
    public function removeBlocks()
    {
        
    }
    
    
    /**
     * @param string $content
     * @return core_ET
     */
    public function removePlaces(&$content = NULL)
    {
        
    }    
    
    
    /**
     * Дали стойността е обект от същия клас
     * 
     * @param mixed $val
     * @return boolean
     */
    public static function isTemplate($val)
    {
        return $val instanceof self;
    }
    
    
    private function isReplaced($place)
    {
        return isset($this->vars[$place]['replace']);
    }

    
    /**
     * Означение на плейсхолдър със зададено име
     */
    private function toPlace($name)
    {
        return "[#{$name}#]";
    }
    
    private function initFromString($str)
    {
        $this->content = $str;
        
        if (preg_match_all($this->placesRegex, $this->content, $matches)) {
            foreach ($matches[1] as $place) {
                if ($place == $this->globalPlace) {
                    throw new Exception("Невалиден плейсхолдър: {$place}");
                }
                $this->vars[$place]['prepend'] = array();
                $this->vars[$place]['append'] = array();
            }
        }
    }
    
    
    private function getHash($content)
    {
        return md5(serialize($content)); 
    }
    

    private function contentUsed($hash, $place)
    {
        return 
            isset($this->vars[$place]['prepend'][$hash]) || 
            isset($this->vars[$place]['append'][$hash]);
    }

    
    /**
     * Замества контролните символи в текста (начало на плейсхолдер)
     * с други символи, които не могат да се разчетат като контролни
     */
    private static function escape($str)
    {
        if (is_array($str)) {
            $str = implode('', $str);
        }
        
        return str_replace('[#', '&#91;#', $str);
    }
}