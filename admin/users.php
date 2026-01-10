<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - FlyWing.1000X</title>
    
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; }

        /* Navbar & Sidebar (Same as before) */
        .navbar-custom { background-color: #0d6efd; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .navbar-brand { font-weight: 700; color: #fff !important; letter-spacing: 1px; }
        .btn-logout { background-color: rgba(255, 255, 255, 0.2); color: #fff; border: 1px solid rgba(255, 255, 255, 0.5); transition: all 0.3s; }
        .btn-logout:hover { background-color: #fff; color: #0d6efd; }
        .sidebar { min-height: 100vh; background-color: #fff; box-shadow: 2px 0 5px rgba(0,0,0,0.05); padding-top: 20px; }
        .nav-link { color: #495057; font-weight: 500; padding: 12px 20px; margin-bottom: 5px; border-radius: 5px; display: flex; align-items: center; transition: all 0.2s; }
        .nav-link i { font-size: 1.2rem; margin-right: 15px; width: 25px; text-align: center; }
        .nav-link:hover { background-color: #e9ecef; color: #0d6efd; transform: translateX(5px); }
        .nav-link.active { background-color: #e7f1ff; color: #0d6efd; font-weight: 600; }

        /* Main Content */
        .main-content { padding: 30px; }
        .table-card { border: none; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); overflow: hidden; background: white; }
        .table-header { background-color: #fff; border-bottom: 1px solid #edf2f9; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        
        /* Table Styles */
        .custom-table { margin-bottom: 0; }
        .custom-table thead th { background-color: #f8f9fa; color: #6c757d; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; border-bottom: 2px solid #edf2f9; padding: 15px; }
        .custom-table tbody td { vertical-align: middle; padding: 15px; color: #333; border-bottom: 1px solid #edf2f9; }
        .custom-table tbody tr:hover { background-color: #fcfcfc; }
        
        /* Avatar & Actions */
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 12px; }
        .user-info h6 { margin: 0; font-weight: 600; color: #2c3e50; }
        .user-info small { color: #8898aa; }
        .action-btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; margin: 0 3px; transition: all 0.2s; text-decoration: none; }
        .btn-view { background-color: #e7f1ff; color: #0d6efd; }
        .btn-view:hover { background-color: #0d6efd; color: #fff; }
        .btn-edit { background-color: #fff3cd; color: #ffc107; }
        .btn-edit:hover { background-color: #ffc107; color: #000; }
        .btn-delete { background-color: #f8d7da; color: #dc3545; }
        .btn-delete:hover { background-color: #dc3545; color: #fff; }

        /* Pagination Styles */
        .pagination .page-item .page-link { color: #0d6efd; border: 1px solid #dee2e6; margin: 0 2px; border-radius: 4px; }
        .pagination .page-item.active .page-link { background-color: #0d6efd; border-color: #0d6efd; color: #fff; }
        .pagination .page-item.disabled .page-link { color: #6c757d; }
        .pagination .page-link:hover { background-color: #e9ecef; }
        .pagination { margin-bottom: 0; cursor: pointer; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand ms-3" href="#">
                <i class="bi bi-airplane-engines-fill me-2"></i>FlyWing.1000X
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end">
                <ul class="navbar-nav me-3">
                    <li class="nav-item">
                        <a class="btn btn-sm btn-logout" href="#">
                            <i class="bi bi-box-arrow-right me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <h6 class="sidebar-heading px-3 mt-2 mb-3 text-muted text-uppercase">Menu</h6>
                    <ul class="nav flex-column px-2">
                        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-house-fill"></i> Home</a></li>
                        <li class="nav-item"><a class="nav-link active" href="users.php"><i class="bi bi-people-fill"></i> Users</a></li>
                        <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-person-lines-fill"></i> Active & Inactive</a></li>
                        <li class="nav-item"><a class="nav-link" href="payment_table.php"><i class="bi bi-table"></i> Payment Table</a></li>
                        <li class="nav-item"><a class="nav-link" href="payment_method_add.php"><i class="bi bi-credit-card-2-front-fill"></i> Payment Add</a></li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-4">
                    <div>
                        <h2 class="h3 fw-bold">User Management</h2>
                        <p class="text-muted">View, edit and manage your system users.</p>
                    </div>
                    <div>
                        <a href="add_user.html" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add New User</a>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-header">
                        <h5 class="mb-0">All Users</h5>
                        <div class="input-group" style="width: 250px;">
                            <input type="text" class="form-control form-control-sm" placeholder="Search user...">
                            <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-search"></i></button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table custom-table table-hover" id="userTable">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Join Date</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <!-- Row 1 -->
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name=John+Doe&background=random" class="user-avatar">
                                            <div class="user-info"><h6>John Doe</h6><small>john@example.com</small></div>
                                        </div>
                                    </td>
                                    <td><span class="text-secondary fw-medium">Admin</span></td>
                                    <td>Oct 24, 2023</td>
                                    <td><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Active</span></td>
                                    <td class="text-end">
                                        <a href="#" class="action-btn btn-view"><i class="bi bi-eye-fill"></i></a>
                                        <a href="#" class="action-btn btn-edit"><i class="bi bi-pencil-square"></i></a>
                                        <a href="#" class="action-btn btn-delete"><i class="bi bi-trash-fill"></i></a>
                                    </td>
                                </tr>
                                <!-- Row 2 -->
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name=Sarah+Smith&background=random" class="user-avatar">
                                            <div class="user-info"><h6>Sarah Smith</h6><small>sarah@example.com</small></div>
                                        </div>
                                    </td>
                                    <td><span class="text-secondary fw-medium">Editor</span></td>
                                    <td>Nov 12, 2023</td>
                                    <td><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Active</span></td>
                                    <td class="text-end">
                                        <a href="#" class="action-btn btn-view"><i class="bi bi-eye-fill"></i></a>
                                        <a href="#" class="action-btn btn-edit"><i class="bi bi-pencil-square"></i></a>
                                        <a href="#" class="action-btn btn-delete"><i class="bi bi-trash-fill"></i></a>
                                    </td>
                                </tr>
                                <!-- Row 3 -->
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name=Mike+Ross&background=random" class="user-avatar">
                                            <div class="user-info"><h6>Mike Ross</h6><small>mike@example.com</small></div>
                                        </div>
                                    </td>
                                    <td><span class="text-secondary fw-medium">User</span></td>
                                    <td>Dec 05, 2023</td>
                                    <td><span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3">Inactive</span></td>
                                    <td class="text-end">
                                        <a href="#" class="action-btn btn-view"><i class="bi bi-eye-fill"></i></a>
                                        <a href="#" class="action-btn btn-edit"><i class="bi bi-pencil-square"></i></a>
                                        <a href="#" class="action-btn btn-delete"><i class="bi bi-trash-fill"></i></a>
                                    </td>
                                </tr>
                                <!-- Row 4 -->
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name=Emily+Clark&background=random" class="user-avatar">
                                            <div class="user-info"><h6>Emily Clark</h6><small>emily@example.com</small></div>
                                        </div>
                                    </td>
                                    <td><span class="text-secondary fw-medium">Admin</span></td>
                                    <td>Jan 15, 2024</td>
                                    <td><span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3">Pending</span></td>
                                    <td class="text-end">
                                        <a href="#" class="action-btn btn-view"><i class="bi bi-eye-fill"></i></a>
                                        <a href="#" class="action-btn btn-edit"><i class="bi bi-pencil-square"></i></a>
                                        <a href="#" class="action-btn btn-delete"><i class="bi bi-trash-fill"></i></a>
                                    </td>
                                </tr>
                                <!-- Row 5 (Added for Demo) -->
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name=Tom+Hanks&background=random" class="user-avatar">
                                            <div class="user-info"><h6>Tom Hanks</h6><small>tom@example.com</small></div>
                                        </div>
                                    </td>
                                    <td><span class="text-secondary fw-medium">Editor</span></td>
                                    <td>Feb 20, 2024</td>
                                    <td><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Active</span></td>
                                    <td class="text-end">
                                        <a href="#" class="action-btn btn-view"><i class="bi bi-eye-fill"></i></a>
                                        <a href="#" class="action-btn btn-edit"><i class="bi bi-pencil-square"></i></a>
                                        <a href="#" class="action-btn btn-delete"><i class="bi bi-trash-fill"></i></a>
                                    </td>
                                </tr>
                                <!-- Row 6 (Added for Demo) -->
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name=Bruce+Wayne&background=random" class="user-avatar">
                                            <div class="user-info"><h6>Bruce Wayne</h6><small>bruce@flywing.com</small></div>
                                        </div>
                                    </td>
                                    <td><span class="text-secondary fw-medium">Admin</span></td>
                                    <td>Mar 01, 2024</td>
                                    <td><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Active</span></td>
                                    <td class="text-end">
                                        <a href="#" class="action-btn btn-view"><i class="bi bi-eye-fill"></i></a>
                                        <a href="#" class="action-btn btn-edit"><i class="bi bi-pencil-square"></i></a>
                                        <a href="#" class="action-btn btn-delete"><i class="bi bi-trash-fill"></i></a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination Controls -->
                    <div class="p-3 border-top d-flex justify-content-between align-items-center">
                        <small class="text-muted" id="pageInfo">Showing 1 to 3 of 6 entries</small>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-end mb-0" id="paginationControls">
                                <!-- Dynamic buttons will be inserted here by JS -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Pagination Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configuration
            const rowsPerPage = 3; // Change this to show more rows per page
            
            const tableBody = document.getElementById('tableBody');
            const paginationContainer = document.getElementById('paginationControls');
            const pageInfo = document.getElementById('pageInfo');
            const rows = Array.from(tableBody.querySelectorAll('tr'));
            const totalRows = rows.length;
            const totalPages = Math.ceil(totalRows / rowsPerPage);
            let currentPage = 1;

            function displayRows(page) {
                const start = (page - 1) * rowsPerPage;
                const end = start + rowsPerPage;

                rows.forEach((row, index) => {
                    if (index >= start && index < end) {
                        row.style.display = ''; // Show
                    } else {
                        row.style.display = 'none'; // Hide
                    }
                });

                // Update info text
                const currentStart = totalRows === 0 ? 0 : start + 1;
                const currentEnd = Math.min(end, totalRows);
                pageInfo.innerText = `Showing ${currentStart} to ${currentEnd} of ${totalRows} entries`;
            }

            function setupPagination() {
                paginationContainer.innerHTML = '';

                // 1. Previous Button
                const prevLi = document.createElement('li');
                prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
                prevLi.innerHTML = `<a class="page-link" href="#">Previous</a>`;
                prevLi.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (currentPage > 1) {
                        currentPage--;
                        updatePagination();
                    }
                });
                paginationContainer.appendChild(prevLi);

                // 2. Page Numbers
                for (let i = 1; i <= totalPages; i++) {
                    const li = document.createElement('li');
                    li.className = `page-item ${currentPage === i ? 'active' : ''}`;
                    li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                    li.addEventListener('click', function(e) {
                        e.preventDefault();
                        currentPage = i;
                        updatePagination();
                    });
                    paginationContainer.appendChild(li);
                }

                // 3. Next Button
                const nextLi = document.createElement('li');
                nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
                nextLi.innerHTML = `<a class="page-link" href="#">Next</a>`;
                nextLi.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (currentPage < totalPages) {
                        currentPage++;
                        updatePagination();
                    }
                });
                paginationContainer.appendChild(nextLi);
            }

            function updatePagination() {
                displayRows(currentPage);
                setupPagination(); // Re-render buttons to update active/disabled states
            }

            // Initialize
            updatePagination();
        });
    </script>
</body>
</html>