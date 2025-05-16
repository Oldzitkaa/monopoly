document.addEventListener('DOMContentLoaded', () => {
    const handleAllElements = document.querySelectorAll('.handle-all');
    const playerInfoElements = document.querySelectorAll('.player-info');

    handleAllElements.forEach(handle => {
        handle.addEventListener('mouseenter', () => {
            const parentPlayerInfo = handle.closest('.player-info');
            if (parentPlayerInfo) {
                const playerClass = Array.from(parentPlayerInfo.classList).find(className => className.startsWith('player'));

                if (playerClass === 'player1' || playerClass === 'player3') {
                    parentPlayerInfo.classList.add('slide-right');
                } else if (playerClass === 'player2' || playerClass === 'player4') {
                    parentPlayerInfo.classList.add('slide-left');
                }
            }
        });

        handle.addEventListener('mouseleave', () => {
            const parentPlayerInfo = handle.closest('.player-info');
            if (parentPlayerInfo) {
                parentPlayerInfo.classList.remove('slide-right');
                parentPlayerInfo.classList.remove('slide-left');
            }
        });
    });
});