/*
* jQuery Glisse plugin
* v1.1.1
* ---
* @author: Victor
* @authorurl: http://victorcoulon.fr
* @twitter: http://twitter.com/_victa
*
* Based on jQuery Plugin Boilerplate 1.3
*
*/
(function ($, document) {
    $.glisse = function (element, options) {

        var plugin = this,
            $element = $(element),
            defaults = {
                dataName: 'data-glisse-big',
                speed: 300,
                changeSpeed: 1000,
                effect: 'bounce',
                mobile: false,
                fullscreen: false,
                disablindRightClick: false,
                copyright: '',
                showDownloadLink: true,
                showMaxLink: true,
                strDownload: '',
                strMax: '',
                strNext: '',
                strPrev: '',
                parent: null // jQuery selector to find the container
            },
            // Private var
            pictureUrl,
            group,
            isChange = false,
            mobile = {},
            touch = {},
            cache = [],
            controlsDefaultHeight = -1,
            getFullUrl = function ($el) {
                return $el.attr(plugin.settings.dataName) || $el.attr('src');
            };

        plugin.settings = {};
        plugin.els = {};

        plugin.init = function () {
            plugin.settings = $.extend({}, defaults, options);

            // Set vars
            group = $element.attr('rel') || null;
            plugin.settings.mobile = !!navigator.userAgent.match(/iPhone|iPod|iPad|Android/i);            
            
            // Set events
            $element.on('click', function () {
                pictureUrl = getFullUrl($element);

                createElements();
                setChangeStyle();
                addImage(pictureUrl);
                setChangeStatus();
                setTitle();
                /* blacknight - add - setDownload */
                setDownload();
                setShowMax();
                preloadImgs();

                // Bind Keyboard events
                $(document).keydown(function(event) {
                    if(event.keyCode.toString() === '27'){ closeLightbox(); }
                    if(event.keyCode.toString() === '39'){ changePicture('next'); }
                    if(event.keyCode.toString() === '37'){ changePicture('prev'); }
                });

                if(plugin.settings.disablindRightClick){
                    plugin.els['content'].on('contextmenu', function(e){
                        return false;
                    });
                }
                // ==== Mobile support =================
                if(plugin.settings.mobile){
                    mobile = {
                        touching: false,
                        nx: 0,
                        oX:0, // Original X-coordinate
                        scrollX: null
                    };

                    document.ontouchmove = document.ontouchstart = document.ontouchend = touchHandler;
                }
            });
           
        };

        var preloadImgs = function preloadImgs(){
            var current, image_urls = [], i;

            $('img[rel="'+group+'"]').each(function(i,el){
                image_urls.push(getFullUrl($(this)));
            });
            function loaded(current){
                cache.push(current);
            }
            for (i = 0; i < image_urls.length; i += 1) {
                current = jQuery("<img>").attr("src", image_urls[i]);
                current.load(loaded(image_urls[i]));
            }
        };

        var createElements = function createElements() {
            $element.addClass('active');

            var cssProp = getPrefix('transition')+'transition',
                cssVal = 'opacity '+plugin.settings.speed+'ms ease, '+getPrefix('transform')+'transform '+plugin.settings.speed+'ms ease';

            // Create Glisse HTML structure
            plugin.els['wrapper']       = $(document.createElement('div')).attr('id','glisse-wrapper');
            plugin.els['overlay']       = $(document.createElement('div')).attr('id','glisse-overlay').css(cssProp, cssVal);
            plugin.els['spinner']       = $(document.createElement('div')).attr('id','glisse-spinner');
            plugin.els['close']         = $(document.createElement('span')).attr('id','glisse-close').css(cssProp, cssVal);
            plugin.els['content']       = $(document.createElement('div')).attr('id','glisse-overlay-content').css(cssProp, cssVal)
                                            .css(getPrefix('transform')+'transform', 'scale(0)');
            plugin.els['controls']      = $(document.createElement('div')).attr('id','glisse-controls').css(cssProp, cssVal);
            //plugin.els['controlNext']   = $(document.createElement('span')).attr('class','glisse-next')
            //                                .append( $(document.createElement('a')).html('&#62;').attr("href", "#"));
            plugin.els['controlNext']   = $(document.createElement('span')).attr('class','glisse-next')
            								.append( $(document.createElement('a')).attr("href", "#")  
            										.attr("title",plugin.settings.strNext)
            										.append( $(document.createElement('i')).attr("class", "fa fa-chevron-right")));
            plugin.els['controlLegend'] = $(document.createElement('span')).attr('class','glisse-legend');
            plugin.els['controlPrev']   = $(document.createElement('span')).attr('class','glisse-prev')
                                            .append($(document.createElement('a')).attr("href", "#")
                                            		.attr("title",plugin.settings.strPrev)
                                            		.append( $(document.createElement('i')).attr("class", "fa fa-chevron-left")));
            /* blacknight - add - controllDownload/controllCopyright */
            if (plugin.settings.showDownloadLink) {
	            plugin.els['controlDownload'] = $(document.createElement('span')).attr('class','glisse-download')
	             								.append($(document.createElement('a')).attr("href", "#").attr("class","glisse-download-link")
	             										.attr("download","")
	             										.attr("title",plugin.settings.strDownload)
	             										.append( $(document.createElement('i')).attr("class", "fa fa-download ")));
            }
            if (plugin.settings.showMaxLink) {
	            plugin.els['controlShowMax'] = $(document.createElement('span')).attr('class','glisse-showmax')
				.append($(document.createElement('a')).attr("href", "#").attr("class","glisse-showmax-link")
						.attr("title",plugin.settings.strMax)
						.append( $(document.createElement('i')).attr("class", "fa fa-arrows-alt ")));	            
            }
            plugin.els['controlCopyright'] = $(document.createElement('span')).attr('class','glisse-copyright').html(plugin.settings.copyright);

            // Add structure
            plugin.els['overlay'].append(plugin.els['spinner']);
            plugin.els['controls'].append(
                    plugin.els['controlNext'],
                    plugin.els['controlLegend'],
                    plugin.els['controlPrev']);
            /* blacknight - add - controllDownload/controllCopyright */            
            plugin.els['controls'].append(
            		plugin.els['controlCopyright']); 
            if (plugin.settings.showMaxLink) plugin.els['controls'].append(plugin.els['controlShowMax']);
            if (plugin.settings.showDownloadLink) plugin.els['controls'].append(plugin.els['controlDownload']);
            plugin.els['wrapper'].append(
                    plugin.els['overlay'],
                    plugin.els['close'],
                    plugin.els['content'],
                    plugin.els['controls']
                );
            $('body').append(plugin.els['wrapper']);

            readyElement.observe('glisse-overlay', function(){ plugin.els['overlay'].css('opacity',1); });
            readyElement.observe('glisse-close', function(){ plugin.els['close'].css('opacity',1); });
            readyElement.observe('glisse-controls', function(){ plugin.els['controls'].css('opacity',1); });

            // Bind events
            plugin.els['controls'].delegate('a','click', function(e){   
            	/* blacknight - change - preventDefault only on next and prev */            	
                if($(this).parent().hasClass('glisse-next')) {
                	e.preventDefault();
                	changePicture('next');
                }
                if($(this).parent().hasClass('glisse-prev')) {
                	e.preventDefault();
                	changePicture('prev');
                }       
            });
            plugin.els['controls'].delegate('span','click', function(e){
                if($(this).hasClass('glisse-legend')) {
                	toggleTitleMaxMin();
                }else if((controlsDefaultHeight != plugin.els['controls'].css('height')) && (controlsDefaultHeight !== -1)) {
                	toggleTitleMaxMin();
                }
            });

            plugin.els['overlay'].on('click', function() { closeLightbox(); });
            plugin.els['content'].on('click', function() { closeLightbox(); });
            plugin.els['close'].on('click', function() { closeLightbox(); });

            if(plugin.settings.fullscreen){
                var docElm = document.documentElement;
                if (docElm.requestFullscreen) {
                    docElm.requestFullscreen();
                }
                else if (docElm.mozRequestFullScreen) {
                    docElm.mozRequestFullScreen();
                }
                else if (docElm.webkitRequestFullScreen) {
                    docElm.webkitRequestFullScreen();
                }
            }
        };

        var closeLightbox = function closeLightbox() {

            // Hide lightbox
            plugin.els['content'].css({opacity: 0}).css(getPrefix('transform')+'transform', 'scale(1.2)');
            plugin.els['overlay'].css({opacity: 0});
            plugin.els['close'].css({opacity: 0});
            plugin.els['controls'].css({opacity: 0});

            // remove lightbox from dom
            setTimeout(function(){
                plugin.els['content'].remove();
                plugin.els['overlay'].remove();
                plugin.els['close'].remove();
                plugin.els['controls'].remove();
                plugin.els['wrapper'].remove();
                $('#glisse-transition-css').remove();
            }, plugin.settings.speed);

            $element.removeClass('active');

            // Unbinds
            document.ontouchmove = function(e){ return true; };
            document.ontouchstart = function(e){ return true; };
            document.ontouchend = function(e){ return true; };
            $(document).unbind("keydown");

            if(plugin.settings.fullscreen){
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
                else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                }
                else if (document.webkitCancelFullScreen) {
                    document.webkitCancelFullScreen();
                }
            }
        };

        var addImage = function addImage(pic) {
            spinner(true);
            var img = $('<img/>',{src: pic}).appendTo(plugin.els['content']).load(function() {
                img.remove();
                spinner(false);
                plugin.els['content'].css({ backgroundImage: 'url("'+pic+'")'});
                plugin.els['content'].css({visibility: 'visible', opacity: 1})
                                     .css(getPrefix('transform')+'transform','scale(1)')
                                     /* black-night - Image max height */
                                     .css({'max-height': ''+this.naturalHeight+'px'})
                                     ;
            });
        };

        var changePicture = function changePicture(direction) {
            var $currentEl = $('img['+plugin.settings.dataName+'="'+pictureUrl+'"][rel='+group+']'),
                currentId  = $('img[rel='+group+']').index($currentEl),
                totGroup   = $('img[rel='+group+']').length,
                change     = true;

            if((currentId === 0 && direction === 'prev') || (currentId === (totGroup-1) && direction === 'next')) {
                change = false;
            }

            if(change && isChange === false){
                if((controlsDefaultHeight != plugin.els['controls'].css('height')) && (controlsDefaultHeight !== -1)) {
                	toggleTitleMaxMin();
                }
                isChange = true;
                var $next = (direction === 'next') ? $('img[rel='+group+']').eq(currentId+1) : $('img[rel='+group+']').eq(currentId-1);
                if(plugin.settings.mobile){
                    if(direction !== 'next'){
                        plugin.els['content'].css(getPrefix('transform')+'transform', 'translateX(2000px)');
                    } else {
                        plugin.els['content'].css(getPrefix('transform')+'transform', 'translateX(-2000px)');
                    }
                } else {
                    plugin.els['content'].addClass('glisse-transitionOut-'+direction);
                    var cssProp = getPrefix('transition')+'transition',
                        cssVal = 'opacity '+plugin.settings.speed+'ms ease, '+getPrefix('transform')+'transform '+plugin.settings.speed+'ms ease';
                    plugin.els['content'].css(cssProp, '');
                }

                pictureUrl = getFullUrl($next);

                if($.inArray(pictureUrl, cache) === -1)
                    spinner(true);

                $currentEl.removeClass('active');
                $next.addClass('active');

                setChangeStatus();
                setTitle();
                /* blacknight - add - setDownload */
                setDownload();
                setShowMax();

                setTimeout(function() {
                    if(plugin.settings.mobile){
                        plugin.els['content'].css(getPrefix('transform')+'transform', 'translateX(0px)')
                            .css('display','none');
                    }

                    var img = $('<img/>',{src: pictureUrl}).appendTo(plugin.els['content']).load(function() {
                        img.remove();
                        
                        if($.inArray(pictureUrl, cache) === -1)
                            spinner(false);

                        if(plugin.settings.mobile){
                            plugin.els['content'].css('display','block');
                        }
                        /* black-night - Image max height */
                        plugin.els['content'].css({'max-height': ''+this.naturalHeight+'px'});                        
                        plugin.els['content'].css({ backgroundImage: 'url("'+pictureUrl+'")'});                        
                        plugin.els['content'].removeClass('glisse-transitionOut-'+direction)
                                                    .addClass('glisse-transitionIn-'+direction);
                        setTimeout(function(){
                            plugin.els['content'].removeClass('glisse-transitionIn-'+direction).css(cssProp, cssVal);
                            isChange = false;
                        }, plugin.settings.changeSpeed);
                    });
                }, plugin.settings.changeSpeed);
            } else if(change === false && isChange === false){
                if(plugin.settings.mobile){
                    plugin.els['content'].css(getPrefix('transform')+'transform', 'translateX(0px)');
                }
                plugin.els['content'].addClass('shake');
                setTimeout(function(){
                    plugin.els['content'].removeClass('shake');
                }, 600);
            }
        };

        var setChangeStyle = function setChangeStyle(){
            // Set change picture keyframes
            var prefix = getPrefix('transform'),
                prefixAnimation = getPrefix('animation'),
                effect = [];

            if(!isValidEffect(plugin.settings.effect))
                plugin.settings.effect = 'bounce';

            switch(plugin.settings.effect){
                case 'bounce':
                    effect = [
                        '@'+prefixAnimation+'keyframes outLeft {',
                            '0% { '+prefix+'transform: translateX(0);}',
                            '20% { opacity: 1;'+prefix+'transform: translateX(20px);}',
                            '100% { opacity: 0;'+prefix+'transform: translateX(-2000px);}',
                        '}',
                        '@'+prefixAnimation+'keyframes inLeft {',
                            '0% {opacity: 0;'+prefix+'transform: translateX(-2000px);}',
                            '60% {opacity: 1;'+prefix+'transform: translateX(30px);}',
                            '80% {'+prefix+'transform: translateX(-10px);}',
                            '100% {'+prefix+'transform: translateX(0);}',
                        '}',
                        '@'+prefixAnimation+'keyframes outRight {',
                            '0% {'+prefix+'transform: translateX(0);}',
                            '20% {opacity: 1;'+prefix+'transform: translateX(-20px);}',
                            '100% {opacity: 0;'+prefix+'transform: translateX(2000px);}',
                        '}',
                        '@'+prefixAnimation+'keyframes inRight {',
                            '0% {opacity: 0;'+prefix+'transform: translateX(2000px);}',
                            '60% {opacity: 1;'+prefix+'transform: translateX(-30px);}',
                            '80% {'+prefix+'transform: translateX(10px);}',
                            '100% {'+prefix+'transform: translateX(0);}',
                        '}'
                    ].join('');
                break;
                case 'fadeBig':
                     effect = [
                        '@'+prefixAnimation+'keyframes outLeft {',
                            '0% { opacity: 1;'+prefix+'transform: translateX(0);}',
                            '100% {opacity: 0;'+prefix+'transform: translateX(-2000px);}',
                        '}',
                        '@'+prefixAnimation+'keyframes inLeft {',
                            '0% { opacity: 0;'+prefix+'transform: translateX(-2000px);}',
                            '100% {opacity: 1;'+prefix+'transform: translateX(0);}',
                        '}',
                        '@'+prefixAnimation+'keyframes outRight {',
                            '0% { opacity: 1;'+prefix+'transform: translateX(0);}',
                            '100% {opacity: 0;'+prefix+'transform: translateX(2000px);}',
                        '}',
                        '@'+prefixAnimation+'keyframes inRight {',
                            '0% { opacity: 0;'+prefix+'transform: translateX(2000px);}',
                            '100% {opacity: 1;'+prefix+'transform: translateX(0);}',
                        '}'
                    ].join('');
                break;
                case 'fade':
                     effect = [
                        '@'+prefixAnimation+'keyframes outLeft {',
                            '0% { opacity: 1;'+prefix+'transform: translateX(0);}',
                            '100% {opacity: 0;'+prefix+'transform: translateX(-200px);}',
                        '}',
                        '@'+prefixAnimation+'keyframes inLeft {',
                            '0% { opacity: 0;'+prefix+'transform: translateX(-200px);}',
                            '100% {opacity: 1;'+prefix+'transform: translateX(0);}',
                        '}',
                        '@'+prefixAnimation+'keyframes outRight {',
                            '0% { opacity: 1;'+prefix+'transform: translateX(0);}',
                            '100% {opacity: 0;'+prefix+'transform: translateX(200px);}',
                        '}',
                        '@'+prefixAnimation+'keyframes inRight {',
                            '0% { opacity: 0;'+prefix+'transform: translateX(200px);}',
                            '100% {opacity: 1;'+prefix+'transform: translateX(0);}',
                        '}'
                    ].join('');
                break;
                case 'roll':
                     effect = [
                        '@'+prefixAnimation+'keyframes outLeft {',
                            '0% { opacity: 1;'+prefix+'transform: translateX(0px) rotate(0deg);}',
                            '100% {opacity: 0;'+prefix+'transform: translateX(-100%) rotate(-120deg);}',
                        '}',
                        '@'+prefixAnimation+'keyframes inLeft {',
                            '0% { opacity: 0;'+prefix+'transform: translateX(-100%) rotate(-120deg);}',
                            '100% {opacity: 1;'+prefix+'transform:  translateX(0px) rotate(0deg);}',
                        '}',
                        '@'+prefixAnimation+'keyframes outRight {',
                            '0% { opacity: 1;'+prefix+'transform:translateX(0px) rotate(0deg);}',
                            '100% {opacity: 0;'+prefix+'transform:translateX(100%) rotate(120deg);}',
                        '}',
                        '@'+prefixAnimation+'keyframes inRight {',
                            '0% { opacity: 0;'+prefix+'transform: translateX(100%) rotate(120deg);}',
                            '100% {opacity: 1;'+prefix+'transform:  translateX(0px) rotate(0deg);}',
                        '}'
                    ].join('');
                break;
                case 'rotate':
                     effect = [
                        '@'+prefixAnimation+'keyframes outRight {',
                            '0% { opacity: 1;'+prefix+'transform: rotate(0deg);'+prefix+'transform-origin:left bottom;}',
                            '100% {opacity: 0;'+prefix+'transform: rotate(-90deg);'+prefix+'transform-origin:left bottom;}',
                        '}',
                        '@'+prefixAnimation+'keyframes inLeft {',
                            '0% { opacity: 0;'+prefix+'transform: rotate(90deg);'+prefix+'transform-origin:left bottom;}',
                            '100% {opacity: 1;'+prefix+'transform: rotate(0deg);'+prefix+'transform-origin:left bottom;}',
                        '}',
                        '@'+prefixAnimation+'keyframes outLeft {',
                            '0% { opacity: 1;'+prefix+'transform: rotate(0deg);'+prefix+'transform-origin:right bottom;}',
                            '100% {opacity: 0;'+prefix+'transform: rotate(90deg);'+prefix+'transform-origin:right bottom;}',
                        '}',
                        '@'+prefixAnimation+'keyframes inRight {',
                            '0% { opacity: 0;'+prefix+'transform: rotate(-90deg);'+prefix+'transform-origin:right bottom;}',
                            '100% {opacity: 1;'+prefix+'transform: rotate(0deg);'+prefix+'transform-origin:right bottom;}',
                        '}'
                    ].join('');
                break;
                case 'flipX':
                    effect = [
                        '@'+prefixAnimation+'keyframes outLeft {',
                            '0% {'+prefix+'transform: perspective(400px) rotateX(0deg);opacity: 1;}',
                            '100% {'+prefix+'transform: perspective(400px) rotateX(90deg);opacity: 0;}',
                        '}',
                        '@'+prefixAnimation+'keyframes inLeft {',
                            '0% {'+prefix+'transform: perspective(400px) rotateX(90deg);opacity: 0;}',
                            '40% {'+prefix+'transform: perspective(400px) rotateX(-10deg);}',
                            '70% {'+prefix+'transform: perspective(400px) rotateX(10deg);}',
                            '100% {'+prefix+'transform: perspective(400px) rotateX(0deg);opacity: 1;}',
                        '}',
                        '@'+prefixAnimation+'keyframes outRight {',
                            '0% {'+prefix+'transform: perspective(400px) rotateX(0deg);opacity: 1;}',
                            '100% {'+prefix+'transform: perspective(400px) rotateX(90deg);opacity: 0;}',
                        '}',
                        '@'+prefixAnimation+'keyframes inRight {',
                            '0% {'+prefix+'transform: perspective(400px) rotateX(90deg);opacity: 0;}',
                            '40% {'+prefix+'transform: perspective(400px) rotateX(-10deg);}',
                            '70% {'+prefix+'transform: perspective(400px) rotateX(10deg);}',
                            '100% {'+prefix+'transform: perspective(400px) rotateX(0deg);opacity: 1;}',
                        '}'
                    ].join('');
                break;
                case 'flipY':
                    effect = [
                        '@'+prefixAnimation+'keyframes outLeft {',
                            '0% {'+prefix+'transform: perspective(400px) rotateY(0deg);opacity: 1;}',
                            '100% {'+prefix+'transform: perspective(400px) rotateY(90deg);opacity: 0;}',
                        '}',
                        '@'+prefixAnimation+'keyframes inLeft {',
                            '0% {'+prefix+'transform: perspective(400px) rotateY(90deg);opacity: 0;}',
                            '40% {'+prefix+'transform: perspective(400px) rotateY(-10deg);}',
                            '70% {'+prefix+'transform: perspective(400px) rotateY(10deg);}',
                            '100% {'+prefix+'transform: perspective(400px) rotateY(0deg);opacity: 1;}',
                        '}',
                        '@'+prefixAnimation+'keyframes outRight {',
                            '0% {'+prefix+'transform: perspective(400px) rotateY(0deg);opacity: 1;}',
                            '100% {'+prefix+'transform: perspective(400px) rotateY(-90deg);opacity: 0;}',
                        '}',
                        '@'+prefixAnimation+'keyframes inRight {',
                            '0% {'+prefix+'transform: perspective(400px) rotateY(90deg);opacity: 0;}',
                            '40% {'+prefix+'transform: perspective(400px) rotateY(-10deg);}',
                            '70% {'+prefix+'transform: perspective(400px) rotateY(10deg);}',
                            '100% {'+prefix+'transform: perspective(400px) rotateY(0deg);opacity: 1;}',
                        '}'
                    ].join('');
                break;
            }
            var changeClass = [
                        '.glisse-transitionOut-next {',
                            prefixAnimation+'animation: '+plugin.settings.changeSpeed+'ms ease;',
                            prefixAnimation+'animation-name: outLeft;',
                            prefixAnimation+'animation-fill-mode: both;',
                        '}',
                        '.glisse-transitionIn-prev {',
                            prefixAnimation+'animation: '+plugin.settings.changeSpeed+'ms ease;',
                            prefixAnimation+'animation-name: inLeft;',
                            prefixAnimation+'animation-fill-mode: both;',
                        '}',
                        '.glisse-transitionOut-prev {',
                            prefixAnimation+'animation: '+plugin.settings.changeSpeed+'ms ease;',
                            prefixAnimation+'animation-name: outRight;',
                            prefixAnimation+'animation-fill-mode: both;',
                        '}',
                        '.glisse-transitionIn-next {',
                            prefixAnimation+'animation: '+plugin.settings.changeSpeed+'ms ease;',
                            prefixAnimation+'animation-name: inRight;',
                            prefixAnimation+'animation-fill-mode: both;',
                        '}'
                    ].join('');

            if(!document.getElementById('glisse-css')) {
                $('<style type="text/css" id="glisse-css">'+effect+changeClass+'</style>').appendTo('head');
            } else {
                $('#glisse-css').html(effect+changeClass);
            }
        };

        // === Controls actions  =================

        var setChangeStatus = function setChangeStatus() {
            var $currentEl = $('img['+plugin.settings.dataName+'="'+pictureUrl+'"][rel='+group+']'),
	            currentId  = $('img[rel='+group+']').index($currentEl),
	            totGroup   = $('img[rel='+group+']').length;
	        if (currentId === (totGroup-1)) {
                plugin.els['controls'].find('.glisse-next').addClass('ended');
            } else {
                plugin.els['controls'].find('.glisse-next').removeClass('ended');
            }
            if (currentId === 0) {
                plugin.els['controls'].find('.glisse-prev').addClass('ended');
            } else {
                plugin.els['controls'].find('.glisse-prev').removeClass('ended');
            }
        };

        var setTitle = function setTitle() {
            var $legend     = plugin.els['controls'].find('.glisse-legend');
            var $currentEl = $('img['+plugin.settings.dataName+'="'+pictureUrl+'"]');
            var title      = $currentEl.attr('title');
             $legend.html( (title) ? title : '');
        };
        /* blacknight - add - setDownload */
        var setDownload = function setDownload() {
        	var $currentEl = $('a[class="glisse-download-link"]');
        	$currentEl.attr("href",pictureUrl);
        };
        var setShowMax = function setShowMax() {
        	var $currentEl = $('a[class="glisse-showmax-link"]');
        	$currentEl.attr("href",pictureUrl).attr("target","_blank");
        };        
        var toggleTitleMaxMin = function toggleTitleMaxMin() {
           	if((controlsDefaultHeight == -1) || (controlsDefaultHeight == plugin.els['controls'].css('height'))) {
        		controlsDefaultHeight = plugin.els['controls'].css('height');
        		plugin.els['controls'].css({'transition':'height 0.32s ease-in-out 0s'});
        		plugin.els['controls'].css({'height':'100%'});
        		plugin.els['controlLegend'].css({'white-space':'normal','right':'40px','overflow':'auto'});  
        		plugin.els['controlCopyright'].css({'display':'none'});
        		plugin.els['controlDownload'].css({'display':'none'});
        		plugin.els['controlShowMax'].css({'display':'none'});
        	}else{
        		plugin.els['controls'].css({'height':controlsDefaultHeight});
        		plugin.els['controlLegend'].css({'white-space':'nowrap','right':'65px','overflow':'hidden'});
        		plugin.els['controlCopyright'].css({'display':'inline'});
        		plugin.els['controlDownload'].css({'display':'inline'});
        		plugin.els['controlShowMax'].css({'display':'inline'});
        	}        	
        };

        // Spinner =========================================
        var spinner = function spinner(action) {
            plugin.els['overlay'].toggleClass('loading', action);
        };


        // Get Vendor prefix
        var getPrefix = function getPrefix(prop){
            var prefixes = ['Moz','Khtml','Webkit','O','ms'],
                elem     = document.createElement('div'),
                upper    = prop.charAt(0).toUpperCase() + prop.slice(1);

            for (var len = prefixes.length; len--; ){
                if ((prefixes[len] + upper)  in elem.style)
                    return ('-'+prefixes[len].toLowerCase()+'-');
            }

            return false;
        };

        var readyElement = (function(){
          return {
            observe : function(id,callback){
              var interval = setInterval(function(){
                if(document.getElementById(id)){
                  callback(document.getElementById(id));
                  clearInterval(interval);
                }
              },60);
            }
          };
        })();

        var isValidEffect = function isValidEffect(effect){
            return !!~$.inArray(effect, ['bounce', 'fadeBig', 'fade', 'roll', 'rotate', 'flipX', 'flipY']);
        };

       // Swipe support
        var touchHandler = function touchHandler(e) {
            if (e.type == "touchstart") {
                mobile.touching = true;
                // If there's only one finger touching
                if (e.touches.length == 1) {
                    // Remove transition
                    plugin.els['content'].css(getPrefix('transition')+'transition', '');

                    var touch = e.touches[0];
                    // If they user tries clicking on a link
                    if(touch.target.onclick) {
                        touch.target.onclick();
                    }
                    // The originating X-coord (point where finger first touched the screen)
                    mobile.oX = touch.pageX;
                    // Reset default values for current X-coord and scroll distance
                    mobile.nX = 0;
                    mobile.scrollX = 0;
                }
            } else if (e.type == "touchmove") {
                // Prevent the default scrolling behaviour (notice: This disables vertical scrolling as well)
                e.preventDefault();
                mobile.scrollX = null;

                // If there's only one finger touching
                if (e.touches.length == 1) {
                    var touch = e.touches[0];
                    // The current X-coord of the users finger
                    mobile.nX = touch.pageX;

                    // If the user moved the finger from the right to the left
                    if (mobile.oX > mobile.nX) {
                        // Find the scrolling distance
                        mobile.scrollX = -(mobile.oX-mobile.nX);
                    // If the user moved the finger from the left to the right
                    } else if(mobile.nX > mobile.oX) {
                        // Find the scrolling distance
                        mobile.scrollX = mobile.nX-mobile.oX;
                    }
                    plugin.els['content'].css(getPrefix('transform')+'transform', 'translateX('+(mobile.scrollX)+'px)');
                }
            // If the user has removed the finger from the screen
            } else if (e.type == "touchend" || e.type == "touchcancel") {
                // Defines the finger as not touching
                mobile.touching = false;
                var cssProp = getPrefix('transition')+'transition',
                    cssVal = 'opacity '+plugin.settings.speed+'ms ease, '+getPrefix('transform')+'transform '+plugin.settings.speed+'ms ease';
                plugin.els['content'].css(cssProp, cssVal);

                if(mobile.scrollX > 140){
                    changePicture('prev');
                } else if(mobile.scrollX < -(140)){
                    changePicture('next');
                } else {
                    plugin.els['content'].css(getPrefix('transform')+'transform', 'translateX(0px)');
                }

            } else {
                // Nothing
            }
        };


        // Public method
        plugin.changeEffect = function(effect) {
            if(isValidEffect(effect)){
                plugin.settings.effect = effect;
                setChangeStyle();
            }
        };


        plugin.init();

    };
    // Return
    $.fn.glisse = function(options) {
        return this.each(function() {
            if (undefined === $(this).data('glisse')) {
                var plugin = new $.glisse(this, options);
                $(this).data('glisse', plugin);
            }
        });
    };
})(jQuery, window.document);
