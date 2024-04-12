'use strict';
var theme = {
  /**
   * Theme's components/functions list
   * Comment out or delete the unnecessary component.
   * Some components have dependencies (plugins).
   * Do not forget to remove dependency from src/js/vendor/ and recompile.
   */
  init: function () {
    theme.stickyHeader();
    theme.offCanvas();
    theme.onepageHeaderOffset();
    theme.spyScroll();
    theme.anchorSmoothScroll();
    theme.backgroundImage();
    theme.backgroundImageMobile();
    theme.imageHoverOverlay();
    theme.rellax();
    theme.swiperSlider();
    theme.progressBar();
    theme.loader();
    theme.pageProgress();
    theme.bsTooltips();
    theme.bsPopovers();
    theme.bsModal();
    theme.forms();
    theme.passVisibility();
    theme.pricingSwitcher();
    theme.textRotator();
  },
  /**
   * Sticky Header
   * Enables sticky behavior on navbar on page scroll
   * Requires assets/js/vendor/headhesive.min.js
  */
  stickyHeader: () => {
    var navbar = document.querySelector(".navbar");
    if (navbar == null) return;
    var options = {
      offset: 350,
      offsetSide: 'top',
      classes: {
        clone: 'navbar-clone fixed',
        stick: 'navbar-stick',
        unstick: 'navbar-unstick',
      },
      onStick: function() {
        var navbarClonedClass = this.clonedElem.classList;
        if (navbarClonedClass.contains('transparent') && navbarClonedClass.contains('navbar-dark')) {
          this.clonedElem.className = this.clonedElem.className.replace("navbar-dark","navbar-light");
        }
      }
    };
    var banner = new Headhesive('.navbar', options);
  },
  /**
   * Sub Menus
   * Enables multilevel dropdown
   */
  


  /**
   * Offcanvas
   * Enables offcanvas-nav, closes offcanvas on anchor clicks, focuses on input in search offcanvas
   */
  offCanvas: () => {
    var navbar = document.querySelector(".navbar");
    if (navbar == null) return;
    const navOffCanvasBtn = document.querySelectorAll(".offcanvas-nav-btn");
    const navOffCanvas = document.querySelector('.navbar:not(.navbar-clone) .offcanvas-nav');
    const bsOffCanvas = new bootstrap.Offcanvas(navOffCanvas, {scroll: true});
    const scrollLink = document.querySelectorAll('.onepage .navbar li a.scroll');
    const searchOffcanvas = document.getElementById('offcanvas-search');
    navOffCanvasBtn.forEach(e => {
      e.addEventListener('click', event => {
        bsOffCanvas.show();
      })
    });
    scrollLink.forEach(e => {
      e.addEventListener('click', event => {
        bsOffCanvas.hide();    
      })
    });
    if(searchOffcanvas != null) {
      searchOffcanvas.addEventListener('shown.bs.offcanvas', function () {
        document.getElementById("search-form").focus();
      });
    }
  },
  /**
   * Isotope
   * Enables isotope grid layout and filtering
   * Requires assets/js/vendor/isotope.pkgd.min.js
   * Requires assets/js/vendor/imagesloaded.pkgd.min.js
   */
  
  
  /**
   * Onepage Header Offset
   * Adds an offset value to anchor point equal to sticky header height on a onepage
   */
  onepageHeaderOffset: () => {
    var navbar = document.querySelector(".navbar");
    if (navbar == null) return;
    const header_height = document.querySelector(".navbar").offsetHeight;
    const shrinked_header_height = 75;
    const sections = document.querySelectorAll(".onepage section");
    sections.forEach(section => {
      section.style.paddingTop = shrinked_header_height + 'px';
      section.style.marginTop = '-' + shrinked_header_height + 'px';
    });
    const first_section = document.querySelector(".onepage section:first-of-type");
    if(first_section != null) {
      first_section.style.paddingTop = header_height + 'px';
      first_section.style.marginTop = '-' + header_height + 'px';
    }
  },
  /**
   * Spy Scroll
   * Highlights the active nav link while scrolling through sections
   */
  spyScroll: () => {
    let section = document.querySelectorAll('section[id]');
    let navLinks = document.querySelectorAll('.nav-link.scroll');
    window.onscroll = () => {
      section.forEach(sec => {
        let top = window.scrollY; //returns the number of pixels that the document is currently scrolled vertically.
        let offset = sec.offsetTop - 0; //returns the distance of the outer border of the current element relative to the inner border of the top of the offsetParent, the closest positioned ancestor element
        let height = sec.offsetHeight; //returns the height of an element, including vertical padding and borders, as an integer
        let id = sec.getAttribute('id'); //gets the value of an attribute of an element
        if (top >= offset && top < offset + height) {
          navLinks.forEach(links => {
            links.classList.remove('active');
            document.querySelector(`.nav-link.scroll[href*=${id}]`).classList.add('active');
            //[att*=val] Represents an element with the att attribute whose value contains at least one instance of the substring "val". If "val" is the empty string then the selector does not represent anything.
          });
        }
      });
    }
  },
  /**
   * Anchor Smooth Scroll
   * Adds smooth scroll animation to links with .scroll class
   * Requires assets/js/vendor/smoothscroll.js
   */
  anchorSmoothScroll: () => {
    const links = document.querySelectorAll(".scroll");
    for(const link of links) {
      link.addEventListener("click", clickHandler);
    }
    function clickHandler(e) {
      e.preventDefault();
      this.blur();
      const href = this.getAttribute("href");
      const offsetTop = document.querySelector(href).offsetTop;
      scroll({
        top: offsetTop,
        behavior: "smooth"
      });
    }
  },

  /**
   * Background Image
   * Adds a background image link via data attribute "data-image-src"
   */
  backgroundImage: () => {
    var bg = document.querySelectorAll(".bg-image");
    for(var i = 0; i < bg.length; i++) {
      var url = bg[i].getAttribute('data-image-src');
      bg[i].style.backgroundImage = "url('" + url + "')";
    }
  },
  /**
   * Background Image Mobile
   * Adds .mobile class to background images on mobile devices for styling purposes
   */
  backgroundImageMobile: () => {
    var isMobile = (navigator.userAgent.match(/Android/i) || navigator.userAgent.match(/webOS/i) || navigator.userAgent.match(/iPhone/i) || navigator.userAgent.match(/iPad/i) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1) || navigator.userAgent.match(/iPod/i) || navigator.userAgent.match(/BlackBerry/i)) ? true : false;
    if(isMobile) {
      document.querySelectorAll(".image-wrapper").forEach(e => {
        e.classList.add("mobile")
      })
    }
  },
  /**
   * Image Hover Overlay
   * Adds span.bg inside .overlay for simpler markup and styling purposes
   */
  imageHoverOverlay: () => {
    var overlay = document.querySelectorAll('.overlay > a, .overlay > span');
    for(var i = 0; i < overlay.length; i++) {
      var overlay_bg = document.createElement('span');
      overlay_bg.className = "bg";
      overlay[i].appendChild(overlay_bg);
    }
  },
  /**
   * Rellax.js
   * Adds parallax animation to shapes and elements
   * Requires assets/js/vendor/rellax.min.js
   */
  rellax: () => {
    if(document.querySelector(".rellax") != null) {
      window.onload = function() {
        var rellax = new Rellax('.rellax', {
          speed: 2,
          center: true,
          breakpoints: [576, 992, 1201]
        });
        var projects_overflow = document.querySelectorAll('.projects-overflow');
        imagesLoaded(projects_overflow, function() {
          rellax.refresh();
        });
      }
    }
  },
  /**
   * scrollCue.js
   * Enables showing elements by scrolling
   * Requires assets/js/vendor/scrollCue.min.js
   */

  
  /**
   * Swiper Slider
   * Enables carousels and sliders
   * Requires assets/js/vendor/swiper-bundle.min.js
   */
  swiperSlider: function() {
    var carousel = document.querySelectorAll('.swiper-container');
    for(var i = 0; i < carousel.length; i++) {
      var slider1 = carousel[i];
      slider1.classList.add('swiper-container-' + i);
      var controls = document.createElement('div');
      controls.className = "swiper-controls";
      var pagi = document.createElement('div');
      pagi.className = "swiper-pagination";
      var navi = document.createElement('div');
      navi.className = "swiper-navigation";
      var prev = document.createElement('div');
      prev.className = "swiper-button swiper-button-prev";
      var next = document.createElement('div');
      next.className = "swiper-button swiper-button-next";
      slider1.appendChild(controls);
      controls.appendChild(navi);
      navi.appendChild(prev);
      navi.appendChild(next);
      controls.appendChild(pagi);
      var sliderEffect = slider1.getAttribute('data-effect') ? slider1.getAttribute('data-effect') : 'slide';
      if (slider1.getAttribute('data-items-auto') === 'true') {
        var slidesPerViewInit = "auto";
        var breakpointsInit = null;
      } else {
        var sliderItems = slider1.getAttribute('data-items') ? slider1.getAttribute('data-items') : 3; // items in all devices
        var sliderItemsXs = slider1.getAttribute('data-items-xs') ? slider1.getAttribute('data-items-xs') : 1; // start - 575
        var sliderItemsSm = slider1.getAttribute('data-items-sm') ? slider1.getAttribute('data-items-sm') : Number(sliderItemsXs); // 576 - 767
        var sliderItemsMd = slider1.getAttribute('data-items-md') ? slider1.getAttribute('data-items-md') : Number(sliderItemsSm); // 768 - 991
        var sliderItemsLg = slider1.getAttribute('data-items-lg') ? slider1.getAttribute('data-items-lg') : Number(sliderItemsMd); // 992 - 1199
        var sliderItemsXl = slider1.getAttribute('data-items-xl') ? slider1.getAttribute('data-items-xl') : Number(sliderItemsLg); // 1200 - end
        var sliderItemsXxl = slider1.getAttribute('data-items-xxl') ? slider1.getAttribute('data-items-xxl') : Number(sliderItemsXl); // 1500 - end
        var slidesPerViewInit = sliderItems;
        var breakpointsInit = {
          0: {
            slidesPerView: Number(sliderItemsXs)
          },
          576: {
            slidesPerView: Number(sliderItemsSm)
          },
          768: {
            slidesPerView: Number(sliderItemsMd)
          },
          992: {
            slidesPerView: Number(sliderItemsLg)
          },
          1200: {
            slidesPerView: Number(sliderItemsXl)
          },
          1400: {
            slidesPerView: Number(sliderItemsXxl)
          }
        }
      }
      var sliderSpeed = slider1.getAttribute('data-speed') ? slider1.getAttribute('data-speed') : 500;
      var sliderAutoPlay = slider1.getAttribute('data-autoplay') !== 'false';
      var sliderAutoPlayTime = slider1.getAttribute('data-autoplaytime') ? slider1.getAttribute('data-autoplaytime') : 5000;
      var sliderAutoHeight = slider1.getAttribute('data-autoheight') === 'true';
      var sliderResizeUpdate = slider1.getAttribute('data-resizeupdate') !== 'false';
      var sliderAllowTouchMove = slider1.getAttribute('data-drag') !== 'false';
      var sliderReverseDirection = slider1.getAttribute('data-reverse') === 'true';
      var sliderMargin = slider1.getAttribute('data-margin') ? slider1.getAttribute('data-margin') : 30;
      var sliderLoop = slider1.getAttribute('data-loop') === 'true';
      var sliderCentered = slider1.getAttribute('data-centered') === 'true';
      var swiper = slider1.querySelector('.swiper:not(.swiper-thumbs)');
      var swiperTh = slider1.querySelector('.swiper-thumbs');
      var sliderTh = new Swiper(swiperTh, {
        slidesPerView: 5,
        spaceBetween: 10,
        loop: false,
        threshold: 2,
        slideToClickedSlide: true
      });
      if (slider1.getAttribute('data-thumbs') === 'true') {
        var thumbsInit = sliderTh;
        var swiperMain = document.createElement('div');
        swiperMain.className = "swiper-main";
        swiper.parentNode.insertBefore(swiperMain, swiper);
        swiperMain.appendChild(swiper);
        slider1.removeChild(controls);
        swiperMain.appendChild(controls);
      } else {
        var thumbsInit = null;
      }
      var slider = new Swiper(swiper, {
        on: {
          beforeInit: function() {
            if(slider1.getAttribute('data-nav') !== 'true' && slider1.getAttribute('data-dots') !== 'true') {
              controls.remove();
            }
            if(slider1.getAttribute('data-dots') !== 'true') {
              pagi.remove();
            }
            if(slider1.getAttribute('data-nav') !== 'true') {
              navi.remove();
            }
          },
          init: function() {
            if(slider1.getAttribute('data-autoplay') !== 'true') {
              this.autoplay.stop();
            }
            this.update();
          }
        },
        autoplay: {
          delay: sliderAutoPlayTime,
          disableOnInteraction: false,
          reverseDirection: sliderReverseDirection,
          pauseOnMouseEnter: false
        },
        allowTouchMove: sliderAllowTouchMove,
        speed: parseInt(sliderSpeed),
        slidesPerView: slidesPerViewInit,
        loop: sliderLoop,
        centeredSlides: sliderCentered,
        spaceBetween: Number(sliderMargin),
        effect: sliderEffect,
        autoHeight: sliderAutoHeight,
        grabCursor: true,
        resizeObserver: false,
        updateOnWindowResize: sliderResizeUpdate,
        breakpoints: breakpointsInit,
        pagination: {
          el: carousel[i].querySelector('.swiper-pagination'),
          clickable: true
        },
        navigation: {
          prevEl: slider1.querySelector('.swiper-button-prev'),
          nextEl: slider1.querySelector('.swiper-button-next'),
        },
        thumbs: {
          swiper: thumbsInit,
        },
      });
    }
  },
  /**
   * GLightbox
   * Enables lightbox functionality
   * Requires assets/js/vendor/glightbox.js
   */
  
  /**
   * Plyr
   * Enables media player
   * Requires assets/js/vendor/plyr.js
   */

  

  /**
   * Progressbar
   * Enables animated progressbars
   * Requires assets/js/vendor/progressbar.min.js
   * Requires assets/js/vendor/noframework.waypoints.min.js
   */
  progressBar: () => {
    const pline = document.querySelectorAll(".progressbar.line");
    const pcircle = document.querySelectorAll(".progressbar.semi-circle");
    pline.forEach(e => {
      var line = new ProgressBar.Line(e, {
        strokeWidth: 6,
        trailWidth: 6,
        duration: 3000,
        easing: 'easeInOut',
        text: {
          style: {
            color: 'inherit',
            position: 'absolute',
            right: '0',
            top: '-30px',
            padding: 0,
            margin: 0,
            transform: null
          },
          autoStyleContainer: false
        },
        step: (state, line) => {
          line.setText(Math.round(line.value() * 100) + ' %');
        }
      });
      var value = e.getAttribute('data-value') / 100;
      new Waypoint({
        element: e,
        handler: function() {
          line.animate(value);
        },
        offset: 'bottom-in-view',
      })
    });
    pcircle.forEach(e => {
      e.innerHTML = ''; // Remove any whitespace of this element
      var circle = new ProgressBar.SemiCircle(e, {
        strokeWidth: 6,
        trailWidth: 6,
        duration: 2000,
        easing: 'easeInOut',
        step: (state, circle) => {
          circle.setText(Math.round(circle.value() * 100));
        }
      });
      var value = e.getAttribute('data-value') / 100;
      new Waypoint({
        element: e,
        handler: function() {
          circle.animate(value);
        },
        offset: 'bottom-in-view',
      })
    });
  },
  /**
   * Loader
   * 
   */
  loader: () => {
    var preloader = document.querySelector('.page-loader');
    if(preloader != null) {
      document.body.onload = function(){
        setTimeout(function() {
          if( !preloader.classList.contains('done') )
          {
            preloader.classList.add('done');
          }
        }, 1000)
      }
    }
  },
  /**
   * Page Progress
   * Shows page progress on the bottom right corner of pages
   */
  pageProgress: () => {
    var progressWrap = document.querySelector('.progress-wrap');
    if(progressWrap != null) {
      var progressPath = document.querySelector('.progress-wrap path');
      var pathLength = progressPath.getTotalLength();
      var offset = 50;
      progressPath.style.transition = progressPath.style.WebkitTransition = 'none';
      progressPath.style.strokeDasharray = pathLength + ' ' + pathLength;
      progressPath.style.strokeDashoffset = pathLength;
      progressPath.getBoundingClientRect();
      progressPath.style.transition = progressPath.style.WebkitTransition = 'stroke-dashoffset 10ms linear';
      window.addEventListener("scroll", function(event) {
        var scroll = document.body.scrollTop || document.documentElement.scrollTop;
        var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        var progress = pathLength - (scroll * pathLength / height);
        progressPath.style.strokeDashoffset = progress;
        var scrollElementPos = document.body.scrollTop || document.documentElement.scrollTop;
        if(scrollElementPos >= offset) {
          progressWrap.classList.add("active-progress")
        } else {
          progressWrap.classList.remove("active-progress")
        }
      });
      progressWrap.addEventListener('click', function(e) {
        e.preventDefault();
        window.scroll({
          top: 0, 
          left: 0,
          behavior: 'smooth'
        });
      });
    }
  },
  /**
   * Counter Up
   * Counts up to a targeted number when the number becomes visible
   * Requires assets/js/vendor/counterup.min.js
   * Requires assets/js/vendor/noframework.waypoints.min.js
   */


  /**
   * Bootstrap Tooltips
   * Enables Bootstrap tooltips
   * Requires Poppers library
   */
  bsTooltips: () => {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl, {
        trigger: 'hover'
      })
    });
    var tooltipTriggerWhite = [].slice.call(document.querySelectorAll('[data-bs-toggle="white-tooltip"]'))
    var tooltipWhite = tooltipTriggerWhite.map(function(tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl, {
        customClass: 'white-tooltip',
        trigger: 'hover',
        placement: 'left'
      })
    })
  },
  /**
   * Bootstrap Popovers
   * Enables Bootstrap popovers
   * Requires Poppers library
   */
  bsPopovers: () => {
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
      return new bootstrap.Popover(popoverTriggerEl)
    })
  },
  /**
   * Bootstrap Modal
   * Enables Bootstrap modal popup
   */
  bsModal: () => {
    if(document.querySelector(".modal-popup") != null) {
      var myModalPopup = new bootstrap.Modal(document.querySelector('.modal-popup'));
      setTimeout(function() {
        myModalPopup.show();
      }, 200);
    }
    // Fixes jumping of page progress caused by modal
    var innerWidth = window.innerWidth;
    var clientWidth = document.body.clientWidth;
    var scrollSize = innerWidth - clientWidth;
    var myModalEl = document.querySelectorAll('.modal');
    var navbarFixed = document.querySelector('.navbar.fixed');
    var pageProgress = document.querySelector('.progress-wrap');
    function setPadding() {
      if(navbarFixed != null) {
        navbarFixed.style.paddingRight = scrollSize + 'px';
      }
      if(pageProgress != null) {
        pageProgress.style.marginRight = scrollSize + 'px';
      }
    }
    function removePadding() {
      if(navbarFixed != null) {
        navbarFixed.style.paddingRight = '';
      }
      if(pageProgress != null) {
       pageProgress.style.marginRight = '';
      }
    }
    myModalEl.forEach(myModalEl => {
      myModalEl.addEventListener('show.bs.modal', function(e) {
        setPadding();
      })
      myModalEl.addEventListener('hidden.bs.modal', function(e) {
        removePadding();
      })
    });
  },
  /**
   * iTooltip
   * Enables custom tooltip style for image hover docs/elements/hover.html
   * Requires assets/js/vendor/itooltip.min.js
   */

  

  /**
   * Form Validation and Contact Form submit
   * Bootstrap validation - Only sends messages if form has class ".contact-form" and is validated and shows success/fail messages
   */
  forms: () => {
    (function() {
      "use strict";
      window.addEventListener("load", function() {
        var forms = document.querySelectorAll(".needs-validation");
        var inputRecaptcha = document.querySelector("input[data-recaptcha]"); 
        window.verifyRecaptchaCallback = function (response) {
          inputRecaptcha.value = response; 
          inputRecaptcha.dispatchEvent(new Event("change"));
        }
        window.expiredRecaptchaCallback = function () {
          var inputRecaptcha = document.querySelector("input[data-recaptcha]"); 
          inputRecaptcha.value = ""; 
          inputRecaptcha.dispatchEvent(new Event("change"));
        }
        var validation = Array.prototype.filter.call(forms, function(form) {
          form.addEventListener("submit", function(event) {
            if(form.checkValidity() === false) {
              event.preventDefault();
              event.stopPropagation();
            }
            form.classList.add("was-validated");
            if(form.checkValidity() === true) {
              event.preventDefault();
              form.classList.remove("was-validated");
              // Send message only if the form has class .contact-form
              var isContactForm = form.classList.contains('contact-form');
              if(isContactForm) {
                var data = new FormData(form);
                var alertClass = 'alert-danger';
                fetch("assets/php/contact.php", {
                  method: "post",
                  body: data
                }).then((data) => {
                  if(data.ok) {
                    alertClass = 'alert-success';
                  }
                  return data.text();
                }).then((txt) => {
                  var alertBox = '<div class="alert ' + alertClass + ' alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' + txt + '</div>';
                  if(alertClass && txt) {
                    form.querySelector(".messages").insertAdjacentHTML('beforeend', alertBox);
                    form.reset();
                    grecaptcha.reset();
                  }
                }).catch((err) => {
                  console.log(err);
                });
              }
            }
          }, false);
        });
      }, false);
    })();
  },
  /**
   * Password Visibility Toggle
   * Toggles password visibility in password input
   */
  passVisibility: () => {
    let pass = document.querySelectorAll('.password-field');
    for (let i = 0; i < pass.length; i++) {
      let passInput = pass[i].querySelector('.form-control');
      let passToggle = pass[i].querySelector('.password-toggle > i');
      passToggle.addEventListener('click', (e) => {
        if (passInput.type === "password") {
          passInput.type = "text";
          passToggle.classList.remove('uil-eye');
          passToggle.classList.add('uil-eye-slash');
        } else {
          passInput.type = "password";
          passToggle.classList.remove('uil-eye-slash'); 
          passToggle.classList.add('uil-eye');
        } 
      }, false);
    }
  },
  /**
   * Pricing Switcher
   * Enables monthly/yearly switcher seen on pricing tables
   */
  pricingSwitcher: () => {
    if(document.querySelector(".pricing-switchers") != null) {
      const wrapper = document.querySelectorAll(".pricing-wrapper");
      wrapper.forEach(wrap => {
        const switchers = wrap.querySelector(".pricing-switchers");
        const switcher = wrap.querySelectorAll(".pricing-switcher");
        const price = wrap.querySelectorAll(".price");
        switchers.addEventListener("click", (e) => {
          switcher.forEach(s => {
            s.classList.toggle("pricing-switcher-active");
          });
          price.forEach(p => {
            p.classList.remove("price-hidden");
            p.classList.toggle("price-show");
            p.classList.toggle("price-hide");
          });
        });
      });
    }
  },
  /**
   * ReplaceMe.js
   * Enables text rotator
   * Requires assets/js/vendor/replaceme.min.js
   */
  textRotator: () => {
    if(document.querySelector(".rotator-zoom") != null) {
      var replace = new ReplaceMe(document.querySelector('.rotator-zoom'), {
        animation: 'animate__animated animate__zoomIn',
        speed: 2500,
        separator: ',',
        clickChange: false,
        loopCount: 'infinite'
      });
    }
    if(document.querySelector(".rotator-fade") != null) {
      var replace = new ReplaceMe(document.querySelector('.rotator-fade'), {
        animation: 'animate__animated animate__fadeInDown',
        speed: 2500,
        separator: ',',
        clickChange: false,
        loopCount: 'infinite'
      });
    }
  },
  /**
   * Clipboard.js
   * Enables clipboard on docs
   * Requires assets/js/vendor/clipboard.min.js
   */
  
}
theme.init();
