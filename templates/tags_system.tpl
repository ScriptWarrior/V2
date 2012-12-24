{foreach from=$tags item=tag}
	<h{$tag.size_class}>{$tag.tag_value}</h{$tag.size_class}>
{/foreach}