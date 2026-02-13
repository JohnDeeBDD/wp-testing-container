/**
 * FieldUpdaterManager - Centralized management of all field updaters
 *
 * This class manages the lifecycle of all field updaters, providing
 * a single point of initialization, configuration, and cleanup for
 * the reactive UI system.
 */

import { DataService } from './DataService';
import { FieldUpdater } from './FieldUpdater';
import { TitleUpdater } from './TitleUpdater';
import { DescriptionUpdater } from './DescriptionUpdater';
import { ContentUpdater } from './ContentUpdater';
import { VersionUpdater } from './VersionUpdater';
import { FieldConfig } from '../types/plugin';

export class FieldUpdaterManager {
  private updaters: Map<string, FieldUpdater> = new Map();
  private dataService: DataService;
  private initialized: boolean = false;

  constructor(dataService: DataService) {
    this.dataService = dataService;
  }

  /**
   * Initialize all field updaters
   */
  public init(): void {
    if (this.initialized) {
      console.warn('FieldUpdaterManager: Already initialized');
      return;
    }

    try {
      console.log('FieldUpdaterManager: Initializing field updaters...');

      // Register title updater
      this.registerUpdater('title', new TitleUpdater(this.dataService, {
        elementSelector: '#post-title-display'
      }));

      // Register description updater
      this.registerUpdater('description', new DescriptionUpdater(this.dataService, {
        elementSelector: '#ai-plugin-description-display'
      }));

      // Register content updater
      this.registerUpdater('content', new ContentUpdater(this.dataService, {
        elementSelector: '#entry-content'
      }));

      // Register version updater (refactored to use new system)
      this.registerUpdater('version', new VersionUpdater(this.dataService));

      // Initialize all registered updaters
      let successCount = 0;
      this.updaters.forEach((updater, key) => {
        try {
          updater.init();
          if (updater.isInitialized()) {
            successCount++;
          }
        } catch (error) {
          console.error(`FieldUpdaterManager: Failed to initialize ${key} updater:`, error);
        }
      });

      this.initialized = true;
      console.log(`FieldUpdaterManager: Initialized successfully with ${successCount}/${this.updaters.size} field updaters`);
    } catch (error) {
      console.error('FieldUpdaterManager: Initialization failed:', error);
    }
  }

  /**
   * Register a field updater
   */
  private registerUpdater(key: string, updater: FieldUpdater): void {
    if (this.updaters.has(key)) {
      console.warn(`FieldUpdaterManager: Updater '${key}' already registered, replacing...`);
      const existingUpdater = this.updaters.get(key);
      if (existingUpdater?.isInitialized()) {
        existingUpdater.destroy();
      }
    }

    this.updaters.set(key, updater);
    console.log(`FieldUpdaterManager: Registered ${key} updater`);
  }

  /**
   * Get a specific field updater by key
   */
  public getUpdater(key: string): FieldUpdater | undefined {
    return this.updaters.get(key);
  }

  /**
   * Get all registered updaters
   */
  public getAllUpdaters(): Map<string, FieldUpdater> {
    return new Map(this.updaters);
  }

  /**
   * Check if the manager is initialized
   */
  public isInitialized(): boolean {
    return this.initialized;
  }

  /**
   * Get initialization status of all updaters
   */
  public getStatus(): { [key: string]: boolean } {
    const status: { [key: string]: boolean } = {};
    this.updaters.forEach((updater, key) => {
      status[key] = updater.isInitialized();
    });
    return status;
  }

  /**
   * Destroy all field updaters and clean up resources
   */
  public destroy(): void {
    if (!this.initialized) {
      return;
    }

    console.log('FieldUpdaterManager: Destroying all field updaters...');

    // Destroy all updaters
    this.updaters.forEach((updater, key) => {
      try {
        if (updater.isInitialized()) {
          updater.destroy();
        }
      } catch (error) {
        console.error(`FieldUpdaterManager: Error destroying ${key} updater:`, error);
      }
    });

    // Clear the updaters map
    this.updaters.clear();

    this.initialized = false;
    console.log('FieldUpdaterManager: Destroyed successfully');
  }

  /**
   * Add a custom field updater at runtime
   */
  public addCustomUpdater(key: string, updater: FieldUpdater): void {
    this.registerUpdater(key, updater);
    
    // Initialize immediately if manager is already initialized
    if (this.initialized) {
      try {
        updater.init();
        console.log(`FieldUpdaterManager: Custom updater '${key}' added and initialized`);
      } catch (error) {
        console.error(`FieldUpdaterManager: Failed to initialize custom updater '${key}':`, error);
      }
    }
  }

  /**
   * Remove a field updater
   */
  public removeUpdater(key: string): boolean {
    const updater = this.updaters.get(key);
    if (!updater) {
      console.warn(`FieldUpdaterManager: Updater '${key}' not found`);
      return false;
    }

    try {
      if (updater.isInitialized()) {
        updater.destroy();
      }
      this.updaters.delete(key);
      console.log(`FieldUpdaterManager: Removed updater '${key}'`);
      return true;
    } catch (error) {
      console.error(`FieldUpdaterManager: Error removing updater '${key}':`, error);
      return false;
    }
  }
}