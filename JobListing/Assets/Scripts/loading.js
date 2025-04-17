async function checkAdminAuth() {
    try {
        const response = await fetch('../Backend/Core/MAIN.php?action=checkAdmin');
        const data = await response.json();
        
        if (!data.isAdmin) {
            await swal({
                title: "Access Denied!",
                text: "You must be logged in as an administrator to access this page.",
                icon: "error",
                button: "Return to Login",
                closeOnClickOutside: false,
                closeOnEsc: false
            });
            window.location.href = '../Frontend/login.html';
            return false;
        }
        return true;
    } catch (error) {
        await swal({
            title: "Authentication Error",
            text: "Please log in again.",
            icon: "error",
            button: false,
            closeOnClickOutside: false,
            closeOnEsc: false
        });
        window.location.href = '../Frontend/login.html';
        return false;
    }
}

document.addEventListener('DOMContentLoaded', async function() {
    const isAdmin = await checkAdminAuth();
    if (!isAdmin) return;
    
    async function showMessage(element, message, isError = false) {
        element.textContent = message;
        element.style.display = 'block';
        element.className = 'status-message ' + (isError ? 'error' : 'success');

        await swal({
            title: isError ? "Error!" : "Success!",
            text: message,
            icon: isError ? "error" : "success",
            button: "Continue"
        });
    }

    function showConsoleOutput(element, output) {
        element.textContent = output;
        element.style.display = 'block';
    }

    function setLoading(button, loading) {
        button.disabled = loading;
        if (loading) {
            button.classList.add('loading');
            swal({
                title: "Processing...",
                text: "Please wait while we handle your request.",
                icon: "info",
                buttons: false,
                closeOnClickOutside: false,
                closeOnEsc: false
            });
        } else {
            button.classList.remove('loading');
        }
    }

    async function handleDatabaseOperation(button, statusMessage, consoleOutput, reset = false) {
        try {
            setLoading(button, true);
            statusMessage.style.display = 'none';
            consoleOutput.style.display = 'none';

            await new Promise(resolve => setTimeout(resolve, 1500));

            const formData = new FormData();
            formData.append('reset', reset);
            formData.append('csrf_token', CSRFManager.getToken());

            const response = await fetch('../Backend/Core/Config/DataManagement/reset_db.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                await showMessage(statusMessage, reset ? 
                    'Database has been reset successfully!' : 
                    'Database has been created successfully!');
            } else {
                await showMessage(statusMessage, data.message || 'Operation failed', true);
            }

            if (data.details) {
                showConsoleOutput(consoleOutput, JSON.stringify(data.details, null, 2));
            }

        } catch (error) {
            await showMessage(statusMessage, 'An error occurred: ' + error.message, true);
        } finally {
            setLoading(button, false);
        }
    }

    window.showMessage = showMessage;
    window.showConsoleOutput = showConsoleOutput;
    window.setLoading = setLoading;
    window.handleDatabaseOperation = handleDatabaseOperation;
});