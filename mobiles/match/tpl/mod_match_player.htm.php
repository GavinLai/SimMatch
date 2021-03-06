<?php defined('IN_SIMPHP') or die('Access Denied');?>

<?php if(''!==$errmsg):?>

<div class="error"><?=$errmsg?></div>

<?php else:?>

<div class="match-player" id="match-player">
  <div class="swipe">
    <div id="slider" class="slider">
      <?php for($i=0;$i<$player_gallery_num;$i++):?>
      <div class="slidit" <?php if(0===$i) echo 'style="display:block"';?>><span class="edge"></span><img src="<?=$player_gallery[$i]?>" alt="" /></div>
      <?php endfor;?>
    </div>
    <?php if ($player_gallery_num>1):/*仅多个图片时才显示*/?>
    <div id="slidnav" class="slidnav clearfix">
      <?php for($i=0;$i<$player_gallery_num;$i++):?>
      <a href="javascript:void(0);" <?php if(0===$i):?>class="active"<?php endif;?>><?php echo $i+1;?></a>
      <?php endfor;?>
     </div>
     <?php endif;?>
  </div>
  <div class="match-pos nameno"><p class="name"><?=$player_info['truename']?></p><p class="no">No.<?=$player_info['player_id']?></p><?php if($player_info['qrcode']!=''):?><p class="qrcode"><a href="javascript:;" onclick="showqrcode(this)"><img src="<?=$player_info['qrcode']?>" alt="qrcode"/></a></p><?php endif;?></div>
  <div class="ltbar">
	  <?php if($player_info['video']<>''):?>
	  <a class="barit tovideo" href="<?=$player_info['video']?>" target="_blank">视频</a>
	  <?php endif;?>
	  <a class="barit tomatch" href="<?php echo U('match/'.$player_info['match_id'])?>">主页</a>
	  <a class="barit lastit tojoin" href="<?php echo U('match/'.$player_info['match_id'].'/join')?>">报名</a>
  </div>
  <a class="match-pos torank" href="<?php echo U('match/'.$player_info['match_id'].'/rank',['player_id' =>$player_info['player_id'] ])?>">
  <?php if($player_info['is_goup']):?>晋级组排名：<?php else:?>未晋级组排名：<?php endif;?><em><?=$player_info['rank_info']['rank']?></em><br/><?php if($player_info['is_goup']):?>晋级组总数：<?php else:?>未晋级组总数：<?php endif;?><em><?=$player_info['rank_info']['total']?></em>
  </a>
  <div class="match-pos btmnav">
    <a class="navit" href="javascript:;" id="op-tovote">投票<br><span>(<em><?=$player_info['votecnt_single']?></em>票)</span></a>
    <a class="navit" href="<?php echo U('trade/order/confirm',['goods'=>'flower','player_id'=>$player_info['player_id']])?>" id="op-toflower">送花<br><span>(<em><?=$player_info['flowercnt']?></em>花)</span></a>
    <a class="navit" href="javascript:;" id="op-tokiss">总票数<br><span>(<em><?php if($player_info['stage']>0){ echo $player_info['votecnt'.$player_info['stage']]; }else{ echo $player_info['votecnt']; }?></em>票)</span></a>
    <a class="navit" href="javascript:;" id="op-toshare">分享给<br><span>朋友</span></a>
  </div>
<script type="text/javascript">
var t1;
$(function(){
	var _active = 0, $_ap = $('#slidnav a');
  
  t1 = new TouchSlider({
     id:'slider',
     auto: false,
     speed:300,
     timeout:5000,
     before:function(newIndex, oldSlide){
    	 if ($_ap.size()>0) {
         $_ap.get(_active).className = '';
         _active = newIndex;
         $_ap.get(_active).className = 'active';
    	 }
     }
  });

  if ($_ap.size()>0) {
	  $_ap.each(function(index,ele){
      $(ele).click(function(){
        t1.slide(index);
        return false;     
      });
		});
  }
  
  setTimeout(function(){t1.resize();},500);

  //禁用浏览器的左右滑动手势
  var control = navigator.control || {};
  if (control.gesture) {
  	control.gesture(false);
  }
});
</script>

<script type="text/javascript">
var currpic = '<?php echo isset($player_gallery[0]) ? $player_gallery[0] : ''?>';
var picset = new Array();
<?php foreach($player_gallery AS $it):?>
picset.push('<?=$it?>');
<?php endforeach;?>
$('#slider img').click(function(){
  wx.previewImage({
    current: currpic,
    urls: picset
  });
});
</script>

<script type="text/javascript">
function showqrcode(obj) {
	myAlert($(obj).html(),null,'<span style="font-size: 14px;font-size:1.4rem;font-weight: normal;">微信扫此二维码可直接进入该页面</span>','关闭',{'padding':'0'});
}
$(function(){

	$('#op-toshare').click(function(){
		wxData.shareinfo.show_cover();
	});
	$('#op-tokiss').click(function(){
		myAlert('总票数 = 投票数 + 送花数<em style="color: #f00">x2</em>');
	});

	var ajaxing = false, player_id = myParseInt('<?=$player_info['player_id']?>');
	$('#op-tovote').click(function(){
		if (ajaxing) return false;
		ajaxing = true;
		var oThis = this;
		F.post('<?php echo U('match/vote')?>', {"player_id": player_id,"token":gData.token}, function(ret){
			ajaxing = false;
			if (ret.flag=='SUC') {
				myAlert(ret.msg);
				$(oThis).find('em').text(ret.votedcnt_single);
				$('#op-tokiss em').text(ret.votedcnt);
			}
			else {
				myAlert(ret.msg);
			}
		});
  });
	
});
</script>

<?php if($animatenum):?>
<?php add_js('FallingEffect.js',['pos'=>'current']);?>
<script type="text/javascript">
$(function(){
	setTimeout(function(){
		(new FallingEffect(<?=$animatenum?>,{
			container: '#match-player',
			onFinish: function(){}
		})).show();
	},100);
});
</script>
<?php endif;?>

</div>
<?php endif;?>