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
    <div class="r r3"><?php if($goods_type=='kiss'):?>每一个吻可抵两票！<?php else: ?>每一束花可抵两票！<?php endif;?></div>
  </div>
</div>

<div class="box-topay"><button class="btn btn-block btn-blue" id="btn-wxpay" data-payid="2">确定赠送</button></div>

</div>

<script>
var goods_type = '<?=$goods_type?>';
var amount_start = '<?=$amount_start?>';
amount_start = ''==amount_start ? 0 : parseInt(amount_start);

//调用微信JS api 支付
function jsApiCall(jsApiParams, back_url)
{
	WeixinJSBridge.invoke(
		'getBrandWCPayRequest',
		jsApiParams,
		function (res){
	  	if ("get_brand_wcpay_request:ok" == res.err_msg) {
	  	  alert('支付成功');
	  	}
	  	else if ("get_brand_wcpay_request:cancel" == res.err_msg) {
	  	  alert('取消支付');
	  	}
	  	else {
	  	  alert('支付失败！');
	  	}
	  	if (typeof(back_url)!='undefined' && ''!=back_url) window.location.href = back_url;
	  	else {
	  	  setTimeout(function(){ WeixinJSBridge.invoke("closeWindow"); },1000);
	  	}
		}
	);
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
			alert('亲，好闺蜜好男友应该大气一点，'+amount_start+'个起送噢~');
			return;
		}

		alert('微信支付稍候接入，请稍等待');
		return;

		var _this = this;
		$(this).text('支付中...').attr('disabled',true);
		F.post('<?php echo U('trade/order/submit')?>',{"goods_type":goods_type,"amount":amount},function(ret){
  			if (ret.flag=='SUC') {
  				jsApiCall(ret.js_api_params, '/');
  			}
  			else{
  				$(_this).text('确定赠送').removeAttr('disabled');
  				alert(ret.msg);
  			}
	  });
		
		return false;
	});
});
</script>

<?php endif;?>