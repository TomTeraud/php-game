import { WebSocketManager } from '../shared/websocket-manager.js';
import { GameRenderer } from './game-renderer.js';
import { GameState } from './game-state.js';
import { UIController } from '../shared/ui-controller.js';
import { GameStatsOverlay } from './game-stats-overlay.js';

export class GameController {
  constructor(websocketUrl, canvasId) {
    this.websocketManager = new WebSocketManager(websocketUrl);
    this.renderer = new GameRenderer(canvasId);
    this.gameState = new GameState();
    this.uiController = new UIController();
    this.statsOverlay = new GameStatsOverlay(canvasId);
    
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
    this.uiController.registerElements({
      'startButton': 'startGameButton',
      'stopButton': 'stopGameButton',
      'pauseButton': 'pauseGameButton',
      'resumeButton': 'resumeGameButton',
      'statsToggle': 'statsToggleButton'
    });

    this.uiController.registerClickListener('startButton', () => this.startGame());
    this.uiController.registerClickListener('stopButton', () => this.stopGame());
    this.uiController.registerClickListener('pauseButton', () => this.pauseGame());
    this.uiController.registerClickListener('resumeButton', () => this.resumeGame());
    this.uiController.registerClickListener('statsToggle', () => this.toggleStats());
  }

  setupStateListeners() {
    this.gameState.addListener((state) => {
      this.statsOverlay.updateStats(state);
      this.renderer.render(state);
      this.statsOverlay.render();
      this.uiController.updateButtonStates({
        'startButton': !state.isRunning,
        'stopButton': state.isRunning,
        'pauseButton': state.isRunning && !state.isPaused
      });
    });
  }

  toggleStats() {
    const isVisible = this.statsOverlay.toggle();
    
    // Update button text based on state
    const buttonText = isVisible ? 'Hide Stats' : 'Show Stats';
    this.uiController.setElementText('statsToggle', buttonText);
    
    // Force re-render to show/hide overlay
    this.renderer.render(this.gameState);
    this.statsOverlay.render();
    
    console.log(`Stats overlay ${isVisible ? 'shown' : 'hidden'}`);
  }

  initialize() {
    this.websocketManager.connect();
    this.renderer.render(this.gameState);
    this.uiController.updateButtonStates(this.gameState.isRunning);
  }

  startGame() {
    if (this.websocketManager.isConnected()) {
      this.websocketManager.send({ type: 'game_start_request' });
      this.gameState.setPaused(false);
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
  
  pauseGame() {
    if (this.websocketManager.isConnected()) {
      this.websocketManager.send({ type: 'game_pause_request' });
      console.log("Sent game_pause_request to server.");
    }
    // this.gameState.setRunning(false);
    this.gameState.setPaused(true);
  }
  resumeGame() {
    if (this.websocketManager.isConnected()) {
      this.websocketManager.send({ type: 'game_resume_request' });
      console.log("Sent game_resume_request to server.");
    }
    // this.gameState.setRunning(false);
    this.gameState.setPaused(false);
  }
}