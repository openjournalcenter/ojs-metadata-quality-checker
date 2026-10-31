<tab id="metadataQualityCheckerTab" label="{translate key="plugins.generic.metadataQualityChecker.tabTitle"}">
	<div id="mqcContentWrapper" class="mqc-container" style="padding: 1.5rem; background: #fff; border-radius: 8px; border: 1px solid #e5e7eb; margin-top: 1rem; position: relative;">
		{include file=$mqcContentTemplate}
	</div>

	<script type="text/javascript">
		(function($) {
			var ajaxUrl = {$mqcAjaxUrl|json_encode};
			var submissionId = {$mqcSubmissionId|json_encode};
			var currentPubId = {$mqcPublicationId|json_encode};
			var refreshTimer = null;

			function reloadMqc() {
				var $wrapper = $('#mqcContentWrapper');
				if (!$wrapper.length) return;

				if (window.pkp && pkp.registry && pkp.registry._instances && pkp.registry._instances.app && pkp.registry._instances.app.workingPublication) {
					currentPubId = pkp.registry._instances.app.workingPublication.id;
				}

				$.ajax({
					url: ajaxUrl,
					type: 'GET',
					data: {
						submissionId: submissionId,
						publicationId: currentPubId
					},
					dataType: 'json',
					success: function(res) {
						if (res && res.status && res.content) {
							$wrapper.html(res.content);
						}
					}
				});
			}

			function scheduleReload() {
				clearTimeout(refreshTimer);
				refreshTimer = setTimeout(reloadMqc, 300);
			}

			if (window.pkp && pkp.eventBus) {
				pkp.eventBus.$on('form-success', function() {
					scheduleReload();
				});
			}
		})(jQuery);
	</script>
</tab>
