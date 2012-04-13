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
        return $this->getContent();
    }
    
    
    /**
     * @param mixed $content string или core_Tpl
     * @param string $place
     * @param boolean $once
     */
    public function append($content, $place = NULL, $once = FALSE)
    {
        $this->sub('append', $content, $place, $once);
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
        $this->sub('prepend', $content, $place, $once);
    }
    
    
    /**
     * @param mixed $content string или core_Tpl
     * @param string $place
     * @param boolean $once
     * @param boolean $global
     */
    public function replace($content, $place = NULL, $once = FALSE, $global = TRUE)
    {
        $this->sub('replace', $content, $place, $once, $global);
    }

    
    /**
     * @param mixed $content string или core_Tpl
     * @param string $place
     * @param boolean $once
     */
    public function push($content, $place, $once = FALSE)
    {
        
    }
    
    
    private function sub($position, $content, $place = NULL, $once = FALSE, $global = TRUE)
    {
        if (!isset($place)) {
        	$place = $this->globalPlace;
        }
        if (!$this->isReplaced($place)) {
        	if ($once) {
        		$hash = $this->getHash($content);
        		if (!$this->isContentUsed($hash, $place)) {
        			$this->vars[$place][$position][$hash] = $content;
        		}
        	} else {
        		$this->vars[$place][$position][] = $content;
        	}
        }
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
        $context = array();
        $content = $this->getRawContent($context);
        
        do {
            $again = FALSE;
            foreach ($context as $place=>$val) {
                if ($this->isPlaceholderExists($place, $content)) {
                    $prepend = isset($val['prepend']) ? implode('', array_reverse($val['prepend'])) : NULL;
                    $replace = isset($val['replace']) ? $val['replace'][0] : NULL;
                    $append  = isset($val['append']) ? implode('', $val['append']) : NULL;
                    $content = str_replace($this->toPlace($place), $prepend . $replace . $append, $content);
                    $again = TRUE;
                }
            }
        } while ($again);
        
        if ($removeBlocks) {
            $content = $this->clearPlaceholders($content);
            $content = $this->clearBlocks($content);
        }
        
        return $content;
    }
    
    private function buildContext()
    {
        $context = array();
        
        foreach ($this->vars as $place=>$val) {
            foreach ($val as $pos=>$data) {
                foreach ($data as $s) {
                    if (static::isTemplate($s)) {
                        $x = $s->getRawContent($context);
                    } else {
                        $x = static::escape($s);
                    }
                    $context[$place][$pos][] = $x;
                }
            }
        }
        
        return $context;
    }
    
    private function mergeContext(&$context, $import)
    {
        foreach ($import as $place=>$val) {
            foreach (array_keys($val) as $pos) {
                if (isset($context[$place][$pos])) {
                    $context[$place][$pos] = array_merge($context[$place][$pos], $import[$place][$pos]);
                } else {
                    $context[$place][$pos] = $import[$place][$pos];
                }
            }
        }
    }
    
    
    private function getRawContent(&$context)
    {
        $result       = $this->content;
        $localContext = $this->buildContext();
        
        if (isset($localContext[$this->globalPlace])) {
        	$val = $localContext[$this->globalPlace];
        	unset($localContext[$this->globalPlace]);
        	
        	$prepend = isset($val['prepend']) ? implode('', array_reverse($val['prepend'])) : NULL;
        	$replace = isset($val['replace']) ? $val['replace'][0] : NULL;
        	$append  = isset($val['append']) ? implode('', $val['append']) : NULL;
        	
        	if (isset($replace)) {
        	    $result = $replace;
        	}
        	
        	$result = $prepend . $result . $append;
        }
        
        $this->mergeContext($context, $localContext);
        
        return $result;
    }

    
    /**
     * Има ли плейсхолдър с това име?
     * 
     * @param string $place
     * @return boolean
     */
    public function isPlaceholderExists($place, $str = NULL)
    {
        if (!isset($str)) {
            $str = $this->content;
        }
        
        return strpos($str, $this->toPlace($place)) !== FALSE;
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
        echo (string)$this;
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
        
//         if (preg_match_all($this->placesRegex, $this->content, $matches)) {
//             foreach ($matches[1] as $place) {
//                 if ($place == $this->globalPlace) {
//                     throw new Exception("Невалиден плейсхолдър: {$place}");
//                 }
//                 $this->vars[$place]['prepend'] = array();
//                 $this->vars[$place]['append'] = array();
//             }
//         }
    }
    
    
    private function clearPlaceholders($str)
    {
        return preg_replace($this->placesRegex, '', $str);
    }
    
    
    private function getHash($content)
    {
        return md5(serialize($content)); 
    }
    

    private function isContentUsed($hash, $place)
    {
        return 
            isset($this->vars[$place]['prepend'][$hash]) || 
            isset($this->vars[$place]['append'][$hash]);
    }

    
    /**
     * Замества контролните символи в текста (начало на плейсхолдер)
     * с други символи, които не могат да се разчетат като контролни
     */
    private static function escape($content)
    {
        if (is_string($content)) {
            $content = str_replace('[#', '&#91;#', $content);
        }
        
        return $content;
    }
}


class core_TplReplacement
{
    var $prepend = array();
    var $replace = array();
    var $append = array();
    
    public function flatten(&$unused)
    {
        
    }
}