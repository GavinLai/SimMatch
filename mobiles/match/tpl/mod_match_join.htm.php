<?php defined('IN_SIMPHP') or die('Access Denied');?>
<?php if(''!==$errmsg):?>

<div class="error"><?=$errmsg?></div>

<?php else:?>

<div class="match-join">
  <div class="row row-ele row-tip">发布违法、反动内容或冒用他人、组织名义发布，将依据记录提交公安机关处理，请不要涉及敏感政治话题。</div>
  <form name="frm_matchjoin" id="frm_matchjoin" action="<?php echo U('match/join')?>" method="post" onsubmit="return false">
    <p class="row"><input name="nid" id="frm_id" type="hidden" value="<?=$nid?>"/><input name="truename" id="frm_truename" type="text" class="inptxt row-ele" value="" placeholder="真实姓名（必填）" /></p>
    <p class="row"><input name="mobile" id="frm_mobile" type="text" class="inptxt row-ele" value="" placeholder="手机号，非常重要，唯一身份识别（必填）" /></p>
    <p class="row"><input name="weixin" id="frm_weixin" type="text" class="inptxt row-ele" value="" placeholder="微信号，唯一互联网联系方式（必填）" /></p>
    <div class="row">
    <select name="province" id="frm_province" class="inpsel row-ele" onchange="change_province(this)">
      <option value="0">请选择省份▼</option>
<?php foreach ($province AS $it):?>
      <option value="<?=$it['locaid']?>"><?=$it['location']?></option>
<?php endforeach;?>
    </select>
    <select name="city" id="frm_city" class="inpsel row-ele" style="margin-left: 2%;">
      <option value="0">请选择城市▼</option>
    </select>
    </div>
    <div class="pvtip">图片预览：</div>
    <div class="row row-ele">
      <div id="match-preview"></div>
      <div class="uparea">
        <input type="file" name="upfile" id="frm_upfile" />
        <p>请上传您的照片，最多可以上传<?=$maxuploadnum?>张<br/><span>(第一张默认为封面，最好选用竖版图片)</span></p>
      </div>
    </div>
    <p class="row"><input name="idcard" id="frm_idcard" type="text" class="inptxt row-ele" value="" placeholder="身份证号（可不填）" /></p>
    <!-- 
    <p class="row"><input name="video" id="frm_video" type="text" class="inptxt row-ele" value="" placeholder="美拍、唱吧、优酷等视频地址（可不填）" /></p>
    <p class="row"><input name="slogan" id="frm_slogan" type="text" class="inptxt row-ele" value="" placeholder="参赛口号（可不填）" /></p>
    <p class="row"><input name="remark" id="frm_remark" type="text" class="inptxt row-ele" value="" placeholder="备注，附加说明（可不填）" /></p>
    -->
    <p class="row tips">所有参赛选手请<em style="color:red;">务必</em>添加官方客服微信号：<br/><em style="color:green;font-size:16px;font-size:1.6rem;">choumeikufang</em>，以便随时关注赛事进程。</p>
    <p class="row"><input name="submit" type="submit" class="btn btn-purple" id="frm_submit" value="提 交" /></p>
    <p class="row">&nbsp;</p>
  </form>
</div>
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
var maxuploadnum = parseInt('<?=$maxuploadnum?>');
$(function(){
	document.getElementById('frm_upfile').onchange = function(e) {
		var files = e.target.files;
		var fr = new FileReader();
		fr.onload = function(ev) {
			var _h = '<div class="pv"><img src="'+ev.target.result+'" alt="" class="pvimg" onload="onload_pic(this)" /><p class="pvop"><a href="javascript:;" onclick="remove_pic(this)">删除</a></p></div>';
			$('#match-preview').append(_h);
		};
	  fr.readAsDataURL(files[0]);
	};
	
	$('#frm_matchjoin').bind('submit', function(){
		
		var post_data = {};
		post_data.nid = $('#frm_id').val();
		
		var _truename = $('#frm_truename');
		post_data.truename = _truename.val().trim();
		if(''==post_data.truename){
			alert('请输入真实姓名');
			_truename.val('').focus();
			return false;
		}

		var _mobile = $('#frm_mobile');
		post_data.mobile = _mobile.val().trim();
		if(''==post_data.mobile){
			alert('请输入手机号');
			_mobile.val('').focus();
			return false;
		}
		else if (!/^\d{11,14}$/.test(post_data.mobile)) {
			alert('手机号非法');
			_mobile.get(0).select();
			return false;
		}

		var _weixin = $('#frm_weixin');
		post_data.weixin = _weixin.val().trim();
		if(''==post_data.weixin){
			alert('请输入微信号');
			_weixin.val('').focus();
			return false;
		}
		
		post_data.province = $('#frm_province').val();
		post_data.city = $('#frm_city').val();
		post_data.idcard = $('#frm_idcard').val().trim();

		/*
		post_data.video = $('#frm_video').val().trim();
		post_data.slogan = $('#frm_slogan').val().trim();
		post_data.remark = $('#frm_remark').val().trim();
		if (''!==post_data.video && !/^http:\/\//i.test(post_data.video)) {
			$('#frm_video').get(0).select();
			alert('视频地址不合法');
			return false;
		}
		*/
		if ( ''!==post_data.idcard && post_data.idcard.length != 18 && post_data.idcard.length != 15 ) {
			$('#frm_idcard').get(0).select();
			alert('身份证号不合法');
			return false;
		}

		var imgs = new Array();
		$('#match-preview img.pvimg').each(function(){
			imgs.push($(this).attr('src'));
		});
		if (0===imgs.length) {
			alert('请至少上传一张图片');
			return false;
		}
		else if (imgs.length > maxuploadnum) {
			alert('最多只能上传'+maxuploadnum+'张图片，请删除'+(imgs.length-maxuploadnum)+'张再提交');
			return false;
		}
		post_data.imgs = imgs;

		var _btn = $('#frm_submit');
		_btn.val('图片上传审核中，请等待片刻...').attr('disabled',true);
		F.post($(this).attr('action'), post_data, function(ret){
			_btn.val('完成！').removeAttr('disabled');
			if(ret.flag=='SUC'){
				alert('恭喜您已报名成功！请“务必”关注大赛客服微信号：choumeikufang，以便随时了解获奖情况。');
				window.location.href = '<?php echo U('match/'.$nid)?>';
			}else{
				_btn.val('提 交');
				alert(ret.msg);
			}
		});

		return false;
	});
});
</script>

<?php endif;?>