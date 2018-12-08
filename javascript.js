function drawOnCanvas(){
    canvas = document.getElementById('logo');
    if (canvas !== null)
    {
        context              = canvas.getContext('2d');
        context.font         = 'bold italic 40px Georgia';
        context.textBaseline = 'top';
        image                = new Image();
        image.onload = function()
        {
            context.drawImage(image, 0, 20)
            gradient = context.createLinearGradient(0, 0, 0, 40)
            gradient.addColorStop(0.70, 'maroon')
            gradient.addColorStop(0.00, 'gold')
            context.fillStyle = gradient
            context.fillText( "Windsor", 0, 0)
            gradient = context.createLinearGradient(200, 120, 200, 200)
            gradient.addColorStop(0.00, 'gold')
            gradient.addColorStop(0.30, 'maroon')
            context.fillStyle = gradient
            context.fillText( "Music Boosters", 200, 120)
        }
        image.src = 'tscore.png';
    }
}


function O(i) { return typeof i == 'object' ? i : document.getElementById(i) }
function S(i) { return O(i).style                                            }
function C(i) { return document.getElementsByClassName(i)                    }
  
/*  jQuery ready function. Specify a function to execute when the DOM is fully loaded.  */
$(document).ready(
  function () {
    drawOnCanvas();
      
    /* hovering effect and submenu management on vav bar */
    $('.nav li').hover(
      function () { //appearing on hover
        $('ul', this).fadeIn();
      },
      function () { //disappearing on hover
        $('ul', this).fadeOut();
      }
    );
  }
);

    
