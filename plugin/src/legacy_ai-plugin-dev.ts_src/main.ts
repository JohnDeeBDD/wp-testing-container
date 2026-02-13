/**
 * Main entry point for AI Plugin Dev TypeScript - Refactored Architecture
 *
 * This file serves as the entry point for the TypeScript build process
 * and initializes the centralized DataService with all dependent components.
 */

import { DataService } from './classes/DataService';
import { BuildController } from './classes/BuildController';
import { FrontendBuildPluginActionButton } from './classes/FrontendBuildPluginActionButton';
import { FieldUpdaterManager } from './classes/FieldUpdaterManager';


/**
 * Global DataService instance - centralized data management
 */
let dataService: DataService | null = null;

/**
 * Global BuildController instance
 */
let buildController: BuildController | null = null;

/**
 * Global FrontendBuildPluginActionButton instance
 */
let frontendActionButton: FrontendBuildPluginActionButton | null = null;

/**
 * Global FieldUpdaterManager instance
 */
let fieldUpdaterManager: FieldUpdaterManager | null = null;


/**
 * Initialize the AI Plugin Dev admin functionality
 * Now waits for cacbot pulse instead of requiring WordPress localized data
 */
async function initAIPluginDev(): Promise<void> {
  try {
    console.log('AI Plugin Dev: Starting initialization, waiting for cacbot...');
    
    // Create and initialize the centralized DataService first
    // This will now wait for cacbot pulse instead of failing immediately
    dataService = new DataService();
    await dataService.init();
    
    // Create and initialize the BuildController with DataService
    buildController = new BuildController(dataService);
    buildController.init();
    
    // Create and initialize the FrontendBuildPluginActionButton with DataService
    frontendActionButton = new FrontendBuildPluginActionButton(dataService);
    frontendActionButton.init();
    
    // Create and initialize the FieldUpdaterManager with DataService
    fieldUpdaterManager = new FieldUpdaterManager(dataService);
    fieldUpdaterManager.init();
    
    // Set global references after initialization for debugging
    (window as any).aiPluginDevDataService = dataService;
    (window as any).aiPluginDevBuildController = buildController;
    (window as any).aiPluginDevFrontendActionButton = frontendActionButton;
    (window as any).aiPluginDevFieldUpdaterManager = fieldUpdaterManager;
    
    console.log('AI Plugin Dev: Initialized successfully with centralized data service');
  } catch (error) {
    console.error('AI Plugin Dev: Initialization failed:', error);
    console.log('AI Plugin Dev: This may be expected if cacbot is not available or not emitting events yet');
  }
}

/**
 * Cleanup function for when the page is unloaded
 */
function cleanup(): void {
  if (buildController) {
    buildController.destroy();
    buildController = null;
  }
  
  if (frontendActionButton) {
    frontendActionButton.destroy();
    frontendActionButton = null;
  }
  
  if (fieldUpdaterManager) {
    fieldUpdaterManager.destroy();
    fieldUpdaterManager = null;
  }
  
  if (dataService) {
    dataService.destroy();
    dataService = null;
  }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initAIPluginDev);
} else {
  initAIPluginDev();
}

// Cleanup on page unload
//window.addEventListener('beforeunload', cleanup);

// Export for global access (for debugging purposes) - will be set after initialization
(window as any).aiPluginDevDataService = null;
(window as any).aiPluginDevBuildController = null;
(window as any).aiPluginDevFrontendActionButton = null;
(window as any).aiPluginDevFieldUpdaterManager = null;


jQuery(function($) {
 jQuery("#action-button-build-plugin").on("click", function() {
    //should get the text from #comment and append "[ai-plugin-dev-build-plugin-action-button-pressed]" to it
    var commentBox = jQuery("#comment");
    if (commentBox.length) {
        var currentText = commentBox.val() as string;
        commentBox.val(currentText + "\n[ai-plugin-dev-build-plugin-action-button-pressed]");
    } else {
        console.warn("Comment box not found");
    }
    jQuery("#submit").click();
    return false; // Prevent default action
  });
});