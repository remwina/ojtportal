async function checkAdminAuth() {
    // Only check admin auth if we're on an admin page
    const isAdminPage = window.location.pathname.includes('/Admin/');
    if (!isAdminPage) {
        return true;
    }

    try {
        const data = await Utils.Api.makeApiCall('../Backend/Core/MAIN.php?action=checkAdmin');
        
        if (!data.isAdmin) {
            await Utils.Error.handleError(
                new Error('You must be logged in as an administrator to access this page.'),
                'Access Denied!',
                'You must be logged in as an administrator to access this page.'
            );
            window.location.href = '../../Frontend/login.html';
            return false;
        }
        return true;
    } catch (error) {
        await Utils.Error.handleError(
            error,
            'Authentication Error',
            'Please log in again.'
        );
        window.location.href = '../../Frontend/login.html';
        return false;
    }
}

document.addEventListener('DOMContentLoaded', async function() {
    const isAdmin = await checkAdminAuth();
    if (!isAdmin) return;

    // Database operation handler
    async function handleDatabaseOperation(button, statusMessage, consoleOutput, reset = false) {
        try {
            Utils.Form.setLoading(button, true);
            statusMessage.style.display = 'none';
            consoleOutput.style.display = 'none';

            await new Promise(resolve => setTimeout(resolve, 1500));

            const formData = new FormData();
            formData.append('reset', reset);

            const data = await Utils.Api.makeApiCall('../Backend/Core/Config/DataManagement/reset_db.php', {
                method: 'POST',
                body: formData
            });
            
            if (data.success) {
                await Utils.Error.handleSuccess(reset ? 
                    'Database has been reset successfully!' : 
                    'Database has been created successfully!'
                );
            }

            if (data.details) {
                consoleOutput.textContent = JSON.stringify(data.details, null, 2);
                consoleOutput.style.display = 'block';
            }

        } catch (error) {
            await Utils.Error.handleError(error);
        } finally {
            Utils.Form.setLoading(button, false);
        }
    }

    window.handleDatabaseOperation = handleDatabaseOperation;
});