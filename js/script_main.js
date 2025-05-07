    const step1Div = document.querySelector('.logo_div.step1');
    const step2Div = document.querySelector('.logo_div.step2');
    const backgImg = document.querySelector('.backg');

    const quantityInput = document.getElementById('quantity');
    const playerCountSpan = document.getElementById('playerCount');

    document.addEventListener('DOMContentLoaded', () => {
        if (quantityInput && playerCountSpan) {
            quantityInput.addEventListener('input', () => {
                playerCountSpan.textContent = quantityInput.value;
            });
        }
    });

    function one() {
       step1Div.style.opacity = '0';
        step1Div.style.transition = 'opacity 0.5s ease-in-out';
      
        setTimeout(() => {
          step1Div.style.display = 'none';
      
          step2Div.style.opacity = '0';
          step2Div.style.display = 'block';
          step2Div.style.transition = 'opacity 0.5s ease-in-out';
          setTimeout(() => {
            step2Div.style.opacity = '1';
          }, 50);
      
          //tło
          backgImg.style.transform = 'translateX(10%)';
          backgImg.style.transition = 'transform 0.5s ease-in-out';
        }, 500);
      }