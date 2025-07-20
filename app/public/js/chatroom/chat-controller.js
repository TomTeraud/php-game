import { UIController } from '../shared/ui-controller.js';
import { WebSocketManager } from '../shared/websocket-manager.js';
import { ChatMessageManager } from './chat-message-manager.js';

export class ChatController {
  constructor(websocketUrl) {
    console.log("ChatController: Setting up components...");
    
    // Create components but don't start connection yet
    this.websocketManager = new WebSocketManager(websocketUrl);
    this.messageManager = new ChatMessageManager('messages');
    this.uiController = new UIController();
    
    // Set up handlers but don't connect
    this.setupWebSocketHandlers();
    this.setupUIElements();
    
    console.log("ChatController: Components ready, waiting for initialize()");
  }

  initialize() {
    console.log("ChatController: Starting initialization...");
    
    // Set initial UI state
    this.uiController.setElementText('status', 'Attempting to connect...');
    this.uiController.setElementEnabled('messageInput', false);
    this.uiController.setElementEnabled('sendButton', false);
    
    // Start WebSocket connection
    this.websocketManager.connect();
    
    console.log("ChatController: Connection started");
  }

  setupWebSocketHandlers() {
    // Handle incoming chat messages
    this.websocketManager.registerMessageHandler('chat_message', (message) => {
      const sender = message.user || "Server";
      const content = message.message || "No message content.";
      this.messageManager.addOtherMessage(sender, content);
    });

    // Handle game messages (ignore them in chat)
    this.websocketManager.registerMessageHandler('game_state_update', (message) => {
      console.log("Received game state update in chat, ignoring:", message);
    });

    // Connection established
    this.websocketManager.onConnection(() => {
      this.uiController.setElementText('status', 'Status: Connected');
      this.messageManager.addSystemMessage('Connection established!');
      this.uiController.setElementEnabled('messageInput', true);
      this.uiController.setElementEnabled('sendButton', true);
    });

    // Connection lost
    this.websocketManager.onDisconnection((event) => {
      this.uiController.setElementText('status', 'Status: Disconnected. Reconnecting...');
      this.uiController.setElementEnabled('messageInput', false);
      this.uiController.setElementEnabled('sendButton', false);
      this.messageManager.addSystemMessage(`Connection closed. Code: ${event.code}. Reason: ${event.reason || 'No reason'}`);
    });

    // Connection error
    this.websocketManager.onError((error) => {
      this.uiController.setElementText('status', 'Status: Error connecting');
      this.messageManager.addSystemMessage('Connection error. Check console for details.');
      this.uiController.setElementEnabled('messageInput', false);
      this.uiController.setElementEnabled('sendButton', false);
      console.error("Chat WebSocket error:", error);
    });
  }

  setupUIElements() {
    // Register all UI elements
    this.uiController.registerElements({
      'status': 'status',
      'messageInput': 'messageInput',
      'sendButton': 'sendButton'
    });

    // Set up event listeners
    this.uiController.registerClickListener('sendButton', () => this.sendMessage());
    this.uiController.registerKeyListener('messageInput', 'Enter', () => this.sendMessage());
  }

  sendMessage() {
    const message = this.uiController.getElementValue('messageInput').trim();
    
    if (message && this.websocketManager.isConnected()) {
      // Send to server
      const chatMessage = {
        type: 'chat_message',
        message: message
      };
      this.websocketManager.send(chatMessage);
      
      // Display in UI
      this.messageManager.addUserMessage(message);
      this.uiController.clearElement('messageInput');
    } else {
      this.messageManager.addSystemMessage('Cannot send message. Connection not open or message is empty.');
      console.warn('Attempted to send message when WS not open or message empty.');
    }
  }
}