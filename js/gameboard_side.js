let gameActions;
let duelCardResult;

document.addEventListener('DOMContentLoaded', () => {
    gameActions = document.querySelector('.game-actions');
    duelCardResult = document.querySelector('.duel-card-result');
});

function randomCard() {
    gameActions.style.opacity = '0';
    duelCardResult.style.opacity = '1';
};

// socket, boostrap