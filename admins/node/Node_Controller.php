<?php
/**
 * Admin Node控制器
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
defined('IN_SIMPHP') or die('Access Denied');

class Node_Controller extends Controller {

  private $_nav = 'sc';
  
  /**
   * hook menu
   *
   * @return array
   */
  public function menu() 
  {
    return [
      'node/match'     => 'index',
      'node/add/%s'    => 'add',
      'node/%d/edit'   => 'add',
      'node/%d'        => 'detail',
      'node/%d/delete' => 'delete',
      'node/%d/orderEdit' => 'orderEdit',
      'node/%d/recommend' => 'recommend',
    ];
  }
  
  /**
   * hook init
   * @param string $action
   * @param Request $request
   * @param Response $response
   */
  public function init($action, Request $request, Response $response)
  {
    $this->v = new PageView();
    $this->v->assign('nav', $this->_nav);
  }
  
  /**
   * default action 'index'
   * @param Request $request
   * @param Response $response
   */
  public function index(Request $request, Response $response)
  {
    //查询条件
    $_query_node = ['keyword'=>'','status'=>''];
    if(empty($_POST)&&isset($_SESSION['_query_node'])){
      $_query_node = $_SESSION['_query_node'];
    }else{
      $keyword = $request->post('keyword','');
      $status = $request->post('status', '');

      $_query_node['keyword'] = $keyword;
      $_query_node['status'] = $status;
      $_SESSION['_query_node'] = $_query_node;
    }

    // Set submenu
    $node_type = $request->arg(1);
    $allowed_ntypes = C('env.allowed_nodetypes');
    if (!in_array($node_type,$allowed_ntypes)) {
      $node_type = 'match';
    }
    $this->v->assign('nav_second', $node_type);
    
    //BEGIN list order
    $orderinfo = $this->v->set_listorder('nid', 'desc');
    $extraurl  = '';
    $extraurl .= $orderinfo[2];
    $this->v->assign('extraurl', $extraurl);
    $this->v->assign('qparturl', "#/node".(''!=$node_type ? '/'.$node_type : ''));
    //END list order
    
    // Game List
    $limit = 20;
    $recordList = Node::getList($node_type,$orderinfo[0],$orderinfo[1],$limit ,$_query_node);
    $recordNum  = count($recordList);
    $totalNum   = $GLOBALS['pager_totalrecord_arr'][0];
    
    $this->v->set_tplname('mod_node_index');
    $this->v->assign('recordList', $recordList)
            ->assign('recordNum', $recordNum)
            ->assign('totalNum', $totalNum)
            ->assign('_query_node', $_query_node)
            ->assign('mainsite', C('env.site.mobile'))
            ;
    $response->send($this->v);
  }
  
  /**
   * action 'add'
   * @param Request $request
   * @param Response $response
   */
  public function add(Request $request, Response $response)
  {
    if ($request->is_post()) {
      
      $nid          = $request->post('nid', 0);
      $ntype        = $request->post('ntype');
      $title        = $request->post('title','');
      $thumb_url    = $request->post('thumb_url','');
      $match_type   = $request->post('match_type','');
      $start_date   = $request->post('start_date','');
      $end_date     = $request->post('end_date','');
      $keyword      = $request->post('keyword','');
      $slogan       = $request->post('slogan','');
      $content      = $request->post('content','');
      $content_dt   = $request->post('content_detail','');
      $status       = $request->post('status','R');
      
      $ret = ['flag' => 'ERR', 'msg' => ''];
      
      if ('match'==$ntype) {
        if (''==$title) {
          $ret['msg'] = '标题不能为空';
          $response->sendJSON($ret);
        }
        elseif (''==$thumb_url) {
          $ret['msg'] = '主图片不能为空';
          $response->sendJSON($ret);
        }
        $match_types = Node::getMatchTypes();
        if (!in_array($match_type, array_keys($match_types))) {
          $match_type = 'bs';
        }
      }
      
      $status_set = Node::getStatus();
      if (!in_array($status, array_keys($status_set))) {
        $status = 'R';
      }
      
      $ninfo = [];
      if ($nid) {
        $ninfo = Node::getInfo($nid);
        if (empty($ninfo)) {
          $ret['msg'] = "Node(nid={$nid})不存在";
          $response->sendJSON($ret);
        }
      }
      
      $now = simphp_time();
      $uid = $_SESSION['logined_uid'];
      $params = [
        'ntype'        => $ntype,
        'title'        => $title,
        'content'      => $content,
        'keyword'      => $keyword,
        'createdby'    => $uid,
        'created'      => $now,
        'changedby'    => $uid,
        'changed'      => $now,
        'status'       => $status
      ];
      
      $allowed_ntypes = C('env.allowed_nodetypes');
      if (empty($ninfo)) { // new insert

        $ninfo['nid'] = D()->insert('node', $params);
        
        if ($ninfo['nid'] && in_array($ntype,$allowed_ntypes) && 'base'!=$ntype) {
          $params_ext = ['enid' => $ninfo['nid']];
          switch ($ntype) {
            case 'match':
              $params_ext['match_type'] = $match_type;
              $params_ext['thumb_url']  = $thumb_url;
              $params_ext['slogan']     = $slogan;
              $params_ext['start_date'] = $start_date;
              $params_ext['end_date']   = $end_date;
              $params_ext['content_detail'] = $content_dt;
              break;
          }
          D()->insert('node_'.$ntype, $params_ext);
        }
        
        $ret['flag'] = 'OK';
        $ret['msg'] = '添加成功！';
        $response->sendJSON($ret);
      }
      else { // edit

        unset($params['createdby'], $params['created']);
        D()->update('node', $params, ['nid'=>$nid]);
        
        if (D()->affected_rows() && in_array($ntype,$allowed_ntypes) && 'base'!=$ntype) {
          $params_ext = [];
          switch ($ntype) {
            case 'match':
              $params_ext['match_type'] = $match_type;
              $params_ext['thumb_url']  = $thumb_url;
              $params_ext['slogan']     = $slogan;
              $params_ext['start_date'] = $start_date;
              $params_ext['end_date']   = $end_date;
              $params_ext['content_detail'] = $content_dt;
              break;
          }
          D()->update('node_'.$ntype, $params_ext, ['enid'=>$nid]);
        }
        
        $ret['flag'] = 'OK';
        $ret['msg'] = '编辑成功！';
        $response->sendJSON($ret);
      }
    }
    else { // GET request
      
      // Node Info
      $nid = $request->arg(1);
      $nid = intval($nid);
      $is_edit = $nid ? TRUE : FALSE;
      $ninfo = $is_edit ? Node::getInfo($nid) : [];
      
      // Node Type
      $node_type = '';
      if (!$is_edit) {
        $node_type = $request->arg(2);
        $allowed_ntypes = C('env.allowed_nodetypes');
        if (!in_array($node_type, $allowed_ntypes)) {
          $node_type = 'base';
        }
      }
      else {
        $node_type = $ninfo['ntype'];
      }
      $this->v->assign('node_type', $node_type);
      $this->v->assign('nav_second', $node_type);
      
      // Node status
      $status_set = Node::getStatus();
      $this->v->assign('status_set', $status_set);
      
      // Extra node info
      switch ($node_type) {
        case 'match':
          $match_types = Node::getMatchTypes();
          $this->v->assign('match_types', $match_types);
          break;
      }

      $this->v->set_tplname('mod_node_add');
      $this->v->assign('ninfo', $ninfo)
              ->assign('is_edit', $is_edit);
      $response->send($this->v);
    }
  }
  
  /**
   * action 'delete'
   * @param Request $request
   * @param Response $response
   */
  public function delete(Request $request, Response $response)
  {
    if ($request->is_post()) {
      $ids = $request->post('rids');
      
      $ret = Node_Model::deleteNodes($ids);
      $response->sendJSON(['flag'=>'OK', 'rids'=>$ret]);
    }
  }
  
  /**
   * action 'recommend'
   * @param Request $request
   * @param Response $response
   */
  public function recommend(Request $request, Response $response)
  {
    if ($request->is_post()) {
      $ids = $request->post('rids');
      $act = $request->post('act',0);
      
      $ret = Node_Model::recommendNodes($ids, $act);
      $response->sendJSON(['flag'=>'OK', 'rids'=>$ret]);
    }
  }
  
  /**
   * action 'suspend'
   * @param Request $request
   * @param Response $response
   */
  public function suspend(Request $request, Response $response)
  {
    if ($request->is_post()) {
      $ids = $request->post('rids');
      $act = $request->post('act',0);
      
      $ret = Node_Model::suspendNodes($ids, $act);
      $response->sendJSON(['flag'=>'OK', 'rids'=>$ret]);
    }
  }

  public function order (Request $request, Response $response){

    // Set submenu
    $this->v->assign('nav_second', 'order');
    
    //BEGIN list order
    $orderinfo = $this->v->set_listorder('oid', 'desc');
    $extraurl  = '';
    $extraurl .= $orderinfo[2];
    $this->v->assign('extraurl', $extraurl);
    $this->v->assign('qparturl', "#/node/order");
    //END list order
      
    //条件查询
    //$_SESSION['_query_order'] = [];
    $_query_order = [
      'order_no'  => '',
      'nickname'  => '',
      'state'     => ''
    ];
    if(empty($_POST)&&isset($_SESSION['_query_order'])){
        $_query_order = $_SESSION['_query_order'];
    }else{
      if(isset($_POST['order_no'])){
        $_query_order['order_no'] = $_POST['order_no'];
      } 
      if(isset($_POST['nickname'])){
        $_query_order['nickname'] = $_POST['nickname'];
      }
      if(isset($_POST['state'])){
        $_query_order['state'] = $_POST['state'];
      }

      $_SESSION['_query_order'] = $_query_order;
    }

    $where = ''; 
    if($_query_order['order_no']!==''){
      $where['order_no'] = $_POST['order_no'];
    } 
    if($_query_order['nickname']!==''){
      $where['nickname'] = $_POST['nickname'];
    }
    if($_query_order['state']!==''){
      $where['state'] = $_POST['state'];
    }

    // order List
    $limit = 20;
    $recordList = Node_Model::getOrderList($where, $orderinfo[0],$orderinfo[1],$limit);
    foreach($recordList AS &$r){
      $r['state_str'] = getOrderState($r['state']);
      if(is_null($r['send_state'])){
        $r['send_state_str'] = '';
      }else{
        $r['send_state_str'] = getSendState($r['send_state']);
      }
    }
    $recordNum  = count($recordList);
    $totalNum   = $GLOBALS['pager_totalrecord_arr'][0];
    $orderState = getOrderState();

    $this->v->set_tplname('mod_node_order');
    $this->v->assign('recordList', $recordList)
            ->assign('recordNum', $recordNum)
            ->assign('totalNum', $totalNum)
            ->assign('orderState', $orderState)
            ->assign('_query_order', $_query_order);
    $response->send($this->v);
  }
  /**
   * 更新订单
   * @param  Request  $request  [description]
   * @param  Response $response [description]
   * @return [type]             [description]
   */
  public function orderEdit(Request $request, Response $response){
    // Set submenu
    $this->v->assign('nav_second', 'order');

    $oid = $request->arg(1);

    $order =  Node_Model::getOrderById($oid);
    $orderSend = Node_Model::getSendState($order['order_no']);

    $order_status = getOrderState();
    $sendState = getSendState();
    $sendType = get_send_type();

    $this->v->assign('oid', $oid)->assign('order',$order);
    $this->v->assign('status', $order_status)->assign('sendState', $sendState);
    $this->v->assign('orderSend', $orderSend)->assign('sendType', $sendType);
    $this->v->set_tplname('mod_node_orderEdit');
    $response->send($this->v);
  }
  /**
   * 更新订单的状态
   * @param  Request  $request  [description]
   * @param  Response $response [description]
   * @return [type]             [description]
   */
  public function updateOrder(Request $request, Response $response){
    $rs = ['flag'=>'FAIL','msg'=>'请稍后再试'];
    $order_no = $request->post('order_no','');
    $sendState = $request->post('sendState','');
    $sendType = $request->post('sendType','');
    $send_no = $request->post('send_no','');

    //查询订单状态
    $order = Node_Model::getOrderByOrderno($order_no);
    if($order['state']>0){//已支付，可更新发货信息
      //查询发货信息
      $orderSend = Node_Model::getSendState($order_no);
      $data = [
        'order_no'=>$order_no,
        'send_type'=>$sendType,
        'send_no'=> $send_no,
        'send_time'=> time(),
        'send_state'=> $sendState
      ];
      if($orderSend){
        unset($data['send_no']);
        if(Node_Model::updateOrderSend($order_no, $data)){
          $rs['flag'] = 'SUC';
          $rs['msg'] = '更新订单成功';
        }
      }else{
        if(Node_Model::addOrderSend($data)){
          $rs['flag'] = 'SUC';
          $rs['msg'] = '更新订单成功';
        }
      }
    }
    $response->sendJSON($rs);
  }
  /**
   * 类别管理
   * @param  Request  $request  [description]
   * @param  Response $response [description]
   * @return [type]             [description]
   */
  public function cate(Request $request, Response $response){
    // Set submenu
    $this->v->assign('nav_second', 'cate');    


    $category = Node_Model::getCategoryList();
    $categoryRTag = [];
    foreach($category as $c){
      $recordes = Node_Model::getCateRTags($c['cate_id']);
      $categoryRTag[$c['cate_id']] = $recordes;
    }


    $this->v->assign('category', $category)->assign('categoryRTag', $categoryRTag);
    $this->v->set_tplname('mod_node_cate');
    $response->send($this->v);
  }

  public function addTag(Request $request, Response $response){
    $rs = ['flag'=>'FAIL','msg'=>'请稍后再试'];
    $cate_id = $request->post('cate_id', 0);
    $tag_name = $request->post('tag_name', '');

    $tags = Node_Model::getTagByName($tag_name);
    if(empty($tags)){
      $tag_id = Node_Model::addTag(['tag_name'=>$tag_name]);
      if(!$tag_id){
        $response->sendJSON($rs);
      }
    }else{
      $tag_id = $tags['tag_id'];
    }

    $affected_rows = Node_Model::cateRTag(['cate_id'=>$cate_id, 'tag_id'=>$tag_id]);
    if($affected_rows>0){
      $rs['flag'] = 'SUC';
      $rs['msg'] = '添加成功！';
    }
    $response->sendJSON($rs);
  }

  public function delTag(Request $request, Response $response){
    $rs = ['flag'=>'FAIL','msg'=>'请稍后再试'];
    $cate_id = $request->post('cate_id', 0);
    $tag_id = $request->post('tag_id', 0);

    if(Node_Model::delTag(['cate_id'=>$cate_id,'tag_id'=>$tag_id])>0){
      $rs['flag']='SUC';
      $rs['msg']='删除成功！';
    }

    $response->sendJSON($rs);
  }
  
  public function updateTag(Request $request, Response $response){
    $rs = ['flag'=>'FAIL','msg'=>'请稍后再试'];
    $rank = intval($request->post('rank', 0));
    $tag_id = intval($request->post('tag_id', 0));
    $cate_id = intval($request->post('cate_id', 0));

    if(Node_Model::updateTagCate(['rank'=>$rank,'tag_id'=>$tag_id,'cate_id'=>$cate_id])>0){
      $rs['flag']='SUC';
      $rs['msg']='更新成功！';
    }

    $response->sendJSON($rs);
  }


  /**
   * action 'import'
   * @param Request $request
   * @param Response $response
   */
  public function import(Request $request, Response $response)
  {
    if ($request->is_post()) {
      $source_id  = $request->post('source_id');
      $source_url = $request->post('source_url');
      
      $ret = ['flag' => 'ERR', 'msg' => ''];
      
      if (!$source_id || !in_array($source_id,Node_Model::getSourceList('music',true))) {
        $ret['msg'] = '请选择有效的来源';
        $response->sendJSON($ret);
      }
      
      if (!$source_url || !preg_match('!^http://.{4,}!i', $source_url)) {
        $ret['msg'] = '请输入有效的URL地址';
        $response->sendJSON($ret);
      }

      Node_Model::importMusic($source_id,$source_url);
      
      $ret = ['flag' => 'OK', 'msg' => '导入成功！'];
      $response->sendJSON($ret);
    }
    else {
  
      // Node Info
      $import_ntype = $request->arg(2);

      $this->v->assign('nav_second', $import_ntype);
  
      // Music Source List
      $sourceList = Node_Model::getSourceList('music');
  
      $this->v->set_tplname('mod_node_import');
      $this->v->assign('sourceList', $sourceList);
      $response->send($this->v);
    }
  }
  public function updateSearch(Request $request, Response $response){
      $nodes = D()->query('SELECT * FROM {node} ')->fetch_array_all();

      $typeData = Node_Model::getTypeList();
      $typeList = [];
      foreach($typeData as $val){
        $typeList[$val['type_id']] = $val['type_name'];
      }

      foreach($nodes as $params){
        $search = $typeList[$params['type_id']];
        $search .= ','.$params['keyword'].','.$params['tag'];

        D()->update('node',['search'=>$search], ['nid'=>$params['nid']]);
      }

      header('Content-type:text/html;charset=utf-8');
      exit('更新搜索字段完成');
  }
  
}
 
/*----- END FILE: Node_Controller.php -----*/
