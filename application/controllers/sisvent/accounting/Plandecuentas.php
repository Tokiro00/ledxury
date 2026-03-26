<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Plan de Cuentas (Chart of Accounts)
 * Unified hierarchical view of the PUC structure:
 * Class > Group > Account > Subaccount
 */
class Plandecuentas extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->backend_lib->controlModule('plan_cuentas');
		$this->load->model("accountclass_model");
		$this->load->model("accountgroup_model");
		$this->load->model("account_model");
		$this->load->model("subaccount_model");
	}

	public function index()
	{
		// Get all classes
		$classes = $this->accountclass_model->getClasses();

		// Build the full tree: Class > Group > Account > Subaccount
		$tree = array();
		foreach ($classes as $cls) {
			$classNode = new stdClass();
			$classNode->id = $cls->id;
			$classNode->classID = $cls->classID;
			$classNode->className = $cls->className;
			$classNode->classDescription = isset($cls->classDescription) ? $cls->classDescription : '';
			$classNode->store_name = isset($cls->store_name) ? $cls->store_name : '';
			$classNode->groups = array();

			// Get groups for this class
			$groups = $this->accountgroup_model->getGroupsByClass($cls->id);
			foreach ($groups as $grp) {
				$groupNode = new stdClass();
				$groupNode->id = $grp->id;
				$groupNode->groupID = $grp->groupID;
				$groupNode->groupName = $grp->groupName;
				$groupNode->accounts = array();

				// Get accounts for this group
				$accounts = $this->account_model->getAccountsByGroup($grp->id);
				foreach ($accounts as $acc) {
					$accountNode = new stdClass();
					$accountNode->id = $acc->id;
					$accountNode->accountID = $acc->accountID;
					$accountNode->accountName = $acc->accountName;
					$accountNode->subaccounts = array();

					// Get subaccounts for this account
					$subaccounts = $this->subaccount_model->getSubaccountsByAccount($acc->id);
					$accountNode->subaccounts = $subaccounts;

					$groupNode->accounts[] = $accountNode;
				}

				$classNode->groups[] = $groupNode;
			}

			$tree[] = $classNode;
		}

		$data = array(
			'tree' => $tree,
		);
		$this->load->view("sisvent/accounting/plandecuentas/index", $data);
	}
}
