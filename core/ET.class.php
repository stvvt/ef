<?php

/**
 * Клас  'core_ET' ['ET'] - Система от текстови шаблони
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_ET extends core_BaseClass
{
    
    /**
     * Съдържание на шаблона
     */
    private $content;
    
    /**
     * Копие на шаблона
     */
    private $contentBackup;
    
    /**
     * Място за заместване по подразбиране
     */
    private $defaultPlace;
    
    /**
     * Масив с блокове
     */
    private $blocks = array();
    
    /**
     * Масив с плейсхолдери
     */
    private $places = array();
    
    /**
     * Регулярен израз за разпознаване на плейсхолдъри
     * 
     * @var string
     */
    private $placesRegex = '/\[#([a-zA-Z0-9_:]{1,})#\]/';
    
    /**
     * Масив с хешове на съдържание, което се замества еднократно
     */
    private $once = array();
    
    /**
     * Чакащи замествания
     */
    private $pending = array();
    
    /**
     * 'Изчезваеми' блокове
     */
    private $removableBlocks = array();
    
    /**
     * 'Изчезваеми' плейсхолдъри
     */
    private $removablePlaces = array();
    
    /**
     * Указател към 'мастер' шаблона
     */
    private $master;
    
    /**
     * Името на детайла
     */
    private $detailName;

    /**
     * Конструктор на шаблона
     * 
     * @param mixed $content core_ET или стринг
     */
    function __construct($content = '')
    {
        static $cache;
        
        if (empty($content)) {
            return;
        }
        
        if ($content instanceof self) {
            $this->initFromObject($content);
        } else {
            $md5 = md5($content);
            
            if (isset($cache[$md5])) {
                $this->initFromObject($cache[$md5]);
            } else {
                $this->initFromString($content);
                $cache[$md5] = clone ($this);
            }
        }
        
        // Всички следващи аргументи, ако има такива се заместват на 
        // плейсхолдери с имена [#1#], [#2#] ...
        $args = func_get_args();
        unset($args[0]);
        $this->placeArray($args);
    }

    /**
     * Добава обграждащите символи към даден стринг,
     * за да се получи означение на плейсхолдър
     */
    private function toPlace($name)
    {
        return "[#{$name}#]";
    }

    /**
     * Превръща име към означение за начало на блок
     */
    private function toBeginMark($blockName)
    {
        return "<!--ET_BEGIN $blockName-->";
    }

    /**
     * Превръща име към означение за край на блок
     */
    private function toEndMark($blockName)
    {
        return "<!--ET_END $blockName-->";
    }

    /**
     * Намира позициите на маркерите за начало и край на блок
     */
    function getMarkerPos($blockName)
    {
        $beginMark = $this->toBeginMark($blockName);
        
        $markerPos = new stdClass();
        
        $markerPos->beginStart = strpos($this->content, $beginMark);
        
        if ($markerPos->beginStart === FALSE) return FALSE;
        
        $endMark = $this->toEndMark($blockName);
        $markerPos->beginStop = $markerPos->beginStart + strlen($beginMark);
        $markerPos->endStart = strpos($this->content, $endMark, $markerPos->beginStop);
        
        if ($markerPos->endStart === FALSE) return FALSE;
        
        $markerPos->endStop = $markerPos->endStart + strlen($endMark);
        
        return $markerPos;
    }
    
    
    /**
     * Връща даден блок
     * 
     * @param string $blockName
     * @return core_ET
     */
    public function getBlock($blockName)
    {
        if (!is_object($this->blocks[$blockName])) {
            $mp = NULL;
            $body = $this->getBlockBody($blockName, $mp);
            expect(
                $body !== FALSE, 
                'Не може да бъде открит блока ' . $blockName, 
                $this->content);
            
            $this->blocks[$blockName] = $newTemplate = new self(
                $body);
            
            $newTemplate->master = $this;
            $newTemplate->detailName = $blockName;
            
            $newTemplate->backup();
            
            $this->places[$blockName] = 1;
            
            // Заместваме блока с [#името_му#]
            $this->setContent($this->toPlace($blockName), $mp);
        }
        
        return $this->blocks[$blockName];
    }

    /**
     * @todo Чака за документация...
     */
    public function removeBlocks()
    {
        if (count($this->removableBlocks)) {
            foreach ($this->removableBlocks as $blockName => $md5) {
                $mp = NULL;
                
                if (($content = $this->getBlockBody($blockName, $mp)) !== FALSE) {
                    // Премахване всички плейсхолдери
                    $this->removePlaces($content);
                    
                    if ($md5 == md5($content)) {
                        $content = '';
                    }
                    
                    $this->setContent($content, $mp);
                }
            }
        }
        
        $this->deletePlaces($this->removablePlaces);
        
        return $this;
    }

    /**
     * @todo Чака за документация...
     */
    public function removePlaces(&$content = NULL)
    {
        if (!isset($content)) {
            $content = &$this->content;
        }
        
        $content = preg_replace($this->placesRegex, '', $content);
        
        return $this;
    }

    /**
     * @todo Чака за документация...
     */
    private function backup()
    {
        $this->contentBackup = $this->content;
    }

    /**
     * @todo Чака за документация...
     */
    private function restore()
    {
        $this->content = $this->contentBackup;
        $this->places = array();
        $this->once = array();
        $this->pending = array();
    }

    /**
     * master-,
     * master-
     */
    public function append2Master()
    {
        if (is_object($this->master)) {
            $this->master->append($this, $this->detailName);
            $this->restore();
        }
    }

    /**
     * master-,       master-
     */
    private function replace2Master()
    {
        if (is_object($this->master)) {
            $this->master->replace($this, $this->detailName);
            $this->restore();
        }
    }

    /**
     * @todo Чака за документация...
     */
    private function prepend2Master()
    {
        if (is_object($this->master)) {
            $this->master->prepend($this, $this->detailName);
            $this->restore();
        }
    }

    /**
     * @todo Чака за документация...
     */
    private function preparePlace($place)
    {
        if ($place === NULL) {
            return $this->toPlace($this->defaultPlace);
        } else {
            $this->places[$place] = 1;
            
            return $this->toPlace($place);
        }
    }

    /**
     * Замества контролните символи в текста (начало на плейсхолдер)
     * с други символи, които не могат да се разчетат като контролни
     */
    static function escape($str)
    {
        return str_replace('[#', '&#91;#', $str);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    private function addSubstitution($str, $place, $once, $mode)
    {
        $this->pending[] = (object)array(
            'str' => $str, 
            'place' => $place, 
            'once' => $once, 
            'mode' => $mode
        );
    }

    /**
     * @todo Чака за документация...
     */
    public function push($value, $place, $once = FALSE)
    {
        if (is_array($value)) {
            foreach ($value as $v) {
                $this->addSubstitution($v, $place, $once, 'push');
            }
        } else {
            $this->addSubstitution($value, $place, $once, 'push');
        }
    }

    /**
     * @todo Чака за документация...
     */
    protected function getArray($place)
    {
        if (count($this->pending)) {
            foreach ($this->pending as $sub) {
                if ($sub->place == $place && $sub->mode == 'push') {
                    if ($sub->once) {
                        $md5 = md5($sub->str);
                        
                        if ($this->once[$md5]) continue;
                        $this->once[$md5] = TRUE;
                    }
                    $res[] = $sub->str;
                }
            }
        }
        
        return $res;
    }

    /**
     * Прилага масив от инструкции за субституции. Някои ще се случат сега, други пак ще бъдат отложени
     * 
     * @param array $pending
     */
    private function applyPendingSubst($pending)
    {
        foreach ($pending as $sub) {
            if (!($sub->str instanceof core_Et)) {
                $s = new self($sub->str);
            } else {
                $s = $sub->str;
            }
            
            switch ($sub->mode) {
                case "append" :
                    $this->append($s, $sub->place, $sub->once);
                    break;
                case "prepend" :
                    $this->prepend($s, $sub->place, $sub->once);
                    break;
                case "replace" :
                    $this->replace($s, $sub->place, $sub->once);
                    break;
                case "push" :
                    $this->push($sub->str, $sub->place, $sub->once);
                    break;
            }
        }
    }


    /**
     * @todo Чака за документация...
     */
    private function sub($content, $placeHolder, $once, $mode, $global = TRUE)
    {
        
        if ($content === NULL) return;
        
        if ($once && $this->once[$md5 = $this->getHash($content)]) {
            return FALSE;
        }
        
        if ($once) {
            $this->once[$md5] = TRUE;
        }
        
        if ($content instanceof self) {
            $str = $content->_getContent(array('removeBlocks'=>FALSE));
            
            // Прехвърля в Master шаблона всички removableBlocks хешове
            $this->removableBlocks += $content->removableBlocks;
            
            // Прехвърля в Master шаблона всички appendOnce хешове
            $this->once += $content->once;
            
            // Прехвърля в мастер шаблона всички плейсхолдери, които трябва да се заличават
            $this->removablePlaces += $content->removablePlaces;
            
            $this->applyPendingSubst($content->pending);
        } else {
            $str = $this->escape($content);
        }
        
        // Маркира, че $placeHolder вече е заместван поне веднъж. Тези плейсхолдъри се изчистват
        // от крайния резултат.
        $place = $this->preparePlace($placeHolder);

        if (strpos($this->content, $place) !== FALSE) {
            
            switch ($mode) {
                case "append" :
                    $new = $str . $place;
                    break;
                case "prepend" :
                    $new = $place . $str;
                    break;
                case "replace" :
                    $new = $str;
                    break;
            }
            
            $this->content = str_replace($place, $new, $this->content);
        } elseif ($placeHolder == NULL) {
            switch ($mode) {
                case "append" :
                    $this->content = $this->content . $str;
                    break;
                case "prepend" :
                    $this->content = $str . $this->content;
                    break;
                case "replace" :
                    $this->content = $str;
                    break;
            }
        } elseif ($global) {
            $this->addSubstitution($str, $placeHolder, $once, $mode);
        }
    }

    /**
     * Заместване след плейсхолдъра
     */
    public function append($content, $placeHolder = NULL, $once = FALSE)
    {
        return $this->sub($content, $placeHolder, $once, "append");
    }

    /**
     * Заместване след пелйсхолдъра.
     * Всички опити за използване на същото съдържание ще бъдат игнорирани
     */
    public function appendOnce($content, $placeHolder = NULL)
    {
        return $this->append($content, $placeHolder, TRUE);
    }

    /**
     * Заместване преди пелйсхолдъра
     */
    public function prepend($content, $placeHolder = NULL, $once = FALSE)
    {
        return $this->sub($content, $placeHolder, $once, "prepend");
    }

    /**
     * Замества посочения плейсходер със съдържанието. Може да се зададе
     * еднократно вкарване на съдържанието при което всички последващи опити
     * за заместване на същото съдържание, ще бъдат пропуснати
     */
    public function replace($content, $placeHolder = NULL, $once = FALSE, $global = TRUE)
    {
        return $this->sub($content, $placeHolder, $once, "replace", $global);
    }

    /**
     * Отпечатва текстовото съдържание на шаблона
     */
    public function output($content = '', $place = "CONTENT")
    {
        if ($content) {
            $this->replace($content, $place);
        }
        
        $this->invoke('output');
        
        echo $this->getContent();
    }

    
    private function _getContent($args)
    {
        extract($args);
        
        $redirectArr = $this->getArray('_REDIRECT_');
        
        if ($redirectArr[0]) redirect($redirectArr[0]);
        
        //   -
        $this->deletePlaces($this->places);
        
        if ($removeBlocks) {
            $this->removeBlocks();
        }
        
        return $this->content;
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
        return $this->_getContent(compact('content', 'place', 'output', 'removeBlocks'));
    }

    /**
     * Прави субституция на данни, които могат да бъдат масив с обекти или масив с масиви
     * в указания блок-държач. Ако няма данни, блока държач изчезва, а се появява указания
     * празен блок
     */
    private function placeMass($data, $holderBlock = NULL, $emptyBlock = NULL)
    {
        if ($holderBlock) {
            $tpl = $this->getBlock($holderBlock);
        } else {
            $tpl = & $this;
        }
        
        if ($emptyBlock) {
            $empty = $this->getBlock("$emptyBlock");
        }
        
        if (is_array($data)) {
            foreach ($data as $name => $object) {
                if (is_object($object)) {
                    $tpl->placeObject($object);
                    $tpl->append2master();
                } elseif (is_array($object)) {
                    $tpl->placeArray($object);
                    $tpl->append2master();
                }
            }
        } else {
            if ($emptyBlock) {
                $empty->replace2master();
            }
        }
    }

    /**
     * Прави субституция на елементите на масив в плейсхолдери започващи
     * с посочения префикс. Ако е посочен блок-държач, субституцията се
     * прави само в неговите рамки.
     */
    public function placeArray($data, $holderBlock = NULL, $prefix = '')
    {
        // Ако данните са обект - конвертираме ги до масив
        if (is_object($data)) {
            $this->placeArray(get_object_vars($data), $holderBlock, $prefix);
        }
        
        if ($holderBlock) {
            $tpl = $this->getBlock($holderBlock);
        } else {
            $tpl = & $this;
        }
        
        if ($prefix) {
            $prefix .= "_";
        }
        
        if (count($data)) {
            foreach ($data as $name => $object) {
                if (is_array($object) || (is_object($object) && !($object instanceof self))) {
                    $tpl->placeArray($object, NULL, $prefix . $name);
                } else {
                    $tpl->replace($object, $prefix . $name, FALSE, FALSE);
                }
            }
        }
        
        if ($holderBlock) {
            $tpl->replace2master();
        }
    }

    /**
     * Прави субституция на променливите на обект в плейсхолдери започващи
     * с посочения префикс
     */
    public function placeObject($data, $holderBlock = NULL, $prefix = NULL)
    {
        $this->placeArray($data, $holderBlock, $prefix);
    }

    /**
     * Превежда съдържанието на посочения език, или на текущия
     */
    private function translate($lg = NULL)
    {
        $this->content = tr("|*" . $this->content);
    }

    /**
     * Връща плейсхолдерите на шаблона
     * 
     * @return array
     */
    public function getPlaceholders()
    {
        preg_match_all($this->placesRegex, $this->content, $matches);
        
        return $matches[1];
    }

    /**
     * Връща TUR, ако има плейсхолдър с посоченото име, и FALSE ако няма
     */
    public function isPlaceholderExists($placeholder)
    {
        $place = $this->toPlace($placeholder);
        
        return strpos($this->content, $place) !== FALSE;
    }

    /**
     * Конвертира към стринг
     */
    public function __toString()
    {
        return $this->getContent();
    }

    /**
     * Инициализира обект с данните на друг обект от същия клас
     * 
     * @param core_ET $src
     */
    private function initFromObject($src)
    {
        $this->content = $src->content;
        $this->removableBlocks = $src->removableBlocks;
        $this->removablePlaces = $src->removablePlaces;
        $this->places = $src->places;
        $this->once = $src->once;
        $this->pending = $src->pending;
        $this->blocks = $src->blocks;
    }

    /**
     * Инициализира обекта от стринг
     * 
     * @param string $src
     */
    private function initFromString($content)
    {
        $this->content = $content;
        
        // Взема началните плейсхолдери, за да могат непопълнените да бъдат изтрити
        if (count($this->removablePlaces = $this->getPlaceholders()) > 0) {
            $this->removablePlaces = array_combine($this->removablePlaces, $this->removablePlaces);
            
            // Задава самоизчезващите блокове - онези за които има едноименен плейсхолдър
            foreach ($this->removablePlaces as $b) {
                if (($content = $this->getBlockBody($b)) !== FALSE) {
                    // Премахване всички плейсхолдери
                    $this->removePlaces($content);
                    $this->removableBlocks[$b] = md5($content);
                }
            }
        }
    }

    /**
     * Конструктор в PHP4 стил
     * 
     * @param mixed $content core_ET или string
     * @deprecated използва се само от наследените класове. Може да се махне, когато те се модернизират
     */
    protected function core_ET()
    {
        $args = func_get_args();
        call_user_func_array(array(
            $this, 
            '__construct'
        ), $args);
    }

    private function getBlockBody($blockName, &$pos = NULL)
    {
        if (!isset($pos)) {
            $pos = $this->getMarkerPos($blockName);
        }
        if ($pos === FALSE) {
            return FALSE;
        }
        
        return substr(
            $this->content, 
            $pos->beginStop, 
            $pos->endStart - $pos->beginStop);
    }

    private function setContent($newContent, $mp = NULL)
    {
        if (isset($mp)) {
            $newContent = substr($this->content, 0, $mp->beginStart) . $newContent . substr(
                $this->content, 
                $mp->endStop, 
                strlen($this->content) - $mp->endStop);
        }
        
        $this->content = $newContent;
    }
    
    /**
     * Премахва от всички плейсхолдъри, чиито имена се срещат в ключовете на масив
     * 
     * @param array $places
     */
    private function deletePlaces($places)
    {
        foreach (array_keys($places) as $place) {
            $this->content = str_replace(
                $this->toPlace($place), 
                '', 
                $this->content);
        }
    }
    
    
    public function __get($name)
    {
        if (!isset($this->{$name})) {
            return NULL;
        }
        
        return $this->{$name};
    }
    
    
    private function getHash($content)
    {
        return md5(serialize($content)); 
    }
}
