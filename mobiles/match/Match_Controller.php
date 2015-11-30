<?php
/**
 *  Match 控制器
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
defined('IN_SIMPHP') or die('Access Denied');

class Match_Controller extends Controller {
  
  private $nav_no     = 0;       //主导航id
  private $topnav_no  = 0;       //顶部导航id
  private $nav_flag1  = 'match'; //导航标识1
  private $nav_flag2  = '';      //导航标识2
  private $nav_flag3  = '';      //导航标识3
  private $extra_css  = '';      //添加给section.scrollArea的额外css类
  
  /**
   * hook init
   *
   * @param string $action
   * @param Request $request
   * @param Response $response
   */
  function init($action, Request $request, Response $response)
  {
    $this->v = new PageView();
    $this->v->add_render_filter(function(View $v){
      $v->assign('nav_no',     $this->nav_no)
      ->assign('topnav_no',  $this->topnav_no)
      ->assign('nav_flag1',  $this->nav_flag1)
      ->assign('nav_flag2',  $this->nav_flag2)
      ->assign('nav_flag3',  $this->nav_flag3)
      ->assign('extra_css',  $this->extra_css);
    });
  }
  
  /**
   * hook menu
   * @see Controller::menu()
   */
  function menu()
  {
    return [
      'match/%d'        => 'detail', 
      'match/%d/passed' => 'passed',
      'match/%d/nopassed' => 'nopassed',
      'match/%d/join'   => 'join',
      'match/%d/rank'   => 'rank',
      'match/%d/repost' => 'repost',
      'match/%d/repost_confirm' => 'repost_confirm',
      'match/player/%d' => 'player',
    ];
  }
  
  /**
   * default action 'index'
   *
   * @param Request $request
   * @param Response $response
   */
  function index(Request $request, Response $response)
  {
    $this->v->set_tplname('mod_match_index');
    if ($request->is_hashreq()) {
  
    }
    else{
  
    }
    $response->send($this->v);
  }
  
  /**
   * 赛事活动页
   *
   * @param Request $request
   * @param Response $response
   */
  function detail(Request $request, Response $response)
  {
    $this->v->set_tplname('mod_match_detail');
    $this->nav_flag1 = 'match_detail';
    
    $nid = $request->arg(1);
    $isajax = $request->get('isajax', 0);
    $this->v->assign('the_nid', $nid)->assign("isajax", $isajax);
  
    if ($request->is_hashreq()) {
  
      $errmsg   = '';
  
      //获取Node信息
      $ninfo = Node::getInfo($nid);
      if (empty($ninfo)) {
        $errmsg = "比赛不存在(nid={$nid})";
      }
      else {
  
      	//获取页面参数
      	$limit = 20;
      	$page  = $request->get('p', 0);
      	$search= $request->get('s', '');
      	$search= trim($search);
      	$this->v->assign('search', $search);
      	
      	//检查周排名信息
      	$stick = config_get('stick'); // 是否置顶
      	$see_weekinfo = [];
      	if ($search=='') { //搜索时不检查
      		$see_weekinfo = Match_Model::getRankWeekInfo($nid);
      	}
      	$stick_player_ids  = [];
      	$stick_player_list = []; //置顶参数者列表
      	if ($stick && !empty($see_weekinfo)) {
      		array_push($stick_player_ids, $see_weekinfo['player_id1'], $see_weekinfo['player_id2']);
      		$stick_player_list = Match_Model::getPlayerList($nid, $stick_player_ids);
      	}
      	
      	if (!$isajax) {
      		
      		//更新访问次数
      		Node::addVisitCnt($nid);
      		
      		//解析一些数据
      		$match_types = Node::getMatchTypes();
      		$ninfo['match_type_text'] = $match_types[$ninfo['match_type']];
      		
      		//解析content_detail里面的[p]xxx[/p]
      		$content_parsed     = Node::parseContentParagraph($ninfo['content_detail']);
      		$content_parsed_num = count($content_parsed);
      		$this->v->assign('content_parsed', $content_parsed);
      		$this->v->assign('content_parsed_num', $content_parsed_num);
      		
      		$total_player_num = Match_Model::getPlayersNum($nid);
      		$this->v->assign('total_player_num', $total_player_num);
      	}
        
        //检查是否启用记录的页码
        if (!$page) {
        	$page = 1;
        	/*
        	if (''==$search) {
        		$page = isset($_SESSION['mark_pageno']) ? $_SESSION['mark_pageno'] : 1;
        	}
        	else {
        		$page = 1;
        	}*/
        }
        else {
        	/*
        	if (''==$search) {
        		$_SESSION['mark_pageno'] = $page;
        	}
        	*/
        }
        $start = ($page-1) * $limit;
        $totalnum = 0;
        $maxpage  = 1;
        
        $player_list = Match_Model::getPlayerList($nid, $search, $start, $limit, $totalnum, $maxpage, $stick_player_ids);
        $player_list = array_merge($stick_player_list, $player_list); //合并
        $player_list = Match_Model::parsePlayerList($player_list, $see_weekinfo);
        
        $this->v->assign('player_list', $player_list);
        $this->v->assign('player_num', count($player_list));
        $this->v->assign('curpage', $page);
        $this->v->assign('maxpage', $maxpage);
        
      }
  
      $this->v->assign('errmsg', $errmsg)
              ->assign('ninfo', $ninfo);
  
      if ($isajax) {
      	$this->v->filter_output_part();
      }
      
    }
    else {
    	$ninfo = Node::getInfo($nid);
    	if (!empty($ninfo)) {
    		//SEO信息
	      $seo = [
	        'title'   => $ninfo['title'],
	        'keyword' => $ninfo['keyword'],
	        'desc'    => $ninfo['slogan']
	      ];
	      $this->v->assign('seo', $seo);
    	}
    	
    	//分享信息
    	$share_info = [
    			'title' => $ninfo['title'],
    			'desc'  => $ninfo['slogan'],
    			'link'  => U('match/'.$ninfo['nid'], '', true),
    			'pic'   => fixpath($ninfo['thumb_url']),
    	];
    	$this->v->assign('share_info', $share_info);
    }
    
    $response->send($this->v);
  }
  
  /**
   * 获取晋级选手列表
   *
   * @param Request $request
   * @param Response $response
   */
  function passed(Request $request, Response $response)
  {
    $this->v->set_tplname('mod_match_passed');
    $this->nav_flag1 = 'match_pass';
    
    $match_id = $request->arg(1);
    $this->v->assign('the_nid', $match_id);
    
    if ($request->is_hashreq()) {
  
      $errmsg   = '';
  
      //获取Node信息
      $ninfo = Node::getInfo($match_id);
      if (empty($ninfo)) {
        $errmsg = "比赛不存在(nid={$match_id})";
      }
      else {
  
      	//参赛者列表
      	$limit = 20;
      	$isajax= $request->get('isajax', 0);
      	$page  = $request->get('p', 1);
      	$start = ($page-1) * $limit;
      	$totalnum = 0;
      	$maxpage  = 1;
      	
      	//检查周排名信息
      	$see_weekinfo = Match_Model::getRankWeekInfo($match_id);
      	
				//获取“晋级”参赛这列表
				$player_pass_list = Match_Model::getPlayerList($match_id, '5000+', $start, $limit, $totalnum, $maxpage);
				$player_pass_list = Match_Model::parsePlayerList($player_pass_list, $see_weekinfo, true);
      	$this->v->assign('player_pass_list', $player_pass_list);
      	$this->v->assign('totalnum', $totalnum);
      	$this->v->assign('maxpage', $maxpage);
      	$this->v->assign('curpage', $page);
      	
      	if ($isajax) {
      		$this->v->filter_output_part();
      	}
      }
      $this->v->assign('errmsg', $errmsg);
    }
    else {
    	
    }
    
    $response->send($this->v);
  }
  
  /**
   * 获取未晋级选手列表
   *
   * @param Request $request
   * @param Response $response
   */
  function nopassed(Request $request, Response $response)
  {
    $this->v->set_tplname('mod_match_nopassed');
    $this->nav_flag1 = 'match_nopassed';
    
    $match_id = $request->arg(1);
    $this->v->assign('the_nid', $match_id);
    
    if ($request->is_hashreq()) {
  
      $errmsg   = '';
  
      //获取Node信息
      $ninfo = Node::getInfo($match_id);
      if (empty($ninfo)) {
        $errmsg = "比赛不存在(nid={$match_id})";
      }
      else {
  
      	//参赛者列表
      	$limit = 20;
      	$isajax= $request->get('isajax', 0);
      	$page  = $request->get('p', 1);
      	$start = ($page-1) * $limit;
      	$totalnum = 0;
      	$maxpage  = 1;
      	
      	//检查周排名信息
      	//$see_weekinfo = Match_Model::getRankWeekInfo($match_id);
      	$see_weekinfo = [];
      	
				//获取“未晋级”参赛这列表
				$player_pass_list = Match_Model::getPlayerList($match_id, '5000-', $start, $limit, $totalnum, $maxpage);
				$player_pass_list = Match_Model::parsePlayerList($player_pass_list, $see_weekinfo, true);
      	$this->v->assign('player_pass_list', $player_pass_list);
      	$this->v->assign('totalnum', $totalnum);
      	$this->v->assign('maxpage', $maxpage);
      	$this->v->assign('curpage', $page);
      	
      	if ($isajax) {
      		$this->v->filter_output_part();
      	}
      }
      $this->v->assign('errmsg', $errmsg);
    }
    else {
    	
    }
    
    $response->send($this->v);
  }
  
  /**
   * 参加活动页
   *
   * @param Request $request
   * @param Response $response
   */
  function join(Request $request, Response $response)
  {
    //最大上传图片数
    $maxuploadnum = 6;
    
    if ($request->is_post()) { //提交数据
  
      $res = ['flag'=>'FAIL', 'msg'=>''];
  
      $match_id = $request->post('nid', 0);
      $truename = $request->post('truename', '');
      $mobile   = $request->post('mobile', '');
      $weixin   = $request->post('weixin', '');
      $province = $request->post('province', 0);
      $city     = $request->post('city', 0);
      $idcard   = $request->post('idcard', '');
      $video    = $request->post('video', '');
      $slogan   = $request->post('slogan', '');
      $remark   = $request->post('remark', '');
      $imgs     = $request->post('imgs', []);
  
      $truename = trim($truename);
      if(''==$truename){
        $res['msg'] = '真实姓名必须填写';
        $response->sendJSON($res);
      }
  
      $mobile = trim($mobile);
      if(''==$mobile){
        $res['msg'] = '手机号必须填写';
        $response->sendJSON($res);
      }
      elseif (!preg_match("/^\d{11,14}$/", $mobile)) {
        $res['msg'] = '手机号不合法';
        $response->sendJSON($res);
      }
  
      $weixin = trim($weixin);
      if(''==$weixin){
        $res['msg'] = '微信号必须填写';
        $response->sendJSON($res);
      }
  
      $idcard = trim($idcard);
      if (''!=$idcard && strlen($idcard)!=18 && strlen($idcard)!=15) {
        $res['msg'] = '身份证号不合法';
        $response->sendJSON($res);
      }
  
      $video = trim($video);
      if (''!=$video && !preg_match("/^http:\/\//i", $video)) {
        $res['msg'] = '视频地址不合法';
        $response->sendJSON($res);
      }
  
      if (empty($imgs)) {
        $res['msg'] = '请至少上传一张图片';
        $response->sendJSON($res);
      }
      elseif (count($imgs) > $maxuploadnum) {
        $res['msg'] = '最多只能上传'.$maxuploadnum.'张图片，请删除'.(count($imgs)-$maxuploadnum).'张再提交';
        $response->sendJSON($res);
      }
  
      $slogan = trim($slogan);
      $remark = trim($remark);
  
      //将省份、城市平均成: "40:北京"这样的结构
      if ($province) {
        $loc = Match_Model::getLocationName($province);
        if ($loc) {
          $province = $province.':'.$loc;
        }
      }
      else {
        $province = '';
      }
      if ($city) {
        $loc = Match_Model::getLocationName($city);
        if ($loc) {
          $city = $city.':'.$loc;
        }
      }
      else {
        $city = '';
      }
  
      $_data = [
        'truename' => $truename,
        'mobile'   => $mobile,
        'weixin'   => $weixin,
        'province' => $province,
        'city'     => $city,
        'idcard'   => $idcard,
        'video'    => $video,
        'slogan'   => $slogan,
        'remark'   => $remark,
        'status'   => 'R'
      ];
  
      $ret = Match_Model::joinMatch($match_id, $_data);
      if($ret>0){
        $res['flag'] = 'SUC';
        $res['player_id'] = $ret;
        
        //保存图片
        $i = 1;
        $cover_pic_id = 0;
        foreach ($imgs AS $img) {
          $imgpaths = Match_Model::saveImgData($img);
          if (is_numeric($imgpaths)) {
            continue;
          }
          $rid = Match_Model::savePlayerGallery($res['player_id'], $imgpaths);
          if (1==$i++) { //默认第一张为封面图片
          	$cover_pic_id = $rid;
          }
        }
        
        //更新封面图片id
        D()->update("player",['cover_pic_id'=>$cover_pic_id],['player_id'=>$res['player_id']]);
      }
      else{
        if (-1==$ret) {
          $res['msg']= 'match_id为空';
        }
        elseif (-2==$ret) {
          $res['msg']= '手机号和微信号为空';
        }
        elseif (-3==$ret||-4==$ret) {
          $res['msg']= '已经报名过，不需要重复报名';
        }
        else {
          $res['msg']= '系统繁忙，请稍后再试！';
        }
      }
      $response->sendJSON($res);
  
    }
    else { //载入页面
      $this->v->set_tplname('mod_match_join');
      $this->v->set_page_render_mode(View::RENDER_MODE_GENERAL);
  
      $errmsg   = '';
      $nid = $request->arg(1);
      if(empty($nid)) {
        $errmsg = 'nid为空';
      }
      elseif (!Node::isExisted($nid)) {
        $errmsg = "赛事不存在(nid={$nid})";
      }
      else {
        //SEO信息
        $ninfo = Node::getInfo($nid);
        $seo = [
          'title'   => '参加比赛 - '.$ninfo['title'],
          'keyword' => $ninfo['keyword'],
          'desc'    => $ninfo['title'],
        ];
        $this->v->assign('seo', $seo);
      }

      if (''==$errmsg) {
        $province = Match_Model::getProvinces();
        $this->v->assign('province', $province);
        $this->v->assign('nid', $nid);
      }

      $this->v->assign('errmsg', $errmsg);
      $this->v->assign('maxuploadnum', $maxuploadnum);
      
      $response->send($this->v);
    }
  }
  
  /**
   * 重复上传图片前的选手确认
   * @param Request $request
   * @param Response $response
   */
  function repost_confirm(Request $request, Response $response)
  {
  	$this->v->set_tplname('mod_match_repost_confirm');
  	$this->v->set_page_render_mode(View::RENDER_MODE_GENERAL);
  	$errmsg    = '';
  	
  	if ($request->is_post()) { //提交数据
  		$match_id  = $request->post('match_id', 0);
  		$player_id = $request->post('player_id', 0);
  		
  		$player_info = Match_Model::getPlayerInfo($player_id);
  		if (empty($match_id) || !Node::isExisted($match_id)) {
  			$errmsg = "赛事不存在(match_id={$match_id})";
  		}
	  	elseif (empty($player_info) || $player_info['status']=='D') {
	      $errmsg = "该参赛者不存在(参赛号：{$player_id})";
	    }
	    elseif ($player_info['status']<>'R') {
	      $errmsg = "该参赛者被冻结(参赛号：{$player_id})";
	    }
	    elseif ($player_info['stage']==0) {
	    	$errmsg = "该参赛者未晋级，不能修改。";
	    }
	    elseif ($player_info['repostcnt']>0) {
	    	$errmsg = "该参赛者已修改过资料，不能再修改。";
	    }
	    else {
	    	$errmsg = '<em style="color: green">检测通过！</em><script type="text/javascript">window.location.href="'.U('match/'.$match_id.'/repost',['player_id'=>$player_id]).'"</script>';
	    }
	    
	    $this->v->assign('errmsg', $errmsg);
	    $this->v->assign('match_id', $match_id);
	    $response->send($this->v);
  	}
  	else {
  		$nid       = $request->arg(1);
  		
  		if (empty($nid) || !Node::isExisted($nid)) {
  			$errmsg = "赛事不存在(nid={$nid})";
  		}
  		else {
  			//SEO信息
  			$ninfo = Node::getInfo($nid);
  			$seo = [
  					'title'   => '上传复赛照片选手确认 - '.$ninfo['title'],
  					'keyword' => $ninfo['keyword'],
  					'desc'    => $ninfo['title'],
  			];
  			$this->v->assign('seo', $seo);
  			$this->v->assign('match_id', $nid);
  		}
  		
  		$this->v->assign('errmsg', $errmsg);
  		$response->send($this->v);
  	}
  }
  
  /**
   * 重复上传图片
   *
   * @param Request $request
   * @param Response $response
   */
  function repost(Request $request, Response $response)
  {
    //最大上传图片数
    $maxuploadnum = 10;
    
    if ($request->is_post()) { //提交数据
  
      $res = ['flag'=>'FAIL', 'msg'=>''];
  
      $player_id = $request->post('player_id', 0);/*
      $truename = $request->post('truename', '');
      $mobile   = $request->post('mobile', '');
      $weixin   = $request->post('weixin', '');
      $province = $request->post('province', 0);
      $city     = $request->post('city', 0);
      $idcard   = $request->post('idcard', '');
      $slogan   = $request->post('slogan', '');
      $remark   = $request->post('remark', '');*/
      $video    = $request->post('video', '');
      $imgs     = $request->post('imgs', []);
      $cover_idx= $request->post('cover_idx', 0);
      
      if (!Match_Model::canRepost($player_id)) {
      	$res['msg'] = '该参赛者已修改过资料，不能再修改。';
      	$response->sendJSON($res);
      }
  /*
      $res['imgs'] = $imgs;
      $response->sendJSON($res);
      
      $truename = trim($truename);
      if(''==$truename){
        $res['msg'] = '真实姓名必须填写';
        $response->sendJSON($res);
      }
  
      $mobile = trim($mobile);
      if(''==$mobile){
        $res['msg'] = '手机号必须填写';
        $response->sendJSON($res);
      }
      elseif (!preg_match("/^\d{11,14}$/", $mobile)) {
        $res['msg'] = '手机号不合法';
        $response->sendJSON($res);
      }
  
      $weixin = trim($weixin);
      if(''==$weixin){
        $res['msg'] = '微信号必须填写';
        $response->sendJSON($res);
      }
  
      $idcard = trim($idcard);
      if (''!=$idcard && strlen($idcard)!=18 && strlen($idcard)!=15) {
        $res['msg'] = '身份证号不合法';
        $response->sendJSON($res);
      }
      */
      $video = trim($video);
      if (''!=$video && !preg_match("/^http:\/\//i", $video)) {
        $res['msg'] = '视频地址不合法';
        $response->sendJSON($res);
      }
  
      if (empty($imgs)) {
        $res['msg'] = '请至少上传一张图片';
        $response->sendJSON($res);
      }
      elseif (count($imgs) > $maxuploadnum) {
        $res['msg'] = '最多只能上传'.$maxuploadnum.'张图片，请删除'.(count($imgs)-$maxuploadnum).'张再提交';
        $response->sendJSON($res);
      }
  /*
      $slogan = trim($slogan);
      $remark = trim($remark);
  
      //将省份、城市平均成: "40:北京"这样的结构
      if ($province) {
        $loc = Match_Model::getLocationName($province);
        if ($loc) {
          $province = $province.':'.$loc;
        }
      }
      else {
        $province = '';
      }
      if ($city) {
        $loc = Match_Model::getLocationName($city);
        if ($loc) {
          $city = $city.':'.$loc;
        }
      }
      else {
        $city = '';
      }
  */
      $_data = [/*
        'truename' => $truename,
        'mobile'   => $mobile,
        'weixin'   => $weixin,
        'province' => $province,
        'city'     => $city,
        'idcard'   => $idcard,
        'slogan'   => $slogan,
        'remark'   => $remark,
        'status'   => 'R',*/
        'video'    => $video
      ];
      
      //保存新增图片，并保持原来的顺序
      $i = 1;
      $cover_pic_id = 0;
      foreach ($imgs AS &$img) {
      	if (!is_numeric($img)) {
      		$imgpaths = Match_Model::saveImgData($img);
      		if (is_numeric($imgpaths)) {
      			unset($img);
      			continue;
      		}
      		$rid = Match_Model::savePlayerGallery($player_id, $imgpaths);
      		if ($rid) {
      			$img = "{$rid}";
      		}
      		else {
      			unset($img);
      		}
      	}
      	if (!$cover_pic_id && isset($img) && is_numeric($img)) { //默认第一张为封面图片
      		$cover_pic_id = $img;
      	}
      }
      //更新图片
      if (!empty($imgs) && is_array($imgs)) { //! 务必检查严格
      	if ($cover_idx && isset($imgs[$cover_idx])) { //如果客户端设定的封面图片有效，则采用客户端的设置
      		$cover_pic_id = $imgs[$cover_idx];
      	}
      	$imgs_idstr = implode(',', $imgs);
      	$existed_rids = D()->from("player_gallery")->where("`rid` IN(%s)", $imgs_idstr)->select("`rid`")->fetch_column('rid');
      	if (!empty($existed_rids)) { //! 务必检查严格，否则容易出现丢失图片数据
      		//先将原有的记录的player_id设为0
      		D()->query("UPDATE `{player_gallery}` SET `old_player_id`=`player_id`,`player_id`=0 WHERE `player_id`=%d",$player_id);
      		//紧接着重新关联新的记录
      		D()->query("UPDATE `{player_gallery}` SET `player_id`=%d,`old_player_id`=%d WHERE `rid` IN(%s)", $player_id, $player_id, $imgs_idstr);
      		//更新排序
      		$o = 1;
      		foreach ($imgs AS $rid) {
      			D()->query("UPDATE `{player_gallery}` SET `sortorder`=%d WHERE `rid`=%d", $o, $rid);
      			$o++;
      		}
      	}
      }
      
      //更新参赛者信息
      $_data['cover_pic_id'] = $cover_pic_id;
      $_data['repostcnt'] = 1;
      D()->update("player", $_data, ['player_id'=>$player_id]);
      
      //返回
      $res['flag'] = 'SUC';
      $res['player_id'] = $player_id;
      $response->sendJSON($res);
  
    }
    else { //载入页面
      $this->v->set_tplname('mod_match_repost');
      $this->v->set_page_render_mode(View::RENDER_MODE_GENERAL);
  
      $errmsg    = '';
      $nid       = $request->arg(1);
      $player_id = $request->get('player_id', 0);
      
      $player_info = Match_Model::getPlayerInfo($player_id);
      if (empty($nid) || !Node::isExisted($nid)) {
        $errmsg = "赛事不存在(nid={$nid})";
      }
      elseif (empty($player_info) || $player_info['status']=='D') {
      	$errmsg = "参赛者不存在(player_id={$player_id})";
      }
      elseif ($player_info['status']<>'R') {
      	$errmsg = "参赛者被冻结(player_id={$player_id})";
      }
      elseif ($player_info['stage']==0) {
      	$errmsg = "该参赛者未晋级，不能修改。";
      }
      else {
        //SEO信息
        $ninfo = Node::getInfo($nid);
        $seo = [
          'title'   => '上传复赛照片 - '.$ninfo['title'],
          'keyword' => $ninfo['keyword'],
          'desc'    => $ninfo['title'],
        ];
        $this->v->assign('seo', $seo);
        
        //获取选手图片信息
        $rs = Match_Model::getPlayerGallery($player_id, true);
        $player_gallery = $rs ? : [];
        $this->v->assign('player_gallery', $player_gallery);
        $this->v->assign('player_gallery_num', count($player_gallery));
      }

      /*
      if (''==$errmsg) {
        $province = Match_Model::getProvinces();
        $this->v->assign('province', $province);
        $this->v->assign('nid', $nid);
      }
      */
      
      $this->v->assign('errmsg', $errmsg);
      $this->v->assign('maxuploadnum', $maxuploadnum);
      $this->v->assign('player_info', $player_info);
      
      $response->send($this->v);
    }
  }
  
  /**
   * 获取省下的城市名列表
   *
   * @param Request $request
   * @param Response $response
   */
  function cities(Request $request, Response $response) {
    $parent_id = $request->get('parent_id', 0);
    $res = ['flag'=>'FAIL', 'msg'=>''];
    if (empty($parent_id)) {
      $res['msg'] = 'parent_id empty';
      $response->sendJSON($res);
    }
    $cities = Match_Model::getCities($parent_id);
    if (empty($cities)) {
      $res['msg'] = 'parent_id invalid';
      $response->sendJSON($res);
    }
    $res['flag'] = 'SUC';
    $res['msg']  = '';
    $res['data'] = $cities;
    $response->sendJSON($res);
  }
  
  /**
   * 参赛者详情页
   *
   * @param Request $request
   * @param Response $response
   */
  function player(Request $request, Response $response)
  {
    $this->v->set_tplname('mod_match_player');
    $this->v->set_page_render_mode(View::RENDER_MODE_GENERAL); //改变默认的hashreq请求模式，改为普通页面请求(一次过render页面所有内容)
    $this->extra_css = 'fixmaxheight';
    $this->nav_flag1 = 'match_player';
  
    $player_id = $request->arg(2);
    $this->v->assign('player_id', $player_id);
  
    $errmsg   = '';

    //获取player信息
    $player_info = Match_Model::getPlayerInfo($player_id);
    if (empty($player_info) || $player_info['status']=='D') {
      $errmsg = "该参赛者不存在(参赛号：{$player_id})";
    }
    elseif ($player_info['status']<>'R') {
      $errmsg = "该参赛者被冻结(参赛号：{$player_id})";
    }
    else {

      //更新Player访问次数
      Match_Model::addVisitCnt($player_id);
      
      //更新Node访问次数
      Node::addVisitCnt($player_info['match_id']);

      //获取选手图片信息
      $rs = Match_Model::getPlayerGallery($player_id);
      $player_gallery = $rs ? : [];
      $this->v->assign('player_gallery', $player_gallery);
      $this->v->assign('player_gallery_num', count($player_gallery));
      
      $player_cover = Match_Model::getPlayerCover($player_id);
      if (empty($player_cover)) {
      	$player_cover = isset($player_gallery[0]) ? $player_gallery[0] : '';
      }
      
      $ninfo = Node::getInfo($player_info['match_id']);
      
      //选手“投票数”统计
      $time_from = 0;
      if ($player_info['stage'] > 0) {
      	$time_from = Node::getMatchStageTime($player_info['match_id']);
      }
      $player_info['votecnt_single'] = Node::getActionNum($player_id, 'vote', $time_from);
      
      //排名信息
      $player_info['rank_info'] = Match_Model::getRankInfo($player_info['match_id'], $player_id);
      
      $usecdn = C('env.usecdn');
      $player_info['qrcode'] = 2==$usecdn&&$player_info['qrcode_cdn']!='' ? $player_info['qrcode_cdn'] : $player_info['qrcode'];
      
      //SEO信息
      $seo = [
        'title'   => $player_info['player_id'].'号 '.$player_info['truename'] . ' - '.$ninfo['title'],
        'keyword' => $player_info['truename'].','.$ninfo['keyword'],
        'desc'    => $ninfo['slogan'],
      ];
      $this->v->assign('seo', $seo);
      
      //分享信息
      $share_info = [
        'title' => '我是'.$player_info['player_id'].'号'.$player_info['truename'].'，正在参加'.$ninfo['title'].'，快来支持我吧！记得是'.$player_info['player_id'].'号哟~',
        'desc'  => $ninfo['slogan'],
        'link'  => U('player/'.$player_info['player_id'], '', true),
        'pic'   => $player_cover,
      ];
      if ($player_info['stage'] > 0) {
      	$share_info['title'] = '我是'.$player_info['player_id'].'号'.$player_info['truename'].'，我已经成功进入'.$ninfo['title'].'复赛，快来给我投票，让我挺进决赛！';
      }
      $this->v->assign('share_info', $share_info);
      
      //送花效果
      $animatenum = 0;
      $effectnum = config_get('flower_animate_num');
      if (isset($_SESSION['animatenum_'.$player_id]) && $_SESSION['animatenum_'.$player_id] >= $effectnum) {
      	$animatenum = $_SESSION['animatenum_'.$player_id];
      	unset($_SESSION['animatenum_'.$player_id]);
      }
      $this->v->assign('animatenum', $animatenum);
    }

    $this->v->assign('errmsg', $errmsg)
            ->assign('player_info', $player_info)
            ;
    
    $response->send($this->v);
  }

  /**
   * 参赛者详情页
   *
   * @param Request $request
   * @param Response $response
   */
  function rank(Request $request, Response $response)
  {
    $this->v->set_tplname('mod_match_rank');
    $this->v->set_page_render_mode(View::RENDER_MODE_GENERAL); //改变默认的hashreq请求模式，改为普通页面请求(一次过render页面所有内容)
    $this->nav_flag1 = 'match_rank';
    $this->topnav_no = 1;
  
    $match_id  = $request->arg(1);
    $player_id = $request->get('player_id',0);
    $isajax    = $request->get('isajax',0);
    $type      = $request->get('t','');
    $page      = $request->get('p',1);
    $return_url= U('match/'.$match_id);
    if (!empty($player_id)) {
    	$return_url= U('player/'.$player_id);
    }
    $this->v->assign('match_id',  $match_id);
    $this->v->assign('player_id', $player_id);
    $this->v->assign('return_url', $return_url);
    $this->v->assign('type', $type);
    $this->v->assign('isajax', $isajax);
    if ($page<1 || !is_numeric($page)) {
    	$page = 1;
    }

    $errmsg   = '';
    $ranklist = [];
    $limit    = 30;
    $start    = ($page-1) * $limit;
    $hasmore  = false;
    
    //~ 获取match信息及player信息
    $ninfo = Node::getInfo($match_id);
    
    //~ 设置SEO信息
    if (!empty($ninfo)) {
    	
    	if (!$isajax) {
    		$seo = [
    				'title'   => '选手排名 - ' . $ninfo['title'],
    				'keyword' => $ninfo['keyword'],
    				'desc'    => $ninfo['slogan'],
    		];
    		$this->v->assign('seo', $seo);
    		
    		//分享信息
    		$share_info = [
    				'title' => '选手排名 - '.$ninfo['title'],
    				'desc'  => $ninfo['slogan'],
    				'link'  => U('match/'.$ninfo['nid'].'/rank', '', true),
    				'pic'   => $ninfo['thumb_url'],
    		];
    		$this->v->assign('share_info', $share_info);
    	}
    	
    	//~ 获取列表
    	$ranklist = Match_Model::getRankList($type, $start, $limit, ['match_id'=>$match_id], $hasmore);
    	
    	if ($isajax) {
    		$this->v->filter_output_part();
    	}
    }
    else {
    	$errmsg = "该比赛不存在(nid：{$match_id})";
    }
		
		$this->v->assign('errmsg', $errmsg);
		$this->v->assign('ranklist', $ranklist);
		$this->v->assign('listnum', count($ranklist));
		$this->v->assign('hasmore', $hasmore);
		$this->v->assign('nextpage', $page+1);
    
    $response->send($this->v);
  }
  
  /**
   * 投票操作
   *
   * @param Request $request
   * @param Response $response
   */
  function vote(Request $request, Response $response)
  {
    if ($request->is_post()) { //提交数据
      $player_id = $request->post('player_id', 0);
      
      $res = ['flag'=>'FAIL', 'msg'=>''];
      if (empty($player_id)) {
        $res['msg'] = 'player_id empty';
        $response->sendJSON($res);
      }
      
      $uid = $GLOBALS['user']->uid;
      if (empty($uid)) {
        $res['msg'] = '您没登录，请先在微信端登录';
        $response->sendJSON($res);
      }
      
      $ret = Node::action('vote', $player_id, $uid);
      if ($ret >= 0) {
      	
      	$time_from  = 0;
      	$vote_field = 'votecnt';
      	$player_info= Match_Model::getPlayerInfo($player_id);
      	if ($player_info['stage'] > 0) {
      		$time_from  = Node::getMatchStageTime($player_info['match_id']);
      		$vote_field = Node::getVoteFiled($player_info['stage']);
      	}
        
        //返回当前player总投票数(包括flower加权)
        $votedcnt = $player_info[$vote_field];
        
        //返回当前player投票数
        $votedcnt_single = Node::getActionNum($player_id, 'vote', $time_from);
        
        $res['flag'] = 'SUC';
        $res['msg']  = "投票成功！";
        $res['votedcnt']  = $votedcnt;
        $res['votedcnt_single']  = $votedcnt_single;
        if ($ret > 0) {
          $res['msg']  .= "您今天还可以投<em style=\"color:red\">{$ret}</em>票！";
        }
        else {
        	//您已投票成功！请关注大赛微信公众平台帐号：FEOfeel，随时关注比赛动态
          $res['msg']  .= '您今天的票数已用完，明天再来，还可以给女神<em style="color:red">送花</em>或为其他女神投票哦~';
        }
        $response->sendJSON($res);
      }
      else {
        if (-11==$ret) {
          $res['msg']  = '票数已用完，明天再来，还可以给女神<em style="color:red">送花</em>或为其他女神投票哦~';
        }
        elseif (-12==$ret) {
          $res['msg']  = '连续投票时间间隔要在2小时以上';
        }
        else {
          $res['msg']  = '发生未知错误';
        }
        $response->sendJSON($res);
      }
    }
  }
    
}
 
/*----- END FILE: Match_Controller.php -----*/