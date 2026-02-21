<?php
// admin/ipe-clearance.php
require_once '../config/config.php';
require_once '../config/database.php';

$currentPage = 'ipe-clearance.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPE Clearance - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/admin-custom.css">
    <style>
        .main-content { margin-left: 250px; padding: 2rem; }
        @media (max-width: 768px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-gavel me-2"></i>IPE Clearance Requests</h2>
                    <button class="btn btn-primary" onclick="refreshTable()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh
                    </button>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="clearanceTable" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Date</th>
                                        <th>User</th>
                                        <th>Reference</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="statusForm">
                        <input type="hidden" id="modalTransactionId">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="modalStatus" required>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="completed">Completed</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Admin Notes</label>
                            <textarea class="form-control" id="modalNotes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveStatusUpdate()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let adminToken = localStorage.getItem('adminToken');
        let clearanceTable = null;
        const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));

        document.addEventListener('DOMContentLoaded', function() {
            if (!adminToken) {
                window.location.href = 'login.html';
                return;
            }
            initTable();
        });

        function initTable() {
            clearanceTable = $('#clearanceTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '../api/admin/transactions.php',
                    data: { type: 'ipe_clearance' },
                    headers: { 'Authorization': 'Bearer ' + adminToken },
                    dataSrc: 'data.transactions'
                },
                columns: [
                    { data: 'id' },
                    { 
                        data: 'created_at',
                        render: data => new Date(data).toLocaleString()
                    },
                    { 
                        data: null,
                        render: data => `<div>${data.user_name || 'N/A'}</div><small class="text-muted">${data.user_email || ''}</small>`
                    },
                    { data: 'reference_number' },
                    { 
                        data: 'amount',
                        render: data => '₦' + parseFloat(data || 0).toLocaleString('en-NG', {minimumFractionDigits: 2})
                    },
                    { 
                        data: 'status',
                        render: data => {
                            const colors = { 'completed': 'success', 'pending': 'warning', 'failed': 'danger', 'processing': 'info' };
                            return `<span class="badge bg-${colors[data] || 'secondary'}">${data}</span>`;
                        }
                    },
                    { 
                        data: null,
                        render: data => `
                            <button class="btn btn-sm btn-outline-warning" onclick="editStatus(${data.id}, '${data.status}', '${data.admin_notes || ''}')">
                                <i class="fas fa-edit"></i>
                            </button>
                        `,
                        orderable: false
                    }
                ],
                order: [[0, 'desc']]
            });
        }

        function refreshTable() { clearanceTable.ajax.reload(); }

        function editStatus(id, status, notes) {
            document.getElementById('modalTransactionId').value = id;
            document.getElementById('modalStatus').value = status;
            document.getElementById('modalNotes').value = notes === 'null' ? '' : notes;
            statusModal.show();
        }

        async function saveStatusUpdate() {
            const id = document.getElementById('modalTransactionId').value;
            const status = document.getElementById('modalStatus').value;
            const notes = document.getElementById('modalNotes').value;

            const response = await fetch('../api/admin/update-service-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + adminToken
                },
                body: JSON.stringify({ id, status, notes })
            });

            const result = await response.json();
            if (result.success) {
                statusModal.hide();
                refreshTable();
            } else {
                alert('Error: ' + result.message);
            }
        }
    </script>
</body>
</html>
