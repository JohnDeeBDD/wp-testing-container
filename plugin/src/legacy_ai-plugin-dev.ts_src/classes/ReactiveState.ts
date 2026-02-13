/**
 * ReactiveState - Lightweight reactive state management
 *
 * This class provides a simple reactive system using native DOM events
 * for automatic UI updates when data changes.
 */

import { PluginData } from '../types/plugin';

export class ReactiveState {
  private state: PluginData;
  private element: HTMLElement;
  private callbacks: Set<(data: PluginData) => void>;

  constructor(initialState: PluginData, element?: HTMLElement) {
    this.state = { ...initialState };
    this.element = element || document.body;
    this.callbacks = new Set();
  }

  /**
   * Get current state
   */
  public getState(): Readonly<PluginData> {
    return { ...this.state };
  }

  /**
   * Update state and trigger reactions
   */
  public setState(newState: Partial<PluginData>): void {
    const previousState = { ...this.state };
    const changedFields: string[] = [];

    // Update state and track changes
    Object.keys(newState).forEach(key => {
      const typedKey = key as keyof PluginData;
      if (newState[typedKey] !== undefined && newState[typedKey] !== this.state[typedKey]) {
        changedFields.push(key);
        (this.state as any)[typedKey] = newState[typedKey];
      }
    });

    // Always update lastUpdated
    this.state.lastUpdated = new Date();

    // Trigger reactions if there were changes
    if (changedFields.length > 0) {
      this.triggerReactions(changedFields);
    }
  }

  /**
   * Subscribe to state changes
   */
  public subscribe(callback: (data: PluginData) => void): void {
    this.callbacks.add(callback);
    
    // Immediately call with current state
    callback(this.state);
  }

  /**
   * Unsubscribe from state changes
   */
  public unsubscribe(callback: (data: PluginData) => void): void {
    this.callbacks.delete(callback);
  }

  /**
   * Trigger all reactions to state changes
   */
  private triggerReactions(changedFields: string[]): void {
    // Notify callbacks
    this.callbacks.forEach(callback => {
      try {
        callback(this.state);
      } catch (error) {
        console.error('ReactiveState: Error in callback:', error);
      }
    });

    // Dispatch DOM event
    const event = new CustomEvent('stateChanged', {
      detail: {
        state: { ...this.state },
        changedFields
      }
    });

    this.element.dispatchEvent(event);
  }

  /**
   * Create a computed property that automatically updates
   */
  public computed<T>(computeFn: (state: PluginData) => T): T {
    return computeFn(this.state);
  }

  /**
   * Watch for specific field changes
   */
  public watch<K extends keyof PluginData>(
    field: K,
    callback: (newValue: PluginData[K], oldValue: PluginData[K]) => void
  ): void {
    let previousValue = this.state[field];

    this.subscribe((newState) => {
      const newValue = newState[field];
      if (newValue !== previousValue) {
        callback(newValue, previousValue);
        previousValue = newValue;
      }
    });
  }

  /**
   * Destroy the reactive state and clean up
   */
  public destroy(): void {
    this.callbacks.clear();
    console.log('ReactiveState: Destroyed');
  }
}