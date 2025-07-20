export class ChatMessageManager {
  constructor(containerElementId) {
    this.messageContainer = document.getElementById(containerElementId);
  }

  addMessage(sender, message) {
    const p = document.createElement('p');
    p.classList.add('py-1', 'px-2', 'rounded-md', 'mb-1', 'break-words');

    if (sender === "You") {
      p.classList.add('bg-blue-100', 'text-blue-800', 'self-end');
      p.style.textAlign = 'right';
      p.innerHTML = `<strong>${sender}:</strong> ${message}`;
    } else if (sender === "System") {
      p.classList.add('bg-gray-200', 'text-gray-700', 'font-medium', 'text-center');
      p.innerHTML = `<em>${message}</em>`;
    } else {
      p.classList.add('bg-white', 'text-gray-900', 'border', 'border-gray-100');
      p.innerHTML = `<strong>${sender}:</strong> ${message}`;
    }

    this.messageContainer.appendChild(p);
    this.messageContainer.scrollTop = this.messageContainer.scrollHeight;
  }

  addSystemMessage(message) {
    this.addMessage("System", message);
  }

  addUserMessage(message) {
    this.addMessage("You", message);
  }

  addOtherMessage(sender, message) {
    this.addMessage(sender, message);
  }
}