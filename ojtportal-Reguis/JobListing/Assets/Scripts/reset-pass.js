document.addEventListener('DOMContentLoaded', async function() {
  await CSRFManager.init();
  CSRFManager.updateFormToken(document.getElementById('resetPasswordForm'));
  
  // Get token from URL query parameter
  const urlParams = new URLSearchParams(window.location.search);
  const token = urlParams.get('token');
  if (!token) {
      Swal.fire({
          title: 'Error',
          text: 'Invalid or missing reset token',
          icon: 'error',
          confirmButtonText: 'Go to Login',
          allowOutsideClick: false
      }).then(() => {
          window.location.href = './login.html';
      });
      return;
  }
  document.getElementById('token').value = token;

  // Handle form submission
  const form = document.getElementById('resetPasswordForm');
  const submitBtn = document.getElementById('submitBtn');

  form.addEventListener('submit', async function(e) {
      e.preventDefault();
      
      // Validate passwords
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      let hasError = false;

      // Clear previous errors
      document.getElementById('password-error').textContent = '';
      document.getElementById('confirm_password-error').textContent = '';

      // Password validation
      if (!password) {
          document.getElementById('password-error').textContent = 'Password is required';
          hasError = true;
      } else if (password.length < 6) {
          document.getElementById('password-error').textContent = 'Password must be at least 6 characters';
          hasError = true;
      } else if (!/[A-Z]/.test(password)) {
          document.getElementById('password-error').textContent = 'Password must contain at least one uppercase letter';
          hasError = true;
      } else if (!/[a-z]/.test(password)) {
          document.getElementById('password-error').textContent = 'Password must contain at least one lowercase letter';
          hasError = true;
      } else if (!/[0-9]/.test(password)) {
          document.getElementById('password-error').textContent = 'Password must contain at least one number';
          hasError = true;
      } else if (!/[!@#$%^&*]/.test(password)) {
          document.getElementById('password-error').textContent = 'Password must contain at least one special character (!@#$%^&*)';
          hasError = true;
      }

      // Confirm password validation
      if (password !== confirmPassword) {
          document.getElementById('confirm_password-error').textContent = 'Passwords do not match';
          hasError = true;
      }

      if (hasError) {
          return;
      }

      submitBtn.disabled = true;
      let success = false;
      
      try {
          // Ensure CSRF token is up to date
          await CSRFManager.init();
          CSRFManager.updateFormToken(form);
          
          const formData = new FormData(form);
          const response = await fetch('../Backend/Core/MAIN.php', {
              method: 'POST',
              body: formData
          });
          
          if (!response.ok) {
              throw new Error('Network response was not ok');
          }

          const data = await response.json();
          if (data.success) {
              success = true;
              form.reset();
              window.location.href = './login.html';
          } else {
              throw new Error(data.message || 'Failed to reset password');
          }
      } catch (error) {
          console.error('Error:', error);
          if (document.body) {  // Check if we haven't redirected yet
              await Swal.fire({
                  title: 'Error',
                  text: error.message || 'An error occurred. Please try again.',
                  icon: 'error',
                  confirmButtonText: 'Try Again'
              });
          }
      } finally {
          if (!success) {
              submitBtn.disabled = false;
          }
      }
  });
});