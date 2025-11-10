@extends('layouts.admin')

@section('title', 'Modern Table Design')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row">
        <div class="col-12 mb-4">
            <h2 class="page-title">Modern Table Design</h2>
            <p class="text-muted">Clean and modern table layout matching your preferred design</p>
        </div>
    </div>

    <!-- Modern Table Container -->
    <div class="table-container">
        <!-- Table Header with Search and Controls -->
        <div class="table-header">
            <div class="table-search">
                <input type="text" placeholder="Type in to Search" id="tableSearch">
            </div>
            <div class="table-actions">
                <button class="export-btn">
                    <i class="mdi mdi-file-excel"></i>
                    Export
                </button>
                <div class="table-show-entries">
                    Show
                    <select id="entriesPerPage">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    entries
                </div>
            </div>
        </div>

        <!-- Table Content -->
        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Account No</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Member Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td><span class="account-number">PM165288616</span></td>
                        <td>George</td>
                        <td>Asio</td>
                        <td>0772976357</td>
                        <td>george@example.com</td>
                        <td><span class="status-badge status-individual">Individual</span></td>
                        <td><span class="status-badge status-verified">Verified</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-modern btn-view">View</button>
                                <button class="btn-modern btn-delete">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td><span class="account-number">PM165295519</span></td>
                        <td>Isaac</td>
                        <td>Sendagire</td>
                        <td>0702682187</td>
                        <td>isaac@example.com</td>
                        <td><span class="status-badge status-individual">Individual</span></td>
                        <td><span class="status-badge status-verified">Verified</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-modern btn-view">View</button>
                                <button class="btn-modern btn-delete">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td><span class="account-number">PM165296155</span></td>
                        <td>Emmanuel</td>
                        <td>Nzeyimana</td>
                        <td>0775632975</td>
                        <td>emmanuel@example.com</td>
                        <td><span class="status-badge status-individual">Individual</span></td>
                        <td><span class="status-badge status-verified">Verified</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-modern btn-view">View</button>
                                <button class="btn-modern btn-delete">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td><span class="account-number">PM165296459</span></td>
                        <td>Ogwang</td>
                        <td>Ignatius</td>
                        <td>0788324350</td>
                        <td>ogwang@example.com</td>
                        <td><span class="status-badge status-individual">Individual</span></td>
                        <td><span class="status-badge status-verified">Verified</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-modern btn-view">View</button>
                                <button class="btn-modern btn-delete">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td><span class="account-number">PM165302750</span></td>
                        <td>Test Norah</td>
                        <td>Member</td>
                        <td>0708356505</td>
                        <td>norah@example.com</td>
                        <td><span class="status-badge status-individual">Individual</span></td>
                        <td><span class="status-badge status-verified">Verified</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-modern btn-view">View</button>
                                <button class="btn-modern btn-delete">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td><span class="account-number">PM165303816</span></td>
                        <td>Ignatius</td>
                        <td>Ogwang</td>
                        <td>0788324350</td>
                        <td>ignatius@example.com</td>
                        <td><span class="status-badge status-individual">Individual</span></td>
                        <td><span class="status-badge status-not-verified">Not Verified</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-modern btn-view">View</button>
                                <button class="btn-modern btn-delete">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td><span class="account-number">PM165306656</span></td>
                        <td>Mary</td>
                        <td>Amuge</td>
                        <td>0784514355</td>
                        <td>mary@example.com</td>
                        <td><span class="status-badge status-individual">Individual</span></td>
                        <td><span class="status-badge status-verified">Verified</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-modern btn-view">View</button>
                                <button class="btn-modern btn-delete">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>8</td>
                        <td><span class="account-number">PM165307057</span></td>
                        <td>Christine</td>
                        <td>Anyodi</td>
                        <td>0777910007</td>
                        <td>christine@example.com</td>
                        <td><span class="status-badge status-individual">Individual</span></td>
                        <td><span class="status-badge status-verified">Verified</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-modern btn-view">View</button>
                                <button class="btn-modern btn-delete">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>9</td>
                        <td><span class="account-number">PM165307453</span></td>
                        <td>Maculate</td>
                        <td>Ikuret</td>
                        <td>0775155963</td>
                        <td>maculate@example.com</td>
                        <td><span class="status-badge status-individual">Individual</span></td>
                        <td><span class="status-badge status-verified">Verified</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-modern btn-view">View</button>
                                <button class="btn-modern btn-delete">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>10</td>
                        <td><span class="account-number">PM165308062</span></td>
                        <td>Florence</td>
                        <td>Atukoit</td>
                        <td>0785555555</td>
                        <td>florence@example.com</td>
                        <td><span class="status-badge status-individual">Individual</span></td>
                        <td><span class="status-badge status-verified">Verified</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-modern btn-view">View</button>
                                <button class="btn-modern btn-delete">Delete</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modern Pagination -->
        <div class="modern-pagination">
            <div class="pagination-info">
                1 - 10 of 598
            </div>
            <div class="pagination-controls">
                <a href="#" class="pagination-btn" disabled>
                    <i class="mdi mdi-chevron-left"></i>
                    Previous
                </a>
                <div class="pagination-numbers">
                    <a href="#" class="pagination-btn active">1</a>
                    <a href="#" class="pagination-btn">2</a>
                    <a href="#" class="pagination-btn">3</a>
                    <a href="#" class="pagination-btn">4</a>
                    <a href="#" class="pagination-btn">5</a>
                    <span class="pagination-btn" style="border: none; background: none;">...</span>
                    <a href="#" class="pagination-btn">60</a>
                </div>
                <a href="#" class="pagination-btn">
                    Next
                    <i class="mdi mdi-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('tableSearch');
    const tableRows = document.querySelectorAll('.modern-table tbody tr');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        updatePaginationInfo();
    });
    
    // Entries per page functionality
    const entriesSelect = document.getElementById('entriesPerPage');
    entriesSelect.addEventListener('change', function() {
        console.log('Show', this.value, 'entries');
        // Implementation for changing entries per page
    });
    
    // Export functionality
    const exportBtn = document.querySelector('.export-btn');
    exportBtn.addEventListener('click', function() {
        console.log('Export table data');
        // Implementation for export
    });
    
    // Action buttons
    document.querySelectorAll('.btn-view').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const accountNo = row.querySelector('.account-number').textContent;
            console.log('View details for', accountNo);
        });
    });
    
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const accountNo = row.querySelector('.account-number').textContent;
            if (confirm('Are you sure you want to delete ' + accountNo + '?')) {
                console.log('Delete', accountNo);
                // Implementation for delete
            }
        });
    });
    
    // Pagination
    document.querySelectorAll('.pagination-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (this.hasAttribute('disabled')) {
                e.preventDefault();
                return;
            }
            
            // Remove active class from all
            document.querySelectorAll('.pagination-btn').forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked if it's a number
            if (!isNaN(this.textContent.trim())) {
                this.classList.add('active');
            }
        });
    });
    
    function updatePaginationInfo() {
        const visibleRows = Array.from(tableRows).filter(row => row.style.display !== 'none');
        const total = visibleRows.length;
        document.querySelector('.pagination-info').textContent = `1 - ${Math.min(10, total)} of ${total}`;
    }
});
</script>
@endpush
@endsection