export class GameState {
  constructor() {
    this.ball = {
      x: 0,
      y: 0,
      radius: 20,
      color: '#3498db'
    };
    this.isRunning = false;
    this.fps = 0;
    this.listeners = [];

    this.lastFrameTime = Date.now();
    this.frameCount = 0;
  }

  updateBall(ballData) {
    this.ball = { ...this.ball, ...ballData };
    this.updateFPS();
    this.notifyListeners();
  }

  setRunning(running) {
    this.isRunning = running;
    this.notifyListeners();
  }  
  
  addListener(listener) {
    this.listeners.push(listener);
  }

  notifyListeners() {
    this.listeners.forEach(listener => listener(this));
  }

  updateFPS() {
    this.frameCount++;
    const now = Date.now();
    
    if (now - this.lastFrameTime >= 1000) { // Update FPS every second
      this.fps = this.frameCount;
      this.frameCount = 0;
      this.lastFrameTime = now;
    }
  }
}