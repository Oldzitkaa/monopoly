let step1Div;
let step2Div;
let step3NickDiv;
let step3CharactersDiv;
let backgImg;
let quantityInput;
let playerCountSpan;
let nicknameInputsContainer;
let characterSelectionHeader;
let playerSetupList; 
let characterDetailsPanel; 
let selectedCharacterImage;
let selectedCharacterName;
let selectedCharacterDescription;
let characterStatsDetailsContainer; 
let characterCardsContainer; 
let startGameButton;
let loadingIndicator; 
let notificationArea; 
let currentStep = 1; 
let numPlayers = 2; 
let playerNicknames = []; 
let playerCharacterSelections = []; 
let currentPlayerChoosingCharacter = 0; 
let charactersData = []; 
const TRANSITION_DURATION_SEC = 0.5; 
const TRANSITION_EASE = 'ease-in-out'; 
const PLACEHOLDER_IMAGE_PATH = '../zdj/placeholder.png';
document.addEventListener('DOMContentLoaded', () => {
    step1Div = document.querySelector('.logo_div.step1');
    step2Div = document.querySelector('.logo_div.step2');
    step3NickDiv = document.querySelector('.logo_div.step3.nick'); 
    step3CharactersDiv = document.querySelector('.logo_div.step3.characters'); 
    backgImg = document.querySelector('.backg');
    quantityInput = document.getElementById('quantity');
    playerCountSpan = document.getElementById('playerCount');
    nicknameInputsContainer = document.getElementById('nicknameInputsContainer');
    characterSelectionHeader = document.getElementById('characterSelectionHeader');
    playerSetupList = document.getElementById('playerSetupList'); 
    characterDetailsPanel = document.querySelector('.character-details-panel'); 
    selectedCharacterImage = document.getElementById('selectedCharacterImage');
    selectedCharacterName = document.getElementById('selectedCharacterName');
    selectedCharacterDescription = document.getElementById('selectedCharacterDescription');
    characterStatsDetailsContainer = document.querySelector('.character-stats-details'); 
    characterCardsContainer = document.getElementById('characterCardsContainer');
    startGameButton = document.getElementById('startGameButton');
    loadingIndicator = document.getElementById('loadingIndicator') || createLoadingIndicator(); 
    notificationArea = document.getElementById('notificationArea') || createNotificationArea(); 
    if (quantityInput && playerCountSpan) {
        playerCountSpan.textContent = quantityInput.value;
        numPlayers = parseInt(quantityInput.value); 
        quantityInput.addEventListener('input', () => {
            playerCountSpan.textContent = quantityInput.value;
            numPlayers = parseInt(quantityInput.value);
        });
    }
    if (characterCardsContainer) {
        characterCardsContainer.addEventListener('click', handleCharacterCardClick);
    }
     if (!notificationArea) {
         notificationArea = createNotificationArea();
     }
     if (!loadingIndicator) {
         loadingIndicator = createLoadingIndicator();
     }
    fetchCharacterData();
});
function createNotificationArea() {
    let area = document.getElementById('notificationArea');
    if (area) return area;
    area = document.createElement('div');
    area.id = 'notificationArea';
    area.className = 'notification-area'; 
    document.body.appendChild(area);
    return area;
}
function showNotification(message, type = 'info', duration = 5000) {
    if (!notificationArea) {
         console.error("Obszar powiadomień nie istnieje, używam console.log/alert.");
         if (type === 'error') alert(`${type.toUpperCase()}: ${message}`);
         else console.log(`${type.toUpperCase()}: ${message}`);
         return;
    }
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notificationArea.appendChild(notification);
    if (duration > 0) {
        setTimeout(() => {
            notification.classList.add('hiding');
            notification.addEventListener('transitionend', function handler() {
                notification.removeEventListener('transitionend', handler);
                notification.remove();
            });
        }, duration);
    }
}
function showSuccessMessage(message, duration = 5000) { showNotification(message, 'success', duration); }
function showErrorMessage(message, duration = 7000) { showNotification(message, 'error', duration); }
function showInfoMessage(message, duration = 5000) { showNotification(message, 'info', duration); }
function createLoadingIndicator() {
    let indicator = document.getElementById('loadingIndicator');
    if (indicator) return indicator;
    indicator = document.createElement('div');
    indicator.id = 'loadingIndicator';
    indicator.className = 'loading-indicator'; 
    indicator.innerHTML = '<div class="spinner"></div><p>Ładowanie...</p>';
    document.body.appendChild(indicator);
    indicator.style.display = 'none'; 
    return indicator;
}
function showLoading() {
    if (loadingIndicator) {
        loadingIndicator.style.display = 'flex'; 
    }
}
function hideLoading() {
    if (loadingIndicator) {
         loadingIndicator.style.display = 'none';
    }
}
async function fetchCharacterData() {
    showLoading(); 
    try {
        const response = await fetch('get_characters.php');
        if (!response.ok) {
             const errorBody = await response.json().catch(() => ({ message: `HTTP status ${response.status}`, detail: 'Błąd serwera bez szczegółów JSON.' }));
             console.error(`HTTP error! status: ${response.status}`, errorBody);
            throw new Error(`Błąd HTTP ${response.status}: ${errorBody.message || 'Nieznany błąd serwera.'}`);
        }
        const data = await response.json(); 
        if (data.error) {
             console.error('API error:', data.message);
             throw new Error(`Błąd API: ${data.message}`);
        }
        charactersData = data;
        populateCharacterCards();
        resetCharacterDetailsPanel();
    } catch (error) {
        console.error('Błąd podczas pobierania danych postaci:', error);
        showErrorMessage('Nie udało się załadować danych postaci. Spróbuj odświeżyć stronę.');
        const nextButtonStep2 = document.querySelector('.logo_div.step2 .next-button');
        if (nextButtonStep2) {
            nextButtonStep2.disabled = true;
            nextButtonStep2.textContent = "Ładowanie postaci nie powiodło się"; 
        }
    } finally {
        hideLoading(); 
    }
}
function populateCharacterCards() {
    if (!characterCardsContainer || charactersData.length === 0) {
         if (characterCardsContainer) {
             characterCardsContainer.innerHTML = '<p style="color:white; text-align:center;">Brak postaci do wyświetlenia. Skontaktuj się z administratorem.</p>';
         }
        return;
    }
    characterCardsContainer.innerHTML = '';
    charactersData.forEach(character => {
        const card = document.createElement('div');
        card.className = 'character-card'; 
        card.dataset.characterId = character.id; 
        card.dataset.characterName = character.name; 
        const imageUrl = character.image_path || PLACEHOLDER_IMAGE_PATH;
        card.innerHTML = `
            <div class="character-image-container">
                <img src="${imageUrl}" alt="${character.name || 'Postać'}" class="character-image">
            </div>
            <div class="character-info">
                <h3>${character.name || 'Nieznana postać'}</h3>
                <p class="character-description">${character.description || 'Brak opisu.'}</p>
                <div class="character-stats">
                    <div class="character-stat"><span>Umiejętność gotowania:</span> <span class="stat-value">${character.base_cook_skill || 0}</span></div>
                    <div class="character-stat"><span>Tolerancja ostrości:</span> <span class="stat-value">${character.base_tolerance || 0}</span></div>
                    <div class="character-stat"><span>Łeb do biznesu:</span> <span class="stat-value">${character.base_business_acumen || 0}</span></div>
                    <div class="character-stat"><span>Pojemność żołądka:</span> <span class="stat-value">${character.base_belly_capacity || 0}</span></div>
                    <div class="character-stat"><span>Zmysł przypraw:</span> <span class="stat-value">${character.base_spice_sense || 0}</span></div>
                    <div class="character-stat"><span>Czas przygotowania:</span> <span class="stat-value">${character.base_prep_time || 0}</span></div>
                    <div class="character-stat"><span>Oddanie tradycji:</span> <span class="stat-value">${character.base_tradition_affinity || 0}</span></div>
                    ${character.special_ability_description ? `<div class="character-stat character-stat--ability"><span>Zdolność spec.:</span> <span class="stat-value">${character.special_ability_description}</span></div>` : ''}
                </div>
            </div>
        `;
        characterCardsContainer.appendChild(card);
    });
}
function updateCharacterDetailsPanel(character) {
    if (!characterDetailsPanel || !selectedCharacterImage || !selectedCharacterName || !selectedCharacterDescription || !characterStatsDetailsContainer) {
        return;
    }
    if (character) {
        const imageUrl = character.image_path || PLACEHOLDER_IMAGE_PATH;
        selectedCharacterImage.src = imageUrl;
        selectedCharacterImage.alt = character.name || 'Wybrana postać';
        selectedCharacterName.textContent = character.name || 'Nieznana postać';
        selectedCharacterDescription.textContent = character.description || 'Brak opisu dla tej postaci.';
        characterStatsDetailsContainer.innerHTML = '';

        const stats = [
            { label: 'Umiejętność gotowania', value: character.base_cook_skill },
            { label: 'Tolerancja ostrości', value: character.base_tolerance },
            { label: 'łeb do biznesu', value: character.base_business_acumen },
            { label: 'Pojemność żołądka', value: character.base_belly_capacity },
            { label: 'Zmysł przypraw', value: character.base_spice_sense },
            { label: 'Czas przygotowania', value: character.base_prep_time },
            { label: 'Oddanie tradycji', value: character.base_tradition_affinity }
        ];
        const maxStatValue = 10;

        const table = document.createElement('table');
        stats.forEach(stat => {
            const row = table.insertRow();
            const labelCell = row.insertCell();
            const valueCell = row.insertCell();
            labelCell.textContent = stat.label + ':';
            labelCell.classList.add('name-tabel'); // Dodano klasę 'name-tabel' do pierwszej komórki

            const statValue = typeof stat.value === 'number' ? stat.value : 0;
            const percentage = (statValue / maxStatValue) * 100;

            const statBarContainer = document.createElement('div');
            statBarContainer.className = 'stat-bar-container';
            const statFill = document.createElement('div');
            statFill.className = 'stat-fill';
            statFill.style.width = `${percentage > 100 ? 100 : (percentage < 0 ? 0 : percentage)}%`;
            const statValueSpan = document.createElement('span');
            statValueSpan.className = 'stat-value';
            statValueSpan.textContent = statValue;

            statBarContainer.appendChild(statFill);
            valueCell.appendChild(statBarContainer);
            valueCell.appendChild(statValueSpan);
            valueCell.classList.add('value-table'); // Dodano klasę 'value-table' do drugiej komórki
        });

        if (character.special_ability_description) {
            const row = table.insertRow();
            const labelCell = row.insertCell();
            const valueCell = row.insertCell();
            labelCell.textContent = 'Zdolność spec.:';
            labelCell.classList.add('name-tabel');
            valueCell.textContent = character.special_ability_description;
            labelCell.classList.add('stat-label');
            valueCell.classList.add('stat-value-ability');
            valueCell.classList.add('value-table');
        }

        characterStatsDetailsContainer.appendChild(table);

    } else {
        selectedCharacterImage.src = PLACEHOLDER_IMAGE_PATH;
        selectedCharacterImage.alt = 'Wybierz postać';
        selectedCharacterName.textContent = 'Wybierz postać';
        selectedCharacterDescription.textContent = 'Kliknij na postać, aby zobaczyć jej opis i statystyki.';
        characterStatsDetailsContainer.innerHTML = '';
    }
}
function resetCharacterDetailsPanel() {
    updateCharacterDetailsPanel(null);
}
function updatePlayerListSummary() {
     if (!playerSetupList) {
         console.error("Element listy graczy nie znaleziono w DOM!");
         return; 
     }
     playerSetupList.innerHTML = '';
     for (let i = 0; i < numPlayers; i++) {
         const playerItem = document.createElement('div');
         playerItem.className = 'player-setup-item'; 
         if (i === currentPlayerChoosingCharacter) {
             playerItem.classList.add('active');
         }
         const selection = playerCharacterSelections[i];
         let characterName = 'Oczekuje na wybór...'; 
         let characterThumbnailHtml = ''; 
         let selectionCharacterClass = 'waiting'; 
         if (selection && selection.characterId !== null) {
             const character = charactersData.find(char => char.id == selection.characterId);
             if (character) {
                 characterName = character.name; 
                 const thumbnailUrl = character.image_path || PLACEHOLDER_IMAGE_PATH;
                 characterThumbnailHtml = `<div class="character-thumbnail" style="background-image: url('${thumbnailUrl}');"></div>`;
                 selectionCharacterClass = 'selected-character-name'; 
             } else {
                 characterName = 'Postać nieznana (ID: ' + selection.characterId + ')';
                 console.error(`Dane postaci o ID ${selection.characterId} brak w charactersData.`);
                 selectionCharacterClass = 'error'; 
                 characterThumbnailHtml = `<div class="character-thumbnail" style="background-image: url('${PLACEHOLDER_IMAGE_PATH}');"></div>`;
             }
         }
         playerItem.innerHTML = `
             ${characterThumbnailHtml} <div class="selection-player">${playerNicknames[i]}</div> <div class="selection-character ${selectionCharacterClass}">${characterName}</div> `;
         playerSetupList.appendChild(playerItem); 
     }
}
function resetCharacterCardStates() {
    document.querySelectorAll('.character-card').forEach(card => {
        card.classList.remove('selected');
        card.classList.remove('disabled');
    });
}
function updateStartButtonState() {
    if (!startGameButton) {
         return; 
     }
    const allPlayersHaveCharacter = playerCharacterSelections.length === numPlayers &&
                                     playerCharacterSelections.every(selection => selection !== null && selection.characterId !== null);
    startGameButton.disabled = !allPlayersHaveCharacter; 
     if (allPlayersHaveCharacter) {
         startGameButton.classList.add('ready');
         startGameButton.style.pointerEvents = 'auto'; 
     } else {
         startGameButton.classList.remove('ready');
         startGameButton.style.pointerEvents = 'none'; 
     }
}
function animateTransition(divOut, divIn, backgroundTranslateX) {
    const commonTransition = `opacity ${TRANSITION_DURATION_SEC}s ${TRANSITION_EASE}`;
    const backgroundTransition = `transform ${TRANSITION_DURATION_SEC}s ${TRANSITION_EASE}`;
    if (divOut) {
        divOut.style.transition = commonTransition;
        divOut.style.opacity = '0'; 
        divOut.style.pointerEvents = 'none'; 
         divOut.style.zIndex = '1';
    }
    if (backgImg && backgroundTranslateX !== null) {
        backgImg.style.transition = backgroundTransition;
        backgImg.style.transform = `translateX(${backgroundTranslateX})`;
    }
    setTimeout(() => {
        if (divOut) {
            divOut.style.display = 'none';
        }
        if (divIn) {
            divIn.style.display = 'flex'; 
            divIn.style.opacity = '0';
            divIn.style.transition = 'none'; 
            requestAnimationFrame(() => {
                divIn.style.transition = commonTransition; 
                divIn.style.opacity = '1'; 
                divIn.style.zIndex = '10'; 
                divIn.style.pointerEvents = 'auto'; 
            });
        }
    }, TRANSITION_DURATION_SEC * 1000); 
}
function goToStepTwo() {
     if (!step1Div || !step2Div) { console.error("Elementy kroków 1 lub 2 nie znaleziono!"); return; }
    animateTransition(step1Div, step2Div, '10%'); 
    currentStep = 2;
}
function goToStepTwoExtra() {
    fetch('end_session_in_start.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
    })
    .then(response => response.json())
    .then(data => {
        console.log(data);
        if (data.success) {
            console.log("Sesja zakończona pomyślnie.");
            animateTransition(step1Div, step2Div, '10%');
            currentStep = 2;
        } else {
            console.error("Błąd podczas kończenia sesji:", data.message);
            showErrorMessage("Błąd podczas kończenia sesji: " + data.message);
        }
    })
    .catch(error => {
        console.error("Wystąpił błąd sieciowy podczas kończenia sesji:", error);
        showErrorMessage("Wystąpił błąd sieciowy podczas kończenia sesji.");
    });
}

function goBackToStepOne() {
    if (!step2Div || !step1Div) { console.error("Elementy kroków 1 lub 2 nie znaleziono!"); return; }
    animateTransition(step2Div, step1Div, '-40%'); 
    currentStep = 1;
}
function goToNicknameStep() {
     if (!step2Div || !step3NickDiv) { console.error("Elementy kroków 2 lub 3a nie znaleziono!"); return; }
    numPlayers = parseInt(quantityInput.value);
    playerNicknames = new Array(numPlayers).fill('');
    if (nicknameInputsContainer) {
        nicknameInputsContainer.innerHTML = ''; 
        for (let i = 0; i < numPlayers; i++) {
            const formGroup = document.createElement('div');
            formGroup.className = 'player-nickname-form-group'; 
            const label = document.createElement('label');
            label.setAttribute('for', `playerNick${i + 1}`);
            label.textContent = `Gracz ${i + 1}:`;
            const input = document.createElement('input');
            input.setAttribute('type', 'text');
            input.setAttribute('id', `playerNick${i + 1}`);
            input.setAttribute('name', `playerNick${i + 1}`); 
            input.setAttribute('placeholder', `Wpisz nick Gracza ${i + 1}`);
            input.className = 'nickname-input'; 
            input.required = true; 
             input.setAttribute('autocomplete', 'off'); 
            input.addEventListener('input', (e) => {
                 const nick = e.target.value.trim();
                 playerNicknames[i] = nick; 
                 if (nick === '') {
                      e.target.classList.add('error');
                 } else {
                      e.target.classList.remove('error');
                 }
            });
            formGroup.appendChild(label);
            formGroup.appendChild(input);
            nicknameInputsContainer.appendChild(formGroup);
        }
    } else {
        console.error("Kontener #nicknameInputsContainer nie znaleziono w DOM!");
    }
    animateTransition(step2Div, step3NickDiv, '20%'); 
    currentStep = 3; 
}
function goBackToStepTwo() {
    if (!step3NickDiv || !step2Div) { console.error("Elementy kroków 3a lub 2 nie znaleziono!"); return; }
    animateTransition(step3NickDiv, step2Div, '10%'); 
    currentStep = 2; 
    playerNicknames = [];
}
function goToCharacterStep() {
    if (!step3NickDiv || !step3CharactersDiv) { console.error("Elementy kroków 3a lub 3b nie znaleziono!"); return; }
    let allNicksValid = true;
    playerNicknames = []; 
    for (let i = 0; i < numPlayers; i++) {
        const nickInput = document.getElementById(`playerNick${i + 1}`);
        if (!nickInput) {
            console.error(`Element pola nicku dla gracza ${i + 1} nie znaleziono!`);
            showErrorMessage('Wystąpił błąd formularza nicków. Spróbuj odświeżyć stronę.');
            return;
        }
        const nick = nickInput.value.trim();
        if (nick === '') {
            allNicksValid = false;
            nickInput.classList.add('error'); 
        } else {
            playerNicknames.push(nick); 
            nickInput.classList.remove('error'); 
        }
    }
     const uniqueNicknames = new Set(playerNicknames);
     if (uniqueNicknames.size !== numPlayers) {
         showErrorMessage('Nicki graczy muszą być unikalne!');
         allNicksValid = false; 
     }
    if (!allNicksValid) {
        showErrorMessage('Wszyscy gracze muszą podać unikalne nicki!'); 
        return;
    }
    playerCharacterSelections = new Array(numPlayers).fill(null);
    currentPlayerChoosingCharacter = 0;
    updateCharacterSelectionScreen();
    animateTransition(step3NickDiv, step3CharactersDiv, '30%'); 
    currentStep = 4; 
    updateStartButtonState();
}
function goBackToNicknameStep() {
    if (!step3CharactersDiv || !step3NickDiv) { console.error("Elementy kroków 3b lub 3a nie znaleziono!"); return; }
    playerCharacterSelections = new Array(numPlayers).fill(null);
    currentPlayerChoosingCharacter = 0;
    resetCharacterCardStates(); 
    resetCharacterDetailsPanel(); 
    animateTransition(step3CharactersDiv, step3NickDiv, '20%'); 
    currentStep = 3; 
     if (characterCardsContainer) characterCardsContainer.scrollTop = 0;
     if (playerSetupList) playerSetupList.scrollTop = 0;
}
function updateCharacterSelectionScreen() {
    if (!characterSelectionHeader) {
        console.error('Element nagłówka wyboru postaci (#characterSelectionHeader) nie znaleziono!');
        return;
    }
    resetCharacterCardStates();
    if (currentPlayerChoosingCharacter < numPlayers) {
        const currentPlayerNick = playerNicknames[currentPlayerChoosingCharacter] || `Gracz ${currentPlayerChoosingCharacter + 1}`;
        characterSelectionHeader.textContent = `${currentPlayerNick}, wybierz postać:`;
        const selectedCharIdsByOthers = playerCharacterSelections
            .filter((selection, index) => index !== currentPlayerChoosingCharacter && selection !== null && selection.characterId !== null)
            .map(selection => parseInt(selection.characterId)); 
        document.querySelectorAll('.character-card').forEach(card => {
            const cardId = parseInt(card.dataset.characterId); 
            if (selectedCharIdsByOthers.includes(cardId)) {
                card.classList.add('disabled'); 
            }
        });
         const currentPlayersSelection = playerCharacterSelections[currentPlayerChoosingCharacter];
         if (currentPlayersSelection && currentPlayersSelection.characterId !== null) {
             const previouslySelectedCard = characterCardsContainer.querySelector(`.character-card[data-character-id="${currentPlayersSelection.characterId}"]`);
             if (previouslySelectedCard) {
                 previouslySelectedCard.classList.add('selected'); 
             }
         }
         resetCharacterDetailsPanel();
    } else {
        characterSelectionHeader.textContent = 'Wszyscy gracze wybrali postacie!';
    }
    updatePlayerListSummary();
    updateStartButtonState();
    if (characterCardsContainer) characterCardsContainer.scrollTop = 0;
     if (playerSetupList) playerSetupList.scrollTop = 0;
}
function handleCharacterCardClick(event) {
    const card = event.target.closest('.character-card');
    if (!card || card.classList.contains('disabled') || currentPlayerChoosingCharacter >= numPlayers) {
        if (card && card.classList.contains('disabled')) {
             showInfoMessage('Ta postać została już wybrana przez innego gracza.');
        }
        return; 
    }
    const characterId = card.dataset.characterId; 
    const characterName = card.dataset.characterName; 
    const clickedCharacter = charactersData.find(char => char.id == characterId);
    if (clickedCharacter) {
        updateCharacterDetailsPanel(clickedCharacter);
    } else {
        console.error(`Dane postaci o ID ${characterId} nie znaleziono w charactersData po kliknięciu karty!`);
    }
    if (playerCharacterSelections[currentPlayerChoosingCharacter]) {
        const oldSelectionId = playerCharacterSelections[currentPlayerChoosingCharacter].characterId;
        if (oldSelectionId !== null && oldSelectionId != characterId) { 
             const oldCardElement = characterCardsContainer.querySelector(`.character-card[data-character-id="${oldSelectionId}"]`);
             if (oldCardElement) {
                 oldCardElement.classList.remove('selected');
             }
        }
    }
    card.classList.add('selected');
    playerCharacterSelections[currentPlayerChoosingCharacter] = {
        characterId: characterId, 
        name: playerNicknames[currentPlayerChoosingCharacter], 
        characterName: characterName 
    };
    currentPlayerChoosingCharacter++; 
    setTimeout(() => {
        updateCharacterSelectionScreen(); 
    }, 300); 
}

async function submitGameSetup() {
    if (playerCharacterSelections.some(selection => selection === null || selection.characterId === null)) {
        showErrorMessage('Proces konfiguracji nie został ukończony. Upewnij się, że wszyscy gracze wybrali postacie.');
        updateStartButtonState();
        return;
    }
    const gameData = {
        numPlayers: numPlayers,
        players: playerCharacterSelections.map((selection, index) => ({
            nickname: playerNicknames[index], 
            characterId: selection.characterId 
        }))
    };
    console.log("Dane do wysłania do create_game.php:", gameData); 
    showLoading(); 
    try {
        const response = await fetch('create_game.php', {
            method: 'POST', 
            headers: {
                'Content-Type': 'application/json', 
            },
            body: JSON.stringify(gameData), 
        });
        if (!response.ok) {
             const errorBody = await response.json().catch(() => ({ message: `HTTP status ${response.status}`, detail: 'Błąd serwera bez szczegółów JSON.' }));
             console.error(`Błąd HTTP! status: ${response.status}`, errorBody);
            throw new Error(`Błąd serwera: ${errorBody.message || `HTTP status ${response.status}`}`);
        }
        const result = await response.json();
        if (result.success) {
            showSuccessMessage('Gra utworzona pomyślnie!', 3000); 
            setTimeout(() => {
                 window.location.href = 'gameboard.php';
            }, 1000);
        } else {
            console.error('Błąd API podczas tworzenia gry:', result.message);
            showErrorMessage('Błąd podczas tworzenia gry: ' + result.message);
        }
    } catch (error) {
        console.error('Wystąpił błąd komunikacji z serwerem lub przetwarzania:', error);
        showErrorMessage('Wystąpił błąd komunikacji z serwerem: ' + error.message);
    } finally {
        hideLoading();
    }
}

