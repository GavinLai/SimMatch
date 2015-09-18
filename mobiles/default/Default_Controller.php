<?php
/**
 * 默认(一般首页)模块控制器，此控制器必须
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
defined('IN_SIMPHP') or die('Access Denied');

class Default_Controller extends Controller {

  private $nav_no     = 0;       //主导航id
  private $topnav_no  = 0;       //顶部导航id
  private $nav_flag1  = 'home';  //导航标识1
  private $nav_flag2  = '';      //导航标识2
  private $nav_flag3  = '';      //导航标识3
  
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
        ->assign('nav_flag3',  $this->nav_flag3);
    });
  }
  
  /**
   * hook menu
   * @see Controller::menu()
   */
  function menu()
  {
    return [
      
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
    $this->v->set_tplname('mod_default_index');
    if ($request->is_hashreq()) {
      
    }
    else{
      
    }
    $response->send($this->v);
  }
  
  function about(Request $request, Response $response)
  {
    $this->v->set_tplname('mod_default_about');
    $this->nav_flag1 = 'about';
    
    if ($request->is_hashreq()) {
      
    }
    else {
      
    }
    $response->send($this->v);
  }
  
}
 
/*----- END FILE: Default_Controller.php -----*/