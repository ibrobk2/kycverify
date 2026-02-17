<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Integrations - Admin Panel</title>
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
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin: 0.25rem 0;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        .api-card {
            transition: transform 0.2s;
        }
        .api-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-plug me-2"></i>API Integrations</h2>
                    <div>
                        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addConfigModal">
                            <i class="fas fa-plus me-2"></i>Add API Integration
                        </button>
                        <button class="btn btn-primary" onclick="loadConfigs()">
                            <i class="fas fa-refresh me-2"></i>Refresh
                        </button>
                    </div>
                </div>

                <!-- API Configs List -->
                <div class="row" id="configsList">
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-3">Loading configurations...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Config Modal -->
    <div class="modal fade" id="addConfigModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New API Integration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addConfigForm">
                        <div class="mb-3">
                            <label class="form-label">Service Name (e.g. katpay, monnify)</label>
                            <input type="text" class="form-control" id="addServiceName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Base URL</label>
                            <input type="url" class="form-control" id="addBaseUrl" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">API Key</label>
                            <input type="text" class="form-control" id="addApiKey">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">API Secret</label>
                            <input type="password" class="form-control" id="addApiSecret">
                        </div>
                        <div class="mb-3 katpay-only" style="display:none;">
                            <label class="form-label">Merchant ID</label>
                            <input type="text" class="form-control" id="addMerchantId">
                        </div>
                        <div class="mb-3 katpay-only" style="display:none;">
                            <label class="form-label">Webhook Secret</label>
                            <input type="text" class="form-control" id="addWebhookSecret">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="addIsActive" checked>
                            <label class="form-check-label">Active</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addConfig()">Add Integration</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Config Modal -->
    <div class="modal fade" id="configModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Configure API</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="configForm">
                        <input type="hidden" id="configId">
                        <div class="mb-3">
                            <label class="form-label">Service Name</label>
                            <input type="text" class="form-control" id="serviceName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Base URL</label>
                            <input type="url" class="form-control" id="baseUrl">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">API Key</label>
                            <input type="text" class="form-control" id="apiKey" placeholder="Leave empty to keep unchanged">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">API Secret</label>
                            <input type="password" class="form-control" id="apiSecret" placeholder="Leave empty to keep unchanged">
                        </div>
                        <div class="mb-3 katpay-only" style="display:none;">
                            <label class="form-label">Merchant ID</label>
                            <input type="text" class="form-control" id="merchantId">
                        </div>
                        <div class="mb-3 katpay-only" style="display:none;">
                            <label class="form-label">Webhook Secret</label>
                            <input type="text" class="form-control" id="webhookSecret">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="isActive">
                            <label class="form-check-label">Active</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-info" onclick="testConnection()">
                        <i class="fas fa-wifi me-2"></i>Test Connection
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveConfig()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let adminToken = null;
        let configModal;

        document.addEventListener('DOMContentLoaded', function() {
            adminToken = localStorage.getItem('adminToken');
            if (!adminToken) {
                window.location.href = 'login.html';
                return;
            }

            configModal = new bootstrap.Modal(document.getElementById('configModal'));
            loadConfigs();
        });

        async function loadConfigs() {
            try {
                const response = await fetch('../api/admin/api-config.php', {
                    headers: { 'Authorization': 'Bearer ' + adminToken }
                });
                const result = await response.json();

                if (result.success) {
                    displayConfigs(result.data);
                } else {
                    alert('Failed to load configurations: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error loading configurations');
            }
        }

        function displayConfigs(configs) {
            const container = document.getElementById('configsList');
            
            if (configs.length === 0) {
                container.innerHTML = '<div class="col-12 text-center">No configurations found</div>';
                return;
            }

            container.innerHTML = configs.map(c => `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 api-card border-${c.status === 'active' ? 'success' : 'secondary'}">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-capitalize">${c.service_name}</h5>
                            <span class="badge bg-${c.status === 'active' ? 'success' : 'secondary'}">${c.status}</span>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><small class="text-muted">Base URL:</small><br><span class="text-truncate d-block">${c.base_url}</span></p>
                            <p class="mb-2"><small class="text-muted">API Key:</small><br><code>${c.api_key ? c.api_key : 'Not Set'}</code></p>
                            <p class="mb-3"><small class="text-muted">Last Updated:</small><br>${new Date(c.updated_at).toLocaleString()}</p>
                            
                            <button class="btn btn-outline-primary w-100" onclick='editConfig(${JSON.stringify(c)})'>
                                <i class="fas fa-cog me-2"></i>Configure
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function editConfig(config) {
            document.getElementById('configId').value = config.id;
            document.getElementById('serviceName').value = config.service_name;
            document.getElementById('baseUrl').value = config.base_url;
            document.getElementById('apiKey').value = config.api_key || '';
            document.getElementById('apiSecret').value = config.api_secret || '';
            document.getElementById('merchantId').value = config.merchant_id || '';
            document.getElementById('webhookSecret').value = config.webhook_secret || '';
            document.getElementById('isActive').checked = config.status === 'active';
            
            // Toggle KatPay only fields
            const katpayFields = document.querySelectorAll('.katpay-only');
            katpayFields.forEach(el => el.style.display = config.service_name === 'katpay' ? 'block' : 'none');
            
            configModal.show();
        }

        // Add listener for service name change in add modal
        document.getElementById('addServiceName').addEventListener('input', function() {
            const isKatpay = this.value.toLowerCase().includes('katpay');
            document.querySelectorAll('#addConfigModal .katpay-only').forEach(el => el.style.display = isKatpay ? 'block' : 'none');
        });

        async function addConfig() {
            const data = {
                action: 'add',
                service_name: document.getElementById('addServiceName').value.trim(),
                base_url: document.getElementById('addBaseUrl').value.trim(),
                api_key: document.getElementById('addApiKey').value,
                api_secret: document.getElementById('addApiSecret').value,
                merchant_id: document.getElementById('addMerchantId').value,
                webhook_secret: document.getElementById('addWebhookSecret').value,
                status: document.getElementById('addIsActive').checked ? 'active' : 'inactive'
            };

            if (!data.service_name || !data.base_url) {
                alert('Service Name and Base URL are required');
                return;
            }

            try {
                const response = await fetch('../api/admin/api-config.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + adminToken
                    },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    bootstrap.Modal.getInstance(document.getElementById('addConfigModal')).hide();
                    document.getElementById('addConfigForm').reset();
                    loadConfigs();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error adding configuration');
            }
        }

        async function saveConfig() {
            const id = document.getElementById('configId').value;
            const data = {
                action: 'update',
                id: id,
                base_url: document.getElementById('baseUrl').value,
                api_key: document.getElementById('apiKey').value,
                api_secret: document.getElementById('apiSecret').value,
                merchant_id: document.getElementById('merchantId').value,
                webhook_secret: document.getElementById('webhookSecret').value,
                status: document.getElementById('isActive').checked ? 'active' : 'inactive'
            };

            try {
                const response = await fetch('../api/admin/api-config.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + adminToken
                    },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    configModal.hide();
                    loadConfigs();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error saving configuration');
            }
        }

        async function testConnection() {
            const btn = document.querySelector('#configModal .btn-info');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing...';
            btn.disabled = true;

            try {
                const response = await fetch('../api/admin/api-config.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + adminToken
                    },
                    body: JSON.stringify({ action: 'test', id: document.getElementById('configId').value })
                });
                const result = await response.json();

                if (result.success && result.connection_status === 'success') {
                    alert('✅ ' + result.message);
                } else {
                    alert('❌ ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error testing connection');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
