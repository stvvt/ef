<?php



/**
 * Клас 'page_Wrapper' - Опаковка на страниците
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
class page_Wrapper extends core_BaseClass {
    
    
    /**
     * Прави стандартна 'обвивка' на изгледа
     */
    function render_($content)
    {
        if (!($tplName = Mode::get('wrapper'))) {
            $tplName = Mode::is('printing') ? 'page_Print' : 'page_Internal';
        }
        
        // Зареждаме опаковката 
        $wrapperTpl = cls::get($tplName);
        
        
        // Вземаме плейсхолдерите
        $placeHolders = $wrapperTpl->getPlaceHolders();

        
        // Заместваме специалните плейсхолдери, със съдържанието към което те сочат
        foreach($placeHolders as $place) {
            
            $method = explode('::', $place);

            if(count($method) != 2) continue;


            $html = call_user_func($method);

            $wrapperTpl->replace($html, $place);
        }
        
        // Изпращаме на изхода опаковано съдържанието
        $wrapperTpl->replace($content, 'PAGE_CONTENT');

        $wrapperTpl->output();
    }
}