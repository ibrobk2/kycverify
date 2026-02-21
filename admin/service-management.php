<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #1e3a8a;
            --cyan: #06b6d4;
        }
        .sidebar {
            background: linear-gradient(135deg, var(--primary-blue), #1e293b);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        .status-badge {
            text-transform: capitalize;
        }
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
                    <h2><i class="fas fa-tasks me-2"></i>Service Management</h2>
                    <button class="btn btn-primary" onclick="refreshTable()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh
                    </button>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Service Type</label>
                                <select class="form-select" id="serviceTypeFilter">
                                    <option value="all">All Services</option>
                                    <option value="nin_verification">NIN Verification</option>
                                    <option value="nin_modification">NIN Modification</option>
                                    <option value="bvn_verification">BVN Verification</option>
                                    <option value="bvn_slip">BVN Slip</option>
                                    <option value="bvn_modification">BVN Modification</option>
                                    <option value="ipe_clearance">IPE Clearance</option>
                                    <option value="birth_attestation">Birth Attestation</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="completed">Completed</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" id="tableSearch" placeholder="Search user, email or reference...">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-primary w-100" onclick="applyFilters()">
                                    <i class="fas fa-filter me-2"></i>Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="servicesTable" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Date</th>
                                        <th>User</th>
                                        <th>Service</th>
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
                    <h5 class="modal-title">Update Service Status</h5>
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
                            <textarea class="form-control" id="modalNotes" rows="3" placeholder="Add some notes for the user..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveStatusUpdate()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="detailsContent">
                        <pre id="detailsJson" class="bg-light p-3 rounded"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let adminToken = null;
        let servicesTable = null;
        const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
        const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));

        document.addEventListener('DOMContentLoaded', function() {
            adminToken = localStorage.getItem('adminToken');
            if (!adminToken) {
                window.location.href = 'login.html';
                return;
            }

            initTable();
        });

        function initTable() {
            servicesTable = $('#servicesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '../api/admin/transactions.php',
                    data: function(d) {
                        d.type = $('#serviceTypeFilter').val();
                        d.status = $('#statusFilter').val();
                        d.search.value = $('#tableSearch').val();
                    },
                    headers: { 'Authorization': 'Bearer ' + adminToken },
                    dataSrc: 'data.transactions'
                },
                columns: [
                    { data: 'id' },
                    { 
                        data: 'created_at',
                        render: function(data) {
                            return new Date(data).toLocaleString();
                        }
                    },
                    { 
                        data: null,
                        render: function(data) {
                            return `<div>${data.user_name || 'N/A'}</div><small class="text-muted">${data.user_email || ''}</small>`;
                        }
                    },
                    { 
                        data: 'service_type',
                        render: function(data) {
                            return `<span class="badge bg-info text-dark">${data.replace(/_/g, ' ')}</span>`;
                        }
                    },
                    { data: 'reference_number' },
                    { 
                        data: 'amount',
                        render: function(data) {
                            return '₦' + parseFloat(data).toLocaleString();
                        }
                    },
                    { 
                        data: 'status',
                        render: function(data) {
                            const colors = { 'completed': 'success', 'pending': 'warning', 'failed': 'danger', 'processing': 'info' };
                            return `<span class="badge bg-${colors[data] || 'secondary'} status-badge">${data}</span>`;
                        }
                    },
                    { 
                        data: null,
                        render: function(data) {
                            return `
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewDetails(${data.id})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="editStatus(${data.id}, '${data.status}', '${data.admin_notes || ''}')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            `;
                        },
                        orderable: false
                    }
                ],
                order: [[0, 'desc']],
                pageLength: 25,
                responsive: true
            });
        }

        function refreshTable() {
            servicesTable.ajax.reload();
        }

        function applyFilters() {
            refreshTable();
        }

        function editStatus(id, currentStatus, notes) {
            document.getElementById('modalTransactionId').value = id;
            document.getElementById('modalStatus').value = currentStatus;
            document.getElementById('modalNotes').value = notes === 'null' ? '' : notes;
            statusModal.show();
        }

        async function saveStatusUpdate() {
            const id = document.getElementById('modalTransactionId').value;
            const status = document.getElementById('modalStatus').value;
            const notes = document.getElementById('modalNotes').value;

            try {
                const response = await fetch('../api/admin/update-service-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + adminToken
                    },
                    body: JSON.stringify({
                        id: id,
                        status: status,
                        notes: notes
                    })
                });

                const result = await response.json();
                if (result.success) {
                    statusModal.hide();
                    refreshTable();
                    alert('Status updated successfully');
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred during update');
            }
        }

        async function viewDetails(id) {
            // Ideally we'd fetch specific details if not already in the row data
            // For now, let's try to find the row data in DataTables cache
            const rowData = servicesTable.rows().data().toArray().find(r => r.id == id);
            if (rowData) {
                document.getElementById('detailsJson').textContent = JSON.stringify(rowData, null, 2);
                detailsModal.show();
            }
        }
    </script>
</body>
</html>
