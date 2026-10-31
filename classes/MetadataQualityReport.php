<?php

namespace APP\plugins\generic\metadataQualityChecker\classes;

use APP\facades\Repo;
use APP\submission\Submission;
use APP\publication\Publication;

class MetadataQualityReport
{
	public static function evaluate(Submission $submission, Publication $publication): array
	{
		$checks = [];
		$earnedScore = 0;
		$maxScore = 0;

		$weightTitle = 15;
		$maxScore += $weightTitle;
		$titleCheck = self::checkTitle($publication);
		$earnedScore += round(($titleCheck['score'] / 100) * $weightTitle);
		$checks[] = $titleCheck;

		$weightAbstract = 20;
		$maxScore += $weightAbstract;
		$abstractCheck = self::checkAbstract($publication);
		$earnedScore += round(($abstractCheck['score'] / 100) * $weightAbstract);
		$checks[] = $abstractCheck;

		$weightKeywords = 15;
		$maxScore += $weightKeywords;
		$keywordsCheck = self::checkKeywords($publication);
		$earnedScore += round(($keywordsCheck['score'] / 100) * $weightKeywords);
		$checks[] = $keywordsCheck;

		$weightAuthors = 25;
		$maxScore += $weightAuthors;
		$authorsCheck = self::checkAuthors($publication);
		$earnedScore += round(($authorsCheck['score'] / 100) * $weightAuthors);
		$checks[] = $authorsCheck;

		$weightReferences = 15;
		$maxScore += $weightReferences;
		$referencesCheck = self::checkReferences($publication);
		$earnedScore += round(($referencesCheck['score'] / 100) * $weightReferences);
		$checks[] = $referencesCheck;

		$weightLanguage = 10;
		$maxScore += $weightLanguage;
		$languageCheck = self::checkLanguageCoverage($publication);
		$earnedScore += round(($languageCheck['score'] / 100) * $weightLanguage);
		$checks[] = $languageCheck;

		$weightDoiGalley = 15;
		$maxScore += $weightDoiGalley;
		$doiGalleyCheck = self::checkDoiAndGalley($publication);
		$earnedScore += round(($doiGalleyCheck['score'] / 100) * $weightDoiGalley);
		$checks[] = $doiGalleyCheck;

		$weightPublishing = 15;
		$maxScore += $weightPublishing;
		$publishingCheck = self::checkPublishingDetails($publication);
		$earnedScore += round(($publishingCheck['score'] / 100) * $weightPublishing);
		$checks[] = $publishingCheck;

		$totalScore = $maxScore > 0 ? (int) round(($earnedScore / $maxScore) * 100) : 0;
		$totalScore = max(0, min(100, $totalScore));

		if ($totalScore >= 85) {
			$badge = 'excellent';
			$statusClass = 'status-excellent';
			$badgeText = __('plugins.generic.metadataQualityChecker.status.excellent');
		} elseif ($totalScore >= 70) {
			$badge = 'good';
			$statusClass = 'status-good';
			$badgeText = __('plugins.generic.metadataQualityChecker.status.good');
		} elseif ($totalScore >= 50) {
			$badge = 'needsWork';
			$statusClass = 'status-needs-work';
			$badgeText = __('plugins.generic.metadataQualityChecker.status.needsWork');
		} else {
			$badge = 'critical';
			$statusClass = 'status-critical';
			$badgeText = __('plugins.generic.metadataQualityChecker.status.critical');
		}

		return [
			'score' => $totalScore,
			'badge' => $badge,
			'badgeText' => $badgeText,
			'statusClass' => $statusClass,
			'checks' => $checks,
		];
	}

	private static function checkTitle(Publication $publication): array
	{
		$titles = $publication->getData('title') ?: [];
		$primaryLocale = $publication->getData('locale') ?: 'en';
		$mainTitle = trim($publication->getLocalizedTitle() ?: ($titles[$primaryLocale] ?? ''));

		$items = [];
		$score = 100;

		if (empty($mainTitle)) {
			return [
				'category' => __('plugins.generic.metadataQualityChecker.rule.title'),
				'score' => 0,
				'status' => 'critical',
				'items' => [[
					'type' => 'error',
					'message' => __('plugins.generic.metadataQualityChecker.check.titleEmpty'),
				]],
			];
		}

		$wordCount = count(preg_split('/\s+/u', $mainTitle, -1, PREG_SPLIT_NO_EMPTY));

		if ($wordCount < 4) {
			$score -= 30;
			$items[] = [
				'type' => 'warn',
				'message' => __('plugins.generic.metadataQualityChecker.check.titleTooShort', ['count' => $wordCount]),
			];
		} elseif ($wordCount > 30) {
			$score -= 20;
			$items[] = [
				'type' => 'warn',
				'message' => __('plugins.generic.metadataQualityChecker.check.titleTooLong', ['count' => $wordCount]),
			];
		} else {
			$items[] = [
				'type' => 'pass',
				'message' => __('plugins.generic.metadataQualityChecker.check.titleIdeal', ['count' => $wordCount]),
			];
		}

		if (mb_strtoupper($mainTitle) === $mainTitle && mb_strlen($mainTitle) > 10) {
			$score -= 40;
			$items[] = [
				'type' => 'error',
				'message' => __('plugins.generic.metadataQualityChecker.check.titleAllCaps'),
			];
		}

		return [
			'category' => __('plugins.generic.metadataQualityChecker.rule.title'),
			'score' => max(0, $score),
			'status' => $score >= 80 ? 'pass' : ($score >= 50 ? 'warn' : 'error'),
			'items' => $items,
		];
	}

	private static function checkAbstract(Publication $publication): array
	{
		$abstract = strip_tags(trim($publication->getLocalizedData('abstract') ?: ''));
		$items = [];
		$score = 100;

		if (empty($abstract)) {
			return [
				'category' => __('plugins.generic.metadataQualityChecker.rule.abstract'),
				'score' => 0,
				'status' => 'critical',
				'items' => [[
					'type' => 'error',
					'message' => __('plugins.generic.metadataQualityChecker.check.abstractEmpty'),
				]],
			];
		}

		$wordCount = count(preg_split('/\s+/u', $abstract, -1, PREG_SPLIT_NO_EMPTY));

		if ($wordCount < 100) {
			$score -= 40;
			$items[] = [
				'type' => 'warn',
				'message' => __('plugins.generic.metadataQualityChecker.check.abstractTooShort', ['count' => $wordCount]),
			];
		} elseif ($wordCount > 350) {
			$score -= 20;
			$items[] = [
				'type' => 'warn',
				'message' => __('plugins.generic.metadataQualityChecker.check.abstractTooLong', ['count' => $wordCount]),
			];
		} else {
			$items[] = [
				'type' => 'pass',
				'message' => __('plugins.generic.metadataQualityChecker.check.abstractIdeal', ['count' => $wordCount]),
			];
		}

		return [
			'category' => __('plugins.generic.metadataQualityChecker.rule.abstract'),
			'score' => max(0, $score),
			'status' => $score >= 80 ? 'pass' : ($score >= 50 ? 'warn' : 'error'),
			'items' => $items,
		];
	}

	private static function checkKeywords(Publication $publication): array
	{
		$keywords = (array) ($publication->getLocalizedData('keywords') ?: []);
		$items = [];
		$score = 100;

		$kwCount = count($keywords);

		if ($kwCount === 0) {
			return [
				'category' => __('plugins.generic.metadataQualityChecker.rule.keywords'),
				'score' => 0,
				'status' => 'critical',
				'items' => [[
					'type' => 'error',
					'message' => __('plugins.generic.metadataQualityChecker.check.keywordsEmpty'),
				]],
			];
		}

		if ($kwCount < 3) {
			$score -= 40;
			$items[] = [
				'type' => 'warn',
				'message' => __('plugins.generic.metadataQualityChecker.check.keywordsTooFew', ['count' => $kwCount]),
			];
		} else {
			$items[] = [
				'type' => 'pass',
				'message' => __('plugins.generic.metadataQualityChecker.check.keywordsAdequate', ['count' => $kwCount]),
			];
		}

		$hasMergedComma = false;
		foreach ($keywords as $kw) {
			if (substr_count($kw, ',') >= 2 || substr_count($kw, ';') >= 2) {
				$hasMergedComma = true;
				break;
			}
		}

		if ($hasMergedComma) {
			$score -= 30;
			$items[] = [
				'type' => 'warn',
				'message' => __('plugins.generic.metadataQualityChecker.check.keywordsMergedComma'),
			];
		}

		return [
			'category' => __('plugins.generic.metadataQualityChecker.rule.keywords'),
			'score' => max(0, $score),
			'status' => $score >= 80 ? 'pass' : ($score >= 50 ? 'warn' : 'error'),
			'items' => $items,
		];
	}

	private static function checkAuthors(Publication $publication): array
	{
		$authors = $publication->getData('authors') ?: [];
		$items = [];
		$score = 100;

		$authorCount = count($authors);
		if ($authorCount === 0) {
			return [
				'category' => __('plugins.generic.metadataQualityChecker.rule.authors'),
				'score' => 0,
				'status' => 'critical',
				'items' => [[
					'type' => 'error',
					'message' => __('plugins.generic.metadataQualityChecker.check.authorsEmpty'),
				]],
			];
		}

		$missingAffiliation = 0;
		$missingCountry = 0;
		$missingOrcid = 0;
		$genericEmailCount = 0;
		$hasPrimaryContact = false;

		foreach ($authors as $author) {
			if ($author->getData('primaryContact')) {
				$hasPrimaryContact = true;
			}

			$affil = $author->getLocalizedData('affiliation');
			if (empty(trim($affil ?: ''))) {
				$missingAffiliation++;
			}

			$country = $author->getData('country');
			if (empty($country)) {
				$missingCountry++;
			}

			$orcid = $author->getData('orcid');
			if (empty($orcid)) {
				$missingOrcid++;
			}

			$email = (string) $author->getData('email');
			$domain = strtolower(substr(strrchr($email, '@') ?: '', 1));
			$genericDomains = [
				'gmail.com', 'yahoo.com', 'yahoo.co.id', 'hotmail.com',
				'outlook.com', 'live.com', 'icloud.com', 'mail.com', 'aol.com',
			];
			if (in_array($domain, $genericDomains, true)) {
				$genericEmailCount++;
			}
		}

		if (!$hasPrimaryContact) {
			$score -= 20;
			$items[] = [
				'type' => 'warn',
				'message' => __('plugins.generic.metadataQualityChecker.check.authorsNoPrimary'),
			];
		}

		if ($missingAffiliation > 0) {
			$score -= min(40, $missingAffiliation * 20);
			$items[] = [
				'type' => 'error',
				'message' => __('plugins.generic.metadataQualityChecker.check.authorsMissingAffil', ['missing' => $missingAffiliation, 'total' => $authorCount]),
			];
		} else {
			$items[] = [
				'type' => 'pass',
				'message' => __('plugins.generic.metadataQualityChecker.check.authorsAllAffil', ['total' => $authorCount]),
			];
		}

		if ($missingCountry > 0) {
			$score -= min(20, $missingCountry * 10);
			$items[] = [
				'type' => 'warn',
				'message' => __('plugins.generic.metadataQualityChecker.check.authorsMissingCountry', ['missing' => $missingCountry]),
			];
		}

		if ($missingOrcid > 0) {
			$items[] = [
				'type' => 'info',
				'message' => __('plugins.generic.metadataQualityChecker.check.authorsMissingOrcid', ['missing' => $missingOrcid, 'total' => $authorCount]),
			];
		} else {
			$items[] = [
				'type' => 'pass',
				'message' => __('plugins.generic.metadataQualityChecker.check.authorsAllOrcid'),
			];
		}

		if ($genericEmailCount > 0) {
			$items[] = [
				'type' => 'info',
				'message' => __('plugins.generic.metadataQualityChecker.check.authorsGenericEmail', ['count' => $genericEmailCount, 'total' => $authorCount]),
			];
		} else {
			$items[] = [
				'type' => 'pass',
				'message' => __('plugins.generic.metadataQualityChecker.check.authorsInstEmail'),
			];
		}

		return [
			'category' => __('plugins.generic.metadataQualityChecker.rule.authors'),
			'score' => max(0, $score),
			'status' => $score >= 80 ? 'pass' : ($score >= 50 ? 'warn' : 'error'),
			'items' => $items,
		];
	}

	private static function checkReferences(Publication $publication): array
	{
		$citations = trim($publication->getData('citationsRaw') ?: '');
		$items = [];
		$score = 100;

		if (empty($citations)) {
			return [
				'category' => __('plugins.generic.metadataQualityChecker.rule.references'),
				'score' => 0,
				'status' => 'critical',
				'items' => [[
					'type' => 'error',
					'message' => __('plugins.generic.metadataQualityChecker.check.refEmpty'),
				]],
			];
		}

		$lines = array_filter(array_map('trim', explode("\n", $citations)), function ($l) {
			return strlen($l) > 15;
		});

		$refCount = count($lines);

		if ($refCount < 10) {
			$score -= 30;
			$items[] = [
				'type' => 'warn',
				'message' => __('plugins.generic.metadataQualityChecker.check.refTooFew', ['count' => $refCount]),
			];
		} else {
			$items[] = [
				'type' => 'pass',
				'message' => __('plugins.generic.metadataQualityChecker.check.refAdequate', ['count' => $refCount]),
			];
		}

		$doiCount = preg_match_all('/(10\.\d{4,9}\/[-._;()\/:A-Z0-9]+|https?:\/\/(?:dx\.)?doi\.org\/)/i', $citations);
		if ($doiCount > 0) {
			$items[] = [
				'type' => 'pass',
				'message' => __('plugins.generic.metadataQualityChecker.check.refDoiFound', ['count' => $doiCount]),
			];
		} else {
			$score -= 15;
			$items[] = [
				'type' => 'info',
				'message' => __('plugins.generic.metadataQualityChecker.check.refNoDoi'),
			];
		}

		return [
			'category' => __('plugins.generic.metadataQualityChecker.rule.references'),
			'score' => max(0, $score),
			'status' => $score >= 80 ? 'pass' : ($score >= 50 ? 'warn' : 'error'),
			'items' => $items,
		];
	}

	private static function checkLanguageCoverage(Publication $publication): array
	{
		$titles = $publication->getData('title') ?: [];
		$abstracts = $publication->getData('abstract') ?: [];
		$items = [];
		$score = 100;

		$hasEnglishTitle = !empty(trim($titles['en'] ?? ''));
		$hasEnglishAbstract = !empty(trim(strip_tags($abstracts['en'] ?? '')));

		if ($hasEnglishTitle && $hasEnglishAbstract) {
			$items[] = [
				'type' => 'pass',
				'message' => __('plugins.generic.metadataQualityChecker.check.coverageFull'),
			];
		} elseif ($hasEnglishTitle || $hasEnglishAbstract) {
			$score -= 30;
			$items[] = [
				'type' => 'warn',
				'message' => __('plugins.generic.metadataQualityChecker.check.coveragePartial'),
			];
		} else {
			$score -= 50;
			$items[] = [
				'type' => 'info',
				'message' => __('plugins.generic.metadataQualityChecker.check.coverageNone'),
			];
		}

		return [
			'category' => __('plugins.generic.metadataQualityChecker.rule.coverage'),
			'score' => max(0, $score),
			'status' => $score >= 70 ? 'pass' : 'warn',
			'items' => $items,
		];
	}

	private static function checkDoiAndGalley(Publication $publication): array
	{
		$items = [];
		$score = 100;

		$doi = $publication->getDoi();
		if ($doi) {
			$items[] = [
				'type' => 'pass',
				'message' => __('plugins.generic.metadataQualityChecker.check.doiAssigned', ['doi' => $doi]),
			];
		} else {
			$score -= 40;
			$items[] = [
				'type' => 'warn',
				'message' => __('plugins.generic.metadataQualityChecker.check.doiMissing'),
			];
		}

		$galleys = Repo::galley()->getCollector()
			->filterByPublicationIds([$publication->getId()])
			->getMany();

		$galleyCount = 0;
		$labels = [];
		foreach ($galleys as $galley) {
			$galleyCount++;
			$labels[] = $galley->getLabel() ?: 'Galley';
		}

		if ($galleyCount > 0) {
			$items[] = [
				'type' => 'pass',
				'message' => __('plugins.generic.metadataQualityChecker.check.galleyPresent', [
					'count' => $galleyCount,
					'labels' => implode(', ', $labels),
				]),
			];
		} else {
			$score -= 50;
			$items[] = [
				'type' => 'error',
				'message' => __('plugins.generic.metadataQualityChecker.check.galleyMissing'),
			];
		}

		return [
			'category' => __('plugins.generic.metadataQualityChecker.rule.doiGalley'),
			'score' => max(0, $score),
			'status' => $score >= 75 ? 'pass' : ($score >= 50 ? 'warn' : 'error'),
			'items' => $items,
		];
	}

	private static function checkPublishingDetails(Publication $publication): array
	{
		$items = [];
		$score = 100;

		$issueId = $publication->getData('issueId');
		if ($issueId) {
			$issue = Repo::issue()->get($issueId);
			$issueName = $issue ? $issue->getIssueIdentification() : '#' . $issueId;
			$items[] = [
				'type' => 'pass',
				'message' => __('plugins.generic.metadataQualityChecker.check.issueAssigned', ['issue' => $issueName]),
			];
		} else {
			$score -= 40;
			$items[] = [
				'type' => 'warn',
				'message' => __('plugins.generic.metadataQualityChecker.check.issueMissing'),
			];
		}

		$pages = trim((string) ($publication->getData('pages') ?: ''));
		if (!empty($pages)) {
			$items[] = [
				'type' => 'pass',
				'message' => __('plugins.generic.metadataQualityChecker.check.pagesPresent', ['pages' => $pages]),
			];
		} else {
			$score -= 30;
			$items[] = [
				'type' => 'warn',
				'message' => __('plugins.generic.metadataQualityChecker.check.pagesMissing'),
			];
		}

		$licenseUrl = trim((string) ($publication->getData('licenseUrl') ?: ''));
		if (!empty($licenseUrl)) {
			$items[] = [
				'type' => 'pass',
				'message' => __('plugins.generic.metadataQualityChecker.check.licensePresent', ['url' => $licenseUrl]),
			];
		} else {
			$score -= 30;
			$items[] = [
				'type' => 'warn',
				'message' => __('plugins.generic.metadataQualityChecker.check.licenseMissing'),
			];
		}

		return [
			'category' => __('plugins.generic.metadataQualityChecker.rule.publishingDetails'),
			'score' => max(0, $score),
			'status' => $score >= 75 ? 'pass' : ($score >= 50 ? 'warn' : 'error'),
			'items' => $items,
		];
	}
}
