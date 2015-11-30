<?php
/**
 * 产生复赛数据
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
define('DAY_SEP', ' ');
define('DAY_BEGIN', DAY_SEP.'00:00:00');
define('DAY_END',   DAY_SEP.'23:59:59');
define('EXEC_DATE', '2015-12-01');

class GenReplayerJob extends CronJob {
	
	public function main($argc, $argv) {
		
		$match_id       = 100;
		$match_type_to  = 'fs';
		$match_stage_to = 1;
		$vote_field_from= 'votecnt';
		$vote_field_to  = 'votecnt1';
		$vote_to_percent= 0.3;
		
		// 更新赛程标识
		D()->update("node_match", ['match_type'=>$match_type_to, 'current_stage'=>$match_stage_to], ['enid'=>$match_id]);
		
		// 更新选手晋级赛程标识，及搬移总投票数据
		D()->query("UPDATE {player} SET `stage`=%d,`{$vote_field_to}`=ROUND(`{$vote_field_from}`*{$vote_to_percent}) WHERE `match_id`=%d AND `{$vote_field_from}`>=5000",
		           $match_stage_to, $match_id);
		
	}
	
}
 
/*----- END FILE: GenReplayerJob.php -----*/