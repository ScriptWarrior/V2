{*  
 - EVERYTHING I MOST FU**ING HATE IN WEBDEV IS CONDENSATED IN THIS RADIOACTIVE HORRIBLE TEMPLATE
 - there should be really two form kinds - AJAX-enabled and smarty enabled, for now we do just AJAX enabled cause we will use it more frequently
 - this is universal sick forms/displayers generator - it generates document able to be used as displayer+inserter+updater+lister
 - It does not support listers now
*}
<script>
var current_id=0
var default_values=[]
</script>
<form enctype="mutlipart/form-data" method="{$method}" action="{$link}">{foreach from=$rules item=rule}<div class="vform_attribute_container">	<div class="vform_attribute_info vform_attribute {if $rule.display_label=="no"}vform_attribute_label_hidden{else}{$rule.class_displayer}{/if}">{$rule.label}</div>	<div class="vform_attribute_input vform_attribute" style="display:none">{if $rule.type == "text"}<textarea title="{$rule.info}" rows="25" cols="65" name="{$rule.attribute_id}" id="vform_attribute_{$rule.attribute_id}_{$rule.id}" class="{$rule.class} vform_autoinput vform_autoinput_{$rule.id}"{if $rule.edit == "no"} disabled="disabled"{/if}>{$rule.content}</textarea>{elseif $rule.type == "checkbox"}<checkbox id="vform_attribute_{$rule.attribute_id}_{$rule.id}" name="{$rule.attribute_id}" title="{$rule.info}" class="{$rule.class} vform_autoinput vform_autoinput_{$rule.id}"{if $rule.edit == "no"} disabled="disabled"{/if} />{elseif $rule.type == "list"}<select id="vform_attribute_{$rule.attribute_id}_{$rule.id}"  name="{$rule.attribute_id}" title="{$rule.info}" class="{$rule.class} vform_autoinput vform_autoinput_{$rule.id}"{if $rule.edit == "no"} disabled="disabled"{/if}>{foreach from=$rule.default key=list_option item=list_value}	<option value="{$list_value}">{$list_option}</option>{/foreach}</select>{else}<input title="{$rule.info}" value="{$rule.content}" type="text" name="{$rule.attribute_id}" id="vform_attribute_{$rule.attribute_id}_{$rule.id}" class="{$rule.class_input} vform_autoinput vform_autoinput_{$rule.id}" size="50"{if $rule.edit == "no"} disabled="disabled"{/if}/>{/if}</div><div class="vform_attribute_displayer vform_attribute  {$rule.class_displayer}" title="{$rule.info}" style="{if $rule.display_label=="no"}margin-left:0px {/if}">{if $rule.display=="yes"}{$rule.content}{/if}</div>{if $MULTI_SAVERS == "yes"}<div class="vform_attribute_saver vform_attribute" style="display:none"><u id="vform_saver_{$rule.id}" class="vform_trigger {$rule.class_saver} vform_autoinput">{$VE_SAVE}</u></div>{/if}<script>$('#vform_attribute_saver_{$rule.id}').click(function(){literal}{{/literal}if(validate_value('{$rule.preg}',$('#vform_attribute_{$rule.attribute_id}_{$rule.id}').val(),'{$rule.error}','jAlert')) update__{$module}({$rule.id},$('#vform_attribute_{$rule.attribute_id}_{$rule.id}').val());{literal}}{/literal});			default_values['{$rule.attribute_id}']='{$rule.default}';	current_id={$rule.id};</script></div>{/foreach}</form>{if $VCAN_EDIT==1} <u id="vform_edit_trigger_{$rule.id}" class="vform_trigger vform_trigger_toggle vform_edit_trigger">{$VE_EDIT}</u>{/if}{if $VCAN_DELETE==1} <u id="vform_remove_trigger_{$rule.id}" class="vform_trigger vform_remove_trigger">{$VE_DELETE}</u>{/if}{if $VCAN_CREATE==1} <u class="vform_trigger vform_trigger_toggle vform_create_trigger">{$VE_CREATE}</u>{/if}{if ($VCAN_EDIT==1||$VCAN_CREATE==1)  && $MULTI_SAVERS == 0}<u id="vform_saver_{$rule.id}" class="vform_trigger {$rule.class_saver} vform_save_trigger">{$VE_SAVE}</u>{/if}<script>$('.vform_attribute_saver,.vform_save_trigger').hide(); $('.vform_attribute_info').balloon(); $('.vform_attribute_displayer').show('slow'); $('.vform_attribute_displayer').each(vbbcode); {if $VCAN_EDIT==1 || $VCAN_CREATE==1}{literal}var create_on=0; var edit_on=0;
/* we need to keep the element's ID to remember values for value switching in inputs */
function switch_values()
{
	for(key in default_values)
	{	
		var save=''
		if(typeof $("#vform_attribute_"+key+"_"+current_id).val()!="undefined") save=$("#vform_attribute_"+key+"_"+current_id).val()
		$("#vform_attribute_"+key+"_"+current_id).val(default_values[key])
		default_values[key]=save
	}
}
function enable_input()
{
		$(this).css('font-size','+2')
		$('.vform_attribute_displayer').hide()
		$('.vform_attribute_input').show()
		$('.vform_attribute_label_hidden').show()
}
function disable_input()
{
		$(this).css('font-size','-2')
		$('.vform_attribute_displayer').show()
		$('.vform_attribute_input').hide()
		$('.vform_attribute_saver,.vform_save_trigger').hide()
		$('.vform_attribute_label_hidden').hide()
}
$('.vform_trigger_toggle').click( // create  & edit callback, instead of toggle
	function()
	{
		if(create_on&&$(this).hasClass('vform_create_trigger'))
		{
			disable_input()
			create_on=0
			$(this).removeClass('vform_trigger_active')
			$('.vform_attribute_saver,.vform_save_trigger').hide()
			switch_values()
		}
		else if(create_on&&$(this).hasClass('vform_edit_trigger'))
		{
			create_on=0
			edit_on=1
			current_id=extract_id($(this).attr('id'))
			$('.vform_trigger').removeClass('vform_trigger_active')
			$(this).addClass('vform_trigger_active')
			switch_values()
		}
		else if(edit_on&&$(this).hasClass('vform_create_trigger'))
		{
			create_on=1
			$('.vform_trigger').removeClass('vform_trigger_active')
			$(this).addClass('vform_trigger_active')
			edit_on=0
			switch_values()
		}
		else if(edit_on&&$(this).hasClass('vform_edit_trigger'))
		{
			edit_on=0
			$(this).removeClass('vform_trigger_active')
			$('.vform_attribute_saver,.vform_save_trigger').hide()
			disable_input()
		}
		else if(!edit_on&&!create_on)
		{
			$(this).addClass('vform_trigger_active')
			$('.vform_attribute_saver,.vform_save_trigger').show()
			if($(this).hasClass('vform_edit_trigger'))
			{
				edit_on=1
				current_id=extract_id($(this).attr('id'))
			}
			else
			{
				switch_values()
				create_on=1
			}
			enable_input()
		}
	}
);
$('.vform_autoinput').each(function(){
	$(this).val($(this).val().replace(/(<|(&lt;))\s*br\s*\/?\s*(>|(&gt;))/g,""))
})
$('.vform_save_trigger').click(function(){
	action='update'
	id=current_id
	if(create_on) 
	{
		action='create'
		id=0
	}
	$.post(get_link({'module':'{/literal}{$CURR_MOD}{literal}','action':action,id:id,'ajax':1}),$('.vform_autoinput_'+current_id).serialize(),function(xml){handle_xml_response(xml,'yes','yes')})
})
$('.vform_remove_trigger').click(function(){
{/literal}
	confirm=confirm("{$VE_DEL_CONFIRM}")
	{literal}
	if(confirm)
	{
		id=extract_id($(this).attr('id'))
		$.ajax(
		{
			url:get_link({'module':{/literal}'{$CURR_MOD}'{literal},'action':'delete','id':id,'ajax':1}),success:function()
			{
				if(!error)
				{
					document.location.href=get_link({module:'{/literal}{$CURR_MOD}{literal}','action':'list','ajax':0})
				}
			}
		}
		)
		// we should do something after such action, hm, I don't know, get back to lister?
	} //end of confirm 
}
)
{/literal}
{/if}
</script>