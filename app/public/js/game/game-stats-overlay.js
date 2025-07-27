export class GameStatsOverlay {
  constructor(canvasId) {
    this.canvas = document.getElementById(canvasId);
    this.ctx = this.canvas ? this.canvas.getContext('2d') : null;
    this.isVisible = false;
    this.stats = {};
    
    if (!this.canvas || !this.ctx) {
      console.warn('GameStatsOverlay: Canvas not found');
    }
  }

  updateStats(gameState) {
    this.stats = {
      ballX: Math.round(gameState.ball?.x || 0),
      ballY: Math.round(gameState.ball?.y || 0),
      ballRadius: gameState.ball?.radius || 0,
      ballColor: gameState.ball?.color || 'unknown',
      // gameRunning: gameState.isRunning || false,
      // isPaused: gameState.isPaused || false,
      // connectionStatus: gameState.connected ? 'Connected' : 'Disconnected',
      fps: gameState.fps || 0,
      lastUpdate: new Date().toLocaleTimeString()
    };
  }

  render() {
    if (!this.isVisible || !this.ctx) return;

    // Save current context state
    this.ctx.save();

    // Semi-transparent background
    this.ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
    this.ctx.fillRect(10, 10, 200, 100);

    // Border
    this.ctx.strokeStyle = '#00ff00';
    this.ctx.lineWidth = 1;
    this.ctx.strokeRect(10, 10, 200, 100);

    // Text styling
    this.ctx.fillStyle = '#00ff00';
    this.ctx.font = '12px monospace';
    this.ctx.textAlign = 'left';

    // Title
    this.ctx.fillStyle = '#ffff00';
    this.ctx.fillText('GAME STATS', 20, 30);

    // Stats
    this.ctx.fillStyle = '#00ff00';
    const statLines = [
      `Ball X: ${this.stats.ballX}`,
      `Ball Y: ${this.stats.ballY}`,
      `Radius: ${this.stats.ballRadius}`,
      `Color: ${this.stats.ballColor}`,
      // `Running: ${this.stats.gameRunning}`,
      // `Paused: ${this.stats.isPaused}`,
      // `Connection: ${this.stats.connectionStatus}`,
      // `FPS: ${this.stats.fps}`,
      // `Updated: ${this.stats.lastUpdate}`
    ];

    statLines.forEach((line, index) => {
      this.ctx.fillText(line, 20, 50 + (index * 15));
    });

    // Restore context state
    this.ctx.restore();
  }

  toggle() {
    this.isVisible = !this.isVisible;
    return this.isVisible;
  }

  show() {
    this.isVisible = true;
  }

  hide() {
    this.isVisible = false;
  }

  isShowing() {
    return this.isVisible;
  }
}