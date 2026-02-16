<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slider Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .img-preview { width: 100%; height: 150px; object-fit: cover; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-images me-2"></i>Slider Management</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addImageModal">
                        <i class="fas fa-plus me-2"></i>Add Slider Image
                    </button>
                </div>

                <div class="row" id="slider-list">
                    <!-- Images will be loaded here -->
                    <div class="col-12 text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">Loading images...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Image Modal -->
    <div class="modal fade" id="addImageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Slider Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="add-image-form">
                        <div class="mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="url" class="form-control" name="image_url" required placeholder="https://example.com/image.jpg">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Caption (Optional)</label>
                            <input type="text" class="form-control" name="caption" placeholder="Enter image caption">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="handleAddImage()">Add Image</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const adminToken = localStorage.getItem('adminToken');
        if (!adminToken) window.location.href = 'login.html';

        document.addEventListener('DOMContentLoaded', loadImages);

        async function loadImages() {
            try {
                const response = await fetch('../api/admin/slider-images.php', {
                    headers: { 'Authorization': 'Bearer ' + adminToken }
                });
                const result = await response.json();

                const listContainer = document.getElementById('slider-list');
                if (result.success) {
                    if (result.data.length === 0) {
                        listContainer.innerHTML = '<div class="col-12 text-center py-5"><p class="text-muted">No slider images found.</p></div>';
                        return;
                    }

                    listContainer.innerHTML = result.data.map(img => `
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card h-100 stat-card">
                                <img src="${img.image_url}" class="card-img-top img-preview" alt="Slider">
                                <div class="card-body">
                                    <h6 class="card-title text-truncate">${img.caption || 'No Caption'}</h6>
                                    <button class="btn btn-outline-danger btn-sm w-100" onclick="deleteImage(${img.id})">
                                        <i class="fas fa-trash me-2"></i>Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    listContainer.innerHTML = '<div class="col-12 text-center py-5"><p class="text-danger">Failed to load images.</p></div>';
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        async function handleAddImage() {
            const form = document.getElementById('add-image-form');
            const formData = new FormData(form);
            const data = {
                image_url: formData.get('image_url'),
                caption: formData.get('caption')
            };

            try {
                const response = await fetch('../api/admin/slider-images.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + adminToken 
                    },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addImageModal'));
                    if (modal) modal.hide();
                    form.reset();
                    loadImages();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('An error occurred.');
            }
        }

        async function deleteImage(id) {
            if (!confirm('Are you sure you want to delete this image?')) return;

            try {
                const response = await fetch(`../api/admin/slider-images.php?id=${id}`, {
                    method: 'DELETE',
                    headers: { 'Authorization': 'Bearer ' + adminToken }
                });
                const result = await response.json();

                if (result.success) {
                    loadImages();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('An error occurred.');
            }
        }
    </script>
</body>
</html>
