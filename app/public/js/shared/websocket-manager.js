export class WebSocketManager {
  constructor(url) {
    this.url = url;
    this.ws = null;
    this.messageHandlers = new Map();
    this.connectionListeners = [];
    this.disconnectionListeners = [];
    this.errorListeners = [];
  }

  connect() {
    if (this.ws && (this.ws.readyState === WebSocket.OPEN || this.ws.readyState === WebSocket.CONNECTING)) {
      console.log("WebSocket already open or connecting.");
      return;
    }

    this.ws = new WebSocket(this.url);
    
    this.ws.onopen = (event) => {
      console.log("WebSocket connected to game server.");
      this.connectionListeners.forEach(listener => listener(event));
    };

    this.ws.onmessage = (event) => {
      const message = JSON.parse(event.data);
      // console.log("Received message from server:", message);
      
      const handler = this.messageHandlers.get(message.type);
      if (handler) {
        handler(message);
      }
    };

    this.ws.onclose = (event) => {
      console.log("WebSocket disconnected from game server:", event);
      this.disconnectionListeners.forEach(listener => listener(event));
    };

    this.ws.onerror = (error) => {
      console.error("WebSocket error:", error);
      this.errorListeners.forEach(listener => listener(error));
    };
  }

  registerMessageHandler(type, handler) {
    this.messageHandlers.set(type, handler);
  }

  onConnection(listener) {
    this.connectionListeners.push(listener);
  }

  onDisconnection(listener) {
    this.disconnectionListeners.push(listener);
  }

  onError(listener) {
    this.errorListeners.push(listener);
  }

  send(message) {
    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify(message));
    } else {
      console.warn("WebSocket not open. Cannot send message:", message);
    }
  }

  isConnected() {
    return this.ws && this.ws.readyState === WebSocket.OPEN;
  }
}