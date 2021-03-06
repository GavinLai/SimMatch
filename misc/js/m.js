/*!
 * mobile common js
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
(function( $, F, w, UNDEF ) {
	
	F.isTouch = "createTouch" in document;
	//gData.downpull_display = true; //允许下拉显示标志位，默认允许，页面可改变此变量以改变其默认行为 (不能在这个位置设置该值，放这里是提醒有这么一个全局变量)
	//gData.page_render_mode = 2; //1: general一般请求页面；2: hash请求页面 (不能在这个位置设置该值，放这里是提醒有这么一个全局变量)
	
	// Set dom constants
	F.doms = {wrapper:"#rtWrap",activepage:"#activePage",nav:"#nav-1",scroller:".scrollArea",loading:"#loadingCanvas"};
	
	// Cache doms
	F.pageactive = $(F.doms.activepage);
	F.scrollarea = $('>'+F.doms.scroller,F.pageactive);
	F.pagebg     = $('>.pageBg',F.pageactive);
	
	// Scroll cookie initialization
	F.scroll2old = false;
	F.scrollYold = 0; // 记录上一个滚动位置
	F.scrollDirection = 0; // <0:向上滚动(滚动条下移); >0:向下滚动(滚动条上移); 0: 位置不变
	F.scroll_cookie_key = function(){
		return 'LS'+gData.currURI.replace(/(\/|\?|\&|\=|\%|\-)/g,'_');
	};
	
	// Loading effect
	F.loading_icons  = {};
	F.loadingStart = function(effect) {
		if (typeof(effect)=='undefined') effect = 'overlay'; //optional effect value: 'switch','overlay','pure'
		if (typeof(F.loading_canvas)=='undefined') F.loading_canvas = $(F.doms.loading);
		var opacity = 1;
		switch (effect) {
		case 'switch':
			F.pageactive.hide();
			break;
		case 'overlay':
			opacity = .75;
			break;
		case 'pure':
			opacity = 0;
			break;
		}
		F.loading_canvas.css('opacity',opacity).show();
		if (this.loading_icons[effect] == UNDEF) {
			var opts = {
					lines: 12, // The number of lines to draw
					length: 6, // The length of each line
					width: 2, // The line thickness
					radius: 6, // The radius of the inner circle
					corners: 1, // Corner roundness (0..1)
					rotate: 0, // The rotation offset
					direction: 1, // 1: clockwise, -1: counterclockwise
					color: '#000', // #rgb or #rrggbb or array of colors
					speed: 1, // Rounds per second
					trail: 60, // Afterglow percentage
					shadow: false, // Whether to render a shadow
					hwaccel: false, // Whether to use hardware acceleration
					className: 'spinner', // The CSS class to assign to the spinner
					zIndex: 2e9, // The z-index (defaults to 2000000000)
					top: '50%', // Top position relative to parent in px
					left: '50%' // Left position relative to parent in px
				};
			F.loading_icons[effect] = new Spinner(opts);
			F.loading_icons[effect].spin(F.loading_canvas.get(0));
		}
		else {
			F.loading_icons[effect].spin(F.loading_canvas.get(0));
		}
	};
	F.loadingStop = function(effect) {
		if (typeof(effect)=='undefined') effect = 'overlay';
		F.loading_icons[effect].stop();
		F.loading_canvas.hide();
		switch (effect) {
		case 'switch':
			F.pageactive.show();
			break;
		case 'overlay':
			break;
		case 'pure':
			break;
		}
	};
	// Util functions
	F.parseInt = function(val){
		return (undefined==val || ''==val.trim()) ? 0 : parseInt(val);
	};
	F.parseFloat = function(val){
		return (undefined==val || ''==val.trim()) ? 0 : parseInt(val);
	};
	// set content minimal height
	F.set_content_minheight = function(){
		if (typeof F.pagenav_height == 'undefined' || !F.pagenav_height) {
			F.pagenav_height = $(F.doms.nav).height();
		}
		if (typeof F.pageactive == 'undefined') {
			F.pageactive = $(F.doms.activepage);
		}
		var _bh=$(document).height()-F.pagenav_height;
		if (F.scrollarea.size()>0) {
			F.scrollarea.css({minHeight:_bh+'px'});
		}
	};
	// set iScroll object
	F.set_scroller = function(toY, runTimeout){
		if (typeof(toY)=='undefined') toY = false;
		if (typeof(runTimeout)=='undefined') runTimeout = 0;
		
		if (typeof(F.set_scroller.timer)=='number') {//避免连续的set_scroller被多次执行
			clearTimeout(F.set_scroller.timer);
			F.set_scroller.timer = UNDEF;
		}
		F.set_scroller.timer = setTimeout(function(){
			if(typeof(F.oIScroll)!='object') {
				F.oIScroll = new IScroll(F.doms.activepage,{probeType:2,mouseWheel:true,scrollbars:true,fadeScrollbars:true,momentum:true});
				F.oIScroll.on('beforeScrollStart',F._beforeScrollStart);
				F.oIScroll.on('scrollCancel',F._scrollCancel);
				F.oIScroll.on('scrollStart',F._scrollStart);
				F.oIScroll.on('scroll',F._scrolling);
				F.oIScroll.on('scrollEnd',F._scrollEnd);
				F.oIScroll.on('flick',F._flick);
			}else{
				F.oIScroll.refresh();
			}
			if (typeof(toY)=='boolean') {
				if(true===toY) { // is scroll to top
					F.oIScroll.scrollTo(0,0,1000);
				}
			}
			else {
				toY = parseInt(toY);
				F.oIScroll.scrollTo(0,toY);
			}
			F.set_scroller.timer = UNDEF;
		},runTimeout);
	};
	//outcall: F.onBeforeScrollStart
	F._beforeScrollStart = function() {
		F.event.execEvent('beforeScrollStart',this);
	};
	//outcall: F.onScrollCancel
	F._scrollCancel = function() {
		F.event.execEvent('scrollCancel',this);
	};
	//outcall: F.onScrollStart
	F._scrollStart = function() {
		F.event.flag.downpull = (0==this.y ? true : false);
		if (typeof(F.event.flag.showbg)=='undefined') {
			F.event.flag.showbg = false;
		}
		F.event.execEvent('scrollStart',this);
	};
	//outcall: F.onScrolling
	F._scrolling = function() {
		F.scrollDirection = this.y - F.scrollYold;
		F.scrollYold = this.y;
		
		var dp_type = 'downPull';
		var can_dpshow = (typeof(gData.downpull_display)=='undefined' || gData.downpull_display) ? true : false;
		if (this.y > 20) {
			
			if (can_dpshow && !F.event.flag.showbg) {
				F.event.flag.showbg = true;
				F.pagebg.show();
			}
			
			if(this.y > 50 && F.event.flag.downpull) {
				F.event.flag.downpull = false; //保证仅触发一次
				if ((typeof(F.event._events[dp_type])=='object') && F.event._events[dp_type].length>0) {
					F.event.execEvent(dp_type,this);
				}
			}
		}
		else {
			if (F.event.flag.showbg) {
				F.event.flag.showbg = false;
				F.pagebg.hide();
			}
		}
		F.event.execEvent('scrolling',this);
	};
	//outcall: F.onScrollEnd
	F._scrollEnd = function() {
		F.event.execEvent('scrollEnd',this);
	};
	F._flick = function() {
		F.event.execEvent('flick',this);
	};
	//事件挂载
	F.onBeforeScrollStart = function(fn) {
		F.event.on('beforeScrollStart',fn);
	};
	F.onScrollCancel = function(fn) {
		F.event.on('scrollCancel',fn);
	};
	F.onScrollStart = function(fn) {
		F.event.on('scrollStart',fn);
	};
	F.onScrolling = function(fn) {
		F.event.on('scrolling',fn);
	};
	F.onScrollEnd = function(fn) {
		F.event.on('scrollEnd',fn);
	};
	F.onFlick = function(fn) {
		F.event.on('flick',fn);
	};
	F.onScrollDownPull = function(fn) {
		F.event.on('downPull',fn);
	};
	
	//img等dom onload 事件
	F.onDocLoad = function(fn) {
		F.event.on('docLoad',fn);
	};
	F._onDocLoad= function(wrap) {
		if (typeof(wrap)=='undefined') wrap = 'body';
		if (typeof(wrap)=='string') wrap = $(wrap);
		var oThis = this;
		(function(){
			var s = $('img[data-loaded=0]',wrap).size();
			if (0===s) {
				F.event.execEvent('docLoad',oThis);
			}else{
				setTimeout(arguments.callee,100);
			}
		})();
	};
	//ajax document ready
	F._onAjaxDocReady= function(wrap) {
		if (typeof(wrap)=='undefined') wrap = F.scrollarea;
		$('img',wrap).attr('data-loaded',0).load(function(){ $(this).attr('data-loaded',1); });
		F.set_scroller(false,100);
	};
	
	//获取内容包裹元素
	F.getContainerEle = function() {
		var $_c = F.scrollarea;
		if ($_c.size()==0) $_c = F.pageactive;
		return $_c;
	};
	
	//附加到末尾的script
	F.renderAppend = function() {
		return '<script type="text/javascript">F.onDocLoad(function(){F.set_scroller(!F.scroll2old?false:Cookies.get(F.scroll_cookie_key()),100)});$(function(){var c = F.getContainerEle();F._onAjaxDocReady(c);F._onDocLoad(c)});</script>';
	};
	
	// Page functions
	// hash请求
	w.go_hashreq = function(hash, maxage, options) {
		F.loadingStart('switch');
		
		var data = {};
		if (maxage) {
			data.maxage = parseInt(maxage);
		}
		if (typeof options == 'undefined') {
			options = {};
		}
		
		options = $.extend({
			container: F.getContainerEle(),
			renderPrepend: '<script type="text/javascript">F.event.reset();</script>'
		},options);
		
		var _effect = 'none';
		if (typeof (options.effect)!='undefined') {
			_effect = options.effect;
		}
		
		F.hashLoad(hash,data,function(ret){
			var _ct = F.scrollarea;
			var toPreClass = 'ui-page-pre-in';
			var toClass = 'slide in';
			if (_effect=='slide_right_in') {
				_ct.addClass(toPreClass);
				_ct.animationComplete(function(){
					_ct.removeClass(toClass);
				});
			}
			F.loadingStop('switch');
			if (_effect=='slide_right_in') {
				_ct.removeClass( toPreClass ).addClass( toClass );
			}
			F.set_scroller(true,500);
			
		},options);
		
		return false;
	};
	
	// 一般ajax请求
	w.go_ajaxreq = function(gouri) {
		if (''==gouri) gouri = '/';
		F.loadingStart('switch');
		F.getJSON(gouri, {maxage:0,_hr:1}, function(ret){
			if (ret.flag=='SUC') {
				ret.body += F.renderAppend();
				F.getContainerEle().html(ret.body);
			}
			F.loadingStop('switch');
		});
	};
	
	// On document ready
	$(function(){
		
		// Bind window.resize event
		setTimeout(function(){
			//F.set_content_minheight();
			FastClick.attach(w.document.body);
		},100);

		// Prevent default scroll action
		w.document.addEventListener('touchmove', function (e) {
			//e.preventDefault();
		}, false);
		
		// Bind window.onhashchange event
		//$(w).hashchange(function(){w.go_hashreq();});
		
		// Hash trigger
		//var init_hash = F.getHash();
		//if (!init_hash) {w.go_hashreq(null,null,{changeHash:false});}
		//else {$(w).hashchange();}
		
		// Req page ajax
		if (typeof(gData.page_render_mode)=='undefined' || gData.page_render_mode==2) {
			w.go_ajaxreq(gData.currURI);
		}
		
	});
	
})(jQuery, FUI, this);

/***** Util Functions *****/

//显示和隐藏弹出框
;function show_popdlg(title,content) {
  var me = show_popdlg;
  if (typeof me._wrap == 'undefined') {
    me._wrap = $('#popdlg');
  }
  me._wrap.find('.poptit .txt').html(title);
  me._wrap.find('.popcont').html(content);
	
  var inPreClass = 'ui-page-pre-in',
	     inClass = 'slideup in';
  me._wrap.addClass(inPreClass).show();
  me._wrap.animationComplete(function(){
    me._wrap.removeClass(inClass);
  });
  me._wrap.removeClass(inPreClass).addClass(inClass);
}
;function hide_popdlg(callback) {
  var outClass = 'slideup out reverse';
  show_popdlg._wrap.animationComplete(function(){
    show_popdlg._wrap.removeClass(outClass).hide();
    var to = typeof callback;
    if (to!='undefined') {
    	if (to=='function') {
    		callback();
    	}
    	else { //是一个dom元素
    		if ($('#topnav-btn-filter').size()>0) {
    			$('#topnav-btn-filter').attr('rel',1).find('.triangle').removeClass('triangle-up');
    		}
    	}
    }
  });
  show_popdlg._wrap.addClass(outClass);
}

//主导航显示和隐藏
;function nav_show(nav_no, nav, nav_second) {
	if (nav_no===undefined) nav_no = 1;
	
	var $thenav = $('#nav-'+nav_no);
	$('a', $thenav).removeClass('cur');
	$('a[rel='+nav+']', $thenav).addClass('cur');
	$('.nav').hide();
	$thenav.show();

	nav_no = parseInt(nav_no);
	F.pagenav_height = $thenav.height();
	
	switch (nav_no) {
	case 1:
		break;
	case 2:
		break;
	case 3:
		break;
	case 4:
		break;
	}
	
	return false;
}
;function nav_hide(nav_no) {
	if (nav_no===undefined) no = 0;
	nav_no = parseInt(nav_no);
	if (0===nav_no) $('.nav').hide();
	else $('#nav-'+nav_no).hide();
	return false;
}

//查看更多内容
;function see_more(_self, callback) {
    var page = $(_self).attr('data-next-page');
    var total_page = $(_self).attr('data-total-page');
    page = parseInt(page);
    total_page = parseInt(total_page);
    if(page>total_page){
      return false;
    }
    var hash = location.hash;
    var connector = hash.indexOf(',')!=-1 ? '&':',';
    hash += connector+'p='+page;
    F.loadingStart();
    F.hashReq(hash,{},function(data){
    	
      F.loadingStop();
      callback(data.body+F.renderAppend());
      $(_self).attr('data-next-page', ++page);
      if(page>total_page){
        $(_self).hide();
      }
      
    },{changeHash:false});
}






