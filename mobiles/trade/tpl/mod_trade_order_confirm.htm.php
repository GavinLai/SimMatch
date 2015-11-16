<?php defined('IN_SIMPHP') or die('Access Denied');?>

<?php if (''!=$errmsg):?>

<div class="list-empty">
  <h1 class="list-empty-header"><?=$errmsg?></h1>
</div>

<?php else :?>

<div class="match-topay">

<div class="box-payamount">
  <h1><?php if($goods_type=='kiss'):?>爱我就狂吻我<?php else: ?>喜欢我就送我花<?php endif;?></h1>
  <div class="frmbox">
    <div class="r r1">输入赠送的数量：<input type="text" name="amount" value="<?=$amount_start?>" id="amount"/> <?php if($goods_type=='kiss'):?>吻<?php else: ?>花<?php endif;?></div>
    <div class="r r2">支付金额：<em id="money"><?=$amount_start?></em> 元</div>
    <div class="r r3"><?php if($goods_type=='kiss'):?>每 1 个吻可抵 2 票！<?php else: ?>每 1 束花可抵 2 票！<?php endif;?></div>
    <div class="r r4"><em><?=$animate_num?></em> <?php if($goods_type=='kiss'):?>吻<?php else: ?>花<?php endif;?>起送有意外效果哦~~</div>
  </div>
</div>

<div class="box-topay"><button class="btn btn-block" id="btn-wxpay" data-payid="2">确定赠送</button></div>

</div>

<script>
var goods_type = '<?=$goods_type?>';
var amount_start = myParseInt('<?=$amount_start?>');
var player_id = myParseInt('<?=$player_id?>');

//调用微信JS api 支付
function jsApiCall(jsApiParams, back_url)
{
	if (typeof(WeixinJSBridge)!='undefined') {
		WeixinJSBridge.invoke(
				'getBrandWCPayRequest',
				jsApiParams,
				function (res){
					  var _msg = '';
				  	if ("get_brand_wcpay_request:ok" == res.err_msg) {
				  		_msg = '您已送花成功！请关注大赛微信公众平台帐号：<em style="color:green">FEOfeel</em>，随时关注比赛动态。';
				  		F.post('<?php echo U('trade/order/payok')?>',{"player_id":player_id,"amount":$('#amount').val().trim()});
				  	}
				  	else if ("get_brand_wcpay_request:cancel" == res.err_msg) {
				  		_msg = '您已取消支付';
				  	}
				  	else {
				  		_msg = '支付失败！';
				  	}
				  	myAlert(_msg,function(backurl){
					  	if (typeof(backurl)!='undefined' && ''!=backurl) window.location.href = backurl;
					  	else setTimeout(function(){ WeixinJSBridge.invoke("closeWindow"); },1000);
					  },back_url);
				}
			);
	}
}

$(function(){
	$('#amount').blur(function(){
		var amount = $(this).val().trim();
		amount = ''==amount ? 0 : amount;
		$('#money').text(amount);
	});
	$('#btn-wxpay').click(function(){
		var amount = $('#amount').val().trim();
		amount = ''==amount ? 0 : parseInt(amount);

		$('#money').text(amount);
		if (amount < amount_start) {
			myAlert('亲，好闺蜜亲男友应该大气一点，'+amount_start+'个起送哦~');
			return;
		}

		var _this = this;
		$(this).text('支付中...').attr('disabled',true);
		F.post('<?php echo U('trade/order/submit')?>',{"player_id":player_id,"goods_type":goods_type,"amount":amount},function(ret){
  			if (ret.flag=='SUC') {
  				jsApiCall(ret.js_api_params, '<?php echo U('player/'.$player_id)?>');
  			}
  			else{
  				$(_this).text('确定赠送').removeAttr('disabled');
  				myAlert(ret.msg);
  			}
	  });
		
		return false;
	});
});
</script>

<?php endif;?>