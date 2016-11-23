<?php

/**
 * UIType sharedOwner Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Vtiger_SharedOwner_UIType extends Vtiger_Base_UIType
{

	/**
	 * Function to get the Template name for the current UI Type object
	 * @return <String> - Template Name
	 */
	public function getTemplateName()
	{
		return 'uitypes/SharedOwner.tpl';
	}

	public function getListSearchTemplateName()
	{
		return 'uitypes/SharedOwnerFieldSearchView.tpl';
	}

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param string $value
	 * @param int $record
	 * @param Vtiger_Record_Model $recordInstance
	 * @param bool $rawText
	 * @return string
	 */
	public function getDisplayValue($values, $record = false, $recordInstance = false, $rawText = false)
	{
		$db = PearDatabase::getInstance();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$displayValue = [];
		if (empty($values)) {
			return $displayValue;
		} elseif (!is_array($values)) {
			$values = explode(',', $values);
		}

		foreach ($values as $shownerid) {
			if (\App\Fields\Owner::getType($shownerid) === 'Users') {
				if ($currentUser->isAdminUser() && !$rawText) {
					$displayValue[] = '<a href="index.php?module=User&view=Detail&record=' . $shownerid . '">' . rtrim(\App\Fields\Owner::getLabel($shownerid)) . '</a>';
				} else {
					$displayValue[] = rtrim(\App\Fields\Owner::getLabel($shownerid));
				}
			} else {
				if ($currentUser->isAdminUser() && !$rawText) {
					$displayValue[] = '<a href="index.php?module=Groups&parent=Settings&view=Detail&record=' . $shownerid . '">' . rtrim(\App\Fields\Owner::getLabel($shownerid)) . '</a>';
				} else {
					$displayValue[] = rtrim(\App\Fields\Owner::getLabel($shownerid));
				}
			}
		}
		return implode(', ', $displayValue);
	}

	/**
	 * Function to get the display value in edit view
	 * @param reference record id
	 * @return link
	 */
	public function getEditViewDisplayValue($value, $record = false)
	{
		if (empty($record)) {
			return [];
		}

		$query = (new \App\Db\Query())->select('userid')->from('u_#__crmentity_showners')->where(['crmid' => $record])->distinct();
		$values = $query->column();
		if (empty($values))
			$values = [];

		return $values;
	}

	/**
	 * Function to get the Display Value in ListView
	 * @param string $value
	 * @param int $record
	 * @param Vtiger_Record_Model $recordInstance
	 * @param bool $rawText
	 * @return string
	 */
	public function getListViewDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		$values = $this->getEditViewDisplayValue($value, $record);
		if (empty($values)) {
			return '';
		}
		$display = $shownerData = [];
		$maxLengthText = $this->get('field')->get('maxlengthtext');
		$isAdmin = \App\User::getCurrentUserModel()->isAdmin();
		foreach ($values as $key => $shownerid) {
			if (\App\Fields\Owner::getType($shownerid) === 'Users') {
				$userModel = Users_Privileges_Model::getInstanceById($shownerid);
				$userModel->setModule('Users');
				$display[$key] = $userModel->getName();
				if ($userModel->get('status') === 'Inactive') {
					$shownerData[$key]['inactive'] = true;
				}
				if ($isAdmin && !$rawText) {
					$shownerData[$key]['link'] = $userModel->getDetailViewUrl();
				}
			} else {
				$shownerName = \App\Fields\Owner::getLabel($shownerid);
				if (empty($shownerName)) {
					continue;
				}
				$display[$key] = $shownerName;
				$recordModel = new Settings_Groups_Record_Model();
				$recordModel->set('groupid', $shownerid);
				$detailViewUrl = $recordModel->getDetailViewUrl();
				if ($isAdmin && !$rawText) {
					$shownerData[$key]['link'] = $detailViewUrl;
				}
			}
		}
		$display = implode(', ', $display);
		$display = explode(', ', \vtlib\Functions::textLength($display, $maxLengthText));
		foreach ($display as $key => &$shownerName) {
			if (isset($shownerData[$key]['inactive'])) {
				$shownerName = '<span class="redColor">' . $shownerName . '</span>';
			}
			if (isset($shownerData[$key]['link'])) {
				$shownerName = "<a href='" . $shownerData[$key]['link'] . "'>$shownerName</a>";
			}
		}
		return implode(', ', $display);
	}

	/**
	 * Function to get the share users list
	 * @param int $record record ID
	 * @param bool $returnArray whether return data in an array
	 * @return array
	 */
	public static function getSharedOwners($record, $moduleName = false)
	{
		$shownerid = Vtiger_Cache::get('SharedOwner', $record);
		if ($shownerid !== false) {
			return $shownerid;
		}

		$query = (new \App\Db\Query())->select('userid')->from('u_#__crmentity_showners')->where(['crmid' => $record])->distinct();
		$values = $query->column();
		if (empty($values))
			$values = [];
		Vtiger_Cache::set('SharedOwner', $record, $values);
		return $values;
	}

	public static function getSearchViewList($module, $view)
	{
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$db = PearDatabase::getInstance();

		$queryGenerator = new QueryGenerator($module, $currentUser);
		$meta = $queryGenerator->getMeta($module);
		$baseTable = $meta->getEntityBaseTable();
		$tableIndexList = $meta->getEntityTableIndexList();
		$baseTableIndex = $tableIndexList[$baseTable];

		$queryGenerator->initForCustomViewById($view);
		$queryGenerator->setFields([]);
		$queryGenerator->setCustomColumn('userid');
		$queryGenerator->setCustomFrom([
			'joinType' => 'INNER',
			'relatedTable' => 'u_yf_crmentity_showners',
			'relatedIndex' => 'crmid',
			'baseTable' => $baseTable,
			'baseIndex' => $baseTableIndex,
		]);
		$listQuery = $queryGenerator->getQuery('SELECT DISTINCT');
		$result = $db->query($listQuery);

		$users = $group = [];
		while ($id = $db->getSingleValue($result)) {
			$name = \App\Fields\Owner::getUserLabel($id);
			if (!empty($name)) {
				$users[$id] = $name;
				continue;
			}
			$name = \App\Fields\Owner::getGroupName($id);
			if ($name !== false) {
				$group[$id] = $name;
				continue;
			}
		}
		asort($users);
		asort($group);
		return ['users' => $users, 'group' => $group];
	}
}
