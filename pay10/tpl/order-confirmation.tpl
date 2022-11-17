{if $pay10_order.valid == 1}
<div class="conf confirmation">
	{l s='Congratulations! Your order has been saved under' mod='pay10'}{if isset($pay10_order.reference)} {l s='the reference' mod='pay10'} <b>{$pay10_order.reference|escape:html:'UTF-8'}</b>{else} {l s='the ID' mod='pay10'} <b>{$pay10_order.id|escape:html:'UTF-8'}</b>{/if}.
</div>
{else}
<div class="error">
	{l s='Unfortunately, an error occurred during the transaction.' mod='pay10'}<br /><br />
	{l s='Reason  : ' mod='pay10'} <b>{$pay10_order.errmsg|escape:html:'UTF-8'}</b><br /><br />
{if isset($pay10_order.reference)}
	({l s='Your Order\'s Reference:' mod='pay10'} <b>{$pay10_order.reference|escape:html:'UTF-8'}</b>)
{else}
	({l s='Your Order\'s ID:' mod='pay10'} <b>{$pay10_order.id|escape:html:'UTF-8'}</b>)
{/if}
</div>
{/if}
