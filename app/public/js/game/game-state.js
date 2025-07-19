export class GameState {
  constructor() {
    this.ball = {
      x: 0,
      y: 0,
      radius: 20,
      color: '#3498db'
    };
    this.isRunning = false;
    this.listeners = [];
  }

  updateBall(ballData) {
    this.ball = { ...this.ball, ...ballData };
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
}