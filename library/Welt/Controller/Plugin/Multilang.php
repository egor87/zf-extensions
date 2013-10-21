<?php

/**
 * Front Controller Plugin.
 * Hooks routeStartup, dispatchLoopStartup.
 * Multilanguage support, which adds language detection by URL prefix.
 *
 * @category   Multilang
 * @package    Multilang_Controller
 * @subpackage Plugins
 *
 * Description of Multilang
 *
 * @author     yugeon
 * @version    SVN: $Id$
 */
class Welt_Controller_Plugin_Multilang extends Zend_Controller_Plugin_Abstract
{
	
	/**
	 * Default language.
	 *
	 * @var string
	 */
	protected $_defaultLang = 'ru';
	/**
	 * Map of supported locales.
	 *
	 * @var array
	 */
	protected $_locales = array('ru' => 'ru_RU');
	/**
	 * URL delimetr symbol.
	 * @var string
	 */
	protected $_urlDelimiter = '/';

	/**
	 * Contructor
	 * Verify options
	 *
	 * @param array $options
	 */
	public function __construct($defaultLang = '', Array $localesMap = array())
	{
		$this->_locales = array_merge($this->_locales, $localesMap);
		if (array_key_exists($defaultLang, $this->_locales))
		{
			$this->_defaultLang = $defaultLang;
		}
	}

	/**
	 * routeStartup() plugin hook
	 * Parse URL and extract language if present in URL. Prepare base url for routing.
	 *
	 * @param Zend_Controller_Request_Abstract $request
	 */
	public function routeStartup(Zend_Controller_Request_Abstract $request)
	{
		if (strpos($request->getPathInfo(), 'admin'))
		{
			$request->setModuleName('admin');
			return true;
		}
		$front = Zend_Controller_Front::getInstance();
		// for multilanguage
		// TODO: set base URL for view http://__HOST__/__BASE_URL__ without language in URL.
		//$view->getHelper('BaseUrl')->setBaseUrl('http://xboom.local');
		// if language present in URL after baseUrl. (http://host/base_url/en/..., /ru, /rus...)
		$uri = $request->getRequestUri();	
		$lang = '';
		if (preg_match("#^/([a-zA-Z]{2,3})($|/)#", $request->getPathInfo(), $matches))
		{
			$lang = $matches[1];
		}
		// Check if lang in list of available language
		if (array_key_exists($lang, $this->_locales))
		{
			/**
			 * Если в url указан язык по умолчанию, редирект на ту же страницу, но
			 * без языка
			 */
			if ($lang == $this->_defaultLang)
			{
				$goto = substr($uri, 4, strlen($uri));
				$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
				$redirector->setCode(301)
						   ->gotoUrl($goto);
			}
			/**
			 * Если url имеет вид /en (без слеша на конце) и язык не тот, 
			 * который установлен по умолчанию , то делаем редиректна url слешом 
			 * на конце. Нужно для seo во избежание дублирования страниц.
			*/
			if ($uri == '/'.$lang && $lang != $this->_defaultLang)
			{
				$goto = $uri.'/';
				$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
				$redirector->setCode(301)
						   ->gotoUrl($goto);
			}
			// save original base URL
			$baseUrl = $front->getBaseUrl();
			Zend_Registry::set('orig_baseUrl', $baseUrl);
			// change base URL
			$front->setBaseUrl($baseUrl . $this->_urlDelimiter . $lang);
			// init path info with new baseUrl.
			$request->setPathInfo();
			// save present language
			Zend_Registry::set('url_lang', $lang);
		}
	}

	/**
	 * dispatchLoopStartup() plugin hook
	 * Last chance to define language.
	 * If language not present in URL and is a GET request then paste language in
	 * URL and redirect immediately.
	 *
	 * @param Zend_Controller_Request_Abstract $request
	 */
	public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
	{
		if (strpos($request->getPathInfo(), 'admin'))
		{
			return true;
		}
		$uri = $request->getRequestUri();
		$pageNotFound = false;
		$lang = '';
		//Если язык не передан в url
		if (!Zend_Registry::isRegistered('url_lang'))
		{
			$lang = $this->_defaultLang;
		}
		else
		{
			// language present in URL
			$lang = Zend_Registry::get('url_lang');
		}
		 //Инициируем локаль
		$localeString = $this->_locales[$lang];	
		$locale = new Zend_Locale($localeString);
		Zend_Registry::set('lang', $lang);
		//Определяем, какие файлы с переводом нужно игнорировать в зависимости от текущего модуля.
		if ($request->module == 'admin')
		{
			$ignore = array('default');
		}
		else
		{
			$ignore = array('admin');
		}
        //Создаём объект Zend_Translate
		$translator = new Zend_Translate (
			array (
				'adapter'	=>		'array',
				'content'	=>		APPLICATION_PATH.'/languages/',
				'locale'	=>		$lang,
				'scan'		=>	 	Zend_Translate::LOCALE_DIRECTORY,
				'ignore'	=>		$ignore
			)
		);
		Zend_Registry::set('translator', $translator);
		//Устанавливаем перевод сообщений для Zend_Form и валидаторов
		Zend_Form::setDefaultTranslator($translator);
		Zend_Validate_Abstract::setDefaultTranslator($translator);
        // Устанавливаем локаль в объект Zend_Translate
        $translator->setLocale($locale);
        //Сохраняем в реестре, чтобы хелперы смогли получить доступ к указанным объектам.
        Zend_Registry::set('Zend_Locale', $locale);
        Zend_Registry::set('Zend_Translate', $translator);
	}

}
