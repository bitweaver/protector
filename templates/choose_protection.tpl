<div class="control-group">
	{formlabel label="Protection Level"}
	{forminput}
		{if $gBitSystem->isFeatureActive( 'protector_single_role' )}
			Assign content to the following user role:<br/>
			{html_options name="protector[role_id]" options=$protectorRolesId selected=`$serviceHash.protector.role_id`}
			{formhelp note="Users assigned to this role can view this content item."}
		{else}
			Limit access to the following user roles:<br/>
			{foreach from=$protectorRoles key=roleId item=role}
				<input type="checkbox" name="protector[role_id][]" value="{$roleId}" {if isset($serviceHash.protector.role.$roleId)}checked="checked"{/if} /> {$role.role_name}<br/>
			{/foreach}
			{formhelp note="Users assigned to these roles can view this content item."}
		{/if}
	{/forminput}
</div>
