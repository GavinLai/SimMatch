<?php defined('IN_SIMPHP') or die('Access Denied');?>

<script type="text/javascript">$(function(){$('#rank-top').html($('#ranktop-html').text())});</script>
<?php if(''!==$errmsg):?>

<div class="error"><?=$errmsg?></div>
<script type="text/html" id="ranktop-html"><div class="prompt">提示</div></script>

<?php else:?>

<script type="text/html" id="ranktop-html">
<a href="<?php echo U('match/'.$match_id.'/rank',['t'=>'total_rank','player_id'=>$player_id])?>" class="c-24-7<?php if($type==''||$type=='total_rank'):?> on<?php endif;?>" onclick="return gorank(this)">总排名</a>
<a href="<?php echo U('match/'.$match_id.'/rank',['t'=>'pass_rank', 'player_id'=>$player_id])?>" class="c-24-6<?php if($type=='pass_rank'):?> on<?php endif;?>" onclick="return gorank(this)">已晋级</a>
<a href="<?php echo U('match/'.$match_id.'/rank',['t'=>'week_rank', 'player_id'=>$player_id])?>" class="c-24-6<?php if($type=='week_rank'):?> on<?php endif;?>" onclick="return gorank(this)">周冠军</a>
<a href="<?=$return_url?>" class="c-24-5">☜返回</a>
</script>
<script type="text/javascript">
gData.downpull_display = false;
function gorank(obj) {
	$(obj).parent().find('a').removeClass('on');
	$(obj).addClass('on');
	return true;
}
</script>

<div class="rankwrap">
	<ul id="ranklist">
<?php if($listnum):?>

	<?php if($type==''||$type=='total_rank'||$type=='pass_rank'):?>
		<li class="h"><div class="c-12-2 cc">名次</div><div class="c-12-4 cl">选手</div><div class="c-12-2 cc">票数</div><div class="c-12-2 cc">花数</div><div class="c-12-2 cc">总票数</div></li>
	<?php elseif ($type=='week_rank'):?>
		<li class="h zgj"><div class="c-10-2 cc zhouci">周次</div><div class="c-10-4 cl renqi"><em>👑</em>人气女神</div><div class="c-10-4 cl xianhua"><em>🌺</em>鲜花女神</div></li>
		<li class="holder"></li>
	<?php endif;?>
<!--{AJAXPART}-->	
		<?php foreach ($ranklist AS $it):?>
	<?php if($type==''||$type=='total_rank'||$type=='pass_rank'):?>
		<li>
			<div class="c-12-2 cc"><?=$it['rankno']?></div>
			<div class="c-12-4 cl"><a href="<?php echo U('player/'.$it['player_id'])?>" class="cimg"><img src="<?=$it['img_thumb']?>" alt="" class="ulogo"/><span><?=$it['truename']?><br/><?=$it['player_id']?>号</span></a></div>
			<div class="c-12-2 cc"><?=$it['votecnt_single']?></div>
			<div class="c-12-2 cc"><?=$it['flowercnt']?></div>
			<div class="c-12-2 cc"><?=$it['votecnt']?></div>
		</li>
	<?php elseif ($type=='week_rank'):?>
		<li>
			<div class="c-10-2 cc"><?=$it['weekno_txt']?></div>
			<div class="c-10-4 cl">
			<?php if(empty($it['player_id1'])):?>
				<span class="weektxt">虚位以待...</span>
			<?php else:?>
				<a href="<?php echo U('player/'.$it['player_id1'])?>" class="cimg"><img src="<?=$it['player1_dt']['cover_pic']?>" alt="" class="ulogo"/><span><?=$it['player1_dt']['truename']?><br/><?=$it['player_id1']?>号</span></a>
			<?php endif;?>
			</div>
			<div class="c-10-4 cl">
			<?php if(empty($it['player_id2'])):?>
				<span class="weektxt">虚伪以待...</span>
			<?php else:?>
				<a href="<?php echo U('player/'.$it['player_id2'])?>" class="cimg"><img src="<?=$it['player2_dt']['cover_pic']?>" alt="" class="ulogo"/><span><?=$it['player2_dt']['truename']?><br/><?=$it['player_id2']?>号</span></a>
			<?php endif;?>
			</div>
		</li>
		<li class="holder"></li>
	<?php endif;?>
		<?php endforeach;?>

	<?php if($hasmore):?>	
		<li class="e"><a href="javascript:;" onclick="morerank(this,'<?=$nextpage?>')">点击查看更多</a></li>
	<?php else:?>
		<?php if($nextpage>2):?>
		<li class="e"><a href="javascript:;" class="none">没有更多了</a></li>
		<?php endif;?>
	<?php endif;?>

<?php if($isajax):?>
<script type="text/javascript">$(function(){F.set_scroller(false,100)})</script>
<?php endif;?>
<!--{/AJAXPART}-->
<script>
function morerank(obj, nextpage) {
	var gourl = "<?php echo U('match/'.$match_id.'/rank',['_hr'=>1,'t'=>$type,'isajax'=>1])?>&p="+nextpage;
	$(obj).text('数据获取中...');
	F.get(gourl,function(ret){
		$(obj).parent().replaceWith(ret.body);
	});
}
</script>
<?php else:?>
		<li><div class="list-empty">数据为空</div></li>
<?php endif;?>
	</ul>
</div>

<?php endif;/*END if(''!==$errmsg) else*/?>