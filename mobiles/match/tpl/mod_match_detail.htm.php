<?php defined('IN_SIMPHP') or die('Access Denied');?>
<?php if(''!==$errmsg):?>

<div class="error"><?=$errmsg?></div>

<?php else:?>

<script type="text/html" id="matchtop-html">
访问数：<em><?=$ninfo['visitcnt']?></em>&nbsp;&nbsp;总票数：<em><?=$ninfo['votecnt']?></em><span>日期：<em><?php echo date('Y-m-d') ?></em></span>
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
<div class="block-page player-info">
<?php if ($player_num):?>
  <div class="search-box"><form action="" method="get" onsubmit="return searchform(this)"><input type="search" name="search" value="<?=$search?>" class="stext" placeholder="请输入“参赛者姓名 或 编号”搜索"/><input type="submit" name="submit" class="sbtn" value="  "/></form></div>
<?php endif;?>  
  <div id="player-list">

<!--{AJAXPART}-->
<script type="text/javascript">
window.curpage = myParseInt('<?=$curpage?>');
window.maxpage = myParseInt('<?=$maxpage?>');
window.searchkey = '<?=$search?>';
</script>
  <?php if (!$player_num):?>
    <div class="emptytip"><?php if($search!=''):?>找不到对应的参赛者<?php else:?>还没有参赛者，快来做第一个吧！<a href="/match/<?=$the_nid?>/join">我要参赛！</a><?php endif;?></div>
  <?php else:?>
  
  <?php foreach ($player_list AS $it):?>
    <div class="itbox">
      <a href="<?php echo U('player/'.$it['player_id'])?>" class="itcont">
        <div class="cot">编号 <?=$it['player_id']?><span class="rt">姓名 <?=$it['truename']?></span><p class="imgc"><span class="edge"></span><img src="<?=$it['img_thumb']?>" alt="" /></p></div>
        <p class="fot"><span class="p lt">票数 <em><?=$it['votecnt']?></em></span><span class="p rt">花数 <em><?=$it['flowercnt']?></em></span></p>
      </a>
    </div>
  <?php endforeach;?>
<script type="text/javascript">
$(function(){
	setTimeout(function(){
		var $ele = $('#player-list .imgc');
		var w = $ele.width();
		var h = parseInt(w/0.75); //ratio used by iphone4 w/h ratio
		$ele.css('height',h+'px');
		F.set_scroller(false,10);
	},1);
});
</script>
  <?php endif;?>
<!--{/AJAXPART}-->
  </div>
  
  <?php if ($player_num):?>
  <?php if ($maxpage > 1):?>
  <div class="paging" id="paging">
  	<a href="javascript:;" rel="begin">首页</a>
  	<a href="javascript:;" rel="last">上一页</a>
  	<a href="javascript:;" rel="next">下一页</a>
  	<a href="javascript:;" rel="end">末页</a>
  </div>
<script type="text/javascript">
$(function(){
	$('#paging > a').bind('click',function(){
		var rel = $(this).attr('rel');
		gopage(rel, curpage, maxpage, searchkey);
	});
});
function gopage(rel, curpage, maxpage, search) {
	var p = 1;
	switch(rel) {
	default:
		case 'begin': p = 1;break;
		case 'end':   p = maxpage;break;
		case 'last':  p = curpage-1;p = p > 0 ? p : 1;break;
		case 'next':  p = curpage+1;p = p > maxpage ? maxpage : p;break;
	}
	if (maxpage<=1) {
		alert('当前仅有一页');
		return;
	}
	else if (p==curpage) {
		if (p==1) {
			alert('已经第一页');
		}
		else if (p==maxpage) {
			alert('已经最后一页');
		}
		return;
	}
	F.get('<?php echo U('match/'.$the_nid,'_hr=1&isajax=1')?>&s='+search+'&p='+p, function(ret){
		$('#player-list').html(ret.body);
	});
}
</script>
<?php endif;/*END if ($maxpage > 1)*/?>
  <div class="join"><a href="/match/<?=$the_nid?>/join" class="btn btn-block btn-purple">我要参赛</a></div>
  <?php endif;?>
</div>
<script type="text/javascript">
function searchform(obj) {
	var _act = '<?php echo U('match/'.$the_nid,'_hr=1&isajax=1')?>';
	var _val = $(obj).find('input').val().trim();
	F.get(_act+'&s='+_val, function(ret){
		$('#player-list').html(ret.body);
	});
	return false;
}
function show_full(obj) {
	$('.match-info .row.hide').show();
	$(obj).parent().remove();
	F.oIScroll.refresh();
}
</script>

<?php endif;?>