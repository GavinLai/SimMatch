<?php
/**
 * 充值管理控制器
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
defined('IN_SIMPHP') or die('Access Denied');

class Pay_Controller extends Controller {
	
	private $_nav = 'cz';
	
	/**
	 * hook menu
	 *
	 * @return array
	 */
	public function menu()
	{
		return [
			'pay' => 'index',
		];
	}
	
	/**
	 * hook init
	 * @param string $action
	 * @param Request $request
	 * @param Response $response
	 */
	public function init($action, Request $request, Response $response)
	{
		$this->v = new PageView();
		$this->v->assign('nav', $this->_nav);
	}
	
	/**
	 * default action 'index'
	 * @param Request $request
	 * @param Response $response
	 */
	public function index(Request $request, Response $response)
	{
		//查询条件
		$query_conds = [];
		/*
		$_query_node = ['keyword'=>'','status'=>''];
		if(empty($_POST)&&isset($_SESSION['_query_node'])){
			$_query_node = $_SESSION['_query_node'];
		}else{
			$keyword = $request->post('keyword','');
			$status = $request->post('status', '');
	
			$_query_node['keyword'] = $keyword;
			$_query_node['status'] = $status;
			$_SESSION['_query_node'] = $_query_node;
		}
		*/
		
		$this->v->assign('nav_second', '');
	
		//BEGIN list order
		$orderinfo = $this->v->set_listorder('order_id', 'desc');
		$extraurl  = '';
		$extraurl .= $orderinfo[2];
		$this->v->assign('extraurl', $extraurl);
		$this->v->assign('qparturl', "#/pay");
		//END list order
	
		// Game List
		$limit = 20;
		$recordList = Pay_Model::getPayList($orderinfo[0],$orderinfo[1],$limit,$query_conds,$statinfo);
		$recordNum  = count($recordList);
		$totalNum   = $GLOBALS['pager_totalrecord_arr'][0];
	
		$this->v->set_tplname('mod_pay_index');
		$this->v->assign('recordList', $recordList)
						->assign('recordNum', $recordNum)
						->assign('totalNum', $totalNum)
						->assign('query_conds', $query_conds)
						->assign('statinfo', $statinfo)
						->assign('mainsite', C('env.site.mobile'))
						;
		$response->send($this->v);
	}
	
	
}
 
/*----- END FILE: Pay_Controller.php -----*/