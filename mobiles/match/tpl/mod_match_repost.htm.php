<?php defined('IN_SIMPHP') or die('Access Denied');?>
<?php if(''!==$errmsg):?>

<div class="error"><?=$errmsg?></div>

<?php else:?>

<div class="match-join">
  <form name="frm_matchjoin" id="frm_matchjoin" action="<?php echo U('match/repost')?>" method="post" onsubmit="return false">
    <div class="row">
    	<input name="player_id" id="frm_id" type="hidden" value="<?=$player_info['player_id']?>"/>
    	<h1 class="pname"><?=$player_info['truename']?>（<?=$player_info['player_id']?>号）</h1>
    </div>
    <div class="row row-tip2">仅可更改图片<em>一次</em>(之后不能再更改)，请尽量选取能展现自己风采或才艺的照片 或 视频链接。</div>
    <div class="pvtip">图片预览：</div>
    <div class="row row-ele">
      <div id="match-preview">
      <?php foreach($player_gallery As $v):?>
      <div class="pv"><img src="<?=$v['img_std']?>" alt="" data-rid="<?=$v['rid']?>" class="pvimg<?php if($player_info['cover_pic_id']==$v['rid']):?> chkcv<?php endif;?>" /><p class="pvop"><a href="javascript:;" class="setcv" onclick="set_cover_pic(this)">设为封面</a><a href="javascript:;" onclick="remove_pic(this)">删除</a></p></div>
      <?php endforeach;?>
      </div>
      <div class="uparea">
        <input type="file" name="upfile" id="frm_upfile" />
        <p>请上传您的照片，至少<em style="color:red;">1</em>张，最多可上传<em style="color:red;"><?=$maxuploadnum?></em>张<br/><span>(第一张默认为封面，也可手动设定；最好选用竖版图片；图片越多越容易受人关注哟)</span>
        	<br/><em style="color:red;">如无法上传照片,请联系微信: </em><em style="color:green;">choumeikufang</em>
        </p>
      </div>
    </div>
    <p class="row"><input name="video" id="frm_video" type="text" class="inptxt row-ele" value="<?=$player_info['video']?>" placeholder="美拍、唱吧、优酷等视频地址（可不填）" /></p>
    <p class="row tips">所有参赛选手请<em style="color:red;">务必</em>添加官方客服微信号：<br/><em style="color:green;font-size:16px;font-size:1.6rem;">choumeikufang</em>，以便随时关注赛事进程。</p>
    <p class="row"><input name="submit" type="submit" class="btn btn-purple" id="frm_submit" value="提 交" /></p>
    <p class="row">&nbsp;</p>
  </form>
</div>
<script type="text/javascript">gData.downpull_display = false;</script>
<script type="text/javascript">
function change_province(obj) {
	var pid = $(obj).val();
	var $city = $('#frm_city');
	var prehtml = '<option value="0">请选择城市▼</option>';
	pid = parseInt(pid);
	if (0===pid) {
		$city.html(prehtml);
		return;
	}
	F.getJSON("<?php echo U('match/cities')?>",{parent_id: pid,maxage: 0},function(ret){
		if (ret.flag=='SUC') {
			var html='';
			for(var i=0; i<ret.data.length; i++) {
				html += '<option value="'+ret.data[i]['locaid']+'">'+ret.data[i]['location']+'</option>';
			}
			$city.html(prehtml+html);
		}
	});
}
function onload_pic(img) {
	F.oIScroll.refresh();
}
function remove_pic(obj) {
	if(confirm('确定删除该图片？')) {
		$(obj).parents('.pv').remove();
		F.oIScroll.refresh();
	}
}
function set_cover_pic(obj) {
	if(confirm('确定设该图片为封面？')) {
		$(obj).parents('#match-preview').find('img.pvimg').removeClass('chkcv');
		$(obj).parents('.pv').find('img.pvimg').addClass('chkcv');
		F.oIScroll.refresh();
	}
}
var maxuploadnum = parseInt('<?=$maxuploadnum?>');
$(function(){
	document.getElementById('frm_upfile').onchange = function(e) {
		var files = e.target.files;
		var fr = new FileReader();
		fr.onload = function(ev) {
			var _h = '<div class="pv"><img src="'+ev.target.result+'" alt="" data-rid="0" class="pvimg" onload="onload_pic(this)" /><p class="pvop"><a href="javascript:;" class="setcv" onclick="set_cover_pic(this)">设为封面</a><a href="javascript:;" onclick="remove_pic(this)">删除</a></p></div>';
			$('#match-preview').append(_h);
		};
	  fr.readAsDataURL(files[0]);
	};
	
	$('#frm_matchjoin').bind('submit', function(){
		
		var post_data = {};
		post_data.player_id = $('#frm_id').val();
		/*
		var _truename = $('#frm_truename');
		post_data.truename = _truename.val().trim();
		if(''==post_data.truename){
			myAlert('请输入真实姓名');
			return false;
		}

		var _mobile = $('#frm_mobile');
		post_data.mobile = _mobile.val().trim();
		if(''==post_data.mobile){
			myAlert('请输入手机号');
			return false;
		}
		else if (!/^\d{11,14}$/.test(post_data.mobile)) {
			myAlert('手机号非法');
			return false;
		}

		var _weixin = $('#frm_weixin');
		post_data.weixin = _weixin.val().trim();
		if(''==post_data.weixin){
			myAlert('请输入微信号');
			return false;
		}
		
		post_data.province = $('#frm_province').val();
		post_data.city = $('#frm_city').val();
		post_data.idcard = $('#frm_idcard').val().trim();
    */
    post_data.video = $('#frm_video').val().trim();
    if (''!==post_data.video && !/^http:\/\//i.test(post_data.video)) {
			myAlert('视频地址不合法');
			return false;
		}
		/*
		post_data.slogan = $('#frm_slogan').val().trim();
		post_data.remark = $('#frm_remark').val().trim();
		if ( ''!==post_data.idcard && post_data.idcard.length != 18 && post_data.idcard.length != 15 ) {
			myAlert('身份证号不合法');
			return false;
		}
		*/

		var imgs = new Array(), cover_idx = 0, i=0;
		$('#match-preview img.pvimg').each(function(){
			var _me = $(this);
			if(_me.attr('data-rid')=='0') {
				imgs.push(_me.attr('src'));
			}
			else {
				imgs.push(_me.attr('data-rid'));
			}
			if (_me.hasClass('chkcv')) {
				cover_idx = i;
			}
			i++;
		});
		if (0===imgs.length) {
			myAlert('请至少上传一张图片');
			return false;
		}
		else if (imgs.length > maxuploadnum) {
			myAlert('最多只能上传'+maxuploadnum+'张图片，请删除'+(imgs.length-maxuploadnum)+'张再提交');
			return false;
		}
		post_data['imgs[]'] = imgs;
		post_data['cover_idx'] = cover_idx;

		var _btn = $('#frm_submit');
		_btn.val('图片上传中，请耐心等待...').attr('disabled',true);
		F.post($(this).attr('action'), post_data, function(ret){
			_btn.val('完成！').removeAttr('disabled');
			if(ret.flag=='SUC'){
				myAlert('已成功上传！请<em style="color:red">务必</em>关注大赛客服微信号：<em style="color:green">choumeikufang</em>，以随时了解获奖情况。',function(gourl){
					window.location.href = gourl;
				},'','','','<?php echo U('player/')?>'+ret.player_id);
			}else{
				_btn.val('提 交');
				myAlert(ret.msg);
			}
		});

		return false;
	});
});
</script>

<?php endif;?>