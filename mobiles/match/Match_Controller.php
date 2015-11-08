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
      'match/%d/join'   => 'join',
      'match/%d/rank'   => 'rank',
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
        $errmsg = "Node不存在(nid={$nid})";
      }
      else {
  
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
      		
      	}
  
        //参赛者列表
        $limit = 20;
        $page  = $request->get('p', 1);
        $search= $request->get('s', '');
        $search= trim($search);
        $this->v->assign('search', $search);
        
        $start = ($page-1) * $limit;
        $totalnum = 0;
        $maxpage  = 1;
        
        //检查周排名信息
        $see_weekinfo = [];
        if ($search=='') { //搜索时不检查
        	$see_weekinfo = Match_Model::getRankWeekInfo($nid);
        }
        $ceil_player_ids  = [];
        $ceil_player_list = [];
        if (!empty($see_weekinfo)) {
        	array_push($ceil_player_ids, $see_weekinfo['player_id1'], $see_weekinfo['player_id2']);
        	$ceil_player_list = Match_Model::getPlayerList($nid, $ceil_player_ids);
        	if (!empty($ceil_player_list)) {
        		foreach ($ceil_player_list AS &$it) {
        			$it['rankflag'] = 0;
        			$it['ranktxt']  = '';
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
        $this->v->assign('ceil_player_list', $ceil_player_list);
        
        $player_list = Match_Model::getPlayerList($nid, $search, $start, $limit, $totalnum, $maxpage, $ceil_player_ids);
        $this->v->assign('player_list', $player_list);
        $this->v->assign('player_num', count($player_list));
        $this->v->assign('totalnum', $totalnum);
        $this->v->assign('curpage', $page);
        $this->v->assign('maxpage', $maxpage);
        
      }
  
      $this->v->assign('errmsg', $errmsg)
              ->assign('ninfo', $ninfo);
  
      if ($isajax) {
      	$this->v->add_output_filter(function($result){
      		preg_match_all('/<!\-\-\{AJAXPART\}\-\->(.*)<!\-\-\{\/AJAXPART\}\-\->/s', $result, $matches);
      		if (!empty($matches) && !empty($matches[1][0])) {
      			$result = $matches[1][0];
      		}
      		return $result;
      	});
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
    if (empty($player_info)) {
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
      $player_info['votecnt_single'] = Node::getActionNum($player_id, 'vote');
      
      //排名信息
      $player_info['rank_info'] = Match_Model::getRankInfo($player_info['match_id'], $player_id);
      
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
      $this->v->assign('share_info', $share_info);
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
    $player_id = $request->get('player_id', 0);
    $isajax    = $request->get('isajax',0);
    $type      = $request->get('t','');
    $page      = $request->get('p',1);
    $this->v->assign('match_id',  $match_id);
    $this->v->assign('player_id', $player_id);
    $this->v->assign('type', $type);
    if ($page<1 || !is_int($page)) {
    	$page = 1;
    }

    $errmsg   = '';
    $ranklist = [];
    $limit    = 30;
    $start    = ($page-1) * $limit;
    $hasmore  = false;
    
    //~ 获取match信息及player信息
    $ninfo = Node::getInfo($match_id);
    $player_info = Match_Model::getPlayerInfo($player_id);
    
    //~ 设置SEO信息
    if (!empty($ninfo)) {
    	
    	if (!$isajax) {
    		$seo = [
    				'title'   => $ninfo['title'],
    				'keyword' => $ninfo['keyword'],
    				'desc'    => $ninfo['slogan'],
    		];
    		if ($type=='week_rank') {
    			$seo['title'] = '全场周冠军 - '.$seo['title'];
    		}
    		else {
    			if (empty($player_info)) {
    				$seo['title'] = '错误提示 - '.$seo['title'];
    			}
    			else {
    				$seo['title'] = $player_info['player_id'].'号 '.$player_info['truename'].' 贡献榜';
    			}
    		}
    		$this->v->assign('seo', $seo);
    	}
    	
    	//~ 获取列表
    	if ($type=='week_rank') { //不需要检查参赛者是否存在
    		$ranklist = Match_Model::getRankList($type, $start, $limit, ['match_id'=>$match_id], $hasmore);
    	}
    	else { //需要检查参赛者是否存在
    		if (empty($player_info)) {
    			$errmsg = "该参赛者不存在(参赛号：{$player_id})";
    		}
    		elseif ($player_info['status']<>'R') {
    			$errmsg = "该参赛者被冻结(参赛号：{$player_id})";
    		}
    		else {
    			$ranklist = Match_Model::getRankList($type, $start, $limit, ['player_id'=>$player_id], $hasmore);
    		}
    	}
    	
    	if ($isajax) {
    		$this->v->add_output_filter(function($result){
    			preg_match_all('/<!\-\-\{AJAXPART\}\-\->(.*)<!\-\-\{\/AJAXPART\}\-\->/s', $result, $matches);
    			if (!empty($matches) && !empty($matches[1][0])) {
    				$result = $matches[1][0];
    			}
    			return $result;
    		});
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
        
        //返回当前player总投票数(包括flower加权)
        $votedcnt = D()->from("player")->where("player_id=%d", $player_id)->select("votecnt")->result();
        
        //返回当前player投票数
        $votedcnt_single = Node::getActionNum($player_id, 'vote');
        
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