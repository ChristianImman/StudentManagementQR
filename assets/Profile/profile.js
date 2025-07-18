function toggleMenu() {
    const menuList = document.getElementById('menuList');
    menuList.classList.toggle('show');
}

function previewPhoto(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        const img = document.getElementById('preview-img');
        if (img) {
            img.src = e.target.result;
            img.style.display = 'block';
        }

        const placeholderText = document.getElementById('placeholder-text');
        if (placeholderText) placeholderText.remove();

        const photoActions = document.getElementById('photoActions');
        if (photoActions) photoActions.classList.add('show');
    };

    reader.readAsDataURL(file);
}

function cancelPhoto() {
    const photoInput = document.getElementById('photoInput');
    photoInput.value = '';

    const photoActions = document.getElementById('photoActions');
    if (photoActions) photoActions.classList.remove('show');

    const img = document.getElementById('preview-img');
    if (img) {
        img.src = '';
        img.style.display = 'none';
    }

    
    const placeholder = document.createElement('span');
    placeholder.id = 'placeholder-text';
    placeholder.innerText = 'Add Photo';
    const photoPlaceholder = document.querySelector('.photo-placeholder');
    if (photoPlaceholder) photoPlaceholder.appendChild(placeholder);
}


document.addEventListener('DOMContentLoaded', function() {
    const uploadBtn = document.getElementById('uploadPhotoBtn');
    if (uploadBtn) {
        uploadBtn.addEventListener('click', function () {
            const form = document.getElementById('uploadForm');
            const formData = new FormData(form);

            fetch('upload.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    
                    window.location.reload();
                } else {
                    alert('Upload failed: ' + data.error);
                }
            })
            .catch(err => {
                console.error('Error uploading photo:', err);
                alert('Error uploading image.');
            });
        });
    }
});

function openSettings() {
    const settingsPopup = document.getElementById('settingsPopup');
    if (settingsPopup) settingsPopup.style.display = 'flex';
}

function closeSettings() {
    const settingsPopup = document.getElementById('settingsPopup');
    if (settingsPopup) settingsPopup.style.display = 'none';
}


function confirmDelete() {
    const deleteConfirmation = document.getElementById('deleteConfirmation');
    if (deleteConfirmation) deleteConfirmation.style.display = 'flex';
}


function cancelDelete() {
    const deleteConfirmation = document.getElementById('deleteConfirmation');
    if (deleteConfirmation) deleteConfirmation.style.display = 'none';
}


function deleteAccount() {
    window.location.href = 'profile.php?delete=true';
}


function saveChanges() {
    const firstName = document.getElementById('changeFirstName')?.value || '';
    const lastName = document.getElementById('changeLastName')?.value || '';
    const email = document.getElementById('changeEmail')?.value || '';
    const password = document.getElementById('changePassword')?.value || '';

    const data = {
        first_name: firstName,
        last_name: lastName,
        email_address: email,
        password: password
    };

    fetch('update_profile.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            alert(result.message || 'Profile updated!');
            closeSettings();
            location.reload();
        } else {
            alert(result.error || 'Update failed.');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Error updating profile.');
    });
}


document.addEventListener('DOMContentLoaded', () => {
    const cancelDeleteBtn = document.querySelector('.cancel-delete');
    const confirmDeleteBtn = document.querySelector('.confirm-delete');

    if (cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', cancelDelete);
    if (confirmDeleteBtn) confirmDeleteBtn.addEventListener('click', deleteAccount);
});
