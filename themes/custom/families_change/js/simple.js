document.addEventListener('DOMContentLoaded', function () {

  document.querySelectorAll("a").forEach(link => {
    if (link.hostname !== window.location.hostname) {
        link.target = "_blank"; // Opens in a new tab
        link.rel = "noopener noreferrer"; // Security best practice
    }
 });

  const mainDiv = document.getElementById('maindiv');
    const sidebar = document.getElementById('sidebar-first-area');
    const sidebarBg = sidebar.querySelector('.sidebar-bg');
    const computedStyle = window.getComputedStyle(mainDiv);
    sidebarBg.style.left = `calc(-${parseFloat(computedStyle.marginLeft)}px - 12px)`;
    window.addEventListener('resize', () => {
      sidebarBg.style.left = `calc(-${parseFloat(computedStyle.marginLeft)}px - 12px)`;
    });
   
    let videoPlayBtns = document.querySelectorAll('.playPauseBtn');
    let videoIframes = document.querySelectorAll(".video-section");
    // console.log(videoIframes.length);
    // console.log(videoPlayBtns.length);

    for(let i=0;i!=videoPlayBtns.length;i++){
        // console.log(videoPlayBtns[i]);
        let currentVideoThumbnail = document.querySelectorAll('.thumbnail-section')[i];
        let currentVideoIframe = videoIframes[i];
        // console.log(currentVideoIframe);
        let currentVideoBtnIcon = document.querySelectorAll(".btn-icon")[i];
        let videoPlaying = false;
        let currentPlayer = new Vimeo.Player(currentVideoIframe);
        videoPlayBtns[i].addEventListener('click', function() {
        console.log("Play button clicked"); 
            if(videoPlaying){
                currentVideoThumbnail.style.display="block";
                currentVideoBtnIcon.style.display="block";
                currentVideoIframe.style.display="none";
              videoPlaying=false;
              currentPlayer.pause().then(function() {     
                console.log('Video paused');
            }).catch(function(error) {
                console.error('Error pausing video:', error);
            });
            }
            else{
                currentVideoThumbnail.style.display="none";
                currentVideoBtnIcon.style.display="none";
                currentVideoIframe.style.display="block";
              videoPlaying=true;

              //pausing all other videos
              for(let j=0;j!=videoIframes.length;j++){
                if(j==i){
                    continue;
                }
                else{
                    let otherPlayer = new Vimeo.Player(videoIframes[j]);
                    otherPlayer.pause().then(function() {     
                        console.log('Video paused');
                    }).catch(function(error) {
                        console.error('Error pausing video:', error);
                    });

                    document.querySelectorAll(".thumbnail-section")[j].style.display="block";
                    document.querySelectorAll(".btn-icon")[j].style.display="block";
                    videoIframes[j].style.display="none";
                }
              }

              //playing current video
                  currentPlayer.play().then(function() {
                    
                  }).catch(function(error) {
                      console.error('Error playing video:', error);
                  });
                }
    });


}

});

function debounce(func, delay) {
  let timerId;

  return function () {
    const context = this;
    const args = arguments;

    clearTimeout(timerId);
    timerId = setTimeout(() => {
      func.apply(context, args);
    }, delay);
  };
}

(function ($) {
  $(document).ready(function () {

    // ----------Parent and teen page setup--------------
    var currentUrl = window.location.pathname;

    if (currentUrl.indexOf('/teen') !== -1) {
      $('.top-menu .nav.navbar-nav li:nth-child(2) a').addClass('is-active');
    }else{
      $('.top-menu .nav.navbar-nav li:nth-child(3) a').addClass('is-active');
    }

    // ------------------------------------------------------------------------------ 
    // Add class in body for overflow control at the time menu toggl 
    $('.mobile-menu #superfish-mobile-menus-toggle').click(function () {
      $('body').toggleClass('overflow');
    });
    // ------------------------------------------------------------------------------ 

  });

  // ------------- Grid multiple video play and pause ----------------
  document.addEventListener('DOMContentLoaded', function () {
    });
    // --------------------------------------- 
    // ACEs open close animation 
    // const buttons = document.querySelectorAll('.aces-list .accordion-button');
    // if(buttons){
    //   buttons.forEach(button=>{
    //     button.addEventListener('click', () => {
    //       button.classList.toggle('active');
    //       const content = button.nextElementSibling;
    //       content.classList.toggle('open');
    //     });
    //   });
    // }
    // --------------------------------------- 

    

  });

// })(jQuery);


// Search From Append

function isScreenSmallerThan768px() {
  return window.innerWidth < 768;
}

function handleMobileMenu(ul) {
  const searchForm = document.getElementById('search-block-form');
  const newListItem = document.createElement('li');
  newListItem.classList.add('mobile-menu-search-form');
  newListItem.append(searchForm);
  ul.append(newListItem);
  var searchInput = document.querySelector('#search-block-form #edit-keys');
  searchInput.setAttribute('placeholder', 'Search topics and more');
}

// Mutation Observer Setup
const observer = new MutationObserver(function (mutationsList) {
  for (let mutation of mutationsList) {
    if (mutation.type === 'childList') {
      mutation.addedNodes.forEach(function (node) {
        if (node.nodeName.toLowerCase() === 'ul' && node.id === 'superfish-mobile-menus-accordion' && isScreenSmallerThan768px()) {
          handleMobileMenu(node);
        }
      });
    }
  }
});

observer.observe(document.body, { childList: true, subtree: true });

jQuery(document).ready(function ($) {
  
  jQuery('.fc-sidebar-menu ul li a.active').each(function () {    
    jQuery(this).closest('li').addClass('show');
    jQuery(this).closest('ul').addClass('show');
    jQuery(this).closest('a').addClass('show');    
  });        

  jQuery('.aces-list .accordion-content').hide();
  jQuery('.aces-list .accordion-item').click(function () {
      jQuery('.accordion-content').not(jQuery(this).find('.accordion-content')).slideUp();
      jQuery(this).find('.accordion-content').slideToggle();
  });

  // Question & answer item in slider next prev
  var items = $(".question--answer--data");
  var currentIndex = 0;

  items.hide().eq(currentIndex).show();

  $(".question--answer--main").append('<div class="qa-nav"><button id="prev">Previous</button><button id="next">Next</button></div>');

  $("#next").click(function () {
      items.eq(currentIndex).fadeOut(300, function () { 
          currentIndex = (currentIndex + 1) % items.length; 
          items.eq(currentIndex).fadeIn(300);
      });
  });

  $("#prev").click(function () {
      items.eq(currentIndex).fadeOut(300, function () { 
          currentIndex = (currentIndex - 1 + items.length) % items.length;
          items.eq(currentIndex).fadeIn(300);
      });
  });

  function updateSelectedOptions() {
    var container = $('#views-exposed-form-help-page-1');
    var selectedOptions = [];
    var formActions = container.find('.form-actions'); // Get form-actions div

    // Collect checked checkboxes
    $('.form-radio:checked').each(function () {
        var labelText = $('label[for="' + $(this).attr('id') + '"]').text();
        selectedOptions.push(labelText);
    });

    // Remove existing <h2> before updating
    container.find('#selected-options-header').remove();

    // If there are selected options, add them inside a single <h2> and move form-actions inside
    if (selectedOptions.length > 0) {
        var header = $('<h2 id="selected-options-header">' + selectedOptions + '</h2>');
        header.append(formActions); // Append form-actions inside h2
        container.prepend(header);
    }
  }

  // Detect checkbox changes
  $(document).on('change', '.form-radio', function () {
      updateSelectedOptions();
  });

  // Restore checked options after AJAX refresh
  $(document).ajaxComplete(function () {
      updateSelectedOptions();
  });

  // Run on page load to show already checked options
 

});
