if($(".game")[0] && typeof(spinningOff) === "undefined"){
    $(".game").rotate({
        bind:
    	{
    	    mouseover : function() {
    			$(this).find('.game-img').rotate({animateTo:360})
    		},
    		mouseout : function() {
    			$(this).find('.game-img').rotate({animateTo:0})
    		}
    	}
    });
}

function footerPosition(){
	if($('body').height() < window.innerHeight){
		$('#footer').css('position', 'absolute');
	} else {
		$('#footer').css('position', 'relative');
	}
}

function navBar(){
	if(window.innerWidth < 768){
		$('#top-navbar-left-list').hide();
		$('#top-navbar-right').hide();
		$('#menu-button').show();
	} else {
		$('#menu-button').hide();
		$('#hidden-list').hide();
		$('#top-navbar-left-list').show();
		$('#top-navbar-right').show();
	}
}

$('#menu-button').click(function(){
	if($('#hidden-list').is(':visible')){
		$('#hidden-list').slideUp();
		$('#menu-button').rotate({animateTo:0});
	} else {
		$('#hidden-list').slideDown();
		$('#menu-button').rotate({animateTo:180});
	}
});

function loadEvents(){
    if(document.getElementById('footer')) {
        footerPosition();
    	navBar();
    } else {
        setTimeout(loadEvents, 15);
    }
}

loadEvents();

window.addEventListener("resize", function(){
	footerPosition();
	navBar();
});

function showError(error){
	$('#errorbox-bottom').html(error);
	$('#table-container').css("display", "table");
	$('#fade-overlay').css("display", "block");
	$("html, body").animate({ scrollTop: 0 }, "slow");
    setTimeout(function(){
        $(document).click(function(event) {
            if($(event.target).is('#errorbox-container')) {
                closeErrorBox();
            }
        });
    }, 50);
}

function showError1(error){
	$('#errorbox-bottom-1').html(error);
	$('#table-container-1').css("display", "table");
	$('#fade-overlay-1').css("display", "block");
	$("html, body").animate({ scrollTop: 0 }, "slow");
    setTimeout(function(){
        $(document).click(function(event) {
            if($(event.target).is('#errorbox-container-1')) {
                closeErrorBox1();
            }
        });
    }, 50);
}

function showError2(error){
	$('#errorbox-bottom-2').html(error);
	$('#table-container-2').css("display", "table");
	$('#fade-overlay-2').css("display", "block");
	$("html, body").animate({ scrollTop: 0 }, "slow");
    setTimeout(function(){
        $(document).click(function(event) {
            if($(event.target).is('#errorbox-container-2')) {
                closeErrorBox2();
            }
        });
    }, 50);
}

function closeErrorBox(){
    if(typeof tinyMCE !== 'undefined'){
        var editor = tinyMCE.get(0);
        if(editor != null){
            editor.remove();
        }
    }
	$('#table-container').css("display", "none");
	$('#fade-overlay').css("display", "none");
	$('#errorbox-bottom').html("");
}

function closeErrorBox1(){
	$('#table-container-1').css("display", "none");
	$('#fade-overlay-1').css("display", "none");
	$('#errorbox-bottom-1').html("");
}

function closeErrorBox2(){
	$('#table-container-2').css("display", "none");
	$('#fade-overlay-2').css("display", "none");
	$('#errorbox-bottom-2').html("");
}

function enableToolTips(){
    $('[data-toggle="tooltip"]').tooltip();
}

function addLoadingCircle(element){
	element.find(".loading-circle").first().remove();
	element.append('<div class="loading-circle"></div>');
}

function removeLoadingCircle(element){
	element.find(".loading-circle").first().remove();
}

function unescapeHTML(string){
    string = string.toString()
            .replace(/&lt;/g, '<')
            .replace(/&gt;/g, '>');
    string = string.replace(/&amp;/g, '&');
    return string;
}

function htmlspecialchars_decode(string, quote_style) {
    var optTemp = 0,
        i = 0,
        noquotes = false;
    if (typeof quote_style === 'undefined') {
        quote_style = 2;
    }
    string = string.toString()
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>');
    var OPTS = {
        'ENT_NOQUOTES'                    : 0,
        'ENT_HTML_QUOTE_SINGLE' : 1,
        'ENT_HTML_QUOTE_DOUBLE' : 2,
        'ENT_COMPAT'                        : 2,
        'ENT_QUOTES'                        : 3,
        'ENT_IGNORE'                        : 4
    };
    if (quote_style === 0) {
        noquotes = true;
    }
    if (typeof quote_style !== 'number') {
        // Allow for a single string or an array of string flags
        quote_style = [].concat(quote_style);
        for (i = 0; i < quote_style.length; i++) {
            // Resolve string input to bitwise e.g. 'PATHINFO_EXTENSION' becomes 4
            if (OPTS[quote_style[i]] === 0) {
                noquotes = true;
            } else if (OPTS[quote_style[i]]) {
                optTemp = optTemp | OPTS[quote_style[i]];
            }
        }
        quote_style = optTemp;
    }
    if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE) {
        string = string.replace(/&#0*39;/g, "'"); // PHP doesn't currently escape if more than one 0, but it should
        // string = string.replace(/&apos;|&#x0*27;/g, "'"); // This would also be useful here, but not a part of PHP
    }
    if (!noquotes) {
        string = string.replace(/&quot;/g, '"');
    }
    // Put this in last place to avoid escape being double-decoded
    string = string.replace(/&amp;/g, '&');

    return string;
}

function langMenu() {
      document.getElementById("lang-dropdown").classList.toggle("show");
}

window.onclick = function(event) {
    if (!event.target.matches('.dropdown-button')) {
        var dropdowns = document.getElementsByClassName("dropdowncontent");
        var i;
        for (i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
            openDropdown.classList.remove('show');
            }
        }
    }
}

enableToolTips();
