import { GameController } from './game-controller.js';

const MyGame = (() => {
  let gameController = null;
  
  const initialize = () => {
    try {
      gameController = new GameController('ws://localhost/ws/', 'gameCanvas');
      gameController.initialize();
    } catch (error) {
      console.error("Failed to initialize game:", error);
    }
  };
  
  // Public API
  return {
    startGame: () => gameController?.startGame(),
    stopGame: () => gameController?.stopGame(),
    initialize
  };
})();

export default MyGame;

// Initialize when DOM is loaded
window.addEventListener('load', MyGame.initialize);