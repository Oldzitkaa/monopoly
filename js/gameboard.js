document.addEventListener('DOMContentLoaded', () => {
    const diceImage = document.getElementById('diceImage');
    const wynikTekst = document.getElementById('wynikTekst');
    const rollDiceButton = document.getElementById('rollDiceButton');
    const playerInfoContainer = document.getElementById('playerInfoContainer');
    const playerInfoBoxes = document.querySelectorAll('.player-info-box');
    const cardSlotText = document.querySelector('.card-slot.card-text');
    const cardSlotChoose = document.querySelector('.card-slot.card-choose');
    let currentTurnPlayerId = initialCurrentTurnPlayerId;
    const GAME_STATE_REFRESH_INTERVAL = 500;
    let gameStateInterval;

    function translatePropertyType(type) {
        switch (type) {
            case 'restaurant': return 'Restauracja';
            case 'hotel': return 'Hotel';
            default: return type;
        }
    }

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

    if (!diceImage || !wynikTekst || !rollDiceButton || typeof gameId === 'undefined' || typeof currentPlayerId === 'undefined' || typeof initialCurrentTurnPlayerId === 'undefined') {
        if (rollDiceButton) {
            rollDiceButton.textContent = 'Błąd konfiguracji gry (brak ID)';
            rollDiceButton.disabled = true;
        }
        console.error("Błąd konfiguracji: gameId, currentPlayerId lub initialCurrentTurnPlayerId nie są zdefiniowane.");
        return;
    }

    console.log('Initial configuration:', {
        gameId: gameId,
        currentPlayerId: currentPlayerId,
        initialCurrentTurnPlayerId: initialCurrentTurnPlayerId
    });

    updateRollButtonState();
    updateCurrentPlayerIndicator(currentTurnPlayerId);
    startGameStateRefresh();
    rollDiceButton.addEventListener('click', handleRollDice);

    function startDiceAnimation() {
        if (diceImage) {
            diceImage.classList.add('animacja');
            setTimeout(() => {
                diceImage.classList.remove('animacja');
            }, 1000);
        }
    }

    function updateRollButtonState() {
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

    function showNotification(message) {
        console.log('Powiadomienie:', message);
        const existingNotification = document.querySelector('.game-notification');
        if (existingNotification) {
            existingNotification.remove();
        }
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

    function updateCurrentPlayerIndicator(playerId) {
        playerInfoBoxes.forEach(box => {
            box.classList.remove('active-turn');
        });

        const currentPlayerBox = document.querySelector(`.player-info-box[data-player-id="${playerId}"]`);
        if (currentPlayerBox) {
            currentPlayerBox.classList.add('active-turn');
        }
    }

    async function handleRollDice() {
        rollDiceButton.disabled = true;
        wynikTekst.textContent = 'Rzut...';
        startDiceAnimation();
        if (cardSlotText) cardSlotText.textContent = '';
        if (cardSlotChoose) {
            cardSlotChoose.innerHTML = '';
            cardSlotChoose.style.display = 'none';
        }
        stopGameStateRefresh();

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
                throw new Error(`Błąd serwera (${response.status}): ${errorBody.message}`);
            }

            const result = await response.json();if (result.player_bankrupt === true) {
    console.log('Gracz zbankrutował!', result);
    
    // Wyświetl komunikat o bankructwie
    if (result.message) {
        alert(result.message);
    }
    
    // Przekieruj na stronę końca gry
    const redirectUrl = result.redirect_to || 'end_game.php';
    
    // Dodaj parametry do URL
    if (gameId && currentPlayerId) {
        const separator = redirectUrl.includes('?') ? '&' : '?';
        window.location.href = `${redirectUrl}${separator}game_id=${gameId}&player_id=${currentPlayerId}&reason=bankrupt`;
    } else {
        window.location.href = redirectUrl;
    }
    
    return; // WAŻNE: Przerwij dalsze przetwarzanie
}
            console.log('Pełna odpowiedź z roll_dice_api.php:', result);

            if (result.success) {
                const rollResult = result.roll_result;
                const newLocation = result.new_location;
                // const newLocation = 2;
                const playerWhoRolledId = currentPlayerId;

                console.log(`Gracz ID: ${playerWhoRolledId}, nowa pozycja: ${newLocation}`);
                diceImage.src = `../zdj/kostki/${rollResult}.png`;
                diceImage.alt = `Wynik: ${rollResult}`;
                movePlayerToken(playerWhoRolledId, newLocation);
                updatePlayerDisplay(playerWhoRolledId, result.new_coins, newLocation);
                console.log('PRZED aktualizacją tury - currentTurnPlayerId:', currentTurnPlayerId);
                let nextPlayerIdFromServer = result.next_player_id || result.current_player_id || result.turn_player_id;

                if (nextPlayerIdFromServer) {
                    console.log('Znaleziono next_player_id w odpowiedzi:', nextPlayerIdFromServer);
                    currentTurnPlayerId = nextPlayerIdFromServer;
                    currentPlayerId = nextPlayerIdFromServer;
                    console.log('PO aktualizacji tury - currentTurnPlayerId:', currentTurnPlayerId);
                    console.log('PO aktualizacji tury - currentPlayerId (viewer):', currentPlayerId);
                    updateCurrentPlayerIndicator(currentTurnPlayerId);

                    if (nextPlayerIdFromServer !== playerWhoRolledId) {
                        const nextPlayerName = document.querySelector(`.player-info-box[data-player-id="${nextPlayerIdFromServer}"] .player-name`);
                        if (nextPlayerName) {
                            showNotification(`Tura przeszła na: ${nextPlayerName.textContent}`);
                        }
                    }
                } else {
                    console.warn('Brak informacji o następnym graczu w odpowiedzi z serwera. Używam fallbacku.');
                    const players = Array.from(document.querySelectorAll('.player-info-box')).map(box => ({
                        id: parseInt(box.dataset.playerId),
                        name: box.querySelector('.player-name').textContent
                    }));

                    const currentPlayerIndex = players.findIndex(p => p.id === playerWhoRolledId);
                    const nextPlayerIndex = (currentPlayerIndex + 1) % players.length;
                    const nextPlayer = players[nextPlayerIndex];

                    if (nextPlayer) {
                        currentTurnPlayerId = nextPlayer.id;
                        currentPlayerId = nextPlayer.id;
                        updateCurrentPlayerIndicator(currentTurnPlayerId);
                        showNotification(`Tura przeszła na: ${nextPlayer.name}`);
                        console.log('Użyto fallback dla zmiany tury na:', nextPlayer.name);
                    }
                }
                if (result.new_round_started) {
                    console.log("Rozpoczęła się nowa runda!");
                    showNotification("Rozpoczęła się nowa runda!");
                }
                setTimeout(async () => {
                    wynikTekst.textContent = rollResult;
                    try {
                        const messageResponse = await fetch(`get_tile_message.php?location=${newLocation}`);
                        if (!messageResponse.ok) {
                            throw new Error(`Błąd pobierania wiadomości o polu: HTTP ${messageResponse.status}`);
                        }
                        const messageHtml = await messageResponse.text();

                        if (cardSlotText) {
                            cardSlotText.innerHTML = messageHtml;
                            cardSlotText.style.display = 'block';
                        }
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
                                const actionButtons = cardSlotChoose.querySelectorAll('.action-button');
                                if (actionButtons.length === 0) {
                                    updateRollButtonState();
                                    startGameStateRefresh();
                                } else {
                                    actionButtons.forEach(button => {
                                        button.addEventListener('click', async (event) => {
                                            event.preventDefault();
                                            event.stopPropagation();

                                            const actionType = event.target.dataset.actionType;

                                            const propertyId = (actionType === 'duel') ? null : (event.target.dataset.propertyId || newLocation);
                                            const targetPlayerIdForDuel = event.target.dataset.playerId;
                                            if (!actionType) {
                                                console.error('Błąd: Brak atrybutu data-action-type na klikniętym przycisku akcji. Sprawdź HTML generowany przez get_tile_choose.php');
                                                if (cardSlotText) cardSlotText.textContent = 'Błąd: Brak typu akcji dla przycisku.';
                                                return;
                                            }

                                            console.log('Sending tile action:', {
                                                actionType: actionType,
                                                playerWhoRolledId: playerWhoRolledId,
                                                gameId: gameId,
                                                newLocation: newLocation,
                                                propertyId: propertyId,
                                                targetPlayerIdForDuel: targetPlayerIdForDuel
                                            });

                                            event.target.disabled = true;

                                            try {
                                                await handleTileAction(actionType, playerWhoRolledId, gameId, newLocation, propertyId, targetPlayerIdForDuel);
                                            } catch (actionError) {
                                                console.error('Błąd podczas akcji na polu:', actionError);
                                            } finally {
                                                event.target.disabled = false;
                                                if (cardSlotChoose) {
                                                    cardSlotChoose.innerHTML = '';
                                                    cardSlotChoose.style.display = 'none';
                                                }
                                                updateRollButtonState();
                                                startGameStateRefresh();
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
                                updateRollButtonState();
                                startGameStateRefresh();
                            }
                        } else {
                            console.warn("Element cardSlotChoose nie został znaleziony.");
                            updateRollButtonState();
                            startGameStateRefresh();
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
                        updateRollButtonState();
                        startGameStateRefresh();
                    }
                }, 2000);

            } else {
                wynikTekst.textContent = `Błąd: ${result.message}`;
                if (cardSlotText) cardSlotText.textContent = result.message;
                if (cardSlotChoose) {
                    cardSlotChoose.innerHTML = '';
                    cardSlotChoose.style.display = 'none';
                }
                updateRollButtonState();
                startGameStateRefresh();
            }

        } catch (error) {
            console.error('Błąd komunikacji z serwerem:', error);
            wynikTekst.textContent = `Błąd: ${error.message}`;
            if (cardSlotText) {
                cardSlotText.textContent = `Błąd sieci/serwera: ${error.message}`;
            }
            if (cardSlotChoose) {
                cardSlotChoose.innerHTML = '';
                cardSlotChoose.style.display = 'none';
            }
            updateRollButtonState();
            startGameStateRefresh();
        }
    }

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
                if (!newTilePlayersContainer.querySelector(`.player-token[data-player-id="${playerId}"]`)) {
                    newTilePlayersContainer.appendChild(playerToken);
                }
            }
        } else {
            console.warn(`Nie znaleziono pionka gracza (ID: ${playerId}) lub pola (ID: ${newTileId}).`);
        }
    }

    playerInfoBoxes.forEach(box => {
        box.addEventListener('click', async (e) => {
            e.stopPropagation();

            const isActive = box.classList.contains('active');

            playerInfoBoxes.forEach(otherBox => {
                otherBox.classList.remove('active');
            });
            playerInfoContainer.classList.remove('has-active-player');

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
                            if (propertiesTableContainer) {
                                propertiesTableContainer.innerHTML = '<h3>Nieruchomości</h3>';
                                if (Array.isArray(data.properties) && data.properties.length > 0) {
                                    let tableHtml = '<table class="player-properties-table"><thead><tr><th>Nazwa</th><th>Typ/Grupa</th><th>Koszt/Czynsz</th><th>Upgrade</th></tr></thead><tbody>';
                                    data.properties.forEach(prop => {
                                        let costRentDisplay = '';
                                        if (prop.cost) { costRentDisplay += `${prop.cost} $`; }
                                        if (prop.calculated_rent) {
                                            if (costRentDisplay) costRentDisplay += ' | ';
                                            costRentDisplay += `${prop.calculated_rent} $`;
                                        }
                                        if (prop.level !== undefined) { costRentDisplay += ` (Poziom: ${prop.level})`; }
                                        if (prop.is_mortgaged) { costRentDisplay += ` (Zastawiono)`; }
                                        if (!costRentDisplay) { costRentDisplay = 'N/A'; }

                                        let upgradeCostDisplay = 'B/D';
                                        if (prop.level >= 5) {
                                            upgradeCostDisplay = 'MAX. POZIOM';
                                        } else if (prop.upgrade_cost !== null && prop.upgrade_cost !== undefined) {
                                            upgradeCostDisplay = `${prop.upgrade_cost} $`;
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

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.player-info-box')) {
            playerInfoBoxes.forEach(box => {
                box.classList.remove('active');
            });
            playerInfoContainer.classList.remove('has-active-player');
        }
    });

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

    function updatePlayerDisplay(playerId, newCoins, newLocation) {
    console.log(`[DEBUG] updatePlayerDisplay wywołane dla gracza ${playerId}, monety: ${newCoins}, lokacja: ${newLocation}`);
    
    const playerBox = document.querySelector(`.player-info-box[data-player-id="${playerId}"]`);
    if (playerBox) {
        const coinsSpan = playerBox.querySelector('.player-coins');
        const locationSpan = playerBox.querySelector('.player-location');

        if (coinsSpan) {
            const oldCoins = coinsSpan.textContent;
            coinsSpan.textContent = `${newCoins} $`;
            console.log(`[DEBUG] Zaktualizowano monety gracza ${playerId}: ${oldCoins} -> ${newCoins} $`);
        } else {
            console.warn(`[DEBUG] Nie znaleziono elementu .player-coins dla gracza ${playerId}`);
        }
        
        if (locationSpan && newLocation !== null) {
            const oldLocation = locationSpan.textContent;
            locationSpan.textContent = `Pole ${newLocation}`;
            console.log(`[DEBUG] Zaktualizowano lokację gracza ${playerId}: ${oldLocation} -> Pole ${newLocation}`);
        } else if (!locationSpan) {
            console.warn(`[DEBUG] Nie znaleziono elementu .player-location dla gracza ${playerId}`);
        }

        // DODANE: Wymuszenie odświeżenia widoku
        playerBox.style.display = 'none';
        playerBox.offsetHeight; // trigger reflow
        playerBox.style.display = '';

        // Reszta kodu bez zmian...
        if (playerBox.classList.contains('active'))  {
                const propertiesTableContainer = playerBox.querySelector('.player-properties-table-container');
                const skillsTableContainer = playerBox.querySelector('.player-skills-table-container');

                if (propertiesTableContainer) propertiesTableContainer.innerHTML = '<h3>Nieruchomości</h3><p>Ładowanie...</p>';
                if (skillsTableContainer) skillsTableContainer.innerHTML = '<h3>Umiejętności</h3><p>Ładowanie...</p>';

                loadPlayerDetails(playerId).then(data => {
                    if (data) {
                        if (propertiesTableContainer) {
                            propertiesTableContainer.innerHTML = '<h3>Nieruchomości</h3>';
                            if (Array.isArray(data.properties) && data.properties.length > 0) {
                                let tableHtml = '<table class="player-properties-table"><thead><tr><th>Nazwa</th><th>Typ/Grupa</th><th>Koszt/Czynsz</th><th>Upgrade</th></tr></thead><tbody>';
                                data.properties.forEach(prop => {
                                    let costRentDisplay = '';
                                    if (prop.cost) { costRentDisplay += `${prop.cost} $`; }
                                    if (prop.calculated_rent) {
                                        if (costRentDisplay) costRentDisplay += ' | ';
                                        costRentDisplay += `${prop.calculated_rent} $`;
                                    }
                                    if (prop.level !== undefined) { costRentDisplay += ` (Poziom: ${prop.level})`; }
                                    if (prop.is_mortgaged) { costRentDisplay += ` (Zastawiono)`; }
                                    if (!costRentDisplay) { costRentDisplay = 'N/A'; }

                                    let upgradeCostDisplay = 'B/D';
                                    if (prop.level >= 5) {
                                        upgradeCostDisplay = 'MAX. POZIOM';
                                    } else if (prop.upgrade_cost !== null && prop.upgrade_cost !== undefined) {
                                        upgradeCostDisplay = `${prop.upgrade_cost} $`;
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


    async function handleTileAction(actionType, playerId, gameId, location, propertyId = null, targetPlayerIdForDuel = null) {
    console.log(`Wykonuję akcję: ${actionType} dla gracza ${playerId} na polu ${location}`);

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

    const requestBody = {
        action_type: actionType,
        player_id: parseInt(playerId),
        game_id: parseInt(gameId),
        location: parseInt(location)
    };

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
    console.log('[DEBUG] refreshPlayerStats - rozpoczęcie');
    
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
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error(`[ERROR] Błąd HTTP podczas pobierania stanu gry: ${response.status} ${response.statusText}. Odpowiedź:`, errorText);
            throw new Error(`Błąd HTTP: ${response.status} ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('[DEBUG] Otrzymane dane z get_game_state.php:', data);
        
        // NOWE: Sprawdź czy gra się zakończyła lub ktoś zbankrutował
        if (data.game_ended === true || data.player_bankrupt === true) {
            console.log('[DEBUG] Wykryto koniec gry lub bankructwo gracza');
            
            // Zatrzymaj odświeżanie
            stopGameStateRefresh();
            
            // Wyświetl komunikat
            if (data.message) {
                alert(data.message);
            }
            
            // Przekieruj na stronę końca gry
            const redirectUrl = data.redirect_to || 'end_game.php';
            
            // Dodaj parametry do URL
            if (gameId && currentPlayerId) {
                const separator = redirectUrl.includes('?') ? '&' : '?';
                const reason = data.player_bankrupt ? 'bankrupt' : 'ended';
                window.location.href = `${redirectUrl}${separator}game_id=${gameId}&player_id=${currentPlayerId}&reason=${reason}`;
            } else {
                window.location.href = redirectUrl;
            }
            
            return; // Przerwij dalsze przetwarzanie
        }
        
        if (data.success && data.players) {
            console.log(`[DEBUG] Aktualizacja ${data.players.length} graczy`);
            
            // NOWE: Sprawdź czy któryś z graczy ma ujemne monety
            const bankruptPlayer = data.players.find(player => player.coins < 0);
            if (bankruptPlayer) {
                console.log('[DEBUG] Wykryto gracza z ujemnymi monetami:', bankruptPlayer);
                
                // Zatrzymaj odświeżanie
                stopGameStateRefresh();
                
                // Wyświetl komunikat o bankructwie
                alert(`Gracz ${bankruptPlayer.name} zbankrutował!`);
                
                // Przekieruj na stronę końca gry
                const redirectUrl = 'end_game.php';
                if (gameId && currentPlayerId) {
                    window.location.href = `${redirectUrl}?game_id=${gameId}&player_id=${currentPlayerId}&reason=bankrupt&bankrupt_player=${bankruptPlayer.id}`;
                } else {
                    window.location.href = redirectUrl;
                }
                
                return;
            }
            
            // Update displayed player information
            data.players.forEach((player, index) => {
                console.log(`[DEBUG] Aktualizacja gracza ${index + 1}/${data.players.length}:`, player);
                
                // Update coins and location in player panel
                updatePlayerDisplay(player.id, player.coins, player.location);
                
                // Move player token on the board
                movePlayerToken(player.id, player.location);
                
                // Update center stats
                updateCenterPlayerStats(player);
            });
            
            // Update currentTurnPlayerId from server
            if (data.current_turn_player_id !== undefined && data.current_turn_player_id !== currentTurnPlayerId) {
                console.log(`[DEBUG] Polling: Zmiana currentTurnPlayerId z ${currentTurnPlayerId} na ${data.current_turn_player_id}`);
                currentTurnPlayerId = data.current_turn_player_id;
                currentPlayerId = data.current_turn_player_id;
                updateCurrentPlayerIndicator(currentTurnPlayerId);
                updateRollButtonState();
                
                // NOWE: Aktualizuj centrum planszy
                updateCurrentPlayerCenterDisplay(currentTurnPlayerId, data.players);
            }
            
            // Zawsze aktualizuj centrum planszy (nawet jeśli gracz się nie zmienił)
            updateCurrentPlayerCenterDisplay(currentTurnPlayerId || data.current_turn_player_id, data.players);
            
            console.log('[DEBUG] refreshPlayerStats - zakończone pomyślnie');
        } else {
            console.error('[ERROR] Błąd odświeżania statystyk gry:', data.message || 'Brak danych.');
        }
    } catch (error) {
        console.error('[ERROR] Błąd komunikacji podczas odświeżania statystyk:', error);
    }
}
function updateCenterPlayerStats(playerData) {
    console.log(`[DEBUG] updateCenterPlayerStats dla gracza:`, playerData);
    
    // Spróbuj różnych selektorów w kolejności priorytetów
    let playerContainer = null;
    
    // Selektor 1: Oryginalny z kodu
    playerContainer = document.querySelector(`.player-info.player${playerData.turn_order}`);
    
    if (!playerContainer) {
        // Selektor 2: Na podstawie data-player-id
        playerContainer = document.querySelector(`.player-info[data-player-id="${playerData.id}"]`);
        console.log(`[DEBUG] Używam selektora data-player-id dla gracza ${playerData.id}`);
    }
    
    if (!playerContainer) {
        // Selektor 3: Szukaj w kontenerach z nazwą gracza
        const allPlayerInfos = document.querySelectorAll('.player-info');
        allPlayerInfos.forEach(container => {
            const nameElement = container.querySelector('p b');
            if (nameElement && nameElement.textContent.includes(playerData.name)) {
                playerContainer = container;
                console.log(`[DEBUG] Znaleziono kontener przez nazwę gracza: ${playerData.name}`);
            }
        });
    }
    
    if (!playerContainer) {
        console.warn(`[WARNING] Nie znaleziono kontenera dla gracza:`, {
            id: playerData.id,
            turn_order: playerData.turn_order,
            name: playerData.name
        });
        
        // Debug: Wyświetl wszystkie dostępne kontenery graczy
        const allContainers = document.querySelectorAll('.player-info, [data-player-id]');
        console.log('[DEBUG] Dostępne kontenery graczy:', Array.from(allContainers).map(el => ({
            className: el.className,
            id: el.id,
            dataPlayerId: el.dataset.playerId,
            textContent: el.textContent?.substring(0, 50) + '...'
        })));
        return;
    }
    
    console.log(`[DEBUG] Znaleziono kontener gracza:`, playerContainer.className);
    
    // ZMIENIONE: Aktualizuj tylko nazwę gracza (bez postaci)
    const playerNameElement = playerContainer.querySelector('p b');
    if (playerNameElement && playerData.name) {
        const oldText = playerNameElement.textContent;
        const newText = playerData.name; // Usunięto character_name
        if (oldText !== newText) {
            playerNameElement.textContent = newText;
            console.log(`[DEBUG] Zaktualizowano nazwę gracza: ${oldText} -> ${newText}`);
        }
    } else if (!playerNameElement) {
        console.warn(`[WARNING] Nie znaleziono elementu 'p b' dla gracza ${playerData.id}`);
    }
    
    // ZMIENIONE: Aktualizuj monety - tylko nazwa gracza bez postaci
    const playerParagraph = playerContainer.querySelector('p');
    if (playerParagraph && playerData.coins !== undefined) {
        const oldHTML = playerParagraph.innerHTML;
        const newHTML = `<b>${playerData.name}</b><br>Monety: ${playerData.coins} $ <br>`; // Usunięto character_name
        
        if (oldHTML !== newHTML) {
            playerParagraph.innerHTML = newHTML;
            console.log(`[DEBUG] Zaktualizowano monety gracza ${playerData.id}: ${playerData.coins} $`);
        }
    } else if (!playerParagraph) {
        console.warn(`[WARNING] Nie znaleziono elementu 'p' dla gracza ${playerData.id}`);
    }
    
    // Aktualizuj statystyki w tabeli
    const statsTable = playerContainer.querySelector('.stats-table-wrapper table');
    if (statsTable) {
        console.log(`[DEBUG] Znaleziono tabelę statystyk dla gracza ${playerData.id}`);
        const rows = statsTable.querySelectorAll('tr');
        
        rows.forEach((row, rowIndex) => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 2) {
                const statName = cells[0].textContent.trim();
                const valueCell = cells[1];
                const oldValue = valueCell.textContent.trim();
                
                // Mapowanie nazw statystyk na dane z serwera
                let newValue = null;
                switch (statName) {
                    case 'Pojemność brzucha:':
                        newValue = playerData.belly_capacity;
                        break;
                    case 'Tolerancja ostrości:':
                        newValue = playerData.tolerance;
                        break;
                    case 'Czas przygotowania:':
                        newValue = playerData.prep_time;
                        break;
                    case 'Tradycyjne Powiązania:':
                        newValue = playerData.tradition_affinity;
                        break;
                    case 'Umiejętności gotowania:':
                        newValue = playerData.cook_skill;
                        break;
                    case 'Zmysł do przypraw:':
                        newValue = playerData.spice_sense;
                        break;
                    case 'Łeb do biznesu:':
                        newValue = playerData.business_acumen;
                        break;
                    default:
                        console.log(`[DEBUG] Nieznana statystyka: ${statName}`);
                        break;
                }
                
                if (newValue !== null && newValue !== undefined) {
                    const newValueStr = newValue.toString();
                    if (oldValue !== newValueStr) {
                        valueCell.textContent = newValueStr;
                        console.log(`[DEBUG] Zaktualizowano ${statName} dla gracza ${playerData.id}: ${oldValue} -> ${newValueStr}`);
                        
                        // Dodaj wizualny efekt zmiany
                        valueCell.style.backgroundColor = '#ffff99';
                        setTimeout(() => {
                            valueCell.style.backgroundColor = '';
                        }, 1000);
                    }
                } else {
                    console.log(`[DEBUG] Brak danych dla statystyki ${statName}, otrzymano:`, newValue);
                }
            } else {
                console.log(`[DEBUG] Wiersz ${rowIndex} ma nieprawidłową liczbę komórek:`, cells.length);
            }
        });
    } else {
        console.warn(`[WARNING] Nie znaleziono tabeli statystyk (.stats-table-wrapper table) dla gracza ${playerData.id}`);
        
        // Debug: Sprawdź dostępne elementy w kontenerze
        console.log('[DEBUG] Dostępne elementy w kontenerze gracza:', {
            'stats-table-wrapper': !!playerContainer.querySelector('.stats-table-wrapper'),
            'table': !!playerContainer.querySelector('table'),
            'stats-table-wrapper table': !!playerContainer.querySelector('.stats-table-wrapper table'),
            innerHTML: playerContainer.innerHTML.substring(0, 200) + '...'
        });
    }
    
    // Wymuszenie odświeżenia renderowania
    playerContainer.style.display = 'none';
    playerContainer.offsetHeight; // trigger reflow
    playerContainer.style.display = '';
    
    console.log(`[DEBUG] updateCenterPlayerStats zakończone dla gracza ${playerData.id}`);
}

function debugPlayerContainers() {
    console.log('[DEBUG] === DIAGNOSTYKA KONTENERÓW GRACZY ===');
    
    // Sprawdź player-info kontenery
    const playerInfos = document.querySelectorAll('.player-info');
    console.log(`Znaleziono ${playerInfos.length} kontenerów .player-info:`);
    
    playerInfos.forEach((container, index) => {
        console.log(`Kontener ${index + 1}:`);
        console.log(`  - className: "${container.className}"`);
        console.log(`  - id: "${container.id}"`);
        console.log(`  - data-player-id: "${container.dataset.playerId}"`);
        
        const nameElement = container.querySelector('p b');
        console.log(`  - nazwa gracza: "${nameElement ? nameElement.textContent : 'BRAK'}"`)
        
        const table = container.querySelector('.stats-table-wrapper table');
        console.log(`  - tabela statystyk: ${table ? 'ZNALEZIONA' : 'BRAK'}`);
        
        if (table) {
            const rows = table.querySelectorAll('tr');
            console.log(`    - liczba wierszy: ${rows.length}`);
        }
        
        console.log(`  - innerHTML (pierwsze 100 znaków): "${container.innerHTML.substring(0, 100)}..."`);
        console.log('---');
    });
    
    // Sprawdź player-info-box kontenery
    const playerBoxes = document.querySelectorAll('.player-info-box');
    console.log(`\nZnaleziono ${playerBoxes.length} kontenerów .player-info-box:`);
    
    playerBoxes.forEach((box, index) => {
        console.log(`Box ${index + 1}:`);
        console.log(`  - data-player-id: "${box.dataset.playerId}"`);
        console.log(`  - coins element: ${box.querySelector('.player-coins') ? 'ZNALEZIONY' : 'BRAK'}`);
        console.log(`  - location element: ${box.querySelector('.player-location') ? 'ZNALEZIONY' : 'BRAK'}`);
    });
}

function testPlayerStatsUpdate() {
    console.log('[TEST] Testowanie aktualizacji statystyk gracza...');
    debugPlayerContainers();
    
    setTimeout(() => {
        console.log('[TEST] Uruchamianie refreshPlayerStats...');
        refreshPlayerStats();
    }, 500);
}

function updateCurrentPlayerInfo(currentPlayerId, players) {
    console.log(`[DEBUG] updateCurrentPlayerInfo dla gracza ID: ${currentPlayerId}`);
    
    // Znajdź element wyświetlający informacje o aktualnym graczu
    const currentPlayerElement = document.querySelector('.current-player-info');
    
    if (!currentPlayerElement) {
        console.warn('[WARNING] Nie znaleziono elementu .current-player-info');
        return;
    }
    
    // Znajdź dane aktualnego gracza
    const currentPlayer = players.find(player => player.id === currentPlayerId);
    
    if (!currentPlayer) {
        console.warn(`[WARNING] Nie znaleziono danych dla gracza ID: ${currentPlayerId}`);
        currentPlayerElement.innerHTML = '<h3>Błąd ładowania gracza</h3>';
        return;
    }
    
    // ZMIENIONE: Aktualizuj zawartość elementu - tylko nazwa gracza
    const playerName = currentPlayer.name || 'Nieznany gracz';
    const displayName = playerName; // Usunięto character_name
    
    currentPlayerElement.innerHTML = `<h3>${displayName}</h3>`;
    
    console.log(`[DEBUG] Zaktualizowano informacje o aktualnym graczu: ${displayName}`);
}

function updateCurrentPlayerCenterDisplay(currentPlayerId, players) {
    console.log(`[DEBUG] updateCurrentPlayerCenterDisplay dla gracza ID: ${currentPlayerId}`);
    
    // POPRAWKA: Użyj ID zamiast selektora klasy
    const currentPlayerNameElement = document.getElementById('current-player-name');
    
    if (!currentPlayerNameElement) {
        console.warn('[WARNING] Nie znaleziono elementu #current-player-name');
        // Fallback - spróbuj oryginalnego selektora
        const fallbackElement = document.querySelector('.current-player h3');
        if (fallbackElement) {
            console.log('[DEBUG] Używam fallback selektora .current-player h3');
            updatePlayerNameElement(fallbackElement, currentPlayerId, players);
        }
        return;
    }
    
    updatePlayerNameElement(currentPlayerNameElement, currentPlayerId, players);
}

function updatePlayerNameElement(element, currentPlayerId, players) {
    // Znajdź dane aktualnego gracza
    const currentPlayer = players.find(player => player.id == currentPlayerId);
    
    if (!currentPlayer) {
        console.warn(`[WARNING] Nie znaleziono danych dla gracza ID: ${currentPlayerId}`);
        element.textContent = 'Ładowanie...';
        return;
    }
    
    // ZMIENIONE: Aktualizuj nazwę gracza - tylko name bez character_name
    const playerName = currentPlayer.name || 'Nieznany gracz';
    const displayName = playerName; // Usunięto character_name
    
    // Sprawdź czy nazwa się zmieniła przed aktualizacją
    const currentText = element.textContent.trim();
    if (currentText !== displayName) {
        element.textContent = displayName;
        console.log(`[DEBUG] Zaktualizowano aktualnego gracza w centrum: ${currentText} -> ${displayName}`);
        
        // Dodaj efekt wizualny zmiany
        element.style.backgroundColor = '#ffff99';
        setTimeout(() => {
            element.style.backgroundColor = '';
        }, 1000);
    }
}
});
