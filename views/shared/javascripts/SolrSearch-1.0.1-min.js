jQuery(document).ready(function(){jQuery(".solr_facets .facet").addClass("clicker").click(function(){return jQuery(this).toggleClass("active"),jQuery(this).next().toggle(),!1}).next().hide()}),function(a,b,c){b.infinitescroll=function(c,d,e){this.element=b(e),this._create(c,d)||(this.failed=!0)},b.infinitescroll.defaults={loading:{finished:c,finishedMsg:"<em>Congratulations, you've reached the end of the internet.</em>",img:"http://www.infinite-scroll.com/loading.gif",msg:null,msgText:"<em>Loading the next set of posts...</em>",selector:null,speed:"fast",start:c},state:{isDuringAjax:!1,isInvalidPage:!1,isDestroyed:!1,isDone:!1,isPaused:!1,currPage:1},callback:c,debug:!1,behavior:c,binder:b(a),nextSelector:"div.navigation a:first",navSelector:"div.navigation",contentSelector:null,extraScrollPx:150,itemSelector:"div.post",animate:!1,pathParse:c,dataType:"html",appendCallback:!0,bufferPx:40,errorCallback:function(){},infid:0,pixelsFromNavToBottom:c,path:c},b.infinitescroll.prototype={_binding:function(b){var d=this,e=d.options;e.v="2.0b2.111027";if(!!e.behavior&&this["_binding_"+e.behavior]!==c){this["_binding_"+e.behavior].call(this);return}if(b!=="bind"&&b!=="unbind")return this._debug("Binding value  "+b+" not valid"),!1;b=="unbind"?this.options.binder.unbind("smartscroll.infscr."+d.options.infid):this.options.binder[b]("smartscroll.infscr."+d.options.infid,function(){d.scroll()}),this._debug("Binding",b)},_create:function(d,e){var f=b.extend(!0,{},b.infinitescroll.defaults,d);if(!this._validate(d))return!1;this.options=f;var g=b(f.nextSelector).attr("href");return g?(f.path=this._determinepath(g),f.contentSelector=f.contentSelector||this.element,f.loading.selector=f.loading.selector||f.contentSelector,f.loading.msg=b('<div id="infscr-loading"><img alt="Loading..." src="'+f.loading.img+'" /><div>'+f.loading.msgText+"</div></div>"),(new Image).src=f.loading.img,f.pixelsFromNavToBottom=b(document).height()-b(f.navSelector).offset().top,f.loading.start=f.loading.start||function(){b(f.navSelector).hide(),f.loading.msg.appendTo(f.loading.selector).show(f.loading.speed,function(){beginAjax(f)})},f.loading.finished=f.loading.finished||function(){f.loading.msg.fadeOut("normal")},f.callback=function(a,d){!!f.behavior&&a["_callback_"+f.behavior]!==c&&a["_callback_"+f.behavior].call(b(f.contentSelector)[0],d),e&&e.call(b(f.contentSelector)[0],d,f)},this._setup(),!0):(this._debug("Navigation selector not found"),!1)},_debug:function(){if(this.options&&this.options.debug)return a.console&&console.log.call(console,arguments)},_determinepath:function(b){var d=this.options;if(!d.behavior||this["_determinepath_"+d.behavior]===c){if(!d.pathParse){if(b.match(/^(.*?)\b2\b(.*?$)/))b=b.match(/^(.*?)\b2\b(.*?$)/).slice(1);else if(b.match(/^(.*?)2(.*?$)/)){if(b.match(/^(.*?page=)2(\/.*|$)/))return b=b.match(/^(.*?page=)2(\/.*|$)/).slice(1),b;b=b.match(/^(.*?)2(.*?$)/).slice(1)}else{if(b.match(/^(.*?page=)1(\/.*|$)/))return b=b.match(/^(.*?page=)1(\/.*|$)/).slice(1),b;this._debug("Sorry, we couldn't parse your Next (Previous Posts) URL. Verify your the css selector points to the correct A tag. If you still get this error: yell, scream, and kindly ask for help at infinite-scroll.com."),d.state.isInvalidPage=!0}return this._debug("determinePath",b),b}return this._debug("pathParse manual"),d.pathParse(b,this.options.state.currPage+1)}this["_determinepath_"+d.behavior].call(this,b);return},_error:function(b){var d=this.options;if(!!d.behavior&&this["_error_"+d.behavior]!==c){this["_error_"+d.behavior].call(this,b);return}b!=="destroy"&&b!=="end"&&(b="unknown"),this._debug("Error",b),b=="end"&&this._showdonemsg(),d.state.isDone=!0,d.state.currPage=1,d.state.isPaused=!1,this._binding("unbind")},_loadcallback:function(e,f){var g=this.options,h=this.options.callback,i=g.state.isDone?"done":g.appendCallback?"append":"no-append",j;if(!!g.behavior&&this["_loadcallback_"+g.behavior]!==c){this["_loadcallback_"+g.behavior].call(this,e,f);return}switch(i){case"done":return this._showdonemsg(),!1;case"no-append":g.dataType=="html"&&(f="<div>"+f+"</div>",f=b(f).find(g.itemSelector));break;case"append":var k=e.children();if(k.length==0)return this._error("end");j=document.createDocumentFragment();while(e[0].firstChild)j.appendChild(e[0].firstChild);this._debug("contentSelector",b(g.contentSelector)[0]),b(g.contentSelector)[0].appendChild(j),f=k.get()}g.loading.finished.call(b(g.contentSelector)[0],g);if(g.animate){var l=b(a).scrollTop()+b("#infscr-loading").height()+g.extraScrollPx+"px";b("html,body").animate({scrollTop:l},800,function(){g.state.isDuringAjax=!1})}g.animate||(g.state.isDuringAjax=!1),h(this,f)},_nearbottom:function(){var e=this.options,f=0+b(document).height()-e.binder.scrollTop()-b(a).height();return!e.behavior||this["_nearbottom_"+e.behavior]===c?(this._debug("math:",f,e.pixelsFromNavToBottom),f-e.bufferPx<e.pixelsFromNavToBottom):this["_nearbottom_"+e.behavior].call(this)},_pausing:function(b){var d=this.options;if(!d.behavior||this["_pausing_"+d.behavior]===c){b!=="pause"&&b!=="resume"&&b!==null&&this._debug("Invalid argument. Toggling pause value instead"),b=!b||b!="pause"&&b!="resume"?"toggle":b;switch(b){case"pause":d.state.isPaused=!0;break;case"resume":d.state.isPaused=!1;break;case"toggle":d.state.isPaused=!d.state.isPaused}return this._debug("Paused",d.state.isPaused),!1}this["_pausing_"+d.behavior].call(this,b);return},_setup:function(){var b=this.options;if(!b.behavior||this["_setup_"+b.behavior]===c)return this._binding("bind"),!1;this["_setup_"+b.behavior].call(this);return},_showdonemsg:function(){var d=this.options;if(!!d.behavior&&this["_showdonemsg_"+d.behavior]!==c){this["_showdonemsg_"+d.behavior].call(this);return}d.loading.msg.find("img").hide().parent().find("div").html(d.loading.finishedMsg).animate({opacity:1},2e3,function(){b(this).parent().fadeOut("normal")}),d.errorCallback.call(b(d.contentSelector)[0],"done")},_validate:function(c){for(var d in c)if(d.indexOf&&d.indexOf("Selector")>-1&&b(c[d]).length===0)return this._debug("Your "+d+" found no elements."),!1;return!0},bind:function(){this._binding("bind")},destroy:function(){return this.options.state.isDestroyed=!0,this._error("destroy")},pause:function(){this._pausing("pause")},resume:function(){this._pausing("resume")},retrieve:function(d){var e=this,f=e.options,g=f.path,h,i,j,k,l,d=d||null,m=d?d:f.state.currPage;beginAjax=function(c){c.state.currPage++,e._debug("heading into ajax",g),h=b(c.contentSelector).is("table")?b("<tbody/>"):b("<div/>"),j=g.join(c.state.currPage),k=c.dataType=="html"||c.dataType=="json"?c.dataType:"html+callback",c.appendCallback&&c.dataType=="html"&&(k+="+callback");switch(k){case"html+callback":e._debug("Using HTML via .load() method"),h.load(j+" "+c.itemSelector,null,function(b){e._loadcallback(h,b)});break;case"html":case"json":e._debug("Using "+k.toUpperCase()+" via $.ajax() method"),b.ajax({url:j,dataType:c.dataType,complete:function(b,c){l=typeof b.isResolved!="undefined"?b.isResolved():c==="success"||c==="notmodified",l?e._loadcallback(h,b.responseText):e._error("end")}})}};if(!!f.behavior&&this["retrieve_"+f.behavior]!==c){this["retrieve_"+f.behavior].call(this,d);return}if(f.state.isDestroyed)return this._debug("Instance is destroyed"),!1;f.state.isDuringAjax=!0,f.loading.start.call(b(f.contentSelector)[0],f)},scroll:function(){var b=this.options,d=b.state;if(!!b.behavior&&this["scroll_"+b.behavior]!==c){this["scroll_"+b.behavior].call(this);return}if(d.isDuringAjax||d.isInvalidPage||d.isDone||d.isDestroyed||d.isPaused)return;if(!this._nearbottom())return;this.retrieve()},toggle:function(){this._pausing()},unbind:function(){this._binding("unbind")},update:function(c){b.isPlainObject(c)&&(this.options=b.extend(!0,this.options,c))}},b.fn.infinitescroll=function(c,d){var e=typeof c;switch(e){case"string":var f=Array.prototype.slice.call(arguments,1);this.each(function(){var a=b.data(this,"infinitescroll");if(!a)return!1;if(!b.isFunction(a[c])||c.charAt(0)==="_")return!1;a[c].apply(a,f)});break;case"object":this.each(function(){var a=b.data(this,"infinitescroll");a?a.update(c):(a=new b.infinitescroll(c,d,this),a.failed||b.data(this,"infinitescroll",a))})}return this};var d=b.event,e;d.special.smartscroll={setup:function(){b(this).bind("scroll",d.special.smartscroll.handler)},teardown:function(){b(this).unbind("scroll",d.special.smartscroll.handler)},handler:function(a,c){var d=this,f=arguments;a.type="smartscroll",e&&clearTimeout(e),e=setTimeout(function(){b.event.handle.apply(d,f)},c==="execAsap"?0:100)}},b.fn.smartscroll=function(a){return a?this.bind("smartscroll",a):this.trigger("smartscroll",["execAsap"])}}(window,jQuery),jQuery(document).ready(function(){jQuery("#solr-nav").hide()}),jQuery(function(a){var b=a("#results");b.infinitescroll({animate:!0,nextSelector:"#solr-nav a.next",navSelector:"#solr-nav",itemSelector:".item",loading:{msgText:"<p><em>Loading next set of items...</em></p>",finishedMsg:"<p><em>You have reached the end of the results.</em></p>"}})})