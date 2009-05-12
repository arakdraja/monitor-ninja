<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<?php
if (!empty($widgets)) {
	foreach ($widgets as $widget) {
		echo $widget;
	}
}
?>

<div class="widget left w98" id="status_service">
<div id="status_msg" class="widget-header"><?php echo $sub_title ?></div>

<table style="table-layout: fixed" id="sorttable">
	<colgroup>
		<col style="width: 30px" />
		<col style="width: 160px" />
		<col style="width: 30px" />
		<col style="width: 160px" />
		<col style="width: 122px" />
		<col style="width: 105px" />
		<col style="width: 100%" />
		<col style="width: 30px" />
		<col style="width: 30px" />
		<col style="width: 30px" />
	</colgroup>
	<thead>
		<tr>
			<th class="no-sort">&nbsp;</th>
			<th class="headerSortDown"><?php echo $this->translate->_('Host') ?></th>
			<th class="header"><?php echo $this->translate->_('') ?></th>
			<th class="header"><?php echo $this->translate->_('Service') ?></th>
			<th class="header"><?php echo $this->translate->_('Last check') ?></th>
			<th class="header"><?php echo $this->translate->_('Duration') ?></th>
			<th class="no-sort"><?php echo $this->translate->_('Status information') ?></th>
			<th class="header" colspan="3"><?php echo $this->translate->_('Actions') ?></th>
			<?php //echo isset($row['url_asc']) ? html::anchor($row['url_asc'], html::image($row['img_asc'], array('alt' => $row['alt_asc'], 'title' => $row['alt_asc']))) : '' ?>
			<?php //echo isset($row['url_desc']) ? html::anchor($row['url_desc'], html::image($row['img_desc'], array('alt' => $row['alt_desc'], 'title' => $row['alt_desc']))) : '' ?>
		</tr>
	</thead>
	<tbody>
<?php
	$curr_host = false;
	$a = 0;
	if (!empty($result)) {
		foreach ($result as $row) {
		$a++;
	?>
	<tr class="<?php echo ($a %2 == 0) ? 'odd' : 'even'; ?>">
		<td class="icon bl <?php echo ($curr_host != $row->host_name) ? 'bt' : 'white' ?>" <?php //echo ($curr_host != $row->host_name) ? '' : 'colspan="2"' ?>>
			<?php
				if ($curr_host != $row->host_name) {
					echo html::image('/application/views/themes/default/images/icons/16x16/shield-'.strtolower(Current_status_Model::status_text($row->host_state, Router::$method)).'.png',array('alt' => Current_status_Model::status_text($row->host_state, Router::$method), 'title' => $this->translate->_('Host status').': '.Current_status_Model::status_text($row->host_state, Router::$method)));
				}
			?>
		</td>
		<td class="<?php echo ($curr_host != $row->host_name) ? 'w80' : 'white' ?>" style="white-space: normal">
			<?php if ($curr_host != $row->host_name) { ?>
				<?php echo html::anchor('extinfo/details/host/'.$row->host_name, html::specialchars($row->host_name)) ?>
				<div style="float: right">
					<?php
						if ($row->problem_has_been_acknowledged) {
							echo html::anchor('extinfo/details/host/'.$row->host_name, html::specialchars('ACK'));
						}
						if (empty($row->notifications_enabled)) {
							echo html::anchor('extinfo/details/host/'.$row->host_name, html::specialchars('nDIS'));
						}
						if (!$row->active_checks_enabled) {
							echo html::anchor('extinfo/details/host/'.$row->host_name, html::specialchars('DIS'));
						}
						if (isset($row->is_flapping) && $row->is_flapping) {
							echo html::anchor('extinfo/details/host/'.$row->host_name, html::specialchars('FPL'));
						}
						if ($row->scheduled_downtime_depth > 0) {
							echo html::anchor('extinfo/details/host/'.$row->host_name, html::specialchars('SDT'));
						}
					?>
				</div>
			<?php } ?>
		</td>
		<td class="bl icon">
			<?php echo html::image('/application/views/themes/default/images/icons/16x16/shield-'.strtolower(Current_status_Model::status_text($row->current_state, Router::$method)).'.png',array('alt' => Current_status_Model::status_text($row->current_state, Router::$method), 'title' => $this->translate->_('Service status').': '.Current_status_Model::status_text($row->current_state, Router::$method))) ?>
		</td>
		<td style="white-space: normal"><?php echo html::anchor('extinfo/details/service/'.$row->host_name.'/?service='.$row->service_description, html::specialchars($row->service_description)) ?></td>
		<td><?php echo date('Y-m-d H:i:s',$row->last_check) ?></td>
		<td><?php echo $row->duration ?></td>
		<td style="white-space: normal"><?php echo str_replace('','',$row->plugin_output) ?></td>
		<td class="icon">
		<?php	if (!empty($row->icon_image)) { ?>
			<?php //echo html::image('application/views/themes/default/images/icons/16x16/action.png',array('alt' => $this->translate->_('View extra host notes'),'title' => $this->translate->_('View extra host notes')))?>
			<img src="<?php echo $logos_path.$row->icon_image ?>" alt="<?php echo $this->translate->_('View extra host notes');?>" title="<?php echo $this->translate->_('View extra host notes');?>" />
		<?php	} ?>
		</td>
		<td class="icon">
		<?php	if (!empty($row->action_url)) { ?>
			<a href="<?php echo $row->action_url ?>" style="border: 0px">
			<?php echo html::image('application/views/themes/default/images/icons/16x16/action.png',array('alt' => $this->translate->_('Perform extra host actions'),'title' => $this->translate->_('Perform extra host actions')))?></a>
		<?php	} if (!empty($row->notes_url)) { ?>
			<a href="<?php echo $row->notes_url ?>" style="border: 0px">
				<?php echo html::image('/application/views/themes/default/images/icons/16x16/notes.png',array('alt' => $this->translate->_('View extra host notes'),'title' => $this->translate->_('View extra host notes')))?>
			</a>
			<?php } ?>
		</td>
		<td class="icon">
			<a href="/monitor/op5/webconfig/edit.php?obj_type=<?php echo Router::$method ?>&amp;host=<?php echo $row->host_name ?>&amp;service=<?php echo str_replace(' ','%20',$row->service_description) ?>" style="border: 0px">
				<?php echo html::image('/application/views/themes/default/images/icons/16x16/nacoma.png',array('alt' => $this->translate->_('Configure this service'),'title' => $this->translate->_('Configure this service')))?>
			</a>
		</td>
	</tr>

	<?php
			$curr_host = $row->host_name;
		} ?>
		</tbody>
	</table>

	<div id="status_count_summary"><?php echo sizeof($result) ?> Matching Service Entries Displayed</div>
<?php } ?>
</div>