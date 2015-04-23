window.onload = function() {
    var menuClicked = false,
        elements = document.querySelectorAll('.nav--drop'),
        showing = false,
        search = document.getElementById('js-search');
        searchBtn = document.getElementById('js-searchBtn'),
        menuVisible = false,
        menuBtn = document.getElementById('js-menuBtn'),
        menu = document.getElementById('js-menu');

    searchBtn.onclick = function() {
        if(!showing){
            if (search.classList) {
              document.getElementById("s").focus();
              search.classList.add('toggle');

             
           } else {
              search.className += ' ' + 'toggle';
              
            // Auto-focus search input when visible
           
          }
          showing = true;
      } else if(showing) {
        if (search.classList) {
          search.classList.remove('toggle'); 
        } else {
          search.toggle = search.toggle.replace(new RegExp('(^|\\b)' + toggle.split(' ').join('|') + '(\\b|$)', 'gi'), ' ');
        }
        showing = false;
      }

    return false;
    }

    menuBtn.onclick = function() {
        if(!menuVisible){
            if (menu.classList) {
              menu.classList.add('menuShow');
           } else {
              menu.className += ' ' + 'menuShow';
          }
          menuBtn.innerHTML = "X";
          menuVisible = true;
      } else if(menuVisible) {
        if (menu.classList) {
          menu.classList.remove('menuShow'); 
        } else {
          menu.menuShow = menu.menuShow.replace(new RegExp('(^|\\b)' + menuShow.split(' ').join('|') + '(\\b|$)', 'gi'), ' ');
        }
        menuBtn.innerHTML = "Menu";
        menuVisible = false;
      }

    return false;
    }

    Array.prototype.forEach.call(elements, function(el, i){
        el.onclick = function() {
            if(!menuClicked) {
                  if (el.parentNode.classList) {
                    el.parentNode.classList.add('show');
                 } else {
                    el.parentNode.className += ' ' + 'show';
                }
                menuClicked = true;
            } else if(menuClicked) {
                  if (el.parentNode.classList) {
                    el.parentNode.classList.remove('show');
                 } else {
                   el.parentNode.show = el.parentNode.show.replace(new RegExp('(^|\\b)' + show.split(' ').join('|') + '(\\b|$)', 'gi'), ' ');
                }
                menuClicked = false;
            }
        }
    });
};
