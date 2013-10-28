<?php
class Welt_Mapper_PagesSeo extends Welt_Mapper
{
	/**
	 * Название таблицы
	 * @var string
	 */
	protected $_tableName = 'pages_seo';
	
	/**
	 * Соответствие полей таблицы в БД свойствам объекта
	 * @var array 
	 */
	protected $_map = array(
		'id'				=>		'id',
		'pageId'			=>		'page_id',
		'title'				=>		'title',
		'titleEng'			=>		'title_eng',
		'metaDesc'			=>		'meta_desc',
		'metaDescEng'		=>		'meta_desc_eng',
		'metaKeywords'		=>		'meta_keywords',
		'metaKeywordsEng'	=>		'meta_keywords_eng',
		'metaOtherHtml'		=>		'meta_other_html',
		'created'			=>		'created',
		'updated'			=>		'updated'
	);
	
	
	/**
	 * Название класса доменного объекта
	 * @var type string
	 */
	protected $_entityClass = 'Welt_Model_PageSeo';
	
	/**
	 * Массив из названий полей для выборки, формирующийся в зависимости от системного языка
	 * @var array 
	 */
	protected $langDependFields;
	
	public function __construct()
	{
		parent::__construct();
		//Устанавливаем кэш
		$this->setCache('databaseExtraLong');
	}

	
	/**
	 * Получение параметров seo для страницы
	 * @param int $pageId
	 * @return type 
	 */
	public function getPageSeo($pageId)
	{
		//Проверяем наличие в кэше, если есть - загружаем
		$cacheId = 'getPageSeo_'.$pageId.$this->_lang;
		if ($this->_cache->test($cacheId))
		{
			$pageSeo = $this->_cache->load($cacheId);
		}
		//Если в кэше нет данных, формируем запрос, получаем даннные
		else
		{
			//Установка полей для выборки в зависимости от системного языка
			$this->setFieldsByLang();
			//Массив полей для выборки
			$fields = array(
				'page_id',
				'title'				=>		$this->langDependFields['title'],
				'meta_desc'			=>		$this->langDependFields['meta_desc'],
				'meta_keywords'		=>		$this->langDependFields['meta_keywords'],
				'meta_other_html'
			);
			//Формируем запрос
			$select = $this->_tableGateway->select();
			$select->from(array($this->_tableName),$fields)
				   ->where('page_id=?',$pageId);
			//Получаем результат и возвращаем его в виде объекта
			$row = $this->_tableGateway->fetchRow($select);
			if (is_null($row))
			{
				$pageSeo = false;
			}
			else
			{
				$pageSeo = $this->rowToModel($row);
			}
			//Сохраняем данные в кэш
			$this->_cache->save($pageSeo, $cacheId);
		}
		return $pageSeo;
	}
	
	/**
	 * Получение всех параметров seo для страницы
	 * @param string $pageId
	 * @return type 
	 */
	public function getPageAllSeo($pageId)
	{
		$row = $this->_tableGateway->fetchRow($this->_tableGateway->select()->where('page_id=?',$pageId));
		if(is_null($row))
		{
			return false;
		}
		return $this->rowToModel($row);
	}
	
	/**
	 * Проверка существования параметров seo для страницы
	 * @param int $pageId
	 * @return bool 
	 */
	public function checkPageSeo($pageId)
	{
		$select = $this->_tableGateway->select();
		$select->from(array($this->_tableName),array('count' => 'COUNT(*)'))
			   ->where('page_id=?',$pageId);
		$row = $this->_tableGateway->fetchRow($select);
		if ($row->count > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Переопределяем функцию обновления
	 * @param Welt_Entity $model 
	 */
	public function update(Welt_Entity $model)
	{
		$data = array(
			'title'					=>		$model->title,
			'title_eng'				=>		$model->titleEng,
			'meta_desc'				=>		$model->metaDesc,
			'meta_desc_eng'			=>		$model->metaDescEng,
			'meta_keywords'			=>		$model->metaKeywords,
			'meta_keywords_eng'		=>		$model->metaKeywordsEng,
			'meta_other_html'		=>		$model->metaOtherHtml
		);
		$where = $this->_getGateway()->getAdapter()->quoteInto('page_id=?', $model->pageId);
		return $this->_getGateway()->update($data, $where);
	}
	
	/**
	 * Переопределяем функцию добавления
	 * @param Welt_Entity $model 
	 */
	public function insert(Welt_Entity $model)
	{
		//Добавляем дату создания
		if ($model->created == '')
		{
			$model->created = Welt_Functions::getDateTimeMysql();
		}
		parent::insert($model);
	}
	
	/**
	 * Установка полей для выборки в зависимости от системного языка
	 */
	protected function setFieldsByLang()
	{
		if ($this->_lang == 'en')
		{
			$this->langDependFields = array(
				'title'			=>		$this->_map['titleEng'],
				'meta_desc'		=>		$this->_map['metaDescEng'],
				'meta_keywords'	=>		$this->_map['metaKeywordsEng']
			);
		}
		else
		{
			$this->langDependFields = array(
				'title'			=>		$this->_map['title'],
				'meta_desc'		=>		$this->_map['metaDesc'],
				'meta_keywords'	=>		$this->_map['metaKeywords']
			);
		}
	}
}