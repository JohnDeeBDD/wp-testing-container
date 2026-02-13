/**
 * DescriptionUpdater - Updates plugin description with fade animation
 *
 * Handles reactive updates to the ai-plugin-description field with a smooth
 * fade animation when the description changes.
 */

import { ExtendedPluginData, FieldConfig } from '../types/plugin';
import { DataService } from './DataService';
import { FieldUpdater } from './FieldUpdater';

export class DescriptionUpdater extends FieldUpdater {
  constructor(dataService: DataService, config?: Partial<FieldConfig>) {
    const defaultConfig: FieldConfig = {
      key: 'description',
      elementSelector: '#ai-plugin-description-display',
      animationType: 'fade',
      animationDuration: 500,
      animationClass: 'field-fade'
    };

    super(dataService, { ...defaultConfig, ...config });
  }

  /**
   * Extract description value from data
   */
  extractValue(data: ExtendedPluginData): string | null {
    return data.description;
  }

  /**
   * Parse current UI value for initialization
   */
  parseCurrentValue(text: string | null): string | null {
    return text && text.trim() !== '' ? text.trim() : null;
  }

  /**
   * Format description for display
   */
  formatValue(value: string | null): string {
    return value || 'No description available';
  }
}