import { ChatController } from './chat-controller.js';


const MyChat = (() => {
  let chatController = null;
  
  const initialize = () => {
    try {
      chatController = new ChatController('ws://localhost/ws/');
      chatController.initialize();
    } catch (error) {
      console.error("Failed to initialize chat:", error);
    }
  };
  
  return {
    // Add chat-specific methods if needed later
    sendMessage: (message) => chatController?.sendMessage(message),
    initialize
  };
})();

// Initialize when DOM is loaded
window.addEventListener('load', MyChat.initialize);