
<div class="row">
	{formlabel label="Protection Level"}
	{forminput}
		{if $gBitSystem->isFeatureActive( 'protector_single_group' )}
			Assign content to the following group:<br/>
			{html_options name="$protectorGroupsDesc" id="protector_group" options=$protectorGroupsId selected=`$serviceHash.protector_group_id`}
			{formhelp note="Users of only this group can view the content of this category."}
		{else}
			Limit access to the following groups:<br/>
			{foreach from=$protectorGroups key=groupId item=group}
				<input type="checkbox" name="protector_group[]" value="{$groupId}" {if $groupId==$serviceHash.protector_group_id}checked="checked"{/if} /> {$group.group_name}<br/>
			{/foreach}
		{/if}
	{/forminput}
</div>

