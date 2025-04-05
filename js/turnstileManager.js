/**
 * turnstileManager.js
 * 
 * Unified manager for Cloudflare Turnstile widgets across the application.
 * Handles script loading, site key fetching, rendering, and cleanup.
 */

const turnstileManager = (() => {
    let siteKey = null;
    let scriptLoading = false;
    let scriptLoaded = false;
    let scriptLoadPromise = null;
    let siteKeyPromise = null;
    const widgetMap = new Map(); // Stores info about rendered widgets { id: { containerId: string, options: object } }

    // Callback function attached to the script URL (?onload=...)
    function handleScriptLoaded() {
        console.log('[TurnstileManager] Script loaded successfully.');
        scriptLoaded = true;
        scriptLoading = false;
        // Resolve the promise for listeners waiting for the script
        if (scriptLoadPromise && typeof scriptLoadPromise.resolve === 'function') {
            scriptLoadPromise.resolve();
        }
    }

    // Function to load the Turnstile API script exactly once
    function loadScript() {
        if (scriptLoaded) {
            console.log('[TurnstileManager] Script already loaded.');
            return Promise.resolve();
        }
        if (scriptLoading && scriptLoadPromise) {
            console.log('[TurnstileManager] Script is currently loading.');
            return scriptLoadPromise.promise;
        }

        console.log('[TurnstileManager] Initiating script load.');
        scriptLoading = true;
        
        // Create a promise that handleScriptLoaded will resolve
        let resolveFn;
        const promise = new Promise(resolve => {
            resolveFn = resolve;
        });
        scriptLoadPromise = { promise, resolve: resolveFn };

        const script = document.createElement('script');
        // IMPORTANT: Point the onload callback to our manager's function
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=turnstileManager.scriptReadyCallback';
        script.async = true;
        script.defer = true;
        script.onerror = () => {
            console.error('[TurnstileManager] Failed to load Turnstile script.');
            scriptLoading = false;
            // Reject the promise if the script fails to load
            // scriptLoadPromise.reject(new Error('Failed to load Turnstile script')); 
            // Or maybe resolve, but indicate failure? For now, just log.
        };
        document.head.appendChild(script);

        return scriptLoadPromise.promise;
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
            // Ensure script is loaded and site key is fetched concurrently
            const [_, currentSiteKey] = await Promise.all([
                loadScript(),
                fetchSiteKey()
            ]);

            if (!currentSiteKey) {
                 console.error('[TurnstileManager] Cannot render widget: Site key is missing.');
                 return null;
            }
            
            if (!window.turnstile) {
                console.error('[TurnstileManager] Cannot render widget: window.turnstile object not found.');
                return null;
            }

            // Wait for a short delay to allow container DOM to stabilize
            await new Promise(resolve => setTimeout(resolve, renderDelay)); 

            const containerElement = document.querySelector(containerSelector);
            if (!containerElement) {
                console.error(`[TurnstileManager] Container element ${containerSelector} not found in DOM.`);
                return null;
            }

            // Clear previous content/widget if any
            containerElement.innerHTML = ''; 

            console.log(`[TurnstileManager] Rendering widget in ${containerSelector} with sitekey ${currentSiteKey}`);

            const renderOptions = {
                ...options,
                sitekey: currentSiteKey,
            };

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
            console.error(`[TurnstileManager] Error rendering widget in ${containerSelector}:`, error);
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

    // Expose the necessary functions and the script loaded callback
    return {
        loadScript, // Should generally not be needed externally
        fetchSiteKey, // Can be called early if needed
        renderWidget,
        removeWidget,
        resetWidget,
        getResponse,
        // IMPORTANT: This function needs to be globally accessible for the onload callback
        scriptReadyCallback: handleScriptLoaded 
    };
})();

// IMPORTANT: Make the callback globally accessible
window.turnstileManager = turnstileManager; 