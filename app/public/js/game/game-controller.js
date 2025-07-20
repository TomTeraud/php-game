import { WebSocketManager } from '../shared/websocket-manager.js';
import { GameRenderer } from './game-renderer.js';
import { GameState } from './game-state.js';
import { UIController } from '../shared/ui-controller.js';

export class GameController {
  constructor(websocketUrl, canvasId) {
    this.websocketManager = new WebSocketManager(websocketUrl);
    this.renderer = new GameRenderer(canvasId);
    this.gameState = new GameState();
    this.uiController = new UIController();
    
    this.setupWebSocketHandlers();
    this.setupUIElements();
    this.setupStateListeners();
  }

  setupWebSocketHandlers() {
    this.websocketManager.registerMessageHandler('game_state_update', (message) => {
      this.gameState.updateBall(message.ball);
    });

    this.websocketManager.registerMessageHandler('game_status', (message) => {
      console.log("Game status from server:", message.message);
    });

    this.websocketManager.onDisconnection(() => {
      this.gameState.setRunning(false);
    });

    this.websocketManager.onError(() => {
      this.gameState.setRunning(false);
    });
  }

  setupUIElements() {
    this.uiController.registerElement('startButton', 'startGameButton');
    this.uiController.registerElement('stopButton', 'stopGameButton');
    
    this.uiController.registerClickListener('startButton', () => this.startGame());
    this.uiController.registerClickListener('stopButton', () => this.stopGame());
  }

  setupStateListeners() {
    this.gameState.addListener((state) => {
      this.renderer.render(state);
      this.uiController.updateButtonStates(state.isRunning);
    });
  }

  initialize() {
    this.websocketManager.connect();
    this.renderer.render(this.gameState);
    this.uiController.updateButtonStates(this.gameState.isRunning);
  }

  startGame() {
    if (this.websocketManager.isConnected()) {
      this.websocketManager.send({ type: 'game_start_request' });
      this.gameState.setRunning(true);
      console.log("Sent game_start_request to server.");
    } else {
      console.warn("WebSocket not open. Attempting to reconnect.");
      this.websocketManager.connect();
    }
  }

  stopGame() {
    if (this.websocketManager.isConnected()) {
      this.websocketManager.send({ type: 'game_stop_request' });
      console.log("Sent game_stop_request to server.");
    }
    this.gameState.setRunning(false);
  }
}