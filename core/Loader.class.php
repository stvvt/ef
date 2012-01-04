<?php

class Loader
{
	/**
	 * Съхранява съответствието между име на клас и директорията, в която е реализацията му.
	 * 
	 * @var array
	 * @see ClassLoader::map()
	 * 
	 */
	protected static $classMap = array();

	
	/**
	 * Съхранява псевдонимите на класовете
	 * 
	 * @var array
	 * @see ClassLoader::classAlias()
	 */
	protected static $classAliases = array();
	
	
	/**
	 * Задава алтернативна директория на реализацията на клас
	 * 
	 * @param string $class име на клас
	 * @param string $path директория
	 */
	public static function map($class, $path)
	{
		static::$classMap[$class] = $path;
	}
	
	
	/**
	 * Задава псевдоним на клас
	 * 
	 * @param string $class име на клас
	 * @param string $alias псевдоним (чувствителен към големи и малки букви!)
	 */
	public static function classAlias($class, $alias = NULL) {
		if (!is_array($class)) {
			$class = array($alias => $class);
		}

		foreach ($class as $a=>$c) {
			static::$classAliases[$a] = $c;
		}
	}
	
	/**
	 * Създава псевдоним на клас в зададено пространство от имена.
	 * 
	 * @param string $class име на съществуващ в глобалното пространство клас. 
	 * @param string $ns namespace
	 */
	public static function createClassAliasNs($class, $ns)
	{
		if (!class_exists("{$class}", FALSE)) {
			return FALSE;
		}
		if (class_exists("{$ns}\\{$class}", FALSE)) {
			return TRUE;
		}

		if ( !@class_alias("{$class}", "{$ns}\\{$class}") ) {
			// Функцията `class_alias()` работи само за класове, дефинирани от потребителя. За
			// останалите класове (напр. `stdClass`) използваме друг подход за създаване на
			// псевдоними.
			eval("namespace {$ns}; class {$class} extends \\{$class} {}");
		}
		
		return TRUE;
	}
	
	
	/**
	 * Зарежда файл с реализация на клас в зададено пространство от имена (namespace)
	 * 
	 * @param string $file файл съдържащ реализация на клас
	 * @param string $ns име на пространство от имена
	 * @param string $className име на клас (без namespace част)
	 */
	public static function loadNs($file, $ns, $className)
	{
		if (!static::createClassAliasNs($className, $ns)) {
			$code = file_get_contents($file);
			$code = str_replace(array('<?php', '<?', '?>'), array('', '', ''), $code);
			
			$res = eval("namespace {$ns}; {$code}");
			
			if ($res === FALSE) {
				bp($code);
			}
		}
	}
	
	
	/**
	 * Разбива пълно име на клас на namespace-част и име на клас без namespace.
	 * 
	 * @param string $className напр name\space\lib_MyClass
	 * @return array масив с 2 елемента - първият е namespace, вторият - име на клас.
	 * 					напр. {[0] => 'name\space', [1] => 'lib_MyClass'}.
	 */
	public static function classNs($className)
	{
		$ns = '';
		
		if ( ($p = strrpos($className, '\\')) !== false ) {
			$ns        = substr($className, 0, $p);
			$className = substr($className, $p+1);
		}
		
		return array($ns, $className);
	}

	
	/**
	 * Разбива име клас на фамилно (всичко преди последния `_`) и собственото име (всичко след последния `_`)
	 * 
	 * @param string $className име на клас, напр. `my_class_Name`
	 * @return array масив с 2 елемента: 1-вия е фамилното име (`my_class`), 
	 * 					втория - собственото име (`Name`)
	 */
	public static function classOwnName($className)
	{
		$parentName = '';

		if ( ($p = strrpos($className, '_')) !== false ) {
			$parentName = substr($className, 0, $p);
			$className  = substr($className, $p+1);
		}
		
		return array($parentName, $className);
	}

	
	/**
	 * Връща истинското име на клас, стоящо зад зададен псевдоним.
	 * 
	 * Ако няма такъв псевдоним, резултата е същото име на клас.
	 * 
	 * @param string $alias
	 * @return string име на клас зад псевдонима $alias.
	 * @see Loader::classAlias()
	 */
	public static function classRealName($alias)
	{
		$realName = $alias;
		
		if (!empty(static::$classAliases[$alias])) {
			$realName = static::$classAliases[$alias];
		}
		
		return $realName;
	}
	
	
	/**
	 * Отделя име на клас до съставни части
	 * 
	 * @param string $className
	 * @return array масив с индекси:
	 * 		o ['ns'] - namespace
	 * 		o ['path'] - директорията на файла с реализацията
	 *		o ['classFile'] - име на файла с реализацията
	 *		o ['className'] - име на класа (без namespace)
	 * 		o ['realClassName'] - истинско име на класа (ако е псевдоним)
	 */
	public static function parseClassName($className)
	{
		list($ns, $className) = static::classNs($className);
		
		if (!empty($ns)) {
			$path = str_replace('\\', '/', $ns);
		}
		
		$realClassName = static::classRealName($className);
		
		list($parentName, $classOwnName) = static::classOwnName($realClassName);
		
		if (empty($path)) {
			if (!empty(static::$classMap[$realClassName])) {
				$path = static::$classMap[$realClassName];
			} else {
				$path = str_replace('_', '/', $parentName);
			}				
		}
		
		$classFile = $path . '/' . $classOwnName . '.class.php';
		
		return compact('ns', 'path', 'classFile', 'className', 'realClassName');
	}
}

//spl_autoload_register(array('App', 'loadClass'));