<?php
class Welt_Model_Page extends Welt_Entity
{
	/**
	 * Возможные свойства объекта
	 * @var type 
	 */
	protected $_data = array(
		//Свойства, заполняющиеся из текущей таблицы
        	'id'				=>		null,
		'pageType'			=>		null,
		'name'				=>		'',
		'nameEng'			=>		'',
		'content'			=>		null,
		'contentEng'			=>		null,
		'link'				=>		null,
		'mvcParams'			=>		null,
		'visible'			=>		1,
		'bannerImg'			=>		null,
		'bannerText'			=>		null,
		'bannerTextEng'			=>		null,
		'createdBy'			=>		'',
		'updatedBy'			=>		'',
		'created'			=>		'',
		'updated'			=>		null
		//Свойста, которые могут заполняться из других таблиц, например, при join
    );
	
}

