<?php defined('IN_SIMPHP') or die('Access Denied');?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<title><?php echo $seo['title']?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="keywords" content="<?php echo $seo['keyword']?>">
<meta name="description" content="<?php echo $seo['desc']?>">
<meta name="author" content="Fuxiaomi Technical Team">
<meta name="apple-mobile-web-app-title" content="<?php echo L('appname')?>">
<meta name="apple-mobile-web-app-capable" content="yes">
<!-- <meta name="apple-mobile-web-app-status-bar-style" content="black"> -->
<meta name="format-detection" content="telephone=no">
<link rel="dns-prefetch" href="res.wx.qq.com" />
<?php if (C('env.usecdn')):?>
<link rel="dns-prefetch" href="fcdn.fxmapp.com" />
<link rel="dns-prefetch" href="fimg.fxmapp.com" />
<?php endif;?>
<link rel="shortcut icon" href="<?=$contextpath;?>favicon.ico" type="image/x-icon" />
<?php tplholder('HEAD_CSS');?>
<?php tplholder('HEAD_JS');?>
<?php headscript();?>
<?php shareinfo(isset($share_info) ? $share_info : array());?>
</head>
<body>
<div id="rtWrap">
  <?php include T($tpl_header);?>
  <div id="activePage" class="useTopNav-<?=$topnav_no?> useNav-<?=$nav_no?>">
    <section class="scrollArea<?php if(isset($extra_css)&&!empty($extra_css)) echo ' '.$extra_css?>"><?php if(1===$page_render_mode):?><?php include T($tpl_content);?><script>$(function(){F.set_scroller(false,100);});</script><?php endif;?></section>
    <script>gData.page_render_mode='<?=$page_render_mode?>';</script>
    <div class="pageBg">该应用由 <?php echo C('env.copyright')?> 提供</div>
  </div>
  <div id="loadingCanvas" class="useTopNav-<?=$topnav_no?> useNav-<?=$nav_no?>"></div>
  <div class="hide"><img src="<?php echo ploadingimg()?>" alt=""/></div>
  <?php include T('_nav');?>
  <?php include T('inc/popdlg');?>
  <?php include T('inc/popalert');?>
</div>
<!-- 微信操作提示 -->
<div id="cover-wxtips" class="wxcover">
<?php if (C('env.usecdn')):?>
<img alt="" src="http://fcdn.fxmapp.com/img/guide.png"/>
<?php else:?>
<img alt="" src="<?=$contextpath;?>themes/mobiles/img/guide.png"/>
<?php endif;?>
</div>
</body>
<?php footscript();?>
<?php tplholder('FOOT_JS');?>
<script>var FST=new Object();FST.autostart=1;FST.uid=parseInt(gUser.uid);</script>
<script type="text/javascript" src="<?=$contextpath;?>misc/js/fst.min.js"></script>
</html><?php

//: add css & js files
if (C('env.usecdn')):
add_css('http://fcdn.fxmapp.com/css/c.min.css',['scope'=>'global','ver'=>'none']);
add_js('http://fcdn.fxmapp.com/js/jquery-2.1.3.min.js',['pos'=>'head','ver'=>'none']);
add_js('http://fcdn.fxmapp.com/js/fm.min.js',['pos'=>'foot','ver'=>'none']);
else:
add_css('c.min.css',['scope'=>'global','ver'=>'none']);
add_js('ext/jquery-2.1.3.min.js',['pos'=>'head','ver'=>'none']);
add_js('fm.min.js',['pos'=>'foot','ver'=>'none']);
endif;
add_css('m.css',['scope'=>'global']);
add_js('global.js',['pos'=>'head']);
add_js('m.js',['pos'=>'foot']);

?>