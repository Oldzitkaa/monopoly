const step1Div = document.querySelector('.logo_div.step1');
const step2Div = document.querySelector('.logo_div.step2');
const step3NickDiv = document.querySelector('.logo_div.step3.nick');
const step3CharactersDiv = document.querySelector('.logo_div.step3.characters');
const backgImg = document.querySelector('.backg');

const quantityInput = document.getElementById('quantity');
const playerCountSpan = document.getElementById('playerCount');
const nicknameInputsContainer = document.getElementById('nicknameInputsContainer');
const characterSelectionHeader = document.getElementById('characterSelectionHeader');
const characterCardsContainer = document.getElementById('characterCardsContainer');
const startGameButton = document.getElementById('startGameButton');
const selectedCharactersInfoDiv = document.getElementById('selectedCharactersInfo');


let currentStep = 1;
let numPlayers = 2;
let playerNicknames = [];
let playerCharacterSelections = [];
let currentPlayerChoosingCharacter = 0;


document.addEventListener('DOMContentLoaded', () => {
    if (quantityInput && playerCountSpan) {
        playerCountSpan.textContent = quantityInput.value;
        quantityInput.addEventListener('input', () => {
            playerCountSpan.textContent = quantityInput.value;
            numPlayers = parseInt(quantityInput.value);
        });
    }
    if (characterCardsContainer) {
        characterCardsContainer.addEventListener('click', handleCharacterCardClick);
    }
});

function animateTransition(divOut, divIn, backgroundTranslateX) {
    const commonTransition = 'opacity 0.5s ease-in-out';
    const backgroundTransition = 'transform 0.5s ease-in-out';

    if (divOut) {
        divOut.style.transition = commonTransition;
        divOut.style.opacity = '0';
    }

    if (backgImg && backgroundTranslateX !== null) {
        backgImg.style.transition = backgroundTransition;
        backgImg.style.transform = `translateX(${backgroundTranslateX})`;
    }

    setTimeout(() => {
        if (divOut) {
            divOut.style.display = 'none';
            divOut.style.zIndex = '1';
        }
        if (divIn) {
            divIn.style.display = 'flex';
            divIn.style.flexDirection = 'column';
            divIn.style.justifyContent = 'center';
            divIn.style.alignItems = 'center';
            divIn.style.zIndex = '10';
            divIn.style.transition = 'none'; 
            divIn.style.opacity = '0';
            
            requestAnimationFrame(() => { 
                divIn.style.transition = commonTransition;
                divIn.style.opacity = '1';
            });
        }
    }, 500);
}

function goToStepTwo() {
    animateTransition(step1Div, step2Div, '10%');
    currentStep = 2;
}

function goBackToStepOne() {
    animateTransition(step2Div, step1Div, '-40%');
    currentStep = 1;
}

function goToNicknameStep() {
    numPlayers = parseInt(quantityInput.value); 
    playerNicknames = new Array(numPlayers).fill(''); 
    
    nicknameInputsContainer.innerHTML = ''; 
    for (let i = 0; i < numPlayers; i++) {
        const label = document.createElement('label');
        label.setAttribute('for', `playerNick${i + 1}`);
        label.textContent = `Gracz ${i + 1}:`;
        
        const input = document.createElement('input');
        input.setAttribute('type', 'text');
        input.setAttribute('id', `playerNick${i + 1}`);
        input.setAttribute('name', `playerNick${i + 1}`);
        input.setAttribute('placeholder', `Wpisz nick Gracza ${i + 1}`);
        input.required = true;
        nicknameInputsContainer.appendChild(label);
        nicknameInputsContainer.appendChild(input);
    }
    animateTransition(step2Div, step3NickDiv, '20%'); 
    currentStep = 3;
}

function goBackToStepTwo() {
    animateTransition(step3NickDiv, step2Div, '10%');
    currentStep = 2;
}

function goToCharacterStep() {
    playerNicknames = [];
    let allNicksFilled = true;
    for (let i = 0; i < numPlayers; i++) {
        const nickInput = document.getElementById(`playerNick${i + 1}`);
        if (nickInput.value.trim() === '') {
            allNicksFilled = false;
            nickInput.style.borderColor = 'red'; 
        } else {
            playerNicknames.push(nickInput.value.trim());
            nickInput.style.borderColor = '';
        }
    }

    if (!allNicksFilled) {
        alert('Wszystkie pola nicków muszą być wypełnione!');
        return;
    }

    playerCharacterSelections = new Array(numPlayers).fill(null);
    currentPlayerChoosingCharacter = 0;
    updateCharacterSelectionScreen();
    animateTransition(step3NickDiv, step3CharactersDiv, '30%');
    currentStep = 4;
    startGameButton.style.display = 'none';
    selectedCharactersInfoDiv.innerHTML = '';
}

function goBackToNicknameStep() {
    animateTransition(step3CharactersDiv, step3NickDiv, '20%');
    currentStep = 3;
    playerCharacterSelections = new Array(numPlayers).fill(null);
    currentPlayerChoosingCharacter = 0;
    resetCharacterCardStates();
}

function updateCharacterSelectionScreen() {
    if (currentPlayerChoosingCharacter < numPlayers) {
        characterSelectionHeader.textContent = `${playerNicknames[currentPlayerChoosingCharacter]} (Gracz ${currentPlayerChoosingCharacter + 1}), wybierz postać:`;
        startGameButton.style.display = 'none';
    } else {
        characterSelectionHeader.textContent = 'Wszyscy gracze wybrali postacie!';
        startGameButton.style.display = 'flex';
    }
    resetCharacterCardStates();
    const allSelectedCharIds = playerCharacterSelections.filter(sel => sel !== null).map(sel => sel.characterId);
    document.querySelectorAll('.character-card').forEach(card => {
        const cardId = card.dataset.characterId;
        if (allSelectedCharIds.includes(cardId) && 
            (!playerCharacterSelections[currentPlayerChoosingCharacter] || playerCharacterSelections[currentPlayerChoosingCharacter].characterId !== cardId)) {
            card.classList.add('disabled');
        }
        if (playerCharacterSelections[currentPlayerChoosingCharacter] && playerCharacterSelections[currentPlayerChoosingCharacter].characterId === cardId) {
            card.classList.add('selected');
        }
    });
    updateSelectedCharactersInfo();
}

function handleCharacterCardClick(event) {
    const card = event.target.closest('.character-card');
    if (!card || card.classList.contains('disabled') || currentPlayerChoosingCharacter >= numPlayers) {
        return; 
    }

    const characterId = card.dataset.characterId;
    const characterName = card.dataset.characterName;
    if (playerCharacterSelections[currentPlayerChoosingCharacter]) {
        const oldCardId = playerCharacterSelections[currentPlayerChoosingCharacter].characterId;
        const oldCardElement = characterCardsContainer.querySelector(`.character-card[data-character-id="${oldCardId}"]`);
        if (oldCardElement) oldCardElement.classList.remove('selected');
    }
    card.classList.add('selected');

    playerCharacterSelections[currentPlayerChoosingCharacter] = {
        characterId: characterId,
        name: playerNicknames[currentPlayerChoosingCharacter],
        characterName: characterName
    };
    currentPlayerChoosingCharacter++;
    updateCharacterSelectionScreen();
}


function resetCharacterCardStates() {
    document.querySelectorAll('.character-card').forEach(card => {
        card.classList.remove('selected');
        card.classList.remove('disabled'); 
    });
}

function updateSelectedCharactersInfo() {
    selectedCharactersInfoDiv.innerHTML = 'Wybrane postacie:<br>';
    playerCharacterSelections.forEach((selection, index) => {
        if (selection) {
            const p = document.createElement('p');
            p.textContent = `${playerNicknames[index]}: ${selection.characterName}`;
            selectedCharactersInfoDiv.appendChild(p);
        } else if (index < numPlayers) {
             const p = document.createElement('p');
            p.textContent = `${playerNicknames[index]}: --- (oczekuje na wybór)`;
            selectedCharactersInfoDiv.appendChild(p);
        }
    });
}


async function submitGameSetup() {
    if (playerCharacterSelections.some(sel => sel === null)) {
        alert('Nie wszyscy gracze wybrali postacie!');
        return;
    }

    const gameData = {
        numPlayers: numPlayers,
        players: playerCharacterSelections.map((sel, index) => ({
            nickname: playerNicknames[index],
            characterId: sel.characterId
        }))
    };

    console.log("Dane do wysłania:", gameData); 

    try {
        const response = await fetch('create_game.php', { 
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(gameData),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ message: 'Nieznany błąd serwera' }));
            throw new Error(`HTTP error! status: ${response.status}, message: ${errorData.message}`);
        }

        const result = await response.json();

        if (result.success) {
            alert('Gra utworzona pomyślnie! ID Gry: ' + result.gameId);
        } else {
            alert('Błąd podczas tworzenia gry: ' + result.message);
        }
    } catch (error) {
        console.error('Błąd wysyłania danych:', error);
        alert('Wystąpił błąd komunikacji z serwerem: ' + error.message);
    }
}