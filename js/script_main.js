    const step1Div = document.querySelector('.logo_div.step1');
    const step2Div = document.querySelector('.logo_div.step2');
    // const step3Div = document.querySelector('.logo_div.step3');
    const step3NickDiv = document.querySelector('.logo_div.step3.nick');
    const step3CharactersDiv = document.querySelector('.logo_div.step3.characters');
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
        step2Div.style.zIndex = '10';

        step1Div.style.opacity = '0';
        step1Div.style.transition = 'opacity 0.5s ease-in-out';

        setTimeout(() => {
            step1Div.style.display = 'none';

            step1Div.style.zIndex = '1';

            step2Div.style.opacity = '0';
            step2Div.style.display = 'flex';
            step2Div.style.transition = 'opacity 0.5s ease-in-out';
            setTimeout(() => {
            step2Div.style.opacity = '1';
            }, 50);

            //tło
            backgImg.style.transform = 'translateX(10%)';
            backgImg.style.transition = 'transform 0.5s ease-in-out';
        }, 500);
    }

    function two() {
        step3NickDiv.style.zIndex = '15';
        step3CharactersDiv.style.zIndex = '14';
        step3NickDiv.style.display = 'flex'; // Upewnij się, że div z nickami jest flexem (jeśli chcesz go wyświetlić)
        step3CharactersDiv.style.display = 'flex'; // Ustaw display na flex, aby go wyświetlić
      
        step2Div.style.opacity = '0';
        step3NickDiv.style.opacity = '1';
        step3CharactersDiv.style.opacity = '1';
      
        step3NickDiv.style.transition = 'opacity 0.5s ease-in-out';
        step3CharactersDiv.style.transition = 'opacity 0.5s ease-in-out';
        step2Div.style.transition = 'opacity 0.5s ease-in-out';
      
        setTimeout(() => {
          step2Div.style.display = 'none';
          // step3NickDiv.style.opacity = '1'; // Już ustawione wyżej
          // step3CharactersDiv.style.opacity = '1'; // Już ustawione wyżej
        }, 500);
    }