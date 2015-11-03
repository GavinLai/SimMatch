<?php
/**
 * 
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
defined('IN_SIMPHP') or die('Access Denied');

class Pay_Model extends Controller {
	
	static function getPayList($orderby='order_id', $order='DESC', $limit=30, Array $query_conds=array(), &$statinfo=array()) {
	
		$where  = "AND `pay_status`=2";
		if (isset($query_conds['seeall']) && $query_conds['seeall']) {
			$where = "";
		}
		$sql    = "SELECT o.*,u.nickname,u.logo,p.truename AS player_name
								FROM `{order_info}` o INNER JOIN `{member}` u ON o.user_id=u.uid
								     INNER JOIN `{player}` p ON o.player_id=p.player_id
				       WHERE 1 {$where}
		           ORDER BY `%s` %s";
	
    $sqlcnt = "SELECT COUNT(o.order_id) AS rcnt FROM {order_info} o WHERE 1 {$where}";
	
    $result = D()->pager_query($sql,$limit,$sqlcnt,0,$orderby,$order)->fetch_array_all();
    $statinfo = ['total_pay'=>0, 'current_pay'=>0];
    $statinfo['total_pay'] = D()->query("SELECT SUM(`goods_amount`) AS total_pay FROM `{order_info}` WHERE `pay_status`=2")->result();
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
	
}
 
/*----- END FILE: Pay_Model.php -----*/