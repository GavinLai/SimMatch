<?php defined('IN_SIMPHP') or die('Access Denied');?>
<!-- for alert dialog -->
<div class="no-bounce" id="popalert-bg"></div>
<div class="no-bounce" id="popalert">
	<div class="top">
		<h1 class="alert-tit">提示</h1>
		<div class="alert-cont" id="alertcont"></div>
	</div>
	<div class="line"></div>
	<div class="btm">
		<button id="alertok">好的</button>
	</div>
</div>
<script>
function myAlert(msg, okcall) {
	if (typeof (myAlert._popbg)=='undefined') myAlert._popbg = $('#popalert-bg');
	if (typeof (myAlert._popdlg)=='undefined') myAlert._popdlg = $('#popalert');
	if (typeof (myAlert._cont)=='undefined') myAlert._cont = $('#alertcont');
	if (typeof (myAlert._wrap)=='undefined') myAlert._wrap = $('#popalert');
	if (typeof (myAlert._rtwrap)=='undefined') myAlert._rtwrap = $('#rtWrap');
	myAlert._okcall = okcall;
	myAlert._okargs = new Array();
	if (arguments.length > 2) {
		for (var i = 2; i < arguments.length; i++) {
			myAlert._okargs.push(arguments[i]);
	  }
	}
	myAlert._cont.html(msg);
	var _h = myAlert._wrap.height();
	var _t = parseInt((myAlert._rtwrap.height()-_h)/2) - 30;
	myAlert._popdlg.css('top',_t+'px');
	myAlert._popbg.show();
	myAlert._popdlg.show();
	return;
}
function _hideAlert() {
	myAlert._popdlg.hide();
	myAlert._popbg.hide();
}
$(function(){
	$('#popalert-bg,#popalert').bind('touchmove',function(e){
		e.preventDefault();
	});
	$('#alertok').bind('click',function(){
		_hideAlert();
		if (typeof(myAlert._okcall)=='function') {
			myAlert._okcall.apply(myAlert,myAlert._okargs);
		}
	});
});
</script>