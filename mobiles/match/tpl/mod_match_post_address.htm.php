<?php defined('IN_SIMPHP') or die('Access Denied');?>
<script type="text/html" id="addrtop-html">
<a href="javascript:;" onclick="return save_address(this)" class="rt">保存</a>
地址上传
<a href="javascript:;" onclick="return cancel_address(this)" class="lt">取消</a>
</script>
<div class="match-address-tit">您刚给<span><?=$player_info['truename']?></span>送了<em><?=$sendmoney?></em>花，请填写收货地址，以便收取<span><?=$player_info['truename']?></span>托平台发出的礼物，以表感谢！
<?php if(!empty($user_address)):?>
<div class="match-address-tip">您之前已上传收货地址，可直接点右上角“<em>保存</em>”...</div>
<?php endif;?>
</div>
<div class="match-join match-address">
	<form action="" method="post" onsubmit="return false">
	<ul>
		<li>
			<div class="c-10-2">姓　　名</div>
			<div class="c-10-8">
				<input name="consignee" id="frm_consignee" value="<?php echo (isset($user_address['consignee']) ? $user_address['consignee']: '')?>" placeholder="收货人姓名" />
				<input name="address_id" id="frm_id" type="hidden" value="<?php echo (isset($user_address['address_id']) ? $user_address['address_id']: '')?>"/>
				<input name="match_id" id="frm_match_id" type="hidden" value="<?=$match_id?>"/>
				<input name="player_id" id="frm_player_id" type="hidden" value="<?=$player_id?>"/>
				<input name="order_id" id="frm_order_id" type="hidden" value="<?=$order_id?>"/>
				<input name="sendmoney" id="frm_sendmoney" type="hidden" value="<?=$sendmoney?>"/>
				<input name="backurl" id="frm_backurl" type="hidden" value="<?=$backurl?>"/>
			</div>
		</li>
		<li><div class="c-10-2">手机号码</div><div class="c-10-8"><input name="mobile" id="frm_mobile" value="<?php echo (isset($user_address['mobile']) ? $user_address['mobile']: '')?>" placeholder="11位手机号" /></div></li>
		<li>
			<div class="c-10-2">选择地区</div>
			<div class="c-10-8">
				<select name="province" id="frm_province" class="inpsel row-ele" onchange="return change_province(this)">
      <option value="0">选择省份▼</option>
      <?php if(empty($user_address)):?>
<?php foreach ($province AS $it):?>
      <option value="<?=$it['locaid']?>"><?=$it['location']?></option>
<?php endforeach;?>
			<?php else:?>
<?php foreach ($province AS $it):?>
      <option value="<?=$it['locaid']?>" <?php echo($it['locaid']==$user_address['province']?'selected="selected"':'');?>><?=$it['location']?></option>
<?php endforeach;?>			
			<?php endif;?>
				</select>
				<select name="city" id="frm_city" class="inpsel row-ele" onchange="return change_city(this)">
      		<?=$city_html?>
    		</select>
			</div>
		</li>
		<li><div class="c-10-2">详细地址</div><div class="c-10-8"><input name="address" id="frm_address" value="<?php echo (isset($user_address['address']) ? $user_address['address']: '')?>" placeholder="县区、街道门牌信息" /></div></li>
		<li class="last"><div class="c-10-2">邮政编码</div><div class="c-10-8"><input name="zipcode" id="frm_zipcode" value="<?php echo (isset($user_address['zipcode']) ? $user_address['zipcode']: '')?>" placeholder="邮政编码（可选）" /></div></li>
	</ul>
	</form>
</div>
<script type="text/javascript">
$(function(){
	$('#form-top').html($('#addrtop-html').text());
});
function change_province(obj) {
	var pid = $(obj).val();
	var $city = $('#frm_city');
	var prehtml = '<option value="0">选择城市▼</option>';
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
		else {
			$city.html(prehtml);
		}
	});
	return false;
}
function change_city(obj) {
	return;
	var pid = $(obj).val();
	var $city = $('#frm_district');
	var prehtml = '<option value="0">选择县区▼</option>';
	pid = parseInt(pid);
	if (0===pid) {
		$city.html(prehtml);
		return;
	}
	F.getJSON("<?php echo U('match/districts')?>",{parent_id: pid,maxage: 0},function(ret){
		if (ret.flag=='SUC') {
			var html='';
			for(var i=0; i<ret.data.length; i++) {
				html += '<option value="'+ret.data[i]['locaid']+'">'+ret.data[i]['location']+'</option>';
			}
			$city.html(prehtml+html);
		}
		else {
			$city.html(prehtml);
		}
	});
	return false;
}
function cancel_address(obj) {
	if(confirm("取消意味着放弃这次获取礼品的机会，\n确定放弃？")) {
		var _back = "<?php echo U('match/'.$match_id);?>";
		var _backurl = $('#frm_backurl').val();
		if (''!=_backurl) _back = _backurl;
		window.location.href = _back;
	}
}
function save_address(obj) {
	var post_data = {};
	post_data.address_id = $('#frm_id').val();
	post_data.match_id   = $('#frm_match_id').val();
	post_data.player_id  = $('#frm_player_id').val();
	post_data.order_id   = $('#frm_order_id').val();
	post_data.sendmoney  = $('#frm_sendmoney').val();
	post_data.backurl    = $('#frm_backurl').val();
	
	var _consignee = $('#frm_consignee').val().trim();
	if (''==_consignee) {
		myAlert('姓名不能为空');
		return false;
	}
	post_data.consignee = _consignee;
	
	var _mobile = $('#frm_mobile').val().trim();
	if (''==_mobile) {
		myAlert('请填写真实手机号');
		return false;
	}
	else if (!/^\d{11,14}$/.test(_mobile)) {
		myAlert('手机号码不正确');
		return false;
	}
	post_data.mobile = _mobile;

	var _province = $('#frm_province').val();
	_province = parseInt(_province);
	if (!_province) {
		myAlert('请选择地址省份');
		return false;
	}
	post_data.province = _province;

	var _city = $('#frm_city').val();
	_city = parseInt(_city);
	if (!_city) {
		myAlert('请选择地址城市');
		return false;
	}
	post_data.city = _city;

	var _address = $('#frm_address').val().trim();
	if (''==_address) {
		myAlert('详细地址不能为空');
		return false;
	}
	post_data.address = _address;

	var _zipcode = $('#frm_zipcode').val().trim();
	if (''!=_zipcode && !/^\d{6}$/.test(_zipcode)) {
		myAlert('邮政编码不正确');
		return false;
	}
	post_data.zipcode = _zipcode;

	F.post("<?php echo U('match/post_address')?>", post_data, function(ret){
		if(ret.flag=='SUC'){
			var backurl = '<?php echo U('match/')?>'+ret.match_id;
			if (ret.backurl!='') {
				backurl = ret.backurl;
			}
			myAlert(ret.msg,function(gourl){
				window.location.href = gourl;
			},'','','',backurl);
		}else{
			myAlert(ret.msg);
		}
	});
	
	return false;
}
</script>