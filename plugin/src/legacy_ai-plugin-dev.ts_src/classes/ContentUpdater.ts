/**
 * ContentUpdater - Updates post content with typing animation
 *
 * Handles reactive updates to the post content field with a typing
 * animation effect when the content changes.
 */

import { ExtendedPluginData, FieldConfig } from '../types/plugin';
import { DataService } from './DataService';
import { FieldUpdater } from './FieldUpdater';

export class ContentUpdater extends FieldUpdater {
  constructor(dataService: DataService, config?: Partial<FieldConfig>) {
    const defaultConfig: FieldConfig = {
      key: 'content',
      elementSelector: '#entry-content',
      animationType: 'slide',
      animationDuration: 300,
      animationClass: 'field-slide'
    };

    super(dataService, { ...defaultConfig, ...config });
  }

  /**
   * Extract content value from data
   */
  extractValue(data: ExtendedPluginData): string | null {
    return data.content;
  }

  /**
   * Parse current UI value for initialization
   */
  parseCurrentValue(text: string | null): string | null {
    return text && text.trim() !== '' ? text.trim() : null;
  }

  /**
   * Format content for display - preserve HTML content for rendering
   */
  formatValue(value: string | null): string {
    if (!value) {
      return '';
    }
    
    // Decode HTML entities that may have been escaped by WordPress REST API
    // but preserve the HTML structure for rendering
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = value;
    return tempDiv.innerHTML;
  }

  /**
   * Override updateField to use innerHTML instead of textContent for HTML content
   */
  protected updateField(data: ExtendedPluginData): void {
    if (!this.element) {
      console.warn(`FieldUpdater[${this.config.key}]: Element not found, cannot update`);
      return;
    }

    const newValue = this.extractValue(data);
    
    console.log(`FieldUpdater[${this.config.key}]: Attempting to update field`, {
      newValue,
      lastValue: this.lastValue,
      hasChanged: newValue !== this.lastValue,
      shouldAnimate: this.shouldAnimate(newValue),
      elementExists: !!this.element,
      currentElementText: this.element.textContent
    });
    
    // Only update if value has actually changed
    if (newValue !== this.lastValue) {
      const formattedValue = this.formatValue(newValue);
      // Use innerHTML instead of textContent to render HTML content
      this.element.innerHTML = formattedValue;
      console.log(`FieldUpdater[${this.config.key}]: Field updated to:`, formattedValue);
      
      // Only animate if this is not the first load and value actually changed
      if (this.shouldAnimate(newValue)) {
        this.applyAnimation();
      }
      
      this.lastValue = newValue;
    } else {
      console.log(`FieldUpdater[${this.config.key}]: Value unchanged, skipping update`);
    }

    // Mark first load as complete after first update attempt
    if (this.isFirstLoad) {
      this.isFirstLoad = false;
      console.log(`FieldUpdater[${this.config.key}]: First load complete`);
    }
  }
}