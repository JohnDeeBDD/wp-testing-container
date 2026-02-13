/**
 * DataService - Event-driven data management for AI Plugin Dev
 *
 * This class provides a reactive data layer that listens to cacbot events
 * instead of polling the REST API. It eliminates duplicate API calls and provides
 * a single source of truth for plugin data by leveraging cacbot's data emission system.
 */

import { AgentStatus, PluginData, ExtendedPluginData, DataSubscriber, DataChangeEvent, WordPressLocalizedData, CacbotEventData, CacbotDataUpdatedEvent } from '../types/plugin';

export class DataService {
  private data: ExtendedPluginData;
  private subscribers: Set<DataSubscriber>;
  private postId: string | null = null;
  private restNonce: string | null = null;
  private cacbotEventListener: ((event: CacbotDataUpdatedEvent) => void) | null = null;

  constructor() {
    this.data = {
      actionEnabled: false,
      agentStatus: 'idle',
      pluginVersion: null,
      lastUpdated: new Date(),
      title: null,
      content: null,
      description: null
    };
    this.subscribers = new Set();
  }

  /**
   * Initialize the DataService - wait for cacbot pulse
   */
  public async init(): Promise<void> {
    // Set up cacbot event listener first
    this.setupCacbotEventListener();

    // Wait for cacbot to be available and emit first event
    await this.waitForCacbotPulse();

    console.log('DataService: Initialized successfully with cacbot event listener');
  }

  /**
   * Wait for cacbot to be available and emit the first data event
   */
  private async waitForCacbotPulse(): Promise<void> {
    return new Promise((resolve, reject) => {
      const timeout = setTimeout(() => {
        reject(new Error('DataService: Timeout waiting for cacbot pulse - cacbot may not be available'));
      }, 10000); // 10 second timeout

      // Listen for the first cacbot event to confirm it's working
      const firstEventHandler = (event: CacbotDataUpdatedEvent) => {
        console.log('DataService: First cacbot pulse received, initialization complete');
        clearTimeout(timeout);
        document.removeEventListener('cacbot-data-updated', firstEventHandler);
        resolve();
      };

      document.addEventListener('cacbot-data-updated', firstEventHandler);
      
      // Also check if cacbot is already available
      if (this.isCacbotAvailable()) {
        console.log('DataService: cacbot already available, waiting for first event...');
      } else {
        console.log('DataService: Waiting for cacbot to become available...');
      }
    });
  }

  /**
   * Subscribe to data updates
   */
  public subscribe(subscriber: DataSubscriber): void {
    this.subscribers.add(subscriber);
    
    // Immediately notify the new subscriber with current data
    subscriber.onDataUpdate(this.data);
    
    console.log('DataService: New subscriber added, total subscribers:', this.subscribers.size);
  }

  /**
   * Unsubscribe from data updates
   */
  public unsubscribe(subscriber: DataSubscriber): void {
    this.subscribers.delete(subscriber);
    console.log('DataService: Subscriber removed, total subscribers:', this.subscribers.size);
  }

  /**
   * Get current data
   */
  public getData(): Readonly<ExtendedPluginData> {
    return { ...this.data };
  }

  /**
   * Update agent status via WordPress REST API
   */
  public async updateAgentStatus(newStatus: AgentStatus): Promise<boolean> {
    if (!this.postId || !this.restNonce) {
      console.error('DataService: Missing post ID or REST nonce');
      return false;
    }

    try {
      const response = await fetch(`/wp-json/wp/v2/ai-plugins/${this.postId}`, {
        method: 'POST',
        headers: {
          'X-WP-Nonce': this.restNonce,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          agent_status: newStatus
        })
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      console.log('DataService: Agent status updated successfully:', newStatus);
      
      // Update local data immediately for better UX
      this.updateData({ agentStatus: newStatus });
      
      return true;
    } catch (error) {
      console.error('DataService: Failed to update agent status:', error);
      return false;
    }
  }

  /**
   * Check if cacbot is available and initialized
   */
  private isCacbotAvailable(): boolean {
    // Check if cacbot has initialized and is emitting events
    return typeof window.cacbot !== 'undefined' && window.cacbot.isInitialized === true;
  }

  /**
   * Set up cacbot event listener
   */
  private setupCacbotEventListener(): void {
    // Create bound event handler for cleanup
    this.cacbotEventListener = this.handleCacbotDataUpdate.bind(this);
    
    document.addEventListener('cacbot-data-updated', this.cacbotEventListener);
    console.log('DataService: Cacbot event listener set up successfully');
  }

  /**
   * Handle cacbot data update events
   */
  private handleCacbotDataUpdate(event: CacbotDataUpdatedEvent): void {
    console.log('DataService: cacbot-data-updated event received', {
      hasDetail: !!event.detail,
      eventType: event.type,
      timestamp: new Date().toISOString()
    });
    
    if (!event.detail) {
      throw new Error('DataService: cacbot-data-updated event received with no data');
    }
    
    const cacbotData = event.detail;
    
    // Initialize post_id and rest_nonce from first cacbot event if not set
    if (!this.postId && cacbotData.post_id) {
      this.postId = cacbotData.post_id;
      console.log('DataService: post_id initialized from cacbot:', this.postId);
    }
    
    // Get rest_nonce from WordPress global if available and not already set
    if (!this.restNonce && window.wpApiSettings?.nonce) {
      this.restNonce = window.wpApiSettings.nonce;
      console.log('DataService: rest_nonce initialized from wpApiSettings');
    } else if (!this.restNonce && window.AIPluginDev?.rest_nonce) {
      this.restNonce = window.AIPluginDev.rest_nonce;
      console.log('DataService: rest_nonce initialized from AIPluginDev localized data');
    }
    
    console.log('DataService: Raw cacbot data received:', {
      post_id: cacbotData.post_id,
      expectedPostId: this.postId,
      hasVersionField: 'ai-plugin-version' in cacbotData,
      versionValue: cacbotData['ai-plugin-version'],
      allKeys: Object.keys(cacbotData)
    });
    
    // Only process data for our specific post (if we have a post_id to compare)
    if (this.postId && cacbotData.post_id !== this.postId) {
      console.log('DataService: Ignoring event for different post', {
        eventPostId: cacbotData.post_id,
        expectedPostId: this.postId
      });
      return; // Ignore events for other posts
    }
    
    console.log('DataService: Processing cacbot data update for our post:', cacbotData);
    
    const mappedData = this.mapCacbotDataToPluginData(cacbotData);
    console.log('DataService: Mapped data:', mappedData);
    
    this.updateData(mappedData);
  }

  /**
   * Map cacbot event data to ExtendedPluginData interface with strict validation
   */
  private mapCacbotDataToPluginData(cacbotData: CacbotEventData): Partial<ExtendedPluginData> {
    // Strict validation - fail if critical fields are missing
    if (typeof cacbotData._cacbot_action_enabled_build_plugin === 'undefined') {
      throw new Error('DataService: _cacbot_action_enabled_build_plugin field missing from cacbot data');
    }
    
    if (typeof cacbotData.agent_status === 'undefined') {
      throw new Error('DataService: agent_status field missing from cacbot data');
    }
    
    return {
      // Existing fields
      actionEnabled: cacbotData._cacbot_action_enabled_build_plugin === "1",
      pluginVersion: cacbotData["ai-plugin-version"] || null,
      agentStatus: cacbotData.agent_status as AgentStatus,
      
      // New fields from FrontendData.php
      title: cacbotData.title || null,
      content: cacbotData.content || null,
      description: cacbotData["ai-plugin-description"] || null,
    };
  }

  /**
   * Load initial data from cacbot - attempt to get current data
   */
  private loadInitialDataFromCacbot(): void {
    // Since we can't directly query cacbot for current data,
    // we'll wait for the first event or use default values
    // The cacbot system should emit an event shortly after initialization
    console.log('DataService: Waiting for initial cacbot data event...');
  }

  /**
   * Update internal data and notify subscribers
   */
  private updateData(newData: Partial<ExtendedPluginData>): void {
    const previousData = { ...this.data };
    const changedFields: string[] = [];

    // Update data and track changes
    Object.keys(newData).forEach(key => {
      const typedKey = key as keyof ExtendedPluginData;
      if (newData[typedKey] !== undefined && newData[typedKey] !== this.data[typedKey]) {
        changedFields.push(key);
        (this.data as any)[typedKey] = newData[typedKey];
      }
    });

    // Always update lastUpdated
    this.data.lastUpdated = new Date();

    // Only notify subscribers if there were actual changes
    if (changedFields.length > 0) {
      console.log('DataService: Data updated, changed fields:', changedFields);
      this.notifySubscribers(changedFields);
      this.dispatchDataChangeEvent(changedFields);
    }
  }

  /**
   * Notify all subscribers of data changes
   */
  private notifySubscribers(changedFields: string[]): void {
    this.subscribers.forEach(subscriber => {
      try {
        subscriber.onDataUpdate(this.data);
      } catch (error) {
        console.error('DataService: Error notifying subscriber:', error);
      }
    });
  }

  /**
   * Dispatch custom DOM event for data changes
   */
  private dispatchDataChangeEvent(changedFields: string[]): void {
    const event: DataChangeEvent = new CustomEvent('dataChanged', {
      detail: {
        data: { ...this.data },
        changedFields
      }
    }) as DataChangeEvent;

    document.dispatchEvent(event);
  }

  /**
   * Get current post ID
   */
  public getPostId(): string | null {
    return this.postId;
  }

  /**
   * Destroy the service and clean up resources
   */
  public destroy(): void {
    // Clean up event listener
    if (this.cacbotEventListener) {
      document.removeEventListener('cacbot-data-updated', this.cacbotEventListener);
      this.cacbotEventListener = null;
    }
    
    this.subscribers.clear();
    console.log('DataService: Destroyed and cleaned up event listeners');
  }

  // Legacy methods removed:
  // - startPolling()
  // - stopPolling() 
  // - fetchPluginData()
  // - loadInitialData()
  // - isCurrentlyPolling()
  // - pollingInterval property
  // - POLLING_INTERVAL_MS constant
  // - isPolling property
}