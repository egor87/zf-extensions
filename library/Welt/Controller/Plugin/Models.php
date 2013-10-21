<?php
//Определяем правила для подключения моделей при использовании модульной структуры
class Welt_Controller_Plugin_Models extends Zend_Controller_Plugin_Abstract
{
	    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
		{
	        set_include_path(
	            get_include_path() . PATH_SEPARATOR .
	            APPLICATION_PATH . '/modules/' . '/' . $request->getModuleName().'/models'
	        );
	    }
}