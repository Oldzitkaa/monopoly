document.addEventListener('DOMContentLoaded', () => {
    const diceImage = document.getElementById('diceImage');
    const wynikTekst = document.getElementById('wynikTekst');
    const rollDiceButton = document.getElementById('rollDiceButton');
    const playerInfoContainer = document.getElementById('playerInfoContainer');
    const playerInfoBoxes = document.querySelectorAll('.player-info-box');
    const cardSlotText = document.querySelector('.card-slot.card-text');
    const cardSlotChoose = document.querySelector('.card-slot.card-choose');

    // These variables are assumed to be defined globally in the HTML
    // from your PHP file (gameboard.php) like this:
    // <script>
    //     const gameId = <?php echo json_encode($gameId); ?>;
    //     let currentPlayerId = <?php echo json_encode($currentPlayerId); ?>; // The ID of the player currently viewing the board (THIS WILL NOW CHANGE)
    //     const initialCurrentTurnPlayerId = <?php echo json_encode($initialCurrentTurnPlayerId); ?>; // The ID of the player whose turn it is when the page loads
    // </script>
    // WAŻNE: Upewnij się, że w gameboard.php zmienna currentPlayerId jest deklarowana jako 'let', a nie 'const'.
    // Przykład w gameboard.php:
    // <script>
    //     const gameId = <?php echo json_encode($gameId); ?>;
    //     let currentPlayerId = <?php echo json_encode($currentPlayerId); ?>; // Zmieniono na 'let'
    //     const initialCurrentTurnPlayerId = <?php echo json_encode($initialCurrentTurnPlayerId); ?>;
    // </script>
    let currentTurnPlayerId = initialCurrentTurnPlayerId; // This will be updated after each roll

    // Interval for refreshing game state (every 3 seconds)
    const GAME_STATE_REFRESH_INTERVAL = 3000;
    let gameStateInterval;

    /**
     * Translates property type to Polish.
     * @param {string} type - Property type (e.g., 'restaurant', 'hotel').
     * @returns {string} Translated type.
     */
    function translatePropertyType(type) {
        switch (type) {
            case 'restaurant': return 'Restauracja';
            case 'hotel': return 'Hotel';
            default: return type;
        }
    }

    /**
     * Translates region name to Polish.
     * @param {string} region - Region name (e.g., 'Azja', 'Amerykapln').
     * @returns {string} Translated region name.
     */
    function translateRegion(region) {
        switch (region) {
            case 'Azja': return 'Azja';
            case 'Afryka': return 'Afryka';
            case 'Australia': return 'Australia';
            case 'Amerykapln': return 'Ameryka Północna';
            case 'Amerykapld': return 'Ameryka Południowa';
            case 'Europa': return 'Europa';
            case 'Pojedynek': return 'Pojedynek';
            case 'Niespodzianka': return 'Niespodzianka';
            case 'Szkolenie': return 'Szkolenie';
            case 'Urlop': return 'Urlop';
            case 'regionenter': return 'Wjazd do regionu';
            case 'Specjalne': return 'Specjalne';
            default: return region;
        }
    }

    // Basic validation of initial variables
    if (!diceImage || !wynikTekst || !rollDiceButton || typeof gameId === 'undefined' || typeof currentPlayerId === 'undefined' || typeof initialCurrentTurnPlayerId === 'undefined') {
        if (rollDiceButton) {
            rollDiceButton.textContent = 'Błąd konfiguracji gry (brak ID)';
            rollDiceButton.disabled = true;
        }
        console.error("Błąd konfiguracji: gameId, currentPlayerId lub initialCurrentTurnPlayerId nie są zdefiniowane.");
        return;
    }

    // Debug: Logging initial values
    console.log('Initial configuration:', {
        gameId: gameId,
        currentPlayerId: currentPlayerId,
        initialCurrentTurnPlayerId: initialCurrentTurnPlayerId
    });

    // Initial check of whose turn it is after page load
    updateRollButtonState();
    updateCurrentPlayerIndicator(currentTurnPlayerId);

    // Start refreshing game state every X seconds
    startGameStateRefresh();

    // Add event listener for dice roll button click
    rollDiceButton.addEventListener('click', handleRollDice);

    /**
     * Starts the dice animation.
     */
    function startDiceAnimation() {
        if (diceImage) {
            diceImage.classList.add('animacja');
            setTimeout(() => {
                diceImage.classList.remove('animacja');
            }, 1000);
        }
    }

    /**
     * Updates the state of the dice roll button (enabled/disabled)
     * depending on whether it's the current player's turn.
     */
    function updateRollButtonState() {
        // For an offline game on one device, currentPlayerId IS the currentTurnPlayerId
        // So the button should always be enabled when it's "this device's" turn (which is always)
        // However, the logic below still checks against currentTurnPlayerId which is updated from server.
        // This is good for showing whose turn it is even if the button is always enabled.
        console.log('Sprawdzanie stanu przycisku - currentPlayerId:', currentPlayerId, 'currentTurnPlayerId:', currentTurnPlayerId);

        if (currentPlayerId === currentTurnPlayerId) {
            rollDiceButton.disabled = false;
            rollDiceButton.textContent = 'Rzuć kostką';
            console.log('Przycisk aktywny - to twoja tura');
        } else {
            rollDiceButton.disabled = true;
            const activePlayerNameElement = document.querySelector(`.player-info-box[data-player-id="${currentTurnPlayerId}"] .player-name`);
            if (activePlayerNameElement) {
                rollDiceButton.textContent = `Tura: ${activePlayerNameElement.textContent}`;
            } else {
                rollDiceButton.textContent = 'Czekaj na swoją turę';
            }
            console.log('Przycisk nieaktywny - nie twoja tura');
        }
    }

    // Helper function for displaying notifications
    function showNotification(message) {
        console.log('Powiadomienie:', message);

        // Remove previous notification if it exists
        const existingNotification = document.querySelector('.game-notification');
        if (existingNotification) {
            existingNotification.remove();
        }

        // Create new notification
        const notification = document.createElement('div');
        notification.className = 'game-notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4CAF50;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 1000;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            animation: slideIn 0.3s ease;
        `;
        notification.textContent = message;
        document.body.appendChild(notification);

        // Automatically remove after 3 seconds
        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }
        }, 3000);
    }

    // Add CSS styles for notification animations
    const notificationStyles = document.createElement('style');
    notificationStyles.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(notificationStyles);

    /**
     * Updates the visual indicator of the active player's turn.
     * @param {number} playerId - ID of the player whose turn is active.
     */
    function updateCurrentPlayerIndicator(playerId) {
        playerInfoBoxes.forEach(box => {
            box.classList.remove('active-turn'); // Remove active turn class from all
        });

        const currentPlayerBox = document.querySelector(`.player-info-box[data-player-id="${playerId}"]`);
        if (currentPlayerBox) {
            currentPlayerBox.classList.add('active-turn'); // Add class to active player
        }
    }

    /**
     * Handles dice roll: sends request to server, updates UI.
     */
    async function handleRollDice() {
        // Immediately disable the button to prevent multiple clicks
        rollDiceButton.disabled = true;
        wynikTekst.textContent = 'Rzut...';
        startDiceAnimation();

        // Clear card slots
        if (cardSlotText) cardSlotText.textContent = '';
        if (cardSlotChoose) {
            cardSlotChoose.innerHTML = '';
            cardSlotChoose.style.display = 'none';
        }

        // Stop game state refresh during roll and action
        stopGameStateRefresh();

        try {
            // Send dice roll request to PHP API
            const response = await fetch('roll_dice_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    game_id: gameId,
                    player_id: currentPlayerId // Send ID of the player making the roll
                }),
            });

            // Handle HTTP response errors
            if (!response.ok) {
                const errorBody = await response.json().catch(() => ({ message: `HTTP ${response.status}` }));
                throw new Error(`Błąd serwera (${response.status}): ${errorBody.message}`);
            }

            const result = await response.json();
            console.log('Pełna odpowiedź z roll_dice_api.php:', result); // Debug

            if (result.success) {
                const rollResult = result.roll_result;
                const newLocation = result.new_location;
                // const newLocation = 2;
                const playerWhoRolledId = currentPlayerId; // This is the player who just rolled

                console.log(`Gracz ID: ${playerWhoRolledId}, nowa pozycja: ${newLocation}`);

                // Update dice image
                diceImage.src = `../zdj/kostki/${rollResult}.png`;
                diceImage.alt = `Wynik: ${rollResult}`;

                // Move player token on the board
                movePlayerToken(playerWhoRolledId, newLocation);

                // Update player's coins and location display
                updatePlayerDisplay(playerWhoRolledId, result.new_coins, newLocation);

                // --- KEY TURN MANAGEMENT LOGIC ---
                console.log('PRZED aktualizacją tury - currentTurnPlayerId:', currentTurnPlayerId);

                // Check various possible field names in the response
                let nextPlayerIdFromServer = result.next_player_id || result.current_player_id || result.turn_player_id;

                if (nextPlayerIdFromServer) {
                    console.log('Znaleziono next_player_id w odpowiedzi:', nextPlayerIdFromServer);
                    currentTurnPlayerId = nextPlayerIdFromServer;
                    // For offline single-device game, currentPlayerId must also update
                    currentPlayerId = nextPlayerIdFromServer; // <--- THIS IS THE CRUCIAL CHANGE
                    console.log('PO aktualizacji tury - currentTurnPlayerId:', currentTurnPlayerId);
                    console.log('PO aktualizacji tury - currentPlayerId (viewer):', currentPlayerId);


                    // Update visual indicator of active player
                    updateCurrentPlayerIndicator(currentTurnPlayerId);

                    // Optionally show turn change notification
                    if (nextPlayerIdFromServer !== playerWhoRolledId) {
                        const nextPlayerName = document.querySelector(`.player-info-box[data-player-id="${nextPlayerIdFromServer}"] .player-name`);
                        if (nextPlayerName) {
                            showNotification(`Tura przeszła na: ${nextPlayerName.textContent}`);
                        }
                    }
                } else {
                    console.warn('Brak informacji o następnym graczu w odpowiedzi z serwera. Używam fallbacku.');
                    // Fallback - if server didn't return next_player_id, find next player locally
                    const players = Array.from(document.querySelectorAll('.player-info-box')).map(box => ({
                        id: parseInt(box.dataset.playerId),
                        name: box.querySelector('.player-name').textContent
                    }));

                    const currentPlayerIndex = players.findIndex(p => p.id === playerWhoRolledId); // Use playerWhoRolledId for finding index
                    const nextPlayerIndex = (currentPlayerIndex + 1) % players.length;
                    const nextPlayer = players[nextPlayerIndex];

                    if (nextPlayer) {
                        currentTurnPlayerId = nextPlayer.id;
                        currentPlayerId = nextPlayer.id; // <--- THIS IS THE CRUCIAL CHANGE for fallback
                        updateCurrentPlayerIndicator(currentTurnPlayerId);
                        showNotification(`Tura przeszła na: ${nextPlayer.name}`);
                        console.log('Użyto fallback dla zmiany tury na:', nextPlayer.name);
                    }
                }

                // ZMIANA: Nie włączaj przycisku rzutu kostką tutaj. Zostanie włączony po interakcji.
                // updateRollButtonState();

                if (result.new_round_started) {
                    console.log("Rozpoczęła się nowa runda!");
                    showNotification("Rozpoczęła się nowa runda!");
                }

                // Rest of the code remains unchanged...
                setTimeout(async () => {
                    wynikTekst.textContent = rollResult;
                    try {
                        // Get tile message
                        const messageResponse = await fetch(`get_tile_message.php?location=${newLocation}`);
                        if (!messageResponse.ok) {
                            throw new Error(`Błąd pobierania wiadomości o polu: HTTP ${messageResponse.status}`);
                        }
                        const messageHtml = await messageResponse.text();

                        if (cardSlotText) {
                            cardSlotText.innerHTML = messageHtml;
                            cardSlotText.style.display = 'block';
                        }

                        // Get action options for the tile
                        if (cardSlotChoose) {
                            cardSlotChoose.innerHTML = '<p>Ładowanie opcji akcji...</p>';
                            cardSlotChoose.style.display = 'block';

                            try {
                                const chooseResponse = await fetch(`get_tile_choose.php?location=${newLocation}&player_id=${playerWhoRolledId}&game_id=${gameId}`);
                                if (!chooseResponse.ok) {
                                    throw new Error(`Błąd pobierania opcji akcji: HTTP ${chooseResponse.status}`);
                                }
                                const chooseHtml = await chooseResponse.text();

                                cardSlotChoose.innerHTML = chooseHtml;

                                // ZMIANA: Sprawdź, czy jakieś przyciski akcji zostały wyrenderowane.
                                const actionButtons = cardSlotChoose.querySelectorAll('.action-button');
                                if (actionButtons.length === 0) {
                                    // Jeśli nie ma przycisków akcji, odblokuj przycisk rzutu kostką
                                    updateRollButtonState();
                                    startGameStateRefresh();
                                } else {
                                    // Add event listeners to action buttons
                                    actionButtons.forEach(button => {
                                        button.addEventListener('click', async (event) => {
                                            event.preventDefault();
                                            event.stopPropagation();

                                            const actionType = event.target.dataset.actionType;
                                            // ZMIANA: Ustaw propertyId na null dla akcji 'duel', aby uniknąć wysyłania ID pola jako ID gracza
                                            const propertyId = (actionType === 'duel') ? null : (event.target.dataset.propertyId || newLocation);
                                            const targetPlayerIdForDuel = event.target.dataset.playerId; // For duel action

                                            // Additional actionType check
                                            if (!actionType) {
                                                console.error('Błąd: Brak atrybutu data-action-type na klikniętym przycisku akcji. Sprawdź HTML generowany przez get_tile_choose.php');
                                                if (cardSlotText) cardSlotText.textContent = 'Błąd: Brak typu akcji dla przycisku.';
                                                return; // Stop further processing
                                            }

                                            console.log('Sending tile action:', {
                                                actionType: actionType,
                                                playerWhoRolledId: playerWhoRolledId, // This is the player who initiated the roll
                                                gameId: gameId,
                                                newLocation: newLocation,
                                                propertyId: propertyId, // Może być null dla pojedynku
                                                targetPlayerIdForDuel: targetPlayerIdForDuel // Pass target player for duel
                                            });

                                            event.target.disabled = true;

                                            try {
                                                // Pass targetPlayerIdForDuel if actionType is 'duel'
                                                await handleTileAction(actionType, playerWhoRolledId, gameId, newLocation, propertyId, targetPlayerIdForDuel);
                                            } catch (actionError) {
                                                console.error('Błąd podczas akcji na polu:', actionError);
                                            } finally {
                                                event.target.disabled = false;
                                                // ZMIANA: Zawsze wyczyść sloty i odblokuj przycisk rzutu kostką po zakończeniu interakcji
                                                if (cardSlotChoose) {
                                                    cardSlotChoose.innerHTML = '';
                                                    cardSlotChoose.style.display = 'none';
                                                }
                                                updateRollButtonState(); // Re-enable button after action is processed
                                                startGameStateRefresh(); // Resume game state refresh after action completes
                                            }
                                        });
                                    });
                                }

                            } catch (chooseError) {
                                console.error("Błąd podczas pobierania lub przetwarzania opcji akcji:", chooseError);
                                if (cardSlotChoose) {
                                    cardSlotChoose.innerHTML = '<p style="color: red;">Błąd ładowania opcji akcji.</p>';
                                    cardSlotChoose.style.display = 'block';
                                }
                                updateRollButtonState(); // Odblokuj przycisk rzutu kostką w przypadku błędu
                                startGameStateRefresh(); // Wznów odświeżanie stanu gry po błędzie
                            }
                        } else {
                            console.warn("Element cardSlotChoose nie został znaleziony.");
                            updateRollButtonState(); // Odblokuj przycisk rzutu kostką, jeśli nie ma slotu na akcje
                            startGameStateRefresh(); // Wznów odświeżanie stanu gry
                        }

                    } catch (msgError) {
                        console.error("Błąd podczas pobierania wiadomości o polu:", msgError);
                        if (cardSlotText) {
                            cardSlotText.textContent = "Błąd ładowania wiadomości o polu.";
                        }
                        if (cardSlotChoose) {
                            cardSlotChoose.innerHTML = '';
                            cardSlotChoose.style.display = 'none';
                        }
                        updateRollButtonState(); // Odblokuj przycisk rzutu kostką w przypadku błędu
                        startGameStateRefresh(); // Wznów odświeżanie stanu gry po błędzie
                    }
                }, 2000);

            } else {
                // Handle error returned by server
                wynikTekst.textContent = `Błąd: ${result.message}`;
                if (cardSlotText) cardSlotText.textContent = result.message;
                if (cardSlotChoose) {
                    cardSlotChoose.innerHTML = '';
                    cardSlotChoose.style.display = 'none';
                }
                updateRollButtonState(); // Odblokuj przycisk rzutu kostką w przypadku błędu
                startGameStateRefresh(); // Wznów odświeżanie stanu gry po błędzie
            }

        } catch (error) {
            // Handle network errors
            console.error('Błąd komunikacji z serwerem:', error);
            wynikTekst.textContent = `Błąd: ${error.message}`;
            if (cardSlotText) {
                cardSlotText.textContent = `Błąd sieci/serwera: ${error.message}`;
            }
            if (cardSlotChoose) {
                cardSlotChoose.innerHTML = '';
                cardSlotChoose.style.display = 'none';
            }
            updateRollButtonState(); // Odblokuj przycisk rzutu kostką w przypadku błędu
            startGameStateRefresh(); // Wznów odświeżanie stanu gry po błędzie
        }
    }

    /**
     * Moves the player token to a new position on the board.
     * @param {number} playerId - ID of the player whose token is to be moved.
     * @param {number} newTileId - ID of the tile to which the token is to be moved.
     */
    function movePlayerToken(playerId, newTileId) {
        const playerToken = document.querySelector(`.player-token[data-player-id="${playerId}"]`);
        const newTile = document.getElementById(`space-${newTileId}`);

        if (playerToken && newTile) {
            const currentTilePlayersContainer = playerToken.closest('.players-on-tile');
            if (currentTilePlayersContainer && currentTilePlayersContainer !== newTile.querySelector('.players-on-tile')) {
                currentTilePlayersContainer.removeChild(playerToken);
            }

            const newTilePlayersContainer = newTile.querySelector('.players-on-tile');
            if (newTilePlayersContainer) {
                // Prevent adding token if it's already there
                if (!newTilePlayersContainer.querySelector(`.player-token[data-player-id="${playerId}"]`)) {
                    newTilePlayersContainer.appendChild(playerToken);
                }
            }
        } else {
            console.warn(`Nie znaleziono pionka gracza (ID: ${playerId}) lub pola (ID: ${newTileId}).`);
        }
    }

    // Add event listeners for each player info box
    playerInfoBoxes.forEach(box => {
        box.addEventListener('click', async (e) => {
            e.stopPropagation(); // Prevent propagation so clicking a box doesn't close others

            const isActive = box.classList.contains('active');

            // Close all open player boxes
            playerInfoBoxes.forEach(otherBox => {
                otherBox.classList.remove('active');
            });
            playerInfoContainer.classList.remove('has-active-player');

            // If the clicked box was not active, open it
            if (!isActive) {
                box.classList.add('active');
                playerInfoContainer.classList.add('has-active-player');

                const playerId = box.dataset.playerId;
                if (playerId) {
                    const propertiesTableContainer = box.querySelector('.player-properties-table-container');
                    const skillsTableContainer = box.querySelector('.player-skills-table-container');

                    if (propertiesTableContainer) propertiesTableContainer.innerHTML = '<h3>Nieruchomości</h3><p>Ładowanie...</p>';
                    if (skillsTableContainer) skillsTableContainer.innerHTML = '<h3>Umiejętności</h3><p>Ładowanie...</p>';

                    try {
                        const data = await loadPlayerDetails(playerId);
                        if (data) {
                            // Populate properties table
                            if (propertiesTableContainer) {
                                propertiesTableContainer.innerHTML = '<h3>Nieruchomości</h3>';
                                if (Array.isArray(data.properties) && data.properties.length > 0) {
                                    let tableHtml = '<table class="player-properties-table"><thead><tr><th>Nazwa</th><th>Typ/Grupa</th><th>Koszt/Czynsz</th><th>Upgrade</th></tr></thead><tbody>';
                                    data.properties.forEach(prop => {
                                        let costRentDisplay = '';
                                        if (prop.cost) { costRentDisplay += `${prop.cost} zł`; }
                                        if (prop.calculated_rent) {
                                            if (costRentDisplay) costRentDisplay += ' | ';
                                            costRentDisplay += `${prop.calculated_rent} zł`;
                                        }
                                        if (prop.level !== undefined) { costRentDisplay += ` (Poziom: ${prop.level})`; }
                                        if (prop.is_mortgaged) { costRentDisplay += ` (Zastawiono)`; }
                                        if (!costRentDisplay) { costRentDisplay = 'N/A'; }

                                        let upgradeCostDisplay = 'B/D';
                                        if (prop.level >= 5) {
                                            upgradeCostDisplay = 'MAX. POZIOM';
                                        } else if (prop.upgrade_cost !== null && prop.upgrade_cost !== undefined) {
                                            upgradeCostDisplay = `${prop.upgrade_cost} zł`;
                                        }

                                        const translatedType = translatePropertyType(prop.type || '');
                                        const translatedRegion = translateRegion(prop.region || '');

                                        tableHtml += `<tr>
                                            <td>${prop.name}</td>
                                            <td style="${prop.color ? 'border-left: 5px solid ' + prop.color + '; padding-left: 5px;' : ''}">${translatedType}<br>${translatedRegion}</td>
                                            <td class="property-cost-rent">${costRentDisplay}</td>
                                            <td class="property-upgrade-cost">${upgradeCostDisplay}</td>
                                        </tr>`;
                                    });
                                    tableHtml += '</tbody></table>';
                                    propertiesTableContainer.innerHTML += tableHtml;
                                } else {
                                    propertiesTableContainer.innerHTML += '<p>Brak posiadanych nieruchomości.</p>';
                                }
                            }

                            // Populate skills table
                            if (skillsTableContainer && data.player_stats) {
                                skillsTableContainer.innerHTML = '<h3>Umiejętności</h3>';
                                let tableHtml = '<table class="player-stats-table"><thead><tr><th>Umiejętność</th><th>Wartość</th></tr></thead><tbody>';
                                tableHtml += `<tr><td>Um. gotowania</td><td class="numeric">${data.player_stats.cook_skill}</td></tr>`;
                                tableHtml += `<tr><td>Tolerancja</td><td class="numeric">${data.player_stats.tolerance}</td></tr>`;
                                tableHtml += `<tr><td>Zmysł biznesowy</td><td class="numeric">${data.player_stats.business_acumen}</td></tr>`;
                                tableHtml += `<tr><td>Pojemność brzucha</td><td class="numeric">${data.player_stats.belly_capacity}</td></tr>`;
                                tableHtml += `<tr><td>Zmysł przypraw</td><td class="numeric">${data.player_stats.spice_sense}</td></tr>`;
                                tableHtml += `<tr><td>Czas przygotowania</td><td class="numeric">${data.player_stats.prep_time}</td></tr>`;
                                tableHtml += `<tr><td>Tradycja</td><td class="numeric">${data.player_stats.tradition_affinity}</td></tr>`;
                                tableHtml += '</tbody></table>';
                                skillsTableContainer.innerHTML += tableHtml;
                            }
                        }
                    } catch (error) {
                        console.error('Error loading player details:', error);
                        if (propertiesTableContainer) propertiesTableContainer.innerHTML = '<h3>Nieruchomości</h3><p>Błąd ładowania nieruchomości.</p>';
                        if (skillsTableContainer) skillsTableContainer.innerHTML = '<h3>Umiejętności</h3><p>Błąd ładowania umiejętności.</p>';
                    }
                }
            }
        });
    });

    // Close open player boxes if clicked outside
    document.addEventListener('click', (event) => {
        if (!event.target.closest('.player-info-box')) {
            playerInfoBoxes.forEach(box => {
                box.classList.remove('active');
            });
            playerInfoContainer.classList.remove('has-active-player');
        }
    });

    /**
     * Loads player details (properties and skills) from the server.
     * @param {number} playerId - Player ID.
     * @returns {Promise<object|null>} Object with player data or null on error.
     */
    async function loadPlayerDetails(playerId) {
        try {
            const response = await fetch('get_player_properties.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    player_id: playerId,
                    game_id: gameId
                }),
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    return data;
                } else {
                    console.error('API Error (loadPlayerDetails):', data.message);
                    return null;
                }
            } else {
                console.error('HTTP Error (loadPlayerDetails):', response.status, response.statusText);
                return null;
            }
        } catch (error) {
            console.error('Fetch Error (loadPlayerDetails):', error);
            return null;
        }
    }

    /**
     * Updates displayed player information (coins, location, properties/skills).
     * @param {number} playerId - ID of the player to update.
     * @param {number} newCoins - New player coin count.
     * @param {number|null} newLocation - New player location (can be null if only updating coins).
     */
    function updatePlayerDisplay(playerId, newCoins, newLocation) {
        const playerBox = document.querySelector(`.player-info-box[data-player-id="${playerId}"]`);
        if (playerBox) {
            const coinsSpan = playerBox.querySelector('.player-coins');
            const locationSpan = playerBox.querySelector('.player-location');

            if (coinsSpan) {
                coinsSpan.textContent = `${newCoins} zł`;
            }
            if (locationSpan && newLocation !== null) { // Update location only if provided
                locationSpan.textContent = `Pole ${newLocation}`;
            }

            // Refresh properties/skills only if the box is currently active (open)
            if (playerBox.classList.contains('active')) {
                const propertiesTableContainer = playerBox.querySelector('.player-properties-table-container');
                const skillsTableContainer = playerBox.querySelector('.player-skills-table-container');

                if (propertiesTableContainer) propertiesTableContainer.innerHTML = '<h3>Nieruchomości</h3><p>Ładowanie...</p>';
                if (skillsTableContainer) skillsTableContainer.innerHTML = '<h3>Umiejętności</h3><p>Ładowanie...</p>';

                loadPlayerDetails(playerId).then(data => {
                    if (data) {
                        // Populate properties table
                        if (propertiesTableContainer) {
                            propertiesTableContainer.innerHTML = '<h3>Nieruchomości</h3>';
                            if (Array.isArray(data.properties) && data.properties.length > 0) {
                                let tableHtml = '<table class="player-properties-table"><thead><tr><th>Nazwa</th><th>Typ/Grupa</th><th>Koszt/Czynsz</th><th>Upgrade</th></tr></thead><tbody>';
                                data.properties.forEach(prop => {
                                    let costRentDisplay = '';
                                    if (prop.cost) { costRentDisplay += `${prop.cost} zł`; }
                                    if (prop.calculated_rent) {
                                        if (costRentDisplay) costRentDisplay += ' | ';
                                        costRentDisplay += `${prop.calculated_rent} zł`;
                                    }
                                    if (prop.level !== undefined) { costRentDisplay += ` (Poziom: ${prop.level})`; }
                                    if (prop.is_mortgaged) { costRentDisplay += ` (Zastawiono)`; }
                                    if (!costRentDisplay) { costRentDisplay = 'N/A'; }

                                    let upgradeCostDisplay = 'B/D';
                                    if (prop.level >= 5) {
                                        upgradeCostDisplay = 'MAX. POZIOM';
                                    } else if (prop.upgrade_cost !== null && prop.upgrade_cost !== undefined) {
                                        upgradeCostDisplay = `${prop.upgrade_cost} zł`;
                                    }

                                    const translatedType = translatePropertyType(prop.type || '');
                                    const translatedRegion = translateRegion(prop.region || '');

                                    tableHtml += `<tr>
                                        <td>${prop.name}</td>
                                        <td style="${prop.color ? 'border-left: 5px solid ' + prop.color + '; padding-left: 5px;' : ''}">${translatedType}<br>${translatedRegion}</td>
                                        <td class="property-cost-rent">${costRentDisplay}</td>
                                        <td class="property-upgrade-cost">${upgradeCostDisplay}</td>
                                    </tr>`;
                                });
                                tableHtml += '</tbody></table>';
                                propertiesTableContainer.innerHTML += tableHtml;
                            } else {
                                propertiesTableContainer.innerHTML += '<p>Brak posiadanych nieruchomości.</p>';
                            }
                        }

                        // Populate skills table
                        if (skillsTableContainer && data.player_stats) {
                            skillsTableContainer.innerHTML = '<h3>Umiejętności</h3>';
                            let tableHtml = '<table class="player-stats-table"><thead><tr><th>Umiejętność</th><th>Wartość</th></tr></thead><tbody>';
                            tableHtml += `<tr><td>Um. gotowania</td><td class="numeric">${data.player_stats.cook_skill}</td></tr>`;
                            tableHtml += `<tr><td>Tolerancja</td><td class="numeric">${data.player_stats.tolerance}</td></tr>`;
                            tableHtml += `<tr><td>Zmysł biznesowy</td><td class="numeric">${data.player_stats.business_acumen}</td></tr>`;
                            tableHtml += `<tr><td>Pojemność brzucha</td><td class="numeric">${data.player_stats.belly_capacity}</td></tr>`;
                            tableHtml += `<tr><td>Zmysł przypraw</td><td class="numeric">${data.player_stats.spice_sense}</td></tr>`;
                            tableHtml += `<tr><td>Czas przygotowania</td><td class="numeric">${data.player_stats.prep_time}</td></tr>`;
                            tableHtml += `<tr><td>Tradycja</td><td class="numeric">${data.player_stats.tradition_affinity}</td></tr>`;
                            tableHtml += '</tbody></table>';
                            skillsTableContainer.innerHTML += tableHtml;
                        }
                    } else {
                        console.error('Failed to load player details during updatePlayerDisplay.');
                    }
                }).catch(error => {
                    console.error('Error during player details refresh in updatePlayerDisplay:', error);
                });
            }
        }
    }

    /**
     * Sends a request to the server to process a tile action (e.g., buy, pay rent).
     * @param {string} actionType - Type of action (e.g., 'buy_restaurant', 'pay_rent').
     * @param {number} playerId - ID of the player performing the action.
     * @param {number} gameId - ID of the current game.
     * @param {number} location - ID of the tile where the action is performed.
     * @param {number|null} propertyId - Optional property ID (if action concerns a specific property).
     * @param {number|null} targetPlayerIdForDuel - Optional target player ID for duel action.
     */
    async function handleTileAction(actionType, playerId, gameId, location, propertyId = null, targetPlayerIdForDuel = null) {
        console.log(`Wykonuję akcję: ${actionType} dla gracza ${playerId} na polu ${location}`);

        // Validate required parameters before sending
        if (!actionType || playerId === null || gameId === null || location === null) {
            console.error('Missing required parameters for tile action:', {
                actionType: actionType,
                playerId: playerId,
                gameId: gameId,
                location: location
            });

            if (cardSlotText) {
                cardSlotText.textContent = 'Błąd: Brak wymaganych parametrów akcji.';
            }
            return;
        }

        // Przygotowanie ciała żądania
        const requestBody = {
            action_type: actionType,
            player_id: parseInt(playerId), // Ensure it's a number
            game_id: parseInt(gameId),     // Ensure it's a number
            location: parseInt(location)   // Ensure it's a number
        };

        // Add property_id if provided (for actions like buying specific properties)
        if (propertyId !== null && propertyId !== undefined) {
            requestBody.property_id = parseInt(propertyId);
        }

        // Add targetPlayerIdForDuel if provided and action is duel
        if (actionType === 'duel' && targetPlayerIdForDuel !== null && targetPlayerIdForDuel !== undefined) {
            requestBody.target_player_id = parseInt(targetPlayerIdForDuel);
        }


        console.log('Sending request body:', requestBody);

        try {
            const actionResponse = await fetch('process_tile_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestBody),
            });

            // Log response status and headers for debugging
            console.log('Response status:', actionResponse.status);
            console.log('Response headers:', [...actionResponse.headers.entries()]);

            if (!actionResponse.ok) {
                // Try to read error message from response body
                let errorMessage;
                try {
                    const errorBody = await actionResponse.json();
                    errorMessage = errorBody.message || `HTTP ${actionResponse.status}`;
                    console.log('Error response body:', errorBody);
                } catch (parseError) {
                    // If JSON cannot be parsed, try to get text
                    try {
                        const errorText = await actionResponse.text();
                        console.log('Error response text:', errorText);
                        errorMessage = errorText || `HTTP ${actionResponse.status}`;
                    } catch (textError) {
                        errorMessage = `HTTP ${actionResponse.status}`; // Corrected error variable
                    }
                }
                throw new Error(`Błąd serwera podczas akcji na polu (${actionResponse.status}): ${errorMessage}`);
            }

            const actionResult = await actionResponse.json();
            console.log('Action result:', actionResult);

            if (actionResult.success) {
                console.log("Akcja na polu zakończona sukcesem:", actionResult.message);

                // Update player display after action
                if (actionResult.new_coins !== undefined) {
                    updatePlayerDisplay(playerId, actionResult.new_coins, location);
                }

                // If action affected another player (e.g., paying rent)
                if (actionResult.affected_player_id && actionResult.affected_player_id !== playerId) {
                    if (actionResult.affected_player_new_coins !== undefined) {
                        updatePlayerDisplay(actionResult.affected_player_id, actionResult.affected_player_new_coins, null); // null for location, as it doesn't change
                    }
                }

                // If action changed the turn (e.g., after purchase/rent payment)
                if (actionResult.next_player_id) {
                    currentTurnPlayerId = actionResult.next_player_id;
                    currentPlayerId = actionResult.next_player_id; // <--- CRUCIAL: Update currentPlayerId for single-device game
                    updateCurrentPlayerIndicator(currentTurnPlayerId);
                    updateRollButtonState();

                    if (actionResult.new_round_started) {
                        console.log("Rozpoczęła się nowa runda!");
                        // You can add additional UI logic for a new round here
                    }
                }

                if (cardSlotText) {
                    cardSlotText.textContent = actionResult.message || "Akcja wykonana pomyślnie.";
                }

            } else {
                console.error("Błąd podczas akcji na polu:", actionResult.message);
                if (cardSlotText) {
                    cardSlotText.textContent = `Błąd: ${actionResult.message}`;
                }
            }

        } catch (error) {
            console.error("Błąd sieci/serwera podczas wykonywania akcji na polu:", error);
            if (cardSlotText) {
                cardSlotText.textContent = `Błąd sieci/serwera: ${error.message}`;
            }
            throw error; // Re-throw error to be caught by the calling function
        }
    }

    /**
     * Starts cyclic refreshing of game state.
     */
    function startGameStateRefresh() {
        // Clear previous interval if it exists
        if (gameStateInterval) {
            clearInterval(gameStateInterval);
        }
        // Set new interval
        gameStateInterval = setInterval(refreshPlayerStats, GAME_STATE_REFRESH_INTERVAL);
        console.log(`Rozpoczęto odświeżanie stanu gry co ${GAME_STATE_REFRESH_INTERVAL / 1000} sekundy.`);
    }

    /**
     * Stops cyclic refreshing of game state.
     */
    function stopGameStateRefresh() {
        if (gameStateInterval) {
            clearInterval(gameStateInterval);
            gameStateInterval = null;
            console.log('Zatrzymano odświeżanie stanu gry.');
        }
    }

    /**
     * Function to refresh player stats and their positions on the board.
     * Fetches full game state from the server.
     */
    async function refreshPlayerStats() {
        try {
            const response = await fetch('get_game_state.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    game_id: gameId
                })
            });

            // Check if the response is OK (status 200)
            if (!response.ok) {
                const errorText = await response.text(); // Get raw text to debug HTML response
                console.error(`Błąd HTTP podczas pobierania stanu gry: ${response.status} ${response.statusText}. Odpowiedź:`, errorText);
                throw new Error(`Błąd HTTP: ${response.status} ${response.statusText}`);
            }

            const data = await response.json();
            if (data.success && data.players) {
                // Update displayed player information
                data.players.forEach(player => {
                    // Update coins and location in player panel
                    updatePlayerDisplay(player.id, player.coins, player.location);
                    // Move player token on the board
                    movePlayerToken(player.id, player.location);
                });

                // Update currentTurnPlayerId from server
                if (data.current_turn_player_id !== undefined && data.current_turn_player_id !== currentTurnPlayerId) {
                    console.log(`Polling: Zmiana currentTurnPlayerId z ${currentTurnPlayerId} na ${data.current_turn_player_id}`);
                    currentTurnPlayerId = data.current_turn_player_id;
                    currentPlayerId = data.current_turn_player_id; // <--- CRUCIAL: Update currentPlayerId during polling
                    updateCurrentPlayerIndicator(currentTurnPlayerId);
                    updateRollButtonState();
                }

            } else {
                console.error('Błąd odświeżania statystyk gry:', data.message || 'Brak danych.');
            }
        } catch (error) {
            console.error('Błąd komunikacji podczas odświeżania statystyk:', error);
        }
    }
});
