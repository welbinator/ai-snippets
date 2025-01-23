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

// Handle the "Edit Snippet" button click
document.querySelectorAll('.edit-snippet').forEach((button) => {
    button.addEventListener('click', function () {
        const snippetEditor = document.getElementById('snippet-editor');
        snippetEditor.style.display = 'block';

        // Populate form with snippet data
        document.getElementById('snippet-id').value = this.dataset.id;
        document.getElementById('snippet-name').value = this.dataset.name;
        document.getElementById('snippet-type').value = this.dataset.type;
        document.getElementById('snippet-description').value = this.dataset.description;

        // Decode the HTML entities for the code field
        const textarea = document.getElementById('snippet-code');
        textarea.value = decodeHtmlEntities(this.dataset.code);
    });
});

// Helper function to decode HTML entities
function decodeHtmlEntities(encodedString) {
    const textArea = document.createElement('textarea');
    textArea.innerHTML = encodedString;
    return textArea.value;
}



// Handle the "Delete Snippet" button click
document.querySelectorAll('.delete-snippet').forEach((button) => {
    button.addEventListener('click', function () {
        const id = this.dataset.id;

        if (!confirm('Are you sure you want to delete this snippet?')) {
            return;
        }

        fetch(ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'delete_snippet',
                security: aiSnippetsData.nonce,
                id: id,
            }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the deleted snippet's row from the table
                    this.closest('tr').remove();
                } else {
                    alert('Failed to delete snippet: ' + (data.message || 'Unknown error.'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the snippet.');
            });
    });
});


// Handle the "Activate/Deactivate Snippet" button click
document.querySelectorAll('.toggle-snippet').forEach((button) => {
    button.addEventListener('click', function () {
        const id = this.dataset.id;
        const active = this.dataset.active === '1' ? '0' : '1';

        fetch(ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'toggle_snippet_status',
                security: aiSnippetsData.nonce,
                id: id,
                active: active,
            }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the button's state and text
                    this.dataset.active = active;
                    this.textContent = active === '1' ? 'Deactivate' : 'Activate';

                    // Update the status column in the table
                    const statusCell = document.querySelector(`#snippet-status-${id}`);
                    if (statusCell) {
                        statusCell.textContent = active === '1' ? 'Active' : 'Inactive';
                    }
                } else {
                    // Show error message if the update fails
                    alert('Failed to update snippet status: ' + (data.message || 'Unknown error.'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating snippet status.');
            });
    });
});







