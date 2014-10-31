<?php echo $header ?><?php echo $column_left ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form-trustly" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
            </div>
            <h1><?php echo $heading_title; ?></h1>
            <ul class="breadcrumb">
            <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
            <?php } ?>
            </ul>
        </div>
    </div>

    <div class="container-fluid">
        <?php foreach ($error as $item) : ?>
            <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $item; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endforeach; ?>

<!--
<div id="tabs" class="htabs">
	<a href="#tab-settings" style="display: inline"><?php echo $text_settings; ?></a>

	<?php if (count($orders) > 0): ?>
	<a href="#tab-orders" style="display:inline"><?php echo $text_orders; ?></a>
	<?php endif; ?>
</div>
-->

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
            </div>
            <div class="panel-body">
                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-trustly" class="form-horizontal">

                    <div>
                        <iframe src="https://trustly.com/opencartsignup" scrolling="no" style="width: 100%%; height: 320px; border: 1px solid #D6D6D6;"></iframe>
                    </div>

                    <div class="well">
                        <h4><?php echo $text_backoffice_info; ?></h4>
                        <ul>
                            <li><?php echo $text_backoffice_link_live; ?> <a href="https://trustly.com/backoffice" target="_blank">https://trustly.com/backoffice</a></li>
                            <li><?php echo $text_backoffice_link_test; ?> <a href="https://test.trustly.com/backoffice" target="_blank">https://test.trustly.com/backoffice</a></li>
                        </ul>
                    </div>

                    <div class="form-group required">
                        <label class="col-sm-2 control-label" for="trustly_username"><?php echo $text_username; ?></label>
                        <div class="col-sm-10">
                            <input class="form-control" type="text" name="trustly_username" id="trustly_username" autocomplete="off"
                                value="<?php echo $trustly_username; ?>" placeholder="<?php echo $text_username ?>" />
                        </div>
                    </div>

                    <div class="form-group required">
                        <label class="col-sm-2 control-label" for="trustly_password"><?php echo $text_password; ?></label>
                        <div class="col-sm-10">
                            <input class="form-control" type="password" name="trustly_password" id="trustly_password" autocomplete="off"
                                value="<?php echo $trustly_password; ?>" placeholder="<?php echo $text_password ?>" />
                        </div>
                    </div>

                    <div class="form-group required">
                        <label class="col-sm-2 control-label" for="trustly_private_key"><?php echo $text_private_key; ?></label>
                        <div class="col-sm-10">
                            <textarea class="form-control" rows=10" name="trustly_private_key" id="trustly_private_key" autocomplete="off"
                                placeholder="<?php echo $text_private_key ?>"><?php echo $trustly_private_key; ?></textarea>
                        </div>
                    </div>

    <?php if(version_compare(phpversion(), '5.2.0', '>=')): ?> 
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="trustly_key_new"><?php echo $text_rsa_keys; ?></label>
                        <div class="col-sm-10">
                            <a class="btn btn-default" id="trustly_key_new"><?php echo $text_new_private_key; ?></a>
                            <a class="btn btn-default" id="trustly_key_show"><?php echo $text_show_public_key; ?></a>
                            <div class="help-block trustly_key_display" style="display: none;">
                                <div style="display: none;" class="trustly_key_new_display"><?php echo $text_new_key_generated; ?></div>
                                <pre id="trustly_key_public_key"></pre>
                            </div>
                        </div>
                    </div>
    <?php endif; ?>

                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="trustly_test_mode"><?php echo $text_test_mode; ?></label>
                        <div class="col-sm-10">
                            <select name="trustly_test_mode" id="trustly_test_mode" class="form-control">
                                <option value="0" <?php echo (!$trustly_test_mode?'selected':'') ?>><?php echo $text_disabled ?></option>
                                <option value="1" <?php echo ($trustly_test_mode?'selected':'') ?>><?php echo $text_enabled ?></option>

                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="trustly_notify_http"><?php echo $text_notify_http; ?></label>
                        <div class="col-sm-10">
                            <select name="trustly_notify_http" id="trustly_notify_http" class="form-control">
                                <option value="0" <?php echo (!$trustly_notify_http?'selected':'') ?>><?php echo $text_disabled ?></option>
                                <option value="1" <?php echo ($trustly_notify_http?'selected':'') ?>><?php echo $text_enabled ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="trustly_total"><span data-toggle="tooltip" title="<?php echo $help_total; ?>"><?php echo $text_total; ?></span></label>
                        <div class="col-sm-10">
                            <input type="text" name="trustly_total" id="trustly_total" value="<?php echo $trustly_total; ?>" placeholder="<?php echo $text_total; ?>" class="form-control" />
                        </div>
                    </div>


                    <div class="form-group required">
                        <label class="col-sm-2 control-label" for="trustly_completed_status_id"><?php echo $text_complete_status; ?></label>
                        <div class="col-sm-10">
                            <select name="trustly_completed_status_id" id="trustly_completed_status_id" class="form-control">
                                <?php foreach ($order_statuses as $order_status): ?>
                                <?php if ($order_status['order_status_id'] == $trustly_completed_status_id): ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"
                                        selected="selected"><?php echo $order_status['name']; ?></option>
                                <?php else: ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group required">
                        <label class="col-sm-2 control-label" for="trustly_pending_status_id"><?php echo $text_pending_status; ?></label>
                        <div class="col-sm-10">
                            <select name="trustly_pending_status_id" id="trustly_pending_status_id" class="form-control">
                                <?php foreach ($order_statuses as $order_status): ?>
                                <?php if ($order_status['order_status_id'] == $trustly_pending_status_id): ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"
                                        selected="selected"><?php echo $order_status['name']; ?></option>
                                <?php else: ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group required">
                        <label class="col-sm-2 control-label" for="trustly_canceled_status_id"><?php echo $text_canceled_status; ?></label>
                        <div class="col-sm-10">
                            <select name="trustly_canceled_status_id" id="trustly_canceled_status_id" class="form-control">
                                <?php foreach ($order_statuses as $order_status): ?>
                                <?php if ($order_status['order_status_id'] == $trustly_canceled_status_id): ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"
                                        selected="selected"><?php echo $order_status['name']; ?></option>
                                <?php else: ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group required">
                        <label class="col-sm-2 control-label" for="trustly_failed_status_id"><?php echo $text_failed_status; ?></label>
                        <div class="col-sm-10">
                            <select name="trustly_failed_status_id" id="trustly_failed_status_id" class="form-control">
                                <?php foreach ($order_statuses as $order_status): ?>
                                <?php if ($order_status['order_status_id'] == $trustly_failed_status_id): ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"
                                        selected="selected"><?php echo $order_status['name']; ?></option>
                                <?php else: ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group required">
                        <label class="col-sm-2 control-label" for="trustly_refunded_status_id"><?php echo $text_refunded_status; ?></label>
                        <div class="col-sm-10">
                            <select name="trustly_refunded_status_id" id="trustly_refunded_status_id" class="form-control">
                                <?php foreach ($order_statuses as $order_status): ?>
                                <?php if ($order_status['order_status_id'] == $trustly_refunded_status_id): ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"
                                        selected="selected"><?php echo $order_status['name']; ?></option>
                                <?php else: ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group required">
                        <label class="col-sm-2 control-label" for="trustly_geo_zone_id"><?php echo $text_geo_zone; ?></label>
                        <div class="col-sm-10">
                            <select name="trustly_geo_zone_id" id="trustly_geo_zone_id" class="form-control">
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
                        </div>
                    </div>

                    <div class="form-group required">
                        <label class="col-sm-2 control-label" for="trustly_status"><?php echo $text_status; ?></label>
                        <div class="col-sm-10">
                            <select name="trustly_status" id="trustly_status" class="form-control">
                                <?php if ($trustly_status): ?>
                                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                <option value="0"><?php echo $text_disabled; ?></option>
                                <?php else: ?>
                                <option value="1"><?php echo $text_enabled; ?></option>
                                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="trustly_sort_order"><?php echo $text_sort_order; ?></label>
                        <div class="col-sm-10">
                            <input class="form-control" type="text" name="trustly_sort_order" id="trustly_sort_order" 
                                value="<?php echo $trustly_sort_order; ?>" placeholder="<?php echo $text_sort_order; ?>" />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!--
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
-->

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
        if(total_refunded != null) {
                //Always use the dot as decimal separator
            if(total_refunded.indexOf('.') == -1 && total_refunded.indexOf(',') > 0) {
                total_refunded = total_refunded.replace(/,/g, '.');
            }

            refunded = parseFloat(total_refunded);
            if (isFinite(refunded) && refunded > 0) {
                call_refund(this, refunded);
            }
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

	$('#trustly_key_new, #trustly_key_show').click(function(ev) {
		var data;
		var ajaxurl = '<?php echo html_entity_decode($action, ENT_QUOTES, 'UTF-8'); ?>'; 
		$('.trustly_key_new_display, .trustly_key_display').hide();

		if(this.id.substr(-3) == 'new') {
			if($('#trustly_private_key').val() != '' && !window.confirm('<?php echo $text_warning_private_key_exists; ?>')) {
				return false;
			}
			data = {
				'action': 'trustly_generate_rsa_key'
			};
		} else {
			data = {
				'action': 'trustly_generate_rsa_public_key',
				'private_key': $('#trustly_private_key').val()
			};
		}

		$.post(ajaxurl, data, function(response) {
			try {
				response = JSON.parse(response);
				if(!response.hasOwnProperty('public_key') || response.public_key == null) {
					throw 'Missing public_key in response';
				}
				$('#trustly_key_public_key').text(response.public_key);
				$('.trustly_key_display').show();
				if(response.hasOwnProperty('private_key')) {
					$('.trustly_key_new_display').show();
					$('#trustly_private_key').val(response.private_key);
				}
			} catch (e) {
				alert('<?php echo $text_failed_generate_key; ?>');
				console.log('Failed to generate key: ' + e);
			}
		});
		ev.preventDefault();
		return false;
	});

	//--></script>
<?php echo $footer ?>
