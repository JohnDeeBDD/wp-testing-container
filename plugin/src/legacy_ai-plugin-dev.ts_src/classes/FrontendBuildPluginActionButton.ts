/**
 * FrontendBuildPluginActionButton - Refactored to use DataService and LoadingAnimation
 *
 * This class listens for clicks on the "action-button-build-plugin" element,
 * appends a specific string to the comment textarea, submits the form, and
 * dynamically shows/hides the button based on centralized data from DataService.
 */

import { DataSubscriber, PluginData } from '../types/plugin';
import { DataService } from './DataService';
import { LoadingAnimation } from './LoadingAnimation';

export class FrontendBuildPluginActionButton implements DataSubscriber {
  private initialized: boolean = false;
  private dataService: DataService;
  private loadingAnimation: LoadingAnimation;
  private lastButtonState: boolean | null = null;

  constructor(dataService: DataService) {
    this.dataService = dataService;
    this.loadingAnimation = new LoadingAnimation();
  }

  /**
   * Initialize the action button handler
   */
  public init(): void {
    if (this.initialized) {
      console.warn('FrontendBuildPluginActionButton: Already initialized');
      return;
    }

    try {
      // Subscribe to data service updates
      this.dataService.subscribe(this);

      this.setupEventListeners();
      
      // Update button visibility based on current data
      const currentData = this.dataService.getData();
      this.updateButtonVisibility(currentData);
      
      this.initialized = true;
      console.log('FrontendBuildPluginActionButton: Initialized successfully');
    } catch (error) {
      console.error('FrontendBuildPluginActionButton: Initialization failed:', error);
    }
  }

  /**
   * Set up event listeners for the action button
   */
  private setupEventListeners(): void {
    const actionButton = document.getElementById('action-button-build-plugin');
    
    if (!actionButton) {
      console.warn('FrontendBuildPluginActionButton: Action button not found');
      return;
    }

    actionButton.addEventListener('click', (event) => {
      event.preventDefault();
      this.handleActionButtonClick();
    });

    console.log('FrontendBuildPluginActionButton: Event listener attached to action button');
  }

  /**
   * Handle the action button click
   */
  private handleActionButtonClick(): void {
    try {
      console.log('FrontendBuildPluginActionButton: Action button clicked');
      
      const commentForm = this.findCommentForm();
      if (!commentForm) {
        console.error('FrontendBuildPluginActionButton: WordPress comment form not found');
        return;
      }

      const commentTextarea = this.findCommentTextarea(commentForm);
      if (!commentTextarea) {
        console.error('FrontendBuildPluginActionButton: Comment textarea not found');
        return;
      }

      // Append the action string to the comment textarea
      this.appendActionStringToComment(commentTextarea);

      // Click the submit button
      this.clickSubmitButton(commentForm);

    } catch (error) {
      console.error('FrontendBuildPluginActionButton: Error handling button click:', error);
    }
  }

  /**
   * Find the WordPress comment form
   */
  private findCommentForm(): HTMLFormElement | null {
    // Try multiple common selectors for WordPress comment forms
    const selectors = [
      '#commentform',
      '.comment-form',
      'form[action*="wp-comments-post.php"]',
      'form#respond form',
      '#respond form'
    ];

    for (const selector of selectors) {
      const form = document.querySelector(selector) as HTMLFormElement;
      if (form) {
        console.log(`FrontendBuildPluginActionButton: Found comment form using selector: ${selector}`);
        return form;
      }
    }

    // If no form found with common selectors, look for any form that might be a comment form
    const allForms = document.querySelectorAll('form');
    for (const form of allForms) {
      const formElement = form as HTMLFormElement;
      if (formElement.action && formElement.action.includes('wp-comments-post.php')) {
        console.log('FrontendBuildPluginActionButton: Found comment form by action URL');
        return formElement;
      }
    }

    return null;
  }

  /**
   * Find the comment textarea within the form
   */
  private findCommentTextarea(form: HTMLFormElement): HTMLTextAreaElement | null {
    // Try multiple common selectors for WordPress comment textareas
    const selectors = [
      '#comment',
      'textarea[name="comment"]',
      '.comment-form-comment textarea',
      'textarea'
    ];

    for (const selector of selectors) {
      const textarea = form.querySelector(selector) as HTMLTextAreaElement;
      if (textarea && textarea.tagName.toLowerCase() === 'textarea') {
        console.log(`FrontendBuildPluginActionButton: Found comment textarea using selector: ${selector}`);
        return textarea;
      }
    }

    return null;
  }

  /**
   * Append the action string to the comment textarea
   */
  private appendActionStringToComment(textarea: HTMLTextAreaElement): void {
    const actionString = '[ai-plugin-dev-build-plugin-action-button-pressed]';
    const currentValue = textarea.value;
    
    // Append the action string to existing content
    if (currentValue.trim()) {
      textarea.value = currentValue + ' ' + actionString;
    } else {
      textarea.value = actionString;
    }
    
    console.log('FrontendBuildPluginActionButton: Appended action string to comment textarea');
    
    // Trigger input event to notify any listeners
    const inputEvent = new Event('input', { bubbles: true });
    textarea.dispatchEvent(inputEvent);
  }

  /**
   * Click the submit button of the comment form
   */
  private clickSubmitButton(form: HTMLFormElement): void {
    try {
      // Try to find the submit button using common selectors
      const submitSelectors = [
        'input[type="submit"]',
        'button[type="submit"]',
        '#submit',
        '.submit',
        'input[name="submit"]'
      ];

      let submitButton: HTMLElement | null = null;

      for (const selector of submitSelectors) {
        const button = form.querySelector(selector) as HTMLElement;
        if (button) {
          submitButton = button;
          console.log(`FrontendBuildPluginActionButton: Found submit button using selector: ${selector}`);
          break;
        }
      }

      if (submitButton) {
        // Click the submit button
        submitButton.click();
        console.log('FrontendBuildPluginActionButton: Submit button clicked successfully');
      } else {
        // Fallback to form.submit() if no submit button found
        console.warn('FrontendBuildPluginActionButton: No submit button found, using form.submit()');
        form.submit();
      }
      
    } catch (error) {
      console.error('FrontendBuildPluginActionButton: Error clicking submit button:', error);
    }
  }

  /**
   * Extract post ID from the current page
   */
  private extractPostIdFromPage(): string | null {
    // Try to get post ID from WordPress body classes
    const bodyClasses = document.body.className;
    const postIdMatch = bodyClasses.match(/postid-(\d+)/);
    if (postIdMatch) {
      return postIdMatch[1];
    }

    // Try to get from URL if it's a single post page
    const urlMatch = window.location.pathname.match(/\/(\d+)\/?$/);
    if (urlMatch) {
      return urlMatch[1];
    }

    return null;
  }

  /**
   * Handle data updates from DataService (implements DataSubscriber)
   */
  public onDataUpdate(data: PluginData): void {
    this.updateButtonVisibility(data);
  }

  /**
   * Update button visibility based on plugin data
   */
  private updateButtonVisibility(data: PluginData): void {
    const shouldShow = this.shouldShowButton(data);
    
    if (shouldShow !== this.lastButtonState) {
      if (shouldShow) {
        this.showButton();
        this.loadingAnimation.hide();
      } else {
        this.hideButton();
        // Show loading animation when button is hidden and building
        if (data.agentStatus === 'building') {
          this.loadingAnimation.show(data.agentStatus);
        } else if (data.agentStatus === 'ready') {
          this.loadingAnimation.show(data.agentStatus);
        } else {
          this.loadingAnimation.hide();
        }
      }
      this.lastButtonState = shouldShow;
    } else if (!shouldShow) {
      // Update loading animation state even if button visibility hasn't changed
      this.loadingAnimation.updateState(data.agentStatus);
    }
  }

  /**
   * Determine if button should be shown based on plugin data
   */
  private shouldShowButton(data: PluginData): boolean {
    // Button should be visible when actionEnabled is true
    return data.actionEnabled;
  }

  /**
   * Show the button
   */
  private showButton(): void {
    console.log("showButton called");
    const actionButton = document.getElementById('action-button-build-plugin');
    if (actionButton) {
      // Use jQuery to show the button (removes display: none)
      jQuery("#action-button-build-plugin").show();
      // Remove any disabled attribute
      actionButton.removeAttribute('disabled');
      console.log('FrontendBuildPluginActionButton: Button shown');
    } else {
      console.warn('FrontendBuildPluginActionButton: Button element not found when trying to show');
    }
  }

  /**
   * Hide the button
   */
  private hideButton(): void {
    const actionButton = document.getElementById('action-button-build-plugin');
    if (actionButton) {
      // Use jQuery to hide the button (sets display: none)
      jQuery("#action-button-build-plugin").hide();
      console.log('FrontendBuildPluginActionButton: Button hidden');
    } else {
      console.warn('FrontendBuildPluginActionButton: Button element not found when trying to hide');
    }
  }

  /**
   * Check if the action button handler is initialized
   */
  public isInitialized(): boolean {
    return this.initialized;
  }

  /**
   * Destroy the action button handler and clean up resources
   */
  public destroy(): void {
    if (!this.initialized) {
      return;
    }

    // Unsubscribe from data service
    this.dataService.unsubscribe(this);

    // Destroy loading animation
    this.loadingAnimation.destroy();

    // Remove event listeners
    const actionButton = document.getElementById('action-button-build-plugin');
    if (actionButton) {
      // Clone and replace to remove all event listeners
      const newButton = actionButton.cloneNode(true);
      actionButton.parentNode?.replaceChild(newButton, actionButton);
    }

    this.initialized = false;
    console.log('FrontendBuildPluginActionButton: Destroyed');
  }
}