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
  
}
 
/*----- END FILE: class.Node.php -----*/