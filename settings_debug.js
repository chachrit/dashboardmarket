// Enhanced JavaScript debug for settings page
// Override the original saveSettings function with detailed logging
window.originalSaveSettings = saveSettings;

async function saveSettings(platform) {
    console.log(`=== SAVE SETTINGS DEBUG: ${platform} ===`);
    console.log('Starting save process...');
    
    const settings = getSettingsFromForm(platform);
    console.log('Form settings:', settings);
    
    try {
        console.log('Making API call...');
        const url = `api.php?action=save_settings&platform=${platform}`;
        console.log('API URL:', url);
        console.log('Request body:', JSON.stringify(settings));
        
        const response = await fetch(url, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(settings)
        });
        
        console.log('Response status:', response.status);
        console.log('Response ok:', response.ok);
        console.log('Response headers:', Object.fromEntries(response.headers.entries()));
        
        const responseText = await response.text();
        console.log('Raw response text:', responseText);
        
        let j;
        try {
            j = JSON.parse(responseText);
            console.log('Parsed response:', j);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.log('Response was not valid JSON:', responseText);
            throw new Error('Server returned invalid JSON: ' + responseText.substring(0, 100));
        }
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        if (j.success) {
            console.log('✓ API call successful');
            showMessage(`บันทึก ${platform} เรียบร้อย`, 'success');
            
            // Verify by reading back the settings
            setTimeout(async () => {
                try {
                    console.log('Verifying saved settings...');
                    const verifyResponse = await fetch(`api.php?action=get_settings&platform=${platform}`);
                    const verifyData = await verifyResponse.json();
                    console.log('Verification result:', verifyData);
                    
                    if (verifyData.success) {
                        console.log('Saved settings verification:', verifyData.data);
                        
                        // Compare what we sent vs what was saved
                        let allMatch = true;
                        for (const [key, sentValue] of Object.entries(settings)) {
                            const savedValue = verifyData.data[key];
                            if (savedValue !== String(sentValue)) {
                                console.warn(`Mismatch for ${key}: sent='${sentValue}', saved='${savedValue}'`);
                                allMatch = false;
                            }
                        }
                        
                        if (allMatch) {
                            console.log('✓ All settings verified successfully');
                        } else {
                            console.error('✗ Some settings did not save correctly');
                            showMessage(`เกิดข้อผิดพลาด: บางข้อมูลไม่ถูกบันทึก กรุณาตรวจสอบ`, 'error');
                        }
                    }
                } catch (verifyError) {
                    console.error('Verification failed:', verifyError);
                }
            }, 1000);
            
        } else {
            console.error('✗ API call failed:', j.error || j.message);
            showMessage(`บันทึก ${platform} ล้มเหลว: ${j.error || j.message}`, 'error');
        }
        return j;
        
    } catch (err) {
        console.error('=== SAVE SETTINGS ERROR ===');
        console.error('Error type:', err.constructor.name);
        console.error('Error message:', err.message);
        console.error('Error stack:', err.stack);
        
        showMessage(`บันทึก ${platform} ล้มเหลว: ${err.message}`, 'error');
        throw err;
    }
}

// Add network monitoring
const originalFetch = window.fetch;
window.fetch = function(...args) {
    console.log('FETCH REQUEST:', args[0], args[1]);
    return originalFetch.apply(this, arguments).then(response => {
        console.log('FETCH RESPONSE:', response.status, response.statusText);
        return response;
    }).catch(error => {
        console.error('FETCH ERROR:', error);
        throw error;
    });
};

console.log('Debug mode enabled for settings.php');
console.log('All API calls will be logged to browser console');
console.log('Check Network tab in DevTools for request details');
