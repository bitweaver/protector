
<div class="row">
	{formlabel label="Protection Level"}
	{forminput}
		Limit Access to the following groups:<br/>
		{foreach from=$protectorGroups key=groupId item=group}
			<input type="checkbox" name="protector_group[]" value="{$groupId}" {if $groupId==$serviceHash.protector_group_id}checked="checked"{/if} /> {$group.group_name}<br/>
		{/foreach}
	{/forminput}
</div>

