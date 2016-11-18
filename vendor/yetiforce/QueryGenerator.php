<?php
namespace App;

/**
 * Query generator class
 * @package YetiForce.App
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class QueryGenerator
{

	const STRING_TYPE = ['string', 'text', 'email', 'reference'];
	const NUMERIC_TYPE = ['integer', 'double', 'currency'];
	const DATE_TYPE = ['date', 'datetime'];
	const EQUALITY_TYPES = ['currency', 'percentage', 'double', 'integer', 'number'];
	const COMMA_TYPES = ['picklist', 'multipicklist', 'owner', 'date', 'datetime', 'time', 'tree', 'sharedOwner', 'sharedOwner'];

	public $deletedCondition = true;
	public $permissions = true;
	private $moduleName;

	/**
	 * @var \App\Db\Query 
	 */
	private $query;

	/**
	 * @var \App\Db\Query 
	 */
	private $buildedQuery;
	private $fields = [];
	private $referenceFields = [];
	private $ownerFields = [];
	private $customColumns = [];
	private $cvColumns;
	private $stdFilterList;
	private $advFilterList;
	private $joins = [];
	private $queryFields = [];
	private $order = [];
	private $sourceRecord;

	/**
	 * @var boolean 
	 */
	private $ignoreComma = false;

	/**
	 * @var array Required conditions
	 */
	private $conditionsAnd = [];

	/**
	 * @var array Optional conditions
	 */
	private $conditionsOr = [];

	/**
	 * @var \Vtiger_Module_Model 
	 */
	private $moduleModel;

	/**
	 * @var array \Vtiger_Field_Model 
	 */
	private $fieldsModel;

	/**
	 * @var \CRMEntity 
	 */
	private $entityModel;

	/**
	 * @var User 
	 */
	private $user;

	/**
	 * QueryGenerator construct
	 * @param string $moduleName
	 * @param mixed $userId
	 */
	public function __construct($moduleName, $userId = false)
	{
		$this->moduleName = $moduleName;
		$this->moduleModel = \Vtiger_Module_Model::getInstance($moduleName);
		$this->entityModel = \CRMEntity::getInstance($moduleName);
		$this->user = User::getUserModel($userId ? $userId : User::getCurrentUserId());
	}

	/**
	 * Get module name
	 * @return string
	 */
	public function getModule()
	{
		return $this->moduleName;
	}

	/**
	 * Get query fields
	 * @return array
	 */
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * Get list view query fields
	 * @return array
	 */
	public function getListViewFields()
	{
		$headerFields = [];
		foreach ($this->getFields() as $fieldName) {
			if ($model = $this->getModuleField($fieldName)) {
				$headerFields[$fieldName] = $model;
			}
		}
		return $headerFields;
	}

	/**
	 * Set query fields
	 * @param type $fields
	 */
	public function setFields($fields)
	{
		$this->fields = $fields;
	}

	/**
	 * Set query field
	 * @param type $fields
	 */
	public function setField($fields)
	{
		if (is_array($fields))
			foreach ($fields as $field)
				$this->fields[] = $field;
		else
			$this->fields[] = $fields;
	}

	/**
	 * Load base module list fields
	 */
	public function loadListFields()
	{
		$listFields = $this->entityModel->list_fields_name;
		$listFields[] = 'id';
		$this->fields = $listFields;
	}

	/**
	 * Set custom column
	 * @param type $columns
	 */
	public function setCustomColumn($columns)
	{
		$this->customColumns[] = $columns;
	}

	/**
	 * Get CRMEntity Model
	 * @return \CRMEntity
	 */
	public function getEntityModel()
	{
		return $this->entityModel;
	}

	/**
	 * Get reference fields
	 * @param string $fieldName
	 * @return array
	 */
	public function getReference($fieldName)
	{
		return $this->referenceFields[$fieldName];
	}

	/**
	 * Add a mandatory condition
	 * @param array $condition
	 */
	public function addAndConditionNative($condition)
	{
		$this->conditionsAnd[] = $condition;
	}

	/**
	 * Add a optional condition
	 * @param array $condition
	 */
	public function addOrConditionNative($condition)
	{
		$this->conditionsOr[] = $condition;
	}

	/**
	 * Set source record
	 * @param int $sourceRecord
	 */
	public function setSourceRecord($sourceRecord)
	{
		$this->sourceRecord = $sourceRecord;
	}

	/**
	 * Appends a JOIN part to the query.
	 * @param array $join
	 */
	public function addJoin($join)
	{
		if (isset($this->joins[$join[1]])) {
			return false;
		}
		$this->joins[$join[1]] = $join;
	}

	/**
	 * Set ignore comma
	 * @param boolean $val
	 */
	public function setIgnoreComma($val)
	{
		$this->ignoreComma = $val;
	}

	/**
	 * Get ignore comma
	 * @return boolean
	 */
	public function getIgnoreComma()
	{
		return $this->ignoreComma;
	}

	/**
	 * Get fields module
	 * @return array
	 */
	public function getModuleFields()
	{
		if ($this->fieldsModel) {
			return $this->fieldsModel;
		}
		$moduleFields = $this->moduleModel->getFields();
		if ($this->moduleName === 'Calendar') {
			$eventModuleFieldList = \Vtiger_Module_Model::getInstance('Events')->getFields();
			$moduleFields = array_merge($moduleFields, $eventModuleFieldList);
		}
		foreach ($moduleFields as $fieldName => &$fieldModel) {
			if ($fieldModel->isReferenceField()) {
				$this->referenceFields[$fieldName] = $fieldModel->getReferenceList();
			}
			if ($fieldModel->getFieldDataType() === 'owner') {
				$this->ownerFields[] = $fieldName;
			}
		}
		return $this->fieldsModel = $moduleFields;
	}

	/**
	 * Get field module
	 * @return \Vtiger_Field_Model
	 */
	public function getModuleField($fieldName)
	{
		if (!$this->fieldsModel) {
			$this->getModuleFields();
		}
		if (isset($this->fieldsModel[$fieldName])) {
			return $this->fieldsModel[$fieldName];
		}
		return false;
	}

	/**
	 * Get default custom view query
	 * @return \App\Db\Query
	 */
	public function getDefaultCustomViewQuery()
	{
		$customView = CustomView::getInstance($this->moduleName, $this->user);
		$viewId = $customView->getViewId();
		if (empty($viewId) || $viewId === 0) {
			return false;
		}
		return $this->getCustomViewQueryById($viewId);
	}

	/**
	 * Init function for default custom view
	 */
	public function initForDefaultCustomView($noCache = false)
	{
		$customView = CustomView::getInstance($this->moduleName, $this->user);
		$viewId = $customView->getViewId($noCache);
		if (empty($viewId) || $viewId === 0) {
			return false;
		}
		$this->initForCustomViewById($viewId);
		return true;
	}

	/**
	 * Get custom view query by id
	 * @param string|int $viewId
	 * @return \App\Db\Query
	 */
	public function getCustomViewQueryById($viewId)
	{
		$this->initForCustomViewById($viewId);
		return $this->createQuery();
	}

	/**
	 * Fix date time value
	 * @param string $fieldName
	 * @param string $value
	 * @param boolean $first
	 * @return string
	 */
	public function fixDateTimeValue($fieldName, $value, $first = true)
	{
		$field = $this->getModuleField($fieldName);
		$type = $field ? $field->getFieldDataType() : false;
		if ($type === 'datetime') {
			if (strrpos($value, ' ') === false) {
				if ($first) {
					$value .= ' 00:00:00';
				} else {
					$value .= ' 23:59:59';
				}
			}
		}
		return $value;
	}

	/**
	 * Get custom view by id
	 * @param mixed $viewId
	 */
	public function initForCustomViewById($viewId)
	{
		$this->fields[] = 'id';
		$customView = CustomView::getInstance($this->moduleName, $this->user);
		$this->cvColumns = $customView->getColumnsListByCvid($viewId);
		if ($this->cvColumns) {
			foreach ($this->cvColumns as &$cvColumn) {
				list ($tableName, $columnName, $fieldName, $moduleFieldLabel, $fieldType) = explode(':', $cvColumn);
				if (empty($fieldName) && $columnName === 'crmid' && $tableName === 'vtiger_crmentity') {
					$this->customViewFields[] = 'id';
				} else {
					$this->fields[] = $fieldName;
					$this->customViewFields[] = $fieldName;
				}
			}
		}
		if ($this->moduleName === 'Calendar' && !in_array('activitytype', $this->fields)) {
			$this->fields[] = 'activitytype';
		}
		if ($this->moduleName === 'Documents') {
			if (in_array('filename', $this->fields)) {
				if (!in_array('filelocationtype', $this->fields)) {
					$this->fields[] = 'filelocationtype';
				}
				if (!in_array('filestatus', $this->fields)) {
					$this->fields[] = 'filestatus';
				}
			}
		}
		$this->stdFilterList = $customView->getStdFilterByCvid($viewId);
		$this->advFilterList = $customView->getAdvFilterByCvid($viewId);
		if (is_array($this->stdFilterList)) {
			if (!empty($this->stdFilterList['columnname'])) {
				list ($tableName, $columnName, $fieldName, $moduleFieldLabel, $fieldType) = explode(':', $this->stdFilterList['columnname']);
				$this->addRequiredCondition([
					'between',
					$fieldName,
					$this->fixDateTimeValue($fieldName, $this->stdFilterList['startdate']),
					$this->fixDateTimeValue($fieldName, $this->stdFilterList['enddate'], false)
				]);
			}
		}
		$this->parseAdvFilter();
	}

	/**
	 * Parsing advanced filters conditions
	 * @return boolean
	 */
	public function parseAdvFilter($advFilterList = false)
	{
		if (!$advFilterList) {
			$advFilterList = $this->advFilterList;
		}
		if (!$advFilterList) {
			return false;
		}
		foreach ($advFilterList as $group => &$filters) {
			$functionName = ($group === 'and' ? 'addAndCondition' : 'addOrCondition');
			foreach ($filters as &$filter) {
				list ($tableName, $columnName, $fieldName, $moduleFieldLabel, $fieldType) = explode(':', $filter['columnname']);
				if (empty($fieldName) && $columnName === 'crmid' && $tableName === 'vtiger_crmentity') {
					$columnName = $this->getColumnName('id');
				}
				$this->$functionName($fieldName, $filter['value'], $filter['comparator']);
			}
		}
	}

	/**
	 * Create query
	 * @return \App\Db\Query
	 */
	public function createQuery($reBuild = false)
	{
		if (!$this->buildedQuery || $reBuild) {
			$this->query = new Db\Query();
			$this->loadSelect();
			$this->loadFrom();
			$this->loadWhere();
			$this->loadOrder();
			$this->loadJoin();
			$this->buildedQuery = $this->query;
		}
		return $this->buildedQuery;
	}

	/**
	 * Sets the SELECT part of the query.
	 */
	public function loadSelect()
	{
		$allFields = array_keys($this->getModuleFields());
		$allFields[] = 'id';
		$this->fields = array_intersect($this->fields, $allFields);
		$columns = [];
		foreach ($this->fields as &$fieldName) {
			$columns[$fieldName] = $this->getColumnName($fieldName);
			//To merge date and time fields
			if ($this->moduleName === 'Calendar' && ($fieldName === 'date_start' || $fieldName === 'due_date')) {
				if ($fieldName === 'date_start') {
					$timeField = 'time_start';
					$columns[$fieldName] = $this->getColumnName($timeField);
				} elseif ($fieldName === 'due_date') {
					$timeField = 'time_end';
					$columns[$fieldName] = $this->getColumnName($timeField);
				}
			}
		}
		foreach ($this->customColumns as $customColumn) {
			$columns[] = $customColumn;
		}
		$this->query->select($columns);
	}

	/**
	 * Get column name by field name
	 * @param string $fieldName
	 * @return string
	 */
	public function getColumnName($fieldName)
	{
		if ($fieldName === 'id') {
			$baseTable = $this->entityModel->table_name;
			return $baseTable . '.' . $this->entityModel->tab_name_index[$baseTable];
		}
		$field = $this->getModuleField($fieldName);
		return $field->getTableName() . '.' . $field->getColumnName();
	}

	/**
	 * Sets the FROM part of the query.
	 */
	public function loadFrom()
	{
		$this->query->from($this->entityModel->table_name);
	}

	/**
	 * Sets the JOINs part of the query.
	 */
	public function loadJoin()
	{
		$tableList = $tableJoin = [];
		$moduleTableIndexList = $this->entityModel->tab_name_index;
		$baseTable = $this->entityModel->table_name;
		$baseTableIndex = $moduleTableIndexList[$baseTable];
		foreach ($this->fields as &$fieldName) {
			if ($fieldName === 'id') {
				continue;
			}
			$field = $this->getModuleField($fieldName);
			if ($field->getFieldDataType() === 'reference') {
				$tableJoin[$field->getTableName()] = 'INNER JOIN';
				foreach ($this->referenceFields[$fieldName] as &$moduleName) {
					if ($moduleName === 'Users' && $this->moduleName !== 'Users') {
						$this->addJoin(['LEFT JOIN', 'vtiger_users vtiger_users' . $fieldName, "{$field->getTableName()}.{$field->getColumnName()} = vtiger_users{$fieldName}.id"]);
						$this->addJoin(['LEFT JOIN', 'vtiger_groups vtiger_groups' . $fieldName, "{$field->getTableName()}.{$field->getColumnName()} = vtiger_groups{$fieldName}.groupid"]);
					}
				}
			} elseif ($field->getFieldDataType() === 'owner' && $fieldName === 'created_user_id') {
				$this->addJoin(['LEFT JOIN', 'vtiger_users vtiger_users' . $fieldName, "{$field->getTableName()}.{$field->getColumnName()} = vtiger_users{$fieldName}.id"]);
				$this->addJoin(['LEFT JOIN', 'vtiger_groups vtiger_groups' . $fieldName, "{$field->getTableName()}.{$field->getColumnName()} = vtiger_groups{$fieldName}.groupid"]);
			}
			if (!isset($tableList[$field->getTableName()])) {
				$tableList[$field->getTableName()] = $field->getTableName();
				$tableJoin[$field->getTableName()] = $this->entityModel->getJoinClause($field->getTableName());
			}
		}
		foreach ($this->getEntityDefaultTableList() as &$table) {
			if (!isset($tableList[$table])) {
				$tableList[$table] = $table;
				$tableJoin[$table] = 'INNER JOIN';
			}
		}
		if ($this->ownerFields) {
			//there are more than one field pointing to the users table, the real one is the one called assigned_user_id if there is one, otherwise pick the first
			if (in_array('assigned_user_id', $this->ownerFields)) {
				$ownerField = 'assigned_user_id';
			} else {
				$ownerField = $this->ownerFields[0];
			}
		}
		foreach ($this->getEntityDefaultTableList() as &$tableName) {
			$this->query->join($tableJoin[$tableName], $tableName, "$baseTable.$baseTableIndex = $tableName.{$moduleTableIndexList[$tableName]}");
			unset($tableList[$tableName]);
		}
		unset($tableList[$baseTable]);
		foreach ($tableList as &$tableName) {
			if ($tableName === 'vtiger_users') {
				$field = $this->getModuleField($ownerField);
				$this->addJoin([$tableJoin[$tableName], $tableName, "{$field->getTableName()}.{$field->getColumnName()} = $tableName.id"]);
			} elseif ($tableName == 'vtiger_groups') {
				$field = $this->getModuleField($ownerField);
				$this->addJoin([$tableJoin[$tableName], $tableName, "{$field->getTableName()}.{$field->getColumnName()} = $tableName.groupid"]);
			} else {
				$this->addJoin([$tableJoin[$tableName], $tableName, "$baseTable.$baseTableIndex = $tableName.$moduleTableIndexList[$tableName]"]);
			}
		}
		foreach ($this->joins as &$join) {
			$on = isset($join[2]) ? $join[2] : '';
			$params = isset($join[3]) ? $join[3] : [];
			$this->query->join($join[0], $join[1], $on, $params);
		}
	}

	/**
	 * Get entity default table list
	 * @return type
	 */
	public function getEntityDefaultTableList()
	{
		if (isset($this->entityModel->tab_name_index['vtiger_crmentity'])) {
			return ['vtiger_crmentity'];
		}
		return [];
	}

	/**
	 * Sets the WHERE part of the query.
	 */
	public function loadWhere()
	{
		if ($this->deletedCondition) {
			$this->query->andWhere($this->getDeletedCondition());
		}
		$this->query->andWhere(['or', array_merge(['and'], $this->conditionsAnd), array_merge(['or'], $this->conditionsOr)]);
		if ($this->permissions) {
			if (\AppConfig::security('CACHING_PERMISSION_TO_RECORD') && $this->moduleName !== 'Users') {
				$userId = $this->user->getUserId();
				$this->query->andWhere(['like', 'vtiger_crmentity.users', ",$userId,"]);
			} else {
				PrivilegeQuery::getConditions($this->query, $this->moduleName, $this->user, $this->sourceRecord);
			}
		}
	}

	/**
	 * Get conditions for non-deleted records
	 * @return string|array
	 */
	public function getDeletedCondition()
	{
		switch ($this->moduleName) {
			case 'Leads':
				$condition = ['vtiger_crmentity.deleted' => 0, 'vtiger_leaddetails.converted' => 0];
				break;
			case 'Users':
				$condition = ['vtiger_users.status' => 'Active'];
				break;
			default:
				$condition = 'vtiger_crmentity.deleted=0';
				break;
		}
		return $condition;
	}

	/**
	 * Added required condition
	 * @param string $fieldName
	 * @param mixed $value
	 * @param string $operator {@see CustomView::ADVANCED_FILTER_OPTIONS} and {@see CustomView::STD_FILTER_CONDITIONS}
	 */
	public function addAndCondition($fieldName, $value, $operator)
	{
		$condition = $this->parseCondition($fieldName, $value, $operator);
		if ($condition) {
			$this->conditionsAnd[] = $condition;
		}
		Log::error('Wrong condition');
	}

	/**
	 * Added optional condition
	 * @param string $fieldName
	 * @param mixed $value
	 * @param string $operator {@see CustomView::ADVANCED_FILTER_OPTIONS} and {@see CustomView::STD_FILTER_CONDITIONS}
	 */
	public function addOrCondition($fieldName, $value, $operator)
	{
		$condition = $this->parseCondition($fieldName, $value, $operator);
		if ($condition) {
			$this->conditionsOr[] = $condition;
		}
		Log::error('Wrong condition');
	}

	/**
	 * Parsing conditions to Yii2 query format
	 * @param string $fieldName
	 * @param mixed $value
	 * @param string $operator {@see CustomView::ADVANCED_FILTER_OPTIONS} and {@see CustomView::STD_FILTER_CONDITIONS}
	 * @return boolean
	 */
	private function parseCondition($fieldName, $value, $operator)
	{
		$queryField = $this->getQueryField($fieldName);
		$queryField->setValue($value);
		$queryField->setOperator($operator);
		return $queryField->getCondition();
	}

	/**
	 * Get query field instance
	 * @param string $fieldName
	 * @return QueryField\BaseField
	 * @throws \Exception\AppException
	 */
	private function getQueryField($fieldName)
	{
		if (isset($this->queryFields[$fieldName])) {
			return $this->queryFields[$fieldName];
		}
		if ($fieldName === 'id') {
			$queryField = new \App\QueryField\IdField($this, '');
			return $this->queryFields[$fieldName] = $queryField;
		}
		$field = $this->getModuleField($fieldName);
		if (empty($field)) {
			Log::error('Not found field model');
			throw new \Exception\AppException('LBL_NOT_FOUND_FIELD_MODEL');
		}
		$className = '\App\QueryField\\' . ucfirst($field->getFieldDataType()) . 'Field';
		if (!class_exists($className)) {
			Log::error('Not found query field condition');
			throw new \Exception\AppException('LBL_NOT_FOUND_QUERY_FIELD_CONDITION');
		}
		$queryField = new $className($this, $field);
		return $this->queryFields[$fieldName] = $queryField;
	}

	/**
	 * Set order
	 * @param string $fieldName
	 * @param string $order ASC/DESC
	 */
	public function setOrder($fieldName, $order = false)
	{
		$queryField = $this->getQueryField($fieldName);
		$this->order = array_merge($this->order, $queryField->getOrderBy($order));
	}

	/**
	 * Sets the ORDER BY part of the query.
	 */
	public function loadOrder()
	{
		if ($this->order) {
			$this->query->orderBy($this->order);
		}
	}

	/**
	 * Set base search condition (search_key,search_value in url)
	 * @param string $fieldName
	 * @param mixed $value
	 * @param string $operator
	 */
	public function addBaseSearchConditions($fieldName, $value, $operator = 'e')
	{
		if (empty($fieldName)) {
			return;
		}
		$field = $this->getModuleField($fieldName);
		$type = $field->getFieldDataType();
		if ($value !== '') {
			$value = function_exists('iconv') ? iconv('UTF-8', \AppConfig::main('default_charset'), $value) : $value; // search other characters like "|, ?, ?" by jagi
			if ($type === 'currency') {
				// Some of the currency fields like Unit Price, Total, Sub-total etc of Inventory modules, do not need currency conversion
				if ($field->getUIType() === 72) {
					$value = \CurrencyField::convertToDBFormat($value, null, true);
				} else {
					$value = \CurrencyField::convertToDBFormat($value);
				}
			}
		}
		if (trim(strtolower($value)) === 'null') {
			$operator = 'e';
		}
		$this->addAndCondition($fieldName, $value, $operator);
	}

	/**
	 * Parse base search condition to db condition 
	 * @param array $searchParams
	 * @return array
	 */
	public function parseBaseSearchParamsToCondition($searchParams)
	{
		if (empty($searchParams)) {
			return [];
		}
		$advFilterConditionFormat = [];
		$glueOrder = ['and', 'or'];
		$groupIterator = 0;
		foreach ($searchParams as &$groupInfo) {
			if (empty($groupInfo)) {
				continue;
			}
			$groupColumnsInfo = $groupConditionInfo = [];
			foreach ($groupInfo as &$fieldSearchInfo) {
				list ($fieldName, $operator, $fieldValue, $specialOption) = $fieldSearchInfo;
				$field = $this->getModuleField($fieldName);
				if ($field->getFieldDataType() === 'tree' && $specialOption) {
					$fieldValue = Settings_TreesManager_Record_Model::getChildren($fieldValue, $fieldName, $moduleModel);
				}
				//Request will be having in terms of AM and PM but the database will be having in 24 hr format so converting
				if ($field->getFieldDataType() === 'time') {
					$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
				}
				if ($field->getFieldDataType() === 'currency') {
					$fieldValue = CurrencyField::convertToDBFormat($fieldValue);
				}
				if ($fieldName === 'date_start' || $fieldName === 'due_date' || $field->getFieldDataType() === 'datetime') {
					$dateValues = explode(',', $fieldValue);
					//Indicate whether it is fist date in the between condition
					$isFirstDate = true;
					foreach ($dateValues as $key => $dateValue) {
						$dateTimeCompoenents = explode(' ', $dateValue);
						if (empty($dateTimeCompoenents[1])) {
							if ($isFirstDate) {
								$dateTimeCompoenents[1] = '00:00:00';
							} else {
								$dateTimeCompoenents[1] = '23:59:59';
							}
						}
						$dateValue = implode(' ', $dateTimeCompoenents);
						$dateValues[$key] = $dateValue;
						$isFirstDate = false;
					}
					$fieldValue = implode(',', $dateValues);
				}
				$groupColumnsInfo[] = ['columnname' => $field->getCustomViewColumnName(), 'comparator' => $operator, 'value' => $fieldValue];
			}
			$advFilterConditionFormat[$glueOrder[$groupIterator]] = $groupColumnsInfo;
			$groupIterator++;
		}
		return $advFilterConditionFormat;
	}

	/**
	 * Is the field date type
	 * @param string $type
	 * @return boolean
	 */
	public function isDateType($type)
	{
		return in_array($type, [static::DATE_TYPE]);
	}

	/**
	 * Is the field numeric type
	 * @param string $type
	 * @return boolean
	 */
	public function isNumericType($type)
	{
		return in_array($type, [static::NUMERIC_TYPE]);
	}

	/**
	 * Is the field string type
	 * @param string $type
	 * @return boolean
	 */
	public function isStringType($type)
	{
		return in_array($type, [static::STRING_TYPE]);
	}

	/**
	 * Is the field equality type
	 * @param string $type
	 * @return boolean
	 */
	public function isEqualityType($type)
	{
		return in_array($type, [static::EQUALITY_TYPES]);
	}

	/**
	 * Is the field comma separated type
	 * @param string $type
	 * @return boolean
	 */
	public function isCommaSeparatedType($type)
	{
		return in_array($type, [static::COMMA_TYPES]);
	}
}
