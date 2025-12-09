// Profile Edit Modal JavaScript
document.addEventListener('DOMContentLoaded', function() {
  const editProfileButton = document.getElementById('openProfileEditModal');
  const profileModal = document.getElementById('profileEditModal');
  const profileModalClose = document.getElementById('profileEditModalClose');
  const profileCancelButton = document.getElementById('profileEditCancelButton');
  
  // Open modal when Edit Profile button is clicked
  if (editProfileButton) {
    editProfileButton.addEventListener('click', function(e) {
      e.preventDefault();
      if (profileModal) {
        profileModal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Prevent scrolling
        // Reset to profile info tab when opening
        switchTab('profile-info');
        resetPasswordFields();
      }
    });
  }
  
  // Close modal when close button is clicked
  if (profileModalClose) {
    profileModalClose.addEventListener('click', function() {
      closeProfileModal();
    });
  }
  
  // Close modal when cancel button is clicked
  if (profileCancelButton) {
    profileCancelButton.addEventListener('click', function() {
      closeProfileModal();
    });
  }
  
  // Close modal when clicking outside the modal
  if (profileModal) {
    profileModal.addEventListener('click', function(e) {
      if (e.target === profileModal) {
        closeProfileModal();
      }
    });
  }
  
  // Tab switching functionality
  const tabs = document.querySelectorAll('.profile-edit-tab');
  tabs.forEach(tab => {
    tab.addEventListener('click', function() {
      const tabName = this.getAttribute('data-tab');
      switchTab(tabName);
    });
  });
  
  // Password strength checker
  const newPasswordInput = document.getElementById('newPassword');
  const confirmPasswordInput = document.getElementById('confirmPassword');
  
  if (newPasswordInput) {
    newPasswordInput.addEventListener('input', checkPasswordStrength);
    newPasswordInput.addEventListener('input', checkPasswordMatch);
  }
  
  if (confirmPasswordInput) {
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);
  }
  
  // Handle form submission with AJAX
  const profileEditForm = document.getElementById('profileEditForm');
  if (profileEditForm) {
    profileEditForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      const activeTab = document.querySelector('.profile-edit-tab.active').getAttribute('data-tab');
      
      // Validate based on active tab
      let isValid = false;
      if (activeTab === 'profile-info') {
        isValid = validateProfileInfo();
      } else if (activeTab === 'change-password') {
        isValid = validatePasswordChange();
      }
      
      if (!isValid) return;
      
      // Show loading state
      const saveButton = document.querySelector('.profile-edit-save-button');
      const originalText = saveButton.textContent;
      saveButton.textContent = 'Saving...';
      saveButton.disabled = true;
      
      try {
        const response = await fetch('/profile/edit/ajax', {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        
        const result = await response.json();
        
        if (result.success) {
          if (activeTab === 'profile-info') {
            // Update the profile information on the page
            updateProfileDisplay(result.user);
            showFlashMessage(result.message || 'Profile updated successfully!', 'success');
            
            // Update global user data
            if (window.collectionData && window.collectionData.currentUser) {
              window.collectionData.currentUser.username = result.user.username;
              window.collectionData.currentUser.profileImage = result.user.profileImage;
              if (result.user.bio !== undefined) {
                window.collectionData.currentUser.bio = result.user.bio;
              }
            }
            
            // Close modal after successful update
            setTimeout(() => {
              closeProfileModal();
            }, 1500);
          } else if (activeTab === 'change-password') {
            // Clear password fields on success
            resetPasswordFields();
            showFlashMessage(result.message || 'Password changed successfully!', 'success');
            
            // Switch back to profile info tab after password change
            setTimeout(() => {
              switchTab('profile-info');
            }, 1500);
          }
        } else {
          showFlashMessage(result.message || 'Error updating profile', 'error');
        }
      } catch (error) {
        console.error('Error:', error);
        showFlashMessage('An error occurred. Please try again.', 'error');
      } finally {
        // Reset button state
        saveButton.textContent = originalText;
        saveButton.disabled = false;
      }
    });
  }
  
  // Preview image before upload
  const fileInput = document.getElementById('profileImage');
  const imagePreview = document.querySelector('.current-profile-image-preview');
  
  if (fileInput && imagePreview) {
    fileInput.addEventListener('change', function() {
      if (this.files && this.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
          imagePreview.src = e.target.result;
          imagePreview.classList.add('upload-preview');
        }
        
        reader.readAsDataURL(this.files[0]);
      } else {
        imagePreview.classList.remove('upload-preview');
      }
    });
  }
  
  // Tab switching function
  function switchTab(tabName) {
    // Update tabs
    document.querySelectorAll('.profile-edit-tab').forEach(tab => {
      tab.classList.remove('active');
      if (tab.getAttribute('data-tab') === tabName) {
        tab.classList.add('active');
      }
    });
    
    // Update tab content
    document.querySelectorAll('.profile-edit-tab-content').forEach(content => {
      content.classList.remove('active');
      if (content.id === `${tabName}-tab`) {
        content.classList.add('active');
      }
    });
    
    // Update button text based on active tab
    const saveButton = document.querySelector('.profile-edit-save-button');
    if (saveButton) {
      saveButton.textContent = tabName === 'change-password' ? 'Change Password' : 'Save Changes';
    }
  }
  
  // Password strength checker function
  function checkPasswordStrength() {
    const password = newPasswordInput.value;
    const strengthBar = document.querySelector('.password-strength-bar');
    const strengthText = document.querySelector('.password-strength-text');
    
    if (!strengthBar || !strengthText) return;
    
    // Reset
    strengthBar.className = 'password-strength-bar';
    strengthBar.style.width = '0%';
    strengthText.textContent = '';
    
    if (!password) return;
    
    let score = 0;
    
    // Length check
    if (password.length >= 8) score += 1;
    if (password.length >= 12) score += 1;
    
    // Character variety checks
    if (/[a-z]/.test(password)) score += 1; // lowercase
    if (/[A-Z]/.test(password)) score += 1; // uppercase
    if (/[0-9]/.test(password)) score += 1; // numbers
    if (/[^a-zA-Z0-9]/.test(password)) score += 1; // special characters
    
    // Determine strength level
    let strength, width, colorClass;
    
    if (score <= 2) {
      strength = 'Very Weak';
      width = '20%';
      colorClass = 'very-weak';
    } else if (score <= 3) {
      strength = 'Weak';
      width = '40%';
      colorClass = 'weak';
    } else if (score <= 4) {
      strength = 'Fair';
      width = '60%';
      colorClass = 'fair';
    } else if (score <= 5) {
      strength = 'Good';
      width = '80%';
      colorClass = 'good';
    } else {
      strength = 'Strong';
      width = '100%';
      colorClass = 'strong';
    }
    
    strengthBar.style.width = width;
    strengthBar.classList.add(colorClass);
    strengthText.textContent = `Password Strength: ${strength}`;
    strengthText.style.color = getStrengthColor(colorClass);
  }
  
  function getStrengthColor(strengthClass) {
    switch(strengthClass) {
      case 'very-weak': return '#dc3545';
      case 'weak': return '#fd7e14';
      case 'fair': return '#ffc107';
      case 'good': return '#28a745';
      case 'strong': return '#20c997';
      default: return '#888';
    }
  }
  
  // Check if passwords match
  function checkPasswordMatch() {
    if (!newPasswordInput || !confirmPasswordInput) return;
    
    const newPassword = newPasswordInput.value;
    const confirmPassword = confirmPasswordInput.value;
    
    if (!newPassword || !confirmPassword) return;
    
    if (newPassword !== confirmPassword) {
      confirmPasswordInput.style.borderColor = '#dc3545';
      confirmPasswordInput.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.1)';
    } else {
      confirmPasswordInput.style.borderColor = '#28a745';
      confirmPasswordInput.style.boxShadow = '0 0 0 3px rgba(40, 167, 69, 0.1)';
    }
  }
  
  // Reset password fields
  function resetPasswordFields() {
    const currentPassword = document.getElementById('currentPassword');
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    
    if (currentPassword) currentPassword.value = '';
    if (newPassword) newPassword.value = '';
    if (confirmPassword) confirmPassword.value = '';
    
    // Reset password strength meter
    checkPasswordStrength();
    
    // Reset border colors
    if (confirmPassword) {
      confirmPassword.style.borderColor = '';
      confirmPassword.style.boxShadow = '';
    }
  }
  
  // Form validation functions
  function validateProfileInfo() {
    const usernameInput = document.getElementById('profileUsername');
    
    if (!usernameInput.value.trim()) {
      showFlashMessage('Please enter a username', 'error');
      usernameInput.focus();
      return false;
    }
    
    const fileInput = document.getElementById('profileImage');
    if (fileInput.files.length > 0) {
      const file = fileInput.files[0];
      const maxSize = 2 * 1024 * 1024; // 2MB in bytes
      
      if (file.size > maxSize) {
        showFlashMessage('File size must be less than 2MB', 'error');
        fileInput.value = '';
        return false;
      }
      
      const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
      if (!allowedTypes.includes(file.type)) {
        showFlashMessage('Please upload a valid image file (JPEG, PNG, GIF, or WebP)', 'error');
        fileInput.value = '';
        return false;
      }
    }
    
    return true;
  }
  
  function validatePasswordChange() {
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (!currentPassword) {
      showFlashMessage('Please enter your current password', 'error');
      document.getElementById('currentPassword').focus();
      return false;
    }
    
    if (!newPassword) {
      showFlashMessage('Please enter a new password', 'error');
      document.getElementById('newPassword').focus();
      return false;
    }
    
    if (newPassword.length < 8) {
      showFlashMessage('New password must be at least 8 characters long', 'error');
      document.getElementById('newPassword').focus();
      return false;
    }
    
    // Check for both letters and numbers
    if (!/(?=.*[a-zA-Z])(?=.*[0-9])/.test(newPassword)) {
      showFlashMessage('Password must contain both letters and numbers', 'warning');
      document.getElementById('newPassword').focus();
      return false;
    }
    
    if (!confirmPassword) {
      showFlashMessage('Please confirm your new password', 'error');
      document.getElementById('confirmPassword').focus();
      return false;
    }
    
    if (newPassword !== confirmPassword) {
      showFlashMessage('New passwords do not match', 'error');
      document.getElementById('confirmPassword').focus();
      return false;
    }
    
    return true;
  }
  
  function updateProfileDisplay(userData) {
    // Update profile name
    const profileName = document.querySelector('.collection-profile-name');
    if (profileName) profileName.textContent = userData.username;
    
    // Update profile bio
    const profileBio = document.querySelector('.collection-profile-bio');
    if (profileBio) {
      if (userData.bio) {
        profileBio.textContent = userData.bio;
        profileBio.style.display = 'block';
      } else {
        profileBio.style.display = 'none';
      }
    }
    
    // Update profile meta (joined date)
    const profileMeta = document.querySelector('.collection-profile-meta');
    if (profileMeta && userData.createdAt) {
      const currentText = profileMeta.textContent;
      const newText = currentText.replace(/Joined [A-Za-z]+ \d{4}/, `Joined ${userData.createdAt}`);
      profileMeta.textContent = newText;
    }
    
    // Update profile images with cache busting
    if (userData.profileImage) {
      const timestamp = new Date().getTime();
      const newImageUrl = `/uploads/profile/${userData.profileImage}?t=${timestamp}`;
      
      const profileImage = document.querySelector('.collection-profile-image');
      if (profileImage) profileImage.src = newImageUrl;
      
      const modalProfileImage = document.querySelector('.current-profile-image-preview');
      if (modalProfileImage) modalProfileImage.src = newImageUrl;
    }
  }
  
  function closeProfileModal() {
    if (profileModal) {
      profileModal.style.display = 'none';
      document.body.style.overflow = ''; // Restore scrolling
      
      // Reset form to original values
      const form = document.getElementById('profileEditForm');
      if (form) {
        // Switch back to profile info tab
        switchTab('profile-info');
        
        // Reset all fields
        form.reset();
        
        // Reset username field to current username
        const usernameInput = document.getElementById('profileUsername');
        if (usernameInput && window.collectionData && window.collectionData.currentUser) {
          usernameInput.value = window.collectionData.currentUser.username;
        }
        
        // Reset bio field
        const bioInput = document.getElementById('profileBio');
        const currentUser = window.collectionData ? window.collectionData.currentUser : null;
        if (bioInput && currentUser && currentUser.bio !== undefined) {
          bioInput.value = currentUser.bio || '';
        }
        
        // Reset image preview to current image
        const imagePreview = document.querySelector('.current-profile-image-preview');
        if (imagePreview) {
          const timestamp = new Date().getTime();
          imagePreview.src = `/uploads/profile/${window.collectionData.currentUser.profileImage}?t=${timestamp}`;
          imagePreview.classList.remove('upload-preview');
        }
        
        // Reset password fields
        resetPasswordFields();
      }
    }
  }
  
  // Close modal with Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && profileModal && profileModal.style.display === 'flex') {
      closeProfileModal();
    }
  });
  
  // Flash message function
  function showFlashMessage(message, type) {
    const flashContainer = document.getElementById('collectionFlashContainer');
    if (!flashContainer) return;
    
    // Remove existing messages
    const existingMessages = flashContainer.querySelectorAll('.collection-flash');
    existingMessages.forEach(msg => msg.remove());
    
    const flashDiv = document.createElement('div');
    flashDiv.className = `collection-flash collection-flash-${type}`;
    flashDiv.textContent = message;
    
    flashContainer.appendChild(flashDiv);
    
    // Remove flash message after 5 seconds
    setTimeout(() => {
      if (flashDiv.parentNode === flashContainer) {
        flashDiv.remove();
      }
    }, 5000);
  }
  
  // Expose functions to global scope if needed
  window.showFlashMessage = showFlashMessage;
  window.closeProfileModal = closeProfileModal;
});



document.addEventListener('DOMContentLoaded', function() {
    const userSelect = document.querySelector('#listing_user');
    const collectibleSelect = document.querySelector('#listing_collectible');
    
    if (userSelect && collectibleSelect) {
        userSelect.addEventListener('change', function() {
            const userId = this.value;
            const form = this.closest('form');
            
            if (!userId) {
                // Clear collectibles if no user selected
                collectibleSelect.innerHTML = '<option value="">Select a user first</option>';
                collectibleSelect.disabled = true;
                return;
            }
            
            // Show loading state
            collectibleSelect.innerHTML = '<option value="">Loading collectibles...</option>';
            collectibleSelect.disabled = true;
            
            // Submit form via AJAX
            const formData = new FormData(form);
            formData.append('ajax_request', '1');
            
            fetch(form.action || window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            .then(response => response.text())
            .then(html => {
                // Parse the HTML response
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Find the updated collectible select
                const newCollectibleSelect = doc.querySelector('#listing_collectible');
                if (newCollectibleSelect) {
                    collectibleSelect.innerHTML = newCollectibleSelect.innerHTML;
                    collectibleSelect.disabled = newCollectibleSelect.disabled;
                    
                    // Update help text if it exists
                    const helpText = document.querySelector('#collectible-help');
                    if (helpText && this.selectedOptions[0]) {
                        const userName = this.selectedOptions[0].text.split(' (ID:')[0];
                        helpText.textContent = 'Showing collectibles owned by ' + userName;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                collectibleSelect.innerHTML = '<option value="">Error loading collectibles</option>';
            });
        });
        
        // Initialize collectible field based on current user selection
        if (userSelect.value) {
            userSelect.dispatchEvent(new Event('change'));
        }
    }
});