<div class="mqc-header" style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e5e7eb; padding-bottom: 1.25rem; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
	<div>
		<h2 style="font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0 0 0.25rem 0;">
			{translate key="plugins.generic.metadataQualityChecker.overallScore"}
		</h2>
		<p style="font-size: 0.875rem; color: #6b7280; margin: 0;">
			{translate key="plugins.generic.metadataQualityChecker.description"}
		</p>
	</div>
	<div style="display: flex; align-items: center; gap: 1rem;">
		<div style="text-align: right;">
			<div style="font-size: 2rem; font-weight: 800; line-height: 1; color: {if $mqcReport.score >= 85}#059669{elseif $mqcReport.score >= 70}#2563eb{elseif $mqcReport.score >= 50}#d97706{else}#dc2626{/if};">
				{$mqcReport.score}%
			</div>
			<span style="display: inline-block; margin-top: 0.35rem; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.65rem; border-radius: 9999px; {if $mqcReport.score >= 85}background:#ecfdf5;color:#065f46;{elseif $mqcReport.score >= 70}background:#eff6ff;color:#1e40af;{elseif $mqcReport.score >= 50}background:#fffbeb;color:#92400e;{else}background:#fef2f2;color:#991b1b;{/if}">
				{$mqcReport.badgeText}
			</span>
		</div>
	</div>
</div>

<div style="background: #f3f4f6; border-radius: 9999px; height: 10px; overflow: hidden; margin-bottom: 2rem;">
	<div style="width: {$mqcReport.score}%; height: 100%; transition: width 0.4s ease; background: {if $mqcReport.score >= 85}#10b981{elseif $mqcReport.score >= 70}#3b82f6{elseif $mqcReport.score >= 50}#f59e0b{else}#ef4444{/if};"></div>
</div>

<div class="mqc-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem;">
	{foreach from=$mqcReport.checks item=check}
		<div class="mqc-card" style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 1rem; background: #fafafa; display: flex; flex-direction: column; justify-content: space-between;">
			<div>
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
					<strong style="font-size: 0.95rem; color: #1f2937;">{$check.category}</strong>
					<span style="font-size: 0.8rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px; {if $check.status === 'pass'}background:#d1fae5;color:#065f46;{elseif $check.status === 'warn'}background:#fef3c7;color:#92400e;{else}background:#fee2e2;color:#991b1b;{/if}">
						{$check.score}%
					</span>
				</div>
				<ul style="list-style: none; padding: 0; margin: 0; font-size: 0.85rem; line-height: 1.45;">
					{foreach from=$check.items item=item}
						<li style="margin-bottom: 0.5rem; display: flex; align-items: flex-start; gap: 0.5rem; color: #374151;">
							<span style="font-weight: bold; flex-shrink: 0; {if $item.type === 'pass'}color: #10b981;{elseif $item.type === 'warn'}color: #f59e0b;{elseif $item.type === 'error'}color: #ef4444;{else}color: #6b7280;{/if}">
								{if $item.type === 'pass'}✓{elseif $item.type === 'warn'}!{elseif $item.type === 'error'}✕{else}ℹ{/if}
							</span>
							<span>{$item.message}</span>
						</li>
					{/foreach}
				</ul>
			</div>
		</div>
	{/foreach}
</div>
