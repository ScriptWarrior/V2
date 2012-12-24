<h2>Geek!life news</h2>
<!-- this is normal smarty template; no pseudo smarty used since this should be indexed by the search engines -->
{$no_results}
{foreach from=$news item=n}
	<div class="news_title_link">{$n.link}</div>
	<div class="news_content">
	{$n.content}
	</div>
	<div class="news_created_at">
		{$VE_CREATED_AT}:{$n.created_at}
	</div>
	<!-- 
	<div class="news_author">
		{$VE_AUTHOR}: {$n.author} this can be both simple string or link to the profile
	</div>
	-->
	<div class="news_keywords">
		<h3>{$VE_KEYWORDS}</h3>
		{$n.keywords} <!-- this should be foreached, but since we can't and shouldn't use PHP in the templates, we will generate those links in the loop after fetch from DB and before assigning the whole array here, no big deal -->
	</div>
{/foreach}
{$VE_PAGER_LABEL} 
{foreach from=$pager item=page}
&nbsp;{$page}
{/foreach}
<hr />
<script>
{literal}
	$(document).ready(function(){$('.news_content').each(vbbcode)})
{/literal}
</script>