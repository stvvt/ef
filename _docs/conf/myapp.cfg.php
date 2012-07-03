<?php

/*****************************************************************************
 *                                                                           *
 *      Примерен конфигурационен файл за приложение в Experta Framework      *
 *                                                                           *
 *      След като се попълнят стойностите на константите, този файл          *
 *      трябва да бъде записан в [conf] директорията под име:                *
 *      [име на приложението].cfg.php                                        *
 *                                                                           *
 *****************************************************************************/




/***************************************************
*                                                  *
* Параметри за връзка с базата данни               *
*                                                  *
****************************************************/ 

// Име на базата данни. По подразбиране е същото, като името на приложението
DEFINE('EF_DB_NAME', EF_APP_NAME);

// Потребителско име. По подразбиране е същото, като името на приложението
DEFINE('EF_DB_USER', EF_APP_NAME);

// По-долу трябва да се постави реалната парола за връзка
// с базата данни на потребителят дефиниран в предходния ред
DEFINE('EF_DB_PASS', 'USER_PASSWORD_FOR_DB'); 

// Сървъра за на базата данни
DEFINE('EF_DB_HOST', 'localhost');
 
// Кодировка на забата данни
DEFINE('EF_DB_CHARSET', 'utf8');


/***************************************************
*                                                  *
* Някои от другите възможни константи              *
*                                                  *
****************************************************/ 

// Къде са външните компоненти? По подразбиране са в
// EF_ROOT_PATH/vendors
 # DEFINE( 'EF_VENDORS_PATH', 'PATH_TO_FOLDER');

// Базова директория, където се намират по-директориите за
// временните файлове. По подразбиране е в
// EF_ROOT_PATH/temp
 # DEFINE( 'EF_TEMP_BASE_PATH', 'PATH_TO_FOLDER');

// Базова директория, където се намират по-директориите за
// потребителски файлове. По подразбиране е в
// EF_ROOT_PATH/uploads
 # DEFINE( 'EF_UPLOADS_BASE_PATH', 'PATH_TO_FOLDER');

// Език на интерфейса по подразбиране. Ако не се дефинира
// се приема, че езика по подрзбиране е български
# DEFINE('EF_DEFAULT_LANGUAGE', 'en');

// Дали вместо ник, за име на потребителя да се приема
// неговия имейл адрес. По подразбиране се приема, че
// трябва да се изисква отделен ник, въведен от потребителя
#DEFINE('EF_USSERS_EMAIL_AS_NICK', TRUE);

// Твърдо, фиксирано име на мениджъра с контролерните функции. 
// Ако се укаже, цялото проложение може да има само един такъв 
// мениджър функции. Това е удобство за специфични приложения, 
// при които не е добре името на мениджъра да се вижда в URL-то
 # DEFINE('EF_CTR_NAME', 'FIXED_CONTROLER');

// Твърдо, фиксирано име на екшън (контролерна функция). 
// Ако се укаже, от URL-то се изпускат екшъните.
 # DEFINE('EF_ACT_NAME', 'FIXED_CONTROLER');
