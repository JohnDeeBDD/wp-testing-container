/**
 * BuildController - Refactored controller using centralized DataService
 *
 * This class manages the plugin builder UI state by subscribing to the
 * centralized DataService for all data updates.
 */

import { AgentStatus, BuilderState, PluginData, DataSubscriber } from '../types/plugin';
import { DataService } from './DataService';

export class BuildController implements DataSubscriber {
  private state: BuilderState;
  private dataService: DataService;

  constructor(dataService: DataService) {
    this.dataService = dataService;
    this.state = {
      agentStatus: 'idle',
      isPolling: false,
      lastUpdated: new Date()
    };
  }

  /**
   * Initialize the BuildController
   */
  public init(): void {
    try {
      // Subscribe to data service updates
      this.dataService.subscribe(this);

      // Set up event listeners
      this.setupEventListeners();

      // Update state based on current data
      const currentData = this.dataService.getData();
      this.updateState(currentData.agentStatus);

      console.log('BuildController: Initialized successfully');
    } catch (error) {
      console.error('BuildController: Initialization failed:', error);
    }
  }

  /**
   * Handle data updates from DataService (implements DataSubscriber)
   */
  public onDataUpdate(data: PluginData): void {
    this.updateState(data.agentStatus);
    // Note: isPolling is no longer relevant since we use event-driven architecture
    this.state.isPolling = false;
  }

  /**
   * Set up event listeners for UI interactions
   */
  private setupEventListeners(): void {
    const buildButton = document.querySelector('.ai-plugin-build-btn') as HTMLButtonElement;
    
    if (buildButton) {
      buildButton.addEventListener('click', async (event) => {
        event.preventDefault();
        
        // Don't allow clicking if already building or unpublished
        if (this.state.agentStatus === 'building' || this.state.agentStatus === 'unpublished') {
          return;
        }
        
        console.log('BuildController: Build button clicked, starting build...');
        
        // Disable button immediately for better UX
        buildButton.disabled = true;
        buildButton.textContent = 'Starting Build...';
        
        try {
          const success = await this.startBuild();
          if (!success) {
            // Re-enable button if build failed to start
            buildButton.disabled = false;
            buildButton.textContent = 'Build Plugin';
            console.error('BuildController: Failed to start build');
          }
        } catch (error) {
          // Re-enable button if there was an error
          buildButton.disabled = false;
          buildButton.textContent = 'Build Plugin';
          console.error('BuildController: Error starting build:', error);
        }
      });
      
      console.log('BuildController: Build button event listener attached');
    } else {
      console.warn('BuildController: Build button not found');
    }
  }

  /**
   * Update agent status via DataService
   */
  public async updateAgentStatus(newStatus: AgentStatus): Promise<boolean> {
    return await this.dataService.updateAgentStatus(newStatus);
  }

  /**
   * Start the build process by updating status to 'building'
   */
  public async startBuild(): Promise<boolean> {
    if (this.state.agentStatus === 'building') {
      console.warn('BuildController: Build already in progress');
      return false;
    }

    if (this.state.agentStatus === 'unpublished') {
      console.warn('BuildController: Cannot build unpublished plugin');
      return false;
    }

    return await this.dataService.updateAgentStatus('building');
  }

  /**
   * Update the internal state and trigger UI updates
   */
  private updateState(newStatus: AgentStatus): void {
    const previousStatus = this.state.agentStatus;
    
    this.state.agentStatus = newStatus;
    this.state.lastUpdated = new Date();

    // Only log and update UI if status actually changed
    if (previousStatus !== newStatus) {
      console.log(`BuildController: Status changed from ${previousStatus} to ${newStatus}`);
      this.updateUI();
    }
  }

  /**
   * Update the UI based on current state
   */
  private updateUI(): void {
    const statusElement = document.querySelector('.agent-status');
    const buildButton = document.querySelector('.ai-plugin-build-btn') as HTMLButtonElement;
    const statusIndicator = document.querySelector('.status-indicator');
    const agentStatusDisplay = document.querySelector('#agent-status-display');
    const agentStatusContainer = document.querySelector('.agent-status-container');

    // Update status text
    if (statusElement) {
      statusElement.textContent = this.getStatusDisplayText();
    }

    // Update status indicator class
    if (statusIndicator) {
      statusIndicator.className = `status-indicator status-${this.state.agentStatus}`;
    }

    // Update build button state
    if (buildButton) {
      const isBuilding = this.state.agentStatus === 'building';
      const isUnpublished = this.state.agentStatus === 'unpublished';
      const isDone = this.state.agentStatus === 'done';
      
      // Hide button entirely if build is complete
      if (isDone) {
        buildButton.style.display = 'none';
        
        // Show completion message if not already present
        this.showCompletionMessage();
      } else {
        // Show button for other statuses
        buildButton.style.display = '';
        buildButton.disabled = isBuilding || isUnpublished;
        
        if (isBuilding) {
          buildButton.textContent = 'Building...';
        } else if (isUnpublished) {
          buildButton.textContent = 'Publish to Build';
        } else {
          buildButton.textContent = 'Build Plugin';
        }
      }
    }

    // Update the agent status display (PHP-rendered element)
    if (agentStatusDisplay) {
      this.updateAgentStatusDisplay(agentStatusDisplay);
    }

    // Add download link if status is done and container exists
    if (this.state.agentStatus === 'done' && agentStatusContainer) {
      this.addDownloadLinkIfNeeded(agentStatusContainer);
    }

    // Dispatch custom event for other components
    const event = new CustomEvent('agentStatusChanged', {
      detail: {
        status: this.state.agentStatus,
        timestamp: this.state.lastUpdated
      }
    });
    document.dispatchEvent(event);
  }

  /**
   * Update the agent status display element
   */
  private updateAgentStatusDisplay(statusDisplay: Element): void {
    const statusTextElement = statusDisplay.querySelector('.status-text');
    const descriptionElement = statusDisplay.querySelector('small');
    
    // Update status class
    statusDisplay.className = `agent-status status-${this.state.agentStatus}`;
    
    // Update status text
    if (statusTextElement) {
      statusTextElement.textContent = this.getStatusDisplayText();
    }
    
    // Update description
    if (descriptionElement) {
      descriptionElement.textContent = this.getStatusDescription();
    }
  }

  /**
   * Add download link if needed and not already present
   */
  private addDownloadLinkIfNeeded(container: Element): void {
    // Check if download link already exists
    const existingDownloadLink = container.querySelector('.download-link');
    if (existingDownloadLink) {
      return; // Already exists
    }

    const postId = this.dataService.getPostId();
    if (!postId) {
      console.error('BuildController: Cannot create download link - missing post ID');
      return;
    }

    // Create download link element
    const downloadDiv = document.createElement('div');
    downloadDiv.className = 'download-link';
    downloadDiv.style.cssText = `
      margin-top: 10px;
      padding: 8px 12px;
      background-color: #d4edda;
      border: 1px solid #c3e6cb;
      border-radius: 4px;
      text-align: center;
    `;

    const downloadLink = document.createElement('a');
    downloadLink.href = `https://aiplugin.dev/wp-content/aiplugins/aiplugin${postId}.zip`;
    downloadLink.target = '_blank';
    downloadLink.textContent = 'Download Plugin';
    downloadLink.style.cssText = `
      color: #155724;
      text-decoration: none;
      font-weight: 500;
    `;
    
    downloadLink.addEventListener('mouseover', () => {
      downloadLink.style.textDecoration = 'underline';
    });
    
    downloadLink.addEventListener('mouseout', () => {
      downloadLink.style.textDecoration = 'none';
    });

    downloadDiv.appendChild(downloadLink);
    container.appendChild(downloadDiv);
  }

  /**
   * Show completion message when build is done
   */
  private showCompletionMessage(): void {
    const buildControls = document.querySelector('.build-controls');
    if (!buildControls) {
      return;
    }

    // Check if completion message already exists
    const existingMessage = buildControls.querySelector('.build-complete-message');
    if (existingMessage) {
      return; // Already exists
    }

    // Create completion message element
    const messageDiv = document.createElement('div');
    messageDiv.className = 'build-complete-message';
    messageDiv.style.cssText = `
      padding: 10px;
      background-color: #d4edda;
      border: 1px solid #c3e6cb;
      border-radius: 4px;
      text-align: center;
      color: #155724;
      font-weight: 500;
      margin-bottom: 10px;
    `;
    messageDiv.textContent = 'Plugin build completed successfully!';

    // Insert at the beginning of build-controls
    buildControls.insertBefore(messageDiv, buildControls.firstChild);
  }

  /**
   * Get status description text
   */
  private getStatusDescription(): string {
    switch (this.state.agentStatus) {
      case 'idle':
        return 'No build process has been started yet.';
      case 'building':
        return 'Build process is currently running...';
      case 'done':
        return 'Build process completed successfully.';
      case 'error':
        return 'Build process failed. Please try again.';
      case 'ready':
        return 'Ready to build.';
      case 'unpublished':
        return 'Publish to enable building.';
      default:
        return `Status: ${this.state.agentStatus}`;
    }
  }

  /**
   * Get human-readable status text
   */
  private getStatusDisplayText(): string {
    switch (this.state.agentStatus) {
      case 'idle':
        return 'Ready to build';
      case 'building':
        return 'Building plugin...';
      case 'done':
        return 'Build completed';
      case 'error':
        return 'Build failed';
      case 'ready':
        return 'Ready';
      case 'unpublished':
        return 'Publish to build';
      default:
        return 'Unknown status';
    }
  }

  /**
   * Get current agent status
   */
  public getAgentStatus(): AgentStatus {
    return this.state.agentStatus;
  }

  /**
   * Check if currently building
   */
  public isBuilding(): boolean {
    return this.state.agentStatus === 'building';
  }

  /**
   * Get current state (for debugging)
   */
  public getState(): Readonly<BuilderState> {
    return { ...this.state };
  }

  /**
   * Destroy the controller and clean up resources
   */
  public destroy(): void {
    // Unsubscribe from data service
    this.dataService.unsubscribe(this);
    console.log('BuildController: Destroyed');
  }
}