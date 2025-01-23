// Handle "Add New Snippet" button click
document.getElementById('add-new-snippet').addEventListener('click', function () {
    const snippetEditor = document.getElementById('snippet-editor');
    if (snippetEditor) {
        snippetEditor.style.display = 'block';
    }
});

// Handle "Use AI to Generate Snippet" button click
document.getElementById('generate-snippet').addEventListener('click', function () {
    const aiGenerator = document.getElementById('ai-generator');
    if (aiGenerator) {
        aiGenerator.style.display = 'block';
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
            
            return response.json();
        })
        .then(data => {
            console.log('Full Response:', data); // Log the full response to confirm its structure
            
            // Check if the response is successful and contains a snippet
            if (data.success && data.data && data.data.snippet) {
                console.log('Generated Snippet:', data.data.snippet);
        
                // Display the snippet in the textarea
                document.getElementById('snippet-code').value = data.data.snippet;
            } else {
                console.error('Unexpected Response Structure:', data); // Log unexpected structures
                alert('Failed to generate snippet. ' + (data.message || 'Unexpected response structure.'));
            }
        })
        
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while generating the snippet.');
        });
});



// Handle "Save Snippet" button click
document.getElementById('save-snippet').addEventListener('click', function () {
    const name = document.getElementById('snippet-name').value;
    const type = document.getElementById('snippet-type').value;
    const code = document.getElementById('snippet-code').value;

    if (!name || !code) {
        alert('Please provide a name and snippet code.');
        return;
    }

    fetch(aiSnippetsData.ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'save_snippet',
            security: aiSnippetsData.nonce,
            name: name,
            type: type,
            code: code,
        }),
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Snippet saved successfully!');
                location.reload(); // Reload the page to refresh the list of snippets
            } else {
                alert('Failed to save snippet. ' + (data.message || ''));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while saving the snippet.');
        });
});
