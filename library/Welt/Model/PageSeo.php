<?php
class Welt_Model_PageSeo extends Welt_Entity
{
	/**
	 * Возможные свойства объекта
	 * @var type 
	 */
	protected $_data = array(
		//Свойства, заполняющиеся из текущей таблицы
		'id'				=>		null,
        'pageId'			=>		null,
		'title'				=>		'',
		'titleEng'			=>		'',
		'metaDesc'			=>		'',
		'metaDescEng'		=>		'',
		'metaKeywords'		=>		'',
		'metaKeywordsEng'	=>		'',
		'metaOtherHtml'		=>		'',
		'created'			=>		'',
		'updated'			=>		''
    );
	
}