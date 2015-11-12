<?php defined('IN_SIMPHP') or die('Access Denied');?>

<?php if(''!==$errmsg):?>

<div class="error"><?=$errmsg?></div>

<?php else:?>

<p class="player-pass-tip">当前<em><?=$totalnum?></em>名选手晋级(5000票以上晋级)&nbsp;&nbsp;<a href="<?php echo U('match/'.$the_nid)?>" class="alink">☜返回首页</a></p>
<div class="player-list" id="player-pass-list">
	<!--{AJAXPART}-->
  <?php foreach ($player_pass_list AS $it):?>
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
  <?php if($maxpage>1):?>
  <div class="themore"><a href="javascript:;" onclick="get_more_passplayers(this)" data-maxpage="<?=$maxpage?>" data-curpage="<?=$curpage?>" <?php if($curpage==$maxpage):?>class="disable"<?php endif;?>><?php if($curpage==$maxpage):?>O(∩_∩)O~没有更多了<?php else:?>点击查看更多<?php endif;?></a></div>
  <?php endif;?>
<script type="text/javascript">
$(function(){
	setTimeout(function(){
		var $ele = $('#player-pass-list .imgc');
		var w = $ele.width();
		var h = parseInt(w/0.75); //ratio used by iphone4 w/h ratio
		$ele.css('height',h+'px');
		F.set_scroller(false,10);
	},1);
});
</script>
  <!--{/AJAXPART}-->
</div>
<script type="text/javascript">
function get_more_passplayers(obj) {
	var maxpage = $(obj).attr('data-maxpage');
	var curpage = $(obj).attr('data-curpage');
	maxpage = parseInt(maxpage);
	curpage = parseInt(curpage);
	if (curpage==maxpage) return;
	$(obj).text('数据获取中...');
	F.get('<?php echo U('match/'.$the_nid.'/passed','_hr=1&isajax=1')?>&p='+(curpage+1), function(ret){
		$(obj).parent().replaceWith(ret.body);
	});
}
</script>
<?php endif;/*END if(''!==$errmsg) else*/?>