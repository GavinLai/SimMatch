<?php defined('IN_SIMPHP') or die('Access Denied');?>

<?php if(''!==$errmsg):?>

<div class="error"><?=$errmsg?></div>
<script type="text/html" id="ranktop-html">
<div class="prompt">提示</div>
</script>
<script type="text/javascript">
$(function(){
	var $_top = $('#rank-top');
	$_top.html($('#ranktop-html').text());
});
</script>

<?php else:?>

<script type="text/html" id="ranktop-html">
<a href="<?php echo U('match/'.$match_id.'/rank',['t'=>'most_vote'  ,'player_id'=>$player_id])?>" class="c-24-7<?php if($type==''||$type=='most_vote'):?> on<?php endif;?>" onclick="return gorank(this)">投票最多</a>
<a href="<?php echo U('match/'.$match_id.'/rank',['t'=>'most_flower','player_id'=>$player_id])?>" class="c-24-7<?php if($type=='most_flower'):?> on<?php endif;?>" onclick="return gorank(this)">送花最多</a>
<a href="<?php echo U('match/'.$match_id.'/rank',['t'=>'week_rank'  ,'player_id'=>$player_id])?>" class="c-24-6<?php if($type=='week_rank'):?> on<?php endif;?>" onclick="return gorank(this)">周冠军</a>
<a href="<?php echo U('player/'.$player_id)?>" class="c-24-4">☜返回</a>
</script>
<script type="text/javascript">
function gorank(obj) {
	$(obj).parent().find('a').removeClass('on');
	$(obj).addClass('on');
}
$(function(){
	var $_top = $('#rank-top');
	$_top.html($('#ranktop-html').text());
});
</script>

<div class="rankwrap">
	<ul id="ranklist">
<?php if($listnum):?>

	<?php if($type==''||$type=='most_vote'):?>
		<li class="h"><div class="c-10-3 c1">投票者</div><div class="c-10-2 c2">投票量</div><div class="c-10-5 c3">最后投票时间</div></li>
	<?php elseif ($type=='most_flower'):?>
		<li class="h"><div class="c-10-3 c1">送花者</div><div class="c-10-2 c2">送花量</div><div class="c-10-5 c3">最后送花时间</div></li>
	<?php elseif ($type=='week_rank'):?>
		<li class="h"><div class="c-10-3 c1">周次</div><div class="c-10-2 c2">周冠军</div><div class="c-10-5 c3">起终时间</div></li>
	<?php endif;?>
<!--{AJAXPART}-->	
		<?php foreach ($ranklist AS $it):?>
	<?php if($type==''||$type=='most_vote'||$type=='most_flower'):?>
		<li>
			<div class="c-10-3 c1"><img src="<?=$it['logo']?>" alt="" class="ulogo"/>&nbsp;<?=$it['nickname']?></div>
			<div class="c-10-2 c2"><?=$it['action_amount']?></div>
			<div class="c-10-5 c3"><?php echo date('Y-m-d H:i:s',$it['lasttime'])?></div>
		</li>
	<?php elseif ($type=='week_rank'):?>
		
	<?php endif;?>
		<?php endforeach;?>
		
	<?php if($hasmore):?>	
		<li class="e"><a href="javascript:;" onclick="morerank(this,'<?=$nextpage?>')">查看更多</a></li>
	<?php else:?>
		<?php if($nextpage>2):?>
		<li class="e"><a href="javascript:;" class="none">没有更多了</a></li>
		<?php endif;?>
	<?php endif;?>
	
<!--{/AJAXPART}-->
<script>
function morerank(obj, nextpage) {
	var gourl = "<?php echo U('match/'.$match_id.'/rank',['_hr'=>1,'t'=>$type,'player_id'=>$player_id,'isajax'=>1])?>&p="+nextpage;
	F.get(gourl,function(ret){
		$(obj).parent().replaceWith(ret.body);
		F.set_scroller(false,100);
	});
}
</script>
<?php else:?>
		<li><div class="list-empty">数据为空</div></li>
<?php endif;?>
	</ul>
</div>

<?php endif;/*END if(''!==$errmsg) else*/?>