jQuery(document).ready(function( $ ) {
	'use strict';

    // Passive Touch
    $.event.special.touchstart = {
        setup: function( _, ns, handle ) {
            this.addEventListener('touchstart', handle, { passive: !ns.includes('noPreventDefault') });
        }
    };

    // Dark Switch
    $('#fl-darkmode').change(function() {
        var theme = 'light';
        if ( $(this).is(':checked') ) {
            document.documentElement.setAttribute('data-theme', 'dark');
            theme = 'dark';
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
        }
        document.cookie = 'rosetta_color_theme=' + theme + '; path=/; SameSite=None; Secure';
    });

    
    function overlayClose() {
        $('#fl-overlay').fadeOut(200);
        $('.overlay-search').fadeOut(200);
        $('#fl-topsearch .bi-x-lg').fadeOut(100);
        $('#fl-topsearch .bi-search').delay(100).fadeIn(200);
        $('#fl-overlay .searchform input[type="text"]').blur();

        $('body').css('overflow-y', 'auto');

        $('#fl-drawer').removeClass('open');
        $('#fl-drawer-icon').removeClass('active');

        if ( !$('#fl-header').hasClass('fixed') ) {
            $('#fl-header').css('box-shadow', 'none');
        }
    }

    function overlayOpen() {
        $('#fl-overlay').fadeIn(200);
        $('body').css('overflow-y', 'hidden');

        if ( !$('#fl-header').hasClass('fixed') ) {
            $('#fl-header').css('box-shadow', '0 0px 9px 1px rgb(0 0 0 / 10%)');
        }
    }

    $('#fl-overlay').click(function (e){
        var container = $('.overlay-search');
        if (!container.is(e.target) && container.has(e.target).length === 0) {
            overlayClose();
        }
    });

	// Overlay Search
	$('#fl-topsearch').click(function () {
		if ( $('#fl-topsearch .bi-x-lg').css('display') == 'none' ) {

            $('#fl-drawer').removeClass('open');
            $('#fl-drawer-icon').removeClass('active');

            $.when( $('#fl-topsearch .bi-search').fadeOut(100) ).done(function() {
                $('#fl-topsearch .bi-x-lg').fadeIn(200);
                overlayOpen();
                $('.overlay-search').fadeIn(200);
                $('.overlay-search .searchform input[type="text"]').delay(200).focus();
            });

        } else {
            overlayClose();
        }
	});

    // Drawer
	$('#fl-drawer-icon').click(function() {
        if( $('#fl-drawer-icon').is('.active') ) {
            overlayClose();
        } else {
            var top = $('#fl-header').height();
            if ( $('#wpadminbar').length ) {
                top = top + $('#wpadminbar').height();
            }

            $('#fl-drawer-icon').addClass('active'); 
            $('#fl-drawer').css('top', top+'px');

            $('.overlay-search').fadeOut(200);
            $('#fl-topsearch .bi-x-lg').fadeOut(100);
            $('#fl-topsearch .bi-search').delay(100).fadeIn(200);
            $('#fl-overlay .searchform input[type="text"]').blur();

            setTimeout(function(){
                overlayOpen();
                $('#fl-drawer').addClass('open');
            }, 200);
        }
	});


	// Widget Menu Toogle
    $('.fl-widget .menu .menu-item-has-children .arrow').click(function () {
        $(this).parent().children('.sub-menu').slideToggle(200);
        $(this).toggleClass('open');
    });

    // Fixed Header
    $( window ).scroll(function() { 
        if ( $('#fl-header').css('position') === 'fixed' ) { 
            if ( $(this).scrollTop() > 64 ) {
                $('#fl-header').addClass('fixed');
            } else {     
                $('#fl-header').removeClass('fixed');
            }
        }
    });
    
    // Featured Posts Slider
    if ( $('#fl-featured').length ) {
        // Owl Default
        var rtlVal = false;


        if ( $('body').hasClass('rtl') ) { rtlVal = true; }

        if ( $('#fl-featured').hasClass('carousel') ) {
            $('#fl-featured').owlCarousel({                
                loop: true,
                autoWidth: true,
                nav: false,
                center: true,
                rtl: rtlVal,
                dots: false,
                autoplay: true,
                autoplayHoverPause: true,
                responsive:{
                    0:{
                        margin: 8,
                    },
                    577:{
                        margin: 16,
                    },
                    769:{
                        margin: 32,
                    },
                }
            });
        } else {
            $('#fl-featured').owlCarousel({                
                items: 1,
                loop: true,
                nav: true,
                rtl: rtlVal,
                dots: true,
                autoplay: true,
                dotsData: true,
                autoplayHoverPause: true,
            });
        }        
    }

    // Comments
    $('.fl-comments-toggle').click(function(event) {
        event.preventDefault();
        var toggleWidth = $(this).width() > 240 ? '240px' : '90%';
        $(this).animate({ width: toggleWidth }, 300);
        $('html,body').animate( { scrollTop: $('.fl-comments-toggle').offset().top - $('#fl-header').height() }, 500 );
        $('.fl-comments').slideToggle(500);
    });

    function openComments() {
        $.when( $('html,body').animate( { scrollTop: $('.fl-comments-toggle').offset().top - $('#fl-header').height() }, 500 ) ).done(function() {
            $('.fl-comments-toggle').animate({ width: '90%' }, 300);
            $('html,body').animate( { scrollTop: $('.fl-comments-toggle').offset().top - $('#fl-header').height() }, 500 );
            $('.fl-comments').slideDown(500);
        });
    }

    $('body.single .fl-meta a.comments').click(function(event) {
        event.preventDefault();
        openComments();
    });

    var hashComment = window.location.hash;
    if ( hashComment == '#comments' || hashComment == '#respond'  ) {
        if ( $('.fl-comments-toggle').length ) {
            openComments();
        }
    }


    // Masonry
    function masonryLayout() {

        var a = 0,
            b = 0,
            c = 0,
            masonryHeight = 0;

        if ( $('.fl-masonry').length ) {
            // Add flex masonry class
            $('.fl-masonry').addClass('fl-flexmasonry');
            // 3 Columns
            if ( $('.fl-masonry').hasClass('col-3') && window.matchMedia('(min-width: 769px)').matches ) {
                $('.fl-loop-posts .fl-post:nth-child(3n+1)').each(function(index ,element ){
                    a +=  $(element).outerHeight(true);
                });

                $('.fl-loop-posts .fl-post:nth-child(3n+2)').each(function(index ,element ){
                    b +=  $(element).outerHeight(true);
                });

                $('.fl-loop-posts .fl-post:nth-child(3n)').each(function(index ,element ){
                    c +=  $(element).outerHeight(true);
                });
            } else {
                $('.fl-loop-posts .fl-post:nth-child(2n+1)').each(function(index ,element ){
                    a +=  $(element).outerHeight(true);
                });

                $('.fl-loop-posts .fl-post:nth-child(2n)').each(function(index ,element ){
                    b +=  $(element).outerHeight(true);
                });
            }

            masonryHeight = Math.max(a, b, c);

            $('.fl-flexmasonry').find('.fl-loop-posts').height( masonryHeight+16 );
        }
    }

    $(window).load(function() {
        masonryLayout();
    });

    var masonryTimeOut;
    $(window).resize(function() {
        clearTimeout(masonryTimeOut);
        masonryTimeOut = setTimeout(masonryLayout, 100);
    });
    


//----------------------------------------------------------
// AJAX PAGINATION & Loop Tabs
//---------------------------------------------------------


    // Ajax Pagination
    function ajax_pagination(element) {

        var current = parseInt( element.attr('current-page') ),
            max = parseInt( element.attr('max-pages') ),
            next = current+1,
            panel = $( element.parent('div') );

        element.hide();
        panel.find('.fl-loading, .fl-error').remove();
        panel.append('<div class="fl-loading animate-loading">···</div>');

        $.ajax({
            type: 'POST',
            url: rosetta.ajaxurl,
            data: {
                'next': next,
                'query_vars': rosetta.query_vars,
                'thumb_size': rosetta.thumb_size,
                'layout': rosetta.layout,
                'action': 'rosetta_posts_ajax'
            },
            success: function (result) {
                panel.find('.fl-loading').hide();
                panel.find('.fl-loop-posts').append( $(result).hide().fadeIn(500) );
                masonryLayout();
                element.attr('current-page', next);
                if ( next < max ) {
                    element.show();
                } else {
                    element.remove();
                    panel.append(rosetta.ajax_end);
                }
            },
            error: function(result) { 
                panel.find('.fl-loading').hide();
                panel.append($('<div class="fl-error">Error! Cannot retrive loop posts.</div>').hide().fadeIn(500));
                element.show();
            }
        });
    }

    // Click Load More
    $(document).on( 'click', '.fl-load-more', function(event) {  
        var element = $(this);
        ajax_pagination(element);
        event.preventDefault();
    });


    // Infintie Scroll Load More
    var latest = false;
    $(window).scroll( function(){
        var latestButton = $('.fl-load-more');
        // Latest
        if ( latestButton.hasClass('infinite') ) {
            if ( $(window).scrollTop() + $(window).height() > $(document).height() - 300 ) {
                if ( latest == false ){
                    ajax_pagination(latestButton);
                    latest = true;
                }
            } else {
                latest = false;
            }
        }
    });
    
});