<?php
/**
 * 充值管理控制器
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
defined('IN_SIMPHP') or die('Access Denied');

define('DAY_SEP', ' ');
define('DAY_BEGIN', DAY_SEP.'00:00:00');
define('DAY_END',   DAY_SEP.'23:59:59');
define('BEGIN_DATE', '2015-10-27');

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
		
		$seeall = $request->get('seeall',0);
		$seeall = $seeall ? 1 : 0;
		$this->v->assign('seeall', $seeall);
		
		$searchinfo = ['target' => '', 'start_date'=>'', 'end_date'=>''];
		$searchinfo['target']      = $request->get('target','');
		$searchinfo['start_date']  = $request->get('sdate','');
		$searchinfo['end_date']    = $request->get('edate','');
		if (strlen($searchinfo['start_date'])!=10) { //format: 'YYYY-MM-DD'
			$searchinfo['start_date'] = '';
		}
		if (strlen($searchinfo['end_date'])!=10) { //format: 'YYYY-MM-DD'
			$searchinfo['end_date'] = '';
		}
		if (!empty($searchinfo['start_date']) && !empty($searchinfo['end_date']) &&  $searchinfo['start_date'] > $searchinfo['end_date']) { //交换
			$t = $searchinfo['start_date'];
			$searchinfo['start_date'] = $searchinfo['end_date'];
			$searchinfo['end_date'] = $t;
		}
		$searchstr  = 'target='.$searchinfo['target'].'&sdate='.$searchinfo['start_date'].'&edate='.$searchinfo['end_date'];
		$this->v->assign('searchinfo', $searchinfo);
		$this->v->assign('searchstr', $searchstr);
		
		$query_conds['seeall'] = $seeall;
		$query_conds = array_merge($query_conds, $searchinfo);
	
		//BEGIN list order
		$orderinfo = $this->v->set_listorder('order_id', 'desc');
		$extraurl  = "seeall={$seeall}&".$searchstr.'&';
		$extraurl .= $orderinfo[2];
		$this->v->assign('extraurl', $extraurl);
		$this->v->assign('qparturl', '#/pay');
		//END list order
	
		// Record List
		$limit = 30;
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
	
	/**
	 * action 'daily'
	 * 
	 * @param Request $request
	 * @param Response $response
	 */
	public function daily(Request $request, Response $response)
	{
		$this->v->set_tplname('mod_pay_daily');
		$this->v->assign('nav_second', 'daily');
		if ($request->is_hashreq()) {
			
			$searchinfo = ['start_date'=>'', 'end_date'=>''];
			$searchinfo['start_date']  = $request->get('sdate','');
			$searchinfo['end_date']    = $request->get('edate','');
			if (strlen($searchinfo['start_date'])!=10) { //format: 'YYYY-MM-DD'
				$searchinfo['start_date'] = '';
			}
			if (strlen($searchinfo['end_date'])!=10) { //format: 'YYYY-MM-DD'
				$searchinfo['end_date'] = '';
			}
			if (!empty($searchinfo['start_date']) && !empty($searchinfo['end_date']) && $searchinfo['start_date'] > $searchinfo['end_date']) { //交换
				$t = $searchinfo['start_date'];
				$searchinfo['start_date'] = $searchinfo['end_date'];
				$searchinfo['end_date'] = $t;
			}
			$searchstr  = 'sdate='.$searchinfo['start_date'].'&edate='.$searchinfo['end_date'];
			$this->v->assign('searchinfo', $searchinfo);
			$this->v->assign('searchstr', $searchstr);
			
			//BEGIN list order
			$orderinfo = $this->v->set_listorder('datetime', 'desc');
			$extraurl  = $searchstr.'&';
			$extraurl .= $orderinfo[2];
			$this->v->assign('extraurl', $extraurl);
			$this->v->assign('qparturl', '#/pay/daily');
			//END list order
			
			// 查数据之前先更新数据
			Pay_Model::updateDailyPay();
			
			// Record List
			$limit = 30;
			$recordList = Pay_Model::getDailyPayList($orderinfo[0],$orderinfo[1],$limit,$searchinfo,$statinfo);
			$recordNum  = count($recordList);
			$totalNum   = $GLOBALS['pager_totalrecord_arr'][0];
			
			// 获取最大单日充值数
			$maxpay = Pay_Model::getMaxDayPay();
			if ($recordNum) {
				foreach ($recordList AS &$it) {
					$it['amount_len'] = 0;
					$it['weekno'] = Fn::to_weekno(date('w',strtotime($it['datetime'].DAY_BEGIN)));
					//$it['weekno'] = date('w',strtotime($it['datetime'].DAY_BEGIN));
					if ($maxpay) {
						$it['amount_len'] = round($it['amount']*100*3/$maxpay);
					}
				}
				//$statinfo['totalpay_len'] = round($statinfo['total_pay']*100*2/$maxpay);
			}
			
			$this->v->assign('recordList', $recordList)
							->assign('recordNum', $recordNum)
							->assign('totalNum', $totalNum)
							->assign('statinfo', $statinfo)
							->assign('today', date('Y-m-d'))
							;
		}
		$response->send($this->v);
	}
}
 
/*----- END FILE: Pay_Controller.php -----*/