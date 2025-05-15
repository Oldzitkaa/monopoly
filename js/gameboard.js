    document.addEventListener('DOMContentLoaded', () => {
        const diceImage = document.getElementById('diceImage');
        const wynikTekst = document.getElementById('wynikTekst');
        const rollDiceButton = document.getElementById('rollDiceButton');
        const gameBoard = document.getElementById('monopoly-board');
        const urlParams = new URLSearchParams(window.location.search);
        // const gameId = urlParams.get('game_id');
        // const currentPlayerId = 1;

        console.log('Plik gameboard.js załadowany.');
        console.log('ID Gry:', gameId);
        console.log('ID Bieżącego Gracza (Placeholder):', currentPlayerId);

        if (!diceImage || !wynikTekst || !rollDiceButton || !gameId || !currentPlayerId) {
            console.error('Błąd: Nie znaleziono wymaganych elementów lub parametrów.');
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
                    const errorBody = await response.json().catch(() => ({ message: `HTTP ${response.status}` }));
                    console.error('Błąd HTTP:', response.status, errorBody);
                    throw new Error(`Błąd serwera (${response.status}): ${errorBody.message}`);
                }

                const result = await response.json();

                if (result.success) {
                    const rollResult = result.roll_result;
                    const newLocation = result.new_location;
                    const playerId = result.player_id || currentPlayerId;

                    console.log('Wynik rzutu:', rollResult, 'Nowa pozycja:', newLocation);

                    diceImage.src = `../zdj/kostki/${rollResult}.png`;
                    diceImage.alt = `Wynik: ${rollResult}`;

                    movePlayerToken(playerId, newLocation);

                    setTimeout(() => {
                        wynikTekst.textContent = rollResult;
                    }, 2000);
                } else {
                    console.error('Błąd API:', result.message);
                    wynikTekst.textContent = `Błąd: ${result.message}`;
                }

            } catch (error) {
                console.error('Błąd komunikacji z API:', error);
                wynikTekst.textContent = `Błąd: ${error.message}`;
            } finally {
                rollDiceButton.disabled = false;
                setTimeout(() => {
                    rollDiceButton.textContent = 'Rzuć kostką';
                }, 500);
            }
        }

        function movePlayerToken(playerId, newTileId) {
            const playerClass = `player-${playerId}`;
            const playerToken = document.querySelector(`.${playerClass}`);
            const newTile = document.getElementById(`space-${newTileId}`);

            if (playerToken && newTile) {
                const currentTile = playerToken.closest('.tile');
                if (currentTile && currentTile.querySelector('.players-on-tile')) {
                    currentTile.querySelector('.players-on-tile').removeChild(playerToken);
                }

                const playerContainer = newTile.querySelector('.players-on-tile');
                if (playerContainer) {
                    playerContainer.appendChild(playerToken);
                }
            } else {
                console.warn('Nie znaleziono pionka lub pola:', playerId, newTileId);
            }
        }
    });
