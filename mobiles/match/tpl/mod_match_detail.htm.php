<?php defined('IN_SIMPHP') or die('Access Denied');?>
<?php if(''!==$errmsg):?>

<div class="error"><?=$errmsg?></div>

<?php else:?>

<script type="text/html" id="matchtop-html">
访问数：<em><?=$ninfo['visitcnt']?></em>&nbsp;&nbsp;总票数：<em><?=$ninfo['votecnt']?></em>&nbsp;&nbsp;参赛人数：<em><?=$total_player_num?></em>
</script>
<script type="text/javascript">
function showtopbar(target, show) {
	if(typeof(show)=='undefined') {
		show = true;
	}
	if (show) {
		if (!F.event.flag.showtop) {
			F.event.flag.showtop = true;
			target.fadeIn();
		}
	}
	else {
		if (F.event.flag.showtop) {
			F.event.flag.showtop = false;
			target.hide();
		}
	}
};
$(function(){
	var $_mtop = $('#match-top');
	$_mtop.html($('#matchtop-html').text());
	F.onScrollStart(function(){
		if (typeof(F.event.flag.showtop)=='undefined') {
			F.event.flag.showtop = true;
		}
	});
	F.onScrolling(function(){
		if (F.scrollDirection < 0) { //向上滑动(滚动条向下走)，隐藏topbar
			if (this.y < -150) {
				showtopbar($_mtop, false);
			}
			else {
				showtopbar($_mtop, true);
			}
		}
		else if (F.scrollDirection > 0) { //向下滑动(滚动条向上走)，则需分情况处理
			if (this.y > -150 && this.y <= 0) {
				showtopbar($_mtop, true);
			}
			else {
				showtopbar($_mtop, false);
			}
		}
	});
});
</script>
<div class="match-thumb">
  <img src="<?=fixpath($ninfo['thumb_url'])?>" alt="" />
  <div class="join"><a href="/match/<?=$the_nid?>/join" class="btn btn-block btn-purple">我要参赛</a></div>
</div>
  
<div class="block-page match-info">
<?php if(0):?>
  <div class="row">比赛时间：<?=$ninfo['match_type_text']?>截止时间 <?php echo date('Y年m月d日',strtotime($ninfo['end_date']))?></div>
<?php endif;?>
  <?php if ($content_parsed_num):?>
  <?php foreach($content_parsed AS $p):?>
  <div class="row<?php if($content_parsed_num > 2 && !in_array($p['tag'], ['show','more'])) echo ' hide'?>">
    <div class="dt"><?=$p['txt']?></div>
    <?php if ($p['tag']=='more'):?>
    <div class="dtmore"><button class="btn btn-block btn-orange" onclick="show_full(this)">点击查看详情</button></div>
    <?php endif;?>
  </div>
  <?php endforeach;?>
  <?php else:?>
  <div class="row"><div class="dt"><?=$ninfo['content_detail']?></div></div>
  <?php endif;?>
  <div class="join"><a href="/match/<?=$the_nid?>/join" class="btn btn-block btn-purple">我要参赛</a></div>
</div>

<div class="block-page player-info" id="player-wrap">
<?php if ($player_num):?>
  <div class="linkbtn-box"><a href="javascript:;" onclick="see_passplayers(this)" class="btn btn-block btn-green">点击查看已晋级复赛选手(总票数5000以上)</a></div>
  <div class="search-box"><form action="" method="get" onsubmit="return searchform(this)"><input type="search" name="search" value="<?=$search?>" class="stext" placeholder="请输入“选手姓名 或 编号”搜索"/><input type="submit" name="submit" class="sbtn" value="  "/></form></div>
<?php endif;?>
  <div id="player-list" class="player-list">

<!--{AJAXPART}-->
  <?php if (!$player_num):?>
    <div class="emptytip"><?php if($search!=''):?>找不到对应的参赛选手<?php else:?>还没有参赛选手，快来做第一个吧！<a href="/match/<?=$the_nid?>/join">我要参赛！</a><?php endif;?></div>
  <?php else:?>
  
  <?php foreach ($player_list AS $it):?>
    <div class="itbox">
      <a href="<?php echo U('player/'.$it['player_id'])?>" class="itcont hl<?=$it['rankflag']?>">
        <div class="cot">编号 <?=$it['player_id']?><span class="rt">姓名 <?=$it['truename']?></span>
        <?php if($it['ranktxt']!=''):?>
        <br/><span class="ranktip"><?php if($it['rankflag']==1):?>👑<?php else:?>🌺<?php endif;?>&nbsp;<?=$it['ranktxt']?></span>
        <?php endif;?>
        	<p class="imgc"><span class="edge"></span><img src="<?=$it['img_thumb']?>" alt="" /></p>
        </div>
        <p class="fot"><span class="p lt">票数 <em><?=$it['votecnt']?></em></span><span class="p rt">花数 <em><?=$it['flowercnt']?></em></span></p>
      </a>
    </div>
  <?php endforeach;?>
<script type="text/javascript">
$(function(){
	setTimeout(function(){
		var $ele = $('.player-list .imgc');
		var w = $ele.width();
		var h = parseInt(w/0.75); //ratio used by iphone4 w/h ratio
		$ele.css('height',h+'px');
		F.set_scroller(false,10);
	},1);
});
</script>

	<!-- BEGIN pager -->
  <div class="paging" id="paging" data-curpage="<?=$curpage?>" data-maxpage="<?=$maxpage?>" data-search="<?=$search?>">
  	<a href="javascript:;" rel="begin" class="pgbtn<?php if(1==$curpage){echo ' disable';}?>" onclick="gopage(this)">首页</a>
  	<a href="javascript:;" rel="last" class="pgbtn<?php if(1==$curpage){echo ' disable';}?>" onclick="gopage(this)">上一页</a>
  	<a href="javascript:;" rel="next" class="pgbtn<?php if($maxpage==$curpage){echo ' disable';}?>" onclick="gopage(this)">下一页</a>
  	<a href="javascript:;" rel="end" class="pgbtn<?php if($maxpage==$curpage){echo ' disable';}?>" onclick="gopage(this)">末页</a>
  	<select name="pgsel" rel="select" class="pgbtn" onchange="gopage(this)">
  	<?php for($i=1; $i<=$maxpage; $i++):?>
  		<option value="<?=$i?>"<?php if($i==$curpage):?> selected="selected"<?php endif;?>>&nbsp;<?=$i?>/<?=$maxpage?></option>
  	<?php endfor;?>
  	</select>
  </div>
  <!-- END pager -->
  
  <?php endif;?>
<!--{/AJAXPART}-->
  </div><!-- END DIV#player-list -->
  <div class="join"><a href="/match/<?=$the_nid?>/join" class="btn btn-block btn-purple">我要参赛</a></div>
  <?php if(!empty($GLOBALS['user']->uid) && in_array($GLOBALS['user']->uid,[10001])): ?>
	<div class="lastrow"><span>送花数：</span><em><?=$ninfo['flowercnt']?></em></div>
	<?php endif;?>
</div><!-- END DIV#player-wrap -->

<?php if ($player_num):?>
<script type="text/javascript">
function gopage(obj) {
	if ($(obj).hasClass('disable')) {
		return;
	}
	var _parent = $(obj).parent();
	var curpage = myParseInt(_parent.attr('data-curpage'));
	var maxpage = myParseInt(_parent.attr('data-maxpage'));
	var search  = _parent.attr('data-search');
	var p = 1, rel = $(obj).attr('rel');
	
	switch(rel) {
	default:
		case 'begin': p = 1;break;
		case 'end':   p = maxpage;break;
		case 'last':  p = curpage-1;p = p > 0 ? p : 1;break;
		case 'next':  p = curpage+1;p = p > maxpage ? maxpage : p;break;
		case 'select': p=$(obj).val();break;
	}
	
	if (maxpage<=1) {
		myAlert('当前仅有一页');
		return;
	}
	else if (p==curpage) {
		if (p==1) {
			myAlert('已经第一页');
		}
		else if (p==maxpage) {
			myAlert('已经最后一页');
		}
		return;
	}
	F.get('<?php echo U('match/'.$the_nid,'_hr=1&isajax=1')?>&s='+search+'&p='+p, function(ret){
		$('#player-list').html(ret.body);
	});
}
function searchform(obj) {
	var _act = '<?php echo U('match/'.$the_nid,'_hr=1&isajax=1')?>';
	var _val = $(obj).find('input').val().trim();
	F.get(_act+'&s='+_val, function(ret){
		$('#player-list').html(ret.body);
	});
	return false;
}
function see_passplayers(obj) {
	var _url = '<?php echo U('match/'.$the_nid.'/passed','_hr=1')?>';
	F.get(_url, function(ret){
		$('#player-wrap').html(ret.body);
	});
	return false;
}
</script>
<?php endif;/*if ($player_num)*/?>
<script type="text/javascript">
function show_full(obj) {
	$('.match-info .row.hide').show();
	$(obj).parent().remove();
	F.oIScroll.refresh();
}
</script>

<?php endif;/*END if(''!==$errmsg) else*/?>