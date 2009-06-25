<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Status controller
 *
 * @package NINJA
 * @author op5 AB
 * @license GPL
 */
class Status_Controller extends Authenticated_Controller {
	public $current = false;
	public $img_sort_up = false;
	public $img_sort_down = false;
	public $logos_path = '';
	public $hoststatustypes = false;
	public $servicestatustypes = false;
	public $hostprops = false;
	public $serviceprops = false;

	public function __construct()
	{
		parent::__construct();

		# load current status for host/service status totals
		$this->current = new Current_status_Model();
		$this->current->analyze_status_data();
		$this->xtra_js[] = $this->add_path('/js/widgets.js');

		$this->logos_path = Kohana::config('config.logos_path');
	}

	/**
	 * Equivalent to style=hostdetail
	 *
	 * @param $host
	 * @param $hoststatustypes
	 * @param $sort_order
	 * @param $sort_field
	 * @param $show_services
	 */
	public function host($host='all', $hoststatustypes=false, $sort_order='ASC', $sort_field='host_name', $show_services=false, $group_type=false, $serviceprops=false, $hostprops=false)
	{
		$host = urldecode($this->input->get('host', $host));
		$items_per_page = urldecode($this->input->get('items_per_page', Kohana::config('pagination.default.items_per_page'))); # @@@FIXME: should be configurable from GUI
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$sort_order = urldecode($this->input->get('sort_order', $sort_order));
		$sort_field = urldecode($this->input->get('sort_field', $sort_field));
		$show_services = urldecode($this->input->get('show_services', $show_services));
		$group_type = urldecode($this->input->get('group_type', $group_type));
		$serviceprops = urldecode($this->input->get('serviceprops', $serviceprops));
		$hostprops = urldecode($this->input->get('hostprops', $hostprops));
		$group_type = urldecode(strtolower($group_type));

		$host = trim($host);
		$hoststatustypes = strtolower($hoststatustypes)==='false' ? false : $hoststatustypes;

		$replace = array(
			1  => $this->translate->_('OK'),
			2  => $this->translate->_('Down'),
			4  => $this->translate->_('Unreachable'),
			6  => $this->translate->_('All problems'),
			64 => $this->translate->_('Pending')
		);

		$title = $this->translate->_('Monitoring » Host details').($hoststatustypes != false ? ' » '.$replace[$hoststatustypes] : '');
		$this->template->title = $title;

		$this->hoststatustypes = $hoststatustypes;
		$this->hostprops = $hostprops;
		$this->serviceprops = $serviceprops;
		$filters = $this->_show_filters();

		$this->template->content = $this->add_view('status/host');
		$this->template->content->filters = $filters;
		$this->template->js_header = $this->add_view('js_header');
		$this->template->css_header = $this->add_view('css_header');

		widget::add('status_totals', array($this->current, $host, $hoststatustypes, false, $group_type, $serviceprops, $hostprops), $this);
		//$this->xtra_css = array_merge($this->xtra_css, array($this->add_path('/css/default/common.css')));
		$this->template->content->widgets = $this->widgets;
		$this->template->js_header->js = $this->xtra_js;
		$this->template->css_header->css = $this->xtra_css;

		# set sort images, used in header_links() below
		$this->img_sort_up = $this->img_path('icons/16x16/up.gif');
		$this->img_sort_down = $this->img_path('icons/16x16/down.gif');

		# assign specific header fields and values for current method
		$header_link_fields = array(
			array('title' => $this->translate->_('Status'), 'sort_field_db' => 'current_state', 'sort_field_str' => 'host status'),
			array('title' => $this->translate->_('Host'), 'sort_field_db' => 'host_name', 'sort_field_str' => 'host name'),
			array('title' => $this->translate->_('Last 	'), 'sort_field_db' => 'last_check', 'sort_field_str' => 'last check time'),
			array('title' => $this->translate->_('Duration'), 'sort_field_db' => 'duration', 'sort_field_str' => 'state duration'),
			array('title' => $this->translate->_('Status Information'))
		);

		# build header links array
		foreach ($header_link_fields as $fields) {
			if (sizeof($fields) > 1) {
				$header_links[] = $this->header_links(Router::$method, $host, $fields['title'], Router::$method, $fields['sort_field_db'], $fields['sort_field_str'], $hoststatustypes, false);
			} else {
				$header_links[] = $this->header_links(Router::$method, $host, $fields['title']);
			}
		}

		$this->template->content->header_links = $header_links;

		$shown = strtolower($host) == 'all' ? $this->translate->_('All Hosts') : $this->translate->_('Host')." '".$host."'";
		$sub_title = $this->translate->_('Host Status Details For').' '.$shown;
		$this->template->content->sub_title = $sub_title;

		# here we should fetch members of group if group_type is set and pass to get_host_status()
		$host_model = new Host_Model();
		$host_model->show_services = $show_services;
		$host_model->state_filter = $hoststatustypes;
		$host_model->set_sort_field($sort_field);
		$host_model->set_sort_order($sort_order);
		$host_model->serviceprops = $serviceprops;
		$host_model->hostprops = $hostprops;
		$host_model->count = true;

		if (!empty($group_type)) {
			# we ned to remove the 'group' part of the group_type variable value
			# since the method we are about to call expects 'host' or 'service'
			$grouptype = str_replace('group', '', $group_type);
			$group_info_res = Group_Model::get_group_info($grouptype, $host);
			if ($group_info_res) {
				$group_members = false;
				foreach ($group_info_res as $row) {
					$group_members[] = $row->host_name;
				}
			}

			$host_model->set_host_list($group_members);

			$result_cnt = $host_model->get_host_status();

			$tot = $result_cnt !== false ? $result_cnt->current()->cnt : 0;
			$pagination = new Pagination(
				array(
					'total_items'=> $tot,
					'items_per_page' => $items_per_page
				)
			);
			$offset = $pagination->sql_offset;
			$host_model->count = false;
			$host_model->items_per_page = $items_per_page;
			$host_model->offset = $offset;

			$result = $host_model->get_host_status();
		} else {
			$host_model->set_host_list($host);
			$result_cnt = $host_model->get_host_status();

			$tot = $result_cnt !== false ? $result_cnt->current()->cnt : 0;
			$pagination = new Pagination(
				array(
					'total_items'=> $tot,
					'items_per_page' => $items_per_page
				)
			);
			$offset = $pagination->sql_offset;
			$host_model->count = false;
			$host_model->items_per_page = $items_per_page;
			$host_model->offset = $offset;

			$result = $host_model->get_host_status();
		}

		$this->template->content->result = $result;
		$this->template->content->pagination = $pagination;
		$this->template->content->total_items = $tot;
		$this->template->content->logos_path = $this->logos_path;

		if (empty($group_type)) {
			if ($host == 'all') {
				$label_host_status_details = $this->translate->_('View Service Status Detail For All Host Groups');
				$label_host_status_overview = $this->translate->_('View Status Overview For All Host Groups');
				$label_host_status_summary = $this->translate->_('View Status Summary For All Host Groups');
				$label_host_status_grid = $this->translate->_('View Status Grid For All Host Groups');
				$page_links = array(
					 $label_host_status_details => Router::$controller.'/hostgroup/all?style=detail',
					 $label_host_status_overview => Router::$controller.'/hostgroup/all',
					 $label_host_status_summary => Router::$controller.'/hostgroup/all?style=summary',
					 $label_host_status_grid => Router::$controller.'/hostgroup_grid/all'
				);
			} else {
				$label_host_history = $this->translate->_('View History For This Host');
				$label_host_notifications = $this->translate->_('View Notifications This Host');
				$label_host_status_details = $this->translate->_('View Service Status Detail For All Hosts');
				$page_links = array(
					 $label_host_history => 'history/host/'.$host,
					 $label_host_notifications => 'notifications/host/'.$host,
					 $label_host_status_details => Router::$controller.'/service/all'
				);
			}
		} else {
			if ($group_type == 'hostgroup') {
				$label_group_status_details_all = $this->translate->_('View Host Status Detail For All Host Groups');
				$label_group_status_details = $this->translate->_('View Service Status Detail For This Host Group');
				$label_group_status_overview = $this->translate->_('View Status Overview For This Host Group');
				$label_group_status_summary = $this->translate->_('View Status Summary For This Host Group');
				$label_group_status_grid = $this->translate->_('View Status Grid For This Host Group');

				$page_links = array(
					$label_group_status_details_all => Router::$controller.'/host/all',
					$label_group_status_details => Router::$controller.'/hostgroup/'.$host.'?style=detail',
					$label_group_status_overview => Router::$controller.'/'.$group_type.'/'.$host,
					$label_group_status_summary => Router::$controller.'/'.$group_type.'_summary/'.$host,
					$label_group_status_grid => Router::$controller.'/'.$group_type.'_grid/'.$host
				);

			} else {
				$label_group_status_overview = $this->translate->_('View Status Overview For This Service Group');
				$label_group_status_summary = $this->translate->_('View Status Summary For This Service Group');
				$label_group_status_grid = $this->translate->_('View Service Status Grid For This Service Group');
				$label_service_status_details = $this->translate->_('View Service Status Detail For All Service Groups');

				$page_links = array(
					$label_group_status_overview => Router::$controller.'/'.$group_type.'group/'.$host,
					$label_group_status_summary => Router::$controller.'/'.$group_type.'group/'.$host.'?style=summary',
					$label_group_status_grid => Router::$controller.'/'.$group_type.'group_grid/'.$host,
					$label_service_status_details => Router::$controller.'/'.$group_type.'group/all?style=detail'
				);
			}
		}
		if (isset($page_links)) {
			$this->template->content->page_links = $page_links;
		}
	}

	/**
	 * List status details for hosts and services
	 *
	 * @param $name
	 * @param $servicestatustypes
	 * @param $hoststatustypes
	 * @param $sort_order
	 * @param $sort_field
	 * @param $group_type
	 */
	public function service($name='all', $hoststatustypes=false, $servicestatustypes=false, $service_props=false, $sort_order='ASC', $sort_field='host_name', $group_type=false, $hostprops=false)
	{
		$name = urldecode($this->input->get('name', $name));
		$items_per_page = urldecode($this->input->get('items_per_page', Kohana::config('pagination.default.items_per_page'))); # @@@FIXME: should be configurable from GUI
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));
		$service_props = urldecode($this->input->get('service_props', $service_props));
		$hostprops = urldecode($this->input->get('hostprops', $hostprops));
		$sort_order = urldecode($this->input->get('sort_order', $sort_order));
		$sort_field = urldecode($this->input->get('sort_field', $sort_field));
		$group_type = urldecode($this->input->get('group_type', $group_type));
		$group_type = strtolower($group_type);

		$name = trim($name);
		$hoststatustypes = strtolower($hoststatustypes)==='false' ? false : $hoststatustypes;
		$servicestatustypes = strtolower($servicestatustypes)==='false' ? false : $servicestatustypes;

		$srv_replace = array(
			1  => $this->translate->_('OK'),
			2  => $this->translate->_('Warning'),
			4  => $this->translate->_('Critical'),
			8  => $this->translate->_('Unknown'),
			14 => $this->translate->_('All problems'),
			64 => $this->translate->_('Pending'),
			65 => $this->translate->_('Non-problem services'),
			71 => $this->translate->_('All services'),
		);

		$host_replace = array(
			1  => $this->translate->_('Host OK'),
			2  => $this->translate->_('Host Down'),
			4  => $this->translate->_('Host Unreachable'),
			6  => $this->translate->_('All host problems'),
			64 => $this->translate->_('Host Pending'),
			65 => $this->translate->_('Non-problem hosts'),
			71 => $this->translate->_('All hosts'),
		);

		$title = $this->translate->_('Monitoring » Service details').
			($hoststatustypes != false ? ' » '.$host_replace[$hoststatustypes] : '').
			($servicestatustypes != false ? ' » '.$srv_replace[$servicestatustypes] : '');

		$this->template->title = $title;

		$sort_order = $sort_order == 'false' || empty($sort_order) ? 'ASC' : $sort_order;
		$sort_field = $sort_field == 'false' || empty($sort_field) ? 'host_name' : $sort_field;

		$this->hoststatustypes = $hoststatustypes;
		$this->hostprops = $hostprops;
		$this->servicestatustypes = $servicestatustypes;
		$this->serviceprops = $service_props;
		$filters = $this->_show_filters();

		$this->template->content = $this->add_view('status/service');
		$this->template->content->filters = $filters;
		$this->template->js_header = $this->add_view('js_header');
		$this->template->css_header = $this->add_view('css_header');

		widget::add('status_totals', array($this->current, $name, $hoststatustypes, $servicestatustypes, $group_type), $this);
		//$this->xtra_css = array_merge($this->xtra_css, array($this->add_path('/css/default/common.css')));
		$this->template->content->widgets = $this->widgets;
		$this->template->js_header->js = $this->xtra_js;
		$this->template->css_header->css = $this->xtra_css;

		# set sort images, used in header_links() below
		$this->img_sort_up = $this->img_path('icons/arrow-up.gif');
		$this->img_sort_down = $this->img_path('icons/arrow-down.gif');

		# assign specific header fields and values for current method
		$header_link_fields = array(
			array('title' => $this->translate->_('Host'), 'sort_field_db' => 'h.host_name', 'sort_field_str' => 'host name'),
			array('title' => $this->translate->_('Status'), 'sort_field_db' => 's.current_state', 'sort_field_str' => 'service status'),
			array('title' => $this->translate->_('Service'), 'sort_field_db' => 's.service_description', 'sort_field_str' => 'service name'),
			array('title' => $this->translate->_('Last Check'), 'sort_field_db' => 'last_check', 'sort_field_str' => 'last check time'),
			array('title' => $this->translate->_('Duration'), 'sort_field_db' => 'duration', 'sort_field_str' => 'state duration'),
			array('title' => $this->translate->_('Status Information'))
		);

		# build header links array
		foreach ($header_link_fields as $fields) {
			if (sizeof($fields) > 1) {
				$header_links[] = $this->header_links(Router::$method, $name, $fields['title'], Router::$method, $fields['sort_field_db'], $fields['sort_field_str'], $hoststatustypes, $servicestatustypes, $service_props);
			} else {
				$header_links[] = $this->header_links(Router::$method, $name, $fields['title']);
			}
		}

		$this->template->content->header_links = $header_links;

		$shown = strtolower($name) == 'all' ? $this->translate->_('All Hosts') : $this->translate->_('Host')." '".$name."'";

		# handle host- or servicegroup details
		$host_model = new Host_Model();
		$host_model->show_services = true;
		$host_model->state_filter = $hoststatustypes;
		$host_model->service_filter = $servicestatustypes;
		$host_model->set_sort_field($sort_field);
		$host_model->set_sort_order($sort_order);
		$host_model->serviceprops = $service_props;
		$host_model->hostprops = $hostprops;

		if (!empty($group_type)) {
			$shown = $group_type == 'servicegroup' ? $this->translate->_('Service Group') : $this->translate->_('Host Group');
			$shown .= " '".$name."'";
			# convert 'servicegroup' to 'service' and 'hostgroup' to 'host'
			$grouptype = str_replace('group', '', $group_type);
			$hostlist = Group_Model::get_group_hoststatus($grouptype, $name, $hoststatustypes, $servicestatustypes, $service_props, $hostprops);
			$group_hosts = false;
			foreach ($hostlist as $host_info) {
				$group_hosts[] = $host_info->host_name;
			}

			# servicegroups should only show services in the group
			if ($group_type == 'servicegroup') {
				$result = Group_Model::get_group_info($grouptype, $name);
			} else {
				$host_model->set_host_list($group_hosts);
				$result = $host_model->get_host_status();
			}
		} else {
			$host_model->num_per_page = false;
			$host_model->offset = false;
			$host_model->count = true;

			$host_model->set_host_list($name);
			$result_cnt = $host_model->get_host_status();
			$tot = $result_cnt !== false ? $result_cnt->current()->cnt : 0;
			$pagination = new Pagination(
				array(
					'total_items'=> $tot,
					'items_per_page' => $items_per_page
				)
			);
			$offset = $pagination->sql_offset;
			$host_model->count = false;
			$host_model->num_per_page = $items_per_page;
			$host_model->offset = $offset;

			$host_model->set_host_list($name);
			$result = $host_model->get_host_status();
		}
		$sub_title = $this->translate->_('Service Status Details For').' '.$shown;
		$this->template->content->sub_title = $sub_title;

		$this->template->content->result = $result;
		$this->template->content->pagination = isset($pagination) ? $pagination : false;
		$this->template->content->logos_path = $this->logos_path;
		if (empty($group_type)) {
			if ($name == 'all') {
				$label_host_history = $this->translate->_('View History For all host');
				$label_host_notifications = $this->translate->_('View Notifications For all hosts');
				$label_host_status_details = $this->translate->_('View Host Status Detail For All Hosts');
				$page_links = array(
					 $label_host_history => 'history/host/'.$name,
					 $label_host_notifications => 'notifications/host/'.$name,
					 $label_host_status_details => Router::$controller.'/host/all'
				);
			} else {
				$label_host_history = $this->translate->_('View History For This Host');
				$label_host_notifications = $this->translate->_('View Notifications This Host');
				$label_host_status_details = $this->translate->_('View Service Status Detail For All Hosts');
				$page_links = array(
					 $label_host_history => 'history/host/'.$name,
					 $label_host_notifications => 'notifications/host/'.$name,
					 $label_host_status_details => Router::$controller.'/service/all'
				);
			}
		} else {
			if ($group_type == 'hostgroup') {
				if ($name == 'all') {
					$label_group_status_details = $this->translate->_('View Host Status Detail For All Host Groups');
					$label_group_status_overview = $this->translate->_('View Status Overview For All Host Groups');
					$label_group_status_summary = $this->translate->_('View Status Summary For All Host Groups');
					$label_group_status_grid = $this->translate->_('View Status Grid For All Host Groups');
					$page_links = array(
						$label_group_status_details => Router::$controller.'/host/all',
						$label_group_status_overview => Router::$controller.'/'.$group_type.'/all',
						$label_group_status_summary => Router::$controller.'/'.$group_type.'/all?style=summary',
						$label_group_status_grid => Router::$controller.'/'.$group_type.'_grid/all',
					);
				} else {
					$label_group_status_details_all = $this->translate->_('View Service Status Detail For All Host Groups');
					$label_group_status_details = $this->translate->_('View Host Status Detail For This Host Group');
					$label_group_status_overview = $this->translate->_('View Status Overview For This Host Group');
					$label_group_status_summary = $this->translate->_('View Status Summary For This Host Group');
					$label_group_status_grid = $this->translate->_('View Status Grid For This Host Group');
					$page_links = array(
						$label_group_status_details_all => Router::$controller.'/'.$group_type.'/all?style=detail',
						$label_group_status_details => Router::$controller.'/host/'.$name.'?group_type='.$group_type,
						$label_group_status_overview => Router::$controller.'/'.$group_type.'/'.$name,
						$label_group_status_summary => Router::$controller.'/'.$group_type.'_summary/'.$name,
						$label_group_status_grid => Router::$controller.'/'.$group_type.'_grid/'.$name,
					);
				}
			} else {
				# servicegroup links
				if ($name == 'all') {
					$label_service_status_overview = $this->translate->_('View Status Overview For All Service Groups');
					$label_group_status_summary = $this->translate->_('View Status Summary For All Service Groups');
					$label_host_status_grid = $this->translate->_('View Service Status Grid For All Service Groups');

					$page_links = array(
						$label_service_status_overview => Router::$controller.'/'.$group_type.'group/all',
						$label_group_status_summary => Router::$controller.'/'.$group_type.'group/all?style=summary',
						$label_host_status_grid => Router::$controller.'/'.$group_type.'group_grid/all'
					);
				} else {
					$label_group_status_overview = $this->translate->_('View Status Overview For This Service Group');
					$label_group_status_summary = $this->translate->_('View Status Summary For This Service Group');
					$label_group_status_grid = $this->translate->_('View Service Status Grid For This Service Group');
					$label_group_status_details = $this->translate->_('View Service Status Detail For All Service Groups');
					$page_links = array(
						$label_group_status_overview => Router::$controller.'/'.$grouptype.'group/'.$name,
						$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/'.$name.'?style=summary',
						$label_group_status_grid => Router::$controller.'/'.$grouptype.'group_grid/'.$name,
						$label_group_status_details => Router::$controller.'/'.$grouptype.'/all'
					);
				}
			}
		}
		if (isset($page_links)) {
			$this->template->content->page_links = $page_links;
		}
	}

	/**
	 * Show servicegroup status, wrapper for group('service', ...)
	 * @param $group
	 * @param $hoststatustypes
	 * @param $servicestatustypes
	 * @param $style
	 *
	 */
	public function servicegroup($group='all', $hoststatustypes=false, $servicestatustypes=false, $style='overview', $serviceprops=false, $hostprops=false)
	{
		$group = urldecode($this->input->get('group', $group));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));
		$serviceprops = urldecode($this->input->get('serviceprops', $serviceprops));
		$hostprops = urldecode($this->input->get('hostprops', $hostprops));
		$style = urldecode($this->input->get('style', $style));
		$grouptype = 'service';
		return $this->group($grouptype, $group, $hoststatustypes, $servicestatustypes, $style, $serviceprops, $hostprops);

		$this->template->title = 'Servicegroup';
	}

	/**
	 * Show hostgroup status, wrapper for group('host', ...)
	 * @param $group
	 * @param $hoststatustypes
	 * @param $servicestatustypes
	 * @param $style
	 *
	 */
	public function hostgroup($group='all', $hoststatustypes=false, $servicestatustypes=false, $style='overview', $serviceprops=false, $hostprops=false)
	{
		$group = urldecode($this->input->get('group', $group));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));
		$serviceprops = urldecode($this->input->get('serviceprops', $serviceprops));
		$hostprops = urldecode($this->input->get('hostprops', $hostprops));
		$style = urldecode($this->input->get('style', $style));
		$grouptype = 'host';
		return $this->group($grouptype, $group, $hoststatustypes, $servicestatustypes, $style, $serviceprops, $hostprops);
	}

	/**
	 * Show status for host- or servicegroups
	 *
	 * @param $grouptype
	 * @param $group
	 * @param $hoststatustypes
	 * @param $servicestatustypes
	 * @param $style
	 */

	public function group($grouptype='service', $group='all', $hoststatustypes=false, $servicestatustypes=false, $style='overview', $serviceprops=false, $hostprops=false)
	{
		$grouptype = urldecode($this->input->get('grouptype', $grouptype));
		$group = urldecode($this->input->get('group', $group));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));
		$serviceprops = urldecode($this->input->get('serviceprops', $serviceprops));
		$hostprops = urldecode($this->input->get('hostprops', $hostprops));
		$style = urldecode($this->input->get('style', $style));
		$group = trim($group);
		$hoststatustypes = strtolower($hoststatustypes)==='false' ? false : $hoststatustypes;

		$this->hoststatustypes = $hoststatustypes;
		$this->hostprops = $hostprops;
		$this->servicestatustypes = $servicestatustypes;
		$this->serviceprops = $serviceprops;

		switch ($style) {
			case 'overview':
				$this->template->title = $this->translate->_('Monitoring » ').$grouptype.$this->translate->_('group overview');
				$this->template->header = $this->translate->_('Monitoring » ').$grouptype.$this->translate->_('group overview');
				$this->template->content = $this->add_view('status/group_overview');
				break;
			case 'detail': case 'details':
				$this->template->title = $grouptype.$this->translate->_('group » Details');
				return $this->service($group, $hoststatustypes, $servicestatustypes, $serviceprops, false, false, $grouptype.'group', $hostprops);
				break;
			case 'summary':
				return $this->group_summary($grouptype, $group, $hoststatustypes, $servicestatustypes, $serviceprops, $hostprops);
				break;
		}
		$group_details = false;
		$groupname_tmp = false;
		if ($group == 'all') {
			$group_info_res = $grouptype == 'service' ? Servicegroup_Model::get_all() : Hostgroup_Model::get_all();
			foreach ($group_info_res as $group_res) {
				$groupname_tmp = $group_res->{$grouptype.'group_name'}; # different db field depending on host- or servicegroup
				$group_details[] = $this->_show_group($grouptype, $groupname_tmp, $hoststatustypes, $servicestatustypes, $style, $serviceprops, $hostprops);
			}
		} else {
			$group_details[] = $this->_show_group($grouptype, $group, $hoststatustypes, $servicestatustypes, $style, $serviceprops, $hostprops);
		}

		$this->template->content->group_details = $group_details;

		$this->template->js_header = $this->add_view('js_header');
		$this->template->css_header = $this->add_view('css_header');

		widget::add('status_totals', array($this->current, $group, $hoststatustypes, $servicestatustypes, $grouptype.'group', $serviceprops, $hostprops), $this);
		//$this->xtra_css = array_merge($this->xtra_css, array($this->add_path('/css/default/common.css')));
		$this->template->content->widgets = $this->widgets;
		$this->template->js_header->js = $this->xtra_js;
		$this->template->css_header->css = $this->xtra_css;

		$content = $this->template->content;
		$t = $this->translate;

		if ($grouptype == 'service') {
			$content->lable_header = strtolower($group) == 'all' ? $t->_("Service Overview For All Service Groups") : $t->_("Service Overview For Service Group");
		} else {
			$content->lable_header = strtolower($group) == 'all' ? $t->_("Service Overview For All Host Groups") : $t->_("Service Overview For Host Group");
		}
		$content->lable_host = $t->_('Host');
		$content->lable_status = $t->_('Status');
		$content->lable_services = $t->_('Services');
		$content->lable_actions = $t->_('Actions');
		$content->grouptype = $grouptype;
		if (empty($group_details)) {
			$this->template->content = $this->add_view('error');
			$this->template->content->error_message = $t->_("No data found");
			return;
		}
		# @@@FIXME: handle macros
		if ($grouptype == 'host') {
			if ($group == 'all') {
				$label_host_status_details = $this->translate->_('View Service Status Detail For All Host Groups');
				$label_group_status_details = $this->translate->_('View Host Status Detail For All Host Groups');
				$label_group_status_summary = $this->translate->_('View Status Summary For All Host Groups');
				$label_host_status_grid = $this->translate->_('View Status Grid For All Host Groups');
				$page_links = array(
					$label_host_status_details => Router::$controller.'/'.$grouptype.'group/all?style=detail',
					$label_group_status_details => Router::$controller.'/service/all',
					$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/all?style=summary',
					$label_host_status_grid => Router::$controller.'/'.$grouptype.'group_grid/all'
				);
			} else {

				$label_group_status_overview_all = $this->translate->_('View Status Overview For All Host Groups');
				$label_group_status_service_details = $this->translate->_('View Service Status Detail For This Host Group');
				$label_group_status_host_details = $this->translate->_('View Host Status Detail For This Host Group');
				$label_group_status_summary = $this->translate->_('View Status Summary For This Host Group');
				$label_group_status_grid = $this->translate->_('View Service Status Grid For This Host Group');

				$page_links = array(

					$label_group_status_overview_all => Router::$controller.'/'.$grouptype.'group/all?style=summary',
					$label_group_status_service_details => Router::$controller.'/'.$grouptype.'group/'.$group.'?style=detail',
					$label_group_status_host_details => Router::$controller.'/host/'.$group.'?group_type='.$grouptype.'group',
					$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/'.$group.'?style=summary',
					$label_group_status_grid => Router::$controller.'/'.$grouptype.'group_grid/'.$group.'?style=summary'
				);
			}
		} else {
			if ($group == 'all') {
				$label_service_status_details = $this->translate->_('View Service Status Detail For All Service Groups');
				$label_group_status_summary = $this->translate->_('View Status Summary For All Service Groups');
				$label_host_status_grid = $this->translate->_('View Service Status Grid For All Service Groups');
				$page_links = array(
					$label_service_status_details => Router::$controller.'/'.$grouptype.'group/all?style=detail',
					$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/all?style=summary',
					$label_host_status_grid => Router::$controller.'/'.$grouptype.'group_grid/all'
				);
			} else {
				$label_group_status_overview = $this->translate->_('View Status Overview For This Service Group');
				$label_group_status_summary = $this->translate->_('View Status Summary For This Service Group');
				$label_group_status_grid = $this->translate->_('View Service Status Grid For This Service Group');
				$label_group_status_details = $this->translate->_('View Service Status Detail For All Service Groups');
				$page_links = array(
					$label_group_status_overview => Router::$controller.'/'.$grouptype.'group/'.$group,
					$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/'.$group.'?style=summary',
					$label_group_status_grid => Router::$controller.'/'.$grouptype.'group_grid/'.$group,
					$label_group_status_details => Router::$controller.'/'.$grouptype.'/all'
				);
			}
		}
		if (isset($page_links)) {
			$this->template->content->page_links = $page_links;
		}
	}

	/**
	 * Display servicegroup summary
	 */
	public function servicegroup_summary($group='all', $hoststatustypes=false, $servicestatustypes=false, $serviceprops=false, $hostprops=false)
	{
		$group = urldecode($this->input->get('group', $group));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));
		$serviceprops = urldecode($this->input->get('serviceprops', $serviceprops));
		$hostprops = urldecode($this->input->get('hostprops', $hostprops));
		$grouptype = 'service';
		return $this->group_summary($grouptype, $group, $hoststatustypes, $servicestatustypes, $serviceprops, $hostprops);
		$this->template->title = $this->translate->_('Servicegroup » Summary');
	}

	public function hostgroup_summary($group='all', $hoststatustypes=false, $servicestatustypes=false, $serviceprops=false, $hostprops=false)
	{
		$group = urldecode($this->input->get('group', $group));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));
		$serviceprops = urldecode($this->input->get('serviceprops', $serviceprops));
		$hostprops = urldecode($this->input->get('hostprops', $hostprops));
		$grouptype = 'host';
		return $this->group_summary($grouptype, $group, $hoststatustypes, $servicestatustypes, $serviceprops, $hostprops);
	}

	public function group_summary($grouptype='service', $group='all', $hoststatustypes=false, $servicestatustypes=false, $serviceprops=false, $hostprops=false)
	{
		$grouptype = urldecode($this->input->get('grouptype', $grouptype));
		$group = urldecode($this->input->get('group', $group));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));
		$serviceprops = urldecode($this->input->get('serviceprops', $serviceprops));
		$hostprops = urldecode($this->input->get('hostprops', $hostprops));
		$this->template->title = $this->translate->_('Monitoring » ').$grouptype.$this->translate->_('group summary');

		$group = trim($group);
		$this->template->content = $this->add_view('status/group_summary');
		$content = $this->template->content;
		$t = $this->translate;

		$this->hoststatustypes = $hoststatustypes;
		$this->hostprops = $hostprops;
		$this->servicestatustypes = $servicestatustypes;
		$this->serviceprops = $serviceprops;

		$this->template->js_header = $this->add_view('js_header');
		$this->template->css_header = $this->add_view('css_header');

		widget::add('status_totals', array($this->current, $group, $hoststatustypes, $servicestatustypes, $grouptype.'group', $serviceprops, $hostprops), $this);
		$this->template->content->widgets = $this->widgets;
		$this->template->js_header->js = $this->xtra_js;
		//$this->template->css_header->css = array_merge($this->xtra_css, array($this->add_path('/css/default/common.css')));

		$group_details = false;
		if (strtolower($group) == 'all') {
			$content->lable_header = $grouptype == 'service' ? $t->_('Status Summary For All Service Groups') : $t->_('Status Summary For All Host Groups');
			$group_info_res = $grouptype == 'service' ? Servicegroup_Model::get_all() : Hostgroup_Model::get_all();
			foreach ($group_info_res as $group_res) {
				$groupname_tmp = $group_res->{$grouptype.'group_name'};
				$group_details[] = $this->_show_group_totals_summary($grouptype, $groupname_tmp, $serviceprops, $hostprops);
			}
		} else {
			# make sure we have the correct servicegroup
			$group_info_res = $grouptype == 'service' ?
				Servicegroup_Model::get_by_field_value('servicegroup_name', $group) :
				Hostgroup_Model::get_by_field_value('hostgroup_name', $group);
			if ($group_info_res) {
				$group = $group_info_res->{$grouptype.'group_name'}; # different field depending on object type
			} else {
				# overwrite previous view with the error view, add some text and bail out
				$this->template->content = $this->add_view('error');
				$this->template->content->error_message = sprintf($t->_("The requested group ('%s') wasn't found"), $group);
				return;
			}
			$label_header = $grouptype == 'service' ? $t->_('Status Summary For Service Group ') : $t->_('Status Summary For Host Group ');
			$content->lable_header = $label_header."'".$group."'";
			$group_details[] = $this->_show_group_totals_summary($grouptype, $group, $serviceprops, $hostprops);
		}

		# since we don't use these values yet we define a default value
		$hostproperties = false;
		$serviceproperties = false;

		$content->grouptype = $grouptype;
		$content->hoststatustypes = $hoststatustypes;
		$content->hostproperties = $hostproperties;
		$content->servicestatustypes = $servicestatustypes;
		$content->serviceproperties = $serviceproperties;
		$content->label_group_name = $t->_('Service Group');
		$content->label_host_summary = $t->_('Host Status Summary');
		$content->label_service_summary = $t->_('Service Status Summary');
		$content->label_no_data = $t->_('No matching hosts');
		$content->label_up = $t->_('UP');
		$content->label_down = $t->_('DOWN');
		$content->label_unhandled = $t->_('Unhandled');
		$content->label_scheduled = $t->_('Scheduled');
		$content->label_acknowledged = $t->_('Acknowledged');
		$content->label_disabled = $t->_('Disabled');
		$content->label_unreachable = $t->_('UNREACHABLE');
		$content->label_pending = $t->_('PENDING');
		$content->label_ok = $t->_('OK');
		$content->label_warning = $t->_('WARNING');
		$content->label_on_problem_hosts = $t->_('on Problem Hosts');
		$content->label_unknown = $t->_('UNKNOWN');
		$content->label_critical = $t->_('CRITICAL');
		$content->label_no_servicedata = $t->_('No matching services');

		$content->group_details = $group_details;

		if ($grouptype == 'host') {
			$content->label_group_name = $t->_('Host Group');
			if ($group == 'all') {
				$label_host_status_details = $this->translate->_('View Service Status Detail For All Host Groups');
				$label_group_status_details = $this->translate->_('View Host Status Detail For All Host Groups');
				$label_group_status_overview = $this->translate->_('View Status Overview For All Host Groups');
				$label_host_status_grid = $this->translate->_('View Status Grid For All Host Groups');
				$page_links = array(
					$label_host_status_details => Router::$controller.'/'.$grouptype.'group/all?style=detail',
					$label_group_status_details => Router::$controller.'/host/all',
					$label_group_status_overview => Router::$controller.'/'.$grouptype.'group/all',
					$label_host_status_grid => Router::$controller.'/'.$grouptype.'group_grid/all'
				);
			} else {
				$label_group_status_summary = $this->translate->_('View Status Summary For All Host Groups');
				$label_group_service_status_details = $this->translate->_('View Service Status Detail For This Host Group');
				$label_group_host_status_details = $this->translate->_('View Host Status Detail For This Host Group');
				$label_group_status_overview = $this->translate->_('View Status Overview For This Host Group');
				$label_group_status_grid = $this->translate->_('View Status Grid For This Host Group');

				$page_links = array(
					$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/all?style=summary',
					$label_group_service_status_details => Router::$controller.'/'.$grouptype.'group/'.$group.'?style=detail',
					$label_group_host_status_details => Router::$controller.'/host/'.$group.'?group_type='.$grouptype.'group',
					$label_group_status_overview => Router::$controller.'/'.$grouptype.'group/'.$group,
					$label_group_status_grid => Router::$controller.'/'.$grouptype.'_grid/'.$group
				);
			}

		} else {
			$content->label_group_name = $t->_('Service Group');
			if ($group == 'all') {
				$label_service_status_details = $this->translate->_('View Service Status Detail For All Service Groups');
				$label_service_status_overview = $this->translate->_('View Status Overview For All Service Groups');
				$label_service_status_grid = $this->translate->_('View Service Status Grid For All Service Groups');
				$page_links = array(
					$label_service_status_details => Router::$controller.'/servicegroup/all?style=detail',
					$label_service_status_overview => Router::$controller.'/servicegroup/all',
					$label_service_status_grid => Router::$controller.'/servicegroup_grid/all'
				);
			} else {
				$label_service_status_details = $this->translate->_('View Service Status Detail For This Service Group');
				$label_group_status_overview = $this->translate->_('View Status Overview For This Service Group');
				$label_group_status_grid = $this->translate->_('View Service Status Grid For This Service Group');
				$label_group_status_summary = $this->translate->_('View Status Summary For All Service Groups');

				$page_links = array(
						$label_service_status_details => Router::$controller.'/host/'.$group.'?group_type='.$grouptype.'group',
						$label_group_status_overview => Router::$controller.'/'.$grouptype.'group/'.$group,
						$label_group_status_grid => Router::$controller.'/'.$grouptype.'group_grid/'.$group,
						$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/all?style=summary'
					);
			}
		}
		if (isset($page_links)) {
			$this->template->content->page_links = $page_links;
		}
	}

	/**
	 * shows host total summary information for a specific host- or servicegroup
	 *
	 * @return obj
	 */
	public function _show_group_totals_summary($grouptype='service', $group=false, $serviceprops=false, $hostprops=false)
	{
		$group = urldecode($this->input->get('group', $group));
		$content = false;
		#$hoststatustypes = strtolower($hoststatustypes)==='false' ? false : $hoststatustypes;
		$serviceprops = $this->serviceprops;
		$hostprops = $this->hostprops;

		$group_info_res = $grouptype == 'service' ?
			Servicegroup_Model::get_by_field_value('servicegroup_name', $group) :
			Hostgroup_Model::get_by_field_value('hostgroup_name', $group);
		if ($group_info_res === false) {
			return;
		}
		$hostlist = Group_Model::get_group_hoststatus($grouptype, $group);
		$content->group_alias = $group_info_res->alias;
		$content->groupname = $group;
		if ($hostlist->count() > 0) {
			$service_states = false;
			$hostinfo = false;
			$hosts_up = 0;
			$hosts_down = 0;
			$hosts_unreachable = 0;
			$hosts_pending = 0;
			$hosts_down_scheduled = 0;
			$hosts_down_acknowledged = 0;
			$hosts_down_disabled = 0;
			$hosts_down_unacknowledged = 0;
			$hosts_unreachable_scheduled = 0;
			$hosts_unreachable_acknowledged = 0;
			$hosts_unreachable_disabled = 0;
			$hosts_unreachable_unacknowledged = 0;
			$seen_hosts = array();
			foreach ($hostlist as $host) {
				$host_problem = true;
				if (in_array($host->host_name, $seen_hosts))
					continue;
				switch ($host->current_state) {
					case Current_status_Model::HOST_UP:
						$hosts_up++;
						break;
					case Current_status_Model::HOST_DOWN:
						if ($host->scheduled_downtime_depth) {
							$hosts_down_scheduled++;
							$host_problem = false;
						}
						if ($host->problem_has_been_acknowledged) {
							$hosts_down_acknowledged++;
							$host_problem = false;
						}
						if ($host->active_checks_enabled) {
							$hosts_down_disabled++;
							$host_problem = false;
						}
						if ($host_problem) {
							$hosts_down_unacknowledged++;
						}
						$hosts_down++;
						break;
					case Current_status_Model::HOST_UNREACHABLE:
						if ($host->scheduled_downtime_depth) {
							$hosts_unreachable_scheduled++;
							$host_problem = false;
						}
						if ($host->problem_has_been_acknowledged) {
							$hosts_unreachable_acknowledged++;
							$host_problem = false;
						}
						if ($host->active_checks_enabled) {
							$hosts_unreachable_disabled++;
							$host_problem = false;
						}
						if ($host_problem) {
							$hosts_unreachable_unacknowledged++;
						}
						$hosts_unreachable++;
						break;
					default:
						$hosts_pending++;
				}
				$seen_hosts[] = $host->host_name;
			}

			$content->hosts_up = $hosts_up;
			$content->groupname = $group;
			$content->hosts_down = $hosts_down;
			$content->hosts_down_unacknowledged = $hosts_down_unacknowledged;
			$content->hosts_down_scheduled = $hosts_down_scheduled;
			$content->hosts_down_acknowledged = $hosts_down_acknowledged;
			$content->hosts_down_disabled = $hosts_down_disabled;
			$content->hosts_unreachable = $hosts_unreachable;
			$content->hosts_unreachable_unacknowledged = $hosts_unreachable_unacknowledged;
			$content->hosts_unreachable_scheduled = $hosts_unreachable_scheduled;
			$content->hosts_unreachable_acknowledged = $hosts_unreachable_acknowledged;
			$content->hosts_unreachable_disabled = $hosts_unreachable_disabled;
			$content->hosts_pending = $hosts_pending;

			# fetch servicedata
			$content->service_data = $this->_show_group_service_summary($grouptype, $seen_hosts, $group, $serviceprops, $hostprops);
		} else {
			# nothing found
		}

		return $content;
	}

	/**
	 *
	 *
	 */
	public function _show_group_service_summary($grouptype='service', $hostlist=false, $group=false, $serviceprops=false, $hostprops=false)
	{
		$group = urldecode($this->input->get('group', $group));
		if (empty($hostlist)) {
			return false;
		}
		$serviceprops = $this->serviceprops;
		$hostprops = $this->hostprops;

		$service_info = false;
		$host_model = new Host_Model();
		$host_model->show_services = true;
		$host_model->serviceprops = $serviceprops;
		$host_model->hostprops = $hostprops;
		$host_model->set_host_list($hostlist);

		$result = $host_model->get_host_status();
		$service_model = new Service_Model();
		$service_data = $service_model->get_services_for_group($group, $grouptype);
		$service_list = false;
		if ($service_data) {
			foreach ($service_data as $row) {
				$service_list[] = $row->id;
			}
		}
		$services_ok = 0;
		$services_warning_host_problem = 0;
		$services_warning_scheduled = 0;
		$services_warning_acknowledged = 0;
		$services_warning_disabled = 0;
		$services_warning_unacknowledged = 0;
		$services_warning = 0;
		$services_unknown_host_problem = 0;
		$services_unknown_scheduled = 0;
		$services_unknown_acknowledged = 0;
		$services_unknown_disabled = 0;
		$services_unknown_unacknowledged = 0;
		$services_unknown = 0;
		$services_critical_host_problem = 0;
		$services_critical_scheduled = 0;
		$services_critical_acknowledged = 0;
		$services_critical_disabled = 0;
		$services_critical_unacknowledged = 0;
		$services_critical = 0;
		$services_pending = 0;

		foreach ($result as $row) {
			if (!in_array($row->service_id, $service_list))
				continue;
			$problem = true;
			switch ($row->current_state) {
				case Current_status_Model::SERVICE_OK:
					$services_ok++;
					break;
				case Current_status_Model::SERVICE_WARNING:
					if ($row->host_state == Current_status_Model::HOST_DOWN || $row->host_state == Current_status_Model::HOST_UNREACHABLE) {
						$services_warning_host_problem++;
						$problem = false;
					}
					if ($row->scheduled_downtime_depth > 0) {
						$services_warning_scheduled++;
						$problem = false;
					}
					if ($row->problem_has_been_acknowledged) {
						$services_warning_acknowledged++;
						$problem = false;
					}
					if (!$row->active_checks_enabled) {
						$services_warning_disabled++;
						$problem = false;
					}
					if ($problem == true)
						$services_warning_unacknowledged++;
					$services_warning++;
					break;
				case Current_status_Model::SERVICE_UNKNOWN:
					if ($row->host_state == Current_status_Model::HOST_DOWN || $row->host_state == Current_status_Model::HOST_UNREACHABLE) {
						$services_unknown_host_problem++;
						$problem = false;
					}
					if ($row->scheduled_downtime_depth > 0) {
						$services_unknown_scheduled++;
						$problem = false;
					}
					if ($row->problem_has_been_acknowledged) {
						$services_unknown_acknowledged++;
						$problem = false;
					}
					if (!$row->active_checks_enabled){
						$services_unknown_disabled++;
						$problem = false;
					}
					if ($problem == true)
						$services_unknown_unacknowledged++;
					$services_unknown++;
					break;
				case Current_status_Model::SERVICE_CRITICAL:
					if ($row->host_state == Current_status_Model::HOST_DOWN || $row->host_state == Current_status_Model::HOST_UNREACHABLE) {
						$services_critical_host_problem++;
						$problem = false;
					}
					if ($row->scheduled_downtime_depth > 0) {
						$services_critical_scheduled++;
						$problem = false;
					}
					if ($row->problem_has_been_acknowledged) {
						$services_critical_acknowledged++;
						$problem = false;
					}
					if (!$row->active_checks_enabled) {
						$services_critical_disabled++;
						$problem = false;
					}
					if ($problem == true)
						$services_critical_unacknowledged++;
					$services_critical++;
					break;
				case Current_status_Model::SERVICE_PENDING:
					$services_pending++;
					break;
				} # end switch
			} # end foreach

		$service_info->services_ok = $services_ok;
		$service_info->services_warning_host_problem = $services_warning_host_problem;
		$service_info->services_warning_scheduled = $services_warning_scheduled ;
		$service_info->services_warning_acknowledged = $services_warning_acknowledged;
		$service_info->services_warning_disabled = $services_warning_disabled;
		$service_info->services_warning_unacknowledged = $services_warning_unacknowledged;
		$service_info->services_warning = $services_warning;
		$service_info->services_unknown_host_problem = $services_unknown_host_problem;
		$service_info->services_unknown_scheduled = $services_unknown_scheduled;
		$service_info->services_unknown_acknowledged = $services_unknown_acknowledged;
		$service_info->services_unknown_disabled = $services_unknown_disabled;
		$service_info->services_unknown_unacknowledged = $services_unknown_unacknowledged;
		$service_info->services_unknown = $services_unknown;
		$service_info->services_critical_host_problem = $services_critical_host_problem;
		$service_info->services_critical_scheduled = $services_critical_scheduled;
		$service_info->services_critical_acknowledged = $services_critical_acknowledged;
		$service_info->services_critical_disabled = $services_critical_disabled;
		$service_info->services_critical_unacknowledged = $services_critical_unacknowledged;
		$service_info->services_critical = $services_critical;
		$service_info->services_pending = $services_pending;

		return $service_info;
	}

	/**
	 * Fetch info on single service- or hostgroup and assign to returned content object for later use in template
	 *
	 * @param $grouptype [host|service]
	 * @param $group name of group
	 * @param $hoststatustypes
	 * @param $servicestatustypes
	 * @param $style
	 * @return obj
	 */
	public function _show_group($grouptype='service', $group=false, $hoststatustypes=false, $servicestatustypes=false, $style='overview')
	{
		$grouptype = urldecode($this->input->get('grouptype', $grouptype));
		$group = urldecode($this->input->get('group', $group));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));
		$style = urldecode($this->input->get('style', $style));

		$hoststatustypes = $this->hoststatustypes;
		$servicestatustypes = $this->servicestatustypes;

		$content = false;
		$hoststatustypes = strtolower($hoststatustypes)==='false' ? false : $hoststatustypes;

		$t = $this->translate;
		$group_info_res = $grouptype == 'service' ? Servicegroup_Model::get_by_field_value('servicegroup_name', $group) : Hostgroup_Model::get_by_field_value('hostgroup_name', $group);
		if (empty($group_info_res)) {
			$this->template->content = $this->add_view('error');
			$this->template->content->error_message = sprintf($t->_("The requested group ('%s') wasn't found"), $group);
			return;
		}
		$hostlist = Group_Model::get_group_hoststatus($grouptype, $group, $hoststatustypes, $servicestatustypes);
		$content->group_alias = $group_info_res->alias;
		$content->groupname = $group;
		if ($hostlist->count() > 0) {
			$content->lable_header .= " '".$group."'";
			$service_states = false;
			$hostinfo = false;
			$lable_extinfo_host = $t->_('View Extended Information For This Host');
			$lable_svc_status = $t->_('View Service Details For This Host');
			$lable_statusmap = $t->_('Locate Host On Map');
			foreach ($hostlist as $host) {
				$nacoma_link = false;
				/**
				 * Modify config/config.php to enable NACOMA
				 * and set the correct path in config/config.php,
				 * if installed, to use this
				 */
				if (Kohana::config('config.nacoma_path')!==false) {
					$lable_nacoma = $t->_('Configure this host using NACOMA (Nagios Configuration Manager)');
					$nacoma_link = html::anchor('configuration/configure/host/'.urlencode($host->host_name), html::image($this->img_path('icons/16x16/nacoma.png'), array('alt' => $lable_nacoma, 'title' => $lable_nacoma)));
				}

				/**
				 * Enable PNP4Nagios integration
				 * Set correct path in config/config.php
				 */
				$pnp_link = false;
				if (Kohana::config('config.pnp4nagios_path')!==false) {
					$lable_pnp = $t->_('Show performance graph');
					$pnp_link = '<a href="'.Kohana::config('config.pnp4nagios_path').'index.php?host='.urlencode($host->host_name).'" style="border: 0px">'.html::image($this->img_path('icons/16x16/pnp.png'), array('alt' => $lable_pnp, 'title' => $lable_pnp)).'</a>';
				}

				# decide status_link host- and servicestate parameters
				$hst_status_type = nagstat::HOST_PENDING|nagstat::HOST_UP|nagstat::HOST_DOWN|nagstat::HOST_UNREACHABLE;
				$svc_status_type = false;
				switch ($host->service_state) {
					case Current_status_Model::SERVICE_OK:
						$svc_status_type = nagstat::SERVICE_OK;
						break;
					case Current_status_Model::SERVICE_WARNING:
						$svc_status_type = nagstat::SERVICE_WARNING;
						break;
					case Current_status_Model::SERVICE_UNKNOWN:
						$svc_status_type = nagstat::SERVICE_UNKNOWN;
						break;
					case Current_status_Model::SERVICE_CRITICAL:
						$svc_status_type = nagstat::SERVICE_CRITICAL;
						break;
					case Current_status_Model::SERVICE_PENDING:
						$svc_status_type = nagstat::SERVICE_PENDING;
						break;
				}
				$service_states[$host->host_name][$host->service_state] = array(
					'class_name' => 'miniStatus' . $this->current->status_text($host->service_state, 'service'),
					'status_link' => html::anchor('status/'.$grouptype.'group/'.urlencode($group).'?hoststatustypes='.$hst_status_type.'&servicestatustypes='.$svc_status_type.'&style=detail', html::specialchars($host->state_count.' '.$this->current->status_text($host->service_state, 'service')), array('style' => 'border: 0px') ),
					'extinfo_link' => html::anchor('extinfo/details/host/'.urlencode($host->host_name), html::image($this->img_path('icons/16x16/extended-information.gif'), array('alt' => $lable_extinfo_host, 'title' => $lable_extinfo_host)), array('style' => 'border: 0px') ),
					'svc_status_link' => html::anchor('status/service/'.urlencode($host->host_name), html::image($this->img_path('icons/16x16/service-details.gif'), array('alt' => $lable_svc_status, 'title' => $lable_svc_status)), array('style' => 'border: 0px') ),
					'statusmap_link' => html::anchor('statusmap/host/'.urlencode($host->host_name), html::image($this->img_path('icons/16x16/locate-host-on-map.png'), array('alt' => $lable_statusmap, 'title' => $lable_statusmap)), array('style' => 'border: 0px') ),
					'nacoma_link' => $nacoma_link,
					'pnp_link' => $pnp_link
					);

				$action_link = false;
				if (!is_null($host->action_url)) {
					$lable_host_action = $t->_('Perform Extra Host Actions');
					$action_link = '<a href="'.$host->action_url.'" style="border: 0px">'.html::image($this->img_path('icons/16x16/host-actions.png'), array('alt' => $lable_host_action, 'title' => $lable_host_action)).'</a>';
				}
				$notes_link = false;
				if (!is_null($host->notes_url)) {
					$lable_host_notes = $t->_('View Extra Host Notes');
					$notes_link = '<a href="'.$host->notes_url.'" style="border: 0px">'.html::image($this->img_path('icons/16x16/host-notes.png'), array('alt' => $lable_host_notes, 'title' => $lable_host_notes)).'</a>';
				}

				$host_icon = false;
				# logos_path
				if (!empty($host->icon_image)) {
					$host_icon = html::image('application/media/images/logos/'.$host->icon_image, array('style' => 'height: 16px; width: 16px', 'alt' => $host->icon_image_alt, 'title' => $host->icon_image_alt));
				}
				$hostinfo[$host->host_name] = array(
					'state_str' => $this->current->status_text($host->current_state, 'host'),
					'class_name' => 'statusHOST' . $this->current->status_text($host->current_state, 'host'),
					'status_link' => html::anchor('status/service/'.urlencode($host->host_name).'?hoststatustypes='.$hoststatustypes.'&servicestatustypes='.(int)$servicestatustypes, html::specialchars($host->host_name), array('title' => $host->address)),
					'action_link' => $action_link,
					'notes_link' => $notes_link,
					'host_icon' => $host_icon
					);
			}
			$content->service_states = $service_states;
			$content->hostinfo = $hostinfo;
			$content->hoststatustypes = $hoststatustypes;
			$content->servicestatustypes = $servicestatustypes;
		} else {
			# nothing found
		}
		return $content;
	}

	/**
	*	Show a grid of hostgroup(s)
	* 	A wrapper for group_grid('host')
	*
	*/
	public function hostgroup_grid($group='all', $hoststatustypes=false, $servicestatustypes=false)
	{
		$group = urldecode($this->input->get('group', $group));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));

		$grouptype = 'host';
		return $this->group_grid($grouptype, $group, $hoststatustypes, $servicestatustypes);
	}

	/**
	*	Show a grid of servicegroup(s)
	* 	A wrapper for group_grid('services')
	*
	*/
	public function servicegroup_grid($group='all', $hoststatustypes=false, $servicestatustypes=false)
	{
		$group = urldecode($this->input->get('group', $group));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));

		$grouptype = 'service';
		return $this->group_grid($grouptype, $group, $hoststatustypes, $servicestatustypes);
	}

	/**
	*	show a grid layout of host- or servicegroup(s)
	*
	*/
	public function group_grid($grouptype='service', $group='all', $hoststatustypes=false, $servicestatustypes=false)
	{
		$grouptype = urldecode($this->input->get('grouptype', $grouptype));
		$group = urldecode($this->input->get('group', $group));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));
		$group = trim($group);
		$this->template->content = $this->add_view('status/group_grid');
		$content = $this->template->content;
		$t = $this->translate;

		$this->hoststatustypes = $hoststatustypes;
		$this->servicestatustypes = $servicestatustypes;

		$this->template->title = $this->translate->_('Monitoring » ').$grouptype.$this->translate->_('group grid');

		$this->template->js_header = $this->add_view('js_header');
		$this->template->css_header = $this->add_view('css_header');

		widget::add('status_totals', array($this->current, $group, $hoststatustypes, $servicestatustypes, $grouptype.'group'), $this);
		$this->template->content->widgets = $this->widgets;
		$this->template->js_header->js = $this->xtra_js;
		//$this->template->css_header->css = array_merge($this->xtra_css, array($this->add_path('/css/default/common.css')));

		$content->label_host = $t->_('Host');
		$content->label_services = $t->_('Services');
		$content->label_actions = $t->_('Actions');
		if (strtolower($group) == 'all') {
			$content->label_header = $grouptype == 'service' ? $t->_('Status Grid For All Service Groups') : $t->_('Status Grid For All Host Groups');
			$group_info_res = $grouptype == 'service' ? Servicegroup_Model::get_all() : Hostgroup_Model::get_all();
			foreach ($group_info_res as $group_res) {
				$groupname_tmp = $group_res->{$grouptype.'group_name'};
				$group_details[] = $this->_show_grid($grouptype, $groupname_tmp);
			}
		} else {
			# make sure we have the correct servicegroup
			$group_info_res = $grouptype == 'service' ?
				Servicegroup_Model::get_by_field_value('servicegroup_name', $group) :
				Hostgroup_Model::get_by_field_value('hostgroup_name', $group);
			if ($group_info_res) {
				$group = $group_info_res->{$grouptype.'group_name'}; # different field depending on object type
			} else {
				# overwrite previous view with the error view, add some text and bail out
				$this->template->content = $this->add_view('error');
				$this->template->content->error_message = sprintf($t->_("The requested group ('%s') wasn't found"), $group);
				return;
			}
			$label_header = $grouptype == 'service' ? $t->_('Status Grid For Service Group ') : $t->_('Status Grid For Host Group ');
			$content->label_header = $label_header."'".$group."'";
			$group_details[] = $this->_show_grid($grouptype, $group);
		}
		$content->group_details = $group_details;
		$content->grouptype = $grouptype;
		$content->logos_path = $this->logos_path;
		$content->icon_path	= $this->img_path('icons/16x16/');
		$content->label_host_extinfo = $t->_('View Extended Information For This Host');
		$content->label_service_status = $t->_('View Service Details For This Host');
		$content->label_status_map = $t->_('Locate Host On Map');
		$nacoma_link = false;
		/**
		 * Modify config/config.php to enable NACOMA
		 * and set the correct path in config/config.php,
		 * if installed, to use this
		 */
		if (Kohana::config('config.nacoma_path')!==false) {
			$content->label_nacoma = $t->_('Configure this host using NACOMA (Nagios Configuration Manager)');
			$content->nacoma_path = Kohana::config('config.nacoma_path');
		}

		/**
		 * Enable PNP4Nagios integration
		 * Set correct path in config/config.php
		 */
		$pnp_link = false;
		if (Kohana::config('config.pnp4nagios_path')!==false) {
			$content->label_pnp = $t->_('Show performance graph');
			$content->pnp_path = Kohana::config('config.pnp4nagios_path');
		}

		if ($grouptype == 'host') {
			if ($group == 'all') {
				$label_host_status_details = $this->translate->_('View Service Status Detail For All Host Groups');
				$label_group_status_details = $this->translate->_('View Host Status Detail For All Host Groups');
				$label_group_status_overview = $this->translate->_('View Status Overview For All Host Groups');
				$label_group_status_summary = $this->translate->_('View Status Summary For All Host Groups');
				$page_links = array(
					$label_host_status_details => Router::$controller.'/'.$grouptype.'group/all?style=detail',
					$label_group_status_details => Router::$controller.'/service/all',
					$label_group_status_overview => Router::$controller.'/'.$grouptype.'group/all',
					$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/all?style=summary'
				);
			} else {
				$label_host_status_grid = $this->translate->_('View Status Grid For All Host Groups');
				$label_group_service_status_details = $this->translate->_('View Service Status Detail For This Host Group');
				$label_group_host_status_details = $this->translate->_('View Host Status Detail For This Host Group');
				$label_group_status_overview = $this->translate->_('View Status Overview For This Host Group');
				$label_group_status_summary = $this->translate->_('View Status Summary For This Host Group');

				$page_links = array(
					$label_host_status_grid => Router::$controller.'/'.$grouptype.'group_grid/all',
					$label_group_service_status_details => Router::$controller.'/'.$grouptype.'group/'.$group.'?style=detail',
					$label_group_host_status_details => Router::$controller.'/host/'.$group.'?group_type='.$grouptype.'group',
					$label_group_status_overview => Router::$controller.'/'.$grouptype.'group/'.$group,
					$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/'.$group.'?style=summary'
				);
			}
		} else {
			if ($group == 'all') {
				$label_service_status_details = $this->translate->_('View Service Status Detail For All Service Groups');
				$label_service_status_overview = $this->translate->_('View Status Overview For All Service Groups');
				$label_group_status_summary = $this->translate->_('View Status Summary For All Service Groups');
				$page_links = array(
					$label_service_status_details => Router::$controller.'/'.$grouptype.'group/all?style=detail',
					$label_service_status_overview => Router::$controller.'/'.$grouptype.'group/all',
					$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/all?style=summary'
				);
			} else {
				$label_service_status_details = $this->translate->_('View Service Status Detail For This Service Group');
				$label_group_status_overview = $this->translate->_('View Status Overview For This Service Group');
				$label_group_status_summary = $this->translate->_('View Status Summary For This Service Group');
				$label_host_status_grid = $this->translate->_('View Service Status Grid For All Service Groups');

				$page_links = array(
						$label_service_status_details => Router::$controller.'/host/'.$group.'?group_type='.$grouptype.'group',
						$label_group_status_overview => Router::$controller.'/'.$grouptype.'group/'.$group,
						$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/'.$group.'?style=summary',
						$label_host_status_grid => Router::$controller.'/'.$grouptype.'group_grid/all'
					);
			}

		}
		if (isset($page_links)) {
			$this->template->content->page_links = $page_links;
		}
	}

	/**
	*	displays status grid for a specific host- or servicegroup
	*
	*/
	public function _show_grid($grouptype='service', $group=false)
	{
		$grouptype = urldecode($this->input->get('grouptype', $grouptype));
		$group = urldecode($this->input->get('group', $group));

		$service_info = array();
		$result = Group_Model::get_group_info($grouptype, $group);
		$content = false;
		$hosts = array();
		$seen_hosts = array();
		if ($result->count() > 0) {
			foreach ($result as $row) {
				# loop over result and assign to return variable
				if (!in_array($row->host_name, $seen_hosts)) {
					$hosts[] = array(
						'host_name' => $row->host_name,
						'current_state' => $row->current_state,
						'notes_url' => $row->notes_url,
						'action_url' => $row->action_url,
						'icon_image' => $row->icon_image,
						'icon_image_alt' => $row->icon_image_alt
					);
					$seen_hosts[] = $row->host_name;
				}
				$service_info[$row->host_name][] = array(
					'current_state' => $row->service_state,
					'service_description' => $row->service_description
				);
			}
		} else {
			return false;
		}
		$content->hosts = $hosts;
		$content->services = $service_info;
		$content->group_name = $group;
		$content->group_type = $grouptype;
		return $content;
	}

	/**
	 * Create header links for status listing
	 */
	private function header_links(
			$type='host',
			$filter_object='all',
			$title=false,
			$method=false,
			$sort_field_db=false,
			$sort_field_str=false,
			$host_status=false,
			$service_status=false,
			$service_props=false)
	{

		$type = trim($type);
		$filter_object = trim($filter_object);
		$title = trim($title);
		if (empty($type) || empty($title))  {
			return false;
		}
		$header = false;
		$lable_ascending = $this->translate->_('ascending');
		$lable_descending = $this->translate->_('descending');
		$lable_sort_by = $this->translate->_('Sort by');
		$lable_last = $this->translate->_('last');
		switch ($type) {
			case 'host':
				$header['title'] = $title;
				if (!empty($method) &&!empty($filter_object) && !empty($sort_field_db)) {
					$header['url_asc'] = Router::$controller.'/'.$method.'/'.$filter_object.'?hoststatustypes='.$host_status.'&sort_order='.nagstat::SORT_ASC.'&sort_field='.$sort_field_db;
					$header['alt_asc'] = $lable_sort_by.' '.$lable_last.' '.$sort_field_str.' ('.$lable_ascending.')';
					$header['img_asc'] = $this->img_sort_up;
					$header['url_desc'] = Router::$controller.'/'.$method.'/'.$filter_object.'?hoststatustypes='.$host_status.'&sort_order='.nagstat::SORT_DESC.'&sort_field='.$sort_field_db;
					$header['img_desc'] = $this->img_sort_down;
					$header['alt_desc'] = $lable_sort_by.' '.$sort_field_str.' ('.$lable_descending.')';
				}
				break;
			case 'service':
				$header['title'] = $title;
				if (!empty($method) &&!empty($filter_object) && !empty($sort_field_db)) {
					$header['url_asc'] = Router::$controller.'/'.$method.'/'.$filter_object.'?hoststatustypes='.$host_status.'&servicestatustypes='.$service_status.'&service_props='.(int)$service_props.'&sort_order='.nagstat::SORT_ASC.'&sort_field='.$sort_field_db;
					$header['img_asc'] = $this->img_sort_up;
					$header['alt_asc'] = $lable_sort_by.' '.$lable_last.' '.$sort_field_str.' ('.$lable_ascending.')';
					$header['url_desc'] = Router::$controller.'/'.$method.'/'.$filter_object.'?hoststatustypes='.$host_status.'&servicestatustypes='.$service_status.'&service_props='.(int)$service_props.'&sort_order='.nagstat::SORT_DESC.'&sort_field='.$sort_field_db;
					$header['img_desc'] = $this->img_sort_down;
					$header['alt_desc'] = $lable_sort_by.' '.$sort_field_str.' ('.$lable_descending.')';
				}
				break;
		}
		return $header;
	}
}
