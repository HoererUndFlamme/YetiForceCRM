<?php
/* +********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ****************************************************************************** */

// Note is used to store customer information.
class Documents extends CRMEntity
{

	public $table_name = "vtiger_notes";
	public $table_index = 'notesid';
	public $default_note_name_dom = array('Meeting vtiger_notes', 'Reminder');
	public $tab_name = Array('vtiger_crmentity', 'vtiger_notes', 'vtiger_notescf');
	public $tab_name_index = Array('vtiger_crmentity' => 'crmid', 'vtiger_notes' => 'notesid', 'vtiger_senotesrel' => 'notesid', 'vtiger_notescf' => 'notesid');

	/**
	 * Mandatory table for supporting custom fields.
	 */
	public $customFieldTable = Array('vtiger_notescf', 'notesid');
	public $column_fields = Array();
	public $sortby_fields = Array('title', 'modifiedtime', 'filename', 'createdtime', 'lastname', 'filedownloadcount', 'smownerid');
	// This is used to retrieve related vtiger_fields from form posts.
	public $additional_column_fields = Array('', '', '', '');
	// This is the list of vtiger_fields that are in the lists.
	public $list_fields = Array(
		'Title' => Array('notes' => 'title'),
		'File Name' => Array('notes' => 'filename'),
		'Modified Time' => Array('crmentity' => 'modifiedtime'),
		'Assigned To' => Array('crmentity' => 'smownerid'),
		'Folder Name' => Array('attachmentsfolder' => 'folderid')
	);
	public $list_fields_name = Array(
		'Title' => 'notes_title',
		'File Name' => 'filename',
		'Modified Time' => 'modifiedtime',
		'Assigned To' => 'assigned_user_id',
		'Folder Name' => 'folderid'
	);

	/**
	 * @var string[] List of fields in the RelationListView
	 */
	public $relationFields = ['notes_title', 'filename', 'modifiedtime', 'assigned_user_id', 'folderid', 'filelocationtype', 'filestatus'];
	public $search_fields = Array(
		'Title' => Array('notes' => 'notes_title'),
		'File Name' => Array('notes' => 'filename'),
		'Assigned To' => Array('crmentity' => 'smownerid'),
		'Folder Name' => Array('attachmentsfolder' => 'foldername')
	);
	public $search_fields_name = Array(
		'Title' => 'notes_title',
		'File Name' => 'filename',
		'Assigned To' => 'assigned_user_id',
		'Folder Name' => 'folderid'
	);
	public $list_link_field = 'notes_title';
	public $old_filename = '';
	public $mandatory_fields = Array('notes_title', 'createdtime', 'modifiedtime', 'filename', 'filesize', 'filetype', 'filedownloadcount', 'assigned_user_id');
	//Added these variables which are used as default order by and sortorder in ListView
	public $default_order_by = '';
	public $default_sort_order = 'DESC';

	/**
	 * Function to handle module specific operations when saving a entity
	 * @param string $module
	 */
	public function save_module($module)
	{
		$db = \App\Db::getInstance();
		if (isset($this->parentid) && !empty($this->parentid))
			$relid = $this->parentid;
		//inserting into vtiger_senotesrel
		if (isset($relid) && !empty($relid)) {
			$this->insertintonotesrel($relid, $this->id);
		}
		$fileTypeFieldName = $this->getFileTypeFieldName();
		$fileNameByField = $this->getFile_FieldName();

		if ($this->column_fields[$fileTypeFieldName] === 'I') {
			if (!empty($_FILES[$fileNameByField]['name'])) {
				$errCode = $_FILES[$fileNameByField]['error'];
				if ($errCode == 0) {
					foreach ($_FILES as $fileindex => $files) {
						$fileInstance = \App\Fields\File::loadFromRequest($files);
						if ($fileInstance->validate()) {
							$fileName = $_FILES[$fileNameByField]['name'];
							$fileName = \vtlib\Functions::fromHTML(preg_replace('/\s+/', '_', $fileName));
							$fileType = $_FILES[$fileNameByField]['type'];
							$fileSize = $_FILES[$fileNameByField]['size'];
							$fileLocationType = 'I';
							$fileName = ltrim(basename(" " . $fileName)); //allowed filename like UTF-8 characters
						}
					}
				}
			} elseif ($this->mode === 'edit') {
				$noteData = (new \App\Db\Query())->select(['filetype', 'filesize', 'filename', 'filedownloadcount', 'filelocationtype'])->from('vtiger_notes')
					->where(['notesid' => $this->id])
					->one();
				if ($noteData) {
					$fileName = $noteData['filename'];
					$fileType = $noteData['filetype'];
					$fileSize = $noteData['filesize'];
					$fileDownloadCount = $noteData['filedownloadcount'];
					$fileLocationType = $noteData['filelocationtype'];
				}
			} elseif ($this->column_fields[$fileNameByField]) {
				$fileName = $this->column_fields[$fileNameByField];
				$fileSize = $this->column_fields['filesize'];
				$fileType = $this->column_fields['filetype'];
				$fileLocationType = $this->column_fields[$fileTypeFieldName];
				$fileDownloadCount = 0;
			} else {
				$fileLocationType = 'I';
				$fileType = '';
				$fileSize = 0;
				$fileDownloadCount = null;
			}
		} else if ($this->column_fields[$fileTypeFieldName] === 'E') {
			$fileLocationType = 'E';
			$fileName = $this->column_fields[$fileNameByField];
			// If filename does not has the protocol prefix, default it to http://
			// Protocol prefix could be like (https://, smb://, file://, \\, smb:\\,...)
			if (!empty($fileName) && !preg_match('/^\w{1,5}:\/\/|^\w{0,3}:?\\\\\\\\/', trim($fileName), $match)) {
				$fileName = "http://$fileName";
			}
			$fileType = '';
			$fileSize = 0;
			$fileDownloadCount = null;
		}
		$db->createCommand()->update('vtiger_notes', ['filename' => decode_html($fileName), 'filesize' => $fileSize, 'filetype' => $fileType, 'filelocationtype' => $fileLocationType, 'filedownloadcount' => $fileDownloadCount], ['notesid' => $this->id])->execute();
//		//Inserting into attachments table
		if ($fileLocationType === 'I') {
			$this->insertIntoAttachment($this->id, 'Documents');
		} else {
			$db->createCommand()->delete('vtiger_seattachmentsrel', ['crmid' => $this->id])->execute();
		}
		//set the column_fields so that its available in the event handlers
		$this->column_fields['filename'] = $fileName;
		$this->column_fields['filesize'] = $fileSize;
		$this->column_fields['filetype'] = $fileType;
		$this->column_fields['filedownloadcount'] = $fileDownloadCount;
	}

	/**
	 *      This function is used to add the vtiger_attachments. This will call the function uploadAndSaveFile which will upload the attachment into the server and save that attachment information in the database.
	 *      @param int $id  - entity id to which the vtiger_files to be uploaded
	 *      @param string $module  - the current module name
	 */
	public function insertIntoAttachment($id, $module)
	{
		$adb = PearDatabase::getInstance();

		\App\Log::trace("Entering into insertIntoAttachment($id,$module) method.");

		$file_saved = false;

		foreach ($_FILES as $fileindex => $files) {
			if ($files['name'] != '' && $files['size'] > 0) {
				$files['original_name'] = AppRequest::get($fileindex . '_hidden');
				$file_saved = $this->uploadAndSaveFile($id, $module, $files);
			}
		}

		\App\Log::trace("Exiting from insertIntoAttachment($id,$module) method.");
	}

	/**    Function used to get the sort order for Documents listview
	 *      @return string  $sorder - first check the $_REQUEST['sorder'] if request value is empty then check in the $_SESSION['NOTES_SORT_ORDER'] if this session value is empty then default sort order will be returned.
	 */
	public function getSortOrder()
	{

		\App\Log::trace('Entering getSortOrder() method ...');
		if (AppRequest::has('sorder'))
			$sorder = $this->db->sql_escape_string(AppRequest::get('sorder'));
		else
			$sorder = (($_SESSION['NOTES_SORT_ORDER'] != '') ? ($_SESSION['NOTES_SORT_ORDER']) : ($this->default_sort_order));
		\App\Log::trace('Exiting getSortOrder() method ...');
		return $sorder;
	}

	/**     Function used to get the order by value for Documents listview
	 *       @return string  $order_by  - first check the $_REQUEST['order_by'] if request value is empty then check in the $_SESSION['NOTES_ORDER_BY'] if this session value is empty then default order by will be returned.
	 */
	public function getOrderBy()
	{

		\App\Log::trace('Entering getOrderBy() method ...');

		$use_default_order_by = '';
		if (AppConfig::performance('LISTVIEW_DEFAULT_SORTING', true)) {
			$use_default_order_by = $this->default_order_by;
		}

		if (AppRequest::has('order_by'))
			$order_by = $this->db->sql_escape_string(AppRequest::get('order_by'));
		else
			$order_by = (($_SESSION['NOTES_ORDER_BY'] != '') ? ($_SESSION['NOTES_ORDER_BY']) : ($use_default_order_by));
		\App\Log::trace('Exiting getOrderBy method ...');
		return $order_by;
	}

	/**
	 * Function used to get the sort order for Documents listview
	 * @return String $sorder - sort order for a given folder.
	 */
	public function getSortOrderForFolder($folderId)
	{
		if (AppRequest::has('sorder') && AppRequest::get('folderid') == $folderId) {
			$sorder = $this->db->sql_escape_string(AppRequest::get('sorder'));
		} elseif (is_array($_SESSION['NOTES_FOLDER_SORT_ORDER']) &&
			!empty($_SESSION['NOTES_FOLDER_SORT_ORDER'][$folderId])) {
			$sorder = $_SESSION['NOTES_FOLDER_SORT_ORDER'][$folderId];
		} else {
			$sorder = $this->default_sort_order;
		}
		return $sorder;
	}

	/**
	 * Function used to get the order by value for Documents listview
	 * @return String order by column for a given folder.
	 */
	public function getOrderByForFolder($folderId)
	{
		$use_default_order_by = '';
		if (AppConfig::performance('LISTVIEW_DEFAULT_SORTING', true)) {
			$use_default_order_by = $this->default_order_by;
		}
		if (AppRequest::has('order_by') && AppRequest::get('folderid') == $folderId) {
			$order_by = $this->db->sql_escape_string(AppRequest::get('order_by'));
		} elseif (is_array($_SESSION['NOTES_FOLDER_ORDER_BY']) &&
			!empty($_SESSION['NOTES_FOLDER_ORDER_BY'][$folderId])) {
			$order_by = $_SESSION['NOTES_FOLDER_ORDER_BY'][$folderId];
		} else {
			$order_by = ($use_default_order_by);
		}
		return $order_by;
	}

	/** Function to export the notes in CSV Format
	 * @param reference variable - where condition is passed when the query is executed
	 * Returns Export Documents Query.
	 */
	public function create_export_query($where)
	{

		$current_user = vglobal('current_user');
		\App\Log::trace("Entering create_export_query(" . $where . ") method ...");

		include("include/utils/ExportUtils.php");
		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery("Documents", "detail_view");
		$fields_list = getFieldsListFromQuery($sql);

		$userNameSql = \vtlib\Deprecated::getSqlForNameInDisplayFormat(array('first_name' =>
				'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'), 'Users');
		$query = "SELECT $fields_list, case when (vtiger_users.user_name not like '') then $userNameSql else vtiger_groups.groupname end as user_name" .
			" FROM vtiger_notes
				inner join vtiger_crmentity
					on vtiger_crmentity.crmid=vtiger_notes.notesid
				LEFT JOIN `vtiger_trees_templates_data` on vtiger_notes.folderid=`vtiger_trees_templates_data`.tree
				LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid=vtiger_users.id " .
			" LEFT JOIN vtiger_groups ON vtiger_crmentity.smownerid=vtiger_groups.groupid "
		;
		$query .= getNonAdminAccessControlQuery('Documents', $current_user);
		$where_auto = " vtiger_crmentity.deleted=0";
		if ($where != "")
			$query .= "  WHERE ($where) && " . $where_auto;
		else
			$query .= '  WHERE %s';

		$query = sprintf($query, $where_auto);
		\App\Log::trace("Exiting create_export_query method ...");
		return $query;
	}

	public function insertintonotesrel($relid, $id)
	{
		$adb = PearDatabase::getInstance();
		$dbQuery = "insert into vtiger_senotesrel values ( ?, ? )";
		$dbresult = $adb->pquery($dbQuery, array($relid, $id));
	}
	/* function save_related_module($module, $crmid, $with_module, $with_crmid){
	  } */


	/*
	 * Function to get the primary query part of a report
	 * @param - $module Primary module name
	 * returns the query string formed on fetching the related data for report for primary module
	 */

	public function generateReportsQuery($module, $queryplanner)
	{
		$moduletable = $this->table_name;
		$moduleindex = $this->tab_name_index[$moduletable];
		$query = "from $moduletable
			inner join vtiger_crmentity on vtiger_crmentity.crmid=$moduletable.$moduleindex";
		if ($queryplanner->requireTable("`vtiger_trees_templates_data`")) {
			$query .= " inner join `vtiger_trees_templates_data` on `vtiger_trees_templates_data`.tree=$moduletable.folderid";
		}
		if ($queryplanner->requireTable("vtiger_groups" . $module)) {
			$query .= " left join vtiger_groups as vtiger_groups" . $module . " on vtiger_groups" . $module . ".groupid = vtiger_crmentity.smownerid";
		}
		if ($queryplanner->requireTable("vtiger_users" . $module)) {
			$query .= " left join vtiger_users as vtiger_users" . $module . " on vtiger_users" . $module . ".id = vtiger_crmentity.smownerid";
		}
		$query .= " left join vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid";
		$query .= " left join vtiger_notescf on vtiger_notes.notesid = vtiger_notescf.notesid";
		$query .= " left join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid";
		if ($queryplanner->requireTable("vtiger_lastModifiedBy" . $module)) {
			$query .= " left join vtiger_users as vtiger_lastModifiedBy" . $module . " on vtiger_lastModifiedBy" . $module . ".id = vtiger_crmentity.modifiedby ";
		}
		return $query;
	}
	/*
	 * Function to get the secondary query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */

	public function generateReportsSecQuery($module, $secmodule, $queryplanner)
	{

		$matrix = $queryplanner->newDependencyMatrix();

		$matrix->setDependency("vtiger_crmentityDocuments", array("vtiger_groupsDocuments", "vtiger_usersDocuments", "vtiger_lastModifiedByDocuments"));
		$matrix->setDependency("vtiger_notes", array("vtiger_crmentityDocuments", "`vtiger_trees_templates_data`"));

		if (!$queryplanner->requireTable('vtiger_notes', $matrix)) {
			return '';
		}
		$query = $this->getRelationQuery($module, $secmodule, "vtiger_notes", "notesid", $queryplanner);
		$query .= " left join vtiger_notescf on vtiger_notes.notesid = vtiger_notescf.notesid";
		if ($queryplanner->requireTable("vtiger_crmentityDocuments", $matrix)) {
			$query .= " left join vtiger_crmentity as vtiger_crmentityDocuments on vtiger_crmentityDocuments.crmid=vtiger_notes.notesid and vtiger_crmentityDocuments.deleted=0";
		}
		if ($queryplanner->requireTable("`vtiger_trees_templates_data`")) {
			$query .= " left join `vtiger_trees_templates_data` on `vtiger_trees_templates_data`.tree=vtiger_notes.folderid";
		}
		if ($queryplanner->requireTable("vtiger_groupsDocuments")) {
			$query .= " left join vtiger_groups as vtiger_groupsDocuments on vtiger_groupsDocuments.groupid = vtiger_crmentityDocuments.smownerid";
		}
		if ($queryplanner->requireTable("vtiger_usersDocuments")) {
			$query .= " left join vtiger_users as vtiger_usersDocuments on vtiger_usersDocuments.id = vtiger_crmentityDocuments.smownerid";
		}
		if ($queryplanner->requireTable("vtiger_lastModifiedByDocuments")) {
			$query .= " left join vtiger_users as vtiger_lastModifiedByDocuments on vtiger_lastModifiedByDocuments.id = vtiger_crmentityDocuments.modifiedby ";
		}
		if ($queryplanner->requireTable("vtiger_createdbyDocuments")) {
			$query .= " left join vtiger_users as vtiger_createdbyDocuments on vtiger_createdbyDocuments.id = vtiger_crmentityDocuments.smcreatorid ";
		}
		return $query;
	}
	/*
	 * Function to get the relation tables for related modules
	 * @param - $secmodule secondary module name
	 * returns the array with table names and fieldnames storing relations between module and this module
	 */

	public function setRelationTables($secmodule = false)
	{
		$relTables = [];
		if ($secmodule === false) {
			return $relTables;
		}
		return $relTables[$secmodule];
	}

	// Function to unlink an entity with given Id from another entity
	public function unlinkRelationship($id, $returnModule, $returnId, $relatedName = false)
	{
		if (empty($returnModule) || empty($returnId))
			return;
		if ($returnModule == 'Accounts') {
			$subQuery = (new \App\Db\Query())->select(['contactid'])->from('vtiger_contactdetails')->where(['parentid' => $returnId]);
			App\Db::getInstance()->createCommand()->delete('vtiger_senotesrel', ['and', ['notesid' => $id], ['or', ['crmid' => $returnId], ['crmid' => $subQuery]]])->execute();
		} else {
			App\Db::getInstance()->createCommand()->delete('vtiger_senotesrel', ['notesid' => $id, 'crmid' => $returnId])->execute();
			parent::deleteRelatedFromDB($relatedName, $id, $returnModule, $returnId);
		}
	}

// Function to get fieldname for uitype 27 assuming that documents have only one file type field

	public function getFileTypeFieldName()
	{
		$adb = PearDatabase::getInstance();

		$query = 'SELECT fieldname from vtiger_field where tabid = ? and uitype = ?';
		$tabid = \App\Module::getModuleId('Documents');
		$filetype_uitype = 27;
		$res = $adb->pquery($query, array($tabid, $filetype_uitype));
		$fieldname = null;
		if (isset($res)) {
			$rowCount = $adb->num_rows($res);
			if ($rowCount > 0) {
				$fieldname = $adb->query_result($res, 0, 'fieldname');
			}
		}
		return $fieldname;
	}

//	public function to get fieldname for uitype 28 assuming that doc has only one file upload type

	public function getFile_FieldName()
	{
		$adb = PearDatabase::getInstance();

		$query = 'SELECT fieldname from vtiger_field where tabid = ? and uitype = ?';
		$tabid = \App\Module::getModuleId('Documents');
		$filename_uitype = 28;
		$res = $adb->pquery($query, array($tabid, $filename_uitype));
		$fieldname = null;
		if (isset($res)) {
			$rowCount = $adb->num_rows($res);
			if ($rowCount > 0) {
				$fieldname = $adb->query_result($res, 0, 'fieldname');
			}
		}
		return $fieldname;
	}

	/**
	 * Check the existence of folder by folderid
	 */
	public function isFolderPresent($folderid)
	{
		$adb = PearDatabase::getInstance();
		$result = $adb->pquery("SELECT tree FROM `vtiger_trees_templates_data` WHERE tree = ?", array($folderid));
		if (!empty($result) && $adb->num_rows($result) > 0)
			return true;
		return false;
	}

	/**
	 * Get Folder Default
	 */
	public function getFolderDefault()
	{
		$adb = PearDatabase::getInstance();
		$result = $adb->pquery("SELECT `tree`,`name` FROM
				`vtiger_trees_templates_data` 
			INNER JOIN `vtiger_field` 
				ON `vtiger_trees_templates_data`.`templateid` = `vtiger_field`.`fieldparams` 
			WHERE `vtiger_field`.`columnname` = ? 
				AND `vtiger_field`.`tablename` = ?
				AND `vtiger_trees_templates_data`.`name` = ?;", array('folderid', 'vtiger_notes', 'Default'));
		return $adb->query_result($result, 0, 'tree');
	}

	/**
	 * Customizing the restore procedure.
	 */
	public function restore($modulename, $id)
	{
		parent::restore($modulename, $id);

		$adb = PearDatabase::getInstance();
		$fresult = $adb->pquery("SELECT folderid FROM vtiger_notes WHERE notesid = ?", array($id));
		if (!empty($fresult) && $adb->num_rows($fresult)) {
			$folderid = $adb->query_result($fresult, 0, 'folderid');
			if (!$this->isFolderPresent($folderid)) {
				// Re-link to default folder
				$adb->pquery("UPDATE vtiger_notes set folderid = ? WHERE notesid = ?", array(self::getFolderDefault()));
			}
		}
	}

	/**
	 * Function to check the module active and user action permissions before showing as link in other modules
	 * like in more actions of detail view.
	 */
	static function isLinkPermitted($linkData)
	{
		$moduleName = 'Documents';
		if (\App\Module::isModuleActive($moduleName) && isPermitted($moduleName, 'EditView') == 'yes') {
			return true;
		}
		return false;
	}
}
