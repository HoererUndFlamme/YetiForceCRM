<?php
/* * *******************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version 1.1.2
 * ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an  "AS IS"  basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for
 * the specific language governing rights and limitations under the License.
 * The Original Code is:  SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 * ****************************************************************************** */
/* * *******************************************************************************
 * $Header: /advent/projects/wesat/vtiger_crm/sugarcrm/modules/Contacts/Contacts.php,v 1.70 2005/04/27 11:21:49 rank Exp $
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 * ******************************************************************************
 * Contributor(s): YetiForce.com
 */

// Contact is used to store customer information.
class Contacts extends CRMEntity
{

	public $table_name = "vtiger_contactdetails";
	public $table_index = 'contactid';
	public $tab_name = Array('vtiger_crmentity', 'vtiger_contactdetails', 'vtiger_contactaddress', 'vtiger_contactsubdetails', 'vtiger_contactscf', 'vtiger_customerdetails', 'vtiger_entity_stats');
	public $tab_name_index = Array('vtiger_crmentity' => 'crmid', 'vtiger_contactdetails' => 'contactid', 'vtiger_contactaddress' => 'contactaddressid', 'vtiger_contactsubdetails' => 'contactsubscriptionid', 'vtiger_contactscf' => 'contactid', 'vtiger_customerdetails' => 'customerid', 'vtiger_entity_stats' => 'crmid');

	/**
	 * Mandatory table for supporting custom fields.
	 */
	public $customFieldTable = Array('vtiger_contactscf', 'contactid');
	public $column_fields = Array();
	public $sortby_fields = Array('lastname', 'firstname', 'title', 'email', 'phone', 'smownerid', 'accountname');
	public $list_link_field = 'lastname';
	// This is the list of vtiger_fields that are in the lists.
	public $list_fields = Array(
		'First Name' => Array('contactdetails' => 'firstname'),
		'Last Name' => Array('contactdetails' => 'lastname'),
		'Title' => Array('contactdetails' => 'title'),
		'Member Of' => Array('account' => 'parentid'),
		'Email' => Array('contactdetails' => 'email'),
		'Office Phone' => Array('contactdetails' => 'phone'),
		'Assigned To' => Array('crmentity' => 'smownerid')
	);
	public $range_fields = Array(
		'first_name',
		'last_name',
		'primary_address_city',
		'account_name',
		'parent_id',
		'id',
		'email1',
		'salutation',
		'title',
		'phone_mobile',
		'reports_to_name',
		'primary_address_street',
		'primary_address_city',
		'primary_address_state',
		'primary_address_postalcode',
		'primary_address_country',
		'alt_address_city',
		'alt_address_street',
		'alt_address_city',
		'alt_address_state',
		'alt_address_postalcode',
		'alt_address_country',
		'office_phone',
		'home_phone',
		'other_phone',
		'fax',
		'department',
		'birthdate',
		'assistant_name',
		'assistant_phone');
	public $list_fields_name = Array(
		'First Name' => 'firstname',
		'Last Name' => 'lastname',
		'Title' => 'title',
		'Member Of' => 'parent_id',
		'Email' => 'email',
		'Office Phone' => 'phone',
		'Assigned To' => 'assigned_user_id'
	);
	public $search_fields = Array(
		'First Name' => Array('contactdetails' => 'firstname'),
		'Last Name' => Array('contactdetails' => 'lastname'),
		'Title' => Array('contactdetails' => 'title'),
		'Member Of' => Array('contactdetails' => 'parent_id'),
		'Assigned To' => Array('crmentity' => 'smownerid'),
	);
	public $search_fields_name = Array(
		'First Name' => 'firstname',
		'Last Name' => 'lastname',
		'Title' => 'title',
		'Member Of' => 'parent_id',
		'Assigned To' => 'assigned_user_id'
	);

	/**
	 * @var string[] List of fields in the RelationListView
	 */
	public $relationFields = ['firstname', 'lastname', 'jobtitle', 'email', 'phone', 'assigned_user_id'];
	// This is the list of vtiger_fields that are required
	public $required_fields = array("lastname" => 1);
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	public $mandatory_fields = Array('assigned_user_id', 'lastname', 'createdtime', 'modifiedtime');
	//Default Fields for Email Templates -- Pavani
	public $emailTemplate_defaultFields = array('firstname', 'lastname', 'salutation', 'title', 'email', 'department', 'phone', 'mobile', 'support_start_date', 'support_end_date');
	//Added these variables which are used as default order by and sortorder in ListView
	public $default_order_by = '';
	public $default_sort_order = 'ASC';
	// For Alphabetical search
	public $def_basicsearch_col = 'lastname';

	// Mike Crowe Mod --------------------------------------------------------Default ordering for us
	/** Function to get the number of Contacts assigned to a particular User.
	 *  @param varchar $user name - Assigned to User
	 *  Returns the count of contacts assigned to user.
	 */
	public function getCount($user_name)
	{

		\App\Log::trace("Entering getCount(" . $user_name . ") method ...");
		$query = "select count(*) from vtiger_contactdetails  inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_contactdetails.contactid inner join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid where user_name=? and vtiger_crmentity.deleted=0";
		$result = $this->db->pquery($query, array($user_name), true, "Error retrieving contacts count");
		$rows_found = $this->db->getRowCount($result);
		$row = $this->db->fetchByAssoc($result, 0);


		\App\Log::trace("Exiting getCount method ...");
		return $row["count(*)"];
	}

	/** Function to process list query for a given query
	 *  @param $query
	 *  Returns the results of query in array format
	 */
	public function process_list_query1($query)
	{

		\App\Log::trace("Entering process_list_query1(" . $query . ") method ...");

		$result = & $this->db->query($query, true, "Error retrieving $this->object_name list: ");
		$list = Array();
		$rows_found = $this->db->getRowCount($result);
		if ($rows_found != 0) {
			$contact = Array();
			for ($index = 0, $row = $this->db->fetchByAssoc($result, $index); $row && $index < $rows_found; $index++, $row = $this->db->fetchByAssoc($result, $index)) {
				foreach ($this->range_fields as $columnName) {
					if (isset($row[$columnName])) {

						$contact[$columnName] = $row[$columnName];
					} else {
						$contact[$columnName] = "";
					}
				}

				$list[] = $contact;
			}
		}

		$response = Array();
		$response['list'] = $list;
		$response['row_count'] = $rows_found;
		$response['next_offset'] = $next_offset;
		$response['previous_offset'] = $previous_offset;


		\App\Log::trace("Exiting process_list_query1 method ...");
		return $response;
	}

	/** Function to export the contact records in CSV Format
	 * @param reference variable - where condition is passed when the query is executed
	 * Returns Export Contacts Query.
	 */
	public function create_export_query($where)
	{

		$current_user = vglobal('current_user');
		\App\Log::trace("Entering create_export_query(" . $where . ") method ...");

		include("include/utils/ExportUtils.php");

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery("Contacts", "detail_view");
		$fields_list = getFieldsListFromQuery($sql);

		$query = "SELECT vtiger_contactdetails.salutation as 'Salutation',$fields_list,case when (vtiger_users.user_name not like '') then vtiger_users.user_name else vtiger_groups.groupname end as user_name
                                FROM vtiger_contactdetails
                                inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_contactdetails.contactid
                                LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid=vtiger_users.id and vtiger_users.status='Active'
                                LEFT JOIN vtiger_account on vtiger_contactdetails.parentid=vtiger_account.accountid
				left join vtiger_contactaddress on vtiger_contactaddress.contactaddressid=vtiger_contactdetails.contactid
				left join vtiger_contactsubdetails on vtiger_contactsubdetails.contactsubscriptionid=vtiger_contactdetails.contactid
			        left join vtiger_contactscf on vtiger_contactscf.contactid=vtiger_contactdetails.contactid
			        left join vtiger_customerdetails on vtiger_customerdetails.customerid=vtiger_contactdetails.contactid
	                        LEFT JOIN vtiger_groups
                        	        ON vtiger_groups.groupid = vtiger_crmentity.smownerid
				LEFT JOIN vtiger_contactdetails vtiger_contactdetails2
					ON vtiger_contactdetails2.contactid = vtiger_contactdetails.reportsto";
		$query .= getNonAdminAccessControlQuery('Contacts', $current_user);
		$where_auto = " vtiger_crmentity.deleted = 0 ";

		if ($where != '')
			$query .= sprintf(' where (%s) && %s', $where, $where_auto);
		else
			$query .= sprintf(' where %s', $where_auto);

		\App\Log::trace("Export Query Constructed Successfully");
		\App\Log::trace("Exiting create_export_query method ...");
		return $query;
	}

	/** Function to handle module specific operations when saving a entity
	 */
	public function save_module($module)
	{
		$this->insertIntoAttachment($this->id, $module);
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
		//This is to added to store the existing attachment id of the contact where we should delete this when we give new image
		$old_attachmentid = $adb->query_result($adb->pquery("select vtiger_crmentity.crmid from vtiger_seattachmentsrel inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_seattachmentsrel.attachmentsid where  vtiger_seattachmentsrel.crmid=?", array($id)), 0, 'crmid');
		if ($_FILES) {
			foreach ($_FILES as $fileindex => $files) {
				$fileInstance = \App\Fields\File::loadFromRequest($files);
				if ($fileInstance->validate('image')) {
					$files['original_name'] = AppRequest::get($fileindex . '_hidden');
					$file_saved = $this->uploadAndSaveFile($id, $module, $files);
				}
			}
		}

		$imageNameSql = 'SELECT name FROM vtiger_seattachmentsrel INNER JOIN vtiger_attachments ON
								vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid LEFT JOIN vtiger_contactdetails ON
								vtiger_contactdetails.contactid = vtiger_seattachmentsrel.crmid WHERE vtiger_seattachmentsrel.crmid = ?';
		$imageNameResult = $adb->pquery($imageNameSql, array($id));
		$imageName = decode_html($adb->query_result($imageNameResult, 0, "name"));

		//Inserting image information of record into base table
		$adb->pquery('UPDATE vtiger_contactdetails SET imagename = ? WHERE contactid = ?', array($imageName, $id));

		//This is to handle the delete image for contacts
		if ($module == 'Contacts' && $file_saved) {
			if ($old_attachmentid != '') {
				$setype = $adb->query_result($adb->pquery("select setype from vtiger_crmentity where crmid=?", array($old_attachmentid)), 0, 'setype');
				if ($setype == 'Contacts Image') {
					$del_res1 = $adb->pquery("delete from vtiger_attachments where attachmentsid=?", array($old_attachmentid));
					$del_res2 = $adb->pquery("delete from vtiger_seattachmentsrel where attachmentsid=?", array($old_attachmentid));
				}
			}
		}

		\App\Log::trace("Exiting from insertIntoAttachment($id,$module) method.");
	}

	/**
	 * Move the related records of the specified list of id's to the given record.
	 * @param String This module name
	 * @param Array List of Entity Id's from which related records need to be transfered
	 * @param Integer Id of the the Record to which the related records are to be moved
	 */
	public function transferRelatedRecords($module, $transferEntityIds, $entityId)
	{
		$adb = PearDatabase::getInstance();

		\App\Log::trace("Entering function transferRelatedRecords ($module, $transferEntityIds, $entityId)");

		$rel_table_arr = Array("Products" => "vtiger_seproductsrel", "Documents" => "vtiger_senotesrel",
			"Attachments" => "vtiger_seattachmentsrel", "Campaigns" => "vtiger_campaign_records",
			'ServiceContracts' => 'vtiger_servicecontracts', 'Project' => 'vtiger_project');

		$tbl_field_arr = Array("vtiger_seproductsrel" => "productid", "vtiger_senotesrel" => "notesid",
			"vtiger_seattachmentsrel" => "attachmentsid", "vtiger_campaign_records" => "campaignid",
			'vtiger_servicecontracts' => 'servicecontractsid', 'vtiger_project' => 'projectid',
			'vtiger_payments' => 'paymentsid');

		$entity_tbl_field_arr = Array("vtiger_seproductsrel" => "crmid", "vtiger_senotesrel" => "crmid",
			"vtiger_seattachmentsrel" => "crmid", "vtiger_campaign_records" => "crmid",
			'vtiger_servicecontracts' => 'sc_related_to', 'vtiger_project' => 'linktoaccountscontacts',
			'vtiger_payments' => 'relatedcontact');

		foreach ($transferEntityIds as $transferId) {
			foreach ($rel_table_arr as $rel_module => $rel_table) {
				$id_field = $tbl_field_arr[$rel_table];
				$entity_id_field = $entity_tbl_field_arr[$rel_table];
				// IN clause to avoid duplicate entries
				$sel_result = $adb->pquery("select $id_field from $rel_table where $entity_id_field=? " .
					" and $id_field not in (select $id_field from $rel_table where $entity_id_field=?)", array($transferId, $entityId));
				$res_cnt = $adb->num_rows($sel_result);
				if ($res_cnt > 0) {
					for ($i = 0; $i < $res_cnt; $i++) {
						$id_field_value = $adb->query_result($sel_result, $i, $id_field);
						$adb->pquery("update $rel_table set $entity_id_field=? where $entity_id_field=? and $id_field=?", array($entityId, $transferId, $id_field_value));
					}
				}
			}
		}
		parent::transferRelatedRecords($module, $transferEntityIds, $entityId);
		\App\Log::trace("Exiting transferRelatedRecords...");
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
		$matrix->setDependency('vtiger_crmentityContacts', array('vtiger_groupsContacts', 'vtiger_usersContacts', 'vtiger_lastModifiedByContacts'));
		$matrix->setDependency('vtiger_contactdetails', array('vtiger_crmentityContacts', 'vtiger_contactaddress',
			'vtiger_customerdetails', 'vtiger_contactsubdetails', 'vtiger_contactscf'));

		if (!$queryplanner->requireTable('vtiger_contactdetails', $matrix)) {
			return '';
		}


		$query = $this->getRelationQuery($module, $secmodule, "vtiger_contactdetails", "contactid", $queryplanner);

		if ($queryplanner->requireTable("vtiger_crmentityContacts", $matrix)) {
			$query .= " left join vtiger_crmentity as vtiger_crmentityContacts on vtiger_crmentityContacts.crmid = vtiger_contactdetails.contactid  and vtiger_crmentityContacts.deleted=0";
		}
		if ($queryplanner->requireTable("vtiger_contactdetailsContacts")) {
			$query .= " left join vtiger_contactdetails as vtiger_contactdetailsContacts on vtiger_contactdetailsContacts.contactid = vtiger_contactdetails.reportsto";
		}
		if ($queryplanner->requireTable("vtiger_contactaddress")) {
			$query .= " left join vtiger_contactaddress on vtiger_contactdetails.contactid = vtiger_contactaddress.contactaddressid";
		}
		if ($queryplanner->requireTable("vtiger_customerdetails")) {
			$query .= " left join vtiger_customerdetails on vtiger_customerdetails.customerid = vtiger_contactdetails.contactid";
		}
		if ($queryplanner->requireTable("vtiger_contactsubdetails")) {
			$query .= " left join vtiger_contactsubdetails on vtiger_contactdetails.contactid = vtiger_contactsubdetails.contactsubscriptionid";
		}
		if ($queryplanner->requireTable("vtiger_accountContacts")) {
			$query .= " left join vtiger_account as vtiger_accountContacts on vtiger_accountContacts.accountid = vtiger_contactdetails.parentid";
		}
		if ($queryplanner->requireTable("vtiger_contactscf")) {
			$query .= " left join vtiger_contactscf on vtiger_contactdetails.contactid = vtiger_contactscf.contactid";
		}
		if ($queryplanner->requireTable("vtiger_email_trackContacts")) {
			$query .= " LEFT JOIN vtiger_email_track AS vtiger_email_trackContacts ON vtiger_email_trackContacts.crmid = vtiger_contactdetails.contactid";
		}
		if ($queryplanner->requireTable("vtiger_groupsContacts")) {
			$query .= " left join vtiger_groups as vtiger_groupsContacts on vtiger_groupsContacts.groupid = vtiger_crmentityContacts.smownerid";
		}
		if ($queryplanner->requireTable("vtiger_usersContacts")) {
			$query .= " left join vtiger_users as vtiger_usersContacts on vtiger_usersContacts.id = vtiger_crmentityContacts.smownerid";
		}
		if ($queryplanner->requireTable("vtiger_lastModifiedByContacts")) {
			$query .= " left join vtiger_users as vtiger_lastModifiedByContacts on vtiger_lastModifiedByContacts.id = vtiger_crmentityContacts.modifiedby ";
		}
		if ($queryplanner->requireTable("vtiger_createdbyContacts")) {
			$query .= " left join vtiger_users as vtiger_createdbyContacts on vtiger_createdbyContacts.id = vtiger_crmentityContacts.smcreatorid ";
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
		$relTables = [
			'Products' => ['vtiger_seproductsrel' => ['crmid', 'productid'], 'vtiger_contactdetails' => 'contactid'],
			'Campaigns' => ['vtiger_campaign_records' => ['crmid', 'campaignid'], 'vtiger_contactdetails' => 'contactid'],
			'Documents' => ['vtiger_senotesrel' => ['crmid', 'notesid'], 'vtiger_contactdetails' => 'contactid']
		];
		if ($secmodule === false) {
			return $relTables;
		}
		return $relTables[$secmodule];
	}

	// Function to unlink all the dependent entities of the given Entity by Id
	public function mark_deleted($recordId)
	{

		$db = \App\Db::getInstance();
		$db->createCommand()->delete('vtiger_portalinfo', ['id' => $recordId])->execute();
		$db->createCommand()->update('vtiger_customerdetails', [
			'portal' => 0,
			'support_start_date' => null,
			'support_end_date' => null
			], ['customerid' => $recordId])->execute();
		parent::mark_deleted($recordId);
	}

	// Function to unlink an entity with given Id from another entity
	public function unlinkRelationship($id, $return_module, $return_id, $relatedName = false)
	{

		if (empty($return_module) || empty($return_id))
			return;

		if ($return_module == 'Accounts') {
			$sql = 'UPDATE vtiger_contactdetails SET parentid = ? WHERE contactid = ?';
			$this->db->pquery($sql, array(null, $id));
		} elseif ($return_module == 'Campaigns') {
			$this->db->delete('vtiger_campaign_records', 'crmid=? && campaignid=?', [$id, $return_id]);
		} elseif ($return_module == 'Products') {
			$sql = 'DELETE FROM vtiger_seproductsrel WHERE crmid=? && productid=?';
			$this->db->pquery($sql, array($id, $return_id));
		} elseif ($return_module == 'Vendors') {
			$sql = 'DELETE FROM vtiger_vendorcontactrel WHERE vendorid=? && contactid=?';
			$this->db->pquery($sql, array($return_id, $id));
		} else {
			parent::unlinkRelationship($id, $return_module, $return_id, $relatedName);
		}
	}

	public function save_related_module($module, $crmid, $withModule, $withCrmid, $relatedName = false)
	{
		$adb = PearDatabase::getInstance();
		$currentUser = Users_Record_Model::getCurrentUserModel();

		if (!is_array($withCrmid))
			$withCrmid = [$withCrmid];
		if (!in_array($withModule, ['Products', 'Campaigns', 'Vendors'])) {
			parent::save_related_module($module, $crmid, $withModule, $withCrmid, $relatedName);
		} else {
			foreach ($withCrmid as $with_crmid) {
				if ($withModule == 'Products') {
					$adb->insert('vtiger_seproductsrel', [
						'crmid' => $crmid,
						'productid' => $with_crmid,
						'setype' => 'Contacts',
						'rel_created_user' => $currentUser->getId(),
						'rel_created_time' => date('Y-m-d H:i:s')
					]);
				} else if ($withModule == 'Campaigns') {
					$adb->insert('vtiger_campaign_records', [
						'campaignid' => $with_crmid,
						'crmid' => $crmid,
						'campaignrelstatusid' => 0
					]);
				} else if ($withModule == 'Vendors') {
					$adb->insert('vtiger_vendorcontactrel', [
						'vendorid' => $with_crmid,
						'contactid' => $crmid
					]);
				}
			}
		}
	}
}
