<?php
/**
 * 
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
defined('IN_SIMPHP') or die('Access Denied');

class Pay_Model extends Controller {
	
	static function getPayList($orderby='order_id', $order='DESC', $limit=30, Array $query_conds=array(), &$statinfo=array()) {
	
		$where  = "AND o.`pay_status`=2";
		if (isset($query_conds['seeall']) && $query_conds['seeall']) {
			$where = "";
		}
		
		$where_cond = '';
		if (isset($query_conds['target']) && $query_conds['target']) {
			if (is_numeric($query_conds['target'])) {
				$where_cond .= " AND o.`player_id`=".$query_conds['target'];
			}
			else {
				$where_cond .= " AND p.`truename` like '%%".D()->escape_string($query_conds['target'])."%%'";
			}
		}
		if (isset($query_conds['start_date']) && $query_conds['start_date']) {
			$starttime = strtotime($query_conds['start_date'].DAY_BEGIN);
			$where_cond .= " AND o.`add_time`>=".$starttime;
		}
		if (isset($query_conds['end_date']) && $query_conds['end_date']) {
			$endtime = strtotime($query_conds['end_date'].DAY_END);
			$where_cond .= " AND o.`add_time`<=".$endtime;
		}
		
		$where = $where . $where_cond;
		
		$sql    = "SELECT o.*,u.nickname,u.logo,p.truename AS player_name
								FROM `{order_info}` o INNER JOIN `{member}` u ON o.user_id=u.uid
								     INNER JOIN `{player}` p ON o.player_id=p.player_id
				       WHERE 1 {$where}
		           ORDER BY `%s` %s";
	
    $sqlcnt = "SELECT COUNT(o.order_id) AS rcnt FROM {order_info} o INNER JOIN `{player}` p ON o.player_id=p.player_id WHERE 1 {$where}";
	
    $result = D()->pager_query($sql,$limit,$sqlcnt,0,$orderby,$order)->fetch_array_all();
    $statinfo = ['total_pay'=>0, 'current_pay'=>0];
    $statinfo['total_pay'] = D()->query("SELECT SUM(o.`goods_amount`) AS total_pay FROM `{order_info}` o INNER JOIN `{player}` p ON o.player_id=p.player_id WHERE o.`pay_status`=2 {$where_cond}")->result();
    if (empty($statinfo['total_pay'])) $statinfo['total_pay'] = 0;
    if (!empty($result)) {
    	foreach ($result AS &$it) {
    		$it['order_status_name'] = Fn::order_status($it['order_status']);
    		$it['pay_status_name']   = Fn::pay_status($it['pay_status']);
    		if ($it['pay_status']==PS_PAYED) {
    			$statinfo['current_pay'] += $it['goods_amount'];
    		}
    	}
    }
    
    return $result;
	
	}
	
	static function getDailyPayList($orderby='datetime', $order='DESC', $limit=30, Array $query_conds=array(), &$statinfo=array()) {
	
		
		$where = '';
		if (isset($query_conds['start_date']) && $query_conds['start_date']) {
			$where .= " AND `datetime`>='".D()->escape_string($query_conds['start_date'])."'";
		}
		if (isset($query_conds['end_date']) && $query_conds['end_date']) {
			$where .= " AND `datetime`<='".D()->escape_string($query_conds['end_date'])."'";
		}
		
		$sql    = "SELECT * FROM `{stat_dailypay}` WHERE 1 {$where} ORDER BY `%s` %s";
	
    $sqlcnt = "SELECT COUNT(rid) AS rcnt FROM {stat_dailypay} WHERE 1 {$where}";
	
    $result = D()->pager_query($sql,$limit,$sqlcnt,0,$orderby,$order)->fetch_array_all();
    $statinfo = ['total_pay'=>0, 'current_pay'=>0];
    $statinfo['total_pay'] = D()->query("SELECT SUM(`amount`) AS total_pay FROM `{stat_dailypay}` WHERE 1 {$where}")->result();
    if (empty($statinfo['total_pay'])) $statinfo['total_pay'] = 0;
    if (!empty($result)) {
    	foreach ($result AS &$it) {
    		$statinfo['current_pay'] += $it['amount'];
    	}
    }
    
    return $result;
	
	}
	
	static function updateDailyPay() {
		
		// 默认起始结束日期
		$begin_date = BEGIN_DATE;
		$end_date   = date('Y-m-d');
		
		// 查找最大日期
		$maxdate = D()->from("stat_dailypay")->where("1")->select("MAX(`datetime`) AS maxdate")->result();
		if (!empty($maxdate)) {
			$begin_date = $maxdate;
		}
		
		// 开始统计日期
		$begin_time = strtotime($begin_date.DAY_BEGIN);
		$end_time   = strtotime($end_date.DAY_END);
		
		//循环更新
		$date_interval = 86400;
		$now = time();
		for ($t=$begin_time; $t<=$end_time; $t+=$date_interval) {
			$curr_date = date('Y-m-d', $t);
			$amount    = D()->from("order_info")->where("`add_time`>=%d AND `add_time` <%d AND `pay_status`=2", $t, $t+$date_interval)->select("SUM(`goods_amount`) AS total_pay")->result();
			if (empty($amount)) $amount = 0;
			$exist_rid = D()->from("stat_dailypay")->where("`datetime`='%s'", $curr_date)->select('`rid`')->result();
			if ($exist_rid) {
				D()->update("stat_dailypay", ['datetime'=>$curr_date, 'amount'=>$amount, 'timeline'=>$now], ['rid'=>$exist_rid]);
			}
			else {
				D()->insert("stat_dailypay", ['datetime'=>$curr_date, 'amount'=>$amount, 'timeline'=>$now]);
			}
		}
		
		return true;
	}
	
}
 
/*----- END FILE: Pay_Model.php -----*/