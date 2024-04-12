((Drupal) => {
    Drupal.behaviors.back_to_top = {
        attach(context) {
            
            /**
             * Page Progress
             * Shows page progress on the bottom right corner of pages
             */
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
    };
})(Drupal);