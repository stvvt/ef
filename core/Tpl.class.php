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
     * Блокове
     *
     * @var array
     */
    private $blocks = array();


    /**
     * Стойности за заместване
     *
     * @var array
     */
    private $vars = array();


    /**
     * PUSH стойности
     *
     * @see core_Tpl::push()
     * @see core_Tpl::getArray()
     *
     * @var array
     */
    private $pushed = array();


    /**
     * Име на шаблона
     *
     * @see self::getBlock(), self::append2master()
     *
     * @var string
     */
    private $name = NULL;


    /**
     * Шаблон-родител
     *
     * @see self::getBlock(), self::append2master()
     *
     * @var core_Tpl
     */
    private $parent = NULL;


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
    	if (static::isTemplate($content)) {
    		$content = $content->content;
    	}

    	$content = $content ? : '';

    	expect(is_string($content), $content);

        $this->initFromString($content);

        // Всички следващи аргументи, ако има такива се заместват на
        // плейсхолдери с имена [#1#], [#2#] ...
        if (count($args = func_get_args()) > 1) {
            unset($args[0]);
            $this->placeArray($args);
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
    	call_user_func_array(
    		array(
		    	$this,
	    		'__construct'
    		),
    		func_get_args()
    	);
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
        expect(static::isTemplate($this->parent));
        expect(is_string($this->name));

        $this->parent->append((string)$this, $this->name);
        $this->resetContext();
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
        if (!is_array($content)) {
            $content = array($content);
        }

        foreach ($content as $c) {
            if ($once) {
                $hash = $this->getHash($c);
                $this->pushed[$place][$hash] = $c;
            } else {
                $this->pushed[$place][] = $c;
            }
        }
    }

    /**
     * @param string $place
     * @return array
     */
    protected function getArray($place)
    {
        return isset($this->pushed[$place]) ? array_values($this->pushed[$place]) : array();
    }


    private function sub($position, $content, $place = NULL, $once = FALSE, $global = TRUE)
    {
        if (!isset($content)) {
            return;
        }

        if (!isset($place)) {
        	$place = $this->globalPlace;
        }
        if (!$this->isReplaced($place)) {
        	if ($once) {
        		$hash = $this->getHash($content);
        		if (!$this->isContentUsed($hash, $place)) {
        		    $this->vars[$place][$position][$hash] = $this->importContent($content);
        		}
        	} else {
        		$this->vars[$place][$position][] = $this->importContent($content);
        	}
        }
    }


    private function importContent($content)
    {
		if (static::isTemplate($content)) {
		    foreach ($content->pushed as $place=>$val) {
		        if (!isset($this->pushed[$place])) {
		            $this->pushed[$place] = $val;
		        } else {
		            $this->pushed[$place] = array_merge($this->pushed[$place], $val);
		        }
		    }
		}

        return $content;
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
        /* @var $block core_Tpl */

        $context = array();
        $content = $this->_getContent($context);

        if ($removeBlocks) {
            foreach ($this->blocks as $place=>$block) {
                $blockContent = $block->applyContext($context);
                if ($block->content == $blockContent) {
                    $blockContent = '';
                }
                $content = str_replace($this->toPlace('@' . $place), $blockContent, $content);
            }

            $content = $this->clearPlaceholders($content);
        }

        return $content;
    }


    private function _getContent(&$context = array())
    {
        $localContext = array();

        foreach ($this->vars as $place=>$val) {
            foreach ($val as $pos=>$data) {
                foreach ($data as $s) {
                    if (static::isTemplate($s)) {
                        $x = $s->_getContent($localContext);
                        $this->blocks += $s->blocks;
                    } else {
                        $x = static::escape($s);
                    }
                    $localContext[$place][$pos][] = $x;
                }
            }
        }

        $content = $this->applyContext($localContext);

        foreach ($localContext as $place=>$val) {
            foreach (array_keys($val) as $pos) {
                if (isset($context[$place][$pos])) {
                    $context[$place][$pos] = array_merge($context[$place][$pos], $localContext[$place][$pos]);
                } else {
                    $context[$place][$pos] = $localContext[$place][$pos];
                }
            }
        }

        return $content;
    }


    private function applyContext(&$localContext)
    {
        $content = $this->content;

        do {
            $again = FALSE;
            foreach ($localContext as $place=>$val) {
            	$again =
        		    $this->isPlaceholderExists($place, $content) ||
            		$place == $this->globalPlace;
                if ($again) {
                    $prepend = isset($val['prepend']) ? implode('', array_reverse($val['prepend'])) : NULL;
                    $replace = isset($val['replace']) ? $val['replace'][0] : NULL;
                    $append  = isset($val['append']) ? implode('', $val['append']) : NULL;

                    if ($place == $this->globalPlace) {
					    if (isset($replace)) {
                    	    $content = $replace;
                    	}
                    	$content = $prepend . $content . $append;
                    } else {
	                    $content = str_replace($this->toPlace($place), $prepend . $replace . $append, $content);
                    }

                    unset($localContext[$place]);
                }
            }
        } while ($again);

        return $content;
    }


    private function buildContext()
    {
        $context = array();

        foreach ($this->vars as $place=>$val) {
            foreach ($val as $pos=>$data) {
                foreach ($data as $s) {
                    if (static::isTemplate($s)) {
                        $x = $s->_getContent($context);
                    } else {
                        $x = static::escape($s);
                    }
                    $context[$place][$pos][] = $x;
                }
            }
        }

        return $context;
    }

    private function mergeContext(&$context, $localContext)
    {
        foreach ($localContext as $place=>$val) {
            foreach (array_keys($val) as $pos) {
                if (isset($context[$place][$pos])) {
                    $context[$place][$pos] = array_merge($context[$place][$pos], $localContext[$place][$pos]);
                } else {
                    $context[$place][$pos] = $localContext[$place][$pos];
                }
            }
        }
    }


    private function resetContext()
    {
        $this->vars = array();
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
        if (!empty($content)) {
            $this->replace($content, $place);
        }

        $this->invoke('output');

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
        expect(is_array($data));

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
        expect(is_object($data));

        $this->placeArray(get_object_vars($data));
    }


    /**
     * Връща даден блок
     *
     * @param string $blockName
     * @return core_Tpl|boolean
     */
    public function getBlock($blockName)
    {
    	if (!isset($this->blocks[$blockName])) {
    	    $pos = NULL;
    		$blockBody = $this->getBlockBody($blockName, $pos);

    		if ($blockBody !== FALSE) {
    			$this->blocks[$blockName] = new self($blockBody);
    			$this->blocks[$blockName]->name   = '@' . $blockName;
    			$this->blocks[$blockName]->parent = $this;

    			// Заместваме блока с [#@името_му#]
    			$this->setContent($this->toPlace('@' . $blockName), $pos);
    		}
    	}

    	if (isset($this->blocks[$blockName])) {
    		$this->blocks[$blockName]->resetContext();
    	    return $this->blocks[$blockName];
    	}

    	return FALSE;
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
     *
     * @param string $name
     * @return string
     */
    private function toPlace($name)
    {
        return "[#{$name}#]";
    }


    /**
     * Превръща име към означение за начало на блок
     *
     * @param string $name
     * @return string
     */
    private function toBeginMark($blockName)
    {
    	return "<!--ET_BEGIN $blockName-->";
    }


	/**
	* Превръща име към означение за край на блок
     *
     * @param string $name
     * @return string
	*/
	private function toEndMark($blockName)
	{
	    return "<!--ET_END $blockName-->";
	}


    private function initFromString($str)
    {
        $this->content = $str;

        $this->prepareRemovableBlocks();
    }


    /**
     * Намира всички блокове и ги замества с едноименни плейсхолдъри
     *
     * @return array масив от блокове. Това са обекти-шаблони, инициализирани с тялото на съотв. блок
     */
    private function prepareRemovableBlocks($content = NULL)
    {
        if (!isset($content)) {
            $content = $this->content;
        }

        $places = $this->getPlaceholders($content);

        // Задава самоизчезващите блокове - онези за които има едноименен плейсхолдър
        foreach ($places as $place) {
            $this->getBlock($place);
        }
    }


    /**
     * Намира позициите на маркерите за начало и край на блок
     *
     * @param string $blockName
     * @return stdClass обект с полета {beginStart, beginStop, endStart, endStop}
     *                  FALSE ако липсва блок с такова име.
     */
    private function getBlockPosition($blockName)
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
     * Текстовото тяло на блок.
     *
     * @param string $blockName
     * @param stdClass $pos @see self::getBlockPosition()
     * @return boolean|string FALSE ако няма такъв блок
     */
    private function getBlockBody($blockName, &$pos = NULL)
    {
    	if (!isset($pos)) {
    		$pos = $this->getBlockPosition($blockName);
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
    		$newContent =
    		    substr($this->content, 0, $mp->beginStart)
    		    . $newContent
    		    . substr($this->content, $mp->endStop);
    	}

    	$this->content = $newContent;
    }


    /**
     * Връща плейсхолдерите на шаблона
     *
     * @return array
     */
    private function getPlaceholders($content = NULL)
    {
        if (!isset($content)) {
            $content = $this->content;
        }

    	preg_match_all($this->placesRegex, $content, $matches);

    	return $matches[1];
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