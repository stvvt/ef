<?php



/**
 * Keylist с избрани потребители. Могат да се избират или самостоятелни потребители или цели екипи
 *
 * Има следните атрибути:
 * - roles:         Избират се само потребители с някоя от тази роля
 * - rolesForTeams: Поне една от тях е необходима за да се покажат всички потребители от екипите, на които той е член
 * - rolesForAll:  Поне една от ролите е необходима за да се покажат всички екипи и потребители
 * Когато се записват като стринг в атрибута, ролите могат да бъдат разделени с вертикална черта
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see       core_Users
 */
class type_User extends type_Key
{
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        setIfNot($params['params']['mvc'], 'core_Users');
        setIfNot($params['params']['select'], 'names');
        
        parent::init($params);
        
        setIfNot($this->params['roles'], 'executive,officer,manager,ceo');
        $this->params['roles'] = str_replace("|", ",", $this->params['roles']);
        
        setIfNot($this->params['rolesForTeams'], 'officer,manager,ceo');
        $this->params['rolesForTeams'] = str_replace("|", ",", $this->params['rolesForTeams']);
        
        setIfNot($this->params['rolesForAll'], 'ceo');
        $this->params['rolesForAll'] = str_replace("|", ",", $this->params['rolesForAll']);
    }
    
    
    /**
     * Подготвя опциите според зададените параметри.
     * Ако е посочен суфикс, извеждате се само интерфейсите
     * чието име завършва на този суфикс
     */
    private function prepareOptions()
    {
        if (isset($this->options)) {
            return;
        }
        
        // Вариант 1: Потребителя няма права да вижда екипите
        // Тогава евентуално можем да покажем само една опция, и тя е с текущия потребител
        if(!haveRole($this->params['rolesForTeams'])) {
            if(haveRole($this->params['roles'])) {
                $user = core_Users::getCurrent();
                $name = core_Users::getCurrent('names', TRUE);
                $this->options = array($user => $name);
            } else {
                $this->options = array();
            }
            
            return;
        } else {
            
            $uQuery = core_Users::getQuery();
            $uQuery->where("#state = 'active'");
            
            // Потребителите, които ще покажем, трябва да имат посочените роли
            $roles = core_Roles::keylistFromVerbal($this->params['roles']);
            $uQuery->likeKeylist('roles', $roles);
            
            if(haveRole($this->params['rolesForAll'])) {
                // Показваме всички екипи
                $teams = core_Roles::getRolesByType('team');
            } else {
                // Показваме само екипите на потребителя
                $teams = core_Users::getUserRolesByType(NULL, 'team');
            }
            
            $teams = type_Keylist::toArray($teams);
            
            $this->options = array();
            
            foreach($teams as $t) {
                $group = new stdClass();
                $group->title = "Екип \"" . core_Roles::getVerbal($t, 'role') . "\"";
                $group->attr = array('class' => 'team');
                $group->group = TRUE;
                $group->keylist = $this->options[$t . ' team'] = $group;
                
                $uQueryCopy = clone($uQuery);
                
                $uQueryCopy->likeKeylist('roles', "|{$t}|");
                
                $teamMembers = '';
                
                $part = $this->params['select'];
                
                while($uRec = $uQueryCopy->fetch()) {
                    $this->options[$t . '_' . $uRec->id ]->title = core_Users::getVerbal($uRec, $part);
                    $this->options[$t . '_' . $uRec->id]->value = $uRec->id;
                    
                    $teamMembers .= $teamMembers ? '|' . $uRec->id : $uRec->id;
                }
                
                if($teamMembers) {
                    // $this->options[$t. ' team']->keylist = "|{$teamMembers}|";
                } else {
                    unset($this->options[$t . ' team']);
                }
            }
        }
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $this->prepareOptions();
        
        if(empty($value)) {
            $value = '|' . core_Users::getCurrent() . '|';
        }
        
        foreach($this->options as $key => $optObj) {
            if($value == $optObj->value) {
                break;
            }
        }
        
        return ht::createSelect($name, $this->options, $key, $attr);
    }
    
    
    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
    function fromVerbal_($value)
    {
        $this->prepareOptions();
        
        return $this->options[$value]->value;
    }
    
    
    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
    function toVerbal_($value)
    {
        $this->prepareOptions();
        
        foreach($this->options as $key => $optObj) {
            if($value == $optObj->value) {
                break;
            }
        }
        
        return $this->options[$key]->title;
    }
}