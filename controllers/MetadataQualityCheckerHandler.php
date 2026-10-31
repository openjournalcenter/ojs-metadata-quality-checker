<?php

namespace APP\plugins\generic\metadataQualityChecker\controllers;

use APP\facades\Repo;
use APP\handler\Handler;
use APP\plugins\generic\metadataQualityChecker\classes\MetadataQualityReport;
use APP\plugins\generic\metadataQualityChecker\MetadataQualityCheckerPlugin;
use APP\template\TemplateManager;
use PKP\core\JSONMessage;
use PKP\security\Role;

class MetadataQualityCheckerHandler extends Handler
{
	public function __construct(public MetadataQualityCheckerPlugin $plugin)
	{
		parent::__construct();
		$this->addRoleAssignment(
			[Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_AUTHOR],
			['fetchReport']
		);
	}

	public function authorize($request, &$args, $roleAssignments)
	{
		$submissionId = (int) $request->getUserVar('submissionId');
		if (!$submissionId) {
			return false;
		}

		$submission = Repo::submission()->get($submissionId);
		if (!$submission) {
			return false;
		}

		$this->markRoleAssignmentsChecked();
		return parent::authorize($request, $args, $roleAssignments);
	}

	public function fetchReport($args, $request)
	{
		$submissionId = (int) $request->getUserVar('submissionId');
		$submission = Repo::submission()->get($submissionId);
		$publicationId = (int) $request->getUserVar('publicationId');
		$publication = $publicationId ? Repo::publication()->get($publicationId) : $submission->getLatestPublication();

		$report = MetadataQualityReport::evaluate($submission, $publication);
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('mqcReport', $report);

		$content = $templateMgr->fetch($this->plugin->getTemplateResource('workflowPublicationTabContent.tpl'));
		return new JSONMessage(true, $content);
	}
}
