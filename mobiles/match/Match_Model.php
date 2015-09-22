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
      $rs = D()->from("player")->where("`match_id`=%d AND `mobile`='%s'", $match_id, $data['mobile'])->select("player_id")->result();
      if ($rs) {
        $existed = true;
        return -3;
      }
    }
    if (isset($data['weixin']) && !empty($data['weixin'])) {
      $rs = D()->from("player")->where("`match_id`=%d AND `weixin`='%s'", $match_id, $data['weixin'])->select("player_id")->result();
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
  
    $b = explode(',', $img_data); //$img_data like 'data:image/jpeg;base64,/9j/4AAQSk...'
  
    $file_data = $b[1];
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
  
  static function getPlayerList($match_id) {
    $list = D()->from("player")->where("`match_id`=%d AND `status`='R'",$match_id)
              ->select("`player_id`,`match_id`,`uid`,`truename`,`slogan`,`votecnt`,`flowercnt`,`kisscnt`")
              ->fetch_array_all();
    if (!empty($list)) {
      foreach($list AS &$it) {
        $rs = D()->from("player_gallery")->where("`player_id`=%d", $it['player_id'])->select("`img_thumb`")->result();
        $it['img_thumb'] = $rs ? : '';
      }
    }
    return $list;
  }
  
  static function getPlayerInfo($player_id) {
    $rs = D()->from("player")->where("`player_id`=%d", $player_id)->select("*")->get_one();
    return $rs;
  }
  
  static function getPlayerGallery($player_id) {
    $base_url  = C('env.site.mobile');
    $rs = D()->from("player_gallery")->where("`player_id`=%d", $player_id)->select("`img_std`")->fetch_column('img_std');
    if (!empty($rs)) {
      foreach ($rs AS &$it) {
        $it = $base_url . $it;
      }
    }
    return $rs;
  }
  
  static function getActionNum($player_id, $type = 'vote') {
    if (!$player_id || !in_array($type, ['vote','flower','kiss'])) {
      return -1;
    }
    $num = D()->from("action")->where("`player_id`=%d AND `action`='%s'", $player_id, $type)->select("COUNT(aid) AS cnt")->result();
    return $num;
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
  
  /**
   * 
   * @param string $act, 'vote','flower','kiss'
   * @param integer $player_id
   * @param integer $uid
   * @param integer $inc
   * @param boolean $nocheck
   * @return number
   *   -1: vote超过了最大次数(5)
   *   -2: vote时间间隔没超过120分钟
   */
  static function action($act, $player_id, $uid, $inc = 1,$nocheck = FALSE) {
    
    if ($act == 'vote') {
      //规则：
      // 1、一个用户一天可以对每个女神投5次，可连续投
      $maxnum = 5;
      
      $votedcnt = 0;
      $now = simphp_time();
      if (!$nocheck) {
        
        $today_start = shorttotime('jt');
        $today_end   = shorttotime('mt');
        
        //查找当天已经投的次数
        $votedcnt = D()->from("action")->where("`player_id`=%d AND `action`='%s' AND `uid`=%d AND `timeline`>=%d AND `timeline`<%d", $player_id,$act,$uid,$today_start,$today_end)
                       ->select("COUNT(`aid`) AS cnt")->result();
        if ($votedcnt >= $maxnum) {
          return -1;
        }
        
        /*
        //查找前一次投票时间
        $now = simphp_time();
        $latest = D()->from("action")->where("`action`='%s' AND `player_id`=%d AND `uid`=%d", $act, $player_id, $uid)->order_by("`aid` DESC")->limit(1)
                     ->select("`timeline`")->result();
        if (($now - $latest) < 60*120) {
          return -2;
        }
        */
        
      }
      
      $aid = D()->insert("action", ['action'=>$act, 'player_id'=>$player_id, 'uid'=>$uid, 'timeline'=>$now]);
      if ($aid) {
        
        //更新player投票数
        D()->query("UPDATE {player} SET votecnt=votecnt+1 WHERE player_id=%d", $player_id);
        
        //更新node总投票数
        $match_id = D()->from("player")->where("player_id=%d", $player_id)->select("match_id")->result();
        D()->query("UPDATE {node} SET votecnt=votecnt+1 WHERE nid=%d", $match_id);
        
        return $maxnum - $votedcnt - 1; //返回当前剩余可投票数
      }
    }
    
    return -100;
  }
  
}
 
/*----- END FILE: Match_Model.php -----*/