export class GameRenderer {
  constructor(canvasId) {
    this.canvas = document.getElementById(canvasId);
    this.ctx = this.canvas ? this.canvas.getContext('2d') : null;
    
    if (!this.canvas || !this.ctx) {
      throw new Error(`Canvas with ID '${canvasId}' not found or context unavailable`);
    }
  }

  clear() {
    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
  }

  drawBall(ball) {
    this.ctx.beginPath();
    
    // Ensure ball is within canvas bounds
    const drawX = Math.max(ball.radius, Math.min(ball.x, this.canvas.width - ball.radius));
    const drawY = Math.max(ball.radius, Math.min(ball.y, this.canvas.height - ball.radius));

    this.ctx.arc(drawX, drawY, ball.radius, 0, Math.PI * 2);
    this.ctx.fillStyle = ball.color;
    this.ctx.fill();
    this.ctx.closePath();
  }

  render(gameState) {
    this.clear();
    if (gameState.ball) {
      this.drawBall(gameState.ball);
    }
  }
}