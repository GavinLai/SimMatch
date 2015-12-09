<?php
/**
 * 购物流程控制器
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
defined('IN_SIMPHP') or die('Access Denied');

class Trade_Controller extends Controller {
  
  private $nav_no     = 0;       //主导航id
  private $topnav_no  = 0;       //顶部导航id
  private $nav_flag1  = 'cart';  //导航标识1
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
  public function init($action, Request $request, Response $response)
  {
    if (!$request->is_post()) {
      $this->v = new PageView();
      $this->v->add_render_filter(function(View $v){
        $v->assign('nav_no',     $this->nav_no)
          ->assign('topnav_no',  $this->topnav_no)
          ->assign('nav_flag1',  $this->nav_flag1)
          ->assign('nav_flag2',  $this->nav_flag2)
          ->assign('nav_flag3',  $this->nav_flag3)
          ->assign('extra_css',  $this->extra_css);
      });
      $this->v->assign('no_display_cart', true);
    }
  }
  
  /**
   * hook menu
   * @see Controller::menu()
   */
  public function menu()
  {
    return [
      'trade/cart/add'    => 'cart_add',
      'trade/cart/list'   => 'cart_list',
      'trade/cart/delete' => 'cart_delete',
      'trade/cart/chgnum' => 'cart_chgnum',
      'trade/order/confirm'  => 'order_confirm',
      'trade/order/submit'   => 'order_submit',
      'trade/order/payok'    => 'order_payok',
      'trade/order/upaddress'=> 'order_upaddress',
      'trade/order/cancel'   => 'order_cancel',
      'trade/order/confirm_shipping'   => 'order_confirm_shipping',
      'trade/order/record'   => 'order_record',
      'trade/order/topay'    => 'order_topay',
    ];
  }
  
  /**
   * default action 'index'
   *
   * @param Request $request
   * @param Response $response
   */
  public function index(Request $request, Response $response)
  {
    
  }
  
  /**
   * 添加购物车
   *
   * @param Request $request
   * @param Response $response
   */
  public function cart_add(Request $request, Response $response)
  {
    if ($request->is_post()) {
      
      $goods_id = $request->post('goods_id',0);
      $goods_num= $request->post('goods_num',1);
      
      $ec_user_id = $GLOBALS['user']->ec_user_id;
      if (!$ec_user_id) {
        $ec_user_id = session_id();
      }
      
      $ret = Goods::addToCart($goods_id, $goods_num, $ec_user_id);
      if ($ret['code']>0) {
        $ret['cart_num'] = Goods::getUserCartNum($ec_user_id);
      }
      $response->sendJSON($ret);
    }
  }
  
  /**
   * 删除购物车中的商品
   *
   * @param Request $request
   * @param Response $response
   */
  public function cart_delete(Request $request, Response $response)
  {
    if ($request->is_post()) {
      $ret = ['flag'=>'FAIL','msg'=>'删除失败'];
      $rec_ids = $request->post('rec_id',[]);
      
      if(empty($rec_ids)) {
        $ret['msg'] = '没有要删的记录';
        $response->sendJSON($ret);
      }
      
      $user_id = $GLOBALS['user']->ec_user_id;
      if (!$user_id) {
        $ret['msg'] = '请先登录';
        $response->sendJSON($ret);
      }
      
      $ret = Goods::deleteCartGoods($rec_ids, $user_id);
      if ($ret['code']>0) {
        $ret['flag'] = 'SUC';
        $ret['rec_ids'] = $rec_ids;
      }
      $response->sendJSON($ret);
    }
  }
  
  /**
   * 改变购物车中的商品选购数量
   *
   * @param Request $request
   * @param Response $response
   */
  public function cart_chgnum(Request $request, Response $response)
  {
    if ($request->is_post()) {
      $ret = ['flag'=>'FAIL','msg'=>'更改失败'];
      $rec_ids = $request->post('rec_id',[]);
      $gnums   = $request->post('gnum',[]);
      
      if(empty($rec_ids)) {
        $ret['msg'] = '没有要更改的记录';
        $response->sendJSON($ret);
      }
      
      $user_id = $GLOBALS['user']->ec_user_id;
      if (!$user_id) {
        $ret['msg'] = '请先登录';
        $response->sendJSON($ret);
      }
      
      $i = 0;
      $succ_rids = [];
      foreach ($rec_ids AS $rid) {
        if (Goods::changeCartGoodsNum($user_id, $rid, $gnums[$i], true, true)) {
          $succ_rids[] = $rid;
        }
        ++$i;
      }
      
      
      if (count($succ_rids)>0) {
        $ret['flag'] = 'SUC';
        $ret['succ_rids'] = $succ_rids;
      }
      $response->sendJSON($ret);
    }
  }
  
  /**
   * 添加购物车
   *
   * @param Request $request
   * @param Response $response
   */
  public function cart_list(Request $request, Response $response)
  {
    $this->v->set_tplname('mod_trade_cart_list');
    $this->nav_flag2 = 'cartlist';
    $this->topnav_no = 1; // >0: 表示有topnav bar，具体值标识哪个topnav bar(有多个的情况下)
    if ($request->is_hashreq()) {
      $user_id  = $GLOBALS['user']->ec_user_id;
      if (!$user_id) $user_id = session_id(); 
      $cartGoods = Goods::getUserCart($user_id);
      $cartNum   = Goods::getUserCartNum($user_id);
      $this->v->assign('cartGoods', $cartGoods);
      $this->v->assign('cartNum', intval($cartNum));
      $this->v->assign('cartRecNum', count($cartGoods));
    }
    else {
      $backurl = U('explore');
      $this->v->assign('backurl', $backurl);
    }
    $response->send($this->v);
  }
  
  /**
   * 购买记录
   *
   * @param Request $request
   * @param Response $response
   */
  public function order_record(Request $request, Response $response)
  {
    $this->v->set_tplname('mod_trade_order_record');
    $this->nav_flag2 = 'buyrecord';
    $this->nav_no    = 0;
    $this->topnav_no = 1; // >0: 表示有topnav bar，具体值标识哪个topnav bar(有多个的情况下)
    if ($request->is_hashreq()) {
      
      $orders_num = 0;
      $errmsg = '';
      $this->v->add_render_filter(function(View $v) use(&$orders_num, &$errmsg){
        $v->assign('errmsg', $errmsg)
          ->assign('orders_num', $orders_num);
      });
      
      $ec_user_id = $GLOBALS['user']->ec_user_id;
      if (!$ec_user_id) {
        $errmsg = "无效请求";
        $response->send($this->v);
      }
      
      $orders = Goods::getOrderList($ec_user_id);
      $orders_num = count($orders);
      $this->v->assign('orders', $orders);
      
    }
    else {
      $refer = $request->refer();
      $backurl = U('explore');
      if (strpos($refer, '/user')!==false) { //来自用户中心
        $backurl = U('user');
      }
      $this->v->assign('backurl', $backurl);
    }
    $response->send($this->v);
  }
  
  /**
   * 订单确认
   *
   * @param Request $request
   * @param Response $response
   */
  public function order_confirm(Request $request, Response $response)
  {
    $this->v->set_tplname('mod_trade_order_confirm');
    $this->v->set_page_render_mode(View::RENDER_MODE_GENERAL); //改变默认的hashreq请求模式，改为普通页面请求(一次过render页面所有内容)
    $this->extra_css = 'fixmaxheight';
    $this->nav_flag1 = 'order';
    $this->nav_flag2 = 'order_confirm';
    $this->nav_no    = 0;
    if (1||$request->is_hashreq()) {
      $goods_type = $request->get('goods','flower');
      $player_id  = $request->get('player_id', 0);
      
      $errmsg = '';
      $this->v->add_render_filter(function(View $v) use(&$errmsg){
        $v->assign('errmsg', $errmsg);
      });
      
      //检查player是否存在
      import('match/Match_Model');
      $player_info = Match_Model::getPlayerInfo($player_id, false);
      if (empty($player_info)) {
        $errmsg = "参赛者不存在(player_id={$player_id})";
        $response->send($this->v);
      }
      $this->v->assign('player_id', $player_id);
      $this->v->assign('player_info', $player_info);
      
      //商品类型
      if (!in_array($goods_type, ['flower','kiss'])) {
        $goods_type = 'flower';
      }
      $this->v->assign('goods_type', $goods_type);
      
      //起送数量
      $amount_start = 1;
      if ($goods_type=='kiss') {
        $amount_start = 2;
      }
      $this->v->assign('amount_start', $amount_start);
      
      $animate_num = config_get('flower_animate_num');
      $this->v->assign('animate_num', $animate_num);
      
      //$_SESSION['animatenum_'.$player_id] = 100;
    }
    else {
      /*
      $code = $request->get('code', '');
      if (''!=$code) { //微信base授权
        
        $state = $request->get('state', '');
        
        //授权出错
        if (!in_array($state, array('base','detail'))) {
          Fn::show_error_message('授权出错，提交订单失败！', true);
        }
        
        $wx = new Weixin([Weixin::PLUGIN_JSADDR]);
        
        //用code换取access token
        $code_ret = $wx->request_access_token($code);
        if (!empty($code_ret['errcode'])) {
          Fn::show_error_message('微信授权错误<br/>'.$code_ret['errcode'].'('.$code_ret['errmsg'].')', true);
        }
        
        $accessToken = $code_ret['access_token'];
        $wxAddrJs = $wx->jsaddr->js($accessToken);
        $this->v->add_append_filter(function(PageView $v) use($wxAddrJs) {
          $v->append_to_foot_js .= $wxAddrJs;
        },'foot');
        
      }
      else { //正常访问
        if (Weixin::isWeixinBrowser()) {
          (new Weixin())->authorizing($request->url(), 'base'); //base授权获取access token以便于操作收货地址
        }
      }
      */
    }
    $response->send($this->v);
  }
  
  /**
   * 订单确认
   *
   * @param Request $request
   * @param Response $response
   */
  public function order_submit(Request $request, Response $response)
  {
    if ($request->is_post()) {
      
      $ret = ['flag'=>'FAIL','msg'=>'订单提交失败'];
      
      $uid = $GLOBALS['user']->uid;
      if (!$uid) {
        $ret['msg'] = '未登录, 请先在微信登录';
        $response->sendJSON($ret);
      }
      
      $player_id     = $request->post('player_id', 0);
      $goods_type    = $request->post('goods_type', 'flower');
      $goods_amount  = $request->post('amount', 0);
      $pay_id        = 2; //2是微信支付，见ec payment表
      
      // 检查数据
      // 参赛者
      if (!Node::playerExisted($player_id)) {
      	$ret['msg'] = "参赛者不存在(player_id={$player_id})";
      	$response->sendJSON($ret);
      }
      
      // 数量
      $goods_amount = intval($goods_amount);
      if (!$goods_amount) {
        $ret['msg'] = 'flower'==$goods_type ? '送花数量不能为空' : '送吻数量不能为空';
        $response->sendJSON($ret);
      }
      $order_amount = $goods_amount; 
      
      // 支付信息
      $pay_info = Goods::getPaymentInfo($pay_id);
      if (empty($pay_info)) {
        $ret['msg'] = '该支付方式暂不可用，请重新选择';
        $response->sendJSON($ret);
      }
      
      $order_sn = Fn::gen_order_no();
      
      $order = [
        'order_sn'         => $order_sn,
        'user_id'          => $uid,
        'order_status'     => OS_UNCONFIRMED,
        'pay_status'       => PS_UNPAYED,
        'pay_id'           => $pay_info['pay_id'],
        'pay_name'         => $pay_info['pay_name'],
        'player_id'        => $player_id,
        //...
        'goods_type'       => $goods_type,
        'goods_amount'     => $goods_amount,
        'order_amount'     => $order_amount,
        'money_paid'       => 0,
        //...
        'add_time'         => simphp_time(),
        //...
      ];
      
      $order_id = D()->insert("order_info", $order);
      if ($order_id) { //订单表生成成功
        
      	$order['order_id'] = $order_id;
      	$true_amount = $order_amount;
      	
        // 处理表 pay_log
        Trade_Model::insertPayLog($order_id, $order_sn, $true_amount, PAY_ORDER);
        
        $jsApiParams = '';
        if (2==$pay_info['pay_id']) { //微信支付
        	$jsApiParams = Wxpay::unifiedOrder($order, $GLOBALS['user']->openid);
        }
        
        $ret = ['flag'=>'SUC','msg'=>'订单提交成功','order_id'=>$order_id,'order_sn'=>$order_sn,'js_api_params'=>json_decode($jsApiParams)];
        $response->sendJSON($ret);
      }
      else {
        $ret['msg'] = '订单生成失败，请返回购物车重新添加';
        $response->sendJSON($ret);
      }
      
    }
    else {
      $this->v->set_tplname('mod_trade_order_submit');
      $this->nav_flag1 = 'order';
      $this->nav_flag2 = 'order_submit';
      $this->nav_no    = 0;
      if ($request->is_hashreq()) {
      
      }
      else {
      
      }
      $response->send($this->v);
      
    }
    
  }

  /**
   * 支付成功后的逻辑处理
   *
   * @param Request $request
   * @param Response $response
   */
  public function order_payok(Request $request, Response $response) {
  	if ($request->is_post()) {
  		$player_id = $request->post('player_id', 0);
  		$amount    = $request->post('amount', 0);
  		$effectnum = config_get('flower_animate_num');
  		if (!empty($player_id) && $amount >= $effectnum) {
  			$_SESSION['animatenum_'.$player_id] = $amount;
  		}
  	}
  }
  
  /**
   * 更新收货地址
   *
   * @param Request $request
   * @param Response $response
   */
  public function order_upaddress(Request $request, Response $response)
  {
    if ($request->is_post()) {
      $ret = ['flag'=>'FAIL','msg'=>'更新失败'];
      
      $ec_user_id = $GLOBALS['user']->ec_user_id;
      if (!$ec_user_id) {
        $ret['msg'] = '未登录, 请登录';
        $response->sendJSON($ret);
      }
      
      $address_id    = $request->post('address_id', 0);
      $consignee     = $request->post('consignee', '');
      $contact_phone = $request->post('contact_phone', '');
      $country       = $request->post('country', 1);
      $country_name  = $request->post('country_name', '中国');
      $province      = $request->post('province', 0);
      $province_name = $request->post('province_name', '');
      $city          = $request->post('city', 0);
      $city_name     = $request->post('city_name', '');
      $district      = $request->post('district', 0);
      $district_name = $request->post('district_name', '');
      $address       = $request->post('address', '');
      $zipcode       = $request->post('zipcode', '');
      
      $address_id = intval($address_id);
      $data = [
        'user_id'       => $ec_user_id,
        'consignee'     => $consignee,
        'country'       => $country,
        'country_name'  => $country_name,
        'province'      => $province,
        'province_name' => $province_name,
        'city'          => $city,
        'city_name'     => $city_name,
        'district'      => $district,
        'district_name' => $district_name,
        'address'       => $address,
        'zipcode'       => $zipcode,
      ];
      /*
      if (preg_match('/^1\d{10}$/', $contact_phone)) { //是手机号
        $data['mobile'] = $contact_phone;
      }
      else {
        $data['tel'] = $contact_phone;
      }
      */
      $data['tel'] = $contact_phone; //遵循ecshop习惯，优先使用tel(因为后台都是优先选择tel,mobile作为第二电话)
      
      $address_id = Goods::saveUserAddress($data, $address_id);
      $ret = ['flag'=>'SUC','msg'=>'更新成功','address_id'=>$address_id];
      
      $response->sendJSON($ret);
    }
  }

  /**
   * 取消订单
   *
   * @param Request $request
   * @param Response $response
   */
  public function order_cancel(Request $request, Response $response)
  {
    if ($request->is_post()) {
      $ret = ['flag'=>'FAIL','msg'=>'取消失败'];
      
      $ec_user_id = $GLOBALS['user']->ec_user_id;
      if (!$ec_user_id) {
        $ret['msg'] = '未登录, 请登录';
        $response->sendJSON($ret);
      }
      
      $order_id = $request->post('order_id', 0);
      if (!$order_id) {
        $ret['msg'] = '订单id为空';
        $response->sendJSON($ret);
      }
      
      $b = Order::cancel($order_id);
      if ($b) {
        $ret = ['flag'=>'SUC','msg'=>'取消成功', 'order_id'=>$order_id];
      }
      
      $response->sendJSON($ret);
    }
  }

  /**
   * 取消订单
   *
   * @param Request $request
   * @param Response $response
   */
  public function order_confirm_shipping(Request $request, Response $response)
  {
    if ($request->is_post()) {
      $ret = ['flag'=>'FAIL','msg'=>'取消失败'];
      
      $ec_user_id = $GLOBALS['user']->ec_user_id;
      if (!$ec_user_id) {
        $ret['msg'] = '未登录, 请登录';
        $response->sendJSON($ret);
      }
      
      $order_id = $request->post('order_id', 0);
      if (!$order_id) {
        $ret['msg'] = '订单id为空';
        $response->sendJSON($ret);
      }
      
      $b = Order::confirm_shipping($order_id);
      if ($b) {
        $ret = ['flag'=>'SUC','msg'=>'确认成功', 'order_id'=>$order_id];
      }
      
      $response->sendJSON($ret);
    }
  }
  
  /**
   * tips页显示
   * @param Request $request
   * @param Response $response
   */
  public function order_topay(Request $request, Response $response){
    
    
    if ($request->is_post()) {
      
      global $user;
      if (!$user->uid) {
        Fn::show_error_message('未登录，请先登录');
      }
      
      $this->v = new PageView('','topay');
      
      $pay_mode = $request->post('pay_mode', 'wxpay'); //默认微信支付
      $order_id = $request->post('order_id', 0);
      $back_url = $request->post('back_url', '');
      
      $supported_paymode = [
        'wxpay'  => '微信安全支付',
        'alipay' => '支付宝支付',
      ];
      
      if (!in_array($pay_mode, array_keys($supported_paymode))) {
        Fn::show_error_message('不支持该支付方式: '.$pay_mode);
      }
      if (!$order_id) {
        Fn::show_error_message('订单为空');
      }
      
      $order_info = Order::info($order_id);
      if (empty($order_info)) {
        Fn::show_error_message('订单不存在');
      }
      else {
        $order_info['order_goods'] = Goods::getOrderGoods($order_info['order_id']);
        if (empty($order_info['order_goods'])) {
          Fn::show_error_message('订单下没有对应商品');
        }
      }
      
      if ('wxpay'==$pay_mode) {
        $jsApiParams = Wxpay::unifiedOrder($order_info, $user->openid);
        $this->v->assign('jsApiParams', $jsApiParams);
      }
      
      $this->v->assign('pay_mode', $pay_mode);
      $this->v->assign('supported_paymode', $supported_paymode);
      
      $this->v->assign('back_url', $back_url);
      
      $response->send($this->v);
      
    }
    else {
      Fn::show_error_message('非法访问');
    }
    
  }
  
}
 
/*----- END FILE: Trade_Controller.php -----*/