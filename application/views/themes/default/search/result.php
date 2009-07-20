<?php defined('SYSPATH') OR die('No direct access allowed.');
$label_na = $this->translate->_('N/A');
?>

<div class="widget left w98" id="search_result">
	<!--<p><strong><?php echo $this->translate->_('Search result for'); ?> &quot;<?php echo $query ?>&quot;</strong>:</p>-->

<?php echo isset($no_data) ? $no_data : '';
# show host data if available
if (isset($host_result) ) { ?>

<table>
	<caption><?php echo $this->translate->_('Host results for').': &quot;'.$query.'&quot'; ?></caption>
	<tr>
		<th class="header">&nbsp;</th>
		<th class="header"><?php echo $this->translate->_('Host'); ?></th>
		<th class="header"><?php echo $this->translate->_('Alias'); ?></th>
		<th class="header" style="width: 70px"><?php echo $this->translate->_('Address'); ?></th>
		<th class="header"><?php echo $this->translate->_('Status Information'); ?></th>
		<th class="header"><?php echo $this->translate->_('Display Name'); ?></th>
		<?php //if (isset ($nacoma_link)) { ?>
		<th class="header" <?php echo (isset ($nacoma_link)) ? 'colspan="2"' : '' ?>>&nbsp;</th>
		<?php //} ?>
	</tr>
<?php	$i = 0; foreach ($host_result as $host) { ?>
	<tr class="<?php echo ($i%2 == 0) ? 'even' : 'odd' ?>">
		<td class="bl icon">
			<?php echo html::image($this->add_path('icons/16x16/shield-'.strtolower(Current_status_Model::status_text($host->current_state)).'.png'),array('alt' => Current_status_Model::status_text($host->current_state), 'title' => $this->translate->_('Host status').': '.Current_status_Model::status_text($host->current_state))); ?>
		</td>
		<td><?php echo html::anchor('extinfo/details/host/'.$host->host_name, $host->host_name) ?></td>
		<td style="white-space: normal"><?php echo $host->alias ?></td>
		<td><?php echo $host->address ?></td>
		<td style="white-space	: normal"><?php echo str_replace('','',$host->output) ?></td>
		<td><?php echo $host->display_name ?></td>
		<td class="icon">
			<?php echo html::anchor('status/service/'.$host->host_name,html::image($this->add_path('icons/16x16/service-details.gif'), $this->translate->_('View service details for this host')), array('style' => 'border: 0px')) ?>
		</td>
		<?php if (isset ($nacoma_link)) { ?>
		<td class="icon">
			<?php echo html::anchor($nacoma_link.'host/'.$host->host_name, html::image($this->img_path('icons/16x16/nacoma.png'), array('alt' => $label_nacoma, 'title' => $label_nacoma))) ?>
		</td>
		<?php } ?>
	</tr>
<?php	$i++; } ?>
</table><br /><?php
}

# show service data if available
if (isset($service_result) ) { ?>

<table>
<caption><?php echo $this->translate->_('Service results for').': &quot;'.$query.'&quot'; ?></caption>
	<tr>
		<th class="header">&nbsp;</th>
		<th class="header"><?php echo $this->translate->_('Host'); ?></th>
		<th class="header">&nbsp;</th>
		<th class="header"><?php echo $this->translate->_('Service'); ?></th>
		<th class="header"><?php echo $this->translate->_('Last Check'); ?></th>
		<th class="header"><?php echo $this->translate->_('Display name'); ?></th>
		<?php if (isset ($nacoma_link)) { ?>
		<th class="header">&nbsp;</th>
		<?php } ?>
	</tr>
<?php
	$i = 0;
	$prev_host = false;
	foreach ($service_result as $service) { ?>
	<tr class="<?php echo ($i%2 == 0) ? 'even' : 'odd' ?>">
		<?php if ($prev_host != $service->host_name) { ?>
		<td class="bl icon"><?php echo html::image($this->add_path('icons/16x16/shield-'.strtolower(Current_status_Model::status_text($service->host_state)).'.png'),array('alt' => Current_status_Model::status_text($service->host_state), 'title' => $this->translate->_('Host status').': '.Current_status_Model::status_text($service->host_state))); ?></td>
		<td><?php echo html::anchor('extinfo/details/host/'.$service->host_name, $service->host_name) ?></td>
		<?php } else { ?>
		<td colspan="2" class="white" style="background-color:#ffffff;border=0"></td>
		<?php } ?>
		<td class="icon"><?php echo html::image($this->add_path('icons/16x16/shield-'.strtolower(Current_status_Model::status_text($service->current_state, 'service')).'.png'),array('alt' => Current_status_Model::status_text($service->current_state, 'service'), 'title' => $this->translate->_('Service status').': '.Current_status_Model::status_text($service->current_state, 'service'))); ?></td>
		<td>
			<?php echo html::anchor('/extinfo/details/service/'.$service->host_name.'?service='.urlencode($service->service_description), $service->service_description) ?>
		</td>
		<td><?php echo $service->last_check ? date('Y-m-d H:i:s',$service->last_check) : $label_na ?></td>
		<td><?php echo $service->display_name ?></td>
		<?php if (isset ($nacoma_link)) { ?>
		<td class="icon">
			<?php echo html::anchor($nacoma_link.'service/'.$service->host_name.'?service='.urlencode($service->service_description), html::image($this->img_path('icons/16x16/nacoma.png'), array('alt' => $label_nacoma, 'title' => $label_nacoma))) ?>
		</td>
		<?php } ?>
	</tr>
<?php	$i++;
	$prev_host = $service->host_name;
	} ?>
</table><br /><?php
}

# show hostgroup data if available
if (isset($servicegroup_result) ) { ?>
<table>
<caption><?php echo $this->translate->_('Servicegroup results for').': &quot;'.$query.'&quot'; ?></caption>
	<tr>
		<th class="header"><?php echo $this->translate->_('Servicegroup'); ?></th>
		<th class="header"><?php echo $this->translate->_('Alias'); ?></th>
	</tr>
<?php	$i = 0; foreach ($servicegroup_result as $servicegroup) { ?>
	<tr class="<?php echo ($i%2 == 0) ? 'even' : 'odd' ?>">
		<td class="bl"><?php echo html::anchor('extinfo/details/servicegroup/'.$servicegroup->servicegroup_name, $servicegroup->servicegroup_name) ?></td>
		<td><?php echo html::anchor('status/servicegroup/'.$servicegroup->servicegroup_name.'?style=detail', $servicegroup->alias) ?></td>
	</tr>
<?php $i++;	} ?>
</table><?php
}