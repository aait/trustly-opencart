<?php echo $header ?>
<div id="content">
<div class="breadcrumb">
	<?php foreach ($breadcrumbs as $breadcrumb): ?>
	<?php echo $breadcrumb['separator']; ?>
	<a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
	<?php endforeach; ?>
</div>

<div class="box">
<div class="heading">
	<h1><img src="view/image/payment.png" alt=""/> <?php echo $heading_title; ?></h1>

	<div class="buttons"><a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a><a
			onclick="location = '<?php echo $cancel; ?>';" class="button"><?php echo $button_cancel; ?></a></div>
</div>
<div class="content">

<?php foreach ($error as $item) : ?>
<div class="warning">
	<?php echo $item ?>
</div>
<?php endforeach; ?>

<div id="tabs" class="htabs">
	<a href="#tab-settings" style="display: inline"><?php echo $text_settings; ?></a>

	<?php if (count($orders) > 0): ?>
	<a href="#tab-orders" style="display:inline"><?php echo $text_orders; ?></a>
	<?php endif; ?>
</div>


<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
<div id="tab-settings">
    <div style="margin-bottom:10px; padding:10px 5px 5px 10px; height: 400px; ">
        <iframe src="https://trustly.com/opencart" style="width: 100%%; height: 400px; border: 1px solid #D6D6D6;"></iframe>
    </div>
    <div class="backoffice">
        <p><?php echo $text_backoffice_info; ?></p>
        <ul>
            <li><?php echo $text_backoffice_link_live; ?> <a href="https://trustly.com/backoffice" target="_blank">https://trustly.com/backoffice</a></li>
            <li><?php echo $text_backoffice_link_test; ?> <a href="https://test.trustly.com/backoffice" target="_blank">https://test.trustly.com/backoffice</a></li>
        </ul>
    </div>

	<table class="form">
		<tr>
			<td>
				<label for="trustly_username"><?php echo $text_username; ?></label>
			</td>
			<td>
				<input type="text" name="trustly_username" id="trustly_username"
					   value="<?php echo $trustly_username; ?>" autocomplete="off">
			</td>
		</tr>
		<tr>
			<td>
				<label for="trustly_password"><?php echo $text_password; ?></label>
			</td>
			<td>
				<input type="password" name="trustly_password" id="trustly_password"
					   value="<?php echo $trustly_password; ?>" autocomplete="off">
			</td>
		</tr>
		<tr>
			<td>
				<label for="trustly_private_key"><?php echo $text_private_key; ?></label>
			</td>
			<td>
				<textarea rows="10" cols="45" name="trustly_private_key" id="trustly_private_key" style="width: 500px; height: 390px;"><?php echo $trustly_private_key; ?></textarea>
			</td>
		</tr>
		<tr>
			<td>
				<label for="trustly_test_mode"><?php echo $text_test_mode; ?></label>
			</td>
			<td>
				<input type="checkbox" name="trustly_test_mode" id="trustly_test_mode" <?php echo $trustly_private_key ? 'checked' : '' ?> value="1">
			</td>
		</tr>
		<tr>
			<td>
				<label for="trustly_notify_http"><?php echo $text_notify_http; ?></label>
			</td>
			<td>
				<input type="checkbox" name="trustly_notify_http" id="trustly_notify_http" <?php echo $trustly_notify_http ? 'checked="checked"' : '' ?> value="1">
			</td>
		</tr>
		<tr>
			<td><?php echo $text_total; ?></td>
			<td><input type="text" name="trustly_total" value="<?php echo $trustly_total; ?>"/></td>
		</tr>
		<tr>
			<td><?php echo $text_complete_status; ?></td>
			<td>
				<select name="trustly_completed_status_id">
					<?php foreach ($order_statuses as $order_status): ?>
					<?php if ($order_status['order_status_id'] == $trustly_completed_status_id): ?>
					<option value="<?php echo $order_status['order_status_id']; ?>"
							selected="selected"><?php echo $order_status['name']; ?></option>
					<?php else: ?>
					<option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
					<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<td><?php echo $text_pending_status; ?></td>
			<td>
				<select name="trustly_pending_status_id">
					<?php foreach ($order_statuses as $order_status): ?>
					<?php if ($order_status['order_status_id'] == $trustly_pending_status_id): ?>
					<option value="<?php echo $order_status['order_status_id']; ?>"
							selected="selected"><?php echo $order_status['name']; ?></option>
					<?php else: ?>
					<option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
					<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<td><?php echo $text_canceled_status; ?></td>
			<td>
				<select name="trustly_canceled_status_id">
					<?php foreach ($order_statuses as $order_status): ?>
					<?php if ($order_status['order_status_id'] == $trustly_canceled_status_id): ?>
					<option value="<?php echo $order_status['order_status_id']; ?>"
							selected="selected"><?php echo $order_status['name']; ?></option>
					<?php else: ?>
					<option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
					<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<td><?php echo $text_failed_status; ?></td>
			<td>
				<select name="trustly_failed_status_id">
					<?php foreach ($order_statuses as $order_status): ?>
					<?php if ($order_status['order_status_id'] == $trustly_failed_status_id): ?>
					<option value="<?php echo $order_status['order_status_id']; ?>"
							selected="selected"><?php echo $order_status['name']; ?></option>
					<?php else: ?>
					<option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
					<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<td><?php echo $text_refunded_status; ?></td>
			<td>
				<select name="trustly_refunded_status_id">
					<?php foreach ($order_statuses as $order_status): ?>
					<?php if ($order_status['order_status_id'] == $trustly_refunded_status_id): ?>
					<option value="<?php echo $order_status['order_status_id']; ?>"
							selected="selected"><?php echo $order_status['name']; ?></option>
					<?php else: ?>
					<option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
					<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>

		<tr>
			<td><?php echo $text_geo_zone; ?></td>
			<td>
				<select name="trustly_geo_zone_id">
					<option value="0"><?php echo $text_all_zones; ?></option>
					<?php foreach ($geo_zones as $geo_zone): ?>
					<?php if ($geo_zone['geo_zone_id'] == $trustly_geo_zone_id): ?>
					<option value="<?php echo $geo_zone['geo_zone_id']; ?>"
							selected="selected"><?php echo $geo_zone['name']; ?></option>
					<?php else: ?>
					<option
						value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?>
                    </option>
					<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<td><?php echo $text_status; ?></td>
			<td>
				<select name="trustly_status">
					<?php if ($trustly_status): ?>
					<option value="1" selected="selected"><?php echo $text_enabled; ?></option>
					<option value="0"><?php echo $text_disabled; ?></option>
					<?php else: ?>
					<option value="1"><?php echo $text_enabled; ?></option>
					<option value="0" selected="selected"><?php echo $text_disabled; ?></option>
					<?php endif; ?>
				</select>
			</td>
		</tr>
		<tr>
			<td><?php echo $text_sort_order; ?></td>
			<td>
				<input type="text" name="trustly_sort_order" value="<?php echo $trustly_sort_order; ?>" size="1" />
			</td>
		</tr>
	</table>
</div>

<?php if (count($orders) > 0): ?>
<div id="tab-orders">
    <table class="form">
        <thead>
        <tr>
            <th><?php echo $text_order_id; ?></th>
            <th><?php echo $text_trustly_order_id; ?></th>
            <th><?php echo $text_notification_id; ?></th>
            <th><?php echo $text_amount; ?></th>
            <th><?php echo $text_date; ?></th>
            <th><?php echo $text_actions; ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
        <tr style="text-align: center;">
            <td><?php echo $order['order_id']; ?></td>
            <td><?php echo $order['trustly_order_id']; ?></td>
            <td><?php echo $order['notification_id']; ?></td>
            <td><?php echo sprintf('%.2f %s', $order['total'], $order['currency_code']); ?></td>
            <td><?php echo $order['date']; ?></td>
            <td>
                <input type="button" class="refund_button" name="refund_button"
                       value="<?php echo $text_refund; ?>" data-order-id="<?php echo $order['order_id']; ?>"
                       data-trustly-order-id="<?php echo $order['trustly_order_id']; ?>"
                       data-currency="<?php echo $order['currency_code']; ?>">
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="pagination"><?php echo $pagination; ?></div>
</div>
<?php endif; ?>

</form>
</div>
</div>
</div>
<script type="text/javascript"><!--
    $(document ).ready(function() {
        // Activate Tabs
        $('#tabs a').tabs();

        // Select Orders Tab
        var current_url = document.URL;
        if (current_url.indexOf('page') > -1) {
            $("#tabs a[href^='#tab-orders']").click();
        }
    });

    // Refund Action
	$('.refund_button').on('click', function () {
		var total_refunded = prompt('Enter refund amount:', '0');
            //Always use the dot as decimal separator
        if(total_refunded.indexOf('.') == -1 && total_refunded.indexOf(',') > 0) {
            total_refunded = total_refunded.replace(/,/g, '.');
        }

		if (parseInt(total_refunded) > 0) {
			call_refund(this, total_refunded);
		}
	});

	function call_refund(el, amount) {
		var order_id = $(el).data('order-id');
        var trustly_order_id = $(el).data('trustly-order-id');
        var currency = $(el).data('currency');
		var current_label = $(el).val();
		$(el).attr('disabled', 'disabled');
		$(el).val('<?php echo $text_wait; ?>');

		$.ajax({
			url: '<?php echo html_entity_decode($action, ENT_QUOTES, 'UTF-8'); ?>',
			type: 'POST',
			cache: false,
			async: true,
			dataType: 'json',
			data: {
				action: 'refund',
				order_id: order_id,
                trustly_order_id: trustly_order_id,
				amount: amount,
                currency: currency
			},
			success: function (response) {
				if (response.status !== 'ok') {
					alert('Error: ' + response.message);
					$(el).removeAttr('disabled');
					$(el).val(current_label);
					return false;
				}
				$(el).val(response.label);
			}
		});
	}

	//--></script>
<?php echo $footer ?>
