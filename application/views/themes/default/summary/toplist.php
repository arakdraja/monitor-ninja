<?php defined('SYSPATH') OR die("No direct access allowed"); ?>

<div class="widget left w98">
	<table>
		<tr>
			<th class="headerNone left"><?php echo $label_rank; ?></th>
			<th class="headerNone left"><?php echo $label_producer_type; ?></th>
			<th class="headerNone left"><?php echo $label_host; ?></th>
			<th class="headerNone left"><?php echo $label_service; ?></th>
			<th class="headerNone left"><?php echo $label_total_alerts; ?></th>
		</tr>
		<?php
		$i=0;
		foreach ($result as $rank => $ary) {
			$i++;
			echo '<tr class="'.($i%2 == 0 ? 'odd' : 'even').'">';
			if (empty($ary['service_description'])) {
				$producer = $label_host;
				$ary['service_description'] = 'N/A';
			} else {
				$producer = $label_service;
			}
		?>
			<td><?php echo $rank; ?></td>
			<td><?php echo $producer; ?></td>
			<td><?php echo $ary['host_name']; ?></td>
			<td><?php echo $ary['service_description']; ?></td>
			<td><?php echo $ary['total_alerts']; ?></td>
		</tr>
		<?php } ?>
	</table>
</div>

<?php // printf("Report completed in %.3f seconds<br />\n", $completion_time); ?>
