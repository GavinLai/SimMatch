<?php
/**
 * Member控制器
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
defined('IN_SIMPHP') or die('Access Denied');

class Member_Controller extends Controller {

  private $_nav = 'yh';
  private $_nav_second = '';//二级导航

  public function menu() {
  	return [
  		'member/%d/edit'  => 'member_edit',
  		'member/save'     => 'member_save',
  		'member/loginlog' => 'member_loginlog',
  		'member/player'            => 'player',
  		'member/cities'            => 'cities',
  		'member/player/%d/edit'    => 'player_edit',
  		'member/player/%d/suspend' => 'player_suspend',
  		'member/player/%d/delete'  => 'player_delete',
  	];
  }
  
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
  		$v->assign('nav',     $this->_nav)
  		  ->assign('nav_second',  $this->_nav_second);
  	});
  }
  
  /**
   * default action 'index'
   * @param Request $request
   * @param Response $response
   */
  public function index(Request $request, Response $response)
  {
  	$search=array();
  	
  	$search['time_type']=0;
  	$search['stime']='';
  	$search['etime']='';
  	$search['username']='';
    $search['nickname']='';
  	$search['uid']='';
  	$search['has_coin']=0;
  	$search['disabled_user']=0;
  	$search['sort']='uid_desc';//排序字段,example:coin_asc,coin_desc
  	
  	//不更新查询条件
  	if(empty($_POST)){
  		if(isset($_SESSION['query']['member_list'])){
  			$search = $_SESSION['query']['member_list'];
  		}else{
  			$_SESSION['query']['member_list'] = $search;
  		}
  	}else{
  		//更新查询条件
  		$search['time_type']=empty($_POST['time_type'])?0:intval($_POST['time_type']);
  		$search['stime']=empty($_POST['stime'])?'':addslashes(trim($_POST['stime']));
  		$search['etime']=empty($_POST['etime'])?'':addslashes(trim($_POST['etime']));
  		$search['username']=empty($_POST['username'])?'':addslashes(trim($_POST['username']));
      $search['nickname']=empty($_POST['nickname'])?'':addslashes(trim($_POST['nickname']));
  		$search['uid']=empty($_POST['uid'])?'':addslashes(trim($_POST['uid']));
  		$search['has_coin'] = isset($_POST['has_coin']) ?  intval($_POST['has_coin']):0;
  		$search['disabled_user'] = isset($_POST['disabled_user']) ?  intval($_POST['disabled_user']):0;
  		$search['sort']=empty($_POST['sort'])? 'uid_desc':addslashes(trim($_POST['sort']));
  	
  		$_SESSION['query']['member_list'] = $search;
  	}
  	
  	$where = "";
  	if($search['username']!=''){
  		$where .=" and username='".$search['username']."'";
  	}
    if($search['nickname']!=''){
      $where .=" and nickname='".$search['nickname']."'";
    }
  	if($search['uid']!=''){
  		$where .=" and uid='".$search['uid']."'";
  	}
  	if($search['time_type']==1){//注册时间
  		if($search['stime']!=''){
  			$where .=" and regtime>=".strtotime($search['stime']);
  		}
  		if($search['etime']!=''){
  			$where .=" and regtime<=".strtotime($search['etime']);
  		}
  	}
  	if($search['time_type']==2){//登录时间
  		if($search['stime']!=''){
  			$where .=" and lasttime>=".strtotime($search['stime']);
  		}
  		if($search['etime']!=''){
  			$where .=" and lasttime<=".strtotime($search['etime']);
  		}
  	}
  	
  	//用户是否有平台币
  	if($search['has_coin']>0){
  		$where .= " AND coin!=0 ";
  	}
  	//已封停用户
  	if($search['disabled_user']>0){
  		$where .= " AND state=0 ";
  	}
  	
  	//排序
  	$sort_where = "";
  	if($search['sort']!=''){
  		$sort = explode('_',$search['sort']);
  		if(count($sort)==2){
  			switch($sort[0]){
  				case 'coin':	$sort_field = 'coin ';break;
  				case 'score':	$sort_field = 'score';break;
  				default:		$sort_field = 'uid';
  			}
  			switch ($sort[1]){
  				case 'asc':	$sort_sequ = ' ASC ';break;
  				default :	$sort_sequ = ' DESC ';break;
  			}
  			$sort_where = ' '.$sort_field.$sort_sequ;
  		}
  	}
  	
  	$members = Member_Model::getMembersByWhere($where,$sort_where);  
  	
    $v = new PageView('mod_member_index');
    $v->assign('nav', $this->_nav)->assign('nav_second', 'member');
    $v->assign('members',$members)->assign('search', $search);
     
    $response->send($v);
  }
  public function member_edit(Request $request, Response $response){
  	$uid = arg(1);
  	$member = Member_Model::getMemberById($uid);
  	if(empty($member)){
  		$msg = '用户不存在';
  		$response->send($msg);
  	}
  	
  	$v = new PageView('mod_member_info');
  	$v->assign('nav', $this->_nav)->assign('nav_second', 'member');
  	$v->assign('member',$member);
  	 
  	$response->send($v);
  	
  }
  
  public function member_save(Request $request, Response $response){
  	$rs = ['flag'=>'FAIL','msg'=>''];
  	
  	$uid = isset($_POST['uid']) ? intval($_POST['uid']):0;
  	$password = isset($_POST['password']) ? $_POST['password']:'';
  	$mobile = isset($_POST['mobile']) ? $_POST['mobile']:'';
  	$email = isset($_POST['email']) ? $_POST['email']:'';
	
  	$member = [];
  	
  	if($password!=''){
  		$salt = create_randcode(6);
	  	$member['password'] = md5(md5($password).$salt);
	  	$member['salt'] = $salt;
  	}
  	
  	if($mobile!=''){
  		list($flag,$error) = Member::checkMobile($mobile);
  		if(!$flag){
  			$rs['msg'] = $error;
  			Response::sendJSON($rs);
  		}
  	}
  	$member['mobile'] = $mobile;
  	if($email!=''){
  		list($flag,$error) = Member::checkEmail($email);
  		if(!$flag){
  			$rs['msg'] = $error;
  			Response::sendJSON($rs);
  		}
  	}
  	$member['email'] = $email;
  	 
  	$affected = Member_Model::updateMemberById($uid, $member);
  	if($affected<1){
  		$rs['msg'] = '修改用户信息失败';
  		Response::sendJSON($rs);
  	}
  	
  	$rs['flag'] = 'SUC';
  	$rs['msg'] = '修改用户信息成功';
  	Response::sendJSON($rs);
  }
  
  public function unAmount(Request $request, Response $response){
  	$rs = ['flag'=>'FAIL','msg'=>''];
  	$uid = isset($_POST['uid']) ? intval($_POST['uid']):0;
  	$afftected = Member_Model::updateMemberById($uid, ['state'=>0]);
  	if($afftected<0){
  		$rs['msg'] = '封停用户账号失败';
  		Response::sendJSON($rs);
  	}
  	$rs['flag']='SUC';
  	$rs['msg'] = '封停用户账号成功';
  	Response::sendJSON($rs);
  } 

  public function amount(Request $request, Response $response){
  	$rs = ['flag'=>'FAIL','msg'=>''];
  	$uid = isset($_POST['uid']) ? intval($_POST['uid']):0;
  	$afftected = Member_Model::updateMemberById($uid, ['state'=>1]);
  	if($afftected<0){
  		$rs['msg'] = '解封用户账号失败';
  		Response::sendJSON($rs);
  	}
  	$rs['flag']='SUC';
  	$rs['msg'] = '解封用户账号成功';
  	Response::sendJSON($rs);
  }
  public function member_loginlog(Request $request, Response $response){
  	$search=array();
  	 
  	$search['stime']='';
  	$search['etime']='';
  	$search['username']='';
  	$search['uid']='';
  	$search['sort']='id_desc';//排序字段,example:coin_asc,coin_desc
  	 
  	//不更新查询条件
  	if(empty($_POST)){
  		if(isset($_SESSION['query']['loginlog'])){
  			$search = $_SESSION['query']['loginlog'];
  		}else{
  			$_SESSION['query']['loginlog'] = $search;
  		}
  	}else{
  		//更新查询条件
  		$search['stime']=empty($_POST['stime'])?'':addslashes(trim($_POST['stime']));
  		$search['etime']=empty($_POST['etime'])?'':addslashes(trim($_POST['etime']));
  		$search['username']=empty($_POST['username'])?'':addslashes(trim($_POST['username']));
  		$search['uid']=empty($_POST['uid'])?'':addslashes(trim($_POST['uid']));
  		$search['sort']=empty($_POST['sort'])? 'id_desc':addslashes(trim($_POST['sort']));
  		 
  		$_SESSION['query']['loginlog'] = $search;
  	}
  	 
  	$where = "";
  	if($search['username']!=''){
  		$where .=" and username='".$search['username']."'";
  	}
  	if($search['uid']!=''){
  		$where .=" and uid='".$search['uid']."'";
  	}
  	if($search['stime']!=''){
  		$where .=" and login_time>=".strtotime($search['stime']);
  	}
  	if($search['etime']!=''){
  		$where .=" and login_time<=".strtotime($search['etime']);
  	}
  	 
  	//排序
  	$sort_where = "";
  	if($search['sort']!=''){
  		$sort = explode('_',$search['sort']);
  		if(count($sort)==2){
  			switch($sort[0]){
  				case 'uid':	$sort_field = 'uid ';break;
  				case 'time':	$sort_field = 'login_time';break;
  				default:		$sort_field = 'log_id';
  			}
  			switch ($sort[1]){
  				case 'asc':	$sort_sequ = ' ASC ';break;
  				default :	$sort_sequ = ' DESC ';break;
  			}
  			$sort_where = ' '.$sort_field.$sort_sequ;
  		}
  	}
  	 
  	$log = Member_Model::getMemberLoginLog($where,$sort_where);
  	 
  	$v = new PageView('mod_member_loginlog');
  	$v->assign('nav', $this->_nav)->assign('nav_second', 'loginlog');
  	$v->assign('log',$log)->assign('search', $search);
  	 
  	$response->send($v);
  }
  
  public function player(Request $request, Response $response) {
  	$this->_nav_second = 'player';
  	$this->v->set_tplname('mod_member_player');
  	
  	$kw = $request->get('kw','');
  	$this->v->assign('kw', $kw);
  	
  	//BEGIN list order
  	$orderinfo = $this->v->set_listorder('player_id', 'desc');
  	$extraurl  = 'kw='.$kw.'&';
  	$extraurl .= $orderinfo[2];
  	$this->v->assign('extraurl', $extraurl);
  	$this->v->assign('qparturl', "#/member/player");
  	//END list order
  	
  	// Record List
  	$limit = 30;
  	$recordList = Member_Model::getPlayerList($orderinfo[0],$orderinfo[1],$limit,['kw'=>$kw]);
  	$recordNum  = count($recordList);
  	$totalNum   = $GLOBALS['pager_totalrecord_arr'][0];
  	
  	$this->v->assign('recordList', $recordList)
				  	->assign('recordNum', $recordNum)
				  	->assign('totalNum', $totalNum)
				  	->assign('mainsite', C('env.site.mobile'))
  	;
  	
  	$response->send($this->v);
  }
  
  /**
   * action 'player_edit'
   * @param Request $request
   * @param Response $response
   */
  public function player_edit(Request $request, Response $response)
  {
  	if ($request->is_post()) {
  
  		$ret = ['flag' => 'ERR', 'msg' => ''];
  		
  		$player_id   = $request->post('player_id', 0);
  		$truename    = $request->post('truename', '');
  		$mobile      = $request->post('mobile', '');
  		$weixin      = $request->post('weixin', '');
  		$idcard      = $request->post('idcard', '');
  		$province    = $request->post('province', 0);
  		$city        = $request->post('city', 0);
  		$inc_vote    = $request->post('inc_vote', 0);
  		$inc_flower  = $request->post('inc_flower', 0);
  		$cover_pic_id= $request->post('cover_pic_id', 0);
  		$imgs        = $request->post('imgs', []);
  		
  		$player_info = Member_Model::getPlayerInfo($player_id);
  		if (empty($player_info)) {
  			$ret['msg'] = '参赛者不存在';
  			$response->sendJSON($ret);
  		}

  		$uid = 10000; //10000 为系统管理员帐号
  		$ret['flag'] = 'SUC';
  		$ret['msg'] = '更新成功';
  		$data = ['cover_pic_id' => $cover_pic_id];
  		if (''!=$truename) {
  			$data['truename'] = $truename;
  		}
  		if (''!=$mobile) {
  			$data['mobile'] = $mobile;
  		}
  		if (''!=$weixin) {
  			$data['weixin'] = $weixin;
  		}
  		if (''!=$idcard && strlen($idcard)<=18) {
  			$data['idcard'] = $idcard;
  		}
  		
  		//将省份、城市平均成: "40:北京"这样的结构
  		if ($province) {
  			$loc = Member_Model::getLocationName($province);
  			if ($loc) {
  				$province = $province.':'.$loc;
  			}
  		}
  		else {
  			$province = '';
  		}
  		if ($city) {
  			$loc = Member_Model::getLocationName($city);
  			if ($loc) {
  				$city = $city.':'.$loc;
  			}
  		}
  		else {
  			$city = '';
  		}
  		$data['province'] = $province;
  		$data['city'] = $city;

  		//更新pic_cover_id
  		D()->update("player",$data,['player_id'=>$player_id]);
  		
  		//更新图片
  		if (!empty($imgs) && is_array($imgs)) { //! 务必检查严格
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
  		
  		if ($inc_vote) {
  			$action_id = Node::action('vote', $player_id, $uid, $inc_vote, TRUE, FALSE);
  			$ret['msg'].= '，增加了'.$inc_vote.'票';
  		}
  		if ($inc_flower) {
  			$action_id = Node::action('flower', $player_id, $uid, $inc_flower);
  			$ret['msg'].= '，增加了'.$inc_flower.'花';
  		}
  		$response->sendJSON($ret);
  		
  	}
  	else { // GET request
  
  		$this->_nav_second = 'player';
  		$this->v->set_tplname('mod_member_player_edit');
  		
  		// Player Info
  		$player_id = $request->arg(2);
  		$player_id = intval($player_id);
  		$is_edit = $player_id ? TRUE : FALSE;
  		$player_info = $is_edit ? Member_Model::getPlayerInfo($player_id) : [];
  		$player_gallery = [];
  		if (!empty($player_info)) {
  			$player_gallery = Member_Model::getPlayerGalleryAll($player_info['player_id'], $player_info['cover_pic_id']);
  			if (!empty($player_info['province'])) {
  				$player_info['province'] = preg_replace('/(:.*)$/', '', $player_info['province']);
  			}
  			if (!empty($player_info['city'])) {
  				$player_info['city'] = preg_replace('/(:.*)$/', '', $player_info['city']);
  			}
  		}
  		
  		$province = Member_Model::getProvinces();
  		$this->v->assign('province', $province);
  
  		$this->v->assign('player_info', $player_info)
  						->assign('player_gallery', $player_gallery)
  		        ->assign('is_edit', $is_edit);
  		$response->send($this->v);
  	}
  }
  
  /**
   * action 'player_suspend'
   * @param Request $request
   * @param Response $response
   */
  public function player_suspend(Request $request, Response $response)
  {
  	if ($request->is_post()) {
  		$ids = $request->post('rids');
  		$act = $request->post('act',0);
  
  		$ret = Member_Model::suspendPlayers($ids, $act);
  		$response->sendJSON(['flag'=>'OK', 'rids'=>$ret]);
  	}
  }
  
  /**
   * action 'player_delete'
   * @param Request $request
   * @param Response $response
   */
  public function player_delete(Request $request, Response $response)
  {
  	if ($request->is_post()) {
  		$ids = $request->post('rids');
  
  		$ret = Member_Model::deletePlayers($ids);
  		$response->sendJSON(['flag'=>'OK', 'rids'=>$ret]);
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
  	$cities = Member_Model::getCities($parent_id);
  	if (empty($cities)) {
  		$res['msg'] = 'parent_id invalid';
  		$response->sendJSON($res);
  	}
  	$res['flag'] = 'SUC';
  	$res['msg']  = '';
  	$res['data'] = $cities;
  	$response->sendJSON($res);
  }
  
}
 
/*----- END FILE: Member_Controller.php -----*/