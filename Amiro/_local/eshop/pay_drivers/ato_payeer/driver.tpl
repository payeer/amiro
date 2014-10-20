%%include_language "_local/eshop/pay_drivers/ato_payeer/driver.lng"%%

<!--#set var="settings_form" value="
<tr>
    <td colspan="2"><hr /></td>
</tr>
<tr>
    <td>%%payeer_url%%:</td>
    <td><input type="text" name="payeer_url" class="field" value="##payeer_url##" size="40" /></td>
</tr>
<tr>
    <td>%%payeer_shop%%:</td>
    <td><input type="text" name="payeer_shop" class="field" value="##payeer_shop##" size="40" /></td>
</tr>
<tr>
    <td>%%payeer_secret_key%%:</td>
    <td><input type="text" name="payeer_secret_key" class="field" value="##payeer_secret_key##" size="40" /></td>
</tr>
<tr>
    <td>%%payeer_log%%:</td>
	<td>
		<input type="radio" name="payeer_log" value="0"##IF(!payeer_log)## checked="checked"##ENDIF## />%%payeer_log_off%%
		<input type="radio" name="payeer_log" value="1"##IF(payeer_log)## checked="checked"##ENDIF## />%%payeer_log_on%%
	</td>
</tr>
<tr>
    <td>%%payeer_ip_filter%%:</td>
    <td><input type="text" name="payeer_ip_filter" class="field" value="##payeer_ip_filter##" size="40" /></td>
</tr>
<tr>
    <td>%%payeer_email_error%%:</td>
    <td><input type="text" name="payeer_email_error" class="field" value="##payeer_email_error##" size="40" /></td>
</tr>
<input type="hidden" name="url" value="###_null_###submitter_link###_null_###">
"-->

<!--#set var="checkout_form" value="
<form name="paymentformpayeer" action="##process_url##" method="post">
    <input type="hidden" name="amount" value="##amount##" />
    <input type="hidden" name="description" value="##description##" />
    <input type="hidden" name="order" value="##order##" />
    <input type="hidden" name="return" value="##return##" />
    <input type="hidden" name="amount" value="##amount##" />
    <input type="hidden" name="currency" value="##currency##" />
##hiddens##
    <input type="submit" name="sbmt" class="btn" value="%%driver_button_caption%%" ##disabled## />
</form>
"-->

##--
<!--#set var="pay_form" value="
<form name="paymentform" action="##url##" method="post">
    <input type="hidden" name="item_number" value="##order##" />
    <input type="hidden" name="status" value="ok" />
##hiddens##
</form>
<script type="text/javascript">
    document.forms.paymentform.submit();
</script>
"-->
--##

<!--#set var="pay_form" value="
<form name="paymentform" action="##payeer_url##" method="get">
	<input type="hidden" name="m_shop" value="##payeer_shop##">
	<input type="hidden" name="m_orderid" value="##order##">
	<input type="hidden" name="m_amount" value="##amount##">
	<input type="hidden" name="m_curr" value="##currency##">
	<input type="hidden" name="m_desc" value="##description##">
	<input type="hidden" name="m_sign" value="##sign##">
	<input type="submit" name="m_process" value="Pay with Payeer" />
</form>
<script type="text/javascript">
    document.forms.paymentform.submit();
</script>
"-->
