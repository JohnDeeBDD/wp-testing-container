/**
 * Type definitions for AI Plugin Dev
 */

export type AgentStatus = 'idle' | 'done' | 'building' | 'error' | 'ready' | 'unpublished';

export interface BuilderState {
  agentStatus: AgentStatus;
  isPolling: boolean;
  lastUpdated: Date;
}

export interface AgentStatusChangeEvent extends CustomEvent {
  detail: {
    status: AgentStatus;
    timestamp: Date;
  };
}

export interface WordPressLocalizedData {
  post_id: string;
  rest_nonce: string;
  ajax_url: string;
  [key: string]: any;
}

export interface RestAPIResponse {
  id: number;
  agent_status: AgentStatus;
  [key: string]: any;
}

// New interfaces for the refactored architecture
export interface PluginData {
  actionEnabled: boolean;
  agentStatus: AgentStatus;
  pluginVersion: string | null;
  lastUpdated: Date;
}

// Extended interface for all FrontendData.php fields
export interface ExtendedPluginData extends PluginData {
  title: string | null;
  content: string | null;
  description: string | null; // ai-plugin-description
}

export interface DataSubscriber {
  onDataUpdate(data: ExtendedPluginData): void;
}

// Field configuration for the generic updater system
export interface FieldConfig {
  key: string;
  elementSelector: string;
  animationType: 'highlight' | 'fade' | 'slide' | 'typing' | 'color';
  animationDuration: number;
  animationClass?: string;
  formatter?: (value: any) => string;
  validator?: (value: any) => boolean;
}

// Animation types enum for better type safety
export type AnimationType = 'highlight' | 'fade' | 'slide' | 'typing' | 'color';

export interface DataChangeEvent extends CustomEvent {
  detail: {
    data: ExtendedPluginData;
    changedFields: string[];
  };
}

export interface PluginMetadata {
  // Current requirements
  actionEnabled: boolean;
  agentStatus: AgentStatus;
  pluginVersion: string | null;
  
  // Future extensibility
  buildProgress?: number;
  buildLogs?: string[];
  dependencies?: string[];
  [key: string]: any; // For unknown future fields
}

// Cacbot integration types
export interface CacbotEventData {
  post_id: string;
  user_id: number;
  content: string;
  title: string;
  "ai-plugin-description": string;
  "ai-plugin-version": string;
  _cacbot_action_enabled_build_plugin: string; // "1" or "0"
  agent_status: string; // Agent status from post meta
  [key: string]: any; // Allow other cacbot-specific fields
}

export interface CacbotDataUpdatedEvent extends CustomEvent {
  detail: CacbotEventData;
}

// Extend Window interface for global access
declare global {
  interface Window {
    AIPluginDev: WordPressLocalizedData;
    aiPluginDevBuildController: import('../classes/BuildController').BuildController | null;
    aiPluginDevFrontendActionButton: import('../classes/FrontendBuildPluginActionButton').FrontendBuildPluginActionButton | null;
    wpApiSettings?: {
      nonce: string;
    };
    pagenow?: string;
    post?: {
      post_status: string;
    };
    cacbot?: {
      isInitialized: boolean;
      [key: string]: any;
    };
  }

  interface Document {
    readyState: 'loading' | 'interactive' | 'complete';
  }

  interface DocumentEventMap {
    'cacbot-data-updated': CacbotDataUpdatedEvent;
  }
}
