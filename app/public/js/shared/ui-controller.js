export class UIController {
  constructor() {
    this.elements = {};
  }

  // --- Element Registration ---
  registerElement(name, elementId) {
    const element = document.getElementById(elementId);
    if (!element) {
      console.warn(`Element with ID '${elementId}' not found`);
      return false;
    }
    this.elements[name] = element;
    return true;
  }

  registerElements(elementMap) {
    // Register multiple elements at once
    // elementMap = { 'startButton': 'startGameButton', 'canvas': 'gameCanvas' }
    Object.entries(elementMap).forEach(([name, elementId]) => {
      this.registerElement(name, elementId);
    });
  }

  // --- Event Listeners ---
  registerClickListener(elementName, listener) {
    const element = this.elements[elementName];
    if (element) {
      element.addEventListener('click', listener);
    } else {
      console.warn(`Element '${elementName}' not registered for click listener`);
    }
  }

  registerKeyListener(elementName, key, listener) {
    const element = this.elements[elementName];
    if (element) {
      element.addEventListener('keypress', (e) => {
        if (e.key === key) listener(e);
      });
    } else {
      console.warn(`Element '${elementName}' not registered for key listener`);
    }
  }

  registerInputListener(elementName, listener) {
    const element = this.elements[elementName];
    if (element) {
      element.addEventListener('input', listener);
    } else {
      console.warn(`Element '${elementName}' not registered for input listener`);
    }
  }

  registerChangeListener(elementName, listener) {
    const element = this.elements[elementName];
    if (element) {
      element.addEventListener('change', listener);
    } else {
      console.warn(`Element '${elementName}' not registered for change listener`);
    }
  }

  // --- Element State Management ---
  setElementEnabled(elementName, enabled) {
    const element = this.elements[elementName];
    if (element) {
      element.disabled = !enabled;
    } else {
      console.warn(`Element '${elementName}' not registered for enable/disable`);
    }
  }

  setElementVisible(elementName, visible) {
    const element = this.elements[elementName];
    if (element) {
      element.style.display = visible ? '' : 'none';
    } else {
      console.warn(`Element '${elementName}' not registered for visibility`);
    }
  }

  setElementText(elementName, text) {
    const element = this.elements[elementName];
    if (element) {
      element.textContent = text;
    } else {
      console.warn(`Element '${elementName}' not registered for text update`);
    }
  }

  setElementHTML(elementName, html) {
    const element = this.elements[elementName];
    if (element) {
      element.innerHTML = html;
    } else {
      console.warn(`Element '${elementName}' not registered for HTML update`);
    }
  }

  // --- Element Value Management ---
  getElementValue(elementName) {
    const element = this.elements[elementName];
    if (element) {
      return element.value || '';
    } else {
      console.warn(`Element '${elementName}' not registered for value retrieval`);
      return '';
    }
  }

  setElementValue(elementName, value) {
    const element = this.elements[elementName];
    if (element) {
      element.value = value;
    } else {
      console.warn(`Element '${elementName}' not registered for value setting`);
    }
  }

  clearElement(elementName) {
    const element = this.elements[elementName];
    if (element) {
      if (element.value !== undefined) {
        element.value = '';
      }
      if (element.focus && typeof element.focus === 'function') {
        element.focus();
      }
    } else {
      console.warn(`Element '${elementName}' not registered for clearing`);
    }
  }

  // --- Element CSS Management ---
  addElementClass(elementName, className) {
    const element = this.elements[elementName];
    if (element) {
      element.classList.add(className);
    } else {
      console.warn(`Element '${elementName}' not registered for class addition`);
    }
  }

  removeElementClass(elementName, className) {
    const element = this.elements[elementName];
    if (element) {
      element.classList.remove(className);
    } else {
      console.warn(`Element '${elementName}' not registered for class removal`);
    }
  }

  toggleElementClass(elementName, className) {
    const element = this.elements[elementName];
    if (element) {
      element.classList.toggle(className);
    } else {
      console.warn(`Element '${elementName}' not registered for class toggle`);
    }
  }

  // --- Bulk Operations ---
  updateButtonStates(buttonStates) {
    // buttonStates = { 'startButton': true, 'stopButton': false, 'pauseButton': true }
    Object.entries(buttonStates).forEach(([buttonName, enabled]) => {
      this.setElementEnabled(buttonName, enabled);
    });
  }

  updateElementTexts(textUpdates) {
    // textUpdates = { 'status': 'Connected', 'score': '1500', 'level': 'Level 3' }
    Object.entries(textUpdates).forEach(([elementName, text]) => {
      this.setElementText(elementName, text);
    });
  }

  // --- Utility Methods ---
  hasElement(elementName) {
    return elementName in this.elements && this.elements[elementName] !== null;
  }

  getElement(elementName) {
    // Direct access when you need the actual DOM element
    return this.elements[elementName] || null;
  }

  getAllElements() {
    return { ...this.elements }; // Return copy to prevent external modification
  }
}