export class UIController {
  constructor() {
    this.elements = {};
    this.listeners = {};
  }

  registerElement(name, elementId) {
    const element = document.getElementById(elementId);
    if (!element) {
      console.warn(`Element with ID '${elementId}' not found`);
      return;
    }
    this.elements[name] = element;
  }

  registerClickListener(elementName, listener) {
    const element = this.elements[elementName];
    if (element) {
      element.addEventListener('click', listener);
    }
  }

  setElementEnabled(elementName, enabled) {
    const element = this.elements[elementName];
    if (element) {
      element.disabled = !enabled;
    }
  }

  updateButtonStates(isGameRunning) {
    this.setElementEnabled('startButton', !isGameRunning);
    this.setElementEnabled('stopButton', isGameRunning);
  }
}