/**
 * TitleUpdater - Updates post title with slide animation
 *
 * Handles reactive updates to the post title field with a smooth
 * slide animation when the title changes.
 */

import { ExtendedPluginData, FieldConfig } from '../types/plugin';
import { DataService } from './DataService';
import { FieldUpdater } from './FieldUpdater';

export class TitleUpdater extends FieldUpdater {
  constructor(dataService: DataService, config?: Partial<FieldConfig>) {
    const defaultConfig: FieldConfig = {
      key: 'title',
      elementSelector: '#post-title-display',
      animationType: 'slide',
      animationDuration: 300,
      animationClass: 'field-slide'
    };

    super(dataService, { ...defaultConfig, ...config });
  }

  /**
   * Extract title value from data
   */
  extractValue(data: ExtendedPluginData): string | null {
    return data.title;
  }

  /**
   * Parse current UI value for initialization
   */
  parseCurrentValue(text: string | null): string | null {
    return text && text.trim() !== '' ? text.trim() : null;
  }

  /**
   * Format title for display
   */
  formatValue(value: string | null): string {
    return value || 'Untitled';
  }
}