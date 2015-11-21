<?php
/**
 * 生成选手二维码，并上传到七牛
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
define('DAY_SEP', ' ');
define('DAY_BEGIN', DAY_SEP.'00:00:00');
define('DAY_END',   DAY_SEP.'23:59:59');
define('BEGIN_DATE', '2015-10-27');

class DailyPayJob extends CronJob {
	
	public function main($argc, $argv) {
		
		// 默认起始结束日期
		$begin_date = BEGIN_DATE;
		$end_date   = date('Y-m-d');
		
		// 查找最大日期
		$maxdate = D()->from("stat_dailypay")->where("1")->select("MAX(`datetime`) AS maxdate")->result();
		if (!empty($maxdate)) {
			$begin_date = $maxdate;
		}
		
		$date_interval = 86400;
		
		// 开始结束统计时间
		$begin_time = strtotime($begin_date.DAY_BEGIN);
		$end_time   = strtotime($end_date.DAY_END);
		$rcnt = D()->from("stat_dailypay")->where("1")->select("COUNT(`rid`) AS rcnt")->result();
		if ($rcnt > 1) { //已有两天记录以上，则前一天也要更新
			$begin_time -= $date_interval;
		}
		
		//循环更新
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
	}
	
}
 
/*----- END FILE: DailyPayJob.php -----*/