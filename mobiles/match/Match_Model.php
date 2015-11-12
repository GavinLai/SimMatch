<?php
/**
 * Match Model
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
defined('IN_SIMPHP') or die('Access Denied');

class Match_Model extends Model {
  
  static function getProvinces(){
    $parent_id = 2; //2=中国
    $ret = D()->from("location")->where("`parent`=%d", $parent_id)->select("locaid,location")->fetch_array_all();
    return $ret;
  }
  
  static function getCities($province_id) {
    //直辖市locaid
    static $_zhixia = [
    '40'  => '北京',
    '59'  => '天津',
    '78'  => '上海',
    '25'  => '重庆',
    '98'  => '香港',
    '117' => '澳门',
    '125' => '台湾',
    ];
  
    //直辖市直接返回市本身
    if (isset($_zhixia[$province_id])) {
      return [['locaid' => $province_id, 'location' => $_zhixia[$province_id]]];
    }
  
    return D()->from("location")->where("`parent`=%d", $province_id)->select("locaid,location")->fetch_array_all();
  }
  
  static function getLocationName($locaid) {
    return D()->from("location")->where("`locaid`=%d",$locaid)->select("location")->result();
  }
  
  /**
   * 保存参赛信息
   *
   * @param integer $match_id
   * @param array $data
   * @return number
   *   -1: match_id为空
   *   -2: 手机号和微信号为空
   *   -3: 手机号已存在
   *   -4: 微信号已存在
   *   -100: 保存失败
   *   >0: 参赛选手ID
   */
  static function joinMatch($match_id, Array $data) {
  
    if (empty($match_id)) {
      return -1;
    }
    if (empty($data['mobile']) || empty($data['weixin'])) {
      return -2;
    }
  
    // 检查是否已经注册过
    if (isset($data['mobile']) && !empty($data['mobile'])) {
      $rs = D()->from("player")->where("`match_id`=%d AND `mobile`='%s' AND `status`<>'D'", $match_id, $data['mobile'])->select("player_id")->result();
      if ($rs) {
        $existed = true;
        return -3;
      }
    }
    if (isset($data['weixin']) && !empty($data['weixin'])) {
      $rs = D()->from("player")->where("`match_id`=%d AND `weixin`='%s' AND `status`<>'D'", $match_id, $data['weixin'])->select("player_id")->result();
      if ($rs) {
        $existed = true;
        return -4;
      }
    }
  
    $uid = $GLOBALS['user']->uid;
    $data = array_merge(['match_id'=>$match_id,'uid'=>$uid],$data,['jointime'=>simphp_time()]);
    $player_id = D()->insert("player", $data);
    return $player_id ? : -100;
  }
  
  /**
   * 保存base64式图片到文件系统，返回文件路径
   *
   * @param string $img_data
   * @return number | string
   *   -1: 图片错误
   *   -100: 保存发生错误
   *   string: 正确，保存的文件路径
   */
  static function saveImgData($img_data) {
  
    $pos = strpos($img_data, ','); //$img_data like 'data:image/jpeg;base64,/9j/4AAQSk...'
    if (false===$pos) {
    	return -1;
    }
  
    $file_data = substr($img_data, $pos+1);
    $file_data = base64_decode($file_data);
    $img_info  = getimagesizefromstring($file_data);
    if(FALSE===$img_info){
      return -1;
    }
  
    $width    = $img_info[0];
    $height   = $img_info[1];
    $imgtype  = $img_info[2];
    $ratio    = $width / ($height ? : 1);
  
    $filetype = 'player';
    $extpart  = '.jpg';
    switch ($imgtype) { //image type
      case IMAGETYPE_GIF:
        $extpart = '.gif';
        break;
      case IMAGETYPE_PNG:
        $extpart = '.png';
        break;
    }
  
    $maxwidth     = 750; //width of iPhone 6
    $thumbwidth   = 270; //width of thumbnail
  
    //~ 写文件
    $folder_ori   = 'original';
    $folder_thumb = 'thumb';
    $filecode     = date('d_His').'_'.randchar();
    $targetdir    = "/a/{$filetype}/".date('Ym').'/';
    $result       = ['ori'=>'', 'std'=>'', 'thumb'=>''];
  
    // 先写ori版本
    $oripath = self::writeImgData($targetdir.$folder_ori.'/'.$filecode.$extpart, $file_data);
    $img = FALSE;
    if (is_string($oripath)) {
  
      $result['ori'] = $result['std'] = $result['thumb'] = $oripath;
      $oripath = SIMPHP_ROOT . $oripath;
  
      switch ($imgtype) { //image type
        default:
        case IMAGETYPE_JPEG:
          $img = imagecreatefromjpeg($oripath);
          break;
        case IMAGETYPE_GIF:
          $img = imagecreatefromgif($oripath);
          break;
        case IMAGETYPE_PNG:
          $img = imagecreatefrompng($oripath);
          break;
      }
  
      if (is_resource($img)) {
  
        //std版本
        if ($width > $maxwidth) { //只有宽度大于$maxwidth才需要生成标准图，否则直接用原图做标准图
          $dstpath = SIMPHP_ROOT.$targetdir.$folder_thumb.'/'.$filecode.$extpart;
          $rv = self::writeImgFile($img, $dstpath, $imgtype, $width, $height, $maxwidth, intval($maxwidth/$ratio));
          if ($rv) {
            $result['std'] = preg_replace("/^".preg_quote(SIMPHP_ROOT,'/')."/", '', $dstpath);
          }
        }
  
        //thumb版本
        if ($width > $thumbwidth) { //只有宽度大于$thumbwidth才需要生成缩略图，否则直接用原图做缩略图
          $dstpath = SIMPHP_ROOT.$targetdir.$folder_thumb.'/'.$filecode.'_thumb'.$extpart;
          $rv = self::writeImgFile($img, $dstpath, $imgtype, $width, $height, $thumbwidth, intval($thumbwidth/$ratio));
          if ($rv) {
            $result['thumb'] = preg_replace("/^".preg_quote(SIMPHP_ROOT,'/')."/", '', $dstpath);
          }
        }
  
        imagedestroy($img);
      }
  
    }
  
    return $result;
  }
  
  static function writeImgData($filepath, $filedata) {
    $filepath_abs= SIMPHP_ROOT . $filepath;
    $filedir_abs = dirname($filepath_abs);
    if(!is_dir($filedir_abs)) {
      mkdirs($filedir_abs, 0777, TRUE);
    }
    if (FALSE !== file_put_contents($filepath_abs, $filedata)) {
      chmod($filepath_abs, 0444);
      return $filepath;
    }
    return -100;
  }
  
  static function writeImgFile($srcimg, $dstpath, $imgtype, $src_w, $src_h, $dst_w, $dst_h) {
    $rv = FALSE;
    $dstimg = imagecreatetruecolor($dst_w, $dst_h);
    if (is_resource($dstimg)) {
      if(!is_dir(dirname($dstpath))) {
        mkdirs(dirname($dstpath), 0777, TRUE);
      }
      imagecopyresampled($dstimg, $srcimg, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
      switch ($imgtype) { //image type
        default:
        case IMAGETYPE_JPEG:
          $rv = imagejpeg($dstimg, $dstpath);
          break;
        case IMAGETYPE_GIF:
          $rv = imagegif($dstimg, $dstpath);
          break;
        case IMAGETYPE_PNG:
          $rv = imagepng($dstimg, $dstpath);
          break;
      }
      if ($rv) {
        chmod($dstpath, 0444);
      }
      imagedestroy($dstimg);
    }
    return $rv;
  }
  
  /**
   *
   * @param integer $player_id
   * @param array $img_path ['ori'=>'','std'=>'','thumb'=>'']
   * @return boolean
   */
  static function savePlayerGallery($player_id, Array $img_path) {
    $_data = [
      'player_id' => $player_id,
      'img_ori'   => $img_path['ori'],
      'img_std'   => $img_path['std'],
      'img_thumb' => $img_path['thumb'],
    ];
    $rid = D()->insert("player_gallery", $_data);
    return $rid ? : false;
  }
  
  static function getPlayersNum($match_id) {
  	$total_player_num = D()->from("player")->where("`match_id`=%d AND `status`='R'", $match_id)->select("COUNT(`player_id`) AS rcnt")->result();
  	return $total_player_num ? : 0;
  }
  
  static function getPlayerList($match_id, $search = '', $start = 0, $limit = 20, &$totalnum = 0, &$maxpage = 0, $exclude_player_ids = array()) {
  	$where = '';
  	if (!empty($search)) {
  		if (is_numeric($search)) {
  			$where .= " AND p.`player_id`=%d";
  		}
  		elseif (is_array($search)) { //内部查询，格式: '16,38' 等等
  			$search = implode(',', $search);
  			$where .= " AND p.`player_id` IN(%s)";
  		}
  		elseif (($pos=strrpos($search, '+'))!==false) {
  			$search = substr($search, 0, $pos);
  			$where .= " AND p.`votecnt`>=%d";
  		}
  		else {
  			$where .= " AND p.`truename` like '%%%s%%'";
  		}
  	}
  	else {
  		$where .= " AND p.`votecnt`<5000";
  	}
  	if (!empty($exclude_player_ids)) {
  		$exclude_ids_str = implode(',', $exclude_player_ids); 
  		$where .= " AND p.`player_id` NOT IN({$exclude_ids_str})";
  	}
  	
  	$totalnum = D()->from("{player} p")->where("p.`match_id`=%d {$where} AND p.`status`='R'",$match_id, $search)
  	               ->select("COUNT(p.`player_id`) AS rcnt")->result();
  	$maxpage  = ceil($totalnum / ($limit?:10));
    $list = D()->query("SELECT p.`player_id`,p.`match_id`,p.`uid`,p.`cover_pic_id`,p.`truename`,p.`slogan`,p.`votecnt`,p.`flowercnt`,p.`kisscnt`,IFNULL(pg.img_thumb,'') AS img_thumb,IFNULL(pg.img_thumb_cdn,'') AS img_thumb_cdn
    		               FROM {player} p LEFT JOIN {player_gallery} pg ON p.cover_pic_id=pg.rid WHERE p.`match_id`=%d {$where} AND p.`status`='R' ORDER BY p.`votecnt` DESC LIMIT {$start}, {$limit}",
    		               $match_id, $search)
               ->fetch_array_all();
    if (!empty($list)) {
    	$usecdn = C('env.usecdn');
      foreach($list AS &$it) {
      	if (''==$it['img_thumb']) { //封面图片id未设置
      		$row = D()->from("player_gallery")->where("`player_id`=%d", $it['player_id'])->limit(0, 1)->select("`img_thumb`,`img_thumb_cdn`")->get_one();
      		$it['img_thumb'] = empty($row) ? '' : (2==$usecdn&&$row['img_thumb_cdn']!='' ? $row['img_thumb_cdn'] : $row['img_thumb']);
      	}
      	else {
      		$it['img_thumb'] = 2==$usecdn&&$it['img_thumb_cdn']!='' ? $it['img_thumb_cdn'] : $it['img_thumb'];
      	}
      }
    }
    return $list;
  }
  
  static function getPlayerInfo($player_id) {
  	if (empty($player_id)) return false;
    $rs = D()->from("player")->where("`player_id`=%d", $player_id)->select("*")->get_one();
    return $rs;
  }
  
  static function getPlayerGallery($player_id) {
    $rs = D()->from("player_gallery")->where("`player_id`=%d", $player_id)->order_by("`sortorder` ASC,`rid` ASC")->select("`img_std`,`img_std_cdn`")->fetch_array_all();
    $ret= [];
    if (!empty($rs)) {
    	$usecdn = C('env.usecdn');
      foreach ($rs AS $it) {
        array_push($ret, fixpath(2==$usecdn&&$it['img_std_cdn']!=''?$it['img_std_cdn']:$it['img_std']));
      }
    }
    return $ret;
  }
  
  static function getPlayerCover($player_id) {
  	$row = D()->query("SELECT pg.`img_std`,pg.`img_std_cdn` FROM `{player}` p INNER JOIN `{player_gallery}` pg ON p.cover_pic_id=pg.rid WHERE p.`player_id`=%d", $player_id)->get_one();
    $ret = '';
    if (!empty($row)) {
    	$usecdn = C('env.usecdn');
    	$ret = fixpath(2==$usecdn&&$row['img_std_cdn']!=''?$row['img_std_cdn']:$row['img_std']);
    }
    return $ret;
  }
  
  static function getRankInfo($match_id, $player_id) {
    if (!$player_id) {
      return false;
    }
    $total_players  = D()->from("player")->where("`match_id`=%d AND `status`='R'", $match_id)->select("COUNT(player_id) AS rnum")->result();
    $player_votecnt = D()->from("player")->where("`match_id`=%d AND `player_id`=%d AND `status`='R'", $match_id, $player_id)->select("votecnt")->result();
    $player_ids = D()->from("player")->where("`match_id`=%d AND `votecnt`>=%d AND `status`='R'", $match_id, $player_votecnt)->order_by("votecnt DESC, player_id ASC")->select("player_id")->fetch_column('player_id');
    $rank = 0;
    foreach ($player_ids AS $pid) {
      ++$rank;
      if ($pid==$player_id) {
        break;
      }
    }
    return ['total'=>$total_players, 'rank'=>$rank];
  }
  
  static function addVisitCnt($player_id, $inc = 1) {
    if (!$player_id) return false;
  
    D()->query("UPDATE `{player}` SET `visitcnt`=`visitcnt`+%d WHERE `player_id`=%d", $inc, $player_id);
    if (D()->affected_rows()==1) {
      return true;
    }
    return false;
  }
  
  static function getRankList($type, $start=0, $limit=20, Array $extra = array(), &$hasmore = false) {
  	if ($type=='') {
  		$type = 'most_vote';
  	}
  	if (!in_array($type, ['most_vote','most_flower','week_rank'])) {
  		return [];
  	}
  	if ($type!='week_rank' && empty($extra['player_id'])) {
  		return [];
  	}
  	
  	$result = [];
  	$limit_true = $limit+1; //多去一个为了判断是否还有下一页
  	if ($type=='most_vote' || $type=='most_flower') {
  		$sql = "SELECT a.`uid` AS user_id,m.nickname,m.logo,SUM(a.`inc`) AS action_amount,MAX(a.`timeline`) AS lasttime
  				    FROM `{action}` a INNER JOIN `{member}` m ON a.`player_id`=%d AND a.`action`='%s' AND a.`uid`=m.`uid`
  				    WHERE a.`uid` <> 10000
  				    GROUP BY user_id
  				    ORDER BY action_amount DESC
  				    LIMIT %d,%d";
  		$result = D()->query($sql, $extra['player_id'], str_replace('most_', '', $type), $start, $limit_true)->fetch_array_all();
  	}
  	elseif ($type=='week_rank') {
  		
  	}
  	
  	$hasmore = false;
  	$resnum  = count($result);
  	if ($resnum > $limit) {
  		$hasmore = true;
  	}
  	
  	return $result;
  	
  }
  
  /**
   * 获取待显示周次信息
   * 
   * @param integer $match_id
   * @return integer
   */
  static function getRankWeekInfo($match_id) {
  	$minfo = D()->from("node_match")->where("enid=%d",$match_id)->select()->get_one();
  	if (!empty($minfo)) {
  		
  		$now_dt = date('Y-m-d H:i:s');
  		$see_weekinfo = false;
  		
  		//获取当前比赛周次
  		$cur_week = D()->from("rank_week")->where("match_id=%d AND match_type='%s' AND '%s'>=start_time AND '%s'<=end_time", $match_id, $minfo['match_type'], $now_dt, $now_dt)->select('weekno')->get_one();
  		if (!empty($cur_week)) {
  			if ($cur_week['weekno'] > 1) {
  				$see_weekinfo = D()->from("rank_week")->where("match_id=%d AND match_type='%s' AND weekno=%d", $match_id, $minfo['match_type'],$cur_week['weekno']-1)->select()->get_one();
  			}
  		}
  		else { //不存在时间窗口，则表明当前时间已经超过竞赛最大时间
  			$see_weekinfo = D()->from("rank_week")->where("match_id=%d AND match_type='%s'", $match_id, $minfo['match_type'])->order_by("weekno DESC")->limit(0, 1)->select()->get_one();
  		}
  		return $see_weekinfo;
  	}
  	return false;
  }
  
  static function parsePlayerList($player_list, $see_weekinfo = array()) {
  	if (!empty($player_list)) {
  		foreach ($player_list AS &$it) {
  			$it['rankflag'] = 0;
  			$it['ranktxt']  = '';
  			if (!empty($see_weekinfo)) {
  				if ($it['player_id'] == $see_weekinfo['player_id1']) {
  					$it['rankflag']= 1;
  					$it['ranktxt'] = '第'.Fn::to_cnnum($see_weekinfo['weekno']).'周人气女神';
  				}
  				if ($it['player_id'] == $see_weekinfo['player_id2']) {
  					$it['rankflag']= 2;
  					$it['ranktxt'] = '第'.Fn::to_cnnum($see_weekinfo['weekno']).'周鲜花女神';
  				}
  			}
  		}
  	}
  	return $player_list;
  }
  
}
 
/*----- END FILE: Match_Model.php -----*/