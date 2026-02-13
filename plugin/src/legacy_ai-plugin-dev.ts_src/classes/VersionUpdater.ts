/**
 * VersionUpdater - Updates plugin version with highlight animation
 *
 * This is the refactored version updater that extends the generic FieldUpdater
 * base class, fixing the animation bug and providing consistent behavior.
 */

import { ExtendedPluginData, FieldConfig } from '../types/plugin';
import { DataService } from './DataService';
import { FieldUpdater } from './FieldUpdater';

export class VersionUpdater extends FieldUpdater {
  constructor(dataService: DataService, config?: Partial<FieldConfig>) {
    const defaultConfig: FieldConfig = {
      key: 'version',
      elementSelector: '#ai-plugin-version-display',
      animationType: 'highlight',
      animationDuration: 2000,
      animationClass: 'version-updated' // Keep existing CSS class for compatibility
    };

    super(dataService, { ...defaultConfig, ...config });
  }

  /**
   * Extract version value from data
   */
  extractValue(data: ExtendedPluginData): string | null {
    return data.pluginVersion;
  }

  /**
   * Parse current UI value for initialization
   */
  parseCurrentValue(text: string | null): string | null {
    if (!text || text.trim() === '' || text.trim() === 'Not available') {
      return null;
    }
    return text.trim();
  }

  /**
   * Format version for display
   */
  formatValue(value: string | null): string {
    return value || 'Not available';
  }
}