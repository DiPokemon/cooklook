$(document).ready(function () {
  $('.slider_wrapper').each(function (index, sliderWrap) {
    var $related_recipes = $(sliderWrap).find('.related_recipes.recipes_slider');

    var $featured_products = $(sliderWrap).find('.featured_products');
    var $advantages = $(sliderWrap).find('.advantages');
    var $testimonials = $(sliderWrap).find('.testimonials');
    var $page_images = $(sliderWrap).find('.page_images_slider');
    var $about_icons = $(sliderWrap).find('.about_icons');
    var $opt_images = $(sliderWrap).find('.opt_img_slider');
    var $buy_icons = $(sliderWrap).find('.buy_icons');
    var $opt_products = $(sliderWrap).find('.opt_products');

    $related_recipes.slick({
      infinite: true,
      autoplay: true,
      dots: false,
      arrows: true,
      //cssEase: 'linear',
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

    $featured_products.slick({
      infinite: true,
      autoplay: true,
      dots: false,
      arrows: false,
      cssEase: 'linear',
      slidesToShow: 4,
      slidesToScroll: 1,
      responsive: [
        {
          breakpoint: 1024,
          settings: {
            slidesToShow: 3,
            slidesToScroll: 1
          }
        },
        {
          breakpoint: 768,
          settings: {
            slidesToShow: 2,
            slidesToScroll: 1
          }
        },
        {
          breakpoint: 600,
          settings: {
            slidesToShow: 1,
            slidesToScroll: 1
          }
        }
      ]
    });

    $advantages.slick({
      infinite: true,
      autoplay: true,
      dots: false,
      arrows: false,
      cssEase: 'linear',
      slidesToShow: 4,
      slidesToScroll: 1,
      responsive: [
        {
          breakpoint: 1024,
          settings: {
            slidesToShow: 3,
            slidesToScroll: 1
          }
        },
        {
          breakpoint: 768,
          settings: {
            slidesToShow: 2,
            slidesToScroll: 1
          }
        },
        {
          breakpoint: 500,
          settings: {
            slidesToShow: 1,
            slidesToScroll: 1
          }
        }
      ]
    });

    $opt_products.slick({
      infinite: true,
      autoplay: true,
      dots: false,
      arrows: false,
      cssEase: 'linear',
      slidesToShow: 1,
      slidesToScroll: 1,      
    });

    $testimonials.slick({
      autoplay: true,
      arrows: false,
      dots: false,
      slidesToShow: 3,
      slidesToScroll: 1,
      //centerMode: true,
      // draggable: true,
      infinite: true,
      pauseOnHover: false,
      swipe: true,
      touchMove: true,
      vertical: true,
      speed: 1000,
      autoplaySpeed: 3000,
      useTransform: true,
      //cssEase: 'cubic-bezier(0.645, 0.045, 0.355, 1.000)',
      cssEase: 'linear',
      adaptiveHeight: true,
      responsive: [
        {
          breakpoint: 1024,
          settings: {
            slidesToShow: 2,
            slidesToScroll: 1
          }
        },
        {
          breakpoint: 768,
          settings: {
            vertical: false,
            centerMode: false,
            slidesToShow: 1,
            slidesToScroll: 1
          }
        }
      ]
    });
    
    $page_images.slick({
      infinite: true,
      autoplay: true,
      dots: false,
      arrows: false,
      cssEase: 'linear',
      slidesToShow: 4,
      slidesToScroll: 1,
      responsive: [
        {
          breakpoint: 1024,
          settings: {
            slidesToShow: 3,
            slidesToScroll: 1
          }
        },
        {
          breakpoint: 768,
          settings: {
            slidesToShow: 2,
            slidesToScroll: 1
          }
        },
        {
          breakpoint: 500,
          settings: {
            slidesToShow: 1,
            slidesToScroll: 1
          }
        }
      ]
    });

    $about_icons.slick({
      infinite: true,
      autoplay: true,
      dots: false,
      arrows: false,
      cssEase: 'linear',
      slidesToShow: 4,
      slidesToScroll: 1,
      responsive: [
        {
          breakpoint: 1024,
          settings: {
            slidesToShow: 3,
            slidesToScroll: 1
          }
        },
        {
          breakpoint: 768,
          settings: {
            slidesToShow: 2,
            slidesToScroll: 1
          }
        },
        {
          breakpoint: 500,
          settings: {
            slidesToShow: 1,
            slidesToScroll: 1
          }
        }
      ]
    });

    $opt_images.slick({
      infinite: true,
      autoplay: false,
      dots: true,
      arrows: false,
      cssEase: 'linear',
      slidesToShow: 1,
      slidesToScroll: 1      
    });

    $buy_icons.slick({
      infinite: true,
      autoplay: true,
      dots: false,
      arrows: false,
      cssEase: 'linear',
      slidesToShow: 3,
      slidesToScroll: 1,
      responsive: [
        {
          breakpoint: 1024,
          settings: {
            slidesToShow: 3,
            slidesToScroll: 1
          }
        },
        {
          breakpoint: 768,
          settings: {
            slidesToShow: 2,
            slidesToScroll: 1
          }
        },
        {
          breakpoint: 500,
          settings: {
            slidesToShow: 1,
            slidesToScroll: 1
          }
        }
      ]
    });


  });
})