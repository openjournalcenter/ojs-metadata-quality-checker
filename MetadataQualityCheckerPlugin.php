<?php

namespace APP\plugins\generic\metadataQualityChecker;

use APP\core\Application;
use APP\plugins\generic\metadataQualityChecker\classes\MetadataQualityReport;
use APP\plugins\generic\metadataQualityChecker\controllers\MetadataQualityCheckerHandler;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class MetadataQualityCheckerPlugin extends GenericPlugin
{
	public function register($category, $path, $mainContextId = null)
	{
		$success = parent::register($category, $path, $mainContextId);

		if ($success && $this->getEnabled($mainContextId)) {
			$this->addLocaleData();
			Hook::add('LoadHandler', [$this, 'setPageHandler']);
			Hook::add('Template::Workflow', [$this, 'addWorkflowQualityTab']);
		}

		return $success;
	}

	public function getDisplayName()
	{
		return __('plugins.generic.metadataQualityChecker.displayName');
	}

	public function getDescription()
	{
		return __('plugins.generic.metadataQualityChecker.description');
	}

	public function setPageHandler(string $hookName, array $params): bool
	{
		$page = &$params[0];
		$handler = &$params[3];

		if ($this->getEnabled() && $page === 'metadataQualityChecker') {
			$handler = new MetadataQualityCheckerHandler($this);
			return true;
		}

		return false;
	}

	public function addWorkflowQualityTab(string $hookName, array $params): bool
	{
		$templateMgr = &$params[1];
		$output = &$params[2];

		$submission = $templateMgr->getTemplateVars('submission');
		if (!$submission) {
			return false;
		}

		$publication = $submission->getLatestPublication();
		if (!$publication) {
			return false;
		}

		$request = Application::get()->getRequest();
		$report = MetadataQualityReport::evaluate($submission, $publication);
		$ajaxUrl = $request->getDispatcher()->url($request, Application::ROUTE_PAGE, null, 'metadataQualityChecker', 'fetchReport');

		$templateMgr->assign([
			'mqcReport' => $report,
			'mqcSubmissionId' => $submission->getId(),
			'mqcPublicationId' => $publication->getId(),
			'mqcAjaxUrl' => $ajaxUrl,
			'mqcContentTemplate' => $this->getTemplateResource('workflowPublicationTabContent.tpl'),
		]);

		$output .= $templateMgr->fetch($this->getTemplateResource('workflowPublicationTab.tpl'));

		return false;
	}
}
