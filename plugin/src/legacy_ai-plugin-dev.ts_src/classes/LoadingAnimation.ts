/**
 * LoadingAnimation - Animated UI states for better user experience
 *
 * This class provides loading animations and visual feedback when the build
 * button is hidden or during various build states.
 */

import { AgentStatus } from '../types/plugin';

export class LoadingAnimation {
  private container: HTMLElement | null = null;
  private animationFrame: number | null = null;
  private isVisible: boolean = false;
  private currentMessage: string = '';
  private currentAnimation: string = '';

  constructor() {
    this.createContainer();
  }

  /**
   * Show loading animation with specified state
   */
  public show(status: AgentStatus = 'building'): void {
    if (!this.container) {
      this.createContainer();
    }

    if (!this.container) {
      console.error('LoadingAnimation: Failed to create container');
      return;
    }

    const { message, animation } = this.getAnimationConfig(status);
    this.currentMessage = message;
    this.currentAnimation = animation;

    this.updateContent();
    this.container.style.display = 'block';
    this.container.classList.add('fade-in');
    
    this.isVisible = true;
    this.startAnimation();

    console.log('LoadingAnimation: Shown with status:', status);
  }

  /**
   * Hide loading animation
   */
  public hide(): void {
    if (!this.container || !this.isVisible) {
      return;
    }

    this.container.classList.remove('fade-in');
    this.container.classList.add('fade-out');

    setTimeout(() => {
      if (this.container) {
        this.container.style.display = 'none';
        this.container.classList.remove('fade-out');
      }
    }, 300);

    this.isVisible = false;
    this.stopAnimation();

    console.log('LoadingAnimation: Hidden');
  }

  /**
   * Update loading message dynamically
   */
  public updateMessage(message: string): void {
    this.currentMessage = message;
    if (this.isVisible) {
      this.updateContent();
    }
  }

  /**
   * Update animation state
   */
  public updateState(status: AgentStatus): void {
    if (!this.isVisible) {
      return;
    }

    const { message, animation } = this.getAnimationConfig(status);
    this.currentMessage = message;
    this.currentAnimation = animation;
    
    this.updateContent();
  }

  /**
   * Create the animation container
   */
  private createContainer(): void {
    // Check if container already exists
    const existing = document.getElementById('ai-plugin-loading-animation');
    if (existing) {
      this.container = existing;
      return;
    }

    this.container = document.createElement('div');
    this.container.id = 'ai-plugin-loading-animation';
    this.container.className = 'loading-container';
    
    // Add CSS styles
    this.container.style.cssText = `
      display: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: rgba(255, 255, 255, 0.95);
      border: 2px solid #0073aa;
      border-radius: 8px;
      padding: 20px;
      text-align: center;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      z-index: 10000;
      min-width: 200px;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    `;

    document.body.appendChild(this.container);
    this.addCSS();
  }

  /**
   * Add CSS animations to the page
   */
  private addCSS(): void {
    // Check if styles already exist
    if (document.getElementById('ai-plugin-loading-styles')) {
      return;
    }

    const style = document.createElement('style');
    style.id = 'ai-plugin-loading-styles';
    style.textContent = `
      .loading-container {
        transition: opacity 0.3s ease-in-out;
      }

      .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #0073aa;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-right: 10px;
      }

      .loading-dots {
        display: inline-block;
        animation: pulse 1.5s ease-in-out infinite;
      }

      .loading-progress {
        display: inline-block;
        width: 30px;
        height: 4px;
        background: #f3f3f3;
        border-radius: 2px;
        overflow: hidden;
        margin-right: 10px;
      }

      .loading-progress::after {
        content: '';
        display: block;
        width: 100%;
        height: 100%;
        background: #0073aa;
        border-radius: 2px;
        animation: progress 2s ease-in-out infinite;
      }

      .fade-in {
        animation: fadeIn 0.5s ease-in-out;
      }

      .fade-out {
        animation: fadeOut 0.3s ease-in-out;
      }

      @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }

      @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
      }

      @keyframes progress {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
      }

      @keyframes fadeIn {
        from { opacity: 0; transform: translate(-50%, -50%) scale(0.9); }
        to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
      }

      @keyframes fadeOut {
        from { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        to { opacity: 0; transform: translate(-50%, -50%) scale(0.9); }
      }
    `;

    document.head.appendChild(style);
  }

  /**
   * Get animation configuration for status
   */
  private getAnimationConfig(status: AgentStatus): { message: string; animation: string } {
    switch (status) {
      case 'building':
        return {
          message: 'Building plugin...',
          animation: 'spinner'
        };
      case 'ready':
        return {
          message: 'Processing...',
          animation: 'dots'
        };
      case 'idle':
        return {
          message: 'Please wait...',
          animation: 'dots'
        };
      case 'error':
        return {
          message: 'Error occurred',
          animation: 'none'
        };
      case 'done':
        return {
          message: 'Completed!',
          animation: 'none'
        };
      default:
        return {
          message: 'Loading...',
          animation: 'progress'
        };
    }
  }

  /**
   * Update container content
   */
  private updateContent(): void {
    if (!this.container) {
      return;
    }

    let animationElement = '';
    
    switch (this.currentAnimation) {
      case 'spinner':
        animationElement = '<div class="loading-spinner"></div>';
        break;
      case 'dots':
        animationElement = '<div class="loading-dots">●●●</div>';
        break;
      case 'progress':
        animationElement = '<div class="loading-progress"></div>';
        break;
      default:
        animationElement = '';
    }

    this.container.innerHTML = `
      <div style="display: flex; align-items: center; justify-content: center;">
        ${animationElement}
        <span style="color: #333; font-weight: 500;">${this.currentMessage}</span>
      </div>
    `;
  }

  /**
   * Start animation loop
   */
  private startAnimation(): void {
    if (this.currentAnimation === 'dots') {
      this.animateDots();
    }
  }

  /**
   * Stop animation loop
   */
  private stopAnimation(): void {
    if (this.animationFrame) {
      cancelAnimationFrame(this.animationFrame);
      this.animationFrame = null;
    }
  }

  /**
   * Animate dots for loading states
   */
  private animateDots(): void {
    const dotsElement = this.container?.querySelector('.loading-dots');
    if (!dotsElement) {
      return;
    }

    let dotCount = 1;
    const maxDots = 3;

    const animate = () => {
      if (!this.isVisible || this.currentAnimation !== 'dots') {
        return;
      }

      dotsElement.textContent = '●'.repeat(dotCount) + '○'.repeat(maxDots - dotCount);
      dotCount = dotCount >= maxDots ? 1 : dotCount + 1;

      this.animationFrame = requestAnimationFrame(() => {
        setTimeout(animate, 500);
      });
    };

    animate();
  }

  /**
   * Check if animation is currently visible
   */
  public isShowing(): boolean {
    return this.isVisible;
  }

  /**
   * Destroy the animation and clean up
   */
  public destroy(): void {
    this.hide();
    this.stopAnimation();

    if (this.container && this.container.parentNode) {
      this.container.parentNode.removeChild(this.container);
    }

    // Remove styles
    const styles = document.getElementById('ai-plugin-loading-styles');
    if (styles && styles.parentNode) {
      styles.parentNode.removeChild(styles);
    }

    this.container = null;
    console.log('LoadingAnimation: Destroyed');
  }
}