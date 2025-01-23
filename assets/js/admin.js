// Handle "Add New Snippet" button click
document.getElementById('add-new-snippet').addEventListener('click', function () {
    const snippetEditor = document.getElementById('snippet-editor');
    if (snippetEditor) {
        snippetEditor.style.display = 'block';
    }
});


// Handle "Submit AI Prompt" button click
document.getElementById('submit-ai-prompt').addEventListener('click', function () {
    const prompt = document.getElementById('ai-prompt').value;
    const snippetType = document.getElementById('snippet-type').value;

    if (!prompt) {
        alert('Please enter a description for the snippet.');
        return;
    }

    // Make the AJAX request
    fetch(ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'generate_snippet',
            security: aiSnippetsData.nonce,
            prompt: prompt,
            type: snippetType,
        }),
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json(); // Parse the response as JSON
        })
        .then(data => {
            console.log('Full Response:', data); // Log the full response for debugging
        
            // Check if the response is successful and contains the snippet
            if (data.success && data.data && data.data.snippet) {
                console.log('Generated Snippet:', data.data.snippet);
        
                // Display the snippet in the textarea
                document.getElementById('snippet-code').value = data.data.snippet;
            } else if (data.success && data.snippet) {
                // Handle older format if `data.snippet` exists
                console.log('Generated Snippet:', data.snippet);
                document.getElementById('snippet-code').value = data.snippet;
            } else {
                // Trigger an alert only if the response indicates failure
                if (!data.success) {
                    alert('Failed to generate snippet. ' + (data.message || 'Unexpected response structure.'));
                }
                console.error('Unexpected Response Structure:', data);
            }
        })
        
        .catch(error => {
            // Log and alert for any fetch-related errors
            console.error('Error:', error);
            alert('An error occurred while generating the snippet.');
        });
});




// Handle "Save Snippet" button click
document.getElementById('save-snippet').addEventListener('click', function () {
    const id = document.getElementById('snippet-id').value || '';
    const name = document.getElementById('snippet-name').value || '';
    const type = document.getElementById('snippet-type').value || '';
    const description = document.getElementById('snippet-description')?.value || ''; // Optional field
    const code = document.getElementById('snippet-code').value || '';

    console.log('Saving Snippet Data:', { id, name, type, description, code }); // Log data for debugging

    if (!name || !code) {
        alert('Please provide a name and snippet code.');
        return;
    }

    fetch(ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'save_snippet',
            security: aiSnippetsData.nonce,
            id: id,
            name: name,
            type: type,
            description: description,
            code: code,
        }),
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Snippet saved successfully!');
                location.reload();
            } else {
                alert('Failed to save snippet: ' + (data.message || 'Unknown error.'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while saving the snippet.');
        });
});




