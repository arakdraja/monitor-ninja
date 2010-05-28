<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>
<div id="content-header"<?php if (isset($noheader) && $noheader) { ?> style="display:none"<?php } ?>>
	<div class="widget left w32" id="page_links">
		<ul>
			<li><?php echo $this->translate->_('View').', '.$label_view_for.':'; ?></li>
		<?php
		if (isset($page_links)) {
			foreach ($page_links as $label => $link) {
				?>
				<li><?php echo html::anchor($link, $label) ?></li>
				<?php
			}
		}
		?>
		</ul>
	</div>


	<?php
	if (!empty($widgets)) {
		foreach ($widgets as $widget) {
			echo $widget;
		}
	}
	?>

	<div id="filters" class="left">
	<?php
	if (isset($filters) && !empty($filters)) {
		echo $filters;
	}
	?>
	</div>
</div>

<div class="widget left w98" id="status_service">
<?php echo (isset($pagination)) ? $pagination : ''; ?>

<?php echo form::open('command/multi_action'); ?><br />
<table style="margin-bottom: 2px" id="service_table">
<caption><?php echo $sub_title ?>: <?php echo html::image($this->add_path('icons/16x16/check-boxes.png'),array('style' => 'margin-bottom: -3px'));?> <a href="#" id="select_multiple_items" style="font-weight: normal"><?php echo $this->translate->_('Select Multiple Items') ?></a></caption>
	<thead>
		<tr>
			<th>&nbsp;</th>
			<?php
				$order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';
				$field = isset($_GET['sort_field']) ? $_GET['sort_field'] : 'h.host_name';
				$n = 0;
				foreach($header_links as $row) {
					$n++;
					if (isset($row['url_desc'])) {
						if ($n == 4)
							echo '<th class="no-sort">'.$this->translate->_('Actions').'</th>';
						echo '<th class="header'.(($order == 'DESC' && strpos($row['url_desc'], $field) == true && isset($row['url_desc'])) ? 'SortUp' : (($order == 'ASC' && strpos($row['url_desc'], $field) == true && isset($row['url_desc'])) ? 'SortDown' : (isset($row['url_desc']) ? '' : 'None'))) .
							'" onclick="location.href=\'' . url::site() .((isset($row['url_desc']) && $order == 'ASC') ? $row['url_desc'] : ((isset($row['url_asc']) && $order == 'DESC') ? $row['url_asc'] : '')).'\'">';
						echo ($row['title'] == 'Service' ? '<div class="item_select"><input type="checkbox" class="select_all_items" title="'.$this->translate->_('Click to select/unselect all').'"></div>' : '');
						echo ($row['title'] == 'Status' ? '' : $row['title']);
						echo '</th>';
					}
				}
			?>
			<th class="no-sort"><?php echo $this->translate->_('Status Information') ?></th>
		</tr>
	</thead>
	<tbody>
<?php
	$curr_host = false;
	$a = 0;
$c=0;
	if (!empty($result)) {
		foreach ($result as $row) {
		$a++;
		if ($curr_host != $row->host_name)
			$c++;
	?>
	<tr class="<?php echo ($a %2 == 0) ? 'odd' : 'even'; ?>">
		<td class="icon <?php echo strtolower(Current_status_Model::status_text($row->host_state)).' '.(($curr_host != $row->host_name) ? ($c == 1 && $a != 1 ? ' bt' : '') : 'white') ?>" <?php echo ($curr_host != $row->host_name) ? '' : 'colspan="1"' ?>>&nbsp;</td>
		<?php if ($curr_host != $row->host_name) { ?>
		<td class="service_hostname w80<?php echo ($c == 1 && $a != 1) ? ' bt' : '';?>" style="white-space: normal; border-right: 1px solid #dcdcdc;">
				<span style="float: left"><?php echo html::anchor('extinfo/details/host/'.$row->host_name, html::specialchars($row->host_name)) ?></span>
				<span style="float: right">
					<?php
						if ($row->hostproblem_is_acknowledged)
							echo html::anchor('extinfo/details/host/'.$row->host_name, html::image($this->add_path('icons/16x16/acknowledged.png'),array('alt' => $this->translate->_('Acknowledged'), 'title' => $this->translate->_('Acknowledged'))), array('style' => 'border: 0px'));
						if (empty($row->host_notifications_enabled))
							echo '&nbsp;'.html::anchor('extinfo/details/host/'.$row->host_name, html::image($this->add_path('icons/16x16/notify-disabled.png'),array('alt' => $this->translate->_('Notification disabled'), 'title' => $this->translate->_('Notification disabled'))), array('style' => 'border: 0px'));
						if (!$row->active_checks_enabled)
							echo '&nbsp;'.html::anchor('extinfo/details/host/'.$row->host_name, html::image($this->add_path('icons/16x16/active-checks-disabled.png'),array('alt' => $this->translate->_('Active checks enabled'), 'title' => $this->translate->_('Active checks disabled'))), array('style' => 'border: 0px'));
						if (isset($row->host_is_flapping) && $row->host_is_flapping)
							echo '&nbsp;'.html::anchor('extinfo/details/host/'.$row->host_name, html::image($this->add_path('icons/16x16/flapping.gif'),array('alt' => $this->translate->_('Flapping'), 'title' => $this->translate->_('Flapping'))), array('style' => 'border: 0px'));
						if ($row->hostscheduled_downtime_depth > 0)
							echo '&nbsp;'.html::anchor('extinfo/details/host/'.$row->host_name, html::image($this->add_path('icons/16x16//scheduled-downtime.png'),array('alt' => $this->translate->_('Scheduled downtime'), 'title' => $this->translate->_('Scheduled downtime'))), array('style' => 'border: 0px'));
						if ($host_comments !== false && array_key_exists($row->host_name, $host_comments)) {
							echo '&nbsp;'.html::anchor('extinfo/details/host/'.$row->host_name.'#comments',
								html::image($this->add_path('icons/16x16/add-comment.png'),
								array('alt' => sprintf($this->translate->_('This host has %s comment(s) associated with it'), $host_comments[$row->host_name]),
								'title' => sprintf($this->translate->_('This host has %s comment(s) associated with it'), $host_comments[$row->host_name]))), array('style' => 'border: 0px'));
						}
					?>
				</span>
		</td>
		<?php } else { $c = 0;?>
			<td class="service_hostname white" style="white-space: normal; border-right: 1px solid #dcdcdc;">&nbsp;</td>
		<?php } ?>
		<td class="icon <?php echo strtolower(Current_status_Model::status_text($row->current_state, 'service')); ?>">&nbsp;</td>
		<td style="white-space: normal">
			<span style="float: left">
				<div class="item_select"><?php echo form::checkbox(array('name' => 'object_select[]'), $row->host_name.';'.$row->service_description); ?></div>
				<?php echo html::anchor('extinfo/details/service/'.$row->host_name.'/?service='.urlencode($row->service_description), html::specialchars($row->service_description)) ?></span>
				<?php	if ($comments !== false && array_key_exists($row->host_name.';'.$row->service_description, $comments)) { ?>
					<span style="float: right">
						<?php echo html::anchor('extinfo/details/service/'.$row->host_name.'?service='.urlencode($row->service_description).'#comments',
								html::image($this->add_path('icons/16x16/add-comment.png'),
								array('alt' => sprintf($this->translate->_('This service has %s comment(s) associated with it'), $comments[$row->host_name.';'.$row->service_description]),
								'title' => sprintf($this->translate->_('This service has %s comment(s) associated with it'), $comments[$row->host_name.';'.$row->service_description]))), array('style' => 'border: 0px')); ?>
					</span>
					<?php } ?>
			<span style="float: right">
			<?php
				$properties = 0;
				if ($row->problem_has_been_acknowledged) {
					$properties++;
					echo html::anchor('extinfo/details/service/'.$row->host_name.'/?service='.urlencode($row->service_description), html::image($this->add_path('icons/16x16/acknowledged.png'),array('alt' => $this->translate->_('Acknowledged'), 'title' => $this->translate->_('Acknowledged'))), array('style' => 'border: 0px'));
				}
				if (empty($row->notifications_enabled)) {
					$properties += 2;
					echo html::anchor('extinfo/details/service/'.$row->host_name.'/?service='.urlencode($row->service_description), html::image($this->add_path('icons/16x16/notify-disabled.png'),array('alt' => $this->translate->_('Notification enabled'), 'title' => $this->translate->_('Notification disabled'))), array('style' => 'border: 0px'));
				}
				if (!$row->active_checks_enabled) {
					$properties += 4;
					echo html::anchor('extinfo/details/service/'.$row->host_name.'/?service='.urlencode($row->service_description), html::image($this->add_path('icons/16x16/active-checks-disabled.png'),array('alt' => $this->translate->_('Active checks enabled'), 'title' => $this->translate->_('Active checks disabled'))), array('style' => 'border: 0px'));
				}
				if (isset($row->service_is_flapping) && $row->service_is_flapping) {
					echo html::anchor('extinfo/details/service/'.$row->host_name.'/?service='.urlencode($row->service_description), html::image($this->add_path('icons/16x16/flapping.gif'),array('alt' => $this->translate->_('Flapping'), 'title' => $this->translate->_('Flapping'))), array('style' => 'border: 0px'));
				}
				if ($row->scheduled_downtime_depth > 0) {
					$properties += 8;
					echo html::anchor('extinfo/details/service/'.$row->host_name.'/?service='.urlencode($row->service_description), html::image($this->add_path('icons/16x16//scheduled-downtime.png'),array('alt' => $this->translate->_('Scheduled downtime'), 'title' => $this->translate->_('Scheduled downtime'))), array('style' => 'border: 0px'));
				}
				if ($row->current_state == Current_status_Model::SERVICE_CRITICAL || $row->current_state == Current_status_Model::SERVICE_UNKNOWN || $row->current_state == Current_status_Model::SERVICE_WARNING ) {
					$properties += 16;
				}
			?>
			</span><span class="obj_prop" style="display:none"><?php echo $properties ?></span>
		</td>
		<td class="icon" style="text-align: left">
			<?php
				if (nacoma::link()===true)
					echo nacoma::link('configuration/configure/service/'.$row->host_name.'?service='.urlencode($row->service_description), 'icons/16x16/nacoma.png', $this->translate->_('Configure this service')).' &nbsp;';
				if (Kohana::config('config.pnp4nagios_path')!==false) {
					if (pnp::has_graph($row->host_name, urlencode($row->service_description)))
						echo html::anchor('pnp/?host='.urlencode($row->host_name).'&srv='.urlencode($row->service_description), html::image($this->add_path('icons/16x16/pnp.png'), array('alt' => 'Show performance graph', 'title' => 'Show performance graph')), array('style' => 'border: 0px')).' &nbsp;';
				}
				if (!empty($row->action_url)) {
					echo '<a href="'.nagstat::process_macros($row->action_url, $row).'" style="border: 0px" target="_blank">';
					echo html::image($this->add_path('icons/16x16/host-actions.png'),array('alt' => $this->translate->_('Perform extra host actions'),'title' => $this->translate->_('Perform extra host actions')));
					echo '</a> &nbsp;';
				}
				if (!empty($row->notes_url)) {
					echo '<a href="'.nagstat::process_macros($row->notes_url, $row).'" style="border: 0px">';
					echo html::image($this->add_path('icons/16x16/host-notes.png'),array('alt' => $this->translate->_('View extra host notes'),'title' => $this->translate->_('View extra host notes')));
					echo '</a> &nbsp;';
				}
			?>
		</td>
		<td style="width: 110px"><?php echo $row->last_check ? date('Y-m-d H:i:s',$row->last_check) : $na_str ?></td>
<?php	if (isset($is_svc_details) && $is_svc_details !== false) {
			# make sure we print service duration and not host since we have a special query result here, i.e displaying servicegroup result ?>
		<td style="width: 110px"><?php echo $row->service_duration != $row->service_cur_time ? time::to_string($row->service_duration) : $na_str ?></td>
<?php	} else { ?>
		<td style="width: 110px"><?php echo $row->duration != $row->cur_time ? time::to_string($row->duration) : $na_str ?></td>
<?php	} ?>
		<td style="text-align: center; width: 60px"><?php echo $row->current_attempt;?>/<?php echo $row->max_check_attempts ?></td>
		<td style="white-space: normal">
		<?php
			if ($row->current_state == Current_status_Model::HOST_PENDING && isset($pending_output)) {
				echo $row->should_be_scheduled ? sprintf($pending_output, date(nagstat::date_format(), $row->next_check)) : $nocheck_output;
			} else {
				$output = nl2br($row->service_output.' '.$row->service_long_output);
				echo str_replace('','', $output);
			}
			?>
		</td>

	</tr>

	<?php
			$curr_host = $row->host_name;
		} ?>
		</tbody>
	</table>

<?php } ?>
	<?php echo form::dropdown(array('name' => 'multi_action', 'class' => 'item_select', 'id' => 'multi_action_select'),
		array(
			'' => $this->translate->_('Select Action'),
			'SCHEDULE_SVC_DOWNTIME' => $this->translate->_('Schedule Downtime'),
			'ACKNOWLEDGE_SVC_PROBLEM' => $this->translate->_('Acknowledge'),
			'REMOVE_SVC_ACKNOWLEDGEMENT' => $this->translate->_('Remove Problem Acknowledgement'),
			'DISABLE_SVC_NOTIFICATIONS' => $this->translate->_('Disable Service Notifications'),
			'ENABLE_SVC_NOTIFICATIONS' => $this->translate->_('Enable Service Notifications'),
			'DISABLE_SVC_CHECK' => $this->translate->_('Disable Active Checks'),
			'ENABLE_SVC_CHECK' => $this->translate->_('Enable Active Checks')
			)
		); ?>
	<?php echo form::submit(array('id' => 'multi_object_submit', 'class' => 'item_select', 'value' => $this->translate->_('Submit'))); ?>
	<?php echo form::hidden('obj_type', 'service'); ?>
	<?php echo form::close(); ?>
<?php echo (isset($pagination)) ? $pagination : ''; ?>
<br /><br />
</div>