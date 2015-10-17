<?php
/**
 * 与Node相关常用方法
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
defined('IN_SIMPHP') or die('Access Denied');

class Node {
  
  static function getList($ntype, $orderby='nid', $order='DESC', $limit=30 , $where=[]) {
    $extra1 = $extra2 = "";
    switch ($ntype) {
      case 'match':
        $extra1 = "ne.*";
        $extra2 = "INNER JOIN {node_match} ne ON n.nid=ne.enid";
        break;
    }
    
    $sql    = "SELECT n.*,{$extra1},au1.admin_uname AS createdbyname,au2.admin_uname AS changedbyname
               FROM {node} n {$extra2}
                 INNER JOIN {admin_user} au1 ON n.createdby=au1.admin_uid
                 INNER JOIN {admin_user} au2 ON n.changedby=au2.admin_uid";
    
    $_where = " WHERE n.`ntype`='%s' AND n.`status`<>'D'";
    $_order = " ORDER BY `%s` %s";
    
    if (isset($where['keyword'])&&$where['keyword']!='') {
      $_where .= " AND n.`keyword` like '%{$where['keyword']}%'";
    }
    if (isset($where['status'])&&$where['status']!='') {
      $_where .= " AND n.`status`='{$where['status']}'";
    }
    
    $sqlcnt = "SELECT COUNT(nid) AS rcnt FROM {node} n";
    
    $sql .= $_where.$_order;
    $sqlcnt .= $_where;
    
    $result = D()->pager_query($sql,$limit,$sqlcnt,0,$ntype,$orderby,$order)->fetch_array_all();
    
    return $result;
    
  }
  
  static function getInfo($nid) {
    $row = D()->get_one("SELECT n.* FROM {node} n WHERE n.`nid`=%d",$nid);
    if (!empty($row) && $row['ntype']!='base') {
      $tb_node = '{node_'.$row['ntype'].'}';
      $row_ext = D()->get_one("SELECT * FROM {$tb_node} WHERE enid=%d",$row['nid']);
      $row     = array_merge($row, $row_ext);
    }
    return $row;
  }
  
  static function isExisted($nid) {
    if (empty($nid)) return false;
    $rs = D()->from("node")->where("`nid`=%d", $nid)->select("`nid`")->result();
    return $rs ? true : false;
  }
  
  static function addVisitCnt($nid, $inc = 1) {
    if (!$nid) return false;
  
    D()->query("UPDATE `{node}` SET `visitcnt`=`visitcnt`+%d WHERE `nid`=%d", $inc, $nid);
    if (D()->affected_rows()==1) {
      return true;
    }
    return false;
  }
  
  static function getMatchTypes() {
    static $_match_types = [
      'bs'   => '比赛',  //一般比赛
      'cs'   => '初赛',
      'fs'   => '复赛',
      'ys'   => '预赛',
      'js'   => '决赛',
      'bjs'  => '半决赛',
    ];
    return $_match_types;
  }
  
  static function getStatus() {
    static $_status = [
      'N' => '新建',
      'R' => '展示',
      'S' => '挂起',
      'D' => '删除'
    ];
    return $_status;
  }
  
  /**
   * 解析content(或content_detail)里的段落标记：[p]xxx[/p]
   * 一般的标记有：
   *   [p]xxx[/p]  一般段落
   *   [p=start]xxx[/p] 起始段落
   *   [p=end]xxx[/p] 结尾段落
   * @param string $content
   * @return array 每个段落一个元素的数组
   */
  static function parseContentParagraph($content) {
    if (empty($content)) {
      return $content;
    }
    
    //先讲可能存在的"\n"转成<br/>
    $content = nl2br($content);
    
    //找出[p]xxx[/p]或[p start]xxx[/p]或[p end]xxx[/p]部分
    preg_match_all('/\[p(\s+\w+)?\](.*?)\[\/p\]/is',$content,$out,PREG_SET_ORDER);
    
    $ret = [];
    if (count($out)) {
      foreach ($out AS $m) {
        $mtag = trim($m[1]);
        $mtxt = trim($m[2]);
      
        //去掉前后的<br/>
        $mtxt = preg_replace('/(^(<br\s*\/?>)+)|((<br\s*\/?>)+$)/i', '', $mtxt);
      
        //去掉开头的</p>或结束的<p>
        $mtxt = preg_replace('/(^(<\/p>)+)|((<p>)+$)/i', '', $mtxt);
        $ret[] = ['tag'=>$mtag, 'txt'=>$mtxt];
      }
    }
    return $ret;
  }
  
  static function playerExisted($player_id) {
    if (empty($player_id)) return false;
    $rs = D()->from("player")->where("`player_id`=%d AND `status`='R'", $player_id)->select("`player_id`")->result();
    return $rs ? true : false;
  }
  
  /**
   *
   * @param string $act, 'vote','flower','kiss'
   * @param integer $player_id
   * @param integer $uid
   * @param integer $inc
   * @param boolean $sendvote
   * @return number
   *   -11: vote超过了最大次数(5)
   *   -12: vote时间间隔没超过120分钟
   *  -100: 操作失败
   */
  static function action($act, $player_id, $uid, $inc = 1, $sendvote = FALSE) {
  
  	$now = simphp_time();
  	$votedcnt   = 0;
  	$maxvotenum = 5; //一个用户一天可以对每个女神投5次票，可连续投
  
  	if ('vote'==$act && !$sendvote) {
  
  		$today_start = shorttotime('jt');
  		$today_end   = shorttotime('mt');
  
  		//查找当天已经投的次数
  		$votedcnt = D()->from("action")->where("`player_id`=%d AND `action`='%s' AND `uid`=%d AND `timeline`>=%d AND `timeline`<%d", $player_id,$act,$uid,$today_start,$today_end)
  		               ->select("SUM(`inc`) AS cnt")->result();
  		if ($votedcnt >= $maxvotenum) {
  			return -11;
  		}
  
  		/*
  		 //查找前一次投票时间
  		 $now = simphp_time();
  		 $latest = D()->from("action")->where("`action`='%s' AND `player_id`=%d AND `uid`=%d", $act, $player_id, $uid)->order_by("`aid` DESC")->limit(1)
  		 ->select("`timeline`")->result();
  		 if (($now - $latest) < 60*120) {
  		 return -12;
  		 }
  		 */
  
  	}
  
  	if (in_array($act, ['vote','flower','kiss'])) {
  		 
  		$aid = 0;
  		if (!$sendvote) {
  			$aid = D()->insert("action", ['action'=>$act, 'player_id'=>$player_id, 'inc'=>$inc, 'uid'=>$uid, 'timeline'=>$now]);
  		}
  		 
  		if ($sendvote || $aid) {
  			 
  			//更新player投票数
  			D()->query("UPDATE {player} SET {$act}cnt={$act}cnt+%d WHERE player_id=%d", $inc, $player_id);
  			 
  			//更新node总投票数
  			$match_id = D()->from("player")->where("player_id=%d", $player_id)->select("match_id")->result();
  			D()->query("UPDATE {node} SET {$act}cnt={$act}cnt+%d WHERE nid=%d", $inc, $match_id);
  			 
  			if ($act == 'vote') {
  				return $maxvotenum - $votedcnt - $inc; //返回当前剩余可投票数
  			}
  			elseif ($act == 'flower') {
  				//规则：
  				// 1、一枝花抵两票
  				// 2、数量不限、时间不限
  				 
  				// 送票(x2)
  				self::action('vote', $player_id, $uid, $inc*2, TRUE);
  			}
  			elseif ($act == 'kiss') {
  				 
  			}
  
  			return $aid; //返回动作id
  		}
  	}
  
  	return -100;
  }
  
  /**
   * 获取player的真实某动作数
   * 
   * @param integer $player_id
   * @param string $type
   * @return number
   */
  static function getActionNum($player_id, $type = 'vote') {
  	if (!$player_id || !in_array($type, ['vote','flower','kiss'])) {
  		return -1;
  	}
  	$num = D()->from("action")->where("`player_id`=%d AND `action`='%s'", $player_id, $type)->select("SUM(`inc`) AS cnt")->result();
  	return $num ? : 0;
  }
  
}
 
/*----- END FILE: class.Node.php -----*/