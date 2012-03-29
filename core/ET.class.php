<?php



/**
 * Клас  'core_ET' ['ET'] - Система от текстови шаблони
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
     */
    function core_ET($content = "")
    {
        static $cache;
        
        if ($content instanceof core_ET) {
            $this->content = $content->content;
            $this->places = $content->places;
            $this->once = $content->once;
            $this->pending = $content->pending;
            $this->blocks = $content->blocks;
            $this->removableBlocks = $content->removableBlocks;
            $this->removablePlaces = $content->removablePlaces;
        } else {
            $md5 = md5($content);
            
            if($c = $cache[$md5]) {
                $this->content = $c->content;
                $this->removableBlocks = $c->removableBlocks;
                $this->removablePlaces = $c->removablePlaces;
            } else {
                $this->content = $content;
                $rmPlaces = $this->getPlaceHolders();
                $this->setRemovableBlocks($rmPlaces);
                
                // Взема началните плейсхолдери, за да могат непопълнените да бъдат изтрити
                
                if(count($rmPlaces)) {
                    foreach($rmPlaces as $place) {
                        $this->removablePlaces[$place] = $place;
                    }
                }
                $cache[$md5] = new stdClass();
                $cache[$md5]->content = $this->content;
                $cache[$md5]->removableBlocks = $this->removableBlocks;
                $cache[$md5]->removablePlaces = $this->removablePlaces;
            }
        }
        
        // Всички следващи аргументи, ако има такива се заместват на 
        // плейсхолдери с имена [#1#], [#2#] ...
        $args = func_get_args();
        
        if (($n = count($args)) > 1) {
            for ($i = 1; $i < $n; $i++) {
                $this->replace($args[$i], $i);
            }
        }
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
    private function getMarkerPos($blockName)
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
        if (is_object($this->blocks[$blockName])) {
            
            return $this->blocks[$blockName];
        }
        
        $placeHolder = $this->toPlace($blockName);
        
        $mp = $this->getMarkerPos($blockName);
        
        expect(is_object($mp), 'Не може да бъде открит блока ' . $blockName, $this->content);
        
        $newTemplate = new ET(substr($this->content, $mp->beginStop,
                $mp->endStart - $mp->beginStop));
        $newTemplate->master = & $this;
        $newTemplate->detailName = $blockName;
        
        $this->content = substr($this->content, 0, $mp->beginStart) .
        $placeHolder .
        substr($this->content, $mp->endStop, strlen($this->content) - $mp->endStop);
        
        $this->places[$blockName] = 1;
        $this->blocks[$blockName] = $newTemplate;
        $newTemplate->backup();
        
        return $newTemplate;
    }
    
    
    /**
     * ,
     * removeBlocks()
     * ,
     */
    private function setRemovableBlocks($places)
    {
        if(count($places)) {
            foreach($places as $b) {
                
                $mp = $this->getMarkerPos($b);
                
                if(is_object($mp)) {
                    $content = substr($this->content, $mp->beginStop, $mp->endStart - $mp->beginStop);
                    
                    // Премахване всички плейсхолдери
                    $content = preg_replace('/\[#([a-zA-Z0-9_]{1,})#\]/', '', $content);
                    
                    $this->removableBlocks[$b] = md5($content);
                }
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    private function removeBlocks()
    {
        if (count($this->removableBlocks)) {
            foreach ($this->removableBlocks as $blockName => $md5) {
                $mp = $this->getMarkerPos($blockName);
                
                if ($mp) {
                    $content = substr($this->content, $mp->beginStop,
                        $mp->endStart - $mp->beginStop);
                    
                    // Премахване всички плейсхолдери
                    $content = preg_replace('/\[#([a-zA-Z0-9_]{1,})#\]/', '', $content);
                    
                    if ($md5 == md5($content)) {
                        
                        $content = '';
                    }
                    
                    $this->content = substr($this->content, 0, $mp->beginStart) .
                    $content .
                    substr($this->content, $mp->endStop,
                        strlen($this->content) - $mp->endStop);
                }
            }
        }
        
        if($this->removablePlaces) {
            
            foreach($this->removablePlaces as $p) {
                $place = $this->toPlace($p);
                $this->content = str_replace($place, '', $this->content);
                
                // Debug::log('Изтрит плейсхолдър: ' . $place);
            }
        }
        
        return $this;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    private function removePlaces()
    {
        $places = $this->getPlaceholders();
        
        foreach ($places as $p) {
            $this->replace('', $p);
        }
        
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
    private static function escape($str)
    {
        return str_replace('[#', '&#91;#', $str);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    private function addSubstitution($str, $place, $once, $mode)
    {
        $this->pending[] = (object) array(
                            'str' => $str,
                            'place' => $place,
                            'once' => $once,
                            'mode' => $mode);
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
                        
                        if ($this->once[$md5])
                        continue;
                        $this->once[$md5] = TRUE;
                    }
                    $res[] = $sub->str;
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    private function processContent($content)
    {
        if (is_a($content, "et") || is_a($content, "core_Et")) {
            //   
            foreach ($content->pending as $sub) {
                if(!($sub->str instanceof core_Et)) {
                    $s = new ET($sub->str);
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
            
            // Прехвърля в Master шаблона всички appendOnce хешове
            if(count($content->once)) {
                foreach ($content->once as $md5 => $true) {
                    $this->once[$md5] = TRUE;
                }
            }
            
            // Прехвърля в мастер шаблона всички плейсхолдери, които трябва да се заличават
            if(count($content->removablePlaces)) {
                foreach ($content->removablePlaces as $place) {
                    $this->removablePlaces[$place] = $place;
                }
            }
            
            return $content->getContent(NULL, 'CONTENT', FALSE, FALSE);
        } else {
            return $this->escape($content);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    private function importRemovableBlocks($content)
    {
        if (is_a($content, "et") || is_a($content, "core_Et")) {
            if (count($content->removableBlocks)) {
                foreach ($content->removableBlocks as $name => $md5) {
                    $this->removableBlocks[$name] = $md5;
                }
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    private function sub($content, $placeHolder, $once, $mode, $global = TRUE)
    {
        
        if ($content === NULL) return;
        
        if ($once) {
            if ($content instanceof core_Et) {
                $str = serialize($content);
            } else {
                $str = $content;
            }
            
            $md5 = md5($str);
            
            if ($this->once[$md5]) {
                
                return  FALSE;
            }
        }
        
        // DEBUG::startTimer("SUB1");
        $this->importRemovableBlocks($content);
        
        //DEBUG::stopTimer("SUB1");
        
        //DEBUG::startTimer("SUB2");
        $str = $this->processContent($content);
        
        //DEBUG::stopTimer("SUB2");
        
        // DEBUG::startTimer("SUB3");
        $place = $this->preparePlace($placeHolder);
        
        // DEBUG::stopTimer("SUB3");
        
        if (strpos($this->content, $place) !== FALSE) {
            
            if ($once) {
                $this->once[$md5] = TRUE;
            }
            
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
        } else {
            if ($placeHolder == NULL) {
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
            } else {
                if($global) {
                    $this->addSubstitution($str, $placeHolder, $once, $mode);
                }
            }
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
        echo $this->getContent($content, $place, TRUE, TRUE);
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
        if ($content) {
            $this->replace($content, $place);
        }
        
        if ($output) {
            $this->invoke('output');
        }
        
        $redirectArr = $this->getArray('_REDIRECT_');
        
        if ($redirectArr[0])
        redirect($redirectArr[0]);
        
        //   -
        if (is_array($this->places)) {
            foreach ($this->places as $place => $dummy) {
                $this->content = str_replace($this->toPlace($place), '', $this->content);
            }
        }
        
        if ($removeBlocks) {
            $this->removeBlocks($removeBlocks);
        }
        
        return $this->content;
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
                if(is_array($object) || (is_object($object) && !($object instanceof core_ET))) {
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
     */
    private function getPlaceholders()
    {
        preg_match_all('/\[#([a-zA-Z0-9_]{1,})#\]/', $this->content, $matches);
        
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
}
