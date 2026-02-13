/**
 * FieldUpdater - Generic base class for reactive UI field updates
 *
 * This abstract class provides a foundation for updating any UI field
 * with proper change detection and animation support. It fixes the
 * initial load animation bug by tracking first load state and reading
 * current UI values on initialization.
 */

import { DataSubscriber, ExtendedPluginData, FieldConfig } from '../types/plugin';
import { DataService } from './DataService';

export abstract class FieldUpdater implements DataSubscriber {
  protected initialized: boolean = false;
  protected dataService: DataService;
  protected element: HTMLElement | null = null;
  protected lastValue: any = null;
  protected isFirstLoad: boolean = true; // Fix for animation bug
  protected config: FieldConfig;

  constructor(dataService: DataService, config: FieldConfig) {
    this.dataService = dataService;
    this.config = config;
  }

  /**
   * Initialize the field updater
   */
  public init(): void {
    if (this.initialized) {
      console.warn(`FieldUpdater[${this.config.key}]: Already initialized`);
      return;
    }

    try {
      // Find the target element
      this.element = document.querySelector(this.config.elementSelector);
      
      if (!this.element) {
        console.warn(`FieldUpdater[${this.config.key}]: Element not found: ${this.config.elementSelector}`);
        return;
      }

      // Initialize with current UI value to prevent false animations
      this.initializeCurrentValue();

      // Subscribe to data service updates
      this.dataService.subscribe(this);

      // Update field display based on current data
      const currentData = this.dataService.getData();
      this.updateField(currentData);

      this.initialized = true;
      console.log(`FieldUpdater[${this.config.key}]: Initialized successfully`);
    } catch (error) {
      console.error(`FieldUpdater[${this.config.key}]: Initialization failed:`, error);
    }
  }

  /**
   * Handle data updates from DataService (implements DataSubscriber)
   */
  public onDataUpdate(data: ExtendedPluginData): void {
    console.log(`FieldUpdater[${this.config.key}]: Received data update`, {
      newValue: this.extractValue(data),
      lastValue: this.lastValue,
      isFirstLoad: this.isFirstLoad,
      timestamp: new Date().toISOString()
    });
    this.updateField(data);
  }

  /**
   * Initialize current value from UI to prevent false animations
   * This is the key fix for the animation bug
   */
  protected initializeCurrentValue(): void {
    if (this.element) {
      const currentText = this.element.textContent?.trim() || null;
      this.lastValue = this.parseCurrentValue(currentText);
      console.log(`FieldUpdater[${this.config.key}]: Initialized with current UI value:`, this.lastValue);
    }
  }

  /**
   * Update the field in the UI with proper change detection
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
      this.element.textContent = formattedValue;
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

  /**
   * Determine if animation should be applied
   * This prevents animations on first load (fixing the bug)
   */
  protected shouldAnimate(newValue: any): boolean {
    // Never animate on first load - this is the key bug fix
    if (this.isFirstLoad) {
      return false;
    }
    
    // Only animate if value actually changed and we have a previous value
    return newValue !== this.lastValue && this.lastValue !== null;
  }

  /**
   * Apply animation to the field element
   */
  protected applyAnimation(): void {
    if (!this.element) {
      return;
    }

    const animationClass = this.config.animationClass || `field-${this.config.animationType}`;
    
    // Add animation class
    this.element.classList.add(animationClass);
    
    console.log(`FieldUpdater[${this.config.key}]: Animation applied:`, animationClass);
    
    // Remove animation class after duration
    setTimeout(() => {
      if (this.element) {
        this.element.classList.remove(animationClass);
        console.log(`FieldUpdater[${this.config.key}]: Animation class removed`);
      }
    }, this.config.animationDuration);
  }

  /**
   * Check if the field updater is initialized
   */
  public isInitialized(): boolean {
    return this.initialized;
  }

  /**
   * Destroy the field updater and clean up resources
   */
  public destroy(): void {
    if (!this.initialized) {
      return;
    }

    // Unsubscribe from data service
    this.dataService.unsubscribe(this);

    // Clear references
    this.element = null;
    this.lastValue = null;

    this.initialized = false;
    console.log(`FieldUpdater[${this.config.key}]: Destroyed`);
  }

  // Abstract methods that must be implemented by subclasses
  
  /**
   * Extract the relevant value from the data object
   */
  abstract extractValue(data: ExtendedPluginData): any;

  /**
   * Parse the current UI value (used for initialization)
   */
  abstract parseCurrentValue(text: string | null): any;

  /**
   * Format the value for display in the UI
   */
  abstract formatValue(value: any): string;
}