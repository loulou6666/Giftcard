{*
* 2007-2013 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2013 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
{if version_compare($smarty.const._PS_VERSION_,'1.6','>=')}
<!-- start template for 1.6+ -->
{if isset($fields.title)}<h3>{$fields.title}</h3>{/if}

{if isset($tabs) && $tabs|count}
<script type="text/javascript">
	var helper_tabs = {$tabs|json_encode};
	var unique_field_id = '';
</script>
{/if}

{block name="defaultForm"}
<form id="{if isset($fields.form.form.id_form)}{$fields.form.form.id_form|escape:'html':'UTF-8'}{else}{if $table == null}configuration_form{else}{$table}_form{/if}{/if}" class="defaultForm {$name_controller} form-horizontal" action="{$current}&amp;token={$token}" method="post" enctype="multipart/form-data"{if isset($style)} style="{$style}"{/if} novalidate>
	{if $form_id}
		<input type="hidden" name="{$identifier}" id="{$identifier}" value="{$form_id}" />
	{/if}
	{if !empty($submit_action)}
		<input type="hidden" name="{$submit_action}" value="1" />
	{/if}
	
	{foreach $fields as $f => $fieldset}
		{block name="fieldset"}
		<div class="panel" id="fieldset_{$f}">
			{foreach $fieldset.form as $key => $field}
				{if $key == 'legend'}
					{block name="legend"}
						<div class="panel-heading">
							{if isset($field.image) && isset($field.title)}<img src="{$field.image}" alt="{$field.title|escape:'html':'UTF-8'}" />{/if}
							{if isset($field.icon)}<i class="{$field.icon}"></i>{/if}
							{$field.title}
						</div>
					{/block}
				{elseif $key == 'description' && $field}
					<div class="alert alert-info">{$field}</div>
				{elseif $key == 'input'}
					<div class="form-wrapper">
					{foreach $field as $input}
						{block name="input_row"}
						<div class="form-group{if isset($input.form_group_class)} {$input.form_group_class}{/if}{if $input.type == 'hidden'} hide{/if}"{if $input.name == 'id_state'} id="contains_states"{if !$contains_states} style="display:none;"{/if}{/if} {if isset($tabs) && isset($input.tab)}data-tab-id="{$input.tab}"{/if}>
						{if $input.type == 'hidden'}
							<input type="hidden" name="{$input.name}" id="{$input.name}" value="{$fields_value[$input.name]|escape:'html':'UTF-8'}" />
						{else}
							{block name="label"}
								{if isset($input.label)}
									<label for="{if isset($input.id)}{$input.id}{if isset($input.lang) AND $input.lang}_{$current_id_lang}{/if}{else}{$input.name}{if isset($input.lang) AND $input.lang}_{$current_id_lang}{/if}{/if}" class="control-label col-lg-3 {if isset($input.required) && $input.required && $input.type != 'radio'}required{/if}">
										{if isset($input.hint)}
										<span class="label-tooltip" data-toggle="tooltip" data-html="true"
											title="{if is_array($input.hint)}
													{foreach $input.hint as $hint}
														{if is_array($hint)}
															{$hint.text}
														{else}
															{$hint}
														{/if}
													{/foreach}
												{else}
													{$input.hint}
												{/if}">
										{/if}
										{$input.label}
										{if isset($input.hint)}
										</span>
										{/if}
									</label>
								{/if}
							{/block}

							{block name="field"}
								<div class="col-lg-{if isset($input.col)}{$input.col|intval}{else}9{/if} {if !isset($input.label)}col-lg-offset-3{/if}">
								{block name="input"}
								{if $input.type == 'text' || $input.type == 'tags'}
									{if isset($input.lang) AND $input.lang}
									{if $languages|count > 1}
									<div class="form-group">
									{/if}
									{foreach $languages as $language}
										{assign var='value_text' value=$fields_value[$input.name][$language.id_lang]}
										{if $languages|count > 1}
										<div class="translatable-field lang-{$language.id_lang}" {if $language.id_lang != $defaultFormLanguage}style="display:none"{/if}>
											<div class="col-lg-9">
										{/if}
												{if $input.type == 'tags'}
													{literal}
														<script type="text/javascript">
															$().ready(function () {
																var input_id = '{/literal}{if isset($input.id)}{$input.id}_{$language.id_lang}{else}{$input.name}_{$language.id_lang}{/if}{literal}';
																$('#'+input_id).tagify({delimiters: [13,44], addTagPrompt: '{/literal}{l s='Add tag' js=1}{literal}'});
																$({/literal}'#{$table}{literal}_form').submit( function() {
																	$(this).find('#'+input_id).val($('#'+input_id).tagify('serialize'));
																});
															});
														</script>
													{/literal}
												{/if}
												{if isset($input.maxchar) || isset($input.prefix) || isset($input.suffix)}
												<div class="input-group {if isset($input.class)}{$input.class}{/if}">
												{/if}
												{if isset($input.maxchar)}
												<span id="{if isset($input.id)}{$input.id}_{$language.id_lang}{else}{$input.name}_{$language.id_lang}{/if}_counter" class="input-group-addon">
													<span class="text-count-down">{$input.maxchar}</span>
												</span>
												{/if}
												{if isset($input.prefix)}
													<span class="input-group-addon">
													  {$input.prefix}
													</span>
													{/if}
												<input type="text"
													id="{if isset($input.id)}{$input.id}_{$language.id_lang}{else}{$input.name}_{$language.id_lang}{/if}"
													name="{$input.name}_{$language.id_lang}"
													class="{if $input.type == 'tags'}tagify {/if}{if isset($input.class)}{$input.class}{/if}"
													value="{if isset($input.string_format) && $input.string_format}{$value_text|string_format:$input.string_format|escape:'html':'UTF-8'}{else}{$value_text|escape:'html':'UTF-8'}{/if}"
													onkeyup="if (isArrowKey(event)) return ;updateFriendlyURL();"
													{if isset($input.size)} size="{$input.size}"{/if}
													{if isset($input.maxchar)} data-maxchar="{$input.maxchar}"{/if}
													{if isset($input.maxlength)} maxlength="{$input.maxlength}"{/if}
													{if isset($input.readonly) && $input.readonly} readonly="readonly"{/if}
													{if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}
													{if isset($input.autocomplete) && !$input.autocomplete} autocomplete="off"{/if}
													{if isset($input.required) && $input.required} required="required" {/if}
													{if isset($input.placeholder) && $input.placeholder} placeholder="{$input.placeholder}"{/if} />
													{if isset($input.suffix)}
													<span class="input-group-addon">
													  {$input.suffix}
													</span>
													{/if}
												{if isset($input.maxchar) || isset($input.prefix) || isset($input.suffix)}
												</div>
												{/if}
										{if $languages|count > 1}
											</div>
											<div class="col-lg-2">
												<button type="button" class="btn btn-default dropdown-toggle" tabindex="-1" data-toggle="dropdown">
													{$language.iso_code}
													<i class="icon-caret-down"></i>
												</button>
												<ul class="dropdown-menu">
													{foreach from=$languages item=language}
													<li><a href="javascript:hideOtherLanguage({$language.id_lang});" tabindex="-1">{$language.name}</a></li>
													{/foreach}
												</ul>
											</div>
										</div>
										{/if}
									{/foreach}
									{if isset($input.maxchar)}
									<script type="text/javascript">
									function countDown($source, $target) {
										var max = $source.attr("data-maxchar");
										$target.html(max-$source.val().length);

										$source.keyup(function(){
											$target.html(max-$source.val().length);
										});
									}

									$(document).ready(function(){
									{foreach from=$languages item=language}
										countDown($("#{if isset($input.id)}{$input.id}_{$language.id_lang}{else}{$input.name}_{$language.id_lang}{/if}"), $("#{if isset($input.id)}{$input.id}_{$language.id_lang}{else}{$input.name}_{$language.id_lang}{/if}_counter"));
									{/foreach}
									});
									</script>
									{/if}
									{if $languages|count > 1}
									</div>
									{/if}
									{else}
										{if $input.type == 'tags'}
											{literal}
											<script type="text/javascript">
												$().ready(function () {
													var input_id = '{/literal}{if isset($input.id)}{$input.id}{else}{$input.name}{/if}{literal}';
													$('#'+input_id).tagify({delimiters: [13,44], addTagPrompt: '{/literal}{l s='Add tag'}{literal}'});
													$({/literal}'#{$table}{literal}_form').submit( function() {
														$(this).find('#'+input_id).val($('#'+input_id).tagify('serialize'));
													});
												});
											</script>
											{/literal}
										{/if}
										{assign var='value_text' value=$fields_value[$input.name]}
										{if isset($input.maxchar) || isset($input.prefix) || isset($input.suffix)}
										<div class="input-group {if isset($input.class)}{$input.class}{/if}">
										{/if}
										{if isset($input.maxchar)}
										<span id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}_counter" class="input-group-addon"><span class="text-count-down">{$input.maxchar}</span></span>
										{/if}
										{if isset($input.prefix)}
										<span class="input-group-addon">
										  {$input.prefix}
										</span>
										{/if}
										<input type="text"
											name="{$input.name}"
											id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}"
											value="{if isset($input.string_format) && $input.string_format}{$value_text|string_format:$input.string_format|escape:'html':'UTF-8'}{else}{$value_text|escape:'html':'UTF-8'}{/if}"
											class="{if $input.type == 'tags'}tagify {/if}{if isset($input.class)}{$input.class}{/if}"
											{if isset($input.size)} size="{$input.size}"{/if}
											{if isset($input.maxchar)} data-maxchar="{$input.maxchar}"{/if}
											{if isset($input.maxlength)} maxlength="{$input.maxlength}"{/if}
											{if isset($input.class)} class="{$input.class}"{/if}
											{if isset($input.readonly) && $input.readonly} readonly="readonly"{/if}
											{if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}
											{if isset($input.autocomplete) && !$input.autocomplete} autocomplete="off"{/if}
											{if isset($input.required) && $input.required } required="required" {/if}
											{if isset($input.placeholder) && $input.placeholder } placeholder="{$input.placeholder}"{/if}
											/>
										{if isset($input.suffix)}
										<span class="input-group-addon">
										  {$input.suffix}
										</span>
										{/if}
										
										{if isset($input.maxchar) || isset($input.prefix) || isset($input.suffix)}
										</div>
										{/if}
										{if isset($input.maxchar)}
										<script type="text/javascript">
										function countDown($source, $target) {
											var max = $source.attr("data-maxchar");
											$target.html(max-$source.val().length);

											$source.keyup(function(){
												$target.html(max-$source.val().length);
											});
										}
										$(document).ready(function(){
											countDown($("#{if isset($input.id)}{$input.id}{else}{$input.name}{/if}"), $("#{if isset($input.id)}{$input.id}{else}{$input.name}{/if}_counter"));
										});
										</script>
										{/if}
									{/if}
								{elseif $input.type == 'textbutton'}
									{assign var='value_text' value=$fields_value[$input.name]}
									<div class="row">
										<div class="col-lg-9">
										{if isset($input.maxchar)}
										<div class="input-group">
											<span id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}_counter" class="input-group-addon">
												<span class="text-count-down">{$input.maxchar}</span>
											</span>
										{/if}
										<input type="text"
											name="{$input.name}"
											id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}"
											value="{if isset($input.string_format) && $input.string_format}{$value_text|string_format:$input.string_format|escape:'html':'UTF-8'}{else}{$value_text|escape:'html':'UTF-8'}{/if}"
											class="{if $input.type == 'tags'}tagify {/if}{if isset($input.class)}{$input.class}{/if}"
											{if isset($input.size)} size="{$input.size}"{/if}
											{if isset($input.maxchar)} data-maxchar="{$input.maxchar}"{/if}
											{if isset($input.maxlength)} maxlength="{$input.maxlength}"{/if}
											{if isset($input.class)} class="{$input.class}"{/if}
											{if isset($input.readonly) && $input.readonly} readonly="readonly"{/if}
											{if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}
											{if isset($input.autocomplete) && !$input.autocomplete} autocomplete="off"{/if}
											{if isset($input.placeholder) && $input.placeholder } placeholder="{$input.placeholder}"{/if}
											/>
										{if isset($input.suffix)}{$input.suffix}{/if}
										{if isset($input.maxchar)}
										</div>
										{/if}
										</div>
										<div class="col-lg-2">
											<button type="button" class="btn btn-default{if isset($input.button.attributes['class'])} {$input.button.attributes['class']}{/if}{if isset($input.button.class)} {$input.button.class}{/if}"
												{foreach from=$input.button.attributes key=name item=value}
													{if $name|lower != 'class'}
													 {$name}="{$value}"
													{/if}
												{/foreach} >
												{$input.button.label}
											</button>
										</div>
									</div>
									{if isset($input.maxchar)}
									<script type="text/javascript">
										function countDown($source, $target) {
											var max = $source.attr("data-maxchar");
											$target.html(max-$source.val().length);
											$source.keyup(function(){
												$target.html(max-$source.val().length);
											});
										}
										$(document).ready(function() {
											countDown($("#{if isset($input.id)}{$input.id}{else}{$input.name}{/if}"), $("#{if isset($input.id)}{$input.id}{else}{$input.name}{/if}_counter"));
										});
									</script>
									{/if}
								{elseif $input.type == 'select'}
									{if isset($input.options.query) && !$input.options.query && isset($input.empty_message)}
										{$input.empty_message}
										{$input.required = false}
										{$input.desc = null}
									{else}
										<select style="position: relative;"
										name="{$input.name|escape:'html':'utf-8'}"
												class="{if isset($input.class)}{$input.class|escape:'html':'utf-8'}{/if} fixed-width-xl"
												id="{if isset($input.id)}{$input.id|escape:'html':'utf-8'}{else}{$input.name|escape:'html':'utf-8'}{/if}"
												{if isset($input.multiple)}multiple="multiple" {/if}
												{if isset($input.size)}size="{$input.size|escape:'html':'utf-8'}"{/if}
												{if isset($input.onchange)}onchange="{$input.onchange|escape:'html':'utf-8'}"{/if}>
											{if isset($input.options.default)}
												<option value="{$input.options.default.value|escape:'html':'utf-8'}">{$input.options.default.label|escape:'html':'utf-8'}</option>
											{/if}
											{if isset($input.options.optiongroup)}
												{foreach $input.options.optiongroup.query AS $optiongroup}
													<optgroup label="{$optiongroup[$input.options.optiongroup.label]}">
														{foreach $optiongroup[$input.options.options.query] as $option}
															<option value="{$option[$input.options.options.id]}"
																{if isset($input.multiple)}
																	{foreach $fields_value[$input.name] as $field_value}
																		{if $field_value == $option[$input.options.options.id]}selected="selected"{/if}
																	{/foreach}
																{else}
																	{if $fields_value[$input.name] == $option[$input.options.options.id]}selected="selected"{/if}
																{/if}
															>{$option[$input.options.options.name]}</option>
														{/foreach}
													</optgroup>
												{/foreach}
											{else}
												{foreach $input.options.query AS $option}
													{if is_object($option)}
														<option value="{$option->$input.options.id}"
															{if isset($input.multiple)}
																{foreach $fields_value[$input.name] as $field_value}
																	{if $field_value == $option->$input.options.id}
																		selected="selected"
																	{/if}
																{/foreach}
															{else}
																{if $fields_value[$input.name] == $option->$input.options.id}
																	selected="selected"
																{/if}
															{/if}
														>{$option->$input.options.name}</option>
													{elseif $option == "-"}
														<option value="">-</option>
													{else}
														<option value="{$option[$input.options.id]}"
															{if isset($input.multiple)}
																{foreach $fields_value[$input.name] as $field_value}
																	{if $field_value == $option[$input.options.id]}
																		selected="selected"
																	{/if}
																{/foreach}
															{else}
																{if $fields_value[$input.name] == $option[$input.options.id]}
																	selected="selected"
																{/if}
															{/if}
														>{$option[$input.options.name]}</option>

													{/if}
												{/foreach}
											{/if}
										</select>
										{if $input.image == "true" && !empty($models)}
											<img src="" width="100" id="display_cover" style="position:absolute; top:0; left:270px;" class="imgm img-thumbnail"/>
											<script>
											$(document).ready(function(){
												$('#display_cover').attr('src',('{$models}' + $("#model option:selected").val()));
												$('#model').change(function() {
													$('#display_cover').attr('src',('{$models}' + $('#model').val()));
													document.getElementById('drop-pos_code').style.backgroundImage ='url({$models}' + $('#model').val() +')';
													document.getElementById('drop-pos_text').style.backgroundImage ='url({$models}' + $('#model').val() +')';
												});
											});
											</script>											
										{/if}
									{/if}
								{elseif $input.type == 'radio'}
									{foreach $input.values as $value}
										<div class="radio {if isset($input.class)}{$input.class}{/if}">
											{strip}
											<label>
											<input type="radio"	name="{$input.name}" id="{$value.id}" value="{$value.value|escape:'html':'UTF-8'}"
												{if $fields_value[$input.name] == $value.value}checked="checked"{/if}
												{if isset($input.disabled) && $input.disabled}disabled="disabled"{/if} />
												{$value.label}
											</label>
											{/strip}
										</div>
										{if isset($value.p) && $value.p}<p class="help-block">{$value.p}</p>{/if}
									{/foreach}
								{elseif $input.type == 'switch'}
									<span class="switch prestashop-switch fixed-width-lg">
										{foreach $input.values as $value}
										<input
											type="radio"
											name="{$input.name}"
											{if $value.value == 1}
												id="{$input.name}_on"
											{else}
												id="{$input.name}_off"
											{/if}
											value="{$value.value}"
											{if $fields_value[$input.name] == $value.value}checked="checked"{/if}
											{if isset($input.disabled) && $input.disabled}disabled="disabled"{/if}
										/>
										{strip}
										<label {if $value.value == 1}
												for="{$input.name}_on"
											{else}
												for="{$input.name}_off"
											{/if}>
											{if $value.value == 1}
												{l s='Yes'}
											{else}
												{l s='No'}
											{/if}
										</label>
										{/strip}
										{/foreach}
										<a class="slide-button btn"></a>
									</span>									
								{elseif $input.type == 'textarea'}
									{assign var=use_textarea_autosize value=true}
									{if isset($input.lang) AND $input.lang}
									{foreach $languages as $language}
									{if $languages|count > 1}
									<div class="form-group translatable-field lang-{$language.id_lang}"  {if $language.id_lang != $defaultFormLanguage}style="display:none;"{/if}>

										<div class="col-lg-9">
									{/if}
											<textarea name="{$input.name}_{$language.id_lang}" class="{if isset($input.autoload_rte) && $input.autoload_rte}rte autoload_rte {if isset($input.class)}{$input.class}{/if}{else}{if isset($input.class)}{$input.class}{else}textarea-autosize{/if}{/if}" >{$fields_value[$input.name][$language.id_lang]|escape:'html':'UTF-8'}</textarea>
									{if $languages|count > 1}	
										</div>
										<div class="col-lg-2">
											<button type="button" class="btn btn-default dropdown-toggle" tabindex="-1" data-toggle="dropdown">
												{$language.iso_code}
												<span class="caret"></span>
											</button>
											<ul class="dropdown-menu">
												{foreach from=$languages item=language}
												<li>
													<a href="javascript:hideOtherLanguage({$language.id_lang});" tabindex="-1">{$language.name}</a>
												</li>
												{/foreach}
											</ul>
										</div>
									</div>
									{/if}
									{/foreach}

									{else}
										<textarea name="{$input.name}" id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}" {if isset($input.cols)}cols="{$input.cols}"{/if} {if isset($input.rows)}rows="{$input.rows}"{/if} class="{if isset($input.autoload_rte) && $input.autoload_rte}rte autoload_rte {if isset($input.class)}{$input.class}{/if}{else}textarea-autosize{/if}">{$fields_value[$input.name]|escape:'html':'UTF-8'}</textarea>
									{/if}

								{elseif $input.type == 'checkbox'}
									{if isset($input.expand)}
										<a class="btn btn-default show_checkbox{if strtolower($input.expand.default) == 'hide'} hidden {/if}" href="#">
											<i class="icon-{$input.expand.show.icon}"></i>
											{$input.expand.show.text}
											{if isset($input.expand.print_total) && $input.expand.print_total > 0}
												<span class="badge">{$input.expand.print_total}</span>
											{/if}
										</a>
										<a class="btn btn-default hide_checkbox{if strtolower($input.expand.default) == 'show'} hidden {/if}" href="#">
											<i class="icon-{$input.expand.hide.icon}"></i>
											{$input.expand.hide.text}
											{if isset($input.expand.print_total) && $input.expand.print_total > 0}
												<span class="badge">{$input.expand.print_total}</span>
											{/if}
										</a>
									{/if}
									{foreach $input.values.query as $value}
										{assign var=id_checkbox value=$input.name|cat:'_'|cat:$value[$input.values.id]}
										<div class="checkbox{if isset($input.expand) && strtolower($input.expand.default) == 'show'} hidden {/if}">
											{strip}
												<label for="{$id_checkbox}">
													<input type="checkbox"
														name="{$id_checkbox}"
														id="{$id_checkbox}"
														class="{if isset($input.class)}{$input.class}{/if}"
														{if isset($value.val)}value="{$value.val|escape:'html':'UTF-8'}"{/if}
														{if isset($fields_value[$id_checkbox]) && $fields_value[$id_checkbox]}checked="checked"{/if} />
													{$value[$input.values.name]}
												</label>
											{/strip}
										</div>
									{/foreach}
								{elseif $input.type == 'change-password'}
									<div class="row">
										<div class="col-lg-12">
											<button type="button" id="{$input.name}-btn-change" class="btn btn-default">
												<i class="icon-lock"></i>
												{l s='Change password...'}
											</button>
											<div id="{$input.name}-change-container" class="form-password-change well hide">
												<div class="form-group">
													<label for="old_passwd" class="control-label col-lg-2 required">
														{l s='Current password'}
													</label>
													<div class="col-lg-10">
														<div class="input-group fixed-width-lg">
															<span class="input-group-addon">
																<i class="icon-unlock"></i>
															</span>
															<input type="password" id="old_passwd" name="old_passwd" class="form-control" value="" required="required" autocomplete="off">
														</div>
													</div>
												</div>
												<hr>
												<div class="form-group">
													<label for="{$input.name}" class="required control-label col-lg-2">
														<span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="Minimum of 8 characters.">						
															{l s='New password'}
														</span>
													</label>
													<div class="col-lg-9">
														<div class="input-group fixed-width-lg">
															<span class="input-group-addon">
																<i class="icon-key"></i>
															</span>
															<input type="password" id="{$input.name}" name="{$input.name}" class="{if isset($input.class)}{$input.class}{/if}" value="" required="required" autocomplete="off"/>
														</div>
														<span id="{$input.name}-output"></span>
													</div>
												</div>
												<div class="form-group">
													<label for="{$input.name}2" class="required control-label col-lg-2">
														{l s='Confirm password'}
													</label>
													<div class="col-lg-4">
														<div class="input-group fixed-width-lg">
															<span class="input-group-addon">
																<i class="icon-key"></i>
															</span>
															<input type="password" id="{$input.name}2" name="{$input.name}2" class="{if isset($input.class)}{$input.class}{/if}" value="" autocomplete="off"/>
														</div>
													</div>
												</div>
												<div class="form-group">
													<div class="col-lg-10 col-lg-offset-2">
														<input type="text" class="form-control fixed-width-md pull-left" id="{$input.name}-generate-field" disabled="disabled">
														<button type="button" id="{$input.name}-generate-btn" class="btn btn-default">
															<i class="icon-random"></i>
															{l s='Generate password'}
														</button>
													</div>
												</div>
												<div class="form-group">
													<div class="col-lg-10 col-lg-offset-2">
														<p class="checkbox">
															<label for="{$input.name}-checkbox-mail">
																<input name="passwd_send_email" id="{$input.name}-checkbox-mail" type="checkbox" checked="checked">
																{l s='Send me this new password by Email'}
															</label>
														</p>
													</div>
												</div>
												<div class="row">
													<div class="col-lg-12">
														<button type="button" id="{$input.name}-cancel-btn" class="btn btn-default">
															<i class="icon-remove"></i>
															{l s='Cancel'}
														</button>
													</div>
												</div>
											</div>
										</div>
									</div>
									<script>
										$(function(){
											var $oldPwd = $('#old_passwd');
											var $passwordField = $('#{$input.name}');
											var $output = $('#{$input.name}-output');
											var $generateBtn = $('#{$input.name}-generate-btn');
											var $generateField = $('#{$input.name}-generate-field');
											var $cancelBtn = $('#{$input.name}-cancel-btn');
											
											var feedback = [
												{ badge: 'text-danger', text: '{l s="Invalid" js=1}' },
												{ badge: 'text-warning', text: '{l s="Okay" js=1}' },
												{ badge: 'text-success', text: '{l s="Good" js=1}' },
												{ badge: 'text-success', text: '{l s="Fabulous" js=1}' }
											];
											$.passy.requirements.length.min = 8;
											$.passy.requirements.characters = 'DIGIT';
											$passwordField.passy(function(strength, valid) {
												$output.text(feedback[strength].text);
												$output.removeClass('text-danger').removeClass('text-warning').removeClass('text-success');
												$output.addClass(feedback[strength].badge);
												if (valid){
													$output.show();
												}
												else {
													$output.hide();
												}
											});
											var $container = $('#{$input.name}-change-container');
											var $changeBtn = $('#{$input.name}-btn-change');
											var $confirmPwd = $('#{$input.name}2');

											$changeBtn.on('click',function(){
												$container.removeClass('hide');
												$changeBtn.addClass('hide');
											});
											$generateBtn.click(function() {
												$generateField.passy( 'generate', 8 );
												var generatedPassword = $generateField.val();
												$passwordField.val(generatedPassword);
												$confirmPwd.val(generatedPassword);
											});
											$cancelBtn.on('click',function() {
												$container.find("input").val("");
												$container.addClass('hide');
												$changeBtn.removeClass('hide');
											});

											$.validator.addMethod('password_same', function(value, element) {
												return $passwordField.val() == $confirmPwd.val();
											}, '{l s="Invalid password confirmation" js=1}');

											$('#employee_form').validate({
												rules: {
													"email": {
														email: true
													},
													"{$input.name}" : {
														minlength: 8
													},
													"{$input.name}2": {
														password_same: true
													},
													"old_passwd" : {},
												},
												// override jquery validate plugin defaults for bootstrap 3
												highlight: function(element) {
													$(element).closest('.form-group').addClass('has-error');
												},
												unhighlight: function(element) {
													$(element).closest('.form-group').removeClass('has-error');
												},
												errorElement: 'span',
												errorClass: 'help-block',
												errorPlacement: function(error, element) {
													if(element.parent('.input-group').length) {
														error.insertAfter(element.parent());
													} else {
														error.insertAfter(element);
													}
												}
											});
										});
									</script>
								{elseif $input.type == 'password'}
									<div class="input-group fixed-width-lg">
										<span class="input-group-addon">
											<i class="icon-key"></i>
										</span>
										<input type="password"
											id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}"
											name="{$input.name}"
											class="{if isset($input.class)}{$input.class}{/if}"
											value=""
											{if isset($input.autocomplete) && !$input.autocomplete}autocomplete="off"{/if}
											{if isset($input.required) && $input.required } required="required" {/if} />
									</div>

								{elseif $input.type == 'birthday'}
								<div class="form-group">
									{foreach $input.options as $key => $select}
									<div class="col-lg-2">
										<select name="{$key}" class="{if isset($input.class)}{$input.class}{/if}" class="fixed-width-lg">
											<option value="">-</option>
											{if $key == 'months'}
												{*
													This comment is useful to the translator tools /!\ do not remove them
													{l s='January'}
													{l s='February'}
													{l s='March'}
													{l s='April'}
													{l s='May'}
													{l s='June'}
													{l s='July'}
													{l s='August'}
													{l s='September'}
													{l s='October'}
													{l s='November'}
													{l s='December'}
												*}
												{foreach $select as $k => $v}
													<option value="{$k}" {if $k == $fields_value[$key]}selected="selected"{/if}>{l s=$v}</option>
												{/foreach}
											{else}
												{foreach $select as $v}
													<option value="{$v}" {if $v == $fields_value[$key]}selected="selected"{/if}>{$v}</option>
												{/foreach}
											{/if}
										</select>
									</div>
									{/foreach}
								</div>
								{elseif $input.type == 'group'}
									{assign var=groups value=$input.values}
									{include file='helpers/form/form_group.tpl'}
								{elseif $input.type == 'shop'}
									{$input.html}
								{elseif $input.type == 'categories'}
									{$categories_tree}
								{elseif $input.type == 'file'}
									{$input.file}
								{elseif $input.type == 'categories_select'}
									{$input.category_tree}
								{elseif $input.type == 'asso_shop' && isset($asso_shop) && $asso_shop}
									{$asso_shop}
								{elseif $input.type == 'color'}
								<div class="form-group">
									<div class="col-lg-2">
										<div class="row">
											<div class="input-group">
												<input type="color"
												data-hex="true"
												{if isset($input.class)}class="{$input.class}"
												{else}class="color mColorPickerInput"{/if}
												name="{$input.name}"
												value="{$fields_value[$input.name]|escape:'html':'UTF-8'}" />
											</div>
										</div>
									</div>
								</div>
								{elseif $input.type == 'date'}
									<div class="row">
										<div class="input-group col-lg-4">
											<input
												id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}"
												type="text"
												data-hex="true"
												{if isset($input.class)}class="{$input.class}"
												{else}class="datepicker"{/if}
												name="{$input.name}"
												value="{$fields_value[$input.name]|escape:'html':'UTF-8'}" />
											<span class="input-group-addon">
												<i class="icon-calendar-empty"></i>
											</span>
										</div>
									</div>
								{elseif $input.type == 'datetime'}
									<div class="row">
										<div class="input-group col-lg-4">
											<input
												id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}"
												type="text"
												data-hex="true"
												{if isset($input.class)}class="{$input.class}"
												{else}class="datetimepicker"{/if}
												name="{$input.name}"
												value="{$fields_value[$input.name]|escape:'html':'UTF-8'}" />
											<span class="input-group-addon">
												<i class="icon-calendar-empty"></i>
											</span>
										</div>
									</div>
								{elseif $input.type == 'free'}
									{$fields_value[$input.name]}
								{elseif $input.type == 'html'}
									{if isset($input.html_content)}
										{$input.html_content}
									{else}
										{$input.name}
									{/if}
								{elseif $input.type == 'position'}
									<div id="card-{$input.name}">										
										<div id="drop-{$input.name}" class="ui-widget-content">
											<div id="drag-{$input.name}" class="ui-widget-content">
 											<p>{$input.text}</p>
											</div>  											
										</div>
									</div>																										
								{/if}
								{/block}{* end block input *}
								{block name="description"}
									{if isset($input.desc) && !empty($input.desc)}
										<p class="help-block">
											{if is_array($input.desc)}
												{foreach $input.desc as $p}
													{if is_array($p)}
														<span id="{$p.id}">{$p.text}</span><br />
													{else}
														{$p}<br />
													{/if}
												{/foreach}
											{else}
												{$input.desc}
											{/if}
										</p>
									{/if}									
								{/block}
								</div>								
							{/block}{* end block field *}
						{/if}
						</div>
						{/block}						
					{/foreach}					
					{hook h='displayAdminForm' fieldset=$f}
					{if isset($name_controller)}
						{capture name=hookName assign=hookName}display{$name_controller|ucfirst}Form{/capture}
						{hook h=$hookName fieldset=$f}
					{elseif isset($smarty.get.controller)}
						{capture name=hookName assign=hookName}display{$smarty.get.controller|ucfirst|htmlentities}Form{/capture}
						{hook h=$hookName fieldset=$f}
					{/if}
				</div><!-- /.form-wrapper -->
				{elseif $key == 'desc'}
					<div class="alert alert-info col-lg-offset-3">
						{if is_array($field)}
							{foreach $field as $k => $p}
								{if is_array($p)}
									<span{if isset($p.id)} id="{$p.id}"{/if}>{$p.text}</span><br />
								{else}
									{$p}
									{if isset($field[$k+1])}<br />{/if}
								{/if}
							{/foreach}
						{else}
							{$field}
						{/if}
					</div>
				{/if}
				{block name="other_input"}{/block}
			{/foreach}
			{block name="footer"}
				{if isset($fieldset['form']['submit']) || isset($fieldset['form']['buttons'])}
					<div class="panel-footer">
						{if isset($fieldset['form']['submit']) && !empty($fieldset['form']['submit'])}
						<button
							type="submit"
							value="1"
							id="{if isset($fieldset['form']['submit']['id'])}{$fieldset['form']['submit']['id']}{else}{$table}_form_submit_btn{/if}"
							name="{if isset($fieldset['form']['submit']['name'])}{$fieldset['form']['submit']['name']}{else}{$submit_action}{/if}{if isset($fieldset['form']['submit']['stay']) && $fieldset['form']['submit']['stay']}AndStay{/if}"
							class="{if isset($fieldset['form']['submit']['class'])}{$fieldset['form']['submit']['class']}{else}btn btn-default pull-right{/if}"
							>
							<i class="{if isset($fieldset['form']['submit']['icon'])}{$fieldset['form']['submit']['icon']}{else}process-icon-save{/if}"></i> {$fieldset['form']['submit']['title']}
						</button>
						{/if}
						{if isset($show_cancel_button) && $show_cancel_button}
						<a href="{$back_url}" class="btn btn-default" onclick="window.history.back()">
							<i class="process-icon-cancel"></i> {l s='Cancel'}
						</a>
						{/if}
						{if isset($fieldset['form']['reset'])}
						<button
							type="reset"
							id="{if isset($fieldset['form']['reset']['id'])}{$fieldset['form']['reset']['id']}{else}{$table}_form_reset_btn{/if}"
							class="{if isset($fieldset['form']['reset']['class'])}{$fieldset['form']['reset']['class']}{else}btn btn-default{/if}"
							>
							{if isset($fieldset['form']['reset']['icon'])}<i class="{$fieldset['form']['reset']['icon']}"></i> {/if} {$fieldset['form']['reset']['title']}
						</button>
						{/if}
						{if isset($fieldset['form']['buttons'])}
						{foreach from=$fieldset['form']['buttons'] item=btn key=k}
							{if isset($btn.href) && trim($btn.href) != ''}
								<a href="{$btn.href}" {if isset($btn['id'])}id="{$btn['id']}"{/if} class="btn btn-default{if isset($btn['class'])} {$btn['class']}{/if}" {if isset($btn.js) && $btn.js} onclick="{$btn.js}"{/if}>{if isset($btn['icon'])}<i class="{$btn['icon']}" ></i> {/if}{$btn.title}</a>
							{else}
								<button type="{if isset($btn['type'])}{$btn['type']}{else}button{/if}" {if isset($btn['id'])}id="{$btn['id']}"{/if} class="btn btn-default{if isset($btn['class'])} {$btn['class']}{/if}" name="{if isset($btn['name'])}{$btn['name']}{else}submitOptions{$table}{/if}"{if isset($btn.js) && $btn.js} onclick="{$btn.js}"{/if}>{if isset($btn['icon'])}<i class="{$btn['icon']}" ></i> {/if}{$btn.title}</button>
							{/if}
						{/foreach}
						{/if}
					</div>
				{/if}
			{/block}
		</div>
		{/block}
		{block name="other_fieldsets"}{/block}
	{/foreach}
</form>
{/block}
{block name="after"}{/block}

{if isset($tinymce) && $tinymce}
<script type="text/javascript">
	var iso = '{$iso|addslashes}';
	var pathCSS = '{$smarty.const._THEME_CSS_DIR_|addslashes}';
	var ad = '{$ad|addslashes}';

	$(document).ready(function(){
		{block name="autoload_tinyMCE"}
			tinySetup({
				editor_selector :"autoload_rte"
			});
		{/block}
	});
</script>
{/if}
{if $firstCall}
	<script type="text/javascript">
		var module_dir = '{$smarty.const._MODULE_DIR_}';
		var id_language = {$defaultFormLanguage|intval};
		var languages = new Array();
		var vat_number = {if $vat_number}1{else}0{/if};
		// Multilang field setup must happen before document is ready so that calls to displayFlags() to avoid
		// precedence conflicts with other document.ready() blocks
		{foreach $languages as $k => $language}
			languages[{$k}] = {
				id_lang: {$language.id_lang},
				iso_code: '{$language.iso_code}',
				name: '{$language.name}',
				is_default: '{$language.is_default}'
			};
		{/foreach}
		// we need allowEmployeeFormLang var in ajax request
		allowEmployeeFormLang = {$allowEmployeeFormLang|intval};
		displayFlags(languages, id_language, allowEmployeeFormLang);

		$(document).ready(function() {

			$(".show_checkbox").click(function () {
				$(this).addClass('hidden')
				$(this).siblings('.checkbox').removeClass('hidden');
				$(this).siblings('.hide_checkbox').removeClass('hidden');
				return false;
			});
			$(".hide_checkbox").click(function () {
				$(this).addClass('hidden')
				$(this).siblings('.checkbox').addClass('hidden');
				$(this).siblings('.show_checkbox').removeClass('hidden');
				return false;
			});

			{if isset($fields_value.id_state)}
				if ($('#id_country') && $('#id_state'))
				{
					ajaxStates({$fields_value.id_state});
					$('#id_country').change(function() {
						ajaxStates();
					});
				}
			{/if}

			if ($(".datepicker").length > 0)
				$(".datepicker").datepicker({
					prevText: '',
					nextText: '',
					dateFormat: 'yy-mm-dd'
				});

			if ($(".datetimepicker").length > 0)
			$('.datetimepicker').datetimepicker({
				prevText: '',
				nextText: '',
				dateFormat: 'yy-mm-dd',
				// Define a custom regional settings in order to use PrestaShop translation tools
				currentText: '{l s='Now'}',
				closeText: '{l s='Done'}',
				ampm: false,
				amNames: ['AM', 'A'],
				pmNames: ['PM', 'P'],
				timeFormat: 'hh:mm:ss tt',
				timeSuffix: '',
				timeOnlyTitle: '{l s='Choose Time' js=1}',
				timeText: '{l s='Time' js=1}',
				hourText: '{l s='Hour' js=1}',
				minuteText: '{l s='Minute' js=1}',
			});
			{if isset($use_textarea_autosize)}
			$(".textarea-autosize").autosize();
			{/if}
		});
	state_token = '{getAdminToken tab='AdminStates'}';
	{block name="script"}{/block}
	</script>
{/if}
<!-- end template for 1.6+ -->
{else}
<!-- start template for 1.5 -->
{if $show_toolbar}
	{include file="toolbar.tpl" toolbar_btn=$toolbar_btn toolbar_scroll=$toolbar_scroll title=$title}
	<div class="leadin">{block name="leadin"}{/block}</div>
{/if}

{if isset($fields.title)}<h2>{$fields.title}</h2>{/if}
{block name="defaultForm"}
<form id="{if isset($fields.form.form.id_form)}{$fields.form.form.id_form|escape:'htmlall':'UTF-8'}{else}{if $table == null}configuration_form{else}{$table}_form{/if}{/if}" class="defaultForm {$name_controller}" action="{$current}&{if !empty($submit_action)}{$submit_action}=1{/if}&token={$token}" method="post" enctype="multipart/form-data" {if isset($style)}style="{$style}"{/if}>
	{if $form_id}
		<input type="hidden" name="{$identifier}" id="{$identifier}" value="{$form_id}" />
	{/if}
	{foreach $fields as $f => $fieldset}
		<fieldset id="fieldset_{$f}">
			{foreach $fieldset.form as $key => $field}
				{if $key == 'legend'}
					<legend>
						{if isset($field.image)}<img src="{$field.image}" alt="{$field.title|escape:'htmlall':'UTF-8'}" />{/if}
						{$field.title}
					</legend>
				{elseif $key == 'description' && $field}
					<p class="description">{$field}</p>
				{elseif $key == 'input'}
					{foreach $field as $input}
						{if $input.type == 'hidden'}
							<input type="hidden" name="{$input.name}" id="{$input.name}" value="{$fields_value[$input.name]|escape:'htmlall':'UTF-8'}" />
						{else}
							{if $input.name == 'id_state'}
								<div id="contains_states" {if !$contains_states}style="display:none;"{/if}>
							{/if}
							{block name="label"}
								{if isset($input.label)}<label class="{if isset($input.form_group_class)} {$input.form_group_class}{/if}">{$input.label} </label>{/if}
							{/block}
							{block name="field"}
								<div class="margin-form{if isset($input.form_group_class)} {$input.form_group_class}{/if}">
								{block name="input"}
								{if $input.type == 'text' || $input.type == 'tags'}
									{if isset($input.lang) AND $input.lang}
										<div class="translatable">
											{foreach $languages as $language}
												<div class="lang_{$language.id_lang}" style="display:{if $language.id_lang == $defaultFormLanguage}block{else}none{/if}; float: left;">
													{if $input.type == 'tags'}
														{literal}
														<script type="text/javascript">
															$().ready(function () {
																var input_id = '{/literal}{if isset($input.id)}{$input.id}_{$language.id_lang}{else}{$input.name}_{$language.id_lang}{/if}{literal}';
																$('#'+input_id).tagify({delimiters: [13,44], addTagPrompt: '{/literal}{l s='Add tag' js=1}{literal}'});
																$({/literal}'#{$table}{literal}_form').submit( function() {
																	$(this).find('#'+input_id).val($('#'+input_id).tagify('serialize'));
																});
															});
														</script>
														{/literal}
													{/if}
													{assign var='value_text' value=$fields_value[$input.name][$language.id_lang]}
													<input type="text"
															name="{$input.name}_{$language.id_lang}"
															id="{if isset($input.id)}{$input.id}_{$language.id_lang}{else}{$input.name}_{$language.id_lang}{/if}"
															value="{if isset($input.string_format) && $input.string_format}{$value_text|string_format:$input.string_format|escape:'htmlall':'UTF-8'}{else}{$value_text|escape:'htmlall':'UTF-8'}{/if}"
															class="{if $input.type == 'tags'}tagify {/if}{if isset($input.class)}{$input.class}{/if}"
															{if isset($input.size)}size="{$input.size}"{/if}
															{if isset($input.maxlength)}maxlength="{$input.maxlength}"{/if}
															{if isset($input.readonly) && $input.readonly}readonly="readonly"{/if}
															{if isset($input.disabled) && $input.disabled}disabled="disabled"{/if}
															{if isset($input.autocomplete) && !$input.autocomplete}autocomplete="off"{/if} />
													{if !empty($input.hint)}<span class="hint" name="help_box">{$input.hint}<span class="hint-pointer">&nbsp;</span></span>{/if}
												</div>
											{/foreach}
										</div>
									{else}
										{if $input.type == 'tags'}
											{literal}
											<script type="text/javascript">
												$().ready(function () {
													var input_id = '{/literal}{if isset($input.id)}{$input.id}{else}{$input.name}{/if}{literal}';
													$('#'+input_id).tagify({delimiters: [13,44], addTagPrompt: '{/literal}{l s='Add tag'}{literal}'});
													$({/literal}'#{$table}{literal}_form').submit( function() {
														$(this).find('#'+input_id).val($('#'+input_id).tagify('serialize'));
													});
												});
											</script>
											{/literal}
										{/if}
										{assign var='value_text' value=$fields_value[$input.name]}
										<input type="text"
												name="{$input.name}"
												id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}"
												value="{if isset($input.string_format) && $input.string_format}{$value_text|string_format:$input.string_format|escape:'htmlall':'UTF-8'}{else}{$value_text|escape:'htmlall':'UTF-8'}{/if}"
												class="{if $input.type == 'tags'}tagify {/if}{if isset($input.class)}{$input.class}{/if}"
												{if isset($input.size)}size="{$input.size}"{/if}
												{if isset($input.maxlength)}maxlength="{$input.maxlength}"{/if}
												{if isset($input.class)}class="{$input.class}"{/if}
												{if isset($input.readonly) && $input.readonly}readonly="readonly"{/if}
												{if isset($input.disabled) && $input.disabled}disabled="disabled"{/if}
												{if isset($input.autocomplete) && !$input.autocomplete}autocomplete="off"{/if} />
										{if isset($input.suffix)}{$input.suffix}{/if}
										{if !empty($input.hint)}<span class="hint" name="help_box">{$input.hint}<span class="hint-pointer">&nbsp;</span></span>{/if}
									{/if}
								{elseif $input.type == 'select'}
								
									{if isset($input.options.query) && !$input.options.query && isset($input.empty_message)}
										{$input.empty_message}
										{$input.required = false}
										{$input.desc = null}
										
									{else}
										<select style=" vertical-align: top; " name="{$input.name}" class="{if isset($input.class)}{$input.class}{/if}"
												id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}"
												{if isset($input.multiple)}multiple="multiple" {/if}
												{if isset($input.size)}size="{$input.size}"{/if}
												{if isset($input.onchange)}onchange="{$input.onchange}"{/if}>
											{if isset($input.options.default)}
												<option value="{$input.options.default.value}">{$input.options.default.label}</option>
											{/if}
											{if isset($input.options.optiongroup)}
												{foreach $input.options.optiongroup.query AS $optiongroup}
													<optgroup label="{$optiongroup[$input.options.optiongroup.label]}">
														{foreach $optiongroup[$input.options.options.query] as $option}
															<option value="{$option[$input.options.options.id]}"
																{if isset($input.multiple)}
																	{foreach $fields_value[$input.name] as $field_value}
																		{if $field_value == $option[$input.options.options.id]}selected="selected"{/if}
																	{/foreach}
																{else}
																	{if $fields_value[$input.name] == $option[$input.options.options.id]}selected="selected"{/if}
																{/if}
															>{$option[$input.options.options.name]}</option>
														{/foreach}
													</optgroup>
												{/foreach}
											{else}
												{foreach $input.options.query AS $option}
													{if is_object($option)}
														<option value="{$option->$input.options.id}"
															{if isset($input.multiple)}
																{foreach $fields_value[$input.name] as $field_value}
																	{if $field_value == $option->$input.options.id}
																		selected="selected"
																	{/if}
																{/foreach}
															{else}
																{if $fields_value[$input.name] == $option->$input.options.id}
																	selected="selected"
																{/if}
															{/if}
														>{$option->$input.options.name}</option>
													{elseif $option == "-"}
														<option value="">-</option>
													{else}
														<option value="{$option[$input.options.id]}"
															{if isset($input.multiple)}
																{foreach $fields_value[$input.name] as $field_value}
																	{if $field_value == $option[$input.options.id]}
																		selected="selected"
																	{/if}
																{/foreach}
															{else}
																{if $fields_value[$input.name] == $option[$input.options.id]}
																	selected="selected"
																{/if}
															{/if}
														>{$option[$input.options.name]}</option>

													{/if}
												{/foreach}
											{/if}
										</select>
										{if !empty($models)}
											<img src="" width="100" id="display_cover" style="top:0; left:270px;" class="imgm img-thumbnail"/>
											<script>
											$(document).ready(function(){
												$('#display_cover').attr('src',('{$models}' + $("#model option:selected").val()));
												$('#model').change(function() {
													$('#display_cover').attr('src',('{$models}' + $('#model').val()));
													document.getElementById('drop-pos_code').style.backgroundImage ='url({$models}' + $('#model').val() +')';
													document.getElementById('drop-pos_text').style.backgroundImage ='url({$models}' + $('#model').val() +')';
												});
											});
											</script>											
										{/if}
										{if !empty($input.hint)}<span class="hint" name="help_box">{$input.hint}<span class="hint-pointer">&nbsp;</span></span>{/if}
									{/if}
								{elseif $input.type == 'radio'}
									{foreach $input.values as $value}
										<input type="radio" name="{$input.name}" id="{$value.id}" value="{$value.value|escape:'htmlall':'UTF-8'}"
												{if $fields_value[$input.name] == $value.value}checked="checked"{/if}
												{if isset($input.disabled) && $input.disabled}disabled="disabled"{/if} />
										<label {if isset($input.class)}class="{$input.class}"{/if} for="{$value.id}">
										 {if isset($input.is_bool) && $input.is_bool == true}
											{if $value.value == 1}
												<img src="../img/admin/enabled.gif" alt="{$value.label}" title="{$value.label}" />
											{else}
												<img src="../img/admin/disabled.gif" alt="{$value.label}" title="{$value.label}" />
											{/if}
										 {else}
											{$value.label}
										 {/if}
										</label>
										{if isset($input.br) && $input.br}<br />{/if}
										{if isset($value.p) && $value.p}<p>{$value.p}</p>{/if}
									{/foreach}
								{elseif $input.type == 'switch'}
									{foreach $input.values as $value}
											<input
											type="radio"
											name="{$input.name}"
											{if $value.value == 1}
												id="{$input.name}_on"
											{else}
												id="{$input.name}_off"
											{/if}
											value="{$value.value}"
											{if $fields_value[$input.name] == $value.value}checked="checked"{/if}
											{if isset($input.disabled) && $input.disabled}disabled="disabled"{/if}
										/>
										<label {if isset($input.class)}class="{$input.class}"{/if} for="{$value.id}">
										 {if isset($input.is_bool) && $input.is_bool == true}
											{if $value.value == 1}
												<img src="../img/admin/enabled.gif" alt="{$value.label}" title="{$value.label}" />
											{else}
												<img src="../img/admin/disabled.gif" alt="{$value.label}" title="{$value.label}" />
											{/if}
										 {else}
											{$value.label}
										 {/if}
										</label>
										{if isset($input.br) && $input.br}<br />{/if}
										{if isset($value.p) && $value.p}<p>{$value.p}</p>{/if}
									{/foreach}
								{elseif $input.type == 'textarea'}
									{if isset($input.lang) AND $input.lang}
										<div class="translatable">
											{foreach $languages as $language}
												<div class="lang_{$language.id_lang}" id="{$input.name}_{$language.id_lang}" style="display:{if $language.id_lang == $defaultFormLanguage}block{else}none{/if}; float: left;">
													<textarea cols="{$input.cols}" rows="{$input.rows}" name="{$input.name}_{$language.id_lang}" {if isset($input.autoload_rte) && $input.autoload_rte}class="rte autoload_rte {if isset($input.class)}{$input.class}{/if}"{/if} >{$fields_value[$input.name][$language.id_lang]|escape:'htmlall':'UTF-8'}</textarea>
												</div>
											{/foreach}
										</div>
									{else}
										<textarea name="{$input.name}" id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}" cols="{$input.cols}" rows="{$input.rows}" {if isset($input.autoload_rte) && $input.autoload_rte}class="rte autoload_rte {if isset($input.class)}{$input.class}{/if}"{/if}>{$fields_value[$input.name]|escape:'htmlall':'UTF-8'}</textarea>
									{/if}
								{elseif $input.type == 'checkbox'}
									{foreach $input.values.query as $value}
										{assign var=id_checkbox value=$input.name|cat:'_'|cat:$value[$input.values.id]}
										<input type="checkbox"
											name="{$id_checkbox}"
											id="{$id_checkbox}"
											class="{if isset($input.class)}{$input.class}{/if}"
											{if isset($value.val)}value="{$value.val|escape:'htmlall':'UTF-8'}"{/if}
											{if isset($fields_value[$id_checkbox]) && $fields_value[$id_checkbox]}checked="checked"{/if} />
										<label for="{$id_checkbox}" class="t"><strong>{$value[$input.values.name]}</strong></label><br />
									{/foreach}
								{elseif $input.type == 'file'}
									{if isset($input.display_image) && $input.display_image}
										{if isset($fields_value[$input.name].image) && $fields_value[$input.name].image}
											<div id="image">
												{$fields_value[$input.name].image}
												<p align="center">{l s='File size'} {$fields_value[$input.name].size}{l s='kb'}</p>
												<a href="{$current}&{$identifier}={$form_id}&token={$token}&deleteImage=1">
													<img src="../img/admin/delete.gif" alt="{l s='Delete'}" /> {l s='Delete'}
												</a>
											</div><br />
										{/if}
									{/if}
									
									{if isset($input.lang) AND $input.lang}
										<div class="translatable">
											{foreach $languages as $language}
												<div class="lang_{$language.id_lang}" id="{$input.name}_{$language.id_lang}" style="display:{if $language.id_lang == $defaultFormLanguage}block{else}none{/if}; float: left;">
													<input type="file" name="{$input.name}_{$language.id_lang}" {if isset($input.id)}id="{$input.id}_{$language.id_lang}"{/if} />
									
												</div>
											{/foreach}
										</div>
									{else}
										<input type="file" name="{$input.name}" {if isset($input.id)}id="{$input.id}"{/if} />
									{/if}
									{if !empty($input.hint)}<span class="hint" name="help_box">{$input.hint}<span class="hint-pointer">&nbsp;</span></span>{/if}
								{elseif $input.type == 'password'}
									<input type="password"
											name="{$input.name}"
											size="{$input.size}"
											class="{if isset($input.class)}{$input.class}{/if}"
											value=""
											{if isset($input.autocomplete) && !$input.autocomplete}autocomplete="off"{/if} />
								{elseif $input.type == 'birthday'}
									{foreach $input.options as $key => $select}
										<select name="{$key}" class="{if isset($input.class)}{$input.class}{/if}">
											<option value="">-</option>
											{if $key == 'months'}
												{*
													This comment is useful to the translator tools /!\ do not remove them
													{l s='January'}
													{l s='February'}
													{l s='March'}
													{l s='April'}
													{l s='May'}
													{l s='June'}
													{l s='July'}
													{l s='August'}
													{l s='September'}
													{l s='October'}
													{l s='November'}
													{l s='December'}
												*}
												{foreach $select as $k => $v}
													<option value="{$k}" {if $k == $fields_value[$key]}selected="selected"{/if}>{l s=$v}</option>
												{/foreach}
											{else}
												{foreach $select as $v}
													<option value="{$v}" {if $v == $fields_value[$key]}selected="selected"{/if}>{$v}</option>
												{/foreach}
											{/if}

										</select>
									{/foreach}
								{elseif $input.type == 'group'}
									{assign var=groups value=$input.values}
									{include file='helpers/form/form_group.tpl'}
								{elseif $input.type == 'shop'}
									{$input.html}
								{elseif $input.type == 'categories'}
									{include file='helpers/form/form_category.tpl' categories=$input.values}
								{elseif $input.type == 'categories_select'}
									{$input.category_tree}
								{elseif $input.type == 'asso_shop' && isset($asso_shop) && $asso_shop}
										{$asso_shop}
								{elseif $input.type == 'color'}
									<input type="color"
										size="{$input.size}"
										data-hex="true"
										{if isset($input.class)}class="{$input.class}"
										{else}class="color mColorPickerInput"{/if}
										name="{$input.name}"
										value="{$fields_value[$input.name]|escape:'htmlall':'UTF-8'}" />
								{elseif $input.type == 'date'}
									<input type="text"
										size="{$input.size}"
										data-hex="true"
										{if isset($input.class)}class="{$input.class}"
										{else}class="datepicker"{/if}
										name="{$input.name}"
										value="{$fields_value[$input.name]|escape:'htmlall':'UTF-8'}" />
								{elseif $input.type == 'free'}
									{$fields_value[$input.name]}
								{elseif $input.type == 'position'}
									<div id="card-{$input.name}">
										<div id="drop-{$input.name}" class="ui-widget-content">
											<div id="drag-{$input.name}" class="ui-widget-content">
 												<p>{$input.text}</p>
											</div>
										</div>
									</div>										
								{/if}
								{if isset($input.required) && $input.required && $input.type != 'radio'} <sup>*</sup>{/if}
								{/block}{* end block input *}
								{block name="description"}
									{if isset($input.desc) && !empty($input.desc)}
										<p class="preference_description">
											{if is_array($input.desc)}
												{foreach $input.desc as $p}
													{if is_array($p)}
														<span id="{$p.id}">{$p.text}</span><br />
													{else}
														{$p}<br />
													{/if}
												{/foreach}
											{else}
												{$input.desc}
											{/if}
										</p>
									{/if}									
								{/block}								
								{if isset($input.lang) && isset($languages)}<div class="clear"></div>{/if}
								</div>
								<div class="clear"></div>
							{/block}{* end block field *}
							{if $input.name == 'id_state'}
								</div>
							{/if}
						{/if}
					{/foreach}
					{hook h='displayAdminForm' fieldset=$f}
					{if isset($name_controller)}
						{capture name=hookName assign=hookName}display{$name_controller|ucfirst}Form{/capture}
						{hook h=$hookName fieldset=$f}
					{elseif isset($smarty.get.controller)}
						{capture name=hookName assign=hookName}display{$smarty.get.controller|ucfirst|htmlentities}Form{/capture}
						{hook h=$hookName fieldset=$f}
					{/if}
				{elseif $key == 'submit'}
					<div class="margin-form">
						<input type="submit"
							id="{if isset($field.id)}{$field.id}{else}{$table}_form_submit_btn{/if}"
							value="{$field.title}"
							name="{if isset($field.name)}{$field.name}{else}{$submit_action}{/if}{if isset($field.stay) && $field.stay}AndStay{/if}"
							{if isset($field.class)}class="{$field.class}"{/if} />
					</div>
				{elseif $key == 'desc'}
					<p class="clear">
						{if is_array($field)}
							{foreach $field as $k => $p}
								{if is_array($p)}
									<span id="{$p.id}">{$p.text}</span><br />
								{else}
									{$p}
									{if isset($field[$k+1])}<br />{/if}
								{/if}
							{/foreach}
						{else}
							{$field}
						{/if}
					</p>
				{/if}
				{block name="other_input"}{/block}
			{/foreach}
			{if $required_fields}
				<div class="small"><sup>*</sup> {l s='Required field'}</div>
			{/if}
		</fieldset>
		{block name="other_fieldsets"}{/block}
		{if isset($fields[$f+1])}<br />{/if}
	{/foreach}
</form>
{/block}
{block name="after"}{/block}
<!-- end template for 1.5 -->
{if isset($tinymce) && $tinymce}
	<script type="text/javascript">

	var iso = '{$iso}';
	var pathCSS = '{$smarty.const._THEME_CSS_DIR_}';
	var ad = '{$ad}';

	$(document).ready(function(){
		{block name="autoload_tinyMCE"}
			tinySetup({
				editor_selector :"autoload_rte"
			});
		{/block}
	});
	</script>
{/if}
{if $firstCall}
	<script type="text/javascript">
		var module_dir = '{$smarty.const._MODULE_DIR_}';
		var id_language = {$defaultFormLanguage};
		var languages = new Array();
		var vat_number = {if $vat_number}1{else}0{/if};
		// Multilang field setup must happen before document is ready so that calls to displayFlags() to avoid
		// precedence conflicts with other document.ready() blocks
		{foreach $languages as $k => $language}
			languages[{$k}] = {
				id_lang: {$language.id_lang},
				iso_code: '{$language.iso_code}',
				name: '{$language.name}',
				is_default: '{$language.is_default}'
			};
		{/foreach}
		// we need allowEmployeeFormLang var in ajax request
		allowEmployeeFormLang = {$allowEmployeeFormLang|intval};
		employee_token = '{getAdminToken tab='AdminEmployees'}';
		displayFlags(languages, id_language, allowEmployeeFormLang);

		$(document).ready(function() {
			{if isset($fields_value.id_state)}
				if ($('#id_country') && $('#id_state'))
				{
					ajaxStates({$fields_value.id_state});
					$('#id_country').change(function() {
						ajaxStates();
					});
				}
			{/if}

			if ($(".datepicker").length > 0)
				$(".datepicker").datepicker({
					prevText: '',
					nextText: '',
					dateFormat: 'yy-mm-dd'
				});
		
	});
	state_token = '{getAdminToken tab='AdminStates'}';
	{block name="script"}{/block}
	</script>
{/if}
{/if}
<script type="text/javascript">	
	$(document).ready(function(){
		//display code on/off
		var display_code_on = document.getElementById('display_code_on');
		var display_code_off = document.getElementById('display_code_off');
		if (display_code_off.checked) {
			$(".display_code_group").hide();
			$("label.display_code_group").hide();
			}
		$("input[name='display_code']").click(function(){
			if (display_code_on.checked) {						
				$(".display_code_group").fadeIn("slow");
				$("label.display_code_group").fadeIn("slow");
				}
			if (display_code_off.checked) {
				$(".display_code_group").fadeOut("slow");
				$("label.display_code_group").fadeOut("slow");
				}
		});
		//display text on/off
		var display_text_on = document.getElementById('display_text_on');
		var display_text_off = document.getElementById('display_text_off');
		if (display_text_off.checked) {
			$(".display_text_group").hide();
			$("label.display_text_group").hide();
			}
		$("input[name='display_text']").click(function(){
			if (display_text_on.checked) {						
				$(".display_text_group").fadeIn("slow");
				$("label.display_text_group").fadeIn("slow");
				}
			if (display_text_off.checked) {
				$(".display_text_group").fadeOut("slow");
				$("label.display_text_group").fadeOut("slow");
				}
		});
		//display shadow on/off
		var display_shadow_on = document.getElementById('display_shadow_on');
		var display_shadow_off = document.getElementById('display_shadow_off');
		if (display_shadow_off.checked)
			$(".display_shadow_group").hide();
		$("input[name='display_shadow']").click(function(){
			if (display_shadow_on.checked) 						
				$(".display_shadow_group").fadeIn("slow");		
			if (display_shadow_off.checked)
				$(".display_shadow_group").fadeOut("slow");		
		});
		//display duration on/off
		var display_duration_on = document.getElementById('display_duration_on');
		var display_duration_off = document.getElementById('display_duration_off');
		if (display_duration_on.checked){
			$(".display_duration_group").show();
			$(".display_validity_group").hide();
		}
		if (display_duration_off.checked){
			$(".display_duration_group").hide();
			$(".display_validity_group").show();
		}		
		$("input[name='display_duration']").click(function(){
			if (display_duration_on.checked) { 						
				$(".display_duration_group").fadeIn("slow");
				$(".display_validity_group").fadeOut("slow");
			}		
			if (display_duration_off.checked) {
				$(".display_duration_group").fadeOut("slow");
				$(".display_validity_group").fadeIn("slow");	
			}	
		});
		//position code
		var x = 100;
		var y = 60;
		$('#drag-pos_code').draggable({    	
			snap : '#card-pos_code',
			containment : 'drop-pos_code',
			grid : [x , y],
			revert : function(event, ui) {
            $(this).data("uiDraggable").originalPosition = {
                top : 60,
                left : 100
            };
            return !event;
			},
		});
		$('#drop-pos_code').droppable({
			accept : '#drag-pos_code', 
   			 drop: function (event, ui) {
				var pos = ui.draggable.offset(), dPos = $(this).offset();
					$('#pos_code_x').val(parseInt(Math.round(pos.left - dPos.left)/x)); 
       				$('#pos_code_y').val(parseInt(Math.round(pos.top - dPos.top)/y));					
   			}    		
		});
		//position text
		$('#drag-pos_text').draggable({    	
			snap : '#card-pos_text',
			containment : 'drop-pos_text',
			grid : [x , y]	,
			revert : function(event, ui) {
            $(this).data("uiDraggable").originalPosition = {
                top : 60,
                left : 100
            };
            return !event;
			},
		});
		$('#drop-pos_text').droppable({
			accept : '#drag-pos_text', 
   			 drop: function (event, ui) {
				var pos = ui.draggable.offset(), dPos = $(this).offset();
					$('#pos_text_x').val(parseInt(Math.round(pos.left - dPos.left)/x)); 
       				$('#pos_text_y').val(parseInt(Math.round(pos.top - dPos.top)/y));					
   			}    		
		});	
	});
</script>
