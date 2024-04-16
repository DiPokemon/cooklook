$(document).ready(function () {
  $('.slider_wrapper').each(function (index, sliderWrap) {
    var $related_recipes = $(sliderWrap).find('.related_recipes.recipes_slider');  

    $related_recipes.slick({
      infinite: true,
      autoplay: true,
      dots: false,
      arrows: true,
      slidesToShow: 3,
      slidesToScroll: 1,
      responsive: [
        {
          breakpoint: 1024,
          settings: {
            slidesToShow: 2
          }
        },
        {
          breakpoint: 768,
          settings: {
            slidesToShow: 1
          }
        }
      ]
    });   
  });

  var $slider = $('.categories_grid');

  if ($slider.length) {
    var currentSlide;
    var slidesCount;
    var sliderCounter = document.getElementById('categories_grid_slider-counter');    
    
    var updateSliderCounter = function(slick, currentIndex) {
      currentSlide = slick.slickCurrentSlide() + 1;
      slidesCount = slick.slideCount;
      $(sliderCounter).html('<span class="count_current">' + currentSlide + '</span>' + ' / ' + '<span class="count_total">' + slidesCount + '</span>');
    };
  
    $slider.on('init', function(event, slick) {
      //$slider.append(sliderCounter);
      updateSliderCounter(slick);
    });
  
    $slider.on('afterChange', function(event, slick, currentSlide) {
      updateSliderCounter(slick, currentSlide);
    });
  
    if ($(window).width() < 1024) {
      $slider.slick({        
          slidesToShow: 3,
          slidesToScroll: 3,
          autoplay: true,
          autoplaySpeed: 2000,
          arrows: true,
          //dots: true,
          
          prevArrow: $('.categories_grid-controls-prev'),
          nextArrow: $('.categories_grid-controls-next'),          
        })          
    }
  
    $(window).resize(function(){
      if ($(window).width() < 1024) {
        $slider.slick({        
            slidesToShow: 3,
            slidesToScroll: 3,
            autoplay: true,
            autoplaySpeed: 2000,
            arrows: true,
            //dots: true,
            prevArrow: $('.categories_grid-controls-prev'),
            nextArrow: $('.categories_grid-controls-next'),
        });
      } else {
          // Если экран становится больше 1024px, отключаем Slick slider
          $('.categories_grid').slick('unslick');
      }
    });
  }
})