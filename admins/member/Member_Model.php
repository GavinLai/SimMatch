<?php
/**
 * Member Model Class
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
defined('IN_SIMPHP') or die('Access Denied');

class Member_Model extends Model {
	
	static function getMembers(){
		$db = D();
		$sql = "SELECT * FROM {member} ORDER BY uid DESC ";
		return $db->query($sql)->fetch_array_all(); 
	}
	
	static function getMemberById($uid){
		$db = D();
		$sql = "SELECT * FROM {member} WHERE uid=%d";
		return $db->get_one($sql,$uid);
	}
	
	static function updateMemberById($uid,$data){
		$db = D();
		return $db->update_table('member', $data, ['uid' => $uid]);
	}
	
	static function getMembersByWhere($where,$sort){
		$db = D();
		$sort_str = '';
		if($sort!=''){
			$sort_str .= ' ORDER BY '.$sort;
		}		
		$sql = "SELECT * FROM {member} WHERE 1 {$where} {$sort_str} ";
		return $db->query($sql)->fetch_array_all();
	}
	
	static function getMemberLoginLog($where,$sort){
		$db = D();
		$sort_str = '';
		if($sort!=''){
			$sort_str .= ' ORDER BY '.$sort;
		}
		$sql = "SELECT * FROM {member_login_log} WHERE 1 {$where} {$sort_str} ";
		return $db->query($sql)->fetch_array_all();
	}
	
	static function strip_location($loca){
		return preg_replace("/^\d+:/", "", $loca);
	}

	static function getPlayerGallery($player_id, $filed = 'img_std') {
		$rs = D()->from("player_gallery")->where("`player_id`=%d", $player_id)->select("`{$filed}`")->fetch_column("{$filed}");
		if (!empty($rs)) {
			foreach ($rs AS &$it) {
				$it = fixpath($it);
			}
		}
		return $rs;
	}
	
	static function getPlayerList($orderby='player_id', $order='DESC', $limit=30 , $where=[]) {
		
		$sql    = "SELECT * FROM {player} WHERE `status`<>'D' ORDER BY `%s` %s";
    $sqlcnt = "SELECT COUNT(player_id) AS rcnt FROM {player} WHERE `status`<>'D'";
	
    $result = D()->pager_query($sql,$limit,$sqlcnt,0,$orderby,$order)->fetch_array_all();
    if (!empty($result)) {
    	foreach ($result AS &$it) {
    		$it['province'] = self::strip_location($it['province']);
    		$it['city']     = self::strip_location($it['city']);
    		$it['gallery'] = self::getPlayerGallery($it['player_id']);
    	}
    }
	
		return $result;
	
	}
	
	static function getPlayerInfo($player_id) {
		if(empty($player_id)) return [];
		$rs = D()->get_one("SELECT * FROM {player} WHERE `player_id`=%d", $player_id);
		return $rs;
	}
	
	static function suspendPlayers($ids, $isSuspend = TRUE) {
		if (!is_array($ids)) {
			$ids = array($ids);
		}
	
		$idstr = implode(',', $ids);
		if ($idstr) {
			 
			$flag = $isSuspend ? 'S' : 'R';
			D()->query("UPDATE `{player}` SET `status`='{$flag}' WHERE `player_id` IN (%s)", $idstr);
	
			return $ids;
		}
		return [];
	}
	
	public static function deletePlayers($ids) {
		if (!is_array($ids)) {
			$ids = array($ids);
		}
	
		$idstr = implode(',', $ids);
		if ($idstr) {
			 
			$now = simphp_time();
	
			//~ update table {channel}
			D()->query("UPDATE `{player}` SET `status`='D' WHERE `player_id` IN (%s)", $idstr);
	
			return $ids;
		}
		return [];
	}
	
}
 
/*----- END FILE: Member_Model.php -----*/