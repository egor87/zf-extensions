<?php
/*
 * Плагин, который позволяет загружать класс Zend_Application_Module_Bootstrap
 * только для текущего модуля. По умолчанию в zf загружаются классы Zend_Application_Module_Bootstrap
 * для всех модулей, вне зависимости от текущего.
 */
class Welt_Controller_Plugin_LazyLoadModules extends Zend_Controller_Plugin_Abstract
{
    /**
     * @var Zend_Application_Bootstrap_BootstrapAbstract
     */
    protected $_bootstrap = null;
    
    /**
     * @var array
     */
    protected $_bootstrapedModules = array(
    );
    
    /**
     * Constructor
     * @param array $options
     **/
    public function __construct($options = null)
    {
        if ($options !== null)
        {
            $this->setOptions($options);
        }
    }
    
    /**
     * Implement configurable object pattern
     * @param array $options
     */
    public function setOptions($options)
    {
        foreach ((array) $options as $name=>$value)
        {
            $setter = 'set'.ucfirst($name);
            if (is_callable(array(
                $this, $setter
            )))
            {
                $this->$setter($value);
            }
        }
    }
    
    /**
     * Bootstrap setter
     */
    public function setBootstrap(Zend_Application_Bootstrap_BootstrapAbstract $bootstrap)
    {
        $this->_bootstrap = $bootstrap;
    }
    
    /**
     * get Front Controller
     * @return Zend_Controller_Front
     */
    protected function _getFront()
    {
        return Zend_Controller_Front::getInstance();
    }
    /**
     * Return is module run
     * @param string $module
     * @return bool
     */
    public function isBootsraped($module)
    {
        return isset($this->_bootstrapedModules[$module]);
    }

    
    /**
     * Get bootstraps that have been run
     *
     * @return Array
     */
    public function getExecutedBootstraps()
    {
        return $this->_bootstrapedModules;
    }
    
    /**
     * Format a module name to the module class prefix
     *
     * @param string $name
     * @return string
     */
    protected function _formatModuleName($name)
    {
        $name = strtolower($name);
        $name = str_replace(array(
            '-', '.'
        ), ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);
        return $name;
    }
    
    /**
     */
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        //Определяем название модуля
        $requesUri = $this->_getFront()->getRequest()->getServer('REQUEST_URI');
        if (strpos($requesUri, 'admin'))
        {
            $module = 'admin';
        }
        else
        {
            $module = $this->_getFront()->getDefaultModule();
        }
        if (!$this->isBootsraped($module))
        {
            $moduleDirectory = $this->_getFront()->getControllerDirectory($module);
            $bootstrapClass = $this->_formatModuleName($module).'_LazyLoadBootstrap';       
            if (!class_exists($bootstrapClass, false))
            {
                $bootstrapPath = dirname($moduleDirectory).'/LazyLoadBootstrap.php';
                if (file_exists($bootstrapPath))
                {
                    $eMsgTpl = 'Bootstrap file found for module "%s" but bootstrap class "%s" not found';
                    include_once $bootstrapPath;
                    if (!class_exists($bootstrapClass, false))
                    {
                        throw new Zend_Application_Resource_Exception(sprintf($eMsgTpl, $module, $bootstrapClass));
                    }
                }
                else
                {
                    return;
                }
            }
            $moduleBootstrap = new $bootstrapClass($this->_bootstrap);
            $moduleBootstrap->bootstrap();
            $this->_bootstrapedModules[$module] = $moduleBootstrap;
        }
    
    }
}
