<hr/><br />
<table>
	<tr>
		<td style="text-align: center; font-size: 6pt; color: #444">
			{$shop_url}<br/>
			{$shop_address}<br />

			{if !empty($shop_phone) OR !empty($shop_fax)}
				{l s='For more assistance, contact Support:' pdf='true'}<br />
				{if !empty($shop_phone)}
					Tel: {$shop_phone|escape:'html':'UTF-8'}
				{/if}

				{if !empty($shop_fax)}
					Fax: {$shop_fax|escape:'html':'UTF-8'}
				{/if}
				<br />
			{/if}
            
            {if isset($shop_details)}
                {$shop_details|escape:'html':'UTF-8'}<br />
            {/if}
		</td>
	</tr>
</table>

