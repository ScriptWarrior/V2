// ViLDeV V2 coded by AcidZed
var MAIN_TPL
var main_container="#dynamic_content"
var error=0
var page=1 
var load_status=0
var link_separator=','
var link_strategy=[] //={'module':'','action':'','id':0,'csrf_protection_code':'','subaction':'','page':0,'ajax':1,'no_output':0,'ln':'pl'} // temporary hardcoded
var previous_func_name='' // for conveniance to easily get back to the previous step
var func_params=[]
var user_id=0	// odwzorowanie stanu sesji po stronie JS, dzialamy bez przeladowania
var user_gid=[]	// list of groups
var block_destructor=0
var MSG=[] // V2 array with language messages,
// CNF[] is also defined, but earlier, before the engine, so these values can be already used here, it was causing issues when I was trying to retrieve it locally
var modules_loaded={}
var found_rows=0
var per_page=10
var site_name=CNF['SITE_NAME']
function get_link(arr)
{
	var params=[]
	var curr_suffix=CNF['LINK_SUFFIX']
	for(key in link_strategy)
	{
		if(key=="ajax"&&link_strategy[key]==1) curr_suffix='.xml' 
		if(typeof arr!="undefined"&&typeof arr[key]!="undefined")
		params.push(arr[key])
		else
		params.push(link_strategy[key])
	}
	return params.join(link_separator)+curr_suffix
}
function get_node_attributes(node)
{
	var f_code='function f_code(){return ['
	var attrs=[]
	if(typeof node!="object"||typeof node.attributes!="object") return new Array() // tolerka
	for(key in node.attributes) if(key.match(/^\d+$/)) attrs.push("'"+node.attributes[key].nodeName+"'")
	f_code+=attrs.join(',')+']}'
	eval(f_code) // lokalna deklaracja funkcji zwracajacej anonimowa tablice
	return f_code()
}
function xml_to_array(xmlObject,element_name)
{
	var ret_arr=[]
	if(typeof $(element_name,xmlObject)[0]=="undefined") return ret_arr
	var attributes=this.get_node_attributes($(element_name,xmlObject)[0])
	$(element_name,xmlObject).each(function(){
		row=[]
		for(key in attributes) 
		{
			row[attributes[key]]=$(this).attr(attributes[key]) 
		}
		ret_arr.push(row)
	})
	return ret_arr
}
function flush()
{
    this.tpl_content=""
}
function assign(key,val)
{
	this.variables[key]=val
}
// prosta implementacja ifow w moich pseudo smartach
function make_if()
{
	var backrefs=this.tpl_content_compiled.match(/{if\s*(.+?)\}(.+?)(\{else\}(.+?))?\{\/if\}/)
	if(!backrefs) return 0
	var whole_match=backrefs[0]
	var condition=backrefs[1]
	var conditional_code=backrefs[2]
	var else_code=""
	if(typeof backrefs[3]!="undefined") else_code=backrefs[4]
	var condition_value=0
	eval('if('+condition+') condition_value=1')
	if(condition_value) this.tpl_content_compiled=this.tpl_content_compiled.replace(whole_match,conditional_code)
	else
	if(else_code!="") this.tpl_content_compiled=this.tpl_content_compiled.replace(whole_match,else_code)
	else
	this.tpl_content_compiled=this.tpl_content_compiled.replace(whole_match,'')
	return 1
}
function make_foreach()
{
	var backrefs=this.tpl_content_compiled.match(/\{foreach\s+(.+?)\}(.+?)\{\/foreach\}/)
	if(backrefs==null) return 0
	var whole_match=backrefs[0]	// caly macz, zastapimy go powtorzonym inner_tpl z podstawionymi wartosciami
	var params=backrefs[1] // parametry foreacha, tj. item, from
	var inner_tpl=backrefs[2]
	var new_content=""
	var param_from=params.match(/from=\$(\w+)/) // jest nazwa tablicy z assigna
	var param_item=params.match(/item=(\w+)/)	// jest nazwa itemu, ktory wystepuje w inner_tpl z $ (moze byc sam lub z kropka)
	if(param_from==null) return 0
	if(param_item==null) return 0
	param_from=param_from[1]
	param_item=param_item[1]
	if(typeof this.variables[param_from]=="object")
	for (arr_key in this.variables[param_from])
	{
		if(typeof this.variables[param_from][arr_key]=='string') eval("new_content+=inner_tpl.replace(/\\{\\$"+param_item+"\\}/g,this.variables[param_from][arr_key]")
		else
		{
			var new_content2=inner_tpl
			for(arr_key2 in this.variables[param_from][arr_key]) eval("new_content2=new_content2.replace(/\\{\\$"+param_item+"\\."+arr_key2+"\\}/g,this.variables[param_from][arr_key][arr_key2])")
			new_content+=new_content2
		}
	}
	this.tpl_content_compiled=this.tpl_content_compiled.replace(whole_match,new_content)
	while(this.make_if()){}
	return 1
}
function fetch()
{
	var tpl_content=""
	if(this.tpl_content=="")
	{
		$.ajax({url:this.tpl_file, cache:true, async: false, success:function(HTML){tpl_content=HTML}})
		this.tpl_content=tpl_content
	}
	this.tpl_content=this.tpl_content.replace(/\n*\r*\t*/g,'') // 'obfuscation'
	this.tpl_content_compiled=this.tpl_content // copy the source template
	for (key in this.variables)
	{
		max_i=0
		while(this.tpl_content_compiled.match('\\{\\$'+key+'\\}'))
		{
			max_i++
			this.tpl_content_compiled=this.tpl_content_compiled.replace('{$'+key+'}',this.variables[key])
			if(max_i==100) 	{ alert('Looks like you need this safety break!');	break; } // just in case something fucked up ;]
		}
	}
	while(this.foreach()){}
	this.tpl_content_compiled=this.tpl_content_compiled.replace(/\{\$\w+(\.\w+)*\}/g,'')
	return this.tpl_content_compiled
}
function pseudo_smarty(tpl_file)
{
	this.tpl_file='/templates/'+tpl_file+'.tpl'
	this.tpl_content=""
	this.tpl_content_compiled=""
	this.variables=[]
	this.assign=assign
	this.fetch=fetch
	this.foreach=make_foreach
	this.make_if=make_if
	this.flush=flush
	this.xml_to_array=xml_to_array
	this.get_node_attributes=get_node_attributes
}
function vbbcode() 
{ 
	$(this).html($(this).html().replace(/\n/g,'<br class="nl2br">')) // nl2br
	// [html]location[/html]
	html_parts=$(this).html().match(/\[html\](.*?)\[\/html\]/mg)
	if(html_parts)
	{
		for(i=0;i<html_parts.length;i++)
		{
				html_parts[i]=html_parts[i].replace('[html]','')
				html_parts[i]=html_parts[i].replace('[/html]','')
				html_parts[i]=html_parts[i].replace(/&gt;/g,'>')
				html_parts[i]=html_parts[i].replace(/&lt;/g,'<')
				html_parts[i]=html_parts[i].replace(/amp;/g,'')
				html_parts[i]=html_parts[i].replace(/<br class="nl2br">/g,'')
				$(this).html($(this).html().replace(/\[html\].*?\[\/html\]/,html_parts[i]))
		}
	}
	$(this).html($(this).html().replace(/\[img\](http(s?):\/\/(((\w+-?)*\w+\.)+\w{2,4})\/\S*?)\[\/img\]/g,'<img class="v_bbimg" src="$1" />'))
	$(this).html($(this).html().replace(/\[url\](http(s?):\/\/(((\w+-?)*\w+\.)+\w{2,4})\/\S*?)\[\/url\]/g,'<a href="$1" target="_blank">$1</a>')) // links

}
function trim(str)
{
	str=str.replace(/^\s*/,'')
	str=str.replace(/\s*$/,'')
	return str
}
function pager_generate(subpage_class,active_class,callback_name)
{
    var stop_page=Math.ceil(parseFloat(found_rows)/parseFloat(per_page))
    var subs_html=''
    for(i=1;i<=stop_page;i++) subs_html+='<u id="subpage_'+i+'" class="'+subpage_class+((page==i)?' '+active_class:'')+'">'+i+'&nbsp;</u>'
    if(page>stop_page) page=stop_page // to nie spelnia swojego zadania - poprawic
    return subs_html+'<script>$(".'+subpage_class+'").click('+callback_name+');</script>'
}
function handle_error_response(returnedXMLResponse)
{
	error=0
  	$('error',returnedXMLResponse).each(function(){error=1; alert($(this).text());})
}
function handle_info_response(returnedXMLResponse)
{
	$('info',returnedXMLResponse).each(function(){alert($(this).text())})
}
function handle_xml_response(returnedXMLResponse,error,info)
{
  		if(typeof error!="undefined"&&error=='yes') handle_error_response(returnedXMLResponse)
      if(typeof info!="undefined"&&info=='yes') handle_info_response(returnedXMLResponse)
}
function extract_id(html_id)
{
	if(typeof html_id=="undefined") return 0
	backref=html_id.match(/(\d+)/)
	if(typeof backref[1]!="undefined") return backref[1]
	return 0
}
function include(src)
{
	$.ajax({url:'/js/'+src+'_mod.js',async:false})
	return 1
}
function load_template(template)
{
	if(typeof MAIN_TPL=="object")  MAIN_TPL.flush()	 // just in case
   MAIN_TPL=new pseudo_smarty(template)
   for(msg_key in MSG) MAIN_TPL.assign(msg_key,MSG[msg_key]) // auto multilang messages embedding
}
function load_strategy()
{
	 if($.cookie('http_req_strategy')) // change to retriever from CNF arr
	 {
	 	 var new_strategy=$.cookie('http_req_strategy').split(',')
		 for(key in new_strategy) 
		 {
		   var strategy_parts=new_strategy[key].split('=')
		   if(typeof strategy_parts[1]=="undefined") strategy_parts[1]=''
		 	link_strategy[strategy_parts[0]]=strategy_parts[1]
		 }
		 link_strategy['ajax']=1 // default for JS engine
	 }
}
// ta zunifokowana metoda jest odpalana szeregowo, przez kolejne linkujace sie moduly, w ten sposob strona nigdy nie potrzebuje przeladowania i z kazdego do kazdego miejsca w interfejsie da sie dotrzec
// od tej pory sterujemy sie funkcjami, funkcja zazwyczaj jako param moze przyjmowac nazwe swojego szablonu
// aby na niego wejsc, badz zdecydowac sama
function init_mod(curr_mod,curr_func,parent_call)
{
	// dynamically read the strategy for link generator and initialize common values
	 if(typeof parent_call!="undefined") block_destructor=1
    user_id=$.cookie('user_logged')
    user_gid=$.cookie('user_gid')
    if(user_gid) user_gid=user_gid.split(';')
    if(typeof modules_loaded[curr_mod]=="undefined"&&(include(curr_mod)&&!load_status))
    {
			alert('Invalid module '+curr_mod+'! (load_status: '+load_status+')')
			return 0
    }
    // read in all messages and labels (both engine's and curr_mod's ;D)
    $.ajax({url:get_link({'module':curr_mod,'action':'','subaction':'load_messages','ajax':1,'csrf_code':''}),success:function(xml)
	 {
	 	$('msg',xml).each(function(){
	 		MSG[$(this).attr('id')]=$(this).text()
	 	})
	 	load_strategy() // strategy has to be refreshed after this
	   modules_loaded[curr_mod]=1
	 	link_strategy['module']=curr_mod
	 	if(func_params.length) for(key in func_params) func_params[key]="'"+func_params[key]+"'"
	 	eval(curr_func+'('+func_params.join(',')+')')
    	if(!block_destructor) destruct()
    	previous_func_name=curr_func
    	block_destructor=0
    }})
}
function validate_value(preg,val,errmsg,method) // used by auto-generated callbacks for auto-generated forms
{
	if(preg=='') return 1
	if(val.match(/^preg$/)) return 1
	if(method.match(/#\w+/)) 
	$("#"+method).html(errmsg)
	else
	jDialog(errmsg,site_name)
}
function destruct()
{
	 if(typeof MAIN_TPL=="object")  $(main_container).html(MAIN_TPL.fetch())
    func_params=[]
    found_rows=0
    load_strategy()
}
function error_handling(XML_via_arg)
{
	var dialog_callback='';
	var dialog_text='';
	$('info',XML_via_arg).each(function()
		{
			dialog_text=$(this).text()
			dialog_callback=''
		})		
	$('error',XML_via_arg).each(function()
		{
			dialog_text=$(this).text()
			dialog_callback=''
			category_id=''
		})
	if(dialog_text!='')
	{
		jAlert(dialog_text,site_name)
		return 1
	}
	return 0
}
// global init regardless of init_mod usage (it's possible to not use init_mod, but just some separate methods)
load_strategy()
$(document).ready(function(){$('body').ajaxComplete(function(e,x){handle_xml_response(x.responseXML,'yes','yes'); load_strategy()})})
 // add our messages catcher