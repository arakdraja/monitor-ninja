<div class="popup-about">
	<a target="_blank" href="http://www.op5.com">
		<img class="popup-about-img" src="/ninja/modules/menu/views/itrs-about-logo.png">
	</a>
  <table class="popup-about-table">
    <tr class="popup-about-row popup-about-row-links">
      <td>
        <a href="https://docs.itrsgroup.com/docs/op5-monitor/">Knowledge Base</a>
      </td>
      <td>
        <a href="http://www.op5.com">www.op5.com</a>
      </td>
      <td>
        <a href="http://www.op5.com/support">Support</a>
      </td>
    </tr>
  </table>
<?php
$data = $about->get_all();
echo html::get_definition_list($data);
?>
</div>
