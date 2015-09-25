<?php defined('IN_SIMPHP') or die('Access Denied');?>
<?php if(''!==$errmsg):?>

<div class="error"><?=$errmsg?></div>

<?php else:?>

<div class="bbsizing match-top">访问人数：<em><?=$ninfo['visitcnt']?></em>　　投票总数：<em><?=$ninfo['votecnt']?></em></div>
<div class="match-thumb">
  <img src="<?=$ninfo['thumb_url']?>" alt="" />
  <div class="join"><div><a href="/match/<?=$the_nid?>/join" class="btn btn-block btn-purple">我要参赛</a></div></div>
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
  <div class="join"><div><a href="/match/<?=$the_nid?>/join" class="btn btn-block btn-purple">我要参赛</a></div></div>
</div>  
<div class="block-page player-info">
<?php if ($player_num):?>
  <div class="search-box"><form action="" method="get"><input type="search" name="search" placeholder="请输入“参赛者姓名 或 编号”搜索"/></form></div>
<?php endif;?>  
  <div id="player-list">
  <?php if (!$player_num):?>
    <div class="emptytip">还没有参赛者，快来做第一个吧！<a href="/match/<?=$the_nid?>/join">我要参赛！</a></div>
  <?php else:?>
  
  <?php foreach ($player_list AS $it):?>
    <div class="itbox">
      <a href="<?php echo U('player/'.$it['player_id'])?>" class="itcont">
        <div class="cot">#<?=$it['player_id']?> <?=$it['truename']?><p class="imgc"><span class="edge"></span><img src="<?=$it['img_thumb']?>" alt="" /></p></div>
        <p class="fot"><span class="p lt">票数 <em><?=$it['votecnt']?></em></span><span class="p rt">花数 <em><?=$it['flowercnt']?></em></span></p>
      </a>
    </div>
  <?php endforeach;?>
    
  <?php endif;?>
  </div>
  <div class="join"><div><a href="/match/<?=$the_nid?>/join" class="btn btn-block btn-purple">我要参赛</a></div></div>
</div>
<script type="text/javascript">
$(function(){
	setTimeout(function(){
		var $ele = $('#player-list .imgc');
		var w = $ele.width();
		var h = parseInt(w/0.75); //ratio used by iphone4 w/h ratio
		$ele.css('height',h+'px');
	},1);
});
</script>
<script type="text/javascript">
function show_full(obj) {
	$('.match-info .row.hide').show();
	$(obj).parent().remove();
	F.oIScroll.refresh();
}
wxData.share.title= '<?=$share_info['title']?>';
wxData.share.desc = '<?=$share_info['desc']?>';
wxData.share.link = '<?=$share_info['link']?>';
wxData.share.pic  = '<?=$share_info['pic']?>';
document.title = wxData.share.title;
wxData.share.refresh();
</script>

<?php endif;?>