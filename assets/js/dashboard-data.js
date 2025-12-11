import $ from 'jquery';
import 'datatables.net';

function initDataTable(tableId, options = {}) {
    if ($.fn.dataTable.isDataTable(tableId)) {
        $(tableId).DataTable().destroy();
    }
    
    if ($(tableId).length) {
        $(tableId).DataTable({
            pageLength: options.pageLength || 5,
            lengthChange: options.lengthChange !== undefined ? options.lengthChange : false,
            lengthMenu: options.lengthMenu || false,
            ordering: options.ordering !== undefined ? options.ordering : true,
            order: options.order || [[0, 'asc']],
            dom: "<'flex justify-between items-center mb-2'<'flex items-center'f>>rt<'flex justify-between items-center mt-2'<'flex items-center'i><'flex items-center'p>>",
            language: options.language || {
                search: "_INPUT_",
                searchPlaceholder: options.searchPlaceholder || "Search...",
                info: "Showing _START_ to _END_ of _TOTAL_",
                zeroRecords: "No matching records found",
                infoEmpty: "No records available",
                emptyTable: options.emptyTable || "<div class='datatable-empty-state'><div class='datatable-empty-icon'>📭</div><p>No data available</p></div>",
                infoFiltered: "(filtered from _MAX_ total)",
                paginate: {
                    first: '«',
                    last: '»',
                    next: '›',
                    previous: '‹'
                }
            },
            columnDefs: options.columnDefs || [],
            initComplete: function() {
                const searchInput = $('.dataTables_filter input');
                searchInput.addClass('form-control');
                searchInput.css({
                    'width': '200px',
                    'display': 'inline-block'
                });
            }
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // User Management Table
    if ($('#userManagementTable').length) {
        initDataTable('#userManagementTable', {
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search users...",
                lengthMenu: "Show _MENU_ users",
                info: "Showing _START_ to _END_ of _TOTAL_ users",
                zeroRecords: "No matching users found",
                infoEmpty: "No users available",
                emptyTable: "<div class='datatable-empty-state'><div class='datatable-empty-icon'>📭</div><p>No users found</p></div>",
                infoFiltered: "(filtered from _MAX_ total users)",
                paginate: {
                    first: '«',
                    last: '»',
                    next: '›',
                    previous: '‹'
                }
            },
            columnDefs: [
                { 
                    targets: 1, // Profile image column
                    searchable: false,
                    orderable: false
                },
                { 
                    targets: 6, // Actions column
                    searchable: false,
                    orderable: false
                }
            ]
        });
    }
    
    // Listing Management Table
    if ($('#listingManagementTable').length) {
        initDataTable('#listingManagementTable', {
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search listings...",
                lengthMenu: "Show _MENU_ listings",
                info: "Showing _START_ to _END_ of _TOTAL_ listings",
                zeroRecords: "No matching listings found",
                infoEmpty: "No listings available",
                emptyTable: "<div class='datatable-empty-state'><div class='datatable-empty-icon'>📭</div><p>No listings found</p></div>",
                infoFiltered: "(filtered from _MAX_ total listings)",
                paginate: {
                    first: '«',
                    last: '»',
                    next: '›',
                    previous: '‹'
                }
            },
            columnDefs: [
                { 
                    targets: 4, // Actions column
                    searchable: false,
                    orderable: false
                }
            ]
        });
    }
    
    // Collectible Management Table
    if ($('#collectibleManagementTable').length) {
        initDataTable('#collectibleManagementTable', {
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search collectibles...",
                lengthMenu: "Show _MENU_ collectibles",
                info: "Showing _START_ to _END_ of _TOTAL_ collectibles",
                zeroRecords: "No matching collectibles found",
                infoEmpty: "No collectibles available",
                emptyTable: "<div class='datatable-empty-state'><div class='datatable-empty-icon'>📭</div><p>No collectibles found</p></div>",
                infoFiltered: "(filtered from _MAX_ total collectibles)",
                paginate: {
                    first: '«',
                    last: '»',
                    next: '›',
                    previous: '‹'
                }
            },
            columnDefs: [
                { 
                    targets: 1, // Image column
                    searchable: false,
                    orderable: false
                },
                { 
                    targets: 5, // Actions column
                    searchable: false,
                    orderable: false
                }
            ]
        });
    }
    
    // Dashboard Tables (Pending Orders)
    if ($('#pendingOrdersTable').length) {
        initDataTable('#pendingOrdersTable', {
            pageLength: 5,
            lengthChange: false,
            lengthMenu: false,
            order: [[0, 'desc']],
            searchPlaceholder: "Search pending orders...",
            emptyTable: "<div class='datatable-empty-state'><div class='datatable-empty-icon'>📭</div><p>No pending orders</p></div>",
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search pending orders...",
                info: "Showing _START_ to _END_ of _TOTAL_ pending orders",
                zeroRecords: "No matching pending orders found",
                infoEmpty: "No pending orders available",
                emptyTable: "<div class='datatable-empty-state'><div class='datatable-empty-icon'>📭</div><p>No pending orders</p></div>",
                infoFiltered: "(filtered from _MAX_ total pending orders)",
                paginate: {
                    first: '«',
                    last: '»',
                    next: '›',
                    previous: '‹'
                }
            },
            columnDefs: [
                { 
                    targets: 7, // Actions column
                    searchable: false,
                    orderable: false
                },
                { 
                    targets: [0, 4, 5, 6],
                    orderable: true
                }
            ]
        });
    }
    
    // Dashboard Tables (Recent Users)
    if ($('#recentUsersTable').length) {
        initDataTable('#recentUsersTable', {
            pageLength: 5,
            lengthChange: false,
            lengthMenu: false,
            order: [[2, 'desc']],
            searchPlaceholder: "Search users...",
            emptyTable: "<div class='datatable-empty-state'><div class='datatable-empty-icon'>📭</div><p>No recent users</p></div>",
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search users...",
                info: "Showing _START_ to _END_ of _TOTAL_ users",
                zeroRecords: "No matching users found",
                infoEmpty: "No users available",
                emptyTable: "<div class='datatable-empty-state'><div class='datatable-empty-icon'>📭</div><p>No recent users</p></div>",
                infoFiltered: "(filtered from _MAX_ total users)",
                paginate: {
                    first: '«',
                    last: '»',
                    next: '›',
                    previous: '‹'
                }
            },
            columnDefs: [
                { 
                    targets: 1,
                    orderable: true
                },
                { 
                    targets: 2,
                    orderable: true
                }
            ]
        });
    }
    
    // Order Management Table (full orders page)
    if ($('#orderManagementTable').length) {
        initDataTable('#orderManagementTable', {
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search orders...",
                lengthMenu: "Show _MENU_ orders",
                info: "Showing _START_ to _END_ of _TOTAL_ orders",
                zeroRecords: "No matching orders found",
                infoEmpty: "No orders available",
                emptyTable: "<div class='datatable-empty-state'><div class='datatable-empty-icon'>📭</div><p>No orders found</p></div>",
                infoFiltered: "(filtered from _MAX_ total orders)",
                paginate: {
                    first: '«',
                    last: '»',
                    next: '›',
                    previous: '‹'
                }
            },
            columnDefs: [
                { 
                    targets: 6, // Actions column
                    searchable: false,
                    orderable: false
                },
                { 
                    targets: [4], // Price column
                    orderable: true
                },
                { 
                    targets: [5], // Status column
                    orderable: true
                }
            ]
        });
    }
});