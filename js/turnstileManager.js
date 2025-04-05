/**
 * turnstileManager.js
 * 
 * Unified manager for Cloudflare Turnstile widgets across the application.
 * Handles script loading, site key fetching, rendering, and cleanup.
 */

const turnstileManager = (() => {
    let siteKey = null;
    let isScriptLoadingOrLoaded = false; // Combined flag to prevent multiple load attempts
    let scriptLoadPromise = null;
    let siteKeyPromise = null;
    const widgetMap = new Map(); // Stores info about rendered widgets { id: { containerId: string, options: object } }
    const POLLING_INTERVAL = 100; // ms
    const POLLING_TIMEOUT = 10000; // ms (10 seconds)

    // Function to load the Turnstile API script exactly once using polling
    function loadScript() {
        if (isScriptLoadingOrLoaded && scriptLoadPromise) {
            console.log('[TurnstileManager] Script load already initiated or completed.');
            return scriptLoadPromise; // Return existing promise
        }

        console.log('[TurnstileManager] Initiating script load sequence.');
        isScriptLoadingOrLoaded = true; // Set flag immediately
        
        // Create a new promise for this load attempt
        scriptLoadPromise = new Promise((resolve, reject) => {
            // Check if Turnstile object already exists (e.g., loaded by another means?)
            if (typeof window.turnstile === 'object' && window.turnstile) {
                console.log('[TurnstileManager] window.turnstile already exists.');
                resolve();
                return;
            }

            const script = document.createElement('script');
            // Load script WITHOUT the onload callback parameter
            script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js'; 
            script.async = true;
            script.defer = true;
            script.onerror = () => {
                console.error('[TurnstileManager] Failed to load Turnstile script via <script> tag.');
                isScriptLoadingOrLoaded = false; // Reset flag on error
                reject(new Error('Failed to load Turnstile script'));
            };
            
            // Append the script
            document.head.appendChild(script);
            console.log('[TurnstileManager] Turnstile script appended to head.');

            // Start polling to check when window.turnstile becomes available
            let elapsedTime = 0;
            const pollInterval = setInterval(() => {
                if (typeof window.turnstile === 'object' && window.turnstile) {
                    clearInterval(pollInterval);
                    console.log('[TurnstileManager] Polling successful: window.turnstile found.');
                    resolve();
                } else {
                    elapsedTime += POLLING_INTERVAL;
                    if (elapsedTime >= POLLING_TIMEOUT) {
                        clearInterval(pollInterval);
                        console.error('[TurnstileManager] Polling timed out: window.turnstile did not appear within', POLLING_TIMEOUT, 'ms.');
                        isScriptLoadingOrLoaded = false; // Reset flag on timeout
                        reject(new Error('Turnstile script loaded but API object did not initialize.'));
                    }
                }
            }, POLLING_INTERVAL);
        });

        return scriptLoadPromise;
    }

    // Function to fetch the site key exactly once
    async function fetchSiteKey() {
        if (siteKey) {
            return siteKey;
        }
        if (siteKeyPromise) {
            return siteKeyPromise;
        }

        console.log('[TurnstileManager] Fetching site key...');
        siteKeyPromise = new Promise(async (resolve, reject) => {
            try {
                const response = await fetch('get_config.php');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const config = await response.json();
                if (config && config.turnstile_sitekey) {
                    siteKey = config.turnstile_sitekey;
                    console.log('[TurnstileManager] Site Key fetched successfully:', siteKey);
                    resolve(siteKey);
                } else {
                    console.error('[TurnstileManager] Failed to retrieve Turnstile site key from config.');
                    // Use fallback ONLY for critical testing/dev, remove for production
                    siteKey = '0x4AAAAAABD07zmMDmgwgTsL'; 
                    console.warn('[TurnstileManager] USING FALLBACK SITE KEY - REMOVE FOR PRODUCTION');
                    resolve(siteKey); // Resolve with fallback for now
                    // reject(new Error('Failed to retrieve Turnstile site key'));
                }
            } catch (error) {
                console.error('[TurnstileManager] Error fetching Turnstile site key:', error);
                 // Use fallback ONLY for critical testing/dev, remove for production
                 siteKey = '0x4AAAAAABD07zmMDmgwgTsL'; 
                 console.warn('[TurnstileManager] USING FALLBACK SITE KEY - REMOVE FOR PRODUCTION');
                 resolve(siteKey); // Resolve with fallback for now
                // reject(error);
            } finally {
                // siteKeyPromise = null; // Allow refetch on failure? Maybe not.
            }
        });
        return siteKeyPromise;
    }

    /**
     * Renders a Turnstile widget in the specified container.
     * 
     * @param {string} containerSelector - CSS selector for the container div (e.g., '#my-widget').
     * @param {object} options - Options for turnstile.render(), including callbacks.
     * @param {number} [renderDelay=100] - Optional delay in ms before rendering.
     * @returns {Promise<string|null>} A promise that resolves with the widget ID, or null on error.
     */
    async function renderWidget(containerSelector, options = {}, renderDelay = 100) {
        console.log(`[TurnstileManager] Request to render widget in: ${containerSelector}`);
        
        try {
            // Ensure script is loaded (via polling) and site key is fetched concurrently
            const [_, currentSiteKey] = await Promise.all([
                loadScript(), // This now uses polling
                fetchSiteKey()
            ]);

            // Check results after promises resolve
            if (!currentSiteKey) {
                 console.error('[TurnstileManager] Cannot render widget: Site key is missing after fetch attempt.');
                 return null;
            }
            if (typeof window.turnstile === 'undefined') {
                 console.error('[TurnstileManager] Cannot render widget: window.turnstile is still undefined after script load attempt.');
                 return null;
            }

            // Add delay AFTER script/key are ready
            await new Promise(resolve => setTimeout(resolve, renderDelay)); 

            const containerElement = document.querySelector(containerSelector);
            if (!containerElement) {
                console.error(`[TurnstileManager] Container element ${containerSelector} not found in DOM after delay.`);
                return null;
            }
            containerElement.innerHTML = ''; 

            console.log(`[TurnstileManager] Rendering widget in ${containerSelector} with sitekey ${currentSiteKey}`);
            const renderOptions = { ...options, sitekey: currentSiteKey };
            const widgetId = window.turnstile.render(containerSelector, renderOptions);
            
            if (widgetId) {
                console.log(`[TurnstileManager] Widget rendered successfully. ID: ${widgetId}`);
                widgetMap.set(widgetId, { containerId: containerSelector, options });
                return widgetId;
            } else {
                console.error(`[TurnstileManager] window.turnstile.render did not return a widget ID for ${containerSelector}.`);
                return null;
            }

        } catch (error) {
            console.error(`[TurnstileManager] Error during renderWidget sequence for ${containerSelector}:`, error);
            // Ensure the flag allows reloading if the whole sequence failed
            // This depends on where the error occurred. If script load failed, flag is already false.
            // If key fetch failed but resolved with fallback, it continues.
            // If render failed, script is loaded, so flag should remain true.
            return null;
        }
    }

    /**
     * Removes a Turnstile widget explicitly.
     * 
     * @param {string} widgetId - The ID of the widget to remove.
     */
    function removeWidget(widgetId) {
        if (!widgetId) return;

        if (window.turnstile) {
            try {
                console.log(`[TurnstileManager] Removing widget ID: ${widgetId}`);
                window.turnstile.remove(widgetId);
            } catch (e) {
                console.warn(`[TurnstileManager] Error removing widget ID ${widgetId}:`, e);
            }
        } else {
            console.log(`[TurnstileManager] window.turnstile not available, cannot remove widget ID: ${widgetId}`);
        }
        widgetMap.delete(widgetId);
        
        // Also attempt to clear the container just in case remove fails silently
        // const widgetInfo = widgetMap.get(widgetId);
        // if (widgetInfo && widgetInfo.containerId) {
        //     const container = document.querySelector(widgetInfo.containerId);
        //     if (container) container.innerHTML = '';
        // }
    }
    
     /**
     * Resets a Turnstile widget to its initial state.
     * 
     * @param {string} widgetId - The ID of the widget to reset.
     */
    function resetWidget(widgetId) {
        if (!widgetId) return;
        if (window.turnstile) {
             try {
                console.log(`[TurnstileManager] Resetting widget ID: ${widgetId}`);
                window.turnstile.reset(widgetId);
            } catch (e) {
                console.warn(`[TurnstileManager] Error resetting widget ID ${widgetId}:`, e);
            }
        } else {
             console.log(`[TurnstileManager] window.turnstile not available, cannot reset widget ID: ${widgetId}`);
        }
    }

    /**
     * Gets the response token from a rendered widget.
     * 
     * @param {string} widgetId - The ID of the widget.
     * @returns {string | null} The response token or null if unavailable.
     */
    function getResponse(widgetId) {
        if (!widgetId) return null;

        if (window.turnstile) {
            try {
                return window.turnstile.getResponse(widgetId);
            } catch (e) {
                console.error(`[TurnstileManager] Error getting response for widget ID ${widgetId}:`, e);
                return null;
            }
        } else {
            console.log(`[TurnstileManager] window.turnstile not available, cannot get response for widget ID: ${widgetId}`);
             // Fallback: try reading hidden input (less reliable)
            // const widgetInfo = widgetMap.get(widgetId);
            // if (widgetInfo && widgetInfo.containerId) {
            //     const input = document.querySelector(`${widgetInfo.containerId} [name="cf-turnstile-response"]`);
            //     return input ? input.value : null;
            // }
            return null;
        }
    }

    // Expose the necessary functions 
    // No need to expose the callback anymore
    return {
        // loadScript, // Internal use mostly
        fetchSiteKey, 
        renderWidget,
        removeWidget,
        resetWidget,
        getResponse 
    };
})();

// Assign to window immediately (IIFE executes)
window.turnstileManager = turnstileManager; 