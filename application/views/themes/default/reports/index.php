<?php defined('SYSPATH') OR die('No direct access allowed.');

if (!empty($widgets)) {
	foreach ($widgets as $widget) {
		echo $widget;
	}
}
?>

<div id="response"></div>
<div id="progress"></div>
<div class="report-page">
<?php
	echo isset($error) ? $error : '';
	echo !empty($header) ? $header : '';
	echo !empty($report_options) ? $report_options : '';
	if (isset($links)) {
		echo '<br /><br />'._('View').': ';
		$html_links = array();
		foreach($links as $url => $name) {
			$html_links[] = html::anchor(url::site($url),html::image($this->add_path('/icons/16x16/'.strtolower(str_replace(' ','-',$name))).'.png',array('alt' => $name, 'title' => $name, 'style' => 'margin-bottom: -3px')),array('style' => 'border: 0px')).
			' <a href="'.url::site($url).'">'.$name.'</a>';
		}
		echo implode(', &nbsp;', $html_links);
	}
	if (!empty($trends_graph)) {
		echo '<strong style="margin-top: 25px;display: block">'.help::render('trends').' '._('Trends').'</strong>';
		echo $trends_graph;
	}
	echo !empty($content) ? $content : '';
	echo !empty($svc_content) ? $svc_content : '';
	echo isset($pie) ? $pie : '';
	echo !empty($log_content) ? $log_content : '';
?>
</div>
