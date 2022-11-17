{$pay10_confirmation}

<img src="{$base_url|escape:'htmlall':'UTF-8'}" alt="" style="display: none;"/>

	<div class="pay10-header">
		<h2 class="page-title"><img src="{$module_dir}logo.png" alt="pay10" class="pay10-logo" /></a>
	Pay10
</h2>
	</div>

	<form action="{$pay10_form|escape:'htmlall':'UTF-8'}" id="module_form" class="defaultForm form-horizontal" method="post">
<div class="panel" id="fieldset_0">    
<div class="panel-heading">
<i class="icon-cogs"></i>Settings
</div>    

<div class="form-wrapper">


<div class="form-group">            
<label  class="control-label col-lg-3" for="pay10_app_id">{l s='Pay  ID:' mod='pay10'}</label>
<div class="col-lg-3">
<div class="input-group">
<span class="input-group-addon"><i class="icon icon-tag"></i></span>
		<input type="text" class="text" name="pay10_app_id" id="pay10_app_id" value="{$pay10_app_id|escape:'htmlall':'UTF-8'}" />
</div>
</div>
</div>


<div class="form-group">              
          
				<label class="control-label col-lg-3" for="pay10_secret_key">{l s='Salt Key:' mod='pay10'}</label>
<div class="col-lg-3">
<div class="input-group">

<span class="input-group-addon"><i class="icon icon-tag"></i></span>
					<input type="text" class="text" name="pay10_secret_key" id="pay10_secret_key" value="{$pay10_secret_key|escape:'htmlall':'UTF-8'}" />

</div>
</div>
</div>

<div class="form-group">              
          
				<label class="control-label col-lg-3" for="pay10_hosted_key">{l s='Hosted Key:' mod='pay10'}</label>
<div class="col-lg-3">
<div class="input-group">

<span class="input-group-addon"><i class="icon icon-tag"></i></span>
					<input type="text" class="text" name="pay10_hosted_key" id="pay10_secret_key" value="{$pay10_hosted_key|escape:'htmlall':'UTF-8'}" />

</div>
</div>
</div>


<div class="form-group">                    
				<label class="control-label col-lg-3" for="pay10_imode">{l s='Test Mode:' mod='pay10'}</label>
<div class="col-lg-3">
				  <select name="pay10_mode" id="input-mode" class="form-control">
					{if $pay10_mode == 'Y'}
					<option value="Y" selected="selected">{l s='Yes' mod='pay10'}</option>
					<option value="N">{l s='No' mod='pay10'}</option>
					{else}
					<option value="Y">{l s='Yes' mod='pay10'}</option>
					<option value="N" selected="selected">{l s='No' mod='pay10'}</option>
					{/if}
				  </select>                

</div>
</div>

                
   
<div class="form-group">                    
				<label class="control-label col-lg-3" for="pay10_order_status">{l s='Success Order Status:' mod='pay10'}</label>                                
<div class="col-lg-3">
	              <select name="pay10_order_status" id="input-transaction-method" class="form-control">
					{foreach from=$orderstates key='ordid' item='ordname'}                  
						<option value="{$ordid}" {if $ordid == $pay10_order_status} selected="selected"{/if}>{$ordname}</option>
					{/foreach}
	              </select>             
</div>
</div>

                


</div>
<div class="panel-footer">                

						<button type="submit" value="1" id="module_form_submit_btn" name="submitpay10" class="btn btn-default pull-right">
					<i class="process-icon-save"></i> Save
				</button>
</div>        
			</div>
	</form>


