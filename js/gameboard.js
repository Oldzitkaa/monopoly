document.addEventListener('DOMContentLoaded', () => {
    const diceImage = document.getElementById('diceImage');
    const wynikTekst = document.getElementById('wynikTekst');
    const rollDiceButton = document.getElementById('rollDiceButton');
    const gameBoard = document.getElementById('monopoly-board');
    const playerInfoContainer = document.getElementById('playerInfoContainer');
    const playerInfoBoxes = document.querySelectorAll('.player-info-box');
    const cardSlotText = document.querySelector('.card-slot.card-text');
    const cardSlotChoose = document.querySelector('.card-slot.card-choose');

    if (!diceImage || !wynikTekst || !rollDiceButton || typeof gameId === 'undefined' || typeof currentPlayerId === 'undefined') {
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

        if (cardSlotText) cardSlotText.textContent = '';
        if (cardSlotChoose) {
            cardSlotChoose.innerHTML = '';
            cardSlotChoose.style.display = 'none';
        }

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

            const result = await response.json();

            if (result.success) {
                const rollResult = result.roll_result;
                const newLocation = result.new_location;
                const playerId = result.player_id || currentPlayerId;

                console.log(`Gracz ID: ${playerId}, nowa pozycja: ${newLocation}`);

                diceImage.src = `../zdj/kostki/${rollResult}.png`;
                diceImage.alt = `Wynik: ${rollResult}`;

                movePlayerToken(playerId, newLocation);
                updatePlayerDisplay(playerId, result.new_coins, newLocation);

                setTimeout(async () => {
                    wynikTekst.textContent = rollResult;
                    try {
                        const messageResponse = await fetch(`get_tile_message.php?location=${newLocation}`);
                        if (!messageResponse.ok) {
                            throw new Error(`Błąd pobierania wiadomości o polu: HTTP ${messageResponse.status}`);
                        }
                        
                        const messageText = await messageResponse.text();
                        if (cardSlotText) {
                            cardSlotText.textContent = messageText;
                        }

                        if (cardSlotChoose) {
                            cardSlotChoose.style.display = 'block';
                            cardSlotChoose.innerHTML = '<p>Ładowanie opcji akcji...</p>';

                            try {
                                const chooseResponse = await fetch(`get_tile_choose.php?location=${newLocation}`); 
                                if (!chooseResponse.ok) {
                                    throw new Error(`Błąd pobierania opcji akcji: HTTP ${chooseResponse.status}`);
                                }
                                const chooseHtml = await chooseResponse.text();
                                
                                cardSlotChoose.innerHTML = chooseHtml; 

                                cardSlotChoose.querySelectorAll('.action-button').forEach(button => {
                                    button.addEventListener('click', (event) => {
                                        const actionType = event.target.dataset.actionType;
                                        const targetPlayerId = event.target.dataset.targetPlayerId;

                                        if (cardSlotChoose) {
                                            cardSlotChoose.innerHTML = '';
                                            cardSlotChoose.style.display = 'none';
                                        }
                                        rollDiceButton.disabled = false;
                                    });
                                });

                            } catch (chooseError) {
                                console.error("Błąd podczas pobierania lub przetwarzania opcji akcji:", chooseError);
                                if (cardSlotChoose) {
                                    cardSlotChoose.innerHTML = '<p style="color: red;">Błąd ładowania opcji akcji.</p>';
                                    cardSlotChoose.style.display = 'block';
                                }
                            }
                        } else {
                            // Jeśli cardSlotChoose nie istnieje (co nie powinno się zdarzyć, ale dla bezpieczeństwa)
                            console.warn("Element cardSlotChoose nie został znaleziony.");
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
                    }
                }, 2000);

            } else {
                wynikTekst.textContent = `Błąd: ${result.message}`;
                if (cardSlotText) cardSlotText.textContent = result.message;
                if (cardSlotChoose) {
                    cardSlotChoose.innerHTML = '';
                    cardSlotChoose.style.display = 'none';
                }
            }

        } catch (error) {
            wynikTekst.textContent = `Błąd: ${error.message}`;
            if (cardSlotText) cardSlotText.textContent = `Błąd sieci/serwera: ${error.message}`;
            if (cardSlotChoose) {
                cardSlotChoose.innerHTML = '';
                cardSlotChoose.style.display = 'none';
            }
        } finally {
            // rollDiceButton.disabled = false;
            setTimeout(() => {
                rollDiceButton.textContent = 'Rzuć kostką';
            }, 500);
        }
    }

    function movePlayerToken(playerId, newTileId) {
        const playerToken = document.querySelector(`.player-token[data-player-id="${playerId}"]`);
        const newTile = document.getElementById(`space-${newTileId}`);

        if (playerToken && newTile) {
            const currentTilePlayersContainer = playerToken.closest('.players-on-tile');
            if (currentTilePlayersContainer) {
                currentTilePlayersContainer.removeChild(playerToken);
            }

            const newTilePlayersContainer = newTile.querySelector('.players-on-tile');
            if (newTilePlayersContainer) {
                newTilePlayersContainer.appendChild(playerToken);
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
                                    let tableHtml = '<table class="player-properties-table"><thead><tr><th>Nazwa</th><th>Typ/Grupa</th><th>Koszt/Czynsz</th></tr></thead><tbody>';
                                    data.properties.forEach(prop => {
                                        tableHtml += `<tr>
                                            <td>${prop.name}</td>
                                            <td>${prop.type || ''}${prop.group_name ? ` (${prop.group_name})` : ''}</td>
                                            <td class="property-cost-rent">${prop.cost || prop.rent || 'N/A'}</td>
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
                    return null;
                }
            } else {
                return null;
            }
        } catch (error) {
            return null;
        }
    }

    function updatePlayerDisplay(playerId, newCoins, newLocation) {
        const playerBox = document.querySelector(`.player-info-box[data-player-id="${playerId}"]`);
        if (playerBox) {
            const coinsSpan = playerBox.querySelector('.player-coins');
            const locationSpan = playerBox.querySelector('.player-location');

            if (coinsSpan) {
                coinsSpan.textContent = `${newCoins} zł`;
            }
            if (locationSpan) {
                locationSpan.textContent = `Pole ${newLocation}`;
            }

            if (playerBox.classList.contains('active')) {
                loadPlayerDetails(playerId).then(data => {
                    if (data) {
                        const propertiesTableContainer = playerBox.querySelector('.player-properties-table-container');
                        const skillsTableContainer = playerBox.querySelector('.player-skills-table-container');

                        if (propertiesTableContainer) {
                            propertiesTableContainer.innerHTML = '<h3>Nieruchomości</h3>';
                            if (Array.isArray(data.properties) && data.properties.length > 0) {
                                let tableHtml = '<table class="player-properties-table"><thead><tr><th>Nazwa</th><th>Typ/Grupa</th><th>Koszt/Czynsz</th></tr></thead><tbody>';
                                data.properties.forEach(prop => {
                                    tableHtml += `<tr>
                                        <td>${prop.name}</td>
                                        <td>${prop.type || ''}${prop.group_name ? ` (${prop.group_name})` : ''}</td>
                                        <td class="property-cost-rent">${prop.cost || prop.rent || 'N/A'}</td>
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
                });
            }
        }
    }
});