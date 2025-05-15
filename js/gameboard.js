document.addEventListener('DOMContentLoaded', () => {
    const diceImage = document.getElementById('diceImage');
    const wynikTekst = document.getElementById('wynikTekst');
    const rollDiceButton = document.getElementById('rollDiceButton');
    const gameBoard = document.getElementById('monopoly-board'); 
    const urlParams = new URLSearchParams(window.location.search);
    const gameId = urlParams.get('game_id');
    const currentPlayerId = 1;
    console.log('Plik gameboard.js załadowany.');
    console.log('ID Gry:', gameId);
    console.log('ID Bieżącego Gracza (Placeholder):', currentPlayerId);
    if (!diceImage || !wynikTekst || !rollDiceButton || !gameId || !currentPlayerId) {
        console.error('Błąd: Nie znaleziono wszystkich wymaganych elementów planszy, brakuje game_id w URL lub nie określono currentPlayerId.');
        if (rollDiceButton) {
            rollDiceButton.textContent = 'Błąd konfiguracji';
            rollDiceButton.disabled = true;
        }
        return; 
    }
    rollDiceButton.addEventListener('click', handleRollDice);
    function startDiceAnimation() {
        if (diceImage) {
            diceImage.classList.add('animacja'); 
            setTimeout(() => {
                diceImage.classList.remove('animacja');
            }, 1000);
        }
    }
    async function handleRollDice() {
        rollDiceButton.disabled = true; 
        wynikTekst.textContent = 'Rzut...'; 
        startDiceAnimation(); 
        try {
            const response = await fetch('roll_dice_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json', 
                },
                body: JSON.stringify({
                    game_id: gameId,
                    player_id: currentPlayerId
                }),
            });
            if (!response.ok) {
                const errorBody = await response.json().catch(() => ({ message: `HTTP status ${response.status}`, detail: 'Brak szczegółów błędu z serwera.' }));
                console.error('Błąd HTTP podczas rzutu kostką:', response.status, errorBody);
                throw new Error(`Błąd serwera (${response.status}): ${errorBody.message || 'Nieznany błąd API rzutu kostką'}`);
            }
            const result = await response.json();
            if (result.success) {
                const rollResult = result.roll_result;
                console.log('Wynik rzutu kostką z serwera:', rollResult);
                diceImage.src = `../zdj/kostki/${rollResult}.png`;
                diceImage.alt = `Wynik: ${rollResult}`;
                setTimeout(() => {
                    wynikTekst.textContent = rollResult; 
                }, 2000); 
            } else {
                console.error('Błąd API rzutu kostką:', result.message);
                wynikTekst.textContent = `Błąd: ${result.message}`;
            }
        } catch (error) {
            console.error('Wystąpił błąd podczas komunikacji z API rzutu kostką:', error);
            wynikTekst.textContent = `Błąd: ${error.message}`;
        } finally {
            rollDiceButton.disabled = false;
            setTimeout(() => {
                rollDiceButton.textContent = 'Rzuć kostką';
            }, 500); 
        }
    }
});