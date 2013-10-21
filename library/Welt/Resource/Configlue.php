<?php
/**
 * Config glue
 * Склеивает конфиги, расположенные в папке configs каждого модуля
 */

/**
 * @see Zend_Application_Resource_ResourceAbstract
 */

class Welt_Resource_Configlue extends Zend_Application_Resource_ResourceAbstract {
    public function init() {
        $this->_mergeConfigs();
    }

    protected function _mergeConfigs() {

        $front = Zend_Controller_Front::getInstance ();

        $modules = $front->getControllerDirectory();

		//Приклеиваем все конфиги кроме конфигов модулей
        foreach (array_keys($modules) as $module)
        {
            $configPath = $front->getModuleDirectory($module) . DIRECTORY_SEPARATOR . 'configs';
            if(is_dir($configPath))
            {
                $cfgdir = new DirectoryIterator($configPath);
                $config = new Zend_Config($this->getBootstrap()->getOptions(), true);
                $modified = false;
                foreach ($cfgdir as $file)
                {
                    $filePath = $file->getPathname();
                    if ($file->isFile() && is_file($filePath))
                    {
                        $pi = pathinfo($filePath);
                        $ext = strtolower($pi['extension']);
                        if(($ext == 'ini' || $ext == 'xml') && !(($module == $front->getDefaultModule() && $pi['filename']=='application') || $pi['filename'] == 'module'))
                        {
                            $options = $this->_loadOptions($filePath);
                            $config->merge($options);
                            $modified = true;
                        }
                    }
                }
                if($modified) $this->getBootstrap()->setOptions($config->toArray());
            }
        }
//      Приклеиваем конфиги модулей
        foreach (array_keys($modules) as $module)
        {
            $configPath  = $front->getModuleDirectory($module) . DIRECTORY_SEPARATOR . 'configs';
            if(is_dir($configPath))
            {
                $appOptions = $this->getBootstrap()->getOptions();
                $modified = false;
                if(is_file($configPath.DIRECTORY_SEPARATOR.'module.ini'))
                {
                    $modified = true;
                    $options = $this->_loadOptions($configPath.DIRECTORY_SEPARATOR.'module.ini');
                    if (array_key_exists($module, $appOptions))
                    {
                        if (is_array($appOptions[$module]))
                            $appOptions[$module] = array_merge($appOptions[$module], $options->toArray());
                        else
                            $appOptions[$module] = $options->toArray();
                    }
                    else
                        $appOptions[$module] = $options->toArray();

                }
                if(is_file($configPath.DIRECTORY_SEPARATOR.'module.xml'))
                {
                    $modified = true;
                    $options = $this->_loadOptions($configPath.DIRECTORY_SEPARATOR.'module.xml');
                    if (array_key_exists($module, $appOptions))
                    {
                        if (is_array($appOptions[$module]))
                            $appOptions[$module] = array_merge($appOptions[$module], $options->toArray());
                        else
                            $appOptions[$module] = $options->toArray();
                    }
                    else
                        $appOptions[$module] = $options->toArray();
                }
                if($modified) $this->getBootstrap()->setOptions($appOptions);
            }
        }
    }

    /**
     * Load the config file
     *
     * @param string $fullpath
     * @return array
     */
    protected function _loadOptions($fullpath){
        if (file_exists ( $fullpath ))
        {
            $pi = pathinfo($fullpath);
            switch (strtolower ( $pi['extension'] ) )
            {
                case 'ini' :
                    $cfg = new Zend_Config_Ini ( $fullpath, $this->getBootstrap ()->getEnvironment () );
                    break;
                case 'xml' :
                    $cfg = new Zend_Config_Xml ( $fullpath, $this->getBootstrap ()->getEnvironment () );
                    break;
                default :
                    throw new Zend_Config_Exception ( 'Invalid format for config file' );
                    break;
            }
        }
        else
        {
            throw new Zend_Application_Resource_Exception ( 'File does not exist' );
        }
        return $cfg;
    }
}
