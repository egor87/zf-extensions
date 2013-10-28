<?php
class Welt_Mapper_Pages extends Welt_Mapper
{
	/**
	 * Название таблицы
	 * @var string
	 */
	protected $_tableName = 'pages';
	
	/**
	 * Карта соответствия полей таблицы в БД свойствам доменного объекта
	 * @var array 
	 */
	protected $_map = array(
		'id'				=>		'id',
		'pageType'			=>		'page_type',
		'name'				=>		'name',
		'nameEng'			=>		'name_eng',
		'content'			=>		'content',
		'contentEng'		=>		'content_eng',
		'link'				=>		'link',
		'mvcParams'			=>		'mvc_params',
		'visible'			=>		'visible',
		'bannerImg'			=>		'banner_img',
		'bannerText'		=>		'banner_text',
		'bannerTextEng'		=>		'banner_text_eng',
		'createdBy'			=>		'created_by',
		'updatedBy'			=>		'updated_by',
		'created'			=>		'created',
		'updated'			=>		'updated'
	);
	
	/**
	 * Названия полей, которые не нужно обновлять при полном обновлении строки
	 * @var array 
	 */
	protected $_updateIgnoreFields = array('page_type', 'visible', 'created_by', 'created', 'updated');
	
	/**
	 * Название класса доменного объекта
	 * @var type string
	 */
	protected $_entityClass = 'Welt_Model_Page';
	
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
	 * Получение данных о странице по link
	 * @param string $link 
	 */
	public function getPageByLink($link)
	{
		//Проверяем наличие в кэше, если есть - загружаем
		$cacheId = 'getPageByLink_'.md5($link).$this->_lang;
		if ($this->_cache->test($cacheId))
		{
			$page = $this->_cache->load($cacheId);
		}
		//Если в кэше нет данных, формируем запрос, получаем даннные
		else
		{
			//Установка полей для выборки в зависимости от системного языка
			$this->setFieldsByLang();
			//Массив полей для выборки
			$fields = array(
				'id',
				'name'			=>		$this->langDependFields['name'],
				'content'		=>		$this->langDependFields['content'],
				'banner_img'	=>		'banner_img',
				'banner_text'	=>		$this->langDependFields['banner_text'],
				'link'
			);
			//Формируем запрос
			$select = $this->_tableGateway->select();
			$select->from(array($this->_tableName), $fields)
				   ->where('link=?',$link)
				   ->where('page_type=?','static')
				   ->where('visible=?',1);
			$resultRow = $this->_tableGateway->fetchRow($select);
			//Если страница не найдена
			if(!$resultRow)
			{
				return false;
			}
			//Если для страницы не задан текст для банера
			//Преобразуем результаты выборки в объект, возращаем результат
			$page = $this->rowToModel($resultRow);
			//Сохраняем данные в кэш
			$this->_cache->save($page, $cacheId);
		}
		return $page;
	}
	
	/**
	 * Получение текста для баннера по умолчанию. Берётся с главной страницы.
	 */
	public function getDefaultBannerText()
	{
		//Установка полей для выборки в зависимости от системного языка
		$this->setFieldsByLang();
		//Формируем запрос
		$select = $this->_tableGateway->select();
		$select->from(array($this->_tableName),array('bannerText'=>$this->langDependFields['banner_text']))
			   ->where('link=?','main');
		$resultRow = $this->_tableGateway->fetchRow($select);
		return $resultRow->bannerText;
	}
	
	/**
	 * Получение списка страниц
	 * @return 
	 */
	public function getPagesList()
	{
		$fields = array(
			'id',
			'page_type',
			'name',
			'name_eng',
			'link',
			'mvc_params',
			'updated'		=>		'DATE_FORMAT(updated,"%d.%m.%Y %H:%i")',
			'visible'
		);
		//Формируем запрос
		$select = $this->_tableGateway->select();
		$select->from(array($this->_tableName),$fields)
			   ->order('page_type DESC')
			   ->order('name ASC');
		$rowSet = $this->_tableGateway->fetchAll($select);
		//Преобразуем результаты выборки в объекты, возращаем результат
		return $this->rowSetToModels($rowSet);
	}
	
	
	/**
	 * Получение полной информации по странице
	 * @param string $pageId 
	 */
	public function getPageFullInfo($pageId)
	{
		$row = $this->_tableGateway->fetchRow($this->_tableGateway->select()->where('id=?',$pageId));
		if(is_null($row))
		{
			return false;
		}
		return $this->rowToModel($row);
	}
	
	/**
	 * Смена режима публикации для страницы
	 * @param int $pageId
	 * @param int $visible 
	 */
	public function changeVisible($pageId, $visible)
	{
		$data = array('visible'=>$visible);
		$where = $this->_getGateway()->getAdapter()->quoteInto('id=?', $pageId);
		return $this->updateData($data, $where);
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
				'name'			=>		$this->_map['nameEng'],
				'content'		=>		$this->_map['contentEng'],
				'banner_text'	=>		$this->_map['bannerTextEng']
			);
		}
		else
		{
			$this->langDependFields = array(
				'name'			=>		$this->_map['name'],
				'content'		=>		$this->_map['content'],
				'banner_text'	=>		$this->_map['bannerText']
			);
		}
	}
}
