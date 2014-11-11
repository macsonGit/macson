$(document).mousedown(function(event)
{
  /**/
  /* help */
  /**/
  if( $(event.target).parents('#help').length === 0 && $(event.target).attr('id') != 'help' )
  {
    $('#help dd').fadeOut('fast');
  }
  
  
  /**/
  /* cart summary */
  /**/
  if( $(event.target).parents('#cart-summary').length === 0 && $(event.target).attr('id') != 'cart-summary' )
  {
    $('#cart-summary dd').fadeOut('fast');
  }
  
  
  /**/
  /* wish list summary */
  /**/
  if( $(event.target).parents('#wish-list-summary').length === 0 && $(event.target).attr('id') != 'wish-list-summary' )
  {
    $('#wish-list-summary dd').fadeOut('fast');
  }
  
  
  /**/
  /* login popup */
  /**/
  if( $(event.target).parents('#login-popup').length === 0 && $(event.target).attr('id') != 'login-popup' )
  {
    $('#login-popup dd').fadeOut('fast');
  }
  
  
  /**/
  /* search */
  /**/
  if( $(event.target).parents('#search .filter').length === 0 && $(event.target).attr('class') != '.filter' )
  {
    $('#search .filter dd').fadeOut('fast');
  }
  
  
  /**/
  /* catalog */
  /**/
  if( $(event.target).parents('#products-filter-price').length === 0 && $(event.target).attr('id') != '#products-filter-price' )
  {
    $('#products-filter-price dd').fadeOut('fast');
  }
  if( $(event.target).parents('#products-filter-color').length === 0 && $(event.target).attr('id') != '#products-filter-color' )
  {
    $('#products-filter-color dd').fadeOut('fast');
  }
  if( $(event.target).parents('#products-filter-size').length === 0 && $(event.target).attr('id') != '#products-filter-size' )
  {
    $('#products-filter-size dd').fadeOut('fast');
  }
  
  
  /**/
  /* size-charts */
  /**/
  if( $(event.target).parents('#size-charts').length === 0 && $(event.target).attr('id') != 'size-charts' )
  {
    $('#size-charts').fadeOut('fast');
  }
  
  
  /**/
  /* what is cvv */
  /**/
  if( $(event.target).parents('#what-is-cvv').length === 0 && $(event.target).attr('id') != 'what-is-cvv' )
  {
    $('#what-is-cvv dd').fadeOut('fast');
  }
});


$(function()
{ 
  /**/
  /* help */
  /**/
  $('#help dt').on('click', function()
  {
    $(this).next().fadeToggle(200);
  });
  
  
  /**/
  /* cart summary */
  /**/
  $('#cart-summary').on('click', 'dt a', function()
  {
    $(this).parent().next().fadeToggle(200);
    return false;
  });
  $('#cart-summary').on('click', '.icon-remove', function()
  {
    itemsNum = $('#cart-summary li').length;
    
    if( itemsNum == 1 )
    {
      $('#cart-summary .empty').slideDown('fast');
      $('#cart-summary .total').slideUp('fast');
      $('#cart-summary .actions').slideUp('fast');
    }
    
    if( itemsNum == 2 )
    {
      $('#cart-summary dt a').text('1 item');
    }
    else
    {
      $('#cart-summary dt a').text((itemsNum - 1) + ' items');
    }
    
    $(this).parent().slideUp('fast', function()
    {
      $(this).remove();     
    });
  });
  
  
  /**/
  /* wish list summary */
  /**/
  $('#wish-list-summary').on('click', 'dt a', function()
  {
    $(this).parent().next().fadeToggle(200);
    return false;
  });
  $('#wish-list-summary').on('click', '.icon-remove', function()
  {
    itemsNum = $('#wish-list-summary li').length;
    
    if( itemsNum == 1 )
    {
      $('#wish-list-summary .empty').slideDown('fast');
      $('#wish-list-summary .share').slideUp('fast');
    }
    
    if( itemsNum == 2 )
    {
      $('#wish-list-summary dt a').text('1 item');
    }
    else
    {
      $('#wish-list-summary dt a').text((itemsNum - 1) + ' items');
    }
    
    $(this).parent().slideUp('fast', function()
    {
      $(this).remove();
    });
  });
  
  
  /**/
  /* login popup */
  /**/
  $('#login-popup').on('click', 'dt', function()
  {
    $(this).next().fadeToggle('fast');
    return false;
  });
  
  
  /**/
  /* popular products */
  /**/
  $('#popular-products').on('click', '.prev', function()
  {
    ol = $(this).next();
    li = $('#popular-products > ol > li.active');
    prev = li.prev().length ? li.prev() : ol.find('> li:last-child');
    
    li.animate({opacity:0, left:'100%'}, function()
    {
      li.hide().removeClass('active');
    });
    
    prev.css('left', '-100%').show().animate({opacity:1, left:'0%'}).addClass('active');
  });
  $('#popular-products').on('click', '.next', function()
  {
    ol = $(this).next().next();
    li = $('#popular-products > ol > li.active');
    next = li.next().length ? li.next() : ol.find('> li:first-child');
    
    li.animate({opacity:0, left:'-100%'}, function()
    {
      li.hide().removeClass('active');
    });
    
    next.css('left', '100%').show().animate({opacity:1, left:'0%'}).addClass('active');
  });
  
  
  /**/
  /* search */
  /**/
  $('#search .open').on('click', function()
  {
    $(this).next().show().parent().animate({width: 75}, 'fast');
    $('#header').animate({height: 108}, 'fast');
    $('#logo span').animate({height: 108}, 'fast');
    $('#products-nav').fadeOut('fast');
    $('#search .form').fadeIn('fast').find('input').focus();
    
    return false;
  });
  $('#search .close').on('click', function()
  {
    $(this).next().show().next().hide().parent().animate({width: 27}, 'fast');
    $('#header').animate({height: 54}, 'fast');
    $('#logo span').animate({height: 54}, 'fast');
    $('#products-nav').fadeIn('fast');
    $('#search .form').fadeOut('fast');
    
    return false;
  });
  $('#search .filter dt').on('click', function()
  {
    $('#search .filter dd').fadeOut('fast');
    $(this).next().stop().fadeIn('fast');
    
    return false;
  });
  $('#search .filter-color').on('click', 'a', function()
  {
    $(this).toggleClass('active');    
    return false;
  });
  $('#search .slider').slider({
    range: true,
    min: 0,
    max: 300,
    values: [0, 300],
    create: function( event, ui )
    {
      $('#search .slider a').append('<span></span>');
    },
    slide: function( event, ui )
    {
      $('#search .slider a').eq(0).find('span').html('$' + ui.values[0]);
      $('#search .slider a').eq(1).find('span').html('$' + ui.values[1]);
    }
  });
  
  
  /**/
  /* slideshow */
  /**/
  $('#slideshow').nivoSlider({
    effect: 'fade',
    pauseTime: 5000,
    directionNav: false,
    controlNavThumbs: true    
  });
  
  
  /**/
  /* product dropdown */
  /**/
  $(document).on('click', '.product-dropdown .switcher', function()
  {
    if( $(this).parent().hasClass('active') )
    {
      $(this).parent().removeClass('active');
    }
    else
    {
      $('.product-dropdown.active').removeClass('active');
      $(this).parent().addClass('active');
    }   
    
    return false;
  });
  
  
  /**/
  /* products grid */
  /**/
  $('.products-grid').on('click', '.color span', function()
  {
    elem = $(this);
    elem.addClass('active').siblings().removeClass('active');
    elem.parent().prev().find('span').eq(elem.index()).addClass('active').siblings().removeClass('active');
    
    return false;
  });
  
  
  /**/
  /* featured products */
  /**/
  $('#featured-products .products-grid').isotope({
    itemSelector : '.item',
    masonry: {columnWidth: 252},
    filter: '.new'
  }); 
  $('#featured-products').on('click', 'header a', function()
  {
    $(this).addClass('active').siblings().removeClass('active');
    $($(this).attr('href')).addClass('active').siblings('li').removeClass('active');
    $('#featured-products .products-grid').isotope({
      filter: $(this).data('filter')
    });
    
    return false;
  });
  
  
  /**/
  /* products showcase */
  /**/
  $('#products-showcase').on('click', '.prev', function()
  {
    ol = $(this).closest('ol');
    li = $(this).closest('li');
    prev = li.prev().length ? li.prev() : ol.find('li:last-child');
    
    li.animate({opacity:0, left:'100%'}, function()
    {
      li.hide();
    });
    
    prev.css('left', '-100%').css('display', 'block').animate({opacity:1, left:'0%'});
    
    return false;
  });
  $('#products-showcase').on('click', '.next', function()
  {
    ol = $(this).closest('ol');
    li = $(this).closest('li');
    next = li.next().length ? li.next() : ol.find('li:first-child');
    
    li.animate({opacity:0, left:'-100%'}, function()
    {
      li.hide();
    });
    
    next.css('left', '100%').css('display', 'block').animate({opacity:1, left:'0%'});
    
    return false;
  });
  $('.products-showcase').on('click', '.color span', function()
  {
    elem = $(this);
    elem.addClass('active').siblings().removeClass('active');
    elem.parent().prev().prev().find('span').eq(elem.index()).addClass('active').siblings().removeClass('active');
    
    return false;
  });
  
  
  /**/
  /* publications */
  /**/
  $('#publications > ul').isotope({
    itemSelector : 'li',
    masonry: {columnWidth: 252},
    getSortData : {
      sort : function($elem)
        {
          return parseInt( $elem.find('.sort').text() );
        }
      },    
    sortBy : 'sort'
  }); 
  $('#publications').on('click', 'header a', function()
  {
    $(this).addClass('active').siblings().removeClass('active');
    $('#publications > ul').isotope({
      filter: $(this).data('filter') + ', .st'
    });
    
    return false;
  });
  $('a.pretty-photo').prettyPhoto({
    opacity: 0.5,
    show_title: false,
    social_tools: ''
  });
  
  
  /**/
  /* catalog */
  /**/
  $('#products-filter').on('click', 'dt', function()
  {
    $(this).next().fadeToggle(200);
  });
  
  $('#products-filter .slider').slider({
    range: true,
    min: 0,
    max: 300,
    values: [0, 300],
    create: function( event, ui )
    {
      $('#products-filter .slider a').append('<span></span>');
    },
    slide: function( event, ui )
    {
      $('#products-filter .slider a').eq(0).find('span').html('$' + ui.values[0]);
      $('#products-filter .slider a').eq(1).find('span').html('$' + ui.values[1]);
      $('#products-filter-price dt').text('$' + ui.values[0] + ' - $' + ui.values[1]);
    }
  });
  
  $('#products-filter-color').on('change', 'input', function()
  {
    title = '';
    $('#products-filter-color input:checked').each(function()
    {
      title += '<em class="' + $(this).attr('id') + '"></em>';
    });
    if( title.length > 0 ) 
    {
      $('#products-filter-color dt').addClass('selected').html(title);
    }
    else
    {
      $('#products-filter-color dt').removeClass('selected').text('color');
    } 
  });
  
  $('#products-filter-size').on('change', 'input', function()
  {
    title = '';
    $('#products-filter-size input:checked').each(function()
    {
      title += $(this).attr('title') + ' / ';
    });
    title = title.substr(0, title.length-3);
    if( title.length > 0 ) 
      $('#products-filter-size dt').text(title);
    else
      $('#products-filter-size dt').text('size');     
  });
  
  $('#catalog .products-grid').isotope({
    itemSelector : '.item',
    masonry: {columnWidth: 252}
  });
  
  
  /**/
  /* product */
  /**/
  $('#product-thumbs').on('click', 'a', function()
  {
    elem = $(this).parent();
    elem.addClass('active').siblings().removeClass('active');
    $('#product-pics li').eq(elem.index()).addClass('active').siblings().removeClass('active');
    
    return false;
  });
  $('#product-pics').on('click', 'a', function()
  {
    $('#product').toggleClass('product-zoomed');
    
    return false;
  });
  $('#product-zoom').on('click', function()
  {
    $('#product').toggleClass('product-zoomed');
    
    return false;
  });
  $('#size-charts-opener').on('click', function()
  {
    $('#size-charts').fadeIn('fast');
    return false;
  });
  $('#size-charts i').on('click', function()
  {
    $('#size-charts').fadeOut('fast');
  });
  $('#product-tabs').on('click', 'li', function()
  {
    elem = $(this);
    elem.addClass('active').siblings().removeClass('active');
    elem.parent().next().find('> li').eq(elem.index()).addClass('active').siblings().removeClass('active');
    
    return false;
  });
  
  
  /**/
  /* recomended */
  /**/
  $('#recomended .products-grid').isotope({
    itemSelector : '.item',
    masonry: {columnWidth: 252}
  });
  
  
  /**/
  /* show more */
  /**/
  $('.show-more').on('click', 'a', function()
  {
    loader = $(this);
    page = $(this).data('page');
    template = $(this).data('template');
    container = $(this).parent().prev();
      
    if( !page )
      return false;
    
    $.get('ajax/' + template + '-page' + page + '.html').done(function(data)
    {
      $(container).isotope('insert', $(data));
      loader.data('page', ++page);      
      
      $('a.pretty-photo').prettyPhoto({
        opacity: 0.5,
        show_title: false,
        social_tools: ''
      });
      
      $.get('ajax/' + template + '-page' + page + '.html').fail(function()
      {
        loader.addClass('disabled');
      });
    }).fail(function()
    {
      loader.addClass('disabled');
    });
    
    return false;
  });
  
  
  /**/
  /* checkout */
  /**/
  /* FIXED Comment it for checkout process in diferent pages 
  $('.checkout').on('click', 'h2', function()
  {
    $('.checkout .form').slideUp('fast');
    $('.checkout h2').removeClass('active');
    $(this).addClass('active').next().stop().slideDown('fast');
  });
  */
  
  /**/
  /* what is cvv */
  /**/
  $('#what-is-cvv dt').on('click', function()
  {
    $(this).next().fadeToggle('fast');
  });
  $('#what-is-cvv i').on('click', function()
  {
    $('#what-is-cvv dd').fadeOut('fast');
  });
});
